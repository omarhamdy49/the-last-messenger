<?php

namespace App\Services\Json;

use Illuminate\Support\Facades\Log;

/**
 * JsonDataTransformer Service
 *
 * Generic transformer for hadith JSON data that automatically detects structure
 * and handles multiple JSON formats (collection-based, single-hadith, etc.)
 *
 * Supports:
 * - Collection-based format (1 file = 1 collection with many hadiths)
 * - Single-hadith format (1 file = 1 hadith in multiple collections)
 * - Rich metadata (narrators, themes, keywords, grading, commentary, etc.)
 *
 * @package App\Services\Json
 */
class JsonDataTransformer
{
    /**
     * Detect JSON structure type
     *
     * @param array $jsonData The complete JSON data
     * @return string 'collection_based', 'single_hadith', or 'nested_hadith'
     */
    public function detectStructureType(array $jsonData): string
    {
        // Single-hadith format: has 'hadith_text' and 'collections' array
        if (isset($jsonData['hadith_text']) && isset($jsonData['collections']) && is_array($jsonData['collections'])) {
            return 'single_hadith';
        }

        // Nested-hadith format: has 'hadith' object with 'collection' and 'matn'
        if (isset($jsonData['hadith']) && is_array($jsonData['hadith']) && isset($jsonData['hadith']['matn'])) {
            return 'nested_hadith';
        }

        // Collection-based format: has 'metadata', 'chapters', and 'hadiths' arrays
        if (isset($jsonData['metadata']) && isset($jsonData['chapters']) && isset($jsonData['hadiths'])) {
            return 'collection_based';
        }

        return 'unknown';
    }

    /**
     * Validate JSON data structure (auto-detects format)
     *
     * @param array $data The JSON data to validate
     * @return bool True if valid, false otherwise
     */
    public function isValidData(array $data): bool
    {
        $type = $this->detectStructureType($data);

        if ($type === 'single_hadith') {
            return $this->validateSingleHadithFormat($data);
        }

        if ($type === 'nested_hadith') {
            return $this->validateNestedHadithFormat($data);
        }

        if ($type === 'collection_based') {
            return $this->validateCollectionBasedFormat($data);
        }

        Log::warning('Unknown JSON structure type');
        return false;
    }

    /**
     * Validate single-hadith format
     *
     * @param array $data The JSON data
     * @return bool
     */
    protected function validateSingleHadithFormat(array $data): bool
    {
        // Check for hadith_text
        if (!isset($data['hadith_text']) || !is_array($data['hadith_text'])) {
            Log::warning('Invalid single-hadith format: missing hadith_text');
            return false;
        }

        $hadithText = $data['hadith_text'];
        if (empty($hadithText['ar']) && empty($hadithText['en'])) {
            Log::warning('Invalid single-hadith format: missing hadith text in both languages');
            return false;
        }

        // Check for collections array
        if (!isset($data['collections']) || !is_array($data['collections']) || empty($data['collections'])) {
            Log::warning('Invalid single-hadith format: missing or empty collections array');
            return false;
        }

        // Validate each collection has required fields
        foreach ($data['collections'] as $index => $collection) {
            if (empty($collection['name_en']) && empty($collection['name_ar'])) {
                Log::warning("Invalid collection at index {$index}: missing name");
                return false;
            }
        }

        return true;
    }

    /**
     * Validate nested-hadith format (hadith.collection + hadith.matn)
     */
    protected function validateNestedHadithFormat(array $data): bool
    {
        $hadith = $data['hadith'] ?? null;
        if (!is_array($hadith)) {
            return false;
        }

        if (empty($hadith['matn'])) {
            Log::warning('Invalid nested-hadith format: missing matn');
            return false;
        }

        $matn = $hadith['matn'];
        if (empty($matn['arabic']['full_text'] ?? '') && empty($matn['english']['translation'] ?? '')) {
            Log::warning('Invalid nested-hadith format: missing text in both languages');
            return false;
        }

        return true;
    }

    /**
     * Validate collection-based format
     *
     * @param array $data The JSON data
     * @return bool
     */
    protected function validateCollectionBasedFormat(array $data): bool
    {
        // Check for metadata
        if (!isset($data['metadata']) || !is_array($data['metadata'])) {
            Log::warning('Invalid collection-based format: missing metadata');
            return false;
        }

        $metadata = $data['metadata'];

        // Check for required metadata fields
        if (!isset($metadata['id'])) {
            Log::warning('Invalid collection-based format: missing metadata.id');
            return false;
        }

        // Check for title in at least one language
        $hasTitle = false;
        if (isset($metadata['arabic']['title']) && !empty($metadata['arabic']['title'])) {
            $hasTitle = true;
        }
        if (isset($metadata['english']['title']) && !empty($metadata['english']['title'])) {
            $hasTitle = true;
        }

        if (!$hasTitle) {
            Log::warning('Invalid collection-based format: missing title');
            return false;
        }

        // Check for hadiths array
        if (!isset($data['hadiths']) || !is_array($data['hadiths'])) {
            Log::warning('Invalid collection-based format: missing hadiths array');
            return false;
        }

        return true;
    }

