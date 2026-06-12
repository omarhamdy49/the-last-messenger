<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Quran\Services\QuranTextDatasetImportService;
use Illuminate\Console\Command;

class ImportQuranTranslationsCommand extends Command
{
    protected $signature = 'quran:import-translations
                            {--path=database/data/quran : Directory holding the Tanzil text files}
                            {--lang=* : Limit to specific languages (en, fr, tr, ur, en-tl)}';

    protected $description = 'Import Quran verse translations (Tanzil text format) into quran_verse_translations — idempotent, keyed by (verse_id, lang)';

    /**
     * Language code => Tanzil dataset file name.
     * 'en-tl' stores the English transliteration as an extra lang row.
     *
     * @var array<string, string>
     */
    private const DATASETS = [
        'en' => 'en.sahih.txt',
        'fr' => 'fr.hamidullah.txt',
        'tr' => 'tr.diyanet.txt',
        'ur' => 'ur.jalandhry.txt',
        'en-tl' => 'en.transliteration.txt',
    ];

    public function __construct(
        private readonly QuranTextDatasetImportService $importService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $directory = base_path($this->option('path'));
        $requestedLangs = (array) $this->option('lang');
        $langs = $requestedLangs === [] ? array_keys(self::DATASETS) : $requestedLangs;

        $unknown = array_diff($langs, array_keys(self::DATASETS));
        if ($unknown !== []) {
            $this->error('Unknown language(s): '.implode(', ', $unknown).'. Supported: '.implode(', ', array_keys(self::DATASETS)));

            return Command::FAILURE;
        }

        $results = [];

        foreach ($langs as $lang) {
            $filePath = $directory.DIRECTORY_SEPARATOR.self::DATASETS[$lang];
            $this->info("Importing [{$lang}] from ".self::DATASETS[$lang].' ...');

            try {
                $result = $this->importService->importTranslationFile($filePath, $lang);
                $results[] = [$lang, $result['lines'], $result['written']];
            } catch (\Throwable $e) {
                $this->error("Import failed for [{$lang}]: {$e->getMessage()}");

                return Command::FAILURE;
            }
        }

        $this->table(['Lang', 'Verse lines', 'Rows written'], $results);

        return Command::SUCCESS;
    }
}
