<?php

declare(strict_types=1);

namespace App\Domain\Shared\Repositories;

use App\Domain\Shared\Models\AdminLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface AdminLogRepositoryInterface
{
    /**
     * Create a new admin log entry.
     *
     * @param  array<string, mixed>  $data
     */
    public function log(array $data): AdminLog;

    /**
     * Get recent activity logs.
     */
    public function getRecentActivity(int $limit = 20): Collection;

    /**
     * Get activity logs for a specific user.
     */
    public function getUserActivity(int $userId, int $limit = 50): Collection;

    /**
     * Get history for a specific model.
     */
    public function getModelHistory(string $modelType, int $modelId): Collection;

    /**
     * Search logs with filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function search(array $filters): LengthAwarePaginator;

    /**
     * Get all logs with pagination.
     */
    public function getAll(int $perPage = 50): LengthAwarePaginator;

    /**
     * Find a log by ID.
     */
    public function findById(int $id): ?AdminLog;
}
