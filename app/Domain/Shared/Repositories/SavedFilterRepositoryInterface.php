<?php

declare(strict_types=1);

namespace App\Domain\Shared\Repositories;

use App\Domain\Shared\Contracts\RepositoryInterface;
use App\Domain\Shared\Models\SavedFilter;
use Illuminate\Support\Collection;

/**
 * Saved Filter Repository Interface
 */
interface SavedFilterRepositoryInterface extends RepositoryInterface
{
    /**
     * Get user's saved filters
     *
     * @param  int  $userId  User ID
     * @param  string|null  $module  Filter by module
     * @return Collection
     */
    public function getByUser(int $userId, ?string $module = null): Collection;

    /**
     * Get public filters
     *
     * @param  string|null  $module  Filter by module
     * @return Collection
     */
    public function getPublic(?string $module = null): Collection;

    /**
     * Get user's default filter for a module
     *
     * @param  int  $userId  User ID
     * @param  string  $module  Module type
     */
    public function getDefault(int $userId, string $module): ?SavedFilter;

    /**
     * Set filter as default
     *
     * @param  int  $filterId  Filter ID
     * @param  int  $userId  User ID
     */
    public function setAsDefault(int $filterId, int $userId): bool;

    /**
     * Increment filter usage
     *
     * @param  int  $filterId  Filter ID
     */
    public function incrementUsage(int $filterId): bool;

    /**
     * Get most used filters
     *
     * @param  int  $userId  User ID
     * @param  int  $limit  Number of results
     * @return Collection
     */
    public function getMostUsed(int $userId, int $limit = 10): Collection;
}
