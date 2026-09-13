<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Shared\Models\BulkOperation;
use App\Domain\Shared\Services\BulkOperationService;
use App\Notifications\BulkOperationFinished;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Process Bulk Operation Job
 *
 * Handles asynchronous bulk operation processing
 */
class ProcessBulkOperation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public array $backoff = [30, 60, 120];

    /**
     * Constructor
     */
    public function __construct(
        public BulkOperation $operation
    ) {}

    /**
     * Execute the job.
     */
    public function handle(BulkOperationService $bulkOperationService): void
    {
        try {
            Log::info('Starting bulk operation processing', [
                'operation_id' => $this->operation->id,
                'operation_type' => $this->operation->operation_type,
                'module' => $this->operation->module,
                'total_items' => $this->operation->total_items,
            ]);

            // Process the operation
            $bulkOperationService->processOperation($this->operation);

            Log::info('Bulk operation completed successfully', [
                'operation_id' => $this->operation->id,
                'success_count' => $this->operation->success_count,
                'failed_count' => $this->operation->failed_count,
                'success_rate' => $this->operation->getSuccessRate(),
                'duration' => $this->operation->getDuration(),
            ]);

            if ($this->operation->notify_on_completion && ! $this->operation->dry_run) {
                $this->sendNotification();
            }
        } catch (Throwable $e) {
            Log::error('Bulk operation processing failed', [
                'operation_id' => $this->operation->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->operation->markAsFailed($e->getMessage());

            // Retries may still succeed. Notify only from failed() after exhaustion.
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        // Queue workers may invoke this on a freshly deserialized job instance.
        $this->operation->refresh();

        Log::error('process_bulk_operation.failed', [
            'job' => static::class,
            'operation_id' => $this->operation->id,
            'operation_type' => $this->operation->operation_type,
            'module' => $this->operation->module,
            'error' => $exception->getMessage(),
        ]);

        $this->operation->markAsFailed('Job failed after '.$this->tries.' attempts: '.$exception->getMessage());

        // Send failure notification
        if ($this->operation->notify_on_completion) {
            $this->sendNotification();
        }
    }

    /**
     * Queue a snapshot so mail delivery cannot re-run a completed operation.
     */
    private function sendNotification(): void
    {
        try {
            $user = $this->operation->creator;

            if ($user && $user->email) {
                $user->notify(BulkOperationFinished::forOperation($this->operation, $user->getPreferredLanguage()));

                Log::info('Bulk operation notification queued', [
                    'operation_id' => $this->operation->id,
                    'user_id' => $user->id,
                ]);
            }
        } catch (Throwable $e) {
            report($e);
            Log::error('Failed to queue bulk operation notification', [
                'operation_id' => $this->operation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<string>
     */
    public function tags(): array
    {
        return [
            'bulk-operation',
            'operation:'.$this->operation->id,
            'module:'.$this->operation->module,
            'type:'.$this->operation->operation_type->value,
        ];
    }
}
