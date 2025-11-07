@extends('layouts.admin')

@section('title', 'Dashboard')

@section('page-header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold mb-2"
                :class="darkMode ? 'text-white' : 'text-gray-900'">
                Welcome back, {{ auth()->user()->name ?? 'Admin' }}!
            </h1>
            <p class="text-sm"
               :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                Here's what's happening with your platform today.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <!-- Quick Actions -->
            <button class="px-4 py-2 rounded-lg font-semibold text-sm transition-colors duration-200"
                    :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200'">
                <i data-lucide="filter" class="w-4 h-4 inline me-2"></i>
                Filter
            </button>

            <button class="px-4 py-2 rounded-lg bg-gradient-to-r from-brand to-secondary text-white font-semibold text-sm hover:shadow-lg transition-all duration-200">
                <i data-lucide="plus" class="w-4 h-4 inline me-2"></i>
                New Content
            </button>
        </div>
    </div>
@endsection

@section('content')
    {{-- Stats Cards Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {{-- Total Users --}}
        <x-admin.stats-card
            icon="users"
            title="Total Users"
            :value="12345"
            change="+12.5%"
            changeType="up"
            :gradient="true"
            delay="delay-0"
            href="/admin/users"
        />

        {{-- Total Articles --}}
        <x-admin.stats-card
            icon="file-text"
            title="Total Articles"
            :value="1842"
            change="+8.2%"
            changeType="up"
            delay="delay-100"
            href="/admin/articles"
        />

        {{-- Media Items --}}
        <x-admin.stats-card
            icon="play-circle"
            title="Media Items"
            :value="567"
            change="+15.3%"
            changeType="up"
            delay="delay-200"
            href="/admin/media"
        />

        {{-- Total Views --}}
        <x-admin.stats-card
            icon="eye"
            title="Total Views"
            value="2.4M"
            change="-2.1%"
            changeType="down"
            delay="delay-300"
        />
    </div>

    {{-- Additional Stats Row --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {{-- Hadith Entries --}}
        <x-admin.stats-card
            icon="book-open"
            title="Hadith Entries"
            :value="4521"
            change="+6.7%"
            changeType="up"
            delay="delay-0"
            href="/admin/hadith"
        />

        {{-- Quran Verses --}}
        <x-admin.stats-card
            icon="book"
            title="Quran Verses"
            :value="6236"
            delay="delay-100"
            href="/admin/verses"
        />

        {{-- Sahaba Profiles --}}
        <x-admin.stats-card
            icon="user-circle"
            title="Sahaba Profiles"
            :value="324"
            change="+4.2%"
            changeType="up"
            delay="delay-200"
            href="/admin/sahaba"
        />

        {{-- Seerah Events --}}
        <x-admin.stats-card
            icon="calendar"
            title="Seerah Events"
            :value="189"
            change="+9.1%"
            changeType="up"
            delay="delay-300"
            href="/admin/seerah-events"
        />
    </div>

    {{-- Charts Section --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        {{-- User Growth Chart --}}
        <x-admin.chart-card
            title="User Growth"
            subtitle="Monthly active users over the last 6 months"
            chartId="userGrowthChart"
            height="350px"
            :periods="['7D', '30D', '3M', '6M', '1Y']"
            defaultPeriod="6M"
            delay="delay-100"
        >
            <x-slot name="statistics">
                <div class="text-center">
                    <p class="text-2xl font-bold gradient-text">+2,543</p>
                    <p class="text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">New Users</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold gradient-text">8,234</p>
                    <p class="text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Active Users</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold gradient-text">67%</p>
                    <p class="text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Retention</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold gradient-text">4.2</p>
                    <p class="text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Avg Session</p>
                </div>
            </x-slot>
        </x-admin.chart-card>

        {{-- Content Performance Chart --}}
        <x-admin.chart-card
            title="Content Performance"
            subtitle="Views and engagement across different modules"
            chartId="contentPerformanceChart"
            height="350px"
            :periods="['Today', 'Week', 'Month']"
            defaultPeriod="Week"
            delay="delay-200"
        >
            <x-slot name="actions">
                <button class="p-2 rounded-lg transition-colors duration-200 hover:bg-gray-100 dark:hover:bg-gray-800"
                        :class="darkMode ? 'text-gray-400' : 'text-gray-600'"
                        title="Compare">
                    <i data-lucide="git-compare" class="w-5 h-5"></i>
                </button>
            </x-slot>

            <x-slot name="statistics">
                <div class="text-center">
                    <p class="text-2xl font-bold text-blue-500">42.3K</p>
                    <p class="text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Hadith</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold text-green-500">38.7K</p>
                    <p class="text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Articles</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold text-purple-500">28.5K</p>
                    <p class="text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Quran</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold text-orange-500">19.2K</p>
                    <p class="text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Media</p>
                </div>
            </x-slot>
        </x-admin.chart-card>
    </div>

    {{-- Activity Feed and Quick Stats --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Activity Feed (2 columns on large screens) --}}
        <div class="lg:col-span-2">
            <x-admin.activity-feed
                title="Recent Activity"
                :activities="[
                    [
                        'type' => 'created',
                        'user' => ['name' => 'Ahmed Mohammed', 'avatar' => null],
                        'action' => 'created a new article',
                        'target' => 'The Battle of Badr: A Turning Point',
                        'time' => '5 minutes ago',
                        'url' => '/admin/articles/123',
                        'details' => 'Published in Seerah category with 2,450 words'
                    ],
                    [
                        'type' => 'updated',
                        'user' => ['name' => 'Sarah Ali', 'avatar' => null],
                        'action' => 'updated',
                        'target' => 'Sahih Bukhari - Book of Faith',
                        'time' => '23 minutes ago',
                        'url' => '/admin/hadith/456'
                    ],
                    [
                        'type' => 'published',
                        'user' => ['name' => 'Omar Hassan', 'avatar' => null],
                        'action' => 'published a video',
                        'target' => 'Stories of the Prophets - Episode 12',
                        'time' => '1 hour ago',
                        'url' => '/admin/media/789'
                    ],
                    [
                        'type' => 'comment',
                        'user' => ['name' => 'Fatima Ibrahim', 'avatar' => null],
                        'action' => 'commented on',
                        'target' => 'Introduction to Islamic Principles',
                        'time' => '2 hours ago',
                        'url' => '/admin/articles/321'
                    ],
                    [
                        'type' => 'login',
                        'user' => ['name' => 'Admin User', 'avatar' => null],
                        'action' => 'logged in to the system',
                        'time' => '3 hours ago'
                    ],
                    [
                        'type' => 'deleted',
                        'user' => ['name' => 'Mohammed Yusuf', 'avatar' => null],
                        'action' => 'deleted',
                        'target' => 'Draft Article: Untitled',
                        'time' => '4 hours ago',
                        'details' => 'Moved to trash. Can be restored within 30 days.'
                    ],
                    [
                        'type' => 'upload',
                        'user' => ['name' => 'Aisha Rahman', 'avatar' => null],
                        'action' => 'uploaded',
                        'target' => '15 images to Media Library',
                        'time' => '5 hours ago'
                    ],
                    [
                        'type' => 'updated',
                        'user' => ['name' => 'Ibrahim Malik', 'avatar' => null],
                        'action' => 'updated user profile',
                        'target' => 'Contact Information',
                        'time' => '6 hours ago'
                    ]
                ]"
                :maxItems="8"
                :showViewAll="true"
                viewAllUrl="/admin/activity-logs"
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
                        System Status
                    </h3>
                </div>

                <div class="space-y-4">
                    {{-- API Status --}}
                    <div class="flex items-center justify-between">
                        <span class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">API</span>
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
                            <span class="text-sm font-medium text-green-500">Operational</span>
                        </div>
                    </div>

                    {{-- Database Status --}}
                    <div class="flex items-center justify-between">
                        <span class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Database</span>
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
                            <span class="text-sm font-medium text-green-500">Operational</span>
                        </div>
                    </div>

                    {{-- Storage Status --}}
                    <div class="flex items-center justify-between">
                        <span class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Storage</span>
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full bg-yellow-500"></div>
                            <span class="text-sm font-medium text-yellow-500">78% Used</span>
                        </div>
                    </div>

                    {{-- CDN Status --}}
                    <div class="flex items-center justify-between">
                        <span class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">CDN</span>
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
                            <span class="text-sm font-medium text-green-500">Operational</span>
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
                        Quick Links
                    </h3>
                </div>

                <div class="space-y-2">
                    <a href="/admin/articles/create"
                       class="flex items-center gap-3 p-3 rounded-lg transition-colors duration-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                        <i data-lucide="file-plus" class="w-4 h-4 text-brand"></i>
                        <span class="text-sm font-medium"
                              :class="darkMode ? 'text-gray-300' : 'text-gray-700'">New Article</span>
                    </a>

                    <a href="/admin/hadith/create"
                       class="flex items-center gap-3 p-3 rounded-lg transition-colors duration-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                        <i data-lucide="book-plus" class="w-4 h-4 text-brand"></i>
                        <span class="text-sm font-medium"
                              :class="darkMode ? 'text-gray-300' : 'text-gray-700'">Add Hadith</span>
                    </a>

                    <a href="/admin/media/upload"
                       class="flex items-center gap-3 p-3 rounded-lg transition-colors duration-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                        <i data-lucide="upload" class="w-4 h-4 text-brand"></i>
                        <span class="text-sm font-medium"
                              :class="darkMode ? 'text-gray-300' : 'text-gray-700'">Upload Media</span>
                    </a>

                    <a href="/admin/users"
                       class="flex items-center gap-3 p-3 rounded-lg transition-colors duration-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                        <i data-lucide="users" class="w-4 h-4 text-brand"></i>
                        <span class="text-sm font-medium"
                              :class="darkMode ? 'text-gray-300' : 'text-gray-700'">Manage Users</span>
                    </a>

                    <a href="/admin/settings"
                       class="flex items-center gap-3 p-3 rounded-lg transition-colors duration-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                        <i data-lucide="settings" class="w-4 h-4 text-brand"></i>
                        <span class="text-sm font-medium"
                              :class="darkMode ? 'text-gray-300' : 'text-gray-700'">Settings</span>
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
                        Storage
                    </h3>
                </div>

                <div class="space-y-4">
                    {{-- Progress Bar --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium"
                                  :class="darkMode ? 'text-gray-300' : 'text-gray-700'">78.5 GB used</span>
                            <span class="text-sm"
                                  :class="darkMode ? 'text-gray-400' : 'text-gray-600'">100 GB total</span>
                        </div>
                        <div class="w-full h-2 rounded-full overflow-hidden"
                             :class="darkMode ? 'bg-gray-800' : 'bg-gray-200'">
                            <div class="h-full bg-gradient-to-r from-brand to-secondary rounded-full"
                                 style="width: 78.5%"></div>
                        </div>
                    </div>

                    {{-- Upgrade Button --}}
                    <button class="w-full py-2 px-4 rounded-lg bg-gradient-to-r from-brand to-secondary text-white text-sm font-semibold hover:shadow-lg transition-all duration-200">
                        <i data-lucide="arrow-up-circle" class="w-4 h-4 inline me-2"></i>
                        Upgrade Storage
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
