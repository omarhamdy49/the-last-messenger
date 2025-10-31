<?php

declare(strict_types=1);

namespace App\Domain\Shared\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * User Engagement Metric Model
 * Stores aggregated engagement metrics for each user
 *
 * @property int $id
 * @property int $user_id
 * @property int $total_views
 * @property int $total_likes
 * @property int $total_shares
 * @property int $total_bookmarks
 * @property int $total_comments
 * @property int $total_downloads
 * @property int $total_searches
 * @property int $engagement_score
 * @property int $streak_days
 * @property \Carbon\Carbon|null $last_activity_at
 * @property \Carbon\Carbon|null $streak_started_at
 * @property int $total_time_spent_seconds
 * @property int $avg_session_duration_seconds
 * @property int $monthly_views
 * @property int $monthly_interactions
 * @property \Carbon\Carbon|null $metrics_month
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class UserEngagementMetric extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'total_views',
        'total_likes',
        'total_shares',
        'total_bookmarks',
        'total_comments',
        'total_downloads',
        'total_searches',
        'engagement_score',
        'streak_days',
        'last_activity_at',
        'streak_started_at',
        'total_time_spent_seconds',
        'avg_session_duration_seconds',
        'monthly_views',
        'monthly_interactions',
        'metrics_month',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_views' => 'integer',
            'total_likes' => 'integer',
            'total_shares' => 'integer',
            'total_bookmarks' => 'integer',
            'total_comments' => 'integer',
            'total_downloads' => 'integer',
            'total_searches' => 'integer',
            'engagement_score' => 'integer',
            'streak_days' => 'integer',
            'last_activity_at' => 'datetime',
            'streak_started_at' => 'datetime',
            'total_time_spent_seconds' => 'integer',
            'avg_session_duration_seconds' => 'integer',
            'monthly_views' => 'integer',
            'monthly_interactions' => 'integer',
            'metrics_month' => 'date',
        ];
    }

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Top engaged users
     */
    public function scopeTopEngaged(Builder $query, int $limit = 10): Builder
    {
        return $query->orderByDesc('engagement_score')->limit($limit);
    }

    /**
     * Scope: Active users (interacted in last 7 days)
     */
    public function scopeActive(Builder $query, int $days = 7): Builder
    {
        return $query->where('last_activity_at', '>=', now()->subDays($days));
    }

    /**
     * Scope: Users with active streaks
     */
    public function scopeWithStreak(Builder $query): Builder
    {
        return $query->where('streak_days', '>', 0);
    }

    /**
     * Scope: Top streak holders
     */
    public function scopeTopStreaks(Builder $query, int $limit = 10): Builder
    {
        return $query->orderByDesc('streak_days')->limit($limit);
    }

    /**
     * Get total interactions count
     */
    public function getTotalInteractionsAttribute(): int
    {
        return $this->total_views
            + $this->total_likes
            + $this->total_shares
            + $this->total_bookmarks
            + $this->total_comments
            + $this->total_downloads
            + $this->total_searches;
    }

    /**
     * Get engagement level (beginner, intermediate, advanced, expert)
     */
    public function getEngagementLevelAttribute(): string
    {
        return match (true) {
            $this->engagement_score >= 10000 => 'expert',
            $this->engagement_score >= 5000 => 'advanced',
            $this->engagement_score >= 1000 => 'intermediate',
            default => 'beginner',
        };
    }

    /**
     * Check if user is active today
     */
    public function isActiveToday(): bool
    {
        return $this->last_activity_at?->isToday() ?? false;
    }

    /**
     * Check if streak is still valid
     */
    public function hasValidStreak(): bool
    {
        if (! $this->last_activity_at) {
            return false;
        }

        // Streak is valid if last activity was today or yesterday
        return $this->last_activity_at->isToday()
            || $this->last_activity_at->isYesterday();
    }

    /**
     * Reset monthly metrics
     */
    public function resetMonthlyMetrics(): void
    {
        $this->update([
            'monthly_views' => 0,
            'monthly_interactions' => 0,
            'metrics_month' => now(),
        ]);
    }

    /**
     * Check if monthly metrics need reset
     */
    public function needsMonthlyReset(): bool
    {
        if (! $this->metrics_month) {
            return true;
        }

        return $this->metrics_month->month !== now()->month
            || $this->metrics_month->year !== now()->year;
    }
}
