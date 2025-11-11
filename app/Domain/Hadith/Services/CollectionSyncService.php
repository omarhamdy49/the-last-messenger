<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Services;

use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Hadith\Models\HadithSyncLog;
use App\Services\Sunnah\SunnahApiClient;
use App\Services\Sunnah\SunnahDataTransformer;
use Illuminate\Support\Facades\Log;

class CollectionSyncService
{
    private SunnahApiClient $apiClient;

    private SunnahDataTransformer $transformer;

    private BookSyncService $bookSyncService;

    private bool $dryRun = false;

    public function __construct(
        SunnahApiClient $apiClient,
        SunnahDataTransformer $transformer,
        BookSyncService $bookSyncService
    ) {
        $this->apiClient = $apiClient;
        $this->transformer = $transformer;
        $this->bookSyncService = $bookSyncService;
    }

    /**
     * Sync all collections from Sunnah API
     */
    public function syncAllCollections(
        bool $dryRun = false,
        ?callable $progressCallback = null
    ): HadithSyncLog {
        $this->dryRun = $dryRun;

        // Create sync log
        $syncLog = HadithSyncLog::create([
            'collection_name' => null,
            'sync_type' => 'full',
            'status' => 'pending',
            'total_records' => 0,
        ]);

        $syncLog->markInProgress('command');

        try {
            Log::info('Starting full collection sync', ['dry_run' => $dryRun]);

            $page = 1;
            $hasMore = true;
            $totalCollections = 0;
            $successCount = 0;

            while ($hasMore) {
                try {
                    // Fetch collections from API
                    $response = $this->apiClient->getCollections(100, $page);

                    if (! isset($response->data) || empty($response->data)) {
                        $hasMore = false;
                        break;
                    }

                    $totalCollections = $response->total ?? count($response->data);
                    $syncLog->update(['total_records' => $totalCollections]);

                    foreach ($response->data as $collectionData) {
                        try {
                            $collectionName = $collectionData->name ?? null;

                            if (! $collectionName) {
                                Log::warning('Collection missing name', ['data' => $collectionData]);
                                $syncLog->incrementFailed();

                                continue;
                            }

                            // Sync this collection
                            $this->syncCollection($collectionName, $syncLog);

                            $successCount++;

                            if ($progressCallback) {
                                $progressCallback($successCount, $totalCollections);
                            }

                            // Add delay after each collection (3 seconds)
                            $delayMs = config('sunnah.api.rate_limit.delay_after_book', 3000);
                            Log::info("Waiting {$delayMs}ms before next collection");
                            usleep($delayMs * 1000); // Convert to microseconds
                        } catch (\Exception $e) {
                            Log::error("Error syncing collection {$collectionData->name}", [
                                'error' => $e->getMessage(),
                            ]);
                            $syncLog->incrementFailed();
                        }
                    }

                    // Check if there's a next page
                    if (! isset($response->next) || $response->next === null) {
                        $hasMore = false;
                    } else {
                        $page = $response->next;
                    }
                } catch (\Exception $e) {
                    Log::error("Error fetching collections page {$page}", [
                        'error' => $e->getMessage(),
                    ]);
                    $syncLog->incrementFailed();
                    $hasMore = false;
                }
            }

            Log::info('Completed full collection sync', [
                'total' => $totalCollections,
                'success' => $successCount,
                'dry_run' => $dryRun,
            ]);

            if (! $dryRun) {
                $syncLog->markSuccess();
            } else {
                $syncLog->update(['status' => 'pending']); // Mark as pending since it was dry run
            }

            return $syncLog;
        } catch (\Exception $e) {
            Log::error('Critical error during full collection sync', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $syncLog->markFailed($e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Sync a specific collection by name
     *
     * This method handles collections with different structures:
     * - Collections with books and chapters (hasBooks: true, hasChapters: true)
     * - Collections with books only (hasBooks: true, hasChapters: false)
     * - Collections with neither (hasBooks: false, hasChapters: false)
     */
    public function syncCollection(
        string $collectionName,
        ?HadithSyncLog $parentSyncLog = null,
        bool $dryRun = false
    ): HadithSyncLog {
        $this->dryRun = $dryRun;

        // Create or update sync log for this collection
        $syncLog = HadithSyncLog::create([
            'collection_name' => $collectionName,
            'sync_type' => 'collection',
            'status' => 'pending',
            'total_records' => 0,
        ]);

        $syncLog->markInProgress('command');

        try {
            Log::info("Starting collection sync: {$collectionName}", ['dry_run' => $dryRun]);

            // Fetch collection details from API
            $collectionData = $this->apiClient->getCollection($collectionName);

            // Log collection structure for debugging
            Log::info('Collection structure', [
                'name' => $collectionName,
                'hasBooks' => $collectionData->hasBooks ?? false,
                'hasChapters' => $collectionData->hasChapters ?? false,
                'totalAvailableHadith' => $collectionData->totalAvailableHadith ?? 0,
            ]);

            // Validate and transform collection
            $transformed = $this->transformer->transformCollection($collectionData);

            if (! $this->transformer->validateCollection($transformed)) {
                throw new \Exception("Collection validation failed for {$collectionName}");
            }

            // Create or update HadithCollection record (separate from books)
            $hadithCollection = null;
            if (! $dryRun) {
                $hadithCollection = HadithCollection::where('slug', $collectionName)->first();

                if ($hadithCollection) {
                    // Update existing collection
                    $hadithCollection->update([
                        'name' => $transformed['name'],
                        'description' => $transformed['description'],
                        'author_name' => $transformed['author_name'],
                        'display_order' => $transformed['display_order'],
                    ]);
                } else {
                    // Create new collection
                    $hadithCollection = HadithCollection::create([
                        'slug' => $transformed['slug'],
                        'name' => $transformed['name'],
                        'description' => $transformed['description'],
                        'author_name' => $transformed['author_name'],
                        'display_order' => $transformed['display_order'],
                        'active' => true,
                        'featured' => false,
                    ]);
                }
            }

            // Determine sync strategy based on collection structure
            $hasBooks = $collectionData->hasBooks ?? false;
            $hasChapters = $collectionData->hasChapters ?? false;

            Log::info('Collection structure detected', [
                'collection' => $collectionName,
                'hasBooks' => $hasBooks,
                'hasChapters' => $hasChapters,
            ]);

            // Always try to sync books first (API hasBooks flag is unreliable)
            // The /hadiths endpoint requires authentication we don't have
            Log::info('Attempting to sync books (hasBooks flag may be unreliable)', ['collection' => $collectionName]);
            $result = $this->bookSyncService->syncCollectionBooks(
                $collectionName,
                $hadithCollection?->id,
                $syncLog,
                $dryRun,
                fn ($count) => Log::info("Progress: Synced {$count} books from collection {$collectionName}")
            );

            $syncLog->update([
                'total_records' => $result['success_count'] ?? 0,
                'synced_records' => $result['success_count'] ?? 0,
            ]);

            Log::info("Completed collection sync: {$collectionName}", [
                'books_synced' => $result['success_count'] ?? 0,
                'dry_run' => $dryRun,
                'has_books' => $hasBooks,
                'has_chapters' => $hasChapters,
            ]);

            if (! $dryRun) {
                $syncLog->markSuccess();
            } else {
                $syncLog->update(['status' => 'pending']);
            }

            if ($parentSyncLog) {
                $parentSyncLog->incrementSynced($syncLog->synced_records);
            }

            return $syncLog;
        } catch (\Exception $e) {
            Log::error("Failed to sync collection: {$collectionName}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $syncLog->markFailed($e->getMessage(), [
                'exception' => get_class($e),
                'collection' => $collectionName,
            ]);

            if ($parentSyncLog) {
                $parentSyncLog->incrementFailed();
            }

            throw $e;
        }
    }

    /**
     * Sync hadiths directly for collections without book structure
     * This handles pagination recursively with limit 100 until next page is null
     *
     * @return array{success_count: int, total_hadith: int}
     */
    private function syncCollectionHadithsDirectly(
        string $collectionName,
        ?int $collectionId,
        HadithSyncLog $syncLog,
        bool $dryRun
    ): array {
        try {
            Log::info("Starting direct hadith sync for collection {$collectionName}");

            // Create a HadithBook record if it doesn't exist (as placeholder for flat collections)
            $book = HadithBook::where('slug', $collectionName)->first();

            if (! $book && ! $dryRun) {
                // Create a single book record for the flat collection
                $book = HadithBook::create([
                    'slug' => $collectionName,
                    'collection_id' => $collectionId,
                    'name' => [
                        'ar' => $collectionName,
                        'en' => $collectionName,
                    ],
                    'description' => [
                        'ar' => '',
                        'en' => '',
                    ],
                    'author_name' => null,
                    'total_hadith_count' => 0,
                    'display_order' => 0,
                    'active' => true,
                ]);
            }

            if (! $book && $dryRun) {
                // For dry-run mode, create a temporary book object (not saved to DB)
                $book = new HadithBook([
                    'id' => 999999, // Temporary ID for dry-run
                    'slug' => $collectionName,
                    'collection_id' => $collectionId,
                    'name' => [
                        'ar' => $collectionName,
                        'en' => $collectionName,
                    ],
                    'description' => [
                        'ar' => '',
                        'en' => '',
                    ],
                    'author_name' => null,
                    'total_hadith_count' => 0,
                    'display_order' => 0,
                    'active' => true,
                ]);
                // Set exists flag to prevent save attempts
                $book->exists = true;
            }

            $totalHadiths = 0;
            $page = 1;
            $hasMore = true;

            // Paginate through all hadiths with limit 100
            while ($hasMore) {
                try {
                    Log::info("Fetching hadiths for collection {$collectionName}", ['page' => $page]);

                    // Fetch hadiths with limit of 100 per page
                    $response = $this->apiClient->getHadiths(
                        collection: $collectionName,
                        bookNumber: null,
                        chapterId: null,
                        hadithNumber: null,
                        limit: 100,
                        page: $page
                    );

                    // Validate response structure
                    if (! isset($response->data) || empty($response->data)) {
                        Log::info("No more hadiths found for collection {$collectionName}", ['page' => $page]);
                        $hasMore = false;
                        break;
                    }

                    $hadiths = $response->data;
                    $totalHadiths += count($hadiths);

                    Log::info('Fetched hadiths', [
                        'collection' => $collectionName,
                        'page' => $page,
                        'count' => count($hadiths),
                        'total_so_far' => $totalHadiths,
                    ]);

                    // Process this batch using HadithSyncService
                    // Convert fetched hadiths to array if needed
                    $hadithsArray = is_array($hadiths) ? $hadiths : array_values((array) $hadiths);

                    $this->bookSyncService->getHadithSyncService()->processFetchedHadithsBatch(
                        $book,
                        $collectionName,
                        '1', // Use '1' as book number for direct sync
                        $hadithsArray,
                        $syncLog,
                        $dryRun
                    );

                    // Check if there's a next page
                    // The response.next field indicates if pagination should continue
                    if (! isset($response->next) || $response->next === null) {
                        Log::info('No next page - pagination complete', ['collection' => $collectionName, 'last_page' => $page]);
                        $hasMore = false;
                    } else {
                        Log::info('Next page available', ['collection' => $collectionName, 'next_page' => $response->next]);
                        $page = $response->next;
                    }
                } catch (\Exception $e) {
                    Log::error("Error fetching hadiths page {$page} for collection {$collectionName}", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    $syncLog->incrementFailed();
                    $hasMore = false;
                }
            }

            Log::info("Completed direct hadith sync for collection {$collectionName}", [
                'total_hadith' => $totalHadiths,
            ]);

            return [
                'success_count' => $totalHadiths,
                'total_hadith' => $totalHadiths,
            ];
        } catch (\Exception $e) {
            Log::error("Failed to sync hadiths directly for collection {$collectionName}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $syncLog->incrementFailed();

            return [
                'success_count' => 0,
                'total_hadith' => 0,
            ];
        }
    }

    /**
     * Get list of available collections from API
     */
    public function getAvailableCollections(): array
    {
        try {
            Log::info('Fetching available collections from API');

            $response = $this->apiClient->getCollections(100, 1);

            if (! isset($response->data)) {
                return [];
            }

            return array_map(function ($collection) {
                return [
                    'name' => $collection->name ?? '',
                    'title' => $collection->collection[0]->title ?? $collection->name ?? '',
                    'total_hadith' => $collection->totalAvailableHadith ?? 0,
                ];
            }, $response->data);
        } catch (\Exception $e) {
            Log::error('Failed to fetch available collections', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }
}
