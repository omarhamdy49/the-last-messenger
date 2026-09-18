@extends('layouts.public')

@section('title', $series['title'].' — '.__('series.public.page_title'))
@section('meta_description', \Illuminate\Support\Str::limit($series['description'] ?: __('series.public.page_description'), 155))

@push('structured_data')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "Collection",
    "name": "{{ $series['title'] }}",
    "description": "{{ \Illuminate\Support\Str::limit($series['description'], 155) }}",
    "url": "{{ $series['url'] }}",
    "inLanguage": "{{ app()->getLocale() }}"
}
</script>
@endpush

@section('content')
@php $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true); @endphp

<div class="sx pb-20">
    <x-public.editorial.hero :image="$series['cover'] ?: ($series['thumbnail'] ?? null)" variant="reading" :title="$series['title']" :lead="$series['description'] ?: null">
        <x-slot:top>
            <nav aria-label="breadcrumb">
                <a href="{{ route('public.series') }}" class="sx-glass-chip"><i data-lucide="arrow-{{ $isRtl ? 'right' : 'left' }}" aria-hidden="true"></i>{{ __('series.public.back_to_series') }}</a>
            </nav>
        </x-slot:top>
        <x-slot:chips>
            <span class="sx-glass-chip"><i data-lucide="route" aria-hidden="true"></i>{{ __('series.public.reading_path') }}</span>
        </x-slot:chips>
        <x-slot:meta>
            <span class="sx-glass-chip"><i data-lucide="file-text" aria-hidden="true"></i>{{ $series['articlesCount'] }} {{ __('series.public.article_unit') }}</span>
            @if (! empty($totalReadTime))
                <span class="sx-glass-chip"><i data-lucide="clock" aria-hidden="true"></i>{{ __('series.public.total_reading') }}: {{ $totalReadTime }} {{ __('series.public.min_read') }}</span>
            @endif
        </x-slot:meta>
        @if (count($articles) > 0)
            <x-slot:actions>
                <a href="{{ $articles[0]['url'] }}" class="sx-btn is-light"><i data-lucide="play" aria-hidden="true"></i>{{ __('series.public.start_reading') }}</a>
                <a href="#reading-path" class="sx-btn is-glass"><i data-lucide="list-ordered" aria-hidden="true"></i>{{ __('series.public.reading_path') }}</a>
            </x-slot:actions>
        @endif
    </x-public.editorial.hero>

    <div class="sx-wrap pt-10">
        <div id="reading-path" class="mx-auto w-full max-w-4xl scroll-mt-24">
            <x-public.editorial.section icon="list-ordered" :title="__('series.public.reading_path')">
                @if (count($articles) === 0)
                    <x-public.editorial.empty-state icon="file-search" :title="__('series.public.series_empty_articles')" :hint="__('series.public.empty_message')" />
                @else
                    <ol class="sx-path">
                        @foreach ($articles as $i => $article)
                            <li>
                                <span class="sx-path-step" aria-hidden="true">{{ $i + 1 }}</span>
                                <x-public.editorial.card variant="row" :url="$article['url']" :title="$article['title']" :excerpt="$article['excerpt'] ?: null"
                                    :image="$article['image'] ?? null" icon="newspaper" :eager="$i < 2"
                                    :badge="$i === 0 ? __('series.public.start_here') : __('series.public.part').' '.($i + 1)"
                                    :meta="[
                                        ['icon' => 'folder', 'text' => $article['category'] ?? null],
                                        ['icon' => 'clock', 'text' => ! empty($article['readTime']) ? $article['readTime'].' '.__('series.public.min_read') : null],
                                        ['icon' => 'eye', 'text' => isset($article['views']) ? number_format($article['views']).' '.__('series.public.views') : null],
                                    ]" />
                            </li>
                        @endforeach
                        <li class="sx-path-finish">
                            <span class="sx-path-step" aria-hidden="true"><i data-lucide="award"></i></span>
                            <div class="sx-surface grid gap-2 border-dashed p-5">
                                <p class="font-extrabold text-[color:var(--sx-ink)]">{{ __('series.public.finish_title') }}</p>
                                <p class="text-sm text-[color:var(--sx-muted)]">{{ __('series.public.finish_message') }}</p>
                                <a href="{{ route('public.series') }}" class="sx-btn justify-self-start mt-1">{{ __('series.public.browse_more_series') }}<i data-lucide="arrow-{{ $isRtl ? 'left' : 'right' }}" aria-hidden="true"></i></a>
                            </div>
                        </li>
                    </ol>
                @endif
            </x-public.editorial.section>
        </div>
    </div>
</div>
@endsection
