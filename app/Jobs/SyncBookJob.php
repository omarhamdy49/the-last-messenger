<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithSyncLog;
use App\Domain\Hadith\Services\ChapterSyncService;
use App\Domain\Hadith\Services\HadithSyncService;
use App\Services\Sunnah\SunnahApiClient;
use App\Services\Sunnah\SunnahDataTransformer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncBookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 120;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public array $backoff = [30, 60, 120];

    /**
     * Delete the job if its models no longer exist.
     */
    public bool $deleteWhenMissingModels = true;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $collectionName,
        public string $bookNumber,
        public ?int $collectionId = null,
        public ?int $syncLogId = null,
        public bool $dryRun = false
    ) {
        // Set queue name for better organization
        $this->onQueue('sync');
    }

    /**
     * Execute the job.
     */
    public function handle(
        SunnahApiClient $apiClient,
        SunnahDataTransformer $transformer,
        ChapterSyncService $chapterSync,
        HadithSyncService $hadithSync
    ): void {
        Log::info('sync_book_job.started', [
            'collection' => $this->collectionName,
            'book' => $this->bookNumber,
            'collection_id' => $this->collectionId,
            'dry_run' => $this->dryRun,
        ]);

        try {
            $syncLog = $this->syncLogId ? HadithSyncLog::find($this->syncLogId) : null;

            // Fetch book data from API
            $bookData = $apiClient->getBook($this->collectionName, $this->bookNumber);

            // Transform and sync book record
            $transformed = $transformer->transformBook($bookData, $this->collectionName);

            if (! $this->dryRun) {
                $bookSlug = $this->collectionName.'-'.$this->bookNumber;

                $book = HadithBook::where('slug', $bookSlug)
                    ->where('collection_id', $this->collectionId)
                    ->first();

                if ($book) {
                    $book->update([
                        'name' => $transformed['name'],
                        'description' => $transformed['description'],
                        'display_order' => $transformed['display_order'],
                    ]);
                } else {
                    $book = HadithBook::create([
                        'slug' => $bookSlug,
                        'collection_id' => $this->collectionId,
                        'name' => $transformed['name'],
                        'description' => $transformed['description'],
                        'author_name' => $transformed['author_name'] ?? null,
                        'total_hadith_count' => 0,
                        'display_order' => $transformed['display_order'],
                        'active' => true,
                    ]);
                }

                // Sync chapters
                $chapterSync->syncBookChapters(
                    $book,
                    $this->collectionName,
                    $this->bookNumber,
                    $syncLog,
                    $this->dryRun
                );

                // Sync hadiths
                $hadithSync->syncBookHadiths(
                    $book,
                    $this->collectionName,
                    $this->bookNumber,
                    $syncLog,
                    $this->dryRun
                );

                // Update hadith count
                $hadithCount = $book->hadiths()->count();
                $book->update(['total_hadith_count' => $hadithCount]);

                Log::info('sync_book_job.completed', [
                    'collection' => $this->collectionName,
                    'book' => $this->bookNumber,
                    'hadith_count' => $hadithCount,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('sync_book_job.failed', [
                'collection' => $this->collectionName,
                'book' => $this->bookNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($this->syncLogId) {
                $syncLog = HadithSyncLog::find($this->syncLogId);
                $syncLog?->incrementFailed();
            }

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('sync_book_job.permanently_failed', [
            'job' => static::class,
            'collection' => $this->collectionName,
            'book' => $this->bookNumber,
            'collection_id' => $this->collectionId,
            'sync_log_id' => $this->syncLogId,
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'sync',
            'hadith',
            "collection:{$this->collectionName}",
            "book:{$this->bookNumber}",
        ];
    }
}
