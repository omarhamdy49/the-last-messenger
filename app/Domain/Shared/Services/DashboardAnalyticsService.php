<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Shared\Contracts\DashboardStatsProviderInterface;
use App\Domain\Shared\Models\AdminLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard Analytics Service
 *
 * Provides chart data, growth metrics, heatmap, and performance analytics
 * for the admin dashboard. Extracted from DashboardService to keep each
 * class under 300 lines and focused on a single responsibility.
 *
 * @see DashboardService for core stats, health, and activity feeds
 */
class DashboardAnalyticsService
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
     * Get content growth data for charts (last 12 months).
     *
     * @return array<string, mixed>
     */
    public function getContentGrowth(): array
    {
        return Cache::remember(
            self::CACHE_PREFIX.'.content_growth',
            self::CACHE_TTL,
            function (): array {
                $startDate = now()->subMonths(11)->startOfMonth();

                return [
                    'users' => $this->getMonthlyGrowthForTable('users', $startDate),
                ];
            }
        );
    }

    /**
     * Get content growth chart data (daily granularity)
     *
     * @return array<string, mixed>
     */
    public function getContentGrowthChart(int $days = 30): array
    {
        return Cache::remember(
            self::CACHE_PREFIX.".content_growth_chart.{$days}",
            self::CACHE_TTL,
            function () use ($days): array {
                $labels = [];
                for ($i = $days - 1; $i >= 0; $i--) {
                    $labels[] = now()->subDays($i)->toDateString();
                }

                $usersData = $this->getDailyCountsForTable('users', $days);

                return [
                    'labels' => $labels,
                    'datasets' => [
                        [
                            'label' => 'Users',
                            'data' => $usersData,
                            'borderColor' => '#0056b3',
                            'backgroundColor' => 'rgba(0, 86, 179, 0.1)',
                        ],
                    ],
                ];
            }
        );
    }

    /**
     * Get module distribution for pie chart
     *
     * @return array<string, mixed>
     */
    public function getModuleDistribution(): array
    {
        return Cache::remember(
            self::CACHE_PREFIX.'.module_distribution',
            self::CACHE_TTL,
            function (): array {
                $labels = [];
                $data = [];
                $colors = ['#0056b3', '#218838', '#3B82F6', '#F59E0B', '#10B981', '#8B5CF6'];

                foreach ($this->statsProviders as $provider) {
                    $stats = $provider->getStats();
                    $labels[] = ucfirst(str_replace('_', ' ', $provider->getModuleKey()));
                    $data[] = $stats['total'] ?? 0;
                }

                return [
                    'labels' => $labels,
                    'data' => $data,
                    'colors' => array_slice($colors, 0, count($labels)),
                ];
            }
        );
    }

    /**
     * Get activity heatmap data
     *
     * @return array<int, array<string, mixed>>
     */
    public function getActivityHeatmap(int $days = 30): array
    {
        return Cache::remember(
            self::CACHE_PREFIX.".activity_heatmap.{$days}",
            self::CACHE_TTL,
            function () use ($days): array {
                $counts = $this->getDailyCountsForTable((new AdminLog)->getTable(), $days);

                $heatmap = [];
                for ($i = $days - 1; $i >= 0; $i--) {
                    $heatmap[] = [
                        'date' => now()->subDays($i)->toDateString(),
                        'count' => $counts[$days - 1 - $i],
                    ];
                }

                return $heatmap;
            }
        );
    }

    /**
     * Get performance metrics
     *
     * @return array<string, mixed>
     */
    public function getPerformanceMetrics(): array
    {
        return Cache::remember(
            self::CACHE_PREFIX.'.performance_metrics',
            self::CACHE_TTL,
            function (): array {
                try {
                    $databaseName = DB::getDatabaseName();
                    $dbSize = DB::select('
                        SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size
                        FROM information_schema.TABLES
                        WHERE table_schema = ?
                    ', [$databaseName]);

                    $activeSessions = DB::table('personal_access_tokens')
                        ->where('last_used_at', '>=', now()->subMinutes(30))
                        ->count();

                    // api_response_time and cache_hit_rate have no source in
                    // this stack (no APM, and the database cache store exposes
                    // no hit counters). They stay null so the UI renders
                    // "unavailable" instead of a made-up number.
                    return [
                        'api_response_time' => null,
                        'database_size' => $dbSize[0]->size ?? 0,
                        'cache_hit_rate' => null,
                        'active_sessions' => $activeSessions,
                        'unavailable_metrics' => ['api_response_time', 'cache_hit_rate'],
                    ];
                } catch (\Throwable $e) {
                    report($e);

                    return [
                        'api_response_time' => null,
                        'database_size' => null,
                        'cache_hit_rate' => null,
                        'active_sessions' => null,
                        'unavailable_metrics' => [
                            'api_response_time',
                            'database_size',
                            'cache_hit_rate',
                            'active_sessions',
                        ],
                    ];
                }
            }
        );
    }

    /**
     * Get popular content placeholder.
     * Module-specific popular content queries belong in module services.
     *
     * @return array<string, mixed>
     */
    public function getPopularContent(int $limit = 10): array
    {
        return Cache::remember(
            self::CACHE_PREFIX.".popular_content.{$limit}",
            self::CACHE_TTL,
            fn (): array => []
        );
    }

    /**
     * Clear analytics-related cache keys
     */
    public function clearCache(): void
    {
        $keys = [
            self::CACHE_PREFIX.'.content_growth',
            self::CACHE_PREFIX.'.module_distribution',
            self::CACHE_PREFIX.'.performance_metrics',
        ];

        for ($i = 5; $i <= 50; $i += 5) {
            $keys[] = self::CACHE_PREFIX.".content_growth_chart.{$i}";
            $keys[] = self::CACHE_PREFIX.".activity_heatmap.{$i}";
            $keys[] = self::CACHE_PREFIX.".popular_content.{$i}";
        }

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    // ============================================================================
    // PRIVATE HELPERS
    // ============================================================================

    /**
     * Get per-day row counts for the last N days, oldest first.
     *
     * Mirrors DashboardService::getSparklineDataForTable: one aggregated query
     * with a range predicate on created_at (so the index is usable) instead of
     * N whereDate() counts that wrap created_at in DATE() and force a scan.
     * Days with no rows are back-filled with 0 to keep the fixed-length array.
     *
     * Before: N queries (30-365), each a full scan. After: 1 index-range query.
     *
     * @return array<int, int>
     */
    private function getDailyCountsForTable(string $table, int $days): array
    {
        try {
            $results = DB::table($table)
                ->selectRaw('DATE(created_at) as day, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDays($days - 1)->startOfDay())
                ->where('created_at', '<=', now()->endOfDay())
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('day')
                ->get()
                ->keyBy('day');

            $data = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $data[] = (int) ($results[now()->subDays($i)->toDateString()]->count ?? 0);
            }

            return $data;
        } catch (\Throwable $e) {
            report($e);

            return array_fill(0, $days, 0);
        }
    }

    /**
     * Get monthly growth for a database table by table name
     *
     * @param  \Carbon\Carbon  $startDate
     * @return array<int, array<string, mixed>>
     */
    private function getMonthlyGrowthForTable(string $table, $startDate): array
    {
        try {
            return DB::table($table)
                ->select(
                    DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                    DB::raw('COUNT(*) as count')
                )
                ->where('created_at', '>=', $startDate)
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->map(fn ($row) => ['month' => $row->month, 'count' => $row->count])
                ->toArray();
        } catch (\Throwable) {
            return [];
        }
    }
}
