<?php

declare(strict_types=1);

namespace App\Domain\Quran\Import\QuranFoundation\Importers;

use App\Domain\Quran\DTOs\ImportReport;
use App\Domain\Quran\Enums\CharType;
use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Enums\ResourceSource;
use App\Domain\Quran\Import\QuranFoundation\Endpoints;
use App\Domain\Quran\Import\QuranFoundation\Exceptions\QfPayloadException;
use App\Domain\Quran\Import\QuranFoundation\Mappers\VerseMapper;
use App\Domain\Quran\Models\Chapter;
use App\Domain\Quran\Models\WordGloss;
use App\Domain\Quran\Repositories\ImportRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * Words (glyphs, per-script text, default-mushaf position, per-word audio) and their
 * word-by-word glosses, from `/verses/by_chapter/{n}?words=true`.
 *
 * Glosses come per request language and QF silently falls back to English when a
 * language has no gloss, so a gloss is stored only when the language QF reports
 * matches the one requested. Transliteration is English-only in QF and is stored once.
 *
 * Glosses are written under QF-sourced resources `qf-wbw-translation-{iso}` and
 * `qf-wbw-transliteration-en`; a word_by_word sync snapshot, when enabled, replaces
 * them with the curated QF resource.
 */
final class WordsImporter
{
    private const WORD_FIELDS = 'text_uthmani,text_uthmani_simple,text_indopak,text_imlaei,text_qpc_hafs,code_v1,code_v2,v1_page,v2_page,page_number,line_number,audio_url,location,char_type_name';

    private const UPDATE_COLUMNS = [
        'verse_id', 'position', 'location', 'char_type', 'text_uthmani', 'text_uthmani_simple', 'text_indopak', 'text_imlaei',
        'text_qpc_hafs', 'code_v1', 'code_v2', 'v1_page', 'v2_page', 'page_number', 'line_number', 'audio_url',
    ];

    /** Real production word count (mushaf 1 snapshot, 2026-09-17). */
    public const MIN_TOTAL_WORDS = 77_430;

    public function __construct(
        private readonly Endpoints $qf,
        private readonly ImportRepositoryInterface $repository,
    ) {}

    /**
     * @param  list<string>  $languages  iso codes; the first also writes the words themselves
     */
    public function run(int $from = 1, int $to = Chapter::COUNT, array $languages = ['en', 'ar', 'fr', 'tr', 'ur'], bool $dryRun = false): ImportReport
    {
        $report = new ImportReport('words');
        $languages = array_values(array_unique(array_map('strtolower', $languages)));
        if ($languages === []) {
            $languages = ['en'];
        }

        $verseIds = DB::table('quran_verses')->whereBetween('chapter_id', [$from, $to])->pluck('id', 'verse_key')->all();
        if ($verseIds === []) {
            $report->fail('verses', 'no verses for the range — run quran:qf:import-verses first');

            return $report;
        }

        $languageNames = DB::table('quran_languages')->pluck('name', 'iso_code')
            ->map(static fn (string $name): string => strtolower($name))->all();
        $languageNames += ['en' => 'english', 'ar' => 'arabic', 'fr' => 'french', 'tr' => 'turkish', 'ur' => 'urdu'];

        $transliterationResourceId = null;
        foreach ($languages as $index => $language) {
            $translationResourceId = $dryRun ? 0 : $this->glossResource(ResourceKind::WORD_TRANSLATION, $language);
            if (! $dryRun && $transliterationResourceId === null) {
                $transliterationResourceId = $this->glossResource(ResourceKind::WORD_TRANSLITERATION, 'en');
            }

            for ($chapter = $from; $chapter <= $to; $chapter++) {
                [$words, $translations, $transliterations] = $this->fetchChapter($chapter, $language, $languageNames[$language] ?? $language, $verseIds, $report);

                if ($index === 0) {
                    $report->expected += count($words);
                }

                if ($dryRun) {
                    continue;
                }

                if ($index === 0) {
                    $report->add($this->repository->upsert('quran_words', $words, ['id'], self::UPDATE_COLUMNS));
                }

                $this->writeGlosses($report, $translations, $translationResourceId, WordGloss::KIND_TRANSLATION, $language);
                if ($transliterationResourceId !== null && $language === 'en') {
                    $this->writeGlosses($report, $transliterations, $transliterationResourceId, WordGloss::KIND_TRANSLITERATION, 'en');
                }
            }

            if (! $dryRun) {
                $this->refreshCount($translationResourceId);
            }
        }

        if (! $dryRun && $transliterationResourceId !== null) {
            $this->refreshCount($transliterationResourceId);
        }

        if (! $dryRun) {
            $this->gates($report, $from, $to);
        }

        return $report;
    }

