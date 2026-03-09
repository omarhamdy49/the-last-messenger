<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Quran\Application\Commands\ImportQuranFromJsonCommand;
use App\Domain\Quran\Application\Handlers\ImportQuranFromJsonHandler;
use Illuminate\Console\Command;

class ImportQuranJsonCommand extends Command
{
    protected $signature = 'quran:import-json
                            {file : Path to JSON file}
                            {--type=surahs : Import type (surahs, verses, translations, tafsir, reciters)}
                            {--truncate : Truncate table before import}';

    protected $description = 'Import Quran data from JSON file';

    public function __construct(
        private ImportQuranFromJsonHandler $handler,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $file = $this->argument('file');
        $type = $this->option('type');
        $truncate = $this->option('truncate') ?? false;

        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return Command::FAILURE;
        }

        $this->newLine();
        $this->info('╔══════════════════════════════════════════╗');
        $this->info('║    Quran JSON Data Import Utility         ║');
        $this->info('╚══════════════════════════════════════════╝');
        $this->newLine();

        $this->info("📖 File: {$file}");
        $this->info("📚 Type: {$type}");
        if ($truncate) {
            $this->warn('🔄 Truncate: YES (existing data will be deleted)');
        }
        $this->newLine();

        try {
            $this->info("⏳ Importing {$type} data...");

            $command = new ImportQuranFromJsonCommand(
                filePath: $file,
                type: $type,
                truncateFirst: $truncate,
            );

            $result = $this->handler->handle($command);

            $this->newLine(2);
            $this->info('╔══════════════════════════════════════════╗');
            $this->info('║    ✅ Import completed successfully!     ║');
            $this->info('╚══════════════════════════════════════════╝');
            $this->newLine();

            // Display results
            $tableData = [];
            foreach ($result as $key => $value) {
                $tableData[] = [ucfirst(str_replace('_', ' ', $key)), $value];
            }

            if (!empty($tableData)) {
                $this->table(['Metric', 'Count'], $tableData);
            }

            return Command::SUCCESS;

        } catch (\Throwable $e) {
            $this->newLine();
            $this->error('╔══════════════════════════════════════════╗');
            $this->error('║         ❌ Import failed!                ║');
            $this->error('╚══════════════════════════════════════════╝');
            $this->error($e->getMessage());

            return Command::FAILURE;
        }
    }
}
