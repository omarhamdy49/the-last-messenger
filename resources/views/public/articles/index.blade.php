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

@section('content')

@php
    $articlesJson   = json_encode($articles,   JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
    $categoriesJson = json_encode($categories, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
    $tagsJson       = json_encode($tags,       JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
@endphp

<div
    x-data="articlesIndex({
        articles:   {{ $articlesJson }},
        categories: {{ $categoriesJson }},
        tags:       {{ $tagsJson }},
        perPage:    9,
    })"
    class="min-h-screen"
>

    {{-- ============================================================ --}}
    {{-- HERO + SEARCH --}}
    {{-- ============================================================ --}}
    <section class="relative overflow-hidden border-b border-gray-100 dark:border-[#0f3460]">
        <div class="absolute inset-0 bg-gradient-to-br from-primary via-secondary to-accent opacity-10"></div>
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative py-14 md:py-16">
            <div class="max-w-3xl mx-auto text-center fade-in-up">
                <div class="flex justify-center mb-5">
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-l from-primary to-secondary flex items-center justify-center text-white shadow-lg">
                        <i data-lucide="library" class="w-8 h-8"></i>
                    </div>
                </div>
                <h1 class="text-3xl md:text-4xl font-bold mb-3 text-gray-900 dark:text-white">
                    {{ __('articles.public.hero_title') }}
                </h1>
                <p class="text-base md:text-lg text-gray-600 dark:text-gray-300 mb-8">
                    {{ __('articles.public.hero_subtitle') }}
                </p>

                {{-- Search --}}
                <div class="relative max-w-2xl mx-auto">
                    <i data-lucide="search" class="absolute start-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>
                    <input
                        type="search"
                        x-model.debounce.250ms="search"
                        @input="page = 1"
                        placeholder="{{ __('articles.public.search_placeholder') }}"
                        aria-label="{{ __('articles.public.search_placeholder') }}"
                        class="w-full ps-12 pe-5 py-4 text-base sm:text-sm rounded-full border-2 border-gray-200 dark:border-[#0f3460] bg-white dark:bg-[#16213e] text-gray-900 dark:text-white focus:outline-none focus:ring-4 focus:ring-primary/20 focus:border-primary transition-all"
                    >
                </div>
            </div>
        </div>
    </section>

    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <x-site.breadcrumb :items="[['label' => __('articles.public.breadcrumb')]]" class="mb-8" />

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

            {{-- ============================================================ --}}
            {{-- SIDEBAR --}}
            {{-- ============================================================ --}}
            <aside class="lg:col-span-3 space-y-6">

                {{-- Categories --}}
                <div class="card-premium p-5">
                    <h2 class="text-sm font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
                        <i data-lucide="folder" class="w-4 h-4"></i>
                        {{ __('articles.public.categories') }}
                    </h2>
                    <div class="space-y-1">
                        <button
                            @click="setCategory(null)"
                            class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-semibold transition-colors"
                            :class="activeCategory === null
                                ? 'bg-gradient-to-l from-primary to-secondary text-white shadow'
                                : 'text-gray-700 dark:text-gray-200 hover:bg-primary/5'"
                        >
                            <span>{{ __('articles.public.all_categories') }}</span>
                            <span class="text-xs opacity-80" x-text="articles.length"></span>
                        </button>
                        <template x-for="cat in categories" :key="cat.id">
                            <button
                                @click="setCategory(cat.id)"
                                class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-semibold transition-colors"
                                :class="activeCategory === cat.id
                                    ? 'bg-gradient-to-l from-primary to-secondary text-white shadow'
                                    : 'text-gray-700 dark:text-gray-200 hover:bg-primary/5'"
                            >
                                <span class="truncate" x-text="cat.name"></span>
                                <span class="text-xs opacity-80 shrink-0 ms-2" x-text="cat.count"></span>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Tag cloud --}}
                <div class="card-premium p-5">
                    <h2 class="text-sm font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
                        <i data-lucide="tag" class="w-4 h-4"></i>
                        {{ __('articles.public.tags') }}
                    </h2>
                    <template x-if="tags.length === 0">
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('articles.public.no_tags') }}</p>
                    </template>
                    <div class="flex flex-wrap gap-2" x-show="tags.length > 0">
                        <template x-for="tag in tags" :key="tag.name">
                            <button
                                @click="toggleTag(tag.name)"
                                class="px-3 py-1.5 rounded-full text-xs font-semibold transition-colors"
                                :class="activeTags.includes(tag.name)
                                    ? 'bg-gradient-to-l from-primary to-secondary text-white shadow'
                                    : 'bg-gray-100 dark:bg-[#0f3460] text-gray-700 dark:text-gray-200 hover:bg-primary/10'"
                            >
                                <span x-text="tag.name"></span>
                                <span class="opacity-70" x-text="'(' + tag.count + ')'"></span>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Results count --}}
                <div class="card-premium p-6 text-center">
                    <div class="text-4xl font-bold text-gradient" x-text="filtered.length"></div>
                    <div class="text-sm mt-1 text-gray-500 dark:text-gray-400">{{ __('articles.public.article_unit') }}</div>
                </div>
            </aside>

            {{-- ============================================================ --}}
            {{-- MAIN --}}
            {{-- ============================================================ --}}
            <main class="lg:col-span-9">

                {{-- Toolbar: count + sort + view toggle --}}
                <div class="flex items-center justify-between flex-wrap gap-3 mb-6">
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        {{ __('site.showing') }}
                        <span class="font-bold text-primary" x-text="filtered.length"></span>
                        {{ __('site.of') }}
                        <span x-text="articles.length"></span>
                        {{ __('articles.public.article_unit') }}
                    </p>

                    <div class="flex items-center gap-2 flex-wrap">
                        {{-- Sort --}}
                        <select
                            x-model="sort"
                            @change="page = 1"
                            aria-label="{{ __('articles.public.sort_label') }}"
                            class="px-4 py-2 text-base sm:text-sm rounded-xl border-2 border-gray-200 dark:border-[#0f3460] bg-white dark:bg-[#16213e] text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/30 transition-all"
                        >
                            <option value="latest">{{ __('articles.public.sort_latest') }}</option>
                            <option value="oldest">{{ __('articles.public.sort_oldest') }}</option>
                            <option value="popular">{{ __('articles.public.sort_popular') }}</option>
                            <option value="title">{{ __('articles.public.sort_title') }}</option>
                        </select>

                        {{-- View toggle --}}
                        <div class="flex gap-1 rounded-xl p-1 bg-gray-100 dark:bg-[#0f3460]">
                            <button
                                @click="setView('grid')"
                                class="p-2 rounded-lg transition-all"
                                :class="view === 'grid' ? 'bg-gradient-to-l from-primary to-secondary text-white' : 'text-gray-500 dark:text-gray-300 hover:text-primary'"
                                :aria-pressed="view === 'grid'"
                                aria-label="{{ __('articles.public.view_grid') }}"
                            >
                                <i data-lucide="layout-grid" class="w-5 h-5"></i>
                            </button>
                            <button
                                @click="setView('list')"
                                class="p-2 rounded-lg transition-all"
                                :class="view === 'list' ? 'bg-gradient-to-l from-primary to-secondary text-white' : 'text-gray-500 dark:text-gray-300 hover:text-primary'"
                                :aria-pressed="view === 'list'"
                                aria-label="{{ __('articles.public.view_list') }}"
                            >
                                <i data-lucide="list" class="w-5 h-5"></i>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Active filters --}}
                <div x-show="hasFilters" x-cloak class="flex flex-wrap items-center gap-2 mb-6">
                    <button
                        @click="clearFilters()"
                        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold bg-red-500 text-white hover:bg-red-600 transition-colors"
                    >
                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                        {{ __('articles.public.clear_filters') }}
                    </button>
                    <template x-if="activeCategory !== null">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-gray-100 dark:bg-[#0f3460] text-gray-700 dark:text-gray-200">
                            <i data-lucide="folder" class="w-3.5 h-3.5"></i>
                            <span x-text="activeCategoryName"></span>
                        </span>
                    </template>
                    <template x-for="tag in activeTags" :key="tag">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-gray-100 dark:bg-[#0f3460] text-gray-700 dark:text-gray-200">
                            <i data-lucide="tag" class="w-3.5 h-3.5"></i>
                            <span x-text="tag"></span>
                        </span>
                    </template>
                </div>

                {{-- ============================================================ --}}
                {{-- LOADING SKELETON (visible until Alpine boots) --}}
                {{-- ============================================================ --}}
                <div x-show="false">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4" role="status">
                        {{ __('articles.public.loading') }}
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6" aria-hidden="true">
                        @for ($i = 0; $i < 3; $i++)
                            <div class="card-premium overflow-hidden animate-pulse motion-reduce:animate-none">
                                <div class="aspect-[16/10] bg-gray-200 dark:bg-[#0f3460]"></div>
                                <div class="p-5 space-y-3">
                                    <div class="h-4 rounded bg-gray-200 dark:bg-[#0f3460] w-3/4"></div>
                                    <div class="h-3 rounded bg-gray-200 dark:bg-[#0f3460]"></div>
                                    <div class="h-3 rounded bg-gray-200 dark:bg-[#0f3460] w-5/6"></div>
                                </div>
                            </div>
                        @endfor
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- GRID VIEW — server-rendered so every article is a real,    --}}
                {{-- crawlable <a href> in the initial HTML (no JS required).    --}}
                {{-- Alpine progressively enhances: per-card x-show drives the   --}}
                {{-- active filter/page, CSS `order` applies the active sort.    --}}
                {{-- ============================================================ --}}
                <div x-show="view === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
                    @foreach ($articles as $article)
                        <a href="{{ $article['url'] }}"
                           x-show="onPage({{ $article['id'] }})"
                           :style="{ order: orderRank({{ $article['id'] }}) }"
                           class="group card-premium overflow-hidden flex flex-col hover-lift">
                            {{-- Image --}}
                            <div class="relative aspect-[16/10] overflow-hidden bg-gray-100 dark:bg-[#0f3460]">
                                <img src="{{ $article['image'] }}" alt="{{ $article['title'] }}" loading="lazy"
                                     onerror="this.style.display='none';this.parentElement.classList.add('img-gradient-fallback')"
                                     onload="this.style.display='';this.parentElement.classList.remove('img-gradient-fallback')"
                                     class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                                @if ($article['isFeatured'])
                                    <span class="absolute top-3 start-3 inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-gradient-to-l from-primary to-secondary text-white shadow">
                                        <i data-lucide="star" class="w-3 h-3"></i>
                                        {{ __('articles.public.featured') }}
                                    </span>
                                @endif
                                @if (! empty($article['category']))
                                    <span class="absolute bottom-3 start-3 px-2.5 py-1 rounded-full text-xs font-semibold bg-white/90 dark:bg-[#16213e]/90 text-primary backdrop-blur">{{ $article['category'] }}</span>
                                @endif
                            </div>

                            {{-- Body --}}
                            <div class="p-5 flex flex-col flex-1">
                                @if (! empty($article['tags']))
                                    <div class="flex flex-wrap gap-1.5 mb-2">
                                        @foreach (array_slice($article['tags'], 0, 3) as $tag)
                                            <span class="px-2 py-0.5 rounded text-[11px] font-medium bg-gray-100 dark:bg-[#0f3460] text-gray-600 dark:text-gray-300">{{ $tag }}</span>
                                        @endforeach
                                    </div>
                                @endif
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2 line-clamp-2 group-hover:text-primary transition-colors">{{ $article['title'] }}</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-300 line-clamp-3 mb-4 flex-1">{{ $article['excerpt'] }}</p>

                                {{-- Meta --}}
                                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 pt-3 border-t border-gray-100 dark:border-[#0f3460]">
                                    <span class="text-gray-400 dark:text-gray-500">{{ $article['dateLabel'] }}</span>
                                    <span class="inline-flex items-center gap-3 shrink-0">
                                        <span class="inline-flex items-center gap-1">
                                            <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                                            {{ $article['readTime'] }}{{ __('articles.public.min_read') }}
                                        </span>
                                        <span class="inline-flex items-center gap-1">
                                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                            {{ $article['views'] }}
                                        </span>
                                    </span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>

                {{-- ============================================================ --}}
                {{-- LIST VIEW --}}
                {{-- ============================================================ --}}
                <div x-show="view === 'list'" x-cloak class="space-y-4">
                    <template x-for="article in paginated" :key="article.id">
                        <a :href="article.url"
                           class="group card-premium overflow-hidden flex flex-col sm:flex-row hover-lift">
                            <div class="relative sm:w-56 shrink-0 aspect-[16/10] sm:aspect-auto overflow-hidden bg-gray-100 dark:bg-[#0f3460]">
                                <img :src="article.image" :alt="article.title" loading="lazy"
                                     onerror="this.style.display='none';this.parentElement.classList.add('img-gradient-fallback')"
                                     onload="this.style.display='';this.parentElement.classList.remove('img-gradient-fallback')"
                                     class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                                <template x-if="article.isFeatured">
                                    <span class="absolute top-3 start-3 inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-gradient-to-l from-primary to-secondary text-white shadow">
                                        <i data-lucide="star" class="w-3 h-3"></i>
                                        {{ __('articles.public.featured') }}
                                    </span>
                                </template>
                            </div>
                            <div class="p-5 flex flex-col flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-2">
                                    <template x-if="article.category">
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-primary/10 text-primary" x-text="article.category"></span>
                                    </template>
                                    <span class="text-xs text-gray-400 dark:text-gray-500" x-text="article.dateLabel"></span>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2 line-clamp-2 group-hover:text-primary transition-colors"
                                    x-text="article.title"></h3>
                                <p class="text-sm text-gray-600 dark:text-gray-300 line-clamp-2 mb-4 flex-1" x-text="article.excerpt"></p>
                                <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400 flex-wrap">
                                    <span class="inline-flex items-center gap-1">
                                        <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                                        <span x-text="article.readTime"></span>{{ __('articles.public.min_read') }}
                                    </span>
                                    <span class="inline-flex items-center gap-1">
                                        <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                        <span x-text="article.views"></span> {{ __('articles.public.views') }}
                                    </span>
                                </div>
                            </div>
                        </a>
                    </template>
                </div>

                {{-- ============================================================ --}}
                {{-- EMPTY STATE --}}
                {{-- ============================================================ --}}
                <div x-show="filtered.length === 0" x-cloak>
                    <x-site.empty-state
                        icon="file-search"
                        :title="__('articles.public.empty_title')"
                        :message="__('articles.public.empty_message')"
                        :showReset="true"
                        @click="clearFilters()"
                    />
                </div>

                {{-- ============================================================ --}}
                {{-- PAGINATION (client-side, numbered) --}}
                {{-- ============================================================ --}}
                <nav x-show="totalPages > 1" x-cloak aria-label="{{ __('articles.public.pagination') }}"
                     class="flex items-center justify-center gap-1.5 mt-10 flex-wrap">
                    <button
                        @click="goTo(page - 1)"
                        :disabled="page === 1"
                        class="px-3 py-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-primary/10 hover:text-primary transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                        aria-label="{{ __('articles.public.previous') }}"
                    >
                        <i data-lucide="chevron-{{ in_array(app()->getLocale(), ['ar', 'ur'], true) ? 'right' : 'left' }}" class="w-4 h-4"></i>
                    </button>

                    <template x-for="p in pageNumbers" :key="p">
                        <button
                            @click="goTo(p)"
                            class="px-4 py-2 rounded-lg font-semibold transition-colors"
                            :class="p === page
                                ? 'text-white bg-gradient-to-l from-primary to-secondary shadow-md'
                                : 'text-gray-700 dark:text-gray-200 hover:bg-primary/10 hover:text-primary'"
                            :aria-current="p === page ? 'page' : null"
                            x-text="p"
                        ></button>
                    </template>

                    <button
                        @click="goTo(page + 1)"
                        :disabled="page === totalPages"
                        class="px-3 py-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-primary/10 hover:text-primary transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                        aria-label="{{ __('articles.public.next') }}"
                    >
                        <i data-lucide="chevron-{{ in_array(app()->getLocale(), ['ar', 'ur'], true) ? 'left' : 'right' }}" class="w-4 h-4"></i>
                    </button>
                </nav>

            </main>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function articlesIndex(config) {
        return {
            articles:   config.articles || [],
            categories: config.categories || [],
            tags:       config.tags || [],
            perPage:    config.perPage || 9,

            search: '',
            activeCategory: null,
            activeTags: [],
            sort: 'latest',
            view: localStorage.getItem('viewMode') || 'grid',
            page: 1,

            init() {
                this.$watch('paginated', () => this.refreshIcons());
                this.$watch('view', () => this.refreshIcons());
                this.refreshIcons();
            },

            get filtered() {
                let list = this.articles;

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

            // --- Server-rendered grid enhancement -------------------------------
            // The grid <a> cards are printed by Blade; Alpine only toggles which
            // ones show (current filtered page) and their visual order (sort).
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
                const cur = this.page;
                const start = Math.max(1, cur - 2);
                const end = Math.min(total, cur + 2);
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
                localStorage.setItem('viewMode', mode);
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
