<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Blog\Models\Article;
use App\Domain\Blog\Models\Category;
use App\Domain\Genealogy\Models\Person;
use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithNarrator;
use App\Domain\Media\Models\MediaProgram;
use App\Domain\Quran\Models\Verse;
use App\Domain\Sahaba\Models\Sahabi;
use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Shared\Models\AdminLog;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard Service
 * Provides statistics and analytics for admin dashboard
 */
class DashboardService
{
    private const CACHE_TTL = 300; // 5 minutes

    private const CACHE_PREFIX = 'dashboard';

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

                // Try to get counts for each module (skip if table doesn't exist)
                try {
                    $stats['articles'] = [
                        'total' => Article::count(),
                        'published' => Article::where('status', 'published')->count(),
                        'draft' => Article::where('status', 'draft')->count(),
                        'archived' => Article::where('status', 'archived')->count(),
                    ];
                } catch (\Exception $e) {
                    $stats['articles'] = ['total' => 0, 'published' => 0, 'draft' => 0, 'archived' => 0];
                }

                try {
                    $stats['hadith'] = ['total' => Hadith::count()];
                } catch (\Exception $e) {
                    $stats['hadith'] = ['total' => 0];
                }

                try {
                    $stats['media_programs'] = ['total' => MediaProgram::count()];
                } catch (\Exception $e) {
                    $stats['media_programs'] = ['total' => 0];
                }

                try {
                    $stats['quran_verses'] = ['total' => Verse::count()];
                } catch (\Exception $e) {
                    $stats['quran_verses'] = ['total' => 0];
                }

                try {
                    $stats['sahaba'] = ['total' => Sahabi::count()];
                } catch (\Exception $e) {
                    $stats['sahaba'] = ['total' => 0];
                }

