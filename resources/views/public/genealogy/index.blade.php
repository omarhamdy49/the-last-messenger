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
    "inLanguage": "ar"
}
</script>
@endpush

@push('head')
<style>
    @keyframes genealogy-glow {
        0%, 100% { box-shadow: 0 0 20px rgba(0, 86, 179, 0.3); }
        50%      { box-shadow: 0 0 40px rgba(0, 86, 179, 0.5); }
    }
    .genealogy-glow { animation: genealogy-glow 2.4s ease-in-out infinite; }

    @keyframes genealogy-fade-in-up {
        from { opacity: 0; transform: translateY(14px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .card-enter {
        animation: genealogy-fade-in-up .45s cubic-bezier(.16,1,.3,1) both;
        animation-delay: var(--enter-delay, 0ms);
    }

    .node-card { transition: transform .3s cubic-bezier(.4,0,.2,1), box-shadow .3s; }
    .node-card:hover { transform: translateY(-6px) scale(1.03); }
    .node-card:active { transform: translateY(-2px) scale(.97); }

    /* Edge fades hinting the tree scrolls horizontally — shown/hidden by
       Alpine based on actual scroll position, not just decoration.
       Gradient colors come from Tailwind classes in the markup; this only
       handles positioning, which Tailwind's arbitrary-inset utilities
       can't express as cleanly for logical (RTL-aware) start/end. */
    .tree-scroll-fade { position: absolute; top: 0; bottom: 0; width: 3rem; pointer-events: none; z-index: 5; transition: opacity .2s ease; }
    .tree-scroll-fade--start { inset-inline-start: 0; }
    .tree-scroll-fade--end { inset-inline-end: 0; }

    @media (prefers-reduced-motion: reduce) {
        .genealogy-glow, .card-enter { animation: none !important; }
        .node-card { transition: none !important; }
        .node-card:hover, .node-card:active { transform: none !important; }
    }
</style>
@endpush

@section('content')
@php
    $peopleJson = json_encode($people, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS);
    $childrenIdsJson = json_encode($childrenIds, JSON_UNESCAPED_UNICODE);
    $grandchildrenIdsJson = json_encode($grandchildrenIds, JSON_UNESCAPED_UNICODE);
    $spouseIdsJson = json_encode($spouseIds, JSON_UNESCAPED_UNICODE);
    $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true);
@endphp

<div
    x-data="genealogyPage({{ $peopleJson }}, {{ $childrenIdsJson }}, {{ $grandchildrenIdsJson }}, {{ $spouseIdsJson }})"
    x-init="init()"
    class="min-h-screen">

    {{-- ============ HERO ============ --}}
    <section class="relative overflow-hidden py-14 sm:py-20
                    bg-gradient-to-br from-blue-50 via-green-50 to-cyan-50
                    dark:from-[#1a1a2e] dark:via-[#16213e] dark:to-[#0f3460]">
        <div class="absolute inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
            <div class="absolute -top-40 -end-40 w-80 h-80 bg-primary/10 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-40 -start-40 w-80 h-80 bg-secondary/10 rounded-full blur-3xl"></div>
        </div>

        <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="mb-6">
                <x-site.breadcrumb :items="[['label' => __('genealogy.public.hero_title')]]" />
            </div>

            <div class="text-center max-w-3xl mx-auto">
                <span class="inline-flex items-center gap-2 px-5 py-2 rounded-full mb-5
                             bg-white/70 dark:bg-white/10 backdrop-blur text-sm font-semibold
                             text-gray-700 dark:text-gray-200">
                    <i data-lucide="sparkles" class="w-4 h-4 text-primary"></i>
                    {{ __('genealogy.public.hero_badge') }}
                </span>

                <h1 class="text-4xl sm:text-5xl md:text-6xl font-black mb-4 text-gray-900 dark:text-white">
                    {{ __('genealogy.public.hero_title') }}
                    <span class="block text-gradient mt-2">{{ __('genealogy.public.hero_subtitle') }}</span>
                </h1>

                <p class="text-base sm:text-lg mb-8 text-gray-600 dark:text-gray-300 leading-relaxed">
                    {{ __('genealogy.public.hero_lead') }}
                </p>

                {{-- Stats (count up on load — countUp() no-ops to the final value instantly under reduced-motion) --}}
                <div class="grid grid-cols-3 gap-3 sm:gap-6 max-w-2xl mx-auto mb-8">
                    <div class="rounded-2xl p-4 sm:p-6 bg-white/70 dark:bg-white/5 backdrop-blur">
                        <div class="text-2xl sm:text-4xl font-black text-gradient tabular-nums" x-text="displayStats.people" x-init="countUp('people', {{ (int) $stats['people'] }})"></div>
                        <div class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 mt-1">{{ __('genealogy.public.stat_people') }}</div>
                    </div>
                    <div class="rounded-2xl p-4 sm:p-6 bg-white/70 dark:bg-white/5 backdrop-blur">
                        <div class="text-2xl sm:text-4xl font-black text-gradient tabular-nums" x-text="displayStats.children" x-init="countUp('children', {{ (int) $stats['children'] }})"></div>
                        <div class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 mt-1">{{ __('genealogy.public.stat_children') }}</div>
                    </div>
                    <div class="rounded-2xl p-4 sm:p-6 bg-white/70 dark:bg-white/5 backdrop-blur">
                        <div class="text-2xl sm:text-4xl font-black text-gradient tabular-nums" x-text="displayStats.generations" x-init="countUp('generations', {{ (int) $stats['generations'] }})"></div>
                        <div class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 mt-1">{{ __('genealogy.public.stat_generations') }}</div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3 justify-center">
                    @if ($hasTree)
                        <button type="button" @click="viewMode = 'tree'"
                                class="inline-flex items-center gap-2 px-6 sm:px-8 py-3 sm:py-4 rounded-full font-bold text-white
                                       bg-gradient-to-l from-primary to-secondary hover:shadow-2xl hover:scale-105 transition-all">
                            <i data-lucide="git-branch-plus" class="w-5 h-5"></i>
                            <span>{{ __('genealogy.public.explore_tree') }}</span>
                        </button>
                    @endif
                    <button type="button" @click="viewMode = 'list'"
                            class="inline-flex items-center gap-2 px-6 sm:px-8 py-3 sm:py-4 rounded-full font-bold transition-all
                                   bg-white dark:bg-[#16213e] text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700 hover:scale-105">
                        <i data-lucide="list" class="w-5 h-5"></i>
                        <span>{{ __('genealogy.public.view_list') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </section>

    {{-- Scopes the sticky controls bar to the tree/list section only — sticky
         positions relative to the nearest ancestor block, and without this
         wrapper that ancestor was the whole page (hero+controls+main+modals),
         so the bar never released and stayed pinned over the footer too. --}}
    <div class="relative">
    {{-- ============ CONTROLS (sticky) ============ --}}
    <section class="sticky top-16 z-30 border-y border-gray-200 dark:border-gray-800
                    bg-white/90 dark:bg-[#16213e]/90 backdrop-blur">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-3">
            <div class="flex flex-wrap items-center justify-between gap-3">

                {{-- View toggle + zoom --}}
                <div class="flex items-center gap-2">
                    @if ($hasTree)
                        <div class="flex items-center gap-1 p-1 rounded-lg bg-gray-100 dark:bg-[#0f3460]">
                            <button type="button" @click="viewMode = 'tree'"
                                    :class="viewMode === 'tree' ? 'bg-gradient-to-l from-primary to-secondary text-white shadow' : 'text-gray-500 dark:text-gray-300'"
                                    class="p-2 rounded-md transition-all" :title="'{{ __('genealogy.public.view_tree') }}'" aria-label="{{ __('genealogy.public.view_tree') }}">
                                <i data-lucide="git-branch-plus" class="w-4 h-4"></i>
                            </button>
                            <button type="button" @click="viewMode = 'list'"
                                    :class="viewMode === 'list' ? 'bg-gradient-to-l from-primary to-secondary text-white shadow' : 'text-gray-500 dark:text-gray-300'"
                                    class="p-2 rounded-md transition-all" :title="'{{ __('genealogy.public.view_list') }}'" aria-label="{{ __('genealogy.public.view_list') }}">
                                <i data-lucide="list" class="w-4 h-4"></i>
                            </button>
                        </div>

                        {{-- Zoom (tree only) --}}
                        <div x-show="viewMode === 'tree'" class="flex items-center gap-1 p-1 rounded-lg bg-gray-100 dark:bg-[#0f3460]">
                            <button type="button" @click="zoomOut()"
                                    class="p-2 rounded-md text-gray-600 dark:text-gray-200 hover:bg-white dark:hover:bg-[#16213e] transition-all"
                                    aria-label="{{ __('genealogy.public.zoom_out') }}">
                                <i data-lucide="zoom-out" class="w-4 h-4"></i>
                            </button>
                            <span class="px-2 text-sm font-semibold text-gray-900 dark:text-white tabular-nums"
                                  x-text="Math.round(zoom * 100) + '%'"></span>
                            <button type="button" @click="zoomIn()"
                                    class="p-2 rounded-md text-gray-600 dark:text-gray-200 hover:bg-white dark:hover:bg-[#16213e] transition-all"
                                    aria-label="{{ __('genealogy.public.zoom_in') }}">
                                <i data-lucide="zoom-in" class="w-4 h-4"></i>
                            </button>
                            <button type="button" @click="resetZoom()"
                                    class="p-2 rounded-md text-gray-600 dark:text-gray-200 hover:bg-white dark:hover:bg-[#16213e] transition-all"
                                    aria-label="{{ __('genealogy.public.reset_zoom') }}">
                                <i data-lucide="maximize-2" class="w-4 h-4"></i>
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

                {{-- Search --}}
                <button type="button" @click="searchOpen = true; $nextTick(() => $refs.searchInput?.focus())"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold
                               bg-gray-100 dark:bg-[#0f3460] text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-[#16213e] transition-all">
                    <i data-lucide="search" class="w-4 h-4"></i>
                    <span class="hidden sm:inline">{{ __('genealogy.public.search') }}</span>
                </button>
            </div>
        </div>
    </section>

    {{-- ============ MAIN ============ --}}
    <main class="py-10 sm:py-14 bg-[#F8F9FA] dark:bg-[#1a1a2e]">

        @if ($hasTree)
            {{-- ===== TREE VIEW ===== --}}
            <div x-show="viewMode === 'tree'" x-cloak class="container mx-auto px-4 sm:px-6 lg:px-8">
                @if (! empty($spouses))
                    <p class="text-center text-xs font-semibold uppercase tracking-wide mb-2 text-gray-500 dark:text-gray-400">
                        {{ __('genealogy.public.generations_shown', ['count' => $stats['generations']]) }}
                        · <button type="button" @click="viewMode = 'list'; activeFilter = 'wives'"
                                  class="underline hover:text-accent transition-colors">{{ __('genealogy.public.filter_wives') }}</button>
                    </p>
                @endif
                <div class="relative">
                    {{-- Scroll-hint fades + real pan buttons — the arrows actually scroll the tree now, not just decorative --}}
                    <div class="tree-scroll-fade tree-scroll-fade--start bg-gradient-to-r rtl:bg-gradient-to-l from-[#F8F9FA] dark:from-[#1a1a2e] to-transparent"
                         x-show="treeScroll.start" x-transition.opacity aria-hidden="true"></div>
                    <div class="tree-scroll-fade tree-scroll-fade--end bg-gradient-to-l rtl:bg-gradient-to-r from-[#F8F9FA] dark:from-[#1a1a2e] to-transparent"
                         x-show="treeScroll.end" x-transition.opacity aria-hidden="true"></div>

                    <button type="button" @click="panTree(-1)" x-show="treeScroll.start" x-transition.opacity
                            class="absolute top-1/2 -translate-y-1/2 start-1 z-10 active:scale-90
                                   w-9 h-9 rounded-full bg-white dark:bg-[#16213e] shadow-md hover:shadow-lg transition-all
                                   flex items-center justify-center" aria-label="{{ __('genealogy.public.scroll_start') }}">
                        <i data-lucide="chevron-left" class="w-4 h-4 text-primary rtl:rotate-180"></i>
                    </button>
                    <button type="button" @click="panTree(1)" x-show="treeScroll.end" x-transition.opacity
                            class="absolute top-1/2 -translate-y-1/2 end-1 z-10 active:scale-90
                                   w-9 h-9 rounded-full bg-white dark:bg-[#16213e] shadow-md hover:shadow-lg transition-all
                                   flex items-center justify-center" aria-label="{{ __('genealogy.public.scroll_end') }}">
                        <i data-lucide="chevron-right" class="w-4 h-4 text-primary rtl:rotate-180"></i>
                    </button>

                    {{-- overflow-x-auto here forces overflow-y's computed value to auto too, even if
                         we tried to set overflow-y: visible — that's mandatory per the CSS overflow
                         spec (you can't scroll one axis while the other stays "visible"), not
                         something a utility class can override. The tree's own content never
                         actually exceeds this box's height even when zoomed (CSS transform: scale()
                         is a paint-only effect and doesn't grow layout size/scrollHeight), so this
                         box has no real vertical overflow to trap — vertical wheel/touch scroll
                         chains straight through to the page normally. --}}
                    <div x-ref="treeScrollContainer" @scroll.passive="updateTreeScroll()"
                         class="overflow-x-auto scrollbar-hide pb-6">
                        <div class="inline-block min-w-full"
                             style="transition: transform .25s ease-out"
                             :style="`transform: scale(${zoom}); transform-origin: top center;`">

                            @if (! empty($spouses))
                                {{-- Spouses — a marriage edge, not a descent edge, so it gets a distinct
                                     dashed connector + icon rather than the solid parent→child lines below. --}}
                                <div class="flex flex-col items-center mb-2">
                                    <p class="text-xs font-bold text-accent mb-3 flex items-center gap-1.5">
                                        <i data-lucide="heart" class="w-3.5 h-3.5"></i>
                                        {{ __('genealogy.public.wives_of_prophet') }}
                                    </p>
                                    <div class="flex flex-wrap justify-center gap-4 sm:gap-6 pb-6">
                                        @foreach ($spouses as $i => $spouse)
                                            <button type="button" @click="openPerson({{ (int) $spouse['id'] }})"
                                                    style="--enter-delay: {{ $i * 60 }}ms"
                                                    class="node-card card-enter rounded-xl border border-dashed border-accent/40
                                                           bg-white dark:bg-[#16213e] shadow-md hover:shadow-xl p-3 sm:p-4 transition-all">
                                                <div class="flex flex-col items-center gap-2">
                                                    <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-full flex items-center justify-center shadow
                                                                bg-gradient-to-br from-pink-500 to-rose-500">
                                                        <i data-lucide="user" class="w-6 h-6 sm:w-7 sm:h-7 text-white"></i>
                                                    </div>
                                                    <h4 class="text-xs sm:text-sm font-bold text-center max-w-[8rem] line-clamp-2 text-gray-900 dark:text-white">
                                                        {{ $spouse['name'] }}
                                                    </h4>
                                                </div>
                                            </button>
                                        @endforeach
                                    </div>
                                    <span class="w-px h-6 bg-gradient-to-b from-accent/50 to-primary/30" aria-hidden="true"></span>
                                </div>
                            @endif

                            <div class="flex justify-center pt-2">
                                @include('public.genealogy._node', ['node' => $tree, 'depth' => 0, 'index' => 0])
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- ===== LIST VIEW (also the fallback when no tree) ===== --}}
        <div x-show="viewMode === 'list'" @if($hasTree) x-cloak @endif class="container mx-auto px-4 sm:px-6 lg:px-8">

            @if (! $hasTree && count($people))
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
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <template x-for="(person, idx) in filteredPeople" :key="person.id">
                            <article class="card-premium p-6 hover-scale active:scale-[0.98] transition-all card-enter"
                                     :style="`--enter-delay: ${Math.min(idx, 11) * 40}ms`">
                                <div class="flex items-center gap-4 mb-4">
                                    <div class="w-16 h-16 rounded-full flex items-center justify-center shadow-lg shrink-0
                                                bg-gradient-to-br from-primary to-secondary">
                                        <i :data-lucide="person.isProphet ? 'crown' : (person.gender === 'female' ? 'user' : 'user')"
                                           class="w-8 h-8 text-white"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <h3 class="text-lg font-bold text-gray-900 dark:text-white truncate" x-html="highlightMatch(person.name)"></h3>
                                        <p class="text-sm text-accent truncate"
                                           x-text="person.isProphet ? '{{ __('genealogy.public.prophet_relation') }}' : (person.title || person.typeLabel || '')"></p>
                                    </div>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-300 line-clamp-2 mb-4 min-h-[2.5rem]"
                                   x-text="person.biography || '{{ __('genealogy.public.no_biography') }}'"></p>
                                <button type="button" @click="openPerson(person.id)"
                                        class="w-full px-4 py-2.5 rounded-lg font-semibold text-white active:scale-[0.97]
                                               bg-gradient-to-l from-primary to-secondary hover:shadow-lg transition-all">
                                    {{ __('genealogy.public.view_details') }}
                                </button>
                            </article>
                        </template>
                    </div>

                    {{-- Load more — with 10k+ genealogy records, the initial batch (tree nodes + a page of recents) is a fraction of the total; this is how the rest becomes reachable --}}
                    <div class="flex flex-col items-center gap-3 mt-10" x-show="hasMorePeople && filteredPeople.length > 0">
                        <button type="button" @click="loadMore()" :disabled="loadingMore"
                                class="inline-flex items-center gap-2 px-8 py-3 rounded-full font-bold transition-all active:scale-[0.97]
                                       bg-white dark:bg-[#16213e] text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700
                                       hover:border-primary/50 hover:shadow-lg disabled:opacity-60 disabled:cursor-wait">
                            <i x-show="!loadingMore" data-lucide="chevron-down" class="w-4 h-4"></i>
                            <i x-show="loadingMore" data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
                            <span x-text="loadingMore ? '{{ __('genealogy.public.loading_more') }}' : '{{ __('genealogy.public.load_more') }}'"></span>
                        </button>
                        <p class="text-xs text-gray-500 dark:text-gray-400" x-text="peopleLoadedLabel"></p>
                    </div>

                    {{-- Empty (after filtering) --}}
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

    {{-- ============ PERSON DETAIL MODAL ============ --}}
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

                {{-- Dates + tribe — only the fields that actually have data are shown --}}
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

                {{-- Only companions have a real public profile — linking out
                     for a tribe/historical entry would 404. The Prophet ﷺ
                     falls back to the Seerah section instead. --}}
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

    {{-- ============ SEARCH MODAL ============ --}}
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
    function genealogyPage(people, childrenIds, grandchildrenIds, spouseIds) {
        return {
            people: people || [],
            childrenIds: new Set(childrenIds || []),
            grandchildrenIds: new Set(grandchildrenIds || []),
            spouseIds: new Set(spouseIds || []),
            loading: true,
            viewMode: @json($hasTree) ? 'tree' : 'list',
            zoom: 1,
            activeFilter: 'all',
            searchQuery: '',
            searchOpen: false,
            searchLoading: false,
            selectedPerson: null,
            seerahRoute: '{{ route('public.seerah') }}',
            sahabaBaseUrl: '{{ url('/sahaba') }}',
            peopleEndpoint: '{{ route('public.genealogy.people') }}',

            // Pagination — the embedded `people` array is only the tree
            // nodes plus one page of "latest" records, a fraction of
            // peopleTotal (10k+). loadMore()/the debounced search fetch
            // are what make the rest of the family actually reachable.
            peopleTotal: {{ (int) $stats['people'] }},
            page: 2,
            hasMorePeople: (people || []).length < {{ (int) $stats['people'] }},
            loadingMore: false,
            lastFetchedQuery: '',
            searchDebounceTimer: null,

            displayStats: { people: 0, children: 0, generations: 0 },
            treeScroll: { start: false, end: false },

            filters: [
                { value: 'all',           label: '{{ __('genealogy.public.filter_all') }}' },
                { value: 'children',      label: '{{ __('genealogy.public.filter_children') }}' },
                { value: 'grandchildren', label: '{{ __('genealogy.public.filter_grandchildren') }}' },
                { value: 'wives',         label: '{{ __('genealogy.public.filter_wives') }}' },
            ],

            init() {
                this.loading = false;
                this.$nextTick(() => {
                    window.lucide?.createIcons();
                    this.updateTreeScroll();
                });
                this.$watch('viewMode', () => this.$nextTick(() => {
                    window.lucide?.createIcons();
                    this.updateTreeScroll();
                }));
                this.$watch('selectedPerson', () => this.$nextTick(() => window.lucide?.createIcons()));
                this.$watch('activeFilter',   () => this.$nextTick(() => window.lucide?.createIcons()));
                this.$watch('searchQuery', () => {
                    this.$nextTick(() => window.lucide?.createIcons());
                    this.debouncedSearch();
                });
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

            matchesFilter(person) {
                if (this.activeFilter === 'all') return true;
                // Backed by the real `relationships` table (relationship_type=spouse) via
                // spouseIds — not a gender/type guess, which used to also catch daughters etc.
                if (this.activeFilter === 'wives') {
                    return this.spouseIds.has(person.id);
                }
                if (this.activeFilter === 'children') {
                    return this.childrenIds.has(person.id);
                }
                if (this.activeFilter === 'grandchildren') {
                    return this.grandchildrenIds.has(person.id);
                }
                return true;
            },

            get filteredPeople() {
                const q = this.searchQuery.trim().toLowerCase();
                return this.people.filter((p) => {
                    if (!this.matchesFilter(p)) return false;
                    if (!q) return true;
                    return (p.name || '').toLowerCase().includes(q)
                        || (p.title || '').toLowerCase().includes(q)
                        || (p.tribe || '').toLowerCase().includes(q);
                });
            },

            get searchResults() {
                const q = this.searchQuery.trim().toLowerCase();
                if (!q) return this.people.slice(0, 8);
                return this.people.filter((p) =>
                    (p.name || '').toLowerCase().includes(q) || (p.title || '').toLowerCase().includes(q)
                ).slice(0, 12);
            },

            get peopleLoadedLabel() {
                return `${this.people.length} / ${this.peopleTotal}`;
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

            async fetchPeople({ q = '', page = 1 } = {}) {
                const url = new URL(this.peopleEndpoint, window.location.origin);
                if (q) url.searchParams.set('q', q);
                url.searchParams.set('page', page);
                const response = await fetch(url, { headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error('genealogy people fetch failed');
                return response.json();
            },

            async loadMore() {
                if (this.loadingMore || !this.hasMorePeople) return;
                this.loadingMore = true;
                try {
                    const result = await this.fetchPeople({ q: this.searchQuery.trim(), page: this.page });
                    this.mergePeople(result.data);
                    this.hasMorePeople = !!result.hasMore;
                    this.page = result.nextPage || this.page + 1;
                } catch (e) {
                    // Network hiccup — leave hasMorePeople as-is so the user can just retry the button.
                } finally {
                    this.loadingMore = false;
                }
            },

            debouncedSearch() {
                clearTimeout(this.searchDebounceTimer);
                const q = this.searchQuery.trim();
                if (!q || q === this.lastFetchedQuery) return;
                this.searchDebounceTimer = setTimeout(() => this.runServerSearch(q), 300);
            },

            async runServerSearch(q) {
                this.searchLoading = true;
                try {
                    const result = await this.fetchPeople({ q, page: 1 });
                    this.mergePeople(result.data);
                    this.lastFetchedQuery = q;
                    // A fresh query gets its own pagination cursor for loadMore().
                    this.page = result.nextPage || 2;
                    this.hasMorePeople = !!result.hasMore;
                } catch (e) {
                    // Client-side filtering of what's already loaded still works even if this fails.
                } finally {
                    this.searchLoading = false;
                }
            },

            updateTreeScroll() {
                const el = this.$refs.treeScrollContainer;
                if (!el) { this.treeScroll.start = false; this.treeScroll.end = false; return; }
                const max = el.scrollWidth - el.clientWidth;
                if (max <= 4) { this.treeScroll.start = false; this.treeScroll.end = false; return; }
                const scrolled = Math.abs(el.scrollLeft);
                this.treeScroll.start = scrolled > 4;
                this.treeScroll.end = scrolled < max - 4;
            },

            // direction: -1 = toward the start, +1 = toward the end. The
            // scroll-hint chevrons call this — they used to be purely
            // decorative (pointer-events-none) and didn't actually scroll
            // anything when clicked.
            panTree(direction) {
                const el = this.$refs.treeScrollContainer;
                if (!el) return;
                const amount = Math.round(el.clientWidth * 0.6);
                const isRtl = getComputedStyle(el).direction === 'rtl';
                // LTR: scrollLeft increases toward the end. The RTL convention
                // modern browsers use is the mirror: scrollLeft is <= 0 and
                // decreases toward the end — same abs()-based normalization
                // updateTreeScroll() uses, just inverted for scrollBy's sign.
                const delta = isRtl ? -direction * amount : direction * amount;
                // behavior: 'smooth' silently no-ops in some environments —
                // an instant jump that reliably works beats an animated pan
                // that sometimes does nothing at all.
                el.scrollBy({ left: delta, behavior: 'auto' });
            },

            zoomIn()    { if (this.zoom < 1.5) this.zoom = Math.round((this.zoom + 0.1) * 10) / 10; this.$nextTick(() => setTimeout(() => this.updateTreeScroll(), 260)); },
            zoomOut()   { if (this.zoom > 0.5) this.zoom = Math.round((this.zoom - 0.1) * 10) / 10; this.$nextTick(() => setTimeout(() => this.updateTreeScroll(), 260)); },
            resetZoom() { this.zoom = 1; this.$nextTick(() => setTimeout(() => this.updateTreeScroll(), 260)); },
        };
    }
</script>
@endpush
@endsection
