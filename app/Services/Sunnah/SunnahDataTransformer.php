<?php

declare(strict_types=1);

namespace App\Services\Sunnah;

use App\Domain\Hadith\Enums\HadithGrade;
use stdClass;

class SunnahDataTransformer
{
    /**
     * Supported languages from our system
     */
    private const SUPPORTED_LANGUAGES = ['ar', 'en'];

    /**
     * Transform collection from Sunnah API to our database format
     */
    public function transformCollection(stdClass $collection): array
    {
        $name = [];
        $description = [];

        // Extract translations from API response
        if (isset($collection->collection) && is_array($collection->collection)) {
            foreach ($collection->collection as $item) {
                $lang = $item->lang ?? 'ar';
                $name[$lang] = $item->title ?? '';
                $description[$lang] = $item->shortIntro ?? '';
            }
        }

        // Fill missing languages with empty strings
        foreach (self::SUPPORTED_LANGUAGES as $lang) {
            if (! isset($name[$lang])) {
                $name[$lang] = '';
            }
            if (! isset($description[$lang])) {
                $description[$lang] = '';
            }
        }

        return [
            'slug' => $collection->name ?? '',
            'collection_name' => $collection->name ?? '',
            'name' => $name,
            'description' => $description,
            'author_name' => null,
            'total_hadith_count' => $collection->totalAvailableHadith ?? 0,
            'display_order' => 0,
            'book_metadata' => [
                'external_name' => $collection->name ?? '',
                'has_books' => $collection->hasBooks ?? false,
                'has_chapters' => $collection->hasChapters ?? false,
                'total_hadith' => $collection->totalHadith ?? 0,
                'total_available_hadith' => $collection->totalAvailableHadith ?? 0,
            ],
        ];
    }

    /**
     * Transform book from Sunnah API to our database format
     * Note: Books become chapters in our schema
     */
    public function transformBook(stdClass $book, string $collectionName): array
    {
        $name = [];

        // Extract translations from API response
        if (isset($book->book) && is_array($book->book)) {
            foreach ($book->book as $item) {
                $lang = $item->lang ?? 'ar';
                $name[$lang] = $item->name ?? '';
            }
        }

        // Fill missing languages with empty strings
        foreach (self::SUPPORTED_LANGUAGES as $lang) {
            if (! isset($name[$lang])) {
                $name[$lang] = '';
            }
        }

        return [
            'book_number' => (int) ($book->bookNumber ?? 0),
            'chapter_number' => (int) ($book->bookNumber ?? 0),
            'external_chapter_id' => (string) ($book->bookNumber ?? ''),
            'name' => $name,
            'description' => $name, // Use book name as description in our schema
            'display_order' => (int) ($book->bookNumber ?? 0),
            'book_metadata' => [
                'hadith_start_number' => $book->hadithStartNumber ?? null,
                'hadith_end_number' => $book->hadithEndNumber ?? null,
                'number_of_hadith' => $book->numberOfHadith ?? 0,
            ],
        ];
    }

    /**
     * Transform chapter from Sunnah API to our database format
     */
    public function transformChapter(stdClass $chapter, string $collectionName, string $bookNumber): array
    {
        $name = [];
        $description = [];

        // Extract translations from API response
        if (isset($chapter->chapter) && is_array($chapter->chapter)) {
            foreach ($chapter->chapter as $item) {
                $lang = $item->lang ?? 'ar';
                $name[$lang] = $item->chapterTitle ?? '';
                $description[$lang] = $item->intro ?? '';
            }
        }

        // Fill missing languages with empty strings
        foreach (self::SUPPORTED_LANGUAGES as $lang) {
            if (! isset($name[$lang])) {
                $name[$lang] = '';
            }
            if (! isset($description[$lang])) {
                $description[$lang] = '';
            }
        }

        return [
            'chapter_number' => (int) ($chapter->chapterId ?? 0),
            'external_chapter_id' => (string) ($chapter->chapterId ?? ''),
            'name' => $name,
            'description' => $description,
            'display_order' => (int) ($chapter->chapterId ?? 0),
            'book_number' => (int) $bookNumber,
        ];
    }