    /**
     * Transform JSON data to database format (auto-detects structure)
     *
     * @param array $jsonData The complete JSON data
     * @return array Transformed data
     */
    public function transform(array $jsonData): array
    {
        $type = $this->detectStructureType($jsonData);

        if ($type === 'single_hadith') {
            return $this->transformSingleHadith($jsonData);
        }

        if ($type === 'nested_hadith') {
            return $this->transformNestedHadith($jsonData);
        }

        if ($type === 'collection_based') {
            return $this->transformCollectionBased($jsonData);
        }

        throw new \InvalidArgumentException('Unknown or invalid JSON structure');
    }

    /**
     * Transform single-hadith format to database records
     *
     * @param array $jsonData The JSON data
     * @return array
     */
    protected function transformSingleHadith(array $jsonData): array
    {
        $result = [];
        $metadataCollection = $jsonData['metadata']['collection'] ?? null;
        $metadataCollectionName = $metadataCollection['name_en'] ?? null;
        $processedCollections = [];

        // If metadata.collection exists, find matching entry in collections[] for book/chapter info
        if ($metadataCollection) {
            // Find the collection entry that matches metadata collection
            $primaryCollectionData = null;
            foreach ($jsonData['collections'] as $collectionData) {
                if (($collectionData['name_en'] ?? '') === $metadataCollectionName) {
                    $primaryCollectionData = $collectionData;
                    break;
                }
            }

            // Use metadata for collection info, but collections[] for book/chapter
            $collectionInfo = $this->extractMetadataCollection($metadataCollection);

            // If we found matching collection entry, use its book/chapter info
            if ($primaryCollectionData) {
                $bookInfo = $this->extractBookFromSingle($primaryCollectionData);
                $chapterInfo = $this->extractChapterFromSingle($primaryCollectionData);
                $hadithInfo = $this->extractHadithFromSingle($jsonData, $primaryCollectionData);
                // Also use metadata.id_in_book if available
                if (isset($jsonData['metadata']['id_in_book'])) {
                    $hadithInfo['hadith_number'] = $jsonData['metadata']['id_in_book'];
                }
            } else {
                // Fallback: use collection name as book (for special collections like Nawawi40)
                $bookInfo = $this->extractMetadataBook($metadataCollection);
                $chapterInfo = $this->extractMetadataChapter($jsonData['metadata']);
                $hadithInfo = $this->extractHadithFromSingle($jsonData, ['hadith_number' => $jsonData['metadata']['id_in_book'] ?? 0]);
            }

            $result[] = [
                'collection' => $collectionInfo,
                'book' => $bookInfo,
                'chapter' => $chapterInfo,
                'hadith' => $hadithInfo,
                'translations' => $this->extractTranslationsFromSingle($jsonData),
                'narrators' => $this->extractNarrators($jsonData),
                'companion' => $this->extractCompanion($jsonData),
                'themes' => $this->extractThemes($jsonData),
                'keywords' => $this->extractKeywords($jsonData),
                'grading' => $this->extractGrading($jsonData),
                'scholarly_comments' => $this->extractScholarlyComments($jsonData),
                'related_verses' => $this->extractRelatedVerses($jsonData),
                'applications' => $this->extractApplications($jsonData),
                'fiqh_rulings' => $this->extractFiqhRulings($jsonData),
                'historical_context' => $this->extractHistoricalContext($jsonData),
            ];

            $processedCollections[] = $metadataCollectionName;
        }

        // Process remaining collections in the array (cross-references)
        foreach ($jsonData['collections'] as $collectionData) {
            $collectionName = $collectionData['name_en'] ?? '';

            // Skip if already processed as primary collection
            if (in_array($collectionName, $processedCollections)) {
                continue;
            }

            $result[] = [
                'collection' => $this->extractCollectionFromSingle($collectionData),
                'book' => $this->extractBookFromSingle($collectionData),
                'chapter' => $this->extractChapterFromSingle($collectionData),
                'hadith' => $this->extractHadithFromSingle($jsonData, $collectionData),
                'translations' => $this->extractTranslationsFromSingle($jsonData),
                'narrators' => $this->extractNarrators($jsonData),
                'companion' => $this->extractCompanion($jsonData),
                'themes' => $this->extractThemes($jsonData),
                'keywords' => $this->extractKeywords($jsonData),
                'grading' => $this->extractGrading($jsonData),
                'scholarly_comments' => $this->extractScholarlyComments($jsonData),
                'related_verses' => $this->extractRelatedVerses($jsonData),
                'applications' => $this->extractApplications($jsonData),
                'historical_context' => $this->extractHistoricalContext($jsonData),
                'fiqh_rulings' => $this->extractFiqhRulings($jsonData),
            ];
        }

        return [
            'type' => 'single_hadith',
            'records' => $result,
        ];
    }

