<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Services;

use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Hadith\Models\HadithSyncLog;
use App\Services\Sunnah\SunnahApiClient;
use App\Services\Sunnah\SunnahDataTransformer;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * CollectionSyncService
 *
 * Handles synchronization of Hadith collections from Sunnah.com API
 * Provides methods to sync all collections or individual collections
 * Supports dry-run mode, progress tracking, and error handling
 */
class CollectionSyncService
{
    /**
     * Constructor
     *
     * @param SunnahApiClient $apiClient
     * @param SunnahDataTransformer $transformer
     */
    public function __construct(
        protected SunnahApiClient $apiClient,
        protected SunnahDataTransformer $transformer
    ) {
    }

    /**
     * Sync all collections from Sunnah API
     *
     * @param HadithSyncLog|null $syncLog Optional sync log for progress tracking
     * @param bool $dryRun If true, no database writes will occur
     * @return array Summary of sync operation with keys: total, successful, failed, skipped, collections, errors
     */
    public function syncAllCollections(?HadithSyncLog $syncLog = null, bool $dryRun = false): array
    {
        Log::info('Starting sync of all Hadith collections', [
            'dry_run' => $dryRun,
            'sync_log_id' => $syncLog?->id,
        ]);

        $summary = [
            'total' => 0,
            'successful' => 0,
            'failed' => 0,
            'skipped' => 0,
            'collections' => [],
            'errors' => [],
        ];

        try {
            // Mark sync as started
            if ($syncLog && !$dryRun) {
                $syncLog->markStarted();
            }

            // Fetch all collections from API
            $collectionsData = $this->fetchAndSaveCollections($dryRun);

            $summary['total'] = count($collectionsData);

            foreach ($collectionsData as $collectionData) {
                try {
                    // Transform the data
                    $transformedData = $this->transformer->transformCollection($collectionData);

                    // Validate transformed data
                    if (!$this->transformer->validateCollection($transformedData)) {
                        Log::warning('Invalid collection data', [
                            'collection' => $transformedData['collection_name'] ?? 'unknown',
                        ]);
                        $summary['skipped']++;
                        $summary['errors'][] = [
                            'collection' => $transformedData['collection_name'] ?? 'unknown',
                            'error' => 'Invalid collection data after transformation',
                        ];
                        continue;
                    }

                    if (!$dryRun) {
                        // Upsert collection to database using raw column names
                        HadithCollection::updateOrCreate(
                            [
                                'name_en' => $transformedData['name']['en'] ?? '',
                            ],
                            [
                                'name_ar' => $transformedData['name']['ar'] ?? '',
                                'name_en' => $transformedData['name']['en'] ?? '',
                                'description' => $transformedData['description']['ar'] ?? $transformedData['description']['en'] ?? null,
                                'compiler' => $transformedData['author_name'],
                                'death_year_hijri' => null, // Not available from API
                            ]
                        );

                        Log::info('Collection synced successfully', [
                            'name' => $transformedData['collection_name'],
                        ]);
                    } else {
                        Log::info('[DRY RUN] Would sync collection', [
                            'name' => $transformedData['collection_name'],
                            'name_ar' => $transformedData['name']['ar'] ?? '',
                            'name_en' => $transformedData['name']['en'] ?? '',
                        ]);
                    }

                    $summary['successful']++;
                    $summary['collections'][] = $transformedData['collection_name'];

                    // Update sync log progress
                    if ($syncLog && !$dryRun) {
                        $syncLog->updateProgress(1, 0, ['last_collection' => $transformedData['collection_name']]);
                    }
                } catch (Exception $e) {
                    $summary['failed']++;
                    $summary['errors'][] = [
                        'collection' => $collectionData->name ?? 'unknown',
                        'error' => $e->getMessage(),
                    ];

                    Log::error('Failed to sync collection', [
                        'collection' => $collectionData->name ?? 'unknown',
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    if ($syncLog && !$dryRun) {
                        $syncLog->updateProgress(0, 1);
                    }
                }
            }

            // Mark sync as completed
            if ($syncLog && !$dryRun) {
                $syncLog->markCompleted();
            }

            Log::info('Completed sync of all Hadith collections', $summary);

            return $summary;
        } catch (Exception $e) {
            Log::error('Critical error during collection sync', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($syncLog && !$dryRun) {
                $syncLog->markFailed($e->getMessage());
            }

            throw $e;
        }
    }

    /**
     * Sync a specific collection by name
     *
     * @param string $collectionName The collection slug/name (e.g., 'bukhari', 'muslim')
     * @param HadithSyncLog|null $syncLog Optional sync log for progress tracking
     * @param bool $dryRun If true, no database writes will occur
     * @return HadithCollection|null The synced collection model (null if dry run)
     * @throws Exception
     */
    public function syncCollection(
        string $collectionName,
        ?HadithSyncLog $syncLog = null,
        bool $dryRun = false
    ): ?HadithCollection {
        Log::info('Starting sync of collection', [
            'collection' => $collectionName,
            'dry_run' => $dryRun,
        ]);

        try {
            // Mark sync as started
            if ($syncLog && !$dryRun) {
                $syncLog->markStarted();
            }

            // Fetch collection from API
            $collectionData = $this->apiClient->getCollection($collectionName);

            // Transform the data
            $transformedData = $this->transformer->transformCollection($collectionData);

            // Validate transformed data
            if (!$this->transformer->validateCollection($transformedData)) {
                throw new Exception('Invalid collection data after transformation');
            }

            if ($dryRun) {
                Log::info('[DRY RUN] Would sync collection', [
                    'name' => $transformedData['collection_name'],
                    'name_ar' => $transformedData['name']['ar'] ?? '',
                    'name_en' => $transformedData['name']['en'] ?? '',
                    'compiler' => $transformedData['author_name'],
                ]);

                return null;
            }

            // Upsert collection to database using raw column names
            $collection = HadithCollection::updateOrCreate(
                [
                    'name_en' => $transformedData['name']['en'] ?? '',
                ],
                [
                    'name_ar' => $transformedData['name']['ar'] ?? '',
                    'name_en' => $transformedData['name']['en'] ?? '',
                    'description' => $transformedData['description']['ar'] ?? $transformedData['description']['en'] ?? null,
                    'compiler' => $transformedData['author_name'],
                    'death_year_hijri' => null, // Not available from API
                ]
            );

            Log::info('Collection synced successfully', [
                'collection_id' => $collection->id,
                'name' => $transformedData['collection_name'],
            ]);

            // Update sync log
            if ($syncLog) {
                $syncLog->updateProgress(1, 0, ['last_collection' => $transformedData['collection_name']]);
                $syncLog->markCompleted();
            }

            return $collection;
        } catch (Exception $e) {
            Log::error('Failed to sync collection', [
                'collection' => $collectionName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($syncLog && !$dryRun) {
                $syncLog->markFailed($e->getMessage());
            }

            throw $e;
        }
    }

    /**
     * Fetch and save all collections from Sunnah API
     *
     * This method handles pagination and fetches all available collections
     *
     * @param bool $dryRun If true, data is fetched but not saved
     * @return array Array of stdClass collection objects from API
     * @throws Exception
     */
    public function fetchAndSaveCollections(bool $dryRun = false): array
    {
        Log::info('Fetching collections from Sunnah API', ['dry_run' => $dryRun]);

        try {
            $allCollections = [];
            $page = 1;
            $limit = 50;
            $hasMore = true;

            while ($hasMore) {
                // Fetch page of collections
                $response = $this->apiClient->getCollections($limit, $page);

                if (!isset($response->data) || !is_array($response->data)) {
                    Log::warning('No data in API response', ['page' => $page]);
                    break;
                }

                $collections = $response->data;
                $allCollections = array_merge($allCollections, $collections);

                Log::info('Fetched collections page', [
                    'page' => $page,
                    'count' => count($collections),
                    'total_so_far' => count($allCollections),
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

            Log::info('Fetched all collections from API', [
                'total_collections' => count($allCollections),
            ]);

            return $allCollections;
        } catch (Exception $e) {
            Log::error('Failed to fetch collections from API', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
