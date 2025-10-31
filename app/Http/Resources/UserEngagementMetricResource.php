<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * User Engagement Metric Resource
 * Transforms UserEngagementMetric model for API responses
 */
class UserEngagementMetricResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
            'engagement_score' => $this->engagement_score,
            'engagement_level' => $this->engagement_level,
            'total_interactions' => $this->total_interactions,
            'breakdown' => [
                'views' => $this->total_views,
                'likes' => $this->total_likes,
                'shares' => $this->total_shares,
                'bookmarks' => $this->total_bookmarks,
                'comments' => $this->total_comments,
                'downloads' => $this->total_downloads,
                'searches' => $this->total_searches,
            ],
            'streak' => [
                'days' => $this->streak_days,
                'active' => $this->hasValidStreak(),
                'started_at' => $this->streak_started_at?->toDateTimeString(),
            ],
            'activity' => [
                'last_activity_at' => $this->last_activity_at?->toDateTimeString(),
                'last_activity_human' => $this->last_activity_at?->diffForHumans(),
                'is_active_today' => $this->isActiveToday(),
            ],
            'time_metrics' => [
                'total_time_spent_seconds' => $this->total_time_spent_seconds,
                'avg_session_duration_seconds' => $this->avg_session_duration_seconds,
                'total_time_formatted' => $this->formatDuration($this->total_time_spent_seconds),
                'avg_session_formatted' => $this->formatDuration($this->avg_session_duration_seconds),
            ],
            'monthly' => [
                'views' => $this->monthly_views,
                'interactions' => $this->monthly_interactions,
                'month' => $this->metrics_month?->format('Y-m'),
            ],
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }

    /**
     * Format duration in seconds to human-readable format
     */
    protected function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds}s";
        }

        $minutes = floor($seconds / 60);
        if ($minutes < 60) {
            $remainingSeconds = $seconds % 60;

            return "{$minutes}m {$remainingSeconds}s";
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        return "{$hours}h {$remainingMinutes}m";
    }
}
