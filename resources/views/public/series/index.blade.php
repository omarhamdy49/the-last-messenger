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
    /* ── Full-bleed gradient hero (mirrors the series-detail banner) ── */
    .series-hero {
        position: relative;
        isolation: isolate;
        overflow: hidden;
        background: linear-gradient(135deg, #0056b3 0%, #136f4b 55%, #218838 100%);
    }
    .series-hero::before {
        content: ''; position: absolute; inset: 0; z-index: -1;
        background: rgba(0, 0, 0, .25);
    }
    /* Dotted lattice fading downward */
    .series-hero-pattern {
        position: absolute; inset: 0; pointer-events: none;
        background-image: radial-gradient(#fff 1px, transparent 1.5px);
        background-size: 26px 26px;
        opacity: .12;
        -webkit-mask-image: linear-gradient(to bottom, black, transparent);
        mask-image: linear-gradient(to bottom, black, transparent);
    }
    /* Soft aurora glows on the gradient */
    .series-hero-glow {
        position: absolute; border-radius: 9999px; filter: blur(90px);
        opacity: .45; pointer-events: none;
    }
    .series-hero-glow--a {
        width: 30rem; height: 30rem; inset-inline-start: -8rem; top: -12rem;
        background: radial-gradient(circle, #64CCC5 0%, transparent 70%);
    }
    .series-hero-glow--b {
        width: 26rem; height: 26rem; inset-inline-end: -6rem; bottom: -12rem;
        background: radial-gradient(circle, #0056b3 0%, transparent 70%);
    }

    /* ── Series cards ── */
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

    /* Cover band: gradient + its own faint lattice */
    .series-card-cover {
        position: relative; overflow: hidden;
        background: linear-gradient(135deg, #0056b3 0%, #136f4b 55%, #218838 100%);
    }
    .series-card-cover::after {
        content: ''; position: absolute; inset: 0;
        background-image: radial-gradient(#fff 1px, transparent 1.5px);
        background-size: 22px 22px;
        opacity: .14;
        pointer-events: none;
    }

    @media (prefers-reduced-motion: reduce) {
        .series-card, .series-cue { transition: none !important; transform: none !important; }
    }
</style>
@endpush

@section('content')

@php
    $isRtlLocale = in_array(app()->getLocale(), ['ar', 'ur'], true);
    $totalArticles = array_sum(array_column($series, 'articlesCount'));
    $totalMinutes = array_sum(array_column($series, 'totalReadTime'));
@endphp

<div class="min-h-screen">

    {{-- ============================================================ --}}
    {{-- HERO — full-bleed brand gradient, matching the detail banner --}}
    {{-- ============================================================ --}}
    <section class="series-hero border-b border-gray-100 dark:border-[#0f3460]">
        <div class="series-hero-glow series-hero-glow--a" aria-hidden="true"></div>
        <div class="series-hero-glow series-hero-glow--b" aria-hidden="true"></div>
        <div class="series-hero-pattern" aria-hidden="true"></div>

        <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative py-16 md:py-24 text-white">
            <div class="max-w-3xl mx-auto text-center fade-in-up">
                <div class="flex justify-center mb-6">
                    <div class="w-16 h-16 rounded-2xl bg-white/15 backdrop-blur flex items-center justify-center shadow-lg">
                        <i data-lucide="layers" class="w-8 h-8"></i>
                    </div>
                </div>

                <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-sm font-semibold bg-white/15 backdrop-blur mb-5">
                    <i data-lucide="route" class="w-4 h-4"></i>
                    {{ __('series.public.reading_path') }}
                </span>

                <h1 class="text-4xl md:text-5xl font-bold mb-4 tracking-tight">
                    {{ __('series.public.hero_title') }}
                </h1>
                <p class="text-base md:text-lg text-white/85 max-w-2xl mx-auto leading-relaxed">
                    {{ __('series.public.hero_subtitle') }}
                </p>

                {{-- Stats strip --}}
                @if (count($series) > 0)
                    <div class="flex items-center justify-center gap-4 sm:gap-6 mt-9 flex-wrap">
                        <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold bg-white/15 backdrop-blur">
                            <i data-lucide="layers" class="w-4 h-4"></i>
                            <span class="font-bold tabular-nums">{{ count($series) }}</span>
                            {{ __('series.public.series_unit') }}
                        </span>
                        <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold bg-white/15 backdrop-blur">
                            <i data-lucide="file-text" class="w-4 h-4"></i>
                            <span class="font-bold tabular-nums">{{ $totalArticles }}</span>
                            {{ __('series.public.article_unit') }}
                        </span>
                        @if ($totalMinutes > 0)
                            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold bg-white/15 backdrop-blur">
                                <i data-lucide="clock" class="w-4 h-4"></i>
                                <span class="font-bold tabular-nums">{{ $totalMinutes }}</span>{{ __('series.public.min_read') }}
                            </span>
                        @endif
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
                        {{-- Cover: kept clean; the meta reads better as text below. --}}
                        <div class="series-card-cover aspect-[16/9]">
                            @if ($item['thumbnail'])
                                <img src="{{ $item['thumbnail'] }}" alt="{{ $item['title'] }}" loading="lazy"
                                     class="relative z-10 w-full h-full object-cover transition-transform duration-700 ease-out group-hover:scale-[1.04] motion-reduce:transform-none">
                                <div class="absolute inset-0 z-20 bg-gradient-to-t from-black/25 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                            @else
                                <div class="absolute inset-0 flex items-center justify-center text-white/80">
                                    <i data-lucide="layers" class="w-12 h-12"></i>
                                </div>
                            @endif
                        </div>

                        {{-- Body --}}
                        <div class="p-5 flex flex-col flex-1">
                            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-2 line-clamp-2 group-hover:text-primary transition-colors leading-snug">
                                {{ $item['title'] }}
                            </h2>

                            @if ($item['description'])
                                <p class="text-sm text-gray-600 dark:text-gray-300 line-clamp-2 leading-relaxed">{{ $item['description'] }}</p>
                            @endif

                            {{-- Scale of the reading path, without listing it --}}
                            <div class="flex items-center gap-3 mt-4 text-xs font-semibold text-gray-500 dark:text-gray-400">
                                <span class="inline-flex items-center gap-1.5">
                                    <i data-lucide="layers" class="w-3.5 h-3.5 text-primary"></i>
                                    <span class="tabular-nums">{{ $item['articlesCount'] }}</span>
                                    {{ __('series.public.article_unit') }}
                                </span>
                                @if (! empty($item['totalReadTime']))
                                    <span class="w-1 h-1 rounded-full bg-gray-300 dark:bg-gray-600"></span>
                                    <span class="inline-flex items-center gap-1.5">
                                        <i data-lucide="clock" class="w-3.5 h-3.5 text-primary"></i>
                                        <span class="tabular-nums">{{ $item['totalReadTime'] }}</span>{{ __('series.public.min_read') }}
                                    </span>
                                @endif
                            </div>

                            <div class="flex items-center justify-between text-xs pt-3 border-t border-gray-100 dark:border-[#0f3460] mt-4">
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
