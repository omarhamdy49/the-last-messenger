@extends('layouts.public')

@section('title', $article['title'])
@section('meta_description', $article['seoDescription'] ?? \Illuminate\Support\Str::limit(strip_tags($article['excerpt'] ?? ''), 155))
@section('canonical', $article['canonical'])
@section('og_type', 'article')
@section('og_image', $article['image'])

@php
    $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true);
    $tocJson = json_encode(array_column($article['toc'], 'id'), JSON_UNESCAPED_UNICODE);
@endphp

@push('head')
    @if (! empty($article['datePublishedIso']))
        <meta property="article:published_time" content="{{ $article['datePublishedIso'] }}">
    @endif
    @if (! empty($article['dateModifiedIso']))
        <meta property="article:modified_time" content="{{ $article['dateModifiedIso'] }}">
    @endif
    @if (! empty($article['author']))
        <meta property="article:author" content="{{ $article['author'] }}">
    @endif
    @if (! empty($article['category']))
        <meta property="article:section" content="{{ $article['category'] }}">
    @endif
@endpush

@push('structured_data')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "Article",
    "headline": @json(\Illuminate\Support\Str::limit($article['title'], 110, '')),
    "description": @json($article['seoDescription'] ?? ''),
    "image": @json($article['image']),
    "author": { "@@type": "Person", "name": @json($article['author']) },
    "publisher": {
        "@@type": "Organization",
        "name": @json(__('site.site_name')),
        "logo": { "@@type": "ImageObject", "url": @json(asset('images/site/rasool-allah-seal.webp')) }
    },
@if (! empty($article['datePublishedIso']))
    "datePublished": @json($article['datePublishedIso']),
@endif
@if (! empty($article['dateModifiedIso']))
    "dateModified": @json($article['dateModifiedIso']),
@endif
    "inLanguage": "{{ app()->getLocale() }}",
    "mainEntityOfPage": { "@@type": "WebPage", "@@id": @json($article['canonical']) }
}
</script>
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [
        { "@@type": "ListItem", "position": 1, "name": @json(__('site.home')), "item": @json(url('/')) },
        { "@@type": "ListItem", "position": 2, "name": @json(__('articles.public.breadcrumb')), "item": @json(route('public.articles')) },
        { "@@type": "ListItem", "position": 3, "name": @json($article['title']) }
    ]
}
</script>
@endpush