    /**
     * Transform collection-based format to database records
     *
     * @param array $jsonData The JSON data
     * @return array
     */
    protected function transformCollectionBased(array $jsonData): array
    {
        $metadata = $jsonData['metadata'] ?? [];
        $arabic = $metadata['arabic'] ?? [];
        $english = $metadata['english'] ?? [];

        $collection = [
            'name_ar' => $this->sanitizeText($arabic['title'] ?? ''),
            'name_en' => $this->sanitizeText($english['title'] ?? ''),
            'slug_ar' => \Illuminate\Support\Str::slug($this->sanitizeText($arabic['title'] ?? ''), '-', 'ar'),
            'slug_en' => \Illuminate\Support\Str::slug($this->sanitizeText($english['title'] ?? '')),
            'description_ar' => null,
            'description_en' => null,
            'abbreviation' => null,
        ];

        $bookTitleAr = $this->sanitizeText($arabic['title'] ?? '');
        $bookTitleEn = $this->sanitizeText($english['title'] ?? '');

        $book = [
            'title_ar' => $bookTitleAr,
            'title_en' => $bookTitleEn,
            'slug_ar' => \Illuminate\Support\Str::slug($bookTitleAr, '-', 'ar'),
            'slug_en' => \Illuminate\Support\Str::slug($bookTitleEn),
            'chapter_title_ar' => null,
            'chapter_title_en' => null,
        ];

        $chapters = [];
        foreach ($jsonData['chapters'] ?? [] as $chapterData) {
            $chapters[] = [
                'chapter_number' => $chapterData['id'] ?? 0,
                'name' => json_encode([
                    'ar' => $this->sanitizeText($chapterData['arabic'] ?? ''),
                    'en' => $this->sanitizeText($chapterData['english'] ?? ''),
                ], JSON_UNESCAPED_UNICODE),
                'description' => null,
                'display_order' => $chapterData['id'] ?? 0,
            ];
        }

        $hadiths = [];
        foreach ($jsonData['hadiths'] ?? [] as $hadithData) {
            $hadiths[] = [
                'hadith' => [
                    'hadith_number' => $hadithData['idInBook'] ?? $hadithData['id'] ?? 0,
                    'grading_status' => null,
                    'grading_agreement' => null,
                ],
                'translations' => $this->transformTranslationsOldFormat($hadithData),
            ];
        }

        return [
            'type' => 'collection_based',
            'collection' => $collection,
            'book' => $book,
            'chapters' => $chapters,
            'hadiths' => $hadiths,
        ];
    }

