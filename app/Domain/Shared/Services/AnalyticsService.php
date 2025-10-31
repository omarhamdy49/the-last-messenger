<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Blog\Models\Article;
use App\Domain\Blog\Models\Category;
use App\Domain\Genealogy\Models\Person;
use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithNarrator;
use App\Domain\Media\Models\MediaEpisode;
use App\Domain\Media\Models\MediaProgram;
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
                        'articles' => [
                            'total' => $query(Article::class)->count(),
                            'published' => $query(Article::class)->where('status', 'published')->count(),
                            'draft' => $query(Article::class)->where('status', 'draft')->count(),
                            'archived' => $query(Article::class)->where('status', 'archived')->count(),
                        ],
                        'hadith' => [
                            'total' => $query(Hadith::class)->count(),
                            'sahih' => $query(Hadith::class)->where('authenticity', 'sahih')->count(),
                            'hasan' => $query(Hadith::class)->where('authenticity', 'hasan')->count(),
                            'daif' => $query(Hadith::class)->where('authenticity', 'daif')->count(),
                        ],
                        'hadith_narrators' => [
                            'total' => $query(HadithNarrator::class)->count(),
                            'sahaba' => $query(HadithNarrator::class)->where('is_sahabi', true)->count(),
                        ],
                        'hadith_books' => [
                            'total' => $query(HadithBook::class)->count(),
                        ],
                        'media' => [
                            'programs' => $query(MediaProgram::class)->count(),
                            'episodes' => $query(MediaEpisode::class)->count(),
                            'total_views' => MediaProgram::sum('views_count') ?? 0,
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

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($startDate, $endDate): array {
            try {
                // Total views across all modules
                $totalViews = Article::sum('views_count')
                    + Hadith::sum('views_count')
                    + MediaProgram::sum('views_count');

                // Average engagement per module
                $articlesCount = Article::where('status', 'published')->count();
                $hadithCount = Hadith::count();
                $mediaCount = MediaProgram::count();

                return [
                    'total_views' => $totalViews,
                    'total_favorites' => Article::sum('favorites_count') ?? 0,
                    'average_views_per_article' => $articlesCount > 0
                        ? round($totalViews / $articlesCount, 2)
                        : 0,
                    'by_module' => [
                        'articles' => [
                            'total_views' => Article::sum('views_count') ?? 0,
                            'total_favorites' => Article::sum('favorites_count') ?? 0,
                            'avg_views' => $articlesCount > 0
                                ? round((Article::sum('views_count') ?? 0) / $articlesCount, 2)
                                : 0,
                        ],
                        'hadith' => [
                            'total_views' => Hadith::sum('views_count') ?? 0,
                            'avg_views' => $hadithCount > 0
                                ? round((Hadith::sum('views_count') ?? 0) / $hadithCount, 2)
                                : 0,
                        ],
                        'media' => [
                            'total_views' => MediaProgram::sum('views_count') ?? 0,
                            'avg_views' => $mediaCount > 0
                                ? round((MediaProgram::sum('views_count') ?? 0) / $mediaCount, 2)
                                : 0,
                        ],
                    ],
                    'engagement_rate' => $this->calculateEngagementRate($totalViews, Article::count()),
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

                // Media programs trend
                $trends['media'] = $this->getTrendData(MediaProgram::class, $startDate, $dateFormat);

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
                    'media' => MediaProgram::orderByDesc('views_count')
                        ->take($limit)
                        ->get(['id', 'title', 'views_count', 'type', 'created_at'])
                        ->map(fn ($item) => [
                            'id' => $item->id,
                            'title' => $item->title['ar'] ?? $item->title['en'] ?? 'Untitled',
                            'views' => $item->views_count ?? 0,
                            'type' => $item->type,
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
                    'media' => collect(),
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
                        'name' => 'Media Programs',
                        'slug' => 'media',
                        'model' => MediaProgram::class,
                        'icon' => 'video',
                        'color' => '#64CCC5',
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
     * Clear analytics cache
     */
    public function clearCache(): void
    {
        Cache::tags([self::CACHE_PREFIX])->flush();
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
            MediaProgram::class,
            MediaEpisode::class,
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
            'media' => MediaProgram::whereBetween('created_at', [$startDate, $endDate])->count(),
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
     * Generate cache key
     *
     * @param  array<string, mixed>  $parameters
     */
    private function getCacheKey(string $method, array $parameters = []): string
    {
        $key = self::CACHE_PREFIX.':'.$method;

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
                'media' => ['programs' => 0, 'episodes' => 0, 'total_views' => 0],
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
                'media' => ['total_views' => 0, 'avg_views' => 0],
            ],
            'engagement_rate' => 0,
        ];
    }
}
