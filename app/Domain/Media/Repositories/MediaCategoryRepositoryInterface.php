<?php

declare(strict_types=1);

namespace App\Domain\Media\Repositories;

use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * MediaCategory Repository Interface
 * Defines specific operations for MediaCategory repository
 */
interface MediaCategoryRepositoryInterface extends RepositoryInterface
{
    /**
     * Get paginated categories with filters
     *
     * @param  array<string, mixed>  $filters
     * @param  array<string>  $with
     */
    public function paginateWithFilters(int $perPage = 50, string $search = '', array $filters = [], array $with = []): LengthAwarePaginator;

    /**
     * Count total programs across all categories
     */
    public function countTotalPrograms(): int;

    /**
     * Get programs by category with pagination
     */
    public function getProgramsByCategory(int $categoryId, int $perPage = 50): LengthAwarePaginator;

    /**
     * Update display order for multiple categories
     *
     * @param  array<int, int>  $items Array of [id => display_order]
     */
    public function updateDisplayOrder(array $items): void;

    /**
     * Search categories by name or description
     */
    public function search(string $query, int $perPage = 50): LengthAwarePaginator;

    /**
     * Get all categories ordered by display order
     *
     * @param  array<string>  $with
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllOrdered(array $with = []): \Illuminate\Database\Eloquent\Collection;

    /**
     * Clear cache
     */
    public function clearCache(): void;
}
