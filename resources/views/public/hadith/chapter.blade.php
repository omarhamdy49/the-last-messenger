@extends('layouts.public')

@php
    $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true);
    $chapterUrl = route('public.hadith.chapter', ['slug' => $collection['slug'], 'chapter' => $chapter['number']]);

    // Build the grading-pill URLs preserving the active search + per-page.
    $pillQuery = static function (?string $grading) use ($filters): string {
        $params = array_filter([
            'q' => $filters['q'] !== '' ? $filters['q'] : null,
            'per_page' => $filters['per_page'] !== 15 ? $filters['per_page'] : null,
            'grading' => $grading,
        ], fn ($value) => $value !== null);

        return $params === [] ? '' : ('?'.http_build_query($params));
    };

    $metaTitle = trim($chapter['title'].' - '.$collection['name']);

    // Whether the page-level "show translation" control is worth rendering — true when
    // at least one hadith on the page has an opposite-language translation. The text
    // itself is fetched lazily (see loadTranslations) so it never loads up front.
    $anyTranslation = ! empty($translatableIds);
@endphp

@section('title', $metaTitle)
@section('meta_description', \Illuminate\Support\Str::limit($chapter['title'].' - '.$collection['name'].' - '.__('hadith.public.page_description'), 155))

@push('head')
<style>
    [x-cloak] { display: none !important; }

    .hadith-arabic {
        font-family: 'Amiri', serif;
        line-height: 2.2;
    }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .fade-in-up { animation: fadeInUp 0.5s ease-out; }

    @media (prefers-reduced-motion: reduce) {
        .fade-in-up { animation: none; }
    }

    .skeleton-pulse {
        animation: skeletonPulse 1.4s ease-in-out infinite;
    }

    @keyframes skeletonPulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.45; }
    }
</style>
@endpush

@section('content')

