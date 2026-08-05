@extends('layouts.public')

@php
    $locale = app()->getLocale();
    $grades = ['sahih', 'hasan', 'daif', 'mawdu', 'unknown'];
    $hasFilters = $criteria->collectionCode || $criteria->grade;
@endphp

@section('title', __('hadith.search_hadiths').' — '.__('site.site_name'))
@section('meta_description', __('hadith.search_hadiths'))

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 max-w-4xl">

        <header class="mb-6 text-center sm:text-start">
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full
                         bg-[#64CCC5]/15 text-[#0056b3] dark:text-[#64CCC5] text-sm font-semibold">
                <i data-lucide="search" class="w-4 h-4" aria-hidden="true"></i>
                {{ __('hadith.hadiths') }}
            </span>
            <h1 class="mt-4 text-2xl sm:text-4xl font-black font-['Amiri'] text-gray-900 dark:text-white">
                {{ __('hadith.search_hadiths') }}
            </h1>
        </header>

        {{-- GET so a result page is linkable and the back button behaves. --}}
        <form method="GET" action="{{ route('public.hadith.search') }}"
              class="rounded-2xl p-4 sm:p-5 mb-8 shadow-lg space-y-3
                     bg-white dark:bg-[#16213e] border border-gray-100 dark:border-gray-800"
              x-data="{ submitting: false }" @submit="submitting = true">

            <div class="flex flex-wrap gap-2">
                <div class="relative flex-1 min-w-[12rem]">
                    <label for="q" class="sr-only">{{ __('hadith.search') }}</label>
                    <input id="q" name="q" type="search" required minlength="2" maxlength="200"
                           value="{{ $criteria->query }}"
                           placeholder="{{ __('hadith.search_placeholder') }}"
                           {{-- text-base stops iOS zooming the page on focus --}}
                           class="w-full ps-11 pe-4 py-3 rounded-xl text-base sm:text-sm
                                  border-2 border-gray-200 dark:border-gray-700
                                  bg-white dark:bg-[#0f3460] text-gray-900 dark:text-gray-100
                                  placeholder:text-gray-400 focus:border-[#64CCC5] focus:outline-none transition-colors">
                    <i data-lucide="search" aria-hidden="true"
                       class="pointer-events-none absolute start-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>
                </div>

                <button type="submit" :disabled="submitting"
                        class="inline-flex min-h-11 items-center gap-2 px-6 rounded-xl font-semibold text-white
                               bg-gradient-to-br from-[#0056b3] to-[#218838] transition hover:shadow-lg
                               disabled:opacity-60 disabled:cursor-not-allowed
                               focus:outline-none focus-visible:ring-2 focus-visible:ring-[#218838]
                               focus-visible:ring-offset-2">
                    <i data-lucide="search" class="w-4 h-4" x-show="!submitting" aria-hidden="true"></i>
                    <i data-lucide="loader-2" class="w-4 h-4 animate-spin" x-show="submitting" x-cloak aria-hidden="true"></i>
                    {{ __('hadith.search_submit') }}
                </button>
            </div>

            <div class="flex flex-wrap gap-2">
                <label for="collection" class="sr-only">{{ __('hadith.collection') }}</label>
                <select id="collection" name="collection"
                        class="min-h-11 px-3 rounded-xl text-base sm:text-sm border-2
                               border-gray-200 dark:border-gray-700 bg-white dark:bg-[#0f3460]
                               text-gray-900 dark:text-gray-100 focus:border-[#64CCC5] focus:outline-none transition-colors">
                    <option value="">{{ __('hadith.filter_all_collections') }}</option>
                    @foreach ($collections as $collection)
                        <option value="{{ $collection->code }}" @selected($criteria->collectionCode === $collection->code)>
                            {{ $collection->getTranslation('name', $locale, true) }}
                        </option>
                    @endforeach
                </select>

                <label for="grade" class="sr-only">{{ __('hadith.grade') }}</label>
                <select id="grade" name="grade"
                        class="min-h-11 px-3 rounded-xl text-base sm:text-sm border-2
                               border-gray-200 dark:border-gray-700 bg-white dark:bg-[#0f3460]
                               text-gray-900 dark:text-gray-100 focus:border-[#64CCC5] focus:outline-none transition-colors">
                    <option value="">{{ __('hadith.filter_all_grades') }}</option>
                    @foreach ($grades as $grade)
                        <option value="{{ $grade }}" @selected($criteria->grade?->value === $grade)>
                            {{ __('hadith.grade_'.$grade) }}
                        </option>
                    @endforeach
                </select>

                @if ($hasFilters)
                    <a href="{{ route('public.hadith.search', ['q' => $criteria->query]) }}"
                       class="inline-flex min-h-11 items-center gap-2 px-4 rounded-xl text-sm font-semibold
                              text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#0f3460]
                              transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[#218838]">
                        <i data-lucide="rotate-ccw" class="w-4 h-4" aria-hidden="true"></i>
                        {{ __('hadith.clear_filters') }}
                    </a>
                @endif
            </div>
        </form>

        @error('q')
            <p role="alert" class="mb-6 flex items-center gap-2 rounded-xl bg-rose-50 dark:bg-rose-900/30 px-4 py-3
                                   text-sm text-rose-800 dark:text-rose-200">
                <i data-lucide="alert-triangle" class="w-4 h-4 shrink-0" aria-hidden="true"></i>
                {{ $message }}
            </p>
        @enderror

        @if ($criteria->query !== '')
            <p class="mb-4 text-sm text-gray-600 dark:text-gray-300 tabular-nums">
                {{ __('hadith.search_results_count', ['count' => number_format($results->total())]) }}
            </p>
        @endif

        {{-- Same card as the book pages: a hadith looks the same wherever it is listed. --}}
        <div x-data="{ textLanguage: @js($locale === 'en' ? 'en' : 'ar') }">
            @if ($results->total() > 0)
                <div class="mb-4 flex justify-end">
                    <x-site.hadith-language-toggle />
                </div>
            @endif

            <div class="space-y-4">
                @forelse ($results as $hadith)
                    <x-site.hadith-card :hadith="$hadith" :index="$loop->index" />
                @empty
                    @if ($criteria->query !== '')
                        <x-site.empty-state icon="search-x"
                                            :title="__('hadith.search_no_results')"
                                            :message="__('hadith.search_no_results_hint')" />
                    @else
                        <x-site.empty-state icon="search" :title="__('hadith.search_start')" message="" />
                    @endif
                @endforelse
            </div>
        </div>

        @if ($results->hasPages())
            <div class="mt-10">{{ $results->withQueryString()->links() }}</div>
        @endif
    </div>
@endsection
