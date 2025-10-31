<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Shared\Importers\ArticleImporter;
use App\Domain\Shared\Importers\HadithImporter;
use App\Domain\Shared\Importers\HadithNarratorImporter;
use App\Domain\Shared\Importers\MediaProgramImporter;
use App\Domain\Shared\Importers\QuranSurahImporter;
use App\Domain\Shared\Importers\QuranVerseImporter;
use App\Domain\Shared\Importers\ReciterImporter;
use App\Domain\Shared\Importers\SahabiImporter;
use App\Domain\Shared\Importers\SeerahEventImporter;
use App\Domain\Shared\Importers\TafseerImporter;
use App\Domain\Shared\Models\Import;
use App\Domain\Shared\Services\AdminLoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Process Import Job
 *
 * Handles asynchronous import processing for large files
 */
class ProcessImport implements ShouldQueue
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
     * Constructor
     */
    public function __construct(
        public Import $import
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AdminLoggerService $adminLogger): void
    {
        try {
            Log::info('Starting import processing', [
                'import_id' => $this->import->id,
                'module' => $this->import->module,
                'total_rows' => $this->import->total_rows,
            ]);

            // Get appropriate importer based on module
            $importer = $this->getImporter();

            if (! $importer) {
                throw new \Exception("No importer found for module: {$this->import->module}");
            }

            // Execute import
            $result = $importer->import();

            Log::info('Import completed successfully', [
                'import_id' => $this->import->id,
                'success_count' => $result->successCount,
                'error_count' => $result->errorCount,
                'skipped_count' => $result->skippedCount,
            ]);

            // Log to admin activity
            $adminLogger->logCustomAction(
                action: 'import_completed',
                model: $this->import,
                user: $this->import->user,
                newValues: [
                    'module' => $this->import->module,
                    'total_rows' => $result->totalRows,
                    'success_count' => $result->successCount,
                    'error_count' => $result->errorCount,
                    'success_rate' => $result->getSuccessRate(),
                ]
            );
        } catch (Throwable $e) {
            Log::error('Import processing failed', [
                'import_id' => $this->import->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->import->markAsFailed($e->getMessage());

            // Log failure to admin activity
            $adminLogger->logCustomAction(
                action: 'import_failed',
                model: $this->import,
                user: $this->import->user,
                newValues: [
                    'module' => $this->import->module,
                    'error' => $e->getMessage(),
                ]
            );

            throw $e;
        }
    }

    /**
     * Get appropriate importer instance
     */
    private function getImporter(): mixed
    {
        return match ($this->import->module) {
            'articles' => new ArticleImporter($this->import),
            'hadith' => new HadithImporter($this->import),
            'media_programs' => new MediaProgramImporter($this->import),
            'seerah_events' => new SeerahEventImporter($this->import),
            'sahaba' => new SahabiImporter($this->import),
            'quran_surahs' => new QuranSurahImporter($this->import),
            'quran_verses' => new QuranVerseImporter($this->import),
            'reciters' => new ReciterImporter($this->import),
            'tafseer' => new TafseerImporter($this->import),
            'hadith_narrators' => new HadithNarratorImporter($this->import),
            default => null,
        };
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Import job failed permanently', [
            'import_id' => $this->import->id,
            'error' => $exception->getMessage(),
        ]);

        $this->import->markAsFailed('Job failed after '.$this->tries.' attempts: '.$exception->getMessage());
    }
}
