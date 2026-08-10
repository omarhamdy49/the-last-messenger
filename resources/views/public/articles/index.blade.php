@extends('layouts.public')

@section('title', __('articles.public.meta_title'))
@section('meta_description', __('articles.public.page_description'))

@push('structured_data')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "CollectionPage",
    "name": "{{ __('articles.public.page_title') }}",
    "description": "{{ __('articles.public.page_description') }}",
    "url": "{{ route('public.articles') }}",
    "inLanguage": "{{ app()->getLocale() }}"
}
</script>
@endpush

@push('head')
<style>
    /* ═══════════════════════════════════════════════════════════════
       ARTICLES LIBRARY — page-scoped chrome.

       The card itself lives in the site.article-card component; everything
       here is the furniture around it: the opening canvas, the spotlight leaf
       and the sticky filter rail. Brand pigments read as manuscript colour —
       lapis #0056b3, verdigris #218838, teal #64CCC5.
       ═══════════════════════════════════════════════════════════════ */

    /* ── Hero canvas ───────────────────────────────────────────────────
       Written light-first, with `.dark` carrying the night canvas. It used to
       be night-only, which dropped a heavy navy slab onto an otherwise pale
       page: the masthead fought the library instead of opening it. Light mode
       now reads as tinted paper — brand pigment bled into the ground rather
       than painted over it. */
    .lib-hero {
        position: relative;
        isolation: isolate;
        overflow: hidden;
        background:
            radial-gradient(ellipse 70% 60% at 18% 0%, rgba(0, 86, 179, .13), transparent 62%),
            radial-gradient(ellipse 60% 55% at 82% 8%, rgba(33, 136, 56, .11), transparent 62%),
            linear-gradient(168deg, #fbfcfe 0%, #eef4fa 55%, #e8f1f2 100%);
        border-block-end: 1px solid rgb(226 232 240);
    }
    .dark .lib-hero {
        background:
            radial-gradient(ellipse 70% 60% at 18% 0%, rgba(0, 86, 179, .5), transparent 62%),
            radial-gradient(ellipse 60% 55% at 82% 8%, rgba(33, 136, 56, .34), transparent 62%),
            linear-gradient(168deg, #06182f 0%, #0a2242 52%, #0b2838 100%);
        border-block-end-color: rgba(100, 204, 197, .14);
    }

    /* Star lattice drawn in gradients — no image asset, no extra request.
       Pigment on paper in light mode, starlight in dark. */
    .lib-hero-lattice {
        position: absolute;
        inset: 0;
        z-index: -1;
        pointer-events: none;
        background-image:
            radial-gradient(rgba(0, 86, 179, .9) 1px, transparent 1.6px),
            radial-gradient(rgba(33, 136, 56, .8) 1px, transparent 1.6px);
        background-size: 30px 30px, 60px 60px;
        background-position: 0 0, 15px 15px;
        opacity: .07;
        -webkit-mask-image: radial-gradient(ellipse 75% 90% at 50% 15%, black, transparent 78%);
        mask-image: radial-gradient(ellipse 75% 90% at 50% 15%, black, transparent 78%);
    }
    .dark .lib-hero-lattice {
        background-image:
            radial-gradient(rgba(255, 255, 255, .85) 1px, transparent 1.6px),
            radial-gradient(rgba(100, 204, 197, .75) 1px, transparent 1.6px);
        opacity: .09;
    }

    /* On paper the breadcrumb keeps the component's own greys; only the night
       canvas needs them inverted. Specificity (0,3,0 / 0,3,1) is what beats the
       Tailwind colour utilities the component merges in — no !important. */
    .dark .lib-hero .lib-crumb { color: rgba(255, 255, 255, .55); }
    .dark .lib-hero .lib-crumb a:hover { color: #64CCC5; }
    .dark .lib-hero .lib-crumb [aria-current] { color: #fff; }

    .lib-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: .3rem .85rem;
        border-radius: 9999px;
        font-size: .75rem;
        font-weight: 700;
        letter-spacing: .08em;
        color: #0d6b61;
        background: rgba(100, 204, 197, .16);
        border: 1px solid rgba(100, 204, 197, .5);
    }
    .dark .lib-eyebrow {
        color: #9ceee4;
        background: rgba(100, 204, 197, .1);
        border-color: rgba(100, 204, 197, .28);
    }

    /* Site family (Cairo) throughout — Amiri stays reserved for scripture. */
    .lib-title {
        line-height: 1.22;
        background: linear-gradient(115deg, #062f5c 0%, #0056b3 48%, #1d7a33 100%);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
    }
    .dark .lib-title {
        background: linear-gradient(115deg, #ffffff 0%, #b9f2ea 55%, #86dca8 100%);
        -webkit-background-clip: text;
        background-clip: text;
    }

    .lib-sub { color: rgb(71 85 105); }
    .dark .lib-sub { color: rgba(255, 255, 255, .6); }

    /* Manuscript ornament between the title and the standfirst — a hairline
       pair around a lozenge, the divider an illuminated page would use. Drawn
       in CSS: no asset, no request, and it inherits the brand gradient. */
    .lib-orn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: .85rem;
        inline-size: min(18rem, 72%);
        margin: 1.15rem auto 1.35rem;
    }
    .lib-orn::before,
    .lib-orn::after {
        content: '';
        flex: 1 1 auto;
        block-size: 1px;
    }
    .lib-orn::before { background: linear-gradient(90deg, transparent, rgba(0, 86, 179, .38)); }
    .lib-orn::after  { background: linear-gradient(90deg, rgba(33, 136, 56, .38), transparent); }
    .lib-orn i {
        flex: none;
        inline-size: .5rem;
        block-size: .5rem;
        rotate: 45deg;
        border-radius: 1px;
        background: linear-gradient(135deg, #0056b3, #218838);
    }
    .dark .lib-orn::before { background: linear-gradient(90deg, transparent, rgba(100, 204, 197, .4)); }
    .dark .lib-orn::after  { background: linear-gradient(90deg, rgba(134, 220, 168, .4), transparent); }
    .dark .lib-orn i { background: linear-gradient(135deg, #64CCC5, #86dca8); }

    /* Hero search — a raised card on paper, glass over the night canvas. */
    .lib-search {
        position: relative;
        border-radius: 1rem;
        background: #fff;
        border: 1px solid rgb(226 232 240);
        box-shadow: 0 14px 40px -22px rgba(2, 30, 68, .45);
        transition: border-color .25s ease, background .25s ease, box-shadow .25s ease;
    }
    .lib-search:focus-within {
        border-color: rgba(0, 86, 179, .5);
        box-shadow: 0 14px 40px -18px rgba(0, 86, 179, .4);
    }
    .lib-search input { color: rgb(30 41 59); }
    .lib-search input::placeholder { color: rgb(148 163 184); }
    .lib-search-icon { color: rgb(148 163 184); }
    .lib-search-clear { color: rgb(100 116 139); }
    .lib-search-clear:hover { color: rgb(30 41 59); background: rgb(241 245 249); }

    .dark .lib-search {
        background: rgba(255, 255, 255, .09);
        border-color: rgba(255, 255, 255, .16);
        -webkit-backdrop-filter: blur(16px);
        backdrop-filter: blur(16px);
        box-shadow: 0 16px 44px -20px rgba(0, 0, 0, .6);
    }
    .dark .lib-search:focus-within {
        background: rgba(255, 255, 255, .13);
        border-color: rgba(100, 204, 197, .55);
        box-shadow: 0 16px 44px -16px rgba(100, 204, 197, .3);
    }
    .dark .lib-search input { color: #fff; }
    .dark .lib-search input::placeholder { color: rgba(255, 255, 255, .5); }
    .dark .lib-search-icon { color: rgba(255, 255, 255, .45); }
    .dark .lib-search-clear { color: rgba(255, 255, 255, .55); }
    .dark .lib-search-clear:hover { color: #fff; background: rgba(255, 255, 255, .15); }

    /* Inline stat readout — a colophon line, not a row of dashboard tiles. */
    .lib-colophon {
        display: inline-flex;
        align-items: center;
        flex-wrap: wrap;
        justify-content: center;
        gap: .55rem;
        font-size: .8125rem;
        color: rgb(100 116 139);
        font-variant-numeric: tabular-nums;
    }
    .lib-colophon b { color: rgb(15 23 42); font-weight: 700; }
    .lib-colophon .sep { color: rgb(203 213 225); }
    .dark .lib-colophon { color: rgba(255, 255, 255, .62); }
    .dark .lib-colophon b { color: #fff; }
    .dark .lib-colophon .sep { color: rgba(255, 255, 255, .28); }

    /* ── Spotlight leaf ────────────────────────────────────────────── */
    .lib-spot {
        position: relative;
        display: grid;
        border-radius: 1.5rem;
        overflow: hidden;
        background: #fff;
        border: 1px solid rgb(233 236 240);
        box-shadow: 0 28px 64px -34px rgba(2, 30, 68, .45), 0 2px 8px rgba(2, 30, 68, .06);
        transition: transform .45s cubic-bezier(.22, 1, .36, 1), box-shadow .45s ease;
    }
    .dark .lib-spot {
        background: #16213e;
        border-color: rgba(100, 204, 197, .14);
        box-shadow: 0 28px 64px -34px rgba(0, 0, 0, .75);
    }
    .lib-spot:hover {
        transform: translateY(-4px);
        box-shadow: 0 38px 80px -34px rgba(0, 86, 179, .42), 0 2px 8px rgba(2, 30, 68, .06);
    }
    .lib-spot:focus-visible {
        outline: none;
        box-shadow: 0 0 0 4px rgba(0, 86, 179, .25);
    }

    @media (min-width: 900px) {
        /* Text leads, plate follows — and the plate never dictates the height,
           so a short excerpt can't open a well of dead space beside it. */
        .lib-spot { grid-template-columns: 1.05fr .95fr; }
    }

    .lib-spot-media {
        position: relative;
        overflow: hidden;
        background: linear-gradient(135deg, #eef2f7, #e3e9f2);
        aspect-ratio: 16 / 10;
    }
    .dark .lib-spot-media { background: linear-gradient(135deg, #0f3460, #16213e); }
    @media (min-width: 900px) {
        .lib-spot-media { aspect-ratio: auto; }
    }
    /* Absolute, not height:100% — above 900px the plate takes its height from
       the text column, so a percentage height would resolve to auto and the
       image would render at its intrinsic size. */
    .lib-spot-media img {
        position: absolute;
        inset: 0;
        width: 100%; height: 100%;
        object-fit: cover;
        transition: transform .8s cubic-bezier(.22, .61, .36, 1);
    }
    .lib-spot:hover .lib-spot-media img { transform: scale(1.05); }
    .lib-spot-media::after {
        content: '';
        position: absolute; inset: 0; pointer-events: none;
        background: linear-gradient(to top, rgba(4, 18, 38, .5), transparent 55%);
    }

    /* Stacked: plate first, then the text. Ordering is done here rather than
       with Tailwind `md:` utilities because the two-column rule below turns on
       at 900px — driving order from a 768px breakpoint opened a band where the
       text jumped above a full-width plate for no reason. */
    .lib-spot-media { order: 1; }
    .lib-spot-body  { order: 2; }
    @media (min-width: 900px) {
        .lib-spot-body  { order: 1; }
        .lib-spot-media { order: 2; }
    }

    .lib-spot-body {
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 1.75rem;
        min-width: 0;
    }
    @media (min-width: 900px) {
        /* The leaf takes its height from this column, so give it enough
           presence to read as an opening folio without opening a void.

           The floor also fits the tallest slide (two-line title + three-line
           excerpt), so advancing the slider never resizes the leaf. Content is
           centred rather than reserved line by line, so a one-line title sits
           balanced instead of hanging over a gap. */
        .lib-spot-body {
            padding: 2.75rem;
            min-block-size: 25rem;
        }
    }

    .lib-spot-title {
        line-height: 1.3;
        transition: color .25s ease;
    }

    .lib-spot:hover .lib-spot-title { color: #0056b3; }
    .dark .lib-spot:hover .lib-spot-title { color: #64CCC5; }

    .lib-spot-cta {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        align-self: flex-start;
        padding: .7rem 1.35rem;
        border-radius: .85rem;
        font-size: .875rem;
        font-weight: 700;
        color: #fff;
        background: linear-gradient(135deg, #0056b3 0%, #218838 100%);
        box-shadow: 0 12px 26px -12px rgba(0, 86, 179, .7);
        transition: transform .3s cubic-bezier(.22, 1, .36, 1), box-shadow .3s ease;
    }
    .lib-spot:hover .lib-spot-cta {
        transform: translateY(-2px);
        box-shadow: 0 18px 34px -12px rgba(0, 86, 179, .8);
    }
    .lib-spot-cta svg { transition: transform .3s cubic-bezier(.22, 1, .36, 1); }
    .lib-spot:hover .lib-spot-cta svg { transform: translateX(3px); }
    [dir="rtl"] .lib-spot:hover .lib-spot-cta svg { transform: translateX(-3px); }

    /* ── Spotlight slider controls ─────────────────────────────────── */
    .lib-spot-nav {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: .75rem;
        margin-block-start: .9rem;
    }

    .lib-spot-arrow {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        /* 44px hit area on touch; the visual disc stays 2.25rem via the icon. */
        inline-size: 2.75rem;
        block-size: 2.75rem;
        border-radius: 9999px;
        cursor: pointer;
        color: rgb(71 85 105);
        background: #fff;
        border: 1px solid rgb(226 232 240);
        box-shadow: 0 6px 18px -12px rgba(2, 30, 68, .5);
        transition: color .22s ease, border-color .22s ease, background .22s ease;
    }
    .lib-spot-arrow:hover { color: #0056b3; border-color: rgba(0, 86, 179, .45); }
    .dark .lib-spot-arrow {
        color: rgb(203 213 225);
        background: rgba(15, 52, 96, .7);
        border-color: rgba(100, 204, 197, .18);
    }
    .dark .lib-spot-arrow:hover { color: #64CCC5; border-color: rgba(100, 204, 197, .5); }

    .lib-spot-dots { display: flex; align-items: center; gap: .45rem; }

    /* The dot is the mark; the button around it carries the 44px target. */
    .lib-spot-dot {
        position: relative;
        inline-size: 1.5rem;
        block-size: 2.75rem;
        border: 0;
        background: none;
        cursor: pointer;
    }
    .lib-spot-dot::after {
        content: '';
        position: absolute;
        inset-block-start: 50%;
        inset-inline-start: 50%;
        translate: -50% -50%;
        inline-size: .5rem;
        block-size: .5rem;
        border-radius: 9999px;
        background: rgb(203 213 225);
        transition: inline-size .28s cubic-bezier(.22, 1, .36, 1), background .28s ease;
    }
    [dir="rtl"] .lib-spot-dot::after { translate: 50% -50%; }
    .dark .lib-spot-dot::after { background: rgba(255, 255, 255, .28); }
    .lib-spot-dot:hover::after { background: rgb(148 163 184); }
    .lib-spot-dot.is-active::after {
        inline-size: 1.4rem;
        background: linear-gradient(135deg, #0056b3 0%, #218838 100%);
    }

    /* ── Sticky filter rail ────────────────────────────────────────── */
    .lib-rail {
        position: sticky;
        top: 4.75rem;
        z-index: 30;
        border-radius: 1.1rem;
        background: rgba(255, 255, 255, .88);
        -webkit-backdrop-filter: blur(18px) saturate(150%);
        backdrop-filter: blur(18px) saturate(150%);
        border: 1px solid rgb(233 236 240);
        box-shadow: 0 10px 30px -20px rgba(2, 30, 68, .35);
    }
    .dark .lib-rail {
        background: rgba(22, 33, 62, .88);
        border-color: rgba(100, 204, 197, .14);
        box-shadow: 0 10px 30px -20px rgba(0, 0, 0, .7);
    }

    /* Pills scroll horizontally instead of wrapping into a tall stack that
       would push the sticky rail over a third of the viewport on mobile. */
    .lib-pills {
        display: flex;
        gap: .45rem;
        overflow-x: auto;
        scrollbar-width: none;
        -webkit-overflow-scrolling: touch;
        padding-block: .1rem;
        -webkit-mask-image: linear-gradient(to right, transparent, black 12px, black calc(100% - 12px), transparent);
        mask-image: linear-gradient(to right, transparent, black 12px, black calc(100% - 12px), transparent);
    }
    .lib-pills::-webkit-scrollbar { display: none; }

    .lib-pill {
        flex: none;
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .5rem .95rem;
        border-radius: 9999px;
        font-size: .8125rem;
        font-weight: 700;
        white-space: nowrap;
        cursor: pointer;
        border: 1px solid rgb(233 236 240);
        color: rgb(71 82 99);
        background: #fff;
        transition: color .22s ease, border-color .22s ease, background .22s ease, box-shadow .22s ease;
    }
    .dark .lib-pill {
        border-color: rgba(100, 204, 197, .16);
        color: rgb(213 219 228);
        background: rgba(15, 52, 96, .55);
    }
    .lib-pill:hover { border-color: rgba(0, 86, 179, .45); color: #0056b3; }
    .dark .lib-pill:hover { color: #64CCC5; border-color: rgba(100, 204, 197, .45); }
    .lib-pill.is-active {
        color: #fff;
        border-color: transparent;
        background: linear-gradient(135deg, #0056b3 0%, #218838 100%);
        box-shadow: 0 8px 18px -8px rgba(0, 86, 179, .6);
    }
    .lib-pill .n {
        font-size: .6875rem;
        padding: .05rem .42rem;
        border-radius: 9999px;
        background: rgb(243 245 248);
        color: rgb(122 132 148);
        font-variant-numeric: tabular-nums;
    }
    .dark .lib-pill .n { background: rgba(255, 255, 255, .08); color: rgb(156 163 175); }
    .lib-pill.is-active .n { background: rgba(255, 255, 255, .24); color: #fff; }

    .lib-ctrl {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: .75rem;
        border: 1px solid rgb(233 236 240);
        background: #fff;
        color: rgb(107 118 135);
        cursor: pointer;
        transition: color .2s ease, border-color .2s ease, background .2s ease;
    }
    .dark .lib-ctrl {
        border-color: rgba(100, 204, 197, .16);
        background: rgba(15, 52, 96, .55);
        color: rgb(200 208 219);
    }
    .lib-ctrl:hover { color: #0056b3; border-color: rgba(0, 86, 179, .4); }
    .dark .lib-ctrl:hover { color: #64CCC5; }
    .lib-ctrl.is-active {
        color: #fff;
        border-color: transparent;
        background: linear-gradient(135deg, #0056b3 0%, #218838 100%);
    }

    /* List view narrows the cards to a reading measure, so the section head and
       the active-filter chips have to follow or they overhang the content they
       label. Matches `.art-list` in the card stylesheet. */
    @media (min-width: 1024px) {
        .lib-narrow {
            max-inline-size: 58rem;
            margin-inline: auto;
        }
    }

    /* Section rule: a hairline that fills the width beside the heading. */
    .lib-rule {
        flex: 1 1 auto;
        height: 1px;
        min-width: 1.5rem;
        background: linear-gradient(90deg, rgb(226 230 236), transparent);
    }
    .dark .lib-rule { background: linear-gradient(90deg, rgba(100, 204, 197, .22), transparent); }
    [dir="rtl"] .lib-rule { background: linear-gradient(270deg, rgb(226 230 236), transparent); }
    [dir="rtl"] .dark .lib-rule { background: linear-gradient(270deg, rgba(100, 204, 197, .22), transparent); }

    /* ── Pagination ────────────────────────────────────────────────── */
    .lib-pager {
        background: rgba(255, 255, 255, .82);
        -webkit-backdrop-filter: blur(14px);
        backdrop-filter: blur(14px);
        border: 1px solid rgb(233 236 240);
        box-shadow: 0 10px 30px -18px rgba(2, 30, 68, .3);
    }
    .dark .lib-pager {
        background: rgba(22, 33, 62, .82);
        border-color: rgba(100, 204, 197, .14);
    }

    @media (prefers-reduced-motion: reduce) {
        .lib-spot, .lib-spot *, .lib-pill, .lib-ctrl {
            transition: none !important;
            transform: none !important;
        }
    }
</style>
@endpush

@section('content')

{{-- Included explicitly, not left to the cards: the spotlight reuses the
     card's badge and meta classes, and a library holding a single article
     renders no card at all — which would otherwise strip the spotlight of
     its styles. `@once` inside makes this free when cards do render. --}}
<x-site.article-card-styles />

@php
    $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true);

    // The library opens on a piece of writing, not a counter: prefer a featured
    // study, else the most recent one. Null when the library is still empty.
    // A library can have any number of featured studies, and they all share
    // the one spotlight leaf as slides. Capped at five: past that the dot row
    // stops being a usable control and nobody pages to slide nine.
    $featured = collect($articles)->where('isFeatured', true)->values();

    // With nothing promoted the library still opens on a piece of writing —
    // the newest one — rather than on the filter rail.
    $spotlights = ($featured->isNotEmpty() ? $featured->take(5) : collect([$articles[0] ?? null])->filter())
        ->values();

    // What the slider shows is withheld from the catalogue below, so no study
    // is printed twice while browsing unfiltered.
    $spotlightIds = $spotlights->pluck('id')->all();

    // Below two articles there is nothing to search, filter, sort or page
    // through — the whole browsing apparatus stays off rather than rendering
    // controls that operate on one row.
    $hasLibrary = count($articles) > 1;

    $articlesJson   = json_encode($articles,   JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
    $categoriesJson = json_encode($categories, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
    $tagsJson       = json_encode($tags,       JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
@endphp

<div
    x-data="articlesLibrary({
        articles:    {{ $articlesJson }},
        categories:  {{ $categoriesJson }},
        tags:        {{ $tagsJson }},
        spotlightIds: {{ json_encode($spotlightIds) }},
        perPage:     9,
    })"
>

    {{-- ============================================================ --}}
    {{-- HERO --}}
    {{-- ============================================================ --}}
    <section class="lib-hero">
        <div class="lib-hero-lattice" aria-hidden="true"></div>

        <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative pt-6 pb-20 md:pb-28">

            {{-- Breadcrumb leads the page. It previously sat below the
                 spotlight, orphaned between the opening article and the filter
                 rail, where it oriented nobody. --}}
            <x-site.breadcrumb :items="[['label' => __('articles.public.breadcrumb')]]" class="lib-crumb mb-8 md:mb-10" />

            <div class="max-w-2xl mx-auto text-center">

                <span class="lib-eyebrow">
                    <i data-lucide="book-open" class="w-3.5 h-3.5" aria-hidden="true"></i>
                    {{ __('articles.public.breadcrumb') }}
                </span>

                <h1 class="lib-title text-[2.4rem] leading-tight md:text-[3.5rem] font-bold mt-5">
                    {{ __('articles.public.hero_title') }}
                </h1>

                <span class="lib-orn" aria-hidden="true"><i></i></span>

                <p class="lib-sub text-[15px] md:text-[17px] leading-relaxed mb-7">
                    {{ __('articles.public.hero_subtitle') }}
                </p>

                {{-- Search and the stat line are apparatus for a library. With
                     a single study on the shelf they only advertise how little
                     there is, so they appear with the second article. --}}
                @if ($hasLibrary)
                {{-- Search --}}
                <div class="lib-search">
                    <i data-lucide="search" class="lib-search-icon absolute start-4 top-1/2 -translate-y-1/2 w-[18px] h-[18px] pointer-events-none" aria-hidden="true"></i>
                    <input
                        type="search"
                        x-model.debounce.250ms="search"
                        @input="page = 1"
                        placeholder="{{ __('articles.public.search_placeholder') }}"
                        aria-label="{{ __('articles.public.search_placeholder') }}"
                        class="w-full ps-12 pe-12 py-3.5 text-base sm:text-[15px] bg-transparent focus:outline-none rounded-2xl"
                    >
                    <button
                        x-show="search.length > 0" x-cloak
                        @click="search = ''; page = 1"
                        class="lib-search-clear absolute end-3 top-1/2 -translate-y-1/2 w-7 h-7 rounded-full flex items-center justify-center transition-colors cursor-pointer"
                        aria-label="{{ __('articles.public.close') }}"
                    >
                        <i data-lucide="x" class="w-4 h-4" aria-hidden="true"></i>
                    </button>
                </div>

                {{-- Colophon --}}
                <p class="lib-colophon mt-5">
                    <span><b x-text="articles.length"></b> {{ __('articles.public.article_unit') }}</span>
                    <span class="sep" aria-hidden="true">•</span>
                    <span><b x-text="categories.length"></b> {{ __('articles.public.categories') }}</span>
                    <span class="sep" aria-hidden="true">•</span>
                    <span><b x-text="tags.length"></b> {{ __('articles.public.tags') }}</span>
                </p>
                @endif
            </div>
        </div>
    </section>

    <div class="container mx-auto px-4 sm:px-6 lg:px-8 pb-16">

        {{-- ============================================================ --}}
        {{-- SPOTLIGHT SLIDER — lifted over the hero seam.                 --}}
        {{--                                                               --}}
        {{-- Every promoted study occupies the same one leaf, so featuring --}}
        {{-- a fifth article costs no page height. Stacking them was the   --}}
        {{-- alternative and it pushed the catalogue below the fold.       --}}
        {{--                                                               --}}
        {{-- Steps aside while a filter or search is active: the reader is --}}
        {{-- looking for something specific, not for our editorial pick.   --}}
        {{-- ============================================================ --}}
        @if ($spotlights->isNotEmpty())
            <section x-show="!hasFilters"
                     class="-mt-12 md:-mt-16 mb-9 md:mb-11 relative z-10"
                     aria-roledescription="carousel"
                     aria-label="{{ __('articles.public.featured') }}">

                @foreach ($spotlights as $i => $item)
                    {{-- Slide 0 renders without Alpine so the leaf is in the
                         initial HTML; the rest stay cloaked until it boots,
                         which is also the no-JS fallback. --}}
                    <a href="{{ $item['url'] }}"
                       class="lib-spot group"
                       x-show="spotIndex === {{ $i }}"
                       x-transition:enter="transition ease-out duration-300"
                       x-transition:enter-start="opacity-0"
                       @if ($i > 0) x-cloak @endif
                       role="group"
                       aria-roledescription="slide"
                       aria-label="{{ $i + 1 }} / {{ $spotlights->count() }}">

                        <div class="lib-spot-body">
                            <div class="flex items-center flex-wrap gap-2 mb-4">
                                @if (! empty($item['isFeatured']))
                                    <span class="art-badge art-badge--featured">
                                        <i data-lucide="star" class="w-3 h-3" aria-hidden="true"></i>
                                        {{ __('articles.public.featured') }}
                                    </span>
                                @endif
                                @if (! empty($item['category']))
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-primary/10 text-primary">
                                        {{ $item['category'] }}
                                    </span>
                                @endif
                            </div>

                            {{-- Clamped so every slide is the same height: an
                                 unclamped third line resized the leaf on each
                                 advance and shunted the whole page down. --}}
                            <h2 class="lib-spot-title text-[1.6rem] md:text-[2.1rem] font-bold text-gray-900 dark:text-white mb-3.5 line-clamp-2">
                                {{ $item['title'] }}
                            </h2>

                            <p class="lib-spot-excerpt text-[15px] text-gray-600 dark:text-gray-300 leading-[1.85] line-clamp-3 mb-5">
                                {{ $item['excerpt'] }}
                            </p>

                            <div class="art-meta !gap-x-5 flex-wrap mb-6">
                                @if (! empty($item['author']))
                                    <span>
                                        <i data-lucide="pen-line" class="w-3.5 h-3.5" aria-hidden="true"></i>
                                        {{ $item['author'] }}
                                    </span>
                                @endif
                                <span>
                                    <i data-lucide="calendar" class="w-3.5 h-3.5" aria-hidden="true"></i>
                                    {{ $item['hijriDate'] ?: $item['dateLabel'] }}
                                </span>
                                <span>
                                    <i data-lucide="clock" class="w-3.5 h-3.5" aria-hidden="true"></i>
                                    {{ $item['readTime'] }} {{ __('articles.public.min_read') }}
                                </span>
                            </div>

                            <span class="lib-spot-cta">
                                {{ __('articles.public.read_more') }}
                                <i data-lucide="arrow-{{ $isRtl ? 'left' : 'right' }}" class="w-4 h-4" aria-hidden="true"></i>
                            </span>
                        </div>

                        <div class="lib-spot-media">
                            <img src="{{ $item['image'] }}" alt="{{ $item['title'] }}"
                                 @if ($i === 0) fetchpriority="high" @else loading="lazy" @endif
                                 decoding="async"
                                 onerror="this.style.display='none';this.parentElement.classList.add('img-gradient-fallback')">
                        </div>
                    </a>
                @endforeach

                {{-- Controls only exist when there is somewhere to go. No
                     auto-advance: it would move the leaf out from under a
                     reader mid-sentence, and a pause control to fix that costs
                     more than the effect is worth. --}}
                @if ($spotlights->count() > 1)
                    <div class="lib-spot-nav" x-cloak>
                        <button type="button" @click="prevSpot()" class="lib-spot-arrow"
                                aria-label="{{ __('articles.public.previous') }}">
                            <i data-lucide="chevron-{{ $isRtl ? 'right' : 'left' }}" class="w-4 h-4" aria-hidden="true"></i>
                        </button>

                        <div class="lib-spot-dots">
                            @foreach ($spotlights as $i => $item)
                                <button type="button" @click="spotIndex = {{ $i }}"
                                        class="lib-spot-dot"
                                        :class="spotIndex === {{ $i }} && 'is-active'"
                                        :aria-current="spotIndex === {{ $i }} ? 'true' : 'false'"
                                        aria-label="{{ $item['title'] }}"></button>
                            @endforeach
                        </div>

                        <button type="button" @click="nextSpot()" class="lib-spot-arrow"
                                aria-label="{{ __('articles.public.next') }}">
                            <i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" class="w-4 h-4" aria-hidden="true"></i>
                        </button>
                    </div>
                @endif
            </section>
        @endif

        {{-- Everything below is the browsable library. A catalogue whose only
             article is already open in the spotlight has nothing to filter,
             sort or page through, so the whole apparatus stays off until
             there is a second study to browse. --}}
        @if (empty($articles))
            <x-site.empty-state
                icon="library"
                :title="__('articles.public.empty_title')"
                :message="__('articles.public.empty_message')"
            />
        @elseif (count($articles) > 1)

        {{-- ============================================================ --}}
        {{-- FILTER RAIL --}}
        {{-- ============================================================ --}}
        <div class="lib-rail px-3.5 py-3">
            <div class="flex items-center gap-3">

                {{-- Categories --}}
                <div class="lib-pills flex-1 min-w-0" role="group" aria-label="{{ __('articles.public.categories') }}">
                    <button @click="setCategory(null)" class="lib-pill" :class="activeCategory === null && 'is-active'">
                        <span>{{ __('articles.public.all_categories') }}</span>
                        <span class="n" x-text="articles.length"></span>
                    </button>
                    <template x-for="cat in categories" :key="cat.id">
                        <button @click="setCategory(cat.id)" class="lib-pill" :class="activeCategory === cat.id && 'is-active'">
                            <span x-text="cat.name"></span>
                            <span class="n" x-text="cat.count"></span>
                        </button>
                    </template>
                </div>

                {{-- Controls --}}
                <div class="flex items-center gap-1.5 shrink-0">
                    <select
                        x-model="sort"
                        @change="page = 1"
                        aria-label="{{ __('articles.public.sort_label') }}"
                        class="lib-ctrl h-9 ps-3 pe-8 text-base sm:text-[13px] font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-primary/30"
                    >
                        <option value="latest">{{ __('articles.public.sort_latest') }}</option>
                        <option value="oldest">{{ __('articles.public.sort_oldest') }}</option>
                        <option value="popular">{{ __('articles.public.sort_popular') }}</option>
                        <option value="title">{{ __('articles.public.sort_title') }}</option>
                    </select>

                    <button
                        x-show="tags.length > 0"
                        @click="showTags = !showTags"
                        class="lib-ctrl w-9 h-9"
                        :class="(showTags || activeTags.length) && 'is-active'"
                        :aria-expanded="showTags"
                        aria-controls="lib-tags"
                        aria-label="{{ __('articles.public.tags') }}"
                    >
                        <i data-lucide="tags" class="w-[18px] h-[18px]" aria-hidden="true"></i>
                    </button>

                    <span class="hidden sm:flex items-center gap-1.5">
                        <button @click="setView('grid')" class="lib-ctrl w-9 h-9" :class="view === 'grid' && 'is-active'"
                                :aria-pressed="view === 'grid'" aria-label="{{ __('articles.public.view_grid') }}">
                            <i data-lucide="layout-grid" class="w-[18px] h-[18px]" aria-hidden="true"></i>
                        </button>
                        <button @click="setView('list')" class="lib-ctrl w-9 h-9" :class="view === 'list' && 'is-active'"
                                :aria-pressed="view === 'list'" aria-label="{{ __('articles.public.view_list') }}">
                            <i data-lucide="list" class="w-[18px] h-[18px]" aria-hidden="true"></i>
                        </button>
                    </span>
                </div>
            </div>

            {{-- Tag drawer --}}
            <div id="lib-tags" x-show="showTags" x-collapse x-cloak
                 class="pt-3 mt-3 border-t border-gray-100 dark:border-white/10">
                <div class="flex flex-wrap gap-1.5">
                    <template x-for="tag in tags" :key="tag.name">
                        <button @click="toggleTag(tag.name)" class="lib-pill"
                                :class="activeTags.includes(tag.name) && 'is-active'">
                            <span x-text="tag.name"></span>
                            <span class="n" x-text="tag.count"></span>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- SECTION HEAD: title + rule + live count, then filter chips   --}}
        {{-- ============================================================ --}}
        <div class="flex items-center gap-4 mt-9 mb-5" :class="view === 'list' && 'lib-narrow'">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white whitespace-nowrap"
                x-text="hasFilters ? @js(__('articles.public.breadcrumb')) : @js(__('articles.public.more_articles'))"></h2>
            <span class="lib-rule" aria-hidden="true"></span>
            <p class="text-[13px] text-gray-500 dark:text-gray-400 whitespace-nowrap tabular-nums">
                {{ __('site.showing') }}
                <b class="text-primary" x-text="filtered.length"></b>
                {{ __('site.of') }}
                <span x-text="articles.length"></span>
                {{ __('articles.public.article_unit') }}
            </p>
        </div>

        <div x-show="hasFilters" x-cloak class="flex flex-wrap items-center gap-2 mb-5"
             :class="view === 'list' && 'lib-narrow'">
            <button @click="clearFilters()"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-red-500 text-white hover:bg-red-600 transition-colors cursor-pointer">
                <i data-lucide="x" class="w-3.5 h-3.5" aria-hidden="true"></i>
                {{ __('articles.public.clear_filters') }}
            </button>
            <template x-if="activeCategory !== null">
                <button @click="setCategory(activeCategory)"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-primary/10 text-primary hover:bg-primary/20 transition-colors cursor-pointer">
                    <i data-lucide="folder" class="w-3.5 h-3.5" aria-hidden="true"></i>
                    <span x-text="activeCategoryName"></span>
                    <i data-lucide="x" class="w-3 h-3 opacity-70" aria-hidden="true"></i>
                </button>
            </template>
            <template x-for="tag in activeTags" :key="tag">
                <button @click="toggleTag(tag)"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-secondary/10 text-secondary hover:bg-secondary/20 transition-colors cursor-pointer">
                    <i data-lucide="tag" class="w-3.5 h-3.5" aria-hidden="true"></i>
                    <span x-text="tag"></span>
                    <i data-lucide="x" class="w-3 h-3 opacity-70" aria-hidden="true"></i>
                </button>
            </template>
        </div>

        {{-- ============================================================ --}}
        {{-- CARDS                                                        --}}
        {{--                                                              --}}
        {{-- Server-rendered so every article is a real, crawlable <a> in --}}
        {{-- the initial HTML and the library works with JS off. Alpine    --}}
        {{-- only decides which leaves are on the current page (x-show)    --}}
        {{-- and their visual order (CSS `order`). Grid vs list is a class --}}
        {{-- swap on this one container — not a second copy of the DOM.    --}}
        {{-- ============================================================ --}}
        <div :class="view === 'list'
                ? 'art-list flex flex-col gap-4'
                : 'grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5 sm:gap-6'">
            @foreach ($articles as $i => $article)
                {{-- Computed out here on purpose: a `<` inside a component-tag
                     attribute derails Blade's tag parser. --}}
                @php($aboveFold = $i <= 2)
                <x-site.article-card
                    :article="$article"
                    :eager="$aboveFold"
                    x-show="onPage({{ $article['id'] }})"
                    ::style="{ order: orderRank({{ $article['id'] }}) }"
                />
            @endforeach
        </div>

        {{-- ============================================================ --}}
        {{-- NO MATCHES — only when a filter actually excluded everything. --}}
        {{-- ============================================================ --}}
        <div x-show="filtered.length === 0" x-cloak>
            <x-site.empty-state
                icon="file-search"
                :title="__('articles.public.empty_title')"
                :message="__('articles.public.empty_message')"
                :showReset="true"
                @click="clearFilters()"
            />
        </div>

        {{-- ============================================================ --}}
        {{-- PAGINATION --}}
        {{-- ============================================================ --}}
        <nav x-show="totalPages > 1" x-cloak aria-label="{{ __('articles.public.pagination') }}"
             class="flex justify-center mt-10">
            <div class="lib-pager rounded-full px-2 py-1.5 flex items-center gap-1 flex-wrap justify-center">
                <button @click="goTo(page - 1)" :disabled="page === 1"
                        class="w-9 h-9 rounded-full flex items-center justify-center text-gray-600 dark:text-gray-300 hover:bg-primary/10 hover:text-primary transition-colors disabled:opacity-35 disabled:cursor-not-allowed cursor-pointer"
                        aria-label="{{ __('articles.public.previous') }}">
                    <i data-lucide="chevron-{{ $isRtl ? 'right' : 'left' }}" class="w-4 h-4" aria-hidden="true"></i>
                </button>

                <template x-for="p in pageNumbers" :key="p">
                    <button @click="goTo(p)"
                            class="min-w-9 h-9 px-3 rounded-full font-bold text-[13px] transition-all tabular-nums cursor-pointer"
                            :class="p === page
                                ? 'text-white bg-gradient-to-l from-primary to-secondary shadow-md'
                                : 'text-gray-700 dark:text-gray-200 hover:bg-primary/10 hover:text-primary'"
                            :aria-current="p === page ? 'page' : null"
                            x-text="p"></button>
                </template>

                <button @click="goTo(page + 1)" :disabled="page === totalPages"
                        class="w-9 h-9 rounded-full flex items-center justify-center text-gray-600 dark:text-gray-300 hover:bg-primary/10 hover:text-primary transition-colors disabled:opacity-35 disabled:cursor-not-allowed cursor-pointer"
                        aria-label="{{ __('articles.public.next') }}">
                    <i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" class="w-4 h-4" aria-hidden="true"></i>
                </button>
            </div>
        </nav>

        @endif

    </div>
</div>

@push('scripts')
<script>
    function articlesLibrary(config) {
        return {
            articles:    config.articles || [],
            categories:  config.categories || [],
            tags:        config.tags || [],
            spotlightIds: config.spotlightIds || [],
            spotIndex: 0,
            perPage:     config.perPage || 9,

            search: '',
            activeCategory: null,
            activeTags: [],
            sort: 'latest',
            view: localStorage.getItem('articlesView') || 'grid',
            showTags: false,
            page: 1,

            init() {
                this.$watch('paginated', () => this.refreshIcons());
                this.$watch('view', () => this.refreshIcons());
                this.$watch('hasFilters', () => this.refreshIcons());
                this.$watch('showTags', () => this.refreshIcons());
                this.$watch('spotIndex', () => this.refreshIcons());
                this.refreshIcons();
            },

            /**
             * While browsing unfiltered, the lead folio and the featured strip
             * are already shown above the rail — listing them again would print
             * the same studies twice. As soon as the reader filters or
             * searches, they step aside and the whole library is searchable.
             */
            get browseList() {
                if (this.hasFilters || this.spotlightIds.length === 0) return this.articles;

                return this.articles.filter(a => !this.spotlightIds.includes(a.id));
            },

            get filtered() {
                let list = this.browseList;

                if (this.activeCategory !== null) {
                    list = list.filter(a => a.categoryId === this.activeCategory);
                }

                if (this.activeTags.length) {
                    list = list.filter(a => this.activeTags.every(t => (a.tags || []).includes(t)));
                }

                const q = this.search.trim().toLowerCase();
                if (q) {
                    list = list.filter(a =>
                        (a.title || '').toLowerCase().includes(q) ||
                        (a.excerpt || '').toLowerCase().includes(q) ||
                        (a.tags || []).some(t => t.toLowerCase().includes(q))
                    );
                }

                list = [...list];
                if (this.sort === 'latest') {
                    list.sort((a, b) => b.timestamp - a.timestamp);
                } else if (this.sort === 'oldest') {
                    list.sort((a, b) => a.timestamp - b.timestamp);
                } else if (this.sort === 'popular') {
                    list.sort((a, b) => b.views - a.views);
                } else if (this.sort === 'title') {
                    list.sort((a, b) => (a.title || '').localeCompare(b.title || '', undefined, { sensitivity: 'base' }));
                }

                return list;
            },

            get totalPages() {
                return Math.max(1, Math.ceil(this.filtered.length / this.perPage));
            },

            get paginated() {
                const start = (this.page - 1) * this.perPage;

                return this.filtered.slice(start, start + this.perPage);
            },

            // --- Server-rendered card enhancement -------------------------------
            // Cards are printed by Blade; Alpine only toggles which ones show
            // (current filtered page) and their visual order (active sort).
            get pageIds() {
                return new Set(this.paginated.map(a => a.id));
            },

            get orderMap() {
                const map = {};
                this.filtered.forEach((a, i) => { map[a.id] = i; });

                return map;
            },

            onPage(id) {
                return this.pageIds.has(id);
            },

            orderRank(id) {
                return this.orderMap[id] ?? 0;
            },
            // --------------------------------------------------------------------

            get pageNumbers() {
                const total = this.totalPages;
                const start = Math.max(1, this.page - 2);
                const end = Math.min(total, this.page + 2);
                const out = [];
                for (let i = start; i <= end; i++) out.push(i);

                return out;
            },

            get hasFilters() {
                return this.search.trim() !== '' || this.activeCategory !== null || this.activeTags.length > 0;
            },

            get activeCategoryName() {
                const cat = this.categories.find(c => c.id === this.activeCategory);

                return cat ? cat.name : '';
            },

            // Spotlight slider — wraps in both directions so the arrows are
            // never dead ends.
            nextSpot() {
                this.spotIndex = (this.spotIndex + 1) % this.spotlightIds.length;
            },

            prevSpot() {
                const n = this.spotlightIds.length;
                this.spotIndex = (this.spotIndex - 1 + n) % n;
            },

            setCategory(id) {
                this.activeCategory = (this.activeCategory === id) ? null : id;
                this.page = 1;
            },

            toggleTag(name) {
                const i = this.activeTags.indexOf(name);
                if (i > -1) this.activeTags.splice(i, 1);
                else this.activeTags.push(name);
                this.page = 1;
            },

            setView(mode) {
                this.view = mode;
                localStorage.setItem('articlesView', mode);
            },

            goTo(p) {
                if (p < 1 || p > this.totalPages) return;
                this.page = p;
                this.$el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            },

            clearFilters() {
                this.search = '';
                this.activeCategory = null;
                this.activeTags = [];
                this.page = 1;
            },

            refreshIcons() {
                this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
            },
        };
    }
</script>
@endpush

@endsection
