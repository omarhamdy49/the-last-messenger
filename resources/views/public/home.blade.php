@extends('layouts.public')

@section('title', __('site.site_name'))
@section('meta_description', __('site.hero_subtitle'))

@php
    $locale = app()->getLocale();
    $isRtl  = in_array($locale, ['ar', 'ur'], true);
    $sahabaUrl = Route::has('public.sahaba') ? route('public.sahaba') : '#';
    $seerahUrl = Route::has('public.seerah') ? route('public.seerah') : '#';
    $hadithUrl = Route::has('public.hadith') ? route('public.hadith') : '#';
    $mediaUrl  = Route::has('public.media')  ? route('public.media')  : '#';
    $quranUrl  = Route::has('public.quran')  ? route('public.quran')  : '#';
    $genealogyUrl = Route::has('public.genealogy') ? route('public.genealogy') : '#';
    $searchUrl = Route::has('public.search') ? route('public.search') : '#';
    $registerUrl = Route::has('register') ? route('register') : '#';

    // Module cards arrive DB-managed from HomeContentService (controller prop).
    // The maps below are purely presentational: Tailwind can't compile classes
    // from runtime values, so each known accent hex maps to its prototype
    // class set (gradients, hover borders) with a brand-gradient fallback.
    $moduleCards = $moduleCards ?? [];
    $quickFilters = $quickFilters ?? [];
    $stats = $stats ?? [];

    $cardStyles = [
        '#0056b3' => ['badgeBg' => 'bg-[#0056b3]', 'iconGrad' => 'from-[#0056b3] to-[#004c9e]', 'hoverBorder' => 'hover:border-[#0056b3]/50', 'hoverGrad' => 'from-[#0056b3]/10', 'cornerGrad' => 'from-[#0056b3]/20'],
        '#218838' => ['badgeBg' => 'bg-[#218838]', 'iconGrad' => 'from-[#218838] to-[#1e7e34]', 'hoverBorder' => 'hover:border-[#218838]/50', 'hoverGrad' => 'from-[#218838]/10', 'cornerGrad' => 'from-[#218838]/20'],
        '#FF7F50' => ['badgeBg' => 'bg-[#FF7F50]', 'iconGrad' => 'from-[#FF7F50] to-[#ff6347]', 'hoverBorder' => 'hover:border-[#FF7F50]/50', 'hoverGrad' => 'from-[#FF7F50]/10', 'cornerGrad' => 'from-[#FF7F50]/20'],
        '#6f42c1' => ['badgeBg' => 'bg-[#6f42c1]', 'iconGrad' => 'from-[#6f42c1] to-[#5a32a3]', 'hoverBorder' => 'hover:border-[#6f42c1]/50', 'hoverGrad' => 'from-[#6f42c1]/10', 'cornerGrad' => 'from-[#6f42c1]/20'],
        '#17a2b8' => ['badgeBg' => 'bg-[#17a2b8]', 'iconGrad' => 'from-[#17a2b8] to-[#138496]', 'hoverBorder' => 'hover:border-[#17a2b8]/50', 'hoverGrad' => 'from-[#17a2b8]/10', 'cornerGrad' => 'from-[#17a2b8]/20'],
        '#fd7e14' => ['badgeBg' => 'bg-[#fd7e14]', 'iconGrad' => 'from-[#fd7e14] to-[#e8590c]', 'hoverBorder' => 'hover:border-[#fd7e14]/50', 'hoverGrad' => 'from-[#fd7e14]/10', 'cornerGrad' => 'from-[#fd7e14]/20'],
    ];
    $fallbackCardStyle = ['badgeBg' => 'bg-[#0056b3]', 'iconGrad' => 'from-[#0056b3] to-[#218838]', 'hoverBorder' => 'hover:border-[#0056b3]/50', 'hoverGrad' => 'from-[#0056b3]/10', 'cornerGrad' => 'from-[#0056b3]/20'];

    // Honest per-card stat lines from real derived counts (no fabricated
    // numbers); cards without a real count simply omit the stat row.
    $cardStats = [
        'seerah' => ['icon' => 'calendar', 'value' => $stats['seerah_events'] ?? 0, 'label' => __('site.events_count')],
        'hadith' => ['icon' => 'book-marked', 'value' => $stats['hadiths'] ?? 0, 'label' => __('site.hadith_count')],
        'quran' => ['icon' => 'file-text', 'value' => $stats['surahs'] ?? 114, 'label' => __('site.surahs')],
    ];

    // Resolve a card/chip target: named route, absolute/relative URL, or '#'.
    $contentUrl = function (?string $target): string {
        if ($target !== null && Route::has($target)) {
            return route($target);
        }

        if ($target !== null && (str_starts_with($target, 'http') || str_starts_with($target, '/'))) {
            return $target;
        }

        return '#';
    };

    // Sahaba carousel accent colours — matched to the prototype order
    $sahabaColors = [
        ['color' => 'from-[#6f42c1] to-[#5a32a3]', 'badge' => null, 'badgeIcon' => 'star',   'badgeColor' => 'from-yellow-400 to-yellow-600',   'accentColor' => 'text-[#6f42c1]'],
        ['color' => 'from-[#218838] to-[#1e7e34]', 'badge' => null, 'badgeIcon' => 'shield', 'badgeColor' => 'from-emerald-400 to-emerald-600',  'accentColor' => 'text-[#218838]'],
        ['color' => 'from-[#17a2b8] to-[#138496]', 'badge' => null, 'badgeIcon' => 'heart',  'badgeColor' => 'from-cyan-400 to-cyan-600',        'accentColor' => 'text-[#17a2b8]'],
        ['color' => 'from-[#0056b3] to-[#004c9e]', 'badge' => null, 'badgeIcon' => 'sword',  'badgeColor' => 'from-blue-400 to-blue-600',        'accentColor' => 'text-[#0056b3]'],
        ['color' => 'from-[#FF7F50] to-[#ff6347]', 'badge' => null, 'badgeIcon' => 'crown',  'badgeColor' => 'from-pink-400 to-pink-600',        'accentColor' => 'text-[#FF7F50]'],
        ['color' => 'from-[#e83e8c] to-[#d63384]', 'badge' => null, 'badgeIcon' => 'star',   'badgeColor' => 'from-rose-400 to-rose-600',        'accentColor' => 'text-[#e83e8c]'],
        ['color' => 'from-[#6f42c1] to-[#5a32a3]', 'badge' => null, 'badgeIcon' => 'shield', 'badgeColor' => 'from-violet-400 to-violet-600',    'accentColor' => 'text-[#6f42c1]'],
        ['color' => 'from-[#218838] to-[#1e7e34]', 'badge' => null, 'badgeIcon' => 'heart',  'badgeColor' => 'from-green-400 to-green-600',      'accentColor' => 'text-[#218838]'],
    ];
