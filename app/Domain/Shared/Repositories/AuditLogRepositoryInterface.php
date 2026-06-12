<?php

declare(strict_types=1);

namespace App\Domain\Shared\Repositories;

use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Audit Log Repository Interface
 */
interface AuditLogRepositoryInterface extends RepositoryInterface
{
    /**
     * Get audit logs for a specific model.
     */
    public function getLogsForModel(Model $model, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get audit logs for a user.
     */
    public function getLogsForUser(int $userId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get audit logs by action.
     */
    public function getLogsByAction(string $action, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get audit logs in date range.
     */
    public function getLogsInRange(
        $startDate,
        $endDate,
        ?string $action = null,
        int $perPage = 15
    ): LengthAwarePaginator;

    /**
     * Get recent audit logs.
     */
    public function getRecentLogs(int $limit = 50): Collection;

    /**
     * Get activity report for a user.
     */
    public function getUserActivityReport(int $userId, $startDate, $endDate): array;

    /**
     * Get activity timeline.
     */
    public function getActivityTimeline($startDate, $endDate): array;

    /**
     * Get audit statistics.
     */
    public function getAuditStats(): array;

    /**
     * Search audit logs.
     */
    public function searchLogs(array $filters, int $perPage = 15): LengthAwarePaginator;

    /**
     * Delete old audit logs.
     */
    public function pruneOldLogs(int $daysToKeep = 90): int;

    /**
     * Get logs by IP address.
     */
    public function getLogsByIp(string $ipAddress, int $perPage = 15): LengthAwarePaginator;
}
