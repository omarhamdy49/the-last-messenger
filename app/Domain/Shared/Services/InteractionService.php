<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Shared\Enums\InteractionType;
use App\Domain\Shared\Models\UserInteraction;
use App\Domain\Shared\Repositories\UserEngagementMetricRepositoryInterface;
use App\Domain\Shared\Repositories\UserInteractionRepositoryInterface;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Interaction Service
 * Handles user interaction tracking and engagement metrics
 *
 * Deliberately does NOT extend BaseService. It holds two repositories and never assigned
 * `$this->repository`, so every one of the nine inherited methods (all/paginate/find/
 * findOrFail/create/update/delete/count/exists) would have raised "typed property
 * BaseService::$repository must not be accessed before initialization". Nothing called
 * them, so the trap was latent rather than live — but the inheritance bought nothing and
 * could not be satisfied, so it is gone rather than papered over by picking one of the two
 * repositories arbitrarily.
 */
class InteractionService
{
    public function __construct(
        protected UserInteractionRepositoryInterface $interactionRepository,
        protected UserEngagementMetricRepositoryInterface $metricRepository
    ) {}

    /**
     * Record a view interaction
     *
     * @param  array<string, mixed>|null  $metadata
     */
    public function recordView(
        User $user,
        Model $content,
        ?array $metadata = null,
        ?int $durationSeconds = null
    ): UserInteraction {
        return $this->recordInteraction(
            user: $user,
            content: $content,
            type: InteractionType::VIEW,
            metadata: $metadata,
            durationSeconds: $durationSeconds
        );
    }

    /**
     * Record any type of interaction
     *
     * @param  array<string, mixed>|null  $metadata
     */
    public function recordInteraction(
        User $user,
        Model $content,
        InteractionType|string $type,
        ?array $metadata = null,
        ?int $durationSeconds = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?string $deviceType = null,
        ?string $platform = null
    ): UserInteraction {
        if (is_string($type)) {
            $type = InteractionType::from($type);
        }

        // Prepare interaction data
        $data = [
            'user_id' => $user->id,
            'interactable_type' => get_class($content),
            'interactable_id' => $content->id,
            'interaction_type' => $type->value,
            'metadata' => $metadata,
            'duration_seconds' => $durationSeconds,
            'ip_address' => $ipAddress ?? request()->ip(),
            'user_agent' => $userAgent ?? request()->userAgent(),
            'device_type' => $deviceType ?? $this->detectDeviceType(),
            'platform' => $platform ?? $this->detectPlatform(),
        ];

        // Create interaction
        $interaction = $this->interactionRepository->create($data);

        // Update engagement metrics asynchronously (fire and forget)
        dispatch(function () use ($user, $type, $durationSeconds) {
            $this->updateEngagementMetrics($user, $type, $durationSeconds);
        })->afterResponse();

        return $interaction;
    }

    /**
     * Update user engagement metrics after interaction
     */
    public function updateEngagementMetrics(
        User $user,
        InteractionType $type,
        ?int $durationSeconds = null
    ): void {
        $metric = $this->metricRepository->getOrCreateForUser($user->id);

        // Update counter based on interaction type
        $counterMap = [
            InteractionType::VIEW->value => 'total_views',
            InteractionType::LIKE->value => 'total_likes',
            InteractionType::SHARE->value => 'total_shares',
            InteractionType::BOOKMARK->value => 'total_bookmarks',
            InteractionType::COMMENT->value => 'total_comments',
            InteractionType::DOWNLOAD->value => 'total_downloads',
            InteractionType::SEARCH->value => 'total_searches',
        ];

        if (isset($counterMap[$type->value])) {
            $this->metricRepository->incrementCounter($user->id, $counterMap[$type->value]);
        }

        // Update engagement score
        if ($type->countsTowardsEngagement()) {
            $this->metricRepository->updateEngagementScore($user->id, $type->getPoints());
        }

        // Update monthly metrics
        if ($metric->needsMonthlyReset()) {
            $this->metricRepository->resetMonthlyMetrics($user->id);
        }

        // monthly_views counts views only — a like or a share must not bump it.
        if ($type === InteractionType::VIEW) {
            $this->metricRepository->incrementCounter($user->id, 'monthly_views');
        }

        $this->metricRepository->incrementCounter($user->id, 'monthly_interactions');

        // Update streak
        $this->metricRepository->updateStreak($user->id);

        // Update time spent if provided
        if ($durationSeconds) {
            $metric->refresh();
            $totalTime = $metric->total_time_spent_seconds + $durationSeconds;
            $totalSessions = $this->interactionRepository->countByUser($user->id);
            $avgDuration = $totalSessions > 0 ? (int) ($totalTime / $totalSessions) : 0;

            DB::table('user_engagement_metrics')
                ->where('user_id', $user->id)
                ->update([
                    'total_time_spent_seconds' => $totalTime,
                    'avg_session_duration_seconds' => $avgDuration,
                ]);
        }
    }