@endphp

@section('content')

{{-- ===================================================================
     SECTION 1 — PREMIUM HERO
     Verbatim port of index-enhanced.html lines ~544-650
     =================================================================== --}}
<section class="relative py-20 md:py-32 pb-32 md:pb-40 overflow-hidden">
    {{-- Background Image with Ken Burns --}}
    <div class="absolute inset-0 w-full h-full">
        <img class="w-full h-full object-cover ken-burns-subtle"
             alt="{{ __('site.hero_image_alt') }}"
             src="{{ asset('images/site/rasool-allah-hero-section-bg.jpg') }}" />
        <div class="absolute inset-0 bg-black/50"></div>
    </div>

    {{-- Hero Content --}}
    <div class="relative z-10 container mx-auto px-4 sm:px-6 lg:px-8 text-center">
        {{-- Title --}}
        <h1 class="text-4xl md:text-6xl lg:text-7xl font-extrabold tracking-tight text-white drop-shadow-2xl fade-slide-up mb-6"
            style="animation-delay: 0.1s;">
            {{ __('site.hero_title') }}
        </h1>

        {{-- Subtitle --}}
        <p class="mt-6 max-w-3xl mx-auto text-lg md:text-xl text-gray-100 drop-shadow-lg fade-slide-up"
           style="animation-delay: 0.3s;">
            {{ __('site.hero_subtitle') }}
        </p>

        {{-- Premium Search Bar --}}
        <div class="mt-10 max-w-2xl mx-auto fade-slide-up" style="animation-delay: 0.5s;">
            {{-- Tailwind dark: variants, not Alpine :class — the hero has no x-data
                 ancestor, so these bindings never evaluated and the search bar
                 rendered with no glass background (plain white input). --}}
            <form action="{{ $searchUrl }}" method="GET"
                  class="relative search-bar-wrapper backdrop-blur-lg rounded-full shadow-2xl overflow-hidden bg-gray-900/80 border border-gray-700 dark:bg-white/10 dark:border-white/20">
                <div class="absolute inset-y-0 start-0 flex items-center ps-5 pointer-events-none">
                    <i class="w-5 h-5 text-gray-400 dark:text-gray-300"
                       data-lucide="search"></i>
                </div>
                <input type="search"
                       name="q"
                       placeholder="{{ __('site.hero_search_placeholder') }}"
                       class="w-full p-4 ps-14 text-base sm:text-lg border-2 border-transparent rounded-full text-white focus:outline-none transition-all bg-gray-900/40 placeholder-gray-400 focus:bg-gray-900/60 focus:border-gray-600 dark:bg-white/10 dark:placeholder-white/70 dark:focus:bg-white/20 dark:focus:border-white/40" />
                <button type="submit"
                        class="absolute end-2.5 top-1/2 -translate-y-1/2 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-bold px-8 py-3 rounded-full hover-scale shadow-lg">
                    {{ __('site.search') }}
                </button>
            </form>

            {{-- Quick Filter Tags — DB-managed chips (HomeContentService) --}}
            @if (count($quickFilters) > 0)
                <div class="mt-6 flex flex-wrap justify-center gap-3">
                    @foreach ($quickFilters as $filter)
                        @php
                            $filterHref = !empty($filter['target_query'])
                                ? $searchUrl.'?q='.urlencode($filter['target_query'])
                                : $contentUrl($filter['target_url'] ?? null);
                        @endphp
                        {{-- Tailwind dark: variants, not Alpine :class — the hero has no
                             x-data ancestor, so bindings here never evaluate (chips rendered
                             with no background at all). --}}
                        <a href="{{ $filterHref }}"
                           class="px-4 py-2 backdrop-blur-md rounded-full text-sm font-semibold transition-all shadow-sm hover:shadow-md bg-gray-900/60 text-white hover:bg-gray-900/80 border border-gray-700 hover:border-gray-600 dark:bg-white/10 dark:text-white/90 dark:hover:bg-white/20 dark:border-white/20 dark:hover:border-white/40">
                            {{ $filter['label'] }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Hero Module Grid Cards --}}
        <div class="mt-16 max-w-6xl mx-auto fade-slide-up" style="animation-delay: 0.7s;">
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-3 gap-6 md:gap-8">
                {{-- Large Seerah Card --}}
                <a href="{{ $seerahUrl }}"
                   class="md:col-span-2 lg:col-span-2 relative rounded-2xl overflow-hidden shadow-2xl group min-h-[280px] flex items-end p-8 text-white hover-scale">
                    <img class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 ease-in-out"
                         alt="{{ __('site.seerah') }}"
                         src="{{ asset('images/site/seerah-thumb.png') }}" />
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent"></div>
                    <div class="relative z-10">
                        <div class="inline-flex items-center space-s-2 backdrop-blur-sm px-4 py-2 rounded-full mb-4 bg-gray-900/60 border border-gray-700 dark:bg-white/20 dark:border-white/30">
                            <i class="w-4 h-4" data-lucide="book-open"></i>
                            <span class="text-sm font-semibold">{{ __('site.featured') }}</span>
                        </div>
                        <h2 class="text-3xl md:text-4xl font-bold mb-3">{{ __('site.seerah') }}</h2>
                        <p class="max-w-md text-white/90 leading-relaxed">{{ __('site.seerah_hero_desc') }}</p>
                        <div class="mt-4 font-semibold flex items-center {{ $isRtl ? 'group-hover:-translate-x-2' : 'group-hover:translate-x-2' }} transition-transform">
                            {{ __('site.discover_seerah') }}
                            <i class="w-5 h-5 ms-2" data-lucide="{{ $isRtl ? 'arrow-left' : 'arrow-right' }}"></i>
                        </div>
                    </div>
                </a>

                {{-- Small Cards --}}
                <div class="grid grid-cols-1 gap-6 md:gap-8">
                    {{-- Hadith Card --}}
                    <a href="{{ $hadithUrl }}"
                       class="backdrop-blur-lg rounded-2xl shadow-2xl p-6 flex flex-col items-center justify-center text-center group hover-scale transition-all bg-gray-900/70 border border-gray-700 hover:border-gray-600 dark:bg-white/10 dark:border-white/20 dark:hover:border-white/40">
                        <div class="w-16 h-16 bg-gradient-to-br from-[#218838] to-[#1a6b2a] rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform shadow-lg">
                            <i class="w-8 h-8 text-white" data-lucide="scroll-text"></i>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-2">{{ __('site.hadith') }}</h3>
                        <p class="text-sm text-white/80">{{ __('site.hadith_hero_sub') }}</p>
                    </a>

                    {{-- Media Card --}}
                    <a href="{{ $mediaUrl }}"
                       class="backdrop-blur-lg rounded-2xl shadow-2xl p-6 flex flex-col items-center justify-center text-center group hover-scale transition-all bg-gray-900/70 border border-gray-700 hover:border-gray-600 dark:bg-white/10 dark:border-white/20 dark:hover:border-white/40">
                        <div class="w-16 h-16 bg-gradient-to-br from-[#FF7F50] to-[#ff6347] rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform shadow-lg">
                            <i class="w-8 h-8 text-white" data-lucide="play-circle"></i>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-2">{{ __('site.media') }}</h3>
                        <p class="text-sm text-white/80">{{ __('site.media_hero_sub') }}</p>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ===================================================================
     SECTION 2 — PREMIUM SAHABA CAROUSEL
     Verbatim port of index-enhanced.html lines ~652-960
     =================================================================== --}}
