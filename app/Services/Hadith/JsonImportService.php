<?php

declare(strict_types=1);

namespace App\Services\Hadith;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithChapter;
use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Hadith\Models\HadithSyncLog;
use App\Domain\Hadith\Models\HadithTranslation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for importing hadith data from JSON files
 *
 * Handles importing collections, books, chapters, and hadiths from various
 * JSON formats with proper validation, transformation, and bulk operations.
 */
class JsonImportService
{
    /**
     * Batch size for bulk operations
     */
    private const BATCH_SIZE = 500;

    /**
     * Supported languages
     */
    private const SUPPORTED_LANGUAGES = ['ar', 'en'];

    /**
     * Statistics tracking
     */
    private array $stats = [
        'collections' => 0,
        'books' => 0,
        'chapters' => 0,
        'hadiths' => 0,
        'translations' => 0,
        'errors' => 0,
    ];

    /**
     * Constructor
     */
    public function __construct(
        private readonly JsonDataTransformer $transformer
    ) {}

    /**
     * Import hadith data from JSON file
     *
     * @param  string  $filePath  Path to JSON file
     * @param  HadithSyncLog|null  $syncLog  Optional sync log for tracking
     * @param  bool  $dryRun  If true, validate without saving
     * @param  string|null  $collectionNameOverride  Override collection name detection
     * @return array Import statistics
     *
     * @throws \Exception If file not found or invalid JSON
     */
    public function importFromFile(
        string $filePath,
        ?HadithSyncLog $syncLog = null,
        bool $dryRun = false,
        ?string $collectionNameOverride = null
    ): array {
        // Reset statistics
        $this->resetStats();

        // Validate file exists
        if (! file_exists($filePath)) {
            throw new \Exception("File not found: {$filePath}");
        }

        if (! is_readable($filePath)) {
            throw new \Exception("File not readable: {$filePath}");
        }

        // Read and parse JSON
        $jsonContent = file_get_contents($filePath);
        $data = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON: '.json_last_error_msg());
        }

        Log::info("Starting JSON import from file: {$filePath}", [
            'dry_run' => $dryRun,
            'collection_override' => $collectionNameOverride,
        ]);

        // Detect collection name
        $collectionName = $collectionNameOverride
            ?? $data['collection_name']
            ?? $this->transformer->detectCollectionFromFilename($filePath);

        if (! $collectionName) {
            throw new \Exception('Could not detect collection name from file');
        }

        // Update sync log
        if ($syncLog) {
            $syncLog->update([
                'collection_name' => $collectionName,
                'status' => HadithSyncLog::STATUS_IN_PROGRESS,
            ]);
        }

