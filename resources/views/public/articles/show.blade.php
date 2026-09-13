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
    /* The scroll lives on a wrapper, not on .toc-rail itself: the rail is the
       positioning context for the hairline and the travelling marker, and
       making it a scroll container would clip both to the visible height. A
       forty-heading article otherwise ran a 1,500px rail down the sidebar and
       pushed the tags and related list off the screen entirely. */
    /* Key-points panel — the prototype's tinted takeaway block. */
    .summary-panel {
        padding: 1.5rem;
        border-radius: .75rem;
        background: linear-gradient(to left, rgb(239 246 255), rgb(240 253 244));
    }
    .dark .summary-panel {
        background: linear-gradient(to left, rgba(15, 52, 96, .8), rgba(15, 52, 96, .45));
    }
    .summary-panel__body :is(p, li) { line-height: 1.9; }
    .summary-panel__body ul { list-style: none; padding: 0; margin: 0; }
    .summary-panel__body li { position: relative; padding-inline-start: 1rem; }
    .summary-panel__body li::before {
        content: '•';
        position: absolute;
        inset-inline-start: 0;
        color: #64CCC5;
    }

    /* The queue matches the stage's height on desktop so the pair reads as one
       player, and becomes a short scroller on narrow screens rather than a
       column of thumbnails as tall as the article. */
    .video-queue { max-block-size: 22rem; }
    @media (min-width: 1024px) {
        .video-queue { max-block-size: min(32rem, 56vw); }
    }

    /* ── Mobile table of contents (disclosure) ─────────────────────── */
    .toc-summary {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        padding: .75rem 1rem;
        /* Comfortably past the 44px touch minimum for the whole header row. */
        min-block-size: 3rem;
        cursor: pointer;
        list-style: none;
    }
    /* Safari draws its own triangle unless both of these are suppressed. */
    .toc-summary::-webkit-details-marker { display: none; }
    .toc-summary::marker { content: ''; }
    .toc-summary:focus-visible {
        outline: 2px solid rgba(0, 86, 179, .5);
        outline-offset: -2px;
    }

    .toc-summary__chevron { transition: transform .25s ease; }
    .toc-mobile[open] .toc-summary__chevron { transform: rotate(180deg); }

    /* Caps the open list so a long article's contents stay a panel rather than
       a second page the reader has to scroll past. */
    .toc-mobile__list {
        max-block-size: 60vh;
        overflow-y: auto;
        overscroll-behavior: contain;
    }

    @media (prefers-reduced-motion: reduce) {
        .toc-summary__chevron { transition: none; }
    }

    .toc-scroll {
        max-block-size: min(26rem, calc(100vh - 20rem));
        overflow-y: auto;
        overscroll-behavior: contain;
        scroll-behavior: smooth;
    }
    /* Brand-gradient thumb on an invisible track — the default scrollbar
       painted a fat white bar down the dark card. Gradients only exist in
       the -webkit- model; the flat teal fallback is gated to engines
       without it (Firefox), since Chrome drops -webkit- styling whenever
       scrollbar-width/scrollbar-color are set. */
    @supports not selector(::-webkit-scrollbar) {
        .toc-scroll {
            scrollbar-width: thin;
            scrollbar-color: rgba(100, 204, 197, .45) transparent;
        }
    }
    .toc-scroll::-webkit-scrollbar { width: 5px; }
    .toc-scroll::-webkit-scrollbar-track { background: transparent; }
    .toc-scroll::-webkit-scrollbar-thumb {
        background: linear-gradient(180deg, #0056b3 0%, #64CCC5 55%, #218838 100%);
        border-radius: 9999px;
    }
    .toc-scroll::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(180deg, #1a6fd1 0%, #7fe0d9 55%, #2aa348 100%);
    }
    @media (prefers-reduced-motion: reduce) {
        .toc-scroll { scroll-behavior: auto; }
    }

    /* Pill links with a lit dot trail — the Path of Light's reading rail.
       A gradient fill climbs the track as the reader moves; passed
       headings hold a lit dot. */
    .toc-link {
        display: flex;
        align-items: center;
        gap: .65rem;
        padding: .5rem .75rem;
        border-radius: .5rem;
        font-size: .875rem;
        line-height: 1.6;
        color: #4b5563;
        transition: color .2s ease, background-color .2s ease;
    }
    .dark .toc-link { color: #9ca3af; }
    .toc-link:hover { color: #111827; background: #f3f4f6; }
    .dark .toc-link:hover { color: #e5e7eb; background: rgba(255, 255, 255, .06); }

    /* Sub-headings read as subordinate: a step down, smaller dot — the
       dot column stays on the rail (no margin indent, it would derail it). */
    .toc-link--sub { font-size: .8125rem; }
    .toc-link--sub .toc-dot { width: 7px; height: 7px; margin-inline-start: 1px; }

    .toc-link.toc-active {
        color: #0d6b61;
        font-weight: 600;
        background: rgba(100, 204, 197, .12);
    }
    .dark .toc-link.toc-active { color: #64CCC5; }

    .toc-rail-track,
    .toc-rail-fill {
        position: absolute;
        top: .9rem;
        bottom: .9rem;
        inset-inline-start: 15.5px;
        width: 2px;
        border-radius: 2px;
        pointer-events: none;
    }
    .toc-rail-track { background: #e5e7eb; }
    .dark .toc-rail-track { background: rgba(255, 255, 255, .1); }
    .toc-rail-fill {
        background: linear-gradient(to bottom, #0056b3, #64CCC5 55%, #218838);
        clip-path: inset(0 0 calc((1 - var(--tp, 0)) * 100%) 0);
        transition: clip-path .4s ease-out;
    }
    .toc-dot {
        width: 9px;
        height: 9px;
        border-radius: 50%;
        flex-shrink: 0;
        position: relative;
        z-index: 1;
        background: #d1d5db;
        transition: background .35s ease, box-shadow .35s ease;
    }
    .dark .toc-dot { background: rgba(255, 255, 255, .22); }
    .toc-dot.is-on { background: #64CCC5; box-shadow: 0 0 9px rgba(100, 204, 197, .8); }

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
       A light trail rather than a flat bar: the brand gradient with a
       soft teal glow, matching the seerah journey's language.
       Bound to the document's own scroll where the browser can do it
       without JS; the Alpine width binding stays as the fallback. */
    .reading-progress__fill {
        background: linear-gradient(90deg, #0056b3 0%, #64CCC5 60%, #218838 100%);
        box-shadow: 0 0 10px rgba(100, 204, 197, .65);
        border-radius: 0 2px 2px 0;
    }
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
    class="min-h-dvh"
>
    {{-- Reading progress bar --}}
    <div class="fixed top-0 inset-x-0 z-[60] h-[3px] bg-transparent no-print"
         role="progressbar" aria-label="{{ __('articles.public.reading_progress') }}"
         :aria-valuenow="progress" aria-valuemin="0" aria-valuemax="100">
        <div class="reading-progress__fill h-full transition-[width] duration-150 ease-out"
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

                    {{-- Hero Gallery --}}
                    <x-public.article.gallery :article="$article" :isRtl="$isRtl" />

                    {{-- Body wrapper (font-size scalable) --}}
                    <div class="p-6 md:p-10" :style="`font-size: ${fontSize}%`">

                        {{-- Article Header --}}
                        <x-public.article.header :article="$article" :isRtl="$isRtl" />

                        {{-- Article Content Blocks --}}
                        <x-public.article.content-blocks :article="$article" :isRtl="$isRtl" />
                    </div>
                </article>

                {{-- Related Articles Navigation --}}
                <x-public.article.related-articles :prev="$prev ?? null" :next="$next ?? null" :related="$related ?? []" :isRtl="$isRtl" />
            </div>

            {{-- ============================================================ --}}
            {{-- SIDEBAR --}}
            {{-- ============================================================ --}}
            <x-public.article.sidebar :article="$article" :isRtl="$isRtl" />
        </div>
    </div>

    {{-- Share Modal --}}
    <x-public.article.share-modal />

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
                // Keep the active heading softly centred inside the TOC panel as
                // the reader moves. Scrolls ONLY the panel — scrollIntoView would
                // also scroll the page and fight the reader's own scrolling.
                this.$watch('active', (id) => this.followInToc(id));
                this.recordView();
            },

            followInToc(id) {
                if (!id) return;
                const link = document.querySelector(`.toc-scroll [data-toc-link="${CSS.escape(id)}"]`);
                const wrap = link?.closest('.toc-scroll');
                if (!link || !wrap) return;
                const lr = link.getBoundingClientRect();
                const wr = wrap.getBoundingClientRect();
                if (lr.top < wr.top || lr.bottom > wr.bottom) {
                    wrap.scrollTop = wrap.scrollTop + (lr.top - wr.top) - wr.height / 2 + lr.height / 2;
                }
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
