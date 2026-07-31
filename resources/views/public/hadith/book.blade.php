@extends('layouts.public')

@php
    $locale = app()->getLocale();
    $isRtl = in_array($locale, ['ar', 'ur'], true);
    $collectionName = $collection->getTranslation('name', $locale, true);
    $bookTitle = $book->getTranslation('title', $locale, true);
    $intro = $book->getTranslation('intro', $locale, true);

    // Icons point the way the reader travels, which flips with the script.
    $backIcon = $isRtl ? 'chevron-right' : 'chevron-left';
    $forwardIcon = $isRtl ? 'chevron-left' : 'chevron-right';

    $grades = ['sahih', 'hasan', 'daif', 'mawdu', 'unknown'];
@endphp

@section('title', $bookTitle.' — '.$collectionName)
@section('meta_description', \Illuminate\Support\Str::limit($intro ?: $bookTitle.' — '.$collectionName, 155))

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">

        <x-site.breadcrumb :items="[
            ['label' => __('hadith.hadiths'), 'url' => route('public.hadith')],
            ['label' => $collectionName, 'url' => route('public.hadith.collection', $collection->code)],
            ['label' => $bookTitle],
        ]" />

        {{-- ── Book header ──────────────────────────────────────────────────── --}}
        <section class="relative overflow-hidden rounded-2xl mt-6 p-6 sm:p-8 shadow-xl animate-scale-in
                        bg-white dark:bg-[#16213e] border border-gray-100 dark:border-gray-800">

            <div class="pointer-events-none absolute -top-16 -end-16 w-64 h-64 rounded-full blur-3xl
                        bg-gradient-to-br from-[#0056b3]/15 to-[#218838]/15" aria-hidden="true"></div>

            <div class="relative">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-3">
                            <span class="grid place-items-center shrink-0 w-12 h-12 rounded-xl
                                         bg-gradient-to-br from-[#0056b3] to-[#218838]">
                                <i data-lucide="book-open" class="w-6 h-6 text-white" aria-hidden="true"></i>
                            </span>
                            <div class="min-w-0">
                                <a href="{{ route('public.hadith.collection', $collection->code) }}"
                                   class="block text-sm font-semibold text-[#0056b3] dark:text-[#64CCC5] truncate hover:underline">
                                    {{ $collectionName }}
                                </a>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ __('hadith.book') }} {{ $book->book_number }}
                                </p>
                            </div>
                        </div>

                        <h1 class="mt-5 text-2xl sm:text-4xl font-bold font-['Amiri']
                                   text-gray-900 dark:text-white break-words">
                            {{ $bookTitle }}
                        </h1>

                        @if ($intro)
                            <p class="mt-3 text-sm sm:text-base leading-8 text-gray-600 dark:text-gray-300 break-words">
                                {{ $intro }}
                            </p>
                        @endif
                    </div>

                    <div class="shrink-0 text-center">
                        <p class="text-3xl sm:text-4xl font-black text-gradient tabular-nums">
                            {{ number_format($paginator->total()) }}
                        </p>
                        <p class="text-xs text-gray-600 dark:text-gray-300">{{ __('hadith.hadiths') }}</p>
                    </div>
                </div>

                {{-- Book-to-book navigation --}}
                <div class="mt-6 flex flex-wrap items-center gap-2 sm:gap-3">
                    @if ($previous = $adjacent['previous'])
                        <a href="{{ route('public.hadith.book', [$collection->code, $previous->book_number]) }}"
                           class="inline-flex min-h-11 max-w-full items-center gap-2 px-4 sm:px-5 rounded-lg
                                  bg-gradient-to-r from-[#0056b3] to-[#218838] text-white text-sm font-semibold
                                  transition hover:shadow-lg focus:outline-none focus-visible:ring-2
                                  focus-visible:ring-[#218838] focus-visible:ring-offset-2">
                            <i data-lucide="{{ $backIcon }}" class="w-5 h-5 shrink-0" aria-hidden="true"></i>
                            <span class="truncate">{{ $previous->getTranslation('title', $locale, true) }}</span>
                        </a>
                    @endif

                    <a href="{{ route('public.hadith.collection', $collection->code) }}"
                       class="inline-flex min-h-11 items-center gap-2 px-4 sm:px-5 rounded-lg text-sm font-semibold
                              bg-gray-100 dark:bg-[#0f3460] text-gray-700 dark:text-gray-200
                              transition hover:bg-gray-200 dark:hover:bg-[#0f3460]/70
                              focus:outline-none focus-visible:ring-2 focus-visible:ring-[#218838]">
                        <i data-lucide="list" class="w-5 h-5" aria-hidden="true"></i>
                        {{ __('hadith.book_index') }}
                    </a>

                    @if ($next = $adjacent['next'])
                        <a href="{{ route('public.hadith.book', [$collection->code, $next->book_number]) }}"
                           class="inline-flex min-h-11 max-w-full items-center gap-2 px-4 sm:px-5 rounded-lg
                                  bg-gradient-to-r from-[#0056b3] to-[#218838] text-white text-sm font-semibold
                                  transition hover:shadow-lg focus:outline-none focus-visible:ring-2
                                  focus-visible:ring-[#218838] focus-visible:ring-offset-2">
                            <span class="truncate">{{ $next->getTranslation('title', $locale, true) }}</span>
                            <i data-lucide="{{ $forwardIcon }}" class="w-5 h-5 shrink-0" aria-hidden="true"></i>
                        </a>
                    @endif
                </div>
            </div>
        </section>

        {{-- ── Reading controls ─────────────────────────────────────────────────
             Filters narrow the hadiths already on this page, not the whole book:
             the page is server-paginated, so the note below says so plainly and
             the site-wide search is offered for anything beyond it. --}}
        <div x-data="{
                 q: '',
                 grade: '',
                 view: 'normal',
                 hit(el) {
                     const needle = window.foldForSearch(this.q);

                     return (!needle || el.dataset.text.includes(needle))
                         && (!this.grade || el.dataset.grade === this.grade);
                 },
                 get shown() {
                     return [...this.$root.querySelectorAll('[data-grade]')].filter(el => this.hit(el)).length;
                 },
             }">

            @if ($paginator->total() > 0)
                <section class="rounded-2xl mt-6 p-4 sm:p-5 shadow-lg animate-fade-in-up stagger-1
                                bg-white dark:bg-[#16213e] border border-gray-100 dark:border-gray-800">
                    <div class="flex flex-col lg:flex-row gap-4 lg:items-center lg:justify-between">

                        <div class="relative w-full lg:max-w-sm">
                            <label for="in-book-search" class="sr-only">{{ __('hadith.filter_this_page') }}</label>
                            <input id="in-book-search" type="search" x-model="q"
                                   placeholder="{{ __('hadith.filter_this_page') }}"
                                   class="w-full ps-11 pe-4 py-3 rounded-lg text-base sm:text-sm
                                          border-2 border-transparent bg-gray-100 dark:bg-[#0f3460]
                                          text-gray-900 dark:text-gray-100 placeholder:text-gray-400
                                          focus:border-[#64CCC5] focus:outline-none transition-colors">
                            <i data-lucide="search" aria-hidden="true"
                               class="pointer-events-none absolute start-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <label for="grade-filter" class="sr-only">{{ __('hadith.grade') }}</label>
                            <select id="grade-filter" x-model="grade"
                                    class="min-h-11 px-4 rounded-lg text-base sm:text-sm border-2
                                           bg-white dark:bg-[#0f3460] border-gray-200 dark:border-gray-700
                                           text-gray-900 dark:text-gray-100
                                           focus:border-[#64CCC5] focus:outline-none transition-colors">
                                <option value="">{{ __('hadith.filter_all_grades') }}</option>
                                @foreach ($grades as $grade)
                                    <option value="{{ $grade }}">{{ __('hadith.grade_'.$grade) }}</option>
                                @endforeach
                            </select>

                            {{-- Reading density --}}
                            <div class="flex items-center gap-1 p-1 rounded-lg bg-gray-100 dark:bg-[#0f3460]"
                                 role="group" aria-label="{{ __('hadith.reading_density') }}">
                                @foreach ([['compact', 'rows-3'], ['normal', 'list'], ['expanded', 'maximize']] as [$mode, $icon])
                                    <button type="button" @click="view = '{{ $mode }}'"
                                            :aria-pressed="view === '{{ $mode }}' ? 'true' : 'false'"
                                            aria-label="{{ __('hadith.view_'.$mode) }}"
                                            :class="view === '{{ $mode }}'
                                                ? 'bg-white dark:bg-[#16213e] shadow text-[#0056b3] dark:text-[#64CCC5]'
                                                : 'text-gray-500 dark:text-gray-400'"
                                            class="grid place-items-center w-11 h-11 rounded-md transition
                                                   focus:outline-none focus-visible:ring-2 focus-visible:ring-[#218838]">
                                        <i data-lucide="{{ $icon }}" class="w-4 h-4"></i>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- No link to the site-wide search here: that route requires a
                         `q` (min 2 chars), so a bare "search this collection" link
                         would bounce back with a validation error. --}}
                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                        {{ __('hadith.filter_scope_note') }}
                    </p>
                </section>

                {{-- Range line: what this page is showing out of the whole book --}}
                <p class="mt-6 mb-4 text-sm text-gray-600 dark:text-gray-300 tabular-nums">
                    {{ __('hadith.showing_range', [
                        'first' => number_format($paginator->firstItem() ?? 0),
                        'last' => number_format($paginator->lastItem() ?? 0),
                        'total' => number_format($paginator->total()),
                    ]) }}
                </p>
            @endif

            {{-- ── The hadiths, under their chapter headings ─────────────────── --}}
            <div class="hadith-list" :data-view="view">
                @forelse ($groups as $group)
                    @include('public.hadith.partials.chapter', [
                        'chapter' => $group['chapter'],
                        'hadiths' => $group['hadiths'],
                        'collection' => $collection,
                    ])
                @empty
                    <x-site.empty-state icon="book-open" :title="__('hadith.no_hadiths')" :message="__('hadith.browse_intro')" />
                @endforelse
            </div>

            {{-- Every card on the page filtered out — not the same as an empty book --}}
            @if ($paginator->total() > 0)
                <div x-show="shown === 0" x-cloak>
                    <x-site.empty-state icon="search-x"
                                        :title="__('hadith.search_no_results')"
                                        :message="__('hadith.search_no_results_hint')" />
                </div>
            @endif
        </div>

        @if ($paginator->hasPages())
            <div class="mt-10">
                {{ $paginator->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
