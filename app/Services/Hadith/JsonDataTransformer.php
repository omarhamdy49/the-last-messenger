<?php

declare(strict_types=1);

namespace App\Services\Hadith;

/**
 * Service for transforming JSON hadith data to database format
 *
 * Transforms various JSON hadith formats (Nawawi 40, Al-Adab Al-Mufrad, etc.)
 * into standardized database structure compatible with the hadith tables.
 */
class JsonDataTransformer
{
    /**
     * Supported languages to store (ar, en only)
     */
    private const SUPPORTED_LANGUAGES = ['ar', 'en'];

    /**
     * Transform JSON collection data to database format
     *
     * @param  array  $data  Raw JSON collection data
     * @return array Transformed collection data
     */
    public function transformCollection(array $data): array
    {
        return [
            'name_ar' => $data['name_ar'] ?? $data['arabic_name'] ?? $data['arabic']['title'] ?? $data['metadata']['arabic']['title'] ?? null,
            'name_en' => $data['name_en'] ?? $data['english_name'] ?? $data['english']['title'] ?? $data['metadata']['english']['title'] ?? $data['name'] ?? $data['collection'] ?? 'Unknown',
            'compiler' => $data['compiler'] ?? $data['author'] ?? $data['arabic']['author'] ?? $data['english']['author'] ?? $data['metadata']['arabic']['author'] ?? $data['metadata']['english']['author'] ?? null,
            'description' => $data['description_en'] ?? $data['description'] ?? $data['english']['introduction'] ?? $data['metadata']['english']['introduction'] ?? null,
            'death_year_hijri' => $data['death_year_hijri'] ?? null,
        ];
    }

    /**
     * Transform JSON book data to database format
     *
     * @param  array  $data  Raw JSON book data
     * @param  int  $collectionId  Database collection ID
     * @return array Transformed book data
     */
    public function transformBook(array $data, int $collectionId): array
    {
        return [
            'collection_id' => $collectionId,
            'title_ar' => $data['name_ar'] ?? $data['arabic_name'] ?? $data['title_ar'] ?? null,
            'title_en' => $data['name_en'] ?? $data['english_name'] ?? $data['title_en'] ?? $data['title'] ?? 'Untitled Book',
            'chapter_title_ar' => $data['chapter_ar'] ?? null,
            'chapter_title_en' => $data['chapter_en'] ?? null,
        ];
    }

    /**
     * Transform JSON chapter data to database format
     *
     * @param  array  $data  Raw JSON chapter data
     * @param  int  $bookId  Database book ID
     * @param  int|null  $collectionId  Database collection ID (optional)
     * @return array Transformed chapter data
     */
    public function transformChapter(array $data, int $bookId, ?int $collectionId = null): array
    {
        return [
            'book_id' => $bookId,
            'chapter_number' => (string) ($data['chapter_number'] ?? $data['number'] ?? $data['id'] ?? '1'),
            'name' => [
                'ar' => $data['name_ar'] ?? $data['arabic_name'] ?? $data['title_ar'] ?? $data['arabic'] ?? null,
                'en' => $data['name_en'] ?? $data['english_name'] ?? $data['title_en'] ?? $data['english'] ?? $data['title'] ?? null,
            ],
            'description' => [
                'ar' => $data['intro_ar'] ?? $data['introduction_ar'] ?? $data['description_ar'] ?? null,
                'en' => $data['intro_en'] ?? $data['introduction_en'] ?? $data['description_en'] ?? null,
            ],
        ];
    }

    /**
     * Transform JSON hadith data to database format
     *
     * @param  array  $data  Raw JSON hadith data
     * @param  int  $bookId  Database book ID
     * @param  int|null  $chapterId  Database chapter ID (optional)
     * @return array Transformed hadith data with translations
     */
    public function transformHadith(array $data, int $bookId, ?int $chapterId = null): array
    {
        $hadithNumber = (string) ($data['hadith_number'] ?? $data['number'] ?? $data['id'] ?? '0');

        // Extract translations
        $translations = $this->extractTranslations($data);

        // Extract grading information
        $grade = $this->extractGrading($data);

        return [
            'book_id' => $bookId,
            'chapter_id' => $chapterId,
            'hadith_number' => $hadithNumber,
            'arabic_text' => $data['arabic'] ?? $data['arabic_text'] ?? $data['text_ar'] ?? null,
            'grading_status' => $grade['status'] ?? 'unknown',
            'grading_agreement' => $grade['agreement'] ?? null,
            'translations' => $translations,
            'metadata' => $this->extractMetadata($data),
        ];
    }

