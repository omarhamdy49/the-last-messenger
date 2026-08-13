@extends('layouts.public')

@section('title', __('hadith.search_hadiths'))
@section('meta_description', __('hadith.library_tagline'))
{{-- Search result pages are thin and near-duplicate; keep them out of the index. --}}
@section('robots', 'noindex, follow')

@php
    $locale = app()->getLocale();
    $isRtl = in_array($locale, ['ar', 'ur'], true);
    $grades = ['sahih', 'hasan', 'daif', 'mawdu'];
@endphp

@section('content')
<div class="min-h-dvh bg-gray-50 dark:bg-[#0b1120]">

    <section class="bg-gradient-to-l from-[#0056b3] to-[#218838] text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-9 sm:py-12">
            <nav class="flex items-center gap-2 text-xs text-white/70 mb-4" aria-label="breadcrumb">
                <a href="{{ route('public.hadith') }}" class="hover:text-white transition-colors">{{ __('hadith.collections') }}</a>
                <i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" class="w-3.5 h-3.5"></i>
                <span class="text-white font-semibold">{{ __('hadith.search') }}</span>
            </nav>

            <h1 class="text-2xl sm:text-3xl font-extrabold">{{ __('hadith.search_hadiths') }}</h1>

            <form action="{{ route('public.hadith.search') }}" method="GET" class="mt-6 grid gap-3 sm:grid-cols-[1fr_auto_auto_auto]">
                <div class="relative">
                    <i data-lucide="search" class="absolute {{ $isRtl ? 'right-4' : 'left-4' }} top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>
                    <input type="search" name="q" value="{{ $term }}" minlength="2" maxlength="120" required
                           placeholder="{{ __('hadith.search_placeholder') }}"
                           class="w-full rounded-xl border-0 bg-white dark:bg-[#111a2e] py-3.5 {{ $isRtl ? 'pr-12 pl-4' : 'pl-12 pr-4' }} text-gray-900 dark:text-white shadow-lg focus:ring-2 focus:ring-[#64CCC5] outline-none">
                </div>

                <select name="collection"
                        class="rounded-xl border-0 bg-white dark:bg-[#111a2e] py-3.5 px-4 text-sm text-gray-900 dark:text-white shadow-lg focus:ring-2 focus:ring-[#64CCC5] outline-none">
                    <option value="">{{ __('hadith.filter_all_collections') }}</option>
                    @foreach ($collections as $c)
                        <option value="{{ $c->slug }}" @selected($filters['collection'] === $c->slug)>{{ $c->title($locale) }}</option>
                    @endforeach
                </select>

                <select name="grade"
                        class="rounded-xl border-0 bg-white dark:bg-[#111a2e] py-3.5 px-4 text-sm text-gray-900 dark:text-white shadow-lg focus:ring-2 focus:ring-[#64CCC5] outline-none">
                    <option value="">{{ __('hadith.filter_all_grades') }}</option>
                    @foreach ($grades as $g)
                        <option value="{{ $g }}" @selected($filters['grade'] === $g)>
                            {{ __('hadith.grade_'.$g) }}@isset($gradeCounts[$g]) ({{ number_format($gradeCounts[$g]) }})@endisset
                        </option>
                    @endforeach
                </select>

                <button type="submit" class="rounded-xl bg-white text-[#0056b3] px-6 py-3.5 text-sm font-bold hover:scale-105 transition-transform">
                    {{ __('hadith.search_submit') }}
                </button>
            </form>
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-4 sm:px-6 py-8 sm:py-12">

        @if ($results === null)
            {{-- No term yet: prompt rather than show an empty result set. --}}
            <div class="rounded-3xl border border-dashed border-gray-300 dark:border-white/10 p-12 sm:p-16 text-center">
                <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center">
                    <i data-lucide="search" class="w-8 h-8 text-white"></i>
                </div>
                <h2 class="text-lg font-extrabold text-gray-900 dark:text-white">{{ __('hadith.search_start') }}</h2>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('hadith.search_suggestions') }}</p>
            </div>

        @elseif ($results->isEmpty())
            <div class="rounded-3xl border border-dashed border-gray-300 dark:border-white/10 p-12 sm:p-16 text-center">
                <i data-lucide="search-x" class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600"></i>
                <h2 class="text-lg font-extrabold text-gray-900 dark:text-white">{{ __('hadith.search_no_results') }}</h2>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('hadith.search_no_results_hint') }}</p>
                @if ($filters['collection'] || $filters['grade'])
                    <a href="{{ route('public.hadith.search', ['q' => $term]) }}"
                       class="mt-5 inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-l from-[#0056b3] to-[#218838] text-white text-sm font-bold">
                        <i data-lucide="filter-x" class="w-4 h-4"></i>{{ __('hadith.clear_filters') }}
                    </a>
                @endif
            </div>

        @else
            {{-- search_results_count carries a :count placeholder — it is the whole
                 sentence, not a label to prefix with a number. --}}
            <div class="mb-6 flex flex-wrap items-baseline gap-x-2 gap-y-1">
                <span class="text-lg font-extrabold text-gray-900 dark:text-white">
                    {{ __('hadith.search_results_count', ['count' => number_format($results->total())]) }}
                </span>
                <span class="text-sm text-gray-400 dark:text-gray-500">— &ldquo;{{ $term }}&rdquo;</span>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                @foreach ($results as $hadith)
                    @include('public.hadith._hadith-card', ['hadith' => $hadith, 'showCollection' => true])
                @endforeach
            </div>

            @if ($results->hasPages())
                <div class="mt-8">{{ $results->links() }}</div>
            @endif
        @endif
    </section>
</div>
@endsection

@push('scripts')
<script>document.addEventListener('DOMContentLoaded', () => window.lucide?.createIcons());</script>
@endpush
