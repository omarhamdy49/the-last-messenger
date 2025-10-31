<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

/**
 * Search Module Enum
 * Defines searchable modules
 */
enum SearchModule: string
{
    case ALL = 'all';
    case ARTICLE = 'article';
    case HADITH = 'hadith';
    case MEDIA = 'media';
    case SEERAH = 'seerah';
    case SAHABA = 'sahaba';
    case QURAN = 'quran';
    case GENEALOGY = 'genealogy';

    /**
     * Get module label
     */
    public function label(): string
    {
        return match ($this) {
            self::ALL => 'All Content',
            self::ARTICLE => 'Articles',
            self::HADITH => 'Hadith',
            self::MEDIA => 'Media Programs',
            self::SEERAH => 'Seerah Events',
            self::SAHABA => 'Companions',
            self::QURAN => 'Quran',
            self::GENEALOGY => 'Genealogy',
        };
    }

    /**
     * Get searchable fields for this module
     *
     * @return array<string>
     */
    public function searchableFields(): array
    {
        return match ($this) {
            self::ARTICLE => ['title', 'content', 'excerpt'],
            self::HADITH => ['hadith_text', 'arabic_text', 'translation'],
            self::MEDIA => ['title', 'description'],
            self::SEERAH => ['title', 'description', 'location'],
            self::SAHABA => ['name', 'kunya', 'description', 'tribe'],
            self::QURAN => ['arabic_text', 'translation'],
            self::GENEALOGY => ['name', 'description'],
            self::ALL => [],
        };
    }

    /**
     * Get filterable fields for this module
     *
     * @return array<string, FilterType>
     */
    public function filterableFields(): array
    {
        return match ($this) {
            self::ARTICLE => [
                'status' => FilterType::SELECT,
                'category_id' => FilterType::MULTISELECT,
                'author_id' => FilterType::SELECT,
                'published_at' => FilterType::DATE_RANGE,
                'created_at' => FilterType::DATE_RANGE,
            ],
            self::HADITH => [
                'book_id' => FilterType::SELECT,
                'chapter_id' => FilterType::SELECT,
                'authenticity_grade' => FilterType::SELECT,
                'narrator_id' => FilterType::MULTISELECT,
            ],
            self::MEDIA => [
                'category_id' => FilterType::SELECT,
                'type' => FilterType::SELECT,
                'duration' => FilterType::RANGE,
                'release_date' => FilterType::DATE_RANGE,
            ],
            self::SEERAH => [
                'event_type' => FilterType::SELECT,
                'importance_level' => FilterType::SELECT,
                'hijri_date' => FilterType::DATE_RANGE,
                'gregorian_date' => FilterType::DATE_RANGE,
            ],
            self::SAHABA => [
                'status' => FilterType::SELECT,
                'tribe' => FilterType::SELECT,
                'gender' => FilterType::SELECT,
            ],
            self::QURAN => [
                'surah_id' => FilterType::SELECT,
                'juz_number' => FilterType::SELECT,
                'revelation_type' => FilterType::SELECT,
            ],
            self::GENEALOGY => [
                'gender' => FilterType::SELECT,
                'birth_date' => FilterType::DATE_RANGE,
            ],
            self::ALL => [],
        };
    }

    /**
     * Get model class for this module
     */
    public function modelClass(): ?string
    {
        return match ($this) {
            self::ARTICLE => \App\Domain\Blog\Models\Article::class,
            self::HADITH => \App\Domain\Hadith\Models\Hadith::class,
            self::MEDIA => \App\Domain\Media\Models\MediaProgram::class,
            self::SEERAH => \App\Domain\Seerah\Models\SeerahEvent::class,
            self::SAHABA => \App\Domain\Sahaba\Models\Sahabi::class,
            self::QURAN => \App\Domain\Quran\Models\QuranVerse::class,
            self::GENEALOGY => \App\Domain\Genealogy\Models\Person::class,
            self::ALL => null,
        };
    }
}
