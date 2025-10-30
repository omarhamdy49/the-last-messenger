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
     * Filter articles by multiple criteria
     *
     * @param  array<string, mixed>  $filters
     * @param  array<string>  $relations
     */
    public function filterArticles(array $filters, int $perPage = 15, array $relations = []): LengthAwarePaginator
    {
        $query = $this->model->query();

        // Filter by category
        if (isset($filters['category_id']) && $filters['category_id']) {
            $query->byCategory((int) $filters['category_id']);
        }

        // Filter by author
        if (isset($filters['author_id']) && $filters['author_id']) {
            $query->where('user_id', (int) $filters['author_id']);
        }

        // Filter by series
        if (isset($filters['series_id']) && $filters['series_id']) {
            $query->bySeries((int) $filters['series_id']);
        }

        // Filter by status
        if (isset($filters['status']) && $filters['status']) {
            $query->where('status', $filters['status']);
        }

        // Filter by date range
        if (isset($filters['start_date']) && $filters['start_date']) {
            $query->where('published_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date']) && $filters['end_date']) {
            $query->where('published_at', '<=', $filters['end_date']);
        }

        // Filter by tag
        if (isset($filters['tag_id']) && $filters['tag_id']) {
            $query->whereHas('tags', function ($q) use ($filters) {
                $q->where('tags.id', (int) $filters['tag_id']);
            });
        }

        // Filter by featured
        if (isset($filters['is_featured']) && $filters['is_featured']) {
            $query->where('is_featured', (bool) $filters['is_featured']);
        }

        // Search query
        if (isset($filters['search']) && $filters['search']) {
            $query->search($filters['search']);
        }

        // Eager load relations
        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query->orderByDesc('published_at')->paginate($perPage);
    }

    /**
     * Get articles by author
     */
    public function getByAuthor(int $authorId, int $perPage = 15, array $relations = []): LengthAwarePaginator
    {
        $query = $this->model->published()
            ->where('user_id', $authorId)
            ->orderByDesc('published_at');

        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get articles by tag
     */
    public function getByTag(int $tagId, int $perPage = 15, array $relations = []): LengthAwarePaginator
    {
        $query = $this->model->published()
            ->whereHas('tags', function ($q) use ($tagId) {
                $q->where('tags.id', $tagId);
            })
            ->orderByDesc('published_at');

        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get articles by date range
     */
    public function getByDateRange(string $startDate, string $endDate, int $perPage = 15, array $relations = []): LengthAwarePaginator
    {
        $query = $this->model->published()
            ->whereBetween('published_at', [$startDate, $endDate])
            ->orderByDesc('published_at');

        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query->paginate($perPage);
    }

    /**
     * Clear cache for all articles
     */
    public function clearCache(): void
    {
        Cache::tags(['articles'])->flush();
    }
}
