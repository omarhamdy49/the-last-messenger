@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <!-- Page Title -->
    <div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="bar-chart-3" class="w-7 h-7 text-white"></i>
            </div>
            لوحة التحليلات
        </h1>
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            إحصائيات شاملة وتحليلات أداء المنصة
        </p>
    </div>

    <!-- Action Buttons -->
    <div class="flex items-center gap-3">
        <!-- Date Range Picker -->
        <div class="flex items-center gap-2 px-4 py-2.5 rounded-xl border"
             :class="darkMode ? 'border-gray-700 bg-gray-800' : 'border-gray-300 bg-white'">
            <i data-lucide="calendar" class="w-4 h-4" :class="darkMode ? 'text-gray-400' : 'text-gray-500'"></i>
            <input type="date" id="start_date"
                   class="bg-transparent border-none text-sm focus:outline-none"
                   :class="darkMode ? 'text-white' : 'text-gray-900'">
            <span :class="darkMode ? 'text-gray-500' : 'text-gray-400'">-</span>
            <input type="date" id="end_date"
                   class="bg-transparent border-none text-sm focus:outline-none"
                   :class="darkMode ? 'text-white' : 'text-gray-900'">
        </div>

        <!-- Refresh Button -->
        <button onclick="loadAllData()"
                class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2"
                :class="darkMode ? 'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
            <i data-lucide="refresh-cw" class="w-5 h-5"></i>
            <span class="hidden sm:inline">تحديث</span>
        </button>
    </div>
</div>
@endsection

