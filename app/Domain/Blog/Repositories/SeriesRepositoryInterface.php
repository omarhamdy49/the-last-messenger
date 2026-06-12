<?php

declare(strict_types=1);

namespace App\Domain\Blog\Repositories;

use App\Domain\Blog\Models\Series;
use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Series Repository Interface
 * Defines contract for Series data access operations
 */
interface SeriesRepositoryInterface extends RepositoryInterface
{
    /**
     * Get paginated series with search and filters
     *
     * @param  int  $perPage  Number of items per page
     * @param  string  $search  Search query across title/description
     * @param  array<string, mixed>  $filters  Additional filters (e.g., status)
     * @param  array<string>  $with  Relations to eager load
     */
    public function paginateWithSearch(
        int $perPage = 50,
        string $search = '',
        array $filters = [],
        array $with = []
    ): LengthAwarePaginator;

    /**
     * Count series by status
     *
     * @param  string  $status  Status to count
     */
    public function countByStatus(string $status): int;

    /**
     * Get series with all articles ordered
     *
     * @param  int  $id  Series ID
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getWithArticles(int $id): Series;

    /**
     * Reorder articles within series
     *
     * @param  int  $seriesId  Series ID
     * @param  array<int>  $articleIds  Article IDs in desired order
     */
    public function reorderArticles(int $seriesId, array $articleIds): void;

    /**
     * Update article count for series
     *
     * @param  int  $id  Series ID
     */
    public function updateArticleCount(int $id): void;

    /**
     * Search series across all languages
     *
     * @param  string  $query  Search query
     * @param  int  $perPage  Number of items per page
     */
    public function search(string $query, int $perPage = 50): LengthAwarePaginator;

    /**
     * Bulk delete series by IDs
     *
     * @param  array<int>  $ids  Series IDs to delete
     * @return int Number of deleted records
     */
    public function bulkDelete(array $ids): int;

    /**
     * Get aggregate stats for the series index page.
     *
     * Returns:
     *   - total:                Total number of series rows
     *   - total_articles_in_series: Sum of total_articles across all series
     *   - max_articles:         Max total_articles value (0 if no rows)
     *
     * @return array{total: int, total_articles_in_series: int, max_articles: int}
     */
    public function getStats(): array;
}
