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
    case MEDIA = 'media';
    case SEERAH = 'seerah';
    case SAHABA = 'sahaba';
    case QURAN = 'quran';

    /**
     * Get module label
     */
    public function label(): string
    {
        return match ($this) {
            self::ALL => 'All Content',
            self::ARTICLE => 'Articles',
            self::MEDIA => 'Media Programs',
            self::SEERAH => 'Seerah Events',
            self::SAHABA => 'Muslims',
            self::QURAN => 'Quran',
        };
    }

    /**
     * Get searchable fields for this module
     *
     * @return array<string>
     */
    public function searchableFields(): array
    {
        // Searchable text for content modules lives in per-locale translation
        // tables, not on the parent row. Fields written as "relation.column"
        // are resolved via a relationship existence query by SearchQueryBuilder.
        return match ($this) {
            self::ARTICLE => ['locales.title', 'locales.excerpt', 'locales.content'],
            self::MEDIA => ['translations.title', 'translations.description'],
            self::SEERAH => ['translations.title', 'translations.description', 'location'],
            self::SAHABA => ['name_ar', 'name_en'],
            self::QURAN => ['translations.text', 'translations.text_notashkeel'],
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
            self::MEDIA => [
                // Real media_programs columns (category is an enum string, not a FK).
                'category' => FilterType::SELECT,
                'maturity_rating' => FilterType::SELECT,
                'year' => FilterType::RANGE,
            ],
            self::SEERAH => [
                'seerah_event_type_id' => FilterType::SELECT,
                'importance_level' => FilterType::SELECT,
                'hijri_date' => FilterType::DATE_RANGE,
                'gregorian_date' => FilterType::DATE_RANGE,
            ],
            self::SAHABA => [
                // `status` and `tribe` were dropped with the Sahaba module.
                'gender' => FilterType::SELECT,
            ],
            self::QURAN => [
                'surah_id' => FilterType::SELECT,
                'juz_number' => FilterType::SELECT,
                'revelation_type' => FilterType::SELECT,
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
            self::ARTICLE => \App\Models\Article::class,
            self::MEDIA => \App\Domain\Media\Models\MediaProgram::class,
            self::SEERAH => \App\Domain\Seerah\Models\SeerahEvent::class,
            self::SAHABA => \App\Domain\People\Models\Muslim::class,
            self::QURAN => \App\Domain\Quran\Models\Verse::class,
            self::ALL => null,
        };
    }
}
