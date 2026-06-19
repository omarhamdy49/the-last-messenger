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
@endphp

<div class="min-h-screen font-['Cairo'] transition-colors duration-300"
     :class="$store.theme.dark ? 'bg-gray-950' : 'bg-gray-50'">

    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Breadcrumb --}}
        <x-site.breadcrumb class="mb-6" :items="[
            ['label' => __('sahaba.page_title')],
        ]" />

        {{-- ============================================================ --}}
        {{-- HERO + STATS --}}
        {{-- ============================================================ --}}
        <div class="relative rounded-3xl overflow-hidden mb-10 fade-in-up"
             :class="$store.theme.dark
                 ? 'bg-gradient-to-r from-gray-900 to-gray-800'
                 : 'bg-gradient-to-r from-[#0056b3] to-[#218838]'">
            <div class="absolute inset-0 opacity-10" aria-hidden="true">
                <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <pattern id="sahaba-grid" width="40" height="40" patternUnits="userSpaceOnUse">
                            <path d="M 40 0 L 0 0 0 40" fill="none" stroke="currentColor" stroke-width="1"/>
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#sahaba-grid)"/>
                </svg>
            </div>

            <div class="relative px-6 py-12 md:px-12 md:py-16 text-center text-white">
                <div class="flex justify-center mb-6">
                    <div class="w-20 h-20 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center">
                        <i data-lucide="users" class="w-10 h-10 text-white"></i>
                    </div>
                </div>

                <h1 class="text-4xl md:text-5xl font-bold mb-4">{{ __('sahaba.hero_title') }}</h1>
                <p class="text-lg md:text-xl mb-8 opacity-90 max-w-2xl mx-auto">{{ __('sahaba.hero_subtitle') }}</p>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 max-w-3xl mx-auto">
                    <div class="glass rounded-2xl p-4">
                        <div class="text-3xl font-bold mb-1">{{ number_format($stats['total']) }}</div>
                        <div class="text-sm opacity-80">{{ __('sahaba.stat_total') }}</div>
                    </div>
                    <div class="glass rounded-2xl p-4">
                        <div class="text-3xl font-bold mb-1">{{ number_format($stats['featured']) }}</div>
                        <div class="text-sm opacity-80">{{ __('sahaba.stat_featured') }}</div>
                    </div>
                    <div class="glass rounded-2xl p-4">
                        <div class="text-3xl font-bold mb-1">{{ number_format($stats['male']) }}</div>
                        <div class="text-sm opacity-80">{{ __('sahaba.stat_male') }}</div>
                    </div>
                    <div class="glass rounded-2xl p-4">
                        <div class="text-3xl font-bold mb-1">{{ number_format($stats['female']) }}</div>
                        <div class="text-sm opacity-80">{{ __('sahaba.stat_female') }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- SEARCH + FILTERS --}}
        {{-- ============================================================ --}}
        <div class="flex flex-col lg:flex-row lg:items-center gap-4 mb-6 stagger-1 fade-in-up">
            {{-- Search (server-side GET) --}}
            <form method="GET" action="{{ route('public.sahaba') }}" class="lg:w-2/5">
                <input type="hidden" name="filter" value="{{ $activeFilter }}">
                <div class="relative">
                    <i data-lucide="search" class="absolute start-4 top-1/2 -translate-y-1/2 w-5 h-5"
                       :class="$store.theme.dark ? 'text-gray-500' : 'text-gray-400'"></i>
                    <input
                        type="search"
                        name="q"
                        value="{{ $search }}"
                        placeholder="{{ __('sahaba.search_placeholder') }}"
                        class="w-full ps-12 pe-4 py-3 text-base sm:text-sm rounded-xl border-2 focus:outline-none focus:ring-2 focus:ring-[#64CCC5] transition-all"
                        :class="$store.theme.dark
                            ? 'bg-gray-900 border-gray-800 text-white placeholder-gray-500'
                            : 'bg-white border-gray-200 text-gray-900'"
                        aria-label="{{ __('sahaba.search_placeholder') }}"
                    >
                </div>
            </form>

            {{-- Filter pills (server-side links) --}}
            <div class="lg:flex-1">
                <x-site.filter-pills :pills="$filterPills" :active="$activeFilter" />
            </div>
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
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 justify-items-center stagger-2 fade-in-up">
                @foreach ($companions as $sahabi)
                    @php $palette = $palettes[$loop->index % count($palettes)]; @endphp
                    <div class="w-full max-w-sm">
                        <x-site.sahaba-card
                            :sahabi="$sahabi"
                            :color="$palette['card']"
                            :badge-icon="$palette['icon']"
                            :badge-color="$palette['ring']"
                            :accent-color="$palette['accent']" />
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
