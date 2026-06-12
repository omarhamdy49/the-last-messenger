<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Quran\Services\QuranTextDatasetImportService;
use Illuminate\Console\Command;

class ImportQuranTafsirCommand extends Command
{
    protected $signature = 'quran:import-tafsir
                            {--path=database/data/quran/ar.muyassar.txt : Tanzil-format tafsir text file (one verse per line)}
                            {--lang=ar : Language code stored on quran_verse_tafsirs}';

    protected $description = 'Import a verse-aligned tafsir (default: Tafsir al-Muyassar, Arabic) into quran_verse_tafsirs — idempotent, keyed by (verse_id, lang)';

    public function __construct(
        private readonly QuranTextDatasetImportService $importService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $filePath = base_path($this->option('path'));
        $lang = (string) $this->option('lang');

        $this->info("Importing tafsir [{$lang}] from {$filePath} ...");

        try {
            $result = $this->importService->importTafsirFile($filePath, $lang);
        } catch (\Throwable $e) {
            $this->error("Tafsir import failed: {$e->getMessage()}");

            return Command::FAILURE;
        }

        $this->table(['Lang', 'Verse lines', 'Rows written'], [[$result['lang'], $result['lines'], $result['written']]]);

        return Command::SUCCESS;
    }
}
