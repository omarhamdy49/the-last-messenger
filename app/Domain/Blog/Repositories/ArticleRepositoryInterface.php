<?php

declare(strict_types=1);

namespace App\Domain\Blog\Repositories;

use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Article Repository Interface
 * Defines specific operations for Article repository
 */
interface ArticleRepositoryInterface extends RepositoryInterface
{
    /**
     * Get published articles
     *
     * @param int $perPage
     * @param array<string> $relations
     * @return LengthAwarePaginator
     */
    public function getPublished(int $perPage = 15, array $relations = []): LengthAwarePaginator;

    /**
     * Get articles by category
     *
     * @param int $categoryId
     * @param int $perPage
     * @param array<string> $relations
     * @return LengthAwarePaginator
     */
    public function getByCategory(int $categoryId, int $perPage = 15, array $relations = []): LengthAwarePaginator;

    /**
     * Get articles by series
     *
     * @param int $seriesId
     * @param array<string> $relations
     * @return Collection
     */
    public function getBySeries(int $seriesId, array $relations = []): Collection;

    /**
     * Get featured articles
     *
     * @param int $limit
     * @param array<string> $relations
     * @return Collection
     */
    public function getFeatured(int $limit = 5, array $relations = []): Collection;

    /**
     * Get popular articles
     *
     * @param int $limit
     * @param array<string> $relations
     * @return Collection
     */
    public function getPopular(int $limit = 10, array $relations = []): Collection;

    /**
     * Get recent articles
     *
     * @param int $limit
     * @param array<string> $relations
     * @return Collection
     */
    public function getRecent(int $limit = 10, array $relations = []): Collection;

    /**
     * Search articles
     *
     * @param string $query
     * @param int $perPage
     * @param array<string> $relations
     * @return LengthAwarePaginator
     */
    public function search(string $query, int $perPage = 15, array $relations = []): LengthAwarePaginator;

    /**
     * Find article by slug
     *
     * @param string $slug
     * @param array<string> $relations
     * @return \App\Domain\Blog\Models\Article|null
     */
    public function findBySlug(string $slug, array $relations = []): ?\App\Domain\Blog\Models\Article;

    /**
     * Increment article views
     *
     * @param int $id
     * @return bool
     */
    public function incrementViews(int $id): bool;

    /**
     * Increment article favorites
     *
     * @param int $id
     * @return bool
     */
    public function incrementFavorites(int $id): bool;

    /**
     * Decrement article favorites
     *
     * @param int $id
     * @return bool
     */
    public function decrementFavorites(int $id): bool;
}

