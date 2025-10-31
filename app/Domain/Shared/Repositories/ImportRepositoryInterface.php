<?php

declare(strict_types=1);

namespace App\Domain\Shared\Repositories;

use App\Domain\Shared\Contracts\RepositoryInterface;
use App\Domain\Shared\Models\Import;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Import Repository Interface
 */
interface ImportRepositoryInterface extends RepositoryInterface
{
    /**
     * Get imports by user
     */
    public function getByUser(int $userId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get imports by module
     */
    public function getByModule(string $module, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get imports by status
     */
    public function getByStatus(string $status, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get recent imports
     */
    public function getRecent(int $limit = 10): Collection;

    /**
     * Get import with errors
     */
    public function getWithErrors(int $id): ?Import;

    /**
     * Delete old completed imports
     */
    public function deleteOldCompleted(int $daysOld = 30): int;
}
