<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Hadith\Services\HadithImportService;
use Illuminate\Console\Command;

class HadithImportCommand extends Command
{
    protected $signature = 'hadith:import {path : Path to JSON file or directory} {--collection-id= : Collection ID (if importing single file)}';

    protected $description = 'Import hadiths from JSON file(s) with deduplication';

    public function __construct(
        private readonly HadithImportService $importService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $path = $this->argument('path');

        if (!file_exists($path)) {
            $this->error("Path not found: {$path}");
            return 1;
        }

        $this->info('Hadith Import Tool');
        $this->info('==================');
        $this->newLine();

        try {
            if (is_file($path)) {
                return $this->importFile($path);
            } elseif (is_dir($path)) {
                return $this->importDirectory($path);
            } else {
                $this->error("Invalid path: {$path}");
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('Import failed: ' . $e->getMessage());
            return 1;
        }
    }

    private function importFile(string $filePath): int
    {
        $collectionId = $this->option('collection-id');

        if (!$collectionId) {
            $this->error('--collection-id is required when importing a single file');
            return 1;
        }

        $this->info("Importing from: {$filePath}");
        $this->info("Collection ID: {$collectionId}");
        $this->newLine();

        $bar = $this->output->createProgressBar(100);
        $bar->start();

        $stats = $this->importService->importFromJsonFile($filePath, (int) $collectionId);

        $bar->finish();
        $this->newLine(2);

        $this->displayStats($stats);

        return empty($stats['errors']) ? 0 : 1;
    }

    private function importDirectory(string $directoryPath): int
    {
        $this->info("Importing from directory: {$directoryPath}");
        $this->newLine();

        $bar = $this->output->createProgressBar(100);
        $bar->start();

        $stats = $this->importService->importFromDirectory($directoryPath);

        $bar->finish();
        $this->newLine(2);

        if (isset($stats['error'])) {
            $this->error($stats['error']);
            return 1;
        }

        $this->info('Import Summary');
        $this->info('==============');
        $this->table(['Metric', 'Count'], [
            ['Total Files', $stats['total_files']],
            ['Files Processed', $stats['files_processed']],
            ['Hadiths Created', $stats['total_created']],
            ['Duplicates Found', $stats['total_duplicates']],
            ['Existing Linked', $stats['total_linked']],
            ['Failed', $stats['total_failed']],
        ]);

        if (!empty($stats['file_stats'])) {
            $this->newLine();
            $this->info('Per-File Details:');
            foreach ($stats['file_stats'] as $filename => $fileStats) {
                $this->line("\n{$filename}:");
                if (isset($fileStats['status'])) {
                    $this->line("  Status: {$fileStats['status']}");
                    if (isset($fileStats['reason'])) {
                        $this->line("  Reason: {$fileStats['reason']}");
                    }
                } else {
                    $this->line("  Created: {$fileStats['created']}");
                    $this->line("  Duplicates: {$fileStats['duplicates']}");
                    $this->line("  Linked: {$fileStats['linked']}");
                    $this->line("  Failed: {$fileStats['failed']}");
                }
            }
        }

        return 0;
    }

    private function displayStats(array $stats): void
    {
        $this->info('Import Summary');
        $this->info('==============');
        $this->table(['Metric', 'Count'], [
            ['Total Rows', $stats['total']],
            ['Created', $stats['created']],
            ['Duplicates Found', $stats['duplicates']],
            ['Linked Existing', $stats['linked']],
            ['Failed', $stats['failed']],
        ]);

        if (!empty($stats['errors'])) {
            $this->newLine();
            $this->error('Errors:');
            foreach (array_slice($stats['errors'], 0, 10) as $error) {
                $this->line("  • {$error}");
            }
            if (count($stats['errors']) > 10) {
                $this->line("  ... and " . (count($stats['errors']) - 10) . " more");
            }
        }
    }
}
