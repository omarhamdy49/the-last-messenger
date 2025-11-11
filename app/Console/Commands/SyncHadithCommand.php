<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Hadith\Services\CollectionSyncService;
use App\Domain\Hadith\Models\HadithSyncLog;
use App\Jobs\SyncCollectionJob;
use App\Services\Sunnah\SunnahApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncHadithCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hadith:sync
        {--collection= : Specific collection to sync (e.g., bukhari, muslim)}
        {--full : Perform a full sync (default)}
        {--dry-run : Simulate the sync without saving data}
        {--parallel : Run sync using queue jobs for parallel processing}
        {--resume= : Resume from a previous sync log ID}
        {--list-collections : List available collections from API}
        {--test-connection : Test API connection and configuration}
    ';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Sync hadith data from Sunnah.com API';

    private CollectionSyncService $collectionSyncService;
    private SunnahApiClient $apiClient;

    public function __construct(
        CollectionSyncService $collectionSyncService,
        SunnahApiClient $apiClient
    ) {
        parent::__construct();
        $this->collectionSyncService = $collectionSyncService;
        $this->apiClient = $apiClient;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Test connection first
        if ($this->option('test-connection')) {
            return $this->testConnection();
        }

        // List available collections
        if ($this->option('list-collections')) {
            return $this->listCollections();
        }

        // Handle resume option
        if ($resumeId = $this->option('resume')) {
            return $this->resumeSync((int) $resumeId);
        }

        $dryRun = $this->option('dry-run');
        $parallel = $this->option('parallel');

        if ($dryRun) {
            $this->warn("⚠️  DRY RUN MODE - No data will be saved");
        }

        if ($parallel) {
            $this->info("🚀 PARALLEL MODE - Using queue jobs for concurrent processing");
        }

        // Sync specific collection or all collections
        if ($this->option('collection')) {
            return $this->syncSpecificCollection($dryRun, $parallel);
        }

        return $this->syncAllCollections($dryRun, $parallel);
    }

    /**
     * Test API connection
     */
    private function testConnection(): int
    {
        $this->info("Testing Sunnah.com API connection...");

        try {
            $isConnected = $this->apiClient->testConnection();

            if ($isConnected) {
                $this->info("✅ API connection successful!");
                $this->info("API Key is valid and endpoint is reachable.");
                return 0;
            } else {
                $this->error("❌ API connection failed!");
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("❌ API connection error: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * List available collections
     */
    private function listCollections(): int
    {
        $this->info("Fetching available collections from Sunnah.com API...");

        try {
            $collections = $this->collectionSyncService->getAvailableCollections();

            if (empty($collections)) {
                $this->error("No collections found or API is not reachable.");
                return 1;
            }

            $this->info("Available collections:");
            $this->line("");

            foreach ($collections as $collection) {
                $this->line("  <fg=cyan>{$collection['name']}</> - {$collection['title']}");
                $this->line("    Total hadiths: <fg=yellow>{$collection['total_hadith']}</>");
                $this->line("");
            }

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to fetch collections: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Sync specific collection
     */
    private function syncSpecificCollection(bool $dryRun, bool $parallel): int
    {
        $collectionName = $this->option('collection');

        $this->info("Starting hadith sync for collection: <fg=cyan>{$collectionName}</>");
        $this->info("Mode: Sequential (one book at a time)");
        $this->line("");

        try {
            // Always use synchronous sync (sequential mode)
            $syncLog = $this->collectionSyncService->syncCollection($collectionName, null, $dryRun);

            return $this->displayResults($syncLog, $dryRun);
        } catch (\Exception $e) {
            $this->error("Sync failed: {$e->getMessage()}");
            Log::error("Sync collection error", [
                'collection' => $collectionName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }

    /**
     * Sync all collections
     */
    private function syncAllCollections(bool $dryRun, bool $parallel): int
    {
        $this->info("Starting full hadith sync from Sunnah.com API");
        $this->info("Mode: Sequential (one collection at a time, one book at a time)");
        $this->line("");

        try {
            // Always use synchronous sync with progress bar and statistics
            $progressBar = null;
            $isFirstUpdate = true;

            $syncLog = $this->collectionSyncService->syncAllCollections(
                $dryRun,
                function ($current, $total) use (&$progressBar, &$isFirstUpdate) {
                    // Initialize progress bar on first callback with actual total
                    if ($isFirstUpdate) {
                        $progressBar = $this->output->createProgressBar($total);
                        $progressBar->setFormat(
                            " %current%/%max% [%bar%] %percent:3s%%\n" .
                            " 🕐 Elapsed: %elapsed:6s%  |  ⏱️  ETA: %estimated:-6s%\n" .
                            " 📊 Collections synced: <fg=green>%current%</> / <fg=cyan>%max%</>"
                        );
                        $progressBar->start();
                        $isFirstUpdate = false;
                    }

                    // Update progress
                    if ($progressBar) {
                        $progressBar->setProgress($current);
                    }
                }
            );

            // Finish progress bar if it was initialized
            if ($progressBar) {
                $progressBar->finish();
                $this->line("");
                $this->line("");
            }

            return $this->displayResults($syncLog, $dryRun);
        } catch (\Exception $e) {
            $this->line("");
            $this->error("Sync failed: {$e->getMessage()}");
            Log::error("Full sync error", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }

    /**
     * Resume sync from a previous sync log
     */
    private function resumeSync(int $syncLogId): int
    {
        $this->info("Attempting to resume sync from log ID: {$syncLogId}");

        try {
            $syncLog = HadithSyncLog::findOrFail($syncLogId);

            $this->line("");
            $this->line("Sync Log Details:");
            $this->line("  Collection:    <fg=cyan>" . ($syncLog->collection_name ?? 'ALL') . "</>");
            $this->line("  Type:          <fg=cyan>{$syncLog->sync_type}</>");
            $this->line("  Status:        " . $this->getStatusDisplay($syncLog->status));
            $this->line("  Progress:      <fg=cyan>{$syncLog->synced_records}/{$syncLog->total_records}</>");

            if ($syncLog->hasCheckpoint()) {
                $this->line("");
                $this->line("Checkpoint Data:");
                $checkpoint = $syncLog->checkpoint;
                foreach ($checkpoint as $key => $value) {
                    if (is_array($value)) {
                        $this->line("  {$key}: " . json_encode($value));
                    } else {
                        $this->line("  {$key}: <fg=yellow>{$value}</>");
                    }
                }
            }

            $this->line("");

            if ($syncLog->status === 'success') {
                $this->warn("⚠️  This sync has already completed successfully!");
                return $this->displayResults($syncLog, false);
            }

            if ($syncLog->status === 'in_progress') {
                $this->warn("⚠️  This sync is currently in progress. Check queue workers.");
                $this->info("Monitor with: php artisan queue:work --queue=sync");
                return 0;
            }

            // Resume the sync
            $this->info("Resuming sync...");

            if ($syncLog->collection_name) {
                // Resume specific collection
                $result = $this->collectionSyncService->syncCollection(
                    $syncLog->collection_name,
                    $syncLog,
                    false
                );
            } else {
                // Resume full sync
                $result = $this->collectionSyncService->syncAllCollections(
                    false,
                    fn ($current, $total) => $this->line("Progress: {$current}/{$total}")
                );
            }

            return $this->displayResults($result, false);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $this->error("Sync log not found with ID: {$syncLogId}");
            return 1;
        } catch (\Exception $e) {
            $this->error("Failed to resume sync: {$e->getMessage()}");
            Log::error("Resume sync error", [
                'sync_log_id' => $syncLogId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }

    /**
     * Get colored status display
     */
    private function getStatusDisplay(string $status): string
    {
        return match ($status) {
            'success' => '<fg=green>✅ SUCCESS</>',
            'failed' => '<fg=red>❌ FAILED</>',
            'partial' => '<fg=yellow>⚠️  PARTIAL</>',
            'in_progress' => '<fg=blue>⏳ IN PROGRESS</>',
            default => '<fg=gray>PENDING</>'
        };
    }

    /**
     * Display sync results
     */
    private function displayResults(HadithSyncLog $syncLog, bool $dryRun): int
    {
        $this->line("");
        $this->line("════════════════════════════════════════════════════════════════");
        $this->line("                        SYNC RESULTS");
        $this->line("════════════════════════════════════════════════════════════════");

        $this->line("Status:              " . $this->getStatusDisplay($syncLog->status));
        $this->line("Sync Type:           <fg=cyan>{$syncLog->sync_type}</>");
        $this->line("");
        $this->line("Total Records:       <fg=cyan>{$syncLog->total_records}</>");
        $this->line("Synced:              <fg=green>{$syncLog->synced_records}</>");
        $this->line("Failed:              <fg=red>{$syncLog->failed_records}</>");
        $this->line("Skipped:             <fg=yellow>{$syncLog->skipped_records}</>");

        if ($syncLog->duration_seconds) {
            $this->line("Duration:            <fg=cyan>{$syncLog->duration_seconds}s</>");
        }

        if ($syncLog->synced_records > 0 && $syncLog->total_records > 0) {
            $percentage = $syncLog->getSuccessPercentage();
            $this->line("Success Rate:        <fg=cyan>{$percentage}%</>");
        }

        if ($syncLog->error_message) {
            $this->line("");
            $this->line("Error Message:       <fg=red>{$syncLog->error_message}</>");
        }

        if ($dryRun) {
            $this->warn("⚠️  This was a DRY RUN - no data was saved");
        }

        $this->line("════════════════════════════════════════════════════════════════");
        $this->line("");

        return $syncLog->status === 'success' ? 0 : 1;
    }
}
