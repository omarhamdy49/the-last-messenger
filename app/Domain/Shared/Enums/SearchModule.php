<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

use App\Domain\Media\Models\MediaProgram;
use App\Domain\People\Models\Muslim;
use App\Domain\Quran\Models\Verse;
use App\Domain\Seerah\Models\SeerahEvent;
use App\Models\Article;

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
            self::SEERAH => ['translations.title', 'translations.description'],
            self::SAHABA => ['name_ar', 'name_en'],
            self::QURAN => ['translations.text', 'translations.text_notashkeel'],
            self::ALL => [],
        };
    }

    /**
     * FULLTEXT groups for this module — one entry per MySQL FULLTEXT index.
     *
     * `MATCH()` requires an index covering EXACTLY the listed column set, and cannot span
     * tables. That is why `getFullTextColumns()` on the models was never viable and every
     * search silently fell back to a full-table LIKE: for all content modules the
     * searchable text lives in a per-locale translation table, so the MATCH has to happen
     * inside the relationship subquery, not against the parent.
     *
     * Verified against the live schema (information_schema.STATISTICS, INDEX_TYPE=FULLTEXT):
     *
     *   article_locale            ft_search                        (title, excerpt)
     *   media_program_translations fulltext_program_title_desc      (title, description)
     *   seerah_event_translations fulltext_title_description        (title, description)
     *   quran_verse_translations  …_text_text_notashkeel_fulltext   (text, text_notashkeel)
     *   muslims                   ft_muslims_search_name_ar        (search_name_ar)
     *   muslims                   ft_muslims_search_name_en        (search_name_en)
     *
     * Note `muslims` has two SEPARATE single-column indexes, so they are two groups —
     * `MATCH(search_name_ar, search_name_en)` would be a MySQL error. They also index the
     * *folded* columns, not `name_ar`/`name_en`, which is what makes the Arabic
     * normalisation (الصلاة vs الصلاه) actually match.
     *
     * `relation` null means the columns live on the module's own table.
     *
     * @return array<int, array{relation: string|null, columns: array<int, string>}>
     */
    public function fullTextGroups(): array
    {
        return match ($this) {
            self::ARTICLE => [
                ['relation' => 'locales', 'columns' => ['title', 'excerpt']],
            ],
            self::MEDIA => [
                ['relation' => 'translations', 'columns' => ['title', 'description']],
            ],
            self::SEERAH => [
                ['relation' => 'translations', 'columns' => ['title', 'description']],
            ],
            self::QURAN => [
                ['relation' => 'translations', 'columns' => ['text', 'text_notashkeel']],
            ],
            self::SAHABA => [
                ['relation' => null, 'columns' => ['search_name_ar']],
                ['relation' => null, 'columns' => ['search_name_en']],
            ],
            self::ALL => [],
        };
    }

    /**
     * Searchable fields with no FULLTEXT index behind them. These keep LIKE and are OR-ed
     * alongside the MATCH clauses, so narrowing the index coverage never silently drops a
     * field from search.
     *
     * @return array<int, string>
     */
    public function likeOnlyFields(): array
    {
        return match ($this) {
            // article_locale's ft_search covers title + excerpt only — `content` is not
            // in the index.
            self::ARTICLE => ['locales.content'],
            default => [],
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
                'event_type' => FilterType::SELECT,
                'sm_id' => FilterType::SELECT,
                'era' => FilterType::SELECT,
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
            self::ARTICLE => Article::class,
            self::MEDIA => MediaProgram::class,
            self::SEERAH => SeerahEvent::class,
            self::SAHABA => Muslim::class,
            self::QURAN => Verse::class,
            self::ALL => null,
        };
    }
}