<section class="py-16 sm:py-20 bg-gradient-to-br from-gray-50 to-white dark:from-[#1a1a2e] dark:to-[#16213e]">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Section Header --}}
        <div class="text-center mb-12 fade-slide-up">
            <div class="inline-block mb-4">
                <span class="bg-gradient-to-r from-[#6f42c1] to-[#8b5cf6] text-white px-6 py-2 rounded-full text-sm font-bold shadow-lg">{{ __('site.noble_companions') }}</span>
            </div>
            <h2 class="text-3xl md:text-4xl font-black mb-4 bg-gradient-to-r from-[#6f42c1] to-[#8b5cf6] bg-clip-text text-transparent">{{ __('site.companions_of_prophet') }}</h2>
            <p class="text-lg text-gray-600 dark:text-gray-300 max-w-2xl mx-auto">{{ __('site.companions_subtitle') }}</p>
        </div>

        {{-- Carousel --}}
        <div class="relative"
             x-data="{
               scrollContainer: null,
               canScrollLeft: false,
               canScrollRight: true,
               init() {
                 this.scrollContainer = this.$refs.scrollContainer;
                 this.checkScroll();
                 this.scrollContainer.addEventListener('scroll', () => this.checkScroll());
                 window.addEventListener('resize', () => this.checkScroll());
               },
               checkScroll() {
                 if (!this.scrollContainer) return;
                 this.canScrollLeft  = this.scrollContainer.scrollLeft > 20;
                 this.canScrollRight = this.scrollContainer.scrollLeft < (this.scrollContainer.scrollWidth - this.scrollContainer.clientWidth - 20);
               },
               scrollBack() {
                 const c = this.scrollContainer;
                 const card = c.querySelector('.sahaba-card');
                 if (!card) return;
                 c.scrollBy({ left: -(card.offsetWidth + 24) * 2, behavior: 'smooth' });
               },
               scrollForward() {
                 const c = this.scrollContainer;
                 const card = c.querySelector('.sahaba-card');
                 if (!card) return;
                 c.scrollBy({ left: (card.offsetWidth + 24) * 2, behavior: 'smooth' });
               }
             }">

            {{-- Scroll container --}}
            <div class="relative overflow-hidden rounded-2xl">
                <div class="flex overflow-x-auto gap-6 pb-4 scroll-smooth"
                     style="scrollbar-width: none; -ms-overflow-style: none;"
                     x-ref="scrollContainer">
                    <style>.home-sahaba-row::-webkit-scrollbar{display:none}</style>

                    @forelse ($featuredSahaba as $index => $sahabi)
                        @php
                            $palette = $sahabaColors[$index % count($sahabaColors)];
                            // Try to pull the companion's laqab as badge text
                            $badgeText = $sahabi->getTranslation('laqab', $locale)
                                      ?: $sahabi->getTranslation('laqab', 'ar');
                        @endphp
                        <x-site.sahaba-card
                            :sahabi="$sahabi"
                            :color="$palette['color']"
                            :badge="$badgeText"
                            :badgeIcon="$palette['badgeIcon']"
                            :badgeColor="$palette['badgeColor']"
                            :accentColor="$palette['accentColor']"
                        />
                    @empty
                        {{-- Fallback skeleton cards when no sahaba in DB yet --}}
                        @for ($i = 0; $i < 5; $i++)
                            <div class="flex-shrink-0 w-72 sahaba-card">
                                <div class="rounded-2xl bg-gradient-to-br from-[#F8F9FA] to-white dark:from-[#16213e] dark:to-[#1a1a2e] shadow-xl overflow-hidden animate-pulse">
                                    <div class="h-64 bg-gray-200 dark:bg-gray-700"></div>
                                    <div class="p-5 space-y-3">
                                        <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded"></div>
                                        <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
                                    </div>
                                </div>
                            </div>
                        @endfor
                    @endforelse
                </div>
            </div>

            {{-- Left arrow (visually right in RTL) --}}
            <button x-show="canScrollLeft"
                    @click="scrollBack()"
                    x-transition
                    class="absolute end-0 top-1/2 -translate-y-1/2 z-10 w-14 h-14 rounded-full glass backdrop-blur-lg flex items-center justify-center text-[#6f42c1] hover:scale-110 transition-all shadow-2xl group"
                    :class="$store.theme.dark ? 'border border-white/20 hover:bg-white/20' : 'border border-gray-300 hover:bg-gray-100'"
                    aria-label="{{ __('site.scroll_previous') }}">
                <i data-lucide="chevron-{{ $isRtl ? 'right' : 'left' }}" class="w-7 h-7 group-hover:translate-x-0.5 transition-transform"></i>
            </button>

            {{-- Right arrow (visually left in RTL) --}}
            <button x-show="canScrollRight"
                    @click="scrollForward()"
                    x-transition
                    class="absolute start-0 top-1/2 -translate-y-1/2 z-10 w-14 h-14 rounded-full glass backdrop-blur-lg flex items-center justify-center text-[#6f42c1] hover:scale-110 transition-all shadow-2xl group"
                    :class="$store.theme.dark ? 'border border-white/20 hover:bg-white/20' : 'border border-gray-300 hover:bg-gray-100'"
                    aria-label="{{ __('site.scroll_next') }}">
                <i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" class="w-7 h-7 group-hover:-translate-x-0.5 transition-transform"></i>
            </button>
        </div>

        {{-- View all button --}}
        <div class="text-center mt-8">
            <a href="{{ $sahabaUrl }}"
               class="inline-flex items-center gap-2 bg-gradient-to-r from-[#6f42c1] to-[#8b5cf6] text-white font-bold px-8 py-3 rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all">
                <span>{{ __('site.view_all_companions') }}</span>
                <i class="w-5 h-5" data-lucide="{{ $isRtl ? 'arrow-left' : 'arrow-right' }}"></i>
            </a>
        </div>
    </div>
