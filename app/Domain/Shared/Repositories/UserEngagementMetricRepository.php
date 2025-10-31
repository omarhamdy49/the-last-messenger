<?php

declare(strict_types=1);

namespace App\Domain\Shared\Repositories;

use App\Domain\Shared\Models\UserEngagementMetric;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * User Engagement Metric Repository Implementation
 */
class UserEngagementMetricRepository extends BaseRepository implements UserEngagementMetricRepositoryInterface
{
    protected int $cacheTTL = 1800; // 30 minutes

    /**
     * {@inheritdoc}
     */
    protected function getModel(): Model
    {
        return new UserEngagementMetric();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrCreateForUser(int $userId): UserEngagementMetric
    {
        return $this->model->firstOrCreate(
            ['user_id' => $userId],
            [
                'total_views' => 0,
                'total_likes' => 0,
                'total_shares' => 0,
                'total_bookmarks' => 0,
                'total_comments' => 0,
                'total_downloads' => 0,
                'total_searches' => 0,
                'engagement_score' => 0,
                'streak_days' => 0,
                'last_activity_at' => now(),
                'metrics_month' => now(),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getTopEngaged(int $limit = 10): Collection
    {
        return Cache::remember(
            "engagement:top_engaged:{$limit}",
            3600, // 1 hour cache
            fn () => $this->model
                ->with('user')
                ->orderByDesc('engagement_score')
                ->limit($limit)
                ->get()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getUsersWithStreaks(int $limit = 10): Collection
    {
        return Cache::remember(
            "engagement:top_streaks:{$limit}",
            3600, // 1 hour cache
            fn () => $this->model
                ->with('user')
                ->where('streak_days', '>', 0)
                ->orderByDesc('streak_days')
                ->limit($limit)
                ->get()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getActiveUsers(int $days = 7): Collection
    {
        return $this->model
            ->with('user')
            ->where('last_activity_at', '>=', now()->subDays($days))
            ->orderByDesc('last_activity_at')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function updateEngagementScore(int $userId, int $points): bool
    {
        $metric = $this->getOrCreateForUser($userId);
        $updated = $metric->increment('engagement_score', $points);

        if ($updated) {
            Cache::forget("engagement:user:{$userId}");
            Cache::tags(['engagement'])->flush();
        }

        return $updated > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function updateStreak(int $userId): bool
    {
        $metric = $this->getOrCreateForUser($userId);

        // Check if last activity was yesterday (continuing streak) or today (already counted)
        if ($metric->last_activity_at?->isToday()) {
            // Already counted today, just update last_activity_at
            return $metric->update(['last_activity_at' => now()]);
        }

        if ($metric->last_activity_at?->isYesterday()) {
            // Continuing streak
            $updated = $metric->update([
                'streak_days' => $metric->streak_days + 1,
                'last_activity_at' => now(),
            ]);
        } else {
            // Starting new streak
            $updated = $metric->update([
                'streak_days' => 1,
                'streak_started_at' => now(),
                'last_activity_at' => now(),
            ]);
        }

        if ($updated) {
            Cache::forget("engagement:user:{$userId}");
            Cache::tags(['engagement'])->flush();
        }

        return $updated;
    }

    /**
     * {@inheritdoc}
     */
    public function resetStreak(int $userId): bool
    {
        $metric = $this->getOrCreateForUser($userId);
        $updated = $metric->update([
            'streak_days' => 0,
            'streak_started_at' => null,
        ]);

        if ($updated) {
            Cache::forget("engagement:user:{$userId}");
        }

        return $updated;
    }

    /**
     * {@inheritdoc}
     */
    public function incrementCounter(int $userId, string $counterName, int $amount = 1): bool
    {
        $metric = $this->getOrCreateForUser($userId);
        $updated = $metric->increment($counterName, $amount);

        if ($updated) {
            Cache::forget("engagement:user:{$userId}");
        }

        return $updated > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function resetMonthlyMetrics(int $userId): bool
    {
        $metric = $this->getOrCreateForUser($userId);
        $updated = $metric->update([
            'monthly_views' => 0,
            'monthly_interactions' => 0,
            'metrics_month' => now(),
        ]);

        if ($updated) {
            Cache::forget("engagement:user:{$userId}");
        }

        return $updated;
    }

    /**
     * {@inheritdoc}
     */
    public function resetAllMonthlyMetrics(): int
    {
        $updated = $this->model
            ->where(function ($query) {
                $query->whereNull('metrics_month')
                    ->orWhereMonth('metrics_month', '!=', now()->month)
                    ->orWhereYear('metrics_month', '!=', now()->year);
            })
            ->update([
                'monthly_views' => 0,
                'monthly_interactions' => 0,
                'metrics_month' => now(),
            ]);

        Cache::tags(['engagement'])->flush();

        return $updated;
    }

    /**
     * {@inheritdoc}
     */
    public function getEngagementStatistics(): array
    {
        return Cache::remember('engagement:statistics', 3600, function () {
            return [
                'total_users' => $this->model->count(),
                'active_users_7d' => $this->model->where('last_activity_at', '>=', now()->subDays(7))->count(),
                'active_users_30d' => $this->model->where('last_activity_at', '>=', now()->subDays(30))->count(),
                'users_with_streaks' => $this->model->where('streak_days', '>', 0)->count(),
                'avg_engagement_score' => round($this->model->avg('engagement_score') ?? 0),
                'max_engagement_score' => $this->model->max('engagement_score') ?? 0,
                'avg_streak_days' => round($this->model->where('streak_days', '>', 0)->avg('streak_days') ?? 0),
                'max_streak_days' => $this->model->max('streak_days') ?? 0,
                'total_views' => $this->model->sum('total_views'),
                'total_likes' => $this->model->sum('total_likes'),
                'total_shares' => $this->model->sum('total_shares'),
                'total_bookmarks' => $this->model->sum('total_bookmarks'),
                'total_comments' => $this->model->sum('total_comments'),
                'total_downloads' => $this->model->sum('total_downloads'),
            ];
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getUsersNeedingMonthlyReset(): Collection
    {
        return $this->model
            ->where(function ($query) {
                $query->whereNull('metrics_month')
                    ->orWhereMonth('metrics_month', '!=', now()->month)
                    ->orWhereYear('metrics_month', '!=', now()->year);
            })
            ->get();
    }
}