    /**
     * Transform nested-hadith format to single_hadith records
     *
     * Converts the nested format (hadith.collection, hadith.matn, etc.)
     * into the same output structure as transformSingleHadith.
     */
    protected function transformNestedHadith(array $jsonData): array
    {
        $hadith = $jsonData['hadith'];
        $metadata = $jsonData['metadata'] ?? [];
        $metadataCollection = $metadata['collection'] ?? [];

        $collection = $hadith['collection'] ?? [];
        $book = $hadith['book'] ?? [];
        $chapter = $hadith['chapter'] ?? [];
        $matn = $hadith['matn'] ?? [];
        $grading = $hadith['grading']['primary'] ?? [];
        $hadithNumber = $hadith['hadith_number'] ?? [];
        $themes = $hadith['themes'] ?? [];
        $keywords = $hadith['keywords'] ?? [];

        $collectionNameEn = $metadataCollection['name_en'] ?? $collection['name'] ?? '';
        $collectionNameAr = $metadataCollection['name_ar'] ?? $collection['arabic_name'] ?? '';

        $collectionInfo = [
            'name_ar' => $this->sanitizeText($collectionNameAr),
            'name_en' => $this->sanitizeText($collectionNameEn),
            'slug_ar' => \Illuminate\Support\Str::slug($this->sanitizeText($collectionNameAr), '-', 'ar'),
            'slug_en' => \Illuminate\Support\Str::slug($this->sanitizeText($collectionNameEn)),
            'description_ar' => null,
            'description_en' => null,
            'abbreviation' => null,
            'total_hadiths' => $metadataCollection['total_hadiths'] ?? $collection['total_hadiths'] ?? 0,
        ];

        $bookTitleAr = $this->sanitizeText($book['arabic_name'] ?? $collectionNameAr);
        $bookTitleEn = $this->sanitizeText($book['name'] ?? $collectionNameEn);

        $bookInfo = [
            'title_ar' => $bookTitleAr,
            'title_en' => $bookTitleEn,
            'slug_ar' => \Illuminate\Support\Str::slug($bookTitleAr, '-', 'ar'),
            'slug_en' => \Illuminate\Support\Str::slug($bookTitleEn),
            'chapter_title_ar' => null,
            'chapter_title_en' => null,
        ];

        $chapterInfo = null;
        if (!empty($chapter['number'])) {
            $chapterInfo = [
                'chapter_number' => $chapter['number'],
                'name' => json_encode([
                    'ar' => $this->sanitizeText($chapter['arabic_title'] ?? ''),
                    'en' => $this->sanitizeText($chapter['title'] ?? ''),
                ], JSON_UNESCAPED_UNICODE),
                'description' => null,
                'display_order' => $chapter['number'],
            ];
        }

        $hadithNum = $hadithNumber['in_book'] ?? $hadithNumber['overall'] ?? $metadata['id_in_book'] ?? 0;

        $hadithInfo = [
            'hadith_number' => $hadithNum,
            'grading_status' => $grading['grade'] ?? null,
            'grading_agreement' => null,
            'significance_ar' => '',
            'significance_en' => '',
            'historical_context_ar' => '',
            'historical_context_en' => '',
            'verification_status' => null,
            'uncertainty_notes' => null,
        ];

        // Translations from matn
        $translations = [];
        if (!empty($matn['arabic']['full_text'])) {
            $translations[] = [
                'language_code' => 'ar',
                'text' => $this->sanitizeText($matn['arabic']['full_text']),
                'notes' => null,
            ];
        }
        if (!empty($matn['arabic']['simplified'])) {
            $translations[] = [
                'language_code' => 'ar_notashkeel',
                'text' => $this->sanitizeText($matn['arabic']['simplified']),
                'notes' => 'Arabic text without diacritical marks',
            ];
        }
        if (!empty($matn['english']['translation'])) {
            $translations[] = [
                'language_code' => 'en',
                'text' => $this->sanitizeText($matn['english']['translation']),
                'notes' => null,
            ];
        }

        // Themes
        $themesList = [];
        foreach ($themes as $theme) {
            $themesList[] = [
                'name_ar' => '',
                'name_en' => $this->sanitizeText($theme['theme'] ?? ''),
                'description' => null,
            ];
        }

        // Keywords
        $keywordsList = [];
        foreach ($keywords['arabic'] ?? [] as $kw) {
            $keywordsList[] = ['keyword' => $this->sanitizeText($kw), 'language_code' => 'ar'];
        }
        foreach ($keywords['english'] ?? [] as $kw) {
            $keywordsList[] = ['keyword' => $this->sanitizeText($kw), 'language_code' => 'en'];
        }

        // Grading
        $gradingList = [];
        if (!empty($grading['scholar'])) {
            $gradingList[] = [
                'scholar_name' => $this->sanitizeText($grading['scholar']),
                'grading_level' => $grading['grade'] ?? null,
                'notes_ar' => '',
                'notes_en' => $this->sanitizeText($grading['explanation'] ?? ''),
            ];
        }

        $record = [
            'collection' => $collectionInfo,
            'book' => $bookInfo,
            'chapter' => $chapterInfo,
            'hadith' => $hadithInfo,
            'translations' => $translations,
            'narrators' => [],
            'companion' => null,
            'themes' => $themesList,
            'keywords' => $keywordsList,
            'grading' => $gradingList,
            'scholarly_comments' => [],
            'related_verses' => [],
            'applications' => [],
            'fiqh_rulings' => [],
            'historical_context' => null,
        ];

        return [
            'type' => 'single_hadith',
            'records' => [$record],
        ];
    }

    // ============================================================================
    // SINGLE-HADITH FORMAT EXTRACTORS
    // ============================================================================

