<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Shared\Services\DashboardAnalyticsService;
use App\Domain\Shared\Services\DashboardService;
use App\Http\Controllers\Controller;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Admin Dashboard Controller
 * Handles dashboard statistics and analytics
 */
class DashboardController extends Controller
{
    use ApiResponse;

    /**
     * Constructor
     */
    public function __construct(
        protected DashboardService $dashboardService,
        protected DashboardAnalyticsService $analyticsService,
    ) {}

    /**
     * Get dashboard overview
     *
     * GET /su/portal/v1/admin/dashboard
     */
    public function index(): \Illuminate\View\View
    {
        // The blade's stat cards read $stats[...] with `?? 0` fallbacks — rendering
        // without this mapping silently shows zeros for every module.
        $statistics = $this->dashboardService->getStatistics();

        $stats = [
            'total_users' => $statistics['users']['total'] ?? 0,
            'new_users' => $statistics['users']['new'] ?? 0,
            'users_change' => $statistics['users']['change'] ?? null,
            'total_articles' => $statistics['articles']['total'] ?? 0,
            'hadith_entries' => $statistics['hadith']['total'] ?? 0,
            'quran_verses' => $statistics['quran_verses']['total'] ?? 0,
            'seerah_events' => $statistics['seerah_events']['total'] ?? 0,
            'sahaba_profiles' => $statistics['sahaba']['total'] ?? 0,
            'media_items' => $statistics['media']['total'] ?? 0,
        ];

        return view('admin.dashboard', compact('stats'));
    }

    /**
     * Get enhanced statistics with sparklines
     *
     * GET /su/portal/v1/admin/dashboard/statistics
     */
    public function statistics(): JsonResponse
    {
        $statistics = $this->dashboardService->getEnhancedStatistics();

        return $this->successResponse($statistics, 'Dashboard statistics retrieved successfully');
    }

    /**
     * Get content growth chart data
     *
     * GET /su/portal/v1/admin/dashboard/content-growth
     */
    public function contentGrowth(Request $request): JsonResponse
    {
        $days = $request->integer('days', 30);
        $growth = $this->analyticsService->getContentGrowthChart($days);

        return $this->successResponse($growth, 'Content growth data retrieved successfully');
    }

    /**
     * Get module distribution for pie chart
     *
     * GET /su/portal/v1/admin/dashboard/module-distribution
     */
    public function moduleDistribution(): JsonResponse
    {
        $distribution = $this->analyticsService->getModuleDistribution();

        return $this->successResponse($distribution, 'Module distribution retrieved successfully');
    }

    /**
     * Get activity heatmap data
     *
     * GET /su/portal/v1/admin/dashboard/activity-heatmap
     */
    public function activityHeatmap(Request $request): JsonResponse
    {
        $days = $request->integer('days', 30);
        $heatmap = $this->analyticsService->getActivityHeatmap($days);

        return $this->successResponse($heatmap, 'Activity heatmap retrieved successfully');
    }

    /**
     * Get recent activity
     *
     * GET /su/portal/v1/admin/dashboard/recent-activity
     */
    public function recentActivity(Request $request): JsonResponse
    {
        $limit = $request->integer('limit', 10);
        $activity = $this->dashboardService->getRecentActivity($limit);

        return $this->successResponse($activity, 'Recent activity retrieved successfully');
    }

    /**
     * Get quick analytics insights
     *
     * GET /su/portal/v1/admin/dashboard/quick-analytics
     */
    public function quickAnalytics(): JsonResponse
    {
        $analytics = $this->dashboardService->getQuickAnalytics();

        return $this->successResponse($analytics, 'Quick analytics retrieved successfully');
    }

    /**
     * Get module health status
     *
     * GET /su/portal/v1/admin/dashboard/module-health
     */
    public function moduleHealth(): JsonResponse
    {
        $health = $this->dashboardService->getModuleHealth();

        return $this->successResponse($health, 'Module health status retrieved successfully');
    }

    /**
     * Get recent content from all modules
     *
     * GET /su/portal/v1/admin/dashboard/recent-content
     */
    public function recentContent(): JsonResponse
    {
        $content = $this->dashboardService->getRecentContent();

        return $this->successResponse($content, 'Recent content retrieved successfully');
    }

    /**
     * Get performance metrics
     *
     * GET /su/portal/v1/admin/dashboard/performance-metrics
     */
    public function performanceMetrics(): JsonResponse
    {
        $metrics = $this->analyticsService->getPerformanceMetrics();

        return $this->successResponse($metrics, 'Performance metrics retrieved successfully');
    }

    /**
     * Get content growth data (legacy endpoint)
     *
     * GET /su/portal/v1/admin/dashboard/growth
     */
    public function growth(): JsonResponse
    {
        $growth = $this->analyticsService->getContentGrowth();

        return $this->successResponse($growth, 'Content growth data retrieved successfully');
    }

    /**
     * Get popular content (legacy endpoint)
     *
     * GET /su/portal/v1/admin/dashboard/popular
     */
    public function popular(Request $request): JsonResponse
    {
        $limit = $request->integer('limit', 10);
        $popular = $this->analyticsService->getPopularContent($limit);

        return $this->successResponse($popular, 'Popular content retrieved successfully');
    }

    /**
     * Clear dashboard cache
     *
     * POST /su/portal/v1/admin/dashboard/cache/clear
     */
    public function clearCache(): JsonResponse
    {
        $this->dashboardService->clearCache();
        $this->analyticsService->clearCache();

        return $this->successResponse(null, 'Dashboard cache cleared successfully');
    }
}
