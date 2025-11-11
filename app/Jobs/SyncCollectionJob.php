<?php

namespace App\Jobs;

use App\Domain\Hadith\Models\HadithSyncLog;
use App\Domain\Hadith\Services\CollectionSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncCollectionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 7200; // 2 hours

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = 60;

    /**
     * Delete the job if its models no longer exist.
     */
    public $deleteWhenMissingModels = true;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $collectionName,
        public ?int $syncLogId = null,
        public bool $dryRun = false
    ) {
        // Set queue name for better organization
        $this->onQueue('sync');
    }

    /**
     * Execute the job.
     */
    public function handle(CollectionSyncService $syncService): void
    {
        Log::info("SyncCollectionJob started", [
            'collection' => $this->collectionName,
            'sync_log_id' => $this->syncLogId,
            'dry_run' => $this->dryRun,
        ]);

        try {
            // Resume from existing sync log if provided
            $parentSyncLog = $this->syncLogId
                ? HadithSyncLog::find($this->syncLogId)
                : null;

            // Sync the collection
            $result = $syncService->syncCollection(
                $this->collectionName,
                $parentSyncLog,
                $this->dryRun
            );

            Log::info("SyncCollectionJob completed", [
                'collection' => $this->collectionName,
                'synced' => $result->synced_records,
                'failed' => $result->failed_records,
                'status' => $result->status,
            ]);
        } catch (\Exception $e) {
            Log::error("SyncCollectionJob failed", [
                'collection' => $this->collectionName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to mark job as failed
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("SyncCollectionJob permanently failed", [
            'collection' => $this->collectionName,
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
        ]);

        // Mark sync log as failed if exists
        if ($this->syncLogId) {
            $syncLog = HadithSyncLog::find($this->syncLogId);
            if ($syncLog && $syncLog->status === 'in_progress') {
                $syncLog->markFailed(
                    "Job failed after {$this->attempts()} attempts: {$exception->getMessage()}",
                    ['exception' => get_class($exception)]
                );
            }
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['sync', 'hadith', "collection:{$this->collectionName}"];
    }
}