    /**
     * Extract collection data from single-hadith format
     */
    protected function extractCollectionFromSingle(array $collectionData): array
    {
        return [
            'name_ar' => $this->sanitizeText($collectionData['name_ar'] ?? ''),
            'name_en' => $this->sanitizeText($collectionData['name_en'] ?? ''),
            'slug_ar' => \Illuminate\Support\Str::slug($this->sanitizeText($collectionData['name_ar'] ?? ''), '-', 'ar'),
            'slug_en' => \Illuminate\Support\Str::slug($this->sanitizeText($collectionData['name_en'] ?? '')),
            'description_ar' => null,
            'description_en' => null,
            'abbreviation' => null,
        ];
    }

    /**
     * Extract book data from single-hadith format
     */
    protected function extractBookFromSingle(array $collectionData): array
    {
        $titleAr = $this->sanitizeText($collectionData['book_name_ar'] ?? '');
        $titleEn = $this->sanitizeText($collectionData['book_name_en'] ?? '');

        return [
            'title_ar' => $titleAr,
            'title_en' => $titleEn,
            'slug_ar' => \Illuminate\Support\Str::slug($titleAr, '-', 'ar'),
            'slug_en' => \Illuminate\Support\Str::slug($titleEn),
            'chapter_title_ar' => null,
            'chapter_title_en' => null,
        ];
    }

    /**
     * Extract chapter data from single-hadith format
     */
    protected function extractChapterFromSingle(array $collectionData): ?array
    {
        if (empty($collectionData['chapter_number'])) {
            return null;
        }

        return [
            'chapter_number' => $collectionData['chapter_number'],
            'name' => json_encode([
                'ar' => $this->sanitizeText($collectionData['chapter_name_ar'] ?? ''),
                'en' => $this->sanitizeText($collectionData['chapter_name_en'] ?? ''),
            ], JSON_UNESCAPED_UNICODE),
            'description' => null,
            'display_order' => $collectionData['chapter_number'],
        ];
    }

    /**
     * Extract collection from metadata (used for primary collection info)
     */
    protected function extractMetadataCollection(array $collectionMetadata): array
    {
        return [
            'name_ar' => $this->sanitizeText($collectionMetadata['name_ar'] ?? ''),
            'name_en' => $this->sanitizeText($collectionMetadata['name_en'] ?? ''),
            'slug_ar' => \Illuminate\Support\Str::slug($this->sanitizeText($collectionMetadata['name_ar'] ?? ''), '-', 'ar'),
            'slug_en' => \Illuminate\Support\Str::slug($this->sanitizeText($collectionMetadata['name_en'] ?? '')),
            'description_ar' => null,
            'description_en' => null,
            'abbreviation' => null,
            'total_hadiths' => $collectionMetadata['total_hadiths'] ?? 0,
        ];
    }

    /**
     * Extract book from metadata collection (fallback for special collections like Nawawi40)
     * Used when collection name IS the book name
     */
    protected function extractMetadataBook(array $collectionMetadata): array
    {
        $titleAr = $this->sanitizeText($collectionMetadata['name_ar'] ?? '');
        $titleEn = $this->sanitizeText($collectionMetadata['name_en'] ?? '');

        return [
            'title_ar' => $titleAr,
            'title_en' => $titleEn,
            'slug_ar' => \Illuminate\Support\Str::slug($titleAr, '-', 'ar'),
            'slug_en' => \Illuminate\Support\Str::slug($titleEn),
            'chapter_title_ar' => null,
            'chapter_title_en' => null,
        ];
    }

    /**
     * Extract chapter from metadata (if chapter_id exists)
     */
    protected function extractMetadataChapter(array $metadata): ?array
    {
        $chapterId = $metadata['chapter_id'] ?? 0;

        if (empty($chapterId)) {
            return null;
        }

        return [
            'chapter_number' => $chapterId,
            'name' => json_encode([
                'ar' => 'الباب',
                'en' => 'Chapter',
            ], JSON_UNESCAPED_UNICODE),
            'description' => null,
            'display_order' => $chapterId,
        ];
    }

    /**
     * Extract hadith data from single-hadith format
     */
    protected function extractHadithFromSingle(array $jsonData, array $collectionData): array
    {
        $grading = $jsonData['grading'] ?? [];

        return [
            'hadith_number' => $collectionData['hadith_number'] ?? 0,
            'grading_status' => $grading['status'] ?? null,
            'grading_agreement' => $grading['agreement'] ?? null,
            'significance_ar' => $this->sanitizeText($jsonData['significance_ar'] ?? ''),
            'significance_en' => $this->sanitizeText($jsonData['significance_en'] ?? ''),
            'historical_context_ar' => $this->sanitizeText($jsonData['historical_context']['occasion_ar'] ?? $jsonData['historical_context']['lesson_ar'] ?? ''),
            'historical_context_en' => $this->sanitizeText($jsonData['historical_context']['occasion_en'] ?? $jsonData['historical_context']['lesson_en'] ?? ''),
            'verification_status' => $jsonData['verification_status'] ?? null,
            'uncertainty_notes' => $jsonData['uncertainty_notes'] ?? null,
        ];
    }

