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

@push('head')
<style>
    /* Page-scoped premium styling for the articles library. */
    .lib-hero {
        position: relative;
        isolation: isolate;
        overflow: hidden;
    }
    .lib-hero::before,
    .lib-hero::after {
        content: '';
        position: absolute;
        z-index: -1;
        border-radius: 9999px;
        filter: blur(90px);
        opacity: .35;
        pointer-events: none;
    }
    .lib-hero::before {
        width: 34rem; height: 34rem;
        inset-inline-start: -8rem; top: -12rem;
        background: radial-gradient(circle, #0056b3 0%, transparent 70%);
    }
    .lib-hero::after {
        width: 30rem; height: 30rem;
        inset-inline-end: -6rem; bottom: -14rem;
        background: radial-gradient(circle, #218838 0%, transparent 70%);
    }
    .dark .lib-hero::before,
    .dark .lib-hero::after { opacity: .25; }

    /* Subtle eight-point-star lattice, drawn in CSS gradients (no asset). */
    .lib-hero-pattern {
        position: absolute; inset: 0; z-index: -1; pointer-events: none;
        background-image: radial-gradient(currentColor 1px, transparent 1.5px);
        background-size: 26px 26px;
        color: #0056b3;
        opacity: .07;
        -webkit-mask-image: linear-gradient(to bottom, black, transparent);
        mask-image: linear-gradient(to bottom, black, transparent);
    }
    .dark .lib-hero-pattern { color: #64CCC5; opacity: .08; }

    .lib-glass {
        background: rgba(255, 255, 255, .72);
        -webkit-backdrop-filter: blur(14px);
        backdrop-filter: blur(14px);
        border: 1px solid rgba(255, 255, 255, .55);
        box-shadow: 0 8px 32px rgba(2, 30, 68, .10);
    }
    .dark .lib-glass {
        background: rgba(22, 33, 62, .72);
        border-color: rgba(100, 204, 197, .14);
        box-shadow: 0 8px 32px rgba(0, 0, 0, .35);
    }

    .lib-card {
        border-radius: 1.25rem;
        overflow: hidden;
        background: #fff;
        border: 1px solid rgb(243 244 246);
        box-shadow: 0 1px 3px rgba(2, 30, 68, .06), 0 8px 24px -12px rgba(2, 30, 68, .10);
        transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
    }
    .dark .lib-card {
        background: #16213e;
        border-color: rgba(100, 204, 197, .10);
        box-shadow: 0 1px 3px rgba(0,0,0,.4);
    }
    .lib-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 4px 8px rgba(2, 30, 68, .06), 0 24px 48px -16px rgba(0, 86, 179, .25);
        border-color: rgba(0, 86, 179, .25);
    }
    .dark .lib-card:hover { box-shadow: 0 24px 48px -16px rgba(100, 204, 197, .18); }

    .lib-card .lib-card-img::after {
        content: '';
        position: absolute; inset: 0;
        background: linear-gradient(to top, rgba(2, 20, 40, .45) 0%, transparent 45%);
        opacity: .9;
        transition: opacity .3s ease;
        pointer-events: none;
    }
    .lib-card:hover .lib-card-img::after { opacity: .55; }

    .lib-card .lib-read-cue {
        transform: translateY(4px);
        opacity: 0;
        transition: transform .25s ease, opacity .25s ease;
    }
    .lib-card:hover .lib-read-cue { transform: translateY(0); opacity: 1; }

    @media (prefers-reduced-motion: reduce) {
        .lib-card, .lib-card *, .lib-read-cue { transition: none !important; transform: none !important; }
    }

    /* ═══════════════════════════════════════════════════════════════
       FOLIO SYSTEM

       The library reads its own brand palette as manuscript pigment:
       lapis (#0056b3) and verdigris (#218838) were the actual colours of
       Islamic illumination, ruled off by a gold jadwal. The hero is an
       opening folio, not a statistics banner.
       ═══════════════════════════════════════════════════════════════ */
    .folio {
        --lapis: #0056b3;
        --verdigris: #218838;
        --rule: #c9bfa8;
        --folio-paper: #fbf9f4;
    }
    .dark .folio { --rule: #3d4f6b; --folio-paper: #16213e; }

    /* Jadwal: the double rule that frames a manuscript text block. */
    .folio-frame {
        position: relative;
        background: var(--folio-paper);
        border: 1px solid var(--rule);
        box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--rule) 35%, transparent);
    }

    /* Corner illumination — a lozenge at each corner of the frame, the
       restrained descendant of an illuminated headpiece. */
    .folio-frame::before,
    .folio-frame::after {
        content: '';
        position: absolute;
        width: 9px;
        height: 9px;
        rotate: 45deg;
        background: var(--lapis);
        opacity: .85;
    }
    .folio-frame::before { inset-block-start: -5px; inset-inline-start: 28px; }
    .folio-frame::after  { inset-block-end: -5px;  inset-inline-end: 28px; background: var(--verdigris); }

    .folio-eyebrow {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: .5rem;
        font-size: .6875rem;
        font-weight: 700;
        letter-spacing: .14em;
        color: var(--verdigris);
    }
    .folio-eyebrow .sep { padding-inline: 0; color: var(--rule); }

    /* Amiri is already loaded for Quranic text; promoting it to the display
       role gives the library a voice native to its own subject. */
    .folio-title {
        font-family: 'Amiri', serif;
        line-height: 1.28;
        letter-spacing: -.005em;
    }

    /* Colophon: the closing dateline of a manuscript, hijri first. */
    .folio-colophon {
        font-variant-numeric: tabular-nums;
        font-size: .8125rem;
        color: #6b7280;
    }
    .dark .folio-colophon { color: #9ca3af; }
    .folio-colophon .sep { color: var(--rule); padding-inline: .55rem; }

    /* Ends of a colophon pushed apart, so no separator can dangle at a wrap. */
    .folio-colophon--row {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: .75rem;
    }

    .folio-plate { border: 1px solid var(--rule); background: #fff; }
    .dark .folio-plate { background: #0f3460; }

    /* Card: a folio leaf. The whole leaf is the affordance, so it carries no
       "read more" cue — the hairline and title shift do that work. */
    .folio-card {
        position: relative;
        background: #fff;
        border: 1px solid #e9e5db;
        border-radius: 2px;
        transition: border-color .3s ease, box-shadow .3s ease, transform .3s ease;
    }
    .dark .folio-card { background: #16213e; border-color: #0f3460; }
    .folio-card:hover {
        border-color: var(--rule);
        box-shadow: 0 18px 40px -22px rgba(16, 24, 32, .45);
        transform: translateY(-3px);
    }
    .dark .folio-card:hover { box-shadow: 0 18px 40px -22px rgba(0, 0, 0, .7); }

    /* Verdigris hairline that draws along the leaf's leading edge on hover. */
    .folio-card::after {
        content: '';
        position: absolute;
        inset-block: 0;
        inset-inline-start: 0;
        width: 2px;
        background: var(--verdigris);
        transform: scaleY(0);
        transform-origin: top;
        transition: transform .35s ease;
    }
    .folio-card:hover::after { transform: scaleY(1); }

    .folio-card-media { position: relative; overflow: hidden; background: #f3f4f6; }
    .dark .folio-card-media { background: #0f3460; }
    .folio-card-media img { transition: transform .6s cubic-bezier(.22,.61,.36,1); }
    .folio-card:hover .folio-card-media img { transform: scale(1.04); }

    .folio-card-rule { height: 1px; background: var(--rule); opacity: .55; }

    .folio-kicker {
        font-size: .6875rem;
        font-weight: 700;
        letter-spacing: .12em;
        color: var(--lapis);
    }
    .dark .folio-kicker { color: #64CCC5; }

    .folio-seal {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        padding: .2rem .55rem;
        font-size: .6875rem;
        font-weight: 700;
        color: #fff;
        background: var(--verdigris);
    }

    @media (prefers-reduced-motion: reduce) {
        .folio-card, .folio-card *, .folio-card::after { transition: none !important; transform: none !important; }
    }
</style>
@endpush

@section('content')

@php
    $articlesJson   = json_encode($articles,   JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
    $categoriesJson = json_encode($categories, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
    $tagsJson       = json_encode($tags,       JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
    $isRtlLocale    = in_array(app()->getLocale(), ['ar', 'ur'], true);
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
    @php
        // The hero opens on a piece of writing, not a counter: prefer a featured
        // study, else the most recent one. Null when the library is still empty.
        $opening = collect($articles)->firstWhere('isFeatured', true) ?? ($articles[0] ?? null);
    @endphp

    <section class="folio lib-hero border-b border-gray-100 dark:border-[#0f3460]">
        <div class="lib-hero-pattern" aria-hidden="true"></div>
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative py-12 md:py-16">

            <div class="max-w-5xl mx-auto">
                {{-- Masthead: the library names itself once, quietly. --}}
                <div class="flex items-baseline gap-3 mb-6">
                    <h1 class="folio-title text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">
                        {{ __('articles.public.hero_title') }}
                    </h1>
                    <span class="hidden sm:block flex-1 h-px bg-[var(--rule)] opacity-60" aria-hidden="true"></span>
                    <span class="folio-colophon hidden sm:inline">
                        <span x-text="articles.length"></span> {{ __('articles.public.article_unit') }}
                        <span class="sep" aria-hidden="true">·</span>
                        <span x-text="categories.length"></span> {{ __('articles.public.categories') }}
                        <span class="sep" aria-hidden="true">·</span>
                        <span x-text="tags.length"></span> {{ __('articles.public.tags') }}
                    </span>
                </div>

                @if ($opening)
                    {{-- The opening folio --}}
                    <a href="{{ $opening['url'] }}"
                       class="folio-frame group block p-6 md:p-10 fade-in-up focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/30">
                        <div class="grid md:grid-cols-[1.35fr_1fr] gap-7 md:gap-10 items-center">

                            <div class="min-w-0">
                                @php
                                    // Only say something the masthead has not already said.
                                    $openingEyebrow = array_values(array_filter([
                                        ! empty($opening['isFeatured']) ? __('articles.public.featured') : null,
                                        $opening['category'] ?? null,
                                    ]));
                                @endphp
                                @if ($openingEyebrow)
                                    <p class="folio-eyebrow mb-3">
                                        @foreach ($openingEyebrow as $i => $part)
                                            @if ($i > 0)<span class="sep" aria-hidden="true">·</span>@endif{{ $part }}
                                        @endforeach
                                    </p>
                                @endif

                                <h2 class="folio-title text-3xl md:text-[2.75rem] font-bold text-gray-900 dark:text-white mb-4 group-hover:text-primary transition-colors">
                                    {{ $opening['title'] }}
                                </h2>

                                <p class="text-[15px] md:text-base text-gray-600 dark:text-gray-300 leading-relaxed line-clamp-3 mb-7">
                                    {{ $opening['excerpt'] }}
                                </p>

                                <div class="h-px bg-[var(--rule)] opacity-50 mb-3.5" aria-hidden="true"></div>

                                {{-- Colophon: hijri first, the calendar this material lives in. --}}
                                <p class="folio-colophon">
                                    @if (! empty($opening['hijriDate']))
                                        <span>{{ $opening['hijriDate'] }}</span>
                                        <span class="sep" aria-hidden="true">·</span>
                                    @endif
                                    <span>{{ $opening['dateLabel'] }}</span>
                                    <span class="sep" aria-hidden="true">·</span>
                                    <span>{{ $opening['readTime'] }} {{ __('articles.public.min_read') }}</span>
                                </p>
                            </div>

                            {{-- Plate: the illustrated leaf facing the text block. --}}
                            <div class="folio-plate p-1.5 order-first md:order-none">
                                <div class="relative aspect-[4/3] overflow-hidden">
                                    <img src="{{ $opening['image'] }}" alt="{{ $opening['title'] }}"
                                         onerror="this.style.display='none';this.parentElement.classList.add('img-gradient-fallback')"
                                         class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-[1.03] motion-reduce:transform-none">
                                </div>
                            </div>
                        </div>
                    </a>
                @endif

                {{-- Search sits beneath the folio: the frame is the subject, this is the tool. --}}
                <div class="relative mt-8 border border-gray-200 dark:border-[#0f3460] bg-white dark:bg-[#16213e] focus-within:border-primary transition-colors">
                    <i data-lucide="search" class="absolute start-4 top-1/2 -translate-y-1/2 w-[18px] h-[18px] text-gray-400 pointer-events-none"></i>
                    <input
                        type="search"
                        x-model.debounce.250ms="search"
                        @input="page = 1"
                        placeholder="{{ __('articles.public.search_placeholder') }}"
                        aria-label="{{ __('articles.public.search_placeholder') }}"
                        class="w-full ps-12 pe-12 py-3.5 text-base sm:text-[15px] bg-transparent text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none"
                    >
                    <button
                        x-show="search.length > 0" x-cloak
                        @click="search = ''; page = 1"
                        class="absolute end-3 top-1/2 -translate-y-1/2 w-7 h-7 flex items-center justify-center text-gray-400 hover:text-white hover:bg-primary transition-colors cursor-pointer"
                        aria-label="{{ __('articles.public.close') }}"
                    >
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
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
            <aside class="lg:col-span-3">
                <div class="lg:sticky lg:top-24 space-y-6">

                    {{-- Categories --}}
                    <div class="card-premium p-5">
                        <h2 class="text-sm font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
                            <span class="w-7 h-7 rounded-lg bg-primary/10 text-primary flex items-center justify-center">
                                <i data-lucide="folder" class="w-4 h-4"></i>
                            </span>
                            {{ __('articles.public.categories') }}
                        </h2>
                        <div class="space-y-1">
                            <button
                                @click="setCategory(null)"
                                class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-semibold transition-all cursor-pointer"
                                :class="activeCategory === null
                                    ? 'bg-gradient-to-l from-primary to-secondary text-white shadow-md'
                                    : 'text-gray-700 dark:text-gray-200 hover:bg-primary/5 hover:translate-x-0.5 rtl:hover:-translate-x-0.5'"
                            >
                                <span>{{ __('articles.public.all_categories') }}</span>
                                <span class="text-xs px-2 py-0.5 rounded-full"
                                      :class="activeCategory === null ? 'bg-white/20' : 'bg-gray-100 dark:bg-[#0f3460]'"
                                      x-text="articles.length"></span>
                            </button>
                            <template x-for="cat in categories" :key="cat.id">
                                <button
                                    @click="setCategory(cat.id)"
                                    class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-semibold transition-all cursor-pointer"
                                    :class="activeCategory === cat.id
                                        ? 'bg-gradient-to-l from-primary to-secondary text-white shadow-md'
                                        : 'text-gray-700 dark:text-gray-200 hover:bg-primary/5 hover:translate-x-0.5 rtl:hover:-translate-x-0.5'"
                                >
                                    <span class="truncate" x-text="cat.name"></span>
                                    <span class="text-xs px-2 py-0.5 rounded-full shrink-0 ms-2"
                                          :class="activeCategory === cat.id ? 'bg-white/20' : 'bg-gray-100 dark:bg-[#0f3460]'"
                                          x-text="cat.count"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- Tag cloud --}}
                    <div class="card-premium p-5">
                        <h2 class="text-sm font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
                            <span class="w-7 h-7 rounded-lg bg-secondary/10 text-secondary flex items-center justify-center">
                                <i data-lucide="tag" class="w-4 h-4"></i>
                            </span>
                            {{ __('articles.public.tags') }}
                        </h2>
                        <template x-if="tags.length === 0">
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('articles.public.no_tags') }}</p>
                        </template>
                        <div class="flex flex-wrap gap-2" x-show="tags.length > 0">
                            <template x-for="tag in tags" :key="tag.name">
                                <button
                                    @click="toggleTag(tag.name)"
                                    class="px-3 py-1.5 rounded-full text-xs font-semibold transition-all cursor-pointer"
                                    :class="activeTags.includes(tag.name)
                                        ? 'bg-gradient-to-l from-primary to-secondary text-white shadow-md scale-105'
                                        : 'bg-gray-100 dark:bg-[#0f3460] text-gray-700 dark:text-gray-200 hover:bg-primary/10 hover:text-primary'"
                                >
                                    <span x-text="tag.name"></span>
                                    <span class="opacity-70" x-text="'(' + tag.count + ')'"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- Results count --}}
                    <div class="card-premium p-6 text-center relative overflow-hidden">
                        <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-l from-primary via-accent to-secondary" aria-hidden="true"></div>
                        <div class="text-4xl font-bold text-gradient tabular-nums" x-text="filtered.length"></div>
                        <div class="text-sm mt-1 text-gray-500 dark:text-gray-400">{{ __('articles.public.article_unit') }}</div>
                    </div>
                </div>
            </aside>

            {{-- ============================================================ --}}
            {{-- MAIN --}}
            {{-- ============================================================ --}}
            <main class="lg:col-span-9">

                {{-- Toolbar: count + sort + view toggle --}}
                <div class="card-premium px-4 py-3 flex items-center justify-between flex-wrap gap-3 mb-6">
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        {{ __('site.showing') }}
                        <span class="font-bold text-primary tabular-nums" x-text="filtered.length"></span>
                        {{ __('site.of') }}
                        <span class="tabular-nums" x-text="articles.length"></span>
                        {{ __('articles.public.article_unit') }}
                    </p>

                    <div class="flex items-center gap-2 flex-wrap">
                        {{-- Sort --}}
                        <select
                            x-model="sort"
                            @change="page = 1"
                            aria-label="{{ __('articles.public.sort_label') }}"
                            class="px-4 py-2 text-base sm:text-sm rounded-xl border border-gray-200 dark:border-[#0f3460] bg-white dark:bg-[#16213e] text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/30 transition-all cursor-pointer"
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
                                class="p-2 rounded-lg transition-all cursor-pointer"
                                :class="view === 'grid' ? 'bg-gradient-to-l from-primary to-secondary text-white shadow' : 'text-gray-500 dark:text-gray-300 hover:text-primary'"
                                :aria-pressed="view === 'grid'"
                                aria-label="{{ __('articles.public.view_grid') }}"
                            >
                                <i data-lucide="layout-grid" class="w-5 h-5"></i>
                            </button>
                            <button
                                @click="setView('list')"
                                class="p-2 rounded-lg transition-all cursor-pointer"
                                :class="view === 'list' ? 'bg-gradient-to-l from-primary to-secondary text-white shadow' : 'text-gray-500 dark:text-gray-300 hover:text-primary'"
                                :aria-pressed="view === 'list'"
                                aria-label="{{ __('articles.public.view_list') }}"
                            >
                                <i data-lucide="list" class="w-5 h-5"></i>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Active filters (each chip removable) --}}
                <div x-show="hasFilters" x-cloak class="flex flex-wrap items-center gap-2 mb-6">
                    <button
                        @click="clearFilters()"
                        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold bg-red-500 text-white hover:bg-red-600 transition-colors cursor-pointer"
                    >
                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                        {{ __('articles.public.clear_filters') }}
                    </button>
                    <template x-if="activeCategory !== null">
                        <button
                            @click="setCategory(activeCategory)"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-primary/10 text-primary hover:bg-primary/20 transition-colors cursor-pointer"
                        >
                            <i data-lucide="folder" class="w-3.5 h-3.5"></i>
                            <span x-text="activeCategoryName"></span>
                            <i data-lucide="x" class="w-3 h-3 opacity-70"></i>
                        </button>
                    </template>
                    <template x-for="tag in activeTags" :key="tag">
                        <button
                            @click="toggleTag(tag)"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-secondary/10 text-secondary hover:bg-secondary/20 transition-colors cursor-pointer"
                        >
                            <i data-lucide="tag" class="w-3.5 h-3.5"></i>
                            <span x-text="tag"></span>
                            <i data-lucide="x" class="w-3 h-3 opacity-70"></i>
                        </button>
                    </template>
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
                           class="group folio folio-card flex flex-col focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/30">
                            {{-- Plate --}}
                            <div class="folio-card-media aspect-[16/10]">
                                <img src="{{ $article['image'] }}" alt="{{ $article['title'] }}" loading="lazy"
                                     onerror="this.style.display='none';this.parentElement.classList.add('img-gradient-fallback')"
                                     onload="this.style.display='';this.parentElement.classList.remove('img-gradient-fallback')"
                                     class="w-full h-full object-cover motion-reduce:transform-none">
                                @if ($article['isFeatured'])
                                    <span class="folio-seal absolute top-0 start-0 z-10">
                                        {{ __('articles.public.featured') }}
                                    </span>
                                @endif
                            </div>
                            <div class="folio-card-rule" aria-hidden="true"></div>

                            {{-- Text block --}}
                            <div class="p-5 flex flex-col flex-1">
                                @if (! empty($article['category']))
                                    <p class="folio-kicker mb-2">{{ $article['category'] }}</p>
                                @endif

                                <h3 class="folio-title text-[1.35rem] font-bold text-gray-900 dark:text-white mb-2.5 line-clamp-2 group-hover:text-primary transition-colors">{{ $article['title'] }}</h3>

                                <p class="text-sm text-gray-600 dark:text-gray-300 line-clamp-3 mb-5 flex-1 leading-relaxed">{{ $article['excerpt'] }}</p>

                                @if (! empty($article['tags']))
                                    <div class="flex flex-wrap gap-x-3 gap-y-1 mb-4 text-[11px] text-gray-500 dark:text-gray-400">
                                        @foreach (array_slice($article['tags'], 0, 3) as $tag)
                                            <span>{{ $tag }}</span>
                                        @endforeach
                                    </div>
                                @endif

                                {{-- Colophon. The card carries the hijri date only:
                                     it is the calendar this material is dated in,
                                     and two dates wrap onto a second line. --}}
                                <div class="folio-colophon folio-colophon--row pt-3 border-t border-gray-100 dark:border-[#0f3460]">
                                    <span>{{ $article['hijriDate'] ?: $article['dateLabel'] }}</span>
                                    <span>{{ $article['readTime'] }} {{ __('articles.public.min_read') }}</span>
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
                           class="group lib-card flex flex-col sm:flex-row">
                            <div class="lib-card-img relative sm:w-56 shrink-0 aspect-[16/10] sm:aspect-auto overflow-hidden bg-gray-100 dark:bg-[#0f3460]">
                                <img :src="article.image" :alt="article.title" loading="lazy"
                                     onerror="this.style.display='none';this.parentElement.classList.add('img-gradient-fallback')"
                                     onload="this.style.display='';this.parentElement.classList.remove('img-gradient-fallback')"
                                     class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105 motion-reduce:transform-none">
                                <template x-if="article.isFeatured">
                                    <span class="absolute top-3 start-3 z-10 inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-gradient-to-l from-primary to-secondary text-white shadow">
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
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2 line-clamp-2 group-hover:text-primary transition-colors leading-snug"
                                    x-text="article.title"></h3>
                                <p class="text-sm text-gray-600 dark:text-gray-300 line-clamp-2 mb-4 flex-1 leading-relaxed" x-text="article.excerpt"></p>
                                <div class="flex items-center justify-between gap-4 text-xs text-gray-500 dark:text-gray-400 flex-wrap">
                                    <span class="inline-flex items-center gap-4">
                                        <span class="inline-flex items-center gap-1">
                                            <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                                            <span x-text="article.readTime"></span>{{ __('articles.public.min_read') }}
                                        </span>
                                        <span class="inline-flex items-center gap-1">
                                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                            <span x-text="article.views"></span> {{ __('articles.public.views') }}
                                        </span>
                                    </span>
                                    <span class="lib-read-cue inline-flex items-center gap-1 font-semibold text-primary">
                                        {{ __('articles.public.read_more') }}
                                        <i data-lucide="arrow-{{ $isRtlLocale ? 'left' : 'right' }}" class="w-3.5 h-3.5"></i>
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
                     class="flex justify-center mt-10">
                    <div class="lib-glass rounded-full px-2 py-1.5 flex items-center gap-1 flex-wrap justify-center">
                        <button
                            @click="goTo(page - 1)"
                            :disabled="page === 1"
                            class="w-10 h-10 rounded-full flex items-center justify-center text-gray-600 dark:text-gray-300 hover:bg-primary/10 hover:text-primary transition-colors disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
                            aria-label="{{ __('articles.public.previous') }}"
                        >
                            <i data-lucide="chevron-{{ $isRtlLocale ? 'right' : 'left' }}" class="w-4 h-4"></i>
                        </button>

                        <template x-for="p in pageNumbers" :key="p">
                            <button
                                @click="goTo(p)"
                                class="min-w-10 h-10 px-3 rounded-full font-semibold text-sm transition-all tabular-nums cursor-pointer"
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
                            class="w-10 h-10 rounded-full flex items-center justify-center text-gray-600 dark:text-gray-300 hover:bg-primary/10 hover:text-primary transition-colors disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
                            aria-label="{{ __('articles.public.next') }}"
                        >
                            <i data-lucide="chevron-{{ $isRtlLocale ? 'left' : 'right' }}" class="w-4 h-4"></i>
                        </button>
                    </div>
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
                this.$watch('hasFilters', () => this.refreshIcons());
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
