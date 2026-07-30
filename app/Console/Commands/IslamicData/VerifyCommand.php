<?php

declare(strict_types=1);

namespace App\Console\Commands\IslamicData;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Quran\Models\Surah;
use App\Domain\Quran\Models\TranslationResource;
use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Models\VerseTafsir;
use App\Domain\Quran\Models\VerseTranslation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerifyCommand extends Command
{
    protected $signature = 'islamic-data:verify
                            {--source= : Override path to the hadith production directory}';

    protected $description = 'Verify imported Quran/Hadith data: source-vs-DB coverage and integrity checks';

    private bool $failed = false;

    public function handle(): int
    {
        $this->verifyQuran();
        $this->newLine();
        $this->verifyHadith();
        $this->newLine();

        if ($this->failed) {
            $this->error('VERIFICATION FAILED — see ✗ rows above.');

            return self::FAILURE;
        }

        $this->info('VERIFICATION PASSED — all coverage and integrity checks hold.');

        return self::SUCCESS;
    }

    private function verifyQuran(): void
    {
        $this->info('Quran');

        $expectedSurahs = (int) config('islamic-data.expected.surahs');
        $expectedVerses = (int) config('islamic-data.expected.verses');

        $surahs = Surah::count();
        $verses = Verse::count();

        $defaultTranslationsByLang = VerseTranslation::query()
            ->join('quran_translation_resources', 'quran_translation_resources.id', '=', 'quran_verse_translations.resource_id')
            ->where('quran_translation_resources.is_default', true)
            ->selectRaw('quran_verse_translations.lang, COUNT(*) AS row_count')
            ->groupBy('quran_verse_translations.lang')
            ->pluck('row_count', 'lang')
            ->all();

        $defaultResourcesByLang = TranslationResource::query()
            ->where('is_default', true)
            ->selectRaw('language, COUNT(*) AS resource_count')
            ->groupBy('language')
            ->pluck('resource_count', 'language')
            ->all();

        $versesMissingArabic = Verse::query()
            ->whereDoesntHave('translations', fn ($query) => $query->where('lang', 'ar'))
            ->count();

        $verseCountMismatch = Surah::query()
            ->whereRaw('verses_count <> (SELECT COUNT(*) FROM quran_verses WHERE quran_verses.surah_id = quran_surahs.id)')
            ->count();

        $rows = [
            $this->check('Surahs', $surahs, "= {$expectedSurahs}", $surahs === $expectedSurahs),
            $this->check('Verses', $verses, "= {$expectedVerses}", $verses === $expectedVerses),
            $this->check('Verses missing Arabic text', $versesMissingArabic, '= 0', $versesMissingArabic === 0),
            $this->check('Surahs whose verses_count ≠ actual verses', $verseCountMismatch, '= 0', $verseCountMismatch === 0),
            $this->check('Tafsir rows', VerseTafsir::count(), '> 0', VerseTafsir::count() > 0),
        ];

        foreach (['en', 'fr', 'tr', 'ur'] as $lang) {
            $defaultResources = (int) ($defaultResourcesByLang[$lang] ?? 0);
            $rowCount = (int) ($defaultTranslationsByLang[$lang] ?? 0);
            $rows[] = $this->check("Default translation resources [{$lang}]", $defaultResources, '= 1', $defaultResources === 1);
            $rows[] = $this->check("Default translations [{$lang}]", $rowCount, "= {$expectedVerses}", $rowCount === $expectedVerses);
        }

        $nullResourceTranslations = VerseTranslation::query()->whereNull('resource_id')->count();
        $rows[] = $this->check('Translations without resource', $nullResourceTranslations, '= 0', $nullResourceTranslations === 0);

        $duplicateVerseResourceRows = DB::query()
            ->fromSub(
                DB::table('quran_verse_translations')
                    ->select('verse_id', 'resource_id')
                    ->groupBy('verse_id', 'resource_id')
                    ->havingRaw('COUNT(*) > 1'),
                'duplicate_translations',
            )
            ->count();
        $rows[] = $this->check('Duplicate verse/resource translations', $duplicateVerseResourceRows, '= 0', $duplicateVerseResourceRows === 0);

        $incompleteActiveResources = DB::query()
            ->fromSub(
                DB::table('quran_translation_resources as r')
                    ->leftJoin('quran_verse_translations as t', 't.resource_id', '=', 'r.id')
                    ->where('r.is_active', true)
                    ->selectRaw('r.id, COUNT(DISTINCT t.verse_id) AS verse_count')
                    ->groupBy('r.id')
                    ->havingRaw('COUNT(DISTINCT t.verse_id) <> ?', [$expectedVerses]),
                'incomplete_resources',
            )
            ->count();
        $rows[] = $this->check('Incomplete active translation resources', $incompleteActiveResources, '= 0', $incompleteActiveResources === 0);

        $this->table(['Check', 'Actual', 'Expected', 'Status'], $rows);
    }

    private function verifyHadith(): void
    {
        $this->info('Hadith (DB vs production source files)');

        $source = $this->option('source') ?: config('islamic-data.hadith_production_dir');
        $map = (array) config('islamic-data.hadith_collection_map');
        $floors = (array) config('islamic-data.expected_hadiths');

        // Counted straight off the denormalised collection_id: chapter_id is NULL
        // wherever has_chapters = 0, so a join through chapters would report those
        // collections as empty.
        $dbCounts = HadithCollection::query()
            ->leftJoin('hadith_hadiths', 'hadith_hadiths.collection_id', '=', 'hadith_collections.id')
            ->selectRaw('hadith_collections.code, COUNT(hadith_hadiths.id) AS hadith_count')
            ->groupBy('hadith_collections.code')
            ->pluck('hadith_count', 'code')
            ->all();

        $rows = [];
        $totalFloor = 0;
        $totalDb = 0;

        // The production directory name is also the collection `code`.
        foreach ($map as $directory => $sourceSlug) {
            $dirPath = rtrim((string) $source, '/')."/{$directory}";
            $fileCount = is_dir($dirPath) ? count(glob($dirPath.'/*.json') ?: []) : 0;
            $floor = (int) ($floors[$directory] ?? 0);
            $dbCount = (int) ($dbCounts[$directory] ?? 0);

            $totalFloor += $floor;
            $totalDb += $dbCount;

            $rows[] = $this->check(
                "{$directory} ({$fileCount} files)",
                "{$dbCount} in DB",
                ">= {$floor}",
                $floor > 0 && $dbCount >= $floor,
            );
        }

        $rows[] = $this->check('TOTAL', "{$totalDb} in DB", ">= {$totalFloor}", $totalDb >= $totalFloor);

        // 125 Malik source files are English-only — a documented source gap, not data loss.
        $knownMissingArabic = (int) config('islamic-data.known_missing_arabic');
        $hadithsMissingArabic = Hadith::query()
            ->whereDoesntHave('texts', fn ($query) => $query->where('locale', 'ar'))
            ->count();
        $rows[] = $this->check(
            'Hadiths missing Arabic text',
            $hadithsMissingArabic,
            "<= {$knownMissingArabic} (known source gap)",
            $hadithsMissingArabic <= $knownMissingArabic,
        );

        // No "hadiths without a book" check: book_id is NOT NULL behind a composite
        // foreign key, so the database cannot hold one. A check that can never fail
        // reads as coverage without providing any.

        // Chapter linkage floor recorded after the 2026-06 chapter_id backfill.
        // Some source files legitimately carry no chapter info (e.g. qudsi40),
        // so the floor is the achieved coverage, not the total hadith count.
        $chapterLinkedFloor = (int) config('islamic-data.expected_chapter_linked');
        $chapterLinked = Hadith::query()->whereNotNull('chapter_id')->count();
        $rows[] = $this->check(
            'Hadiths linked to a chapter',
            $chapterLinked,
            ">= {$chapterLinkedFloor}",
            $chapterLinked >= $chapterLinkedFloor,
        );

        $this->table(['Check', 'Actual', 'Expected', 'Status'], $rows);
    }

    /**
     * @return array{0: string, 1: int|string, 2: string, 3: string}
     */
    private function check(string $label, int|string $actual, string $expected, bool $passes): array
    {
        if (! $passes) {
            $this->failed = true;
        }

        return [$label, $actual, $expected, $passes ? '✓' : '✗'];
    }
}
