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
    /* ── Path of Light — this page's one authored moment ──────────────
       The journey spine fills continuously with scroll (--jp is set 0→1 by a
       rAF-throttled handler) and a glowing traveller rides the fill tip at the
       reading line, trailing light back the way it came. The geometry and the
       clip-path fill are genuinely unique to this page; every colour is a
       system token. Everything else on the page uses .sx-reveal / .sx-rise. */
    #seerah-journey { --jp: 0; }
    .seerah-spine,
    .seerah-spine-fill {
        position: absolute;
        top: 0.75rem;
        bottom: 3rem;
        inset-inline-start: 26px;
        width: 3px;
        border-radius: 999px;
    }
    .seerah-spine { background: var(--sx-line-strong); }
    /* Paint-only reveal: the gradient keeps its position, the clip slides. */
    .seerah-spine-fill {
        background: linear-gradient(to bottom, var(--sx-accent), var(--sx-teal), var(--sx-green));
        clip-path: inset(0 0 calc((1 - var(--jp, 0)) * 100%) 0);
        transition: clip-path 0.18s ease-out;
    }
    .seerah-traveler {
        position: absolute;
        /* Same track geometry as the spine: starts at top .75rem, ends 3rem up. */
        top: calc(0.75rem + var(--jp, 0) * (100% - 3.75rem));
        inset-inline-start: 27.5px;
        width: 26px;
        height: 26px;
        margin-top: -13px;
        margin-inline-start: -13px;
        border-radius: 50%;
        pointer-events: none;
        z-index: 5;
        background: radial-gradient(circle, #fff 0%, var(--sx-teal) 45%, transparent 74%);
        filter: drop-shadow(0 0 10px var(--sx-teal)) drop-shadow(0 0 26px var(--sx-teal));
        transition: top 0.18s ease-out;
        animation: seerah-traveler-breathe 2.6s ease-in-out infinite;
    }
    .seerah-traveler::after {
        content: '';
        position: absolute;
        inset-inline-start: 50%;
        bottom: 50%;
        width: 5px;
        height: 96px;
        margin-inline-start: -2.5px;
        border-radius: 3px;
        opacity: 0.75;
        background: linear-gradient(to top, var(--sx-teal), transparent);
        filter: blur(0.5px);
    }
    @media (min-width: 640px) {
        .seerah-spine,
        .seerah-spine-fill { inset-inline-start: 30px; }
        .seerah-traveler { inset-inline-start: 31.5px; }
    }
    @keyframes seerah-traveler-breathe {
        0%, 100% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.28); opacity: 0.82; }
    }

    /* Era states along the path: a passed node stays lit, the current era's
       card carries the traveller's aura. States, not extra animation. */
    .seerah-node-lit { box-shadow: 0 0 18px -4px color-mix(in srgb, var(--sx-teal) 60%, transparent); }
    .seerah-card-aura { box-shadow: 0 0 0 2px color-mix(in srgb, var(--sx-teal) 45%, transparent), var(--sx-shadow); }

    @media (prefers-reduced-motion: reduce) {
        .seerah-traveler { animation: none; }
        .seerah-traveler,
        .seerah-spine-fill { transition: none; }
    }
</style>
@endpush

@php
    /**
     * The articles feed arrives as JSON, so one real <x-public.seerah.event-card> is
     * rendered below as a stencil inside <template> and cloned + filled per item. The
     * feed therefore gets exactly the same card as the server-rendered directory —
     * including the card→hero image morph — instead of a second card markup to keep
     * in sync. Placeholders are non-blank ('·') so every optional slot exists in the
     * stencil for the script to fill or drop.
     */
    $cardStencil = [
        'url' => '#',
        'title' => '',
        'description' => '·',
        'typeLabel' => '·',
        'icon' => 'circle',
        'image' => null,
        'hijriYear' => 0,
        'hijriDate' => '·',
        'gregorianYear' => '·',
        'location' => '·',
        'hasDispute' => true,
    ];
@endphp

