<?php

declare(strict_types=1);

namespace App\Services\Json;

use Illuminate\Support\Str;

/**
 * HadithJsonTransformer
 *
 * Converts detected hadith JSON structures into the normalized database
 * record format expected by JsonImportService and SyncHadithFromJsonCommand.
 *
 * Supported input formats:
 * - single_hadith      — one file per hadith, cross-referenced to multiple collections
 * - single_hadith_flat — one file per hadith, Ahmad-style flat format (collection string, object-pair themes)
 * - nested_hadith      — hadith.collection + hadith.matn structure
 * - collection_based   — one file per collection containing many hadiths (old format)
 *
 * All public transform methods return a consistent envelope:
 * [
 *   'type'       => 'single_hadith' | 'collection_based',
 *   'records'    => [...],   // for single/nested
 *   'collection' => [...],   // for collection_based
 *   'book'       => [...],
 *   'chapters'   => [...],
 *   'hadiths'    => [...],
 * ]
 *
 * Per-field extraction logic lives in HadithRecordExtractors trait to keep this
 * class focused on orchestration.
 *
 * @see HadithRecordExtractors
 * @see HadithJsonValidator
 */
class HadithJsonTransformer
{
    use HadithRecordExtractors;

    private HadithJsonValidator $validator;

    public function __construct(?HadithJsonValidator $validator = null)
    {
        $this->validator = $validator ?? new HadithJsonValidator();
    }

    // ============================================================================
    // PUBLIC API
    // ============================================================================

    /**
     * Auto-detect structure type and transform accordingly
     *
     * @throws \InvalidArgumentException on unknown structure
     */
    public function transform(array $jsonData): array
    {
        $type = $this->validator->detectStructureType($jsonData);

        return match ($type) {
            'single_hadith'      => $this->transformSingleHadith($jsonData),
            'single_hadith_flat' => $this->transformSingleHadithFlat($jsonData),
            'nested_hadith'      => $this->transformNestedHadith($jsonData),
            'collection_based'   => $this->transformCollectionBased($jsonData),
            default              => throw new \InvalidArgumentException('Unknown or invalid JSON structure'),
        };
    }

    /**
     * Get statistics from JSON data (auto-detects format)
     *
     * @return array<string, mixed>
     */
    public function getStatistics(array $jsonData): array
    {
        $type = $this->validator->detectStructureType($jsonData);

        return match ($type) {
            'single_hadith' => [
                'type'                     => 'single_hadith',
                'collections_count'        => count($jsonData['collections'] ?? []),
                'narrators_count'          => count($jsonData['sanad']['narrators'] ?? []),
                'themes_count'             => count($jsonData['themes_en'] ?? []),
                'keywords_count'           => count($jsonData['keywords'] ?? []),
                'scholarly_comments_count' => count($jsonData['scholarly_comments'] ?? []),
                'related_verses_count'     => count($jsonData['related_verses'] ?? []),
                'applications_count'       => count($jsonData['applications'] ?? []),
                'has_companion'            => !empty($jsonData['companion']),
                'has_grading'              => !empty($jsonData['grading']),
            ],
            'single_hadith_flat' => [
                'type'                     => 'single_hadith_flat',
                'collections_count'        => 1,
                'narrators_count'          => count($jsonData['sanad'] ?? []),
                'themes_count'             => count($jsonData['themes'] ?? []),
                'keywords_count'           => count($jsonData['keywords'] ?? []),
                'scholarly_comments_count' => count($jsonData['scholarly_comments'] ?? []),
                'related_verses_count'     => count($jsonData['related_verses'] ?? []),
                'applications_count'       => count($jsonData['practical_applications'] ?? []),
                'has_companion'            => !empty($jsonData['companion']),
                'has_grading'              => !empty($jsonData['grading']),
            ],
            'nested_hadith' => [
                'type'              => 'nested_hadith',
                'collections_count' => 1,
                'themes_count'      => count($jsonData['hadith']['themes'] ?? []),
                'keywords_count'    => count($jsonData['hadith']['keywords']['arabic'] ?? [])
                                     + count($jsonData['hadith']['keywords']['english'] ?? []),
                'has_grading'       => !empty($jsonData['hadith']['grading']),
            ],
            'collection_based' => [
                'type'           => 'collection_based',
                'total_chapters' => count($jsonData['chapters'] ?? []),
                'total_hadiths'  => count($jsonData['hadiths'] ?? []),
                'book_id'        => $jsonData['metadata']['id'] ?? null,
                'has_arabic'     => !empty($jsonData['metadata']['arabic']['title'] ?? ''),
                'has_english'    => !empty($jsonData['metadata']['english']['title'] ?? ''),
            ],
            default => ['type' => 'unknown'],
        };
    }