<div x-data="{
        darkMode: ($store.theme && $store.theme.dark) ?? (localStorage.getItem('darkMode') === 'true'),
        loading: false,
        density: localStorage.getItem('hadithChapterDensity') || 'normal',
        // Per request: translations are hidden on every page load (not persisted).
        // Per-hadith open state; the toolbar control just flips them all at once.
        openTranslations: @js((object) array_fill_keys(array_values($translatableIds), false)),
        // Lazy translation payload — fetched on first toggle, never on page load.
        translationsUrl: '{{ route('public.hadith.translations') }}',
        altLang: @js($altLang),
        translatableIds: @js(array_values($translatableIds)),
        translations: {},
        translationsLoading: false,
        translationsLoaded: false,
        get anyTranslationOpen() { return this.translatableIds.some(id => this.openTranslations[id]); },

        setDensity(value) {
            this.density = value;
            localStorage.setItem('hadithChapterDensity', value);
        },

        async loadTranslations() {
            if (this.translationsLoaded || this.translationsLoading || this.translatableIds.length === 0) return;
            this.translationsLoading = true;
            try {
                const url = new URL(this.translationsUrl, window.location.origin);
                this.translatableIds.forEach(id => url.searchParams.append('ids[]', id));
                url.searchParams.set('lang', this.altLang);
                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                if (res.ok) {
                    const data = await res.json();
                    this.translations = data.translations || {};
                    this.translationsLoaded = true;
                }
            } catch (e) {
                // Leave translationsLoaded false so a later toggle retries.
            } finally {
                this.translationsLoading = false;
                this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
            }
        },

        toggleAllTranslations() {
            const target = ! this.anyTranslationOpen;
            if (target) this.loadTranslations();
            this.translatableIds.forEach(id => { this.openTranslations[id] = target; });
            this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
        },

        toggleHadithTranslation(id) {
            this.openTranslations[id] = ! this.openTranslations[id];
            if (this.openTranslations[id]) this.loadTranslations();
            this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
        },

        startLoading() { this.loading = true; },

        init() {
            if (this.$store.theme) { this.$watch('$store.theme.dark', v => this.darkMode = v); }
            this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
            this.$watch('loading', () => this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); }));
        }
    }">

    <main class="py-8 md:py-12">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Breadcrumb -->
            <nav class="flex mb-8 text-sm" aria-label="Breadcrumb">
                <ol class="inline-flex items-center gap-2 flex-wrap">
                    <li>
                        <a href="{{ url('/') }}" class="flex items-center gap-1 transition-colors"
                           :class="darkMode ? 'text-gray-400 hover:text-[#64CCC5]' : 'text-gray-500 hover:text-[#0056b3]'">
                            <i data-lucide="home" class="w-4 h-4"></i>
                        </a>
                    </li>
                    <li><i data-lucide="{{ $isRtl ? 'chevron-left' : 'chevron-right' }}" class="w-4 h-4 text-gray-400"></i></li>
                    <li>
                        <a href="{{ route('public.hadith') }}" class="transition-colors"
                           :class="darkMode ? 'text-gray-400 hover:text-[#64CCC5]' : 'text-gray-500 hover:text-[#0056b3]'">
                            {{ __('hadith.public.breadcrumb') }}
                        </a>
                    </li>
                    <li><i data-lucide="{{ $isRtl ? 'chevron-left' : 'chevron-right' }}" class="w-4 h-4 text-gray-400"></i></li>
                    <li>
                        <a href="{{ route('public.hadith.book', $collection['slug']) }}" class="transition-colors max-w-[40vw] truncate inline-block align-bottom"
                           :class="darkMode ? 'text-gray-400 hover:text-[#64CCC5]' : 'text-gray-500 hover:text-[#0056b3]'">
                            {{ $collection['name'] }}
                        </a>
                    </li>
                    <li><i data-lucide="{{ $isRtl ? 'chevron-left' : 'chevron-right' }}" class="w-4 h-4 text-gray-400"></i></li>
                    <li class="max-w-[45vw] truncate font-semibold" :class="darkMode ? 'text-gray-200' : 'text-gray-900'">
                        {{ $chapter['title'] ?: __('hadith.public.chapter_breadcrumb').' '.$chapter['number'] }}
                    </li>
                </ol>
            </nav>

            <!-- Chapter Header -->
            <header class="rounded-2xl p-6 md:p-8 mb-8 shadow-xl fade-in-up bg-gradient-to-br from-[#0056b3] to-[#218838] text-white">
                <div class="flex flex-wrap items-center gap-3 mb-3">
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-white/15 backdrop-blur-sm">
                        {{ $collection['name'] }}
                    </span>
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-white/15 backdrop-blur-sm">
                        {{ __('hadith.public.chapter_breadcrumb') }} {{ $chapter['number'] }}
                    </span>
                </div>
                <h1 class="text-2xl md:text-4xl font-bold mb-2 break-words">
                    {{ $chapter['title'] ?: __('hadith.public.chapter_breadcrumb').' '.$chapter['number'] }}
                </h1>
                <p class="text-white/85 text-sm md:text-base">
                    {{ number_format($chapter['hadithCount']) }} {{ __('hadith.public.chapter_hadith_unit') }}
                </p>

                <!-- Prev / Index / Next -->
                <div class="flex flex-wrap items-center gap-3 mt-6">
                    @if ($navigation['previous'])
                        <a href="{{ route('public.hadith.chapter', ['slug' => $collection['slug'], 'chapter' => $navigation['previous']['number']]) }}"
                           @click="startLoading"
                           class="flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold bg-white/15 hover:bg-white/25 transition-colors max-w-full">
                            <i data-lucide="{{ $isRtl ? 'chevron-right' : 'chevron-left' }}" class="w-4 h-4 flex-shrink-0"></i>
                            <span class="truncate max-w-[10rem]">{{ $navigation['previous']['title'] ?: __('hadith.public.chapter_prev') }}</span>
                        </a>
                    @endif

                    <a href="{{ route('public.hadith.book', $collection['slug']) }}"
                       class="flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold bg-white/15 hover:bg-white/25 transition-colors">
                        <i data-lucide="list" class="w-4 h-4"></i>
                        {{ __('hadith.public.chapter_index') }}
                    </a>

                    @if ($navigation['next'])
                        <a href="{{ route('public.hadith.chapter', ['slug' => $collection['slug'], 'chapter' => $navigation['next']['number']]) }}"
                           @click="startLoading"
                           class="flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold bg-white/15 hover:bg-white/25 transition-colors max-w-full">
                            <span class="truncate max-w-[10rem]">{{ $navigation['next']['title'] ?: __('hadith.public.chapter_next') }}</span>
                            <i data-lucide="{{ $isRtl ? 'chevron-left' : 'chevron-right' }}" class="w-4 h-4 flex-shrink-0"></i>
                        </a>
                    @endif
                </div>
            </header>

            <!-- Toolbar: search + grading pills + density -->
            <section class="rounded-2xl p-4 md:p-6 mb-8 shadow-lg fade-in-up" :class="darkMode ? 'bg-gray-900' : 'bg-white'">
                <form method="GET" action="{{ $chapterUrl }}" @submit="startLoading" class="flex flex-col lg:flex-row gap-4">
                    @if ($filters['grading'] !== '')
                        <input type="hidden" name="grading" value="{{ $filters['grading'] }}">
                    @endif
                    <input type="hidden" name="per_page" value="{{ $filters['per_page'] }}">

                    <div class="flex-1 relative">
                        <label for="chapter-search" class="sr-only">{{ __('hadith.public.chapter_search_label') }}</label>
                        <input id="chapter-search" type="search" name="q" value="{{ $filters['q'] }}"
                               placeholder="{{ __('hadith.public.chapter_search_placeholder') }}"
                               class="w-full px-5 py-3 ps-12 rounded-full text-base sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-all"
                               :class="darkMode ? 'bg-gray-800 text-white placeholder-gray-500' : 'bg-gray-50 text-gray-900 border border-gray-200'" />
                        <i data-lucide="search" class="absolute start-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none"></i>
                    </div>

                    <button type="submit"
                            class="px-6 py-3 rounded-full font-bold text-white bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2">
                        <i data-lucide="search" class="w-4 h-4"></i>
                        {{ __('hadith.public.chapter_apply') }}
                    </button>
                </form>

                <div class="flex flex-wrap items-center justify-between gap-4 mt-4">
                    <!-- Authenticity filter pills -->
                    <div class="flex flex-wrap items-center gap-2" role="group" aria-label="{{ __('hadith.public.explorer_grading') }}">
                        <a href="{{ $chapterUrl }}{{ $pillQuery(null) }}" @click="startLoading"
                           class="px-4 py-2 rounded-full text-sm font-semibold transition-all {{ $filters['grading'] === '' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-md' : '' }}"
                           @if ($filters['grading'] !== '') :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'" @endif>
                            {{ __('hadith.public.chapter_filter_all') }}
                        </a>
                        @foreach ($gradingOptions as $option)
                            <a href="{{ $chapterUrl }}{{ $pillQuery($option['value']) }}" @click="startLoading"
                               class="px-4 py-2 rounded-full text-sm font-semibold transition-all
                                   {{ $filters['grading'] === $option['value']
                                       ? ($option['variant'] === 'sahih' ? 'bg-green-600 text-white shadow-md'
                                           : ($option['variant'] === 'hasan' ? 'bg-amber-500 text-white shadow-md' : 'bg-gray-500 text-white shadow-md'))
                                       : '' }}"
                               @if ($filters['grading'] !== $option['value']) :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'" @endif>
                                {{ $option['label'] }}
                            </a>
                        @endforeach
                    </div>

                  <div class="flex flex-wrap items-center gap-3">
                    @if ($anyTranslation)
                        <!-- Show/hide ALL translations on the page (default off) -->
                        <button type="button" @click="toggleAllTranslations()" :aria-pressed="anyTranslationOpen"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-full text-xs font-semibold transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0056b3]"
                                :class="anyTranslationOpen
                                    ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow'
                                    : (darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-600 hover:bg-gray-200')">
                            <i data-lucide="languages" class="w-4 h-4"></i>
                            <span x-text="anyTranslationOpen ? @js(__('hadith.public.chapter_hide_all_translations')) : @js(__('hadith.public.chapter_show_all_translations'))"></span>
                        </button>
                    @endif

                    <!-- Density toggle -->
                    <div class="flex items-center gap-2">
                        <span class="text-xs hidden sm:inline" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                            {{ __('hadith.public.chapter_view_density') }}
                        </span>
                        <div class="flex rounded-full p-1" :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
                            <button type="button" @click="setDensity('compact')" :aria-pressed="density === 'compact'"
                                    title="{{ __('hadith.public.chapter_view_compact') }}"
                                    class="px-3 py-1.5 rounded-full text-xs font-semibold transition-all"
                                    :class="density === 'compact' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow' : (darkMode ? 'text-gray-400 hover:text-gray-200' : 'text-gray-500 hover:text-gray-800')">
                                <i data-lucide="rows-4" class="w-4 h-4"></i>
                            </button>
                            <button type="button" @click="setDensity('normal')" :aria-pressed="density === 'normal'"
                                    title="{{ __('hadith.public.chapter_view_normal') }}"
                                    class="px-3 py-1.5 rounded-full text-xs font-semibold transition-all"
                                    :class="density === 'normal' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow' : (darkMode ? 'text-gray-400 hover:text-gray-200' : 'text-gray-500 hover:text-gray-800')">
                                <i data-lucide="rows-3" class="w-4 h-4"></i>
                            </button>
                            <button type="button" @click="setDensity('expanded')" :aria-pressed="density === 'expanded'"
                                    title="{{ __('hadith.public.chapter_view_expanded') }}"
                                    class="px-3 py-1.5 rounded-full text-xs font-semibold transition-all"
                                    :class="density === 'expanded' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow' : (darkMode ? 'text-gray-400 hover:text-gray-200' : 'text-gray-500 hover:text-gray-800')">
                                <i data-lucide="rows-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                  </div>
                </div>
            </section>

            <!-- Skeleton (shown while navigating between pages/filters) -->
            <div x-show="loading" x-cloak class="space-y-4" aria-hidden="true">
                <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('hadith.public.chapter_loading') }}</p>
                @foreach (range(1, 4) as $i)
                    <div class="rounded-2xl p-6 shadow skeleton-pulse" :class="darkMode ? 'bg-gray-900' : 'bg-white'">
                        <div class="h-5 w-28 rounded-full mb-4" :class="darkMode ? 'bg-gray-800' : 'bg-gray-200'"></div>
                        <div class="h-4 w-full rounded mb-2" :class="darkMode ? 'bg-gray-800' : 'bg-gray-200'"></div>
                        <div class="h-4 w-3/4 rounded" :class="darkMode ? 'bg-gray-800' : 'bg-gray-200'"></div>
                    </div>
                @endforeach
            </div>

            <!-- Results -->
            <div x-show="!loading">
                @if (count($hadiths) > 0)
                    <div class="space-y-4">
                        @foreach ($hadiths as $hadith)
                            <article class="rounded-2xl shadow-lg hover:shadow-xl transition-all fade-in-up overflow-hidden"
                                     :class="[darkMode ? 'bg-gray-900' : 'bg-white', density === 'compact' ? 'p-4' : (density === 'expanded' ? 'p-6 md:p-8' : 'p-5 md:p-6')]">
                                <div class="flex flex-wrap items-center gap-2 mb-3">
                                    <span class="inline-flex items-center justify-center min-w-[2.5rem] px-2 h-10 rounded-xl text-sm font-bold text-white bg-gradient-to-br from-[#0056b3] to-[#218838]">
                                        {{ $hadith['number'] }}
                                    </span>

                                    @php $variant = $hadith['grading']['variant']; @endphp
                                    <span class="px-3 py-1 rounded-full text-xs font-bold
                                        {{ $variant === 'sahih' ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300'
                                            : ($variant === 'hasan' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300'
                                            : ($variant === 'daif' ? 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                                            : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400')) }}">
                                        {{ $hadith['grading']['label'] }}
                                    </span>

                                    @if ($hadith['bookTitle'])
                                        <span class="text-xs truncate max-w-[12rem]" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                                            {{ $hadith['bookTitle'] }}
                                        </span>
                                    @endif
                                </div>

                                @php
                                    $primaryText = $hadith['primaryText'] ?? $hadith['text'];
                                    $primaryRtl = in_array($hadith['primaryLang'] ?? 'ar', ['ar', 'ur'], true);
                                @endphp

                                @if ($primaryText)
                                    <p dir="{{ $primaryRtl ? 'rtl' : 'ltr' }}"
                                       class="break-words {{ $primaryRtl ? 'hadith-arabic' : 'leading-relaxed' }}"
                                       :class="[darkMode ? 'text-gray-100' : 'text-gray-900',
                                                density === 'compact' ? 'text-base line-clamp-2'
                                                    : (density === 'expanded' ? 'text-xl' : 'text-lg line-clamp-3')]">
                                        {{ $primaryText }}
                                    </p>
                                @else
                                    <p class="text-sm italic" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                                        {{ __('hadith.public.single_no_translation') }}
                                    </p>
                                @endif

                                @if (in_array($hadith['id'], $translatableIds, true))
                                    {{-- Per-hadith translation toggle (the toolbar control flips all of these at once). --}}
                                    <button type="button" @click="toggleHadithTranslation({{ $hadith['id'] }})"
                                            :aria-expanded="openTranslations[{{ $hadith['id'] }}]"
                                            class="mt-3 inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-full transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0056b3]"
                                            :class="openTranslations[{{ $hadith['id'] }}]
                                                ? 'bg-[#0056b3]/10 text-[#0056b3] dark:bg-[#64CCC5]/15 dark:text-[#64CCC5]'
                                                : (darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-600 hover:bg-gray-200')">
                                        <i data-lucide="languages" class="w-3.5 h-3.5"></i>
                                        <span x-text="openTranslations[{{ $hadith['id'] }}] ? @js(__('hadith.public.chapter_hide_translation')) : @js(__('hadith.public.chapter_show_translation'))"></span>
                                        <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold" :class="darkMode ? 'bg-white/10' : 'bg-black/5'">{{ $altLabel }}</span>
                                    </button>

                                    {{-- Translation — text fetched lazily on first open. --}}
                                    <div x-show="openTranslations[{{ $hadith['id'] }}]" x-collapse x-cloak
                                         class="mt-3 pt-3 border-t" :class="darkMode ? 'border-gray-800' : 'border-gray-100'">
                                        <div class="flex items-center gap-1.5 mb-1.5 text-[11px] font-bold text-[#0056b3] dark:text-[#64CCC5]">
                                            <i data-lucide="book-open" class="w-3.5 h-3.5"></i>
                                            {{ $altLabel }}
                                            <span x-show="translationsLoading && !translations[{{ $hadith['id'] }}]" class="font-normal text-gray-400">
                                                <i data-lucide="loader-2" class="w-3 h-3 animate-spin"></i>
                                            </span>
                                        </div>
                                        <p dir="{{ $altRtl ? 'rtl' : 'ltr' }}"
                                           class="break-words {{ $altRtl ? 'hadith-arabic text-lg' : 'leading-relaxed' }}"
                                           :class="darkMode ? 'text-gray-300' : 'text-gray-700'"
                                           x-text="translations[{{ $hadith['id'] }}] || ''"></p>
                                    </div>
                                @endif

                                <div class="mt-4 pt-4 border-t flex items-center justify-between" :class="darkMode ? 'border-gray-800' : 'border-gray-100'">
                                    <a href="{{ route('public.hadith.show', $hadith['id']) }}"
                                       class="inline-flex items-center gap-2 text-sm font-semibold text-[#0056b3] dark:text-[#64CCC5] hover:underline">
                                        {{ __('hadith.public.chapter_read') }}
                                        <i data-lucide="{{ $isRtl ? 'arrow-left' : 'arrow-right' }}" class="w-4 h-4"></i>
                                    </a>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <!-- Pagination + per-page -->
                    <div class="mt-8 rounded-2xl p-4 md:p-6 shadow-lg" :class="darkMode ? 'bg-gray-900' : 'bg-white'">
                        <div class="flex flex-col sm:flex-row flex-wrap items-center justify-between gap-4">
                            <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                {{ __('hadith.public.chapter_showing', [
                                    'from' => number_format($paginator?->firstItem() ?? 0),
                                    'to' => number_format($paginator?->lastItem() ?? 0),
                                    'total' => number_format($total),
                                ]) }}
                            </p>

                            <form method="GET" action="{{ $chapterUrl }}" @submit="startLoading" class="flex items-center gap-2">
                                @if ($filters['q'] !== '')
                                    <input type="hidden" name="q" value="{{ $filters['q'] }}">
                                @endif
                                @if ($filters['grading'] !== '')
                                    <input type="hidden" name="grading" value="{{ $filters['grading'] }}">
                                @endif
                                <label for="chapter-per-page" class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                    {{ __('hadith.public.chapter_per_page') }}
                                </label>
                                <select id="chapter-per-page" name="per_page" onchange="this.form.submit()"
                                        class="rounded-lg px-3 py-2 text-base sm:text-sm focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                                        :class="darkMode ? 'bg-gray-800 text-white border border-gray-700' : 'bg-gray-50 text-gray-900 border border-gray-200'">
                                    @foreach ($perPageOptions as $option)
                                        <option value="{{ $option }}" @selected($filters['per_page'] === $option)>{{ $option }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </div>

                        @if ($paginator)
                            <x-site.pagination :paginator="$paginator" />
                        @endif
                    </div>
                @else
                    <!-- Empty state -->
                    <div class="rounded-2xl p-12 text-center shadow-lg fade-in-up" :class="darkMode ? 'bg-gray-900' : 'bg-white'">
                        <i data-lucide="search-x" class="w-12 h-12 mx-auto mb-4 text-gray-400"></i>
                        <h2 class="text-xl font-bold mb-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                            {{ __('hadith.public.chapter_empty_title') }}
                        </h2>
                        <p class="text-sm mb-6" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            {{ __('hadith.public.chapter_empty_message') }}
                        </p>
                        <a href="{{ $chapterUrl }}" @click="startLoading"
                           class="inline-flex items-center gap-2 px-6 py-3 rounded-full font-bold text-white bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-md hover:shadow-lg transition-all">
                            <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                            {{ __('hadith.public.chapter_reset_filters') }}
                        </a>
                    </div>
                @endif
            </div>

        </div>
    </main>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') { lucide.createIcons(); }
    });
</script>
@endpush

@endsection