@section('content')
<div x-data="seerahQuest({
        milestones: {{ Js::from(collect($journey)->map(fn ($m) => [
            'slug' => $m['slug'],
            'anchor' => $m['anchor'],
            'title' => $m['title'],
            'range' => $m['hijriRange'],
            'url' => $m['eventsUrl'],
        ])->values()->all()) }},
        articlesUrl: {{ Js::from(route('public.seerah.articles.feed')) }},
        suffix: {{ Js::from([
            'hijri' => __('seerah.public.hijri_suffix'),
            'beforeHijra' => __('seerah.public.before_hijra_suffix'),
            'gregorian' => __('seerah.public.gregorian_suffix'),
        ]) }},
     })" x-init="init()"
     class="sx min-h-[100dvh] pb-20">

    {{-- ───────────────────────── HEADER ───────────────────────── --}}
    <div class="sx-wrap">
        <x-public.editorial.page-hero
            :eyebrow-label="__('seerah.public.journey_badge')" eyebrow-icon="sparkles"
            :title="__('seerah.public.hero_heading')" :lead="__('seerah.public.journey_subtitle')"
            :stats="[
                ['icon' => 'flag', 'label' => __('seerah.public.stat_milestones'), 'value' => $stats['milestones']],
                ['icon' => 'map-pin', 'label' => __('seerah.public.stat_stations'), 'value' => $stats['events']],
                ['icon' => 'calendar-range', 'label' => __('seerah.public.stat_span'), 'value' => $stats['span'], 'small' => true],
            ]">

            {{-- Search is the CTA on a directory: this posts straight to the events
                 page, so typing here lands on the filtered results. --}}
            <form method="GET" action="{{ route('public.seerah.events') }}" role="search"
                  class="flex flex-col gap-2 sm:flex-row sm:items-stretch">
                <label for="seerah-journey-q" class="sr-only">{{ __('seerah.public.search_placeholder') }}</label>
                <div class="relative flex-1">
                    <i data-lucide="search" aria-hidden="true"
                       class="pointer-events-none absolute start-4 top-1/2 h-5 w-5 -translate-y-1/2 text-[color:var(--sx-muted)]"></i>
                    <input id="seerah-journey-q" type="search" name="q"
                           placeholder="{{ __('seerah.public.search_placeholder') }}"
                           class="sx-input !ps-12">
                </div>
                <button type="submit" class="sx-btn is-primary">{{ __('seerah.public.search_submit') }}</button>
            </form>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('public.seerah.events') }}" class="sx-chip">
                    <i data-lucide="sliders-horizontal" aria-hidden="true"></i>{{ __('seerah.public.browse_and_filter') }}
                </a>
                {{-- Shortcut into the filtered directory: the flag exists on individual
                     stations, but nothing at this level pointed at it. --}}
                @if (($stats['disputed'] ?? 0) > 0)
                    <a href="{{ route('public.seerah.events', ['disputed' => 1]) }}" class="sx-chip is-danger">
                        <i data-lucide="alert-triangle" aria-hidden="true"></i>{{ __('seerah.public.dispute_badge') }}
                        <span class="sx-count">{{ $stats['disputed'] }}</span>
                    </a>
                @endif
            </div>
        </x-public.editorial.page-hero>

        {{-- Journey / articles: two readings of the same corpus. --}}
        <div class="sx-tabs" role="tablist" aria-label="{{ __('seerah.public.page_title') }}">
            <button type="button" id="seerah-tab-journey" role="tab" aria-controls="seerah-panel-journey"
                    @click="setMode('journey')" :aria-selected="(mode === 'journey').toString()" class="sx-tab">
                <i data-lucide="route" aria-hidden="true"></i>{{ __('seerah.public.mode_journey') }}
            </button>
            <button type="button" id="seerah-tab-articles" role="tab" aria-controls="seerah-panel-articles"
                    @click="setMode('articles')" :aria-selected="(mode === 'articles').toString()" class="sx-tab">
                <i data-lucide="layout-grid" aria-hidden="true"></i>{{ __('seerah.public.mode_articles') }}
            </button>
        </div>
    </div>

    {{-- The journey groups events under eras. Events outside any era stay
         reachable through the articles feed. Only a genuinely empty corpus
         gets the empty state. --}}
    @if (empty($journey) && $stats['events'] === 0)
        <div class="sx-wrap mt-10">
            <x-public.editorial.empty-state icon="milestone" :title="__('seerah.public.empty_journey')" />
        </div>
    @else

    {{-- ──────────── STICKY TIMELINE BAR (current era + progress rail) ──────────── --}}
    <div x-show="mode==='journey'" x-cloak
         class="sticky top-[var(--sx-header-h)] z-30 border-b border-[color:var(--sx-line)] bg-[color:var(--sx-bg)]/85 backdrop-blur-lg">
        <div class="sx-wrap">
            <div class="mx-auto max-w-3xl py-3">
                {{-- current era (title + its date range) + counter --}}
                <div class="mb-3 flex items-center justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-2.5">
                        <span class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-xl bg-[image:var(--sx-grad)] text-xs font-extrabold tabular-nums text-white"
                              x-text="currentIndex+1"></span>
                        <div class="min-w-0 leading-tight">
                            <span class="sx-clamp-1 block text-sm font-bold text-[color:var(--sx-ink)] sm:text-base"
                                  :title="currentTitle" x-text="currentTitle"></span>
                            <span class="sx-clamp-1 block text-[11px] font-bold tabular-nums text-[color:var(--sx-accent)]"
                                  x-show="currentRange" x-text="currentRange"></span>
                        </div>
                    </div>
                    <span class="mt-0.5 flex flex-shrink-0 items-center gap-1 self-start text-xs font-bold tabular-nums text-[color:var(--sx-muted)]">
                        <i data-lucide="flag-triangle-right" aria-hidden="true" class="h-3.5 w-3.5 text-[color:var(--sx-accent)]"></i>
                        <span x-text="currentIndex+1"></span><span aria-hidden="true">/</span><span x-text="total"></span>
                    </span>
                </div>
                {{-- evenly-distributed progress rail (no year labels) --}}
                <div class="relative flex h-5 items-center">
                    <div class="absolute inset-x-1.5 h-1.5 rounded-full bg-[color:var(--sx-surface-2)]"></div>
                    <div class="absolute start-1.5 h-1.5 rounded-full bg-[image:var(--sx-grad)] transition-all duration-500 ease-out"
                         :style="`width:calc((100% - 0.75rem) * ${total>1 ? currentIndex/(total-1) : 1})`"></div>
                    <div class="relative flex w-full justify-between">
                        @foreach ($journey as $m)
                        @php($i = $loop->index)
                        <button @click="goTo('{{ $m['anchor'] }}', {{ $i }})" type="button"
                                :aria-current="currentIndex==={{ $i }} ? 'step' : false"
                                aria-label="{{ __('seerah.public.milestone_chapter') }} {{ $m['order'] }}"
                                title="{{ __('seerah.public.milestone_chapter') }} {{ $m['order'] }} — {{ $m['title'] }}"
                                class="group relative -m-0.5 flex h-5 w-5 items-center justify-center">
                            <span class="block rounded-full ring-2 ring-[color:var(--sx-bg)] transition-all duration-300"
                                  :class="currentIndex==={{ $i }} ? 'w-4 h-4 bg-[image:var(--sx-grad)]' : ({{ $i }} < currentIndex ? 'w-2.5 h-2.5 bg-[color:var(--sx-accent)]' : 'w-2.5 h-2.5 bg-[color:var(--sx-line-strong)]')"></span>
                        </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ───────── JOURNEY: vertical timeline spine, date-range eras ───────── --}}
    <div x-show="mode==='journey'" x-cloak id="seerah-panel-journey" role="tabpanel" aria-labelledby="seerah-tab-journey"
         class="sx-wrap pb-10 pt-8">
        <div class="relative mx-auto max-w-3xl" id="seerah-journey">
            <div class="seerah-spine" aria-hidden="true"></div>
            {{-- spine progress fill — clip-path follows --jp (continuous scroll progress) --}}
            <div class="seerah-spine-fill" aria-hidden="true"></div>
            {{-- the traveller light riding the fill tip --}}
            <div class="seerah-traveler" aria-hidden="true"></div>

            @foreach ($journey as $m)
            @php($i = $loop->index)
            <article id="{{ $m['anchor'] }}" data-milestone="{{ $m['slug'] }}" data-index="{{ $i }}" data-shown="false"
                     class="sx-reveal relative scroll-mt-32 pb-7 ps-16 sm:pb-9 sm:ps-[78px]">

                {{-- node on the spine — the milestone's own gradient plate (admin content) --}}
                <div class="absolute start-0 top-1">
                    <div class="relative flex h-[54px] w-[54px] flex-col items-center justify-center rounded-2xl bg-gradient-to-br {{ $m['gradient'] }} text-white ring-4 ring-[color:var(--sx-bg)] transition-all duration-300 sm:h-[62px] sm:w-[62px]"
                         :class="currentIndex==={{ $i }} ? 'scale-[1.07] seerah-node-lit' : ({{ $i }} > currentIndex ? 'opacity-80 saturate-[.85]' : 'seerah-node-lit')">
                        <span class="text-lg font-extrabold leading-none tabular-nums sm:text-2xl">{{ $m['order'] }}</span>
                        <i data-lucide="{{ $m['icon'] }}" aria-hidden="true" class="mt-0.5 h-3.5 w-3.5 opacity-90"></i>
                        <div x-show="{{ $i }} < currentIndex" x-cloak
                             class="absolute -end-1.5 -top-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-[color:var(--sx-green)] ring-2 ring-[color:var(--sx-bg)]">
                            <i data-lucide="check" aria-hidden="true" class="h-3 w-3 text-white"></i>
                        </div>
                    </div>
                </div>

                {{-- era card --}}
                <div class="sx-surface overflow-hidden" :class="currentIndex==={{ $i }} ? 'seerah-card-aura' : ''">

                    {{-- date-range header (faint era tint, big date) --}}
                    <div class="relative border-b border-[color:var(--sx-line)] px-5 py-4 sm:px-6">
                        <div class="pointer-events-none absolute inset-0 bg-gradient-to-l {{ $m['gradient'] }} opacity-[0.07] dark:opacity-[0.16]"></div>
                        <div class="relative flex flex-wrap items-center justify-between gap-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="sx-chip">
                                    <i data-lucide="flag" aria-hidden="true"></i>{{ __('seerah.public.milestone_chapter') }} {{ $m['order'] }}
                                </span>
                                <span x-show="currentIndex==={{ $i }}" x-cloak class="sx-chip is-active">
                                    <i data-lucide="navigation" aria-hidden="true"></i>{{ __('seerah.public.you_are_here') }}
                                </span>
                                <span x-show="{{ $i }} < currentIndex" x-cloak class="sx-chip">
                                    <i data-lucide="check" aria-hidden="true"></i>{{ __('seerah.public.finish_label') }}
                                </span>
                            </div>
                            <span class="sx-chip">
                                <i data-lucide="map-pin" aria-hidden="true"></i><span class="tabular-nums">{{ $m['eventCount'] }}</span> {{ __('seerah.public.stations_count') }}
                            </span>
                        </div>
                        {{-- the date range, the visual anchor of the era --}}
                        <div class="relative mt-2.5 flex flex-wrap items-baseline gap-2">
                            <i data-lucide="calendar-range" aria-hidden="true" class="h-5 w-5 flex-shrink-0 self-center text-[color:var(--sx-accent)]"></i>
                            @if ($m['hijriRange'])
                                <span class="text-xl font-extrabold leading-none tracking-tight tabular-nums text-[color:var(--sx-ink)] sm:text-3xl">{{ $m['hijriRange'] }}</span>
                            @endif
                            @if ($m['gregorianRange'])
                                <span class="text-xs font-semibold tabular-nums text-[color:var(--sx-muted)] sm:text-sm">· {{ $m['gregorianRange'] }}</span>
                            @endif
                        </div>
                    </div>

                    {{-- body --}}
                    <div class="px-5 py-5 sm:px-6">
                        <h2 class="sx-clamp-2 text-xl font-extrabold leading-snug text-[color:var(--sx-ink)] sm:text-2xl" title="{{ $m['title'] }}">{{ $m['title'] }}</h2>
                        @if ($m['subtitle'])
                            <p class="sx-clamp-2 mt-1.5 text-sm leading-relaxed text-[color:var(--sx-muted)]" title="{{ $m['subtitle'] }}">{{ $m['subtitle'] }}</p>
                        @endif

                        {{-- Per-era flag count, a link into the filtered directory. --}}
                        @if (($m['disputedCount'] ?? 0) > 0)
                            <div class="mt-3 flex flex-wrap gap-2">
                                <a href="{{ $m['disputedUrl'] }}" class="sx-chip is-danger">
                                    <i data-lucide="alert-triangle" aria-hidden="true"></i>{{ __('seerah.public.dispute_badge') }}
                                    <span class="sx-count">{{ $m['disputedCount'] }}</span>
                                </a>
                            </div>
                        @endif

                        {{-- stations — lazy-loaded when the era scrolls into view, one per row --}}
                        <div class="sx-tile-list mt-4" data-events-grid></div>

                        {{-- skeleton placeholders (reserve space, avoid CLS) --}}
                        <div x-show="sections['{{ $m['slug'] }}'] !== 'loaded' && sections['{{ $m['slug'] }}'] !== 'error'"
                             class="sx-tile-list mt-4" aria-hidden="true">
                            @for ($s = 0; $s < min($m['eventCount'], 4); $s++)
                                <div class="sx-tile">
                                    <span class="sx-skeleton block h-10 w-10 !rounded-xl"></span>
                                    <span class="grid gap-2">
                                        <span class="sx-skeleton block h-3.5 w-3/4"></span>
                                        <span class="sx-skeleton block h-2.5 w-1/2"></span>
                                    </span>
                                    <span></span>
                                </div>
                            @endfor
                        </div>

                        {{-- load failure + retry --}}
                        <div x-show="sections['{{ $m['slug'] }}'] === 'error'" x-cloak role="alert"
                             class="sx-note mt-4 items-center !bg-[color:var(--sx-danger-soft)] !text-[color:var(--sx-danger)]">
                            <i data-lucide="wifi-off" aria-hidden="true" class="h-5 w-5 !text-[color:var(--sx-danger)]"></i>
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <p class="font-semibold">{{ __('seerah.public.load_failed') }}</p>
                                <button type="button" @click="loadMilestone('{{ $m['slug'] }}', true)" class="sx-btn">
                                    <i data-lucide="rotate-cw" aria-hidden="true"></i>{{ __('seerah.public.retry') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </article>
            @endforeach

            {{-- finish node --}}
            <div class="relative flex min-h-[54px] flex-col justify-center ps-16 sm:min-h-[62px] sm:ps-[78px]">
                <div class="absolute start-0 top-0 grid h-[54px] w-[54px] place-items-center rounded-2xl bg-[color:var(--sx-accent-soft)] text-[color:var(--sx-accent)] ring-4 ring-[color:var(--sx-bg)] sm:h-[62px] sm:w-[62px]">
                    <i data-lucide="trophy" aria-hidden="true" class="h-6 w-6"></i>
                </div>
                <div class="text-base font-extrabold text-[color:var(--sx-ink)]">{{ __('seerah.public.finish_label') }}</div>
                <div class="text-xs text-[color:var(--sx-muted)] sm:text-sm">{{ __('seerah.public.finish_hint') }}</div>
            </div>
        </div>
    </div>

    {{-- ───────────────────── ARTICLES MODE (lazy infinite feed) ───────────────────── --}}
    <div x-show="mode==='articles'" x-cloak id="seerah-panel-articles" role="tabpanel" aria-labelledby="seerah-tab-articles"
         class="sx-wrap py-9 sm:py-12">
        <p class="mb-6 flex items-center gap-2 text-sm text-[color:var(--sx-muted)]">
            <i data-lucide="clock" aria-hidden="true" class="h-4 w-4"></i>{{ __('seerah.public.articles_mode_hint') }}
        </p>
        <div id="articles-grid" class="sx-grid"></div>

        {{-- feed status: skeletons / error+retry / end --}}
        <div x-show="articles.status === 'loading' || articles.status === 'idle'" class="sx-grid mt-5" aria-hidden="true">
            @for ($s = 0; $s < 6; $s++)
                <div class="sx-card">
                    <div class="sx-card-media sx-skeleton !bg-[color:var(--sx-surface-2)]"></div>
                    <div class="sx-card-body">
                        <div class="sx-skeleton h-5 w-3/4"></div>
                        <div class="sx-skeleton h-4 w-full"></div>
                        <div class="sx-skeleton h-4 w-1/2"></div>
                    </div>
                </div>
            @endfor
        </div>

        <div x-show="articles.status === 'error'" x-cloak role="alert"
             class="sx-note mt-6 items-center !bg-[color:var(--sx-danger-soft)] !text-[color:var(--sx-danger)]">
            <i data-lucide="wifi-off" aria-hidden="true" class="h-5 w-5 !text-[color:var(--sx-danger)]"></i>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="font-semibold">{{ __('seerah.public.load_failed') }}</p>
                <button type="button" @click="loadArticles(true)" class="sx-btn">
                    <i data-lucide="rotate-cw" aria-hidden="true"></i>{{ __('seerah.public.retry') }}
                </button>
            </div>
        </div>

        <p x-show="articles.done" x-cloak class="mt-8 flex items-center justify-center gap-2 text-sm font-semibold text-[color:var(--sx-muted)]">
            <i data-lucide="check-circle-2" aria-hidden="true" class="h-4 w-4"></i>{{ __('seerah.public.articles_end') }}
        </p>

        {{-- sentinel: when visible, the next page loads --}}
        <div id="articles-sentinel" class="h-px"></div>
    </div>

    {{-- CTA --}}
    <section class="sx-wrap mt-4 py-12">
        <div class="sx-surface !border-transparent bg-[image:var(--sx-grad)] p-8 text-center text-white sm:p-10">
            <h2 class="text-2xl font-extrabold sm:text-3xl">{{ __('seerah.public.cta_title') }}</h2>
            <p class="mx-auto mt-3 max-w-2xl leading-relaxed text-white/85">{{ __('seerah.public.cta_subtitle') }}</p>
            {{-- The Shama'il section left the timeline for its own article series,
                 so this CTA is the path readers take to reach it. --}}
            <a href="{{ route('public.series.show', 'shamail-an-nabi') }}" class="sx-btn is-light mt-6">
                <i data-lucide="sparkles" aria-hidden="true"></i>{{ __('seerah.public.browse_shamail_series') }}
            </a>
        </div>
    </section>
    @endif
</div>

{{-- Client-side stencils for the lazy feeds. Data is injected via textContent
     (never innerHTML) so titles/locations stay XSS-safe. --}}
<template id="seerah-ev-tpl">
    <a class="sx-tile">
        <span class="sx-tile-icon"><i data-tpl="icon" aria-hidden="true"></i></span>
        <span class="min-w-0">
            <span data-tpl="title" class="sx-tile-title"></span>
            <span class="sx-tile-sub mt-0.5 flex items-center gap-1.5">
                <span data-tpl="type" class="font-semibold text-[color:var(--sx-accent)]"></span>
                <span data-tpl="hijri" class="tabular-nums"></span>
                <span data-tpl="location" class="truncate"></span>
                <span data-tpl="dispute" class="inline-flex items-center gap-1 font-bold text-[color:var(--sx-danger)]">
                    <i data-lucide="scale" aria-hidden="true" class="h-3 w-3 !text-[color:var(--sx-danger)]"></i>{{ __('seerah.public.dispute_badge') }}
                </span>
            </span>
        </span>
        <i data-lucide="chevron-left" aria-hidden="true" class="h-4 w-4 flex-shrink-0 text-[color:var(--sx-muted)] ltr:rotate-180"></i>
    </a>
</template>

<template id="seerah-art-tpl">
    <x-public.seerah.event-card :event="$cardStencil" />
</template>
@endsection

@push('scripts')
<script>
    function seerahQuest(config) {
        return {
            // With no eras there is nothing for journey mode to draw, so start
            // in articles mode rather than opening on a blank tab.
            mode: (config.milestones.length === 0 || localStorage.getItem('seerahMode') === 'articles') ? 'articles' : 'journey',
            total: config.milestones.length,
            currentIndex: 0,
            currentTitle: config.milestones[0]?.title ?? '',
            currentRange: config.milestones[0]?.range ?? '',
            // slug -> 'idle' | 'loading' | 'loaded' | 'error'
            sections: Object.fromEntries(config.milestones.map(m => [m.slug, 'idle'])),
            articles: { status: 'idle', page: 0, lastPage: null, done: false },
            _milestones: config.milestones,
            _reduce: false,
            _artObserver: null,
            _journeyEl: null,
            _pathTicking: false,

            init() {
                this._reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });

                // Path of Light: continuous journey progress (--jp, 0→1) drives
                // the spine fill and the traveller. One rAF-throttled listener,
                // one layout read per frame.
                this._journeyEl = document.getElementById('seerah-journey');
                const onPathScroll = () => {
                    if (this._pathTicking) return;
                    this._pathTicking = true;
                    requestAnimationFrame(() => { this._pathTicking = false; this.updatePath(); });
                };
                if (this._journeyEl) {
                    window.addEventListener('scroll', onPathScroll, { passive: true });
                    window.addEventListener('resize', onPathScroll, { passive: true });
                    this.updatePath();
                }

                if (this._reduce) {
                    this.revealAll();
                } else {
                    const io = new IntersectionObserver((entries) => {
                        entries.forEach(en => { if (en.isIntersecting) { en.target.dataset.shown = 'true'; io.unobserve(en.target); } });
                    }, { threshold: 0.08 });
                    document.querySelectorAll('.sx-reveal').forEach(el => io.observe(el));
                }

                const spy = new IntersectionObserver((entries) => {
                    entries.forEach(en => {
                        if (en.isIntersecting) {
                            const idx = parseInt(en.target.dataset.index, 10);
                            this.currentIndex = idx;
                            this.currentTitle = this._milestones[idx]?.title ?? '';
                            this.currentRange = this._milestones[idx]?.range ?? '';
                        }
                    });
                }, { rootMargin: '-25% 0px -65% 0px', threshold: 0 });
                document.querySelectorAll('[data-milestone]').forEach(el => spy.observe(el));

                // Lazy-load each era's events when it approaches the viewport.
                const loader = new IntersectionObserver((entries) => {
                    entries.forEach(en => {
                        if (en.isIntersecting) {
                            loader.unobserve(en.target);
                            this.loadMilestone(en.target.dataset.milestone);
                        }
                    });
                }, { rootMargin: '600px 0px' });
                document.querySelectorAll('[data-milestone]').forEach(el => loader.observe(el));

                // Articles feed: sentinel-driven infinite scroll.
                const sentinel = document.getElementById('articles-sentinel');
                if (sentinel) {
                    this._artObserver = new IntersectionObserver((entries) => {
                        if (entries.some(en => en.isIntersecting)) this.loadArticles();
                    }, { rootMargin: '600px 0px' });
                    this._artObserver.observe(sentinel);
                }
                if (this.mode === 'articles') this.loadArticles();
            },

            revealAll() {
                document.querySelectorAll('.sx-reveal').forEach(el => { el.dataset.shown = 'true'; });
            },

            updatePath() {
                if (this.mode !== 'journey' || ! this._journeyEl) return;
                const rect = this._journeyEl.getBoundingClientRect();
                // The "reading line" sits ~38% down the viewport — the light
                // stays just ahead of where the eye is.
                const line = window.innerHeight * 0.38;
                const progress = Math.min(1, Math.max(0, (line - rect.top) / Math.max(rect.height, 1)));
                this._journeyEl.style.setProperty('--jp', progress.toFixed(4));
            },

            async loadMilestone(slug, isRetry = false) {
                const state = this.sections[slug];
                if (state === 'loading' || state === 'loaded' || (state === 'error' && ! isRetry)) return;
                const milestone = this._milestones.find(m => m.slug === slug);
                const grid = document.querySelector(`[data-milestone="${slug}"] [data-events-grid]`);
                if (! milestone || ! grid) return;

                this.sections[slug] = 'loading';
                try {
                    const res = await fetch(milestone.url, { headers: { 'Accept': 'application/json' } });
                    if (! res.ok) throw new Error(`HTTP ${res.status}`);
                    const data = await res.json();
                    grid.replaceChildren(...(data.events ?? []).map(ev => this.buildEventTile(ev)));
                    this.sections[slug] = 'loaded';
                    if (window.lucide) lucide.createIcons();
                } catch (e) {
                    this.sections[slug] = 'error';
                }
            },

            async loadArticles(isRetry = false) {
                const a = this.articles;
                if (this.mode !== 'articles' || a.done || a.status === 'loading' || (a.status === 'error' && ! isRetry)) return;
                a.status = 'loading';
                try {
                    const url = new URL(config.articlesUrl, window.location.origin);
                    url.searchParams.set('page', a.page + 1);
                    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    if (! res.ok) throw new Error(`HTTP ${res.status}`);
                    const data = await res.json();
                    const grid = document.getElementById('articles-grid');
                    (data.items ?? []).forEach(ev => grid.appendChild(this.buildArticleCard(ev)));
                    a.page = data.page;
                    a.lastPage = data.lastPage;
                    a.done = data.page >= data.lastPage;
                    a.status = 'loaded';
                    if (window.lucide) lucide.createIcons();
                } catch (e) {
                    a.status = 'error';
                }
            },

            _fill(root, sel, text) {
                const el = root.querySelector(`[data-tpl="${sel}"]`);
                if (! el) return null;
                if (text === null || text === undefined || text === '') { el.remove(); return null; }
                el.textContent = text;
                return el;
            },

            // Keeps a pre-rendered flag marker (icon + label) or drops it entirely.
            _toggle(root, sel, keep) {
                const el = root.querySelector(`[data-tpl="${sel}"]`);
                if (el && ! keep) el.remove();
            },

            // Replaces the trailing label of an "icon + text" element, keeping the icon.
            _label(el, text) {
                if (! el) return;
                [...el.childNodes].forEach(n => { if (n.nodeType === Node.TEXT_NODE) n.remove(); });
                el.append(' ' + text);
            },

            // A station in an era: one .sx-tile row.
            buildEventTile(ev) {
                const node = document.getElementById('seerah-ev-tpl').content.firstElementChild.cloneNode(true);
                node.href = ev.url;
                node.title = ev.title;
                node.querySelector('[data-tpl="icon"]').setAttribute('data-lucide', ev.icon || 'circle');
                node.querySelector('[data-tpl="title"]').textContent = ev.title;
                this._fill(node, 'type', ev.typeLabel);
                this._fill(node, 'hijri', ev.hijriLabel ? `· ${ev.hijriLabel}` : null);
                this._fill(node, 'location', ev.location ? `· ${ev.location}` : null);
                this._toggle(node, 'dispute', ev.hasDispute);
                return node;
            },

            // The articles feed reuses the editorial card stencil rendered in Blade,
            // so a fetched card is identical to a server-rendered one (morph included).
            buildArticleCard(ev) {
                const node = document.getElementById('seerah-art-tpl').content.firstElementChild.cloneNode(true);
                node.href = ev.url;
                node.title = ev.title;

                // Every lookup is optional: the stencil is Blade-owned, so a prop
                // change there must degrade a card, never throw mid-feed.
                const set = (sel, text) => { const el = node.querySelector(sel); if (el) el.textContent = text; };
                set('.sx-card-title', ev.title);

                const media = node.querySelector('.sx-card-media');
                if (media && ev.image) {
                    const img = document.createElement('img');
                    Object.assign(img, { src: ev.image, alt: '', width: 640, height: 400, loading: 'lazy', decoding: 'async' });
                    img.onerror = () => img.remove();
                    media.prepend(img);
                }

                node.querySelector('.sx-card-glyph i')?.setAttribute('data-lucide', ev.icon || 'circle');

                const badge = node.querySelector('.sx-card-type .sx-glass-chip');
                if (ev.typeLabel) {
                    badge?.querySelector('i')?.setAttribute('data-lucide', ev.icon || 'circle');
                    this._label(badge, ev.typeLabel);
                } else {
                    badge?.remove();
                }

                const year = node.querySelector('.sx-card-year');
                if (ev.hijriYear === null || ev.hijriYear === undefined) {
                    year?.remove();
                } else if (year) {
                    set('.sx-card-year strong', Math.abs(ev.hijriYear));
                    set('.sx-card-year span', ev.hijriYear >= 0 ? config.suffix.hijri : config.suffix.beforeHijra);
                }

                if (ev.description) { set('.sx-card-desc', ev.description); } else { node.querySelector('.sx-card-desc')?.remove(); }

                this._cardMeta(node, 'moon', ev.hijriDate);
                this._cardMeta(node, 'calendar', ev.gregorianYear ? `${ev.gregorianYear} ${config.suffix.gregorian}` : null);
                this._cardMeta(node, 'map-pin', ev.location);
                this._cardMeta(node, 'scale', ev.hasDispute ? true : null);
                return node;
            },

            // One .sx-card-meta entry, addressed by its icon so the stencil's order
            // is not baked into this script. `true` keeps the pre-rendered label.
            _cardMeta(node, icon, text) {
                const entry = [...node.querySelectorAll('.sx-card-meta > span')]
                    .find(span => span.querySelector(`[data-lucide="${icon}"]`));
                if (! entry) return;
                if (! text) { entry.remove(); return; }
                if (text !== true) entry.querySelector('.sx-clamp-1').textContent = text;
            },

            setMode(m) {
                this.mode = m;
                localStorage.setItem('seerahMode', m);
                this.$nextTick(() => {
                    this.revealAll();
                    if (window.lucide) lucide.createIcons();
                });
                if (m === 'articles') this.loadArticles();
                if (m === 'journey') this.$nextTick(() => this.updatePath());
            },

            goTo(anchor, index) {
                if (typeof index === 'number') {
                    this.currentIndex = index;
                    this.currentTitle = this._milestones[index]?.title ?? '';
                    this.currentRange = this._milestones[index]?.range ?? '';
                    // Jumping ahead skips the scroll path — load the target now.
                    const slug = this._milestones[index]?.slug;
                    if (slug) this.loadMilestone(slug);
                }
                const el = document.getElementById(anchor);
                if (el) el.scrollIntoView({ behavior: this._reduce ? 'auto' : 'smooth', block: 'start' });
            },
        };
    }
    document.addEventListener('DOMContentLoaded', () => { if (window.lucide) lucide.createIcons(); });
</script>
@endpush