        try {
            // Import based on JSON structure
            if (isset($data['collection'])) {
                // Full collection structure
                $this->importFullCollection($data, $collectionName, $dryRun, $syncLog);
            } elseif (isset($data['hadiths']) && is_array($data['hadiths'])) {
                // Hadiths-only format
                $this->importHadithsOnly($data, $collectionName, $dryRun, $syncLog);
            } else {
                throw new \Exception('Unrecognized JSON structure');
            }

            // Mark sync log as completed
            if ($syncLog && ! $dryRun) {
                $syncLog->update([
                    'status' => HadithSyncLog::STATUS_COMPLETED,
                    'completed_at' => now(),
                    'synced_records' => $this->stats['hadiths'],
                    'failed_records' => $this->stats['errors'],
                    'metadata' => array_merge($syncLog->metadata ?? [], [
                        'stats' => $this->stats,
                    ]),
                ]);
            }

            Log::info('JSON import completed successfully', [
                'file' => $filePath,
                'stats' => $this->stats,
            ]);

            return $this->stats;

        } catch (\Exception $e) {
            // Mark sync log as failed
            if ($syncLog) {
                $syncLog->markFailed($e->getMessage(), [
                    'file' => $filePath,
                    'stats' => $this->stats,
                ]);
            }

            Log::error('JSON import failed', [
                'file' => $filePath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Import full collection structure (collection > books > chapters > hadiths)
     *
     * @param  array  $data  Full JSON data
     * @param  string  $collectionName  Collection identifier
     * @param  bool  $dryRun  If true, validate only
     * @param  HadithSyncLog|null  $syncLog  Optional sync log
     */
    private function importFullCollection(
        array $data,
        string $collectionName,
        bool $dryRun,
        ?HadithSyncLog $syncLog
    ): void {
        // Step 1: Import collection
        $collectionData = $this->transformer->transformCollection($data['collection']);

        if (! $this->transformer->validateCollection($collectionData)) {
            throw new \Exception('Invalid collection data');
        }

        $collection = null;
        if (! $dryRun) {
            $collection = HadithCollection::updateOrCreate(
                ['name_en' => $collectionData['name_en']],
                $collectionData
            );
            $this->stats['collections']++;

            Log::debug('Collection imported', ['id' => $collection->id, 'name' => $collectionData['name_en']]);
        }

        // Step 2: Import books
        if (isset($data['books']) && is_array($data['books'])) {
            foreach ($data['books'] as $bookData) {
                $this->importBook($bookData, $collection?->id ?? 0, $dryRun, $syncLog);
            }
        }
    }

    /**
     * Import book and its chapters/hadiths
     *
     * @param  array  $bookData  Book data from JSON
     * @param  int  $collectionId  Collection database ID
     * @param  bool  $dryRun  If true, validate only
     * @param  HadithSyncLog|null  $syncLog  Optional sync log
     */
    private function importBook(
        array $bookData,
        int $collectionId,
        bool $dryRun,
        ?HadithSyncLog $syncLog
    ): void {
        // Transform and validate book
        $transformedBook = $this->transformer->transformBook($bookData, $collectionId);

        if (! $this->transformer->validateBook($transformedBook)) {
            Log::warning('Invalid book data, skipping', ['book' => $bookData]);
            $this->stats['errors']++;

            return;
        }

        $book = null;
        if (! $dryRun) {
            // Try to find existing book by title
            $book = HadithBook::where('collection_id', $collectionId)
                ->where('title->en', $transformedBook['title']['en'])
                ->first();

            if (! $book) {
                $book = HadithBook::create($transformedBook);
            }
            $this->stats['books']++;

            Log::debug('Book imported', ['id' => $book->id, 'title' => $transformedBook['title_en']]);
        }

        // Import chapters if present
        if (isset($bookData['chapters']) && is_array($bookData['chapters'])) {
            foreach ($bookData['chapters'] as $chapterData) {
                $this->importChapter($chapterData, $book?->id ?? 0, $collectionId, $dryRun, $syncLog);
            }
        }

        // Import hadiths if present
        if (isset($bookData['hadiths']) && is_array($bookData['hadiths'])) {
            $this->importHadithBatch($bookData['hadiths'], $book?->id ?? 0, null, $dryRun, $syncLog);
        }
    }

    /**
     * Import chapter and its hadiths
     *
     * @param  array  $chapterData  Chapter data from JSON
     * @param  int  $bookId  Book database ID
     * @param  int  $collectionId  Collection database ID
     * @param  bool  $dryRun  If true, validate only
     * @param  HadithSyncLog|null  $syncLog  Optional sync log
     */
    private function importChapter(
        array $chapterData,
        int $bookId,
        int $collectionId,
        bool $dryRun,
        ?HadithSyncLog $syncLog
    ): void {
        // Transform and validate chapter
        $transformedChapter = $this->transformer->transformChapter($chapterData, $bookId, $collectionId);

        if (! $this->transformer->validateChapter($transformedChapter)) {
            Log::warning('Invalid chapter data, skipping', ['chapter' => $chapterData]);
            $this->stats['errors']++;

            return;
        }

        $chapter = null;
        if (! $dryRun) {
            $chapter = HadithChapter::updateOrCreate(
                [
                    'book_id' => $bookId,
                    'chapter_number' => $transformedChapter['chapter_number'],
                ],
                $transformedChapter
            );
            $this->stats['chapters']++;

            Log::debug('Chapter imported', ['id' => $chapter->id, 'number' => $transformedChapter['chapter_number']]);
        }

        // Import hadiths if present
        if (isset($chapterData['hadiths']) && is_array($chapterData['hadiths'])) {
            $this->importHadithBatch($chapterData['hadiths'], $bookId, $chapter?->id, $dryRun, $syncLog);
        }
    }

    /**
     * Import hadiths-only format (no collection/book structure)
     *
     * Creates default collection and book if needed
     *
     * @param  array  $data  JSON data with 'hadiths' array
     * @param  string  $collectionName  Collection identifier
     * @param  bool  $dryRun  If true, validate only
     * @param  HadithSyncLog|null  $syncLog  Optional sync log
     */
    private function importHadithsOnly(
        array $data,
        string $collectionName,
        bool $dryRun,
        ?HadithSyncLog $syncLog
    ): void {
        $collection = null;
        $book = null;

        if (! $dryRun) {
            // Extract metadata if present
            $metadata = $data['metadata'] ?? $data;

            // Create or get collection
            $collection = HadithCollection::firstOrCreate(
                ['name_en' => ucfirst($collectionName)],
                [
                    'name_ar' => $data['name_ar'] ?? $metadata['arabic']['title'] ?? null,
                    'name_en' => $data['name_en'] ?? $metadata['english']['title'] ?? ucfirst($collectionName),
                    'compiler' => $data['compiler'] ?? $metadata['arabic']['author'] ?? $metadata['english']['author'] ?? null,
                    'description' => $data['description_en'] ?? $metadata['english']['introduction'] ?? null,
                ]
            );
            $this->stats['collections']++;

            // Create default book
            $bookTitleEn = $data['book_name_en'] ?? $metadata['english']['title'] ?? 'Default Book';
            $bookTitleAr = $data['book_name_ar'] ?? $metadata['arabic']['title'] ?? null;

            $book = HadithBook::where('collection_id', $collection->id)
                ->where('title_en', $bookTitleEn)
                ->first();

            if (! $book) {
                $book = HadithBook::create([
                    'collection_id' => $collection->id,
                    'title_ar' => $bookTitleAr,
                    'title_en' => $bookTitleEn,
                ]);
            }
            $this->stats['books']++;

            // Import chapters if present
            if (isset($data['chapters']) && is_array($data['chapters'])) {
                foreach ($data['chapters'] as $chapterData) {
                    try {
                        $transformedChapter = $this->transformer->transformChapter(
                            $chapterData,
                            $book->id
                        );

                        if ($this->transformer->validateChapter($transformedChapter)) {
                            HadithChapter::updateOrCreate(
                                [
                                    'book_id' => $transformedChapter['book_id'],
                                    'chapter_number' => $transformedChapter['chapter_number'],
                                ],
                                $transformedChapter
                            );
                            $this->stats['chapters']++;

                            Log::debug('Chapter imported', [
                                'book_id' => $book->id,
                                'chapter_number' => $transformedChapter['chapter_number'],
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::warning('Failed to import chapter', [
                            'chapter' => $chapterData,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        }

        // Import hadiths
        $this->importHadithBatch($data['hadiths'], $book?->id ?? 0, null, $dryRun, $syncLog);
    }

    /**
     * Import batch of hadiths with bulk operations
     *
     * @param  array  $hadiths  Array of hadith data
     * @param  int  $bookId  Book database ID
     * @param  int|null  $chapterId  Optional chapter database ID
     * @param  bool  $dryRun  If true, validate only
     * @param  HadithSyncLog|null  $syncLog  Optional sync log
     */
    private function importHadithBatch(
        array $hadiths,
        int $bookId,
        ?int $chapterId,
        bool $dryRun,
        ?HadithSyncLog $syncLog
    ): void {
        // Process in chunks for memory efficiency
        $chunks = array_chunk($hadiths, self::BATCH_SIZE);

        foreach ($chunks as $chunk) {
            $transformedHadiths = [];

            // Transform and validate each hadith
            foreach ($chunk as $hadithData) {
                try {
                    $transformed = $this->transformer->transformHadith($hadithData, $bookId, $chapterId);

                    if (! $this->transformer->validateHadith($transformed)) {
                        Log::warning('Invalid hadith data, skipping', ['hadith' => $hadithData]);
                        $this->stats['errors']++;

                        continue;
                    }

                    // Sanitize text
                    if (! empty($transformed['arabic_text'])) {
                        $transformed['arabic_text'] = $this->sanitizeText($transformed['arabic_text']);
                    }

                    foreach (self::SUPPORTED_LANGUAGES as $lang) {
                        if (isset($transformed['translations'][$lang])) {
                            $transformed['translations'][$lang] = $this->sanitizeText(
                                $transformed['translations'][$lang]
                            );
                        }
                    }

                    $transformedHadiths[] = $transformed;

                } catch (\Exception $e) {
                    Log::error('Failed to transform hadith', [
                        'hadith' => $hadithData,
                        'error' => $e->getMessage(),
                    ]);
                    $this->stats['errors']++;
                }
            }

            // Perform bulk upsert
            if (! $dryRun && ! empty($transformedHadiths)) {
                $result = $this->bulkUpsertHadiths($transformedHadiths);
                $this->stats['hadiths'] += $result['hadiths'];
                $this->stats['translations'] += $result['translations'];

                // Update sync log
                if ($syncLog) {
                    $syncLog->incrementSynced($result['hadiths']);
                    if ($result['failed'] > 0) {
                        $syncLog->incrementFailed($result['failed']);
                    }
                }
            } elseif ($dryRun) {
                // In dry run, just count valid hadiths
                $this->stats['hadiths'] += count($transformedHadiths);
            }
        }
    }

    /**
     * Bulk upsert hadiths and translations
     *
     * @param  array  $hadiths  Array of transformed hadith data
     * @return array Statistics ['hadiths' => count, 'translations' => count, 'failed' => count]
     */
    private function bulkUpsertHadiths(array $hadiths): array
    {
        $hadithCount = 0;
        $translationCount = 0;
        $failedCount = 0;

        try {
            DB::transaction(function () use ($hadiths, &$hadithCount, &$translationCount) {
                foreach ($hadiths as $hadithData) {
                    try {
                        // Insert/update hadith
                        $hadith = Hadith::updateOrCreate(
                            [
                                'book_id' => $hadithData['book_id'],
                                'hadith_number' => $hadithData['hadith_number'],
                            ],
                            [
                                'chapter_id' => $hadithData['chapter_id'],
                                'grading_status' => $hadithData['grading_status'],
                                'grading_agreement' => $hadithData['grading_agreement'],
                            ]
                        );
                        $hadithCount++;

                        // Insert/update translations
                        if (! empty($hadithData['translations'])) {
                            foreach (self::SUPPORTED_LANGUAGES as $lang) {
                                if (! empty($hadithData['translations'][$lang])) {
                                    HadithTranslation::updateOrCreate(
                                        [
                                            'hadith_id' => $hadith->id,
                                            'language_code' => $lang,
                                        ],
                                        [
                                            'matn' => $hadithData['translations'][$lang],
                                        ]
                                    );
                                    $translationCount++;
                                }
                            }
                        }

                    } catch (\Exception $e) {
                        Log::error('Failed to upsert individual hadith', [
                            'hadith_number' => $hadithData['hadith_number'],
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        } catch (\Exception $e) {
            Log::error('Bulk upsert transaction failed', [
                'error' => $e->getMessage(),
            ]);
            $failedCount = count($hadiths);
        }

        return [
            'hadiths' => $hadithCount,
            'translations' => $translationCount,
            'failed' => $failedCount,
        ];
    }

    /**
     * Sanitize text (remove HTML, invisible characters)
     *
     * @param  string  $text  Text to sanitize
     * @return string Sanitized text
     */
    private function sanitizeText(string $text): string
    {
        // Remove HTML tags
        $text = strip_tags($text);

        // Remove zero-width and invisible characters
        $text = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $text);

        // Remove control characters
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);

        // Normalize whitespace
        $text = preg_replace('/[ \t]+/', ' ', $text);

        // Normalize line breaks
        $text = preg_replace('/\r\n|\r/', "\n", $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }

    /**
     * Reset statistics counters
     */
    private function resetStats(): void
    {
        $this->stats = [
            'collections' => 0,
            'books' => 0,
            'chapters' => 0,
            'hadiths' => 0,
            'translations' => 0,
            'errors' => 0,
        ];
    }

    /**
     * Get current statistics
     *
     * @return array Statistics array
     */
    public function getStats(): array
    {
        return $this->stats;
    }
}
