<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Services;

use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithChapter;
use App\Domain\Hadith\Models\HadithSyncLog;
use App\Services\Sunnah\SunnahApiClient;
use App\Services\Sunnah\SunnahDataTransformer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChapterSyncService
{
    private SunnahApiClient $apiClient;
    private SunnahDataTransformer $transformer;
    private bool $dryRun = false;

    public function __construct(
        SunnahApiClient $apiClient,
        SunnahDataTransformer $transformer
    ) {
        $this->apiClient = $apiClient;
        $this->transformer = $transformer;
    }

    /**
     * Sync chapters for a book
     */
    public function syncBookChapters(
        HadithBook $book,
        string $collectionName,
        string $bookNumber,
        ?HadithSyncLog $syncLog = null,
        bool $dryRun = false
    ): void {
        $this->dryRun = $dryRun;

        Log::info("Starting chapter sync for book {$bookNumber} in collection {$collectionName}");

        try {
            $page = 1;
            $hasMore = true;

            while ($hasMore) {
                try {
                    // Fetch chapters from API
                    $response = $this->apiClient->getChapters($collectionName, $bookNumber, 1000, $page);

                    if (!isset($response->data) || empty($response->data)) {
                        $hasMore = false;
                        break;
                    }

                    // Process batch
                    $this->processBatch($book, $collectionName, $bookNumber, $response->data, $syncLog);

                    // Check if there's a next page
                    if (!isset($response->next) || $response->next === null) {
                        $hasMore = false;
                    } else {
                        $page = $response->next;
                    }
                } catch (\Exception $e) {
                    Log::error("Error fetching chapters page {$page} for book {$bookNumber}", [
                        'error' => $e->getMessage(),
                        'collection' => $collectionName,
                    ]);

                    if ($syncLog) {
                        $syncLog->incrementFailed();
                    }

                    $hasMore = false;
                }
            }

            Log::info("Completed chapter sync for book {$bookNumber}");
        } catch (\Exception $e) {
            Log::error("Critical error syncing chapters for book {$bookNumber}", [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Process a batch of chapters
     */
    private function processBatch(
        HadithBook $book,
        string $collectionName,
        string $bookNumber,
        array $chapters,
        ?HadithSyncLog $syncLog = null
    ): void {
        $toInsert = [];
        $toUpdate = [];
        $skipped = 0;

        foreach ($chapters as $chapterData) {
            try {
                // Validate before transformation
                if (!isset($chapterData->chapterId)) {
                    Log::debug("Skipping chapter - invalid structure", ['data' => (array) $chapterData]);
                    $skipped++;
                    continue;
                }

                // Transform the chapter
                $transformed = $this->transformer->transformChapter($chapterData, $collectionName, $bookNumber);

                // Validate transformed data
                if (!$this->transformer->validateChapter($transformed)) {
                    Log::debug("Skipping chapter - validation failed", [
                        'chapter_number' => $chapterData->chapterId,
                    ]);
                    $skipped++;
                    continue;
                }

                // Set book relationship
                $transformed['book_id'] = $book->id;
                $transformed['synced_at'] = now();

                // Check if chapter exists
                $existing = HadithChapter::where('book_id', $book->id)
                    ->where('chapter_number', $transformed['chapter_number'])
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
                Log::error("Error transforming chapter", [
                    'error' => $e->getMessage(),
                    'chapter_id' => $chapterData->chapterId ?? 'unknown',
                ]);
                $skipped++;
            }
        }

        // Batch insert and update
        if (!$this->dryRun) {
            $this->batchInsert($toInsert);
            $this->batchUpdate($toUpdate);
        }

        $recordsProcessed = count($toInsert) + count($toUpdate);
        if ($syncLog) {
            $syncLog->incrementSynced($recordsProcessed);
            if ($skipped > 0) {
                $syncLog->incrementSkipped($skipped);
            }
        }
    }

    /**
     * Batch insert chapters
     */
    private function batchInsert(array $chapters): void
    {
        if (empty($chapters)) {
            return;
        }

        foreach (array_chunk($chapters, 500) as $chunk) {
            try {
                DB::table('hadith_chapters')->insertOrIgnore($chunk);
            } catch (\Exception $e) {
                Log::error("Batch chapter insert failed", [
                    'error' => $e->getMessage(),
                    'count' => count($chunk),
                ]);

                // Try inserting individually
                foreach ($chunk as $chapter) {
                    try {
                        DB::table('hadith_chapters')->insertOrIgnore([$chapter]);
                    } catch (\Exception $individualError) {
                        Log::error("Individual chapter insert failed", [
                            'chapter_number' => $chapter['chapter_number'],
                            'error' => $individualError->getMessage(),
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Batch update chapters
     */
    private function batchUpdate(array $updates): void
    {
        if (empty($updates)) {
            return;
        }

        foreach ($updates as $update) {
            try {
                HadithChapter::where('id', $update['id'])->update($update['data']);
            } catch (\Exception $e) {
                Log::error("Update failed for chapter", [
                    'id' => $update['id'],
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
