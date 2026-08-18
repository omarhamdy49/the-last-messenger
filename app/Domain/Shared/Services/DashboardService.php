<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Shared\Contracts\DashboardStatsProviderInterface;
use App\Domain\Shared\Models\AdminLog;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard Service
 *
 * Provides core statistics, module health, recent activity, and quick analytics
 * for the admin dashboard. Chart/growth/analytics data lives in DashboardAnalyticsService.
 *
 * Domain model access is fully delegated to injected DashboardStatsProviderInterface
 * implementations, keeping this shared service free from cross-module imports.
 *
 * @see DashboardAnalyticsService for chart data, heatmap, growth, and performance metrics
 */
class DashboardService
{
    private const CACHE_TTL = 300; // 5 minutes

    private const CACHE_PREFIX = 'dashboard';

    /**
     * @param  DashboardStatsProviderInterface[]  $statsProviders
     */
    public function __construct(
        private readonly array $statsProviders = [],
    ) {}

    /**
     * Get dashboard statistics
     *
     * @return array<string, mixed>
     */
    public function getStatistics(): array
    {
        return Cache::remember(
            self::CACHE_PREFIX.'.statistics',
            self::CACHE_TTL,
            function (): array {
                $stats = [];

                foreach ($this->statsProviders as $provider) {
                    $stats[$provider->getModuleKey()] = $provider->getStats();
                }

                $stats['users'] = [
                    'total' => User::count(),
                    'verified' => User::whereNotNull('email_verified_at')->count(),
                    'unverified' => User::whereNull('email_verified_at')->count(),
                ];

                return $stats;
            }
        );
    }

    /**
     * Get enhanced statistics with sparklines and percentage changes.
     *
     * @return array<string, mixed>
     */
    public function getEnhancedStatistics(): array
    {
        return Cache::remember(
            self::CACHE_PREFIX.'.enhanced_statistics',
            self::CACHE_TTL,
            function (): array {
                try {
                    $usersTotal = User::count();
                    $usersNew = User::where('created_at', '>=', now()->subMonth())->count();
                    $usersLastWeek = User::where('created_at', '>=', now()->subWeek())->count();
                    $usersPreviousWeek = User::whereBetween('created_at', [now()->subWeeks(2), now()->subWeek()])->count();

                    $moduleStats = [];
                    foreach ($this->statsProviders as $provider) {
                        $moduleStats[$provider->getModuleKey()] = $provider->getStats();
                    }

                    return array_merge($moduleStats, [
                        'users' => [
                            'total' => $usersTotal,
                            'new' => $usersNew,
                            'change' => $this->calculatePercentageChange($usersLastWeek, $usersPreviousWeek),
                            'sparkline' => $this->getSparklineDataForTable('users', 7),
                        ],
                    ]);
                } catch (\Throwable $e) {
                    // Fallback kept, but the failure is now traceable: without report() a real
                    // bug (broken query, dropped column) looked identical to 'no data'.
                    report($e);

                    return [
                        'users' => ['total' => 0, 'new' => 0, 'change' => 0, 'sparkline' => []],
                    ];
                }
            }
        );
    }

    /**
     * Get recent activity from admin logs
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getRecentActivity(int $limit = 10): Collection
    {
        return Cache::remember(
            self::CACHE_PREFIX.".recent_activity.{$limit}",
            self::CACHE_TTL,
            function () use ($limit): Collection {
                try {
                    return AdminLog::with('user:id,name,avatar')
                        ->select(['id', 'user_id', 'action', 'model_type', 'model_id', 'new_values', 'created_at'])
                        ->orderBy('created_at', 'desc')
                        ->limit($limit)
                        ->get()
                        ->map(fn ($log) => [
                            'id' => $log->id,
                            'user' => [
                                'name' => $log->user?->name ?? 'Unknown User',
                                'avatar' => $log->user?->avatar ?? null,
                            ],
                            'action' => $log->action,
                            'model' => class_basename($log->model_type ?? 'Unknown'),
                            'description' => $this->formatActionDescription($log),
                            'timestamp' => $log->created_at->diffForHumans(),
                            'icon' => $this->getActionIcon($log->action),
                            'color' => $this->getActionColor($log->action),
                        ]);
                } catch (\Throwable $e) {
                    // Fallback kept, but the failure is now traceable: without report() a real
                    // bug (broken query, dropped column) looked identical to 'no data'.
                    report($e);

                    return collect();
                }
            }
        );
    }

    /**
     * Get quick analytics insights based on admin log data.
     *
     * @return array<string, mixed>
     */
    public function getQuickAnalytics(): array
    {
        return Cache::remember(
            self::CACHE_PREFIX.'.quick_analytics',
            self::CACHE_TTL,
            function (): array {
                try {
                    $mostActiveDay = AdminLog::select(
                        DB::raw('DATE(created_at) as date'),
                        DB::raw('COUNT(*) as count')
                    )
                        ->groupBy('date')
                        ->orderByDesc('count')
                        ->first();

                    return [
                        'most_active_day' => $mostActiveDay ? [
                            'date' => $mostActiveDay->date,
                            'count' => $mostActiveDay->count,
                        ] : null,
                        'top_article' => null,
                        'popular_category' => null,
                        'top_narrator' => null,
                        'latest_upload' => null,
                        'pending_reviews' => 0,
                    ];
                } catch (\Throwable $e) {
                    // Fallback kept, but the failure is now traceable: without report() a real
                    // bug (broken query, dropped column) looked identical to 'no data'.
                    report($e);

                    return [
                        'top_article' => null,
                        'most_active_day' => null,
                        'popular_category' => null,
                        'top_narrator' => null,
                        'latest_upload' => null,
                        'pending_reviews' => 0,
                    ];
                }
            }
        );
    }