    /**
     * Transform hadith from Sunnah API to our database format
     */
    public function transformHadith(stdClass $hadith, string $collectionName, string $bookNumber): array
    {
        $arabicText = '';
        $englishText = '';
        $translations = [];
        $grade = 'unknown';
        $urn = null;

        // Initialize all languages with empty strings
        foreach (self::SUPPORTED_LANGUAGES as $lang) {
            $translations[$lang] = '';
        }

        // Extract translations and metadata from API response
        if (isset($hadith->hadith) && is_array($hadith->hadith)) {
            foreach ($hadith->hadith as $item) {
                $lang = $item->lang ?? 'ar';
                $body = $item->body ?? '';

                if ($lang === 'ar') {
                    $arabicText = $body;
                    $translations['ar'] = $body;
                } elseif ($lang === 'en') {
                    $englishText = $body;
                    $translations['en'] = $body;
                } elseif (in_array($lang, self::SUPPORTED_LANGUAGES)) {
                    $translations[$lang] = $body;
                }

                // Get grade from first language that has it
                if (($grade === 'unknown' || empty($grade)) && isset($item->grades)) {
                    $grade = $this->extractGrade($item);
                }

                // Get URN from any language
                if (! $urn && isset($item->urn)) {
                    $urn = $item->urn;
                }
            }
        }

        // Map grade to our enum
        $grade = $this->mapGrade($grade);

        return [
            'book_id' => 0, // Will be set by the sync service using FK
            'chapter_id' => null, // Optional, will be set if available
            'hadith_number' => (int) ($hadith->hadithNumber ?? 0),
            'external_id' => (string) ($urn ?? $hadith->hadithNumber ?? ''), // Use URN as external_id
            'urn' => $urn,
            'arabic_text' => $arabicText,
            'translations' => $translations,
            'narrator_chain' => null, // Not available from API
            'grade' => $grade,
            'reference_url' => $urn ? "https://sunnah.com/{$urn}" : null,
            'collection_name' => $collectionName,
            'book_number' => (int) $bookNumber,
        ];
    }

    /**
     * Extract grade from hadith item
     */
    private function extractGrade(stdClass $item): string
    {
        if (! isset($item->grades) || ! is_array($item->grades) || empty($item->grades)) {
            return 'unknown';
        }

        // Get the first grade
        $firstGrade = reset($item->grades);

        return $firstGrade->grade ?? 'unknown';
    }

    /**
     * Map Sunnah.com grade to our HadithGrade enum
     */
    private function mapGrade(string $grade): string
    {
        $gradeMap = [
            'Sahih' => HadithGrade::SAHIH->value,
            'sahih' => HadithGrade::SAHIH->value,
            'Hasan' => HadithGrade::HASAN->value,
            'hasan' => HadithGrade::HASAN->value,
            'Daif' => HadithGrade::DAIF->value,
            'daif' => HadithGrade::DAIF->value,
            'Mawdu' => HadithGrade::MAWDU->value,
            'mawdu' => HadithGrade::MAWDU->value,
            'Fabricated' => HadithGrade::MAWDU->value,
            'fabricated' => HadithGrade::MAWDU->value,
        ];

        return $gradeMap[trim($grade)] ?? HadithGrade::UNKNOWN->value;
    }

    /**
     * Validate transformed collection data
     */
    public function validateCollection(array $data): bool
    {
        return ! empty($data['slug']) && ! empty($data['name']['ar']);
    }

    /**
     * Validate transformed book/chapter data
     */
    public function validateBook(array $data): bool
    {
        return isset($data['book_number']) && ! empty($data['name']['ar']);
    }

    /**
     * Validate transformed chapter data
     */
    public function validateChapter(array $data): bool
    {
        // Accept if we have a chapter number and at least one language name
        $hasName = ! empty($data['name']['ar']) ||
                  ! empty($data['name']['en']);

        return isset($data['chapter_number']) && $hasName;
    }

    /**
     * Validate transformed hadith data
     */
    public function validateHadith(array $data): bool
    {
        // Accept if we have at least one language translation and a hadith number
        $hasTranslation = ! empty($data['translations']['ar']) ||
                         ! empty($data['translations']['en']);

        return $hasTranslation && $data['hadith_number'] > 0;
    }
}