    /**
     * Extract translations from JSON hadith data
     *
     * Supports multiple JSON formats and returns only ar/en translations
     *
     * @param  array  $data  Raw JSON hadith data
     * @return array Translations array ['ar' => '...', 'en' => '...']
     */
    private function extractTranslations(array $data): array
    {
        $translations = [];

        // Format 1: Direct fields (arabic, english)
        if (isset($data['arabic'])) {
            $translations['ar'] = is_string($data['arabic']) ? $data['arabic'] : '';
        }
        if (isset($data['english'])) {
            // Handle both string and object formats
            if (is_string($data['english'])) {
                $translations['en'] = $data['english'];
            } elseif (is_array($data['english'])) {
                // Format: {"narrator": "...", "text": "..."}
                $narrator = $data['english']['narrator'] ?? '';
                $text = $data['english']['text'] ?? '';
                $translations['en'] = trim($narrator."\n\n".$text);
            }
        }

        // Format 2: text_ar, text_en fields
        if (isset($data['text_ar'])) {
            $translations['ar'] = $data['text_ar'];
        }
        if (isset($data['text_en'])) {
            $translations['en'] = $data['text_en'];
        }

        // Format 3: translations array
        if (isset($data['translations']) && is_array($data['translations'])) {
            foreach ($data['translations'] as $translation) {
                if (is_array($translation)) {
                    $lang = $translation['lang'] ?? $translation['language'] ?? null;
                    $text = $translation['text'] ?? $translation['content'] ?? null;

                    if ($lang && $text && in_array($lang, self::SUPPORTED_LANGUAGES)) {
                        $translations[$lang] = $text;
                    }
                } elseif (is_string($translation)) {
                    // Simple key-value format
                    $key = key($data['translations']);
                    if (in_array($key, self::SUPPORTED_LANGUAGES)) {
                        $translations[$key] = $translation;
                    }
                }
            }
        }

        // Format 4: matn field (Arabic text)
        if (isset($data['matn']) && ! isset($translations['ar'])) {
            $translations['ar'] = $data['matn'];
        }

        // Format 5: translation field (English text)
        if (isset($data['translation']) && ! isset($translations['en'])) {
            $translations['en'] = $data['translation'];
        }

        return $translations;
    }

    /**
     * Extract grading information from JSON data
     *
     * @param  array  $data  Raw JSON hadith data
     * @return array Grading info ['status' => 'sahih', 'agreement' => 0.95]
     */
    private function extractGrading(array $data): array
    {
        $status = 'unknown';
        $agreement = null;

        // Check various grade field names
        if (isset($data['grade'])) {
            $status = $this->normalizeGradeStatus($data['grade']);
        } elseif (isset($data['grading'])) {
            $status = $this->normalizeGradeStatus($data['grading']);
        } elseif (isset($data['authenticity'])) {
            $status = $this->normalizeGradeStatus($data['authenticity']);
        } elseif (isset($data['grading_status'])) {
            $status = $this->normalizeGradeStatus($data['grading_status']);
        }

        // Check for agreement/consensus
        if (isset($data['grading_agreement'])) {
            $agreement = (float) $data['grading_agreement'];
        } elseif (isset($data['consensus'])) {
            $agreement = (float) $data['consensus'];
        }

        return [
            'status' => $status,
            'agreement' => $agreement,
        ];
    }

