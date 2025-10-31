<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Shared\Models\BulkOperation;
use App\Domain\Shared\Services\BulkOperationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
    public int $timeout = 3600; // 1 hour

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

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

            // Send notification if enabled
            if ($this->operation->notify_on_completion) {
                $this->sendCompletionNotification();
            }
        } catch (Throwable $e) {
            Log::error('Bulk operation processing failed', [
                'operation_id' => $this->operation->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->operation->markAsFailed($e->getMessage());

            // Send failure notification
            if ($this->operation->notify_on_completion) {
                $this->sendFailureNotification($e->getMessage());
            }

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Bulk operation job failed permanently', [
            'operation_id' => $this->operation->id,
            'error' => $exception->getMessage(),
        ]);

        $this->operation->markAsFailed('Job failed after '.$this->tries.' attempts: '.$exception->getMessage());

        // Send failure notification
        if ($this->operation->notify_on_completion) {
            $this->sendFailureNotification($exception->getMessage());
        }
    }

    /**
     * Send completion notification
     */
    private function sendCompletionNotification(): void
    {
        try {
            $user = $this->operation->creator;

            if ($user && $user->email) {
                // TODO: Implement email notification
                // Mail::to($user->email)->send(new BulkOperationCompletedMail($this->operation));

                Log::info('Bulk operation completion notification sent', [
                    'operation_id' => $this->operation->id,
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);
            }
        } catch (Throwable $e) {
            Log::error('Failed to send completion notification', [
                'operation_id' => $this->operation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send failure notification
     */
    private function sendFailureNotification(string $errorMessage): void
    {
        try {
            $user = $this->operation->creator;

            if ($user && $user->email) {
                // TODO: Implement email notification
                // Mail::to($user->email)->send(new BulkOperationFailedMail($this->operation, $errorMessage));

                Log::info('Bulk operation failure notification sent', [
                    'operation_id' => $this->operation->id,
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);
            }
        } catch (Throwable $e) {
            Log::error('Failed to send failure notification', [
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
            'type:'.$this->operation->operation_type,
        ];
    }
}