</section>

{{-- ===================================================================
     SECTION 3 — PROPHET MUHAMMAD ID CARD
     Verbatim port of index-enhanced.html lines ~962-1145
     =================================================================== --}}
<section class="py-12 sm:py-16 bg-gradient-to-br from-[#0f3460] via-[#16213e] to-[#0a2647] dark:from-[#0a1929] dark:via-[#16213e] dark:to-[#0f1922] relative overflow-hidden">

    {{-- Decorative background circles --}}
    <div class="absolute inset-0 opacity-5">
        <div class="absolute top-10 end-10 w-64 h-64 border-4 border-white rounded-full animate-pulse"></div>
        <div class="absolute bottom-10 start-10 w-80 h-80 border-4 border-white rounded-full animate-pulse" style="animation-delay: 1s;"></div>
    </div>

    <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="max-w-4xl mx-auto">
            <div class="bg-gradient-to-br from-white/15 via-white/10 to-white/5 backdrop-blur-xl rounded-3xl p-6 md:p-8 border border-white/30 shadow-2xl hover:shadow-yellow-500/20 transition-all duration-500">

                {{-- Header with avatar --}}
                <div class="flex flex-col md:flex-row items-center md:items-start gap-6 mb-8 pb-6 border-b border-white/20">
                    <div class="relative group">
                        <div class="absolute inset-0 bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-2xl blur-xl opacity-50 group-hover:opacity-75 transition-opacity animate-pulse"></div>
                        <div class="relative w-24 h-24 bg-gradient-to-br from-yellow-400 via-yellow-500 to-yellow-600 rounded-2xl flex items-center justify-center shadow-2xl transform group-hover:scale-110 transition-transform duration-300">
                            <i class="w-12 h-12 text-white drop-shadow-lg" data-lucide="sparkles"></i>
                        </div>
                        <div class="absolute -top-2 -end-2 w-7 h-7 bg-yellow-400 rounded-full flex items-center justify-center shadow-lg">
                            <i class="w-3.5 h-3.5 text-white" data-lucide="crown"></i>
                        </div>
                    </div>

                    <div class="flex-1 text-center md:text-{{ $isRtl ? 'right' : 'left' }}">
                        <div class="inline-flex items-center gap-2 mb-2">
                            <span class="bg-gradient-to-r from-yellow-400 to-yellow-500 text-white px-4 py-1.5 rounded-full text-xs font-black border-2 border-yellow-300/50 shadow-lg backdrop-blur-sm">
                                {{ __('site.seal_of_prophets') }}
                            </span>
                        </div>
                        <h3 class="text-3xl md:text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-white via-yellow-200 to-white mb-2 drop-shadow-2xl leading-tight">
                            {{ __('site.prophet_name_ar') }}
                        </h3>
                        <p class="text-gray-200 text-base font-semibold drop-shadow">{{ __('site.prophet_name_en') }}</p>
                    </div>
                </div>

                {{-- ID Information Grid --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">

                    {{-- Full Name --}}
                    <div class="group bg-white/10 hover:bg-white/15 rounded-xl p-6 border border-white/10 hover:border-yellow-400/40 transition-all duration-300 hover:shadow-lg hover:shadow-yellow-400/10 hover:-translate-y-1">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 bg-yellow-400/20 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                                <i class="w-5 h-5 text-yellow-400" data-lucide="user"></i>
                            </div>
                            <p class="text-gray-400 text-sm font-bold uppercase tracking-wide">{{ __('site.full_name') }}</p>
                        </div>
                        <p class="text-white font-black text-xl leading-relaxed">{{ __('site.prophet_full_name') }}</p>
                        <p class="text-gray-400 text-xs mt-2">{{ __('site.prophet_tribe') }}</p>
                    </div>

                    {{-- Birth Date --}}
                    <div class="group bg-white/10 hover:bg-white/15 rounded-xl p-6 border border-white/10 hover:border-yellow-400/40 transition-all duration-300 hover:shadow-lg hover:shadow-yellow-400/10 hover:-translate-y-1">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 bg-yellow-400/20 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                                <i class="w-5 h-5 text-yellow-400" data-lucide="calendar"></i>
                            </div>
                            <p class="text-gray-400 text-sm font-bold uppercase tracking-wide">{{ __('site.birth_date') }}</p>
                        </div>
                        <p class="text-white font-black text-xl">{{ __('site.prophet_birth_hijri') }}</p>
                        <div class="flex items-center gap-2 mt-2">
                            <span class="text-yellow-400 text-sm font-semibold">{{ __('site.year_of_elephant') }}</span>
                            <span class="text-gray-400">•</span>
                            <span class="text-gray-400 text-sm">571 {{ __('site.ce') }}</span>
                        </div>
                    </div>

                    {{-- Birth Location --}}
                    <div class="group bg-white/10 hover:bg-white/15 rounded-xl p-6 border border-white/10 hover:border-yellow-400/40 transition-all duration-300 hover:shadow-lg hover:shadow-yellow-400/10 hover:-translate-y-1">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 bg-yellow-400/20 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                                <i class="w-5 h-5 text-yellow-400" data-lucide="map-pin"></i>
                            </div>
                            <p class="text-gray-400 text-sm font-bold uppercase tracking-wide">{{ __('site.birth_place') }}</p>
                        </div>
                        <p class="text-white font-black text-xl">{{ __('site.makkah') }}</p>
                        <p class="text-gray-400 text-sm mt-2">{{ __('site.hejaz_region') }}</p>
                    </div>

                    {{-- Prophetic Mission --}}
                    <div class="group bg-gradient-to-br from-yellow-400/20 to-yellow-500/10 hover:from-yellow-400/30 hover:to-yellow-500/20 rounded-xl p-6 border border-yellow-400/30 hover:border-yellow-400/60 transition-all duration-300 hover:shadow-lg hover:shadow-yellow-400/20 hover:-translate-y-1">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 bg-yellow-400/30 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                                <i class="w-5 h-5 text-yellow-400" data-lucide="zap"></i>
                            </div>
                            <p class="text-yellow-300 text-sm font-bold uppercase tracking-wide">{{ __('site.mission_start') }}</p>
                        </div>
                        <p class="text-white font-black text-xl">{{ __('site.hira_cave') }}</p>
                        <div class="flex items-center gap-2 mt-2">
                            <span class="text-yellow-300 text-sm font-semibold">{{ __('site.age_40') }}</span>
                            <span class="text-white/40">•</span>
                            <span class="text-gray-300 text-sm">610 {{ __('site.ce') }}</span>
                        </div>
                    </div>

                    {{-- Death Date --}}
                    <div class="group bg-white/10 hover:bg-white/15 rounded-xl p-6 border border-white/10 hover:border-yellow-400/40 transition-all duration-300 hover:shadow-lg hover:shadow-yellow-400/10 hover:-translate-y-1">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 bg-yellow-400/20 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                                <i class="w-5 h-5 text-yellow-400" data-lucide="star"></i>
                            </div>
                            <p class="text-gray-400 text-sm font-bold uppercase tracking-wide">{{ __('site.death_date') }}</p>
                        </div>
                        <p class="text-white font-black text-xl">{{ __('site.prophet_death_hijri') }}</p>
                        <div class="flex items-center gap-2 mt-2">
                            <span class="text-gray-400 text-sm">632 {{ __('site.ce') }}</span>
                            <span class="text-gray-400">•</span>
                            <span class="text-gray-400 text-sm">{{ __('site.monday') }}</span>
                        </div>
                    </div>

                    {{-- Death Location --}}
                    <div class="group bg-white/10 hover:bg-white/15 rounded-xl p-6 border border-white/10 hover:border-yellow-400/40 transition-all duration-300 hover:shadow-lg hover:shadow-yellow-400/10 hover:-translate-y-1">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 bg-yellow-400/20 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                                <i class="w-5 h-5 text-yellow-400" data-lucide="map"></i>
                            </div>
                            <p class="text-gray-400 text-sm font-bold uppercase tracking-wide">{{ __('site.death_place') }}</p>
                        </div>
                        <p class="text-white font-black text-xl">{{ __('site.madinah') }}</p>
                        <p class="text-gray-400 text-sm mt-2">{{ __('site.aisha_house') }}</p>
                    </div>
                </div>

                {{-- Timeline Stats --}}
                <div class="bg-gradient-to-r from-yellow-400/10 via-yellow-500/10 to-yellow-400/10 rounded-2xl p-5 mb-6 border border-yellow-400/20">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-center">
                        <div class="group cursor-default">
                            <div class="flex items-center justify-center gap-2 mb-1">
                                <i class="w-4 h-4 text-yellow-400 group-hover:scale-125 transition-transform" data-lucide="hourglass"></i>
                                <p class="text-gray-400 text-xs font-bold">{{ __('site.noble_age') }}</p>
                            </div>
                            <p class="text-white font-black text-2xl">63</p>
                            <p class="text-yellow-400 text-xs font-semibold">{{ __('site.years') }}</p>
                        </div>
                        <div class="group cursor-default border-x border-white/10">
                            <div class="flex items-center justify-center gap-2 mb-1">
                                <i class="w-4 h-4 text-yellow-400 group-hover:scale-125 transition-transform" data-lucide="megaphone"></i>
                                <p class="text-gray-400 text-xs font-bold">{{ __('site.dawah_years') }}</p>
                            </div>
                            <p class="text-white font-black text-2xl">23</p>
                            <p class="text-yellow-400 text-xs font-semibold">{{ __('site.years') }}</p>
                        </div>
                        <div class="group cursor-default">
                            <div class="flex items-center justify-center gap-2 mb-1">
                                <i class="w-4 h-4 text-yellow-400 group-hover:scale-125 transition-transform" data-lucide="users"></i>
                                <p class="text-gray-400 text-xs font-bold">{{ __('site.companions_count') }}</p>
                            </div>
                            <p class="text-white font-black text-2xl">100,000+</p>
                            <p class="text-yellow-400 text-xs font-semibold">{{ __('site.companion') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Action buttons --}}
                <div class="flex flex-col sm:flex-row gap-3 items-center justify-center flex-wrap">
                    <a href="{{ $seerahUrl }}"
                       class="group relative inline-flex items-center gap-2 bg-gradient-to-r from-yellow-400 via-yellow-500 to-yellow-600 text-white font-black px-8 py-3 rounded-xl shadow-2xl hover:shadow-yellow-500/50 transform hover:scale-105 transition-all duration-300 overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-r from-yellow-300 to-yellow-500 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <i class="w-5 h-5 relative z-10 group-hover:rotate-12 transition-transform" data-lucide="book-open"></i>
                        <span class="relative z-10">{{ __('site.explore_full_seerah') }}</span>
                        <i class="w-5 h-5 relative z-10 group-hover:translate-x-1 transition-transform" data-lucide="{{ $isRtl ? 'arrow-left' : 'arrow-right' }}"></i>
                    </a>

                    <a href="{{ $genealogyUrl }}"
                       class="group inline-flex items-center gap-2 bg-white/10 hover:bg-white/20 text-white font-bold px-6 py-3 rounded-xl border-2 border-white/30 hover:border-yellow-400/50 transition-all hover:scale-105">
                        <i class="w-4 h-4 group-hover:scale-110 transition-transform" data-lucide="git-branch"></i>
                        <span>{{ __('site.family_tree') }}</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ===================================================================
     SECTION 4 — DAILY QURAN & HADITH
     Verbatim port of index-enhanced.html lines ~1147-1294
     =================================================================== --}}
