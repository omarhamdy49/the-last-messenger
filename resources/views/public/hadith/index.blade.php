@extends('layouts.public')

@section('title', __('hadith.public.page_title'))
@section('meta_description', __('hadith.public.page_description'))

@push('structured_data')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "CollectionPage",
    "name": "{{ __('hadith.public.page_title') }}",
    "description": "{{ __('hadith.public.page_description') }}",
    "url": "{{ route('public.hadith') }}",
    "inLanguage": "{{ app()->getLocale() }}"
}
</script>
@endpush

@push('head')
<style>
    [x-cloak] { display: none !important; }

    .font-amiri { font-family: 'Amiri', serif; }

    /* Subtle Islamic geometric backdrop */
    .lib-pattern {
        background-image:
            radial-gradient(circle at 12% 18%, rgba(0, 86, 179, 0.05) 0%, transparent 45%),
            radial-gradient(circle at 88% 82%, rgba(33, 136, 56, 0.05) 0%, transparent 45%);
    }
    .hero-pattern {
        background-image: url("data:image/svg+xml,%3Csvg width='52' height='52' viewBox='0 0 52 52' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M26 0l6 14 14 6-14 6-6 14-6-14-14-6 14-6z' fill='%23ffffff' fill-opacity='0.06'/%3E%3C/svg%3E");
    }

    /* Premium card hover lift */
    .lib-card {
        transition: transform .28s cubic-bezier(.4,0,.2,1), box-shadow .28s cubic-bezier(.4,0,.2,1), border-color .28s ease;
    }
    .lib-card:hover { transform: translateY(-6px); }

    /* Book cover header sheen */
    .cover-sheen { position: relative; overflow: hidden; }
    .cover-sheen::after {
        content: '';
        position: absolute; inset: 0;
        background: linear-gradient(115deg, transparent 30%, rgba(255,255,255,.18) 50%, transparent 70%);
        transform: translateX(-120%);
        transition: transform .7s ease;
    }
    .group:hover .cover-sheen::after { transform: translateX(120%); }

    @keyframes lib-fade-up { from { opacity: 0; transform: translateY(18px); } to { opacity: 1; transform: translateY(0); } }
    .lib-fade-up { animation: lib-fade-up .5s ease-out both; }

    .lib-scroll::-webkit-scrollbar { height: 6px; }
    .lib-scroll::-webkit-scrollbar-track { background: transparent; }
    .lib-scroll::-webkit-scrollbar-thumb { background: linear-gradient(90deg,#0056b3,#218838); border-radius: 9999px; }

    @media (prefers-reduced-motion: reduce) {
        .lib-card, .cover-sheen::after, .lib-fade-up { transition: none !important; animation: none !important; }
        .lib-card:hover { transform: none; }
    }
</style>
@endpush

@section('content')

@php
    // Deterministic per-category cover palette so each collection stays visually
    // distinct while remaining on-brand.
    $gradientFor = [
        'sahih'   => 'from-emerald-700 via-emerald-800 to-emerald-900',
        'sunan'   => 'from-blue-700 via-blue-800 to-blue-900',
        'masanid' => 'from-cyan-700 via-cyan-800 to-cyan-900',
        'arbaeen' => 'from-amber-600 via-amber-700 to-amber-800',
        'other'   => 'from-indigo-700 via-indigo-800 to-indigo-900',
    ];
    $spineFor = [
        'sahih'   => '#10b981',
        'sunan'   => '#3b82f6',
        'masanid' => '#06b6d4',
        'arbaeen' => '#f59e0b',
        'other'   => '#6366f1',
    ];

    $alpineBooks = [];
    foreach (array_values($books) as $book) {
        $cat = $book['category'] ?? 'other';
        $alpineBooks[] = [
            'id' => $book['id'],
            'slug' => $book['slug'],
            'title' => $book['nameAr'] ?: $book['name'],
            'subtitle' => $book['nameEn'] ?? '',
            'author' => $book['author'] ?? '',
            'description' => $book['description'] ?? '',
            'category' => $cat,
            'hadithCount' => (int) ($book['hadithCount'] ?? 0),
            'bookCount' => (int) ($book['bookCount'] ?? 0),
            'featured' => (bool) ($book['featured'] ?? false),
            'coverGradient' => $gradientFor[$cat] ?? $gradientFor['other'],
            'spineColor' => $spineFor[$cat] ?? $spineFor['other'],
        ];
    }

    $alpineCategories = array_map(static fn ($c) => [
        'id' => $c['id'],
        'name' => $c['label'],
        'icon' => $c['icon'],
        'count' => $c['count'],
    ], array_values($categories));

    $booksJson = json_encode($alpineBooks, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
    $categoriesJson = json_encode($alpineCategories, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
@endphp

<div
    x-data="{
        searchQuery: '',
        selectedCategory: 'all',
        bookBase: '{{ url('/hadith/book') }}',
        books: {{ $booksJson }},
        categories: {{ $categoriesJson }},

        bookUrl(slug) { return this.bookBase + '/' + encodeURIComponent(slug); },

        get featuredBooks() { return this.books.filter(b => b.featured); },

        get filteredBooks() {
            let list = this.books;
            if (this.selectedCategory !== 'all') {
                list = list.filter(b => b.category === this.selectedCategory);
            }
            const q = this.searchQuery.trim().toLowerCase();
            if (q) {
                list = list.filter(b =>
                    (b.title || '').toLowerCase().includes(q) ||
                    (b.subtitle || '').toLowerCase().includes(q) ||
                    (b.author || '').toLowerCase().includes(q)
                );
            }
            return list;
        },

        refreshIcons() { this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); }); },

        init() {
            this.refreshIcons();
            this.$watch('filteredBooks', () => this.refreshIcons());
        }
    }"
    class="bg-[#F8F9FA] dark:bg-[#12152b] text-gray-900 dark:text-gray-100 min-h-screen"
