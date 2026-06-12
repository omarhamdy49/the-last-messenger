<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Blog\Importers\ArticleImporter;
use App\Domain\Hadith\Importers\HadithImporter;
use App\Domain\Hadith\Importers\HadithNarratorImporter;
use App\Domain\Quran\Importers\QuranSurahImporter;
use App\Domain\Quran\Importers\QuranVerseImporter;
use App\Domain\Quran\Importers\ReciterImporter;
use App\Domain\Quran\Importers\TafseerImporter;
use App\Domain\Sahaba\Importers\SahabiImporter;
use App\Domain\Seerah\Importers\SeerahEventImporter;
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
    public int $timeout = 600;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public array $backoff = [30, 60, 120];

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
                throw new \RuntimeException('No importer found for module: '.$this->import->module);
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
        Log::error('process_import.failed', [
            'job' => static::class,
            'import_id' => $this->import->id,
            'module' => $this->import->module,
            'error' => $exception->getMessage(),
        ]);

        $this->import->markAsFailed('Job failed after '.$this->tries.' attempts: '.$exception->getMessage());
    }
}