    /**
     * Get engagement score for user
     */
    public function getEngagementScore(User $user): int
    {
        return Cache::remember(
            "engagement:score:user:{$user->id}",
            1800,
            fn () => $this->metricRepository->getOrCreateForUser($user->id)->engagement_score
        );
    }

    /**
     * Get content engagement statistics
     *
     * @return array<string, mixed>
     */
    public function getContentEngagement(Model $content): array
    {
        $contentType = get_class($content);
        $contentId = $content->id;

        return Cache::remember(
            "engagement:content:{$contentType}:{$contentId}",
            1800,
            function () use ($contentType, $contentId) {
                // Aggregated in SQL: the previous version hydrated (and cached)
                // every interaction row for the item just to derive ~8 counts.
                $aggregate = $this->interactionRepository->aggregateByContent($contentType, $contentId);
                $byType = $aggregate['by_type'];

                $stats = [
                    'total_interactions' => array_sum($byType),
                    'unique_users' => $aggregate['unique_users'],
                    'views' => $byType[InteractionType::VIEW->value] ?? 0,
                    'likes' => $byType[InteractionType::LIKE->value] ?? 0,
                    'shares' => $byType[InteractionType::SHARE->value] ?? 0,
                    'bookmarks' => $byType[InteractionType::BOOKMARK->value] ?? 0,
                    'comments' => $byType[InteractionType::COMMENT->value] ?? 0,
                    'downloads' => $byType[InteractionType::DOWNLOAD->value] ?? 0,
                    'avg_time_spent' => $aggregate['avg_duration'],
                ];

                // Calculate engagement score
                $score = 0;
                foreach (InteractionType::cases() as $type) {
                    $score += ($byType[$type->value] ?? 0) * $type->getPoints();
                }
                $stats['engagement_score'] = $score;

                return $stats;
            }
        );
    }

    /**
     * Get user engagement trends over time
     *
     * @return array<array<string, mixed>>
     */
    public function getEngagementTrends(User $user, int $days = 30): array
    {
        $startDate = now()->subDays($days)->startOfDay();
        $endDate = now()->endOfDay();

        // One grouped query scoped to this user, instead of loading every
        // user's interactions in the window (eager-loaded) and re-filtering
        // the whole collection once per day.
        $counts = $this->interactionRepository->dailyCountsByUser($user->id, $startDate, $endDate);

        $trendData = [];
        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            $dateStr = $date->toDateString();
            $day = $counts[$dateStr] ?? [];

            $trendData[] = [
                'date' => $dateStr,
                'total' => array_sum($day),
                'views' => $day[InteractionType::VIEW->value] ?? 0,
                'likes' => $day[InteractionType::LIKE->value] ?? 0,
                'shares' => $day[InteractionType::SHARE->value] ?? 0,
                'bookmarks' => $day[InteractionType::BOOKMARK->value] ?? 0,
            ];
        }

