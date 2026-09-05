<?php

declare(strict_types=1);

namespace App\Domain\Blog\Repositories;

use App\Domain\Blog\Models\Series;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Series Repository Implementation
 * Handles all database operations for series with optimization and caching
 */
class SeriesRepository extends BaseRepository implements SeriesRepositoryInterface
{
    protected int $cacheTTL = 900; // 15 minutes

    /**
     * {@inheritdoc}
     */
    protected function getModel(): Model
    {
        return new Series;
    }

    /**
     * {@inheritdoc}
     */
    public function paginateWithSearch(
        int $perPage = 50,
        string $search = '',
        array $filters = [],
        array $with = []
    ): LengthAwarePaginator {
        $query = $this->model->withCount('articles')
            ->orderByDesc('created_at');

        // Search the translations table: series.title/description were dropped
        // in favour of series_translations, so the old JSON_EXTRACT raw query
        // threw for every search.
        if (! empty($search)) {
            $query->whereHas('translations', function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        // Apply additional filters
        foreach ($filters as $column => $value) {
            if (! empty($value)) {
                $query->where($column, $value);
            }
        }

        // Eager load relationships
        if (! empty($with)) {
            $query->with($with);
        }

        return $query->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function countByStatus(string $status): int
    {
        $cacheKey = $this->getCacheKey('countByStatus', compact('status'));

        return (int) Cache::remember($cacheKey, $this->cacheTTL, function () use ($status) {
            return $this->model->where('status', $status)->count();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getWithArticles(int $id): Series
    {
        $cacheKey = $this->getCacheKey('getWithArticles', compact('id'));

        return Cache::remember($cacheKey, $this->cacheTTL, function () use ($id) {
            return $this->model->with([
                'articles:id,thumb,writer_id,read_time,views,is_featured,is_pinned,category_id,created_at',
                'translations:id,series_id,locale,title,description,is_ready',
            ])->findOrFail($id);
        });
    }

    // reorderArticles()/updateArticleCount() were removed: both queried a
    // phantom articles.series_id column (the real link is the article_series
    // pivot). The working implementations live in SeriesService::reorderArticles()
    // and Series::updateArticlesCount().

    /**
     * {@inheritdoc}
     */
    public function search(string $query, int $perPage = 50): LengthAwarePaginator
    {
        $searchQuery = $this->model->withCount('articles')
            ->whereHas('translations', function ($q) use ($query) {
                $q->where('title', 'LIKE', "%{$query}%")
                    ->orWhere('description', 'LIKE', "%{$query}%");
            })
            ->orderByDesc('created_at');

        return $searchQuery->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function bulkDelete(array $ids): int
    {
        $deleted = $this->model->whereIn('id', $ids)->delete();
        $this->clearCache();

        return $deleted;
    }

    /**
     * {@inheritdoc}
     */
    public function getStats(): array
    {
        $cacheKey = $this->getCacheKey('stats');

        return Cache::remember($cacheKey, $this->cacheTTL, function () {
            $row = $this->model->toBase()
                ->selectRaw('COUNT(*) as total, COALESCE(SUM(total_articles), 0) as total_articles_in_series, COALESCE(MAX(total_articles), 0) as max_articles')
                ->first();

            return [
                'total' => (int) ($row->total ?? 0),
                'total_articles_in_series' => (int) ($row->total_articles_in_series ?? 0),
                'max_articles' => (int) ($row->max_articles ?? 0),
            ];
        });
    }

    /**
     * Clear cache for all series
     */
    public function clearCache(): void
    {
        // Must bump the version counter first: every cached read in this class
        // (countByStatus/getWithArticles/getStats) and in BaseRepository
        // (find/findBy/all/findAllBy) is stored under a versioned getCacheKey()
        // hash, which a plain Cache::forget() of a literal key never reaches.
        parent::clearCache();

        // Legacy literal keys, kept in case anything outside the repository
        // still writes them.
        Cache::forget('series:all');
        Cache::forget('series:featured');
    }
}
