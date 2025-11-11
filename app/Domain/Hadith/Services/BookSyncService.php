<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Services;

use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithSyncLog;
use App\Services\Sunnah\SunnahApiClient;
use App\Services\Sunnah\SunnahDataTransformer;
use Illuminate\Support\Facades\Log;

class BookSyncService
{
    private SunnahApiClient $apiClient;

    private SunnahDataTransformer $transformer;

    private ChapterSyncService $chapterSyncService;

    private HadithSyncService $hadithSyncService;

    private bool $dryRun = false;

    public function __construct(
        SunnahApiClient $apiClient,
        SunnahDataTransformer $transformer,
        ChapterSyncService $chapterSyncService,
        HadithSyncService $hadithSyncService
    ) {
        $this->apiClient = $apiClient;
        $this->transformer = $transformer;
        $this->chapterSyncService = $chapterSyncService;
        $this->hadithSyncService = $hadithSyncService;
    }

    /**
     * Sync all books for a collection
     */
    public function syncCollectionBooks(
        string $collectionName,
        ?int $collectionId = null,
        ?HadithSyncLog $syncLog = null,
        bool $dryRun = false,
        ?callable $progressCallback = null
    ): array {
        $this->dryRun = $dryRun;

        Log::info("Starting book sync for collection {$collectionName}", ['collection_id' => $collectionId]);

        try {
            // Always try to fetch books from API
            // The hasBooks flag is unreliable - some collections report false but have books
            // Example: 'virtues' reports hasBooks: false but has book #1

            $page = 1;
            $hasMore = true;
            $totalBooks = 0;
            $successCount = 0;

            while ($hasMore) {
                try {
                    // Fetch books from API
                    $response = $this->apiClient->getBooks($collectionName, 1000, $page);

                    if (! isset($response->data) || empty($response->data)) {
                        $hasMore = false;
                        break;
                    }

                    foreach ($response->data as $bookData) {
                        try {
                            $bookNumber = $bookData->bookNumber ?? null;

                            if (! $bookNumber) {
                                Log::warning('Book missing book number', ['data' => $bookData]);
                                if ($syncLog) {
                                    $syncLog->incrementFailed();
                                }

                                continue;
                            }

                            // Store or update book with collection_id
                            $this->syncBookRecord($collectionName, $bookData, $collectionId);

                            // Sync chapters and hadiths for this book
                            $this->syncBookContents($collectionName, (string) $bookNumber, $collectionId, $syncLog);

                            $successCount++;
                            $totalBooks++;

                            if ($progressCallback) {
                                $progressCallback($totalBooks);
                            }

                            // Add delay after each book (3 seconds)
                            $delayMs = config('sunnah.api.rate_limit.delay_after_book', 3000);
                            Log::debug("Waiting {$delayMs}ms before next book", ['current_book' => $bookNumber]);
                            usleep($delayMs * 1000); // Convert to microseconds
                        } catch (\Exception $e) {
                            Log::error("Error syncing book {$bookData->bookNumber}", [
                                'error' => $e->getMessage(),
                                'collection' => $collectionName,
                            ]);
                            if ($syncLog) {
                                $syncLog->incrementFailed();
                            }
                        }
                    }

                    // Check if there's a next page
                    if (! isset($response->next) || $response->next === null) {
                        $hasMore = false;
                    } else {
                        $page = $response->next;
                    }
                } catch (\Exception $e) {
                    Log::error("Error fetching books page {$page} for collection {$collectionName}", [
                        'error' => $e->getMessage(),
                    ]);

                    if ($syncLog) {
                        $syncLog->incrementFailed();
                    }

                    $hasMore = false;
                }
            }

            Log::info("Completed book sync for collection {$collectionName}. Total: {$totalBooks}, Success: {$successCount}");

            return [
                'total_books' => $totalBooks,
                'success_count' => $successCount,
            ];
        } catch (\Exception $e) {
            Log::error("Critical error syncing books for collection {$collectionName}", [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get the HadithSyncService instance (for use by CollectionSyncService)
     */
    public function getHadithSyncService(): HadithSyncService
    {
        return $this->hadithSyncService;
    }

    /**
     * Sync a single book record
     */
    private function syncBookRecord(string $collectionName, mixed $bookData, ?int $collectionId = null): void
    {
        try {
            // Transform book data
            $transformed = $this->transformer->transformBook($bookData, $collectionName);

            if (! $this->dryRun) {
                // Create slug for book (use collection name + book number for uniqueness)
                $bookSlug = $collectionName.'-'.($bookData->bookNumber ?? 1);

                // Get or create the book record
                $book = HadithBook::where('slug', $bookSlug)
                    ->where('collection_id', $collectionId)
                    ->first();

                if ($book) {
                    // Update existing book
                    $book->update([
                        'name' => $transformed['name'],
                        'description' => $transformed['description'],
                        'display_order' => $transformed['display_order'],
                    ]);

                    Log::info("Updated book {$bookSlug}");
                } else {
                    // Create new book
                    HadithBook::create([
                        'slug' => $bookSlug,
                        'collection_id' => $collectionId,
                        'name' => $transformed['name'],
                        'description' => $transformed['description'],
                        'author_name' => $transformed['author_name'] ?? null,
                        'total_hadith_count' => 0,
                        'display_order' => $transformed['display_order'],
                        'active' => true,
                    ]);

                    Log::info("Created book {$bookSlug} for collection_id {$collectionId}");
                }
            }
        } catch (\Exception $e) {
            Log::error('Error syncing book record', [
                'error' => $e->getMessage(),
                'book_number' => $bookData->bookNumber ?? 'unknown',
            ]);
            throw $e;
        }
    }

    /**
     * Sync chapters and hadiths for a book
     */
    private function syncBookContents(
        string $collectionName,
        string $bookNumber,
        ?int $collectionId = null,
        ?HadithSyncLog $syncLog = null
    ): void {
        try {
            // Build the book slug (same format as in syncBookRecord)
            $bookSlug = $collectionName.'-'.$bookNumber;

            // Get the book record by slug and collection_id
            $book = HadithBook::where('slug', $bookSlug)
                ->where('collection_id', $collectionId)
                ->first();

            if (! $book) {
                Log::warning("Book not found after sync for collection {$collectionName}", [
                    'book_slug' => $bookSlug,
                    'collection_id' => $collectionId,
                ]);

                return;
            }

            // Sync chapters
            $this->chapterSyncService->syncBookChapters(
                $book,
                $collectionName,
                $bookNumber,
                $syncLog,
                $this->dryRun
            );

            // Sync hadiths
            $this->hadithSyncService->syncBookHadiths(
                $book,
                $collectionName,
                $bookNumber,
                $syncLog,
                $this->dryRun
            );

            // Update hadith count
            if (! $this->dryRun) {
                $hadithCount = $book->hadiths()->count();
                $book->update(['total_hadith_count' => $hadithCount]);
            }
        } catch (\Exception $e) {
            Log::error('Error syncing book contents', [
                'error' => $e->getMessage(),
                'collection' => $collectionName,
                'book' => $bookNumber,
            ]);
            throw $e;
        }
    }
}
