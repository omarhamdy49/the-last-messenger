@extends('layouts.public')

@section('title', __('quran.playlists.title').' | '.__('quran.public.page_title'))
@section('meta_description', __('quran.playlists.subtitle'))

@push('head')
<style>
    @keyframes plFadeUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }
    .pl-grid .pl-card { animation: plFadeUp .4s cubic-bezier(.16,1,.3,1) both; animation-delay: calc(var(--i, 0) * 45ms); }
    .pl-hero-in { animation: plFadeUp .5s cubic-bezier(.16,1,.3,1) both; }
    .pl-hero-in-2 { animation: plFadeUp .5s cubic-bezier(.16,1,.3,1) .12s both; }
    /* Liquid glass: blur + 1px inner edge + top highlight, so the panel reads as a physical sheet. */
    .pl-glass { background: rgba(255,255,255,.10); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px); border: 1px solid rgba(255,255,255,.22); box-shadow: inset 0 1px 0 rgba(255,255,255,.28), 0 24px 48px -24px rgba(0,0,0,.45); }
    @media (prefers-reduced-motion: reduce) { .pl-grid .pl-card, .pl-hero-in, .pl-hero-in-2 { animation: none; } }
</style>
@endpush

@section('content')
@php
    $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true);
    $num = fn ($n) => app()->getLocale() === 'ar' ? arabic_digits($n) : number_format((int) $n);
    $createUrl = auth()->check() ? route('public.quran.playlists.create') : route('public.login');
    $stats = [
        ['value' => $totals['public'], 'label' => __('quran.playlists.totals_public')],
        ['value' => $totals['plays'], 'label' => __('quran.playlists.totals_plays')],
        ['value' => $totals['reciters'], 'label' => __('quran.playlists.totals_reciters')],
    ];
@endphp

