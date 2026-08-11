<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Shared\Services\AnalyticsService;
use App\Domain\Shared\Services\DashboardAnalyticsService;
use App\Domain\Shared\Services\DashboardService;
use App\Http\Controllers\Controller;
use App\Support\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Analytics Controller
 * Handles comprehensive analytics dashboard and insights
 */
class AnalyticsController extends Controller
{
    use ApiResponse;

    /**
     * Constructor
     */
    public function __construct(
        protected AnalyticsService $analyticsService,
        protected DashboardService $dashboardService,
        protected DashboardAnalyticsService $dashboardAnalyticsService,
    ) {}

    /**
     * Display analytics dashboard
     *
     * GET /su/portal/v1/admin/analytics
     */
    public function dashboard(): View
    {
        return view('admin.analytics.dashboard');
    }

    /**
     * Get comprehensive statistics
     *
     * GET /su/portal/v1/admin/analytics/stats
     *
     * Query params:
     * - start_date: Y-m-d format
     * - end_date: Y-m-d format
     */
    public function getStats(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $request->has('start_date')
            ? Carbon::parse($request->input('start_date'))
            : null;

        $endDate = $request->has('end_date')
            ? Carbon::parse($request->input('end_date'))
            : null;

        $stats = [
            'content' => $this->analyticsService->getContentStats($startDate, $endDate),
            'engagement' => $this->analyticsService->getEngagementMetrics($startDate, $endDate),
            'users' => $this->analyticsService->getUserStats($startDate, $endDate),
            'modules' => $this->analyticsService->getModuleBreakdown(),
        ];

        return $this->successResponse($stats, 'Statistics retrieved successfully');
    }

    /**
     * Get chart data for visualizations
     *
     * GET /su/portal/v1/admin/analytics/charts/{type}
     *
     * Types: growth, distribution, engagement, activity
     *
     * Query params:
     * - period: daily, weekly, monthly
     * - limit: number of data points
     */
    public function getChartData(Request $request, string $type): JsonResponse
    {
        $request->validate([
            'period' => 'nullable|in:hourly,daily,weekly,monthly',
            'limit' => 'nullable|integer|min:7|max:365',
        ]);

        $period = $request->input('period', 'daily');
        $limit = $request->integer('limit', 30);

        $chartData = match ($type) {
            'growth' => $this->analyticsService->getGrowthTrends($period, $limit),
            'distribution' => $this->dashboardAnalyticsService->getModuleDistribution(),
            'engagement' => $this->analyticsService->getViewsChart($limit),
            'activity' => $this->dashboardAnalyticsService->getActivityHeatmap($limit),
            default => null,
        };

        if ($chartData === null) {
            return $this->errorResponse('Invalid chart type', 400);
        }

        return $this->successResponse($chartData, 'Chart data retrieved successfully');
    }

    /**
     * Get engagement metrics
     *
     * GET /su/portal/v1/admin/analytics/engagement
     */
    public function getEngagement(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $request->has('start_date')
            ? Carbon::parse($request->input('start_date'))
            : null;

        $endDate = $request->has('end_date')
            ? Carbon::parse($request->input('end_date'))
            : null;

        $engagement = $this->analyticsService->getEngagementMetrics($startDate, $endDate);

        return $this->successResponse($engagement, 'Engagement metrics retrieved successfully');
    }

