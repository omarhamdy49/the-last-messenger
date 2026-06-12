<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Services;

use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Hadith\Models\HadithSyncLog;
use App\Services\Sunnah\SunnahApiClient;
use App\Services\Sunnah\SunnahDataTransformer;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * BookSyncService
 *
 * Handles synchronization of Hadith books from Sunnah.com API
 * Books are fetched per collection and saved to hadith_books table
 * Supports dry-run mode, progress tracking, and error handling with rate limiting
 */
class BookSyncService
{
    /**
     * Constructor
     */
    public function __construct(
        protected SunnahApiClient $apiClient,
        protected SunnahDataTransformer $transformer
    ) {}

    /**
     * Sync all books for a given collection
     *
     * @param  HadithCollection  $collection  The collection to sync books for
     * @param  HadithSyncLog|null  $syncLog  Optional sync log for progress tracking
     * @param  bool  $dryRun  If true, no database writes will occur
     * @return array Summary of sync operation with keys: total, successful, failed, skipped, books, errors
     */
    public function syncCollectionBooks(
        HadithCollection $collection,
        ?HadithSyncLog $syncLog = null,
        bool $dryRun = false
    ): array {
        $collectionName = $collection->name_en ?? $collection->name_ar;

        Log::info('Starting sync of books for collection', [
            'collection_id' => $collection->id,
            'collection_name' => $collectionName,
            'dry_run' => $dryRun,
        ]);

        $summary = [
            'collection_id' => $collection->id,
            'collection_name' => $collectionName,
            'total' => 0,
            'successful' => 0,
            'failed' => 0,
            'skipped' => 0,
            'books' => [],
            'errors' => [],
        ];

        try {
            // Mark sync as started
            if ($syncLog && ! $dryRun) {
                $syncLog->markStarted();
            }

            // Fetch all books for this collection
            $booksData = $this->fetchCollectionBooks($collectionName);

            $summary['total'] = count($booksData);

            foreach ($booksData as $index => $bookData) {
                try {
                    // Transform the data
                    $transformedData = $this->transformer->transformBook($bookData, $collectionName);

                    // Validate transformed data
                    if (! $this->transformer->validateBook($transformedData)) {
                        Log::warning('Invalid book data', [
                            'collection' => $collectionName,
                            'book_number' => $transformedData['book_number'] ?? 'unknown',
                        ]);
                        $summary['skipped']++;
                        $summary['errors'][] = [
                            'book_number' => $transformedData['book_number'] ?? 'unknown',
                            'error' => 'Invalid book data after transformation',
                        ];

                        continue;
                    }

                    if (! $dryRun) {
                        // Upsert book to database using raw column names
                        $book = HadithBook::updateOrCreate(
                            [
                                'collection_id' => $collection->id,
                                'book_number' => $transformedData['book_number'],
                            ],
                            [
                                'title_ar' => $transformedData['name']['ar'] ?? null,
                                'title_en' => $transformedData['name']['en'] ?? null,
                                'chapter_title_ar' => $transformedData['description']['ar'] ?? null,
                                'chapter_title_en' => $transformedData['description']['en'] ?? null,
                            ]
                        );

                        Log::info('Book synced successfully', [
                            'book_id' => $book->id,
                            'collection_id' => $collection->id,
                            'book_number' => $transformedData['book_number'],
                        ]);
                    } else {
                        Log::info('[DRY RUN] Would sync book', [
                            'collection' => $collectionName,
                            'book_number' => $transformedData['book_number'],
                            'title_ar' => $transformedData['name']['ar'] ?? '',
                            'title_en' => $transformedData['name']['en'] ?? '',
                        ]);
                    }

                    $summary['successful']++;
                    $summary['books'][] = $transformedData['book_number'];

                    // Update sync log progress
                    if ($syncLog && ! $dryRun) {
                        $syncLog->updateProgress(1, 0, [
                            'last_book' => $transformedData['book_number'],
                            'collection' => $collectionName,
                        ]);
                    }

                    // Apply rate limiting (5 seconds between requests)
                    if ($index < count($booksData) - 1) {
                        Log::debug('Rate limiting: waiting 5 seconds before next book');
                        sleep(5);
                    }
                } catch (Exception $e) {
                    $summary['failed']++;
                    $summary['errors'][] = [
                        'book' => $bookData->bookNumber ?? 'unknown',
                        'error' => $e->getMessage(),
                    ];

                    Log::error('Failed to sync book', [
                        'collection' => $collectionName,
                        'book' => $bookData->bookNumber ?? 'unknown',
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    if ($syncLog && ! $dryRun) {
                        $syncLog->updateProgress(0, 1);
                    }
                }
            }

            // Mark sync as completed
            if ($syncLog && ! $dryRun) {
                $syncLog->markCompleted();
            }

            Log::info('Completed sync of books for collection', $summary);

            return $summary;
        } catch (Exception $e) {
            Log::error('Critical error during book sync', [
                'collection' => $collectionName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($syncLog && ! $dryRun) {
                $syncLog->markFailed($e->getMessage());
            }

            throw $e;
        }
    }

    /**
     * Sync a specific book by collection name and book number
     *
     * @param  string  $collectionName  The collection slug/name (e.g., 'bukhari', 'muslim')
     * @param  string  $bookNumber  The book number to sync
     * @param  int  $collectionId  The collection ID for database relationship
     * @param  HadithSyncLog|null  $syncLog  Optional sync log for progress tracking
     * @param  bool  $dryRun  If true, no database writes will occur
     * @return HadithBook|null The synced book model (null if dry run)
     *
     * @throws Exception
     */
    public function syncBook(
        string $collectionName,
        string $bookNumber,
        int $collectionId,
        ?HadithSyncLog $syncLog = null,
        bool $dryRun = false
    ): ?HadithBook {
        Log::info('Starting sync of book', [
            'collection' => $collectionName,
            'book_number' => $bookNumber,
            'dry_run' => $dryRun,
        ]);

        try {
            // Mark sync as started
            if ($syncLog && ! $dryRun) {
                $syncLog->markStarted();
            }

            // Fetch book from API
            $bookData = $this->apiClient->getBook($collectionName, $bookNumber);

            // Transform the data
            $transformedData = $this->transformer->transformBook($bookData, $collectionName);

            // Validate transformed data
            if (! $this->transformer->validateBook($transformedData)) {
                throw new Exception('Invalid book data after transformation');
            }

            if ($dryRun) {
                Log::info('[DRY RUN] Would sync book', [
                    'collection' => $collectionName,
                    'book_number' => $transformedData['book_number'],
                    'title_ar' => $transformedData['name']['ar'] ?? '',
                    'title_en' => $transformedData['name']['en'] ?? '',
                ]);

                return null;
            }

            // Upsert book to database using raw column names
            $book = HadithBook::updateOrCreate(
                [
                    'collection_id' => $collectionId,
                    'book_number' => $transformedData['book_number'],
                ],
                [
                    'title_ar' => $transformedData['name']['ar'] ?? null,
                    'title_en' => $transformedData['name']['en'] ?? null,
                    'chapter_title_ar' => $transformedData['description']['ar'] ?? null,
                    'chapter_title_en' => $transformedData['description']['en'] ?? null,
                ]
            );

            Log::info('Book synced successfully', [
                'book_id' => $book->id,
                'collection' => $collectionName,
                'book_number' => $bookNumber,
            ]);

            // Update sync log
            if ($syncLog) {
                $syncLog->updateProgress(1, 0, [
                    'last_book' => $bookNumber,
                    'collection' => $collectionName,
                ]);
                $syncLog->markCompleted();
            }

            return $book;
        } catch (Exception $e) {
            Log::error('Failed to sync book', [
                'collection' => $collectionName,
                'book_number' => $bookNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($syncLog && ! $dryRun) {
                $syncLog->markFailed($e->getMessage());
            }

            throw $e;
        }
    }

    /**
     * Fetch all books for a collection from Sunnah API
     *
     * This method handles pagination and fetches all available books for a collection
     *
     * @param  string  $collectionName  The collection slug/name
     * @return array Array of stdClass book objects from API
     *
     * @throws Exception
     */
    private function fetchCollectionBooks(string $collectionName): array
    {
        Log::info('Fetching books for collection from Sunnah API', [
            'collection' => $collectionName,
        ]);

        try {
            $allBooks = [];
            $page = 1;
            $limit = 50;
            $hasMore = true;

            while ($hasMore) {
                // Fetch page of books
                $response = $this->apiClient->getBooks($collectionName, $limit, $page);

                if (! isset($response->data) || ! is_array($response->data)) {
                    Log::warning('No data in API response', [
                        'collection' => $collectionName,
                        'page' => $page,
                    ]);
                    break;
                }

                $books = $response->data;
                $allBooks = array_merge($allBooks, $books);

                Log::info('Fetched books page', [
                    'collection' => $collectionName,
                    'page' => $page,
                    'count' => count($books),
                    'total_so_far' => count($allBooks),
                ]);

                // Check if there are more pages
                $hasMore = isset($response->next) && $response->next !== null;
                if ($hasMore) {
                    $page = is_int($response->next) ? $response->next : $page + 1;

                    // Apply rate limiting (5 seconds between requests)
                    Log::debug('Rate limiting: waiting 5 seconds before next page');
                    sleep(5);
                }
            }

            Log::info('Fetched all books for collection from API', [
                'collection' => $collectionName,
                'total_books' => count($allBooks),
            ]);

            return $allBooks;
        } catch (Exception $e) {
            Log::error('Failed to fetch books from API', [
                'collection' => $collectionName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