@push('head')
<style>
    /* Active TOC item (scroll-spy) */
    .toc-link.toc-active {
        color: #218838;
        font-weight: 600;
        background: rgba(100, 204, 197, 0.10);
        border-inline-start-color: #64CCC5;
    }
    /* Horizontal carousel scrollbar */
    .rail-scroll::-webkit-scrollbar { height: 6px; }
    .rail-scroll::-webkit-scrollbar-track { background: transparent; }
    .rail-scroll::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, #0056b3, #218838);
        border-radius: 9999px;
    }
    /* Quranic / poetic blockquotes inside CMS body */
    .prose-article blockquote { font-family: 'Amiri', serif; }

    /* ── Inline module embeds (verse / hadith / sahabi / seerah) ── */
    .module-embed {
        display: block;
        margin: 1.75rem 0;
        padding: 1.25rem 1.25rem 1rem;
        border-radius: 1rem;
        border-inline-start: 4px solid var(--embed-accent, #0056b3);
        background: var(--embed-bg, #f8fafc);
    }
    .module-embed--verse  { --embed-accent: #218838; --embed-bg: #f0fdf4; }
    .module-embed--hadith { --embed-accent: #0056b3; --embed-bg: #eff6ff; }
    .module-embed--sahabi { --embed-accent: #0d9488; --embed-bg: #f0fdfa; }
    .module-embed--seerah { --embed-accent: #7c3aed; --embed-bg: #f5f3ff; }
    .dark .module-embed { background: rgba(255, 255, 255, 0.03); }
    .module-embed__label {
        display: inline-flex; align-items: center; gap: .4rem;
        font-size: .72rem; font-weight: 700; letter-spacing: .02em;
        padding: .28rem .75rem; border-radius: 9999px; margin-bottom: .85rem;
        color: #fff; background: var(--embed-accent, #0056b3);
    }
    .module-embed__label i, .module-embed__link i { width: 1rem; height: 1rem; }
    .module-embed__body { font-size: 1rem; line-height: 1.85; }
    .module-embed__arabic { font-family: 'Amiri', serif; font-size: 1.35rem; line-height: 2.2; }
    .module-embed__arabic p { margin: 0 0 .35rem; }
    .verse-number {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 1.6rem; height: 1.6rem; padding: 0 .35rem; margin-inline-start: .3rem;
        border-radius: 9999px; font-size: .8rem; font-family: 'Cairo', sans-serif;
        color: var(--embed-accent, #218838);
        background: rgba(100, 204, 197, .18);
    }
    .module-embed__footer { display: flex; align-items: center; gap: .65rem; flex-wrap: wrap; margin-top: .9rem; }
    .module-embed__link {
        display: inline-flex; align-items: center; gap: .4rem; margin-top: .9rem;
        font-size: .85rem; font-weight: 700; text-decoration: none;
        color: var(--embed-accent, #0056b3); transition: gap .15s ease;
    }
    .module-embed__footer .module-embed__link { margin-top: 0; }
    .module-embed__link:hover { gap: .7rem; text-decoration: underline; }
    .module-embed__source { display: inline-block; margin-top: .9rem; font-size: .85rem; font-weight: 600; color: #6b7280; }
    .module-embed__kunya, .module-embed__year { color: #6b7280; font-weight: 500; }
    .dark .module-embed__source, .dark .module-embed__kunya, .dark .module-embed__year { color: #9ca3af; }
    .module-embed__grade { font-size: .7rem; font-weight: 700; padding: .15rem .55rem; border-radius: 9999px; }
    .module-embed__grade--sahih, .module-embed__grade--صحيح { background: #dcfce7; color: #166534; }
    .module-embed__grade--hasan, .module-embed__grade--حسن { background: #fef9c3; color: #854d0e; }
    .module-embed__grade--daif, .module-embed__grade--ضعيف,
    .module-embed__grade--mawdu, .module-embed__grade--موضوع { background: #fee2e2; color: #991b1b; }

    /* ── Hero gallery: cinematic cross-dissolve + Ken Burns ── */
    .gallery-img { view-transition-name: gallery-hero; will-change: transform; backface-visibility: hidden; }
    @media (prefers-reduced-motion: no-preference) {
        .gallery-stage .gallery-img { animation: ken-burns 24s ease-in-out infinite alternate; }
    }
    @keyframes ken-burns {
        from { transform: scale(1.05) translate3d(0, 0, 0); }
        to   { transform: scale(1.14) translate3d(-2%, -1.6%, 0); }
    }
    /* Keep the page itself static; only the hero image cross-dissolves. */
    ::view-transition-group(root) { animation-duration: 0.001s; }
    ::view-transition-old(gallery-hero),
    ::view-transition-new(gallery-hero) {
        animation-duration: 0.55s;
        animation-timing-function: cubic-bezier(.4, 0, .2, 1);
        mix-blend-mode: normal;
    }
    @media (prefers-reduced-motion: reduce) {
        ::view-transition-group(*), ::view-transition-old(*), ::view-transition-new(*) { animation: none !important; }
        .gallery-stage .gallery-img { animation: none !important; }
    }

    @media print {
        header, footer, aside, .no-print { display: none !important; }
    }
</style>
@endpush

@section('content')

<div
    x-data="articleReader({ tocIds: {{ $tocJson }}, title: @js($article['title']) })"
    x-init="init()"
    class="min-h-screen"
>
    {{-- Reading progress bar --}}
    <div class="fixed top-0 inset-x-0 z-[60] h-1 bg-transparent no-print">
        <div class="h-full bg-gradient-to-l from-primary via-secondary to-accent transition-[width] duration-150 ease-out"
             :style="`width: ${progress}%`"></div>
    </div>

    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <x-site.breadcrumb
            :items="[
                ['label' => __('articles.public.breadcrumb'), 'url' => route('public.articles')],
                ['label' => $article['title']],
            ]"
            class="mb-8 no-print"
        />

        <div class="grid grid-cols-1 lg:grid-cols-12 lg:gap-8">

            {{-- ============================================================ --}}
            {{-- MAIN ARTICLE --}}
            {{-- ============================================================ --}}
            <div class="lg:col-span-8 xl:col-span-9 min-w-0">
                <article class="card-premium overflow-hidden shadow-xl animate-fade-in-up">

                    {{-- Hero — premium gallery slider (falls back to the single cover image) --}}
                    @php
                        $heroImages = ! empty($article['gallery'])
                            ? $article['gallery']
                            : (! empty($article['image']) ? [['url' => $article['image'], 'caption' => null]] : []);
                    @endphp
                    @if (! empty($heroImages))
                        <div x-data="gallerySlider(@js($heroImages), @js($article['title']), {{ $isRtl ? 'true' : 'false' }})"
                             x-init="init()"
                             @keydown.window="onKey($event)"
                             @mouseenter="hover = true" @mouseleave="hover = false">

                            {{-- Main stage --}}
                            <div class="gallery-stage group relative h-60 md:h-96 overflow-hidden bg-gray-100 dark:bg-[#0f3460] cursor-zoom-in"
                                 @click="openLightbox()">
                                <img :src="current.url" :alt="current.caption || title"
                                     onerror="this.style.display='none';this.parentElement.classList.add('img-gradient-fallback')"
                                     onload="this.style.display='';this.parentElement.classList.remove('img-gradient-fallback')"
                                     class="gallery-img w-full h-full object-cover">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/65 via-black/10 to-transparent pointer-events-none"></div>

                                {{-- Per-image caption + article category/tag chips --}}
                                <div class="absolute bottom-5 start-5 end-5 space-y-2 pointer-events-none z-10">
                                    <p x-show="current.caption" x-cloak x-text="current.caption"
                                       class="text-white text-sm md:text-base font-medium drop-shadow line-clamp-2"></p>
                                    @if (! empty($article['category']) || ! empty($article['tags']))
                                        <div class="flex flex-wrap gap-2">
                                            @if (! empty($article['category']))
                                                <span class="px-4 py-1.5 rounded-full text-sm font-semibold bg-gradient-to-l from-primary to-secondary text-white shadow-lg">{{ $article['category'] }}</span>
                                            @endif
                                            @foreach (array_slice($article['tags'], 0, 2) as $tag)
                                                <span class="px-4 py-1.5 rounded-full text-sm font-medium glass text-white">{{ $tag }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                {{-- Multi-image controls --}}
                                <template x-if="imgs.length > 1">
                                    <div>
                                        {{-- counter --}}
                                        <span dir="ltr" class="absolute top-4 {{ $isRtl ? 'start-4' : 'end-4' }} px-3 py-1 rounded-full bg-black/50 backdrop-blur text-white text-xs font-medium tabular-nums z-10">
                                            <span x-text="gi + 1"></span> / <span x-text="imgs.length"></span>
                                        </span>
                                        {{-- autoplay toggle --}}
                                        <button type="button" @click.stop="togglePlay()" :aria-label="playing ? 'pause' : 'play'"
                                                class="absolute top-4 {{ $isRtl ? 'end-4' : 'start-4' }} w-9 h-9 rounded-full backdrop-blur flex items-center justify-center text-white transition-all z-10"
                                                :class="playing ? 'bg-gradient-to-l from-primary to-secondary shadow-lg' : 'bg-black/40 hover:bg-black/60'">
                                            <i x-show="!playing" data-lucide="play" class="w-4 h-4"></i>
                                            <i x-show="playing" x-cloak data-lucide="pause" class="w-4 h-4"></i>
                                        </button>
                                        {{-- prev / next --}}
                                        <button type="button" @click.stop="prev()" aria-label="{{ __('articles.public.previous') }}"
                                                class="absolute start-4 top-1/2 -translate-y-1/2 w-11 h-11 rounded-full bg-white/20 backdrop-blur flex items-center justify-center text-white opacity-0 group-hover:opacity-100 focus-visible:opacity-100 hover:bg-white/35 hover:scale-110 transition-all z-10">
                                            <i data-lucide="chevron-{{ $isRtl ? 'right' : 'left' }}" class="w-6 h-6"></i>
                                        </button>
                                        <button type="button" @click.stop="next()" aria-label="{{ __('articles.public.next') }}"
                                                class="absolute end-4 top-1/2 -translate-y-1/2 w-11 h-11 rounded-full bg-white/20 backdrop-blur flex items-center justify-center text-white opacity-0 group-hover:opacity-100 focus-visible:opacity-100 hover:bg-white/35 hover:scale-110 transition-all z-10">
                                            <i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" class="w-6 h-6"></i>
                                        </button>
                                        {{-- dot indicators (desktop) --}}
                                        <div class="absolute bottom-4 left-1/2 -translate-x-1/2 hidden sm:flex items-center gap-1.5 z-10">
                                            <template x-for="(img, i) in imgs" :key="i">
                                                <button type="button" @click.stop="select(i)" :aria-label="'image ' + (i + 1)"
                                                        class="h-2 rounded-full transition-all duration-300"
                                                        :class="gi === i ? 'w-6 bg-white' : 'w-2 bg-white/50 hover:bg-white/80'"></button>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            {{-- Thumbnail strip under the main image --}}
                            <div x-show="imgs.length > 1" class="flex gap-2 overflow-x-auto rail-scroll px-4 py-3 bg-gray-50 dark:bg-[#0f3460]/40 border-b border-gray-100 dark:border-[#0f3460]">
                                <template x-for="(img, i) in imgs" :key="i">
                                    <button type="button" :data-thumb="i" @click="select(i)"
                                            class="relative w-20 h-14 rounded-lg overflow-hidden shrink-0 bg-gray-100 dark:bg-[#0f3460] transition-all"
                                            :class="gi === i ? 'ring-2 ring-primary' : 'opacity-60 hover:opacity-100'">
                                        <img :src="img.url" :alt="img.caption || title"
                                             onerror="this.style.display='none';this.parentElement.classList.add('img-gradient-fallback')"
                                             class="w-full h-full object-cover">
                                    </button>
                                </template>
                            </div>

                            {{-- Fullscreen lightbox — teleported to <body> so the article's
                                 transformed ancestor (animate-fade-in-up) can't clip the fixed overlay. --}}
                            <template x-teleport="body">
                            <div x-show="lightbox" x-cloak x-transition.opacity
                                 class="fixed inset-0 z-[110] bg-black/95 backdrop-blur flex flex-col no-print">
                                <div class="flex items-center gap-3 p-4 text-white">
                                    <span dir="ltr" x-show="imgs.length > 1" class="px-3 py-1 rounded-full bg-white/10 text-sm font-medium tabular-nums">
                                        <span x-text="gi + 1"></span> / <span x-text="imgs.length"></span>
                                    </span>
                                    <div class="flex items-center gap-2 ms-auto">
                                        <button @click="closeLightbox()" class="w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors" aria-label="{{ __('articles.public.close') }}">
                                            <i data-lucide="x" class="w-5 h-5"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="flex-1 relative flex items-center justify-center px-4 pb-2 min-h-0" @click.self="closeLightbox()">
                                    <img :src="current.url" :alt="current.caption || title"
                                         class="max-w-full max-h-full object-contain rounded-lg shadow-2xl">
                                    <template x-if="imgs.length > 1">
                                        <div>
                                            <button @click="prev()" aria-label="{{ __('articles.public.previous') }}"
                                                    class="absolute start-4 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-white/15 hover:bg-white/30 backdrop-blur flex items-center justify-center text-white transition-all">
                                                <i data-lucide="chevron-{{ $isRtl ? 'right' : 'left' }}" class="w-6 h-6"></i>
                                            </button>
                                            <button @click="next()" aria-label="{{ __('articles.public.next') }}"
                                                    class="absolute end-4 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-white/15 hover:bg-white/30 backdrop-blur flex items-center justify-center text-white transition-all">
                                                <i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" class="w-6 h-6"></i>
                                            </button>
                                        </div>
                                    </template>
                                    <p x-show="current.caption" x-cloak x-text="current.caption"
                                       class="absolute bottom-3 inset-x-0 mx-auto w-fit max-w-2xl text-center text-white text-sm bg-black/50 backdrop-blur rounded-full py-2 px-5"></p>
                                </div>
                                <div x-show="imgs.length > 1" class="flex gap-2 overflow-x-auto rail-scroll px-4 py-3 justify-start sm:justify-center">
                                    <template x-for="(img, i) in imgs" :key="i">
                                        <button type="button" :data-thumb="i" @click="select(i)"
                                                class="relative w-20 h-14 rounded-lg overflow-hidden shrink-0 transition-all"
                                                :class="gi === i ? 'ring-2 ring-white' : 'opacity-50 hover:opacity-100'">
                                            <img :src="img.url" :alt="img.caption || title"
                                                 onerror="this.style.display='none';this.parentElement.classList.add('img-gradient-fallback')"
                                                 class="w-full h-full object-cover">
                                        </button>
                                    </template>
                                </div>
                            </div>
                            </template>
                        </div>
                    @endif

                    {{-- Body wrapper (font-size scalable) --}}
                    <div class="p-6 md:p-10" :style="`font-size: ${fontSize}%`">

                        {{-- Meta row: dates + views on one side, share + font controls on the left --}}
                        <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-3 mb-6">
                            <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                                <span class="inline-flex items-center gap-1.5">
                                    <i data-lucide="calendar" class="w-4 h-4 text-accent"></i>{{ $article['dateLabel'] }}
                                </span>
                                @if (! empty($article['hijriDate']))
                                    <span class="inline-flex items-center gap-1.5">
                                        <i data-lucide="moon" class="w-4 h-4 text-accent"></i>{{ $article['hijriDate'] }}
                                    </span>
                                @endif
                                <span class="inline-flex items-center gap-1.5">
                                    <i data-lucide="clock" class="w-4 h-4 text-accent"></i>{{ $article['readTime'] }} {{ __('articles.public.min_read') }}
                                </span>
                                <span class="inline-flex items-center gap-1.5">
                                    <i data-lucide="eye" class="w-4 h-4 text-accent"></i><span x-text="views.toLocaleString('en-US')">{{ number_format($article['views']) }}</span> {{ __('articles.public.views') }}
                                </span>
                            </div>

                            {{-- Share + font size (left of the counts/dates) --}}
                            <div class="flex items-center gap-2 no-print">
                                <button @click="showShare = true"
                                        class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold bg-gray-100 dark:bg-[#0f3460] text-gray-700 dark:text-gray-200 transition-all hover:scale-105 hover:text-primary">
                                    <i data-lucide="share-2" class="w-4 h-4"></i>{{ __('articles.public.share') }}
                                </button>
                                <div class="inline-flex items-center gap-1 rounded-full bg-gray-100 dark:bg-[#0f3460] p-1">
                                    <button @click="decreaseFont()" aria-label="A-" class="w-8 h-8 rounded-full flex items-center justify-center text-gray-600 dark:text-gray-300 hover:bg-white dark:hover:bg-[#16213e] transition-colors">
                                        <i data-lucide="minus" class="w-4 h-4"></i>
                                    </button>
                                    <span class="px-1 text-xs font-semibold text-gray-700 dark:text-gray-200 w-10 text-center" x-text="fontSize + '%'"></span>
                                    <button @click="increaseFont()" aria-label="A+" class="w-8 h-8 rounded-full flex items-center justify-center text-gray-600 dark:text-gray-300 hover:bg-white dark:hover:bg-[#16213e] transition-colors">
                                        <i data-lucide="plus" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Title --}}
                        <h1 class="text-3xl md:text-4xl xl:text-5xl font-bold mb-6 leading-tight text-gray-900 dark:text-white break-words">
                            {{ $article['title'] }}
                        </h1>

                        @if (! empty($article['excerpt']))
                            <p class="text-lg text-gray-600 dark:text-gray-300 leading-relaxed mb-8 break-words">
                                {{ $article['excerpt'] }}
                            </p>
                        @endif

                        {{-- Mobile TOC (collapsible) --}}
                        @if (! empty($article['toc']))
                            <details class="lg:hidden rounded-xl border border-gray-100 dark:border-[#0f3460] p-4 mb-8 no-print" open>
                                <summary class="cursor-pointer font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <i data-lucide="list" class="w-4 h-4 text-accent"></i>{{ __('articles.public.table_of_contents') }}
                                </summary>
                                <ul class="mt-3 space-y-1.5">
                                    @foreach ($article['toc'] as $item)
                                        <li @class(['ps-3' => ($item['level'] ?? 2) === 3])>
                                            <a href="#{{ $item['id'] }}" class="text-sm text-gray-600 dark:text-gray-300 hover:text-primary transition-colors">{{ $item['label'] }}</a>
                                        </li>
                                    @endforeach
                                </ul>
                            </details>
                        @endif

                        {{-- Article body (sanitized CMS content) — collapses behind a
                             read-more fade when it exceeds maxCollapsed px. --}}
                        @if (! empty($article['body']))
                            <div class="relative">
                                <div x-ref="articleBody"
                                     class="prose-article max-w-none break-words"
                                     :class="(bodyOverflow && !bodyExpanded) ? 'overflow-hidden' : ''"
                                     :style="(bodyOverflow && !bodyExpanded) ? `max-height: ${maxCollapsed}px` : ''">
                                    {!! $article['body'] !!}
                                </div>
                                <div x-show="bodyOverflow && !bodyExpanded" x-cloak
                                     class="absolute inset-x-0 bottom-0 h-28 bg-gradient-to-t from-white dark:from-[#16213e] to-transparent pointer-events-none"></div>
                            </div>
                            <div x-show="bodyOverflow" x-cloak class="mt-5 flex justify-center no-print">
                                <button @click="toggleBody()"
                                        class="inline-flex items-center gap-2 px-6 py-2.5 rounded-full text-sm font-semibold bg-gray-100 dark:bg-[#0f3460] text-gray-700 dark:text-gray-200 hover:bg-primary/10 hover:text-primary transition-all">
                                    <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" :class="bodyExpanded ? 'rotate-180' : ''"></i>
                                    <span x-text="bodyExpanded ? '{{ __('articles.public.read_less') }}' : '{{ __('articles.public.read_more') }}'"></span>
                                </button>
                            </div>
                        @else
                            <x-site.empty-state icon="file-text" :message="__('articles.public.empty_message')" class="!py-12" />
                        @endif

                        {{-- Sources & References --}}
                        @if (! empty($article['references']))
                            <div class="mt-12 pt-8 border-t border-gray-100 dark:border-[#0f3460]">
                                <h3 class="text-xl font-bold mb-4 flex items-center gap-3 text-gray-900 dark:text-white">
                                    <i data-lucide="book-open" class="w-5 h-5 text-accent"></i>{{ __('articles.public.sources') }}
                                </h3>
                                <ul class="space-y-3">
                                    @foreach ($article['references'] as $ref)
                                        <li class="flex items-start gap-3 text-gray-600 dark:text-gray-300">
                                            <i data-lucide="book" class="w-4 h-4 mt-1 text-accent shrink-0"></i>
                                            <span class="break-words">
                                                @if (! empty($ref['url']))
                                                    <a href="{{ $ref['url'] }}" target="_blank" rel="noopener nofollow"
                                                       class="font-semibold text-gray-800 dark:text-gray-100 hover:text-primary hover:underline">{{ $ref['name'] }}</a>
                                                @else
                                                    <strong class="text-gray-800 dark:text-gray-100">{{ $ref['name'] }}</strong>
                                                @endif
                                                @if (! empty($ref['type']))
                                                    <span class="ms-2 px-2 py-0.5 rounded text-[11px] font-medium bg-gray-100 dark:bg-[#0f3460] text-gray-500 dark:text-gray-400">{{ $ref['type'] }}</span>
                                                @endif
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- Tags --}}
                        @if (! empty($article['tags']))
                            <div class="flex flex-wrap items-center gap-2 mt-8 pt-6 border-t border-gray-100 dark:border-[#0f3460]">
                                <span class="inline-flex items-center gap-1.5 text-sm font-semibold text-gray-500 dark:text-gray-400 me-1">
                                    <i data-lucide="tag" class="w-4 h-4"></i>{{ __('articles.public.tags') }}:
                                </span>
                                @foreach ($article['tags'] as $tag)
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 dark:bg-[#0f3460] text-gray-700 dark:text-gray-200">{{ $tag }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </article>

                {{-- ============================================================ --}}
                {{-- PREVIOUS / NEXT ARTICLE --}}
                {{-- ============================================================ --}}
                @if (! empty($prev) || ! empty($next))
                <nav class="mt-10 grid grid-cols-1 md:grid-cols-2 gap-4 no-print" aria-label="{{ __('articles.public.related_articles') }}">
                    @if (! empty($prev))
                        <a href="{{ $prev['url'] }}" class="group card-premium p-5 flex items-center gap-4 hover-lift">
                            <span class="w-11 h-11 rounded-full bg-gradient-to-l from-primary to-secondary text-white flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                                <i data-lucide="chevron-{{ $isRtl ? 'right' : 'left' }}" class="w-5 h-5"></i>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('articles.public.previous_article') }}</span>
                                <span class="block font-bold text-gray-900 dark:text-white line-clamp-1 group-hover:text-primary transition-colors">{{ $prev['title'] }}</span>
                            </span>
                        </a>
                    @else
                        <span class="hidden md:block"></span>
                    @endif

                    @if (! empty($next))
                        <a href="{{ $next['url'] }}" class="group card-premium p-5 flex items-center gap-4 hover-lift text-end">
                            <span class="flex-1 min-w-0">
                                <span class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('articles.public.next_article') }}</span>
                                <span class="block font-bold text-gray-900 dark:text-white line-clamp-1 group-hover:text-primary transition-colors">{{ $next['title'] }}</span>
                            </span>
                            <span class="w-11 h-11 rounded-full bg-gradient-to-l from-primary to-secondary text-white flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                                <i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" class="w-5 h-5"></i>
                            </span>
                        </a>
                    @endif
                </nav>
                @endif
            </div>

            {{-- ============================================================ --}}
            {{-- SIDEBAR --}}
            {{-- ============================================================ --}}
            <aside class="lg:col-span-4 xl:col-span-3 mt-8 lg:mt-0 no-print">
                <div class="lg:sticky lg:top-24 space-y-6">

                    {{-- TOC scroll-spy --}}
                    @if (! empty($article['toc']))
                        <nav class="card-premium p-5 hidden lg:block animate-fade-in-up" aria-label="{{ __('articles.public.table_of_contents') }}">
                            <h3 class="text-lg font-bold mb-4 flex items-center gap-2 text-gray-900 dark:text-white">
                                <i data-lucide="list" class="w-5 h-5 text-accent"></i>{{ __('articles.public.table_of_contents') }}
                            </h3>
                            <ul class="space-y-1">
                                @foreach ($article['toc'] as $item)
                                    <li @class(['ps-2' => ($item['level'] ?? 2) === 3])>
                                        <a href="#{{ $item['id'] }}"
                                           @click.prevent="scrollTo('{{ $item['id'] }}')"
                                           class="toc-link block px-3 py-2 rounded-lg text-sm border-s-2 border-transparent transition-all text-gray-600 dark:text-gray-300 hover:text-primary"
                                           :class="active === '{{ $item['id'] }}' ? 'toc-active' : ''">
                                            {{ $item['label'] }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </nav>
                    @endif

                    {{-- Quick stats (real statistics) --}}
                    @if (! empty($article['statistics']))
                        <div class="card-premium p-5 animate-fade-in-up stagger-1">
                            <h3 class="text-lg font-bold mb-4 flex items-center gap-2 text-gray-900 dark:text-white">
                                <i data-lucide="bar-chart-2" class="w-5 h-5 text-accent"></i>{{ __('articles.public.quick_stats') }}
                            </h3>
                            <div class="space-y-3">
                                @foreach ($article['statistics'] as $stat)
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-sm text-gray-500 dark:text-gray-400 break-words">{{ $stat['name'] }}</span>
                                        <span class="font-semibold text-gray-900 dark:text-white shrink-0">{{ $stat['value'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Tags widget --}}
                    @if (! empty($article['tags']))
                        <div class="card-premium p-5 animate-fade-in-up stagger-2">
                            <h3 class="text-lg font-bold mb-4 flex items-center gap-2 text-gray-900 dark:text-white">
                                <i data-lucide="tag" class="w-5 h-5 text-accent"></i>{{ __('articles.public.tags') }}
                            </h3>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($article['tags'] as $tag)
                                    <span class="px-3 py-1.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-[#0f3460] text-gray-700 dark:text-gray-200">{{ $tag }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Related articles (compact cards) --}}
                    @if (! empty($related))
                        <div class="card-premium p-5 animate-fade-in-up stagger-3">
                            <h3 class="text-lg font-bold mb-4 flex items-center gap-2 text-gray-900 dark:text-white">
                                <i data-lucide="layers" class="w-5 h-5 text-accent"></i>{{ __('articles.public.related_articles') }}
                            </h3>
                            <ul class="space-y-4">
                                @foreach (array_slice($related, 0, 5) as $item)
                                    <li>
                                        <a href="{{ $item['url'] }}" class="group flex items-start gap-3">
                                            <span class="w-16 h-16 rounded-lg overflow-hidden shrink-0 bg-gray-100 dark:bg-[#0f3460]">
                                                <img src="{{ $item['image'] }}" alt="{{ $item['title'] }}" loading="lazy"
                                                     onerror="this.onerror=null;this.style.display='none';this.parentElement.classList.add('img-gradient-fallback')"
                                                     class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                                            </span>
                                            <span class="min-w-0 flex-1">
                                                <span class="block text-sm font-bold text-gray-900 dark:text-white line-clamp-2 leading-snug group-hover:text-primary transition-colors">{{ $item['title'] }}</span>
                                                <span class="mt-1 inline-flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                                                    <i data-lucide="clock" class="w-3 h-3"></i>{{ $item['readTime'] }} {{ __('articles.public.min_read') }}
                                                </span>
                                            </span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Back to articles --}}
                    <a href="{{ route('public.articles') }}"
                       class="card-premium p-4 flex items-center gap-3 hover:bg-primary/5 transition-colors group">
                        <span class="w-9 h-9 rounded-full bg-primary/10 text-primary flex items-center justify-center group-hover:bg-primary group-hover:text-white transition-colors">
                            <i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" class="w-4 h-4"></i>
                        </span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ __('articles.public.back_to_articles') }}</span>
                    </a>
                </div>
            </aside>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- SHARE MODAL --}}
    {{-- ============================================================ --}}
    <div x-show="showShare" x-cloak @click.self="showShare = false" @keydown.escape.window="showShare = false"
         class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm no-print"
         x-transition.opacity>
        <div class="card-premium p-7 max-w-md w-full shadow-2xl" @click.stop x-transition.scale.origin.center>
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('articles.public.share_article') }}</h3>
                <button @click="showShare = false" aria-label="{{ __('articles.public.close') }}"
                        class="w-9 h-9 rounded-full flex items-center justify-center text-gray-500 hover:bg-gray-100 dark:hover:bg-[#0f3460]">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <button @click="shareTwitter()" class="flex flex-col items-center gap-2 p-5 rounded-xl bg-[#1DA1F2] text-white hover:scale-105 transition-transform">
                    <i data-lucide="twitter" class="w-7 h-7"></i><span class="text-sm font-medium">{{ __('articles.public.share_twitter') }}</span>
                </button>
                <button @click="shareFacebook()" class="flex flex-col items-center gap-2 p-5 rounded-xl bg-[#1877F2] text-white hover:scale-105 transition-transform">
                    <i data-lucide="facebook" class="w-7 h-7"></i><span class="text-sm font-medium">{{ __('articles.public.share_facebook') }}</span>
                </button>
                <button @click="shareWhatsapp()" class="flex flex-col items-center gap-2 p-5 rounded-xl bg-[#25D366] text-white hover:scale-105 transition-transform">
                    <i data-lucide="message-circle" class="w-7 h-7"></i><span class="text-sm font-medium">{{ __('articles.public.share_whatsapp') }}</span>
                </button>
                <button @click="copyLink()" class="flex flex-col items-center gap-2 p-5 rounded-xl bg-gray-100 dark:bg-[#0f3460] text-gray-700 dark:text-gray-200 hover:scale-105 transition-transform">
                    <i data-lucide="link" class="w-7 h-7"></i><span class="text-sm font-medium">{{ __('articles.public.copy_link') }}</span>
                </button>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
    function articleReader(config) {
        return {
            tocIds: config.tocIds || [],
            title: config.title || '',
            progress: 0,
            active: '',
            fontSize: 100,
            bodyExpanded: false,
            bodyOverflow: false,
            maxCollapsed: 1100,
            showShare: false,
            articleId: @json($article['id']),
            views: @json((int) $article['views']),

            init() {
                const fs = parseInt(localStorage.getItem('articleFontSize') || '100', 10);
                if (!Number.isNaN(fs)) this.fontSize = Math.min(150, Math.max(80, fs));
                this.onScroll();
                window.addEventListener('scroll', () => this.onScroll(), { passive: true });
                window.addEventListener('resize', () => this.measureBody(), { passive: true });
                this.$nextTick(() => {
                    this.measureBody();
                    if (window.lucide) window.lucide.createIcons();
                });
                this.recordView();
            },

            // Count this view via an async call so it keeps working while the
            // page itself is cached. Updates the displayed count if the server
            // incremented it (first view this session).
            recordView() {
                const token = document.querySelector('meta[name="csrf-token"]')?.content;
                fetch(`{{ route('public.articles.view', ['id' => $article['id']]) }}`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                })
                    .then((r) => (r.ok ? r.json() : null))
                    .then((data) => { if (data && typeof data.views === 'number') this.views = data.views; })
                    .catch(() => {});
            },

            measureBody() {
                const el = this.$refs.articleBody;
                if (!el) return;
                // Collapse the body to roughly one viewport height ("the view height shown").
                this.maxCollapsed = Math.max(420, Math.round(window.innerHeight * 0.85));
                this.bodyOverflow = el.scrollHeight > (this.maxCollapsed + 140);
            },

            toggleBody() {
                this.bodyExpanded = !this.bodyExpanded;
                this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
            },

            onScroll() {
                const doc = document.documentElement;
                const scrollable = doc.scrollHeight - doc.clientHeight;
                this.progress = scrollable > 0 ? Math.min(100, Math.round((doc.scrollTop / scrollable) * 100)) : 0;
                let current = '';
                for (const id of this.tocIds) {
                    const el = document.getElementById(id);
                    if (el && el.getBoundingClientRect().top <= 120) current = id;
                }
                if (current) this.active = current;
            },

            scrollTo(id) {
                // Expand collapsed content first so headings below the fold are reachable.
                const jump = () => {
                    const el = document.getElementById(id);
                    if (!el) return;
                    window.scrollTo({ top: el.getBoundingClientRect().top + window.scrollY - 90, behavior: 'smooth' });
                    this.active = id;
                };
                if (this.bodyOverflow && !this.bodyExpanded) {
                    this.bodyExpanded = true;
                    this.$nextTick(() => setTimeout(jump, 50));
                } else {
                    jump();
                }
            },

            increaseFont() { if (this.fontSize < 150) { this.fontSize += 10; localStorage.setItem('articleFontSize', this.fontSize); this.$nextTick(() => this.measureBody()); } },
            decreaseFont() { if (this.fontSize > 80) { this.fontSize -= 10; localStorage.setItem('articleFontSize', this.fontSize); this.$nextTick(() => this.measureBody()); } },

            shareTwitter() {
                const url = encodeURIComponent(window.location.href);
                window.open(`https://twitter.com/intent/tweet?url=${url}&text=${encodeURIComponent(this.title)}`, '_blank', 'noopener');
            },
            shareFacebook() {
                window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(window.location.href)}`, '_blank', 'noopener');
            },
            shareWhatsapp() {
                window.open(`https://wa.me/?text=${encodeURIComponent(this.title + ' ' + window.location.href)}`, '_blank', 'noopener');
            },
            copyLink() {
                navigator.clipboard?.writeText(window.location.href).then(() => {
                    if (this.$store.toast) this.$store.toast.push('{{ __('articles.public.link_copied') }}', 'success');
                    this.showShare = false;
                });
            },
        };
    }

    function gallerySlider(images, title, rtl) {
        return {
            imgs: Array.isArray(images) ? images : [],
            title: title || '',
            rtl: !!rtl,
            gi: 0,
            lightbox: false,
            playing: false,
            hover: false,
            reduced: false,
            timer: null,

            get current() { return this.imgs[this.gi] || { url: '', caption: null }; },

            init() {
                this.reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                this.$watch('gi', () => this.$nextTick(() => {
                    this.centerThumbs();
                    if (window.lucide) window.lucide.createIcons();
                }));
                this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
            },

            // Scoped cross-dissolve on the hero image via View Transitions; the
            // page root is overridden to swap instantly (see CSS), so only the
            // image dissolves. Falls back to an instant swap where unsupported.
            go(i) {
                const n = this.imgs.length;
                if (n < 2) return;
                const idx = ((i % n) + n) % n;
                if (idx === this.gi) return;
                const swap = () => { this.gi = idx; return this.$nextTick(); };
                if (!this.reduced && !this.lightbox && document.startViewTransition) {
                    document.startViewTransition(swap);
                } else {
                    swap();
                }
            },
            next() { this.go(this.gi + 1); this.bump(); },
            prev() { this.go(this.gi - 1); this.bump(); },
            select(i) { this.go(i); this.bump(); },

            _interval() {
                return setInterval(() => { if (!this.hover && !this.lightbox) this.go(this.gi + 1); }, 5000);
            },
            togglePlay() { this.playing ? this.stop() : this.start(); },
            start() { if (this.imgs.length < 2) return; this.playing = true; this.timer = this._interval(); },
            stop() { this.playing = false; clearInterval(this.timer); this.timer = null; },
            bump() { if (this.playing) { clearInterval(this.timer); this.timer = this._interval(); } },

            openLightbox() {
                this.lightbox = true;
                document.documentElement.style.overflow = 'hidden';
                this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); this.centerThumbs(); });
            },
            closeLightbox() {
                this.lightbox = false;
                document.documentElement.style.overflow = '';
            },

            onKey(e) {
                if (!this.lightbox) return;
                if (e.key === 'Escape') return this.closeLightbox();
                if (e.key === 'ArrowLeft') return this.rtl ? this.next() : this.prev();
                if (e.key === 'ArrowRight') return this.rtl ? this.prev() : this.next();
            },

            centerThumbs() {
                this.$root.querySelectorAll('[data-thumb]').forEach((el) => {
                    if (parseInt(el.dataset.thumb, 10) === this.gi) {
                        el.scrollIntoView({ inline: 'center', block: 'nearest', behavior: this.reduced ? 'auto' : 'smooth' });
                    }
                });
            },
        };
    }
</script>
@endpush

@endsection
