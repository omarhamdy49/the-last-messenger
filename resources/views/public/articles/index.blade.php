@extends('layouts.public')

@section('title', __('articles.public.meta_title'))
@section('meta_description', __('articles.public.page_description'))

@push('structured_data')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "CollectionPage",
    "name": "{{ __('articles.public.page_title') }}",
    "description": "{{ __('articles.public.page_description') }}",
    "url": "{{ route('public.articles') }}",
    "inLanguage": "{{ app()->getLocale() }}"
}
</script>
@endpush

@php
    $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true);
    // Featured studies share one spotlight (max 5); with none promoted, the newest leads.
    $featured = collect($articles)->where('isFeatured', true)->values();
    $spotlights = ($featured->isNotEmpty() ? $featured->take(5) : collect([$articles[0] ?? null])->filter())->values();
    $spotlightIds = $spotlights->pluck('id')->all();
    // One article has nothing to search, filter, sort or page through.
    $hasLibrary = count($articles) > 1;
    $config = ['articles' => $articles, 'categories' => $categories, 'tags' => $tags, 'spotlightIds' => $spotlightIds, 'perPage' => 9];
@endphp

@section('content')
<div class="sx min-h-[100dvh] pb-20" x-data="articlesLibrary(@js($config))">
    <div class="sx-wrap">
        <x-public.editorial.page-hero
            :eyebrow-label="__('articles.public.breadcrumb')" eyebrow-icon="newspaper"
            :title="__('articles.public.hero_title')" :lead="__('articles.public.hero_subtitle')"
            :stats="$hasLibrary ? [
                ['icon' => 'book-open', 'label' => __('articles.public.article_unit'), 'value' => number_format(count($articles))],
                ['icon' => 'layers', 'label' => __('articles.public.categories'), 'value' => count($categories)],
                ['icon' => 'tags', 'label' => __('articles.public.tags'), 'value' => count($tags)],
            ] : []" />

        @if (empty($articles))
            <x-public.editorial.empty-state icon="library" :title="__('articles.public.empty_title')" :hint="__('articles.public.empty_message')" />
        @else
            {{-- Spotlight: steps aside while the reader filters or searches. --}}
            <section x-show="!hasFilters" class="relative mb-8" aria-roledescription="carousel" aria-label="{{ __('articles.public.featured') }}">
                @foreach ($spotlights as $i => $item)
                    <a href="{{ $item['url'] }}" title="{{ $item['title'] }}" class="sx-surface sx-spotlight group"
                       x-show="spotIndex === {{ $i }}" @if ($i > 0) x-cloak @endif
                       role="group" aria-roledescription="slide" aria-label="{{ $i + 1 }} / {{ $spotlights->count() }}">
                        <div class="sx-spotlight-media">
                            <img src="{{ $item['image'] }}" alt="" width="1280" height="720" @if ($i === 0) fetchpriority="high" @else loading="lazy" @endif decoding="async" onerror="this.remove()"
                                 class="transition-transform duration-700 group-hover:scale-[1.03]">
                        </div>
                        <div class="sx-spotlight-body">
                            <div class="flex flex-wrap items-center gap-2">
                                @if (! empty($item['isFeatured']))
                                    <span class="sx-chip is-active"><i data-lucide="star" aria-hidden="true"></i>{{ __('articles.public.featured') }}</span>
                                @endif
                                @if (! empty($item['category']))
                                    <span class="sx-chip sx-clamp-1 max-w-[14rem]">{{ $item['category'] }}</span>
                                @endif
                            </div>
                            <h2 class="sx-section-title sx-clamp-2 group-hover:text-[color:var(--sx-accent)] transition-colors">{{ $item['title'] }}</h2>
                            @if (! empty($item['excerpt']))
                                <p class="sx-clamp-3 leading-loose text-[color:var(--sx-ink-2)]">{{ $item['excerpt'] }}</p>
                            @endif
                            <div class="sx-card-meta">
                                @if (! empty($item['author']))<span><i data-lucide="pen-line" aria-hidden="true"></i><span class="sx-clamp-1">{{ $item['author'] }}</span></span>@endif
                                <span><i data-lucide="calendar" aria-hidden="true"></i>{{ $item['hijriDate'] ?: $item['dateLabel'] }}</span>
                                <span><i data-lucide="clock" aria-hidden="true"></i>{{ $item['readTime'] }} {{ __('articles.public.min_read') }}</span>
                            </div>
                            <span class="sx-btn is-primary justify-self-start">{{ __('articles.public.read_more') }}<i data-lucide="arrow-{{ $isRtl ? 'left' : 'right' }}" aria-hidden="true"></i></span>
                        </div>
                    </a>
                @endforeach
                @if ($spotlights->count() > 1)
                    <div class="mt-3 flex items-center justify-center gap-2" x-cloak>
                        <button type="button" @click="prevSpot()" class="sx-page" aria-label="{{ __('articles.public.previous') }}"><i data-lucide="chevron-{{ $isRtl ? 'right' : 'left' }}" class="w-5 h-5" aria-hidden="true"></i></button>
                        @foreach ($spotlights as $i => $item)
                            <button type="button" @click="spotIndex = {{ $i }}" class="h-2.5 rounded-full transition-all"
                                    :class="spotIndex === {{ $i }} ? 'w-8 bg-[color:var(--sx-accent)]' : 'w-2.5 bg-[color:var(--sx-line-strong)]'"
                                    :aria-current="(spotIndex === {{ $i }}).toString()" aria-label="{{ $item['title'] }}"></button>
                        @endforeach
                        <button type="button" @click="nextSpot()" class="sx-page" aria-label="{{ __('articles.public.next') }}"><i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" class="w-5 h-5" aria-hidden="true"></i></button>
                    </div>
                @endif
            </section>

            @if ($hasLibrary)
                <section class="grid gap-4" aria-label="{{ __('articles.public.search_placeholder') }}">
                    <div class="sx-surface sx-filters">
                        <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_12rem_auto] md:items-center">
                            <div class="relative">
                                <label for="lib-q" class="sr-only">{{ __('articles.public.search_placeholder') }}</label>
                                <i data-lucide="search" class="pointer-events-none absolute start-4 top-1/2 h-5 w-5 -translate-y-1/2 text-[color:var(--sx-muted)]" aria-hidden="true"></i>
                                <input id="lib-q" type="search" x-model.debounce.250ms="search" @input="page = 1" placeholder="{{ __('articles.public.search_placeholder') }}" class="sx-input ps-12" enterkeyhint="search">
                            </div>
                            <div>
                                <label for="lib-sort" class="sr-only">{{ __('articles.public.sort_label') }}</label>
                                <select id="lib-sort" x-model="sort" @change="page = 1" class="sx-input">
                                    <option value="latest">{{ __('articles.public.sort_latest') }}</option>
                                    <option value="oldest">{{ __('articles.public.sort_oldest') }}</option>
                                    <option value="popular">{{ __('articles.public.sort_popular') }}</option>
                                    <option value="title">{{ __('articles.public.sort_title') }}</option>
                                </select>
                            </div>
                            <button type="button" x-show="tags.length > 0" class="sx-btn" @click="showTags = !showTags" :aria-expanded="showTags.toString()" aria-controls="lib-tags"
                                    :class="(showTags || activeTags.length) && '!border-[color:var(--sx-accent)] !text-[color:var(--sx-accent)]'">
                                <i data-lucide="tags" aria-hidden="true"></i>{{ __('articles.public.tags') }}
                                <span x-show="activeTags.length" x-cloak class="tabular-nums" x-text="'(' + activeTags.length + ')'"></span>
                            </button>
                        </div>
                        <div id="lib-tags" x-show="showTags" x-collapse x-cloak>
                            <div class="flex flex-wrap gap-2 border-t border-[color:var(--sx-line)] pt-3">
                                <template x-for="tag in tags" :key="tag.name">
                                    <button type="button" @click="toggleTag(tag.name)" class="sx-chip" :class="activeTags.includes(tag.name) && 'is-active'" :aria-pressed="activeTags.includes(tag.name).toString()">
                                        <span class="sx-clamp-1 max-w-[12rem]" x-text="tag.name" :title="tag.name"></span><span class="sx-count" x-text="tag.count"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-2 overflow-x-auto pb-1" role="group" aria-label="{{ __('articles.public.categories') }}">
                        <button type="button" @click="setCategory(null)" class="sx-chip shrink-0" :class="activeCategory === null && 'is-active'">
                            {{ __('articles.public.all_categories') }}<span class="sx-count" x-text="articles.length"></span>
                        </button>
                        <template x-for="cat in categories" :key="cat.id">
                            <button type="button" @click="setCategory(cat.id)" class="sx-chip shrink-0" :class="activeCategory === cat.id && 'is-active'">
                                <span class="sx-clamp-1 max-w-[14rem]" x-text="cat.name" :title="cat.name"></span><span class="sx-count" x-text="cat.count"></span>
                            </button>
                        </template>
                    </div>
                </section>

                <div class="mt-8 mb-5 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm text-[color:var(--sx-muted)]" aria-live="polite">
                        {{ __('site.showing') }} <strong class="tabular-nums text-[color:var(--sx-ink)]" x-text="filtered.length"></strong>
                        {{ __('site.of') }} <span class="tabular-nums" x-text="articles.length"></span> {{ __('articles.public.article_unit') }}
                    </p>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" x-show="hasFilters" x-cloak @click="clearFilters()" class="sx-chip is-danger"><i data-lucide="x" aria-hidden="true"></i>{{ __('articles.public.clear_filters') }}</button>
                        <div class="sx-segment" role="group" aria-label="{{ __('articles.public.view_grid') }}">
                            <button type="button" @click="setView('grid')" :aria-pressed="(view === 'grid').toString()"><i data-lucide="layout-grid" aria-hidden="true"></i>{{ __('articles.public.view_grid') }}</button>
                            <button type="button" @click="setView('list')" :aria-pressed="(view === 'list').toString()"><i data-lucide="list" aria-hidden="true"></i>{{ __('articles.public.view_list') }}</button>
                        </div>
                    </div>
                </div>

                {{-- Cards are server-rendered; Alpine only shows the current filtered page and orders it. --}}
                <div class="sx-grid" :class="view === 'list' && '!grid-cols-1'">
                    @foreach ($articles as $article)
                        <x-public.articles.card :article="$article" :eager="$loop->index < 3"
                            x-show="onPage({{ (int) $article['id'] }})" ::style="'order:' + orderRank({{ (int) $article['id'] }})" ::class="view === 'list' && 'is-row'" />
                    @endforeach
                </div>

                <div x-show="filtered.length === 0" x-cloak>
                    <x-public.editorial.empty-state :title="__('articles.public.empty_title')" :hint="__('articles.public.empty_message')" />
                </div>

                <nav x-show="totalPages > 1" x-cloak class="sx-pager mt-12" aria-label="{{ __('articles.public.pagination') }}">
                    <p class="text-sm text-[color:var(--sx-muted)] tabular-nums"><strong class="text-[color:var(--sx-ink)]" x-text="page"></strong> / <span x-text="totalPages"></span></p>
                    <div class="sx-pages">
                        <button type="button" class="sx-page" @click="goTo(page - 1)" :disabled="page === 1" aria-label="{{ __('articles.public.previous') }}"><i data-lucide="chevron-{{ $isRtl ? 'right' : 'left' }}" class="w-5 h-5" aria-hidden="true"></i></button>
                        <template x-for="n in pageNumbers" :key="n">
                            <button type="button" class="sx-page" @click="goTo(n)" :aria-current="n === page ? 'page' : null" x-text="n"></button>
                        </template>
                        <button type="button" class="sx-page" @click="goTo(page + 1)" :disabled="page === totalPages" aria-label="{{ __('articles.public.next') }}"><i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" class="w-5 h-5" aria-hidden="true"></i></button>
                    </div>
                </nav>
            @endif
        @endif
    </div>
