<?php

declare(strict_types=1);

namespace App\Console\Commands\IslamicData;

use App\Domain\Hadith\Models\HadithSyncLog;
use App\Domain\Hadith\Services\JsonImportService;
use App\Domain\Hadith\ValueObjects\SyncStatistics;
use App\Services\Hadith\JsonFileScanner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ImportHadithCommand extends Command
{
    protected $signature = 'islamic-data:import-hadith
                            {--source= : Override path to the hadith production directory}
                            {--collection=* : Limit to specific collection directories (e.g. bukhari)}
                            {--chunk=500 : Files per batch (memory is reclaimed between batches)}
                            {--dry-run : Simulate without writing to the database}';

    protected $description = 'Import all reviewed hadith production data — idempotent, duplicate-safe, logs failures per collection';

    public function __construct(
        private readonly JsonImportService $importService,
        private readonly JsonFileScanner $fileScanner,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $source = $this->option('source') ?: config('islamic-data.hadith_production_dir');

        if (! is_dir($source)) {
            $this->error("Hadith production directory not found: {$source}");

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $chunkSize = max(1, (int) $this->option('chunk'));
        $collections = $this->resolveCollections($source);

        if ($collections === []) {
            $this->error('No matching collection directories found.');

            return self::FAILURE;
        }

        if ($dryRun) {
            $this->warn('Dry run — nothing will be written.');
        }

        $overall = SyncStatistics::empty();
        $failedFiles = [];

        foreach ($collections as $directory) {
            $name = basename($directory);
            $files = $this->fileScanner->scan($directory);

            if ($files === []) {
                $this->warn("{$name}: no JSON files, skipped.");

                continue;
            }

            $this->info("Importing {$name} (".count($files).' files)');
            $syncLog = $this->createSyncLog($name, $directory, $dryRun, count($files));

            $bar = $this->output->createProgressBar(count($files));
            $stats = SyncStatistics::empty();

            foreach (array_chunk($files, $chunkSize) as $batch) {
                foreach ($batch as $filePath) {
                    try {
                        $result = $this->importService->importFromFile($filePath, null, $dryRun);
                        $stats = $stats->merge(SyncStatistics::fromArray($result)->withFileProcessed());
                    } catch (\Throwable $e) {
                        $stats = $stats->merge(SyncStatistics::empty()->withFileFailed());
                        $failedFiles[] = $filePath;
                        Log::error('islamic-data.import-hadith.file_failed', [
                            'file' => $filePath,
                            'error' => $e->getMessage(),
                        ]);
                    }
                    $bar->advance();
                }
                // Keep memory flat across tens of thousands of files.
                gc_collect_cycles();
            }

            $bar->finish();
            $this->newLine();
            $this->completeSyncLog($syncLog, $stats);
            $overall = $overall->merge($stats);
        }

        $this->displaySummary($overall, $failedFiles);

        return $failedFiles === [] ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @return list<string> Absolute paths of collection directories to import
     */
    private function resolveCollections(string $source): array
    {
        $requested = array_map(strtolower(...), (array) $this->option('collection'));
        $directories = glob(rtrim($source, '/').'/*', GLOB_ONLYDIR) ?: [];

        if ($requested === []) {
            return $directories;
        }

        return array_values(array_filter(
            $directories,
            static fn (string $dir): bool => in_array(strtolower(basename($dir)), $requested, true),
        ));
    }

    private function createSyncLog(string $collection, string $directory, bool $dryRun, int $fileCount): ?HadithSyncLog
    {
        if ($dryRun) {
            return null;
        }

        return HadithSyncLog::create([
            'source_collection_code' => $collection,
            'type' => 'json_import',
            'status' => HadithSyncLog::STATUS_IN_PROGRESS,
            'started_at' => now(),
            'metadata' => [
                'directory' => $directory,
                'file_count' => $fileCount,
                'command' => 'islamic-data:import-hadith',
            ],
        ]);
    }

    private function completeSyncLog(?HadithSyncLog $syncLog, SyncStatistics $stats): void
    {
        $syncLog?->update([
            'status' => $stats->errors > 0 ? HadithSyncLog::STATUS_FAILED : HadithSyncLog::STATUS_COMPLETED,
            'completed_at' => now(),
            'summary' => $stats->toArray(),
        ]);
    }

    /**
     * @param  list<string>  $failedFiles
     */
    private function displaySummary(SyncStatistics $overall, array $failedFiles): void
    {
        $this->newLine();
        $this->info('Import summary');
        $this->table(['Metric', 'Count'], [
            ['Files processed', $overall->filesProcessed],
            ['Files failed', $overall->filesFailed],
            ['Hadiths created/updated', $overall->hadiths],
            ['Translations', $overall->translations],
            ['Books', $overall->books],
            ['Chapters', $overall->chapters],
            ['Narrators', $overall->narrators],
            ['Gradings', $overall->gradings],
        ]);

        if ($failedFiles !== []) {
            $this->error(count($failedFiles).' file(s) failed — first 10:');
            foreach (array_slice($failedFiles, 0, 10) as $file) {
                $this->line("  • {$file}");
            }
            $this->line('Full list in laravel.log (islamic-data.import-hadith.file_failed).');
        }

        $this->line('Run `php artisan islamic-data:verify` for the coverage report.');
    }
}
