<?php

declare(strict_types=1);

namespace App\Console\Commands\IslamicData;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Quran\Models\Surah;
use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Models\VerseTafsir;
use App\Domain\Quran\Models\VerseTranslation;
use Illuminate\Console\Command;

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

        $translationsByLang = VerseTranslation::query()
            ->selectRaw('lang, COUNT(*) AS row_count')
            ->groupBy('lang')
            ->pluck('row_count', 'lang')
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
            $rowCount = (int) ($translationsByLang[$lang] ?? 0);
            $rows[] = $this->check("Translations [{$lang}]", $rowCount, "= {$expectedVerses}", $rowCount === $expectedVerses);
        }

        $this->table(['Check', 'Actual', 'Expected', 'Status'], $rows);
    }

    private function verifyHadith(): void
    {
        $this->info('Hadith (DB vs production source files)');

        $source = $this->option('source') ?: config('islamic-data.hadith_production_dir');
        $map = (array) config('islamic-data.hadith_collection_map');
        $floors = (array) config('islamic-data.expected_hadiths');

        $dbCounts = HadithCollection::query()
            ->leftJoin('hadith_books', 'hadith_books.collection_id', '=', 'hadith_collections.id')
            ->leftJoin('hadith_hadiths', 'hadith_hadiths.book_id', '=', 'hadith_books.id')
            ->selectRaw('hadith_collections.source_slug, COUNT(hadith_hadiths.id) AS hadith_count')
            ->groupBy('hadith_collections.source_slug')
            ->pluck('hadith_count', 'source_slug')
            ->all();

        $rows = [];
        $totalFloor = 0;
        $totalDb = 0;

        foreach ($map as $directory => $sourceSlug) {
            $dirPath = rtrim((string) $source, '/')."/{$directory}";
            $fileCount = is_dir($dirPath) ? count(glob($dirPath.'/*.json') ?: []) : 0;
            $floor = (int) ($floors[$directory] ?? 0);
            $dbCount = (int) ($dbCounts[$sourceSlug] ?? 0);

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
            ->whereDoesntHave('translations', fn ($query) => $query->where('language_code', 'ar'))
            ->count();
        $rows[] = $this->check(
            'Hadiths missing Arabic text',
            $hadithsMissingArabic,
            "<= {$knownMissingArabic} (known source gap)",
            $hadithsMissingArabic <= $knownMissingArabic,
        );

        $orphanHadiths = Hadith::query()->whereNull('book_id')->count();
        $rows[] = $this->check('Hadiths without a book', $orphanHadiths, '= 0', $orphanHadiths === 0);

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