                try {
                    $stats['seerah_events'] = ['total' => SeerahEvent::count()];
                } catch (\Exception $e) {
                    $stats['seerah_events'] = ['total' => 0];
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
                    return AdminLog::with('user')
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
                } catch (\Exception $e) {
                    // Fallback to original implementation if admin_logs table doesn't exist
                    $activities = collect();

                    // Recent articles
                    try {
                        Article::with('author:id,name')
                            ->latest()
                            ->take($limit)
                            ->get(['id', 'title', 'status', 'user_id', 'created_at'])
                            ->each(function ($article) use ($activities): void {
                                $activities->push([
                                    'type' => 'article',
                                    'id' => $article->id,
                                    'title' => $article->title['ar'] ?? $article->title['en'] ?? 'Untitled',
                                    'status' => $article->status,
                                    'author' => $article->author?->name,
                                    'created_at' => $article->created_at,
                                ]);
                            });
                    } catch (\Exception $e) {
                        // Skip
                    }

                    // Recent hadith
                    try {
                        Hadith::with('book:id,name')
                            ->latest()
                            ->take($limit)
                            ->get(['id', 'arabic_text', 'book_id', 'hadith_number', 'created_at'])
                            ->each(function ($hadith) use ($activities): void {
                                $activities->push([
                                    'type' => 'hadith',
                                    'id' => $hadith->id,
                                    'title' => mb_substr($hadith->arabic_text, 0, 50).'...',
                                    'hadith_number' => $hadith->hadith_number,
                                    'book' => $hadith->book?->name ?? 'Unknown Book',
                                    'created_at' => $hadith->created_at,
                                ]);
                            });
                    } catch (\Exception $e) {
                        // Skip
                    }

                    // Recent media programs
                    try {
                        MediaProgram::latest()
                            ->take($limit)
                            ->get(['id', 'title', 'type', 'created_at'])
                            ->each(function ($program) use ($activities): void {
                                $activities->push([
                                    'type' => 'media_program',
                                    'id' => $program->id,
                                    'title' => $program->title['ar'] ?? $program->title['en'] ?? 'Untitled',
                                    'program_type' => $program->type,
                                    'created_at' => $program->created_at,
                                ]);
                            });
                    } catch (\Exception $e) {
                        // Skip
                    }

                    return $activities
                        ->sortByDesc('created_at')
                        ->take($limit)
                        ->values();
                }
            }
        );
    }

    /**
     * Get content growth data for charts (last 12 months)
     *
     * @return array<string, array<string, mixed>>
     */
    public function getContentGrowth(): array
    {
        return Cache::remember(
            self::CACHE_PREFIX.'.content_growth',
            self::CACHE_TTL,
            function (): array {
                $startDate = now()->subMonths(11)->startOfMonth();

                return [
                    'articles' => $this->getMonthlyGrowth(Article::class, $startDate),
                    'hadith' => $this->getMonthlyGrowth(Hadith::class, $startDate),
                    'media_programs' => $this->getMonthlyGrowth(MediaProgram::class, $startDate),
                    'sahaba' => $this->getMonthlyGrowth(Sahabi::class, $startDate),
                    'users' => $this->getMonthlyGrowth(User::class, $startDate),
                ];
            }
        );
    }

    /**
     * Get popular content across modules
     *
     * @return array<string, Collection>
     */
    public function getPopularContent(int $limit = 10): array
    {
        return Cache::remember(
            self::CACHE_PREFIX.".popular_content.{$limit}",
            self::CACHE_TTL,
            function () use ($limit): array {
                $popular = [];

                // Popular articles
                try {
                    $popular['articles'] = Article::where('status', 'published')
                        ->orderByDesc('views_count')
                        ->take($limit)
                        ->get(['id', 'title', 'views_count', 'favorites_count'])
                        ->map(fn ($article) => [
                            'id' => $article->id,
                            'title' => $article->title['ar'] ?? $article->title['en'] ?? 'Untitled',
                            'views' => $article->views_count,
                            'favorites' => $article->favorites_count,
                        ]);
                } catch (\Exception $e) {
                    $popular['articles'] = collect();
                }

                // Popular hadith
                try {
                    $popular['hadith'] = Hadith::with('book:id,name')
                        ->orderByDesc('views_count')
                        ->take($limit)
                        ->get(['id', 'arabic_text', 'book_id', 'hadith_number', 'views_count'])
                        ->map(fn ($hadith) => [
                            'id' => $hadith->id,
                            'text' => mb_substr($hadith->arabic_text, 0, 100).'...',
                            'hadith_number' => $hadith->hadith_number,
                            'book' => $hadith->book?->name ?? 'Unknown Book',
                            'views' => $hadith->views_count,
                        ]);
                } catch (\Exception $e) {
                    $popular['hadith'] = collect();
                }

                // Popular media programs
                try {
                    $popular['media_programs'] = MediaProgram::orderByDesc('views_count')
                        ->take($limit)
                        ->get(['id', 'title', 'views_count'])
                        ->map(fn ($program) => [
                            'id' => $program->id,
                            'title' => $program->title['ar'] ?? $program->title['en'] ?? 'Untitled',
                            'views' => $program->views_count,
                        ]);
                } catch (\Exception $e) {
                    $popular['media_programs'] = collect();
                }

                return $popular;
            }
        );
    }

    /**
     * Get enhanced statistics with sparklines and percentage changes
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
                    // Article stats
                    $articlesTotal = Article::count();
                    $articlesPublished = Article::where('status', 'published')->count();
                    $articlesDraft = Article::where('status', 'draft')->count();
                    $articlesLastWeek = Article::where('created_at', '>=', now()->subWeek())->count();
                    $articlesPreviousWeek = Article::whereBetween('created_at', [now()->subWeeks(2), now()->subWeek()])->count();

                    // Hadith stats
                    $hadithTotal = Hadith::count();
                    $hadithSahih = Hadith::where('authenticity', 'sahih')->count();
                    $hadithLastWeek = Hadith::where('created_at', '>=', now()->subWeek())->count();
                    $hadithPreviousWeek = Hadith::whereBetween('created_at', [now()->subWeeks(2), now()->subWeek()])->count();

                    // Media stats
                    $mediaTotal = MediaProgram::count();
                    $mediaViews = MediaProgram::sum('views_count') ?? 0;
                    $mediaLastWeek = MediaProgram::where('created_at', '>=', now()->subWeek())->count();
                    $mediaPreviousWeek = MediaProgram::whereBetween('created_at', [now()->subWeeks(2), now()->subWeek()])->count();

                    // User stats
                    $usersTotal = User::count();
                    $usersNew = User::where('created_at', '>=', now()->subMonth())->count();
                    $usersLastWeek = User::where('created_at', '>=', now()->subWeek())->count();
                    $usersPreviousWeek = User::whereBetween('created_at', [now()->subWeeks(2), now()->subWeek()])->count();

                    return [
                        'articles' => [
                            'total' => $articlesTotal,
                            'published' => $articlesPublished,
                            'draft' => $articlesDraft,
                            'change' => $this->calculatePercentageChange($articlesLastWeek, $articlesPreviousWeek),
                            'sparkline' => $this->getSparklineData('articles', 7),
                        ],
                        'hadith' => [
                            'total' => $hadithTotal,
                            'sahih' => $hadithSahih,
                            'percentage' => $hadithTotal > 0 ? round(($hadithSahih / $hadithTotal) * 100) : 0,
                            'change' => $this->calculatePercentageChange($hadithLastWeek, $hadithPreviousWeek),
                            'sparkline' => $this->getSparklineData('hadith', 7),
                        ],
                        'media' => [
                            'total' => $mediaTotal,
                            'views' => $mediaViews,
                            'change' => $this->calculatePercentageChange($mediaLastWeek, $mediaPreviousWeek),
                            'sparkline' => $this->getSparklineData('media', 7),
                        ],
                        'users' => [
                            'total' => $usersTotal,
                            'new' => $usersNew,
                            'change' => $this->calculatePercentageChange($usersLastWeek, $usersPreviousWeek),
                            'sparkline' => $this->getSparklineData('users', 7),
                        ],
                    ];
                } catch (\Exception $e) {
                    return [
                        'articles' => ['total' => 0, 'published' => 0, 'draft' => 0, 'change' => 0, 'sparkline' => []],
                        'hadith' => ['total' => 0, 'sahih' => 0, 'percentage' => 0, 'change' => 0, 'sparkline' => []],
                        'media' => ['total' => 0, 'views' => 0, 'change' => 0, 'sparkline' => []],
                        'users' => ['total' => 0, 'new' => 0, 'change' => 0, 'sparkline' => []],
                    ];
                }
            }
        );
    }

    /**
     * Get content growth chart data
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
                $articlesData = [];
                $hadithData = [];
                $mediaData = [];

                for ($i = $days - 1; $i >= 0; $i--) {
                    $date = now()->subDays($i)->toDateString();
                    $labels[] = $date;

                    try {
                        $articlesData[] = Article::whereDate('created_at', $date)->count();
                        $hadithData[] = Hadith::whereDate('created_at', $date)->count();
                        $mediaData[] = MediaProgram::whereDate('created_at', $date)->count();
                    } catch (\Exception $e) {
                        $articlesData[] = 0;
                        $hadithData[] = 0;
                        $mediaData[] = 0;
                    }
                }

                return [
                    'labels' => $labels,
                    'datasets' => [
                        [
                            'label' => 'Articles',
                            'data' => $articlesData,
                            'borderColor' => '#0056b3',
                            'backgroundColor' => 'rgba(0, 86, 179, 0.1)',
                        ],
                        [
                            'label' => 'Hadith',
                            'data' => $hadithData,
                            'borderColor' => '#218838',
                            'backgroundColor' => 'rgba(33, 136, 56, 0.1)',
                        ],
                        [
                            'label' => 'Media',
                            'data' => $mediaData,
                            'borderColor' => '#64CCC5',
                            'backgroundColor' => 'rgba(100, 204, 197, 0.1)',
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
                try {
                    $data = [
                        Article::count(),
                        Hadith::count(),
                        MediaProgram::count(),
                        Sahabi::count(),
                        SeerahEvent::count(),
                        Verse::count(),
                        Person::count(),
                    ];

                    return [
                        'labels' => ['Articles', 'Hadith', 'Media', 'Sahaba', 'Seerah', 'Quran', 'Genealogy'],
                        'data' => $data,
                        'colors' => ['#0056b3', '#218838', '#64CCC5', '#3B82F6', '#F59E0B', '#10B981', '#8B5CF6'],
                    ];
                } catch (\Exception $e) {
                    return [
                        'labels' => ['Articles', 'Hadith', 'Media', 'Sahaba', 'Seerah', 'Quran', 'Genealogy'],
                        'data' => [0, 0, 0, 0, 0, 0, 0],
                        'colors' => ['#0056b3', '#218838', '#64CCC5', '#3B82F6', '#F59E0B', '#10B981', '#8B5CF6'],
                    ];
                }
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
                $heatmap = [];

                try {
                    for ($i = $days - 1; $i >= 0; $i--) {
                        $date = now()->subDays($i)->toDateString();

                        // Count all activity for this day
                        $count = AdminLog::whereDate('created_at', $date)->count();

                        // Also count content creation
                        $count += Article::whereDate('created_at', $date)->count();
                        $count += Hadith::whereDate('created_at', $date)->count();
                        $count += MediaProgram::whereDate('created_at', $date)->count();

                        $heatmap[] = [
                            'date' => $date,
                            'count' => $count,
                        ];
                    }
                } catch (\Exception $e) {
                    // Return empty heatmap on error
                    for ($i = $days - 1; $i >= 0; $i--) {
                        $heatmap[] = [
                            'date' => now()->subDays($i)->toDateString(),
                            'count' => 0,
                        ];
                    }
                }

                return $heatmap;
            }
        );
    }

    /**
     * Get quick analytics insights
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
                    // Top article by views
                    $topArticle = Article::where('status', 'published')
                        ->orderByDesc('views_count')
                        ->first(['id', 'title', 'views_count']);

                    // Most active day
                    $mostActiveDay = AdminLog::select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
                        ->groupBy('date')
                        ->orderByDesc('count')
                        ->first();

                    // Popular category
                    $popularCategory = Category::withCount('articles')
                        ->orderByDesc('articles_count')
                        ->first(['id', 'name']);

                    // Top narrator
                    $topNarrator = HadithNarrator::withCount('hadith')
                        ->orderByDesc('hadith_count')
                        ->first(['id', 'name']);

                    // Latest media upload
                    $latestUpload = MediaProgram::latest()
                        ->first(['id', 'title', 'created_at']);

                    // Pending reviews (draft articles)
                    $pendingReviews = Article::where('status', 'draft')->count();

                    return [
                        'top_article' => $topArticle ? [
                            'id' => $topArticle->id,
                            'title' => $topArticle->title['ar'] ?? $topArticle->title['en'] ?? 'Untitled',
                            'views' => $topArticle->views_count,
                        ] : null,
                        'most_active_day' => $mostActiveDay ? [
                            'date' => $mostActiveDay->date,
                            'count' => $mostActiveDay->count,
                        ] : null,
                        'popular_category' => $popularCategory ? [
                            'id' => $popularCategory->id,
                            'name' => $popularCategory->name['ar'] ?? $popularCategory->name['en'] ?? 'Unknown',
                            'count' => $popularCategory->articles_count ?? 0,
                        ] : null,
                        'top_narrator' => $topNarrator ? [
                            'id' => $topNarrator->id,
                            'name' => $topNarrator->name,
                            'count' => $topNarrator->hadith_count ?? 0,
                        ] : null,
                        'latest_upload' => $latestUpload ? [
                            'id' => $latestUpload->id,
                            'title' => $latestUpload->title['ar'] ?? $latestUpload->title['en'] ?? 'Untitled',
                            'date' => $latestUpload->created_at->format('Y-m-d'),
                        ] : null,
                        'pending_reviews' => $pendingReviews,
                    ];
                } catch (\Exception $e) {
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
                $modules = [
                    ['name' => 'Articles', 'model' => Article::class, 'icon' => 'book-open'],
                    ['name' => 'Hadith', 'model' => Hadith::class, 'icon' => 'book'],
                    ['name' => 'Media', 'model' => MediaProgram::class, 'icon' => 'video'],
                    ['name' => 'Sahaba', 'model' => Sahabi::class, 'icon' => 'users'],
                    ['name' => 'Seerah', 'model' => SeerahEvent::class, 'icon' => 'calendar'],
                    ['name' => 'Quran', 'model' => Verse::class, 'icon' => 'book-open'],
                    ['name' => 'Genealogy', 'model' => Person::class, 'icon' => 'git-branch'],
                ];

                return collect($modules)->map(function ($module) {
                    try {
                        $model = $module['model'];
                        $count = $model::count();
                        $lastActivity = $model::latest()->first()?->created_at;

                        // Determine health status
                        $status = 'healthy';
                        if ($count < 50) {
                            $status = 'critical';
                        } elseif ($count < 100) {
                            $status = 'warning';
                        }

                        if ($lastActivity && $lastActivity->lt(now()->subDays(30))) {
                            $status = $status === 'healthy' ? 'warning' : 'critical';
                        }

                        return [
                            'name' => $module['name'],
                            'status' => $status,
                            'count' => $count,
                            'last_activity' => $lastActivity?->format('Y-m-d H:i:s'),
                            'icon' => $module['icon'],
                        ];
                    } catch (\Exception $e) {
                        return [
                            'name' => $module['name'],
                            'status' => 'critical',
                            'count' => 0,
                            'last_activity' => null,
                            'icon' => $module['icon'],
                        ];
                    }
                })->toArray();
            }
        );
    }

    /**
     * Get recent content from each module
     *
     * @return array<string, Collection>
     */
    public function getRecentContent(): array
    {
        return Cache::remember(
            self::CACHE_PREFIX.'.recent_content',
            self::CACHE_TTL,
            function (): array {
                $recentContent = [];

                try {
                    $recentContent['articles'] = Article::with('author')
                        ->latest()
                        ->take(10)
                        ->get()
                        ->map(fn ($article) => [
                            'id' => $article->id,
                            'title' => $article->title['ar'] ?? $article->title['en'] ?? 'Untitled',
                            'author' => $article->author?->name,
                            'status' => $article->status,
                            'created_at' => $article->created_at->format('Y-m-d H:i'),
                        ]);
                } catch (\Exception $e) {
                    $recentContent['articles'] = collect();
                }

                try {
                    $recentContent['hadith'] = Hadith::with('book')
                        ->latest()
                        ->take(10)
                        ->get()
                        ->map(fn ($hadith) => [
                            'id' => $hadith->id,
                            'text' => mb_substr($hadith->arabic_text, 0, 100).'...',
                            'book' => $hadith->book?->name ?? 'Unknown',
                            'created_at' => $hadith->created_at->format('Y-m-d H:i'),
                        ]);
                } catch (\Exception $e) {
                    $recentContent['hadith'] = collect();
                }

                try {
                    $recentContent['media'] = MediaProgram::latest()
                        ->take(10)
                        ->get()
                        ->map(fn ($program) => [
                            'id' => $program->id,
                            'title' => $program->title['ar'] ?? $program->title['en'] ?? 'Untitled',
                            'type' => $program->type,
                            'created_at' => $program->created_at->format('Y-m-d H:i'),
                        ]);
                } catch (\Exception $e) {
                    $recentContent['media'] = collect();
                }

                return $recentContent;
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
                    // Get database size
                    $databaseName = DB::getDatabaseName();
                    $dbSize = DB::select('
                        SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size
                        FROM information_schema.TABLES
                        WHERE table_schema = ?
                    ', [$databaseName]);

                    // Cache hit rate (if using Redis)
                    $cacheHitRate = 85.0; // Placeholder - would need Redis info command

                    // Active sessions (count logged in users in last 30 minutes)
                    $activeSessions = DB::table('personal_access_tokens')
                        ->where('last_used_at', '>=', now()->subMinutes(30))
                        ->count();

                    return [
                        'api_response_time' => 145, // Placeholder - would need actual monitoring
                        'database_size' => $dbSize[0]->size ?? 0,
                        'cache_hit_rate' => $cacheHitRate,
                        'active_sessions' => $activeSessions,
                    ];
                } catch (\Exception $e) {
                    return [
                        'api_response_time' => 0,
                        'database_size' => 0,
                        'cache_hit_rate' => 0,
                        'active_sessions' => 0,
                    ];
                }
            }
        );
    }

    /**
     * Clear dashboard cache
     */
    public function clearCache(): void
    {
        // Clear all dashboard cache keys
        $keys = [
            self::CACHE_PREFIX.'.statistics',
            self::CACHE_PREFIX.'.enhanced_statistics',
            self::CACHE_PREFIX.'.content_growth',
            self::CACHE_PREFIX.'.module_distribution',
            self::CACHE_PREFIX.'.quick_analytics',
            self::CACHE_PREFIX.'.module_health',
            self::CACHE_PREFIX.'.recent_content',
            self::CACHE_PREFIX.'.performance_metrics',
        ];

        // Clear recent activity and content growth for common limits/days
        for ($i = 5; $i <= 50; $i += 5) {
            $keys[] = self::CACHE_PREFIX.".recent_activity.{$i}";
            $keys[] = self::CACHE_PREFIX.".popular_content.{$i}";
            $keys[] = self::CACHE_PREFIX.".content_growth_chart.{$i}";
            $keys[] = self::CACHE_PREFIX.".activity_heatmap.{$i}";
        }

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Get monthly growth for a model
     *
     * @param  class-string  $model
     * @param  \Carbon\Carbon  $startDate
     * @return array<int, array<string, mixed>>
     */
    private function getMonthlyGrowth(string $model, $startDate): array
    {
        return DB::table((new $model)->getTable())
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', $startDate)
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn ($row) => [
                'month' => $row->month,
                'count' => $row->count,
            ])
            ->toArray();
    }

    /**
     * Format action description for admin log
     */
    private function formatActionDescription(AdminLog $log): string
    {
        $modelName = class_basename($log->model_type ?? 'Unknown');
        $userName = $log->user?->name ?? 'Unknown User';

        // Try to get title or name from new_values
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
     * Get sparkline data for last N days
     *
     * @return array<int, int>
     */
    private function getSparklineData(string $model, int $days = 7): array
    {
        $modelClass = match ($model) {
            'articles' => Article::class,
            'hadith' => Hadith::class,
            'media' => MediaProgram::class,
            'users' => User::class,
            default => null
        };

        if (! $modelClass) {
            return array_fill(0, $days, 0);
        }

        $data = [];

        try {
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = now()->subDays($i)->toDateString();
                $count = $modelClass::whereDate('created_at', $date)->count();
                $data[] = $count;
            }
        } catch (\Exception $e) {
            $data = array_fill(0, $days, 0);
        }

        return $data;
    }
}