    /**
     * Extract translations from single-hadith format
     */
    protected function extractTranslationsFromSingle(array $jsonData): array
    {
        $translations = [];
        $hadithText = $jsonData['hadith_text'] ?? [];

        // Arabic translation
        if (!empty($hadithText['ar'])) {
            $translations[] = [
                'language_code' => 'ar',
                'text' => $this->sanitizeText($hadithText['ar']),
                'notes' => null,
            ];
        }

        // Arabic without tashkeel
        if (!empty($hadithText['ar_notashkeel'])) {
            $translations[] = [
                'language_code' => 'ar_notashkeel',
                'text' => $this->sanitizeText($hadithText['ar_notashkeel']),
                'notes' => 'Arabic text without diacritical marks',
            ];
        }

        // English translation
        if (!empty($hadithText['en'])) {
            $translations[] = [
                'language_code' => 'en',
                'text' => $this->sanitizeText($hadithText['en']),
                'notes' => null,
            ];
        }

        return $translations;
    }

    /**
     * Extract narrators from JSON
     */
    protected function extractNarrators(array $jsonData): array
    {
        $sanad = $jsonData['sanad'] ?? [];
        $narrators = $sanad['narrators'] ?? [];

        $result = [];
        foreach ($narrators as $narrator) {
            $result[] = [
                'name_ar' => $this->sanitizeText($narrator['name_ar'] ?? ''),
                'name_en' => $this->sanitizeText($narrator['narrator_en'] ?? ''),
                'full_name_ar' => $this->sanitizeText($narrator['full_name_ar'] ?? ''),
                'full_name_en' => null,
                'birth_year_hijri' => null,
                'death_year_hijri' => $narrator['death_year_hijri'] ?? null,
                'reliability_grade' => $narrator['reliability_en'] ?? null,
                'biography_ar' => $this->sanitizeText($narrator['bio_ar'] ?? ''),
                'biography_en' => $this->sanitizeText($narrator['bio_en'] ?? ''),
                // Isnad-specific data
                'position_in_chain' => $narrator['order'] ?? 0,
                'transmission_method' => $narrator['narrated_from_en'] ?? null,
                'verified' => $narrator['verified'] ?? false,
            ];
        }

        return $result;
    }

    /**
     * Extract companion information
     */
    protected function extractCompanion(array $jsonData): ?array
    {
        $companion = $jsonData['companion'] ?? null;
        if (empty($companion)) {
            return null;
        }

        return [
            'name_ar' => $this->sanitizeText($companion['name_ar'] ?? ''),
            'name_en' => $this->sanitizeText($companion['name_en'] ?? ''),
            'full_name_ar' => $this->sanitizeText($companion['full_name_ar'] ?? ''),
            'full_name_en' => null,
            'title_ar' => $this->sanitizeText($companion['title_ar'] ?? ''),
            'title_en' => $this->sanitizeText($companion['title_en'] ?? ''),
            'birth_year_hijri' => $companion['birth_year_hijri'] ?? null,
            'death_year_hijri' => $companion['death_year_hijri'] ?? null,
            'conversion_year' => $companion['conversion_year'] ?? null,
            'bio_ar' => $this->sanitizeText($companion['bio_ar'] ?? ''),
            'bio_en' => $this->sanitizeText($companion['bio_en'] ?? ''),
            'virtues' => $this->sanitizeText($companion['virtues'] ?? ''),
            'verified' => $companion['verified'] ?? false,
        ];
    }

    /**
     * Extract themes
     */
    protected function extractThemes(array $jsonData): array
    {
        $themesEn = $jsonData['themes_en'] ?? [];
        $themesAr = $jsonData['themes_ar'] ?? [];

        $result = [];
        $maxCount = max(count($themesEn), count($themesAr));

        for ($i = 0; $i < $maxCount; $i++) {
            $result[] = [
                'name_ar' => $this->sanitizeText($themesAr[$i] ?? ''),
                'name_en' => $this->sanitizeText($themesEn[$i] ?? ''),
                'description' => null,
            ];
        }

        return $result;
    }

    /**
     * Extract keywords
     */
    protected function extractKeywords(array $jsonData): array
    {
        $keywords = $jsonData['keywords'] ?? [];

        $result = [];
        foreach ($keywords as $keyword) {
            $result[] = [
                'keyword' => $this->sanitizeText($keyword),
                'language_code' => 'ar', // Keywords are in Arabic by default
            ];
        }

        return $result;
    }