<div class="min-h-dvh font-['Cairo'] bg-gray-50 dark:bg-gray-950 transition-colors duration-300"
     x-data
     :style="$store.audioPlayer.track ? 'padding-bottom: 7rem' : ''">
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">

    {{-- Breadcrumb --}}
    <nav class="mb-5 text-sm text-zinc-500 dark:text-zinc-400 flex items-center gap-2" aria-label="breadcrumb">
        <a href="{{ route('public.quran') }}" class="hover:text-primary transition-colors">{{ __('quran.public.page_title') }}</a>
        <i data-lucide="{{ $isRtl ? 'chevron-left' : 'chevron-right' }}" class="w-4 h-4"></i>
        <span class="text-zinc-900 dark:text-white font-semibold">{{ __('quran.playlists.title') }}</span>
    </nav>

    {{-- ============================ HERO (asymmetric: copy | featured glass panel) ============================ --}}
    <section class="relative rounded-3xl overflow-hidden shadow-2xl mb-10 sm:mb-14">
        <div class="absolute inset-0 bg-gradient-to-l from-[#0056b3] via-[#116a53] to-[#218838]"></div>
        <div class="absolute inset-0 opacity-10 pointer-events-none">
            <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\"60\" height=\"60\" viewBox=\"0 0 60 60\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cg fill=\"none\" fill-rule=\"evenodd\"%3E%3Cg fill=\"%23ffffff\" fill-opacity=\"0.4\"%3E%3Cpath d=\"M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
        </div>
        <div class="absolute -top-24 -end-24 w-96 h-96 rounded-full bg-white/10 blur-3xl pointer-events-none"></div>

        <div class="relative grid lg:grid-cols-[1.15fr_0.85fr] gap-10 lg:gap-14 items-center px-6 sm:px-10 lg:px-14 py-12 sm:py-16 text-white">
            {{-- Copy --}}
            <div class="pl-hero-in">
                <p class="inline-flex items-center gap-2 text-xs font-bold tracking-wide uppercase text-white/80 mb-4">
                    <i data-lucide="list-music" class="w-4 h-4"></i>{{ __('quran.public.page_title') }}
                </p>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold font-['Amiri'] leading-[1.15] text-start">{{ __('quran.playlists.title') }}</h1>
                <p class="mt-4 text-base sm:text-lg text-white/85 leading-relaxed max-w-[52ch]">{{ __('quran.playlists.subtitle') }}</p>

                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <a href="{{ $createUrl }}"
                       @if (! auth()->check()) @click.prevent="$dispatch('open-login')" @endif
                       class="inline-flex items-center gap-2 h-12 px-6 rounded-2xl bg-white text-[#0056b3] font-bold shadow-lg transition-transform duration-200 hover:-translate-y-0.5 active:scale-[0.98] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/80">
                        <i data-lucide="list-plus" class="w-5 h-5"></i>{{ __('quran.playlists.create') }}
                    </a>
                    <a href="{{ route('public.quran.reciters') }}"
                       class="inline-flex items-center gap-2 h-12 px-5 rounded-2xl pl-glass text-white font-semibold transition-transform duration-200 hover:-translate-y-0.5 active:scale-[0.98] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/80">
                        <i data-lucide="mic" class="w-5 h-5"></i>{{ __('quran.public.reciters') }}
                    </a>
                </div>

                {{-- Bare figures, divided — no boxes --}}
                <dl class="mt-10 grid grid-cols-3 max-w-md divide-x divide-white/25 {{ $isRtl ? 'divide-x-reverse' : '' }}">
                    @foreach ($stats as $i => $stat)
                        <div class="{{ $i === 0 ? '' : 'ps-5' }} {{ $i === 2 ? '' : 'pe-5' }}">
                            <dd class="text-3xl sm:text-4xl font-black tabular-nums leading-none">{{ $num($stat['value']) }}</dd>
                            <dt class="mt-1.5 text-xs sm:text-sm text-white/75">{{ $stat['label'] }}</dt>
                        </div>
                    @endforeach
                </dl>
            </div>

            {{-- Featured glass panel / how-it-works --}}
            <div class="pl-hero-in-2 lg:justify-self-end w-full max-w-md">
                @if ($featured)
                    @php
                        $fr = array_slice($featured['reciters'], 0, 4);
                        $fHue = 165 + (crc32($featured['slug']) % 60);
                    @endphp
                    <a href="{{ $featured['url'] }}" class="group block rounded-3xl pl-glass p-5 sm:p-6 transition-transform duration-300 hover:-translate-y-1 active:scale-[0.99] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/80">
                        <div class="flex items-center justify-between mb-4">
                            <span class="inline-flex items-center gap-1.5 text-xs font-bold text-amber-200">
                                <i data-lucide="star" class="w-3.5 h-3.5 fill-current"></i>{{ __('quran.playlists.featured') }}
                            </span>
                            <span class="inline-flex items-center gap-1 text-xs text-white/75 tabular-nums">
                                <i data-lucide="headphones" class="w-3.5 h-3.5"></i>{{ $num($featured['plays']) }}
                            </span>
                        </div>

                        {{-- portrait collage on a per-playlist tint --}}
                        <div class="relative rounded-2xl overflow-hidden aspect-[16/9] mb-4" style="background: linear-gradient(135deg, hsl({{ $fHue }} 62% 40%) 0%, hsl({{ $fHue + 28 }} 58% 30%) 100%)">
                            <div class="absolute inset-0 opacity-[0.08]" style="background-image: radial-gradient(circle, #fff 1px, transparent 1px); background-size: 18px 18px;"></div>
                            <span aria-hidden="true" class="absolute top-1 end-4 text-[72px] leading-none font-black tabular-nums text-white/15 select-none">{{ $num($featured['items']) }}</span>
                            <div class="absolute inset-0 flex items-end p-4">
                                <div class="flex {{ $isRtl ? 'space-x-reverse' : '' }} -space-x-4">
                                    @foreach ($fr as $i => $r)
                                        @if ($r['image'])
                                            <img src="{{ $r['image'] }}" alt="{{ $r['name'] }}" width="64" height="64" class="w-16 h-16 rounded-full object-cover ring-[3px] ring-white/90 shadow-lg">
                                        @else
                                            <span class="w-16 h-16 rounded-full ring-[3px] ring-white/90 bg-white/20 backdrop-blur-sm text-white text-xl font-bold flex items-center justify-center shadow-lg" title="{{ $r['name'] }}">{{ mb_substr($r['name'], 0, 1) }}</span>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                            <span class="absolute bottom-4 end-4 w-12 h-12 rounded-full bg-white text-[#0056b3] flex items-center justify-center shadow-lg transition-transform duration-300 group-hover:scale-110" aria-hidden="true">
                                <i data-lucide="play" class="w-5 h-5 fill-current {{ $isRtl ? '-scale-x-100' : '' }}"></i>
                            </span>
                        </div>

                        <h2 class="text-xl sm:text-2xl font-bold leading-snug line-clamp-2">{{ $featured['name'] }}</h2>
                        <p class="mt-1.5 text-sm text-white/75 tabular-nums">
                            {{ __('quran.playlists.surahs_count', ['count' => $num($featured['items'])]) }}
                            <span aria-hidden="true" class="mx-1.5">·</span>
                            {{ __('quran.playlists.reciters_count', ['count' => $num(count($featured['reciters']))]) }}
                            <span aria-hidden="true" class="mx-1.5">·</span>
                            {{ __('quran.playlists.by') }} {{ $featured['owner']['name'] }}
                        </p>
                    </a>
                @else
                    <div class="rounded-3xl pl-glass p-6 sm:p-7">
                        <p class="text-sm font-bold text-white/80 mb-5">{{ __('quran.playlists.how_title') }}</p>
                        <ol class="space-y-4">
                            @foreach ([['mic', 'how_1'], ['list-checks', 'how_2'], ['play', 'how_3']] as $i => [$icon, $key])
                                <li class="flex items-start gap-4">
                                    <span class="w-11 h-11 shrink-0 rounded-2xl bg-white/15 flex items-center justify-center"><i data-lucide="{{ $icon }}" class="w-5 h-5"></i></span>
                                    <span class="pt-1">
                                        <span class="block text-xs text-white/60 tabular-nums">{{ $num($i + 1) }}</span>
                                        <span class="block font-semibold leading-snug">{{ __('quran.playlists.'.$key) }}</span>
                                    </span>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- ============================ TOOLBAR ============================ --}}
    <form method="GET" action="{{ route('public.quran.playlists.index') }}"
          class="mb-6 rounded-2xl border border-zinc-200/80 dark:border-white/10 bg-white dark:bg-zinc-900 p-3 sm:p-4 grid grid-cols-1 md:grid-cols-[1fr_auto_auto] gap-3 items-center">
        <div class="relative">
            <label for="pl-search" class="sr-only">{{ __('quran.playlists.search') }}</label>
            <i data-lucide="search" class="w-4 h-4 absolute top-1/2 -translate-y-1/2 start-4 text-zinc-400 pointer-events-none"></i>
            <input id="pl-search" type="search" name="q" value="{{ $search }}" autocomplete="off" placeholder="{{ __('quran.playlists.search') }}"
                   class="w-full h-12 ps-11 pe-4 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-white placeholder:text-zinc-400 focus:bg-white dark:focus:bg-zinc-900 focus:border-primary focus:ring-2 focus:ring-primary/20 transition">
        </div>
        <input type="hidden" name="sort" value="{{ $sort }}">
        <div class="inline-flex p-1 rounded-xl bg-zinc-100 dark:bg-zinc-800" role="group" aria-label="{{ __('quran.playlists.sort_latest') }} / {{ __('quran.playlists.sort_plays') }}">
            @foreach (['latest' => ['sort_latest', 'clock'], 'plays' => ['sort_plays', 'flame']] as $key => [$label, $icon])
                <a href="{{ route('public.quran.playlists.index', array_filter(['q' => $search, 'sort' => $key])) }}"
                   @if ($sort === $key) aria-current="true" @endif
                   class="inline-flex items-center gap-1.5 h-10 px-4 rounded-lg text-sm font-semibold transition-all
                          {{ $sort === $key ? 'bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white shadow-sm' : 'text-zinc-600 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-white' }}">
                    <i data-lucide="{{ $icon }}" class="w-4 h-4"></i>{{ __('quran.playlists.'.$label) }}
                </a>
            @endforeach
        </div>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 tabular-nums md:text-end">
            {{ __('quran.playlists.results_count', ['count' => $num($playlists->total())]) }}
        </p>
        <button type="submit" class="sr-only">{{ __('quran.playlists.search') }}</button>
    </form>

    {{-- ============================ GRID ============================ --}}
    @if ($playlists->count())
        <div class="pl-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5 sm:gap-6">
            @foreach ($playlists as $i => $card)
                <x-site.playlist-card :card="$card" :index="$i" />
            @endforeach
        </div>
        <x-site.pagination :paginator="$playlists" />
    @else
        <x-site.empty-state icon="list-music"
            :title="$search !== '' ? __('quran.playlists.empty_search') : __('quran.playlists.empty')"
            :message="$search !== '' ? __('site.try_adjusting_filters') : __('quran.playlists.empty_hint')"
            class="rounded-3xl border border-dashed border-zinc-300 dark:border-zinc-700 bg-white/60 dark:bg-zinc-900/60">
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
