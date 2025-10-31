<?php

declare(strict_types=1);

namespace App\Domain\Blog\Repositories;

use App\Domain\Blog\Models\Article;
use App\Domain\Blog\Models\Series;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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

        // Apply search across JSON title and description fields
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $languages = ['ar', 'en', 'fr', 'tr', 'ur'];
                foreach ($languages as $lang) {
                    $q->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(title, '$.{$lang}')) LIKE ?", ["%{$search}%"])
                        ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(description, '$.{$lang}')) LIKE ?", ["%{$search}%"]);
                }
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

        return Cache::remember($cacheKey, $this->cacheTTL, function () use ($status) {
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
            return $this->model->with(['articles' => function ($query) {
                $query->orderBy('published_at');
            }])->findOrFail($id);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function reorderArticles(int $seriesId, array $articleIds): void
    {
        DB::transaction(function () use ($seriesId, $articleIds) {
            $position = 1;
            foreach ($articleIds as $articleId) {
                Article::where('id', $articleId)
                    ->where('series_id', $seriesId)
                    ->update([
                        'published_at' => now()->addSeconds($position),
                    ]);
                $position++;
            }
        });

        $this->clearCache();
    }

    /**
     * {@inheritdoc}
     */
    public function updateArticleCount(int $id): void
    {
        $series = $this->findOrFail($id);
        $count = Article::where('series_id', $id)->count();

        $series->update(['total_articles' => $count]);
        $this->clearCache();
    }

    /**
     * {@inheritdoc}
     */
    public function search(string $query, int $perPage = 50): LengthAwarePaginator
    {
        $searchQuery = $this->model->withCount('articles')
            ->where(function ($q) use ($query) {
                $languages = ['ar', 'en', 'fr', 'tr', 'ur'];
                foreach ($languages as $lang) {
                    $q->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(title, '$.{$lang}')) LIKE ?", ["%{$query}%"])
                        ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(description, '$.{$lang}')) LIKE ?", ["%{$query}%"]);
                }
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
     * Clear cache for all series
     */
    public function clearCache(): void
    {
        Cache::forget('series:all');
        Cache::forget('series:featured');
    }
}
