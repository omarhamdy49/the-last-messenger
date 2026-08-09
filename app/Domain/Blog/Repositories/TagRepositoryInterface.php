<?php

declare(strict_types=1);

namespace App\Domain\Blog\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

/**
 * TagRepositoryInterface
 *
 * Contract for tag data access
 */
interface TagRepositoryInterface
{
    /**
     * Get paginated tags with filters
     */
    public function paginate(
        int $perPage = 50,
        string $search = '',
        array $filters = [],
        array $with = []
    ): LengthAwarePaginator;

    /**
     * Create new tag
     */
    public function create(array $data): Model;

    /**
     * Update tag
     */
    public function update(int $id, array $data): Model;

    /**
     * Delete tag
     */
    public function delete(int $id): bool;

    /**
     * Bulk delete tags
     */
    public function bulkDelete(array $ids): int;

    /**
     * Find tag by ID or fail
     */
    public function findOrFail(int $id): Model;

    /**
     * Get total count
     */
    public function count(): int;

    /**
     * Get total usage count (all articles using tags)
     */
    public function getTotalUsageCount(): int;

    /**
     * Get most used tag
     */
    public function getMostUsedTag(): ?Model;

    /**
     * Get recently added tags count
     */
    public function getRecentlyAddedCount(int $days = 7): int;

    /**
     * Get usage count for specific tag
     */
    public function getUsageCount(int $tagId): int;

    /**
     * Get published articles count for tag
     */
    public function getPublishedArticlesCount(int $tagId): int;

    /**
     * Get articles for tag (paginated)
     */
    public function getArticlesForTag(int $tagId, int $perPage = 20): LengthAwarePaginator;

    /**
     * Find duplicate tag by name
     */
    public function findDuplicate(array $name): ?Model;

}
