@extends('layouts.public')

@section('title', __('seerah.public.page_title'))
@section('meta_description', __('seerah.public.page_description'))

@push('structured_data')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "CollectionPage",
    "name": "{{ __('seerah.public.page_title') }}",
    "description": "{{ __('seerah.public.page_description') }}",
    "url": "{{ route('public.seerah') }}",
    "inLanguage": "{{ app()->getLocale() }}"
}
</script>
@endpush

@push('head')
<style>
    [x-cloak] { display: none !important; }

    /* Smooth transitions */
    * {
      transition: background-color 0.3s ease, color 0.3s ease;
    }

    /* Dark mode styles */
    .dark {
      background-color: #1a1a2e;
      color: #eee;
    }

    .dark .bg-white {
      background-color: #16213e !important;
    }

    .dark .text-\[\#212529\] {
      color: #eee !important;
    }

    .dark .bg-\[\#F8F9FA\] {
      background-color: #0f3460 !important;
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

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
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

    @keyframes float {
      0%, 100% { transform: translateY(0px); }
      50% { transform: translateY(-10px); }
    }

    @keyframes scaleIn {
      from {
        opacity: 0;
        transform: scale(0.8);
      }
      to {
        opacity: 1;
        transform: scale(1);
      }
    }

    @keyframes pulse-glow {
      0%, 100% {
        box-shadow: 0 0 20px rgba(0, 86, 179, 0.3);
      }
      50% {
        box-shadow: 0 0 40px rgba(0, 86, 179, 0.6);
      }
    }

    .animate-fade-in-up {
      animation: fadeInUp 0.8s ease-out forwards;
    }

    .animate-float {
      animation: float 3s ease-in-out infinite;
    }

    .pulse-glow {
      animation: pulse-glow 2s ease-in-out infinite;
    }

    /* Glassmorphism effect */
    .glass {
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
    }

    .dark .glass {
      background: rgba(22, 33, 62, 0.9);
    }

    /* Gradient text */
    .gradient-text {
      background: linear-gradient(135deg, #0056b3 0%, #218838 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    /* Hover effects */
    .hover-lift {
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .hover-lift:hover {
      transform: translateY(-8px);
      box-shadow: 0 20px 40px rgba(0, 86, 179, 0.2);
    }

    .timeline-line {
      background: linear-gradient(to bottom,
        rgba(0, 86, 179, 0.3) 0%,
        rgba(33, 136, 56, 0.3) 50%,
        rgba(0, 86, 179, 0.3) 100%
      );
    }

    .timeline-card {
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .timeline-card:hover {
      transform: scale(1.02) translateX(-5px);
      z-index: 10;
    }

    [dir="rtl"] .timeline-card:hover {
      transform: scale(1.02) translateX(5px);
    }

    .phase-marker {
      background: linear-gradient(135deg, #0056b3 0%, #218838 100%);
      box-shadow: 0 10px 40px rgba(0, 86, 179, 0.3);
    }

    .journey-hero {
      background: linear-gradient(135deg, rgba(0, 86, 179, 0.95) 0%, rgba(33, 136, 56, 0.9) 100%);
      position: relative;
      overflow: hidden;
    }

    .journey-hero::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-image:
        radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(33, 136, 56, 0.2) 0%, transparent 50%);
      animation: float 6s ease-in-out infinite;
    }

    .scroll-progress {
      position: fixed;
      top: 0;
      left: 0;
      height: 3px;
      background: linear-gradient(to right, #0056b3, #218838);
      z-index: 100;
      transition: width 0.1s ease-out;
    }

    .event-icon {
      transition: all 0.3s ease;
    }

    .timeline-card:hover .event-icon {
      transform: rotate(360deg) scale(1.2);
    }

    .stagger-1 { animation-delay: 0.1s; }
    .stagger-2 { animation-delay: 0.2s; }
    .stagger-3 { animation-delay: 0.3s; }
    .stagger-4 { animation-delay: 0.4s; }

    /* Horizontal Timeline Styles */
    .horizontal-timeline {
      display: flex;
      overflow-x: auto;
      scroll-snap-type: x mandatory;
      scroll-behavior: smooth;
      padding: 2rem 0;
    }

    .horizontal-timeline::-webkit-scrollbar {
      height: 8px;
    }

    .horizontal-timeline::-webkit-scrollbar-track {
      background: rgba(0, 0, 0, 0.1);
      border-radius: 10px;
    }

    .horizontal-timeline::-webkit-scrollbar-thumb {
      background: linear-gradient(to right, #0056b3, #218838);
      border-radius: 10px;
    }

    .horizontal-event {
      flex: 0 0 350px;
      scroll-snap-align: start;
      margin: 0 1rem;
    }

    /* Map container */
    .map-container {
      height: 400px;
      background: linear-gradient(135deg, #e3f2fd 0%, #c8e6c9 100%);
      border-radius: 1rem;
      position: relative;
      overflow: hidden;
    }

    .map-marker {
      position: absolute;
      width: 30px;
      height: 30px;
      background: #0056b3;
      border-radius: 50%;
      border: 3px solid white;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 4px 12px rgba(0, 86, 179, 0.4);
    }

    .map-marker:hover {
      transform: scale(1.3);
      z-index: 10;
    }

    .map-marker::after {
      content: '';
      position: absolute;
      width: 100%;
      height: 100%;
      border-radius: 50%;
      background: #0056b3;
      opacity: 0.3;
      animation: ping 2s cubic-bezier(0, 0, 0.2, 1) infinite;
    }

    @keyframes ping {
      75%, 100% {
        transform: scale(2);
        opacity: 0;
      }
    }

    /* Filter pills */
    .filter-pill {
      transition: all 0.3s ease;
    }

    .filter-pill.active {
      background: linear-gradient(135deg, #0056b3 0%, #218838 100%);
      color: white;
      transform: scale(1.05);
    }
</style>
@endpush

@section('content')

@php
    // Map the controller's real events to the field names the prototype's Alpine
    // template consumes verbatim (id, phase, category, year, hijri, title, location,
    // color, icon, featured) — plus url/typeLabel/description for the real data.
    $timelineEvents = collect($events)->map(fn (array $e) => [
        'id' => $e['slug'],
        'phase' => $e['phase'],
        'category' => $e['type'],
        'year' => $e['gregorianYear'],
        'hijri' => $e['hijriYear'],
        'hijriDate' => $e['hijriDate'],
        'title' => $e['title'],
        'location' => $e['location'],
        'color' => $e['gradient'],
        'icon' => $e['icon'],
        'featured' => $e['isPivotal'],
        'typeLabel' => $e['typeLabel'],
        'url' => $e['url'],
    ])->values()->all();

    $eventsJson = json_encode($timelineEvents, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS);
    $phaseLabels = collect($phases)->pluck('label', 'value');
    $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true);
    $forwardArrow = $isRtl ? 'arrow-left' : 'arrow-right';
@endphp

<div
  x-data="{
    scrollProgress: 0,
    activePhase: null,
    searchQuery: '',
    selectedCategory: 'all',
    viewMode: 'vertical',
    showMap: false,
    events: {{ $eventsJson }},
    phaseLabels: @js($phaseLabels),
    dateLabels: @js([
        'gregorian' => __('seerah.public.gregorian_suffix'),
        'hijriYear' => __('seerah.public.hijri_year_label'),
    ]),

    get filteredEvents() {
      return this.events.filter(event => {
        const matchesCategory = this.selectedCategory === 'all' || event.category === this.selectedCategory;
        const matchesSearch = this.searchQuery === '' ||
          (event.title || '').includes(this.searchQuery) ||
          (event.location || '').includes(this.searchQuery);
        return matchesCategory && matchesSearch;
      });
    },

    updateScrollProgress() {
      const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
      const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
      this.scrollProgress = (winScroll / height) * 100;
    },

    scrollToPhase(phaseId) {
      document.getElementById(phaseId)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    },

    phaseTitle(phase) {
      return this.phaseLabels[phase] || '';
    },

    getEventDetails(eventId) {
      return this.events.find(e => e.id === eventId);
    }
  }"
  @scroll.window="updateScrollProgress()"
  x-init="$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); }); $watch('filteredEvents', () => $nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); }))"
>

  <!-- Scroll Progress Bar -->
  <div class="scroll-progress" :style="`width: ${scrollProgress}%`"></div>

  <!-- Hero Section -->
  <section class="journey-hero relative py-20 sm:py-32">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
      <div class="text-center max-w-4xl mx-auto">
        <div class="inline-block mb-6 px-6 py-2 rounded-full bg-white/10 backdrop-blur-lg border border-white/20 animate-fade-in-up">
          <span class="text-white text-sm font-semibold">{{ __('seerah.public.hero_tagline') }}</span>
        </div>

        <h1 class="text-4xl sm:text-6xl font-bold text-white mb-6 animate-fade-in-up">
          {{ __('seerah.public.hero_heading') }}
          <span class="block text-3xl sm:text-5xl mt-2 text-[#64CCC5]">{{ __('seerah.public.hero_heading_accent') }}</span>
        </h1>

        <p class="text-xl text-white/90 mb-8 max-w-2xl mx-auto animate-fade-in-up stagger-1">
          {{ __('seerah.public.hero_subtitle') }}
        </p>

        <!-- Quick Stats -->
        <div class="grid grid-cols-3 gap-6 max-w-3xl mx-auto animate-fade-in-up stagger-2">
          <div class="text-center">
            <div class="text-4xl font-bold text-white mb-2">63</div>
            <div class="text-white/80 text-sm">{{ __('seerah.public.stat_years_of_life') }}</div>
          </div>
          <div class="text-center">
            <div class="text-4xl font-bold text-white mb-2">23</div>
            <div class="text-white/80 text-sm">{{ __('seerah.public.stat_years_of_dawah') }}</div>
          </div>
          <div class="text-center">
            <div class="text-4xl font-bold text-white mb-2">{{ $stats['total'] }}</div>
            <div class="text-white/80 text-sm">{{ __('seerah.public.stat_historical_events') }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Decorative Elements -->
    <div class="absolute bottom-0 left-0 right-0 h-20 bg-gradient-to-t from-[#F8F9FA] dark:from-[#1a1a2e]"></div>
  </section>

  <!-- Search & Filter Section -->
  <section class="sticky top-20 z-40 bg-[#F8F9FA] dark:bg-[#1a1a2e] py-4 border-b border-[#DEE2E6] dark:border-[#0f3460]">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Search Bar -->
      <div class="mb-4">
        <div class="relative max-w-2xl mx-auto">
          <input
            type="search"
            x-model="searchQuery"
            placeholder="{{ __('seerah.public.search_placeholder') }}"
            class="w-full px-6 py-3 pe-12 rounded-full bg-white dark:bg-[#16213e] border border-[#DEE2E6] dark:border-[#0f3460] focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
          />
          <i data-lucide="search" class="absolute end-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>
        </div>
      </div>

      <!-- Category Filters -->
      <div class="flex items-center justify-center gap-3 overflow-x-auto pb-2">
        @foreach ($categoryPills as $pill)
        <button
          @click="selectedCategory = '{{ $pill['value'] }}'"
          class="filter-pill px-5 py-2 rounded-full font-semibold whitespace-nowrap text-sm"
          :class="selectedCategory === '{{ $pill['value'] }}' ? 'active' : 'bg-[#E9ECEF] dark:bg-[#16213e] hover:bg-[#DEE2E6] dark:hover:bg-[#0f3460]'"
        >
          <i data-lucide="{{ $pill['icon'] }}" class="w-4 h-4 inline-block ml-1"></i>
          {{ $pill['label'] }}
        </button>
        @endforeach
      </div>
    </div>
  </section>

  <!-- Interactive Map (Conditional) -->
  <section x-show="showMap" x-transition class="py-8 bg-white dark:bg-[#16213e]">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
      <h2 class="text-2xl font-bold mb-6 text-center">{{ __('seerah.public.map_title') }}</h2>
      <div class="map-container relative">
        <!-- Simple map visualization -->
        <div class="absolute inset-0 flex items-center justify-center text-gray-500">
          <div class="text-center">
            <i data-lucide="map" class="w-16 h-16 mx-auto mb-4 text-[#0056b3]"></i>
            <p class="text-lg font-semibold">{{ __('seerah.public.map_region') }}</p>
            <p class="text-sm mt-2">{{ __('seerah.public.map_hint') }}</p>
          </div>
        </div>

        <!-- Map markers for each event -->
        <template x-for="event in filteredEvents" :key="event.id">
          <div
            class="map-marker"
            :style="`top: ${event.lat}%; left: ${event.lng}%;`"
            :title="event.title"
            @click="scrollToPhase('event-' + event.id)"
          >
          </div>
        </template>
      </div>
    </div>
  </section>

  <!-- Phase Navigation -->
  <section class="sticky top-36 z-30 bg-[#F8F9FA] dark:bg-[#1a1a2e] py-4">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-center gap-4 overflow-x-auto pb-2">
        @foreach ($phases as $phase)
        <button
          @click="scrollToPhase('phase-{{ $phase['value'] }}')"
          class="px-6 py-2 rounded-full font-semibold whitespace-nowrap transition-all bg-[#E9ECEF] dark:bg-[#16213e] hover:bg-[#DEE2E6] dark:hover:bg-[#0f3460]"
        >
          <i data-lucide="{{ $phase['icon'] }}" class="w-4 h-4 inline-block ml-2"></i>
          {{ $phase['label'] }}
        </button>
        @endforeach
      </div>
    </div>
  </section>

  <!-- Timeline Content -->
  <main class="py-16 sm:py-24">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-6xl">

      <!-- Vertical Timeline View -->
      <div x-show="viewMode === 'vertical'" class="relative">

        <!-- Central Timeline Line -->
        <div class="absolute hidden lg:block timeline-line w-1 top-0 bottom-0 start-1/2 -translate-x-1/2"></div>

        <template x-for="(event, index) in filteredEvents" :key="event.id">
          <div
            :id="'event-' + event.id"
            class="relative mb-16 animate-fade-in-up"
            :class="'stagger-' + ((index % 4) + 1)"
          >
            <!-- Phase Header (show at phase start) -->
            <div
              x-show="index === 0 || filteredEvents[index - 1].phase !== event.phase"
              class="flex items-center justify-center mb-12"
              :id="'phase-' + event.phase"
            >
              <div class="phase-marker text-white px-8 py-4 rounded-2xl text-center">
                <div class="flex items-center gap-3 justify-center">
                  <i :data-lucide="event.phase === 'early_life' ? 'sunrise' : event.phase === 'mecca' ? 'book-open' : event.phase === 'hijra' ? 'map' : 'heart'" class="w-6 h-6"></i>
                  <div>
                    <h2 class="text-2xl font-bold" x-text="phaseTitle(event.phase)"></h2>
                  </div>
                </div>
              </div>
            </div>

            <!-- Event Card -->
            <div
              class="flex flex-col items-center"
              :class="index % 2 === 0 ? 'lg:flex-row' : 'lg:flex-row-reverse'"
            >
              <!-- Timeline Dot (Desktop) -->
              <div class="hidden lg:block absolute start-1/2 -translate-x-1/2 w-12 h-12 rounded-full bg-gradient-to-br from-[#0056b3] to-[#218838] border-4 border-[#F8F9FA] dark:border-[#1a1a2e] pulse-glow" x-show="event.featured">
                <div class="w-full h-full flex items-center justify-center">
                  <i :data-lucide="event.icon" class="w-6 h-6 text-white event-icon"></i>
                </div>
              </div>
              <div class="hidden lg:block absolute start-1/2 -translate-x-1/2 w-12 h-12 rounded-full bg-gradient-to-br from-[#0056b3] to-[#218838] border-4 border-[#F8F9FA] dark:border-[#1a1a2e]" x-show="!event.featured">
                <div class="w-full h-full flex items-center justify-center">
                  <i :data-lucide="event.icon" class="w-6 h-6 text-white event-icon"></i>
                </div>
              </div>

              <!-- Date Side -->
              <div
                class="hidden lg:flex w-1/2 justify-end pe-20"
                :class="index % 2 === 0 ? '' : 'hidden'"
                x-show="index % 2 === 0"
              >
                <div class="text-end">
                  <div class="inline-block px-4 py-2 rounded-lg bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-bold mb-2" x-text="event.year + ' ' + dateLabels.gregorian">
                  </div>
                  <div class="text-lg font-semibold text-gray-600 dark:text-gray-300" x-text="event.hijri + ' ' + dateLabels.hijriYear"></div>
                </div>
              </div>

              <div
                class="hidden lg:flex w-1/2 justify-start ps-20"
                :class="index % 2 === 1 ? '' : 'hidden'"
                x-show="index % 2 === 1"
              >
                <div class="text-start">
                  <div class="inline-block px-4 py-2 rounded-lg bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-bold mb-2" x-text="event.year + ' ' + dateLabels.gregorian">
                  </div>
                  <div class="text-lg font-semibold text-gray-600 dark:text-gray-300" x-text="event.hijri + ' ' + dateLabels.hijriYear"></div>
                </div>
              </div>

              <!-- Content Side -->
              <div
                class="w-full lg:w-1/2"
                :class="index % 2 === 0 ? 'lg:ps-20' : 'lg:pe-20'"
              >
                <div class="timeline-card hover-lift rounded-2xl overflow-hidden shadow-xl bg-white dark:bg-[#16213e]">
                  <!-- Event Image -->
                  <div class="relative h-48 bg-gradient-to-br overflow-hidden" :class="event.color">
                    <div class="absolute inset-0 flex items-center justify-center">
                      <i :data-lucide="event.icon" class="w-20 h-20 text-white/30"></i>
                    </div>
                    <div class="absolute top-4 right-4" x-show="event.featured">
                      <span class="px-3 py-1 rounded-full text-xs font-semibold bg-white/20 backdrop-blur-lg text-white border border-white/30 pulse-glow">
                        {{ __('seerah.public.pivotal_event') }}
                      </span>
                    </div>
                  </div>

                  <!-- Event Content -->
                  <div class="p-6">
                    <!-- Date (Mobile) -->
                    <div class="lg:hidden mb-4">
                      <span class="inline-block px-4 py-2 rounded-lg bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-bold text-sm" x-text="event.year + ' ' + dateLabels.gregorian + ' - ' + event.hijri + ' ' + dateLabels.hijriYear">
                      </span>
                    </div>

                    <h3 class="text-2xl font-bold mb-3 text-[#212529] dark:text-white" x-text="event.title"></h3>

                    <div class="flex items-center gap-4 mb-4 text-sm text-gray-600 dark:text-gray-400">
                      <span class="flex items-center gap-1">
                        <i data-lucide="map-pin" class="w-4 h-4"></i>
                        <span x-text="event.location"></span>
                      </span>
                      <span class="flex items-center gap-1">
                        <i data-lucide="user" class="w-4 h-4"></i>
                        <span x-text="event.typeLabel"></span>
                      </span>
                    </div>

                    <a
                      :href="event.url"
                      class="inline-flex items-center gap-2 px-6 py-3 rounded-lg bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-semibold hover:shadow-lg transition-all hover-lift"
                    >
                      <span>{{ __('seerah.public.read_more') }}</span>
                      <i data-lucide="{{ $forwardArrow }}" class="w-4 h-4"></i>
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </template>

      </div>

      <!-- Horizontal Timeline View -->
      <div x-show="viewMode === 'horizontal'" class="horizontal-timeline">
        <template x-for="event in filteredEvents" :key="event.id">
          <div class="horizontal-event">
            <div class="timeline-card hover-lift rounded-2xl overflow-hidden shadow-xl bg-white dark:bg-[#16213e]">
              <div class="relative h-48 bg-gradient-to-br overflow-hidden" :class="event.color">
                <div class="absolute inset-0 flex items-center justify-center">
                  <i :data-lucide="event.icon" class="w-20 h-20 text-white/30"></i>
                </div>
              </div>
              <div class="p-6">
                <div class="mb-3">
                  <span class="inline-block px-3 py-1 rounded-lg bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-bold text-xs" x-text="event.year + ' ' + dateLabels.gregorian"></span>
                </div>
                <h3 class="text-xl font-bold mb-3 text-[#212529] dark:text-white" x-text="event.title"></h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4" x-text="event.location"></p>
                <a :href="event.url" class="inline-flex items-center gap-2 text-[#0056b3] dark:text-[#64CCC5] font-semibold text-sm">
                  <span>{{ __('seerah.public.read_more') }}</span>
                  <i data-lucide="{{ $forwardArrow }}" class="w-4 h-4"></i>
                </a>
              </div>
            </div>
          </div>
        </template>
      </div>

      <!-- No Results Message -->
      <div x-show="filteredEvents.length === 0" class="text-center py-20">
        <i data-lucide="search-x" class="w-20 h-20 mx-auto mb-6 text-gray-400"></i>
        <h3 class="text-2xl font-bold mb-2">{{ __('seerah.public.no_results_title') }}</h3>
        <p class="text-gray-600 dark:text-gray-400">{{ __('seerah.public.no_results_hint') }}</p>
      </div>

      <!-- CTA Section -->
      <div class="mt-20 text-center" x-show="filteredEvents.length > 0">
        <div class="glass rounded-3xl p-12 relative overflow-hidden">
          <div class="relative z-10">
            <i data-lucide="book-heart" class="w-16 h-16 mx-auto mb-6 text-[#0056b3]"></i>
            <h2 class="text-3xl font-bold mb-4 text-[#212529] dark:text-white">
              {{ __('seerah.public.cta_title') }}
            </h2>
            <p class="text-lg mb-8 max-w-2xl mx-auto text-gray-700 dark:text-gray-300">
              {{ __('seerah.public.cta_subtitle') }}
            </p>
            <div class="flex items-center justify-center gap-4 flex-wrap">
              <a
                href="{{ route('public.seerah.events') }}"
                class="inline-flex items-center gap-2 px-8 py-4 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-bold hover:shadow-2xl transition-all hover-lift"
              >
                <span>{{ __('seerah.public.browse_all_events') }}</span>
                <i data-lucide="{{ $forwardArrow }}" class="w-5 h-5"></i>
              </a>
              <a
                href="{{ route('public.media') }}"
                class="inline-flex items-center gap-2 px-8 py-4 rounded-xl font-bold transition-all bg-[#E9ECEF] dark:bg-[#16213e] hover:bg-[#DEE2E6] dark:hover:bg-[#0f3460]"
              >
                <span>{{ __('seerah.public.cta_watch_media') }}</span>
                <i data-lucide="play-circle" class="w-5 h-5"></i>
              </a>
            </div>
          </div>
        </div>
      </div>

    </div>
  </main>

</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') { lucide.createIcons(); }
    });
</script>
@endpush

@endsection
