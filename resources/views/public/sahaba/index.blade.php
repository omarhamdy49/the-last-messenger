@extends('layouts.public')

@section('title', __('sahaba.page_title'))
@section('meta_description', __('sahaba.page_description'))

@push('structured_data')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "CollectionPage",
    "name": "{{ __('sahaba.page_title') }}",
    "description": "{{ __('sahaba.page_description') }}",
    "url": "{{ route('public.sahaba') }}",
    "inLanguage": "{{ app()->getLocale() }}"
}
</script>
@endpush

@section('content')

@php
    $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true);

    // Rotating accent palette for cards (gradient pair + badge ring + accent text).
    $palettes = [
        ['card' => 'from-[#0056b3] to-[#004c9e]', 'ring' => 'from-yellow-400 to-yellow-600', 'accent' => 'text-[#0056b3]', 'icon' => 'star'],
        ['card' => 'from-[#218838] to-[#1e7e34]', 'ring' => 'from-[#64CCC5] to-[#218838]', 'accent' => 'text-[#218838]', 'icon' => 'shield'],
        ['card' => 'from-[#6f42c1] to-[#5a32a3]', 'ring' => 'from-purple-400 to-purple-600', 'accent' => 'text-[#6f42c1]', 'icon' => 'award'],
        ['card' => 'from-[#FF7F50] to-[#e8633a]', 'ring' => 'from-orange-400 to-orange-600', 'accent' => 'text-[#FF7F50]', 'icon' => 'flame'],
    ];

    // Hero credibility strip — rendered as an inline divided bar, not stat boxes.
    $heroStats = [
        ['icon' => 'users',       'value' => $stats['total'],    'label' => __('sahaba.stat_total')],
        ['icon' => 'star',        'value' => $stats['featured'], 'label' => __('sahaba.stat_featured')],
        ['icon' => 'user',        'value' => $stats['male'],     'label' => __('sahaba.stat_male')],
        ['icon' => 'user-round',  'value' => $stats['female'],   'label' => __('sahaba.stat_female')],
    ];
@endphp

@push('head')
<style>
    /* Sahaba hero — gentle, GPU-only medallion rotation; disabled for reduced motion. */
    @media (prefers-reduced-motion: no-preference) {
        .sahaba-hero-medallion { animation: sahabaHeroSpin 90s linear infinite; transform-box: fill-box; transform-origin: center; }
    }
    @keyframes sahabaHeroSpin { to { transform: rotate(360deg); } }
</style>
@endpush