    /**
     * Extract grading information
     */
    protected function extractGrading(array $jsonData): array
    {
        $grading = $jsonData['grading'] ?? [];
        $authenticatedBy = $grading['authenticated_by'] ?? [];

        $result = [];
        foreach ($authenticatedBy as $scholarName) {
            $result[] = [
                'scholar_name' => $this->sanitizeText($scholarName),
                'grading_level' => $grading['status'] ?? null,
                'notes_ar' => $this->sanitizeText($grading['notes_ar'] ?? ''),
                'notes_en' => $this->sanitizeText($grading['notes_en'] ?? ''),
            ];
        }

        // Add Albani grading if different
        if (!empty($grading['albani_grade']) && $grading['albani_grade'] !== ($grading['status'] ?? null)) {
            $result[] = [
                'scholar_name' => 'Shaykh al-Albani',
                'grading_level' => $grading['albani_grade'],
                'notes_ar' => null,
                'notes_en' => null,
            ];
        }

        return $result;
    }

    /**
     * Extract scholarly comments
     */
    protected function extractScholarlyComments(array $jsonData): array
    {
        $comments = $jsonData['scholarly_comments'] ?? [];

        $result = [];
        foreach ($comments as $comment) {
            $result[] = [
                'scholar_name_ar' => $this->sanitizeText($comment['scholar_ar'] ?? ''),
                'scholar_name_en' => $this->sanitizeText($comment['scholar_en'] ?? ''),
                'commentary_ar' => $this->sanitizeText($comment['comment_ar'] ?? ''),
                'commentary_en' => $this->sanitizeText($comment['comment_en'] ?? ''),
                'source_reference' => $this->sanitizeText($comment['source'] ?? ''),
                'verified' => $comment['verified'] ?? false,
            ];
        }

        return $result;
    }

    /**
     * Extract related Quranic verses
     */
    protected function extractRelatedVerses(array $jsonData): array
    {
        $verses = $jsonData['related_verses'] ?? [];

        $result = [];
        foreach ($verses as $verse) {
            $result[] = [
                'surah' => $verse['surah'] ?? 0,
                'surah_name_ar' => $this->sanitizeText($verse['surah_name_ar'] ?? ''),
                'surah_name_en' => $this->sanitizeText($verse['surah_name_en'] ?? ''),
                'ayah' => $verse['ayah'] ?? 0,
                'text_ar' => $this->sanitizeText($verse['text_ar'] ?? ''),
                'text_en' => $this->sanitizeText($verse['text_en'] ?? ''),
                'relevance' => $this->sanitizeText($verse['relevance'] ?? ''),
            ];
        }

        return $result;
    }

    /**
     * Extract practical applications
     */
    protected function extractApplications(array $jsonData): array
    {
        $applications = $jsonData['applications'] ?? [];

        $result = [];
        foreach ($applications as $app) {
            $result[] = [
                'category_ar' => $this->sanitizeText($app['category_ar'] ?? ''),
                'category_en' => $this->sanitizeText($app['category_en'] ?? ''),
                'description_ar' => $this->sanitizeText($app['description_ar'] ?? ''),
                'description_en' => $this->sanitizeText($app['description_en'] ?? ''),
            ];
        }

        return $result;
    }

    /**
     * Extract historical context
     */
    protected function extractHistoricalContext(array $jsonData): ?array
    {
        $context = $jsonData['historical_context'] ?? null;
        if (empty($context)) {
            return null;
        }

        return [
            'occasion_ar' => $this->sanitizeText($context['occasion_ar'] ?? ''),
            'occasion_en' => $this->sanitizeText($context['occasion_en'] ?? ''),
            'lesson_ar' => $this->sanitizeText($context['lesson_ar'] ?? ''),
            'lesson_en' => $this->sanitizeText($context['lesson_en'] ?? ''),
        ];
    }

    /**
     * Extract fiqh rulings
     */
    protected function extractFiqhRulings(array $jsonData): array
    {
        $legalRulings = $jsonData['legal_rulings'] ?? [];
        $principlesEn = $legalRulings['fiqh_principles_en'] ?? [];
        $principlesAr = $legalRulings['fiqh_principles_ar'] ?? [];

        $result = [];

        foreach ($principlesAr as $principle) {
            $result[] = [
                'principle' => $this->sanitizeText($principle),
                'language_code' => 'ar',
            ];
        }

        foreach ($principlesEn as $principle) {
            $result[] = [
                'principle' => $this->sanitizeText($principle),
                'language_code' => 'en',
            ];
        }

        return $result;
    }

