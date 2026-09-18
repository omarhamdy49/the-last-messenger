<?php

declare(strict_types=1);

namespace App\Domain\Quran\Import\QuranFoundation\Importers;

use App\Domain\Quran\DTOs\ImportReport;
use App\Domain\Quran\Enums\SectionType;
use App\Domain\Quran\Import\QuranFoundation\Endpoints;
use App\Domain\Quran\Import\QuranFoundation\Exceptions\QfPayloadException;
use App\Domain\Quran\Import\QuranFoundation\Mappers\StructureMapper;
use App\Domain\Quran\Models\Chapter;
use App\Domain\Quran\Repositories\ImportRepositoryInterface;

/**
 * Chapters (+ names in the five site locales), the five section axes, the default
 * mushaf's pages and the content languages.
 */
final class StructureImporter
{
    /** Site locales whose chapter names are imported. */
    public const LOCALES = ['ar', 'en', 'fr', 'tr', 'ur'];

    /** QF `translated_name.language_name` per site locale. */
    private const LANGUAGE_NAMES = ['en' => 'english', 'fr' => 'french', 'tr' => 'turkish', 'ur' => 'urdu'];

    /** Real QF totals (checked against production 2026-09-17). */
    public const MIN_LANGUAGES = 50;

    public function __construct(
        private readonly Endpoints $qf,
        private readonly ImportRepositoryInterface $repository,
    ) {}

    public function run(bool $dryRun = false): ImportReport
    {
        $report = new ImportReport('structure');

        $this->chapters($report, $dryRun);
        $this->sections($report, $dryRun);
        $this->pages($report, $dryRun);
        $this->languages($report, $dryRun);

        return $report;
    }

    private function chapters(ImportReport $report, bool $dryRun): void
    {
        $payload = $this->qf->chapters('en');
        $items = $this->list($payload, 'chapters', 'chapters');
        $report->expected += count($items);

        $rows = array_map(StructureMapper::chapter(...), $items);
        $report->gate(count($rows) === Chapter::COUNT, sprintf('chapters: QF returned %d of %d', count($rows), Chapter::COUNT));

        $translations = [];
        foreach (self::LOCALES as $locale) {
            // QF translates almost no chapter names into Arabic (2 of 114 on
            // 2026-09-17) and falls back to English; its own `name_arabic` is the
            // authoritative Arabic name.
            if ($locale === 'ar') {
                foreach ($rows as $row) {
                    $translations[] = ['chapter_id' => $row['id'], 'locale' => 'ar', 'name' => $row['name_arabic'], 'is_ready' => true];
                }

                continue;
            }

            $expectedLanguage = self::LANGUAGE_NAMES[$locale];
            $localized = $locale === 'en' ? $items : $this->list($this->qf->chapters($locale), 'chapters', "chapters?language={$locale}");
            $fallbacks = 0;
            foreach ($localized as $item) {
                $name = StructureMapper::chapterTranslatedName($item);
                $language = strtolower((string) ($item['translated_name']['language_name'] ?? ''));
                if ($name === null || $language !== $expectedLanguage) {
                    // An English fallback is not a translation: the reader falls back itself.
                    $fallbacks++;

                    continue;
                }
                $translations[] = ['chapter_id' => (int) $item['id'], 'locale' => $locale, 'name' => $name, 'is_ready' => true];
            }
            if ($fallbacks > 0) {
                $report->note("chapter names ({$locale}): {$fallbacks} not translated by QF");
            }
        }

        if ($dryRun) {
            $report->note('dry run: '.count($rows).' chapters, '.count($translations).' chapter names not written');

            return;
        }

        $report->add($this->repository->upsert('quran_chapters', $rows, ['id'],
            ['revelation_place', 'revelation_order', 'bismillah_pre', 'name_simple', 'name_complex', 'name_arabic', 'verses_count', 'first_page', 'last_page']));
        $report->add($this->repository->upsert('quran_chapter_translations', $translations, ['chapter_id', 'locale'], ['name', 'is_ready']));

        $stored = $this->repository->count('quran_chapters');
        $report->gate($stored === Chapter::COUNT, "chapters stored: {$stored} of ".Chapter::COUNT);
        $names = $this->repository->count('quran_chapter_translations');
        $report->gate($names === count($translations), "chapter names stored: {$names} of ".count($translations));
    }

    private function sections(ImportReport $report, bool $dryRun): void
    {
        foreach (SectionType::cases() as $type) {
            $items = $this->list($this->qf->sections($type), $type->qfPath(), $type->qfPath());
            $rows = StructureMapper::sections($type, $items);
            $report->expected += count($rows);
            $report->gate(count($rows) === $type->max(), sprintf('%s: QF returned %d distinct of %d', $type->qfPath(), count($rows), $type->max()));

            if ($dryRun) {
                continue;
            }

            $report->add($this->repository->upsert('quran_sections', $rows, ['type', 'number'],
                ['surah_ruku_number', 'first_verse_id', 'last_verse_id', 'verses_count', 'verse_mapping']));

            $stored = $this->repository->count('quran_sections', ['type' => $type->value]);
            $report->gate($stored === $type->max(), "{$type->qfPath()} stored: {$stored} of {$type->max()}");
        }
    }

    private function pages(ImportReport $report, bool $dryRun): void
    {
        $mushafId = (int) config('quran.mushaf.default_id', 1);
        $items = $this->list($this->qf->pages($mushafId), 'pages', 'pages');
        $report->expected += count($items);

        if ($dryRun) {
            return;
        }

        if ($this->repository->count('quran_mushafs', ['id' => $mushafId]) === 0) {
            $report->fail("mushaf:{$mushafId}", 'default mushaf row missing — run `php artisan db:seed --class=QuranMushafSeeder` first');

            return;
        }

        $rows = array_map(static fn (array $item): array => StructureMapper::page($mushafId, $item), $items);
        $report->add($this->repository->upsert('quran_mushaf_pages', $rows, ['mushaf_id', 'page_number'],
            ['first_verse_id', 'last_verse_id', 'first_word_id', 'last_word_id', 'verses_count', 'verse_mapping']));

        $stored = $this->repository->count('quran_mushaf_pages', ['mushaf_id' => $mushafId]);
        $report->gate($stored === count($items), "pages stored for mushaf {$mushafId}: {$stored} of ".count($items));
    }

    private function languages(ImportReport $report, bool $dryRun): void
    {
        $items = $this->list($this->qf->languages(), 'languages', 'resources/languages');
        $report->expected += count($items);
        $report->gate(count($items) >= self::MIN_LANGUAGES, sprintf('languages: QF returned %d (expected at least %d)', count($items), self::MIN_LANGUAGES));

        if ($dryRun) {
            return;
        }

        $rows = array_map(StructureMapper::language(...), $items);
        $report->add($this->repository->upsert('quran_languages', $rows, ['id'], ['name', 'native_name', 'iso_code', 'direction', 'translations_count']));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array<string, mixed>>
     */
    private function list(array $payload, string $key, string $endpoint): array
    {
        if (! isset($payload[$key]) || ! is_array($payload[$key])) {
            throw new QfPayloadException($endpoint, $key);
        }

        return array_values($payload[$key]);
    }
}
