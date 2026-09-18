@extends('layouts.public')

@section('title', __('hadith.library_title'))
@section('meta_description', __('hadith.library_tagline'))

@php
    $featured = $collections->where('is_featured', true);
@endphp

@section('content')
<div class="sx min-h-[100dvh] pb-20">
    <div class="sx-wrap">

        <x-public.editorial.page-hero
            :eyebrow-label="__('hadith.full_library')" eyebrow-icon="library-big"
            :title="__('hadith.library_title')" :lead="__('hadith.library_tagline')"
            :stats="[
                ['icon' => 'scroll-text', 'label' => __('hadith.hadiths'), 'value' => number_format($total)],
                ['icon' => 'library', 'label' => __('hadith.collections'), 'value' => number_format($collections->count())],
            ]">
            <form action="{{ route('public.hadith.search') }}" method="GET" class="flex max-w-2xl flex-wrap items-center gap-3">
                <div class="relative min-w-0 flex-1">
                    <label for="hadith-q" class="sr-only">{{ __('hadith.search_placeholder') }}</label>
                    <i data-lucide="search" class="pointer-events-none absolute start-4 top-1/2 h-5 w-5 -translate-y-1/2 text-[color:var(--sx-muted)]" aria-hidden="true"></i>
                    <input id="hadith-q" type="search" name="q" minlength="2" maxlength="120" enterkeyhint="search"
                           placeholder="{{ __('hadith.search_placeholder') }}" class="sx-input ps-12">
                </div>
                <button type="submit" class="sx-btn is-primary">
                    <i data-lucide="search" aria-hidden="true"></i>{{ __('hadith.search_submit') }}
                </button>
            </form>
        </x-public.editorial.page-hero>

        @if ($collections->isEmpty())
            <x-public.editorial.empty-state icon="library" :title="__('hadith.no_collections')" />
        @else
            @if ($featured->isNotEmpty())
                <x-public.editorial.section icon="star" :title="__('hadith.featured_collections')" class="mb-12">
                    <div class="sx-grid is-wide">
                        @foreach ($featured as $collection)
                            <x-public.hadith.collection-card :collection="$collection" :eager="$loop->index < 2" />
                        @endforeach
                    </div>
                </x-public.editorial.section>
            @endif

            <x-public.editorial.section icon="library-big" :title="__('hadith.all_collections')">
                <div class="sx-grid">
                    @foreach ($collections as $collection)
                        <x-public.hadith.collection-card :collection="$collection" />
                    @endforeach
                </div>
            </x-public.editorial.section>
        @endif
    </div>
</div>
@endsection
