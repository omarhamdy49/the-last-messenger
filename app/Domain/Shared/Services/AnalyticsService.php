<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Blog\Models\Article;
use App\Domain\Genealogy\Models\Person;
use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Quran\Models\Reciter;
use App\Domain\Quran\Models\Surah;
use App\Domain\Quran\Models\Verse;
use App\Domain\Sahaba\Models\Sahabi;
use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Shared\Models\AdminLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Analytics Service
 * Provides comprehensive analytics and insights for the platform
 */
class AnalyticsService
{
    private const CACHE_TTL = 3600; // 1 hour

    private const CHART_CACHE_TTL = 1800; // 30 minutes

    private const TRENDS_CACHE_TTL = 86400; // 1 day

    private const CACHE_PREFIX = 'analytics';

    /**
     * Get overall content statistics
     *
     * @return array<string, mixed>
     */
    public function getContentStats(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $cacheKey = $this->getCacheKey('content_stats', compact('startDate', 'endDate'));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($startDate, $endDate): array {
            $query = function ($model) use ($startDate, $endDate) {
                $q = $model::query();

                if ($startDate && $endDate) {
                    $q->whereBetween('created_at', [$startDate, $endDate]);
                }

                return $q;
            };

            try {
                return [
                    'total_content' => $this->getTotalContentCount($startDate, $endDate),
                    'by_module' => [
                        'articles' => (function () use ($startDate, $endDate): array {
                            $q = DB::table('articles')->whereNull('deleted_at');
                            if ($startDate && $endDate) {
                                $q->whereBetween('created_at', [$startDate, $endDate]);
                            }
                            $row = (clone $q)->selectRaw("
                                COUNT(*) as total,
                                SUM(status = 'published') as published,
                                SUM(status = 'draft') as draft,
                                SUM(status = 'archived') as archived
                            ")->first();

                            return [
                                'total' => (int) ($row->total ?? 0),
                                'published' => (int) ($row->published ?? 0),
                                'draft' => (int) ($row->draft ?? 0),
                                'archived' => (int) ($row->archived ?? 0),
                            ];
                        })(),
                        'hadith' => (function () use ($startDate, $endDate): array {
                            $q = DB::table('hadith_hadiths');
                            if ($startDate && $endDate) {
                                $q->whereBetween('created_at', [$startDate, $endDate]);
                            }
                            $row = (clone $q)->selectRaw("
                                COUNT(*) as total,
                                SUM(authenticity = 'sahih') as sahih,
                                SUM(authenticity = 'hasan') as hasan,
                                SUM(authenticity = 'daif') as daif
                            ")->first();

                            return [
                                'total' => (int) ($row->total ?? 0),
                                'sahih' => (int) ($row->sahih ?? 0),
                                'hasan' => (int) ($row->hasan ?? 0),
                                'daif' => (int) ($row->daif ?? 0),
                            ];
                        })(),
                        'hadith_narrators' => (function () use ($startDate, $endDate): array {
                            $q = DB::table('hadith_narrators');
                            if ($startDate && $endDate) {
                                $q->whereBetween('created_at', [$startDate, $endDate]);
                            }
                            $row = (clone $q)->selectRaw('
                                COUNT(*) as total,
                                SUM(is_sahabi = 1) as sahaba
                            ')->first();

                            return [
                                'total' => (int) ($row->total ?? 0),
                                'sahaba' => (int) ($row->sahaba ?? 0),
                            ];
                        })(),
                        'hadith_books' => [
                            'total' => $query(HadithBook::class)->count(),
                        ],
                        'seerah' => [
                            'events' => $query(SeerahEvent::class)->count(),
                        ],
                        'sahaba' => [
                            'total' => $query(Sahabi::class)->count(),
                        ],
                        'quran' => [
                            'surahs' => Surah::count(),
                            'verses' => Verse::count(),
                            'reciters' => $query(Reciter::class)->count(),
                        ],
                        'genealogy' => [
                            'people' => $query(Person::class)->count(),
                        ],
                    ],
                ];
            } catch (\Exception $e) {
                return $this->getEmptyContentStats();
            }
        });
    }

    /**
     * Get engagement metrics
     *
     * @return array<string, mixed>
     */
    public function getEngagementMetrics(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $cacheKey = $this->getCacheKey('engagement_metrics', compact('startDate', 'endDate'));

        return Cache::remember($cacheKey, self::CACHE_TTL, function (): array {
            try {
                // Single aggregate per table replaces repeated sum/count queries
                $articleAgg = DB::table('articles')
                    ->whereNull('deleted_at')
                    ->selectRaw("
                        COUNT(*) as total,
                        SUM(status = 'published') as published_count,
                        SUM(views_count) as total_views,
                        SUM(favorites_count) as total_favorites
                    ")
                    ->first();

                $hadithAgg = DB::table('hadith_hadiths')
                    ->selectRaw('
                        COUNT(*) as total,
                        SUM(views_count) as total_views
                    ')
                    ->first();

                $articleTotalViews = (int) ($articleAgg->total_views ?? 0);
                $articleTotalFavs = (int) ($articleAgg->total_favorites ?? 0);
                $articleTotal = (int) ($articleAgg->total ?? 0);
                $articlePublished = (int) ($articleAgg->published_count ?? 0);
                $hadithTotalViews = (int) ($hadithAgg->total_views ?? 0);
                $hadithTotal = (int) ($hadithAgg->total ?? 0);

                $totalViews = $articleTotalViews + $hadithTotalViews;

                return [
                    'total_views' => $totalViews,
                    'total_favorites' => $articleTotalFavs,
                    'average_views_per_article' => $articlePublished > 0
                        ? round($totalViews / $articlePublished, 2)
                        : 0,
                    'by_module' => [
                        'articles' => [
                            'total_views' => $articleTotalViews,
                            'total_favorites' => $articleTotalFavs,
                            'avg_views' => $articlePublished > 0
                                ? round($articleTotalViews / $articlePublished, 2)
                                : 0,
                        ],
                        'hadith' => [
                            'total_views' => $hadithTotalViews,
                            'avg_views' => $hadithTotal > 0
                                ? round($hadithTotalViews / $hadithTotal, 2)
                                : 0,
                        ],
                    ],
                    'engagement_rate' => $this->calculateEngagementRate($totalViews, $articleTotal),
                ];
            } catch (\Exception $e) {
                return $this->getEmptyEngagementMetrics();
            }
        });
    }

    /**
     * Get growth trends over time
     *
     * @return array<string, mixed>
     */
    public function getGrowthTrends(string $period = 'daily', int $limit = 30): array
    {
        $cacheKey = $this->getCacheKey('growth_trends', compact('period', 'limit'));

        return Cache::remember($cacheKey, self::TRENDS_CACHE_TTL, function () use ($period, $limit): array {
            try {
                $dateFormat = match ($period) {
                    'hourly' => '%Y-%m-%d %H:00:00',
                    'daily' => '%Y-%m-%d',
                    'weekly' => '%Y-%u',
                    'monthly' => '%Y-%m',
                    default => '%Y-%m-%d',
                };

                $startDate = match ($period) {
                    'hourly' => now()->subHours($limit),
                    'daily' => now()->subDays($limit),
                    'weekly' => now()->subWeeks($limit),
                    'monthly' => now()->subMonths($limit),
                    default => now()->subDays($limit),
                };

                $trends = [];

                // Articles trend
                $trends['articles'] = $this->getTrendData(Article::class, $startDate, $dateFormat);

                // Hadith trend
                $trends['hadith'] = $this->getTrendData(Hadith::class, $startDate, $dateFormat);

                // Users trend
                $trends['users'] = $this->getTrendData(User::class, $startDate, $dateFormat);

                // Activity trend (admin logs)
                $trends['activity'] = $this->getTrendData(AdminLog::class, $startDate, $dateFormat);

                return [
                    'period' => $period,
                    'limit' => $limit,
                    'trends' => $trends,
                ];
            } catch (\Exception $e) {
                return ['period' => $period, 'limit' => $limit, 'trends' => []];
            }
        });
    }

    /**
     * Get top content across all modules
     *
     * @return array<string, Collection>
     */
    public function getTopContent(int $limit = 10): array
    {
        $cacheKey = $this->getCacheKey('top_content', compact('limit'));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($limit): array {
            try {
                return [
                    'articles' => Article::where('status', 'published')
                        ->orderByDesc('views_count')
                        ->take($limit)
                        ->get(['id', 'title', 'views_count', 'favorites_count', 'created_at'])
                        ->map(fn ($item) => [
                            'id' => $item->id,
                            'title' => $item->title['ar'] ?? $item->title['en'] ?? 'Untitled',
                            'views' => $item->views_count ?? 0,
                            'favorites' => $item->favorites_count ?? 0,
                            'created_at' => $item->created_at->format('Y-m-d'),
                        ]),
                    'hadith' => Hadith::with('book:id,name')
                        ->orderByDesc('views_count')
                        ->take($limit)
                        ->get(['id', 'arabic_text', 'book_id', 'hadith_number', 'views_count', 'created_at'])
                        ->map(fn ($item) => [
                            'id' => $item->id,
                            'text' => mb_substr($item->arabic_text, 0, 100).'...',
                            'book' => $item->book?->name ?? 'Unknown',
                            'hadith_number' => $item->hadith_number,
                            'views' => $item->views_count ?? 0,
                            'created_at' => $item->created_at->format('Y-m-d'),
                        ]),
                    'reciters' => Reciter::orderByDesc('order')
                        ->take($limit)
                        ->get(['id', 'name', 'style', 'is_featured'])
                        ->map(fn ($item) => [
                            'id' => $item->id,
                            'name' => $item->name['ar'] ?? $item->name['en'] ?? 'Unknown',
                            'style' => $item->style,
                            'is_featured' => $item->is_featured,
                        ]),
                ];
            } catch (\Exception $e) {
                return [
                    'articles' => collect(),
                    'hadith' => collect(),
                    'reciters' => collect(),
                ];
            }
        });
    }

    /**
     * Get user statistics
     *
     * @return array<string, mixed>
     */
    public function getUserStats(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $cacheKey = $this->getCacheKey('user_stats', compact('startDate', 'endDate'));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($startDate, $endDate): array {
            try {
                $query = User::query();

                if ($startDate && $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                }

                $totalUsers = $query->count();
                $verifiedUsers = (clone $query)->whereNotNull('email_verified_at')->count();
                $activeUsers = $this->getActiveUsersCount($startDate, $endDate);

                // Users by role
                $usersByRole = DB::table('model_has_roles')
                    ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                    ->select('roles.name', DB::raw('COUNT(*) as count'))
                    ->groupBy('roles.name')
                    ->get()
                    ->mapWithKeys(fn ($item) => [$item->name => $item->count]);

                return [
                    'total' => $totalUsers,
                    'verified' => $verifiedUsers,
                    'unverified' => $totalUsers - $verifiedUsers,
                    'active' => $activeUsers,
                    'by_role' => $usersByRole->toArray(),
                    'verification_rate' => $totalUsers > 0
                        ? round(($verifiedUsers / $totalUsers) * 100, 2)
                        : 0,
                ];
            } catch (\Exception $e) {
                return [
                    'total' => 0,
                    'verified' => 0,
                    'unverified' => 0,
                    'active' => 0,
                    'by_role' => [],
                    'verification_rate' => 0,
                ];
            }
        });
    }

    /**
     * Get module breakdown statistics
     *
     * @return array<int, array<string, mixed>>
     */
    public function getModuleBreakdown(): array
    {
        $cacheKey = $this->getCacheKey('module_breakdown');

        return Cache::remember($cacheKey, self::CACHE_TTL, function (): array {
            try {
                $modules = [
                    [
                        'name' => 'Articles',
                        'slug' => 'articles',
                        'model' => Article::class,
                        'icon' => 'book-open',
                        'color' => '#0056b3',
                    ],
                    [
                        'name' => 'Hadith',
                        'slug' => 'hadith',
                        'model' => Hadith::class,
                        'icon' => 'book',
                        'color' => '#218838',
                    ],
                    [
                        'name' => 'Sahaba',
                        'slug' => 'sahaba',
                        'model' => Sahabi::class,
                        'icon' => 'users',
                        'color' => '#3B82F6',
                    ],
                    [
                        'name' => 'Seerah Events',
                        'slug' => 'seerah',
                        'model' => SeerahEvent::class,
                        'icon' => 'calendar',
                        'color' => '#F59E0B',
                    ],
                    [
                        'name' => 'Quran Verses',
                        'slug' => 'quran',
                        'model' => Verse::class,
                        'icon' => 'book-open',
                        'color' => '#10B981',
                    ],
                    [
                        'name' => 'Genealogy',
                        'slug' => 'genealogy',
                        'model' => Person::class,
                        'icon' => 'git-branch',
                        'color' => '#8B5CF6',
                    ],
                ];

                return collect($modules)->map(function ($module) {
                    try {
                        $model = $module['model'];
                        $count = $model::count();
                        $recentCount = $model::where('created_at', '>=', now()->subDays(7))->count();
                        $lastActivity = $model::latest()->first()?->created_at;

                        return [
                            'name' => $module['name'],
                            'slug' => $module['slug'],
                            'icon' => $module['icon'],
                            'color' => $module['color'],
                            'count' => $count,
                            'recent_count' => $recentCount,
                            'last_activity' => $lastActivity?->format('Y-m-d H:i:s'),
                            'growth_rate' => $this->calculateGrowthRate($count, $recentCount),
                        ];
                    } catch (\Exception $e) {
                        return [
                            'name' => $module['name'],
                            'slug' => $module['slug'],
                            'icon' => $module['icon'],
                            'color' => $module['color'],
                            'count' => 0,
                            'recent_count' => 0,
                            'last_activity' => null,
                            'growth_rate' => 0,
                        ];
                    }
                })->toArray();
            } catch (\Exception $e) {
                return [];
            }
        });
    }

    /**
     * Get date range analytics with comparison to previous period
     *
     * @return array<string, mixed>
     */
    public function getDateRangeAnalytics(Carbon $startDate, Carbon $endDate): array
    {
        $cacheKey = $this->getCacheKey('date_range_analytics', compact('startDate', 'endDate'));

        return Cache::remember($cacheKey, self::CHART_CACHE_TTL, function () use ($startDate, $endDate): array {
            try {
                // Calculate previous period
                $daysDiff = $startDate->diffInDays($endDate);
                $previousStart = $startDate->copy()->subDays($daysDiff + 1);
                $previousEnd = $startDate->copy()->subDay();

                // Current period stats
                $currentStats = $this->getPeriodStats($startDate, $endDate);

                // Previous period stats
                $previousStats = $this->getPeriodStats($previousStart, $previousEnd);

                // Calculate percentage changes
                $changes = $this->calculateChanges($currentStats, $previousStats);

                return [
                    'current_period' => [
                        'start' => $startDate->format('Y-m-d'),
                        'end' => $endDate->format('Y-m-d'),
                        'stats' => $currentStats,
                    ],
                    'previous_period' => [
                        'start' => $previousStart->format('Y-m-d'),
                        'end' => $previousEnd->format('Y-m-d'),
                        'stats' => $previousStats,
                    ],
                    'changes' => $changes,
                ];
            } catch (\Exception $e) {
                return [
                    'current_period' => ['start' => $startDate->format('Y-m-d'), 'end' => $endDate->format('Y-m-d'), 'stats' => []],
                    'previous_period' => ['start' => '', 'end' => '', 'stats' => []],
                    'changes' => [],
                ];
            }
        });
    }

    /**
     * Clear analytics cache.
     *
     * Cache::tags() requires Redis/Memcached and is unavailable with the file
     * or database drivers. We bump a version counter embedded in every cache
     * key so that stale entries are orphaned without needing tag support.
     */
    public function clearCache(): void
    {
        $versionKey = self::CACHE_PREFIX.':version';
        Cache::forever($versionKey, ((int) Cache::get($versionKey, 0)) + 1);
    }

    /**
     * Get total content count across all modules
     */
    private function getTotalContentCount(?Carbon $startDate = null, ?Carbon $endDate = null): int
    {
        $count = 0;

        $models = [
            Article::class,
            Hadith::class,
            SeerahEvent::class,
            Sahabi::class,
            Verse::class,
            Person::class,
        ];

        foreach ($models as $model) {
            try {
                $query = $model::query();

                if ($startDate && $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                }

                $count += $query->count();
            } catch (\Exception $e) {
                continue;
            }
        }

        return $count;
    }

    /**
     * Get active users count
     */
    private function getActiveUsersCount(?Carbon $startDate = null, ?Carbon $endDate = null): int
    {
        try {
            $query = DB::table('personal_access_tokens')
                ->where('last_used_at', '>=', now()->subDays(30));

            if ($startDate && $endDate) {
                $query->whereBetween('last_used_at', [$startDate, $endDate]);
            }

            return $query->distinct('tokenable_id')->count('tokenable_id');
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Calculate engagement rate
     */
    private function calculateEngagementRate(int $totalViews, int $totalContent): float
    {
        if ($totalContent === 0) {
            return 0.0;
        }

        return round(($totalViews / $totalContent) * 100, 2);
    }

    /**
     * Get trend data for a model
     *
     * @param  class-string  $model
     * @return array<int, array<string, mixed>>
     */
    private function getTrendData(string $model, Carbon $startDate, string $dateFormat): array
    {
        try {
            return DB::table((new $model)->getTable())
                ->select(
                    DB::raw("DATE_FORMAT(created_at, '{$dateFormat}') as period"),
                    DB::raw('COUNT(*) as count')
                )
                ->where('created_at', '>=', $startDate)
                ->groupBy('period')
                ->orderBy('period')
                ->get()
                ->map(fn ($row) => [
                    'period' => $row->period,
                    'count' => $row->count,
                ])
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get period statistics
     *
     * @return array<string, int>
     */
    private function getPeriodStats(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'articles' => Article::whereBetween('created_at', [$startDate, $endDate])->count(),
            'hadith' => Hadith::whereBetween('created_at', [$startDate, $endDate])->count(),
            'users' => User::whereBetween('created_at', [$startDate, $endDate])->count(),
            'activity' => AdminLog::whereBetween('created_at', [$startDate, $endDate])->count(),
        ];
    }

    /**
     * Calculate percentage changes between two periods
     *
     * @param  array<string, int>  $current
     * @param  array<string, int>  $previous
     * @return array<string, float>
     */
    private function calculateChanges(array $current, array $previous): array
    {
        $changes = [];

        foreach ($current as $key => $value) {
            $previousValue = $previous[$key] ?? 0;

            if ($previousValue === 0) {
                $changes[$key] = $value > 0 ? 100.0 : 0.0;
            } else {
                $changes[$key] = round((($value - $previousValue) / $previousValue) * 100, 2);
            }
        }

        return $changes;
    }

    /**
     * Calculate growth rate
     */
    private function calculateGrowthRate(int $total, int $recent): float
    {
        if ($total === 0) {
            return 0.0;
        }

        return round(($recent / $total) * 100, 2);
    }

    /**
     * Generate cache key.
     *
     * The version segment is read from cache so that after clearCache() bumps
     * the counter, all previously-stored keys are effectively orphaned and
     * subsequent reads repopulate fresh entries. This works with any driver.
     *
     * @param  array<string, mixed>  $parameters
     */
    private function getCacheKey(string $method, array $parameters = []): string
    {
        $version = (int) Cache::get(self::CACHE_PREFIX.':version', 0);
        $key = self::CACHE_PREFIX.':v'.$version.':'.$method;

        if (! empty($parameters)) {
            $key .= ':'.md5(serialize($parameters));
        }

        return $key;
    }

    /**
     * Get empty content stats structure
     *
     * @return array<string, mixed>
     */
    private function getEmptyContentStats(): array
    {
        return [
            'total_content' => 0,
            'by_module' => [
                'articles' => ['total' => 0, 'published' => 0, 'draft' => 0, 'archived' => 0],
                'hadith' => ['total' => 0, 'sahih' => 0, 'hasan' => 0, 'daif' => 0],
                'hadith_narrators' => ['total' => 0, 'sahaba' => 0],
                'hadith_books' => ['total' => 0],
                'seerah' => ['events' => 0],
                'sahaba' => ['total' => 0],
                'quran' => ['surahs' => 0, 'verses' => 0, 'reciters' => 0],
                'genealogy' => ['people' => 0],
            ],
        ];
    }

    /**
     * Get empty engagement metrics structure
     *
     * @return array<string, mixed>
     */
    private function getEmptyEngagementMetrics(): array
    {
        return [
            'total_views' => 0,
            'total_favorites' => 0,
            'average_views_per_article' => 0,
            'by_module' => [
                'articles' => ['total_views' => 0, 'total_favorites' => 0, 'avg_views' => 0],
                'hadith' => ['total_views' => 0, 'avg_views' => 0],
            ],
            'engagement_rate' => 0,
        ];
    }
}
