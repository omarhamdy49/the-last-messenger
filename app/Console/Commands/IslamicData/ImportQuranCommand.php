<?php

declare(strict_types=1);

namespace App\Console\Commands\IslamicData;

use App\Domain\Quran\Models\Surah;
use App\Domain\Quran\Models\TranslationResource;
use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Models\VerseTafsir;
use App\Domain\Quran\Models\VerseTranslation;
use Illuminate\Console\Command;

class ImportQuranCommand extends Command
{
    protected $signature = 'islamic-data:import-quran
                            {--source= : Override path to the Quran JSON file}
                            {--fresh : Truncate Quran tables before importing (asks for confirmation)}
                            {--dry-run : Validate the source and report counts without writing}
                            {--skip-translations : Skip Tanzil translation datasets}
                            {--skip-tafsir : Skip the Muyassar tafsir dataset}';

    protected $description = 'Import the full Quran (surahs, verses, translations, tafsir) — idempotent, safe to re-run';

    public function handle(): int
    {
        $source = $this->option('source') ?: config('islamic-data.quran_json');

        if (! is_file($source)) {
            $this->error("Quran source file not found: {$source}");

            return self::FAILURE;
        }

        $parsed = $this->parseSource($source);
        if ($parsed === null) {
            return self::FAILURE;
        }

        [$surahCount, $ayahCount] = $parsed;
        $this->info("Source: {$source}");
        $this->info("Source contains {$surahCount} surahs / {$ayahCount} ayahs.");

        if ($this->option('dry-run')) {
            return $this->reportDryRun($surahCount, $ayahCount);
        }

        $fresh = (bool) $this->option('fresh');
        if ($fresh && ! $this->confirm('--fresh will TRUNCATE quran surah/verse tables before import. Continue?')) {
            $this->info('Cancelled.');

            return self::SUCCESS;
        }

        $this->line('Step 1/4: surahs');
        if ($this->call('quran:import-json', ['file' => $source, '--type' => 'surahs', '--truncate' => $fresh]) !== self::SUCCESS) {
            return self::FAILURE;
        }

        $this->line('Step 2/4: verses + Arabic text');
        if ($this->call('quran:import-json', ['file' => $source, '--type' => 'verses', '--truncate' => $fresh]) !== self::SUCCESS) {
            return self::FAILURE;
        }

        if (! $this->option('skip-translations')) {
            $this->line('Step 3/4: translations (en, fr, tr, ur, transliteration)');
            if ($this->call('quran:import-translations') !== self::SUCCESS) {
                return self::FAILURE;
            }
        }

        if (! $this->option('skip-tafsir')) {
            $this->line('Step 4/4: tafsir (Muyassar, ar)');
            if ($this->call('quran:import-tafsir') !== self::SUCCESS) {
                return self::FAILURE;
            }
        }

        $this->displayFinalCounts($surahCount, $ayahCount);

        return self::SUCCESS;
    }

    /**
     * @return array{0: int, 1: int}|null [surahCount, ayahCount] or null on invalid structure
     */
    private function parseSource(string $source): ?array
    {
        $decoded = json_decode((string) file_get_contents($source), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Invalid JSON: '.json_last_error_msg());

            return null;
        }

        $surahs = $decoded['data']['surahs'] ?? null;
        if (! is_array($surahs) || $surahs === []) {
            $this->error('Unexpected structure: data.surahs missing — expected alquran.cloud format.');

            return null;
        }

        $ayahCount = array_sum(array_map(
            static fn (array $surah): int => count($surah['ayahs'] ?? []),
            $surahs,
        ));

        return [count($surahs), $ayahCount];
    }

    private function reportDryRun(int $surahCount, int $ayahCount): int
    {
        $this->warn('Dry run — nothing written.');
        $this->table(['Metric', 'Source', 'Currently in DB'], [
            ['Surahs', $surahCount, Surah::count()],
            ['Verses', $ayahCount, Verse::count()],
            ['Verse translations', '—', VerseTranslation::count()],
            ['Tafsir rows', '—', VerseTafsir::count()],
        ]);

        return self::SUCCESS;
    }

    private function displayFinalCounts(int $surahCount, int $ayahCount): void
    {
        $defaultTranslationsByLang = VerseTranslation::query()
            ->join('quran_translation_resources', 'quran_translation_resources.id', '=', 'quran_verse_translations.resource_id')
            ->where('quran_translation_resources.is_default', true)
            ->selectRaw('quran_verse_translations.lang, COUNT(*) AS row_count')
            ->groupBy('quran_verse_translations.lang')
            ->pluck('row_count', 'lang')
            ->all();

        $resourcesByLang = TranslationResource::query()
            ->selectRaw('language, COUNT(*) AS resource_count')
            ->groupBy('language')
            ->pluck('resource_count', 'language')
            ->all();

        $rows = [
            ['Surahs', Surah::count(), $surahCount],
            ['Verses', Verse::count(), $ayahCount],
            ['Tafsir rows', VerseTafsir::count(), '—'],
        ];
        foreach ($defaultTranslationsByLang as $lang => $rowCount) {
            $rows[] = ["Default translations [{$lang}]", $rowCount, $ayahCount];
        }
        foreach ($resourcesByLang as $lang => $resourceCount) {
            $rows[] = ["Translation resources [{$lang}]", $resourceCount, '—'];
        }

        $this->newLine();
        $this->info('Final counts');
        $this->table(['Metric', 'In DB', 'Expected'], $rows);
        $this->line('Run `php artisan islamic-data:verify` for the full integrity report.');
    }
}
