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

@push('head')
<style>
    .series-hero { position: relative; isolation: isolate; overflow: hidden; }
    .series-hero-pattern {
        position: absolute; inset: 0; z-index: -1; pointer-events: none;
        background-image: radial-gradient(currentColor 1px, transparent 1.5px);
        background-size: 26px 26px;
        color: #218838;
        opacity: .07;
        -webkit-mask-image: linear-gradient(to bottom, black, transparent);
        mask-image: linear-gradient(to bottom, black, transparent);
    }
    .dark .series-hero-pattern { color: #64CCC5; opacity: .08; }
    .series-hero::before,
    .series-hero::after {
        content: ''; position: absolute; z-index: -1; border-radius: 9999px;
        filter: blur(90px); opacity: .32; pointer-events: none;
    }
    .series-hero::before {
        width: 32rem; height: 32rem; inset-inline-start: -9rem; top: -12rem;
        background: radial-gradient(circle, #218838 0%, transparent 70%);
    }
    .series-hero::after {
        width: 30rem; height: 30rem; inset-inline-end: -7rem; bottom: -14rem;
        background: radial-gradient(circle, #0056b3 0%, transparent 70%);
    }
    .dark .series-hero::before, .dark .series-hero::after { opacity: .22; }

    .series-card {
        border-radius: 1.25rem; overflow: hidden; background: #fff;
        border: 1px solid rgb(243 244 246);
        box-shadow: 0 1px 3px rgba(2,30,68,.06), 0 8px 24px -12px rgba(2,30,68,.10);
        transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
    }
    .dark .series-card { background: #16213e; border-color: rgba(100,204,197,.10); }
    .series-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 24px 48px -16px rgba(33,136,56,.25);
        border-color: rgba(33,136,56,.30);
    }
    .series-card .series-cue { opacity: 0; transform: translateY(4px); transition: all .25s ease; }
    .series-card:hover .series-cue { opacity: 1; transform: translateY(0); }

    @media (prefers-reduced-motion: reduce) {
        .series-card, .series-cue { transition: none !important; transform: none !important; }
    }
</style>
@endpush

@section('content')

@php $isRtlLocale = in_array(app()->getLocale(), ['ar', 'ur'], true); @endphp

<div class="min-h-screen">

    {{-- HERO --}}
    <section class="series-hero border-b border-gray-100 dark:border-[#0f3460] bg-gradient-to-b from-secondary/[0.04] to-transparent dark:from-transparent">
        <div class="series-hero-pattern" aria-hidden="true"></div>
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative py-16 md:py-20">
            <div class="max-w-3xl mx-auto text-center fade-in-up">
                <div class="flex justify-center mb-6">
                    <div class="relative">
                        <div class="absolute inset-0 rounded-2xl bg-gradient-to-l from-secondary to-primary blur-lg opacity-40" aria-hidden="true"></div>
                        <div class="relative w-16 h-16 rounded-2xl bg-gradient-to-l from-secondary to-primary flex items-center justify-center text-white shadow-xl">
                            <i data-lucide="layers" class="w-8 h-8"></i>
                        </div>
                    </div>
                </div>
                <h1 class="text-4xl md:text-5xl font-bold mb-4 text-gray-900 dark:text-white tracking-tight">
                    {{ __('series.public.hero_title') }}
                </h1>
                <p class="text-base md:text-lg text-gray-600 dark:text-gray-300 max-w-2xl mx-auto leading-relaxed">
                    {{ __('series.public.hero_subtitle') }}
                </p>

                {{-- Quick stats --}}
                @if (count($series) > 0)
                    <div class="flex items-center justify-center gap-6 sm:gap-10 mt-9 text-sm">
                        <div class="flex items-center gap-2 text-gray-600 dark:text-gray-300">
                            <i data-lucide="layers" class="w-4 h-4 text-secondary"></i>
                            <span class="font-bold text-gray-900 dark:text-white tabular-nums">{{ count($series) }}</span>
                            {{ __('series.public.series_unit') }}
                        </div>
                        <div class="w-px h-4 bg-gray-300 dark:bg-[#0f3460]" aria-hidden="true"></div>
                        <div class="flex items-center gap-2 text-gray-600 dark:text-gray-300">
                            <i data-lucide="file-text" class="w-4 h-4 text-primary"></i>
                            <span class="font-bold text-gray-900 dark:text-white tabular-nums">{{ array_sum(array_column($series, 'articlesCount')) }}</span>
                            {{ __('series.public.article_unit') }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <x-site.breadcrumb :items="[['label' => __('series.public.breadcrumb')]]" class="mb-8" />

        @if (count($series) === 0)
            <x-site.empty-state
                icon="layers"
                :title="__('series.public.empty_title')"
                :message="__('series.public.empty_message')"
            />
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($series as $item)
                    <a href="{{ $item['url'] }}" class="group series-card flex flex-col">
                        {{-- Visual --}}
                        <div class="relative aspect-[16/9] overflow-hidden bg-gradient-to-br from-primary via-[#136f4b] to-secondary">
                            @if ($item['thumbnail'])
                                <img src="{{ $item['thumbnail'] }}" alt="{{ $item['title'] }}" loading="lazy"
                                     class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105 motion-reduce:transform-none">
                            @else
                                <div class="absolute inset-0 flex items-center justify-center text-white/80">
                                    <i data-lucide="layers" class="w-14 h-14"></i>
                                </div>
                            @endif
                            <span class="absolute top-3 start-3 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-white/90 dark:bg-[#16213e]/90 text-primary backdrop-blur">
                                <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                                {{ $item['articlesCount'] }} {{ __('series.public.article_unit') }}
                            </span>
                        </div>

                        {{-- Body --}}
                        <div class="p-5 flex flex-col flex-1">
                            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-2 line-clamp-2 group-hover:text-primary transition-colors leading-snug">
                                {{ $item['title'] }}
                            </h2>
                            @if ($item['description'])
                                <p class="text-sm text-gray-600 dark:text-gray-300 line-clamp-3 mb-4 flex-1 leading-relaxed">{{ $item['description'] }}</p>
                            @endif
                            <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 pt-3 border-t border-gray-100 dark:border-[#0f3460] mt-auto">
                                <span class="text-gray-400 dark:text-gray-500">{{ $item['dateLabel'] }}</span>
                                <span class="series-cue inline-flex items-center gap-1 font-semibold text-primary">
                                    {{ __('series.public.browse_series') }}
                                    <i data-lucide="arrow-{{ $isRtlLocale ? 'left' : 'right' }}" class="w-3.5 h-3.5"></i>
                                </span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>

@endsection
