@extends('layouts.public')

@php
    $locale = app()->getLocale();
    $grades = ['sahih', 'hasan', 'daif', 'mawdu', 'unknown'];
@endphp

@section('title', __('hadith.search_hadiths').' — '.__('site.site_name'))
@section('meta_description', __('hadith.search_hadiths'))

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 max-w-4xl">

        <h1 class="text-3xl font-black text-gray-900 dark:text-white mb-6">
            {{ __('hadith.search_hadiths') }}
        </h1>

        {{-- GET so a result page is linkable and the back button behaves. --}}
        <form method="GET" action="{{ route('public.hadith.search') }}" class="space-y-3 mb-8"
              x-data="{ submitting: false }" @submit="submitting = true">
            <div class="flex flex-wrap gap-2">
                <label for="q" class="sr-only">{{ __('hadith.search') }}</label>
                <input id="q" name="q" type="search" required minlength="2" maxlength="200"
                       value="{{ $criteria->query }}"
                       placeholder="{{ __('hadith.search_placeholder') }}"
                       {{-- text-base stops iOS zooming the page on focus --}}
                       class="flex-1 min-w-[12rem] rounded-xl border border-gray-300 dark:border-gray-700
                              bg-white dark:bg-[#16213e] px-4 py-3 text-base sm:text-sm
                              text-gray-900 dark:text-gray-100 placeholder:text-gray-400">

                <button type="submit" :disabled="submitting"
                        class="px-6 py-3 rounded-xl font-semibold text-white
                               bg-gradient-to-br from-[#0056b3] to-[#218838]
                               disabled:opacity-60 disabled:cursor-not-allowed">
                    <span x-show="!submitting">{{ __('hadith.search_submit') }}</span>
                    <span x-show="submitting" x-cloak>…</span>
                </button>
            </div>

            <div class="flex flex-wrap gap-2">
                <select name="collection"
                        class="rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-[#16213e]
                               px-3 py-2 text-base sm:text-sm text-gray-900 dark:text-gray-100">
                    <option value="">{{ __('hadith.filter_all_collections') }}</option>
                    @foreach ($collections as $collection)
                        <option value="{{ $collection->code }}" @selected($criteria->collectionCode === $collection->code)>
                            {{ $collection->getTranslation('name', $locale, true) }}
                        </option>
                    @endforeach
                </select>

                <select name="grade"
                        class="rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-[#16213e]
                               px-3 py-2 text-base sm:text-sm text-gray-900 dark:text-gray-100">
                    <option value="">{{ __('hadith.filter_all_grades') }}</option>
                    @foreach ($grades as $grade)
                        <option value="{{ $grade }}" @selected($criteria->grade?->value === $grade)>
                            {{ __('hadith.grade_'.$grade) }}
                        </option>
                    @endforeach
                </select>

                @if ($criteria->collectionCode || $criteria->grade)
                    <a href="{{ route('public.hadith.search', ['q' => $criteria->query]) }}"
                       class="px-4 py-2 rounded-xl text-sm text-gray-600 dark:text-gray-300 underline">
                        {{ __('hadith.clear_filters') }}
                    </a>
                @endif
            </div>
        </form>

        @error('q')
            <p role="alert" class="mb-6 rounded-xl bg-rose-50 dark:bg-rose-900/30 px-4 py-3
                                   text-sm text-rose-800 dark:text-rose-200">{{ $message }}</p>
        @enderror

        <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">
            {{ __('hadith.search_results_count', ['count' => number_format($results->total())]) }}
        </p>

        @forelse ($results as $hadith)
            @php $text = $hadith->textFor($locale); @endphp
            <article class="mb-4 rounded-xl border border-gray-200 dark:border-gray-800
                            bg-white dark:bg-[#16213e] p-5">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                    <a href="{{ route('public.hadith.show', [$hadith->collection->code, $hadith->hadith_number]) }}"
                       class="text-sm font-bold text-[#0056b3] dark:text-[#64CCC5] hover:underline">
                        {{ $hadith->collection->getTranslation('name', $locale, true) }}
                        {{ $hadith->hadith_number }}
                    </a>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        {{ __('hadith.grade') }}: {{ __('hadith.grade_'.$hadith->display_grade->value) }}
                    </span>
                </div>

                @if ($text)
                    <p dir="{{ $text->locale === 'ar' ? 'rtl' : 'ltr' }}" lang="{{ $text->locale }}"
                       class="{{ $text->locale === 'ar' ? "font-['Amiri'] text-xl leading-[2.2]" : 'text-sm leading-7' }}
                              text-gray-800 dark:text-gray-200 line-clamp-3 break-words text-start">
                        {{ $text->body }}
                    </p>
                @endif
            </article>
        @empty
            @if ($criteria->query !== '')
                <x-site.empty-state icon="search-x"
                                    :title="__('hadith.search_no_results')"
                                    :message="__('hadith.search_no_results_hint')" />
            @else
                <x-site.empty-state icon="search" :title="__('hadith.search_start')" message="" />
            @endif
        @endforelse

        @if ($results->hasPages())
            <div class="mt-8">{{ $results->withQueryString()->links() }}</div>
        @endif
    </div>
@endsection
