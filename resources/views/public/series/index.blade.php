@extends('layouts.public')

@section('title', __('series.public.meta_title'))
@section('meta_description', __('series.public.page_description'))

@push('structured_data')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "CollectionPage",
    "name": "{{ __('series.public.page_title') }}",
    "description": "{{ __('series.public.page_description') }}",
    "url": "{{ route('public.series') }}",
    "inLanguage": "{{ app()->getLocale() }}"
}
</script>
@endpush

@section('content')
@php
    $totalArticles = array_sum(array_column($series, 'articlesCount'));
    $totalMinutes = array_sum(array_column($series, 'totalReadTime'));
@endphp

<div class="sx min-h-[100dvh] pb-20">
    <div class="sx-wrap">
        <x-public.editorial.page-hero
            :eyebrow-url="route('public.articles')" :eyebrow-label="__('series.public.reading_path')" eyebrow-icon="route"
            :title="__('series.public.hero_title')" :lead="__('series.public.hero_subtitle')"
            :stats="count($series) > 0 ? array_values(array_filter([
                ['icon' => 'layers', 'label' => __('series.public.series_unit'), 'value' => count($series)],
                ['icon' => 'file-text', 'label' => __('series.public.article_unit'), 'value' => number_format($totalArticles)],
                $totalMinutes > 0 ? ['icon' => 'clock', 'label' => __('series.public.min_read'), 'value' => number_format($totalMinutes)] : null,
            ])) : []" />

        @if (count($series) === 0)
            <x-public.editorial.empty-state icon="layers" :title="__('series.public.empty_title')" :hint="__('series.public.empty_message')" />
        @else
            <div class="sx-grid">
                @foreach ($series as $item)
                    <x-public.editorial.card :url="$item['url']" :title="$item['title']" :excerpt="$item['description'] ?: null"
                        :image="$item['thumbnail'] ?: ($item['cover'] ?? null)" icon="layers" :eager="$loop->index < 3"
                        :big="$item['articlesCount']" :big-suffix="__('series.public.article_unit')"
                        :meta="[
                            ['icon' => 'clock', 'text' => ! empty($item['totalReadTime']) ? $item['totalReadTime'].' '.__('series.public.min_read') : null],
                            ['icon' => 'calendar', 'text' => $item['dateLabel'] ?? null],
                        ]" />
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