@section('content')
<div x-data="analyticsDashboard()">

    <!-- Stats Cards Row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Content -->
        <div class="rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-1 animate-fadeInUp"
             :class="darkMode ? 'bg-gradient-to-br from-brand to-secondary text-white' : 'bg-gradient-to-br from-brand to-secondary text-white'">
            <div class="flex items-start justify-between mb-4">
                <div class="p-3 rounded-xl bg-white/20 group-hover:scale-110 transition-transform duration-300">
                    <i data-lucide="file-text" class="w-6 h-6 text-white"></i>
                </div>
            </div>
            <div class="mb-2">
                <h3 class="text-3xl font-bold text-white" x-text="stats.content?.total?.toLocaleString() || '...'">...</h3>
            </div>
            <p class="text-sm font-medium text-white/80">إجمالي المحتوى</p>
        </div>

        <!-- Total Views -->
        <div class="rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-1 animate-fadeInUp delay-100"
             :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white border border-gray-200'">
            <div class="flex items-start justify-between mb-4">
                <div class="p-3 rounded-xl bg-gradient-to-br from-brand to-secondary group-hover:scale-110 transition-transform duration-300">
                    <i data-lucide="eye" class="w-6 h-6 text-white"></i>
                </div>
            </div>
            <div class="mb-2">
                <h3 class="text-3xl font-bold gradient-text" x-text="stats.engagement?.total_views?.toLocaleString() || '...'">...</h3>
            </div>
            <p class="text-sm font-medium" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">إجمالي المشاهدات</p>
        </div>

        <!-- Total Users -->
        <div class="rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-1 animate-fadeInUp delay-200"
             :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white border border-gray-200'">
            <div class="flex items-start justify-between mb-4">
                <div class="p-3 rounded-xl bg-gradient-to-br from-brand to-secondary group-hover:scale-110 transition-transform duration-300">
                    <i data-lucide="users" class="w-6 h-6 text-white"></i>
                </div>
            </div>
            <div class="mb-2">
                <h3 class="text-3xl font-bold gradient-text" x-text="stats.users?.total?.toLocaleString() || '...'">...</h3>
            </div>
            <p class="text-sm font-medium" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">المستخدمون</p>
        </div>

        <!-- Active Modules -->
        <div class="rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-1 animate-fadeInUp delay-300"
             :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white border border-gray-200'">
            <div class="flex items-start justify-between mb-4">
                <div class="p-3 rounded-xl bg-gradient-to-br from-brand to-secondary group-hover:scale-110 transition-transform duration-300">
                    <i data-lucide="layout-grid" class="w-6 h-6 text-white"></i>
                </div>
            </div>
            <div class="mb-2">
                <h3 class="text-3xl font-bold gradient-text" x-text="Object.keys(stats.modules || {}).length || '...'">...</h3>
            </div>
            <p class="text-sm font-medium" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">الأقسام النشطة</p>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Growth Chart -->
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="trending-up" class="w-5 h-5 text-accent"></i>
                    نمو المحتوى
                </h2>
                <select id="growthPeriod" onchange="loadGrowthChart()"
                        class="px-3 py-1.5 rounded-lg text-sm border focus:ring-2 focus:ring-accent"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="daily">يومي</option>
                    <option value="weekly">أسبوعي</option>
                    <option value="monthly">شهري</option>
                </select>
            </div>
            <div class="h-64 flex items-center justify-center" id="growthChartContainer">
                <div class="text-center">
                    <div class="animate-spin w-8 h-8 border-4 border-accent border-t-transparent rounded-full mx-auto mb-3"></div>
                    <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">جاري تحميل البيانات...</p>
                </div>
            </div>
            <canvas id="growthChart" class="hidden"></canvas>
        </div>

        <!-- Distribution Chart -->
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="pie-chart" class="w-5 h-5 text-accent"></i>
                توزيع المحتوى
            </h2>
            <div class="h-64 flex items-center justify-center" id="distributionChartContainer">
                <div class="text-center">
                    <div class="animate-spin w-8 h-8 border-4 border-accent border-t-transparent rounded-full mx-auto mb-3"></div>
                    <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">جاري تحميل البيانات...</p>
                </div>
            </div>
            <canvas id="distributionChart" class="hidden"></canvas>
        </div>
    </div>

    <!-- Engagement & Activity Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Engagement Chart -->
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="activity" class="w-5 h-5 text-accent"></i>
                تفاعل المحتوى
            </h2>
            <div class="h-64 flex items-center justify-center" id="engagementChartContainer">
                <div class="text-center">
                    <div class="animate-spin w-8 h-8 border-4 border-accent border-t-transparent rounded-full mx-auto mb-3"></div>
                    <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">جاري تحميل البيانات...</p>
                </div>
            </div>
            <canvas id="engagementChart" class="hidden"></canvas>
        </div>

        <!-- Module Breakdown -->
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="layers" class="w-5 h-5 text-accent"></i>
                إحصائيات الأقسام
            </h2>

            <div class="space-y-4" id="moduleBreakdown">
                <template x-for="(data, module) in stats.modules || {}" :key="module">
                    <div class="flex items-center justify-between p-3 rounded-xl"
                         :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                        <div class="flex items-center gap-3">
                            <div class="w-3 h-3 rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838]"></div>
                            <span class="font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="module"></span>
                        </div>
                        <span class="font-bold text-accent" x-text="(data.count || data.total || 0).toLocaleString()"></span>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Top Content & Recent Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top Content -->
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="trophy" class="w-5 h-5 text-accent"></i>
                المحتوى الأكثر مشاهدة
            </h2>

            <div class="space-y-3" id="topContentList">
                <template x-for="(item, index) in topContent" :key="index">
                    <div class="flex items-center gap-4 p-3 rounded-xl transition-all hover:scale-[1.01]"
                         :class="darkMode ? 'bg-gray-800/50 hover:bg-gray-800' : 'bg-gray-50 hover:bg-gray-100'">
                        <span class="w-8 h-8 rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838] flex items-center justify-center text-white text-sm font-bold"
                              x-text="index + 1"></span>
                        <div class="flex-1 min-w-0">
                            <h4 class="font-semibold truncate" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="item.title"></h4>
                            <p class="text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-500'" x-text="item.type"></p>
                        </div>
                        <span class="text-sm font-bold text-accent" x-text="(item.views || 0).toLocaleString()"></span>
                    </div>
                </template>
                <div x-show="topContent.length === 0" class="text-center py-6">
                    <p class="text-sm" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">جاري تحميل البيانات...</p>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="clock" class="w-5 h-5 text-accent"></i>
                النشاط الأخير
            </h2>

            <div class="space-y-3" id="recentActivityList">
                <template x-for="(activity, index) in recentActivity" :key="index">
                    <div class="flex items-center gap-3 p-3 rounded-xl"
                         :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                        <div class="w-2 h-2 rounded-full"
                             :class="{
                                 'bg-green-500': activity.action === 'created',
                                 'bg-blue-500': activity.action === 'updated',
                                 'bg-red-500': activity.action === 'deleted',
                                 'bg-gray-500': !['created','updated','deleted'].includes(activity.action)
                             }"></div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium truncate" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="activity.description"></p>
                            <p class="text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-400'" x-text="activity.time"></p>
                        </div>
                    </div>
                </template>
                <div x-show="recentActivity.length === 0" class="text-center py-6">
                    <p class="text-sm" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">جاري تحميل البيانات...</p>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function analyticsDashboard() {
        return {
            stats: {},
            topContent: [],
            recentActivity: [],

            init() {
                this.loadAllData();
            },

            async loadAllData() {
                await Promise.all([
                    this.loadStats(),
                    this.loadTopContent(),
                    this.loadRecentActivity(),
                ]);

                this.loadCharts();
            },

            async loadStats() {
                try {
                    const params = new URLSearchParams();
                    const startDate = document.getElementById('start_date')?.value;
                    const endDate = document.getElementById('end_date')?.value;
                    if (startDate) params.set('start_date', startDate);
                    if (endDate) params.set('end_date', endDate);

                    const response = await fetch(`{{ route('admin.analytics.stats') }}?${params}`, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const result = await response.json();
                    if (result.success) {
                        this.stats = result.data;
                    }
                } catch (error) {
                    console.error('Failed to load stats:', error);
                }
            },

            async loadTopContent() {
                try {
                    const response = await fetch(`{{ route('admin.analytics.top-content') }}?limit=5`, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const result = await response.json();
                    if (result.success) {
                        this.topContent = Array.isArray(result.data) ? result.data : [];
                    }
                } catch (error) {
                    console.error('Failed to load top content:', error);
                }
            },

            async loadRecentActivity() {
                try {
                    const response = await fetch(`{{ route('admin.analytics.recent-activity') }}?limit=10`, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const result = await response.json();
                    if (result.success) {
                        this.recentActivity = Array.isArray(result.data) ? result.data : [];
                    }
                } catch (error) {
                    console.error('Failed to load recent activity:', error);
                }
            },

            async loadCharts() {
                // Charts will be loaded when Chart.js is available
                // Placeholder for chart initialization
                ['growthChartContainer', 'distributionChartContainer', 'engagementChartContainer'].forEach(id => {
                    const container = document.getElementById(id);
                    if (container) {
                        container.innerHTML = '<p class="text-sm text-center" style="color: var(--text-muted)">سيتم تحميل الرسم البياني عند توفر البيانات</p>';
                    }
                });
            }
        };
    }

    function loadAllData() {
        const dashboard = document.querySelector('[x-data]').__x;
        if (dashboard && dashboard.$data) {
            dashboard.$data.loadAllData();
        }
    }

    function loadGrowthChart() {
        // Triggered when growth period changes
    }

    document.addEventListener('DOMContentLoaded', () => {
        lucide.createIcons();
    });
</script>
@endpush