    /**
     * Normalize grading status to standard values
     *
     * @param  mixed  $grade  Raw grade value
     * @return string Normalized grade (sahih, hasan, daif, unknown)
     */
    private function normalizeGradeStatus($grade): string
    {
        if (is_array($grade)) {
            // If array, take first element or 'status' field
            $grade = $grade['status'] ?? $grade[0] ?? 'unknown';
        }

        $grade = strtolower(trim((string) $grade));

        // Map various grade formats to standard
        $gradeMap = [
            'sahih' => 'sahih',
            'صحيح' => 'sahih',
            'authentic' => 'sahih',
            'hasan' => 'hasan',
            'حسن' => 'hasan',
            'good' => 'hasan',
            'daif' => 'daif',
            'ضعيف' => 'daif',
            'weak' => 'daif',
            'mawdu' => 'mawdu',
            'موضوع' => 'mawdu',
            'fabricated' => 'mawdu',
        ];

        return $gradeMap[$grade] ?? 'unknown';
    }

    /**
     * Extract metadata from JSON hadith
     *
     * @param  array  $data  Raw JSON hadith data
     * @return array Metadata array
     */
    private function extractMetadata(array $data): array
    {
        $metadata = [];

        // Extract reference/source info
        if (isset($data['reference'])) {
            $metadata['reference'] = $data['reference'];
        }
        if (isset($data['source'])) {
            $metadata['source'] = $data['source'];
        }

        // Extract narrator chain (isnad)
        if (isset($data['isnad'])) {
            $metadata['isnad'] = $data['isnad'];
        } elseif (isset($data['chain'])) {
            $metadata['isnad'] = $data['chain'];
        }

        // Extract commentary/notes
        if (isset($data['commentary'])) {
            $metadata['commentary'] = $data['commentary'];
        } elseif (isset($data['notes'])) {
            $metadata['notes'] = $data['notes'];
        }

        // Extract keywords/tags
        if (isset($data['keywords'])) {
            $metadata['keywords'] = is_array($data['keywords'])
                ? $data['keywords']
                : explode(',', (string) $data['keywords']);
        } elseif (isset($data['tags'])) {
            $metadata['tags'] = is_array($data['tags'])
                ? $data['tags']
                : explode(',', (string) $data['tags']);
        }

        return $metadata;
    }

    /**
     * Validate transformed collection data
     *
     * @param  array  $data  Transformed collection data
     * @return bool True if valid
     */
    public function validateCollection(array $data): bool
    {
        return ! empty($data['name_ar']) || ! empty($data['name_en']);
    }

    /**
     * Validate transformed book data
     *
     * @param  array  $data  Transformed book data
     * @return bool True if valid
     */
    public function validateBook(array $data): bool
    {
        return ! empty($data['collection_id'])
            && (! empty($data['title_ar']) || ! empty($data['title_en']));
    }

    /**
     * Validate transformed chapter data
     *
     * @param  array  $data  Transformed chapter data
     * @return bool True if valid
     */
    public function validateChapter(array $data): bool
    {
        return ! empty($data['book_id'])
            && isset($data['chapter_number']);
    }

    /**
     * Validate transformed hadith data
     *
     * @param  array  $data  Transformed hadith data
     * @return bool True if valid
     */
    public function validateHadith(array $data): bool
    {
        return isset($data['book_id'])
            && ! empty($data['hadith_number'])
            && (! empty($data['arabic_text']) || ! empty($data['translations']));
    }

    /**
     * Detect collection name from filename
     *
     * Extracts collection identifier from common filename patterns
     *
     * @param  string  $filename  File name or path
     * @return string|null Collection name or null if not detected
     */
    public function detectCollectionFromFilename(string $filename): ?string
    {
        $basename = basename($filename, '.json');
        $basename = strtolower($basename);

        // Common collection patterns
        $patterns = [
            '/nawawi[-_]?40/' => 'nawawi40',
            '/adab[-_]?mufrad/' => 'adabmufrad',
            '/bukhari/' => 'bukhari',
            '/muslim/' => 'muslim',
            '/tirmidhi/' => 'tirmidhi',
            '/abudawud/' => 'abudawud',
            '/nasai/' => 'nasai',
            '/ibnmajah/' => 'ibnmajah',
            '/malik/' => 'malik',
            '/ahmad/' => 'ahmad',
        ];

        foreach ($patterns as $pattern => $collection) {
            if (preg_match($pattern, $basename)) {
                return $collection;
            }
        }

        // If no pattern matches, use cleaned filename
        return preg_replace('/[^a-z0-9]/', '', $basename) ?: null;
    }
}
