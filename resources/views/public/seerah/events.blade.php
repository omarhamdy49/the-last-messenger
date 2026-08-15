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
      inset-inline-start: 1.5rem;
      top: 0;
      bottom: 0;
      width: 3px;
      background: linear-gradient(to bottom, #0056b3, #218838, #64CCC5);
    }

    /* Event card. The previous hover slid the card 8px sideways and swept a
       shimmer across it — motion with no meaning, and it nudged the row it sat
       in. Elevation + a hairline accent says "interactive" without moving
       anything. */
    .event-card {
      position: relative;
      transition: box-shadow .25s cubic-bezier(.4, 0, .2, 1),
                  border-color .25s cubic-bezier(.4, 0, .2, 1);
    }

    .event-card:hover,
    .event-card:focus-visible {
      box-shadow: 0 1px 2px rgba(16, 24, 52, .04), 0 18px 40px -18px rgba(0, 86, 179, .35);
    }

    @media (prefers-reduced-motion: reduce) {
      .event-card { transition: none; }
    }

    .event-card::after {
      content: '';
      position: absolute;
      inset-inline-start: 0;
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
      padding-inline-start: 4rem;
    }

    .timeline-dot {
      position: absolute;
      inset-inline-start: 1rem;
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
        padding-inline-start: 0;
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
        {{-- sortBy dropped: sorting is a server-side query param now, not a dead client model. --}}
        x-data="{ darkMode: $store.theme.dark, loading: false }"
        x-effect="darkMode = $store.theme.dark">

    {{-- Editorial header. The old header leaned on a full-bleed gradient slab, a floating
         circle icon and an SVG grid — three generic devices competing at once.
         Restraint reads as premium here: type does the work, colour accents it. --}}
    {{-- overflow-x-clip: the glow orbs are wider than a 375px viewport and were
         pushing 15px of horizontal scroll onto the page. --}}
    <header class="relative mb-10 fade-in-up overflow-x-clip">
      <div class="absolute -top-24 start-1/4 w-72 h-72 rounded-full bg-[#0056b3]/10 dark:bg-[#0056b3]/20 blur-3xl -z-10 pointer-events-none"></div>
      <div class="absolute -top-16 end-1/4 w-64 h-64 rounded-full bg-[#218838]/10 dark:bg-[#64CCC5]/15 blur-3xl -z-10 pointer-events-none"></div>

      <nav aria-label="breadcrumb" class="flex items-center justify-center gap-2 text-sm font-semibold mb-5">
        <a href="{{ route('public.seerah') }}"
           class="inline-flex items-center gap-1.5 text-[#0056b3] dark:text-[#64CCC5] hover:underline focus:outline-none focus:ring-2 focus:ring-[#64CCC5] rounded">
          <i data-lucide="route" class="w-4 h-4"></i>{{ __('seerah.public.breadcrumb_seerah') }}
        </a>
        <i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" class="w-4 h-4 text-gray-300 dark:text-gray-600"></i>
        <span :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('seerah.public.events_heading') }}</span>
      </nav>

      <h1 class="text-center text-4xl sm:text-5xl lg:text-6xl font-black tracking-tight leading-[1.15]">
        <span class="bg-gradient-to-l from-[#004a9c] via-[#1a7a32] to-[#0a8079] dark:from-[#4aa3ff] dark:via-[#3fbf6b] dark:to-[#64CCC5] bg-clip-text text-transparent">
          {{ __('seerah.public.events_heading') }}
        </span>
      </h1>
      <p class="mt-3 mx-auto max-w-2xl text-center text-base sm:text-lg leading-relaxed" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
        {{ __('seerah.public.events_subheading') }}
      </p>

      {{-- Hairline meta strip — numbers as data, not as decoration. --}}
      <dl class="mt-6 mx-auto max-w-2xl grid grid-cols-3 divide-x rtl:divide-x-reverse rounded-2xl border overflow-hidden"
          :class="darkMode ? 'border-gray-800 divide-gray-800 bg-gray-900/40' : 'border-gray-200 divide-gray-200 bg-white'">
        @foreach ([
          ['icon' => 'book-open', 'value' => number_format($totalEvents), 'label' => __('seerah.public.results_count')],
          ['icon' => 'clock', 'value' => __('seerah.public.events_span'), 'label' => __('seerah.public.stat_span')],
          ['icon' => 'layers', 'value' => max(0, count($categoryPills) - 1), 'label' => __('seerah.public.categories_label')],
        ] as $meta)
        <div class="px-3 py-4 text-center">
          <dt class="flex items-center justify-center gap-1.5 text-[11px] font-bold" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
            <i data-lucide="{{ $meta['icon'] }}" class="w-3.5 h-3.5"></i>{{ $meta['label'] }}
          </dt>
          <dd class="mt-1 text-lg sm:text-xl font-black tabular-nums" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $meta['value'] }}</dd>
        </div>
        @endforeach
      </dl>
    </header>

    {{-- Controls: one GET form carries every filter, so a filtered view is a
         shareable URL and the back button restores it. --}}
    <form method="GET" action="{{ route('public.seerah.events') }}"
          @submit="loading = true"
          x-data="{ advanced: {{ ($filters['milestone'] || $filters['importance'] || $filters['pivotal'] || $filters['disputed']) ? 'true' : 'false' }} }"
          class="rounded-3xl border p-4 sm:p-5 mb-8 stagger-1 fade-in-up shadow-sm"
          :class="darkMode ? 'bg-gray-900/60 border-gray-800' : 'bg-white border-gray-200'">
      @if ($filters['type'] !== '')
        <input type="hidden" name="category" value="{{ $filters['type'] }}">
      @endif

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-3">
        <!-- Search -->
        <div class="lg:col-span-6">
          <label for="seerah-q" class="sr-only">{{ __('seerah.public.search_placeholder') }}</label>
          <div class="relative">
            <i data-lucide="search" class="absolute start-4 top-1/2 -translate-y-1/2 w-5 h-5 pointer-events-none" :class="darkMode ? 'text-gray-500' : 'text-gray-400'"></i>
            <input
              id="seerah-q"
              type="search"
              name="q"
              value="{{ $searchQuery }}"
              placeholder="{{ __('seerah.public.search_placeholder') }}"
              class="w-full h-12 ps-12 pe-4 rounded-xl border-2 focus:outline-none focus:ring-2 focus:ring-[#64CCC5] transition-all"
              :class="darkMode ? 'bg-gray-900 border-gray-800 text-white placeholder-gray-500' : 'bg-white border-gray-200 text-gray-900 placeholder-gray-400'"
            >
          </div>
        </div>

        <!-- Sort (server-side: the list really reorders) -->
        <div class="lg:col-span-3">
          <label for="seerah-sort" class="sr-only">{{ __('seerah.public.sort_label') }}</label>
          <select id="seerah-sort" name="sort" @change="$el.form.requestSubmit()"
            class="w-full h-12 px-4 rounded-xl border-2 focus:outline-none focus:ring-2 focus:ring-[#64CCC5] transition-all font-medium"
            :class="darkMode ? 'bg-gray-900 border-gray-800 text-white' : 'bg-white border-gray-200 text-gray-900'">
            <option value="asc" @selected($filters['sort'] === 'asc')>{{ __('seerah.public.sort_oldest_first') }}</option>
            <option value="desc" @selected($filters['sort'] === 'desc')>{{ __('seerah.public.sort_newest_first') }}</option>
          </select>
        </div>

        <div class="lg:col-span-3 grid grid-cols-2 gap-2">
          <button type="submit"
                  class="h-12 rounded-xl font-bold whitespace-nowrap text-white bg-gradient-to-r from-[#0056b3] to-[#218838] hover:shadow-lg hover:shadow-[#0056b3]/20 transition-all focus:outline-none focus:ring-2 focus:ring-[#64CCC5] focus:ring-offset-2">
            {{ __('seerah.public.apply_filters') }}
          </button>
          <button type="button" @click="advanced = !advanced" :aria-expanded="advanced.toString()"
                  class="h-12 rounded-xl border-2 font-semibold whitespace-nowrap inline-flex items-center justify-center gap-1.5 transition-all focus:outline-none focus:ring-2 focus:ring-[#64CCC5]"
                  :class="darkMode ? 'border-gray-800 text-gray-300 hover:bg-gray-800' : 'border-gray-200 text-gray-700 hover:bg-gray-100'">
            <i data-lucide="sliders-horizontal" class="w-4 h-4 flex-shrink-0"></i>
            {{-- Label always visible: an icon-only control here is exactly the thing being fixed. --}}
            <span>{{ __('seerah.public.more_filters') }}</span>
          </button>
        </div>
      </div>

      {{-- Progressive disclosure: the four secondary facets stay folded until asked for. --}}
      <div x-show="advanced" x-cloak x-collapse class="mt-4 pt-4 border-t grid grid-cols-1 sm:grid-cols-2 gap-3"
           :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
        <div>
          <label for="seerah-milestone" class="block text-xs font-semibold mb-1.5" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('seerah.public.milestone_label') }}</label>
          <select id="seerah-milestone" name="milestone"
            class="w-full h-12 px-4 rounded-xl border-2 focus:outline-none focus:ring-2 focus:ring-[#64CCC5] transition-all"
            :class="darkMode ? 'bg-gray-900 border-gray-800 text-white' : 'bg-white border-gray-200 text-gray-900'">
            <option value="">{{ __('seerah.public.filter_all') }}</option>
            @foreach ($milestoneOptions as $option)
              <option value="{{ $option['value'] }}" @selected($filters['milestone'] === $option['value'])>{{ $option['label'] }}</option>
            @endforeach
          </select>
        </div>

        <div>
          <label for="seerah-importance" class="block text-xs font-semibold mb-1.5" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('seerah.public.importance_label') }}</label>
          <select id="seerah-importance" name="importance"
            class="w-full h-12 px-4 rounded-xl border-2 focus:outline-none focus:ring-2 focus:ring-[#64CCC5] transition-all"
            :class="darkMode ? 'bg-gray-900 border-gray-800 text-white' : 'bg-white border-gray-200 text-gray-900'">
            <option value="">{{ __('seerah.public.filter_all') }}</option>
            @foreach ($importanceOptions as $option)
              <option value="{{ $option['value'] }}" @selected($filters['importance'] === $option['value'])>{{ $option['label'] }} ({{ $option['count'] }})</option>
            @endforeach
          </select>
        </div>

        {{-- Flag toggles: links, not checkboxes — one tap applies, and each is a real URL. --}}
        <div class="sm:col-span-2 flex flex-wrap gap-2">
          @foreach ([
            ['key' => 'pivotal', 'icon' => 'star', 'label' => __('seerah.public.pivotal_event'), 'count' => $facets['pivotal'], 'tone' => 'brand'],
            ['key' => 'disputed', 'icon' => 'scale', 'label' => __('seerah.public.dispute_badge'), 'count' => $facets['disputed'], 'tone' => 'danger'],
          ] as $toggle)
            @continue($toggle['count'] === 0)
            {{-- Static + conditional classes must live in ONE @class: a second
                 literal class attribute next to it is silently dropped. --}}
            <a href="{{ $filterUrl([$toggle['key'] => $filters[$toggle['key']] ? null : 1]) }}"
               aria-pressed="{{ $filters[$toggle['key']] ? 'true' : 'false' }}"
               @class([
                 'min-h-11 inline-flex items-center gap-1.5 px-3.5 rounded-xl border font-bold text-[13px] transition-colors focus:outline-none focus:ring-2 focus:ring-[#64CCC5]',
                 'bg-gradient-to-l from-[#0056b3] to-[#218838] text-white border-transparent shadow-sm' => $filters[$toggle['key']] && $toggle['tone'] === 'brand',
                 'bg-red-600 border-red-600 text-white shadow-sm shadow-red-600/25' => $filters[$toggle['key']] && $toggle['tone'] === 'danger',
                 'border-[#0056b3]/40 text-[#0056b3] dark:text-[#64CCC5] dark:border-[#64CCC5]/40 hover:bg-[#0056b3]/10' => ! $filters[$toggle['key']] && $toggle['tone'] === 'brand',
                 'border-red-500/40 text-red-700 dark:text-red-300 hover:bg-red-500/10' => ! $filters[$toggle['key']] && $toggle['tone'] === 'danger',
               ])>
              <i data-lucide="{{ $toggle['icon'] }}" class="w-3.5 h-3.5"></i>
              <span>{{ $toggle['label'] }}</span>
              <span class="px-1.5 rounded-full text-[11px] tabular-nums {{ $filters[$toggle['key']] ? 'bg-white/25' : ($toggle['tone'] === 'danger' ? 'bg-red-500/15' : 'bg-[#0056b3]/10 dark:bg-[#64CCC5]/15') }}">{{ $toggle['count'] }}</span>
            </a>
          @endforeach
        </div>
      </div>
    </form>

    @if ($activeChips !== [])
      <div class="flex flex-wrap items-center gap-2 mb-6 -mt-3 stagger-1 fade-in-up">
        <span class="text-xs font-semibold" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">{{ __('seerah.public.active_filters') }}</span>
        @foreach ($activeChips as $chip)
          <a href="{{ $chip['url'] }}"
             class="min-h-9 inline-flex items-center gap-1.5 ps-3 pe-2 rounded-full text-sm font-semibold border transition-all focus:outline-none focus:ring-2 focus:ring-[#64CCC5]"
             :class="darkMode ? 'bg-gray-900 border-gray-800 text-gray-200 hover:border-gray-600' : 'bg-white border-gray-200 text-gray-700 hover:border-gray-400'"
             aria-label="{{ __('seerah.public.remove_filter', ['filter' => $chip['label']]) }}">
            <span>{{ $chip['label'] }}</span>
            <i data-lucide="x" class="w-3.5 h-3.5 opacity-60"></i>
          </a>
        @endforeach
        <a href="{{ route('public.seerah.events') }}"
           class="min-h-9 inline-flex items-center gap-1.5 px-3 rounded-full text-sm font-semibold text-[#0056b3] dark:text-[#64CCC5] hover:underline focus:outline-none focus:ring-2 focus:ring-[#64CCC5]">
          <i data-lucide="x-circle" class="w-4 h-4"></i>{{ __('seerah.public.reset_filters') }}
        </a>
      </div>
    @endif

    <!-- Category Pills -->
    <div class="flex gap-3 overflow-x-auto custom-scrollbar pb-4 mb-8 stagger-2 fade-in-up">
      @foreach ($categoryPills as $cat)
        <a
          href="{{ $cat['url'] }}"
          @class([
            'filter-tag flex items-center gap-2 px-5 py-2.5 rounded-full font-semibold whitespace-nowrap transition-all',
            'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg scale-105' => $activeCategory === $cat['value'],
          ])
          @if ($activeCategory !== $cat['value'])
            :class="darkMode ? 'bg-gray-900 text-gray-400 hover:text-white hover:bg-gray-800' : 'bg-white text-gray-600 hover:text-gray-900 hover:bg-gray-100 shadow-sm'"
          @endif
        >
          <span>{{ $cat['label'] }}</span>
          <span @class([
                  'px-2 py-0.5 rounded-full text-xs font-bold',
                  'bg-white/20' => $activeCategory === $cat['value'],
                ])
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

        {{-- Reset lives with the active-filter chips above; a second one here just competes. --}}
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
            class="group relative block event-card rounded-2xl p-5 sm:p-6 border slide-in-right stagger-{{ ($index % 4) + 1 }} focus:outline-none focus-visible:ring-2 focus-visible:ring-[#64CCC5]"
            :class="darkMode ? 'bg-gray-900 border-gray-800 hover:border-[#64CCC5]/40' : 'bg-white border-gray-200 hover:border-[#0056b3]/30 shadow-sm'"
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
                <div class="w-[72px] h-[72px] sm:w-20 sm:h-20 rounded-2xl bg-gradient-to-br flex flex-col items-center justify-center text-white shadow-sm ring-1 ring-black/5 {{ $event['gradient'] }}">
                  <div class="text-2xl sm:text-[26px] font-black leading-none tabular-nums">{{ abs((int) $event['hijriYear']) }}</div>
                  <div class="mt-0.5 text-[10px] font-bold uppercase tracking-wide opacity-85">{{ (int) $event['hijriYear'] >= 0 ? __('seerah.public.hijri_suffix') : __('seerah.public.before_hijra_suffix') }}</div>
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

                  <!-- Pivotal -->
                  @if ($event['isPivotal'])
                    <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-gradient-to-l from-[#0056b3] to-[#218838] text-white border-transparent">
                      <i data-lucide="star" class="w-3 h-3"></i>
                      <span class="text-[11px] font-bold">{{ __('seerah.public.pivotal_event') }}</span>
                    </div>
                  @endif

                  <!-- Dating dispute -->
                  @if ($event['hasDispute'] ?? false)
                    <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-red-500/10 border border-red-500/30">
                      <i data-lucide="alert-triangle" class="w-3 h-3 text-red-600 dark:text-red-400"></i>
                      <span class="text-[11px] font-bold text-red-700 dark:text-red-300">{{ __('seerah.public.dispute_badge') }}</span>
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
    class="scroll-top-btn fixed bottom-8 end-8 z-40 w-12 h-12 rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-xl flex items-center justify-center"
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
