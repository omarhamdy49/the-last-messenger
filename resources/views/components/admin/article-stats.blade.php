{{--
/**
 * Article Statistics Widget Component
 *
 * Displays article performance metrics with animated counters and trend indicators.
 * Features 4 stat cards for views, favorites, comments, and shares.
 *
 * @props
 * - article: Article model (required) - The article to display stats for
 *
 * @example
 * <x-admin.article-stats :article="$article" />
 */
--}}

@props(['article'])

@php
    // Calculate stats (replace with actual data from your models)
    $stats = [
        'views' => $article->views_count ?? rand(1000, 50000),
        'viewsTrend' => '+12%',
        'favorites' => $article->favorites_count ?? rand(50, 5000),
        'favoritesTrend' => '+8%',
        'comments' => $article->comments_count ?? rand(10, 500),
        'commentsTrend' => '+5%',
        'shares' => $article->shares_count ?? rand(20, 1000),
        'sharesTrend' => '+15%'
    ];
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6"
     x-data="{
         stats: {
             views: { current: 0, target: {{ $stats['views'] }}, trend: '{{ $stats['viewsTrend'] }}' },
             favorites: { current: 0, target: {{ $stats['favorites'] }}, trend: '{{ $stats['favoritesTrend'] }}' },
             comments: { current: 0, target: {{ $stats['comments'] }}, trend: '{{ $stats['commentsTrend'] }}' },
             shares: { current: 0, target: {{ $stats['shares'] }}, trend: '{{ $stats['sharesTrend'] }}' }
         },

         init() {
             this.animateCounters();
         },

         animateCounters() {
             Object.keys(this.stats).forEach(key => {
                 this.animateCounter(key);
             });
         },

         animateCounter(key) {
             const stat = this.stats[key];
             const duration = 1500;
             const steps = 60;
             const stepValue = stat.target / steps;
             const stepDuration = duration / steps;

             let currentStep = 0;
             const interval = setInterval(() => {
                 currentStep++;
                 stat.current = Math.min(
                     Math.round(stepValue * currentStep),
                     stat.target
                 );

                 if (currentStep >= steps) {
                     clearInterval(interval);
                     stat.current = stat.target;
                 }
             }, stepDuration);
         },

         formatNumber(num) {
             if (num >= 1000000) {
                 return (num / 1000000).toFixed(1) + 'M';
             }
             if (num >= 1000) {
                 return (num / 1000).toFixed(1) + 'K';
             }
             return num.toLocaleString();
         }
     }">

    <!-- Views Card -->
    <div class="group rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-1 animate-fadeInUp border"
         :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">

        <!-- Header -->
        <div class="flex items-start justify-between mb-4">
            <div class="p-3 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 group-hover:scale-110 transition-transform duration-300">
                <i data-lucide="eye" class="w-6 h-6 text-white"></i>
            </div>

            <!-- Trend Badge -->
            <div class="flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-green-500/10 text-green-500">
                <i data-lucide="trending-up" class="w-3 h-3"></i>
                <span x-text="stats.views.trend"></span>
            </div>
        </div>

        <!-- Value -->
        <div class="mb-2">
            <h3 class="text-3xl font-bold"
                :class="darkMode ? 'text-white' : 'text-gray-900'"
                x-text="formatNumber(stats.views.current)">
                0
            </h3>
        </div>

        <!-- Label -->
        <p class="text-sm font-medium"
           :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            Total Views
        </p>

        <!-- Progress Bar -->
        <div class="mt-4 h-1 rounded-full overflow-hidden"
             :class="darkMode ? 'bg-gray-800' : 'bg-gray-200'">
            <div class="h-full bg-gradient-to-r from-blue-500 to-blue-600 transition-all duration-1000"
                 :style="`width: ${Math.min((stats.views.current / stats.views.target) * 100, 100)}%`">
            </div>
        </div>
    </div>

    <!-- Favorites Card -->
    <div class="group rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-1 animate-fadeInUp stagger-1 border"
         :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">

        <!-- Header -->
        <div class="flex items-start justify-between mb-4">
            <div class="p-3 rounded-xl bg-gradient-to-br from-pink-500 to-rose-500 group-hover:scale-110 transition-transform duration-300">
                <i data-lucide="heart" class="w-6 h-6 text-white"></i>
            </div>

            <!-- Trend Badge -->
            <div class="flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-green-500/10 text-green-500">
                <i data-lucide="trending-up" class="w-3 h-3"></i>
                <span x-text="stats.favorites.trend"></span>
            </div>
        </div>

        <!-- Value -->
        <div class="mb-2">
            <h3 class="text-3xl font-bold"
                :class="darkMode ? 'text-white' : 'text-gray-900'"
                x-text="formatNumber(stats.favorites.current)">
                0
            </h3>
        </div>

        <!-- Label -->
        <p class="text-sm font-medium"
           :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            Favorites
        </p>

        <!-- Progress Bar -->
        <div class="mt-4 h-1 rounded-full overflow-hidden"
             :class="darkMode ? 'bg-gray-800' : 'bg-gray-200'">
            <div class="h-full bg-gradient-to-r from-pink-500 to-rose-500 transition-all duration-1000"
                 :style="`width: ${Math.min((stats.favorites.current / stats.favorites.target) * 100, 100)}%`">
            </div>
        </div>
    </div>

    <!-- Comments Card -->
    <div class="group rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-1 animate-fadeInUp stagger-2 border"
         :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">

        <!-- Header -->
        <div class="flex items-start justify-between mb-4">
            <div class="p-3 rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 group-hover:scale-110 transition-transform duration-300">
                <i data-lucide="message-circle" class="w-6 h-6 text-white"></i>
            </div>

            <!-- Trend Badge -->
            <div class="flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-green-500/10 text-green-500">
                <i data-lucide="trending-up" class="w-3 h-3"></i>
                <span x-text="stats.comments.trend"></span>
            </div>
        </div>

        <!-- Value -->
        <div class="mb-2">
            <h3 class="text-3xl font-bold"
                :class="darkMode ? 'text-white' : 'text-gray-900'"
                x-text="formatNumber(stats.comments.current)">
                0
            </h3>
        </div>

        <!-- Label -->
        <p class="text-sm font-medium"
           :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            Comments
        </p>

        <!-- Progress Bar -->
        <div class="mt-4 h-1 rounded-full overflow-hidden"
             :class="darkMode ? 'bg-gray-800' : 'bg-gray-200'">
            <div class="h-full bg-gradient-to-r from-purple-500 to-purple-600 transition-all duration-1000"
                 :style="`width: ${Math.min((stats.comments.current / stats.comments.target) * 100, 100)}%`">
            </div>
        </div>
    </div>

    <!-- Shares Card -->
    <div class="group rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-1 animate-fadeInUp stagger-3 border"
         :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">

        <!-- Header -->
        <div class="flex items-start justify-between mb-4">
            <div class="p-3 rounded-xl bg-gradient-to-br from-brand to-secondary group-hover:scale-110 transition-transform duration-300">
                <i data-lucide="share-2" class="w-6 h-6 text-white"></i>
            </div>

            <!-- Trend Badge -->
            <div class="flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-green-500/10 text-green-500">
                <i data-lucide="trending-up" class="w-3 h-3"></i>
                <span x-text="stats.shares.trend"></span>
            </div>
        </div>

        <!-- Value -->
        <div class="mb-2">
            <h3 class="text-3xl font-bold"
                :class="darkMode ? 'text-white' : 'text-gray-900'"
                x-text="formatNumber(stats.shares.current)">
                0
            </h3>
        </div>

        <!-- Label -->
        <p class="text-sm font-medium"
           :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            Social Shares
        </p>

        <!-- Progress Bar -->
        <div class="mt-4 h-1 rounded-full overflow-hidden"
             :class="darkMode ? 'bg-gray-800' : 'bg-gray-200'">
            <div class="h-full bg-gradient-to-r from-brand to-secondary transition-all duration-1000"
                 :style="`width: ${Math.min((stats.shares.current / stats.shares.target) * 100, 100)}%`">
            </div>
        </div>
    </div>