<section class="py-16 sm:py-20 bg-[#F8F9FA] dark:bg-[#1a1a2e]">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

            {{-- Daily Quran Verse --}}
            <div class="bg-gradient-to-br from-[#17a2b8] to-[#138496] rounded-2xl shadow-2xl overflow-hidden">
                <div class="p-8">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <span class="bg-white/20 text-white px-4 py-2 rounded-full text-sm font-bold backdrop-blur-sm">{{ __('site.quran_of_day') }}</span>
                            <h3 class="text-3xl font-black text-white mt-3">{{ __('site.verse_of_day') }}</h3>
                        </div>
                        <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-sm">
                            <i class="w-10 h-10 text-white" data-lucide="book-open"></i>
                        </div>
                    </div>

                    @if($dailyVerse)
                        {{-- Surah info --}}
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6 mb-6 border border-white/20">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h4 class="text-2xl font-bold text-white mb-1">
                                        {{ $dailyVerse['surah_name'] ?? __('site.quran') }}
                                    </h4>
                                    <p class="text-gray-200 text-sm">{{ __('site.surah_label') }}</p>
                                </div>
                                <div class="text-end">
                                    <p class="text-white font-bold text-lg">{{ $dailyVerse['surah_id'] ?? '' }}</p>
                                    <p class="text-gray-200 text-xs">{{ __('site.surah_number') }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 text-sm text-gray-200">
                                <div class="flex items-center gap-2">
                                    <i class="w-4 h-4" data-lucide="file-text"></i>
                                    <span>{{ __('site.verse') }} {{ $dailyVerse['verse_number'] ?? '' }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Verse text --}}
                        <div class="bg-white rounded-xl p-6 mb-6">
                            @if($dailyVerse['text_arabic'])
                                <p class="text-center text-2xl leading-loose font-arabic text-gray-800 mb-4" dir="rtl">
                                    {{ $dailyVerse['text_arabic'] }}
                                </p>
                            @endif
                            @php
                                $enTranslation = $dailyVerse['translations']['en'] ?? null;
                            @endphp
                            @if($enTranslation && $locale !== 'ar')
                                <p class="text-center text-sm text-gray-600 mb-2">{{ $enTranslation }}</p>
                            @endif
                            <div class="text-center mt-3">
                                <span class="bg-[#17a2b8]/10 text-[#17a2b8] px-3 py-1 rounded-full text-xs font-semibold">
                                    {{ __('site.verse') }} {{ $dailyVerse['verse_number'] ?? '' }}
                                </span>
                            </div>
                        </div>
                    @else
                        {{-- Empty/loading state --}}
                        <div class="bg-white/10 rounded-xl p-6 mb-6 text-center">
                            <p class="text-white/70 text-sm">{{ __('site.loading') }}</p>
                        </div>
                    @endif

                    {{-- Actions --}}
                    <div class="flex gap-3">
                        <a href="{{ $quranUrl }}"
                           class="flex-1 bg-white text-[#17a2b8] font-bold py-3 px-4 rounded-xl hover:bg-gray-100 transition text-center flex items-center justify-center gap-2">
                            <i class="w-5 h-5" data-lucide="book-open"></i>
                            <span>{{ __('site.read_surah') }}</span>
                        </a>
                        <button class="w-14 h-14 bg-white/20 backdrop-blur-sm text-white rounded-xl hover:bg-white/30 transition flex items-center justify-center"
                                aria-label="{{ __('site.share') }}">
                            <i class="w-6 h-6" data-lucide="share-2"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Daily Hadith --}}
            <div class="bg-gradient-to-br from-[#218838] to-[#1e7e34] rounded-2xl shadow-2xl overflow-hidden">
                <div class="p-8">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <span class="bg-white/20 text-white px-4 py-2 rounded-full text-sm font-bold backdrop-blur-sm">{{ __('site.hadith_of_day') }}</span>
                            <h3 class="text-3xl font-black text-white mt-3">{{ __('site.hadith') }}</h3>
                        </div>
                        <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-sm">
                            <i class="w-10 h-10 text-white" data-lucide="message-square-quote"></i>
                        </div>
                    </div>

                    @if($dailyHadith)
                        @php
                            $hadithTextAr = $dailyHadith['translations']['ar']['text'] ?? null;
                            $hadithTextEn = $dailyHadith['translations']['en']['text'] ?? null;
                            $hadithGrading = $dailyHadith['grading_status'] ?? null;
                            $hadithBook = $dailyHadith['book_title'] ?? null;
                            $hadithCollection = $dailyHadith['collection_name'] ?? null;
                        @endphp

                        {{-- Hadith text card --}}
                        <div class="bg-white rounded-xl p-6 mb-6">
                            <div class="text-center mb-4">
                                <div class="inline-flex items-center justify-center w-12 h-12 bg-[#218838]/10 rounded-full mb-3">
                                    <i class="w-6 h-6 text-[#218838]" data-lucide="quote"></i>
                                </div>
                            </div>
                            @if($hadithTextAr)
                                <p class="text-lg leading-relaxed text-gray-800 text-center mb-4" dir="rtl">"{{ $hadithTextAr }}"</p>
                            @endif
                            @if($hadithTextEn && $locale !== 'ar')
                                <p class="text-sm text-gray-600 text-center mb-4">"{{ $hadithTextEn }}"</p>
                            @endif

                            <div class="border-t border-gray-200 pt-4">
                                <div class="flex items-center justify-between text-sm">
                                    <div class="space-y-1">
                                        @if($hadithCollection)
                                            <div class="flex items-center gap-2 text-gray-600">
                                                <i class="w-4 h-4" data-lucide="book-marked"></i>
                                                <span>{{ $hadithCollection }}</span>
                                            </div>
                                        @elseif($hadithBook)
                                            <div class="flex items-center gap-2 text-gray-600">
                                                <i class="w-4 h-4" data-lucide="book-marked"></i>
                                                <span>{{ $hadithBook }}</span>
                                            </div>
                                        @endif
                                    </div>
                                    @if($hadithGrading)
                                        <div class="mt-2 flex items-center gap-2">
                                            <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-semibold">
                                                {{ __('site.grading_' . $hadithGrading) ?: $hadithGrading }}
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Brief explanation --}}
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-5 mb-6 border border-white/20">
                            <h4 class="text-white font-bold mb-2 flex items-center gap-2">
                                <i class="w-5 h-5" data-lucide="lightbulb"></i>
                                <span>{{ __('site.benefit') }}</span>
                            </h4>
                            <p class="text-gray-200 text-sm leading-relaxed">
                                {{ __('site.hadith_benefit_placeholder') }}
                            </p>
                        </div>
                    @else
                        <div class="bg-white/10 rounded-xl p-6 mb-6 text-center">
                            <p class="text-white/70 text-sm">{{ __('site.loading') }}</p>
                        </div>
                    @endif

                    {{-- Actions --}}
                    <div class="flex gap-3">
                        <a href="{{ $hadithUrl }}"
                           class="flex-1 bg-white text-[#218838] font-bold py-3 px-4 rounded-xl hover:bg-gray-100 transition text-center flex items-center justify-center gap-2">
                            <i class="w-5 h-5" data-lucide="book-open"></i>
                            <span>{{ __('site.read_explanation') }}</span>
                        </a>
                        <button class="w-14 h-14 bg-white/20 backdrop-blur-sm text-white rounded-xl hover:bg-white/30 transition flex items-center justify-center"
                                aria-label="{{ __('site.bookmark') }}">
                            <i class="w-6 h-6" data-lucide="bookmark"></i>
                        </button>
                        <button class="w-14 h-14 bg-white/20 backdrop-blur-sm text-white rounded-xl hover:bg-white/30 transition flex items-center justify-center"
                                aria-label="{{ __('site.share') }}">
                            <i class="w-6 h-6" data-lucide="share-2"></i>
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

