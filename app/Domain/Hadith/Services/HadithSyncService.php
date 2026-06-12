<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Services;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithSyncLog;
use App\Domain\Hadith\Models\HadithTranslation;
use App\Services\Sunnah\SunnahApiClient;
use App\Services\Sunnah\SunnahDataTransformer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for syncing hadiths from Sunnah.com API
 *
 * Handles bulk synchronization of hadiths and their translations with proper
 * text sanitization, transaction wrapping, and error handling.
 *
 * IMPORTANT: Only stores 'ar' and 'en' languages per project requirements.
 */
class HadithSyncService
{
    /**
     * Number of hadiths to process per batch
     */
    private const BATCH_SIZE = 500;

    /**
     * Maximum retry attempts for failed transactions
     */
    private const MAX_RETRIES = 3;

    /**
     * Supported languages to store (ar, en only)
     */
    private const SUPPORTED_LANGUAGES = ['ar', 'en'];

    /**
     * Constructor
     */
    public function __construct(
        private readonly SunnahApiClient $apiClient,
        private readonly SunnahDataTransformer $transformer
    ) {}

    /**
     * Sync all hadiths for a specific book
     *
     * Fetches hadiths from Sunnah API with pagination, transforms data,
     * sanitizes Arabic text, and performs bulk upsert operations for both
     * hadiths and their translations.
     *
     * @param  HadithBook  $book  The book to sync hadiths for
     * @param  string  $collectionName  API collection name (e.g., 'bukhari')
     * @param  string  $bookNumber  API book number
     * @param  HadithSyncLog|null  $syncLog  Optional sync log for progress tracking
     * @param  bool  $dryRun  If true, only validate without persisting
     * @return int Number of hadiths successfully synced
     */
    public function syncBookHadiths(
        HadithBook $book,
        string $collectionName,
        string $bookNumber,
        ?HadithSyncLog $syncLog = null,
        bool $dryRun = false
    ): int {
        Log::info('hadith.sync.book.started', [
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
                // Fetch hadiths from API with pagination
                $response = $this->apiClient->getHadithsByBook(
                    $collectionName,
                    $bookNumber,
                    self::BATCH_SIZE,
                    $page
                );

                if (! isset($response->data) || empty($response->data)) {
                    Log::debug('hadith.sync.book.no_more_data', [
                        'book_number' => $bookNumber,
                        'page' => $page,
                    ]);
                    break;
                }

                $hadiths = is_array($response->data) ? $response->data : [$response->data];
                $transformedHadiths = [];

                // Transform and validate each hadith
                foreach ($hadiths as $hadithData) {
                    try {
                        $transformed = $this->transformer->transformHadith(
                            $hadithData,
                            $collectionName,
                            $bookNumber
                        );

                        // Validate transformed data
                        if (! $this->transformer->validateHadith($transformed)) {
                            Log::warning('Invalid hadith data, skipping', [
                                'hadith_number' => $transformed['hadith_number'] ?? 'unknown',
                                'book_id' => $book->id,
                            ]);
                            $failedCount++;

                            continue;
                        }

                        // Set book_id
                        $transformed['book_id'] = $book->id;

                        // Sanitize Arabic text (remove HTML, invisible chars)
                        if (! empty($transformed['arabic_text'])) {
                            $transformed['arabic_text'] = $this->sanitizeText($transformed['arabic_text']);
                        }

                        // Sanitize translations (only ar and en)
                        if (! empty($transformed['translations'])) {
                            foreach (self::SUPPORTED_LANGUAGES as $lang) {
                                if (isset($transformed['translations'][$lang])) {
                                    $transformed['translations'][$lang] = $this->sanitizeText(
                                        $transformed['translations'][$lang]
                                    );
                                }
                            }
                        }

                        $transformedHadiths[] = $transformed;
                    } catch (\Exception $e) {
                        Log::error('Failed to transform hadith', [
                            'book_id' => $book->id,
                            'error' => $e->getMessage(),
                        ]);
                        $failedCount++;
                    }
                }

                // Perform bulk upsert if not dry run
                if (! $dryRun && ! empty($transformedHadiths)) {
                    $result = $this->batchUpsert($transformedHadiths);
                    $syncedCount += $result['success'];
                    $failedCount += $result['failed'];

                    // Update sync log progress
                    if ($syncLog) {
                        $summary = $syncLog->summary ?? [];
                        $summary['synced_records'] = ($summary['synced_records'] ?? 0) + $result['success'];
                        $summary['failed_records'] = ($summary['failed_records'] ?? 0) + $result['failed'];
                        $syncLog->updateProgress($summary);
                    }

                    Log::info('hadith.sync.book.batch_processed', [
                        'success' => $result['success'],
                        'inserts' => $result['inserts'],
                        'updates' => $result['updates'],
                        'skipped' => $result['failed'],
                    ]);
                }

                // Check for next page
                $hasMore = isset($response->next) && $response->next !== null;
                $page = $response->next ?? $page + 1;

                // Apply rate limiting (respect API limits)
                if ($hasMore) {
                    usleep(100000); // 100ms delay between pages
                }
            }

            Log::info('hadith.sync.book.completed', [
                'book_id' => $book->id,
                'book_number' => $bookNumber,
                'synced' => $syncedCount,
                'failed' => $failedCount,
            ]);

            return $syncedCount;
        } catch (\Exception $e) {
            Log::error('hadith.sync.book.error', [
                'book_id' => $book->id,
                'book_number' => $bookNumber,
                'collection' => $collectionName,
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
     * Batch upsert hadiths and their translations
     *
     * Performs bulk upsert operations with transaction wrapping and retry logic.
     * Stores hadiths first, then their translations separately for proper normalization.
     *
     * @param  array  $hadiths  Array of hadith data to upsert
     * @return array Statistics array with counts
     */
    public function batchUpsert(array $hadiths): array
    {
        if (empty($hadiths)) {
            return ['success' => 0, 'failed' => 0, 'inserts' => 0, 'updates' => 0];
        }

        $successCount = 0;
        $failedCount = 0;
        $insertCount = 0;
        $updateCount = 0;

        // Process in chunks for memory efficiency
        $chunks = array_chunk($hadiths, self::BATCH_SIZE);

        foreach ($chunks as $chunk) {
            $attempt = 0;
            $success = false;

            // Try bulk upsert with retries
            while ($attempt < self::MAX_RETRIES && ! $success) {
                try {
                    DB::transaction(function () use ($chunk, &$successCount, &$insertCount, &$updateCount) {
                        // Step 1: Upsert hadiths
                        $hadithData = [];
                        $translationData = [];

                        foreach ($chunk as $hadith) {
                            // Prepare hadith record
                            $hadithData[] = [
                                'book_id' => $hadith['book_id'],
                                'hadith_number' => $hadith['hadith_number'],
                                'grading_status' => $hadith['grade'] ?? 'unknown',
                                'grading_agreement' => null, // Not available from API
                                'created_at' => now(),
                            ];

                            // Store translations separately for later processing
                            $translationData[] = [
                                'hadith_number' => $hadith['hadith_number'],
                                'book_id' => $hadith['book_id'],
                                'translations' => $hadith['translations'] ?? [],
                            ];
                        }

                        // Bulk upsert hadiths
                        if (! empty($hadithData)) {
                            DB::table('hadiths')->upsert(
                                $hadithData,
                                ['book_id', 'hadith_number'], // Unique constraint
                                ['grading_status', 'grading_agreement'] // Update fields
                            );
                        }

                        // Step 2: Get inserted hadith IDs and upsert translations
                        $this->upsertTranslations($translationData);

                        $successCount += count($hadithData);
                        $insertCount += count($hadithData); // Approximate
                    });

                    $success = true;
                } catch (\Exception $e) {
                    $attempt++;

                    if ($attempt >= self::MAX_RETRIES) {
                        // Fallback to individual inserts
                        Log::warning('hadith.sync.bulk_upsert.fallback', [
                            'attempts' => $attempt,
                            'chunk_size' => count($chunk),
                            'error' => $e->getMessage(),
                        ]);

                        $result = $this->fallbackIndividualInserts($chunk);
                        $successCount += $result['success'];
                        $failedCount += $result['failed'];
                        $success = true; // Mark as handled
                    } else {
                        Log::warning('hadith.sync.bulk_upsert.retry', [
                            'attempt' => $attempt,
                            'error' => $e->getMessage(),
                        ]);
                        usleep(100000 * $attempt); // Exponential backoff
                    }
                }
            }
        }

        return [
            'success' => $successCount,
            'failed' => $failedCount,
            'inserts' => $insertCount,
            'updates' => $updateCount,
        ];
    }

    /**
     * Upsert translations for hadiths
     *
     * IMPORTANT: Only stores 'ar' and 'en' languages
     *
     * @param  array  $translationData  Array of translation data
     */
    private function upsertTranslations(array $translationData): void
    {
        $translationsToInsert = [];

        foreach ($translationData as $item) {
            // Get the hadith ID from database
            $hadith = Hadith::where('book_id', $item['book_id'])
                ->where('hadith_number', $item['hadith_number'])
                ->first();

            if (! $hadith) {
                Log::warning('Hadith not found for translations', [
                    'book_id' => $item['book_id'],
                    'hadith_number' => $item['hadith_number'],
                ]);

                continue;
            }

            // Process only supported languages
            foreach (self::SUPPORTED_LANGUAGES as $lang) {
                if (! empty($item['translations'][$lang])) {
                    $translationsToInsert[] = [
                        'hadith_id' => $hadith->id,
                        'language_code' => $lang,
                        'matn' => $item['translations'][$lang],
                        'translator_name' => null, // Not available from API
                        'notes' => null,
                        'created_at' => now(),
                    ];
                }
            }
        }

        // Bulk upsert translations
        if (! empty($translationsToInsert)) {
            // Process in chunks to avoid memory issues
            foreach (array_chunk($translationsToInsert, 500) as $chunk) {
                DB::table('hadith_translations')->upsert(
                    $chunk,
                    ['hadith_id', 'language_code'], // Unique constraint
                    ['matn', 'translator_name', 'notes'] // Update fields
                );
            }
        }
    }

    /**
     * Fallback mechanism to insert hadiths individually when batch fails
     *
     * @param  array  $hadiths  Array of hadith data
     * @return array Statistics array
     */
    private function fallbackIndividualInserts(array $hadiths): array
    {
        $successCount = 0;
        $failedCount = 0;

        foreach ($hadiths as $hadith) {
            try {
                DB::transaction(function () use ($hadith, &$successCount) {
                    // Insert/update hadith
                    $hadithRecord = Hadith::updateOrCreate(
                        [
                            'book_id' => $hadith['book_id'],
                            'hadith_number' => $hadith['hadith_number'],
                        ],
                        [
                            'grading_status' => $hadith['grade'] ?? 'unknown',
                            'grading_agreement' => null,
                        ]
                    );

                    // Insert/update translations (only ar and en)
                    if (! empty($hadith['translations'])) {
                        foreach (self::SUPPORTED_LANGUAGES as $lang) {
                            if (! empty($hadith['translations'][$lang])) {
                                HadithTranslation::updateOrCreate(
                                    [
                                        'hadith_id' => $hadithRecord->id,
                                        'language_code' => $lang,
                                    ],
                                    [
                                        'matn' => $hadith['translations'][$lang],
                                        'translator_name' => null,
                                        'notes' => null,
                                    ]
                                );
                            }
                        }
                    }

                    $successCount++;
                });
            } catch (\Exception $e) {
                Log::error('Individual hadith insert failed', [
                    'hadith_number' => $hadith['hadith_number'],
                    'book_id' => $hadith['book_id'],
                    'error' => $e->getMessage(),
                ]);
                $failedCount++;
            }
        }

        Log::info('Fallback individual inserts completed', [
            'success' => $successCount,
            'failed' => $failedCount,
        ]);

        return ['success' => $successCount, 'failed' => $failedCount];
    }

    /**
     * Sanitize text by removing HTML tags and invisible characters
     *
     * Removes:
     * - HTML tags
     * - Zero-width characters
     * - Control characters
     * - Excess whitespace
     *
     * @param  string  $text  Text to sanitize
     * @return string Sanitized text
     */
    public function sanitizeText(string $text): string
    {
        // Remove HTML tags
        $text = strip_tags($text);

        // Remove zero-width and invisible characters
        $text = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $text);

        // Remove control characters (except newlines and tabs)
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);

        // Normalize whitespace (convert multiple spaces to single)
        $text = preg_replace('/[ \t]+/', ' ', $text);

        // Normalize line breaks
        $text = preg_replace('/\r\n|\r/', "\n", $text);

        // Remove excessive line breaks (more than 2 consecutive)
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        // Trim whitespace
        $text = trim($text);

        return $text;
    }

    /**
     * Get hadith count for a specific book
     *
     * @param  int  $bookId  The book ID
     * @return int Number of hadiths
     */
    public function getHadithCount(int $bookId): int
    {
        return Hadith::where('book_id', $bookId)->count();
    }

    /**
     * Get translation count for a specific hadith
     *
     * @param  int  $hadithId  The hadith ID
     * @return int Number of translations
     */
    public function getTranslationCount(int $hadithId): int
    {
        return HadithTranslation::where('hadith_id', $hadithId)->count();
    }

    /**
     * Verify sync integrity for a book
     *
     * Checks if all hadiths have the required translations
     *
     * @param  int  $bookId  The book ID
     * @return array Integrity report
     */
    public function verifySyncIntegrity(int $bookId): array
    {
        $hadiths = Hadith::where('book_id', $bookId)->with('translations')->get();
        $missingTranslations = [];

        foreach ($hadiths as $hadith) {
            $translations = $hadith->translations->pluck('language_code')->toArray();

            foreach (self::SUPPORTED_LANGUAGES as $lang) {
                if (! in_array($lang, $translations)) {
                    $missingTranslations[] = [
                        'hadith_id' => $hadith->id,
                        'hadith_number' => $hadith->hadith_number,
                        'missing_language' => $lang,
                    ];
                }
            }
        }

        return [
            'total_hadiths' => $hadiths->count(),
            'missing_translations_count' => count($missingTranslations),
            'missing_translations' => $missingTranslations,
        ];
    }
}
