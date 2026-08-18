<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Shared\Models\Import;
use App\Domain\Shared\Repositories\ImportRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Cleanup Failed Imports Job
 *
 * Removes old completed imports and their associated files
 */
class CleanupFailedImports implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 60;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public array $backoff = [30, 60, 120];

    /**
     * Constructor
     */
    public function __construct(
        public int $daysOld = 30
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ImportRepositoryInterface $importRepository): void
    {
        try {
            Log::info('Starting import cleanup', ['days_old' => $this->daysOld]);

            // Get old completed imports
            $imports = Import::where('status', 'completed')
                ->where('completed_at', '<', now()->subDays($this->daysOld))
                ->get();

            $deletedCount = 0;
            $deletedFiles = 0;

            foreach ($imports as $import) {
                // Delete associated file
                if (Storage::exists($import->file_path)) {
                    Storage::delete($import->file_path);
                    $deletedFiles++;
                }

                // Delete import record (cascade deletes errors and mappings)
                $import->delete();
                $deletedCount++;
            }

            Log::info('Import cleanup completed', [
                'deleted_imports' => $deletedCount,
                'deleted_files' => $deletedFiles,
            ]);
        } catch (\Throwable $e) {
            Log::error('Import cleanup failed', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('cleanup_failed_imports.failed', [
            'job' => static::class,
            'days_old' => $this->daysOld,
            'error' => $exception->getMessage(),
        ]);
    }
}