{{-- ===================================================================
     SECTION 5 — MAIN MODULE DISCOVERY GRID
     Verbatim port of index-enhanced.html lines ~1296-1607
     =================================================================== --}}
<section id="explore" class="py-20 sm:py-28 bg-gradient-to-b from-white to-[#F8F9FA] dark:from-[#1a1a2e] dark:to-[#0f3460]">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <div class="inline-block mb-4">
                <span class="bg-gradient-to-r from-[#0056b3] to-[#218838] text-white px-6 py-2 rounded-full text-sm font-bold">{{ __('site.discover_content') }}</span>
            </div>
            <h2 class="text-4xl md:text-5xl font-black mb-6 bg-gradient-to-r from-[#0056b3] to-[#218838] bg-clip-text text-transparent">
                {{ __('site.explore_encyclopedia') }}
            </h2>
            <p class="text-xl text-gray-600 dark:text-gray-300 max-w-3xl mx-auto">
                {{ __('site.modules_subtitle') }}
            </p>
        </div>

        @if (count($moduleCards) > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                @foreach ($moduleCards as $card)
                    @php
                        $style = $cardStyles[$card['accent_color']] ?? $fallbackCardStyle;
                        $cardStat = $cardStats[$card['key']] ?? null;
                        $accent = $card['accent_color'] ?? '#0056b3';
                    @endphp
                    <a href="{{ $contentUrl($card['target_url'] ?? null) }}"
                       class="module-card group relative bg-white dark:bg-[#16213e] rounded-xl shadow-lg overflow-hidden hover-lift border border-gray-100 dark:border-gray-800 {{ $style['hoverBorder'] }} transition-all">

                        {{-- Corner accent --}}
                        <div class="absolute top-0 end-0 w-16 h-16 bg-gradient-to-br {{ $style['cornerGrad'] }} to-transparent rounded-bl-3xl"></div>

                        {{-- Badge --}}
                        @if($card['is_featured'])
                            <div class="absolute top-2 end-2 z-10 flex items-center gap-2">
                                <span class="{{ $style['badgeBg'] }} text-white text-xs font-bold px-2.5 py-1 rounded-full shadow-md">{{ __('site.featured') }}</span>
                            </div>
                        @endif

                        {{-- Quick action buttons (hover) --}}
                        <div class="absolute top-2 start-2 z-10 flex gap-1.5 opacity-0 group-hover:opacity-100 focus-within:opacity-100 transition-opacity">
                            <button type="button" class="w-7 h-7 bg-white dark:bg-gray-800 rounded-full shadow-md flex items-center justify-center hover:scale-110 transition focus-visible:ring-2 focus-visible:ring-[#64CCC5] focus:outline-none"
                                    aria-label="{{ __('site.bookmark') }}">
                                <i class="w-3.5 h-3.5 text-gray-700 dark:text-gray-300" data-lucide="bookmark"></i>
                            </button>
                            <button type="button" class="w-7 h-7 bg-white dark:bg-gray-800 rounded-full shadow-md flex items-center justify-center hover:scale-110 transition focus-visible:ring-2 focus-visible:ring-[#64CCC5] focus:outline-none"
                                    aria-label="{{ __('site.share') }}">
                                <i class="w-3.5 h-3.5 text-gray-700 dark:text-gray-300" data-lucide="share-2"></i>
                            </button>
                        </div>

                        <div class="absolute inset-0 bg-gradient-to-br {{ $style['hoverGrad'] }} to-transparent opacity-0 group-hover:opacity-100 transition"></div>

                        <div class="relative p-5">
                            <div class="module-icon w-14 h-14 bg-gradient-to-br {{ $style['iconGrad'] }} rounded-xl flex items-center justify-center mb-4 shadow-md">
                                <i class="w-7 h-7 text-white" data-lucide="{{ $card['icon'] ?? 'book-open' }}"></i>
                            </div>
                            <div class="mb-3">
                                <h3 class="text-lg font-bold mb-1.5 truncate">{{ $card['title'] }}</h3>
                                @if($cardStat && $cardStat['value'] > 0)
                                    <div class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400 mb-2">
                                        <i class="w-3.5 h-3.5" data-lucide="{{ $cardStat['icon'] }}"></i>
                                        <span>{{ number_format($cardStat['value']) }} {{ $cardStat['label'] }}</span>
                                    </div>
                                @endif
                            </div>
                            <p class="text-gray-600 dark:text-gray-300 text-sm mb-4 line-clamp-2">{{ $card['description'] }}</p>

                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-s-1.5 font-semibold text-sm"
                                     style="color: {{ $accent }};">
                                    <span>{{ __('site.explore') }}</span>
                                    <i class="w-4 h-4 transform {{ $isRtl ? 'group-hover:-translate-x-1' : 'group-hover:translate-x-1' }} transition"
                                       data-lucide="{{ $isRtl ? 'arrow-left' : 'arrow-right' }}"></i>
                                </div>
                                <div class="opacity-0 group-hover:opacity-100 transition-opacity"
                                     style="color: {{ $accent }};">
                                    <i class="w-5 h-5" data-lucide="external-link"></i>
                                </div>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</section>

{{-- ===================================================================
     SECTION 6 — FEATURED CONTENT
     Verbatim port of index-enhanced.html lines ~1609-1664
     =================================================================== --}}
<section class="py-20 bg-[#E9ECEF] dark:bg-[#0f3460]">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-12">
            <div>
                <h2 class="text-3xl md:text-4xl font-black mb-2">{{ __('site.featured_content') }}</h2>
                <p class="text-gray-600 dark:text-gray-300">{{ __('site.handpicked_content') }}</p>
            </div>
            <a href="{{ Route::has('public.articles') ? route('public.articles') : '#' }}"
               class="hidden md:flex items-center space-s-2 font-semibold text-[#0056b3] dark:text-[#4a9eff] hover:underline">
                <span>{{ __('site.view_all') }}</span>
                <i class="w-5 h-5" data-lucide="{{ $isRtl ? 'arrow-left' : 'arrow-right' }}"></i>
            </a>
        </div>

        <div class="grid gap-8 grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
            {{-- Article 1 — Seerah --}}
            <article class="bg-white dark:bg-[#16213e] rounded-2xl shadow-xl overflow-hidden hover-lift">
                <img src="{{ asset('images/site/article-1.png') }}"
                     alt="{{ __('site.article_1_title') }}"
                     class="w-full h-56 object-cover" />
                <div class="p-6">
                    <span class="inline-block bg-[#0056b3]/10 text-[#0056b3] dark:bg-[#0056b3]/20 dark:text-[#4a9eff] text-xs font-bold px-3 py-1 rounded-full mb-3">{{ __('site.seerah') }}</span>
                    <h3 class="text-xl font-bold mb-2">{{ __('site.article_1_title') }}</h3>
                    <p class="text-gray-600 dark:text-gray-300 mb-4">{{ __('site.article_1_excerpt') }}</p>
                    <a href="{{ $seerahUrl }}"
                       class="inline-flex items-center space-s-2 text-[#0056b3] dark:text-[#4a9eff] font-semibold hover:underline">
                        <span>{{ __('site.read_more') }}</span>
                        <i class="w-4 h-4" data-lucide="{{ $isRtl ? 'arrow-left' : 'arrow-right' }}"></i>
                    </a>
                </div>
            </article>

            {{-- Article 2 — Hadith --}}
            <article class="bg-white dark:bg-[#16213e] rounded-2xl shadow-xl overflow-hidden hover-lift">
                <img src="{{ asset('images/site/article-2.png') }}"
                     alt="{{ __('site.article_2_title') }}"
                     class="w-full h-56 object-cover" />
                <div class="p-6">
                    <span class="inline-block bg-[#218838]/10 text-[#218838] dark:bg-[#218838]/20 dark:text-[#5cb85c] text-xs font-bold px-3 py-1 rounded-full mb-3">{{ __('site.hadith') }}</span>
                    <h3 class="text-xl font-bold mb-2">{{ __('site.article_2_title') }}</h3>
                    <p class="text-gray-600 dark:text-gray-300 mb-4">{{ __('site.article_2_excerpt') }}</p>
                    <a href="{{ $hadithUrl }}"
                       class="inline-flex items-center space-s-2 text-[#218838] dark:text-[#5cb85c] font-semibold hover:underline">
                        <span>{{ __('site.read_more') }}</span>
                        <i class="w-4 h-4" data-lucide="{{ $isRtl ? 'arrow-left' : 'arrow-right' }}"></i>
                    </a>
                </div>
            </article>

            {{-- Article 3 — Media --}}
            <article class="bg-white dark:bg-[#16213e] rounded-2xl shadow-xl overflow-hidden hover-lift">
                <img src="{{ asset('images/site/article-3.png') }}"
                     alt="{{ __('site.article_3_title') }}"
                     class="w-full h-56 object-cover" />
                <div class="p-6">
                    <span class="inline-block bg-[#FF7F50]/10 text-[#FF7F50] text-xs font-bold px-3 py-1 rounded-full mb-3">{{ __('site.media') }}</span>
                    <h3 class="text-xl font-bold mb-2">{{ __('site.article_3_title') }}</h3>
                    <p class="text-gray-600 dark:text-gray-300 mb-4">{{ __('site.article_3_excerpt') }}</p>
                    <a href="{{ $mediaUrl }}"
                       class="inline-flex items-center space-s-2 text-[#FF7F50] font-semibold hover:underline">
                        <span>{{ __('site.watch_now') }}</span>
                        <i class="w-4 h-4" data-lucide="play"></i>
                    </a>
                </div>
            </article>
        </div>
    </div>
</section>

{{-- ===================================================================
     SECTION 7 — CALL TO ACTION
     Verbatim port of index-enhanced.html lines ~1666-1688
     =================================================================== --}}
<section class="py-24 relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-r from-[#0056b3] to-[#218838]"></div>
    <div class="absolute inset-0 opacity-10">
        <div class="absolute top-0 start-0 w-96 h-96 bg-white rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 end-0 w-96 h-96 bg-white rounded-full blur-3xl"></div>
    </div>

    <div class="relative z-10 container mx-auto px-4 sm:px-6 lg:px-8 text-center text-white">
        <h2 class="text-4xl md:text-5xl font-black mb-6">{{ __('site.join_community') }}</h2>
        <p class="text-xl md:text-2xl opacity-90 mb-10 max-w-3xl mx-auto">{{ __('site.join_description') }}</p>
        <div class="flex flex-wrap justify-center gap-4">
            <a href="{{ $registerUrl }}"
               class="inline-flex items-center space-s-2 bg-white text-[#0056b3] font-bold px-8 py-4 rounded-xl shadow-xl hover:shadow-2xl transform hover:scale-105 transition">
                <i class="w-5 h-5" data-lucide="user-plus"></i>
                <span>{{ __('site.register') }}</span>
            </a>
            <a href="#explore"
               class="inline-flex items-center space-s-2 bg-white/10 backdrop-blur-sm border-2 border-white text-white font-bold px-8 py-4 rounded-xl hover:bg-white/20 transform hover:scale-105 transition">
                <i class="w-5 h-5" data-lucide="info"></i>
                <span>{{ __('site.learn_more') }}</span>
            </a>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script>
    // Re-render icons after Blade renders dynamic elements
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof window.renderIcons === 'function') {
            window.renderIcons();
        }
    });
</script>
@endpush
