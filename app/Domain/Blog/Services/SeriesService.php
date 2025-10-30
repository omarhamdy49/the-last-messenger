<?php

declare(strict_types=1);

namespace App\Domain\Blog\Services;

use App\Domain\Blog\Models\Series;
use App\Domain\Blog\Repositories\SeriesRepositoryInterface;
use App\Domain\Shared\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Series Service
 * Handles business logic for series management
 */
class SeriesService extends BaseService
{
    /**
     * Constructor
     *
     * @param  SeriesRepositoryInterface  $repository
     */
    public function __construct(
        SeriesRepositoryInterface $repository
    ) {
        $this->repository = $repository;
    }

    /**
     * Get paginated series with search and filters
     *
     * @param  int  $perPage  Number of items per page
     * @param  string  $search  Search query
     * @param  array<string, mixed>  $filters  Additional filters
     * @param  array<string>  $with  Relations to eager load
     * @return LengthAwarePaginator
     */
    public function paginateWithSearch(
        int $perPage = 50,
        string $search = '',
        array $filters = [],
        array $with = []
    ): LengthAwarePaginator {
        return $this->repository->paginateWithSearch($perPage, $search, $filters, $with);
    }

    /**
     * Create new series
     *
     * @param  array<string, mixed>  $data
     * @return Series
     */
    public function createSeries(array $data): Series
    {
        $series = $this->repository->create($data);
        $this->repository->clearCache();

        return $series;
    }

    /**
     * Update series by ID
     *
     * @param  int  $id
     * @param  array<string, mixed>  $data
     * @return Series
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function updateSeries(int $id, array $data): Series
    {
        $this->repository->update($id, $data);
        $this->repository->clearCache();

        return $this->repository->findOrFail($id);
    }

    /**
     * Delete series by ID
     *
     * @param  int  $id
     * @return bool
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function deleteSeries(int $id): bool
    {
        $deleted = $this->repository->delete($id);
        $this->repository->clearCache();

        return $deleted;
    }

    /**
     * Bulk delete series
     *
     * @param  array<int>  $ids  Series IDs to delete
     * @return int  Number of deleted records
     */
    public function bulkDelete(array $ids): int
    {
        $deleted = $this->repository->bulkDelete($ids);
        $this->repository->clearCache();

        return $deleted;
    }

    /**
     * Find series by ID with articles
     *
     * @param  int  $id
     * @return Series
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findById(int $id): Series
    {
        return $this->repository->findOrFail($id);
    }

    /**
     * Get total series count
     *
     * @return int
     */
    public function getTotalCount(): int
    {
        return $this->repository->count();
    }

    /**
     * Count series by status
     *
     * @param  string  $status
     * @return int
     */
    public function countByStatus(string $status): int
    {
        return $this->repository->countByStatus($status);
    }

    /**
     * Get series with all articles in order
     *
     * @param  int  $id
     * @return Series
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getWithArticles(int $id): Series
    {
        return $this->repository->getWithArticles($id);
    }

    /**
     * Reorder articles within series
     *
     * @param  int  $seriesId
     * @param  array<int>  $articleIds  Article IDs in desired order
     * @return void
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function reorderArticles(int $seriesId, array $articleIds): void
    {
        // Verify series exists
        $this->repository->findOrFail($seriesId);

        // Reorder articles
        $this->repository->reorderArticles($seriesId, $articleIds);

        // Clear cache
        $this->repository->clearCache();
    }

    /**
     * Update article count for series
     *
     * @param  int  $id
     * @return void
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function updateArticleCount(int $id): void
    {
        $this->repository->updateArticleCount($id);
        $this->repository->clearCache();
    }

    /**
     * Search series across all languages
     *
     * @param  string  $query
     * @param  int  $perPage
     * @return LengthAwarePaginator
     */
    public function search(string $query, int $perPage = 50): LengthAwarePaginator
    {
        return $this->repository->search($query, $perPage);
    }
}
