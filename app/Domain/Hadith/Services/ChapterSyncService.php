<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Services;

use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithSyncLog;
use App\Services\Sunnah\SunnahApiClient;
use App\Services\Sunnah\SunnahDataTransformer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for syncing chapters from Sunnah.com API
 *
 * Handles bulk synchronization of book chapters with proper error handling,
 * transaction wrapping, and fallback mechanisms for failed batch operations.
 */
class ChapterSyncService
{
    /**
     * Number of chapters to process per batch for bulk upsert
     */
    private const BATCH_SIZE = 100;

    /**
     * Maximum retry attempts for failed transactions
     */
    private const MAX_RETRIES = 3;

    /**
     * Constructor
     */
    public function __construct(
        private readonly SunnahApiClient $apiClient,
        private readonly SunnahDataTransformer $transformer
    ) {
    }

    /**
     * Sync all chapters for a specific book
     *
     * Fetches chapters from Sunnah API, transforms the data, and performs
     * bulk upsert operations with proper JSON encoding for translatable fields.
     *
     * CRITICAL: This method JSON-encodes name and description arrays before
     * insert to prevent "Array to string conversion" errors (per SYNC_STATUS_CURRENT.md)
     *
     * @param HadithBook $book The book to sync chapters for
     * @param string $collectionName API collection name (e.g., 'bukhari')
     * @param string $bookNumber API book number
     * @param HadithSyncLog|null $syncLog Optional sync log for progress tracking
     * @param bool $dryRun If true, only validate without persisting
     * @return int Number of chapters successfully synced
     */
    public function syncBookChapters(
        HadithBook $book,
        string $collectionName,
        string $bookNumber,
        ?HadithSyncLog $syncLog = null,
        bool $dryRun = false
    ): int {
        Log::info("Starting chapter sync for book {$bookNumber} in collection {$collectionName}", [
            'book_id' => $book->id,
            'collection' => $collectionName,
            'book_number' => $bookNumber,
            'dry_run' => $dryRun,
        ]);

        $syncedCount = 0;
        $failedCount = 0;
        $page = 1;
        $hasMore = true;

        try {
            while ($hasMore) {
                // Fetch chapters from API with pagination
                $response = $this->apiClient->getChapters($collectionName, $bookNumber, self::BATCH_SIZE, $page);

                if (!isset($response->data) || empty($response->data)) {
                    Log::debug("No more chapters found for book {$bookNumber} on page {$page}");
                    break;
                }

                $chapters = is_array($response->data) ? $response->data : [$response->data];
                $transformedChapters = [];

                // Transform each chapter
                foreach ($chapters as $chapterData) {
                    try {
                        $transformed = $this->transformer->transformChapter(
                            $chapterData,
                            $collectionName,
                            $bookNumber
                        );

                        // Validate transformed data
                        if (!$this->transformer->validateChapter($transformed)) {
                            Log::warning("Invalid chapter data, skipping", [
                                'chapter_number' => $transformed['chapter_number'] ?? 'unknown',
                                'book_id' => $book->id,
                            ]);
                            $failedCount++;
                            continue;
                        }

                        // Add book_id to the transformed data
                        $transformed['book_id'] = $book->id;

                        $transformedChapters[] = $transformed;
                    } catch (\Exception $e) {
                        Log::error("Failed to transform chapter", [
                            'book_id' => $book->id,
                            'error' => $e->getMessage(),
                        ]);
                        $failedCount++;
                    }
                }

                // Perform bulk upsert if not dry run
                if (!$dryRun && !empty($transformedChapters)) {
                    $count = $this->bulkUpsertChapters($transformedChapters, $book->id);
                    $syncedCount += $count;

                    // Update sync log progress
                    if ($syncLog) {
                        $summary = $syncLog->summary ?? [];
                        $summary['synced_records'] = ($summary['synced_records'] ?? 0) + $count;
                        $summary['failed_records'] = ($summary['failed_records'] ?? 0) + $failedCount;
                        $syncLog->updateProgress($summary);
                    }
                }

                // Check for next page
                $hasMore = isset($response->next) && $response->next !== null;
                $page = $response->next ?? $page + 1;

                Log::debug("Processed chapter batch", [
                    'page' => $page - 1,
                    'synced' => $syncedCount,
                    'failed' => $failedCount,
                    'has_more' => $hasMore,
                ]);
            }

            Log::info("Completed chapter sync for book {$bookNumber}", [
                'book_id' => $book->id,
                'synced' => $syncedCount,
                'failed' => $failedCount,
            ]);

            return $syncedCount;
        } catch (\Exception $e) {
            Log::error("Chapter sync failed for book {$bookNumber}", [
                'book_id' => $book->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($syncLog) {
                $summary = $syncLog->summary ?? [];
                $summary['failed_records'] = ($summary['failed_records'] ?? 0) + 1;
                $syncLog->updateProgress($summary);
            }

            throw $e;
        }
    }

    /**
     * Bulk upsert chapters with proper JSON encoding and error handling
     *
     * CRITICAL FIX: JSON-encodes name and description arrays before insert
     * to prevent "Array to string conversion" errors.
     *
     * Uses transaction wrapping with retry mechanism and fallback to individual
     * inserts if batch operation fails.
     *
     * @param array $chapters Array of chapter data to upsert
     * @param int $bookId The book ID these chapters belong to
     * @return int Number of chapters successfully upserted
     */
    private function bulkUpsertChapters(array $chapters, int $bookId): int
    {
        if (empty($chapters)) {
            return 0;
        }

        $successCount = 0;
        $attempt = 0;

        // Process in chunks for memory efficiency
        $chunks = array_chunk($chapters, self::BATCH_SIZE);

        foreach ($chunks as $chunk) {
            $attempt = 0;
            $success = false;

            // Try bulk upsert with retries
            while ($attempt < self::MAX_RETRIES && !$success) {
                try {
                    DB::transaction(function () use ($chunk, &$successCount) {
                        // CRITICAL: JSON-encode translatable fields before upsert
                        $preparedData = array_map(function ($chapter) {
                            return [
                                'book_id' => $chapter['book_id'],
                                'chapter_number' => $chapter['chapter_number'],
                                'external_chapter_id' => $chapter['external_chapter_id'] ?? (string) $chapter['chapter_number'],
                                'name' => json_encode($chapter['name']), // JSON encode for translatable field
                                'description' => json_encode($chapter['description'] ?? []), // JSON encode for translatable field
                                'display_order' => $chapter['display_order'] ?? $chapter['chapter_number'],
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }, $chunk);

                        // Perform bulk upsert
                        DB::table('hadith_books')->upsert(
                            $preparedData,
                            ['book_id', 'chapter_number'], // Unique constraint
                            ['name', 'description', 'external_chapter_id', 'display_order', 'updated_at'] // Update fields
                        );

                        $successCount += count($preparedData);

                        Log::info("Bulk upsert succeeded", ['count' => count($preparedData)]);
                    });

                    $success = true;
                } catch (\Exception $e) {
                    $attempt++;

                    if ($attempt >= self::MAX_RETRIES) {
                        // Fallback to individual inserts
                        Log::warning("Bulk upsert failed after {$attempt} attempts, falling back to individual inserts", [
                            'error' => $e->getMessage(),
                            'chunk_size' => count($chunk),
                        ]);

                        $successCount += $this->fallbackIndividualInserts($chunk, $bookId);
                        $success = true; // Mark as handled
                    } else {
                        Log::warning("Bulk upsert attempt {$attempt} failed, retrying", [
                            'error' => $e->getMessage(),
                        ]);
                        usleep(100000 * $attempt); // 100ms, 200ms, 300ms backoff
                    }
                }
            }
        }

        return $successCount;
    }

    /**
     * Fallback mechanism to insert chapters individually when batch fails
     *
     * CRITICAL: Also JSON-encodes name and description arrays
     *
     * @param array $chapters Array of chapter data
     * @param int $bookId The book ID
     * @return int Number of successfully inserted chapters
     */
    private function fallbackIndividualInserts(array $chapters, int $bookId): int
    {
        $successCount = 0;

        foreach ($chapters as $chapter) {
            try {
                DB::transaction(function () use ($chapter, &$successCount) {
                    $data = [
                        'book_id' => $chapter['book_id'],
                        'chapter_number' => $chapter['chapter_number'],
                        'external_chapter_id' => $chapter['external_chapter_id'] ?? (string) $chapter['chapter_number'],
                        'name' => json_encode($chapter['name']), // JSON encode
                        'description' => json_encode($chapter['description'] ?? []), // JSON encode
                        'display_order' => $chapter['display_order'] ?? $chapter['chapter_number'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    DB::table('hadith_books')->updateOrInsert(
                        [
                            'book_id' => $data['book_id'],
                            'chapter_number' => $data['chapter_number'],
                        ],
                        $data
                    );

                    $successCount++;
                });
            } catch (\Exception $e) {
                Log::error("Individual chapter insert failed", [
                    'chapter_number' => $chapter['chapter_number'],
                    'book_id' => $bookId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info("Fallback individual inserts completed", [
            'success' => $successCount,
            'total' => count($chapters),
        ]);

        return $successCount;
    }

    /**
     * Get chapter count for a specific book
     *
     * @param int $bookId The book ID
     * @return int Number of chapters
     */
    public function getChapterCount(int $bookId): int
    {
        return DB::table('hadith_books')
            ->where('book_id', $bookId)
            ->count();
    }
}
