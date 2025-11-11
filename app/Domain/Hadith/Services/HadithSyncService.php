<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Services;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithChapter;
use App\Domain\Hadith\Models\HadithSyncLog;
use App\Services\Sunnah\SunnahApiClient;
use App\Services\Sunnah\SunnahDataTransformer;
use App\Support\Utilities\TextSanitizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HadithSyncService
{
    private SunnahApiClient $apiClient;

    private SunnahDataTransformer $transformer;

    private HadithSyncLog $syncLog;

    private int $batchSize;

    private bool $dryRun = false;

    public function __construct(
        SunnahApiClient $apiClient,
        SunnahDataTransformer $transformer
    ) {
        $this->apiClient = $apiClient;
        $this->transformer = $transformer;
        $this->batchSize = config('sunnah.sync.batch_size', 500);
    }

    /**
     * Sync all hadiths for a book
     */
    public function syncBookHadiths(
        HadithBook $book,
        string $collectionName,
        string $bookNumber,
        ?HadithSyncLog $syncLog = null,
        bool $dryRun = false,
        bool $isDirectSync = false
    ): void {
        $this->syncLog = $syncLog;
        $this->dryRun = $dryRun;

        Log::info("Starting hadith sync for book {$bookNumber} in collection {$collectionName}", ['isDirectSync' => $isDirectSync]);

        try {
            $page = 1;
            $hasMore = true;
            $totalFetched = 0;
            $consecutiveFailures = 0;
            $maxConsecutiveFailures = 3; // Stop after 3 consecutive failed pages

            while ($hasMore && $consecutiveFailures < $maxConsecutiveFailures) {
                try {
                    // For collections without books, fetch hadiths directly by collection
                    // API has a maximum page size limit of 14 hadiths per request
                    $pageSize = 14;

                    if ($isDirectSync) {
                        Log::info("Fetching hadiths directly for collection {$collectionName}", ['page' => $page]);
                        $response = $this->apiClient->getHadiths($collectionName, null, null, null, $pageSize, $page);
                    } else {
                        // Fetch hadiths from API by book
                        $response = $this->apiClient->getHadithsByBook($collectionName, $bookNumber, $pageSize, $page);
                    }

                    Log::debug("API Response", [
                        'has_data' => isset($response->data),
                        'data_count' => isset($response->data) ? count($response->data) : 0,
                        'has_next' => isset($response->next),
                        'has_error' => isset($response->error),
                        'page' => $page
                    ]);

                    // Check if API returned an error (returns 200 status with error in body)
                    if (isset($response->error)) {
                        $consecutiveFailures++;
                        Log::warning("API returned error for page {$page}, trying next page ({$consecutiveFailures}/{$maxConsecutiveFailures})", [
                            'error' => $response->error->details ?? 'Unknown error',
                            'code' => $response->error->code ?? 'unknown',
                            'page' => $page,
                        ]);
                        $page++;
                        continue;
                    }

                    if (! isset($response->data) || empty($response->data)) {
                        $hasMore = false;
                        break;
                    }

                    // Reset consecutive failures on successful page fetch
                    $consecutiveFailures = 0;

                    $totalFetched += count($response->data);

                    // Transform and batch upsert
                    $this->processBatch($book, $collectionName, $bookNumber, $response->data);

                    // Check if there's a next page
                    if (! isset($response->next) || $response->next === null) {
                        $hasMore = false;
                    } else {
                        $page = $response->next;
                    }
                } catch (\Exception $e) {
                    $consecutiveFailures++;

                    Log::warning("Error fetching hadiths page {$page} for book {$bookNumber}, trying next page ({$consecutiveFailures}/{$maxConsecutiveFailures})", [
                        'error' => $e->getMessage(),
                        'collection' => $collectionName,
                        'book' => $bookNumber,
                        'page' => $page,
                        'consecutive_failures' => $consecutiveFailures,
                    ]);

                    if ($this->syncLog) {
                        $this->syncLog->incrementFailed();
                    }

                    // Continue to next page instead of stopping (API has buggy pagination)
                    $page++;
                    continue;
                }
            }

            Log::info("Completed hadith sync for book {$bookNumber}. Total fetched: {$totalFetched}");
        } catch (\Exception $e) {
            Log::error("Critical error syncing hadiths for book {$bookNumber}", [
                'error' => $e->getMessage(),
                'collection' => $collectionName,
            ]);
            throw $e;
        }
    }

    /**
     * Process a batch of hadiths
     */
    private function processBatch(
        HadithBook $book,
        string $collectionName,
        string $bookNumber,
        array $hadiths
    ): void {
        $toInsert = [];
        $toUpdate = [];
        $skipped = 0;

        foreach ($hadiths as $hadithData) {
            try {
                // Validate before transformation
                if (! isset($hadithData->hadithNumber) || ! isset($hadithData->hadith)) {
                    Log::debug('Skipping hadith - invalid structure', ['data' => (array) $hadithData]);
                    $skipped++;

                    continue;
                }

                // Transform the hadith
                $transformed = $this->transformer->transformHadith($hadithData, $collectionName, $bookNumber);

                // Validate transformed data
                if (! $this->transformer->validateHadith($transformed)) {
                    Log::debug('Skipping hadith - validation failed', [
                        'hadith_number' => $hadithData->hadithNumber ?? 'unknown',
                        'has_ar' => ! empty($transformed['translations']['ar']),
                        'has_en' => ! empty($transformed['translations']['en']),
                    ]);
                    $skipped++;

                    continue;
                }

                // Set book FK (important: use book_id, not collection_name)
                $transformed['book_id'] = $book->id;
                $transformed['synced_at'] = now();

                // Sanitize text for full-text search indexing
                if (! empty($transformed['arabic_text'])) {
                    $transformed['arabic_text'] = TextSanitizer::cleanText($transformed['arabic_text'], normalize_arabic: false);
                }

                // Sanitize translations for search
                if (is_array($transformed['translations'])) {
                    if (! empty($transformed['translations']['en'])) {
                        $transformed['translations']['en'] = TextSanitizer::cleanText($transformed['translations']['en']);
                    }
                    if (! empty($transformed['translations']['ar'])) {
                        $transformed['translations']['ar'] = TextSanitizer::cleanText($transformed['translations']['ar'], normalize_arabic: false);
                    }
                }

                // Keep URN from transformer (it's now stored in database)
                // Extract URN if it exists in transformed data
                $urn = $transformed['urn'] ?? null;

                // Remove fields that don't exist in hadith table (schema refactor removed them)
                unset($transformed['collection_name']);
                unset($transformed['book_number']);
                unset($transformed['external_id']); // external_id column was removed
                unset($transformed['reference_url']); // reference_url column was removed
                unset($transformed['narrator_chain']); // narrator_chain column was removed

                // Re-add URN if it exists (keep it now, don't remove)
                if ($urn !== null) {
                    $transformed['urn'] = $urn;
                }

                // Try to find existing chapter if chapterId is provided
                if (isset($hadithData->chapterId)) {
                    $chapter = HadithChapter::where('book_id', $book->id)
                        ->where('chapter_number', (int) $hadithData->chapterId)
                        ->first();

                    if ($chapter) {
                        $transformed['chapter_id'] = $chapter->id;
                    }
                }

                // Check if hadith already exists by book_id and hadith_number
                // (URN column was removed in schema refactor, use book_id + hadith_number instead)
                $existing = Hadith::where('book_id', $book->id)
                    ->where('hadith_number', $transformed['hadith_number'])
                    ->first();

                if ($existing) {
                    $toUpdate[] = [
                        'id' => $existing->id,
                        'data' => $transformed,
                    ];
                } else {
                    $toInsert[] = $transformed;
                }
            } catch (\Exception $e) {
                Log::error('Error transforming hadith', [
                    'error' => $e->getMessage(),
                    'hadith_number' => $hadithData->hadithNumber ?? 'unknown',
                ]);
                $skipped++;
            }
        }

        // Batch upsert (insert + update combined)
        if (! $this->dryRun) {
            $this->batchUpsert($toInsert, $toUpdate);
        }

        $recordsProcessed = count($toInsert) + count($toUpdate);
        if ($this->syncLog) {
            $this->syncLog->incrementSynced($recordsProcessed);
            if ($skipped > 0) {
                $this->syncLog->incrementSkipped($skipped);
            }
        }

        Log::info("Processed batch: {$recordsProcessed} hadiths (inserts: ".count($toInsert).', updates: '.count($toUpdate).", skipped: {$skipped})");
    }

    /**
     * Batch upsert hadiths (combined insert + update)
     * Uses single upsert query for better performance
     */
    private function batchUpsert(array $toInsert, array $toUpdate): void
    {
        if (empty($toInsert) && empty($toUpdate)) {
            return;
        }

        // Combine inserts and updates into single dataset
        $allRecords = array_merge(
            $toInsert,
            array_map(fn ($u) => $u['data'], $toUpdate)
        );

        if (empty($allRecords)) {
            return;
        }

        // Process in chunks with transaction wrapping
        foreach (array_chunk($allRecords, $this->batchSize) as $chunk) {
            try {
                // Wrap in transaction for atomic operation
                DB::transaction(function () use ($chunk) {
                    // Prepare JSON fields
                    $prepared = array_map(function ($hadith) {
                        if (is_array($hadith['translations'])) {
                            $hadith['translations'] = json_encode($hadith['translations']);
                        }

                        // Ensure timestamps
                        if (!isset($hadith['created_at'])) {
                            $hadith['created_at'] = now();
                        }
                        $hadith['updated_at'] = now();

                        return $hadith;
                    }, $chunk);

                    // Single upsert query instead of N insert/update queries
                    DB::table('hadith')->upsert(
                        $prepared,
                        uniqueBy: ['book_id', 'hadith_number'], // Unique constraint
                        update: [
                            'arabic_text',
                            'translations',
                            'grade',
                            'urn',
                            'chapter_id',
                            'synced_at',
                            'updated_at',
                        ]
                    );
                }, attempts: 3);

                Log::info('Bulk upsert succeeded', ['count' => count($chunk)]);
            } catch (\Exception $e) {
                Log::error('Bulk upsert failed', [
                    'error' => $e->getMessage(),
                    'count' => count($chunk),
                ]);

                // Fallback to individual inserts
                $this->fallbackIndividualUpserts($chunk);
            }
        }
    }

    /**
     * Fallback to individual upserts when bulk operation fails
     */
    private function fallbackIndividualUpserts(array $hadiths): void
    {
        foreach ($hadiths as $hadith) {
            try {
                // Prepare data
                if (is_array($hadith['translations'])) {
                    $hadith['translations'] = json_encode($hadith['translations']);
                }

                if (!isset($hadith['created_at'])) {
                    $hadith['created_at'] = now();
                }
                $hadith['updated_at'] = now();

                // Try upsert
                DB::table('hadith')->upsert(
                    [$hadith],
                    uniqueBy: ['book_id', 'hadith_number'],
                    update: [
                        'arabic_text',
                        'translations',
                        'grade',
                        'urn',
                        'chapter_id',
                        'synced_at',
                        'updated_at',
                    ]
                );
            } catch (\Exception $individualError) {
                Log::error('Individual upsert failed for hadith', [
                    'hadith_number' => $hadith['hadith_number'] ?? 'unknown',
                    'error' => $individualError->getMessage(),
                ]);
            }
        }
    }

    /**
     * Log error details
     */
    private function logError(string $message, mixed $data): void
    {
        Log::error($message, [
            'data' => is_object($data) ? json_decode(json_encode($data), true) : $data,
        ]);
    }

    /**
     * Process a batch of already-fetched hadiths
     * Used by CollectionSyncService for direct sync pagination
     */
    public function processFetchedHadithsBatch(
        HadithBook $book,
        string $collectionName,
        string $bookNumber,
        array $hadiths,
        ?HadithSyncLog $syncLog = null,
        bool $dryRun = false
    ): void {
        $this->syncLog = $syncLog;
        $this->dryRun = $dryRun;

        $this->processBatch($book, $collectionName, $bookNumber, $hadiths);
    }

    /**
     * Sync hadiths for a specific collection and book
     */
    public function syncCollectionBook(
        string $collectionName,
        string $bookNumber,
        bool $dryRun = false
    ): array {
        $this->dryRun = $dryRun;

        // Get or create the book record by slug
        $book = HadithBook::where('slug', $collectionName)
            ->first();

        if (! $book) {
            throw new \Exception("Book not found for collection {$collectionName}");
        }

        // Create sync log
        $this->syncLog = HadithSyncLog::create([
            'collection_name' => $collectionName,
            'sync_type' => 'book',
            'status' => 'pending',
            'total_records' => 0, // Will be updated as we go
        ]);

        $this->syncLog->markInProgress('command');

        try {
            // Get total count
            $response = $this->apiClient->getHadithsByBook($collectionName, $bookNumber, 1, 1);
            $this->syncLog->update(['total_records' => $response->total ?? 0]);

            // Start syncing
            $this->syncBookHadiths($book, $collectionName, $bookNumber, $this->syncLog, $dryRun);

            if (! $dryRun) {
                $this->syncLog->markSuccess();
            }

            return [
                'success' => true,
                'message' => "Successfully synced {$this->syncLog->synced_records} hadiths",
                'sync_log_id' => $this->syncLog->id,
            ];
        } catch (\Exception $e) {
            $this->syncLog->markFailed($e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => "Failed to sync hadiths: {$e->getMessage()}",
                'sync_log_id' => $this->syncLog->id,
            ];
        }
    }
}
