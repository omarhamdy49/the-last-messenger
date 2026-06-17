@extends('layouts.public')

@php
    $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true);
    $booksJson = json_encode($books, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
    $explorerBase = url('/hadith/explorer');
@endphp

@section('title', $collection['name'])
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($collection['description'] ?? $collection['name']), 155))

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

    /* Hero geometric pattern */
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

<div
    x-data="{
        bookmarked: false,
        activeTab: 'chapters',

        collectionId: {{ (int) $collection['id'] }},
        explorerBase: '{{ $explorerBase }}',
        searchQuery: '',
        books: {{ $booksJson }},

        get filteredBooks() {
            const q = this.searchQuery.trim().toLowerCase();
            if (!q) return this.books;
            return this.books.filter(b =>
                (b.title || '').toLowerCase().includes(q) ||
                (b.titleEn || '').toLowerCase().includes(q) ||
                String(b.number).includes(q)
            );
        },

        chapterUrlTemplate: '{{ route('public.hadith.chapter', ['slug' => $collection['slug'], 'chapter' => '__NUM__']) }}',

        bookHref(book) {
            if (book.number > 0) {
                return this.chapterUrlTemplate.replace('__NUM__', book.number);
            }
            return this.explorerBase + '?collection=' + this.collectionId;
        },

        toggleBookmark() {
            this.bookmarked = !this.bookmarked;
            const message = this.bookmarked
                ? '{{ __('hadith.public.book_added_favorite') }}'
                : '{{ __('hadith.public.book_removed_favorite') }}';
            if (typeof window.toast === 'function') { window.toast(message, 'success'); }
        },

        scrollToSection(section) {
            document.getElementById(section)?.scrollIntoView({ behavior: 'smooth' });
        },

        refreshIcons() {
            this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
        },

        init() {
            this.refreshIcons();
            this.$watch('activeTab', () => this.refreshIcons());
            this.$watch('filteredBooks', () => this.refreshIcons());
        }
    }"
    class="bg-[#F8F9FA] dark:bg-[#12152b] text-gray-900 dark:text-gray-100 min-h-screen"