</div>

<!-- Detailed Stats Section (Optional - can be toggled) -->
<div class="mt-6 rounded-2xl overflow-hidden shadow-lg glass border"
     :class="darkMode ? 'glass-dark' : 'glass-light'"
     x-data="{ showDetails: false }">

    <!-- Toggle Header -->
    <button @click="showDetails = !showDetails"
            class="w-full p-6 flex items-center justify-between hover:bg-accent/5 transition-colors">
        <div class="flex items-center gap-3">
            <i data-lucide="bar-chart-2" class="w-5 h-5 text-accent"></i>
            <h3 class="text-lg font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                Detailed Analytics
            </h3>
        </div>
        <i data-lucide="chevron-down" class="w-5 h-5 transition-transform"
           :class="[showDetails ? 'rotate-180' : '', darkMode ? 'text-gray-400' : 'text-gray-600']"></i>
    </button>

    <!-- Details Content -->
    <div x-show="showDetails"
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform -translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         class="p-6 pt-0 border-t"
         :class="darkMode ? 'border-gray-800' : 'border-gray-200'">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Last Updated -->
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg" :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
                    <i data-lucide="clock" class="w-5 h-5" :class="darkMode ? 'text-gray-400' : 'text-gray-600'"></i>
                </div>
                <div>
                    <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Last Updated</p>
                    <p class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ $article->updated_at->diffForHumans() }}
                    </p>
                </div>
            </div>

            <!-- Published Date -->
            @if($article->published_at)
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-lg" :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
                        <i data-lucide="calendar" class="w-5 h-5" :class="darkMode ? 'text-gray-400' : 'text-gray-600'"></i>
                    </div>
                    <div>
                        <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Published</p>
                        <p class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                            {{ $article->published_at->format('M d, Y') }}
                        </p>
                    </div>
                </div>
            @endif

            <!-- Reading Time -->
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg" :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
                    <i data-lucide="book-open" class="w-5 h-5" :class="darkMode ? 'text-gray-400' : 'text-gray-600'"></i>
                </div>
                <div>
                    <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Reading Time</p>
                    <p class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        @php
                            $wordCount = str_word_count(strip_tags($article->translations->first()->content ?? ''));
                            $readingTime = ceil($wordCount / 200);
                        @endphp
                        {{ $readingTime }} min read
                    </p>
                </div>
            </div>

            <!-- Word Count -->
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg" :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
                    <i data-lucide="type" class="w-5 h-5" :class="darkMode ? 'text-gray-400' : 'text-gray-600'"></i>
                </div>
                <div>
                    <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">Word Count</p>
                    <p class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ number_format($wordCount ?? 0) }} words
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* Stagger animation delays */
    .stagger-1 {
        animation-delay: 0.1s;
    }

    .stagger-2 {
        animation-delay: 0.2s;
    }

    .stagger-3 {
        animation-delay: 0.3s;
    }
</style>
@endpush