    // ============================================================================
    // COLLECTION-BASED FORMAT (OLD FORMAT)
    // ============================================================================

    /**
     * Transform translations for old format
     */
    protected function transformTranslationsOldFormat(array $hadithData): array
    {
        $translations = [];

        // Arabic translation
        $arabicText = $hadithData['arabic'] ?? '';
        if (!empty($arabicText)) {
            $translations[] = [
                'language_code' => 'ar',
                'matn' => $this->sanitizeText($arabicText),
                'translator_name' => null,
                'notes' => null,
            ];
        }

        // English translation
        $english = $hadithData['english'] ?? [];
        if (is_array($english)) {
            $narrator = $english['narrator'] ?? '';
            $text = $english['text'] ?? '';
            $englishText = trim($narrator . ' ' . $text);
        } else {
            $englishText = $english;
        }

        if (!empty($englishText)) {
            $translations[] = [
                'language_code' => 'en',
                'matn' => $this->sanitizeText($englishText),
                'translator_name' => null,
                'notes' => null,
            ];
        }

        return $translations;
    }

    /**
     * Detect collection name from file path based on parent directory name
     */
    public function detectCollectionFromFilename(string $filePath): ?string
    {
        $directory = basename(dirname($filePath));

        $knownCollections = [
            'nawawi40' => 'nawawi40',
            'bukhari' => 'bukhari',
            'muslim' => 'muslim',
            'abudawud' => 'abudawud',
            'tirmidhi' => 'tirmidhi',
            'nasai' => 'nasai',
            'ibnmajah' => 'ibnmajah',
        ];

        $normalized = strtolower(str_replace(['-', '_', ' '], '', $directory));

        foreach ($knownCollections as $key => $value) {
            if (str_contains($normalized, $key)) {
                return $value;
            }
        }

        return $directory ?: null;
    }

    // ============================================================================
    // UTILITY METHODS
    // ============================================================================

    /**
     * Sanitize text by removing HTML, extra whitespace, and control characters
     *
     * @param string $text The text to sanitize
     * @return string Sanitized text
     */
    public function sanitizeText(string $text): string
    {
        // Remove HTML tags
        $text = strip_tags($text);

        // Remove zero-width characters
        $text = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $text);

        // Remove control characters (except newlines and tabs)
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);

        // Normalize line breaks
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // Remove excessive line breaks (3+ becomes 2)
        $text = preg_replace("/\n{3,}/", "\n\n", $text);

        // Normalize whitespace (but preserve single line breaks)
        $text = preg_replace('/[ \t]+/', ' ', $text);

        // Trim whitespace
        $text = trim($text);

        return $text;
    }

    /**
     * Get statistics from JSON data (auto-detects format)
     *
     * @param array $jsonData The complete JSON data
     * @return array Statistics including counts
     */
    public function getStatistics(array $jsonData): array
    {
        $type = $this->detectStructureType($jsonData);

        if ($type === 'single_hadith') {
            return [
                'type' => 'single_hadith',
                'collections_count' => count($jsonData['collections'] ?? []),
                'narrators_count' => count($jsonData['sanad']['narrators'] ?? []),
                'themes_count' => count($jsonData['themes_en'] ?? []),
                'keywords_count' => count($jsonData['keywords'] ?? []),
                'scholarly_comments_count' => count($jsonData['scholarly_comments'] ?? []),
                'related_verses_count' => count($jsonData['related_verses'] ?? []),
                'applications_count' => count($jsonData['applications'] ?? []),
                'has_companion' => !empty($jsonData['companion']),
                'has_grading' => !empty($jsonData['grading']),
            ];
        }

        if ($type === 'nested_hadith') {
            return [
                'type' => 'nested_hadith',
                'collections_count' => 1,
                'themes_count' => count($jsonData['hadith']['themes'] ?? []),
                'keywords_count' => count($jsonData['hadith']['keywords']['arabic'] ?? []) + count($jsonData['hadith']['keywords']['english'] ?? []),
                'has_grading' => !empty($jsonData['hadith']['grading']),
            ];
        }

        if ($type === 'collection_based') {
            return [
                'type' => 'collection_based',
                'total_chapters' => count($jsonData['chapters'] ?? []),
                'total_hadiths' => count($jsonData['hadiths'] ?? []),
                'book_id' => $jsonData['metadata']['id'] ?? null,
                'has_arabic' => !empty($jsonData['metadata']['arabic']['title'] ?? ''),
                'has_english' => !empty($jsonData['metadata']['english']['title'] ?? ''),
            ];
        }

        return ['type' => 'unknown'];
    }
}