    /**
     * Get module health status
     *
     * @return array<int, array<string, mixed>>
     */
    public function getModuleHealth(): array
    {
        return Cache::remember(
            self::CACHE_PREFIX.'.module_health',
            self::CACHE_TTL,
            function (): array {
                return collect($this->statsProviders)
                    ->map(function (DashboardStatsProviderInterface $provider): array {
                        $stats = $provider->getStats();
                        $count = $stats['total'] ?? 0;
                        $status = 'healthy';

                        if ($count < 50) {
                            $status = 'critical';
                        } elseif ($count < 100) {
                            $status = 'warning';
                        }

                        return [
                            'name' => ucfirst(str_replace('_', ' ', $provider->getModuleKey())),
                            'status' => $status,
                            'count' => $count,
                            'last_activity' => null,
                            'icon' => 'book-open',
                        ];
                    })
                    ->toArray();
            }
        );
    }

    /**
     * Get recent content from each module.
     * Domain-specific queries belong in module services; this preserves the API contract.
     *
     * @return array<string, Collection>
     */
    public function getRecentContent(): array
    {
        return Cache::remember(
            self::CACHE_PREFIX.'.recent_content',
            self::CACHE_TTL,
            fn (): array => []
        );
    }

    /**
     * Clear core dashboard cache keys
     */
    public function clearCache(): void
    {
        $keys = [
            self::CACHE_PREFIX.'.statistics',
            self::CACHE_PREFIX.'.enhanced_statistics',
            self::CACHE_PREFIX.'.recent_content',
            self::CACHE_PREFIX.'.quick_analytics',
            self::CACHE_PREFIX.'.module_health',
        ];

        for ($i = 5; $i <= 50; $i += 5) {
            $keys[] = self::CACHE_PREFIX.".recent_activity.{$i}";
        }

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    // ============================================================================
    // PRIVATE HELPERS
    // ============================================================================

    /**
     * Get sparkline data for last N days using a raw table name.
     *
     * Uses a single aggregated query with a range predicate on created_at so
     * the database can satisfy the scan with the existing index instead of
     * applying DATE() to every row (which bypasses the index entirely).
     * Missing days are back-filled with 0 to preserve the fixed-length array
     * format expected by callers.
     *
     * Before: N queries, each a full-table scan via whereDate()/DATE() wrap.
     * After:  1 query with index-range seek + GROUP BY on the DATE expression.
     *
     * @return array<int, int>
     */
    private function getSparklineDataForTable(string $table, int $days = 7): array
    {
        try {
            $start = now()->subDays($days - 1)->startOfDay();
            $end = now()->endOfDay();

            // Single query: range predicate keeps the index seek, GROUP BY
            // aggregates per calendar day. keyBy('day') gives O(1) lookups.
            $results = DB::table($table)
                ->selectRaw('DATE(created_at) as day, COUNT(*) as count')
                ->where('created_at', '>=', $start)
                ->where('created_at', '<=', $end)
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('day')
                ->get()
                ->keyBy('day');

            // Build the ordered array, filling gaps (days with no rows) with 0.
            $data = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $dateKey = now()->subDays($i)->toDateString();
                $data[] = (int) ($results[$dateKey]->count ?? 0);
            }

            return $data;
        } catch (\Throwable $e) {
            // Fallback kept, but the failure is now traceable: without report() a real
            // bug (broken query, dropped column) looked identical to 'no data'.
            report($e);

            return array_fill(0, $days, 0);
        }
    }

    /**
     * Calculate percentage change between two values
     */
    private function calculatePercentageChange(int $current, int $previous): float
    {
        if ($previous === 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * Format action description for admin log
     */
    private function formatActionDescription(AdminLog $log): string
    {
        $modelName = class_basename($log->model_type ?? 'Unknown');
        $userName = $log->user?->name ?? 'Unknown User';

        $modelTitle = null;

        if ($log->new_values) {
            if (isset($log->new_values['title']['ar'])) {
                $modelTitle = $log->new_values['title']['ar'];
            } elseif (isset($log->new_values['title']['en'])) {
                $modelTitle = $log->new_values['title']['en'];
            } elseif (isset($log->new_values['name']['ar'])) {
                $modelTitle = $log->new_values['name']['ar'];
            } elseif (isset($log->new_values['name']['en'])) {
                $modelTitle = $log->new_values['name']['en'];
            } elseif (isset($log->new_values['name'])) {
                $modelTitle = $log->new_values['name'];
            }
        }

        $modelTitle = $modelTitle ?? "#{$log->model_id}";

        return match ($log->action) {
            'created' => "{$userName} أنشأ {$modelName} '{$modelTitle}'",
            'updated' => "{$userName} حدّث {$modelName} '{$modelTitle}'",
            'deleted' => "{$userName} حذف {$modelName} '{$modelTitle}'",
            'login' => "{$userName} سجل الدخول",
            'logout' => "{$userName} سجل الخروج",
            default => "{$userName} {$log->action} {$modelName}"
        };
    }

    /**
     * Get icon for action type
     */
    private function getActionIcon(string $action): string
    {
        return match ($action) {
            'created' => 'plus-circle',
            'updated' => 'edit',
            'deleted' => 'trash-2',
            'login' => 'log-in',
            'logout' => 'log-out',
            default => 'activity'
        };
    }

    /**
     * Get color for action type
     */
    private function getActionColor(string $action): string
    {
        return match ($action) {
            'created' => 'green',
            'updated' => 'blue',
            'deleted' => 'red',
            'login' => 'yellow',
            'logout' => 'gray',
            default => 'gray'
        };
    }
}
