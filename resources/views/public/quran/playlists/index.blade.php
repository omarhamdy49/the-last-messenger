@extends('layouts.public')

@section('title', __('quran.playlists.title').' | '.__('quran.public.page_title'))
@section('meta_description', __('quran.playlists.subtitle'))

@push('head')
<style>
    @keyframes plFadeUp { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
    .pl-grid .pl-card { animation: plFadeUp .35s ease-out both; animation-delay: calc(var(--i, 0) * 40ms); }
    @media (prefers-reduced-motion: reduce) { .pl-grid .pl-card { animation: none; } }
</style>
@endpush

@section('content')
@php
    $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true);
    $num = fn ($n) => app()->getLocale() === 'ar' ? arabic_digits($n) : number_format((int) $n);
    $createUrl = auth()->check() ? route('public.quran.playlists.create') : route('public.login');
@endphp

<div class="min-h-dvh font-['Cairo'] bg-gray-50 dark:bg-gray-950 transition-colors duration-300"
     x-data
     :style="$store.audioPlayer.track ? 'padding-bottom: 7rem' : ''">
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">

    {{-- Breadcrumb --}}
    <nav class="mb-6 text-sm text-zinc-500 dark:text-zinc-400 flex items-center gap-2" aria-label="breadcrumb">
        <a href="{{ route('public.quran') }}" class="hover:text-primary transition-colors">{{ __('quran.public.page_title') }}</a>
        <i data-lucide="{{ $isRtl ? 'chevron-left' : 'chevron-right' }}" class="w-4 h-4"></i>
        <span class="text-zinc-900 dark:text-white font-semibold">{{ __('quran.playlists.title') }}</span>
    </nav>

    {{-- Header: start-aligned title, CTA at the end --}}
    <header class="grid md:grid-cols-[1fr_auto] items-end gap-6 mb-10">
        <div>
            <h1 class="text-3xl sm:text-4xl font-black tracking-tight text-zinc-900 dark:text-white text-start">{{ __('quran.playlists.title') }}</h1>
            <p class="mt-2 text-base text-zinc-600 dark:text-zinc-300 leading-relaxed max-w-[60ch]">{{ __('quran.playlists.subtitle') }}</p>
        </div>
        <a href="{{ $createUrl }}"
           @if (! auth()->check()) @click.prevent="$dispatch('open-login')" @endif
           class="inline-flex items-center justify-center gap-2 h-12 px-6 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-bold shadow-sm transition-transform duration-200 hover:-translate-y-0.5 active:scale-[0.98] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/60">
            <i data-lucide="list-plus" class="w-5 h-5"></i>
            {{ __('quran.playlists.create') }}
        </a>
    </header>

    {{-- Featured band: 2fr / 1fr --}}
    @if ($featured)
        <section class="grid md:grid-cols-[2fr_1fr] gap-6 mb-12" aria-label="{{ __('quran.playlists.featured') }}">
            <x-site.playlist-card :card="$featured" size="wide" class="pl-card" />
            <div class="grid grid-cols-2 md:grid-cols-1 gap-6 md:border-s md:border-zinc-200 md:dark:border-zinc-800 md:ps-8 content-center">
                <div>
                    <p class="text-4xl sm:text-5xl font-black tabular-nums text-zinc-900 dark:text-white leading-none">{{ $num($totals['public']) }}</p>
                    <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ __('quran.playlists.totals_public') }}</p>
                </div>
                <div>
                    <p class="text-4xl sm:text-5xl font-black tabular-nums text-zinc-900 dark:text-white leading-none">{{ $num($totals['plays']) }}</p>
                    <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ __('quran.playlists.totals_plays') }}</p>
                </div>
            </div>
        </section>
    @endif

    {{-- Search + sort (plain GET, state in the URL) --}}
    <form method="GET" action="{{ route('public.quran.playlists.index') }}"
          class="flex flex-col sm:flex-row sm:items-end gap-4 mb-6">
        <div class="flex-1">
            <label for="pl-search" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-200 mb-1.5">{{ __('quran.playlists.search') }}</label>
            <div class="relative">
                <i data-lucide="search" class="w-4 h-4 absolute top-1/2 -translate-y-1/2 start-3.5 text-zinc-400 pointer-events-none"></i>
                <input id="pl-search" type="search" name="q" value="{{ $search }}" autocomplete="off"
                       class="w-full h-12 ps-10 pe-4 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white placeholder:text-zinc-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition">
            </div>
        </div>
        <input type="hidden" name="sort" value="{{ $sort }}">
        <div class="flex items-center gap-2" role="group" aria-label="{{ __('quran.playlists.sort_latest') }} / {{ __('quran.playlists.sort_plays') }}">
            @foreach (['latest' => 'sort_latest', 'plays' => 'sort_plays'] as $key => $label)
                <a href="{{ route('public.quran.playlists.index', array_filter(['q' => $search, 'sort' => $key])) }}"
                   @if ($sort === $key) aria-current="true" @endif
                   class="inline-flex items-center h-11 px-4 rounded-full text-sm font-semibold border transition-colors
                          {{ $sort === $key ? 'bg-zinc-900 text-white border-zinc-900 dark:bg-white dark:text-zinc-900 dark:border-white' : 'bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-200 border-zinc-200 dark:border-zinc-700 hover:border-zinc-400' }}">
                    {{ __('quran.playlists.'.$label) }}
                </a>
            @endforeach
            <button type="submit" class="sr-only">{{ __('quran.playlists.search') }}</button>
        </div>
    </form>

    {{-- Grid --}}
    @if ($playlists->count())
        <div class="pl-grid grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
            @foreach ($playlists as $i => $card)
                <x-site.playlist-card :card="$card" :index="$i" />
            @endforeach
        </div>
        <x-site.pagination :paginator="$playlists" />
    @else
        <x-site.empty-state icon="list-music"
            :title="$search !== '' ? __('quran.playlists.empty_search') : __('quran.playlists.empty')"
            :message="$search !== '' ? __('site.try_adjusting_filters') : __('quran.playlists.empty_hint')"
            class="rounded-2xl border border-dashed border-zinc-200 dark:border-zinc-800">
            <a href="{{ $createUrl }}"
               @if (! auth()->check()) @click.prevent="$dispatch('open-login')" @endif
               class="inline-flex items-center gap-2 h-11 px-5 rounded-full text-sm font-semibold text-primary border border-primary/30 hover:bg-primary/5 transition-colors">
                <i data-lucide="list-plus" class="w-4 h-4"></i>{{ __('quran.playlists.create') }}
            </a>
        </x-site.empty-state>
    @endif
</div>
</div>
@endsection