    /**
     * Detect collection name from file path based on parent directory name
     */
    public function detectCollectionFromFilename(string $filePath): ?string
    {
        $directory = basename(dirname($filePath));

        $knownCollections = [
            'nawawi40' => 'nawawi40',
            'bukhari'  => 'bukhari',
            'muslim'   => 'muslim',
            'abudawud' => 'abudawud',
            'tirmidhi' => 'tirmidhi',
            'nasai'    => 'nasai',
            'ibnmajah' => 'ibnmajah',
            'malik'    => 'malik',
            'riyad'    => 'riyad-as-salihin',
            'bulugh'   => 'bulugh-al-maram',
            'ahmed'    => 'ahmed',
            'aladab'   => 'aladab-al-mufrad',
        ];

        $normalized = strtolower(str_replace(['-', '_', ' '], '', $directory));

        foreach ($knownCollections as $key => $value) {
            if (str_contains($normalized, $key)) {
                return $value;
            }
        }

        return $directory ?: null;
    }

    /**
     * Sanitize text by removing HTML, extra whitespace, and control characters
     */
    public function sanitizeText(string $text): string
    {
        $text = strip_tags($text);
        $text = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $text);
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text);
        $text = preg_replace('/[ \t]+/', ' ', $text);

        return trim($text);
    }

    // ============================================================================
    // TRANSFORM METHODS
    // ============================================================================

    /**
     * Transform single-hadith format to database records
     */
    protected function transformSingleHadith(array $jsonData): array
    {
        $result = [];
        $metadataCollection     = $jsonData['metadata']['collection'] ?? null;
        $metadataCollectionName = $metadataCollection['name_en'] ?? null;
        $processedCollections   = [];

        if ($metadataCollection) {
            $primaryCollectionData = null;
            foreach ($jsonData['collections'] as $collectionData) {
                if (($collectionData['name_en'] ?? '') === $metadataCollectionName) {
                    $primaryCollectionData = $collectionData;
                    break;
                }
            }

            $collectionInfo = $this->extractMetadataCollection($metadataCollection);

            if ($primaryCollectionData) {
                $bookInfo    = $this->extractBookFromSingle($primaryCollectionData);
                $chapterInfo = $this->extractChapterFromSingle($primaryCollectionData);
                $hadithInfo  = $this->extractHadithFromSingle($jsonData, $primaryCollectionData);
                if (isset($jsonData['metadata']['id_in_book'])) {
                    $hadithInfo['hadith_number'] = $jsonData['metadata']['id_in_book'];
                }
            } else {
                $bookInfo    = $this->extractMetadataBook($metadataCollection);
                $chapterInfo = $this->extractMetadataChapter($jsonData['metadata']);
                $hadithInfo  = $this->extractHadithFromSingle($jsonData, ['hadith_number' => $jsonData['metadata']['id_in_book'] ?? 0]);
            }

            $result[]               = $this->buildRecord($collectionInfo, $bookInfo, $chapterInfo, $hadithInfo, $jsonData);
            $processedCollections[] = $metadataCollectionName;
        }

        foreach ($jsonData['collections'] as $collectionData) {
            $collectionName = $collectionData['name_en'] ?? '';
            if (in_array($collectionName, $processedCollections)) {
                continue;
            }
            $result[] = $this->buildRecord(
                $this->extractCollectionFromSingle($collectionData),
                $this->extractBookFromSingle($collectionData),
                $this->extractChapterFromSingle($collectionData),
                $this->extractHadithFromSingle($jsonData, $collectionData),
                $jsonData,
            );
        }

        return ['type' => 'single_hadith', 'records' => $result];
    }

    /**
     * Transform flat single-hadith format (Ahmad-style) to a single normalized record.
     *
     * The flat format stores one hadith per file with a scalar `collection` field and
     * object-pair arrays for themes/keywords. The output envelope uses 'single_hadith'
     * so the existing importSingleHadithFormat handler picks it up without changes.
     */
    protected function transformSingleHadithFlat(array $jsonData): array
    {
        $collectionInfo = $this->extractCollectionFromFlat($jsonData);
        $bookInfo       = $this->extractBookFromFlat($jsonData);
        $chapterInfo    = $this->extractChapterFromFlat($jsonData);
        $hadithInfo     = $this->extractHadithFromFlat($jsonData);

        $record = [
            'collection'         => $collectionInfo,
            'book'               => $bookInfo,
            'chapter'            => $chapterInfo,
            'hadith'             => $hadithInfo,
            'translations'       => $this->extractTranslationsFromSingle($jsonData),
            'narrators'          => $this->extractNarratorsFromFlat($jsonData),
            'companion'          => $this->extractCompanionFromFlat($jsonData),
            'themes'             => $this->extractThemesFromFlat($jsonData),
            'keywords'           => $this->extractKeywordsFromFlat($jsonData),
            'grading'            => $this->extractGradingFromFlat($jsonData),
            'scholarly_comments' => $this->extractScholarlyCommentsFromFlat($jsonData),
            'related_verses'     => $this->extractRelatedVersesFromFlat($jsonData),
            'applications'       => $this->extractApplicationsFromFlat($jsonData),
            'fiqh_rulings'       => $this->extractFiqhRulingsFromFlat($jsonData),
            'historical_context' => $this->extractHistoricalContextFromFlat($jsonData),
        ];

        return ['type' => 'single_hadith', 'records' => [$record]];
    }

    /**
     * Transform collection-based format to database records
     */
    protected function transformCollectionBased(array $jsonData): array
    {
        $metadata = $jsonData['metadata'] ?? [];
        $arabic   = $metadata['arabic'] ?? [];
        $english  = $metadata['english'] ?? [];

        $nameAr = $this->sanitizeText($arabic['title'] ?? '');
        $nameEn = $this->sanitizeText($english['title'] ?? '');

        $collection = [
            'name_ar'        => $nameAr,
            'name_en'        => $nameEn,
            'slug_ar'        => Str::slug($nameAr, '-', 'ar'),
            'slug_en'        => Str::slug($nameEn),
            'description_ar' => null,
            'description_en' => null,
            'abbreviation'   => null,
        ];

        $book = [
            'title_ar'         => $nameAr,
            'title_en'         => $nameEn,
            'slug_ar'          => Str::slug($nameAr, '-', 'ar'),
            'slug_en'          => Str::slug($nameEn),
            'chapter_title_ar' => null,
            'chapter_title_en' => null,
        ];

        $chapters = [];
        foreach ($jsonData['chapters'] ?? [] as $chapterData) {
            $chapters[] = [
                'chapter_number' => $chapterData['id'] ?? 0,
                'name'           => json_encode([
                    'ar' => $this->sanitizeText($chapterData['arabic'] ?? ''),
                    'en' => $this->sanitizeText($chapterData['english'] ?? ''),
                ], JSON_UNESCAPED_UNICODE),
                'description'    => null,
                'display_order'  => $chapterData['id'] ?? 0,
            ];
        }

        $hadiths = [];
        foreach ($jsonData['hadiths'] ?? [] as $hadithData) {
            $hadiths[] = [
                'hadith' => [
                    'hadith_number'     => $hadithData['idInBook'] ?? $hadithData['id'] ?? 0,
                    'grading_status'    => null,
                    'grading_agreement' => null,
                ],
                'translations' => $this->transformTranslationsOldFormat($hadithData),
            ];
        }

        return [
            'type'       => 'collection_based',
            'collection' => $collection,
            'book'       => $book,
            'chapters'   => $chapters,
            'hadiths'    => $hadiths,
        ];
    }

    /**
     * Transform nested-hadith format to single_hadith records
     */
    protected function transformNestedHadith(array $jsonData): array
    {
        $hadith             = $jsonData['hadith'];
        $metadata           = $jsonData['metadata'] ?? [];
        $metadataCollection = $metadata['collection'] ?? [];
        $collection         = $hadith['collection'] ?? [];
        $book               = $hadith['book'] ?? [];
        $chapter            = $hadith['chapter'] ?? [];
        $matn               = $hadith['matn'] ?? [];
        $grading            = $hadith['grading']['primary'] ?? [];
        $hadithNumber       = $hadith['hadith_number'] ?? [];
        $themes             = $hadith['themes'] ?? [];
        $keywords           = $hadith['keywords'] ?? [];

        $collectionNameEn = $metadataCollection['name_en'] ?? $collection['name'] ?? '';
        $collectionNameAr = $metadataCollection['name_ar'] ?? $collection['arabic_name'] ?? '';

        $collectionInfo = [
            'name_ar'        => $this->sanitizeText($collectionNameAr),
            'name_en'        => $this->sanitizeText($collectionNameEn),
            'slug_ar'        => Str::slug($this->sanitizeText($collectionNameAr), '-', 'ar'),
            'slug_en'        => Str::slug($this->sanitizeText($collectionNameEn)),
            'description_ar' => null,
            'description_en' => null,
            'abbreviation'   => null,
            'total_hadiths'  => $metadataCollection['total_hadiths'] ?? $collection['total_hadiths'] ?? 0,
        ];

        $bookTitleAr = $this->sanitizeText($book['arabic_name'] ?? $collectionNameAr);
        $bookTitleEn = $this->sanitizeText($book['name'] ?? $collectionNameEn);

        $bookInfo = [
            'title_ar'         => $bookTitleAr,
            'title_en'         => $bookTitleEn,
            'slug_ar'          => Str::slug($bookTitleAr, '-', 'ar'),
            'slug_en'          => Str::slug($bookTitleEn),
            'chapter_title_ar' => null,
            'chapter_title_en' => null,
        ];

        $chapterInfo = null;
        if (!empty($chapter['number'])) {
            $chapterInfo = [
                'chapter_number' => $chapter['number'],
                'name'           => json_encode([
                    'ar' => $this->sanitizeText($chapter['arabic_title'] ?? ''),
                    'en' => $this->sanitizeText($chapter['title'] ?? ''),
                ], JSON_UNESCAPED_UNICODE),
                'description'    => null,
                'display_order'  => $chapter['number'],
            ];
        }

        $hadithNum  = $hadithNumber['in_book'] ?? $hadithNumber['overall'] ?? $metadata['id_in_book'] ?? 0;
        $hadithInfo = [
            'hadith_number'         => $hadithNum,
            'grading_status'        => $grading['grade'] ?? null,
            'grading_agreement'     => null,
            'significance_ar'       => '',
            'significance_en'       => '',
            'historical_context_ar' => '',
            'historical_context_en' => '',
            'verification_status'   => null,
            'uncertainty_notes'     => null,
        ];

        $translations = $this->buildNestedTranslations($matn);
        $themesList   = $this->buildNestedThemes($themes);
        $keywordsList = $this->buildNestedKeywords($keywords);
        $gradingList  = $this->buildNestedGrading($grading);

        $record = [
            'collection'         => $collectionInfo,
            'book'               => $bookInfo,
            'chapter'            => $chapterInfo,
            'hadith'             => $hadithInfo,
            'translations'       => $translations,
            'narrators'          => [],
            'companion'          => null,
            'themes'             => $themesList,
            'keywords'           => $keywordsList,
            'grading'            => $gradingList,
            'scholarly_comments' => [],
            'related_verses'     => [],
            'applications'       => [],
            'fiqh_rulings'       => [],
            'historical_context' => null,
        ];

        return ['type' => 'single_hadith', 'records' => [$record]];
    }

    // ============================================================================
    // COLLECTION-BASED FORMAT HELPER
    // ============================================================================

    /**
     * Transform translations for old (collection-based) format
     */
    protected function transformTranslationsOldFormat(array $hadithData): array
    {
        $translations = [];

        $arabicText = $hadithData['arabic'] ?? '';
        if (!empty($arabicText)) {
            $translations[] = ['language_code' => 'ar', 'matn' => $this->sanitizeText($arabicText), 'translator_name' => null, 'notes' => null];
        }

        $english = $hadithData['english'] ?? [];
        if (is_array($english)) {
            $englishText = trim(($english['narrator'] ?? '') . ' ' . ($english['text'] ?? ''));
        } else {
            $englishText = $english;
        }

        if (!empty($englishText)) {
            $translations[] = ['language_code' => 'en', 'matn' => $this->sanitizeText($englishText), 'translator_name' => null, 'notes' => null];
        }

        return $translations;
    }

    // ============================================================================
    // PRIVATE HELPERS
    // ============================================================================

    /**
     * Build a normalized record array from extracted parts
     */
    private function buildRecord(
        array $collectionInfo,
        array $bookInfo,
        ?array $chapterInfo,
        array $hadithInfo,
        array $jsonData,
    ): array {
        return [
            'collection'         => $collectionInfo,
            'book'               => $bookInfo,
            'chapter'            => $chapterInfo,
            'hadith'             => $hadithInfo,
            'translations'       => $this->extractTranslationsFromSingle($jsonData),
            'narrators'          => $this->extractNarrators($jsonData),
            'companion'          => $this->extractCompanion($jsonData),
            'themes'             => $this->extractThemes($jsonData),
            'keywords'           => $this->extractKeywords($jsonData),
            'grading'            => $this->extractGrading($jsonData),
            'scholarly_comments' => $this->extractScholarlyComments($jsonData),
            'related_verses'     => $this->extractRelatedVerses($jsonData),
            'applications'       => $this->extractApplications($jsonData),
            'fiqh_rulings'       => $this->extractFiqhRulings($jsonData),
            'historical_context' => $this->extractHistoricalContext($jsonData),
        ];
    }

    /**
     * Build translations from nested-hadith matn structure
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildNestedTranslations(array $matn): array
    {
        $translations = [];

        if (!empty($matn['arabic']['full_text'])) {
            $translations[] = ['language_code' => 'ar', 'text' => $this->sanitizeText($matn['arabic']['full_text']), 'notes' => null];
        }
        if (!empty($matn['arabic']['simplified'])) {
            $translations[] = ['language_code' => 'ar_notashkeel', 'text' => $this->sanitizeText($matn['arabic']['simplified']), 'notes' => 'Arabic text without diacritical marks'];
        }
        if (!empty($matn['english']['translation'])) {
            $translations[] = ['language_code' => 'en', 'text' => $this->sanitizeText($matn['english']['translation']), 'notes' => null];
        }

        return $translations;
    }

    /**
     * Build themes from nested-hadith themes array
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildNestedThemes(array $themes): array
    {
        return array_map(
            fn ($theme) => ['name_ar' => '', 'name_en' => $this->sanitizeText($theme['theme'] ?? ''), 'description' => null],
            $themes
        );
    }

    /**
     * Build keywords from nested-hadith keywords structure
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildNestedKeywords(array $keywords): array
    {
        $result = [];

        foreach ($keywords['arabic'] ?? [] as $kw) {
            $result[] = ['keyword' => $this->sanitizeText($kw), 'language_code' => 'ar'];
        }
        foreach ($keywords['english'] ?? [] as $kw) {
            $result[] = ['keyword' => $this->sanitizeText($kw), 'language_code' => 'en'];
        }

        return $result;
    }

    /**
     * Build grading list from nested-hadith primary grading
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildNestedGrading(array $grading): array
    {
        if (empty($grading['scholar'])) {
            return [];
        }

        return [[
            'scholar_name'  => $this->sanitizeText($grading['scholar']),
            'grading_level' => $grading['grade'] ?? null,
            'notes_ar'      => '',
            'notes_en'      => $this->sanitizeText($grading['explanation'] ?? ''),
        ]];
    }
}
