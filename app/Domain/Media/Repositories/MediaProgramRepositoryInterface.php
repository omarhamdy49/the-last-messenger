<?php

declare(strict_types=1);

namespace App\Domain\Media\Repositories;

use App\Domain\Media\Models\MediaProgram;
use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;

interface MediaProgramRepositoryInterface extends RepositoryInterface
{
    /**
     * Paginated list, optionally filtered by category/search term/status and sorted.
     *
     * @param  string|null  $category  ProgramCategory value
     * @param  string  $sortBy  Column: 'display_order'|'views_count'|'created_at'|'rating'
     * @param  string  $sortDir  'asc'|'desc'
     * @param  string|null  $search  Matches translation title/description
     * @param  bool  $publishedOnly  Restrict to published programs (public consumers)
     * @param  string|null  $status  Admin lifecycle filter: 'published'|'scheduled'|'draft'
     */
    public function paginatePrograms(
        int $perPage = 15,
        ?string $category = null,
        string $sortBy = 'display_order',
        string $sortDir = 'asc',
        ?string $search = null,
        bool $publishedOnly = false,
        ?string $status = null
    ): LengthAwarePaginator;

    /**
     * Publish every program whose scheduled publish_at has passed, clearing caches.
     *
     * @return int Number of programs published
     */
    public function publishDue(): int;

    /** Top-N by views. */
    public function getPopular(int $limit = 12): Collection;

    /** Most recently created. */
    public function getRecent(int $limit = 12): Collection;

    /** Featured flag = true. */
    public function getFeatured(int $limit = 6): Collection;

    /** Featured programs with full hero columns (description, year, maturity) + episode count. */
    public function getFeaturedForHero(int $limit = 5): Collection;

    /** Programs flagged is_trending = true, with episode count. */
    public function getTrendingFlagged(int $limit = 12): Collection;

    /** Programs of one category for a horizontal row, with episode count. */
    public function getByCategory(string $category, int $limit = 12): Collection;

    /**
     * Published programs across the given categories, fetched in one query and
     * grouped by category value (each capped at {$perCategory}). Avoids the
     * N+1 of calling getByCategory() once per featured category.
     *
     * @param  list<string>  $categoryKeys
     * @return SupportCollection<string, Collection<int, MediaProgram>>
     */
    public function getPublishedGroupedByCategories(array $categoryKeys, int $perCategory = 12): SupportCollection;

    /** Full-text / LIKE search across translations. */
    public function search(string $term, int $perPage = 15, bool $publishedOnly = false): LengthAwarePaginator;

    /** Single program with translations eager-loaded. */
    public function findBySlug(string $slug): ?MediaProgram;

    /**
     * Category keys (ProgramCategory values) featured on the public list, in
     * display order. Falls back to every enum value while the
     * media_categories table is unseeded.
     *
     * @return list<string>
     */
    public function getFeaturedCategoryKeys(): array;

    /** All category rows with their program counts, in display order. */
    public function getAllCategoriesWithProgramCounts(): Collection;

    /** Toggle a category's visibility on the public programs list. */
    public function setCategoryFeatured(string $key, bool $isFeatured): void;

    /** Single category row with program count and aggregate views. */
    public function findCategoryByKey(string $key): ?\App\Domain\Media\Models\MediaCategory;

    /**
     * Update a category's presentation fields.
     *
     * @param  array{name?:array|null,description?:array|null,icon?:string|null,color?:string|null,is_featured?:bool,display_order?:int}  $attributes
     */
    public function updateCategory(string $key, array $attributes): bool;

    /**
     * Delete a category row. Refused (returns false) while programs are
     * still assigned to the key.
     */
    public function deleteCategory(string $key): bool;

    /**
     * Persist display_order to match the given key order (0-based).
     *
     * @param  list<string>  $orderedKeys
     */
    public function reorderCategories(array $orderedKeys): void;

    /** Clear all cache entries touching this program. */
    public function clearCacheForModel(int $id): void;

    /** Aggregate stats for dashboard (single query). */
    public function getIndexStats(): array;
}
