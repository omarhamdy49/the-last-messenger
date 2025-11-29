<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Services;

use App\Domain\Hadith\Models\Companion;
use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithApplication;
use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithChapter;
use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Hadith\Models\HadithCommentary;
use App\Domain\Hadith\Models\HadithFiqhRuling;
use App\Domain\Hadith\Models\HadithGrading;
use App\Domain\Hadith\Models\HadithGradingLevel;
use App\Domain\Hadith\Models\HadithIsnad;
use App\Domain\Hadith\Models\HadithKeyword;
use App\Domain\Hadith\Models\HadithNarrator;
use App\Domain\Hadith\Models\HadithRelatedVerse;
use App\Domain\Hadith\Models\HadithScholar;
use App\Domain\Hadith\Models\HadithSyncLog;
use App\Domain\Hadith\Models\HadithTheme;
use App\Domain\Hadith\Models\HadithTranslation;
use App\Services\Json\JsonDataTransformer;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * JsonImportService - Enhanced with Generic Transformer Support
 *
 * Handles bulk import of hadith data from JSON files with automatic format detection.
 * Supports multiple JSON formats:
 * - Single-hadith format (1 hadith in multiple collections)
 * - Collection-based format (1 collection with many hadiths)
 *
 * Imports all rich metadata:
 * - Narrators & Isnad chains
 * - Companions (Sahabah)
 * - Themes & Keywords
 * - Grading & Scholarly commentary
 * - Related Quranic verses
 * - Practical applications
 * - Historical context
 * - Fiqh rulings
 *
 * @package App\Domain\Hadith\Services
 */
class JsonImportService
{
    private const MAX_RETRIES = 3;

    public function __construct(
        protected JsonDataTransformer $transformer
    ) {
    }