    /**
     * Get trend data
     *
     * GET /su/portal/v1/admin/analytics/trends
     *
     * Query params:
     * - period: hourly, daily, weekly, monthly
     * - limit: number of periods to show
     */
    public function getTrends(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'nullable|in:hourly,daily,weekly,monthly',
            'limit' => 'nullable|integer|min:7|max:365',
        ]);

        $period = $request->input('period', 'daily');
        $limit = $request->integer('limit', 30);

        $trends = $this->analyticsService->getGrowthTrends($period, $limit);

        return $this->successResponse($trends, 'Trend data retrieved successfully');
    }

    /**
     * Get top content across modules
     *
     * GET /su/portal/v1/admin/analytics/top-content
     *
     * Query params:
     * - limit: number of items per module
     */
    public function getTopContent(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'nullable|integer|min:5|max:50',
        ]);

        $limit = $request->integer('limit', 10);

        $topContent = $this->analyticsService->getTopContent($limit);

        return $this->successResponse($topContent, 'Top content retrieved successfully');
    }

    /**
     * Get module-specific analytics
     *
     * GET /su/portal/v1/admin/analytics/module/{module}
     *
     * Modules: articles, hadith, media, sahaba, seerah, quran, genealogy
     */
    public function getModuleAnalytics(Request $request, string $module): JsonResponse
    {
        $validModules = ['articles', 'hadith', 'media', 'sahaba', 'seerah', 'quran', 'genealogy'];

        if (! in_array($module, $validModules)) {
            return $this->errorResponse('Invalid module', 400);
        }

        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $request->has('start_date')
            ? Carbon::parse($request->input('start_date'))
            : null;

        $endDate = $request->has('end_date')
            ? Carbon::parse($request->input('end_date'))
            : null;

        $analytics = $this->getModuleSpecificAnalytics($module, $startDate, $endDate);

        return $this->successResponse($analytics, "Analytics for {$module} retrieved successfully");
    }

    /**
     * Get media view statistics from real view events
     *
     * GET /su/portal/v1/admin/analytics/media-views
     *
     * Query params:
     * - limit: number of items per list (default 10)
     */
    public function getMediaViews(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'nullable|integer|min:5|max:50',
        ]);

        $stats = $this->analyticsService->getMediaViewStats($request->integer('limit', 10));

        return $this->successResponse($stats, 'Media view statistics retrieved successfully');
    }

    /**
     * Get date range comparison analytics
     *
     * GET /su/portal/v1/admin/analytics/comparison
     *
     * Query params:
     * - start_date: Y-m-d format (required)
     * - end_date: Y-m-d format (required)
     */
    public function getComparison(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));

        $comparison = $this->analyticsService->getDateRangeAnalytics($startDate, $endDate);

        return $this->successResponse($comparison, 'Comparison analytics retrieved successfully');
    }

    /**
     * Export analytics data
     *
     * GET /su/portal/v1/admin/analytics/export
     *
     * Query params:
     * - format: csv, excel
     * - type: stats, trends, top_content, engagement
     * - start_date: Y-m-d format
     * - end_date: Y-m-d format
     */
    public function exportAnalytics(Request $request): JsonResponse
    {
        $request->validate([
            'format' => 'required|in:csv,excel',
            'type' => 'required|in:stats,trends,top_content,engagement',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        // Not implemented: nothing here produces a file, so a 200 "success"
        // envelope would tell the caller an export exists when none does.
        // 501 until this is wired to ExportController/ExportService.
        return $this->errorResponse(
            'Analytics export is not implemented yet.',
            Response::HTTP_NOT_IMPLEMENTED,
            'not_implemented',
            [
                'requested_format' => $request->input('format'),
                'requested_type' => $request->input('type'),
            ]
        );
    }

    /**
     * Get recent activity summary
     *
     * GET /su/portal/v1/admin/analytics/recent-activity
     */
    public function getRecentActivity(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'nullable|integer|min:5|max:100',
        ]);

        $limit = $request->integer('limit', 20);

        $activity = $this->dashboardService->getRecentActivity($limit);

        return $this->successResponse($activity, 'Recent activity retrieved successfully');
    }

    /**
     * Get performance metrics
     *
     * GET /su/portal/v1/admin/analytics/performance
     */
    public function getPerformance(): JsonResponse
    {
        $performance = $this->dashboardAnalyticsService->getPerformanceMetrics();

        return $this->successResponse($performance, 'Performance metrics retrieved successfully');
    }

    /**
     * Clear analytics cache
     *
     * POST /su/portal/v1/admin/analytics/cache/clear
     */
    public function clearCache(): JsonResponse
    {
        $this->analyticsService->clearCache();
        $this->dashboardService->clearCache();
        $this->dashboardAnalyticsService->clearCache();

        return $this->successResponse(null, 'Analytics cache cleared successfully');
    }

    /**
     * Get module-specific analytics helper
     *
     * @return array<string, mixed>
     */
    private function getModuleSpecificAnalytics(string $module, ?Carbon $startDate, ?Carbon $endDate): array
    {
        $contentStats = $this->analyticsService->getContentStats($startDate, $endDate);
        $engagement = $this->analyticsService->getEngagementMetrics($startDate, $endDate);

        return match ($module) {
            'articles' => [
                'stats' => $contentStats['by_module']['articles'] ?? [],
                'engagement' => $engagement['by_module']['articles'] ?? [],
                'top_content' => $this->analyticsService->getTopContent(10)['articles'] ?? [],
            ],
            'hadith' => [
                'stats' => $contentStats['by_module']['hadith'] ?? [],
                'engagement' => $engagement['by_module']['hadith'] ?? [],
                'top_content' => $this->analyticsService->getTopContent(10)['hadith'] ?? [],
            ],
            'media' => [
                'stats' => $contentStats['by_module']['media'] ?? [],
                'engagement' => $engagement['by_module']['media'] ?? [],
                'top_content' => $this->analyticsService->getTopContent(10)['media'] ?? [],
            ],
            'sahaba' => [
                'stats' => $contentStats['by_module']['sahaba'] ?? [],
            ],
            'seerah' => [
                'stats' => $contentStats['by_module']['seerah'] ?? [],
            ],
            'quran' => [
                'stats' => $contentStats['by_module']['quran'] ?? [],
                'top_reciters' => $this->analyticsService->getTopContent(10)['reciters'] ?? [],
            ],
            'genealogy' => [
                'stats' => $contentStats['by_module']['genealogy'] ?? [],
            ],
            default => [],
        };
    }
}
