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
     *
     * @param int $perPage
     * @param string $search
     * @param array $filters
     * @param array $with
     * @return LengthAwarePaginator
     */
    public function paginate(
        int $perPage = 50,
        string $search = '',
        array $filters = [],
        array $with = []
    ): LengthAwarePaginator;

    /**
     * Create new tag
     *
     * @param array $data
     * @return Model
     */
    public function create(array $data): Model;

    /**
     * Update tag
     *
     * @param int $id
     * @param array $data
     * @return Model
     */
    public function update(int $id, array $data): Model;

    /**
     * Delete tag
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Bulk delete tags
     *
     * @param array $ids
     * @return int
     */
    public function bulkDelete(array $ids): int;

    /**
     * Find tag by ID or fail
     *
     * @param int $id
     * @return Model
     */
    public function findOrFail(int $id): Model;

    /**
     * Get total count
     *
     * @return int
     */
    public function count(): int;

    /**
     * Get total usage count (all articles using tags)
     *
     * @return int
     */
    public function getTotalUsageCount(): int;

    /**
     * Get most used tag
     *
     * @return Model|null
     */
    public function getMostUsedTag(): ?Model;

    /**
     * Get recently added tags count
     *
     * @param int $days
     * @return int
     */
    public function getRecentlyAddedCount(int $days = 7): int;

    /**
     * Get usage count for specific tag
     *
     * @param int $tagId
     * @return int
     */
    public function getUsageCount(int $tagId): int;

    /**
     * Get published articles count for tag
     *
     * @param int $tagId
     * @return int
     */
    public function getPublishedArticlesCount(int $tagId): int;

    /**
     * Get articles for tag (paginated)
     *
     * @param int $tagId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getArticlesForTag(int $tagId, int $perPage = 20): LengthAwarePaginator;

    /**
     * Find duplicate tag by name
     *
     * @param array $name
     * @return Model|null
     */
    public function findDuplicate(array $name): ?Model;
}