    /**
     * @param  array<string, int>  $verseIds
     * @return array{0: list<array<string, mixed>>, 1: array<int, string>, 2: array<int, string>}
     */
    private function fetchChapter(int $chapter, string $language, string $languageName, array $verseIds, ImportReport $report): array
    {
        $words = [];
        $translations = [];
        $transliterations = [];
        $page = 1;

        do {
            $payload = $this->qf->versesByChapter($chapter, [
                'words' => 'true',
                'language' => $language,
                'word_fields' => self::WORD_FIELDS,
                'per_page' => 50,
                'page' => $page,
            ]);
            if (! isset($payload['verses']) || ! is_array($payload['verses'])) {
                throw new QfPayloadException("verses/by_chapter/{$chapter}", 'verses');
            }

            foreach ($payload['verses'] as $verse) {
                $key = (string) ($verse['verse_key'] ?? '');
                $verseId = $verseIds[$key] ?? null;
                if ($verseId === null) {
                    $report->fail("verse:{$key}", 'verse not imported');

                    continue;
                }

                foreach ($verse['words'] ?? [] as $qfWord) {
                    $row = VerseMapper::word($qfWord, $verseId);
                    $words[] = $row;

                    $gloss = VerseMapper::glosses($qfWord);
                    $translationLanguage = strtolower((string) ($qfWord['translation']['language_name'] ?? ''));
                    if ($gloss['translation'] !== null && $translationLanguage === $languageName) {
                        $translations[$row['id']] = $gloss['translation'];
                    }
                    if ($gloss['transliteration'] !== null) {
                        $transliterations[$row['id']] = $gloss['transliteration'];
                    }
                }
            }

            $next = $payload['pagination']['next_page'] ?? null;
            $page = is_numeric($next) ? (int) $next : 0;
        } while ($page > 0);

        return [$words, $translations, $transliterations];
    }

    /**
     * @param  array<int, string>  $glosses  word id => text
     */
    private function writeGlosses(ImportReport $report, array $glosses, int $resourceId, string $kind, string $language): void
    {
        if ($glosses === []) {
            return;
        }

        $rows = [];
        foreach ($glosses as $wordId => $text) {
            $rows[] = [
                'word_id' => $wordId,
                'resource_id' => $resourceId,
                'kind' => $kind,
                'language_iso' => $language,
                'text' => mb_substr($text, 0, 500),
            ];
        }

        $counts = $this->repository->upsert('quran_word_glosses', $rows, ['word_id', 'resource_id'], ['kind', 'language_iso', 'text']);
        $report->note(sprintf('%s glosses (%s): %d new, %d updated, %d unchanged', $kind, $language, $counts['written'], $counts['updated'], $counts['unchanged']));
    }

    /**
     * The QF-sourced catalogue row that owns derived glosses for one language.
     * Created inactive; an editor activates it.
     */
    private function glossResource(ResourceKind $kind, string $language): int
    {
        $prefix = $kind === ResourceKind::WORD_TRANSLATION ? 'qf-wbw-translation-' : 'qf-wbw-transliteration-';
        $slug = $prefix.$language;

        $existing = DB::table('quran_resources')->where('kind', $kind->value)->where('slug', $slug)->value('id');
        if ($existing !== null) {
            return (int) $existing;
        }

        $label = $kind === ResourceKind::WORD_TRANSLATION ? 'Word by word translation' : 'Word by word transliteration';

        return (int) DB::table('quran_resources')->insertGetId([
            'kind' => $kind->value,
            'source' => ResourceSource::QF->value,
            'slug' => $slug,
            'name' => "{$label} ({$language})",
            'language_iso' => $language,
            'is_active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function refreshCount(int $resourceId): void
    {
        DB::table('quran_resources')->where('id', $resourceId)->update([
            'records_count' => DB::table('quran_word_glosses')->where('resource_id', $resourceId)->count(),
            'last_synced_at' => now(),
        ]);
    }

    private function gates(ImportReport $report, int $from, int $to): void
    {
        $withoutWords = DB::table('quran_verses as v')
            ->leftJoin('quran_words as w', 'w.verse_id', '=', 'v.id')
            ->whereBetween('v.chapter_id', [$from, $to])
            ->whereNull('w.id')
            ->count();
        $report->gate($withoutWords === 0, "verses without words: {$withoutWords}");

        $badEnds = DB::table('quran_verses as v')
            ->whereBetween('v.chapter_id', [$from, $to])
            ->whereRaw('(select count(*) from quran_words w where w.verse_id = v.id and w.char_type = ?) <> 1', [CharType::END->value])
            ->count();
        $report->gate($badEnds === 0, "verses without exactly one verse-end mark: {$badEnds}");

        if ($from === 1 && $to === Chapter::COUNT) {
            $total = DB::table('quran_words')->count();
            $report->gate($total >= self::MIN_TOTAL_WORDS, "words stored: {$total} (expected at least ".self::MIN_TOTAL_WORDS.')');
        }
    }
}
