<?php

declare(strict_types=1);

namespace App\Domain\Blog\Repositories;

use App\Domain\Blog\Enums\CategoryType;
use App\Domain\Blog\Models\Category;
use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Category Repository Interface
 * Defines specific operations for Category repository
 */
interface CategoryRepositoryInterface extends RepositoryInterface
{
    /**
     * Get paginated categories with search and filters
     *
     * @param  string  $search  Search query across name/description in all languages
     * @param  array<string, mixed>  $filters  Additional filters (type, parent_id, etc.)
     * @param  array<string>  $with  Relations to eager load
     */
    public function paginateWithFilters(int $perPage = 50, string $search = '', array $filters = [], array $with = []): LengthAwarePaginator;

    /**
     * Count categories by status
     */
    public function countByStatus(string $status): int;

    /**
     * Count categories by type
     */
    public function countByType(CategoryType $type): int;

    /**
     * Get root categories (no parent)
     */
    public function getRootCategories(): Collection;

    /**
     * Get root categories excluding specific category (for parent selector)
     */
    public function getRootCategoriesExcept(int $excludeId): Collection;

    /**
     * Search categories across all languages
     *
     * @param  string|null  $type  Filter by category type (blog, media, timeline)
     */
    public function search(string $query, int $perPage = 50, ?string $type = null): LengthAwarePaginator;

    /**
     * Bulk delete categories by IDs
     *
     * @param  array<int>  $ids
     * @return int Number of deleted records
     */
    public function bulkDelete(array $ids): int;

    /**
     * Update display order for multiple categories
     *
     * @param  array<array{id: int, display_order: int}>  $items
     */
    public function updateDisplayOrder(array $items): void;

    /**
     * Get root categories with children eager-loaded, optimised for public API
     */
    public function listRoots(): Collection;

    /**
     * Find a category by slug with children and parent eager-loaded
     */
    public function findBySlug(string $slug): ?Category;

    /**
     * Clear cache
     */
    public function clearCache(): void;

    /**
     * Restore a soft-deleted category.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function restoreById(int $id): Category;
}
