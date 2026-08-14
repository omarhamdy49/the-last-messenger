{{--
    Shared "Manuscript Leaf" article-card styles.

    Lives on its own so <x-site.article-card> can be dropped onto any surface
    without each one growing its own lookalike stylesheet. `@once` is keyed to
    this file, so including it from several places (the card itself pulls it in)
    still emits the CSS exactly once per request.

    Palette is the site brand read as manuscript pigment: lapis #0056b3,
    verdigris #218838, teal #64CCC5. All motion is GPU-only (transform/opacity)
    and gated behind prefers-reduced-motion, so the card degrades to a clean
    static composition.
--}}
@once
@push('head')
<style>
    /* ── Card shell ─────────────────────────────────────────────── */
    .art-card {
        position: relative;
        display: flex;
        flex-direction: column;
        height: 100%;
        border-radius: 1.25rem;
        overflow: hidden;
        background: #fff;
        border: 1px solid rgb(237 239 242);
        box-shadow: 0 1px 2px rgba(2, 30, 68, .05), 0 8px 24px -16px rgba(2, 30, 68, .18);
        transition: transform .4s cubic-bezier(.22, 1, .36, 1),
                    box-shadow .4s cubic-bezier(.22, 1, .36, 1),
                    border-color .4s ease;
    }
    .dark .art-card {
        background: #16213e;
        border-color: rgba(100, 204, 197, .12);
        box-shadow: 0 1px 2px rgba(0, 0, 0, .35), 0 8px 24px -16px rgba(0, 0, 0, .6);
    }
    .art-card:hover {
        transform: translateY(-6px);
        border-color: rgba(0, 86, 179, .28);
        box-shadow: 0 8px 16px -8px rgba(2, 30, 68, .1), 0 28px 56px -24px rgba(0, 86, 179, .35);
    }
    .dark .art-card:hover {
        border-color: rgba(100, 204, 197, .32);
        box-shadow: 0 28px 56px -24px rgba(100, 204, 197, .22);
    }
    /* Light hairline: the card's top edge ignites on hover/focus — the
       library's echo of the seerah light trail. Paint-only. */
    .art-card::after {
        content: '';
        position: absolute;
        top: 0;
        inset-inline: 14%;
        height: 2px;
        border-radius: 2px;
        z-index: 2;
        pointer-events: none;
        background: linear-gradient(90deg, transparent, #64CCC5, transparent);
        opacity: 0;
        transform: scaleX(.35);
        transition: opacity .45s ease, transform .55s cubic-bezier(.22, 1, .36, 1);
    }
    .art-card:hover::after,
    .art-card:focus-visible::after { opacity: 1; transform: scaleX(1); }
    .art-card:focus-visible {
        outline: none;
        border-color: rgba(0, 86, 179, .5);
        box-shadow: 0 0 0 4px rgba(0, 86, 179, .22);
    }

    /* Illumination thread that draws across the head of the leaf on hover.
       Origin follows the writing direction so it grows from the leading edge. */
    .art-card::before {
        content: '';
        position: absolute;
        inset-inline: 0;
        top: 0;
        height: 3px;
        z-index: 3;
        background: linear-gradient(90deg, #0056b3, #64CCC5, #218838);
        transform: scaleX(0);
        transform-origin: left;
        transition: transform .45s cubic-bezier(.22, 1, .36, 1);
    }
    [dir="rtl"] .art-card::before { transform-origin: right; }
    .art-card:hover::before,
    .art-card:focus-visible::before { transform: scaleX(1); }

    /* ── Media plate ────────────────────────────────────────────── */
    .art-card-media {
        position: relative;
        overflow: hidden;
        background: linear-gradient(135deg, #eef2f7, #e3e9f2);
        flex: none;
    }
    .dark .art-card-media { background: linear-gradient(135deg, #0f3460, #16213e); }

    /* Absolutely positioned rather than height:100% — in the row variant the
       plate has no definite height, so a percentage height would resolve to
       auto and the image would render at its full intrinsic size, dragging
       the whole card open. */
    .art-card-media img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform .7s cubic-bezier(.22, .61, .36, 1);
    }
    .art-card:hover .art-card-media img { transform: scale(1.06); }

    /* Scrim keeps the badges legible over any photograph. */
    .art-card-media::after {
        content: '';
        position: absolute;
        inset: 0;
        pointer-events: none;
        background: linear-gradient(to top, rgba(4, 18, 38, .58) 0%, rgba(4, 18, 38, .12) 38%, transparent 62%);
        transition: opacity .4s ease;
    }
    .art-card:hover .art-card-media::after { opacity: .75; }

    /* ── Badges ─────────────────────────────────────────────────── */
    .art-badge {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        padding: .3rem .7rem;
        border-radius: 9999px;
        font-size: .6875rem;
        font-weight: 700;
        line-height: 1.4;
        white-space: nowrap;
    }
    .art-badge--featured {
        color: #fff;
        background: linear-gradient(135deg, #0056b3 0%, #218838 100%);
        box-shadow: 0 6px 16px -6px rgba(0, 86, 179, .65);
    }
    .art-badge--category {
        color: #fff;
        background: rgba(6, 22, 44, .5);
        border: 1px solid rgba(255, 255, 255, .28);
        -webkit-backdrop-filter: blur(10px);
        backdrop-filter: blur(10px);
    }

    /* ── Body ───────────────────────────────────────────────────── */
    .art-card-body {
        display: flex;
        flex-direction: column;
        flex: 1 1 auto;
        min-width: 0;
        padding: 1.25rem;
    }

    /* Inherits the site family (Cairo) — Amiri is reserved for scripture, and
       running it in the headline role made the library read as a different
       product from the rest of the site. */
    .art-card-title {
        line-height: 1.45;
        transition: color .25s ease;
    }
    .art-card:hover .art-card-title { color: #0056b3; }
    .dark .art-card:hover .art-card-title { color: #64CCC5; }

    .art-card-excerpt {
        font-size: .875rem;
        line-height: 1.75;
        color: rgb(90 100 116);
    }
    .dark .art-card-excerpt { color: rgb(178 186 199); }

    .art-tag {
        padding: .15rem .55rem;
        border-radius: 9999px;
        font-size: .6875rem;
        font-weight: 600;
        color: rgb(107 114 128);
        background: rgb(244 246 249);
    }
    .dark .art-tag { color: rgb(156 163 175); background: rgba(255, 255, 255, .06); }

    /* Footer is pinned with margin-block-start:auto so the meta row lines up
       across every card in a row regardless of title or excerpt length. */
    .art-card-foot {
        margin-block-start: auto;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        padding-block-start: .85rem;
        border-block-start: 1px solid rgb(240 242 245);
    }
    .dark .art-card-foot { border-block-start-color: rgba(100, 204, 197, .12); }

    .art-meta {
        display: inline-flex;
        align-items: center;
        gap: .9rem;
        min-width: 0;
        font-size: .75rem;
        color: rgb(122 132 148);
        font-variant-numeric: tabular-nums;
    }
    .dark .art-meta { color: rgb(148 157 172); }
    .art-meta > span { display: inline-flex; align-items: center; gap: .3rem; }

    /* The cue stays visible at rest — touch devices have no hover, so a
       hover-only affordance would never appear on a phone. Only the arrow
       animates. */
    .art-cue {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        flex: none;
        font-size: .75rem;
        font-weight: 700;
        color: #0056b3;
    }
    .dark .art-cue { color: #64CCC5; }
    .art-cue i, .art-cue svg {
        transition: transform .3s cubic-bezier(.22, 1, .36, 1);
    }
    .art-card:hover .art-cue svg { transform: translateX(3px); }
    [dir="rtl"] .art-card:hover .art-cue svg { transform: translateX(-3px); }

    /* ── Row variant (list view / wide rails) ───────────────────────
       `.art-list` on the container turns the *same* cards into rows, so the
       library's grid/list toggle is pure CSS over one set of DOM nodes rather
       than two parallel renderings that drift apart. The specificity here
       (0,2,0) intentionally beats Tailwind's aspect-* utility (0,1,0) on the
       media plate. */
    @media (min-width: 640px) {
        .art-card--row,
        .art-list .art-card { flex-direction: row; }

        .art-card--row .art-card-media,
        .art-list .art-card-media {
            width: 17rem;
            aspect-ratio: auto;
        }

        .art-card--row .art-card-body,
        .art-list .art-card-body { padding: 1.4rem 1.5rem; }
    }

    /* A row spanning the full container is an editorial listing, not a wide
       grid card: at grid-card type sizes the text occupied about a third of
       the width and the bottom-pinned footer flung its cue to the far edge,
       leaving a hole through the middle. From here up the row gets its own
       scale — larger head, longer excerpt, meta and cue kept together — so
       the width is filled by content instead of by gap. */
    @media (min-width: 1024px) {
        /* A list is reading mode, so it takes a reading measure rather than the
           full container. Stretched edge to edge, a row with a two-line excerpt
           left a third of its width blank no matter how the inside was
           arranged — the width itself was the defect. */
        .art-list {
            max-inline-size: 58rem;
            margin-inline: auto;
        }

        .art-card--row .art-card-media,
        .art-list .art-card-media { width: 19rem; }

        .art-card--row .art-card-body,
        .art-list .art-card-body {
            justify-content: center;
            padding: 1.9rem 2.1rem;
        }

        .art-card--row .art-card-title,
        .art-list .art-card-title {
            font-size: 1.6rem;
            line-height: 1.4;
            margin-block-end: .6rem;
        }

        /* Three lines at a wider measure — the excerpt is what actually fills
           an editorial row, so let it. */
        .art-card--row .art-card-excerpt,
        .art-list .art-card-excerpt {
            font-size: .9375rem;
            line-height: 1.85;
            max-inline-size: 68ch;
            -webkit-line-clamp: 3;
        }

        /* Footer flows with the text rather than being pinned to the card
           floor, and the cue sits beside the meta instead of across a void. */
        .art-card--row .art-card-foot,
        .art-list .art-card-foot {
            margin-block-start: 1.1rem;
            justify-content: flex-start;
            gap: 1.75rem;
            border-block-start: 0;
            padding-block-start: 0;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .art-card,
        .art-card *,
        .art-card::before {
            transition: none !important;
            transform: none !important;
        }
    }
</style>
@endpush
@endonce
