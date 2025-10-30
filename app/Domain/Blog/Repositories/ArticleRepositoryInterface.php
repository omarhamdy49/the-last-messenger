<?php

declare(strict_types=1);

namespace App\Domain\Blog\Repositories;

use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Article Repository Interface
 * Defines specific operations for Article repository
 */
interface ArticleRepositoryInterface extends RepositoryInterface
{
    /**
     * Get published articles
     *
     * @param  array<string>  $relations
     */
    public function getPublished(int $perPage = 15, array $relations = []): LengthAwarePaginator;

    /**
     * Get articles by category
     *
     * @param  array<string>  $relations
     */
    public function getByCategory(int $categoryId, int $perPage = 15, array $relations = []): LengthAwarePaginator;

    /**
     * Get articles by series
     *
     * @param  array<string>  $relations
     */
    public function getBySeries(int $seriesId, array $relations = []): Collection;

    /**
     * Get featured articles
     *
     * @param  array<string>  $relations
     */
    public function getFeatured(int $limit = 5, array $relations = []): Collection;

    /**
     * Get popular articles
     *
     * @param  array<string>  $relations
     */
    public function getPopular(int $limit = 10, array $relations = []): Collection;

    /**
     * Get recent articles
     *
     * @param  array<string>  $relations
     */
    public function getRecent(int $limit = 10, array $relations = []): Collection;

    /**
     * Search articles
     *
     * @param  array<string>  $relations
     */
    public function search(string $query, int $perPage = 15, array $relations = []): LengthAwarePaginator;

    /**
     * Find article by slug
     *
     * @param  array<string>  $relations
     */
    public function findBySlug(string $slug, array $relations = []): ?\App\Domain\Blog\Models\Article;

    /**
     * Increment article views
     */
    public function incrementViews(int $id): bool;

    /**
     * Increment article favorites
     */
    public function incrementFavorites(int $id): bool;

    /**
     * Decrement article favorites
     */
    public function decrementFavorites(int $id): bool;

    /**
     * Count articles by status
     */
    public function countByStatus(string $status): int;

    /**
     * Clear cache
     */
    public function clearCache(): void;
}
