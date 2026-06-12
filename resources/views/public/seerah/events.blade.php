@extends('layouts.public')

@section('title', __('seerah.public.events_title'))
@section('meta_description', __('seerah.public.events_description'))

@php
    $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true);
    // Server-side data → reshape into the markup the prototype expects, per item.
    $items = collect($events->items());
    $totalPages = $events->lastPage();
    $currentPage = $events->currentPage();
@endphp

@push('head')
<style>
    [x-cloak] { display: none !important; }

    /* Custom Scrollbar */
    .custom-scrollbar::-webkit-scrollbar {
      width: 8px;
      height: 8px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
      background: rgba(0, 0, 0, 0.1);
      border-radius: 10px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
      background: linear-gradient(135deg, #0056b3, #218838);
      border-radius: 10px;
    }

    /* Glassmorphism */
    .glass {
      background: rgba(255, 255, 255, 0.05);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.1);
    }

    /* Animations */
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes slideInRight {
      from {
        opacity: 0;
        transform: translateX(-30px);
      }
      to {
        opacity: 1;
        transform: translateX(0);
      }
    }

    @keyframes shimmer {
      0% {
        background-position: -1000px 0;
      }
      100% {
        background-position: 1000px 0;
      }
    }

    @keyframes pulse {
      0%, 100% {
        opacity: 1;
      }
      50% {
        opacity: 0.5;
      }
    }

    @keyframes scaleIn {
      from {
        opacity: 0;
        transform: scale(0.9);
      }
      to {
        opacity: 1;
        transform: scale(1);
      }
    }

    @keyframes float {
      0%, 100% {
        transform: translateY(0px);
      }
      50% {
        transform: translateY(-10px);
      }
    }

    .fade-in-up {
      animation: fadeInUp 0.6s ease-out;
    }

    .slide-in-right {
      animation: slideInRight 0.6s ease-out;
    }

    .scale-in {
      animation: scaleIn 0.5s ease-out;
    }

    .float {
      animation: float 3s ease-in-out infinite;
    }

    .stagger-1 { animation-delay: 0.1s; opacity: 0; animation-fill-mode: forwards; }
    .stagger-2 { animation-delay: 0.2s; opacity: 0; animation-fill-mode: forwards; }
    .stagger-3 { animation-delay: 0.3s; opacity: 0; animation-fill-mode: forwards; }
    .stagger-4 { animation-delay: 0.4s; opacity: 0; animation-fill-mode: forwards; }

    /* Skeleton Loading */
    .skeleton {
      background: linear-gradient(90deg, rgba(255,255,255,0.05) 25%, rgba(255,255,255,0.1) 50%, rgba(255,255,255,0.05) 75%);
      background-size: 1000px 100%;
      animation: shimmer 2s infinite;
    }

    /* Timeline Line */
    .timeline-line {
      position: absolute;
      right: 1.5rem;
      top: 0;
      bottom: 0;
      width: 3px;
      background: linear-gradient(to bottom, #0056b3, #218838, #64CCC5);
    }

    /* Event Card Hover */
    .event-card {
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      overflow: hidden;
    }

    .event-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(100, 204, 197, 0.1), transparent);
      transition: left 0.5s;
    }

    .event-card:hover::before {
      left: 100%;
    }

    .event-card:hover {
      transform: translateX(8px);
      box-shadow: 0 20px 40px rgba(100, 204, 197, 0.2);
    }

    .event-card::after {
      content: '';
      position: absolute;
      right: 0;
      top: 0;
      width: 4px;
      height: 100%;
      background: linear-gradient(to bottom, #0056b3, #218838);
      opacity: 0;
      transition: opacity 0.3s;
    }

    .event-card:hover::after {
      opacity: 1;
    }

    /* Timeline View Styles */
    .timeline-view {
      position: relative;
      padding-right: 4rem;
    }

    .timeline-dot {
      position: absolute;
      right: 1rem;
      width: 1.5rem;
      height: 1.5rem;
      border-radius: 50%;
      z-index: 10;
      transition: all 0.3s;
    }

    .event-card:hover .timeline-dot {
      transform: scale(1.3);
      box-shadow: 0 0 20px rgba(100, 204, 197, 0.6);
    }

    /* Pagination Styles */
    .pagination-btn {
      transition: all 0.3s ease;
    }

    .pagination-btn:hover:not(:disabled) {
      transform: scale(1.05);
      box-shadow: 0 4px 12px rgba(100, 204, 197, 0.3);
    }

    .pagination-btn:disabled {
      opacity: 0.4;
      cursor: not-allowed;
    }

    /* Scroll to Top Button */
    .scroll-top-btn {
      transition: all 0.3s ease;
    }

    .scroll-top-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(0, 86, 179, 0.3);
    }

    /* Filter Tags Animation */
    .filter-tag {
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .filter-tag:hover {
      transform: translateY(-2px);
    }

    /* Stats Counter Animation */
    @keyframes countUp {
      from {
        opacity: 0;
        transform: translateY(10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .stat-counter {
      animation: countUp 0.5s ease-out;
    }

    /* Mobile Timeline Adjustment */
    @media (max-width: 768px) {
      .timeline-view {
        padding-right: 0;
      }
    }

    /* Page Transition */
    .page-transition {
      animation: fadeInUp 0.4s ease-out;
    }

    /* Enhanced Focus Styles */
    button:focus-visible, input:focus-visible, select:focus-visible {
      outline: 2px solid #64CCC5;
      outline-offset: 2px;
    }
</style>
@endpush

@section('content')

  <!-- Main Content -->
  <main class="container mx-auto px-4 sm:px-6 lg:px-8 py-8"
        x-data="{ darkMode: $store.theme.dark, loading: false, sortBy: 'chronological' }"
        x-effect="darkMode = $store.theme.dark">

    <!-- Hero Section -->
    <div class="relative rounded-3xl overflow-hidden mb-12 fade-in-up" :class="darkMode ? 'bg-gradient-to-r from-gray-900 to-gray-800' : 'bg-gradient-to-r from-[#0056b3] to-[#218838]'">
      <div class="absolute inset-0 opacity-10">
        <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse"><path d="M 40 0 L 0 0 0 40" fill="none" stroke="currentColor" stroke-width="1"/></pattern></defs><rect width="100%" height="100%" fill="url(#grid)"/></svg>
      </div>

      <div class="relative px-6 py-12 md:px-12 md:py-16 text-center text-white">
        <div class="flex justify-center mb-6">
          <div class="w-20 h-20 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center float">
            <i data-lucide="calendar-clock" class="w-10 h-10 text-white"></i>
          </div>
        </div>

        <h1 class="text-4xl md:text-5xl font-bold mb-4">{{ __('seerah.public.events_heading') }}</h1>
        <p class="text-xl mb-4 opacity-90">{{ __('seerah.public.events_subheading') }}</p>
        <div class="flex items-center justify-center gap-6 text-lg opacity-80 flex-wrap">
          <div class="flex items-center gap-2">
            <i data-lucide="book-open" class="w-5 h-5"></i>
            <span><span class="stat-counter font-bold">{{ number_format($totalEvents) }}</span> {{ __('seerah.public.results_count') }}</span>
          </div>
          <div class="flex items-center gap-2">
            <i data-lucide="clock" class="w-5 h-5"></i>
            <span>{{ __('seerah.public.events_span') }}</span>
          </div>
          <div class="flex items-center gap-2">
            <i data-lucide="layers" class="w-5 h-5"></i>
            <span><span class="stat-counter font-bold">{{ max(0, count($categoryPills) - 1) }}</span> {{ __('seerah.public.categories_label') }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Controls -->
    <form method="GET" action="{{ route('public.seerah.events') }}"
          @submit="loading = true"
          class="grid grid-cols-1 lg:grid-cols-12 gap-4 mb-8 stagger-1 fade-in-up">
      @if ($activeCategory !== 'all')
        <input type="hidden" name="category" value="{{ $activeCategory }}">
      @endif

      <!-- Search -->
      <div class="lg:col-span-9">
        <div class="relative">
          <i data-lucide="search" class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5" :class="darkMode ? 'text-gray-500' : 'text-gray-400'"></i>
          <input
            type="search"
            name="q"
            value="{{ $searchQuery }}"
            placeholder="{{ __('seerah.public.search_placeholder') }}"
            class="w-full pr-12 pl-4 py-3 rounded-xl border-2 focus:outline-none focus:ring-2 focus:ring-[#64CCC5] transition-all"
            :class="darkMode ? 'bg-gray-900 border-gray-800 text-white placeholder-gray-500' : 'bg-white border-gray-200 text-gray-900 placeholder-gray-400'"
          >
        </div>
      </div>

      <!-- Sort -->
      <div class="lg:col-span-3">
        <select
          x-model="sortBy"
          class="w-full px-4 py-3 rounded-xl border-2 focus:outline-none focus:ring-2 focus:ring-[#64CCC5] transition-all font-medium"
          :class="darkMode ? 'bg-gray-900 border-gray-800 text-white' : 'bg-white border-gray-200 text-gray-900'"
        >
          <option value="chronological">{{ __('seerah.public.sort_oldest_first') }} ⏫</option>
          <option value="reverse">{{ __('seerah.public.sort_newest_first') }} ⏬</option>
        </select>
      </div>
    </form>

    <!-- Category Pills -->
    <div class="flex gap-3 overflow-x-auto custom-scrollbar pb-4 mb-8 stagger-2 fade-in-up">
      @foreach ($categoryPills as $cat)
        <a
          href="{{ $cat['url'] }}"
          class="filter-tag flex items-center gap-2 px-5 py-2.5 rounded-full font-semibold whitespace-nowrap transition-all"
          @class([
            'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg scale-105' => $activeCategory === $cat['value'],
          ])
          @if ($activeCategory !== $cat['value'])
            :class="darkMode ? 'bg-gray-900 text-gray-400 hover:text-white hover:bg-gray-800' : 'bg-white text-gray-600 hover:text-gray-900 hover:bg-gray-100 shadow-sm'"
          @endif
        >
          <span>{{ $cat['label'] }}</span>
          <span class="px-2 py-0.5 rounded-full text-xs font-bold"
                @class(['bg-white/20' => $activeCategory === $cat['value']])
                @if ($activeCategory !== $cat['value'])
                  :class="darkMode ? 'bg-gray-800' : 'bg-gray-200'"
                @endif
          >{{ $cat['count'] }}</span>
        </a>
      @endforeach
    </div>

    <!-- Results Info & Reset -->
    <div class="mb-6 flex items-center justify-between stagger-3 fade-in-up flex-wrap gap-4">
      <div class="flex items-center gap-4">
        <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
          {!! __('seerah.public.showing_results', [
              'shown' => '<span class="font-bold text-[#64CCC5]">' . $items->count() . '</span>',
              'total' => '<span class="font-bold">' . number_format($totalEvents) . '</span>',
          ]) !!}
        </p>

        @if ($searchQuery !== '' || $activeCategory !== 'all')
          <a
            href="{{ route('public.seerah.events') }}"
            class="text-sm flex items-center gap-1 px-3 py-1 rounded-lg transition-all hover:scale-105"
            :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'"
          >
            <i data-lucide="x-circle" class="w-4 h-4"></i>
            <span>{{ __('seerah.public.reset_filters') }}</span>
          </a>
        @endif
      </div>

      <p class="text-sm font-medium" :class="darkMode ? 'text-gray-500' : 'text-gray-600'">
        {!! __('seerah.public.page_of', [
            'current' => '<span class="text-[#64CCC5] font-bold">' . $currentPage . '</span>',
            'total' => '<span>' . $totalPages . '</span>',
        ]) !!}
      </p>
    </div>

    <!-- Loading Skeleton -->
    <div x-show="loading" x-cloak class="space-y-6 page-transition">
      <template x-for="i in 5">
        <div class="rounded-2xl p-6 shadow-lg" :class="darkMode ? 'bg-gray-900' : 'bg-white'">
          <div class="flex gap-6">
            <div class="w-20 h-20 rounded-2xl skeleton"></div>
            <div class="flex-1 space-y-3">
              <div class="h-6 w-3/4 rounded skeleton"></div>
              <div class="h-4 w-full rounded skeleton"></div>
              <div class="h-4 w-2/3 rounded skeleton"></div>
            </div>
          </div>
        </div>
      </template>
    </div>

    @if ($items->count() > 0)
    <!-- Timeline View -->
    <div x-show="!loading" class="relative timeline-view page-transition">
      <!-- Timeline Line -->
      <div class="timeline-line hidden md:block"></div>

      <!-- Events List -->
      <div class="space-y-6">
        @foreach ($items as $index => $event)
          <a
            href="{{ $event['url'] }}"
            class="group relative block event-card rounded-2xl p-6 shadow-lg transition-all slide-in-right stagger-{{ ($index % 4) + 1 }}"
            :class="darkMode ? 'bg-gray-900 hover:bg-gray-800' : 'bg-white hover:bg-gray-50'"
          >
            <!-- Timeline Dot -->
            <div
              class="timeline-dot hidden md:flex items-center justify-center bg-gradient-to-r text-white {{ $event['gradient'] }}"
            >
              <i data-lucide="{{ $event['icon'] }}" class="w-4 h-4"></i>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-start gap-6">
              <!-- Year Badge -->
              <div class="flex-shrink-0">
                <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-2xl bg-gradient-to-br flex flex-col items-center justify-center text-white shadow-lg group-hover:scale-110 transition-transform {{ $event['gradient'] }}">
                  <div class="text-2xl sm:text-3xl font-bold">{{ abs((int) $event['hijriYear']) }}</div>
                  <div class="text-xs opacity-80">{{ (int) $event['hijriYear'] >= 0 ? __('seerah.public.hijri_suffix') : __('seerah.public.before_hijra_suffix') }}</div>
                </div>
              </div>

              <!-- Event Content -->
              <div class="flex-1 min-w-0">
                <!-- Title & Hijri Date -->
                <div class="flex items-start justify-between gap-4 mb-3 flex-wrap">
                  <h3 class="text-xl sm:text-2xl font-bold group-hover:text-[#64CCC5] transition-colors" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $event['title'] }}</h3>
                  @if ($event['hijriDate'])
                    <span class="flex-shrink-0 px-3 py-1 rounded-full text-xs font-bold whitespace-nowrap" :class="darkMode ? 'bg-gray-800 text-gray-400' : 'bg-gray-200 text-gray-600'">{{ $event['hijriDate'] }}</span>
                  @endif
                </div>

                <!-- Description -->
                @if ($event['description'])
                  <p class="text-sm sm:text-base mb-4 line-clamp-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ $event['description'] }}</p>
                @endif

                <!-- Meta Info -->
                <div class="flex flex-wrap items-center gap-3 sm:gap-4 text-sm">
                  <!-- Location -->
                  @if ($event['location'])
                    <div class="flex items-center gap-2">
                      <i data-lucide="map-pin" class="w-4 h-4 text-[#64CCC5]"></i>
                      <span :class="darkMode ? 'text-gray-500' : 'text-gray-500'">{{ $event['location'] }}</span>
                    </div>
                  @endif

                  <!-- Category Badge -->
                  <div class="flex items-center gap-2 px-3 py-1 rounded-full" :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
                    <i data-lucide="{{ $event['icon'] }}" class="w-4 h-4 text-[#64CCC5]"></i>
                    <span class="text-xs font-medium" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ $event['typeLabel'] }}</span>
                  </div>

                  <!-- Gregorian Year -->
                  @if ($event['gregorianYear'])
                    <div class="flex items-center gap-2 px-3 py-1 rounded-full" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-100/50'">
                      <i data-lucide="calendar" class="w-4 h-4 text-[#64CCC5]"></i>
                      <span class="text-xs font-medium" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">{{ $event['gregorianYear'] }} {{ __('seerah.public.gregorian_suffix') }}</span>
                    </div>
                  @endif
                </div>
              </div>

              <!-- Arrow Icon -->
              <div class="flex-shrink-0 hidden sm:block">
                <i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" class="w-6 h-6 opacity-0 group-hover:opacity-100 group-hover:translate-x-[-4px] transition-all" :class="darkMode ? 'text-gray-400' : 'text-gray-600'"></i>
              </div>
            </div>
          </a>
        @endforeach
      </div>
    </div>
    @else
    <!-- No Results -->
    <div x-show="!loading" class="text-center py-20 scale-in">
      <div class="inline-flex items-center justify-center w-20 h-20 rounded-full mb-6" :class="darkMode ? 'bg-gray-800' : 'bg-gray-200'">
        <i data-lucide="search-x" class="w-10 h-10" :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
      </div>
      <h3 class="text-2xl font-bold mb-2" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('seerah.public.no_results_title') }}</h3>
      <p class="mb-6" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('seerah.public.no_results_hint') }}</p>
      <a href="{{ route('public.seerah.events') }}" class="inline-block px-6 py-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-semibold hover:shadow-lg transition-all hover:scale-105">
        {{ __('seerah.public.reset_search') }}
      </a>
    </div>
    @endif

    @if ($totalPages > 1)
    <!-- Pagination -->
    <div x-show="!loading" class="mt-12 flex flex-col sm:flex-row items-center justify-between gap-6 scale-in">

      <!-- Page Info (Mobile) -->
      <div class="sm:hidden text-center w-full">
        <p class="text-sm font-medium" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
          {!! __('seerah.public.page_of', [
              'current' => '<span class="text-[#64CCC5] font-bold text-lg">' . $currentPage . '</span>',
              'total' => '<span class="font-bold">' . $totalPages . '</span>',
          ]) !!}
        </p>
      </div>

      <!-- Previous Button -->
      @php $prevUrl = $events->previousPageUrl(); @endphp
      <a
        @if ($prevUrl) href="{{ $prevUrl }}" @endif
        @class([
          'pagination-btn flex items-center gap-2 px-5 py-3 rounded-xl font-semibold transition-all',
          'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg hover:shadow-xl' => $prevUrl,
        ])
        @if (! $prevUrl)
          :class="darkMode ? 'bg-gray-800 text-gray-600' : 'bg-gray-200 text-gray-400'"
        @endif
      >
        <i data-lucide="chevron-{{ $isRtl ? 'right' : 'left' }}" class="w-5 h-5"></i>
        <span class="hidden sm:inline">{{ __('seerah.public.previous') }}</span>
      </a>

      <!-- Page Numbers -->
      <div class="flex items-center gap-2">
        @php
          $maxVisible = 5;
          $half = intdiv($maxVisible, 2);
          $startPage = max(1, $currentPage - $half);
          $endPage = min($totalPages, $startPage + $maxVisible - 1);
          if ($endPage - $startPage < $maxVisible - 1) {
              $startPage = max(1, $endPage - $maxVisible + 1);
          }
        @endphp

        <!-- First Page -->
        @if ($startPage > 1)
          <a
            href="{{ $events->url(1) }}"
            class="pagination-btn w-10 h-10 sm:w-12 sm:h-12 rounded-lg font-semibold transition-all inline-flex items-center justify-center"
            :class="darkMode ? 'bg-gray-800 text-gray-400 hover:bg-gray-700' : 'bg-white text-gray-600 hover:bg-gray-100'"
          >1</a>
          @if ($startPage > 2)
            <span :class="darkMode ? 'text-gray-600' : 'text-gray-400'">...</span>
          @endif
        @endif

        <!-- Page Numbers -->
        @for ($page = $startPage; $page <= $endPage; $page++)
          <a
            href="{{ $events->url($page) }}"
            @class([
              'pagination-btn w-10 h-10 sm:w-12 sm:h-12 rounded-lg font-semibold transition-all inline-flex items-center justify-center',
              'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg scale-110' => $page === $currentPage,
            ])
            @if ($page !== $currentPage)
              :class="darkMode ? 'bg-gray-800 text-gray-400 hover:bg-gray-700 hover:text-white' : 'bg-white text-gray-600 hover:bg-gray-100'"
            @endif
          >{{ $page }}</a>
        @endfor

        @if ($endPage < $totalPages - 1)
          <span :class="darkMode ? 'text-gray-600' : 'text-gray-400'">...</span>
        @endif

        <!-- Last Page -->
        @if ($endPage < $totalPages)
          <a
            href="{{ $events->url($totalPages) }}"
            class="pagination-btn w-10 h-10 sm:w-12 sm:h-12 rounded-lg font-semibold transition-all inline-flex items-center justify-center"
            :class="darkMode ? 'bg-gray-800 text-gray-400 hover:bg-gray-700' : 'bg-white text-gray-600 hover:bg-gray-100'"
          >{{ $totalPages }}</a>
        @endif
      </div>

      <!-- Next Button -->
      @php $nextUrl = $events->nextPageUrl(); @endphp
      <a
        @if ($nextUrl) href="{{ $nextUrl }}" @endif
        @class([
          'pagination-btn flex items-center gap-2 px-5 py-3 rounded-xl font-semibold transition-all',
          'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg hover:shadow-xl' => $nextUrl,
        ])
        @if (! $nextUrl)
          :class="darkMode ? 'bg-gray-800 text-gray-600' : 'bg-gray-200 text-gray-400'"
        @endif
      >
        <span class="hidden sm:inline">{{ __('seerah.public.next') }}</span>
        <i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" class="w-5 h-5"></i>
      </a>
    </div>
    @endif

  </main>

  <!-- Scroll to Top Button -->
  <button
    x-data="{ showScrollTop: false }"
    @scroll.window="showScrollTop = window.pageYOffset > 300"
    x-show="showScrollTop"
    @click="window.scrollTo({ top: 0, behavior: 'smooth' })"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 scale-0"
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-0"
    x-cloak
    class="scroll-top-btn fixed bottom-8 left-8 z-40 w-12 h-12 rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-xl flex items-center justify-center"
    aria-label="{{ __('seerah.public.scroll_to_top') }}"
  >
    <i data-lucide="arrow-up" class="w-6 h-6"></i>
  </button>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') { lucide.createIcons(); }
    });
</script>
@endpush

@endsection
