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
    @media (prefers-reduced-motion: reduce) { .genealogy-glow { animation: none; } }
    .node-card { transition: transform .3s cubic-bezier(.4,0,.2,1), box-shadow .3s; }
    .node-card:hover { transform: translateY(-6px) scale(1.03); }
</style>
@endpush

@section('content')
@php
    $peopleJson = json_encode($people, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS);
    $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true);
@endphp

<div
    x-data="genealogyPage({{ $peopleJson }})"
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

                {{-- Stats --}}
                <div class="grid grid-cols-3 gap-3 sm:gap-6 max-w-2xl mx-auto mb-8">
                    <div class="rounded-2xl p-4 sm:p-6 bg-white/70 dark:bg-white/5 backdrop-blur">
                        <div class="text-2xl sm:text-4xl font-black text-gradient">{{ $stats['people'] }}</div>
                        <div class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 mt-1">{{ __('genealogy.public.stat_people') }}</div>
                    </div>
                    <div class="rounded-2xl p-4 sm:p-6 bg-white/70 dark:bg-white/5 backdrop-blur">
                        <div class="text-2xl sm:text-4xl font-black text-gradient">{{ $stats['children'] }}</div>
                        <div class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 mt-1">{{ __('genealogy.public.stat_children') }}</div>
                    </div>
                    <div class="rounded-2xl p-4 sm:p-6 bg-white/70 dark:bg-white/5 backdrop-blur">
                        <div class="text-2xl sm:text-4xl font-black text-gradient">{{ $stats['generations'] }}</div>
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
                <div class="overflow-x-auto scrollbar-hide pb-6">
                    <div class="inline-block min-w-full"
                         :style="`transform: scale(${zoom}); transform-origin: top center;`">
                        <div class="flex justify-center pt-2">
                            @include('public.genealogy._node', ['node' => $tree, 'depth' => 0, 'index' => 0])
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
                            <article class="card-premium p-6 hover-scale transition-all">
                                <div class="flex items-center gap-4 mb-4">
                                    <div class="w-16 h-16 rounded-full flex items-center justify-center shadow-lg shrink-0
                                                bg-gradient-to-br from-primary to-secondary">
                                        <i :data-lucide="person.isProphet ? 'crown' : (person.gender === 'female' ? 'user' : 'user')"
                                           class="w-8 h-8 text-white"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <h3 class="text-lg font-bold text-gray-900 dark:text-white truncate" x-text="person.name"></h3>
                                        <p class="text-sm text-accent truncate"
                                           x-text="person.isProphet ? '{{ __('genealogy.public.prophet_relation') }}' : (person.title || person.typeLabel || '')"></p>
                                    </div>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-300 line-clamp-2 mb-4 min-h-[2.5rem]"
                                   x-text="person.biography || '{{ __('genealogy.public.no_biography') }}'"></p>
                                <button type="button" @click="openPerson(person.id)"
                                        class="w-full px-4 py-2.5 rounded-lg font-semibold text-white
                                               bg-gradient-to-l from-primary to-secondary hover:shadow-lg transition-all">
                                    {{ __('genealogy.public.view_details') }}
                                </button>
                            </article>
                        </template>
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

    {{-- ============ PERSON DETAIL MODAL ============ --}}
    <div x-show="selectedPerson" x-cloak
         x-transition.opacity
         @keydown.escape.window="selectedPerson = null"
         @click.self="selectedPerson = null"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
        <div class="w-full max-w-2xl max-h-[85vh] overflow-y-auto rounded-3xl bg-white dark:bg-[#16213e] shadow-2xl"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0">

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

                <div>
                    <h4 class="flex items-center gap-2 text-lg font-bold mb-2 text-gray-900 dark:text-white">
                        <i data-lucide="book-open" class="w-5 h-5 text-primary"></i>
                        {{ __('genealogy.public.virtues') }}
                    </h4>
                    <ul class="space-y-2">
                        <li class="flex items-start gap-3">
                            <i data-lucide="check-circle" class="w-4 h-4 mt-1 text-secondary shrink-0"></i>
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('genealogy.public.virtue_ahl_bayt') }}</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i data-lucide="check-circle" class="w-4 h-4 mt-1 text-secondary shrink-0"></i>
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('genealogy.public.virtue_beloved') }}</span>
                        </li>
                    </ul>
                </div>

                <template x-if="selectedPerson?.tribe">
                    <div class="flex items-center gap-2">
                        <x-site.badge variant="accent" icon="users">
                            <span x-text="selectedPerson?.tribe"></span>
                        </x-site.badge>
                    </div>
                </template>

                <a :href="seerahUrl"
                   class="inline-flex items-center justify-center gap-2 w-full px-6 py-3 rounded-xl font-bold text-white
                          bg-gradient-to-l from-primary to-secondary hover:shadow-lg transition-all">
                    <i data-lucide="book-open" class="w-5 h-5"></i>
                    {{ __('genealogy.public.read_full') }}
                </a>
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
                    <i data-lucide="search" class="absolute start-5 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>
                    <button type="button" @click="searchOpen = false"
                            class="absolute end-4 top-1/2 -translate-y-1/2 p-1.5 rounded-lg text-gray-400 hover:bg-gray-100 dark:hover:bg-[#0f3460] transition-all"
                            aria-label="{{ __('genealogy.public.close') }}">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">{{ __('genealogy.public.search_hint') }}</p>
            </div>

            <div class="border-t border-gray-200 dark:border-gray-800 max-h-[50vh] overflow-y-auto">
                <template x-for="person in searchResults" :key="'s-' + person.id">
                    <button type="button" @click="searchOpen = false; openPerson(person.id)"
                            class="w-full flex items-center gap-3 px-5 sm:px-6 py-3 text-start
                                   hover:bg-gray-100 dark:hover:bg-[#0f3460] transition-all">
                        <i :data-lucide="person.isProphet ? 'crown' : 'user'" class="w-5 h-5 text-gray-400 shrink-0"></i>
                        <span class="min-w-0">
                            <span class="block font-semibold text-gray-900 dark:text-white truncate" x-text="person.name"></span>
                            <span class="block text-xs text-gray-500 dark:text-gray-400 truncate"
                                  x-text="person.title || person.typeLabel || ''"></span>
                        </span>
                    </button>
                </template>
                <div x-show="searchQuery.length > 0 && searchResults.length === 0"
                     class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                    {{ __('site.no_results') }}
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function genealogyPage(people) {
        return {
            people: people || [],
            loading: true,
            viewMode: @json($hasTree) ? 'tree' : 'list',
            zoom: 1,
            activeFilter: 'all',
            searchQuery: '',
            searchOpen: false,
            selectedPerson: null,
            seerahRoute: '{{ route('public.seerah') }}',

            filters: [
                { value: 'all',           label: '{{ __('genealogy.public.filter_all') }}' },
                { value: 'children',      label: '{{ __('genealogy.public.filter_children') }}' },
                { value: 'grandchildren', label: '{{ __('genealogy.public.filter_grandchildren') }}' },
                { value: 'wives',         label: '{{ __('genealogy.public.filter_wives') }}' },
            ],

            init() {
                this.loading = false;
                this.$nextTick(() => window.lucide?.createIcons());
                this.$watch('viewMode',       () => this.$nextTick(() => window.lucide?.createIcons()));
                this.$watch('selectedPerson', () => this.$nextTick(() => window.lucide?.createIcons()));
                this.$watch('searchQuery',    () => this.$nextTick(() => window.lucide?.createIcons()));
                this.$watch('activeFilter',   () => this.$nextTick(() => window.lucide?.createIcons()));
            },

            matchesFilter(person) {
                if (this.activeFilter === 'all') return true;
                if (this.activeFilter === 'wives') {
                    return person.type === 'family' && person.gender === 'female';
                }
                // children / grandchildren are tree-relative; in the flat list we show all.
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

            get seerahUrl() {
                return this.seerahRoute;
            },

            openPerson(id) {
                this.selectedPerson = this.people.find((p) => p.id === id) || null;
            },

            zoomIn()    { if (this.zoom < 1.5) this.zoom = Math.round((this.zoom + 0.1) * 10) / 10; },
            zoomOut()   { if (this.zoom > 0.5) this.zoom = Math.round((this.zoom - 0.1) * 10) / 10; },
            resetZoom() { this.zoom = 1; },
        };
    }
</script>
@endpush
@endsection
