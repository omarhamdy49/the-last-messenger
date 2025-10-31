<?php

declare(strict_types=1);

namespace App\Domain\Shared\Repositories;

use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Search History Repository Interface
 */
interface SearchHistoryRepositoryInterface extends RepositoryInterface
{
    /**
     * Get recent searches for a user
     *
     * @param  int  $userId  User ID
     * @param  int  $limit  Number of results
     * @return Collection
     */
    public function getRecentByUser(int $userId, int $limit = 20): Collection;

    /**
     * Get popular searches
     *
     * @param  string|null  $module  Filter by module
     * @param  int  $days  Days to look back
     * @param  int  $limit  Number of results
     * @return Collection
     */
    public function getPopular(?string $module = null, int $days = 7, int $limit = 20): Collection;

    /**
     * Get search suggestions
     *
     * @param  string  $query  Partial query
     * @param  string|null  $module  Filter by module
     * @param  int  $limit  Number of suggestions
     * @return Collection
     */
    public function getSuggestions(string $query, ?string $module = null, int $limit = 10): Collection;

    /**
     * Clear user's search history
     *
     * @param  int  $userId  User ID
     * @param  string|null  $module  Filter by module
     * @return int  Number of deleted records
     */
    public function clearByUser(int $userId, ?string $module = null): int;
}
