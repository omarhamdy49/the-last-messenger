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
    /* ═══════════════════════════════════════════════════════════════
       THE READING INSTRUMENT

       Everything here exists to carry a reader through a long study:
       a measured column, an opening that marks where reading begins, a
       rail that reports position, and citations that arrive rather than
       appear. Motion is one authored idea — approach — not scattered
       effects, and every part of it is optional at the OS level.
       ═══════════════════════════════════════════════════════════════ */

    /* ── Measure ──
       The body fills its container. A capped measure was tried and rejected:
       it left the reading column visibly narrower than the surrounding card. */
    .prose-article { max-width: none; }

    /* ── The opening ──
       Deliberately NOT a drop cap. Arabic is a cursive script: ::first-letter
       severs the opening letter from the rest of its word and breaks the
       joining, so "كانت" renders as a stranded "ك" followed by "انت".
       The opening is marked by weight and size across the whole first
       paragraph instead, which no script objects to. */
    .prose-article > p:first-of-type {
        font-size: 1.125em;
        line-height: 1.95;
        color: #111827;
    }
    .dark .prose-article > p:first-of-type { color: #f3f4f6; }

    /* ── The rail ──
       Position reported as a continuous track rather than a list of
       pills: one hairline, one travelling marker, labels that step
       forward as they take focus. */
    .toc-rail { position: relative; }
    .toc-rail::before {
        content: '';
        position: absolute;
        inset-block: 0;
        inset-inline-start: 0;
        width: 1px;
        background: currentColor;
        opacity: .14;
    }
    .toc-rail__marker {
        position: absolute;
        inset-inline-start: -1px;
        width: 3px;
        background: #218838;
        border-radius: 2px;
        transform: translateY(var(--marker-y, 0));
        height: var(--marker-h, 0);
        opacity: 0;
        transition: transform .42s cubic-bezier(.16,1,.3,1),
                    height .42s cubic-bezier(.16,1,.3,1),
                    opacity .25s ease;
    }
    .toc-rail[data-ready='true'] .toc-rail__marker { opacity: 1; }

    .toc-link {
        display: block;
        padding-block: .5rem;
        padding-inline: 1rem .5rem;
        font-size: .875rem;
        color: #6b7280;
        transition: color .25s ease, transform .25s cubic-bezier(.16,1,.3,1);
    }
    .dark .toc-link { color: #9ca3af; }
    .toc-link:hover { color: #218838; }
    .toc-link.toc-active {
        color: #111827;
        font-weight: 600;
        transform: translateX(var(--toc-shift, .25rem));
    }
    .dark .toc-link.toc-active { color: #f9fafb; }
    [dir='rtl'] .toc-link.toc-active { --toc-shift: -.25rem; }

    /* ── Citations arrive ──
       Verse and hadith cards settle into place as the reader reaches
       them. Driven by the view timeline where supported, so there is no
       scroll listener and no JS; everywhere else they are simply
       already visible, which is the correct fallback. */
    @supports (animation-timeline: view()) {
        @media (prefers-reduced-motion: no-preference) {
            .prose-article .module-embed {
                animation: embed-arrive linear both;
                animation-timeline: view();
                animation-range: entry 5% entry 55%;
            }
        }
    }
    @keyframes embed-arrive {
        from { opacity: 0; transform: translateY(1.25rem); clip-path: inset(0 0 12% 0); }
        to   { opacity: 1; transform: none;                clip-path: inset(0 0 0 0); }
    }

    /* ── Progress ──
       Bound to the document's own scroll where the browser can do it
       without JS; the Alpine width binding stays as the fallback. */
    @supports (animation-timeline: scroll()) {
        .reading-progress__fill {
            width: 100% !important;
            transform-origin: var(--progress-origin, left);
            animation: progress-advance linear both;
            animation-timeline: scroll(root block);
        }
        [dir='rtl'] .reading-progress__fill { --progress-origin: right; }
    }
    @keyframes progress-advance {
        from { transform: scaleX(0); }
        to   { transform: scaleX(1); }
    }

    /* ── Continuity ──
       The card image on the library index and the hero here are the same
       object, so the browser morphs one into the other across the
       navigation instead of cutting. */
    .article-hero-media { view-transition-name: article-hero; }

    @media (prefers-reduced-motion: reduce) {
        .toc-rail__marker, .toc-link { transition: none !important; }
        .prose-article .module-embed { animation: none !important; opacity: 1 !important; transform: none !important; clip-path: none !important; }
        .article-hero-media { view-transition-name: none; }
    }

    @media (max-width: 1023px) {
    
        .prose-article > p:first-of-type::first-letter { font-size: 2.9em; }
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
        /* The kind of citation is carried by its label and tint, not by a
           slab of colour down one edge. A hairline in the same hue keeps the
           distinction without the accent-bar tell. */
        border: 1px solid color-mix(in srgb, var(--embed-accent, #0056b3) 28%, transparent);
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

    /* Legacy inline embeds saved before the blot adopted the BEM contract used
       by ArticleLocale::renderBlocksToHtml(). Without these the older markup
       collapses into one run-on line inside a correctly framed card. */
    /* Legacy embeds carry no module-embed--{type} modifier, so the accent
       variable is unset; without a fallback the label is white on nothing. */
    .module-embed .embed-label {
        display: inline-flex;
        margin-bottom: .6rem;
        padding: .15rem .65rem;
        border-radius: 9999px;
        background: var(--embed-accent, #0056b3);
        color: #fff;
        font-size: .6875rem;
        font-weight: 700;
    }
    .module-embed .embed-text {
        display: block;
        font-family: 'Amiri', serif;
        font-size: 1.35rem;
        line-height: 2.2;
    }
    .module-embed .embed-reference { display: block; margin-top: .5rem; font-size: .85rem; font-weight: 600; color: #6b7280; }
    .module-embed .embed-link { display: inline-block; margin-top: .9rem; font-size: .85rem; font-weight: 600; color: var(--embed-accent, #0056b3); }

    /* ═══════════════════════════════════════════════════════════════
       FOLIO — carried over from the library index so a study looks like
       the same object whether it is on a shelf or open on the desk.
       ═══════════════════════════════════════════════════════════════ */
    .folio { --lapis: #0056b3; --verdigris: #218838; --rule: #c9bfa8; --folio-paper: #fbf9f4; }
    .dark .folio { --rule: #3d4f6b; --folio-paper: rgba(255,255,255,.03); }

    .folio-title { font-family: 'Amiri', serif; line-height: 1.24; letter-spacing: -.005em; }

    .folio-colophon {
        font-variant-numeric: tabular-nums;
        font-size: .8125rem;
        color: #6b7280;
    }
    .dark .folio-colophon { color: #9ca3af; }
    .folio-colophon .sep { color: var(--rule); padding-inline: .55rem; }

    .folio-eyebrow {
        display: flex; align-items: center; flex-wrap: wrap; gap: .5rem;
        font-size: .6875rem; font-weight: 700; letter-spacing: .14em;
        color: var(--verdigris);
    }
    .folio-eyebrow .sep { color: var(--rule); }

    /* Khulasa: the scribe's précis, set apart from the running text. */
    .folio-summary {
        position: relative;
        margin: 0 0 2.25rem;
        padding: 1.5rem 1.75rem;
        background: var(--folio-paper);
        border: 1px solid var(--rule);
    }
    .folio-summary::after {
        content: '';
        position: absolute;
        inset-block-start: -5px; inset-inline-end: 24px;
        width: 9px; height: 9px; rotate: 45deg;
        background: var(--verdigris);
    }
    .folio-summary__label {
        display: block;
        margin-bottom: .6rem;
        font-size: .6875rem; font-weight: 700; letter-spacing: .14em;
        color: var(--verdigris);
    }
    .folio-summary__body {
        font-size: 1.0625rem; line-height: 1.9;
        color: #1f2937;
    }
    .dark .folio-summary__body { color: #e5e7eb; }
    .folio-summary__body p { margin: 0 0 .6rem; }
    .folio-summary__body p:last-child { margin-bottom: 0; }

    @media (max-width: 640px) {
        .folio-summary { padding: 1.15rem 1.25rem; }
        .folio-summary__body { font-size: 1rem; line-height: 1.8; }
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
    <div class="fixed top-0 inset-x-0 z-[60] h-[3px] bg-transparent no-print"
         role="progressbar" aria-label="{{ __('articles.public.reading_progress') }}"
         :aria-valuenow="progress" aria-valuemin="0" aria-valuemax="100">
        <div class="reading-progress__fill h-full bg-secondary transition-[width] duration-150 ease-out"
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
                            <div class="gallery-stage article-hero-media group relative h-60 md:h-96 overflow-hidden bg-gray-100 dark:bg-[#0f3460] cursor-zoom-in"
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
                                            {{-- Both sit on a photograph, so the surface has to be
                                                 darker than the text, not lighter. The light `glass`
                                                 utility put white type on a near-white pill. --}}
                                            @if (! empty($article['category']))
                                                <span class="px-4 py-1.5 rounded-full text-sm font-semibold bg-secondary text-white shadow-lg">{{ $article['category'] }}</span>
                                            @endif
                                            @foreach (array_slice($article['tags'], 0, 2) as $tag)
                                                <span class="px-4 py-1.5 rounded-full text-sm font-medium bg-black/45 backdrop-blur-sm ring-1 ring-white/25 text-white">{{ $tag }}</span>
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

                        {{-- Title --}}
                        <h1 class="text-3xl md:text-4xl xl:text-5xl font-bold mb-5 leading-tight tracking-tight text-gray-900 dark:text-white break-words">
                            {{ $article['title'] }}
                        </h1>

                        {{-- Lede --}}
                        @if (! empty($article['excerpt']))
                            <p class="text-lg md:text-xl text-gray-600 dark:text-gray-300 leading-relaxed mb-7 break-words ps-4 border-s-4 border-accent/60">
                                {{ $article['excerpt'] }}
                            </p>
                        @endif

                        {{-- Meta bar: author + dates + reading stats | share + font controls --}}
                        <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-3 mb-6 py-4 border-y border-gray-100 dark:border-[#0f3460]">
                            <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-gray-500 dark:text-gray-400">
                                @if (! empty($article['author']))
                                    <span class="inline-flex items-center gap-2">
                                        <span class="w-8 h-8 rounded-full bg-gradient-to-l from-primary to-secondary text-white flex items-center justify-center">
                                            <i data-lucide="user" class="w-4 h-4"></i>
                                        </span>
                                        <span class="font-semibold text-gray-800 dark:text-gray-100">{{ $article['author'] }}</span>
                                    </span>
                                @endif
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

                            {{-- Share + font size --}}
                            <div class="flex items-center gap-2 no-print">
                                <button @click="showShare = true"
                                        class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold bg-gradient-to-l from-primary to-secondary text-white shadow hover:shadow-lg hover:scale-105 transition-all cursor-pointer">
                                    <i data-lucide="share-2" class="w-4 h-4"></i>{{ __('articles.public.share') }}
                                </button>
                                <div class="inline-flex items-center gap-1 rounded-full bg-gray-100 dark:bg-[#0f3460] p-1">
                                    <button @click="decreaseFont()" aria-label="A-" class="w-8 h-8 rounded-full flex items-center justify-center text-gray-600 dark:text-gray-300 hover:bg-white dark:hover:bg-[#16213e] transition-colors cursor-pointer">
                                        <i data-lucide="minus" class="w-4 h-4"></i>
                                    </button>
                                    <span class="px-1 text-xs font-semibold text-gray-700 dark:text-gray-200 w-10 text-center tabular-nums" x-text="fontSize + '%'"></span>
                                    <button @click="increaseFont()" aria-label="A+" class="w-8 h-8 rounded-full flex items-center justify-center text-gray-600 dark:text-gray-300 hover:bg-white dark:hover:bg-[#16213e] transition-colors cursor-pointer">
                                        <i data-lucide="plus" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Series membership banner --}}
                        @if (! empty($article['series']))
                            <a href="{{ $article['series']['url'] }}"
                               class="group flex items-center gap-4 rounded-2xl p-4 mb-8 no-print border border-primary/15 bg-gradient-to-l from-primary/[0.06] to-secondary/[0.06] hover:from-primary/[0.12] hover:to-secondary/[0.12] transition-colors">
                                <span class="w-11 h-11 rounded-xl bg-gradient-to-l from-primary to-secondary text-white flex items-center justify-center shrink-0 shadow-md">
                                    <i data-lucide="layers" class="w-5 h-5"></i>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('series.public.part_of_series') }}</span>
                                    <span class="block font-bold text-gray-900 dark:text-white truncate group-hover:text-primary transition-colors">{{ $article['series']['title'] }}</span>
                                </span>
                                <span class="hidden sm:inline-flex items-center gap-1.5 text-sm font-semibold text-primary shrink-0">
                                    {{ __('series.public.view_series') }}
                                    <i data-lucide="arrow-{{ $isRtl ? 'left' : 'right' }}" class="w-4 h-4 group-hover:scale-110 transition-transform"></i>
                                </span>
                            </a>
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
                                        class="inline-flex items-center gap-2 px-7 py-3 rounded-full text-sm font-bold bg-gradient-to-l from-primary to-secondary text-white shadow-md hover:shadow-lg hover:scale-105 transition-all cursor-pointer">
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
                                    <span class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center"><i data-lucide="book-open" class="w-4 h-4"></i></span>{{ __('articles.public.sources') }}
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

                        {{-- Khulasa — the editor's per-locale summary. Closes the piece:
                             a recap lands after the argument, not before it. --}}
                        {{-- Attached media. Streams through the article-scoped
                             public route; the library itself stays admin-only. --}}
                        @if (! empty($article['media']))
                            <section class="mt-10" aria-labelledby="article-media-label">
                                <h2 id="article-media-label" class="text-lg font-bold mb-4 flex items-center gap-2 text-gray-900 dark:text-white">
                                    <i data-lucide="play-circle" class="w-5 h-5 text-accent"></i>{{ __('articles.public.attached_media') }}
                                </h2>
                                <div class="space-y-4">
                                    @foreach ($article['media'] as $item)
                                        @if ($item['type'] === 'video')
                                            <figure class="rounded-2xl overflow-hidden bg-black">
                                                <video controls preload="metadata" playsinline
                                                       class="w-full h-auto aspect-video bg-black"
                                                       aria-label="{{ $item['title'] }}">
                                                    <source src="{{ $item['url'] }}" @if($item['mime']) type="{{ $item['mime'] }}" @endif>
                                                </video>
                                            </figure>
                                        @elseif ($item['type'] === 'audio')
                                            <figure class="rounded-2xl border border-gray-200 dark:border-[#0f3460] p-4">
                                                <figcaption class="text-sm font-semibold mb-2 text-gray-900 dark:text-white">{{ $item['title'] }}</figcaption>
                                                <audio controls preload="metadata" class="w-full" aria-label="{{ $item['title'] }}">
                                                    <source src="{{ $item['url'] }}" @if($item['mime']) type="{{ $item['mime'] }}" @endif>
                                                </audio>
                                            </figure>
                                        @else
                                            <a href="{{ $item['url'] }}" target="_blank" rel="noopener"
                                               class="flex items-center gap-3 rounded-xl border border-gray-200 dark:border-[#0f3460] p-4 hover:border-primary transition-colors">
                                                <i data-lucide="file" class="w-5 h-5 text-gray-400 shrink-0"></i>
                                                <span class="font-medium text-gray-900 dark:text-white truncate">{{ $item['title'] }}</span>
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            </section>
                        @endif

                        {{-- Gallery as its own section. It also feeds the hero
                             slider, but a single hero image reads as a cover,
                             not as a gallery the reader can browse. --}}
                        @if (count($article['gallery'] ?? []) > 0)
                            <section class="mt-10" aria-labelledby="article-gallery-label">
                                <h2 id="article-gallery-label" class="text-lg font-bold mb-4 flex items-center gap-2 text-gray-900 dark:text-white">
                                    <i data-lucide="images" class="w-5 h-5 text-accent"></i>{{ __('articles.public.gallery') }}
                                </h2>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                    @foreach ($article['gallery'] as $index => $image)
                                        {{-- A plain link, not the hero's lightbox: that
                                             component's scope ends with the hero. --}}
                                        <a href="{{ $image['url'] }}" target="_blank" rel="noopener"
                                           class="group relative aspect-[4/3] overflow-hidden rounded-xl bg-gray-100 dark:bg-[#0f3460] block focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/30">
                                            <img src="{{ $image['url'] }}" alt="{{ $image['caption'] ?: $article['title'] }}" loading="lazy"
                                                 class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105 motion-reduce:transform-none">
                                            @if (! empty($image['caption']))
                                                <span class="absolute inset-x-0 bottom-0 p-2 text-xs text-white bg-black/55 text-start line-clamp-1">{{ $image['caption'] }}</span>
                                            @endif
                                        </a>
                                    @endforeach
                                </div>
                            </section>
                        @endif

                        @if (! empty($article['summary']))
                            <aside class="folio folio-summary mt-10" aria-labelledby="article-summary-label">
                                <span class="folio-summary__label" id="article-summary-label">
                                    {{ __('articles.public.summary') }}
                                </span>
                                <div class="folio-summary__body">
                                    {!! $article['summary'] !!}
                                </div>
                            </aside>
                        @endif

                        {{-- Tags --}}
                        @if (! empty($article['tags']))
                            <div class="flex flex-wrap items-center gap-2 mt-8 pt-6 border-t border-gray-100 dark:border-[#0f3460]">
                                <span class="inline-flex items-center gap-1.5 text-sm font-semibold text-gray-500 dark:text-gray-400 me-1">
                                    <i data-lucide="tag" class="w-4 h-4"></i>{{ __('articles.public.tags') }}:
                                </span>
                                @foreach ($article['tags'] as $tag)
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-primary/10 text-primary dark:bg-accent/10 dark:text-accent">{{ $tag }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </article>

                {{-- Continue reading. The controller has always resolved these
                     neighbours (following the series order where the article
                     belongs to one) but nothing rendered them. --}}
                @if ($prev || $next)
                    <nav class="grid gap-4 sm:grid-cols-2 mt-8 no-print" aria-label="{{ __('articles.public.more_articles') }}">
                        @foreach ([['item' => $prev, 'dir' => 'prev'], ['item' => $next, 'dir' => 'next']] as $neighbour)
                            @php $item = $neighbour['item']; @endphp
                            @if ($item)
                                <a href="{{ $item['url'] }}"
                                   class="group card-premium p-5 flex items-center gap-4 hover:border-primary/40 transition-colors {{ $neighbour['dir'] === 'next' ? 'sm:text-end sm:flex-row-reverse' : '' }}">
                                    <span class="w-10 h-10 rounded-full bg-primary/10 text-primary flex items-center justify-center shrink-0 group-hover:bg-primary group-hover:text-white transition-colors">
                                        <i data-lucide="chevron-{{ ($neighbour['dir'] === 'prev') === $isRtl ? 'right' : 'left' }}" class="w-5 h-5"></i>
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">
                                            {{ $neighbour['dir'] === 'prev' ? __('articles.public.previous_article') : __('articles.public.next_article') }}
                                        </span>
                                        <span class="block font-bold text-gray-900 dark:text-white line-clamp-2 group-hover:text-primary transition-colors">{{ $item['title'] }}</span>
                                    </span>
                                </a>
                            @else
                                <span class="hidden sm:block" aria-hidden="true"></span>
                            @endif
                        @endforeach
                    </nav>
                @endif

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
                        {{-- The rail. Not a card: a track the reader's position
                             travels along, so the sidebar reports where they are
                             instead of restating the article's structure. --}}
                        <nav class="hidden lg:block" aria-label="{{ __('articles.public.table_of_contents') }}">
                            <p class="text-[11px] font-bold tracking-[.14em] text-gray-400 dark:text-gray-500 mb-4">
                                {{ __('articles.public.table_of_contents') }}
                            </p>
                            <div class="toc-rail text-gray-900 dark:text-white"
                                 x-ref="tocRail"
                                 :data-ready="active ? 'true' : 'false'"
                                 x-effect="active; $nextTick(() => positionTocMarker())">
                                <span class="toc-rail__marker" aria-hidden="true"></span>
                                <ul>
                                    @foreach ($article['toc'] as $item)
                                        <li @class(['ps-4' => ($item['level'] ?? 2) === 3])>
                                            <a href="#{{ $item['id'] }}"
                                               @click.prevent="scrollTo('{{ $item['id'] }}')"
                                               data-toc-link="{{ $item['id'] }}"
                                               class="toc-link"
                                               :class="active === '{{ $item['id'] }}' ? 'toc-active' : ''"
                                               :aria-current="active === '{{ $item['id'] }}' ? 'true' : 'false'">
                                                {{ $item['label'] }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </nav>
                    @endif

                    {{-- Quick stats (real statistics) --}}
                    @if (! empty($article['statistics']))
                        <div class="card-premium p-5 animate-fade-in-up stagger-1">
                            <h3 class="text-lg font-bold mb-4 flex items-center gap-2 text-gray-900 dark:text-white">
                                <span class="w-7 h-7 rounded-lg bg-secondary/10 text-secondary flex items-center justify-center"><i data-lucide="bar-chart-2" class="w-4 h-4"></i></span>{{ __('articles.public.quick_stats') }}
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
                                <span class="w-7 h-7 rounded-lg bg-primary/10 text-primary flex items-center justify-center"><i data-lucide="tag" class="w-4 h-4"></i></span>{{ __('articles.public.tags') }}
                            </h3>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($article['tags'] as $tag)
                                    <span class="px-3 py-1.5 rounded-full text-xs font-semibold bg-primary/10 text-primary dark:bg-accent/10 dark:text-accent">{{ $tag }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Related articles (compact cards) --}}
                    @if (! empty($related))
                        <div class="card-premium p-5 animate-fade-in-up stagger-3">
                            <h3 class="text-lg font-bold mb-4 flex items-center gap-2 text-gray-900 dark:text-white">
                                <span class="w-7 h-7 rounded-lg bg-primary/10 text-primary flex items-center justify-center"><i data-lucide="layers" class="w-4 h-4"></i></span>{{ __('articles.public.related_articles') }}
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

            /**
             * Move the rail marker onto the active entry.
             *
             * Measured rather than CSS-only: the marker has to match the real
             * height of a wrapped label, which no selector can know. Reads
             * offsets in one pass and writes two custom properties, so the
             * transition runs on the compositor.
             */
            positionTocMarker() {
                const rail = this.$refs.tocRail;
                if (!rail || !this.active) return;

                const link = rail.querySelector(`[data-toc-link="${CSS.escape(this.active)}"]`);
                if (!link) return;

                rail.style.setProperty('--marker-y', `${link.offsetTop}px`);
                rail.style.setProperty('--marker-h', `${link.offsetHeight}px`);
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