<div class="min-h-dvh font-['Cairo'] transition-colors duration-300"
     :class="$store.theme.dark ? 'bg-gray-950' : 'bg-gray-50'">

    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Breadcrumb --}}
        <x-site.breadcrumb class="mb-6" :items="[
            ['label' => __('sahaba.page_title')],
        ]" />

        {{-- ============================================================ --}}
        {{-- HERO + STATS --}}
        {{-- ============================================================ --}}
        <section class="relative rounded-3xl overflow-hidden mb-8 fade-in-up
                        bg-gradient-to-br from-[#0056b3] via-[#0a6aa6] to-[#218838]
                        dark:from-[#0a182b] dark:via-[#11243d] dark:to-[#0c2a22]
                        shadow-[0_24px_60px_-24px_rgba(0,86,179,0.55)]">

            {{-- Islamic eight-point geometric tile (subtle) --}}
            <svg class="absolute inset-0 w-full h-full text-white opacity-[0.07]" aria-hidden="true">
                <defs>
                    <pattern id="sahaba-stars" width="60" height="60" patternUnits="userSpaceOnUse">
                        <g fill="none" stroke="currentColor" stroke-width="1">
                            <rect x="16" y="16" width="28" height="28"/>
                            <rect x="16" y="16" width="28" height="28" transform="rotate(45 30 30)"/>
                        </g>
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#sahaba-stars)"/>
            </svg>

            {{-- Light source + bottom legibility scrim --}}
            <div class="absolute inset-0" aria-hidden="true"
                 style="background:radial-gradient(80% 70% at 22% 0%, rgba(255,255,255,.20), transparent 60%);"></div>
            <div class="absolute inset-x-0 bottom-0 h-1/2 bg-gradient-to-t from-black/25 to-transparent" aria-hidden="true"></div>

            {{-- Rotating medallion watermark — echoes the companion cards --}}
            <svg class="sahaba-hero-medallion absolute -bottom-24 -end-20 w-[26rem] h-[26rem] text-white/[0.07] hidden md:block"
                 viewBox="0 0 100 100" fill="none" aria-hidden="true">
                <circle cx="50" cy="50" r="47" stroke="currentColor" stroke-width="0.5" stroke-dasharray="1 3"/>
                <rect x="22" y="22" width="56" height="56" rx="4" stroke="currentColor" stroke-width="0.7"/>
                <rect x="22" y="22" width="56" height="56" rx="4" stroke="currentColor" stroke-width="0.7" transform="rotate(45 50 50)"/>
                <circle cx="50" cy="50" r="29" stroke="currentColor" stroke-width="0.6"/>
                <circle cx="50" cy="50" r="24" stroke="currentColor" stroke-width="0.4"/>
            </svg>

            <div class="relative px-6 py-12 sm:px-10 sm:py-14 lg:px-14 lg:py-16 text-white text-start">
                {{-- Ornamental eyebrow --}}
                <div class="inline-flex items-center gap-2.5 mb-6">
                    <i data-lucide="star" class="w-4 h-4 text-[#9fe3de]"></i>
                    <span class="h-px w-12 bg-white/40"></span>
                </div>

                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold leading-[1.15] mb-4 max-w-3xl">
                    {{ __('sahaba.hero_title') }}
                </h1>
                <p class="text-base sm:text-lg text-white/85 leading-relaxed max-w-2xl">
                    {{ __('sahaba.hero_subtitle') }}
                </p>

                {{-- Search — the directory's primary call to action --}}
                <form method="GET" action="{{ route('public.sahaba') }}" class="mt-8 max-w-xl">
                    <input type="hidden" name="filter" value="{{ $activeFilter }}">
                    <div class="relative">
                        <i data-lucide="search" class="absolute start-5 top-1/2 -translate-y-1/2 w-5 h-5 text-white/70 pointer-events-none"></i>
                        <input
                            type="search"
                            name="q"
                            value="{{ $search }}"
                            placeholder="{{ __('sahaba.search_placeholder') }}"
                            class="w-full ps-14 pe-28 sm:pe-32 py-4 text-base rounded-full text-white placeholder-white/65
                                   bg-white/15 backdrop-blur-md border border-white/25
                                   focus:outline-none focus:ring-2 focus:ring-white/70 focus:bg-white/20 transition-all"
                            aria-label="{{ __('sahaba.search_placeholder') }}">
                        <button type="submit"
                                class="absolute end-2 top-1/2 -translate-y-1/2 px-5 sm:px-6 py-2.5 rounded-full text-sm font-bold
                                       bg-white text-[#0056b3] hover:bg-white/90 active:scale-95 shadow-lg
                                       focus:outline-none focus-visible:ring-2 focus-visible:ring-white/80 transition-all">
                            {{ __('site.search') }}
                        </button>
                    </div>
                </form>

                {{-- Credibility strip --}}
                <div class="mt-10 grid grid-cols-2 gap-y-6 sm:flex sm:flex-wrap sm:gap-y-0">
                    @foreach ($heroStats as $i => $stat)
                        <div class="flex items-center gap-3 {{ $i > 0 ? 'sm:ps-6 sm:ms-6 sm:border-s sm:border-white/25' : '' }}">
                            <span class="flex items-center justify-center w-10 h-10 rounded-xl bg-white/10 backdrop-blur-sm shrink-0">
                                <i data-lucide="{{ $stat['icon'] }}" class="w-5 h-5 text-[#9fe3de]"></i>
                            </span>
                            <div class="min-w-0">
                                <div class="text-2xl sm:text-3xl font-bold tabular-nums leading-none">{{ number_format($stat['value']) }}</div>
                                <div class="text-xs sm:text-sm text-white/75 mt-1 truncate">{{ $stat['label'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ============================================================ --}}
        {{-- FILTERS (search lives in the hero) --}}
        {{-- ============================================================ --}}
        <div class="mb-6 stagger-1 fade-in-up">
            <x-site.filter-pills :pills="$filterPills" :active="$activeFilter" />
        </div>

        {{-- Results count --}}
        <div class="mb-6 flex items-center justify-between flex-wrap gap-3 stagger-2 fade-in-up">
            <p class="text-sm" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-600'">
                {{ __('site.showing') }}
                <span class="font-bold text-[#64CCC5]">{{ number_format($paginator->total()) }}</span>
                {{ __('sahaba.companions_unit') }}
            </p>

            @if ($search !== '' || $activeFilter !== 'all')
                <a href="{{ route('public.sahaba') }}"
                   class="inline-flex items-center gap-1.5 text-sm font-semibold text-primary hover:underline">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    {{ __('site.reset_filters') }}
                </a>
            @endif
        </div>

        {{-- ============================================================ --}}
        {{-- GRID --}}
        {{-- ============================================================ --}}
        @if ($companions->isEmpty())
            <x-site.empty-state
                icon="users"
                :title="__('sahaba.empty_title')"
                :message="__('sahaba.empty_message')">
                <a href="{{ route('public.sahaba') }}"
                   class="mt-5 inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-semibold text-white bg-gradient-to-l from-primary to-secondary hover:shadow-lg transition-all">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    {{ __('site.reset_filters') }}
                </a>
            </x-site.empty-state>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 sm:gap-5 justify-items-center stagger-2 fade-in-up">
                @foreach ($companions as $sahabi)
                    @php $palette = $palettes[$loop->index % count($palettes)]; @endphp
                    <div class="w-full max-w-sm">
                        <x-site.sahaba-card
                            :sahabi="$sahabi"
                            :color="$palette['card']"
                            :badge-icon="$palette['icon']"
                            :badge-color="$palette['ring']"
                            :accent-color="$palette['accent']"
                            width="w-full" />
                    </div>
                @endforeach
            </div>

            {{-- Pagination (query string preserved) --}}
            <x-site.pagination :paginator="$paginator->withQueryString()" />
        @endif

    </div>{{-- /container --}}
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof window.renderIcons === 'function') {
            window.renderIcons();
        } else if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>
@endpush

@endsection
