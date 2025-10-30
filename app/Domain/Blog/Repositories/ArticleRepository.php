<?php

declare(strict_types=1);

namespace App\Domain\Blog\Repositories;

use App\Domain\Blog\Models\Article;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Article Repository Implementation
 * Handles all database operations for articles with optimization and caching
 */
class ArticleRepository extends BaseRepository implements ArticleRepositoryInterface
{
    protected int $cacheTTL = 900; // 15 minutes

    /**
     * {@inheritdoc}
     */
    protected function getModel(): Model
    {
        return new Article;
    }

    /**
     * {@inheritdoc}
     */
    public function getPublished(int $perPage = 15, array $relations = []): LengthAwarePaginator
    {
        $query = $this->model->published()
            ->orderByDesc('published_at');

        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function getByCategory(int $categoryId, int $perPage = 15, array $relations = []): LengthAwarePaginator
    {
        $query = $this->model->published()
            ->byCategory($categoryId)
            ->orderByDesc('published_at');

        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function getBySeries(int $seriesId, array $relations = []): Collection
    {
        $cacheKey = $this->getCacheKey('bySeries', compact('seriesId', 'relations'));

        return Cache::remember($cacheKey, $this->cacheTTL, function () use ($seriesId, $relations) {
            $query = $this->model->published()
                ->bySeries($seriesId)
                ->orderBy('published_at');

            if (! empty($relations)) {
                $query->with($relations);
            }

            return $query->get();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getFeatured(int $limit = 5, array $relations = []): Collection
    {
        $cacheKey = $this->getCacheKey('featured', compact('limit', 'relations'));

        return Cache::remember($cacheKey, $this->cacheTTL, function () use ($limit, $relations) {
            $query = $this->model->featured()->limit($limit);

            if (! empty($relations)) {
                $query->with($relations);
            }

            return $query->get();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getPopular(int $limit = 10, array $relations = []): Collection
    {
        $cacheKey = $this->getCacheKey('popular', compact('limit', 'relations'));

        return Cache::remember($cacheKey, $this->cacheTTL, function () use ($limit, $relations) {
            $query = $this->model->popular($limit);

            if (! empty($relations)) {
                $query->with($relations);
            }

            return $query->get();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getRecent(int $limit = 10, array $relations = []): Collection
    {
        $cacheKey = $this->getCacheKey('recent', compact('limit', 'relations'));

        return Cache::remember($cacheKey, $this->cacheTTL, function () use ($limit, $relations) {
            $query = $this->model->recent($limit);

            if (! empty($relations)) {
                $query->with($relations);
            }

            return $query->get();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function search(string $query, int $perPage = 15, array $relations = []): LengthAwarePaginator
    {
        $searchQuery = $this->model->published()
            ->search($query)
            ->orderByDesc('published_at');

        if (! empty($relations)) {
            $searchQuery->with($relations);
        }

        return $searchQuery->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function findBySlug(string $slug, array $relations = []): ?Article
    {
        $cacheKey = $this->getCacheKey('findBySlug', compact('slug', 'relations'));

        return Cache::remember($cacheKey, $this->cacheTTL, function () use ($slug, $relations) {
            $query = $this->model->where('slug', $slug);

            if (! empty($relations)) {
                $query->with($relations);
            }

            return $query->first();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function incrementViews(int $id): bool
    {
        $result = $this->model->where('id', $id)->increment('views_count');
        $this->clearCache();

        return $result > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function incrementFavorites(int $id): bool
    {
        $result = $this->model->where('id', $id)->increment('favorites_count');
        $this->clearCache();

        return $result > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function decrementFavorites(int $id): bool
    {
        $result = $this->model->where('id', $id)->decrement('favorites_count');
        $this->clearCache();

        return $result > 0;
    }

    /**
     * Get articles with eager loaded relationships (optimized)
     *
     * @param  array<string>  $relations
     */
    public function getAllWithRelations(array $relations = []): Collection
    {
        return $this->model->published()
            ->with($relations)
            ->orderByDesc('published_at')
            ->get();
    }

    /**
     * Get draft articles for specific user
     */
    public function getDraftsByUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->where('user_id', $userId)
            ->where('status', 'draft')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Count articles by status
     */
    public function countByStatus(string $status): int
    {
        return $this->model->where('status', $status)->count();
    }

    /**
     * Clear cache for all articles
     */
    public function clearCache(): void
    {
        Cache::tags(['articles'])->flush();
    }
}
