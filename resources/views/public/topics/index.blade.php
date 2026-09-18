@extends('layouts.public')

@section('title', __('topics.index_title'))
@section('meta_description', __('topics.index_description'))

@push('structured_data')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "CollectionPage",
    "name": "{{ __('topics.index_title') }}",
    "description": "{{ __('topics.index_description') }}",
    "url": "{{ route('public.topics') }}",
    "inLanguage": "{{ app()->getLocale() }}"
}
</script>
@endpush

@php
    // Ledger figures are sums of the same counts the cards print.
    $verses = collect($topics)->sum('versesCount');
    $articles = collect($topics)->sum('articlesCount');
@endphp

@section('content')
<div class="sx min-h-[100dvh] pb-20">
    <div class="sx-wrap">
        <x-public.editorial.page-hero
            :eyebrow-url="url('/')" :eyebrow-label="__('site.home')" eyebrow-icon="home"
            :title="__('topics.index_title')" :lead="__('topics.index_subtitle')"
            :stats="$totalTopics > 0 ? [
                ['icon' => 'bookmark', 'label' => __('topics.topics_count'), 'value' => number_format($totalTopics)],
                ['icon' => 'book-open', 'label' => __('topics.stat_verses'), 'value' => number_format($verses)],
                ['icon' => 'file-text', 'label' => __('topics.stat_articles'), 'value' => number_format($articles)],
            ] : []" />

        @if (count($topics) === 0)
            <x-public.editorial.empty-state
                icon="bookmark"
                :title="__('topics.empty_topics_title')"
                :hint="__('topics.empty_topics_message')" />
        @else
            <div class="sx-grid">
                @foreach ($topics as $topic)
                    <x-public.topics.card :topic="$topic" :eager="$loop->index < 3" />
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
