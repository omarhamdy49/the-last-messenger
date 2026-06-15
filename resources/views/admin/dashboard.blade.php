@extends('layouts.admin')

@section('title', __('dashboard.title'))

@section('page-header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold mb-2"
                :class="darkMode ? 'text-white' : 'text-gray-900'">
                {{ __('dashboard.welcome_back', ['name' => auth()->user()->name ?? __('admin.admin')]) }}
            </h1>
            <p class="text-sm"
               :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                {{ __('dashboard.subtitle') }}
            </p>
        </div>

        <div class="flex items-center gap-3">
            <!-- Quick Actions -->
            <button class="px-4 py-2 rounded-lg font-semibold text-sm transition-colors duration-200"
                    :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200'">
                <i data-lucide="filter" class="w-4 h-4 inline {{ app()->getLocale() === 'ar' ? 'ms-2' : 'me-2' }}"></i>
                {{ __('admin.filter') }}
            </button>

            <a href="{{ route('admin.articles.create') }}" class="px-4 py-2 rounded-lg bg-gradient-to-r from-brand to-secondary text-white font-semibold text-sm hover:shadow-lg transition-all duration-200">
                <i data-lucide="plus" class="w-4 h-4 inline {{ app()->getLocale() === 'ar' ? 'ms-2' : 'me-2' }}"></i>
                {{ __('dashboard.new_content') }}
            </a>
        </div>
    </div>
@endsection

@section('content')
    {{-- Stats Cards Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {{-- Total Users --}}
        <x-admin.stats-card
            icon="users"
            :title="__('dashboard.stats.total_users')"
            :value="$stats['total_users'] ?? 0"
            :change="isset($stats['users_change']) ? ($stats['users_change'] > 0 ? '+' : '') . $stats['users_change'] . '%' : null"
            :changeType="isset($stats['users_change']) && $stats['users_change'] > 0 ? 'up' : 'down'"
            :gradient="true"
            delay="delay-0"
            href="{{ route('admin.users.index') }}"
        />

        {{-- Total Articles --}}
        <x-admin.stats-card
            icon="file-text"
            :title="__('dashboard.stats.total_articles')"
            :value="$stats['total_articles'] ?? 0"
            :change="isset($stats['articles_change']) ? ($stats['articles_change'] > 0 ? '+' : '') . $stats['articles_change'] . '%' : null"
            :changeType="isset($stats['articles_change']) && $stats['articles_change'] > 0 ? 'up' : 'down'"
            delay="delay-100"
            href="{{ route('admin.articles.index') }}"
        />

        {{-- Media Items --}}
        <x-admin.stats-card
            icon="play-circle"
            :title="__('dashboard.stats.media_items')"
            :value="$stats['media_items'] ?? 0"
            :change="isset($stats['media_change']) ? ($stats['media_change'] > 0 ? '+' : '') . $stats['media_change'] . '%' : null"
            :changeType="isset($stats['media_change']) && $stats['media_change'] > 0 ? 'up' : 'down'"
            delay="delay-200"
            href="{{ route('admin.media-programs.index') }}"
        />

        {{-- Total Views --}}
        <x-admin.stats-card
            icon="eye"
            :title="__('dashboard.stats.total_views')"
            :value="$stats['total_views'] ?? 0"
            :change="isset($stats['views_change']) ? ($stats['views_change'] > 0 ? '+' : '') . $stats['views_change'] . '%' : null"
            :changeType="isset($stats['views_change']) && $stats['views_change'] > 0 ? 'up' : 'down'"
            delay="delay-300"
        />
    </div>

    {{-- Additional Stats Row --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {{-- Hadith Entries --}}
        <x-admin.stats-card
            icon="book-open"
            :title="__('dashboard.stats.hadith_entries')"
            :value="$stats['hadith_entries'] ?? 0"
            :change="isset($stats['hadith_change']) ? ($stats['hadith_change'] > 0 ? '+' : '') . $stats['hadith_change'] . '%' : null"
            :changeType="isset($stats['hadith_change']) && $stats['hadith_change'] > 0 ? 'up' : 'down'"
            delay="delay-0"
        />

        {{-- Quran Verses --}}
        <x-admin.stats-card
            icon="book"
            :title="__('dashboard.stats.quran_verses')"
            :value="$stats['quran_verses'] ?? 6236"
            delay="delay-100"
        />

        {{-- Sahaba Profiles --}}
        <x-admin.stats-card
            icon="user-circle"
            :title="__('dashboard.stats.sahaba_profiles')"
            :value="$stats['sahaba_profiles'] ?? 0"
            :change="isset($stats['sahaba_change']) ? ($stats['sahaba_change'] > 0 ? '+' : '') . $stats['sahaba_change'] . '%' : null"
            :changeType="isset($stats['sahaba_change']) && $stats['sahaba_change'] > 0 ? 'up' : 'down'"
            delay="delay-200"
        />

        {{-- Seerah Events --}}
        <x-admin.stats-card
            icon="calendar"
            :title="__('dashboard.stats.seerah_events')"
            :value="$stats['seerah_events'] ?? 0"
            :change="isset($stats['seerah_change']) ? ($stats['seerah_change'] > 0 ? '+' : '') . $stats['seerah_change'] . '%' : null"
            :changeType="isset($stats['seerah_change']) && $stats['seerah_change'] > 0 ? 'up' : 'down'"
            delay="delay-300"
        />
    </div>

    {{-- Charts Section --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        {{-- User Growth Chart --}}
        <x-admin.chart-card
            :title="__('dashboard.charts.user_growth')"
            :subtitle="__('dashboard.charts.user_growth_subtitle')"
            chartId="userGrowthChart"
            height="350px"
            :periods="[
                __('dashboard.periods.7d'),
                __('dashboard.periods.30d'),
                __('dashboard.periods.3m'),
                __('dashboard.periods.6m'),
                __('dashboard.periods.1y')
            ]"
            :defaultPeriod="__('dashboard.periods.6m')"
            delay="delay-100"
        >
            <x-slot name="statistics">
                <div class="text-center">
                    <p class="text-2xl font-bold gradient-text">{{ $stats['new_users'] ?? 0 }}</p>
                    <p class="text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('dashboard.stats.new_users') }}</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold gradient-text">{{ $stats['active_users'] ?? 0 }}</p>
                    <p class="text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('dashboard.stats.active_users') }}</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold gradient-text">{{ $stats['retention_rate'] ?? 0 }}%</p>
                    <p class="text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('dashboard.stats.retention') }}</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold gradient-text">{{ $stats['avg_session'] ?? 0 }}</p>
                    <p class="text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('dashboard.stats.avg_session') }}</p>
                </div>
            </x-slot>
        </x-admin.chart-card>

        {{-- Content Performance Chart --}}
        <x-admin.chart-card
            :title="__('dashboard.charts.content_performance')"
            :subtitle="__('dashboard.charts.content_performance_subtitle')"
            chartId="contentPerformanceChart"
            height="350px"
            :periods="[
                __('dashboard.periods.today'),
                __('dashboard.periods.week'),
                __('dashboard.periods.month')
            ]"
            :defaultPeriod="__('dashboard.periods.week')"
            delay="delay-200"
        >
            <x-slot name="actions">
                <button class="p-2 rounded-lg transition-colors duration-200 hover:bg-gray-100 dark:hover:bg-gray-800"
                        :class="darkMode ? 'text-gray-400' : 'text-gray-600'"
                        title="{{ __('dashboard.compare') }}">
                    <i data-lucide="git-compare" class="w-5 h-5"></i>
                </button>
            </x-slot>

            <x-slot name="statistics">
                <div class="text-center">
                    <p class="text-2xl font-bold text-blue-500">{{ format_arabic_number($stats['hadith_views'] ?? 0) }}</p>
                    <p class="text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('hadith.title') }}</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold text-green-500">{{ format_arabic_number($stats['articles_views'] ?? 0) }}</p>
                    <p class="text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('articles.title') }}</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold text-purple-500">{{ format_arabic_number($stats['quran_views'] ?? 0) }}</p>
                    <p class="text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('quran.title') }}</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold text-orange-500">{{ format_arabic_number($stats['media_views'] ?? 0) }}</p>
                    <p class="text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('media.title') }}</p>
                </div>
            </x-slot>
        </x-admin.chart-card>
    </div>

    {{-- Activity Feed and Quick Stats --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Activity Feed (2 columns on large screens) --}}
        <div class="lg:col-span-2">
            <x-admin.activity-feed
                :title="__('dashboard.recent_activity')"
                :activities="$activities ?? []"
                :maxItems="8"
                :showViewAll="true"
                viewAllUrl="{{ route('admin.activity-logs.index') }}"
                delay="delay-100"
            />
        </div>

        {{-- Quick Stats Sidebar --}}
        <div class="space-y-6">
            {{-- System Status --}}
            <div class="rounded-2xl shadow-lg p-6 animate-fadeInUp delay-200"
                 :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white border border-gray-200'">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-2 rounded-lg bg-gradient-to-br from-brand to-secondary">
                        <i data-lucide="server" class="w-5 h-5 text-white"></i>
                    </div>
                    <h3 class="text-lg font-bold"
                        :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ __('dashboard.system_status.title') }}
                    </h3>
                </div>

                <div class="space-y-4">
                    {{-- API Status --}}
                    <div class="flex items-center justify-between">
                        <span class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('dashboard.system_status.api') }}</span>
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
                            <span class="text-sm font-medium text-green-500">{{ __('dashboard.system_status.operational') }}</span>
                        </div>
                    </div>

                    {{-- Database Status --}}
                    <div class="flex items-center justify-between">
                        <span class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('dashboard.system_status.database') }}</span>
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
                            <span class="text-sm font-medium text-green-500">{{ __('dashboard.system_status.operational') }}</span>
                        </div>
                    </div>

                    {{-- Storage Status --}}
                    <div class="flex items-center justify-between">
                        <span class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('dashboard.system_status.storage') }}</span>
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full {{ ($stats['storage_percent'] ?? 0) > 80 ? 'bg-red-500' : 'bg-yellow-500' }}"></div>
                            <span class="text-sm font-medium {{ ($stats['storage_percent'] ?? 0) > 80 ? 'text-red-500' : 'text-yellow-500' }}">{{ $stats['storage_percent'] ?? 0 }}% {{ __('dashboard.system_status.used') }}</span>
                        </div>
                    </div>

                    {{-- CDN Status --}}
                    <div class="flex items-center justify-between">
                        <span class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('dashboard.system_status.cdn') }}</span>
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
                            <span class="text-sm font-medium text-green-500">{{ __('dashboard.system_status.operational') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Quick Links --}}
            <div class="rounded-2xl shadow-lg p-6 animate-fadeInUp delay-300"
                 :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white border border-gray-200'">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-2 rounded-lg bg-gradient-to-br from-brand to-secondary">
                        <i data-lucide="zap" class="w-5 h-5 text-white"></i>
                    </div>
                    <h3 class="text-lg font-bold"
                        :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ __('dashboard.quick_links.title') }}
                    </h3>
                </div>

                <div class="space-y-2">
                    <a href="{{ route('admin.articles.create') }}"
                       class="flex items-center gap-3 p-3 rounded-lg transition-colors duration-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                        <i data-lucide="file-plus" class="w-4 h-4 text-brand"></i>
                        <span class="text-sm font-medium"
                              :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('dashboard.quick_links.new_article') }}</span>
                    </a>

                    <a href="{{ route('admin.media-programs.index') }}"
                       class="flex items-center gap-3 p-3 rounded-lg transition-colors duration-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                        <i data-lucide="upload" class="w-4 h-4 text-brand"></i>
                        <span class="text-sm font-medium"
                              :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('dashboard.quick_links.upload_media') }}</span>
                    </a>

                    <a href="{{ route('admin.users.index') }}"
                       class="flex items-center gap-3 p-3 rounded-lg transition-colors duration-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                        <i data-lucide="users" class="w-4 h-4 text-brand"></i>
                        <span class="text-sm font-medium"
                              :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('dashboard.quick_links.manage_users') }}</span>
                    </a>

                    <a href="{{ route('admin.settings') }}"
                       class="flex items-center gap-3 p-3 rounded-lg transition-colors duration-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                        <i data-lucide="settings" class="w-4 h-4 text-brand"></i>
                        <span class="text-sm font-medium"
                              :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('dashboard.quick_links.settings') }}</span>
                    </a>
                </div>
            </div>

            {{-- Storage Usage --}}
            <div class="rounded-2xl shadow-lg p-6 animate-fadeInUp delay-400"
                 :class="darkMode ? 'bg-gray-900 border border-gray-800' : 'bg-white border border-gray-200'">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-2 rounded-lg bg-gradient-to-br from-brand to-secondary">
                        <i data-lucide="hard-drive" class="w-5 h-5 text-white"></i>
                    </div>
                    <h3 class="text-lg font-bold"
                        :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ __('dashboard.storage.title') }}
                    </h3>
                </div>

                <div class="space-y-4">
                    {{-- Progress Bar --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium"
                                  :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ $stats['storage_used'] ?? '0 GB' }} {{ __('dashboard.storage.used') }}</span>
                            <span class="text-sm"
                                  :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ $stats['storage_total'] ?? '100 GB' }} {{ __('dashboard.storage.total') }}</span>
                        </div>
                        <div class="w-full h-2 rounded-full overflow-hidden"
                             :class="darkMode ? 'bg-gray-800' : 'bg-gray-200'">
                            <div class="h-full bg-gradient-to-r from-brand to-secondary rounded-full"
                                 style="width: {{ $stats['storage_percent'] ?? 0 }}%"></div>
                        </div>
                    </div>

                    {{-- Upgrade Button --}}
                    <button class="w-full py-2 px-4 rounded-lg bg-gradient-to-r from-brand to-secondary text-white text-sm font-semibold hover:shadow-lg transition-all duration-200">
                        <i data-lucide="arrow-up-circle" class="w-4 h-4 inline {{ app()->getLocale() === 'ar' ? 'ms-2' : 'me-2' }}"></i>
                        {{ __('dashboard.storage.upgrade') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Dashboard-specific JavaScript
    document.addEventListener('DOMContentLoaded', () => {
        // Reinitialize icons after Alpine renders
        setTimeout(() => {
            lucide.createIcons();
        }, 100);
    });

    // Listen for period changes from chart cards
    document.addEventListener('period-changed', (event) => {
        console.log('Period changed:', event.detail);
        // Here you would typically fetch new data and update the chart
        // Example: fetchChartData(event.detail.chartId, event.detail.period);
    });
</script>
@endpush
