@extends('layouts.public')

@section('title', __('quran.public.reciters_page_title'))
@section('meta_description', __('quran.public.reciters_page_description'))

@section('content')
@php
    $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true);
    $config = ['reciters' => $reciters];
@endphp

<div class="sx min-h-[100dvh] pb-20" x-data="recitersDirectory(@js($config))">
    <div class="sx-wrap">

        <x-public.editorial.page-hero
            :eyebrow-url="route('public.quran')" :eyebrow-label="__('site.quran')" eyebrow-icon="book-open"
            :title="__('quran.public.reciters_hero_title')" :lead="__('quran.public.reciters_hero_subtitle')"
            :stats="[
                ['icon' => 'mic', 'label' => __('quran.public.reciter_unit'), 'value' => number_format($totalReciters)],
                ['icon' => 'star', 'label' => __('quran.public.featured_reciters'), 'value' => count($featuredReciters)],
            ]">
            <div class="flex flex-wrap items-center gap-2">
                <span class="sx-chip"><i data-lucide="audio-lines" aria-hidden="true"></i>{{ __('quran.public.high_quality_recitations') }}</span>
                <a href="{{ route('public.quran.playlists.index') }}" class="sx-chip">
                    <i data-lucide="list-music" aria-hidden="true"></i>{{ __('quran.playlists.title') }}
                </a>
            </div>
        </x-public.editorial.page-hero>

        @if (count($featuredReciters) > 0)
            <x-public.editorial.section id="featured" icon="star" :title="__('quran.public.featured_reciters')" class="mb-10">
                <div class="sx-grid is-wide">
                    @foreach ($featuredReciters as $reciter)
                        <x-public.quran.reciter-card :reciter="$reciter" :eager="true" />
                    @endforeach
                </div>
            </x-public.editorial.section>
        @endif

        {{-- Search, style filter, sort, page size and the view switch. --}}
        <div class="sx-surface sx-filters">
            <div class="sx-filter-row">
                <div class="relative">
                    <label for="reciters-search" class="sr-only">{{ __('quran.public.reciters_search_placeholder') }}</label>
                    <i data-lucide="search" class="pointer-events-none absolute start-4 top-1/2 h-5 w-5 -translate-y-1/2 text-[color:var(--sx-muted)]" aria-hidden="true"></i>
                    <input id="reciters-search" type="search" x-model.debounce.250ms="searchQuery" @input="currentPage = 1"
                           autocomplete="off" enterkeyhint="search"
                           placeholder="{{ __('quran.public.reciters_search_placeholder') }}"
                           aria-label="{{ __('quran.public.reciters_search_placeholder') }}"
                           class="sx-input ps-12">
                </div>

                <div>
                    <label for="reciters-sort" class="sr-only">{{ __('quran.public.sort_label') }}</label>
                    <select id="reciters-sort" x-model="sortBy" @change="currentPage = 1" class="sx-input">
                        <option value="popular">{{ __('quran.public.sort_popular') }}</option>
                        <option value="name">{{ __('quran.public.sort_name') }}</option>
                    </select>
                </div>

                <div>
                    <label for="reciters-per-page" class="sr-only">{{ __('site.showing') }}</label>
                    <select id="reciters-per-page" x-model.number="perPage" @change="setPerPage(perPage)" class="sx-input w-auto text-sm">
                        <option :value="10">10</option>
                        <option :value="20">20</option>
                        <option :value="40">40</option>
                        <option :value="reciters.length">{{ __('site.all') }}</option>
                    </select>
                </div>

                <div class="sx-segment" role="group" aria-label="{{ __('quran.public.view_grid') }}">
                    <button type="button" @click="setViewMode('grid')" :aria-pressed="(viewMode === 'grid').toString()"
                            aria-label="{{ __('quran.public.view_grid') }}" title="{{ __('quran.public.view_grid') }}">
                        <i data-lucide="layout-grid" aria-hidden="true"></i>{{ __('quran.public.view_grid') }}
                    </button>
                    <button type="button" @click="setViewMode('list')" :aria-pressed="(viewMode === 'list').toString()"
                            aria-label="{{ __('quran.public.view_list') }}" title="{{ __('quran.public.view_list') }}">
                        <i data-lucide="list" aria-hidden="true"></i>{{ __('quran.public.view_list') }}
                    </button>
                </div>
            </div>

            <div class="sx-scroller border-t border-[color:var(--sx-line)] pt-3">
                <button type="button" @click="filterStyle = 'all'; currentPage = 1" class="sx-chip shrink-0"
                        :class="filterStyle === 'all' && 'is-active'" :aria-pressed="(filterStyle === 'all').toString()">
                    {{ __('quran.public.filter_all') }}<span class="sx-count" x-text="reciters.length"></span>
                </button>
                <button type="button" @click="filterStyle = 'hafs'; currentPage = 1" class="sx-chip shrink-0"
                        :class="filterStyle === 'hafs' && 'is-active'" :aria-pressed="(filterStyle === 'hafs').toString()">
                    <i data-lucide="mic" aria-hidden="true"></i>{{ __('quran.reciters.hafs') }}
                </button>
            </div>
        </div>

        <div class="mt-8 mb-5 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-[color:var(--sx-muted)]" aria-live="polite">
                {{ __('site.showing') }}
                <strong class="tabular-nums text-[color:var(--sx-ink)]" x-text="paginatedReciters.length"></strong>
                {{ __('site.of') }}
                <strong class="tabular-nums text-[color:var(--sx-ink)]" x-text="filteredReciters.length"></strong>
                {{ __('quran.public.reciter_unit') }}
            </p>
            <p class="text-sm text-[color:var(--sx-muted)] tabular-nums">
                {{ __('quran.public.page') }} <strong class="text-[color:var(--sx-ink)]" x-text="currentPage"></strong>
                {{ __('site.of') }} <span x-text="totalFilteredPages"></span>
            </p>
        </div>

        {{-- Cards are server-rendered; Alpine only shows the current page and orders it. --}}
        <div class="sx-grid" :class="viewMode === 'list' && '!grid-cols-1'">
            @foreach ($reciters as $reciter)
                <x-public.quran.reciter-card :reciter="$reciter" :eager="$loop->index < 4"
                    x-show="onPage({{ (int) $reciter['id'] }})"
                    ::style="'order:' + orderRank({{ (int) $reciter['id'] }})"
                    ::class="viewMode === 'list' && 'is-row'" />
            @endforeach
        </div>

        <div x-show="filteredReciters.length === 0" x-cloak class="grid justify-items-center gap-4">
            <x-public.editorial.empty-state class="w-full" icon="search-x" :title="__('site.no_results')" :hint="__('site.try_adjusting_filters')" />
            <button type="button" @click="searchQuery = ''; filterStyle = 'all'" class="sx-btn is-primary">
                <i data-lucide="rotate-ccw" aria-hidden="true"></i>{{ __('site.reset_filters') }}
            </button>
        </div>

        <nav x-show="filteredReciters.length > 0" class="sx-pager mt-12" aria-label="{{ __('quran.public.page') }}">
            <button type="button" @click="goToPage(currentPage - 1)" :disabled="currentPage === 1" class="sx-btn disabled:opacity-40 disabled:cursor-not-allowed">
                <i data-lucide="chevron-{{ $isRtl ? 'right' : 'left' }}" aria-hidden="true"></i>{{ __('quran.public.prev_page') }}
            </button>
            <div class="sx-pages">
                <template x-for="p in pageNumbers" :key="p">
                    <button type="button" @click="goToPage(p)" class="sx-page" :aria-current="currentPage === p ? 'page' : null" x-text="p"></button>
                </template>
            </div>
            <button type="button" @click="goToPage(currentPage + 1)" :disabled="currentPage === totalFilteredPages" class="sx-btn disabled:opacity-40 disabled:cursor-not-allowed">
                {{ __('quran.public.next_page') }}<i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" aria-hidden="true"></i>
            </button>
        </nav>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function recitersDirectory(config) {
        return {
            reciters: config.reciters || [],
            searchQuery: '',
            filterStyle: 'all',
            sortBy: 'popular',
            currentPage: 1,
            viewMode: (() => { try { return localStorage.getItem('recitersViewMode') || 'grid'; } catch (e) { return 'grid'; } })(),
            perPage: (() => { try { return parseInt(localStorage.getItem('recitersPerPage')) || 20; } catch (e) { return 20; } })(),

            init() {
                this.$watch('paginatedReciters', () => this.refreshIcons());
                this.$watch('viewMode', () => this.refreshIcons());
            },

            refreshIcons() { this.$nextTick(() => window.lucide?.createIcons()); },

            get filteredReciters() {
                let r = this.reciters;
                if (this.filterStyle !== 'all') {
                    r = r.filter(x => (x.styles || [x.style]).includes(this.filterStyle));
                }
                if (this.searchQuery) {
                    const q = this.searchQuery.toLowerCase();
                    r = r.filter(x =>
                        (x.nameAr && x.nameAr.includes(this.searchQuery)) ||
                        (x.nameEn && x.nameEn.toLowerCase().includes(q)) ||
                        (x.country && x.country.toLowerCase().includes(q)) ||
                        (x.aliases && x.aliases.toLowerCase().includes(q)) ||
                        (x.countryName && x.countryName.includes(this.searchQuery))
                    );
                }
                if (this.sortBy === 'popular') {
                    r = [...r].sort((a, b) => (b.followersCount || 0) - (a.followersCount || 0));
                } else if (this.sortBy === 'name') {
                    r = [...r].sort((a, b) => (a.nameAr || '').localeCompare(b.nameAr || '', 'ar'));
                }
                return r;
            },

            get totalFilteredPages() {
                return Math.max(1, Math.ceil(this.filteredReciters.length / this.perPage));
            },

            get paginatedReciters() {
                const s = (this.currentPage - 1) * this.perPage;
                return this.filteredReciters.slice(s, s + this.perPage);
            },

            get pageNumbers() {
                const tp = this.totalFilteredPages;
                const cp = this.currentPage;
                const maxV = 5;
                let start = Math.max(1, cp - Math.floor(maxV / 2));
                let end = Math.min(tp, start + maxV - 1);
                if (end - start < maxV - 1) { start = Math.max(1, end - maxV + 1); }
                const pages = [];
                for (let i = start; i <= end; i++) { pages.push(i); }
                return pages;
            },

            // The cards sit in display order in the DOM: filtering hides, sorting and paging reorder.
            get pageOrder() {
                const order = {};
                this.paginatedReciters.forEach((r, i) => { order[r.id] = i; });
                return order;
            },
            onPage(id) { return this.pageOrder[id] !== undefined; },
            orderRank(id) { return this.pageOrder[id] ?? 0; },

            setViewMode(m) { this.viewMode = m; try { localStorage.setItem('recitersViewMode', m); } catch (e) {} },

            setPerPage(v) {
                this.perPage = v;
                this.currentPage = 1;
                try { localStorage.setItem('recitersPerPage', v); } catch (e) {}
            },

            goToPage(p) {
                if (p < 1 || p > this.totalFilteredPages) { return; }
                this.currentPage = p;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },
        };
    }
</script>
@endpush