</div>

@push('scripts')
<script>
    function articlesLibrary(config) {
        return {
            articles:    config.articles || [],
            categories:  config.categories || [],
            tags:        config.tags || [],
            spotlightIds: config.spotlightIds || [],
            spotIndex: 0,
            perPage:     config.perPage || 9,

            search: '',
            activeCategory: null,
            activeTags: [],
            sort: 'latest',
            view: (() => { try { return localStorage.getItem('articlesView') || 'grid'; } catch (e) { return 'grid'; } })(),
            showTags: false,
            page: 1,

            init() {
                this.$watch('paginated', () => this.refreshIcons());
                this.$watch('view', () => this.refreshIcons());
                this.$watch('hasFilters', () => this.refreshIcons());
                this.$watch('showTags', () => this.refreshIcons());
                this.$watch('spotIndex', () => this.refreshIcons());
                this.refreshIcons();
            },

            /**
             * While browsing unfiltered, the lead folio and the featured strip
             * are already shown above the rail — listing them again would print
             * the same studies twice. As soon as the reader filters or
             * searches, they step aside and the whole library is searchable.
             */
            get browseList() {
                if (this.hasFilters || this.spotlightIds.length === 0) return this.articles;

                return this.articles.filter(a => !this.spotlightIds.includes(a.id));
            },

            get filtered() {
                let list = this.browseList;

                if (this.activeCategory !== null) {
                    list = list.filter(a => a.categoryId === this.activeCategory);
                }

                if (this.activeTags.length) {
                    list = list.filter(a => this.activeTags.every(t => (a.tags || []).includes(t)));
                }

                const q = this.search.trim().toLowerCase();
                if (q) {
                    list = list.filter(a =>
                        (a.title || '').toLowerCase().includes(q) ||
                        (a.excerpt || '').toLowerCase().includes(q) ||
                        (a.tags || []).some(t => t.toLowerCase().includes(q))
                    );
                }

                list = [...list];
                if (this.sort === 'latest') {
                    list.sort((a, b) => b.timestamp - a.timestamp);
                } else if (this.sort === 'oldest') {
                    list.sort((a, b) => a.timestamp - b.timestamp);
                } else if (this.sort === 'popular') {
                    list.sort((a, b) => b.views - a.views);
                } else if (this.sort === 'title') {
                    list.sort((a, b) => (a.title || '').localeCompare(b.title || '', undefined, { sensitivity: 'base' }));
                }

                return list;
            },

            get totalPages() {
                return Math.max(1, Math.ceil(this.filtered.length / this.perPage));
            },

            get paginated() {
                const start = (this.page - 1) * this.perPage;

                return this.filtered.slice(start, start + this.perPage);
            },

            // --- Server-rendered card enhancement -------------------------------
            // Cards are printed by Blade; Alpine only toggles which ones show
            // (current filtered page) and their visual order (active sort).
            get pageIds() {
                return new Set(this.paginated.map(a => a.id));
            },

            get orderMap() {
                const map = {};
                this.filtered.forEach((a, i) => { map[a.id] = i; });

                return map;
            },

            onPage(id) {
                return this.pageIds.has(id);
            },

            orderRank(id) {
                return this.orderMap[id] ?? 0;
            },
            // --------------------------------------------------------------------

            get pageNumbers() {
                const total = this.totalPages;
                const start = Math.max(1, this.page - 2);
                const end = Math.min(total, this.page + 2);
                const out = [];
                for (let i = start; i <= end; i++) out.push(i);

                return out;
            },

            get hasFilters() {
                return this.search.trim() !== '' || this.activeCategory !== null || this.activeTags.length > 0;
            },

            get activeCategoryName() {
                const cat = this.categories.find(c => c.id === this.activeCategory);

                return cat ? cat.name : '';
            },

            // Spotlight slider — wraps in both directions so the arrows are
            // never dead ends.
            nextSpot() {
                this.spotIndex = (this.spotIndex + 1) % this.spotlightIds.length;
            },

            prevSpot() {
                const n = this.spotlightIds.length;
                this.spotIndex = (this.spotIndex - 1 + n) % n;
            },

            setCategory(id) {
                this.activeCategory = (this.activeCategory === id) ? null : id;
                this.page = 1;
            },

            toggleTag(name) {
                const i = this.activeTags.indexOf(name);
                if (i > -1) this.activeTags.splice(i, 1);
                else this.activeTags.push(name);
                this.page = 1;
            },

            setView(mode) {
                this.view = mode;
                try { localStorage.setItem('articlesView', mode); } catch (e) {}
            },

            goTo(p) {
                if (p < 1 || p > this.totalPages) return;
                this.page = p;
                this.$el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            },

            clearFilters() {
                this.search = '';
                this.activeCategory = null;
                this.activeTags = [];
                this.page = 1;
            },

            refreshIcons() {
                this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
            },
        };
    }
</script>
@endpush
@endsection
