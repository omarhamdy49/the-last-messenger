<?php

declare(strict_types=1);

namespace App\Domain\Shared\Repositories;

use App\Domain\Shared\Contracts\RepositoryInterface;
use App\Domain\Shared\Models\UserEngagementMetric;
use Illuminate\Database\Eloquent\Collection;

/**
 * User Engagement Metric Repository Interface
 */
interface UserEngagementMetricRepositoryInterface extends RepositoryInterface
{
    /**
     * Get or create metric for user
     */
    public function getOrCreateForUser(int $userId): UserEngagementMetric;

    /**
     * Get top engaged users
     */
    public function getTopEngaged(int $limit = 10): Collection;

    /**
     * Get users with active streaks
     */
    public function getUsersWithStreaks(int $limit = 10): Collection;

    /**
     * Get active users (last N days)
     */
    public function getActiveUsers(int $days = 7): Collection;

    /**
     * Update engagement score for user
     */
    public function updateEngagementScore(int $userId, int $points): bool;

    /**
     * Update streak for user
     */
    public function updateStreak(int $userId): bool;

    /**
     * Reset streak for user
     */
    public function resetStreak(int $userId): bool;

    /**
     * Increment interaction counter
     */
    public function incrementCounter(int $userId, string $counterName, int $amount = 1): bool;

    /**
     * Reset monthly metrics for user
     */
    public function resetMonthlyMetrics(int $userId): bool;

    /**
     * Reset monthly metrics for all users
     */
    public function resetAllMonthlyMetrics(): int;

    /**
     * Get engagement statistics
     *
     * @return array<string, mixed>
     */
    public function getEngagementStatistics(): array;

    /**
     * Get users needing monthly reset
     */
    public function getUsersNeedingMonthlyReset(): Collection;
}
