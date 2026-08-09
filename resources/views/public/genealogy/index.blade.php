@extends('layouts.public')

@section('title', __('genealogy.public.page_title'))
@section('meta_description', __('genealogy.public.page_description'))

@push('structured_data')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "CollectionPage",
    "name": "{{ __('genealogy.public.page_title') }}",
    "description": "{{ __('genealogy.public.page_description') }}",
    "url": "{{ route('public.genealogy') }}",
    "inLanguage": "{{ app()->getLocale() }}"
}
</script>
@endpush

@push('head')
<style>
    /* ══════════════════════════════════════════════════════════════════════
       THE TREE STAGE

       The tree itself is an SVG scene rendered by resources/js/genealogy-tree.js
       — a separate Vite entry that is dynamic-import()ed, with its d3
       dependencies, only when this canvas approaches the viewport. Cards are
       HTML inside <foreignObject>, so everything visual about them is styled
       HERE: dark mode, brand palette and RTL text shaping all come from the
       page, and the JS never hardcodes a colour.
       ══════════════════════════════════════════════════════════════════════ */
    .gt-stage {
        position: relative;
        height: min(74vh, 46rem);
        min-height: 30rem;
        border-radius: 1.5rem;
        overflow: hidden;
        cursor: grab;
        background:
            radial-gradient(90% 70% at 50% 0%, rgba(0, 86, 179, .06) 0%, transparent 60%),
            linear-gradient(180deg, #fdfefe 0%, #f4f8fb 100%);
        border: 1px solid rgba(0, 0, 0, .06);
        box-shadow: 0 24px 60px -30px rgba(0, 86, 179, .35), inset 0 1px 0 rgba(255, 255, 255, .8);
    }
    .gt-stage:active { cursor: grabbing; }
    .dark .gt-stage {
        background:
            radial-gradient(90% 70% at 50% 0%, rgba(100, 204, 197, .07) 0%, transparent 60%),
            linear-gradient(180deg, #1c2440 0%, #141a30 100%);
        border-color: rgba(255, 255, 255, .08);
        box-shadow: 0 24px 60px -30px rgba(0, 0, 0, .8);
    }
    /* Subtle dot grid gives the pan gesture something to move against. */
    .gt-stage::before {
        content: '';
        position: absolute;
        inset: 0;
        background-image: radial-gradient(rgba(0, 86, 179, .13) 1px, transparent 1px);
        background-size: 26px 26px;
        pointer-events: none;
    }
    .dark .gt-stage::before {
        background-image: radial-gradient(rgba(100, 204, 197, .1) 1px, transparent 1px);
    }

    .gt-svg { display: block; touch-action: none; }
    .gt-cell { width: 100%; height: 100%; display: flex; align-items: flex-start; justify-content: center; }

    /* ── Edges ── */
    .gt-link {
        fill: none;
        stroke: rgba(0, 86, 179, .3);
        stroke-width: 2;
    }
    .gt-link--collateral { stroke: rgba(0, 86, 179, .16); stroke-dasharray: 5 4; }
    .gt-link--focus { stroke: rgba(33, 136, 56, .5); stroke-width: 2.5; }
    .dark .gt-link { stroke: rgba(100, 204, 197, .3); }
    .dark .gt-link--collateral { stroke: rgba(100, 204, 197, .15); }
    .dark .gt-link--focus { stroke: rgba(100, 204, 197, .55); }

    /* ── Cards ── */
    .gt-card {
        position: relative;
        display: flex;
        align-items: center;
        gap: .55rem;
        width: 100%;
        height: calc(100% - 8px);
        padding: .5rem .65rem;
        border-radius: .9rem;
        text-align: start;
        cursor: pointer;
        background: #fff;
        border: 1px solid rgba(0, 0, 0, .07);
        box-shadow: 0 6px 18px -10px rgba(0, 0, 0, .35);
        transition: transform .25s cubic-bezier(.22, 1, .36, 1), box-shadow .25s ease, border-color .25s ease;
    }
    .gt-card:hover {
        transform: translateY(-3px);
        border-color: rgba(0, 86, 179, .45);
        box-shadow: 0 16px 30px -14px rgba(0, 86, 179, .5);
    }
    .gt-card:focus-visible { outline: 2px solid #0056b3; outline-offset: 2px; }
    .dark .gt-card { background: #1d2743; border-color: rgba(255, 255, 255, .1); }
    .dark .gt-card:hover { border-color: rgba(100, 204, 197, .5); box-shadow: 0 16px 30px -14px rgba(0, 0, 0, .7); }

    /* The chain above the focus person. */
    .gt-card--ancestor { border-inline-start: 3px solid rgba(0, 86, 179, .55); }
    .dark .gt-card--ancestor { border-inline-start-color: rgba(100, 204, 197, .55); }

    /* Uncles, aunts, cousins — present but visually subordinate. */
    .gt-card--collateral { opacity: .88; }

    /* The focus person ﷺ. */
    .gt-card--focus {
        background: linear-gradient(135deg, #0056b3 0%, #1a7a6d 55%, #218838 100%);
        border-color: transparent;
        box-shadow: 0 18px 40px -16px rgba(0, 86, 179, .65);
    }
    .gt-card--focus:hover { box-shadow: 0 24px 50px -16px rgba(0, 86, 179, .8); }
    .gt-card--focus .gt-name { color: #fff; font-size: .95rem; }
    .gt-card--focus .gt-sub { color: rgba(255, 255, 255, .85); }
    .gt-card--focus .gt-spouse { color: rgba(255, 255, 255, .75); }

    .gt-ava {
        flex-shrink: 0;
        width: 2.1rem;
        height: 2.1rem;
        border-radius: 9999px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1rem;
        color: #fff;
        background: linear-gradient(135deg, #0056b3, #3b82f6);
        box-shadow: inset 0 0 0 2px rgba(255, 255, 255, .35);
        user-select: none;
    }
    .gt-card--collateral .gt-ava { background: linear-gradient(135deg, #218838, #64CCC5); }
    .gt-ava--focus {
        width: 2.9rem;
        height: 2.9rem;
        font-size: 1.5rem;
        background: rgba(255, 255, 255, .18);
        backdrop-filter: blur(4px);
        box-shadow: inset 0 0 0 2px rgba(255, 255, 255, .4);
    }

    .gt-meta { min-width: 0; display: flex; flex-direction: column; gap: .1rem; }
    .gt-name {
        font-weight: 700;
        font-size: .8rem;
        line-height: 1.25;
        color: #111827;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .dark .gt-name { color: #fff; }
    .gt-sub {
        font-size: .62rem;
        font-weight: 600;
        color: #0f766e;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .dark .gt-sub { color: #64CCC5; }
    .gt-spouse {
        display: inline-flex;
        align-items: center;
        gap: .25rem;
        font-size: .62rem;
        font-weight: 600;
        color: #c2185b;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .dark .gt-spouse { color: #f9a8d4; }
    .gt-spouse svg { width: .65rem; height: .65rem; flex-shrink: 0; }
    .gt-spouse b { font-weight: 800; opacity: .75; }

    .gt-crown {
        position: absolute;
        top: -0.7rem;
        inset-inline-end: .6rem;
        width: 1.5rem;
        height: 1.5rem;
        border-radius: 9999px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        background: linear-gradient(135deg, #f59e0b, #eab308);
        box-shadow: 0 4px 10px -3px rgba(245, 158, 11, .7);
    }
    .gt-crown svg { width: .85rem; height: .85rem; }

    /* Expand / collapse chip riding the card's bottom edge. */
    .gt-toggle {
        margin: 0 auto;
        min-width: 2rem;
        padding: .1rem .5rem;
        border-radius: 9999px;
        font-size: .68rem;
        font-weight: 800;
        line-height: 1.35;
        cursor: pointer;
        color: #0056b3;
        background: #fff;
        border: 1.5px solid rgba(0, 86, 179, .4);
        box-shadow: 0 3px 8px -3px rgba(0, 0, 0, .35);
        transition: transform .2s ease, background .2s ease, color .2s ease;
    }
    .gt-toggle:hover { transform: scale(1.12); background: #0056b3; color: #fff; }
    .dark .gt-toggle { background: #1d2743; color: #64CCC5; border-color: rgba(100, 204, 197, .45); }
    .dark .gt-toggle:hover { background: #64CCC5; color: #141a30; }

    /* Floating control cluster over the stage. */
    .gt-controls {
        position: absolute;
        top: .9rem;
        inset-inline-end: .9rem;
        z-index: 10;
        display: flex;
        flex-direction: column;
        gap: .35rem;
    }
    .gt-ctrl {
        width: 2.4rem;
        height: 2.4rem;
        border-radius: .8rem;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #374151;
        background: rgba(255, 255, 255, .92);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(0, 0, 0, .07);
        box-shadow: 0 6px 16px -8px rgba(0, 0, 0, .4);
        transition: transform .2s ease, color .2s ease;
    }
    .gt-ctrl:hover { transform: scale(1.08); color: #0056b3; }
    .gt-ctrl:active { transform: scale(.95); }
    .dark .gt-ctrl { background: rgba(29, 39, 67, .92); color: #d1d5db; border-color: rgba(255, 255, 255, .1); }
    .dark .gt-ctrl:hover { color: #64CCC5; }

    /* ── Shared entrance ── */
    @keyframes genealogy-fade-in-up {
        from { opacity: 0; transform: translateY(14px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .card-enter {
        animation: genealogy-fade-in-up .45s cubic-bezier(.16, 1, .3, 1) both;
        animation-delay: var(--enter-delay, 0ms);
    }

    @media (prefers-reduced-motion: reduce) {
        .card-enter { animation: none !important; }
        .gt-card, .gt-toggle, .gt-ctrl { transition: none !important; }
        .gt-card:hover, .gt-toggle:hover, .gt-ctrl:hover { transform: none !important; }
    }
</style>
@endpush

@section('content')
{{-- The list view renders the Sahaba "Refined Medallion" card from Alpine data,
     so it needs that component's stylesheet even though no x-site.sahaba-card
     is rendered on this page. --}}
<x-site.medallion-card-styles />
@php
    $peopleJson = json_encode($people, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS);
    $graphJson = json_encode($graph, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS);
    $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true);

    // One map instead of positional id arrays. The filter pills are built from
    // this: a group with nobody in it never renders a pill, so the list cannot
    // offer a filter that returns an empty grid.
    $groupIdsJson = json_encode([
        'lineage' => $lineageIds ?? [],
        'wives' => $spouseIds ?? [],
        'children' => $childrenIds ?? [],
        'grandchildren' => $grandchildrenIds ?? [],
        'siblings' => $siblingIds ?? [],
        'uncles' => $uncleAuntIds ?? [],
        'cousins' => $cousinIds ?? [],
    ], JSON_UNESCAPED_UNICODE);

    $hasGraph = $graph !== null && ($hasLineage || $hasTree);
    $oldestAncestor = ($lineage ?? []) !== [] ? ($lineage[0]['name'] ?? null) : null;
@endphp

<div
    x-data="genealogyPage({{ $peopleJson }}, {{ $groupIdsJson }})"
    x-init="init()"
    class="min-h-screen">

    {{-- ═══════════════════ HERO ═══════════════════
         Deliberately shallow: one breadcrumb row, one headline row with the
         stats folded into a compact strip beside it, one CTA row. The tree is
         the page; the hero's job is to hand over to it above the fold. --}}
    <section class="relative overflow-hidden py-8 sm:py-10
                    bg-gradient-to-br from-[#f2f7fd] via-[#f4faf6] to-[#eefaf9]
                    dark:from-[#1a1a2e] dark:via-[#16213e] dark:to-[#0f3460]">

        {{-- Eight-point-star tessellation, decorative only. --}}
        <svg class="absolute inset-0 w-full h-full opacity-[0.05] dark:opacity-[0.08]
                    text-[#0056b3] dark:text-[#64CCC5] pointer-events-none" aria-hidden="true">
            <defs>
                <pattern id="nasab-motif" width="60" height="60" patternUnits="userSpaceOnUse">
                    <g fill="none" stroke="currentColor" stroke-width="1.1">
                        <rect x="15" y="15" width="30" height="30"/>
                        <rect x="15" y="15" width="30" height="30" transform="rotate(45 30 30)"/>
                        <circle cx="30" cy="30" r="4.5"/>
                    </g>
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#nasab-motif)"/>
        </svg>
        <div class="absolute inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
            <div class="absolute -top-32 -end-24 w-80 h-80 bg-primary/10 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-36 -start-24 w-80 h-80 bg-secondary/10 rounded-full blur-3xl"></div>
        </div>

        <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="mb-5">
                <x-site.breadcrumb :items="[['label' => __('genealogy.public.hero_title')]]" />
            </div>

            <div class="flex flex-col lg:flex-row lg:items-center gap-6 lg:gap-10">
                {{-- Headline --}}
                <div class="lg:flex-1 min-w-0">
                    <span class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full mb-3
                                 bg-white/80 dark:bg-white/10 backdrop-blur
                                 ring-1 ring-black/5 dark:ring-white/10
                                 text-[11px] sm:text-xs font-bold tracking-wide
                                 text-gray-700 dark:text-gray-200">
                        <i data-lucide="sparkles" class="w-3.5 h-3.5 text-primary dark:text-accent"></i>
                        {{ __('genealogy.public.hero_badge') }}
                    </span>

                    <h1 class="text-3xl sm:text-4xl lg:text-[2.6rem] leading-[1.15] font-black
                               text-gray-900 dark:text-white">
                        {{ __('genealogy.public.hero_title') }}
                        <span class="text-gradient">{{ __('genealogy.public.hero_subtitle') }}</span>
                    </h1>

                    <p class="mt-3 text-sm sm:text-[15px] max-w-xl
                              text-gray-600 dark:text-gray-300 leading-relaxed">
                        {{ __('genealogy.public.hero_lead') }}
                    </p>

                    <div class="flex flex-wrap gap-2.5 mt-5">
                        @if ($hasGraph)
                            <button type="button" @click="showTree()"
                                    class="inline-flex items-center gap-2 px-6 py-2.5 rounded-full text-sm font-bold text-white
                                           bg-gradient-to-l from-primary to-secondary
                                           shadow-[0_10px_26px_-12px_rgba(0,86,179,.7)]
                                           hover:shadow-[0_14px_32px_-12px_rgba(0,86,179,.85)]
                                           hover:-translate-y-0.5 active:translate-y-0 transition-all">
                                <i data-lucide="git-branch-plus" class="w-4 h-4"></i>
                                <span>{{ __('genealogy.public.explore_tree') }}</span>
                            </button>
                        @endif
                        <button type="button" @click="viewMode = 'list'"
                                class="inline-flex items-center gap-2 px-6 py-2.5 rounded-full text-sm font-bold transition-all
                                       bg-white/90 dark:bg-[#16213e] text-gray-900 dark:text-white
                                       ring-1 ring-black/10 dark:ring-white/10
                                       hover:-translate-y-0.5 active:translate-y-0">
                            <i data-lucide="list" class="w-4 h-4"></i>
                            <span>{{ __('genealogy.public.view_list') }}</span>
                        </button>
                    </div>
                </div>

                {{-- Stat strip — one glass panel; every figure is a count this
                     page actually renders, not a site-wide total. --}}
                <div class="shrink-0 grid grid-cols-3 lg:grid-cols-1 rounded-2xl overflow-hidden
                            bg-white/75 dark:bg-white/[0.06] backdrop-blur
                            ring-1 ring-black/5 dark:ring-white/10
                            shadow-[0_10px_36px_-20px_rgba(0,86,179,.5)]
                            divide-x lg:divide-x-0 lg:divide-y divide-black/5 dark:divide-white/10 rtl:divide-x-reverse">
                    <div class="px-5 py-3 lg:px-7 lg:py-3.5 flex lg:items-center gap-1.5 lg:gap-3 flex-col lg:flex-row">
                        <span class="text-xl sm:text-2xl font-black text-gradient tabular-nums leading-none"
                              x-text="displayStats.generations"
                              x-init="countUp('generations', {{ (int) $stats['generations'] }})">0</span>
                        <span class="text-[10px] sm:text-[11px] font-semibold text-gray-600 dark:text-gray-400">
                            {{ __('genealogy.public.stat_generations') }}
                        </span>
                    </div>
                    <div class="px-5 py-3 lg:px-7 lg:py-3.5 flex lg:items-center gap-1.5 lg:gap-3 flex-col lg:flex-row">
                        <span class="text-xl sm:text-2xl font-black text-gradient tabular-nums leading-none"
                              x-text="displayStats.people"
                              x-init="countUp('people', {{ (int) $stats['people'] }})">0</span>
                        <span class="text-[10px] sm:text-[11px] font-semibold text-gray-600 dark:text-gray-400">
                            {{ __('genealogy.public.stat_people') }}
                        </span>
                    </div>
                    <div class="px-5 py-3 lg:px-7 lg:py-3.5 flex lg:items-center gap-1.5 lg:gap-3 flex-col lg:flex-row">
                        @if ((int) $stats['children'] > 0)
                            <span class="text-xl sm:text-2xl font-black text-gradient tabular-nums leading-none"
                                  x-text="displayStats.children"
                                  x-init="countUp('children', {{ (int) $stats['children'] }})">0</span>
                            <span class="text-[10px] sm:text-[11px] font-semibold text-gray-600 dark:text-gray-400">
                                {{ __('genealogy.public.stat_children') }}
                            </span>
                        @else
                            <span class="text-sm sm:text-base font-black text-gradient leading-tight truncate"
                                  title="{{ $oldestAncestor ?? '—' }}">{{ $oldestAncestor ?? '—' }}</span>
                            <span class="text-[10px] sm:text-[11px] font-semibold text-gray-600 dark:text-gray-400">
                                {{ __('genealogy.public.stat_oldest_ancestor') }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Scopes the sticky controls bar to the tree/list section only. --}}
    <div class="relative">

    {{-- ═══════════════════ CONTROLS (sticky) ═══════════════════ --}}
    <section class="sticky top-16 z-30 border-y border-gray-200 dark:border-gray-800
                    bg-white/90 dark:bg-[#16213e]/90 backdrop-blur">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-2.5">
            <div class="flex flex-wrap items-center justify-between gap-3">

                <div class="flex items-center gap-2">
                    @if ($hasGraph)
                        <div class="flex items-center gap-1 p-1 rounded-lg bg-gray-100 dark:bg-[#0f3460]">
                            <button type="button" @click="showTree()"
                                    :class="viewMode === 'tree' ? 'bg-gradient-to-l from-primary to-secondary text-white shadow' : 'text-gray-500 dark:text-gray-300'"
                                    class="p-2 rounded-md transition-all" title="{{ __('genealogy.public.view_tree') }}"
                                    aria-label="{{ __('genealogy.public.view_tree') }}">
                                <i data-lucide="git-branch-plus" class="w-4 h-4"></i>
                            </button>
                            <button type="button" @click="viewMode = 'list'"
                                    :class="viewMode === 'list' ? 'bg-gradient-to-l from-primary to-secondary text-white shadow' : 'text-gray-500 dark:text-gray-300'"
                                    class="p-2 rounded-md transition-all" title="{{ __('genealogy.public.view_list') }}"
                                    aria-label="{{ __('genealogy.public.view_list') }}">
                                <i data-lucide="list" class="w-4 h-4"></i>
                            </button>
                        </div>
                    @endif
                </div>

                {{-- Filter pills (list view) --}}
                <div x-show="viewMode === 'list'" class="flex items-center gap-2 overflow-x-auto scrollbar-hide">
                    <template x-for="pill in filters" :key="pill.value">
                        <button type="button" @click="activeFilter = pill.value"
                                :class="activeFilter === pill.value
                                    ? 'bg-gradient-to-l from-primary to-secondary text-white shadow-md'
                                    : 'bg-white dark:bg-[#16213e] text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-700 hover:border-primary/50'"
                                class="whitespace-nowrap shrink-0 px-4 py-2 rounded-full text-sm font-semibold transition-all"
                                x-text="pill.label"></button>
                    </template>
                </div>

                <button type="button" @click="searchOpen = true; $nextTick(() => $refs.searchInput?.focus())"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold
                               bg-gray-100 dark:bg-[#0f3460] text-gray-700 dark:text-gray-200
                               hover:bg-gray-200 dark:hover:bg-[#16213e] transition-all">
                    <i data-lucide="search" class="w-4 h-4"></i>
                    <span class="hidden sm:inline">{{ __('genealogy.public.search') }}</span>
                </button>
            </div>
        </div>
    </section>

    {{-- ═══════════════════ MAIN ═══════════════════ --}}
    <main class="py-8 sm:py-10 bg-[#F8F9FA] dark:bg-[#1a1a2e]">

        @if ($hasGraph)
            {{-- ═════ TREE VIEW ═════ --}}
            <div x-show="viewMode === 'tree'" x-cloak x-ref="treeTop"
                 class="container mx-auto px-4 sm:px-6 lg:px-8">

                <div class="gt-stage" x-ref="treeStage">
                    {{-- Floating controls: driven by the mounted tree's API. --}}
                    <div class="gt-controls" x-show="treeReady" x-transition.opacity>
                        <button type="button" class="gt-ctrl" @click="treeApi?.zoomIn()"
                                title="{{ __('genealogy.public.zoom_in') }}" aria-label="{{ __('genealogy.public.zoom_in') }}">
                            <i data-lucide="zoom-in" class="w-4 h-4"></i>
                        </button>
                        <button type="button" class="gt-ctrl" @click="treeApi?.zoomOut()"
                                title="{{ __('genealogy.public.zoom_out') }}" aria-label="{{ __('genealogy.public.zoom_out') }}">
                            <i data-lucide="zoom-out" class="w-4 h-4"></i>
                        </button>
                        <button type="button" class="gt-ctrl" @click="treeApi?.centerFocus()"
                                title="{{ __('genealogy.public.center_focus') }}" aria-label="{{ __('genealogy.public.center_focus') }}">
                            <i data-lucide="crosshair" class="w-4 h-4"></i>
                        </button>
                        <button type="button" class="gt-ctrl" @click="treeApi?.fit()"
                                title="{{ __('genealogy.public.fit_tree') }}" aria-label="{{ __('genealogy.public.fit_tree') }}">
                            <i data-lucide="maximize-2" class="w-4 h-4"></i>
                        </button>
                        <button type="button" class="gt-ctrl" @click="treeApi?.expandAll()"
                                title="{{ __('genealogy.public.expand_all') }}" aria-label="{{ __('genealogy.public.expand_all') }}">
                            <i data-lucide="chevrons-down" class="w-4 h-4"></i>
                        </button>
                        <button type="button" class="gt-ctrl" @click="treeApi?.collapseAll()"
                                title="{{ __('genealogy.public.collapse_all') }}" aria-label="{{ __('genealogy.public.collapse_all') }}">
                            <i data-lucide="chevrons-up" class="w-4 h-4"></i>
                        </button>
                    </div>

                    {{-- Loading state while the module chunk fetches. --}}
                    <div x-show="!treeReady" class="absolute inset-0 z-[5] flex flex-col items-center justify-center gap-3">
                        <i data-lucide="loader-2" class="w-8 h-8 text-primary dark:text-accent animate-spin"></i>
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">
                            {{ __('genealogy.public.canvas_label') }}
                        </span>
                    </div>

                    {{-- The SVG mounts here. --}}
                    <div x-ref="treeCanvas" class="absolute inset-0"></div>
                </div>

                <p class="mt-4 text-center text-[11px] text-gray-400 dark:text-gray-500">
                    {{ __('genealogy.public.canvas_hint') }}
                </p>
            </div>
        @endif

        {{-- ═════ LIST VIEW (also the fallback when there is no tree) ═════ --}}
        <div x-show="viewMode === 'list'" @if($hasGraph) x-cloak @endif
             class="container mx-auto px-4 sm:px-6 lg:px-8">

            @if (! $hasGraph && count($people))
                <div class="mb-6 flex items-start gap-3 rounded-xl border border-amber-200 dark:border-amber-900/50
                            bg-amber-50 dark:bg-amber-900/20 px-4 py-3 text-sm text-amber-800 dark:text-amber-200">
                    <i data-lucide="info" class="w-5 h-5 shrink-0 mt-0.5"></i>
                    <span>{{ __('genealogy.public.tree_unavailable') }}</span>
                </div>
            @endif

            {{-- Loading skeleton (Alpine flips off after init) --}}
            <div x-show="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <x-site.skeleton type="card" :count="6" />
            </div>

            <div x-show="!loading">
                @if (count($people))
                    {{-- The same "Refined Medallion" card the Sahaba directory
                         uses, rendered from Alpine data because this list is
                         filtered and paged client-side. --}}
                    <div x-ref="listTop" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-5">
                        <template x-for="(person, idx) in pagedPeople" :key="person.id">
                            <button type="button" @click="openPerson(person.id)"
                                    :style="`--enter-delay: ${Math.min(idx, 11) * 40}ms`"
                                    :title="personName(person)"
                                    class="sahaba-medallion-card card-enter group flex h-full w-full flex-col rounded-2xl overflow-hidden text-start
                                           bg-gradient-to-br from-white to-[#F8F9FA] dark:from-[#16213e] dark:to-[#1a1a2e]
                                           shadow-[0_10px_30px_-12px_rgba(0,0,0,0.25)] hover:shadow-[0_28px_50px_-12px_rgba(0,0,0,0.35)]
                                           border border-black/5 hover:border-black/10 dark:border-white/10 dark:hover:border-white/20
                                           focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0056b3] focus-visible:ring-offset-2
                                           focus-visible:ring-offset-[#F8F9FA] dark:focus-visible:ring-offset-[#1a1a2e]">

                                <div class="sahaba-portrait relative h-44 sm:h-48 overflow-hidden flex items-center justify-center bg-gradient-to-br"
                                     :class="cardPalette(person, idx).card">

                                    <div class="absolute inset-0 opacity-90"
                                         style="background:radial-gradient(120% 90% at 50% 18%, rgba(255,255,255,.18) 0%, transparent 55%);"></div>

                                    <div class="absolute top-2.5 end-2.5 z-[4] w-7 h-7 rounded-full flex items-center justify-center
                                                bg-gradient-to-br shadow-md ring-2 ring-white/60 dark:ring-white/20"
                                         :class="cardPalette(person, idx).ring">
                                        <i class="w-4 h-4 text-white" :data-lucide="cardPalette(person, idx).icon"></i>
                                    </div>

                                    <div class="relative z-[1] -mt-3 flex items-center justify-center
                                                transition-transform duration-500 group-hover:scale-105">
                                        <svg class="sahaba-medallion absolute w-24 h-24 text-white/90" viewBox="0 0 100 100"
                                             fill="none" aria-hidden="true">
                                            <circle cx="50" cy="50" r="47" stroke="currentColor" stroke-width="0.6"
                                                    stroke-dasharray="1 3" opacity="0.45"/>
                                            <g class="ring-outer" opacity="0.5">
                                                <rect x="22" y="22" width="56" height="56" rx="4" stroke="currentColor" stroke-width="0.9"/>
                                                <rect x="22" y="22" width="56" height="56" rx="4" stroke="currentColor" stroke-width="0.9"
                                                      transform="rotate(45 50 50)"/>
                                            </g>
                                            <g class="ring-inner" opacity="0.7">
                                                <circle cx="50" cy="50" r="29" stroke="currentColor" stroke-width="0.8"/>
                                                <circle cx="50" cy="50" r="24" stroke="currentColor" stroke-width="0.5" opacity="0.6"/>
                                            </g>
                                        </svg>

                                        <div class="relative w-14 h-14 rounded-full flex items-center justify-center
                                                    bg-white/15 backdrop-blur-sm border border-white/30 shadow-inner">
                                            <span class="text-white text-3xl leading-none select-none drop-shadow"
                                                  style="text-shadow:0 2px 8px rgba(0,0,0,.25);"
                                                  x-text="personInitial(person)"></span>
                                        </div>
                                    </div>

                                    <span class="sahaba-sheen"></span>

                                    <div class="absolute bottom-0 start-0 end-0 z-[3] px-3.5 pb-2.5 pt-7 text-white
                                                bg-gradient-to-t from-black/75 via-black/30 to-transparent">
                                        <template x-if="personHonorific(person)">
                                            <span class="inline-flex max-w-full items-center mb-1 px-2 py-0.5 rounded-full
                                                         text-[10px] font-semibold bg-white/20 backdrop-blur-sm truncate"
                                                  x-text="personHonorific(person)"></span>
                                        </template>
                                        <h3 class="text-lg sm:text-xl font-bold leading-tight truncate"
                                            x-html="highlightMatch(personName(person))"></h3>
                                    </div>
                                </div>

                                {{-- Footer — pinned so the action line aligns across cards. --}}
                                <div class="relative mt-auto px-4 pb-3.5 pt-3 w-full">
                                    <span class="absolute inset-x-4 top-0 h-px opacity-40 text-[#0056b3]" aria-hidden="true"
                                          style="background:linear-gradient(90deg,transparent,currentColor,transparent);"></span>

                                    <div class="flex items-center justify-between gap-3">
                                        <template x-if="personDeathYear(person)">
                                            <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold
                                                         text-gray-500 dark:text-gray-400">
                                                <i class="h-3.5 w-3.5 shrink-0" data-lucide="calendar" aria-hidden="true"></i>
                                                <span class="tabular-nums" x-text="personDeathYear(person)"></span>
                                            </span>
                                        </template>
                                        <template x-if="!personDeathYear(person)"><span></span></template>

                                        <span class="inline-flex items-center gap-1.5 text-xs font-bold text-[#0056b3] dark:text-[#64CCC5]">
                                            <span>{{ __('genealogy.public.view_details') }}</span>
                                            <i class="sahaba-card-arrow h-4 w-4 shrink-0"
                                               data-lucide="{{ $isRtl ? 'arrow-left' : 'arrow-right' }}" aria-hidden="true"></i>
                                        </span>
                                    </div>
                                </div>
                            </button>
                        </template>
                    </div>

                    <nav x-show="filteredPeople.length > perPage" role="navigation"
                         aria-label="{{ __('genealogy.public.pagination_label') }}"
                         class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-10">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('site.showing') }}
                            <span class="font-semibold tabular-nums" x-text="pageRangeStart"></span>–<span class="font-semibold tabular-nums" x-text="pageRangeEnd"></span>
                            {{ __('site.of') }}
                            <span class="font-semibold tabular-nums" x-text="filteredPeople.length"></span>
                        </p>

                        <div class="flex items-center gap-1.5">
                            <button type="button" @click="goToPage(currentPage - 1)"
                                    :disabled="currentPage === 1"
                                    class="px-3 py-2 rounded-lg transition-colors text-gray-600 dark:text-gray-300
                                           hover:bg-primary/10 hover:text-primary
                                           disabled:text-gray-300 dark:disabled:text-gray-600 disabled:hover:bg-transparent disabled:cursor-not-allowed"
                                    aria-label="{{ __('genealogy.public.prev_page') }}">
                                <i data-lucide="chevron-{{ $isRtl ? 'right' : 'left' }}" class="w-4 h-4"></i>
                            </button>

                            <template x-for="page in pageWindow" :key="page">
                                <button type="button" @click="goToPage(page)"
                                        :aria-current="page === currentPage ? 'page' : null"
                                        class="px-4 py-2 rounded-lg font-semibold tabular-nums transition-colors"
                                        :class="page === currentPage
                                            ? 'font-bold text-white bg-gradient-to-l from-primary to-secondary shadow-md'
                                            : 'text-gray-700 dark:text-gray-200 hover:bg-primary/10 hover:text-primary'"
                                        x-text="page"></button>
                            </template>

                            <button type="button" @click="goToPage(currentPage + 1)"
                                    :disabled="currentPage >= totalPages"
                                    class="px-3 py-2 rounded-lg transition-colors text-gray-600 dark:text-gray-300
                                           hover:bg-primary/10 hover:text-primary
                                           disabled:text-gray-300 dark:disabled:text-gray-600 disabled:hover:bg-transparent disabled:cursor-not-allowed"
                                    aria-label="{{ __('genealogy.public.next_page') }}">
                                <i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </nav>

                    <div x-show="filteredPeople.length === 0" x-cloak>
                        <x-site.empty-state
                            icon="users"
                            :title="__('genealogy.public.empty_title')"
                            :message="__('site.try_adjusting_filters')"
                            :showReset="true"
                            @click="activeFilter = 'all'; searchQuery = ''" />
                    </div>
                @else
                    <x-site.empty-state
                        icon="users"
                        :title="__('genealogy.public.empty_title')"
                        :message="__('genealogy.public.empty_message')" />
                @endif
            </div>
        </div>
    </main>
    </div>

    {{-- ═══════════════════ PERSON DETAIL MODAL ═══════════════════ --}}
    <div x-show="selectedPerson" x-cloak
         x-transition.opacity
         @keydown.escape.window="selectedPerson = null"
         @click.self="selectedPerson = null"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
        <div class="w-full max-w-2xl max-h-[85vh] overflow-y-auto rounded-3xl bg-white dark:bg-[#16213e] shadow-2xl"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 translate-y-2 scale-95">

            <div class="sticky top-0 z-10 flex items-center justify-between gap-4 p-5 sm:p-6
                        border-b border-gray-200 dark:border-gray-800 bg-white/95 dark:bg-[#16213e]/95 backdrop-blur">
                <div class="flex items-center gap-4 min-w-0">
                    <div class="w-14 h-14 rounded-full flex items-center justify-center shadow-lg shrink-0
                                bg-gradient-to-br from-primary to-secondary">
                        <i :data-lucide="selectedPerson?.isProphet ? 'crown' : 'user'" class="w-7 h-7 text-white"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white truncate" x-text="selectedPerson?.name"></h3>
                        <p class="text-sm text-accent truncate"
                           x-text="selectedPerson?.isProphet ? '{{ __('genealogy.public.prophet_relation') }}' : (selectedPerson?.title || selectedPerson?.typeLabel || '')"></p>
                    </div>
                </div>
                <button type="button" @click="selectedPerson = null"
                        class="p-2 rounded-full text-gray-500 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#0f3460] transition-all"
                        aria-label="{{ __('genealogy.public.close') }}">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <div class="p-5 sm:p-6 space-y-6">
                <div>
                    <h4 class="flex items-center gap-2 text-lg font-bold mb-2 text-gray-900 dark:text-white">
                        <i data-lucide="info" class="w-5 h-5 text-primary"></i>
                        {{ __('genealogy.public.historical_note') }}
                    </h4>
                    <p class="text-sm leading-relaxed text-gray-700 dark:text-gray-300 whitespace-pre-line"
                       x-text="(selectedPerson?.biography || selectedPerson?.notes) || '{{ __('genealogy.public.no_biography') }}'"></p>
                </div>

                <div class="flex flex-wrap items-center gap-2" x-show="hasDetails(selectedPerson)">
                    <template x-if="selectedPerson?.birthDateHijri || selectedPerson?.birthDateGregorian">
                        <x-site.badge variant="default" icon="calendar-plus">
                            <span x-text="'{{ __('genealogy.public.born') }} ' + (selectedPerson?.birthDateHijri || selectedPerson?.birthDateGregorian)"></span>
                        </x-site.badge>
                    </template>
                    <template x-if="selectedPerson?.deathDateHijri || selectedPerson?.deathDateGregorian">
                        <x-site.badge variant="default" icon="calendar-minus">
                            <span x-text="'{{ __('genealogy.public.died') }} ' + (selectedPerson?.deathDateHijri || selectedPerson?.deathDateGregorian)"></span>
                        </x-site.badge>
                    </template>
                    <template x-if="selectedPerson?.tribe">
                        <x-site.badge variant="accent" icon="users">
                            <span x-text="selectedPerson?.tribe"></span>
                        </x-site.badge>
                    </template>
                </div>

                {{-- Marriages. Every graph node carries its spouses, so the
                     modal shows a person's husband or wife wherever the record
                     holds one — not only for the focus person. Chips navigate:
                     tapping علي on فاطمة's card opens علي. --}}
                <template x-if="selectedPerson?.spouses?.length">
                    <div>
                        <h4 class="flex items-center gap-2 text-sm font-bold mb-2.5 text-gray-900 dark:text-white">
                            <i data-lucide="heart" class="w-4 h-4 text-[#e91e63]"></i>
                            {{ __('genealogy.public.marriages') }}
                            <span class="tabular-nums text-xs font-bold text-gray-400" x-text="`(${selectedPerson.spouses.length})`"></span>
                        </h4>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="spouse in selectedPerson.spouses" :key="'sp-' + spouse.id">
                                <button type="button" @click="openPerson(spouse.id)"
                                        class="inline-flex items-center gap-2 max-w-full ps-1.5 pe-3 py-1.5 rounded-full
                                               bg-pink-50 dark:bg-pink-500/10
                                               border border-dashed border-pink-300/70 dark:border-pink-400/30
                                               hover:border-pink-400 transition-colors
                                               focus:outline-none focus-visible:ring-2 focus-visible:ring-pink-400">
                                    <span class="shrink-0 w-6 h-6 rounded-full flex items-center justify-center
                                                 bg-gradient-to-br from-[#c2185b] to-[#e91e63]">
                                        <span class="text-[11px] font-bold leading-none text-white" x-text="personInitial(spouse)"></span>
                                    </span>
                                    <span class="text-[11px] font-bold truncate text-[#c2185b] dark:text-pink-300" x-text="spouse.name"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>

                <template x-if="selectedPerson?.hasProfile">
                    <a :href="sahabaProfileUrl(selectedPerson)"
                       class="inline-flex items-center justify-center gap-2 w-full px-6 py-3 rounded-xl font-bold text-white active:scale-[0.97]
                              bg-gradient-to-l from-primary to-secondary hover:shadow-lg transition-all">
                        <i data-lucide="book-open" class="w-5 h-5"></i>
                        <span x-text="'{{ __('genealogy.public.know_more_about') }} ' + selectedPerson?.name"></span>
                    </a>
                </template>
                <template x-if="!selectedPerson?.hasProfile && selectedPerson?.isProphet">
                    <a :href="seerahUrl"
                       class="inline-flex items-center justify-center gap-2 w-full px-6 py-3 rounded-xl font-bold text-white active:scale-[0.97]
                              bg-gradient-to-l from-primary to-secondary hover:shadow-lg transition-all">
                        <i data-lucide="book-open" class="w-5 h-5"></i>
                        {{ __('genealogy.public.read_full') }}
                    </a>
                </template>
                <template x-if="!selectedPerson?.hasProfile && !selectedPerson?.isProphet">
                    <p class="text-xs text-center text-gray-500 dark:text-gray-400">
                        {{ __('genealogy.public.no_profile_available') }}
                    </p>
                </template>
            </div>
        </div>
    </div>

    {{-- ═══════════════════ SEARCH MODAL ═══════════════════ --}}
    <div x-show="searchOpen" x-cloak
         x-transition.opacity
         @keydown.escape.window="searchOpen = false"
         @click.self="searchOpen = false"
         class="fixed inset-0 z-50 flex items-start justify-center pt-20 px-4 bg-black/70 backdrop-blur-sm">
        <div class="w-full max-w-2xl rounded-2xl bg-white dark:bg-[#16213e] shadow-2xl overflow-hidden"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-3"
             x-transition:enter-end="opacity-100 translate-y-0">
            <div class="p-5 sm:p-6">
                <div class="relative">
                    <input type="search" x-ref="searchInput" x-model="searchQuery"
                           @input="viewMode = 'list'"
                           placeholder="{{ __('genealogy.public.search_placeholder') }}"
                           class="w-full rounded-xl border-2 border-gray-300 dark:border-gray-700 bg-transparent
                                  py-3.5 ps-14 pe-12 text-base sm:text-lg text-gray-900 dark:text-white
                                  placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:border-primary transition-colors" />
                    <i x-show="!searchLoading" data-lucide="search" class="absolute start-5 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>
                    <i x-show="searchLoading" data-lucide="loader-2" class="absolute start-5 top-1/2 -translate-y-1/2 w-5 h-5 text-primary animate-spin"></i>
                    <button type="button" @click="searchOpen = false"
                            class="absolute end-4 top-1/2 -translate-y-1/2 p-1.5 rounded-lg text-gray-400 hover:bg-gray-100 dark:hover:bg-[#0f3460] transition-all"
                            aria-label="{{ __('genealogy.public.close') }}">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">{{ __('genealogy.public.search_hint') }}</p>
            </div>

            <div class="border-t border-gray-200 dark:border-gray-800 max-h-[50vh] overflow-y-auto">
                <template x-for="(person, idx) in searchResults" :key="'s-' + person.id">
                    <button type="button" @click="searchOpen = false; openPerson(person.id)"
                            class="w-full flex items-center gap-3 px-5 sm:px-6 py-3 text-start active:bg-gray-100 dark:active:bg-[#0f3460]
                                   hover:bg-gray-100 dark:hover:bg-[#0f3460] transition-all card-enter"
                            :style="`--enter-delay: ${Math.min(idx, 8) * 30}ms`">
                        <i :data-lucide="person.isProphet ? 'crown' : 'user'" class="w-5 h-5 text-gray-400 shrink-0"></i>
                        <span class="min-w-0">
                            <span class="block font-semibold text-gray-900 dark:text-white truncate" x-html="highlightMatch(person.name)"></span>
                            <span class="block text-xs text-gray-500 dark:text-gray-400 truncate"
                                  x-text="person.title || person.typeLabel || ''"></span>
                        </span>
                    </button>
                </template>
                <div x-show="searchQuery.length > 0 && !searchLoading && searchResults.length === 0"
                     class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                    {{ __('site.no_results') }}
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function genealogyPage(people, groupIds) {
        // { lineage: Set, wives: Set, children: Set, … } — one lookup table the
        // filter pills and the card palette both read from.
        const groups = Object.fromEntries(
            Object.entries(groupIds || {}).map(([key, ids]) => [key, new Set(ids || [])])
        );

        return {
            people: people || [],
            groups,
            loading: true,

            // ── Interactive tree ────────────────────────────────────────────
            // The component (and d3) live in their own chunk; `treeMounted`
            // guards the import so it fires exactly once.
            treeApi: null,
            treeReady: false,
            treeMounted: false,

            viewMode: @json($hasGraph) ? 'tree' : 'list',
            activeFilter: 'all',
            searchQuery: '',
            searchOpen: false,
            searchLoading: false,
            selectedPerson: null,
            seerahRoute: '{{ route('public.seerah') }}',
            sahabaBaseUrl: '{{ url('/sahaba') }}',
            peopleEndpoint: '{{ route('public.genealogy.people') }}',

            searchDebounceTimer: null,
            lastFetchedQuery: '',

            // Client-side paging over the set this page actually holds.
            perPage: 12,
            currentPage: 1,

            displayStats: { people: 0, children: 0, generations: 0 },

            // A pill is offered only where the group has members, so no filter
            // can lead to an empty grid.
            filters: [
                { value: 'all',           label: '{{ __('genealogy.public.filter_all') }}' },
                { value: 'lineage',       label: '{{ __('genealogy.public.filter_lineage') }}' },
                { value: 'wives',         label: '{{ __('genealogy.public.filter_wives') }}' },
                { value: 'children',      label: '{{ __('genealogy.public.filter_children') }}' },
                { value: 'grandchildren', label: '{{ __('genealogy.public.filter_grandchildren') }}' },
                { value: 'siblings',      label: '{{ __('genealogy.public.kin_siblings') }}' },
                { value: 'uncles',        label: '{{ __('genealogy.public.kin_uncles_aunts') }}' },
                { value: 'cousins',       label: '{{ __('genealogy.public.kin_cousins') }}' },
            ].filter((pill) => pill.value === 'all' || (groups[pill.value]?.size ?? 0) > 0),

            init() {
                this.loading = false;
                this.$nextTick(() => window.lucide?.createIcons());
                this.$watch('viewMode', (mode) => {
                    if (mode === 'tree') this.ensureTree();
                    this.$nextTick(() => window.lucide?.createIcons());
                });
                this.$watch('treeReady', () => this.$nextTick(() => window.lucide?.createIcons()));
                this.$watch('selectedPerson', () => this.$nextTick(() => window.lucide?.createIcons()));
                this.$watch('activeFilter', () => {
                    this.currentPage = 1;
                    this.$nextTick(() => window.lucide?.createIcons());
                });
                this.$watch('searchQuery', () => {
                    this.currentPage = 1;
                    this.$nextTick(() => window.lucide?.createIcons());
                    this.debouncedSearch();
                });

                // Lazy mount: the tree module (and d3) fetch only when the
                // stage is on its way into view — never for a visitor who
                // bounces off the hero, and not at all for the list view.
                if (this.viewMode === 'tree' && this.$refs.treeStage) {
                    const observer = new IntersectionObserver((entries) => {
                        if (entries.some((e) => e.isIntersecting)) {
                            observer.disconnect();
                            this.ensureTree();
                        }
                    }, { rootMargin: '400px' });
                    observer.observe(this.$refs.treeStage);
                }
            },

            async ensureTree() {
                if (this.treeMounted || !this.$refs.treeCanvas) return;
                this.treeMounted = true;
                try {
                    const mod = await import('{{ \Illuminate\Support\Facades\Vite::asset('resources/js/genealogy-tree.js') }}');
                    // Production chunks don't preserve entry exports (see the
                    // note at the bottom of genealogy-tree.js); the module
                    // publishes a window global as its side effect.
                    const mountGenealogyTree = mod.mountGenealogyTree ?? window.mountGenealogyTree;
                    this.treeApi = mountGenealogyTree(
                        this.$refs.treeCanvas,
                        {!! $graphJson ?: 'null' !!},
                        {
                            focusId: {{ (int) ($focusId ?? 0) }},
                            rtl: @json($isRtl),
                            labels: {
                                canvasLabel: '{{ __('genealogy.public.canvas_label') }}',
                                expand: '{{ __('genealogy.public.expand_branch') }}',
                                collapse: '{{ __('genealogy.public.collapse_branch') }}',
                            },
                            onSelect: (person) => this.openPerson(person.id),
                        },
                    );
                    this.treeReady = true;
                } catch (e) {
                    // A failed chunk fetch falls back to the list rather than a
                    // dead stage; the flag resets so a retry can succeed.
                    this.treeMounted = false;
                    this.viewMode = 'list';
                }
            },

            showTree() {
                this.viewMode = 'tree';
                this.$nextTick(() => {
                    this.$refs.treeTop?.scrollIntoView({
                        block: 'start',
                        behavior: this.prefersReducedMotion() ? 'auto' : 'smooth',
                    });
                });
            },

            // ── List cards ──────────────────────────────────────────────────
            personName(person) {
                return (person?.name || '').trim() || '{{ __('genealogy.public.unnamed') }}';
            },

            // Calligraphic monogram — leading punctuation is skipped so
            // transliterations like "'Umar" don't render as an apostrophe.
            personInitial(person) {
                const name = this.personName(person);
                return (name.replace(/^[^\p{L}\p{N}]+/u, '') || name).charAt(0).toUpperCase() || '۞';
            },

            personHonorific(person) {
                if (person?.isProphet) return '{{ __('genealogy.public.prophet_relation') }}';
                return person?.title || person?.typeLabel || '';
            },

            // Already era-formatted server-side ("53 ق.هـ" / "11 هـ").
            personDeathYear(person) {
                return person?.deathDateHijri || '';
            },

            // Palette rotates for variety but stays anchored: the Prophet ﷺ and
            // his wives always get their own colour so the list agrees with the
            // tree rather than recolouring them at random.
            cardPalette(person, index) {
                if (person?.isProphet) {
                    return { card: 'from-[#0056b3] to-[#218838]', ring: 'from-yellow-400 to-yellow-600', icon: 'crown' };
                }
                if (this.groups.wives?.has(person?.id)) {
                    return { card: 'from-[#c2185b] to-[#e91e63]', ring: 'from-pink-300 to-pink-500', icon: 'heart' };
                }
                // Five entries against a four-column grid on purpose: an even
                // count would line each colour up into a vertical stripe.
                const palettes = [
                    { card: 'from-[#0056b3] to-[#004c9e]', ring: 'from-yellow-400 to-yellow-600',   icon: 'star' },
                    { card: 'from-[#218838] to-[#1e7e34]', ring: 'from-[#64CCC5] to-[#218838]',     icon: 'shield' },
                    { card: 'from-[#6f42c1] to-[#5a32a3]', ring: 'from-purple-400 to-purple-600',   icon: 'award' },
                    { card: 'from-[#0f766e] to-[#64CCC5]', ring: 'from-teal-300 to-teal-500',       icon: 'users' },
                    { card: 'from-[#FF7F50] to-[#e8633a]', ring: 'from-orange-400 to-orange-600',   icon: 'flame' },
                ];
                return palettes[index % palettes.length];
            },

            prefersReducedMotion() {
                return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            },

            countUp(key, target) {
                if (this.prefersReducedMotion() || !target) { this.displayStats[key] = target; return; }
                const durationMs = 900;
                const start = performance.now();
                const step = (now) => {
                    const progress = Math.min((now - start) / durationMs, 1);
                    const eased = 1 - Math.pow(1 - progress, 3); // ease-out cubic
                    this.displayStats[key] = Math.round(target * eased);
                    if (progress < 1) requestAnimationFrame(step);
                };
                requestAnimationFrame(step);
            },

            escapeHtml(str) {
                return (str || '').replace(/[&<>"']/g, (c) => ({
                    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
                }[c]));
            },

            highlightMatch(text) {
                const safeText = this.escapeHtml(text);
                const q = this.searchQuery.trim();
                if (!q) return safeText;
                const safeQuery = this.escapeHtml(q).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                return safeText.replace(new RegExp('(' + safeQuery + ')', 'ig'), '<mark class="bg-accent/30 text-inherit rounded-sm">$1</mark>');
            },

            // Membership comes from the server's own grouping — marriages from
            // the relationship rows, everything else derived from the descent
            // edges — never from a gender or name guess.
            matchesFilter(person) {
                if (this.activeFilter === 'all') return true;
                return this.groups[this.activeFilter]?.has(person.id) ?? true;
            },

            get filteredPeople() {
                const q = this.searchQuery.trim().toLowerCase();
                return this.people.filter((p) => {
                    if (!this.matchesFilter(p)) return false;
                    if (!q) return true;
                    return (p.name || '').toLowerCase().includes(q)
                        || (p.title || '').toLowerCase().includes(q);
                });
            },

            get searchResults() {
                const q = this.searchQuery.trim().toLowerCase();
                if (!q) return this.people.slice(0, 8);
                return this.people.filter((p) =>
                    (p.name || '').toLowerCase().includes(q) || (p.title || '').toLowerCase().includes(q)
                ).slice(0, 12);
            },

            // ── Pagination ──────────────────────────────────────────────────
            get totalPages() {
                return Math.max(1, Math.ceil(this.filteredPeople.length / this.perPage));
            },

            get pagedPeople() {
                const start = (this.currentPage - 1) * this.perPage;
                return this.filteredPeople.slice(start, start + this.perPage);
            },

            get pageRangeStart() {
                return this.filteredPeople.length === 0 ? 0 : (this.currentPage - 1) * this.perPage + 1;
            },

            get pageRangeEnd() {
                return Math.min(this.currentPage * this.perPage, this.filteredPeople.length);
            },

            // Five-page window around the cursor, same shape the server-side
            // site.pagination component renders.
            get pageWindow() {
                const last = this.totalPages;
                let from = Math.max(1, this.currentPage - 2);
                const to = Math.min(last, from + 4);
                from = Math.max(1, to - 4);
                return Array.from({ length: to - from + 1 }, (_, i) => from + i);
            },

            goToPage(page) {
                if (page < 1 || page > this.totalPages) return;
                this.currentPage = page;
                this.$nextTick(() => {
                    this.$refs.listTop?.scrollIntoView({
                        block: 'start',
                        behavior: this.prefersReducedMotion() ? 'auto' : 'smooth',
                    });
                });
            },

            get seerahUrl() {
                return this.seerahRoute;
            },

            sahabaProfileUrl(person) {
                return person?.slug ? `${this.sahabaBaseUrl}/${person.slug}` : '#';
            },

            hasDetails(person) {
                return !!(person && (person.birthDateHijri || person.birthDateGregorian
                    || person.deathDateHijri || person.deathDateGregorian || person.tribe));
            },

            openPerson(id) {
                this.selectedPerson = this.people.find((p) => p.id === id) || null;
            },

            mergePeople(freshPeople) {
                const known = new Set(this.people.map((p) => p.id));
                const additions = (freshPeople || []).filter((p) => !known.has(p.id));
                if (additions.length) this.people = this.people.concat(additions);
            },

            debouncedSearch() {
                clearTimeout(this.searchDebounceTimer);
                const q = this.searchQuery.trim();
                if (q.length < 2 || q === this.lastFetchedQuery) return;
                this.searchDebounceTimer = setTimeout(() => this.runServerSearch(q), 300);
            },

            // The embedded set is this page's own family. Searching reaches
            // past it into the wider corpus and merges whatever comes back.
            async runServerSearch(q) {
                this.searchLoading = true;
                try {
                    const url = new URL(this.peopleEndpoint, window.location.origin);
                    url.searchParams.set('q', q);
                    const response = await fetch(url, { headers: { Accept: 'application/json' } });
                    if (!response.ok) throw new Error('genealogy people fetch failed');
                    const result = await response.json();
                    this.mergePeople(result.data);
                    this.lastFetchedQuery = q;
                } catch (e) {
                    // Client-side filtering of what's already loaded still works.
                } finally {
                    this.searchLoading = false;
                }
            },
        };
    }
</script>
@endpush
@endsection