>

    {{-- ===================== HERO ===================== --}}
    <section class="relative overflow-hidden bg-gradient-to-br from-[#0056b3] to-[#218838]">
        <div class="absolute inset-0 hero-pattern opacity-60"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-black/25 to-transparent"></div>

        <div class="relative container mx-auto px-4 sm:px-6 lg:px-8 py-10 md:py-14">

            {{-- Breadcrumb --}}
            <nav aria-label="{{ __('hadith.public.breadcrumb') }}" class="mb-7 lib-fade-up">
                <ol class="inline-flex flex-wrap items-center gap-1.5 text-sm">
                    <li>
                        <a href="{{ url('/') }}"
                           class="inline-flex items-center gap-1 text-white/70 hover:text-white transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white rounded">
                            <i data-lucide="home" class="w-4 h-4 shrink-0"></i>
                            <span>{{ __('hadith.public.home') }}</span>
                        </a>
                    </li>
                    <li aria-hidden="true">
                        <i data-lucide="chevron-left" class="w-4 h-4 text-white/50 shrink-0"></i>
                    </li>
                    <li>
                        <a href="{{ route('public.hadith') }}"
                           class="text-white/70 hover:text-white transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white rounded">
                            {{ __('hadith.public.breadcrumb') }}
                        </a>
                    </li>
                    <li aria-hidden="true">
                        <i data-lucide="chevron-left" class="w-4 h-4 text-white/50 shrink-0"></i>
                    </li>
                    <li aria-current="page">
                        <span class="font-semibold text-white truncate max-w-[16rem] inline-block align-bottom">{{ $collection['name'] }}</span>
                    </li>
                </ol>
            </nav>

            {{-- Hero body --}}
            <div class="flex flex-col md:flex-row items-start md:items-center gap-8">

                {{-- Cover illustration --}}
                <div class="shrink-0 lib-fade-up" style="animation-delay:.1s">
                    <div class="cover-sheen w-36 h-48 md:w-44 md:h-60 rounded-2xl bg-gradient-to-br from-amber-100 to-amber-50 shadow-2xl border-4 border-white/20 flex items-center justify-center relative overflow-hidden">
                        <div class="absolute start-0 top-0 bottom-0 w-3 bg-gradient-to-b from-amber-600 to-amber-800 rounded-s-2xl"></div>
                        @if ($collection['featured'])
                            <span class="absolute top-2 end-2 inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-400 text-amber-950 text-[10px] font-bold shadow ring-1 ring-amber-200">
                                <i data-lucide="star" class="w-3 h-3 fill-current shrink-0"></i>
                                {{ __('hadith.public.featured_badge') }}
                            </span>
                        @endif
                        <div class="text-center px-4">
                            <i data-lucide="book-open" class="w-14 h-14 text-amber-600 mx-auto mb-2"></i>
                            <p class="text-amber-800 font-bold text-sm font-amiri leading-snug line-clamp-3">{{ $collection['nameAr'] ?? $collection['name'] }}</p>
                        </div>
                    </div>
                </div>

                {{-- Metadata --}}
                <div class="flex-1 min-w-0 lib-fade-up" style="animation-delay:.15s">
                    <span class="inline-flex items-center gap-2 px-3.5 py-1.5 mb-4 rounded-full bg-white/15 backdrop-blur-sm text-white text-xs font-semibold ring-1 ring-white/25">
                        <i data-lucide="bookmark" class="w-4 h-4"></i>
                        {{ __('hadith.public.book_from_hadith_books') }}
                    </span>

                    <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white font-amiri leading-tight mb-2">
                        {{ $collection['nameAr'] ?? $collection['name'] }}
                    </h1>

                    @if (!empty($collection['nameEn']))
                        <p class="text-lg text-white/80 mb-1 leading-relaxed">{{ $collection['nameEn'] }}</p>
                    @endif

                    @if (!empty($collection['author']))
                        <p class="text-base text-white/75 mb-5 leading-relaxed">
                            {{ __('hadith.public.by_author') }} {{ $collection['author'] }}
                        </p>
                    @else
                        <div class="mb-5"></div>
                    @endif

                    {{-- Stat chips --}}
                    <div class="flex flex-wrap gap-3 mb-6">
                        <div class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white/12 backdrop-blur-sm ring-1 ring-white/15">
                            <i data-lucide="scroll-text" class="w-5 h-5 text-[#a8e6e1]"></i>
                            <span class="text-xl font-bold text-white tabular-nums">{{ number_format($collection['hadithCount']) }}</span>
                            <span class="text-sm text-white/75">{{ __('hadith.public.stat_hadiths') }}</span>
                        </div>
                        <div class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white/12 backdrop-blur-sm ring-1 ring-white/15">
                            <i data-lucide="folder-tree" class="w-5 h-5 text-[#a8e6e1]"></i>
                            <span class="text-xl font-bold text-white tabular-nums">{{ number_format($collection['bookCount']) }}</span>
                            <span class="text-sm text-white/75">{{ __('hadith.public.stat_books') }}</span>
                        </div>
                    </div>

                    {{-- Action buttons --}}
                    <div class="flex flex-wrap gap-3">
                        <button @click="activeTab = 'chapters'; scrollToSection('chapters')"
                                class="inline-flex items-center gap-2 px-6 py-3 rounded-full bg-white text-[#0056b3] font-bold shadow-xl hover:shadow-2xl hover:scale-105 transition-all text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white">
                            <i data-lucide="list" class="w-5 h-5"></i>
                            {{ __('hadith.public.book_browse_chapters') }}
                        </button>
                        <button @click="toggleBookmark()"
                                class="inline-flex items-center gap-2 px-6 py-3 rounded-full font-bold shadow-lg hover:scale-105 transition-all text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white"
                                :class="bookmarked ? 'bg-[#64CCC5] text-gray-900' : 'bg-white/20 backdrop-blur-sm text-white hover:bg-white/30'">
                            <i :data-lucide="bookmarked ? 'bookmark-check' : 'bookmark-plus'" class="w-5 h-5"></i>
                            <span x-text="bookmarked ? '{{ __('hadith.public.book_in_favorites') }}' : '{{ __('hadith.public.book_add_favorite') }}'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="lib-pattern">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-10 md:py-14">

            {{-- ===================== TAB NAV ===================== --}}
            <div class="mb-8 lib-fade-up" style="animation-delay:.2s">
                <div class="flex gap-3 overflow-x-auto lib-scroll pb-1">
                    <button @click="activeTab = 'about'"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full font-semibold whitespace-nowrap text-sm transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0056b3]"
                            :class="activeTab === 'about'
                                ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-md'
                                : 'bg-white dark:bg-[#1b1f3b] text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-white/10 hover:border-[#64CCC5] hover:text-[#0056b3] dark:hover:text-[#64CCC5]'">
                        <i data-lucide="info" class="w-4 h-4"></i>
                        {{ __('hadith.public.book_about') }}
                    </button>
                    <button @click="activeTab = 'chapters'; scrollToSection('chapters')"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full font-semibold whitespace-nowrap text-sm transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0056b3]"
                            :class="activeTab === 'chapters'
                                ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-md'
                                : 'bg-white dark:bg-[#1b1f3b] text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-white/10 hover:border-[#64CCC5] hover:text-[#0056b3] dark:hover:text-[#64CCC5]'">
                        <i data-lucide="folder-tree" class="w-4 h-4"></i>
                        {{ __('hadith.public.book_chapters_heading') }}
                    </button>
                    <button @click="activeTab = 'author'"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full font-semibold whitespace-nowrap text-sm transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0056b3]"
                            :class="activeTab === 'author'
                                ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-md'
                                : 'bg-white dark:bg-[#1b1f3b] text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-white/10 hover:border-[#64CCC5] hover:text-[#0056b3] dark:hover:text-[#64CCC5]'">
                        <i data-lucide="user" class="w-4 h-4"></i>
                        {{ __('hadith.public.book_author_heading') }}
                    </button>
                </div>
            </div>

            {{-- ===================== ABOUT TAB ===================== --}}
            <section x-show="activeTab === 'about'"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="mb-12 space-y-6">

                {{-- Stat cards row --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="lib-card rounded-2xl p-5 bg-white dark:bg-[#1b1f3b] border border-gray-200/80 dark:border-white/10 shadow-sm hover:shadow-xl flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center shadow-lg shadow-blue-500/15 shrink-0">
                            <i data-lucide="scroll-text" class="w-6 h-6 text-white"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-2xl font-bold tabular-nums">{{ number_format($collection['hadithCount']) }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('hadith.public.stat_hadiths') }}</p>
                        </div>
                    </div>
                    <div class="lib-card rounded-2xl p-5 bg-white dark:bg-[#1b1f3b] border border-gray-200/80 dark:border-white/10 shadow-sm hover:shadow-xl flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#218838] to-[#64CCC5] flex items-center justify-center shadow-lg shadow-green-500/15 shrink-0">
                            <i data-lucide="folder-tree" class="w-6 h-6 text-white"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-2xl font-bold tabular-nums">{{ number_format($collection['bookCount']) }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('hadith.public.stat_books') }}</p>
                        </div>
                    </div>
                    <div class="lib-card rounded-2xl p-5 bg-white dark:bg-[#1b1f3b] border border-gray-200/80 dark:border-white/10 shadow-sm hover:shadow-xl flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#64CCC5] to-[#0056b3] flex items-center justify-center shadow-lg shadow-teal-500/15 shrink-0">
                            <i data-lucide="shield-check" class="w-6 h-6 text-white"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-lg font-bold">{{ __('hadith.public.book_grade_high') }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('hadith.public.book_count_label') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Description card --}}
                <div class="rounded-2xl p-6 sm:p-8 bg-white dark:bg-[#1b1f3b] border border-gray-200/80 dark:border-white/10 shadow-sm">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center shadow-lg shadow-blue-500/15 shrink-0">
                            <i data-lucide="book-open-text" class="w-6 h-6 text-white"></i>
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-2xl font-bold font-amiri leading-tight">{{ __('hadith.public.book_about') }}</h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('hadith.public.book_about_subtitle') }}</p>
                        </div>
                    </div>

                    @if (!empty($collection['description']))
                        <p class="text-base sm:text-lg leading-relaxed text-gray-700 dark:text-gray-300 font-amiri mb-6">
                            {{ $collection['description'] }}
                        </p>
                    @else
                        <p class="text-base sm:text-lg leading-relaxed text-gray-700 dark:text-gray-300 font-amiri mb-6">
                            {{ __('hadith.public.book_default_description', [
                                'name'   => $collection['nameAr'] ?? $collection['name'],
                                'author' => $collection['author'] ?? '',
                                'count'  => number_format($collection['hadithCount']),
                                'books'  => number_format($collection['bookCount']),
                            ]) }}
                        </p>
                    @endif

                    <div class="p-4 sm:p-5 rounded-xl bg-gradient-to-br from-blue-50 to-green-50 dark:from-[#0056b3]/10 dark:to-[#218838]/10 border border-blue-100 dark:border-white/5">
                        <div class="flex items-start gap-3">
                            <i data-lucide="lightbulb" class="w-5 h-5 text-[#64CCC5] shrink-0 mt-0.5"></i>
                            <p class="text-sm sm:text-base leading-relaxed text-gray-700 dark:text-gray-300">
                                {{ __('hadith.public.book_browse_tip') }}
                            </p>
                        </div>
                    </div>

                    {{-- Feature checklist --}}
                    <div class="mt-6 grid sm:grid-cols-2 gap-3">
                        @foreach ([
                            ['icon' => 'check', 'label' => __('hadith.public.book_feature_isnad'), 'sub' => __('hadith.public.book_feature_isnad_sub')],
                            ['icon' => 'check', 'label' => __('hadith.public.book_feature_fiqh'), 'sub' => __('hadith.public.book_feature_fiqh_sub')],
                            ['icon' => 'check', 'label' => __('hadith.public.book_feature_ilal'), 'sub' => __('hadith.public.book_feature_ilal_sub')],
                            ['icon' => 'check', 'label' => __('hadith.public.book_feature_reference'), 'sub' => __('hadith.public.book_feature_reference_sub')],
                        ] as $feat)
                            <div class="flex items-center gap-3 p-3.5 rounded-xl bg-gray-50 dark:bg-white/5">
                                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center shrink-0">
                                    <i data-lucide="{{ $feat['icon'] }}" class="w-4 h-4 text-white"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-sm leading-snug truncate">{{ $feat['label'] }}</p>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 truncate">{{ $feat['sub'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- ===================== CHAPTERS TAB ===================== --}}
            <section x-show="activeTab === 'chapters'"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     id="chapters"
                     class="mb-12">

                {{-- Section header --}}
                <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-[#218838] to-[#64CCC5] flex items-center justify-center shadow-lg shadow-green-500/15 shrink-0">
                            <i data-lucide="folder-tree" class="w-6 h-6 text-white"></i>
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-2xl font-bold font-amiri leading-tight">{{ __('hadith.public.book_chapters_heading') }}</h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                <span x-text="books.length"></span>
                                {{ __('hadith.public.book_unit') }}
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Search within chapters --}}
                <div class="relative mb-6">
                    <label for="chapter-search" class="sr-only">{{ __('hadith.public.book_search_placeholder') }}</label>
                    <i data-lucide="search" class="absolute start-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none"></i>
                    <input id="chapter-search"
                           type="search"
                           x-model.debounce.200ms="searchQuery"
                           placeholder="{{ __('hadith.public.book_search_placeholder') }}"
                           class="w-full ps-12 pe-12 py-3.5 rounded-2xl text-base sm:text-sm bg-white dark:bg-[#1b1f3b] border border-gray-200 dark:border-white/10 text-gray-900 dark:text-white placeholder:text-gray-400 shadow-sm focus:outline-none focus:ring-2 focus:ring-[#0056b3] focus:border-transparent transition">
                    <button x-show="searchQuery" x-cloak
                            @click="searchQuery = ''"
                            type="button"
                            class="absolute end-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 focus-visible:outline-none"
                            aria-label="{{ __('hadith.public.reset_search') }}">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                {{-- No chapters at all (server) --}}
                @if (count($books) === 0)
                    <div class="rounded-2xl border border-dashed border-gray-300 dark:border-white/10 bg-white/60 dark:bg-white/5 py-16 px-6 text-center">
                        <div class="w-16 h-16 mx-auto mb-5 rounded-2xl bg-gray-100 dark:bg-white/10 flex items-center justify-center">
                            <i data-lucide="book-x" class="w-8 h-8 text-gray-400"></i>
                        </div>
                        <h3 class="text-xl font-bold font-amiri mb-2">{{ __('hadith.public.book_empty_title') }}</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 max-w-md mx-auto">{{ __('hadith.public.book_empty_message') }}</p>
                    </div>
                @else
                    {{-- Chapters grid --}}
                    <div x-show="filteredBooks.length > 0"
                         class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5">
                        <template x-for="(chapter, index) in filteredBooks" :key="chapter.id">
                            <a :href="bookHref(chapter)"
                               class="lib-card group flex rounded-2xl overflow-hidden bg-white dark:bg-[#1b1f3b] border border-gray-200/80 dark:border-white/10 shadow-sm hover:shadow-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0056b3]">

                                {{-- Gradient icon chip --}}
                                <div class="cover-sheen shrink-0 w-16 sm:w-20 bg-gradient-to-br from-[#0056b3] to-[#218838] flex flex-col items-center justify-center gap-2 px-2">
                                    <span class="w-9 h-9 rounded-full bg-white/15 ring-2 ring-white/30 flex items-center justify-center backdrop-blur-sm">
                                        <i data-lucide="book-open" class="w-5 h-5 text-white"></i>
                                    </span>
                                    <span class="text-[11px] font-bold text-white/80 tabular-nums"
                                          x-text="chapter.number > 0 ? chapter.number : (index + 1)"></span>
                                </div>

                                {{-- Card body --}}
                                <div class="flex-1 min-w-0 p-4 flex flex-col">
                                    <h3 class="text-sm sm:text-base font-bold font-amiri leading-snug line-clamp-2 group-hover:text-[#0056b3] dark:group-hover:text-[#64CCC5] transition-colors"
                                        x-text="chapter.title || ('{{ __('hadith.public.book_chapter_default') }} ' + (chapter.number > 0 ? chapter.number : (index + 1)))"></h3>

                                    <div class="mt-auto pt-3 flex items-center justify-between border-t border-gray-100 dark:border-white/5">
                                        <div class="inline-flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-400">
                                            <i data-lucide="scroll-text" class="w-3.5 h-3.5 text-[#0056b3] dark:text-[#64CCC5]"></i>
                                            <span class="tabular-nums" x-text="Number(chapter.hadithCount).toLocaleString()"></span>
                                            <span>{{ __('hadith.public.hadith_unit') }}</span>
                                        </div>
                                        <i data-lucide="arrow-left" class="w-4 h-4 text-[#64CCC5] transition-transform group-hover:-translate-x-1 ltr:rotate-180 ltr:group-hover:translate-x-1"></i>
                                    </div>
                                </div>
                            </a>
                        </template>
                    </div>

                    {{-- Empty state (client-side search) --}}
                    <div x-show="filteredBooks.length === 0" x-cloak
                         class="rounded-2xl border border-dashed border-gray-300 dark:border-white/10 bg-white/60 dark:bg-white/5 py-16 px-6 text-center">
                        <div class="w-16 h-16 mx-auto mb-5 rounded-2xl bg-gray-100 dark:bg-white/10 flex items-center justify-center">
                            <i data-lucide="search-x" class="w-8 h-8 text-gray-400"></i>
                        </div>
                        <h3 class="text-xl font-bold font-amiri mb-2">{{ __('hadith.public.book_empty_title') }}</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 max-w-md mx-auto mb-6">{{ __('hadith.public.book_empty_message') }}</p>
                        <button type="button"
                                @click="searchQuery = ''"
                                class="inline-flex items-center gap-2 px-6 py-2.5 rounded-full font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-md hover:shadow-lg transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0056b3]">
                            <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                            {{ __('hadith.public.reset_search') }}
                        </button>
                    </div>
                @endif

                {{-- Server pagination --}}
                @isset($paginator)
                    @if ($paginator)
                        <div class="mt-8">
                            <x-site.pagination :paginator="$paginator" />
                        </div>
                    @endif
                @endisset
            </section>

            {{-- ===================== AUTHOR TAB ===================== --}}
            <section x-show="activeTab === 'author'"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="mb-12">

                <div class="rounded-2xl overflow-hidden bg-white dark:bg-[#1b1f3b] border border-gray-200/80 dark:border-white/10 shadow-sm">
                    {{-- Author banner --}}
                    <div class="relative h-40 bg-gradient-to-br from-[#0056b3] to-[#218838] overflow-hidden">
                        <div class="absolute inset-0 hero-pattern opacity-50"></div>
                    </div>

                    <div class="px-6 sm:px-8 pb-8">
                        {{-- Avatar --}}
                        <div class="flex justify-center -mt-14 mb-5">
                            <div class="w-28 h-28 rounded-full bg-gradient-to-br from-amber-100 to-amber-50 shadow-2xl border-4 border-white dark:border-[#1b1f3b] flex items-center justify-center">
                                <i data-lucide="user" class="w-14 h-14 text-amber-600"></i>
                            </div>
                        </div>

                        {{-- Name & subtitle --}}
                        <div class="text-center mb-8">
                            <h2 class="text-2xl sm:text-3xl font-bold font-amiri mb-1">
                                {{ $collection['author'] ?: __('hadith.public.book_author_heading') }}
                            </h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ __('hadith.public.book_author_of') }} {{ $collection['nameAr'] ?? $collection['name'] }}
                            </p>
                        </div>

                        {{-- Biography --}}
                        <div class="rounded-xl p-4 sm:p-5 bg-gradient-to-br from-blue-50 to-green-50 dark:from-[#0056b3]/10 dark:to-[#218838]/10 border border-blue-100 dark:border-white/5 mb-6">
                            <div class="flex items-start gap-3">
                                <i data-lucide="award" class="w-5 h-5 text-[#64CCC5] shrink-0 mt-0.5"></i>
                                <p class="text-sm sm:text-base leading-relaxed text-gray-700 dark:text-gray-300 font-amiri">
                                    @if (!empty($collection['author']))
                                        {{ __('hadith.public.book_author_bio', [
                                            'author' => $collection['author'],
                                            'name'   => $collection['nameAr'] ?? $collection['name'],
                                            'count'  => number_format($collection['hadithCount']),
                                        ]) }}
                                    @else
                                        {{ __('hadith.public.book_author_bio_no_author', [
                                            'count' => number_format($collection['hadithCount']),
                                            'books' => number_format($collection['bookCount']),
                                        ]) }}
                                    @endif
                                </p>
                            </div>
                        </div>

                        {{-- Metadata grid --}}
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div class="flex items-start gap-3 p-4 rounded-xl bg-gray-50 dark:bg-white/5">
                                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center shrink-0">
                                    <i data-lucide="book-marked" class="w-4 h-4 text-white"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-sm leading-snug mb-0.5">{{ __('hadith.public.book_about') }}</p>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 truncate">
                                        {{ $collection['nameAr'] ?? $collection['name'] }}
                                        @if (!empty($collection['nameEn'])) ({{ $collection['nameEn'] }}) @endif
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 p-4 rounded-xl bg-gray-50 dark:bg-white/5">
                                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center shrink-0">
                                    <i data-lucide="scroll-text" class="w-4 h-4 text-white"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-sm leading-snug mb-0.5">{{ __('hadith.public.stat_hadiths') }}</p>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 tabular-nums">{{ number_format($collection['hadithCount']) }} {{ __('hadith.public.hadith_unit') }}</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 p-4 rounded-xl bg-gray-50 dark:bg-white/5">
                                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center shrink-0">
                                    <i data-lucide="folder-tree" class="w-4 h-4 text-white"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-sm leading-snug mb-0.5">{{ __('hadith.public.stat_books') }}</p>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 tabular-nums">{{ number_format($collection['bookCount']) }} {{ __('hadith.public.book_unit') }}</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 p-4 rounded-xl bg-gray-50 dark:bg-white/5">
                                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center shrink-0">
                                    <i data-lucide="list" class="w-4 h-4 text-white"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-sm leading-snug mb-0.5">{{ __('hadith.public.book_browse_chapters') }}</p>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">{{ __('hadith.public.book_browse_tip_short') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

        </div>
    </div>

</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) window.lucide.createIcons();
    });
</script>
@endpush

@endsection
