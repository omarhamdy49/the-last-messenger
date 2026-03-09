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
            'engagement' => $this->getEngagementChartData($limit),
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

        // TODO: Implement export using Laravel Excel
        // This would integrate with the existing ExportController/ExportService

        return $this->successResponse([
            'message' => 'Export functionality coming soon',
            'requested_format' => $request->input('format'),
            'requested_type' => $request->input('type'),
        ], 'Export request received');
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
     * Get engagement chart data helper
     *
     * @return array<string, mixed>
     */
    private function getEngagementChartData(int $days): array
    {
        $labels = [];
        $articlesViews = [];
        $hadithViews = [];
        $mediaViews = [];

        // This is a simplified version - in production you'd query actual view data per day
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $labels[] = $date;

            // Placeholder data - replace with actual queries
            $articlesViews[] = rand(100, 1000);
            $hadithViews[] = rand(50, 500);
            $mediaViews[] = rand(200, 800);
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Articles Views',
                    'data' => $articlesViews,
                    'borderColor' => '#0056b3',
                    'backgroundColor' => 'rgba(0, 86, 179, 0.1)',
                ],
                [
                    'label' => 'Hadith Views',
                    'data' => $hadithViews,
                    'borderColor' => '#218838',
                    'backgroundColor' => 'rgba(33, 136, 56, 0.1)',
                ],
                [
                    'label' => 'Media Views',
                    'data' => $mediaViews,
                    'borderColor' => '#64CCC5',
                    'backgroundColor' => 'rgba(100, 204, 197, 0.1)',
                ],
            ],
        ];
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
