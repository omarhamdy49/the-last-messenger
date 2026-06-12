<?php

declare(strict_types=1);

namespace App\Domain\Shared\Repositories;

use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Bulk Operation Repository Interface
 */
interface BulkOperationRepositoryInterface extends RepositoryInterface
{
    /**
     * Get operations by user
     */
    public function getByUser(int $userId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get operations by status
     */
    public function getByStatus(string $status, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get operations by module
     */
    public function getByModule(string $module, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get pending operations
     */
    public function getPending(): Collection;

    /**
     * Get processing operations
     */
    public function getProcessing(): Collection;

    /**
     * Get recent operations
     */
    public function getRecent(int $limit = 10): Collection;

    /**
     * Get operations with filters
     *
     * @param  array<string, mixed>  $filters
     */
    public function getWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator;

    /**
     * Clean up old completed operations
     */
    public function cleanupOld(int $daysOld = 30): int;
}
