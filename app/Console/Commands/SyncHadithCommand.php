<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Hadith\Services\CollectionSyncService;
use App\Domain\Hadith\Models\HadithSyncLog;
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

        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn("⚠️  DRY RUN MODE - No data will be saved");
        }

        // Sync specific collection or all collections
        if ($this->option('collection')) {
            return $this->syncSpecificCollection($dryRun);
        }

        return $this->syncAllCollections($dryRun);
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
    private function syncSpecificCollection(bool $dryRun): int
    {
        $collectionName = $this->option('collection');

        $this->info("Starting hadith sync for collection: <fg=cyan>{$collectionName}</>");

        try {
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
    private function syncAllCollections(bool $dryRun): int
    {
        $this->info("Starting full hadith sync from Sunnah.com API");

        // Create progress bar
        $progressBar = $this->output->createProgressBar(100);
        $progressBar->start();

        try {
            $syncLog = $this->collectionSyncService->syncAllCollections(
                $dryRun,
                function ($current, $total) use ($progressBar) {
                    $progressBar->setProgress((int) ($current / max($total, 1) * 100));
                }
            );

            $progressBar->finish();
            $this->line("");

            return $this->displayResults($syncLog, $dryRun);
        } catch (\Exception $e) {
            $progressBar->finish();
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
     * Display sync results
     */
    private function displayResults(HadithSyncLog $syncLog, bool $dryRun): int
    {
        $this->line("");
        $this->line("════════════════════════════════════════════════════════════════");
        $this->line("                        SYNC RESULTS");
        $this->line("════════════════════════════════════════════════════════════════");

        $status = match ($syncLog->status) {
            'success' => '<fg=green>✅ SUCCESS</>',
            'failed' => '<fg=red>❌ FAILED</>',
            'partial' => '<fg=yellow>⚠️  PARTIAL</>',
            default => '<fg=gray>PENDING</>'
        };

        $this->line("Status:              {$status}");
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