        return $trendData;
    }

    /**
     * Get most engaged users (leaderboard)
     */
    public function getMostEngagedUsers(int $limit = 10): Collection
    {
        return Cache::remember(
            "engagement:leaderboard:{$limit}",
            3600,
            fn () => $this->metricRepository->getTopEngaged($limit)
        );
    }

    /**
     * Get most engaged content across platform
     *
     * @return array<array<string, mixed>>
     */
    public function getMostEngagedContent(int $limit = 10): array
    {
        return Cache::remember(
            "engagement:content:top:{$limit}",
            3600,
            fn () => $this->interactionRepository->getMostInteractedContent($limit)
        );
    }

    /**
     * Get user's recent activity
     */
    public function getUserActivity(User $user, int $limit = 50): Collection
    {
        return $this->interactionRepository->getByUser($user->id, $limit);
    }

    /**
     * Get user engagement history (detailed stats)
     *
     * @return array<string, mixed>
     */
    public function getUserEngagementHistory(User $user): array
    {
        $metric = $this->metricRepository->getOrCreateForUser($user->id);

        return [
            'user_id' => $user->id,
            'engagement_score' => $metric->engagement_score,
            'engagement_level' => $metric->engagement_level,
            'streak_days' => $metric->streak_days,
            'streak_active' => $metric->hasValidStreak(),
            'last_activity' => $metric->last_activity_at?->toDateTimeString(),
            'total_interactions' => $metric->total_interactions,
            'breakdown' => [
                'views' => $metric->total_views,
                'likes' => $metric->total_likes,
                'shares' => $metric->total_shares,
                'bookmarks' => $metric->total_bookmarks,
                'comments' => $metric->total_comments,
                'downloads' => $metric->total_downloads,
                'searches' => $metric->total_searches,
            ],
            'time_metrics' => [
                'total_time_spent' => $metric->total_time_spent_seconds,
                'avg_session_duration' => $metric->avg_session_duration_seconds,
                'total_time_formatted' => $this->formatSeconds($metric->total_time_spent_seconds),
                'avg_session_formatted' => $this->formatSeconds($metric->avg_session_duration_seconds),
            ],
            'monthly' => [
                'views' => $metric->monthly_views,
                'interactions' => $metric->monthly_interactions,
            ],
        ];
    }

    /**
     * Get platform-wide engagement statistics
     *
     * @return array<string, mixed>
     */
    public function getPlatformEngagementStats(): array
    {
        return Cache::remember('engagement:platform:stats', 3600, function () {
            $stats = $this->metricRepository->getEngagementStatistics();

            // Add interaction type breakdown
            $interactionStats = [];
            foreach (InteractionType::cases() as $type) {
                $interactionStats[$type->value] = $this->interactionRepository->countByType($type);
            }
            $stats['interaction_breakdown'] = $interactionStats;

            // COUNT(*) rather than get()->count(): the getToday/getThisWeek/
            // getThisMonth collections eager-load user + interactable for every
            // row and were only ever counted.
            $stats['today'] = $this->interactionRepository->countBetweenDates(
                now()->startOfDay(),
                now()->endOfDay()
            );
            $stats['this_week'] = $this->interactionRepository->countBetweenDates(
                now()->startOfWeek(),
                now()->endOfWeek()
            );
            $stats['this_month'] = $this->interactionRepository->countBetweenDates(
                now()->startOfMonth(),
                now()->endOfMonth()
            );

            return $stats;
        });
    }

    /**
     * Export user engagement data
     *
     * @return array<string, mixed>
     */
    public function exportUserEngagementData(User $user): array
    {
        $interactions = $this->interactionRepository->getByUser($user->id);
        $history = $this->getUserEngagementHistory($user);

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'summary' => $history,
            'interactions' => $interactions->map(function ($interaction) {
                return [
                    'type' => $interaction->interaction_type->value,
                    'content_type' => class_basename($interaction->interactable_type),
                    'content_id' => $interaction->interactable_id,
                    'created_at' => $interaction->created_at->toDateTimeString(),
                    'duration' => $interaction->duration_seconds,
                    'device' => $interaction->device_type,
                    'platform' => $interaction->platform,
                ];
            })->toArray(),
        ];
    }

    /**
     * Detect device type from user agent
     */
    protected function detectDeviceType(): ?string
    {
        $userAgent = request()->userAgent();
        if (! $userAgent) {
            return null;
        }

        if (preg_match('/mobile|android|iphone|ipod|blackberry|iemobile|opera mini/i', $userAgent)) {
            return 'mobile';
        }

        if (preg_match('/tablet|ipad|playbook|silk/i', $userAgent)) {
            return 'tablet';
        }

        return 'desktop';
    }

    /**
     * Detect platform from user agent
     */
    protected function detectPlatform(): ?string
    {
        $userAgent = request()->userAgent();
        if (! $userAgent) {
            return null;
        }

        if (preg_match('/iphone|ipad|ipod/i', $userAgent)) {
            return 'ios';
        }

        if (preg_match('/android/i', $userAgent)) {
            return 'android';
        }

        if (preg_match('/windows/i', $userAgent)) {
            return 'windows';
        }

        if (preg_match('/macintosh|mac os x/i', $userAgent)) {
            return 'macos';
        }

        if (preg_match('/linux/i', $userAgent)) {
            return 'linux';
        }

        return 'web';
    }

    /**
     * Format seconds into human-readable time
     */
    /**
     * Nullable: a user with no recorded sessions has NULL aggregates, not 0.
     * Previously this fataled with a TypeError on the first such user.
     */
    protected function formatSeconds(?int $seconds): string
    {
        $seconds ??= 0;

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
