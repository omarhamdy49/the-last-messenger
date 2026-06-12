<?php

declare(strict_types=1);

namespace App\Domain\Shared\Repositories;

use App\Domain\Shared\Contracts\RepositoryInterface;
use App\Domain\Shared\Enums\ScheduleAction;
use App\Domain\Shared\Enums\ScheduleStatus;
use App\Domain\Shared\Models\ContentSchedule;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface SchedulerRepositoryInterface extends RepositoryInterface
{
    /**
     * Get all due schedules (ready to execute)
     */
    public function getDueSchedules(): Collection;

    /**
     * Get schedules by status
     */
    public function getByStatus(ScheduleStatus|string $status, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get schedules by action
     */
    public function getByAction(ScheduleAction|string $action, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get schedules for a specific content
     */
    public function getSchedulesForContent(string $type, int $id): Collection;

    /**
     * Get schedule history for content
     */
    public function getHistoryForContent(string $type, int $id, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get upcoming schedules
     */
    public function getUpcoming(int $limit = 10): Collection;

    /**
     * Get recurring schedules
     */
    public function getRecurring(int $perPage = 15): LengthAwarePaginator;

    /**
     * Get failed schedules that can be retried
     */
    public function getRetryable(): Collection;

    /**
     * Check if content has pending schedule
     */
    public function hasPendingSchedule(string $type, int $id, ScheduleAction $action): bool;

    /**
     * Create schedule
     */
    public function createSchedule(array $data): ContentSchedule;

    /**
     * Update schedule
     */
    public function updateSchedule(int $id, array $data): bool;

    /**
     * Cancel schedule
     */
    public function cancelSchedule(int $id): bool;

    /**
     * Mark schedule as processing
     */
    public function markAsProcessing(int $id): bool;

    /**
     * Mark schedule as published
     */
    public function markAsPublished(int $id): bool;

    /**
     * Mark schedule as failed
     */
    public function markAsFailed(int $id, string $errorMessage): bool;

    /**
     * Get statistics for dashboard
     */
    public function getStatistics(): array;

    /**
     * Search schedules with filters
     */
    public function search(array $filters): LengthAwarePaginator;
}
