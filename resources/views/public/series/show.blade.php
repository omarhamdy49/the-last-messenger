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
    /* ── Path of Light: the reading path ───────────────────────────
       The connector is a track whose gradient fill follows the reading
       line (--jp, paint-only clip-path); step numbers ignite as they
       are passed. Track sits at 1.8125rem — the step chips' centre
       (start-3 = .75rem + half of w-9 = 1.125rem); the old 1.375rem
       line missed the chips by 7px. */
    .series-path { position: relative; --jp: 0; }
    /* On phones the cards sit beside the rail (ms-9) and the chips dock
       half-on-the-rail; the rail therefore runs at 2.25rem. From sm: the
       chips live inside the card padding and the rail moves to their
       centre at 1.8125rem (start-3 + half of w-9). */
    .series-path::before {
        content: ''; position: absolute; top: 1.5rem; bottom: 1.5rem;
        inset-inline-start: calc(2.25rem - 1px); width: 2px; border-radius: 2px;
        background: #e5e7eb;
    }
    .dark .series-path::before { background: rgba(255, 255, 255, .1); }
    .series-path-fill {
        position: absolute; top: 1.5rem; bottom: 1.5rem;
        inset-inline-start: calc(2.25rem - 1px); width: 2px; border-radius: 2px;
        pointer-events: none;
        background: linear-gradient(to bottom, #0056b3, #64CCC5 55%, #218838);
        clip-path: inset(0 0 calc((1 - var(--jp, 0)) * 100%) 0);
        transition: clip-path .18s ease-out;
    }
    @media (min-width: 640px) {
        .series-path::before,
        .series-path-fill { inset-inline-start: calc(1.8125rem - 1px); }
    }
    .series-step-num { transition: box-shadow .5s ease, filter .5s ease; }
    .series-step-card:not(.is-lit) .series-step-num { filter: grayscale(.4) brightness(.95); }
    .series-step-card.is-lit .series-step-num { box-shadow: 0 0 18px -3px rgba(100, 204, 197, .85); }

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

    /* Staggered arrival of the steps (added by JS only when IO + motion allow) */
    .series-reveal { opacity: 0; transform: translateY(16px); transition: opacity .5s ease-out, transform .55s cubic-bezier(.16,1,.3,1); }
    .series-reveal.is-visible { opacity: 1; transform: none; }

    /* Hero aurora glows drift slowly, matching the site's light language. */
    .series-glow {
        position: absolute; border-radius: 9999px; filter: blur(90px);
        opacity: .45; pointer-events: none;
    }
    .series-glow--a {
        width: 28rem; height: 28rem; inset-inline-start: -8rem; top: -12rem;
        background: radial-gradient(circle, #64CCC5 0%, transparent 70%);
        animation: series-drift-a 26s ease-in-out infinite;
    }
    .series-glow--b {
        width: 24rem; height: 24rem; inset-inline-end: -6rem; bottom: -12rem;
        background: radial-gradient(circle, #0056b3 0%, transparent 70%);
        animation: series-drift-b 22s ease-in-out infinite;
    }
    @keyframes series-drift-a { 0%, 100% { transform: translate(0, 0) scale(1); } 50% { transform: translate(28px, 16px) scale(1.07); } }
    @keyframes series-drift-b { 0%, 100% { transform: translate(0, 0) scale(1); } 50% { transform: translate(-24px, 12px) scale(1.05); } }

    @media (prefers-reduced-motion: reduce) {
        .series-step-card { transition: none !important; transform: none !important; }
        .series-path-fill, .series-step-num { transition: none; }
        .series-glow--a, .series-glow--b { animation: none; }
        .series-reveal { opacity: 1 !important; transform: none !important; transition: none !important; }
    }
</style>
@endpush

@section('content')

@php $isRtlLocale = in_array(app()->getLocale(), ['ar', 'ur'], true); @endphp

<div class="min-h-dvh">

    {{-- SERIES HEADER --}}
    <section class="relative overflow-hidden border-b border-gray-100 dark:border-[#0f3460]">
        <div class="absolute inset-0 bg-gradient-to-br from-primary via-[#136f4b] to-secondary" aria-hidden="true"></div>
        <div class="absolute inset-0 bg-black/25" aria-hidden="true"></div>
        {{-- Dotted lattice, fading downward (matches the library heroes) --}}
        <div class="absolute inset-0 opacity-[0.12] pointer-events-none"
             style="background-image: radial-gradient(#fff 1px, transparent 1.5px); background-size: 26px 26px; -webkit-mask-image: linear-gradient(to bottom, black, transparent); mask-image: linear-gradient(to bottom, black, transparent);"
             aria-hidden="true"></div>
        <div class="series-glow series-glow--a" aria-hidden="true"></div>
        <div class="series-glow series-glow--b" aria-hidden="true"></div>
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative py-14 md:py-20 text-white">
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
                <div class="series-path space-y-5 sm:space-y-4">
                    <div class="series-path-fill" aria-hidden="true"></div>
                    @foreach ($articles as $i => $article)
                        {{-- Chapter card. Phones read it as a story chapter: full-width
                             banner with the part label on the image, chip docked on the
                             rail. sm:+ keeps the compact horizontal row. --}}
                        <a href="{{ $article['url'] }}"
                           class="group series-step-card relative flex flex-col sm:flex-row sm:items-start sm:gap-4 ms-9 sm:ms-0 p-0 sm:p-5 sm:ps-16 active:scale-[0.99] transition-transform">
                            {{-- Step number — half on the rail on phones, inside the card from sm: --}}
                            <span class="series-step-num absolute -start-[1.125rem] top-7 sm:start-3 sm:top-5 w-9 h-9 rounded-full bg-gradient-to-l from-primary to-secondary text-white text-sm font-bold flex items-center justify-center shadow-md tabular-nums z-10">
                                {{ $i + 1 }}
                            </span>

                            @if (! empty($article['image']))
                                <span class="relative block w-full aspect-[2/1] rounded-t-2xl sm:rounded-xl sm:w-24 sm:h-20 sm:aspect-auto overflow-hidden shrink-0 bg-gray-100 dark:bg-[#0f3460]">
                                    <img src="{{ $article['image'] }}" alt="{{ $article['title'] }}" loading="lazy"
                                         onerror="this.onerror=null;this.style.display='none';this.parentElement.classList.add('img-gradient-fallback')"
                                         class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105 motion-reduce:transform-none">
                                    {{-- phone-only scrim + chapter label on the image --}}
                                    <span class="absolute inset-0 bg-gradient-to-t from-black/55 via-black/10 to-transparent sm:hidden" aria-hidden="true"></span>
                                    <span class="absolute bottom-2.5 start-3.5 sm:hidden inline-flex items-center gap-2 text-white text-xs font-bold drop-shadow">
                                        {{ __('series.public.part') }} {{ $i + 1 }}
                                        @if ($i === 0)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] bg-[#64CCC5]/90 text-gray-900">
                                                <i data-lucide="sparkles" class="w-3 h-3"></i>{{ __('series.public.start_here') }}
                                            </span>
                                        @endif
                                    </span>
                                </span>
                            @endif

                            <div class="min-w-0 flex-1 p-4 pt-3.5 sm:p-0">
                                {{-- kicker: on phones the banner already names the part,
                                     so it only renders here when there is no image (or from sm:) --}}
                                <div @class([
                                    'items-center gap-2 mb-1.5 flex-wrap',
                                    'hidden sm:flex' => ! empty($article['image']),
                                    'flex' => empty($article['image']),
                                ])>
                                    <span class="text-[11px] font-bold uppercase tracking-wide text-primary">{{ __('series.public.part') }} {{ $i + 1 }}</span>
                                    @if ($i === 0)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#64CCC5]/15 text-[#0a7d77] dark:text-[#64CCC5]">
                                            <i data-lucide="sparkles" class="w-3 h-3"></i>{{ __('series.public.start_here') }}
                                        </span>
                                    @endif
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
                                <div class="flex items-center gap-x-3 gap-y-1 sm:gap-4 text-xs text-gray-500 dark:text-gray-400 flex-wrap">
                                    <span class="inline-flex items-center gap-1">
                                        <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                                        {{ $article['readTime'] }}{{ __('series.public.min_read') }}
                                    </span>
                                    <span class="inline-flex items-center gap-1">
                                        <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                        {{ $article['views'] }} {{ __('series.public.views') }}
                                    </span>
                                    <span class="text-gray-400 dark:text-gray-500">{{ $article['dateLabel'] }}</span>
                                </div>
                            </div>
                            <span class="hidden sm:block self-center text-gray-300 dark:text-gray-600 group-hover:text-primary transition-colors shrink-0">
                                <i data-lucide="chevron-{{ $isRtlLocale ? 'left' : 'right' }}" class="w-5 h-5"></i>
                            </span>
                        </a>
                    @endforeach

                    {{-- Closing node — the journey has an ending, not just a last item. --}}
                    <div class="relative ms-9 sm:ms-0 sm:ps-16">
                        <span class="series-step-num absolute -start-[1.125rem] sm:start-3 top-5 w-9 h-9 rounded-full bg-gradient-to-l from-amber-400 to-orange-500 text-white flex items-center justify-center shadow-md z-10">
                            <i data-lucide="award" class="w-4 h-4"></i>
                        </span>
                        <div class="p-5 ps-8 sm:ps-5 rounded-2xl border border-dashed border-gray-200 dark:border-white/10">
                            <p class="font-bold text-gray-900 dark:text-white">{{ __('series.public.finish_title') }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ __('series.public.finish_message') }}</p>
                            <a href="{{ route('public.series') }}"
                               class="inline-flex items-center gap-1.5 mt-3 text-sm font-bold text-primary hover:underline">
                                {{ __('series.public.browse_more_series') }}
                                <i data-lucide="arrow-{{ $isRtlLocale ? 'left' : 'right' }}" class="w-4 h-4"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Path of Light on the reading path: one rAF-throttled scroll listener
    // drives the connector fill (--jp) and ignites passed step numbers.
    // Steps also arrive with a staggered reveal where motion is allowed.
    (function () {
        const path = document.querySelector('.series-path');
        if (! path) return;
        const steps = path.querySelectorAll('.series-step-card');
        const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        if (! reduce && 'IntersectionObserver' in window) {
            steps.forEach(el => el.classList.add('series-reveal'));
            const io = new IntersectionObserver((entries) => {
                entries.forEach(en => {
                    if (en.isIntersecting) { en.target.classList.add('is-visible'); io.unobserve(en.target); }
                });
            }, { rootMargin: '60px 0px', threshold: 0.05 });
            steps.forEach(el => io.observe(el));
            setTimeout(() => steps.forEach(el => el.classList.add('is-visible')), 2500);
        }

        let ticking = false;
        const update = () => {
            const rect = path.getBoundingClientRect();
            const line = window.innerHeight * 0.4;
            path.style.setProperty('--jp', Math.min(1, Math.max(0, (line - rect.top) / Math.max(rect.height, 1))).toFixed(4));
            steps.forEach(s => s.classList.toggle('is-lit', s.getBoundingClientRect().top < line));
        };
        const onScroll = () => {
            if (ticking) return;
            ticking = true;
            requestAnimationFrame(() => { ticking = false; update(); });
        };
        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onScroll, { passive: true });
        update();
    })();
</script>
@endpush

@endsection