>

    {{-- ===================== HERO ===================== --}}
    <section class="relative overflow-hidden bg-gradient-to-br from-[#0056b3] to-[#218838]">
        <div class="absolute inset-0 hero-pattern opacity-60"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-black/25 to-transparent"></div>

        <div class="relative container mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-16">
            <div class="max-w-3xl">
                <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/15 backdrop-blur-sm text-white text-xs sm:text-sm font-semibold ring-1 ring-white/25 lib-fade-up">
                    <i data-lucide="library-big" class="w-4 h-4"></i>
                    {{ __('hadith.public.breadcrumb') }}
                </span>

                <h1 class="mt-5 text-4xl md:text-5xl lg:text-6xl font-bold text-white font-amiri leading-tight lib-fade-up">
                    {{ __('hadith.public.hero_title') }}
                </h1>
                <p class="mt-3 text-base md:text-lg text-white/85 max-w-2xl leading-relaxed lib-fade-up">
                    {{ __('hadith.public.hero_subtitle') }}
                </p>

                {{-- Stat chips --}}
                <div class="mt-7 flex flex-wrap gap-3 lib-fade-up">
                    <div class="flex items-center gap-2.5 px-4 py-2.5 rounded-xl bg-white/12 backdrop-blur-sm ring-1 ring-white/15">
                        <i data-lucide="book-marked" class="w-5 h-5 text-[#a8e6e1]"></i>
                        <span class="text-xl font-bold text-white tabular-nums">{{ number_format($stats['collections']) }}</span>
                        <span class="text-sm text-white/75">{{ __('hadith.public.stat_collections') }}</span>
                    </div>
                    <div class="flex items-center gap-2.5 px-4 py-2.5 rounded-xl bg-white/12 backdrop-blur-sm ring-1 ring-white/15">
                        <i data-lucide="scroll-text" class="w-5 h-5 text-[#a8e6e1]"></i>
                        <span class="text-xl font-bold text-white tabular-nums">{{ number_format($stats['hadiths']) }}</span>
                        <span class="text-sm text-white/75">{{ __('hadith.public.stat_hadiths') }}</span>
                    </div>
                    <div class="flex items-center gap-2.5 px-4 py-2.5 rounded-xl bg-white/12 backdrop-blur-sm ring-1 ring-white/15">
                        <i data-lucide="book-open" class="w-5 h-5 text-[#a8e6e1]"></i>
                        <span class="text-xl font-bold text-white tabular-nums">{{ number_format($stats['books']) }}</span>
                        <span class="text-sm text-white/75">{{ __('hadith.public.stat_books') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="lib-pattern">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-10 md:py-14">

            {{-- ===================== FEATURED RAIL ===================== --}}
            <section x-show="featuredBooks.length > 0" x-cloak class="mb-14">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center shadow-lg shadow-amber-500/20 shrink-0">
                        <i data-lucide="sparkles" class="w-6 h-6 text-white"></i>
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-2xl font-bold font-amiri leading-tight">{{ __('hadith.public.featured_heading') }}</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400 truncate">{{ __('hadith.public.featured_subtitle') }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                    <template x-for="book in featuredBooks" :key="'f-' + book.id">
                        <a :href="bookUrl(book.slug)"
                           class="lib-card group flex rounded-2xl overflow-hidden bg-white dark:bg-[#1b1f3b] border border-gray-200/80 dark:border-white/10 shadow-sm hover:shadow-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0056b3]">
                            {{-- Spine / cover panel --}}
                            <div class="cover-sheen relative w-24 sm:w-28 shrink-0 bg-gradient-to-br flex flex-col items-center justify-center gap-3 p-3"
                                 :class="book.coverGradient">
                                <span class="absolute top-2.5 start-2.5 w-2.5 h-full" :style="`background:${book.spineColor};opacity:.35`"></span>
                                <span class="w-12 h-12 rounded-full bg-white/15 ring-2 ring-white/40 flex items-center justify-center backdrop-blur-sm">
                                    <i data-lucide="book-open-text" class="w-6 h-6 text-white"></i>
                                </span>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-400 text-amber-950 text-[10px] font-bold shadow">
                                    <i data-lucide="star" class="w-3 h-3 fill-current"></i>
                                    {{ __('hadith.public.featured_badge') }}
                                </span>
                            </div>

                            {{-- Body --}}
                            <div class="flex-1 min-w-0 p-4 sm:p-5 flex flex-col">
                                <h3 class="text-lg sm:text-xl font-bold font-amiri leading-snug line-clamp-2 group-hover:text-[#0056b3] dark:group-hover:text-[#64CCC5] transition-colors"
                                    x-text="book.title"></h3>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 line-clamp-1"
                                   x-show="book.author" x-text="'{{ __('hadith.public.by_author') }} ' + book.author"></p>
                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300 leading-relaxed line-clamp-2 font-amiri"
                                   x-show="book.description" x-text="book.description"></p>

                                <div class="mt-auto pt-4 flex items-center justify-between">
                                    <div class="flex items-center gap-3 text-xs text-gray-600 dark:text-gray-400">
                                        <span class="inline-flex items-center gap-1">
                                            <i data-lucide="scroll-text" class="w-3.5 h-3.5 text-[#0056b3] dark:text-[#64CCC5]"></i>
                                            <span class="tabular-nums" x-text="book.hadithCount.toLocaleString()"></span>
                                        </span>
                                        <span class="inline-flex items-center gap-1">
                                            <i data-lucide="book-open" class="w-3.5 h-3.5 text-[#218838] dark:text-[#64CCC5]"></i>
                                            <span class="tabular-nums" x-text="book.bookCount.toLocaleString()"></span>
                                        </span>
                                    </div>
                                    <span class="inline-flex items-center gap-1 text-sm font-semibold text-[#0056b3] dark:text-[#64CCC5]">
                                        {{ __('hadith.public.view_collection') }}
                                        <i data-lucide="arrow-left" class="w-4 h-4 transition-transform group-hover:-translate-x-1 rtl:group-hover:-translate-x-1 ltr:rotate-180 ltr:group-hover:translate-x-1"></i>
                                    </span>
                                </div>
                            </div>
                        </a>
                    </template>
                </div>
            </section>

            {{-- ===================== TOOLBAR ===================== --}}
            <section class="mb-8">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center shadow-lg shadow-blue-500/15 shrink-0">
                        <i data-lucide="library" class="w-6 h-6 text-white"></i>
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-2xl font-bold font-amiri leading-tight">{{ __('hadith.public.full_library_heading') }}</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400 truncate">{{ __('hadith.public.full_library_subtitle') }}</p>
                    </div>
                </div>

                {{-- Search --}}
                <div class="relative mb-5">
                    <label for="lib-search" class="sr-only">{{ __('hadith.public.search_label') }}</label>
                    <i data-lucide="search" class="absolute start-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none"></i>
                    <input id="lib-search" type="search" x-model.debounce.200ms="searchQuery"
                           placeholder="{{ __('hadith.public.search_placeholder') }}"
                           class="w-full ps-12 pe-12 py-3.5 rounded-2xl text-base sm:text-base bg-white dark:bg-[#1b1f3b] border border-gray-200 dark:border-white/10 text-gray-900 dark:text-white placeholder:text-gray-400 shadow-sm focus:outline-none focus:ring-2 focus:ring-[#0056b3] focus:border-transparent transition">
                    <button x-show="searchQuery" x-cloak @click="searchQuery = ''" type="button"
                            class="absolute end-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                            aria-label="{{ __('hadith.public.reset_search') }}">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                {{-- Category pills + result count --}}
                <div class="flex flex-wrap items-center gap-2.5">
                    <template x-for="category in categories" :key="category.id">
                        <button type="button" @click="selectedCategory = category.id"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0056b3]"
                                :class="selectedCategory === category.id
                                    ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-md'
                                    : 'bg-white dark:bg-[#1b1f3b] text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-white/10 hover:border-[#64CCC5] hover:text-[#0056b3] dark:hover:text-[#64CCC5]'">
                            <i :data-lucide="category.icon" class="w-4 h-4"></i>
                            <span x-text="category.name"></span>
                            <span class="px-1.5 py-0.5 rounded-full text-[11px] font-bold tabular-nums"
                                  :class="selectedCategory === category.id ? 'bg-white/25' : 'bg-gray-100 dark:bg-white/10'"
                                  x-text="category.count"></span>
                        </button>
                    </template>

                    <span class="ms-auto text-sm text-gray-500 dark:text-gray-400 tabular-nums"
                          x-text="`${filteredBooks.length} {{ __('hadith.public.stat_collections') }}`"></span>
                </div>
            </section>

            {{-- ===================== LIBRARY GRID ===================== --}}
            <section>
                <div x-show="filteredBooks.length > 0"
                     class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 sm:gap-5">
                    <template x-for="book in filteredBooks" :key="book.id">
                        <a :href="bookUrl(book.slug)"
                           class="lib-card group flex flex-col rounded-2xl overflow-hidden bg-white dark:bg-[#1b1f3b] border border-gray-200/80 dark:border-white/10 shadow-sm hover:shadow-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0056b3]">
                            {{-- Cover --}}
                            <div class="cover-sheen relative aspect-[5/3] bg-gradient-to-br flex items-center justify-center"
                                 :class="book.coverGradient">
                                {{-- spine accent on the start edge --}}
                                <span class="absolute start-0 top-0 bottom-0 w-1.5" :style="`background:${book.spineColor}`"></span>

                                {{-- featured star --}}
                                <span x-show="book.featured" x-cloak
                                      class="absolute top-2 start-3 w-7 h-7 rounded-full bg-amber-400 text-amber-950 flex items-center justify-center shadow ring-2 ring-white/40"
                                      title="{{ __('hadith.public.featured_badge') }}">
                                    <i data-lucide="star" class="w-3.5 h-3.5 fill-current"></i>
                                </span>

                                {{-- category badge --}}
                                <span class="absolute top-2 end-2 px-2 py-0.5 rounded-full bg-black/25 backdrop-blur-sm text-white text-[10px] font-semibold"
                                      x-text="categories.find(c => c.id === book.category)?.name || ''"></span>

                                <span class="w-12 h-12 rounded-full bg-white/15 ring-2 ring-white/30 flex items-center justify-center backdrop-blur-sm">
                                    <i data-lucide="book-open-text" class="w-6 h-6 text-white"></i>
                                </span>
                            </div>

                            {{-- Body --}}
                            <div class="flex-1 flex flex-col p-3.5">
                                <h3 class="text-sm sm:text-base font-bold font-amiri leading-snug line-clamp-2 min-h-[2.5rem] group-hover:text-[#0056b3] dark:group-hover:text-[#64CCC5] transition-colors"
                                    x-text="book.title"></h3>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 line-clamp-1"
                                   x-text="book.author || book.subtitle || ''"></p>

                                <div class="mt-auto pt-3 flex items-center justify-between border-t border-gray-100 dark:border-white/5">
                                    <div class="flex items-center gap-3 text-[11px] text-gray-600 dark:text-gray-400">
                                        <span class="inline-flex items-center gap-1">
                                            <i data-lucide="scroll-text" class="w-3.5 h-3.5 text-[#0056b3] dark:text-[#64CCC5]"></i>
                                            <span class="tabular-nums" x-text="book.hadithCount.toLocaleString()"></span>
                                        </span>
                                        <span class="inline-flex items-center gap-1">
                                            <i data-lucide="book-open" class="w-3.5 h-3.5 text-[#218838] dark:text-[#64CCC5]"></i>
                                            <span class="tabular-nums" x-text="book.bookCount.toLocaleString()"></span>
                                        </span>
                                    </div>
                                    <i data-lucide="arrow-left" class="w-4 h-4 text-[#64CCC5] transition-transform group-hover:-translate-x-1 ltr:rotate-180 ltr:group-hover:translate-x-1"></i>
                                </div>
                            </div>
                        </a>
                    </template>
                </div>

                {{-- Empty state --}}
                <div x-show="filteredBooks.length === 0" x-cloak
                     class="rounded-2xl border border-dashed border-gray-300 dark:border-white/10 bg-white/60 dark:bg-white/5 py-16 px-6 text-center">
                    <div class="w-16 h-16 mx-auto mb-5 rounded-2xl bg-gray-100 dark:bg-white/10 flex items-center justify-center">
                        <i data-lucide="search-x" class="w-8 h-8 text-gray-400"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2">{{ __('hadith.public.empty_title') }}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 max-w-md mx-auto mb-6">{{ __('hadith.public.empty_message') }}</p>
                    <button type="button" @click="searchQuery = ''; selectedCategory = 'all'"
                            class="inline-flex items-center gap-2 px-6 py-2.5 rounded-full font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-md hover:shadow-lg transition">
                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                        {{ __('hadith.public.reset_search') }}
                    </button>
                </div>
            </section>

        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => { if (window.lucide) window.lucide.createIcons(); });
</script>
@endpush

@endsection
