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

@push('head')
<style>
    .series-path { position: relative; }
    /* Vertical connector line for the ordered reading path */
    .series-path::before {
        content: ''; position: absolute; top: 1.5rem; bottom: 1.5rem;
        inset-inline-start: 1.375rem; width: 2px;
        background: linear-gradient(to bottom, #0056b3, #218838);
        opacity: .25;
    }
    .series-step-card {
        border-radius: 1rem; background: #fff;
        border: 1px solid rgb(243 244 246);
        box-shadow: 0 1px 3px rgba(2,30,68,.06);
        transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
    }
    .dark .series-step-card { background: #16213e; border-color: rgba(100,204,197,.10); }
    .series-step-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 16px 32px -12px rgba(0,86,179,.22);
        border-color: rgba(0,86,179,.25);
    }
    @media (prefers-reduced-motion: reduce) {
        .series-step-card { transition: none !important; transform: none !important; }
    }
</style>
@endpush

@section('content')

@php $isRtlLocale = in_array(app()->getLocale(), ['ar', 'ur'], true); @endphp

<div class="min-h-screen">

    {{-- SERIES HEADER --}}
    <section class="relative overflow-hidden border-b border-gray-100 dark:border-[#0f3460]">
        <div class="absolute inset-0 bg-gradient-to-br from-primary via-[#136f4b] to-secondary" aria-hidden="true"></div>
        <div class="absolute inset-0 bg-black/25" aria-hidden="true"></div>
        {{-- Dotted lattice, fading downward (matches the library heroes) --}}
        <div class="absolute inset-0 opacity-[0.12] pointer-events-none"
             style="background-image: radial-gradient(#fff 1px, transparent 1.5px); background-size: 26px 26px; -webkit-mask-image: linear-gradient(to bottom, black, transparent); mask-image: linear-gradient(to bottom, black, transparent);"
             aria-hidden="true"></div>
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative py-14 md:py-18 text-white">
            <a href="{{ route('public.series') }}"
               class="inline-flex items-center gap-2 text-sm font-semibold text-white/80 hover:text-white transition-colors mb-6">
                <i data-lucide="arrow-{{ $isRtlLocale ? 'right' : 'left' }}" class="w-4 h-4"></i>
                {{ __('series.public.back_to_series') }}
            </a>
            <div class="flex items-start gap-5">
                <div class="hidden sm:flex w-16 h-16 rounded-2xl bg-white/15 backdrop-blur items-center justify-center shrink-0 shadow-lg">
                    <i data-lucide="layers" class="w-8 h-8"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <h1 class="text-3xl md:text-4xl font-bold mb-3 tracking-tight">{{ $series['title'] }}</h1>
                    @if ($series['description'])
                        <p class="text-white/85 max-w-2xl leading-relaxed mb-5">{{ $series['description'] }}</p>
                    @endif
                    <div class="flex items-center gap-3 flex-wrap">
                        <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-semibold bg-white/15 backdrop-blur">
                            <i data-lucide="file-text" class="w-4 h-4"></i>
                            {{ $series['articlesCount'] }} {{ __('series.public.article_unit') }}
                        </span>
                        @if (! empty($totalReadTime))
                            <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-semibold bg-white/15 backdrop-blur">
                                <i data-lucide="clock" class="w-4 h-4"></i>
                                {{ __('series.public.total_reading') }}: {{ $totalReadTime }}{{ __('series.public.min_read') }}
                            </span>
                        @endif
                        @if (count($articles) > 0)
                            <a href="{{ $articles[0]['url'] }}"
                               class="inline-flex items-center gap-2 px-5 py-2 rounded-full text-sm font-bold bg-white text-primary shadow-lg hover:shadow-xl hover:scale-105 transition-all cursor-pointer">
                                <i data-lucide="play" class="w-4 h-4"></i>
                                {{ __('series.public.start_reading') }}
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <x-site.breadcrumb :items="[
            ['label' => __('series.public.breadcrumb'), 'url' => route('public.series')],
            ['label' => $series['title']],
        ]" class="mb-8" />

        <div class="max-w-3xl mx-auto">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                <span class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center">
                    <i data-lucide="list-ordered" class="w-4 h-4"></i>
                </span>
                {{ __('series.public.reading_path') }}
            </h2>

            @if (count($articles) === 0)
                <x-site.empty-state
                    icon="file-search"
                    :title="__('series.public.series_empty_articles')"
                    :message="__('series.public.empty_message')"
                />
            @else
                <div class="series-path space-y-4">
                    @foreach ($articles as $i => $article)
                        <a href="{{ $article['url'] }}" class="group series-step-card relative flex gap-4 p-5 ps-16">
                            {{-- Step number --}}
                            <span class="absolute start-3 top-5 w-9 h-9 rounded-full bg-gradient-to-l from-primary to-secondary text-white text-sm font-bold flex items-center justify-center shadow-md tabular-nums z-10">
                                {{ $i + 1 }}
                            </span>
                            {{-- Thumbnail --}}
                            @if (! empty($article['image']))
                                <span class="hidden sm:block w-24 h-20 rounded-xl overflow-hidden shrink-0 bg-gray-100 dark:bg-[#0f3460]">
                                    <img src="{{ $article['image'] }}" alt="{{ $article['title'] }}" loading="lazy"
                                         onerror="this.onerror=null;this.style.display='none';this.parentElement.classList.add('img-gradient-fallback')"
                                         class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110 motion-reduce:transform-none">
                                </span>
                            @endif
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                                    <span class="text-[11px] font-bold uppercase tracking-wide text-primary">{{ __('series.public.part') }} {{ $i + 1 }}</span>
                                    @if ($article['category'])
                                        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-gray-100 dark:bg-[#0f3460] text-gray-600 dark:text-gray-300">{{ $article['category'] }}</span>
                                    @endif
                                </div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1.5 group-hover:text-primary transition-colors leading-snug">
                                    {{ $article['title'] }}
                                </h3>
                                @if ($article['excerpt'])
                                    <p class="text-sm text-gray-600 dark:text-gray-300 line-clamp-2 mb-3 leading-relaxed">{{ $article['excerpt'] }}</p>
                                @endif
                                <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400 flex-wrap">
                                    <span class="text-gray-400 dark:text-gray-500">{{ $article['dateLabel'] }}</span>
                                    <span class="inline-flex items-center gap-1">
                                        <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                                        {{ $article['readTime'] }}{{ __('series.public.min_read') }}
                                    </span>
                                    <span class="inline-flex items-center gap-1">
                                        <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                        {{ $article['views'] }} {{ __('series.public.views') }}
                                    </span>
                                </div>
                            </div>
                            <span class="self-center text-gray-300 dark:text-gray-600 group-hover:text-primary transition-colors shrink-0">
                                <i data-lucide="chevron-{{ $isRtlLocale ? 'left' : 'right' }}" class="w-5 h-5"></i>
                            </span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

@endsection
