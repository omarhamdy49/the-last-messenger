@extends('layouts.public')

@section('title', __('quran.public.asbab_page_title'))
@section('meta_description', __('quran.public.asbab_page_description'))

@push('head')
<style>
    .timeline-connector {
        position: absolute;
        inset-inline-start: 20px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: linear-gradient(to bottom, #0056b3, #218838);
    }

    @media (min-width: 768px) {
        .timeline-connector {
            inset-inline-start: 50%;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .animate-fadeInUp, .animate-fadeIn { animation: none !important; }
    }
</style>
@endpush

@section('content')
<div
    x-data="{ loading: false }"
    :class="$store.theme.dark ? 'bg-gray-950' : 'bg-gray-50'"
    class="min-h-dvh font-['Cairo'] transition-colors duration-300"
>

<main class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- Breadcrumb --}}
    <nav class="flex mb-8 text-sm" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-600'"
         aria-label="{{ __('site.breadcrumb') }}">
        <ol class="inline-flex items-center gap-2 flex-wrap">
            <li><a href="{{ route('public.home') }}" class="hover:text-[#64CCC5] transition-colors">{{ __('site.home') }}</a></li>
            <li class="flex items-center gap-2">
                <svg class="w-3 h-3 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                <a href="{{ route('public.quran') }}" class="hover:text-[#64CCC5] transition-colors">{{ __('site.quran') }}</a>
            </li>
            <li class="flex items-center gap-2">
                <svg class="w-3 h-3 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                <span class="font-medium" :class="$store.theme.dark ? 'text-white' : 'text-gray-900'">
                    {{ __('quran.public.asbab_title') }}
                </span>
            </li>
        </ol>
    </nav>

    {{-- Hero --}}
    <section class="relative mb-12 rounded-3xl overflow-hidden shadow-2xl animate-fadeInUp">
        <div class="absolute inset-0 bg-gradient-to-r from-[#0056b3] to-[#218838]"></div>
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\"60\" height=\"60\" viewBox=\"0 0 60 60\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cg fill=\"none\" fill-rule=\"evenodd\"%3E%3Cg fill=\"%23ffffff\" fill-opacity=\"0.4\"%3E%3Cpath d=\"M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
        </div>
        <div class="relative px-8 py-16 md:py-20 text-center text-white">
            <h1 class="text-4xl md:text-6xl font-bold font-['Amiri'] mb-4">{{ __('quran.public.asbab_hero_title') }}</h1>
            <p class="text-lg md:text-xl text-white/90 max-w-2xl mx-auto mb-6">
                {{ __('quran.public.asbab_hero_subtitle') }}
            </p>
            <div class="flex flex-wrap items-center justify-center gap-4 text-sm">
                <div class="flex items-center px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full">
                    <svg class="w-5 h-5 me-2" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                    </svg>
                    <span>{{ __('quran.public.asbab_badge_events') }}</span>
                </div>
                <div class="flex items-center px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full">
                    <svg class="w-5 h-5 me-2" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <span>{{ __('quran.public.asbab_badge_context') }}</span>
                </div>
                <div class="flex items-center px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full">
                    <svg class="w-5 h-5 me-2" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z"/>
                    </svg>
                    <span>{{ __('quran.public.asbab_badge_references') }}</span>
                </div>
            </div>
        </div>
    </section>

    {{-- Search & filters (server-side via GET) --}}
    <section class="mb-8 space-y-4">
        <form method="GET" action="{{ route('public.quran.asbab') }}" id="asbab-filters" @submit="loading = true">
            <input type="hidden" name="period" value="{{ $filters['period'] ?? '' }}">

            <div class="relative mb-4">
                <input
                    type="search"
                    name="q"
                    value="{{ $filters['q'] ?? '' }}"
                    maxlength="100"
                    placeholder="{{ __('quran.public.asbab_search_placeholder') }}"
                    class="w-full px-6 py-4 text-base rounded-2xl shadow-lg transition-all focus:ring-4 focus:ring-[#0056b3]/20 focus:outline-none"
                    :class="$store.theme.dark ? 'bg-gray-900 text-white border-gray-700' : 'bg-white text-gray-900 border-gray-200'"
                    aria-label="{{ __('quran.public.asbab_search_placeholder') }}"
                >
                <button type="submit"
                        class="absolute end-4 top-1/2 -translate-y-1/2 p-2 rounded-xl transition-colors"
                        :class="$store.theme.dark ? 'text-gray-400 hover:text-white' : 'text-gray-500 hover:text-gray-900'"
                        aria-label="{{ __('site.search') }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-4">
                {{-- Period pills --}}
                <div class="flex flex-wrap items-center gap-3" role="group" aria-label="{{ __('quran.public.filter_all') }}">
                    @foreach(['' => __('quran.public.filter_all'), 'makki' => __('quran.public.filter_makki'), 'madani' => __('quran.public.filter_madani')] as $value => $label)
                        <a href="{{ route('public.quran.asbab', array_filter(array_merge($filters, ['period' => $value, 'page' => null]), fn ($v) => $v !== null && $v !== '')) }}"
                           @click="loading = true"
                           class="px-6 py-2.5 rounded-full font-semibold transition-all duration-300 transform hover:scale-105
                                  {{ ($filters['period'] ?? '') === $value || (($filters['period'] ?? null) === null && $value === '') ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg' : '' }}"
                           :class="@js(($filters['period'] ?? '') === $value || (($filters['period'] ?? null) === null && $value === '')) ? '' : ($store.theme.dark ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-200 text-gray-700 hover:bg-gray-300')"
                        >{{ $label }}</a>
                    @endforeach
                </div>

                {{-- Selects --}}
                <div class="flex items-center gap-4 flex-wrap">
                    <select name="surah" onchange="this.form.submit()"
                        class="px-4 py-2.5 text-base sm:text-sm rounded-xl font-medium transition-all focus:ring-4 focus:ring-[#0056b3]/20 focus:outline-none"
                        :class="$store.theme.dark ? 'bg-gray-800 text-white border-gray-700' : 'bg-white text-gray-900 border-gray-200'"
                        aria-label="{{ __('quran.public.all_surahs') }}">
                        <option value="">{{ __('quran.public.all_surahs') }}</option>
                        @foreach($surahOptions as $surahOption)
                            <option value="{{ $surahOption['number'] }}" {{ ($filters['surah'] ?? null) === $surahOption['number'] ? 'selected' : '' }}>
                                {{ $surahOption['number'] }}. {{ $surahOption['nameAr'] }}
                            </option>
                        @endforeach
                    </select>

                    <select name="sort" onchange="this.form.submit()"
                        class="px-4 py-2.5 text-base sm:text-sm rounded-xl font-medium transition-all focus:ring-4 focus:ring-[#0056b3]/20 focus:outline-none"
                        :class="$store.theme.dark ? 'bg-gray-800 text-white border-gray-700' : 'bg-white text-gray-900 border-gray-200'"
                        aria-label="{{ __('quran.public.sort_label') }}">
                        <option value="mushaf" {{ ($filters['sort'] ?? 'mushaf') === 'mushaf' ? 'selected' : '' }}>{{ __('quran.public.sort_number') }}</option>
                        <option value="chronological" {{ ($filters['sort'] ?? '') === 'chronological' ? 'selected' : '' }}>{{ __('quran.public.sort_chronological') }}</option>
                    </select>

                    <select name="per_page" onchange="this.form.submit()"
                        class="px-4 py-2.5 text-base sm:text-sm rounded-xl font-medium transition-all focus:ring-4 focus:ring-[#0056b3]/20 focus:outline-none"
                        :class="$store.theme.dark ? 'bg-gray-800 text-white border-gray-700' : 'bg-white text-gray-900 border-gray-200'"
                        aria-label="{{ __('quran.public.sort_label') }}">
                        @foreach([5, 10, 20, 50] as $size)
                            <option value="{{ $size }}" {{ ($filters['per_page'] ?? 10) === $size ? 'selected' : '' }}>{{ $size }} {{ __('quran.public.asbab_event_unit') }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>
    </section>

    {{-- Results count --}}
    <div class="mb-6 flex items-center justify-between flex-wrap gap-2"
         :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-600'">
        <p class="text-sm">
            {{ __('site.showing') }}
            <span class="font-semibold" :class="$store.theme.dark ? 'text-white' : 'text-gray-900'">{{ $contexts->count() }}</span>
            {{ __('site.of') }}
            <span class="font-semibold" :class="$store.theme.dark ? 'text-white' : 'text-gray-900'">{{ $contexts->total() }}</span>
            {{ __('quran.public.asbab_event_unit') }}
        </p>
        @if($contexts->total() > 0)
        <p class="text-sm">
            {{ __('quran.public.page') }} <span class="font-semibold">{{ $contexts->currentPage() }}</span>
            {{ __('site.of') }} <span class="font-semibold">{{ $contexts->lastPage() }}</span>
        </p>
        @endif
    </div>

    {{-- Skeleton loading --}}
    <div x-show="loading" x-cloak class="space-y-8 mb-12" role="status" aria-label="{{ __('quran.public.asbab_loading') }}">
        <p class="text-center text-sm" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-600'">{{ __('quran.public.asbab_loading') }}</p>
        @for($i = 0; $i < 3; $i++)
            <div class="rounded-2xl shadow-lg overflow-hidden animate-pulse md:w-[calc(50%-3rem)] {{ $i % 2 === 0 ? 'md:me-auto' : 'md:ms-auto' }}"
                 :class="$store.theme.dark ? 'bg-gray-900' : 'bg-white'">
                <div class="p-6 space-y-4">
                    <div class="h-5 w-32 rounded-full" :class="$store.theme.dark ? 'bg-gray-800' : 'bg-gray-200'"></div>
                    <div class="h-7 w-2/3 rounded" :class="$store.theme.dark ? 'bg-gray-800' : 'bg-gray-200'"></div>
                    <div class="h-20 rounded-xl" :class="$store.theme.dark ? 'bg-gray-800' : 'bg-gray-100'"></div>
                    <div class="h-4 rounded" :class="$store.theme.dark ? 'bg-gray-800' : 'bg-gray-200'"></div>
                    <div class="h-4 w-5/6 rounded" :class="$store.theme.dark ? 'bg-gray-800' : 'bg-gray-200'"></div>
                </div>
            </div>
        @endfor
    </div>

    {{-- Timeline --}}
    <div x-show="!loading" class="space-y-8 mb-12 relative">
        @if($contexts->count() > 0)
            <div class="timeline-connector hidden md:block" :class="$store.theme.dark ? 'opacity-20' : 'opacity-30'" aria-hidden="true"></div>
        @endif

        @forelse($contexts as $index => $context)
            <div class="relative animate-fadeInUp" style="animation-delay: {{ ($index % 4) * 0.1 }}s">
                {{-- Timeline node --}}
                <div class="absolute start-0 md:start-1/2 md:rtl:translate-x-1/2 md:ltr:-translate-x-1/2 w-8 h-8 bg-gradient-to-br from-[#0056b3] to-[#218838] rounded-full shadow-lg hidden md:flex items-center justify-center z-10" aria-hidden="true">
                    <div class="w-3 h-3 bg-white rounded-full"></div>
                </div>

                {{-- Card (alternating on desktop, stacked on mobile) --}}
                <div class="md:w-[calc(50%-3rem)] {{ $index % 2 === 0 ? 'md:me-auto' : 'md:ms-auto' }}">
                    <div class="rounded-2xl shadow-lg overflow-hidden hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-1"
                         :class="$store.theme.dark ? 'bg-gray-900' : 'bg-white'">
                        {{-- Header --}}
                        <div class="p-6 bg-gradient-to-r from-[#0056b3]/10 to-[#218838]/10 border-b"
                             :class="$store.theme.dark ? 'border-gray-700' : 'border-gray-200'">
                            <div class="flex items-center justify-between gap-3 mb-3 flex-wrap">
                                <div class="flex items-center gap-3 flex-wrap">
                                    @if($context->period === 'makki')
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                                            {{ __('quran.public.makki') }}
                                        </span>
                                    @else
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300">
                                            {{ __('quran.public.madani') }}
                                        </span>
                                    @endif
                                    <a href="{{ route('public.quran.surah', $context->surahNumber) }}"
                                       class="text-sm font-semibold hover:text-[#64CCC5] transition-colors truncate"
                                       :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-600'">
                                        {{ __('quran.public.surah_unit') }} {{ $context->surahNameAr }} • {{ __('quran.public.asbab_verses_label') }} {{ $context->verseRange }}
                                    </a>
                                </div>
                            </div>
                            <h3 class="text-2xl font-bold mb-1 truncate" :class="$store.theme.dark ? 'text-white' : 'text-gray-900'">
                                {{ $context->title }}
                            </h3>
                        </div>

                        {{-- Body --}}
                        <div class="p-6">
                            {{-- Verse excerpt --}}
                            @if($context->verseExcerpt)
                                <div class="mb-6 p-4 rounded-xl" :class="$store.theme.dark ? 'bg-gray-800/50' : 'bg-gray-50'">
                                    <p class="text-2xl font-['Amiri'] leading-loose text-center" dir="rtl"
                                       :class="$store.theme.dark ? 'text-white' : 'text-gray-900'"
                                       style="word-break: break-word;">
                                        {{ $context->verseExcerpt }}
                                    </p>
                                </div>
                            @endif

                            {{-- Narrative --}}
                            <div class="mb-4">
                                <h4 class="text-lg font-bold mb-3 flex items-center" :class="$store.theme.dark ? 'text-white' : 'text-gray-900'">
                                    <svg class="w-5 h-5 me-2 text-[#0056b3] flex-shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ __('quran.public.historical_context') }}
                                </h4>
                                <p class="leading-relaxed" :class="$store.theme.dark ? 'text-gray-300' : 'text-gray-700'" style="word-break: break-word;">
                                    {{ $context->narrative }}
                                </p>
                            </div>

                            {{-- Source reference --}}
                            @if($context->sourceReference)
                                <div class="pt-4 mt-4 border-t" :class="$store.theme.dark ? 'border-gray-700' : 'border-gray-200'">
                                    <div class="flex items-center gap-2 text-sm flex-wrap" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-600'">
                                        <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                            <path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z"/>
                                        </svg>
                                        <span class="font-semibold">{{ __('quran.public.reference_label') }}:</span>
                                        <span style="word-break: break-word;">{{ $context->sourceReference }}</span>
                                    </div>
                                </div>
                            @endif

                            {{-- Actions --}}
                            <div class="flex flex-wrap items-center gap-3 mt-4">
                                <a href="{{ route('public.quran.surah', $context->surahNumber) }}"
                                   class="flex-grow px-4 py-2 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-semibold text-center hover:shadow-lg transition-all transform hover:scale-105">
                                    {{ __('quran.public.read_surah') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            {{-- Empty state --}}
            <div class="text-center py-20">
                <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="text-2xl font-bold mb-2" :class="$store.theme.dark ? 'text-white' : 'text-gray-900'">{{ __('site.no_results') }}</h3>
                <p class="text-sm mb-4" :class="$store.theme.dark ? 'text-gray-300' : 'text-gray-600'">{{ __('quran.public.asbab_empty_message') }}</p>
                <a href="{{ route('public.quran.asbab') }}"
                   @click="loading = true"
                   class="inline-block mt-2 px-6 py-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-semibold hover:shadow-lg transition-all">
                    {{ __('site.reset_filters') }}
                </a>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($contexts->hasPages())
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 py-8" x-show="!loading">
        {{-- Previous --}}
        @if($contexts->onFirstPage())
            <span class="px-6 py-3 rounded-xl font-semibold opacity-50 cursor-not-allowed flex items-center gap-2"
                  :class="$store.theme.dark ? 'bg-gray-800 text-gray-600' : 'bg-gray-200 text-gray-400'">
                <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                {{ __('quran.public.prev_page') }}
            </span>
        @else
            <a href="{{ $contexts->withQueryString()->previousPageUrl() }}" @click="loading = true"
               class="px-6 py-3 rounded-xl font-semibold transition-all flex items-center gap-2 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white hover:shadow-lg transform hover:scale-105">
                <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                {{ __('quran.public.prev_page') }}
            </a>
        @endif

        {{-- Page numbers (window of 5) --}}
        @php
            $window = 2;
            $start = max(1, $contexts->currentPage() - $window);
            $end = min($contexts->lastPage(), $start + 4);
            $start = max(1, $end - 4);
        @endphp
        <div class="flex items-center gap-2 flex-wrap justify-center">
            @for($page = $start; $page <= $end; $page++)
                @if($page === $contexts->currentPage())
                    <span aria-current="page"
                          class="w-10 h-10 rounded-xl font-semibold flex items-center justify-center bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg">{{ $page }}</span>
                @else
                    <a href="{{ $contexts->withQueryString()->url($page) }}" @click="loading = true"
                       class="w-10 h-10 rounded-xl font-semibold flex items-center justify-center transition-all"
                       :class="$store.theme.dark ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'">{{ $page }}</a>
                @endif
            @endfor
        </div>

        {{-- Next --}}
        @if($contexts->hasMorePages())
            <a href="{{ $contexts->withQueryString()->nextPageUrl() }}" @click="loading = true"
               class="px-6 py-3 rounded-xl font-semibold transition-all flex items-center gap-2 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white hover:shadow-lg transform hover:scale-105">
                {{ __('quran.public.next_page') }}
                <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
            </a>
        @else
            <span class="px-6 py-3 rounded-xl font-semibold opacity-50 cursor-not-allowed flex items-center gap-2"
                  :class="$store.theme.dark ? 'bg-gray-800 text-gray-600' : 'bg-gray-200 text-gray-400'">
                {{ __('quran.public.next_page') }}
                <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
            </span>
        @endif
    </div>
    @endif

</main>

</div>
@endsection
