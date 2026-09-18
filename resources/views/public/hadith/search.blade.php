@extends('layouts.public')

@section('title', __('hadith.search_hadiths'))
@section('meta_description', __('hadith.library_tagline'))
{{-- Search result pages are thin and near-duplicate; keep them out of the index. --}}
@section('robots', 'noindex, follow')

@php
    $locale = app()->getLocale();
    $grades = ['sahih', 'hasan', 'daif', 'mawdu'];
@endphp

@section('content')
<div class="sx min-h-[100dvh] pb-20">
    <div class="sx-wrap">

        <x-public.editorial.page-hero
            :eyebrow-url="route('public.hadith')" :eyebrow-label="__('hadith.collections')" eyebrow-icon="library-big"
            :title="__('hadith.search_hadiths')" :lead="__('hadith.library_tagline')" />

        <form action="{{ route('public.hadith.search') }}" method="GET" class="sx-surface sx-filters" role="search">
            <div class="sx-filter-row">
                <div class="relative min-w-0">
                    <label for="hadith-q" class="sr-only">{{ __('hadith.search_placeholder') }}</label>
                    <i data-lucide="search" class="pointer-events-none absolute start-4 top-1/2 h-5 w-5 -translate-y-1/2 text-[color:var(--sx-muted)]" aria-hidden="true"></i>
                    <input id="hadith-q" type="search" name="q" value="{{ $term }}" minlength="2" maxlength="120" required
                           enterkeyhint="search" placeholder="{{ __('hadith.search_placeholder') }}" class="sx-input ps-12">
                </div>

                <div>
                    <label for="hadith-collection" class="sr-only">{{ __('hadith.filter_all_collections') }}</label>
                    <select id="hadith-collection" name="collection" class="sx-input">
                        <option value="">{{ __('hadith.filter_all_collections') }}</option>
                        @foreach ($collections as $c)
                            <option value="{{ $c->slug }}" @selected($filters['collection'] === $c->slug)>{{ $c->title($locale) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="hadith-grade" class="sr-only">{{ __('hadith.filter_all_grades') }}</label>
                    <select id="hadith-grade" name="grade" class="sx-input">
                        <option value="">{{ __('hadith.filter_all_grades') }}</option>
                        @foreach ($grades as $g)
                            <option value="{{ $g }}" @selected($filters['grade'] === $g)>
                                {{ __('hadith.grade_'.$g) }}@isset($gradeCounts[$g]) ({{ number_format($gradeCounts[$g]) }})@endisset
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="sx-btn is-primary">
                    <i data-lucide="search" aria-hidden="true"></i>{{ __('hadith.search_submit') }}
                </button>
            </div>
        </form>

        <div class="mt-8">
            @if ($results === null)
                {{-- No term yet: prompt rather than show an empty result set. --}}
                <x-public.editorial.empty-state icon="search"
                    :title="__('hadith.search_start')" :hint="__('hadith.search_suggestions')" />

            @elseif ($results->isEmpty())
                <x-public.editorial.empty-state icon="search-x"
                    :title="__('hadith.search_no_results')" :hint="__('hadith.search_no_results_hint')"
                    :action-url="($filters['collection'] || $filters['grade']) ? route('public.hadith.search', ['q' => $term]) : null"
                    :action-label="__('hadith.clear_filters')" />

            @else
                {{-- search_results_count carries a :count placeholder — it is the whole
                     sentence, not a label to prefix with a number. --}}
                <div class="mb-5 flex flex-wrap items-baseline gap-x-2 gap-y-1">
                    <span class="text-lg font-extrabold text-[color:var(--sx-ink)]">
                        {{ __('hadith.search_results_count', ['count' => number_format($results->total())]) }}
                    </span>
                    <span class="text-sm text-[color:var(--sx-muted)]">— &ldquo;{{ $term }}&rdquo;</span>
                </div>

                <div class="grid gap-4">
                    @foreach ($results as $hadith)
                        <x-public.hadith.card :hadith="$hadith" :show-collection="true" :eager="$loop->first" />
                    @endforeach
                </div>

                <div class="mt-12">
                    <x-public.editorial.pagination :paginator="$results" />
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
