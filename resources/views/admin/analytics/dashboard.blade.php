@extends('layouts.admin')

@section('title', 'لوحة التحليلات المتقدمة')
@section('page-title', 'لوحة التحليلات المتقدمة')

@push('styles')
<style>
    /* Chart Container Styles */
    .chart-container {
        position: relative;
        height: 300px;
        width: 100%;
    }

    .chart-card {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    [x-cloak] { display: none; }

    .dark .chart-card {
        background: rgba(31, 41, 55, 0.95);
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(75, 85, 99, 0.2);
    }

    .chart-card:hover {
        box-shadow: 0 10px 25px rgba(0, 86, 179, 0.15);
        transform: translateY(-2px);
    }

    .dark .chart-card:hover {
        box-shadow: 0 10px 25px rgba(0, 86, 179, 0.25);
    }

    /* Statistics Card Styles */
    .stat-card {
        background: linear-gradient(135deg, #0056b3 0%, #218838 100%);
        color: white;
        border-radius: 12px;
        padding: 24px;
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 100px;
        height: 100px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        transform: translate(30%, -30%);
    }

    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 20px 40px rgba(0, 86, 179, 0.25);
    }

    .stat-value {
        font-size: 2.5rem;
        font-weight: bold;
        margin: 12px 0;
    }

    .stat-change {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 600;
    }

    .stat-change.positive {
        background: rgba(16, 185, 129, 0.2);
        color: #10B981;
    }

    .stat-change.negative {
        background: rgba(239, 68, 68, 0.2);
        color: #EF4444;
    }

    /* Table Styles */
    .analytics-table {
        width: 100%;
        border-collapse: collapse;
    }

    .analytics-table thead th {
        background: linear-gradient(135deg, #0056b3 0%, #218838 100%);
        color: white;
        padding: 12px 16px;
        text-align: right;
        font-weight: 600;
        font-size: 0.875rem;
        transition: background 0.3s ease;
    }

    .dark .analytics-table thead th {
        background: linear-gradient(135deg, rgba(0, 86, 179, 0.8) 0%, rgba(33, 136, 56, 0.8) 100%);
        border-bottom: 2px solid rgba(75, 85, 99, 0.3);
    }

    .analytics-table tbody td {
        padding: 12px 16px;
        border-bottom: 1px solid #E5E7EB;
        font-size: 0.875rem;
        color: #1F2937;
        transition: background 0.2s ease;
    }

    .dark .analytics-table tbody td {
        border-bottom-color: rgba(75, 85, 99, 0.2);
        color: #E5E7EB;
    }

    .analytics-table tbody tr:hover {
        background: #F3F4F6;
    }

    .dark .analytics-table tbody tr:hover {
        background: rgba(75, 85, 99, 0.2);
    }

    /* Filter Styles */
    .filter-group {
        display: flex;
        gap: 12px;
        align-items: center;
        flex-wrap: wrap;
    }

    .filter-label {
        font-size: 0.875rem;
        font-weight: 500;
        color: #374151;
        transition: color 0.3s ease;
    }

    .dark .filter-label {
        color: #D1D5DB;
    }

    .filter-input {
        padding: 8px 16px;
        border: 1px solid #D1D5DB;
        border-radius: 8px;
        font-size: 0.875rem;
        background: white;
        color: #1F2937;
        transition: all 0.3s ease;
    }

    .dark .filter-input {
        background: rgba(31, 41, 55, 0.8);
        border-color: rgba(75, 85, 99, 0.4);
        color: #E5E7EB;
    }

    .filter-input:focus {
        outline: none;
        border-color: #0056b3;
        box-shadow: 0 0 0 3px rgba(0, 86, 179, 0.1);
    }

    .dark .filter-input:focus {
        box-shadow: 0 0 0 3px rgba(0, 86, 179, 0.3);
    }

    /* Loading State */
    .skeleton {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: loading 1.5s infinite;
    }

    .dark .skeleton {
        background: linear-gradient(90deg, rgba(75, 85, 99, 0.3) 25%, rgba(75, 85, 99, 0.5) 50%, rgba(75, 85, 99, 0.3) 75%);
        background-size: 200% 100%;
        animation: loading 1.5s infinite;
    }

    @keyframes loading {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }

    /* Module Cards */
    .module-card {
        border-left: 4px solid;
        transition: all 0.3s ease;
        background: white;
        border-radius: 8px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }

    .dark .module-card {
        background: rgba(31, 41, 55, 0.95);
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        border-left-color: var(--module-color, #0056b3);
    }

    .module-card:hover {
        transform: translateX(-4px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .dark .module-card:hover {
        box-shadow: 0 4px 12px rgba(0, 86, 179, 0.2);
    }

    .module-card-label {
        color: #6B7280;
        font-size: 0.875rem;
    }

    .dark .module-card-label {
        color: #9CA3AF;
    }

    .module-card-title {
        color: #1F2937;
        font-size: 1.5rem;
        font-weight: bold;
        margin-top: 0.25rem;
    }

    .dark .module-card-title {
        color: #F3F4F6;
    }

    .module-card-stat {
        color: #6B7280;
        font-size: 0.75rem;
    }

    .dark .module-card-stat {
        color: #9CA3AF;
    }

    .module-card-stat-value {
        color: #1F2937;
        font-weight: 500;
    }

    .dark .module-card-stat-value {
        color: #E5E7EB;
    }

    /* Loading Overlay */
    .loading-overlay {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(4px);
    }

    .dark .loading-overlay {
        background: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(4px);
    }

    .loading-spinner-bg {
        border-color: rgba(0, 86, 179, 0.2);
        border-top-color: #0056b3;
        border-right-color: #0056b3;
    }

    .loading-text {
        color: #374151;
    }

    .dark .loading-text {
        color: #F3F4F6;
    }
</style>
@endpush

@section('content')
<div class="px-6 py-8" x-data="analyticsData()" x-cloak>
    <!-- Header with Filters -->
    <div class="mb-8 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold mb-2 transition-colors duration-300" :class="darkMode ? 'text-gray-100' : 'text-gray-900'">لوحة التحليلات الشاملة</h1>
            <p class="transition-colors duration-300" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">تحليلات متقدمة لجميع محتويات المنصة</p>
        </div>

        <!-- Date Range Filter -->
        <div class="filter-group">
            <div class="flex items-center gap-2">
                <label class="filter-label">من:</label>
                <input
                    type="date"
                    x-model="startDate"
                    @change="loadAnalytics()"
                    class="filter-input"
                >
            </div>
            <div class="flex items-center gap-2">
                <label class="filter-label">إلى:</label>
                <input
                    type="date"
                    x-model="endDate"
                    @change="loadAnalytics()"
                    class="filter-input"
                >
            </div>
            <button
                @click="clearFilters()"
                class="px-4 py-2 rounded-lg transition text-sm font-medium"
                :class="darkMode ? 'bg-gray-700 text-gray-200 hover:bg-gray-600' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'">
                <i data-lucide="x" class="w-4 h-4 inline ml-1"></i>
                مسح الفلاتر
            </button>
            <button
                @click="refreshData()"
                class="px-4 py-2 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white rounded-lg hover:shadow-lg transition text-sm font-medium">
                <i data-lucide="refresh-cw" class="w-4 h-4 inline ml-1"></i>
                تحديث
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Content -->
        <div class="stat-card" style="background: linear-gradient(135deg, #0056b3 0%, #218838 100%);">
            <div class="relative z-10">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white/80 text-sm font-medium">إجمالي المحتوى</p>
                        <div class="stat-value" x-text="stats.content?.total_content || 0"></div>
                        <div class="stat-change positive" x-show="stats.changes?.content_change >= 0">
                            <i data-lucide="trending-up" class="w-4 h-4"></i>
                            <span x-text="'+' + (stats.changes?.content_change || 0) + '%'"></span>
                        </div>
                    </div>
                    <div class="p-4 bg-white/20 rounded-full">
                        <i data-lucide="database" class="w-8 h-8 text-white"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Views -->
        <div class="stat-card" style="background: linear-gradient(135deg, #64CCC5 0%, #0056b3 100%);">
            <div class="relative z-10">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white/80 text-sm font-medium">إجمالي المشاهدات</p>
                        <div class="stat-value" x-text="formatNumber(stats.engagement?.total_views || 0)"></div>
                        <div class="stat-change positive" x-show="stats.changes?.views_change >= 0">
                            <i data-lucide="eye" class="w-4 h-4"></i>
                            <span x-text="'+' + (stats.changes?.views_change || 0) + '%'"></span>
                        </div>
                    </div>
                    <div class="p-4 bg-white/20 rounded-full">
                        <i data-lucide="eye" class="w-8 h-8 text-white"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Users -->
        <div class="stat-card" style="background: linear-gradient(135deg, #218838 0%, #64CCC5 100%);">
            <div class="relative z-10">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white/80 text-sm font-medium">إجمالي المستخدمين</p>
                        <div class="stat-value" x-text="stats.users?.total || 0"></div>
                        <div class="stat-change positive" x-show="stats.changes?.users_change >= 0">
                            <i data-lucide="users" class="w-4 h-4"></i>
                            <span x-text="'+' + (stats.changes?.users_change || 0) + '%'"></span>
                        </div>
                    </div>
                    <div class="p-4 bg-white/20 rounded-full">
                        <i data-lucide="users" class="w-8 h-8 text-white"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Engagement Rate -->
        <div class="stat-card" style="background: linear-gradient(135deg, #F59E0B 0%, #10B981 100%);">
            <div class="relative z-10">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white/80 text-sm font-medium">معدل التفاعل</p>
                        <div class="stat-value" x-text="(stats.engagement?.engagement_rate || 0) + '%'"></div>
                        <p class="text-white/60 text-xs mt-1">مشاهدات لكل محتوى</p>
                    </div>
                    <div class="p-4 bg-white/20 rounded-full">
                        <i data-lucide="activity" class="w-8 h-8 text-white"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Content Growth Chart -->
        <div class="chart-card">
            <h3 class="text-lg font-bold mb-4 flex items-center transition-colors duration-300" :class="darkMode ? 'text-gray-100' : 'text-gray-900'">
                <i data-lucide="trending-up" class="w-5 h-5 ml-2 text-[#0056b3]"></i>
                نمو المحتوى (آخر 30 يوم)
            </h3>
            <div class="chart-container">
                <canvas id="contentGrowthChart"></canvas>
            </div>
        </div>

        <!-- Module Distribution Chart -->
        <div class="chart-card">
            <h3 class="text-lg font-bold mb-4 flex items-center transition-colors duration-300" :class="darkMode ? 'text-gray-100' : 'text-gray-900'">
                <i data-lucide="pie-chart" class="w-5 h-5 ml-2 text-[#218838]"></i>
                توزيع المحتوى حسب النوع
            </h3>
            <div class="chart-container">
                <canvas id="distributionChart"></canvas>
            </div>
        </div>

        <!-- Engagement Chart -->
        <div class="chart-card">
            <h3 class="text-lg font-bold mb-4 flex items-center transition-colors duration-300" :class="darkMode ? 'text-gray-100' : 'text-gray-900'">
                <i data-lucide="bar-chart" class="w-5 h-5 ml-2 text-[#64CCC5]"></i>
                تفاعل المستخدمين
            </h3>
            <div class="chart-container">
                <canvas id="engagementChart"></canvas>
            </div>
        </div>

        <!-- Activity Heatmap -->
        <div class="chart-card">
            <h3 class="text-lg font-bold mb-4 flex items-center transition-colors duration-300" :class="darkMode ? 'text-gray-100' : 'text-gray-900'">
                <i data-lucide="activity" class="w-5 h-5 ml-2 text-[#F59E0B]"></i>
                خريطة النشاط
            </h3>
            <div class="chart-container">
                <canvas id="activityChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Module Breakdown -->
    <div class="mb-8">
        <h2 class="text-2xl font-bold mb-6 transition-colors duration-300" :class="darkMode ? 'text-gray-100' : 'text-gray-900'">تحليل الوحدات</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <template x-for="module in stats.modules" :key="module.slug">
                <div class="module-card rounded-lg p-6" :style="'--module-color: ' + (module.color || '#0056b3')" :class="darkMode ? 'bg-gray-800/60 border-gray-700' : 'bg-white'">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <p class="module-card-label" x-text="module.name"></p>
                            <p class="module-card-title" x-text="module.count"></p>
                        </div>
                        <div class="p-3 rounded-lg" :style="'background: ' + module.color + '20'">
                            <i :data-lucide="module.icon" class="w-6 h-6" :style="'color: ' + module.color"></i>
                        </div>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="module-card-stat">إضافات حديثة:</span>
                        <span class="module-card-stat-value" x-text="module.recent_count"></span>
                    </div>
                    <div class="mt-2 flex items-center text-xs">
                        <span class="module-card-stat">آخر نشاط:</span>
                        <span class="module-card-stat-value mr-2" x-text="module.last_activity || 'لا يوجد'"></span>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Top Content Tables -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Top Articles -->
        <div class="chart-card">
            <h3 class="text-lg font-bold mb-4 flex items-center justify-between transition-colors duration-300" :class="darkMode ? 'text-gray-100' : 'text-gray-900'">
                <span class="flex items-center">
                    <i data-lucide="book-open" class="w-5 h-5 ml-2 text-[#0056b3]"></i>
                    المقالات الأكثر مشاهدة
                </span>
                <span class="text-sm font-normal transition-colors duration-300" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">أعلى 10</span>
            </h3>
            <div class="overflow-x-auto">
                <table class="analytics-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>العنوان</th>
                            <th>المشاهدات</th>
                            <th>المفضلة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(article, index) in topContent.articles" :key="article.id">
                            <tr>
                                <td x-text="index + 1"></td>
                                <td class="font-medium" x-text="article.title"></td>
                                <td x-text="formatNumber(article.views)"></td>
                                <td x-text="article.favorites"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Hadith -->
        <div class="chart-card">
            <h3 class="text-lg font-bold mb-4 flex items-center justify-between transition-colors duration-300" :class="darkMode ? 'text-gray-100' : 'text-gray-900'">
                <span class="flex items-center">
                    <i data-lucide="book" class="w-5 h-5 ml-2 text-[#218838]"></i>
                    الأحاديث الأكثر مشاهدة
                </span>
                <span class="text-sm font-normal transition-colors duration-300" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">أعلى 10</span>
            </h3>
            <div class="overflow-x-auto">
                <table class="analytics-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>النص</th>
                            <th>الكتاب</th>
                            <th>المشاهدات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(hadith, index) in topContent.hadith" :key="hadith.id">
                            <tr>
                                <td x-text="index + 1"></td>
                                <td class="font-medium" x-text="hadith.text"></td>
                                <td x-text="hadith.book"></td>
                                <td x-text="formatNumber(hadith.views)"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div x-show="loading" class="loading-overlay fixed inset-0 flex items-center justify-center z-50">
        <div class="rounded-lg p-8 flex flex-col items-center transition-colors duration-300" :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white'">
            <div class="loading-spinner-bg animate-spin rounded-full h-16 w-16 mb-4"></div>
            <p class="loading-text font-medium">جاري تحميل البيانات...</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
function analyticsData() {
    return {
        loading: false,
        darkMode: localStorage.getItem('darkMode') !== 'false',
        startDate: null,
        endDate: null,
        stats: {
            content: {},
            engagement: {},
            users: {},
            modules: [],
            changes: {}
        },
        topContent: {
            articles: [],
            hadith: []
        },
        charts: {
            growth: null,
            distribution: null,
            engagement: null,
            activity: null
        },

        init() {
            // Initialize dark mode state
            this.initDarkMode();
            this.loadAnalytics();
        },

        initDarkMode() {
            // Sync with document dark class
            if (document.documentElement.classList.contains('dark')) {
                this.darkMode = true;
            }

            // Watch for external dark mode changes
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.attributeName === 'class') {
                        this.darkMode = document.documentElement.classList.contains('dark');
                    }
                });
            });

            observer.observe(document.documentElement, {
                attributes: true,
                attributeFilter: ['class']
            });
        },

        async loadAnalytics() {
            this.loading = true;
            try {
                // Load statistics
                await this.loadStats();
                // Load top content
                await this.loadTopContent();
                // Load charts
                await this.loadCharts();
            } catch (error) {
                console.error('Error loading analytics:', error);
            } finally {
                this.loading = false;
                // Reinitialize Lucide icons
                this.$nextTick(() => lucide.createIcons());
            }
        },

        async loadStats() {
            const params = new URLSearchParams();
            if (this.startDate) params.append('start_date', this.startDate);
            if (this.endDate) params.append('end_date', this.endDate);

            const response = await fetch(`/su/portal/v1/admin/analytics/stats?${params}`);
            const data = await response.json();

            if (data.success) {
                this.stats = data.data;
            }
        },

        async loadTopContent() {
            const response = await fetch('/su/portal/v1/admin/analytics/top-content?limit=10');
            const data = await response.json();

            if (data.success) {
                this.topContent = data.data;
            }
        },

        async loadCharts() {
            await Promise.all([
                this.loadGrowthChart(),
                this.loadDistributionChart(),
                this.loadEngagementChart(),
                this.loadActivityChart()
            ]);
        },

        async loadGrowthChart() {
            const response = await fetch('/su/portal/v1/admin/analytics/charts/growth?period=daily&limit=30');
            const data = await response.json();

            if (data.success && data.data.trends) {
                this.renderGrowthChart(data.data.trends);
            }
        },

        async loadDistributionChart() {
            const response = await fetch('/su/portal/v1/admin/analytics/charts/distribution');
            const data = await response.json();

            if (data.success) {
                this.renderDistributionChart(data.data);
            }
        },

        async loadEngagementChart() {
            const response = await fetch('/su/portal/v1/admin/analytics/charts/engagement?limit=30');
            const data = await response.json();

            if (data.success) {
                this.renderEngagementChart(data.data);
            }
        },

        async loadActivityChart() {
            const response = await fetch('/su/portal/v1/admin/analytics/charts/activity?limit=30');
            const data = await response.json();

            if (data.success) {
                this.renderActivityChart(data.data);
            }
        },

        renderGrowthChart(trends) {
            const ctx = document.getElementById('contentGrowthChart');
            if (!ctx) return;

            // Destroy existing chart
            if (this.charts.growth) {
                this.charts.growth.destroy();
            }

            // Prepare datasets
            const labels = trends.articles?.map(t => t.period) || [];

            this.charts.growth = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'مقالات',
                            data: trends.articles?.map(t => t.count) || [],
                            borderColor: '#0056b3',
                            backgroundColor: 'rgba(0, 86, 179, 0.1)',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'أحاديث',
                            data: trends.hadith?.map(t => t.count) || [],
                            borderColor: '#218838',
                            backgroundColor: 'rgba(33, 136, 56, 0.1)',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'برامج',
                            data: trends.media?.map(t => t.count) || [],
                            borderColor: '#64CCC5',
                            backgroundColor: 'rgba(100, 204, 197, 0.1)',
                            tension: 0.4,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            align: 'end'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        },

        renderDistributionChart(data) {
            const ctx = document.getElementById('distributionChart');
            if (!ctx) return;

            if (this.charts.distribution) {
                this.charts.distribution.destroy();
            }

            this.charts.distribution = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: data.labels || [],
                    datasets: [{
                        data: data.data || [],
                        backgroundColor: data.colors || [],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            });
        },

        renderEngagementChart(data) {
            const ctx = document.getElementById('engagementChart');
            if (!ctx) return;

            if (this.charts.engagement) {
                this.charts.engagement.destroy();
            }

            this.charts.engagement = new Chart(ctx, {
                type: 'bar',
                data: data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        },

        renderActivityChart(data) {
            const ctx = document.getElementById('activityChart');
            if (!ctx) return;

            if (this.charts.activity) {
                this.charts.activity.destroy();
            }

            this.charts.activity = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.map(d => d.date) || [],
                    datasets: [{
                        label: 'نشاط',
                        data: data.map(d => d.count) || [],
                        borderColor: '#F59E0B',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        },

        clearFilters() {
            this.startDate = null;
            this.endDate = null;
            this.loadAnalytics();
        },

        refreshData() {
            this.loadAnalytics();
        },

        formatNumber(num) {
            return new Intl.NumberFormat('ar-EG').format(num);
        }
    }
}
</script>
@endpush