    /**
     * Import hadith data from JSON file (auto-detects format)
     *
     * @param string $filePath Absolute path to JSON file
     * @param HadithSyncLog|null $syncLog Optional sync log for progress tracking
     * @param bool $dryRun If true, no database writes occur (validation only)
     * @return array Summary statistics
     * @throws Exception If file is invalid or import fails
     */
    public function importFromFile(string $filePath, ?HadithSyncLog $syncLog = null, bool $dryRun = false): array
    {
        Log::info('Starting JSON import from file', [
            'file' => $filePath,
            'dry_run' => $dryRun,
        ]);

        // Validate file
        if (!$this->validateJsonFile($filePath)) {
            throw new InvalidArgumentException("Invalid JSON file: {$filePath}");
        }

        try {
            // Mark sync as started
            if ($syncLog && !$dryRun) {
                $syncLog->markStarted();
            }

            // Read and decode JSON file
            $jsonContent = file_get_contents($filePath);
            $jsonData = json_decode($jsonContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new InvalidArgumentException('Invalid JSON syntax: '.json_last_error_msg());
            }

            // Transform using generic transformer (auto-detects format)
            $transformed = $this->transformer->transform($jsonData);

            // Route to appropriate import method based on type
            if ($transformed['type'] === 'single_hadith') {
                $summary = $this->importSingleHadithFormat($transformed, $syncLog, $dryRun);
            } elseif ($transformed['type'] === 'collection_based') {
                $summary = $this->importCollectionBasedFormat($transformed, $syncLog, $dryRun);
            } else {
                throw new InvalidArgumentException('Unknown JSON format type: '.$transformed['type']);
            }

            // Mark sync as completed
            if ($syncLog && !$dryRun) {
                $syncLog->markCompleted();
            }

            Log::info('Completed JSON import from file', [
                'file' => $filePath,
                'type' => $transformed['type'],
                'summary' => $summary,
            ]);

            return $summary;
        } catch (Exception $e) {
            Log::error('Failed to import JSON file', [
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);

            if ($syncLog && !$dryRun) {
                $syncLog->markFailed($e->getMessage(), ['file' => $filePath]);
            }

            throw $e;
        }
    }

    /**
     * Import single-hadith format (1 hadith in multiple collections)
     *
     * @param array $transformed Transformed data from JsonDataTransformer
     * @param HadithSyncLog|null $syncLog
     * @param bool $dryRun
     * @return array Summary statistics
     */
    protected function importSingleHadithFormat(array $transformed, ?HadithSyncLog $syncLog, bool $dryRun): array
    {
        $summary = [
            'collections' => 0,
            'books' => 0,
            'chapters' => 0,
            'hadiths' => 0,
            'translations' => 0,
            'narrators' => 0,
            'companions' => 0,
            'themes' => 0,
            'keywords' => 0,
            'gradings' => 0,
            'scholarly_comments' => 0,
            'related_verses' => 0,
            'applications' => 0,
            'fiqh_rulings' => 0,
        ];

        $records = $transformed['records'] ?? [];

        foreach ($records as $record) {
            try {
                DB::transaction(function () use ($record, &$summary, $syncLog, $dryRun) {
                    // 1. Import/Find Collection
                    $collection = $this->upsertCollection($record['collection'], $dryRun);
                    if ($collection) {
                        $summary['collections']++;
                    }

                    // 2. Import/Find Book
                    $book = $this->upsertBook($record['book'], $collection?->id, $dryRun);
                    if ($book) {
                        $summary['books']++;
                    }

                    // 3. Import/Find Chapter (if exists)
                    $chapter = null;
                    if (!empty($record['chapter'])) {
                        $chapter = $this->upsertChapter($record['chapter'], $book?->id, $dryRun);
                        if ($chapter) {
                            $summary['chapters']++;
                        }
                    }

                    // 4. Import/Find Companion (if exists)
                    $companion = null;
                    if (!empty($record['companion']) && !$dryRun) {
                        $companion = Companion::findOrCreateByName($record['companion']);
                        if ($companion) {
                            $summary['companions']++;
                        }
                    }

                    // 5. Import Hadith with extended metadata
                    $hadithData = $record['hadith'];
                    $hadithData['companion_id'] = $companion?->id;

                    // Add historical context if present
                    if (!empty($record['historical_context'])) {
                        $hadithData['historical_context_ar'] = $record['historical_context']['occasion_ar'] ?? '';
                        $hadithData['historical_context_en'] = $record['historical_context']['occasion_en'] ?? '';
                    }

                    $hadith = $this->upsertHadith($hadithData, $book?->id, $dryRun);
                    if ($hadith) {
                        $summary['hadiths']++;
                    }

                    if ($dryRun || !$hadith) {
                        return; // Skip rich metadata in dry-run
                    }

                    // 6. Import Translations
                    $summary['translations'] += $this->importTranslations($record['translations'], $hadith->id);

                    // 7. Import Narrators & Isnad Chain
                    $summary['narrators'] += $this->importNarrators($record['narrators'], $hadith->id);

                    // 8. Import Themes
                    $summary['themes'] += $this->importThemes($record['themes'], $hadith->id);

                    // 9. Import Keywords
                    $summary['keywords'] += $this->importKeywords($record['keywords'], $hadith->id);

                    // 10. Import Grading
                    $summary['gradings'] += $this->importGrading($record['grading'], $hadith->id);

                    // 11. Import Scholarly Comments
                    $summary['scholarly_comments'] += $this->importScholarlyComments($record['scholarly_comments'], $hadith->id);

                    // 12. Import Related Quranic Verses
                    $summary['related_verses'] += $this->importRelatedVerses($record['related_verses'], $hadith->id);

                    // 13. Import Practical Applications
                    $summary['applications'] += $this->importApplications($record['applications'], $hadith->id);

                    // 14. Import Fiqh Rulings
                    $summary['fiqh_rulings'] += $this->importFiqhRulings($record['fiqh_rulings'], $hadith->id);

                    // Update sync log progress
                    if ($syncLog) {
                        $syncLog->updateProgress($summary);
                    }
                });
            } catch (Exception $e) {
                Log::error('Failed to import single hadith record', [
                    'collection' => $record['collection']['name_en'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);

                if ($syncLog) {
                    $syncLog->incrementFailed();
                }

                // Continue with next record
            }
        }

        return $summary;
    }

    /**
     * Import collection-based format (1 collection with many hadiths)
     *
     * @param array $transformed Transformed data from JsonDataTransformer
     * @param HadithSyncLog|null $syncLog
     * @param bool $dryRun
     * @return array Summary statistics
     */
    protected function importCollectionBasedFormat(array $transformed, ?HadithSyncLog $syncLog, bool $dryRun): array
    {
        $summary = [
            'collections' => 0,
            'books' => 0,
            'chapters' => 0,
            'hadiths' => 0,
            'translations' => 0,
        ];

        try {
            // Import collection
            $collection = $this->upsertCollection($transformed['collection'], $dryRun);
            if ($collection) {
                $summary['collections'] = 1;
            }

            // Import book
            $book = $this->upsertBook($transformed['book'], $collection?->id, $dryRun);
            if ($book) {
                $summary['books'] = 1;
            }

            // Import chapters
            foreach ($transformed['chapters'] as $chapterData) {
                $chapter = $this->upsertChapter($chapterData, $book?->id, $dryRun);
                if ($chapter) {
                    $summary['chapters']++;
                }
            }

            // Import hadiths with translations
            foreach ($transformed['hadiths'] as $hadithRecord) {
                $hadith = $this->upsertHadith($hadithRecord['hadith'], $book?->id, $dryRun);
                if ($hadith) {
                    $summary['hadiths']++;

                    if (!$dryRun) {
                        $summary['translations'] += $this->importTranslations($hadithRecord['translations'], $hadith->id);
                    }
                }
            }

            if ($syncLog) {
                $syncLog->updateProgress($summary['hadiths'], 0, $summary);
            }
        } catch (Exception $e) {
            Log::error('Failed to import collection-based format', [
                'error' => $e->getMessage(),
            ]);

            if ($syncLog) {
                $syncLog->markFailed($e->getMessage());
            }

            throw $e;
        }

        return $summary;
    }

    // ========================================================================
    // UPSERT METHODS FOR CORE ENTITIES
    // ========================================================================

    protected function upsertCollection(array $data, bool $dryRun): ?HadithCollection
    {
        if ($dryRun) {
            Log::info('[DRY RUN] Would upsert collection', $data);

            return null;
        }

        return HadithCollection::updateOrCreate(
            ['name_en' => $data['name_en']],
            $data
        );
    }

    protected function upsertBook(array $data, ?int $collectionId, bool $dryRun): ?HadithBook
    {
        if ($dryRun || !$collectionId) {
            Log::info('[DRY RUN] Would upsert book', $data);

            return null;
        }

        $data['collection_id'] = $collectionId;

        // Remove book_number if present (not in schema)
        unset($data['book_number']);

        // Use collection_id + title_en as unique key
        $uniqueKey = [
            'collection_id' => $collectionId,
            'title_en' => $data['title_en'] ?? null,
        ];

        // If title_en is null, try title_ar
        if (empty($uniqueKey['title_en']) && !empty($data['title_ar'])) {
            $uniqueKey = [
                'collection_id' => $collectionId,
                'title_ar' => $data['title_ar'],
            ];
        }

        return HadithBook::updateOrCreate($uniqueKey, $data);
    }

    protected function upsertChapter(array $data, ?int $bookId, bool $dryRun): ?HadithChapter
    {
        if ($dryRun || !$bookId) {
            Log::info('[DRY RUN] Would upsert chapter', $data);

            return null;
        }

        $data['book_id'] = $bookId;

        return HadithChapter::updateOrCreate(
            [
                'book_id' => $bookId,
                'chapter_number' => $data['chapter_number'],
            ],
            $data
        );
    }

    protected function upsertHadith(array $data, ?int $bookId, bool $dryRun): ?Hadith
    {
        if ($dryRun || !$bookId) {
            Log::info('[DRY RUN] Would upsert hadith', $data);

            return null;
        }

        $data['book_id'] = $bookId;

        return Hadith::updateOrCreate(
            [
                'book_id' => $bookId,
                'hadith_number' => $data['hadith_number'],
            ],
            $data
        );
    }

    // ========================================================================
    // IMPORT METHODS FOR RICH METADATA
    // ========================================================================

    protected function importTranslations(array $translations, int $hadithId): int
    {
        $count = 0;

        foreach ($translations as $translationData) {
            try {
                $translationData['hadith_id'] = $hadithId;

                HadithTranslation::updateOrCreate(
                    [
                        'hadith_id' => $hadithId,
                        'language_code' => $translationData['language_code'],
                    ],
                    $translationData
                );

                $count++;
            } catch (Exception $e) {
                Log::error('Failed to import translation', [
                    'hadith_id' => $hadithId,
                    'language' => $translationData['language_code'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    protected function importNarrators(array $narrators, int $hadithId): int
    {
        $count = 0;

        foreach ($narrators as $narratorData) {
            try {
                // Find or create narrator
                $narrator = HadithNarrator::updateOrCreate(
                    ['name_ar' => $narratorData['name_ar']],
                    [
                        'name_en' => $narratorData['name_en'],
                        'full_name_ar' => $narratorData['full_name_ar'],
                        'full_name_en' => $narratorData['full_name_en'],
                        'birth_year_hijri' => $narratorData['birth_year_hijri'],
                        'death_year_hijri' => $narratorData['death_year_hijri'],
                        'reliability_grade' => $narratorData['reliability_grade'],
                        'biography' => $narratorData['biography_ar'] ?? $narratorData['biography_en'],
                    ]
                );

                // Create isnad chain entry
                HadithIsnad::updateOrCreate(
                    [
                        'hadith_id' => $hadithId,
                        'narrator_id' => $narrator->id,
                    ],
                    [
                        'position_in_chain' => $narratorData['position_in_chain'],
                        'transmission_method' => $narratorData['transmission_method'],
                    ]
                );

                $count++;
            } catch (Exception $e) {
                Log::error('Failed to import narrator', [
                    'hadith_id' => $hadithId,
                    'narrator' => $narratorData['name_en'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    protected function importThemes(array $themes, int $hadithId): int
    {
        $count = 0;

        foreach ($themes as $themeData) {
            try {
                if (empty($themeData['name_ar']) && empty($themeData['name_en'])) {
                    continue;
                }

                // Find or create theme (use theme_ar/theme_en column names)
                $theme = HadithTheme::firstOrCreate(
                    ['theme_ar' => $themeData['name_ar']],
                    [
                        'theme_en' => $themeData['name_en'],
                    ]
                );

                // Attach to hadith
                $hadith = Hadith::find($hadithId);
                if ($hadith && !$hadith->themes()->where('hadith_theme_id', $theme->id)->exists()) {
                    $hadith->themes()->attach($theme->id);
                }

                $count++;
            } catch (Exception $e) {
                Log::error('Failed to import theme', [
                    'hadith_id' => $hadithId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    protected function importKeywords(array $keywords, int $hadithId): int
    {
        $count = 0;

        foreach ($keywords as $keywordData) {
            try {
                $keyword = $keywordData['keyword'] ?? null;
                $language = $keywordData['language_code'] ?? 'ar';

                if (empty($keyword)) {
                    continue;
                }

                // Create keyword record (table has hadith_id, keyword_ar, keyword_en)
                $data = [
                    'hadith_id' => $hadithId,
                ];

                if ($language === 'ar') {
                    $data['keyword_ar'] = $keyword;
                } else {
                    $data['keyword_en'] = $keyword;
                }

                // Check if keyword already exists for this hadith
                $existing = HadithKeyword::where('hadith_id', $hadithId)
                    ->where($language === 'ar' ? 'keyword_ar' : 'keyword_en', $keyword)
                    ->first();

                if (!$existing) {
                    HadithKeyword::create($data);
                    $count++;
                }
            } catch (Exception $e) {
                Log::error('Failed to import keyword', [
                    'hadith_id' => $hadithId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    protected function importGrading(array $grading, int $hadithId): int
    {
        $count = 0;

        foreach ($grading as $gradingData) {
            try {
                // Get scholar name - support multiple field formats from transformer
                $scholarName = $gradingData['scholar_name']
                    ?? $gradingData['scholar_name_en']
                    ?? $gradingData['scholar_name_ar']
                    ?? null;

                if (empty($scholarName)) {
                    continue; // Skip if no scholar name
                }

                // Find or create scholar
                $scholar = HadithScholar::updateOrCreate(
                    ['name_en' => $scholarName],
                    [
                        'name_ar' => $gradingData['scholar_name_ar'] ?? $scholarName,
                    ]
                );

                // Get grading level - support multiple field formats
                $gradeEn = $gradingData['grading_level']
                    ?? $gradingData['grade_en']
                    ?? $gradingData['grade']
                    ?? 'unknown';
                $gradeAr = $gradingData['grade_ar'] ?? null;

                // Normalize grade code
                $gradeCode = strtolower(str_replace([' ', "'", '-'], ['_', '', '_'], $gradeEn));

                $gradingLevel = HadithGradingLevel::firstOrCreate(
                    ['grade_en' => $gradeCode],
                    [
                        'grade_ar' => $gradeAr ?? $gradeEn,
                        'description' => null,
                        'rank' => $this->getGradeRank($gradeCode),
                    ]
                );

                // Use updateOrCreate to prevent duplicates
                // Unique key: hadith_id + scholar_id
                $notes = $gradingData['notes_en'] ?? $gradingData['notes_ar'] ?? $gradingData['notes'] ?? null;

                HadithGrading::updateOrCreate(
                    [
                        'hadith_id' => $hadithId,
                        'scholar_id' => $scholar->id,
                    ],
                    [
                        'grading_level_id' => $gradingLevel->id,
                        'grading_notes' => $notes,
                        'source_reference' => $gradingData['source_reference'] ?? null,
                    ]
                );

                $count++;
            } catch (Exception $e) {
                Log::error('Failed to import grading', [
                    'hadith_id' => $hadithId,
                    'scholar' => $gradingData['scholar_name_en'] ?? $gradingData['scholar_name_ar'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    /**
     * Get the rank for a grading code (lower is more authentic)
     */
    protected function getGradeRank(string $gradeCode): int
    {
        $ranks = [
            'sahih' => 1,
            'sahih_lighairihi' => 2,
            'hasan' => 3,
            'hasan_lighairihi' => 4,
            'hasan_sahih' => 3,
            'daif' => 5,
            'daif_jiddan' => 6,
            'very_weak' => 6,
            'munkar' => 7,
            'shadh' => 7,
            'mursal' => 8,
            'munqati' => 8,
            'mudallas' => 8,
            'mawdu' => 9,
            'fabricated' => 9,
        ];

        return $ranks[$gradeCode] ?? 10;
    }

    protected function importScholarlyComments(array $comments, int $hadithId): int
    {
        $count = 0;

        foreach ($comments as $commentData) {
            try {
                // Get scholar name (use author column directly, not foreign key)
                $scholarName = $commentData['scholar_name_en'] ?? $commentData['scholar_name_ar'];
                if (empty($scholarName)) {
                    continue;
                }

                // Create commentary with Arabic text
                if (!empty($commentData['commentary_ar'])) {
                    HadithCommentary::updateOrCreate(
                        [
                            'hadith_id' => $hadithId,
                            'author' => $scholarName,
                            'language_code' => 'ar',
                        ],
                        [
                            'commentary_text' => $commentData['commentary_ar'],
                            'source' => $commentData['source_reference'] ?? null,
                        ]
                    );
                }

                // English commentary
                if (!empty($commentData['commentary_en'])) {
                    HadithCommentary::updateOrCreate(
                        [
                            'hadith_id' => $hadithId,
                            'author' => $scholarName,
                            'language_code' => 'en',
                        ],
                        [
                            'commentary_text' => $commentData['commentary_en'],
                            'source' => $commentData['source_reference'] ?? null,
                        ]
                    );
                }

                $count++;
            } catch (Exception $e) {
                Log::error('Failed to import scholarly comment', [
                    'hadith_id' => $hadithId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    protected function importRelatedVerses(array $verses, int $hadithId): int
    {
        $count = 0;

        foreach ($verses as $verseData) {
            try {
                // Use updateOrCreate to prevent duplicates
                // Unique key: hadith_id + surah + ayah
                HadithRelatedVerse::updateOrCreate(
                    [
                        'hadith_id' => $hadithId,
                        'surah' => $verseData['surah'],
                        'ayah' => $verseData['ayah'],
                    ],
                    [
                        'surah_name_ar' => $verseData['surah_name_ar'],
                        'surah_name_en' => $verseData['surah_name_en'],
                        'text_ar' => $verseData['text_ar'],
                        'text_en' => $verseData['text_en'],
                        'relevance' => $verseData['relevance'],
                    ]
                );

                $count++;
            } catch (Exception $e) {
                Log::error('Failed to import related verse', [
                    'hadith_id' => $hadithId,
                    'surah' => $verseData['surah'] ?? 'unknown',
                    'ayah' => $verseData['ayah'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    protected function importApplications(array $applications, int $hadithId): int
    {
        $count = 0;
        $order = 0;

        foreach ($applications as $appData) {
            try {
                // Use updateOrCreate to prevent duplicates
                // Unique key: hadith_id + category_en + description_en (or ar if en is empty)
                $uniqueKey = [
                    'hadith_id' => $hadithId,
                    'category_en' => $appData['category_en'],
                ];

                // If category_en is empty, use category_ar as part of key
                if (empty($appData['category_en']) && !empty($appData['category_ar'])) {
                    $uniqueKey = [
                        'hadith_id' => $hadithId,
                        'category_ar' => $appData['category_ar'],
                    ];
                }

                HadithApplication::updateOrCreate(
                    $uniqueKey,
                    [
                        'category_ar' => $appData['category_ar'],
                        'category_en' => $appData['category_en'],
                        'description_ar' => $appData['description_ar'],
                        'description_en' => $appData['description_en'],
                        'display_order' => $order++,
                    ]
                );

                $count++;
            } catch (Exception $e) {
                Log::error('Failed to import application', [
                    'hadith_id' => $hadithId,
                    'category' => $appData['category_en'] ?? $appData['category_ar'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    protected function importFiqhRulings(array $rulings, int $hadithId): int
    {
        $count = 0;
        $order = 0;

        foreach ($rulings as $rulingData) {
            try {
                // Use updateOrCreate to prevent duplicates
                // Unique key: hadith_id + principle + language_code
                HadithFiqhRuling::updateOrCreate(
                    [
                        'hadith_id' => $hadithId,
                        'principle' => $rulingData['principle'],
                        'language_code' => $rulingData['language_code'],
                    ],
                    [
                        'display_order' => $order++,
                    ]
                );

                $count++;
            } catch (Exception $e) {
                Log::error('Failed to import fiqh ruling', [
                    'hadith_id' => $hadithId,
                    'principle' => mb_substr($rulingData['principle'] ?? '', 0, 50),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    // ========================================================================
    // VALIDATION
    // ========================================================================

    public function validateJsonFile(string $filePath): bool
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return false;
        }

        if (pathinfo($filePath, PATHINFO_EXTENSION) !== 'json') {
            return false;
        }

        if (filesize($filePath) > 100 * 1024 * 1024) { // 100MB limit
            return false;
        }

        $jsonContent = file_get_contents($filePath);
        $jsonData = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        return $this->transformer->isValidData($jsonData);
    }
}
