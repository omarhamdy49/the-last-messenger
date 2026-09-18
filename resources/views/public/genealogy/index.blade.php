@extends('layouts.public')

@section('title', __('genealogy.public.page_title'))
@section('meta_description', __('genealogy.public.page_description'))

@push('structured_data')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "CollectionPage",
    "name": "{{ __('genealogy.public.page_title') }}",
    "description": "{{ __('genealogy.public.page_description') }}",
    "url": "{{ route('public.genealogy') }}",
    "inLanguage": "{{ app()->getLocale() }}"
}
</script>
@endpush

@push('head')
<style>
    /* ══════════════════════════════════════════════════════════════════════
       THE NASAB, IN THE PUBLIC DESIGN SYSTEM.

       Surfaces, ink, hairlines, elevation, radii and easing come from the `sx`
       tokens — which is why almost every `.dark` twin in this file is gone: the
       tokens flip themselves, so the old specificity trap (`.dark .gs-marker`
       outranking `.gs-marker--focus`) cannot come back.

       What stays page-specific is the geometry no other page has — a rail, a
       pannable canvas, connector paths — and ONE relation palette.
       ══════════════════════════════════════════════════════════════════════ */

    /* ══════════════════════════════════════════════════════════════════════
       ONE RELATION PALETTE, THREE VIEWS

       The spine, the directory and the canvas all say the same thing about the
       same person, so the colour that means "forefather" is defined once here
       and read by all three. It used to rotate through five palettes by array
       index, which meant the same person was blue in the tree and orange in
       the list.

       Colour is never the only carrier: every one of these is paired with the
       written relation label.
       ══════════════════════════════════════════════════════════════════════ */
    .genealogy {
        --rel-ancestor: var(--sx-accent);
        --rel-ancestor_mother: #be185d;
        --rel-self: var(--sx-green);
        --rel-wife: #be185d;
        --rel-child: #0f766e;
        --rel-grandchild: #0e7490;
        --rel-sibling: #6d28d9;
        --rel-uncle: #6d28d9;
        --rel-cousin: #b45309;
        --rel-relative: var(--sx-muted);
    }
    .dark .genealogy {
        --rel-ancestor_mother: #f9a8d4;
        --rel-self: var(--sx-teal);
        --rel-wife: #f9a8d4;
        --rel-child: #5eead4;
        --rel-grandchild: #67e8f9;
        --rel-sibling: #c4b5fd;
        --rel-uncle: #c4b5fd;
        --rel-cousin: #fcd34d;
    }

    /* ══════════════════════════════════════════════════════════════════════
       THE NASAB SPINE  (.gs-*)  — the reading at every width.
       Server-rendered; no canvas, no gestures, no JavaScript required to read.
       ══════════════════════════════════════════════════════════════════════ */
    .gs { position: relative; }

    .gs-rail { position: relative; margin: 0; padding: 0 0 0.5rem; list-style: none; }
    /* The vertical rail the generations hang from. */
    .gs-rail::before {
        content: '';
        position: absolute;
        inset-block: 0.75rem;
        inset-inline-start: 1.09rem;
        width: 2px;
        border-radius: 2px;
        background: linear-gradient(180deg, var(--sx-accent), var(--sx-teal), var(--sx-green));
        opacity: 0.5;
    }

    .gs-row { position: relative; display: flex; align-items: flex-start; gap: .75rem; padding-block: .3rem; }

    .gs-marker {
        position: relative;
        z-index: 1;
        flex-shrink: 0;
        width: 2.25rem;
        height: 2.25rem;
        margin-top: .55rem;
        border-radius: 9999px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .68rem;
        font-weight: 800;
        font-variant-numeric: tabular-nums;
        color: var(--sx-accent);
        background: var(--sx-surface);
        border: 2px solid var(--sx-line-strong);
    }
    .gs-marker--focus {
        color: #fff;
        border-color: transparent;
        background: var(--sx-grad);
        box-shadow: 0 6px 18px -6px rgb(0 86 179 / 0.8);
    }

    .gs-body { min-width: 0; flex: 1; }

    .gs-card {
        display: flex;
        align-items: center;
        gap: .7rem;
        width: 100%;
        /* 44px floor: the whole row is the tap target, not the name inside it. */
        min-height: 3.5rem;
        padding: .6rem .8rem;
        border-radius: 1rem;
        text-align: start;
        cursor: pointer;
        background: var(--sx-surface);
        border: 1px solid var(--sx-line);
        box-shadow: var(--sx-shadow);
        transition: border-color .2s var(--sx-ease), transform .2s var(--sx-ease);
    }
    .gs-card:hover { border-color: rgb(0 86 179 / 0.35); transform: translateY(-1px); }
    .gs-card:focus-visible { outline: 2px solid var(--sx-teal); outline-offset: 2px; }

    .gs-card--focus {
        padding: .9rem 1rem;
        border-color: transparent;
        background: var(--sx-grad);
    }
    .gs-card--focus .gs-name { color: #fff; font-size: 1.15rem; }
    .gs-card--focus .gs-go { color: rgb(255 255 255 / 0.8); }

    .gs-ava {
        flex-shrink: 0;
        width: 2.4rem;
        height: 2.4rem;
        border-radius: 9999px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.05rem;
        font-weight: 700;
        color: #fff;
        background: var(--sx-grad);
        box-shadow: inset 0 0 0 2px rgb(255 255 255 / 0.3);
        user-select: none;
    }
    .gs-ava--focus {
        width: 3.1rem;
        height: 3.1rem;
        font-size: 1.4rem;
        background: rgb(255 255 255 / 0.2);
        box-shadow: inset 0 0 0 2px rgb(255 255 255 / 0.45);
    }

    .gs-meta { min-width: 0; flex: 1; display: flex; flex-direction: column; gap: .3rem; }
    .gs-name {
        font-weight: 700;
        font-size: .95rem;
        line-height: 1.3;
        color: var(--sx-ink);
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .gs-tags { display: flex; flex-wrap: wrap; gap: .3rem; }
    .gs-tag {
        padding: .1rem .5rem;
        border-radius: 9999px;
        font-size: .65rem;
        font-weight: 700;
        color: var(--rel-ancestor);
        background: var(--sx-accent-soft);
    }
    .gs-tag--soft {
        color: var(--sx-muted);
        background: var(--sx-surface-2);
        max-width: 14rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .gs-tag--root { color: var(--rel-cousin); background: color-mix(in srgb, currentColor 12%, transparent); }
    .gs-tag--focus { color: #fff; background: rgb(255 255 255 / 0.22); }

    .gs-go { width: 1.1rem; height: 1.1rem; flex-shrink: 0; color: var(--sx-muted); }

    /* Lifespan, on the trailing edge. Rendered only when the record holds one
       — most of the early nasab does not, and an empty slot reading "—" would
       be worse than the space. */
    .gs-life {
        display: none;
        flex-shrink: 0;
        margin-inline-start: auto;
        padding-inline: .6rem;
        font-size: .7rem;
        font-weight: 700;
        color: var(--sx-muted);
        white-space: nowrap;
    }
    .gs-card--focus .gs-life { color: rgb(255 255 255 / 0.85); }
    @media (min-width: 480px) { .gs-life { display: block; } }

    .gs-mother, .gs-branch-toggle {
        display: flex;
        align-items: center;
        gap: .4rem;
        width: 100%;
        min-height: 2.5rem;
        margin-top: .35rem;
        padding: .4rem .7rem;
        border-radius: .75rem;
        text-align: start;
        cursor: pointer;
        font-size: .75rem;
        font-weight: 600;
        transition: border-color .2s var(--sx-ease), background-color .2s var(--sx-ease);
    }
    .gs-mother {
        color: var(--rel-wife);
        background: color-mix(in srgb, currentColor 7%, transparent);
        border: 1px dashed color-mix(in srgb, currentColor 35%, transparent);
    }
    .gs-mother:hover { border-color: currentColor; }
    .gs-mother-name { font-weight: 800; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .gs-mother-rel { opacity: .75; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

    .gs-branches { margin-top: .35rem; }
    .gs-branch-toggle {
        color: var(--sx-ink-2);
        background: var(--sx-surface-2);
        border: 1px solid var(--sx-line);
    }
    .gs-branch-toggle:hover { border-color: var(--sx-accent); }
    .gs-caret { margin-inline-start: auto; transition: transform .2s var(--sx-ease); }
    .gs-caret--open { transform: rotate(180deg); }

    .gs-branch-list {
        display: flex;
        flex-direction: column;
        gap: .4rem;
        margin-top: .45rem;
        padding-inline-start: .6rem;
        border-inline-start: 2px dashed var(--sx-line-strong);
    }

    .gs-chip {
        display: flex;
        align-items: center;
        gap: .5rem;
        width: 100%;
        min-height: 2.75rem;
        padding: .35rem .6rem;
        border-radius: .7rem;
        text-align: start;
        cursor: pointer;
        background: var(--sx-surface);
        border: 1px solid var(--sx-line);
        transition: border-color .2s var(--sx-ease);
    }
    .gs-chip:hover { border-color: var(--sx-accent); }
    .gs-chip--spouse { border-style: dashed; border-color: color-mix(in srgb, var(--rel-wife) 40%, transparent); }

    .gs-chip-ava {
        flex-shrink: 0;
        width: 1.75rem;
        height: 1.75rem;
        border-radius: 9999px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .75rem;
        font-weight: 700;
        color: #fff;
        background: var(--sx-grad);
    }
    .gs-chip--spouse .gs-chip-ava { background: var(--rel-wife); }
    .gs-chip-text { min-width: 0; display: flex; flex-direction: column; }
    .gs-chip-name {
        font-size: .8rem;
        font-weight: 700;
        line-height: 1.3;
        color: var(--sx-ink);
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .gs-chip-sub {
        font-size: .65rem;
        font-weight: 600;
        color: var(--rel-child);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .gs-kids {
        display: flex;
        flex-wrap: wrap;
        gap: .3rem;
        margin-top: .3rem;
        padding-inline-start: 2.25rem;
    }
    .gs-kid {
        max-width: 100%;
        min-height: 1.9rem;
        text-align: start;
        padding: .25rem .6rem;
        border-radius: 9999px;
        font-size: .7rem;
        font-weight: 600;
        cursor: pointer;
        color: var(--sx-ink-2);
        background: var(--sx-surface-2);
        transition: background-color .2s var(--sx-ease), color .2s var(--sx-ease);
    }
    .gs-kid:hover { color: var(--sx-accent); background: var(--sx-accent-soft); }

    .gs-fold { display: flex; padding: .35rem 0; }
    .gs-fold-btn {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        min-height: 2.5rem;
        /* Lines up with .gs-body so the rail reads through the control. */
        margin-inline-start: 3rem;
        padding: .4rem .9rem;
        border-radius: 9999px;
        font-size: .75rem;
        font-weight: 700;
        cursor: pointer;
        color: var(--sx-accent);
        background: var(--sx-surface);
        border: 1px dashed var(--sx-accent);
        transition: background-color .2s var(--sx-ease), color .2s var(--sx-ease);
    }
    .gs-fold-btn:hover { color: #fff; background: var(--sx-grad); border-color: transparent; }

    /* ── Wide viewports ──────────────────────────────────────────────────
       The spine is the rendering at every width now, so it has to look built
       for a desktop rather than like a phone column someone stretched. More
       air, a bigger focus card, and the collateral branches laid out as a grid
       — fourteen uncles in a single 900px-wide column is a lot of scrolling
       for a set that fits three across. */
    @media (min-width: 640px) {
        .gs-rail::before { inset-inline-start: 1.34rem; }
        .gs-row { gap: 1rem; padding-block: .45rem; }
        .gs-marker { width: 2.75rem; height: 2.75rem; font-size: .8rem; margin-top: .7rem; }
        .gs-card { min-height: 4.25rem; padding: .85rem 1.1rem; border-radius: 1.15rem; }
        .gs-ava { width: 2.9rem; height: 2.9rem; font-size: 1.25rem; }
        .gs-name { font-size: 1.05rem; }
        .gs-tag { font-size: .7rem; padding: .15rem .6rem; }
        .gs-tag--soft { max-width: 22rem; }

        .gs-card--focus { padding: 1.25rem; }
        .gs-card--focus .gs-name { font-size: 1.5rem; }
        .gs-ava--focus { width: 4rem; height: 4rem; font-size: 1.9rem; }

        .gs-branch-list {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .6rem;
            padding-inline-start: .9rem;
        }
        .gs-fold-btn { margin-inline-start: 3.75rem; }
    }

    @media (min-width: 1024px) {
        /* A marriage group owns its children, so it spans the full row rather
           than sitting in a column beside an unrelated uncle. */
        .gs-branch:has(.gs-kids) { grid-column: 1 / -1; }
        .gs-kids { padding-inline-start: 2.5rem; }
    }

    /* ══════════════════════════════════════════════════════════════════════
       THE DIRECTORY  (.gl-*)
       ══════════════════════════════════════════════════════════════════════ */
    .gl-heading {
        display: flex;
        align-items: center;
        gap: .55rem;
        margin-bottom: .85rem;
        font-size: .95rem;
        font-weight: 800;
        color: var(--sx-ink);
    }
    .gl-heading-dot { width: .55rem; height: .55rem; border-radius: 9999px; background: currentColor; }
    .gl-heading-count {
        padding: .05rem .5rem;
        border-radius: 9999px;
        font-size: .7rem;
        font-weight: 700;
        color: var(--sx-muted);
        background: var(--sx-surface-2);
    }

    .gl-card {
        display: flex;
        align-items: center;
        gap: .7rem;
        width: 100%;
        min-height: 4rem;
        padding: .65rem .8rem;
        border-radius: 1rem;
        text-align: start;
        cursor: pointer;
        background: var(--sx-surface);
        border: 1px solid var(--sx-line);
        box-shadow: var(--sx-shadow);
        transition: transform .2s var(--sx-ease), border-color .2s var(--sx-ease);
    }
    .gl-card:hover { transform: translateY(-2px); border-color: rgb(0 86 179 / 0.35); }
    .gl-card:focus-visible { outline: 2px solid var(--sx-teal); outline-offset: 2px; }

    .gl-ava {
        flex-shrink: 0;
        width: 2.6rem;
        height: 2.6rem;
        border-radius: 9999px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        font-weight: 700;
        color: #fff;
        /* The plate is the relation's colour, and the written relation label
           sits right beside it — the colour never carries the meaning alone. */
        background: var(--rel-relative);
        box-shadow: inset 0 0 0 2px rgb(255 255 255 / 0.28);
        user-select: none;
    }
    .gl-ava--ancestor        { background: var(--rel-ancestor); }
    .gl-ava--ancestor_mother { background: var(--rel-ancestor_mother); }
    .gl-ava--wife            { background: var(--rel-wife); }
    .gl-ava--self            { background: var(--sx-grad); }
    .gl-ava--child           { background: var(--rel-child); }
    .gl-ava--grandchild      { background: var(--rel-grandchild); }
    .gl-ava--sibling,
    .gl-ava--uncle           { background: var(--rel-sibling); }
    .gl-ava--cousin          { background: var(--rel-cousin); }
    .gl-ava--relative        { background: var(--rel-relative); }

    .gl-meta { min-width: 0; flex: 1; display: flex; flex-direction: column; gap: .3rem; }
    .gl-name {
        font-weight: 700;
        font-size: .9rem;
        line-height: 1.3;
        color: var(--sx-ink);
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .gl-tags { display: flex; align-items: center; flex-wrap: wrap; gap: .35rem; }
    .gl-rel {
        padding: .1rem .5rem;
        border-radius: 9999px;
        font-size: .65rem;
        font-weight: 800;
        color: var(--rel-relative);
        background: color-mix(in srgb, currentColor 12%, transparent);
    }
    .gl-rel--ancestor        { color: var(--rel-ancestor); }
    .gl-rel--ancestor_mother { color: var(--rel-ancestor_mother); }
    .gl-rel--self            { color: var(--rel-self); }
    .gl-rel--wife            { color: var(--rel-wife); }
    .gl-rel--child           { color: var(--rel-child); }
    .gl-rel--grandchild      { color: var(--rel-grandchild); }
    .gl-rel--sibling         { color: var(--rel-sibling); }
    .gl-rel--uncle           { color: var(--rel-uncle); }
    .gl-rel--cousin          { color: var(--rel-cousin); }

    .gl-dot--ancestor        { color: var(--rel-ancestor); }
    .gl-dot--ancestor_mother { color: var(--rel-ancestor_mother); }
    .gl-dot--self            { color: var(--rel-self); }
    .gl-dot--wife            { color: var(--rel-wife); }
    .gl-dot--child           { color: var(--rel-child); }
    .gl-dot--grandchild      { color: var(--rel-grandchild); }
    .gl-dot--sibling         { color: var(--rel-sibling); }
    .gl-dot--uncle           { color: var(--rel-uncle); }
    .gl-dot--cousin          { color: var(--rel-cousin); }
    .gl-dot--relative        { color: var(--rel-relative); }

    .gl-date {
        display: inline-flex;
        align-items: center;
        gap: .25rem;
        font-size: .68rem;
        font-weight: 600;
        color: var(--sx-muted);
    }
    .gl-go { width: 1.1rem; height: 1.1rem; flex-shrink: 0; color: var(--sx-muted); }

    /* ══════════════════════════════════════════════════════════════════════
       THE CANVAS  (.gt-*)  — the opt-in fullscreen sheet.

       Cards are absolutely-positioned DOM buttons in a transformed scene, NOT
       <foreignObject> (see the header of resources/js/genealogy-tree.js for
       why). Only the connector paths are SVG.
       ══════════════════════════════════════════════════════════════════════ */
    .gt-stage {
        position: relative;
        height: min(72vh, 44rem);
        min-height: 26rem;
        border-radius: 1.5rem;
        overflow: hidden;
        background:
            radial-gradient(90% 70% at 50% 0%, var(--sx-accent-soft) 0%, transparent 60%),
            var(--sx-bg);
        border: 1px solid var(--sx-line);
        box-shadow: var(--sx-shadow);
    }
    /* The fullscreen sheet. On a phone the canvas may claim the touch gesture
       ONLY here, where it fills the screen and carries an explicit close
       button — inline, it would have eaten the page scroll. */
    .gt-stage--full {
        position: fixed;
        inset: 0;
        z-index: 60;
        height: 100dvh;
        min-height: 0;
        border-radius: 0;
        border: 0;
    }

    .gt-stage::before {
        content: '';
        position: absolute;
        inset: 0;
        background-image: radial-gradient(var(--sx-line-strong) 1px, transparent 1px);
        background-size: 26px 26px;
        pointer-events: none;
    }

    .gt-root {
        position: absolute;
        inset: 0;
        overflow: hidden;
        cursor: grab;
        touch-action: none;
    }
    .gt-root:active { cursor: grabbing; }
    .gt-scene { position: absolute; top: 0; left: 0; transform-origin: 0 0; will-change: transform; }
    .gt-edges { position: absolute; overflow: visible; pointer-events: none; }
    .gt-nodes { position: absolute; left: 0; top: 0; }

    .gt-node { position: absolute; left: 0; top: 0; }

    /* ── Edges ── */
    .gt-link { fill: none; stroke: var(--sx-accent); stroke-opacity: .35; stroke-width: 2; }
    .gt-link--collateral { stroke-opacity: .18; stroke-dasharray: 5 4; }
    .gt-link--focus { stroke: var(--sx-green); stroke-opacity: .55; stroke-width: 2.5; }
    .gt-link--spouse { stroke: var(--rel-wife); stroke-opacity: .4; stroke-dasharray: 4 4; }

    /* ── Cards ── */
    .gt-card {
        position: relative;
        display: flex;
        align-items: center;
        gap: .55rem;
        width: 100%;
        height: 100%;
        padding: .5rem .65rem;
        border-radius: .9rem;
        text-align: start;
        cursor: pointer;
        background: var(--sx-surface);
        border: 1px solid var(--sx-line);
        box-shadow: var(--sx-shadow);
        transition: transform .25s var(--sx-ease), border-color .25s var(--sx-ease);
    }
    .gt-card:hover { transform: translateY(-3px); border-color: var(--sx-accent); }
    .gt-card:focus-visible { outline: 2px solid var(--sx-teal); outline-offset: 2px; }

    .gt-card--ancestor { border-inline-start: 3px solid var(--rel-ancestor); }
    .gt-card--collateral { opacity: .88; }
    .gt-card--spouse { border-inline-start: 3px solid var(--rel-wife); }
    .gt-card--spouse:hover { border-color: var(--rel-wife); }
    .gt-card--spouse .gt-ava { background: var(--rel-wife); }
    .gt-card--spouse .gt-sub { color: var(--rel-wife); }

    .gt-card--focus,
    .gt-card--prophet {
        background: var(--sx-grad);
        border-color: transparent;
    }
    .gt-card--focus .gt-name, .gt-card--prophet .gt-name { color: #fff; font-size: .95rem; }
    .gt-card--focus .gt-sub, .gt-card--prophet .gt-sub { color: rgb(255 255 255 / 0.85); }
    .gt-card--focus .gt-spouse, .gt-card--prophet .gt-spouse { color: rgb(255 255 255 / 0.75); }

    .gt-ava {
        flex-shrink: 0;
        width: 2.1rem;
        height: 2.1rem;
        border-radius: 9999px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1rem;
        color: #fff;
        background: var(--rel-ancestor);
        box-shadow: inset 0 0 0 2px rgb(255 255 255 / 0.35);
        user-select: none;
    }
    .gt-card--collateral .gt-ava { background: var(--rel-sibling); }
    .gt-ava--focus {
        width: 2.9rem;
        height: 2.9rem;
        font-size: 1.5rem;
        background: rgb(255 255 255 / 0.18);
        box-shadow: inset 0 0 0 2px rgb(255 255 255 / 0.4);
    }

    .gt-meta { min-width: 0; display: flex; flex-direction: column; gap: .1rem; }
    .gt-name {
        font-weight: 700;
        font-size: .8rem;
        line-height: 1.25;
        color: var(--sx-ink);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .gt-sub {
        font-size: .62rem;
        font-weight: 700;
        color: var(--rel-child);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .gt-spouse {
        display: inline-flex;
        align-items: center;
        gap: .25rem;
        font-size: .62rem;
        font-weight: 600;
        color: var(--rel-wife);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .gt-spouse svg { width: .65rem; height: .65rem; flex-shrink: 0; }
    .gt-spouse b { font-weight: 800; opacity: .75; }

    .gt-crown {
        position: absolute;
        top: -0.7rem;
        inset-inline-end: .6rem;
        z-index: 1;
        width: 1.5rem;
        height: 1.5rem;
        border-radius: 9999px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        background: var(--rel-cousin);
    }
    .gt-crown svg { width: .85rem; height: .85rem; }

    /* Expand / collapse chip riding the card's bottom edge. `top: 100%` is the
       card's own bottom, so it follows any card height without JS. */
    .gt-toggle {
        position: absolute;
        top: 100%;
        left: 50%;
        transform: translate(-50%, -50%);
        /* "+3" is a sign and a count, not prose — the RTL card container was
           reordering it to "3+". */
        direction: ltr;
        min-width: 2rem;
        padding: .1rem .5rem;
        border-radius: 9999px;
        font-size: .68rem;
        font-weight: 800;
        line-height: 1.35;
        font-variant-numeric: tabular-nums;
        cursor: pointer;
        color: var(--sx-accent);
        background: var(--sx-surface);
        border: 1.5px solid var(--sx-line-strong);
        transition: background-color .2s var(--sx-ease), color .2s var(--sx-ease);
    }
    .gt-toggle:hover { background: var(--sx-accent); color: var(--sx-bg); }

    /* The ancestor chip rides the TOP edge — the direction its people lie in. */
    .gt-toggle--up {
        top: auto;
        bottom: 100%;
        transform: translate(-50%, 50%);
        color: var(--rel-sibling);
    }
    .gt-toggle--up:hover { background: var(--rel-sibling); color: var(--sx-bg); }

    .gt-controls {
        position: absolute;
        top: .9rem;
        inset-inline-end: .9rem;
        z-index: 10;
        display: flex;
        flex-direction: column;
        gap: .35rem;
    }
    .gt-ctrl {
        width: 2.75rem;
        height: 2.75rem;
        border-radius: .8rem;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--sx-ink-2);
        background: color-mix(in srgb, var(--sx-surface) 92%, transparent);
        -webkit-backdrop-filter: blur(8px);
        backdrop-filter: blur(8px);
        border: 1px solid var(--sx-line);
        box-shadow: var(--sx-shadow);
        transition: color .2s var(--sx-ease), background-color .2s var(--sx-ease);
    }
    .gt-ctrl:hover { color: var(--sx-accent); }
    .gt-ctrl:focus-visible { outline: 2px solid var(--sx-teal); outline-offset: 2px; }
    .gt-ctrl--close { color: var(--sx-danger); }

    /* ── Shared entrance ── */
    @keyframes genealogy-fade-in-up {
        from { opacity: 0; transform: translateY(10px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .card-enter {
        animation: genealogy-fade-in-up .4s var(--sx-ease) both;
        animation-delay: var(--enter-delay, 0ms);
    }

    @media (prefers-reduced-motion: reduce) {
        .card-enter { animation: none !important; }
        .gt-card, .gt-toggle, .gt-ctrl, .gs-card, .gl-card, .gs-caret { transition: none !important; }
        .gt-card:hover, .gl-card:hover, .gs-card:hover { transform: none !important; }
    }
</style>
@endpush

@section('content')
@php
    $peopleJson = json_encode($people, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS);
    $graphJson = json_encode($graph, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS);
    $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true);

    // One map instead of positional id arrays. The filter pills are built from
    // this: a group with nobody in it never renders a pill, so the list cannot
    // offer a filter that returns an empty grid.
    $groupIdsJson = json_encode([
        'lineage' => $lineageIds ?? [],
        'wives' => $spouseIds ?? [],
        'children' => $childrenIds ?? [],
        'grandchildren' => $grandchildrenIds ?? [],
        'siblings' => $siblingIds ?? [],
        'uncles' => $uncleAuntIds ?? [],
        'cousins' => $cousinIds ?? [],
    ], JSON_UNESCAPED_UNICODE);

    $hasGraph = $graph !== null && ($hasLineage || $hasTree);
    $hasSpine = ($spine ?? []) !== [];
    $oldestAncestor = ($lineage ?? []) !== [] ? ($lineage[0]['name'] ?? null) : null;

    // Section headings for the directory, in the same reading order the
    // controller sorted the people into.
    $groupLabelsJson = json_encode([
        'ancestor' => __('genealogy.public.group_ancestor'),
        'self' => __('genealogy.public.group_self'),
        'wife' => __('genealogy.public.group_wife'),
        'child' => __('genealogy.public.group_child'),
        'grandchild' => __('genealogy.public.group_grandchild'),
        'sibling' => __('genealogy.public.group_sibling'),
        'uncle' => __('genealogy.public.group_uncle'),
        'cousin' => __('genealogy.public.group_cousin'),
        'relative' => __('genealogy.public.group_relative'),
    ], JSON_UNESCAPED_UNICODE);
@endphp

<div
    class="genealogy sx min-h-[100dvh] pb-20"
    x-data="genealogyPage({{ $peopleJson }}, {{ $groupIdsJson }}, {{ $groupLabelsJson }})"
    x-init="init()">

    {{-- ═══════════════════ HERO ═══════════════════
         Deliberately short. The previous hero ran 1,300px before the tree
         began — a badge, a four-line headline, a paragraph, two stacked
         full-width buttons and a stat panel, all above the thing the page is
         for. There are no buttons here on purpose either: the sticky switcher
         below is the single place the view changes, and the hero spends its
         space on the one thing the switcher cannot say — what the chain is.

         The ledger figures are server-rendered counts, so they read the same
         without JavaScript. --}}
    <div class="sx-wrap">
        <x-public.editorial.page-hero
            :eyebrow-url="url('/')" :eyebrow-label="__('site.home')" eyebrow-icon="home"
            :title="__('genealogy.public.hero_title').' '.__('genealogy.public.hero_subtitle')"
            :lead="__('genealogy.public.hero_lead')"
            :stats="[
                ['icon' => 'network', 'label' => __('genealogy.public.stat_generations'), 'value' => number_format((int) $stats['generations'])],
                ['icon' => 'users', 'label' => __('genealogy.public.stat_people'), 'value' => number_format((int) $stats['people'])],
                (int) $stats['children'] > 0
                    ? ['icon' => 'baby', 'label' => __('genealogy.public.stat_children'), 'value' => number_format((int) $stats['children'])]
                    : ['icon' => 'milestone', 'label' => __('genealogy.public.stat_oldest_ancestor'), 'value' => $oldestAncestor ?? '—', 'small' => true],
            ]">

            @if ($oldestAncestor && $hasSpine)
                <p class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm font-bold text-[color:var(--sx-muted)]">
                    <span class="sx-clamp-1 max-w-[14rem] text-[color:var(--sx-ink)]" title="{{ $oldestAncestor }}">{{ $oldestAncestor }}</span>
                    <span class="text-[color:var(--sx-accent)]" aria-hidden="true">←</span>
                    <span class="sx-chip tabular-nums">{{ (int) $stats['generations'] }}</span>
                    <span class="text-[color:var(--sx-accent)]" aria-hidden="true">←</span>
                    <span class="sx-clamp-1 max-w-[14rem] text-[color:var(--sx-ink)]" title="{{ $root?->name(app()->getLocale()) }}">{{ $root?->name(app()->getLocale()) }}</span>
                </p>
            @endif
        </x-public.editorial.page-hero>
    </div>

    <div class="relative">

    {{-- ═══════════════════ CONTROLS (sticky) ═══════════════════
         `top-20`, matching the h-20 site header. It was `top-16`, which parked
         the bar four pixels under the header and let it slide beneath. --}}
    <section class="sticky top-20 z-30 border-y border-[color:var(--sx-line)]"
             style="background: color-mix(in srgb, var(--sx-bg) 88%, transparent); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);">
        <div class="sx-wrap py-2">
            <div class="flex items-center justify-between gap-2">

                @if ($hasGraph || $hasSpine)
                    {{-- Labelled, not icon-only: two unlabelled glyphs gave no
                         clue which was which. --}}
                    <div class="sx-segment" role="group" aria-label="{{ __('genealogy.public.view_tree') }}">
                        <button type="button" @click="showTree()" :aria-pressed="(viewMode === 'tree').toString()">
                            <i data-lucide="git-branch-plus" aria-hidden="true"></i>
                            <span>{{ __('genealogy.public.view_tree') }}</span>
                        </button>
                        <button type="button" @click="viewMode = 'list'" :aria-pressed="(viewMode === 'list').toString()">
                            <i data-lucide="list" aria-hidden="true"></i>
                            <span>{{ __('genealogy.public.view_list') }}</span>
                        </button>
                    </div>
                @endif

                <button type="button" @click="searchOpen = true; $nextTick(() => $refs.searchInput?.focus())"
                        class="sx-btn" title="{{ __('genealogy.public.search') }}" aria-label="{{ __('genealogy.public.search') }}">
                    <i data-lucide="search" aria-hidden="true"></i>
                    <span class="hidden sm:inline">{{ __('genealogy.public.search') }}</span>
                </button>
            </div>

            {{-- Filter pills get their own scrollable row instead of competing
                 with the switcher for one line at 375px. --}}
            <div x-show="viewMode === 'list'" x-cloak class="sx-scroller pt-2">
                <template x-for="pill in filters" :key="pill.value">
                    <button type="button" @click="activeFilter = pill.value"
                            class="sx-chip shrink-0" :class="activeFilter === pill.value && 'is-active'"
                            :aria-pressed="(activeFilter === pill.value).toString()"
                            x-text="pill.label"></button>
                </template>
            </div>
        </div>
    </section>

    {{-- ═══════════════════ MAIN ═══════════════════ --}}
    <main class="py-6 sm:py-10">

        {{-- ═════ TREE VIEW ═════
             Two renderings of one graph. Which one you get is decided by the
             viewport, not by a guess: `isWide` is a live matchMedia. --}}
        <div x-show="viewMode === 'tree'" @if($hasGraph || $hasSpine) x-cloak @endif x-ref="treeTop">

            {{-- The spine: phones and tablets, and any viewport if the canvas
                 chunk fails to load. --}}
            @if ($hasSpine)
                {{-- A narrow centred column, not the full container.
                     Most rows in a nasab carry a name and nothing else — only
                     42 of these 80 people have recorded dates — so a 900px row
                     is 700px of empty card. 42rem fits the longest name in the
                     corpus with its relation tag and stops there. --}}
                <div class="sx-wrap">
                    <div class="mx-auto w-full max-w-2xl">
                    <x-public.genealogy.spine-view
                        :spine="$spine ?? []"
                        :isRtl="$isRtl"
                        :lineageComplete="$lineageComplete ?? true" />

                    @if ($hasGraph)
                        <button type="button" @click="openCanvas()" class="sx-btn is-primary mt-5 w-full">
                            <i data-lucide="maximize-2" aria-hidden="true"></i>
                            <span>{{ __('genealogy.public.open_canvas') }}</span>
                        </button>

                        <p x-show="canvasError" x-cloak role="alert" class="sx-note mt-2 !text-[color:var(--sx-danger)]">
                            <i data-lucide="triangle-alert" class="w-4 h-4 shrink-0" aria-hidden="true"></i>
                            <span>{{ __('genealogy.public.canvas_failed') }}</span>
                        </p>
                    @endif
                    </div>
                </div>
            @endif

            @if ($hasGraph)
                {{-- The canvas. Inline on wide viewports; on a phone it exists
                     only as the fullscreen sheet, because a pannable canvas
                     inline would swallow the page scroll. --}}
                <div x-show="canvasFullscreen" x-cloak>

                    <div x-ref="treeStage" class="gt-stage gt-stage--full">
                        <div class="gt-controls" x-show="treeReady" x-transition.opacity>
                            <button type="button" class="gt-ctrl gt-ctrl--close"
                                    @click="closeCanvas()"
                                    title="{{ __('genealogy.public.close_canvas') }}"
                                    aria-label="{{ __('genealogy.public.close_canvas') }}">
                                <i data-lucide="x" class="w-5 h-5" aria-hidden="true"></i>
                            </button>
                            <button type="button" class="gt-ctrl" @click="treeApi?.zoomIn()"
                                    title="{{ __('genealogy.public.zoom_in') }}" aria-label="{{ __('genealogy.public.zoom_in') }}">
                                <i data-lucide="zoom-in" class="w-4 h-4" aria-hidden="true"></i>
                            </button>
                            <button type="button" class="gt-ctrl" @click="treeApi?.zoomOut()"
                                    title="{{ __('genealogy.public.zoom_out') }}" aria-label="{{ __('genealogy.public.zoom_out') }}">
                                <i data-lucide="zoom-out" class="w-4 h-4" aria-hidden="true"></i>
                            </button>
                            <button type="button" class="gt-ctrl" @click="treeApi?.centerFocus()"
                                    title="{{ __('genealogy.public.center_focus') }}" aria-label="{{ __('genealogy.public.center_focus') }}">
                                <i data-lucide="crosshair" class="w-4 h-4" aria-hidden="true"></i>
                            </button>
                            <button type="button" class="gt-ctrl" @click="treeApi?.fit()"
                                    title="{{ __('genealogy.public.fit_tree') }}" aria-label="{{ __('genealogy.public.fit_tree') }}">
                                <i data-lucide="maximize-2" class="w-4 h-4" aria-hidden="true"></i>
                            </button>
                            <button type="button" class="gt-ctrl hidden sm:flex" @click="treeApi?.expandAll()"
                                    title="{{ __('genealogy.public.expand_all') }}" aria-label="{{ __('genealogy.public.expand_all') }}">
                                <i data-lucide="chevrons-down" class="w-4 h-4" aria-hidden="true"></i>
                            </button>
                            <button type="button" class="gt-ctrl hidden sm:flex" @click="treeApi?.collapseAll()"
                                    title="{{ __('genealogy.public.collapse_all') }}" aria-label="{{ __('genealogy.public.collapse_all') }}">
                                <i data-lucide="chevrons-up" class="w-4 h-4" aria-hidden="true"></i>
                            </button>
                        </div>

                        <div x-show="!treeReady" class="absolute inset-0 z-[5] flex flex-col items-center justify-center gap-3">
                            <i data-lucide="loader-2" class="w-8 h-8 text-[color:var(--sx-accent)] animate-spin" aria-hidden="true"></i>
                            <span class="text-xs font-semibold text-[color:var(--sx-muted)]">
                                {{ __('genealogy.public.canvas_label') }}
                            </span>
                        </div>

                        {{-- The scene mounts here. --}}
                        <div x-ref="treeCanvas" class="absolute inset-0"></div>
                    </div>

                </div>
            @endif
        </div>

        {{-- ═════ LIST VIEW (also the fallback when there is no graph) ═════ --}}
        <div x-show="viewMode === 'list'" @if($hasGraph || $hasSpine) x-cloak @endif>
            <x-public.genealogy.directory-view
                :people="$people ?? []"
                :hasGraph="$hasGraph"
                :isRtl="$isRtl" />
        </div>
    </main>
    </div>

    {{-- ═══════════════════ PERSON DETAIL MODAL ═══════════════════ --}}
    <div x-show="selectedPerson" x-cloak
         x-transition.opacity
         @keydown.escape.window="selectedPerson = null"
         @click.self="selectedPerson = null"
         role="dialog" aria-modal="true"
         class="fixed inset-0 z-[70] flex items-end sm:items-center justify-center sm:p-4 bg-black/70 backdrop-blur-sm">
        {{-- A bottom sheet on a phone, a centred dialog above it: the close
             control and the first line of content both land in reach. --}}
        <div class="sx-surface w-full sm:max-w-2xl max-h-[88vh] overflow-y-auto !rounded-b-none sm:!rounded-b-3xl"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-6 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95">

            <div class="sticky top-0 z-10 flex items-center justify-between gap-3 border-b border-[color:var(--sx-line)] p-4 sm:p-6"
                 style="background: color-mix(in srgb, var(--sx-surface) 94%, transparent); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="sx-avatar" aria-hidden="true">
                        <i :data-lucide="selectedPerson?.isProphet ? 'crown' : 'user'"></i>
                    </span>
                    <div class="min-w-0">
                        <h3 class="truncate text-lg font-extrabold text-[color:var(--sx-ink)] sm:text-2xl"
                            :title="selectedPerson?.name" x-text="selectedPerson?.name"></h3>
                        {{-- The relation leads here too — it is the reason this
                             person is on this page. --}}
                        <p class="text-xs sm:text-sm font-bold truncate"
                           :class="`gl-rel--${selectedPerson?.relation}`"
                           x-text="selectedPerson?.relationLabel || selectedPerson?.title || ''"></p>
                    </div>
                </div>
                <button type="button" @click="selectedPerson = null" class="sx-btn shrink-0 !px-3"
                        title="{{ __('genealogy.public.close') }}" aria-label="{{ __('genealogy.public.close') }}">
                    <i data-lucide="x" class="w-5 h-5" aria-hidden="true"></i>
                </button>
            </div>

            <div class="p-4 sm:p-6 space-y-5">
                <div class="flex flex-wrap items-center gap-2" x-show="hasDetails(selectedPerson)">
                    <template x-if="selectedPerson?.birthDateHijri">
                        <span class="sx-chip"><i data-lucide="calendar-plus" aria-hidden="true"></i>
                            <span class="tabular-nums" x-text="'{{ __('genealogy.public.born') }} ' + selectedPerson?.birthDateHijri"></span>
                        </span>
                    </template>
                    <template x-if="selectedPerson?.deathDateHijri">
                        <span class="sx-chip"><i data-lucide="calendar-minus" aria-hidden="true"></i>
                            <span class="tabular-nums" x-text="'{{ __('genealogy.public.died') }} ' + selectedPerson?.deathDateHijri"></span>
                        </span>
                    </template>
                    <template x-if="selectedPerson?.title">
                        <span class="sx-chip is-active"><i data-lucide="scroll-text" aria-hidden="true"></i>
                            <span class="sx-clamp-1 max-w-[14rem]" :title="selectedPerson?.title" x-text="selectedPerson?.title"></span>
                        </span>
                    </template>
                </div>

                <div>
                    <h4 class="mb-2 flex items-center gap-2 text-base font-extrabold text-[color:var(--sx-ink)]">
                        <i data-lucide="info" class="w-4 h-4 text-[color:var(--sx-accent)]" aria-hidden="true"></i>
                        {{ __('genealogy.public.historical_note') }}
                    </h4>
                    <p class="whitespace-pre-line text-sm leading-relaxed text-[color:var(--sx-ink-2)]"
                       x-text="(selectedPerson?.biography || selectedPerson?.notes) || '{{ __('genealogy.public.no_biography') }}'"></p>
                </div>

                {{-- Marriages. Chips navigate: tapping علي on فاطمة's card
                     opens علي. --}}
                <template x-if="selectedPerson?.spouses?.length">
                    <div>
                        <h4 class="mb-2.5 flex items-center gap-2 text-sm font-extrabold text-[color:var(--sx-ink)]">
                            <i data-lucide="heart" class="w-4 h-4" style="color: var(--rel-wife)" aria-hidden="true"></i>
                            {{ __('genealogy.public.marriages') }}
                            <span class="tabular-nums text-xs font-bold text-[color:var(--sx-muted)]" x-text="`(${selectedPerson.spouses.length})`"></span>
                        </h4>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="spouse in selectedPerson.spouses" :key="'sp-' + spouse.id">
                                {{-- The spine's marriage chip, reused: same dashed
                                     edge, same plate, same relation colour. --}}
                                <button type="button" @click="openPerson(spouse.id)" class="gs-chip gs-chip--spouse !w-auto max-w-full"
                                        :title="spouse.name">
                                    <span class="gs-chip-ava" aria-hidden="true" x-text="personInitial(spouse)"></span>
                                    <span class="gs-chip-name" x-text="spouse.name"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>

                <div class="flex flex-col gap-2">
                    <template x-if="selectedPerson?.hasProfile">
                        <a :href="sahabaProfileUrl(selectedPerson)" class="sx-btn is-primary w-full">
                            <i data-lucide="book-open" aria-hidden="true"></i>
                            <span class="truncate" x-text="'{{ __('genealogy.public.know_more_about') }} ' + selectedPerson?.name"></span>
                        </a>
                    </template>

                    {{-- Only offered when the canvas is actually mounted and
                         holds this person; otherwise it is a dead button. --}}
                    <template x-if="canvasHas(selectedPerson)">
                        <button type="button" @click="revealInCanvas(selectedPerson)" class="sx-btn w-full">
                            <i data-lucide="crosshair" aria-hidden="true"></i>
                            <span>{{ __('genealogy.public.open_canvas') }}</span>
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════ SEARCH MODAL ═══════════════════ --}}
    <div x-show="searchOpen" x-cloak
         x-transition.opacity
         @keydown.escape.window="searchOpen = false"
         @click.self="searchOpen = false"
         role="dialog" aria-modal="true"
         class="fixed inset-0 z-[70] flex items-start justify-center pt-16 sm:pt-20 px-3 sm:px-4 bg-black/70 backdrop-blur-sm">
        <div class="sx-surface w-full max-w-2xl overflow-hidden"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-3"
             x-transition:enter-end="opacity-100 translate-y-0">
            <div class="p-4 sm:p-6">
                <div class="relative">
                    {{-- text-base, not text-sm: iOS zooms the whole page when a
                         focused input is under 16px. --}}
                    {{-- text-base, not text-sm: iOS zooms the whole page when a
                         focused input is under 16px (.sx-input is 1rem). --}}
                    <input type="search" x-ref="searchInput" x-model="searchQuery"
                           @input="viewMode = 'list'"
                           enterkeyhint="search"
                           placeholder="{{ __('genealogy.public.search_placeholder') }}"
                           aria-label="{{ __('genealogy.public.search_title') }}"
                           class="sx-input ps-12 pe-12" />
                    <i x-show="!searchLoading" data-lucide="search" class="pointer-events-none absolute start-4 top-1/2 h-5 w-5 -translate-y-1/2 text-[color:var(--sx-muted)]" aria-hidden="true"></i>
                    <i x-show="searchLoading" data-lucide="loader-2" class="absolute start-4 top-1/2 h-5 w-5 -translate-y-1/2 animate-spin text-[color:var(--sx-accent)]" aria-hidden="true"></i>
                    <button type="button" @click="searchOpen = false"
                            class="absolute end-1.5 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-lg text-[color:var(--sx-muted)] hover:bg-[color:var(--sx-surface-2)] hover:text-[color:var(--sx-ink)]"
                            title="{{ __('genealogy.public.close') }}" aria-label="{{ __('genealogy.public.close') }}">
                        <i data-lucide="x" class="w-5 h-5" aria-hidden="true"></i>
                    </button>
                </div>
                <p class="mt-3 text-xs text-[color:var(--sx-muted)] sm:text-sm">{{ __('genealogy.public.search_hint') }}</p>
            </div>

            <div class="max-h-[50vh] overflow-y-auto border-t border-[color:var(--sx-line)]">
                <template x-for="(person, idx) in searchResults" :key="'s-' + person.id">
                    <button type="button" @click="searchOpen = false; openPerson(person.id)"
                            class="card-enter flex min-h-[3.25rem] w-full items-center gap-3 px-4 py-3 text-start transition-colors hover:bg-[color:var(--sx-surface-2)] sm:px-6"
                            :style="`--enter-delay: ${Math.min(idx, 8) * 30}ms`" :title="personName(person)">
                        <i :data-lucide="person.isProphet ? 'crown' : 'user'" class="h-5 w-5 shrink-0 text-[color:var(--sx-muted)]" aria-hidden="true"></i>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate font-semibold text-[color:var(--sx-ink)]" x-html="highlightMatch(person.name)"></span>
                            <span class="block text-xs font-bold truncate"
                                  :class="`gl-rel--${person.relation}`"
                                  x-text="person.relationLabel || person.title || ''"></span>
                        </span>
                    </button>
                </template>
                <div x-show="searchQuery.length > 0 && !searchLoading && searchResults.length === 0"
                     class="px-6 py-8 text-center text-sm text-[color:var(--sx-muted)]">
                    {{ __('site.no_results') }}
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function genealogyPage(people, groupIds, groupLabels) {
        // { lineage: Set, wives: Set, children: Set, … } — one lookup table the
        // filter pills read from.
        const groups = Object.fromEntries(
            Object.entries(groupIds || {}).map(([key, ids]) => [key, new Set(ids || [])])
        );

        return {
            people: people || [],
            groups,
            groupLabels: groupLabels || {},

            // Reading order for the directory's sections — the same order the
            // server sorted `people` into, so a section never reorders people
            // inside itself.
            groupOrder: ['ancestor', 'self', 'wife', 'child', 'grandchild', 'sibling', 'uncle', 'cousin', 'relative'],

            // ── Canvas ──────────────────────────────────────────────────────
            treeApi: null,
            treeReady: false,
            treeMounted: false,
            // Live, not sampled once: rotating a phone or dragging a desktop
            // window across the breakpoint has to switch the rendering.
            isWide: false,
            canvasFullscreen: false,
            canvasError: false,

            hasGraph: @json($hasGraph),
            hasSpine: @json($hasSpine),

            viewMode: @json($hasGraph || $hasSpine) ? 'tree' : 'list',
            activeFilter: 'all',
            searchQuery: '',
            searchOpen: false,
            searchLoading: false,
            selectedPerson: null,
            seerahRoute: '{{ route('public.seerah') }}',
            sahabaBaseUrl: '{{ url('/sahaba') }}',
            peopleEndpoint: '{{ route('public.genealogy.people') }}',

            searchDebounceTimer: null,
            lastFetchedQuery: '',
            mediaQuery: null,

            displayStats: { people: 0, children: 0, generations: 0 },

            // A pill is offered only where the group has members, so no filter
            // can lead to an empty grid.
            filters: [
                { value: 'all',           label: '{{ __('genealogy.public.filter_all') }}' },
                { value: 'lineage',       label: '{{ __('genealogy.public.filter_lineage') }}' },
                { value: 'wives',         label: '{{ __('genealogy.public.filter_wives') }}' },
                { value: 'children',      label: '{{ __('genealogy.public.filter_children') }}' },
                { value: 'grandchildren', label: '{{ __('genealogy.public.filter_grandchildren') }}' },
                { value: 'siblings',      label: '{{ __('genealogy.public.kin_siblings') }}' },
                { value: 'uncles',        label: '{{ __('genealogy.public.kin_uncles_aunts') }}' },
                { value: 'cousins',       label: '{{ __('genealogy.public.kin_cousins') }}' },
            ].filter((pill) => pill.value === 'all' || (groups[pill.value]?.size ?? 0) > 0),

            /**
             * The canvas is NEVER the inline rendering — at any width.
             *
             * It was, on `lg` and up, and it was just as unreadable there as on
             * a phone: a 22-generation chain with an 11-wide fan of marriages
             * does not fit a 1512×513 stage at any legible scale, so a desktop
             * visitor saw one card, a row of wives clipped at both edges, and
             * no chain at all. The shape of a nasab is a column, not a plane;
             * the spine renders it correctly at every width and the canvas is
             * now strictly opt-in for people who want free pan and zoom.
             */
            get canvasActive() {
                return this.canvasFullscreen;
            },

            init() {
                const icons = () => this.$nextTick(() => window.lucide?.createIcons());

                // Watched rather than read once — see `isWide`.
                this.mediaQuery = window.matchMedia('(min-width: 1024px)');
                this.isWide = this.mediaQuery.matches;
                this.mediaQuery.addEventListener('change', (e) => {
                    this.isWide = e.matches;
                    icons();
                });

                icons();
                this.$watch('viewMode', () => { if (this.canvasActive) this.ensureTree(); icons(); });
                this.$watch('canvasFullscreen', () => { if (this.canvasActive) this.ensureTree(); icons(); });
                this.$watch('treeReady', icons);
                this.$watch('selectedPerson', icons);
                this.$watch('activeFilter', icons);
                this.$watch('searchQuery', () => { icons(); this.debouncedSearch(); });

                // Nothing mounts the canvas on load any more: openCanvas() is
                // the only entry point, so d3 is fetched when — and only when —
                // a visitor asks for the interactive tree.
            },

            async ensureTree() {
                if (this.treeMounted || ! this.$refs.treeCanvas) return;
                this.treeMounted = true;
                try {
                    // Resolved by the bundler (see window.loadGenealogyTree in
                    // resources/js/app.js) rather than by a URL built here.
                    const mountGenealogyTree = await window.loadGenealogyTree();
                    this.treeApi = mountGenealogyTree(
                        this.$refs.treeCanvas,
                        {!! $graphJson ?: 'null' !!},
                        {
                            focusId: {{ (int) ($focusId ?? 0) }},
                            rtl: @json($isRtl),
                            labels: {
                                canvasLabel: '{{ __('genealogy.public.canvas_label') }}',
                                expand: '{{ __('genealogy.public.expand_branch') }}',
                                collapse: '{{ __('genealogy.public.collapse_branch') }}',
                                hideAncestors: '{{ __('genealogy.public.hide_ancestors') }}',
                                showAncestors: '{{ __('genealogy.public.show_ancestors') }}',
                            },
                            onSelect: (person) => this.openPerson(person.id),
                        },
                    );
                    this.treeReady = true;
                } catch (e) {
                    // A failed chunk fetch is survivable now: the spine is
                    // already in the page. But it must SAY so — closing the
                    // sheet and resetting the flag left the button looking
                    // simply dead.
                    this.treeMounted = false;
                    this.canvasFullscreen = false;
                    this.canvasError = true;
                    document.body.style.overflow = '';
                    if (! this.hasSpine) this.viewMode = 'list';
                }
            },

            openCanvas() {
                this.canvasFullscreen = true;
                // The sheet covers the page; letting what is underneath scroll
                // behind it is the classic scroll-bleed.
                document.body.style.overflow = 'hidden';
                this.ensureTree();
                this.$nextTick(() => this.treeApi?.centerFocus());
            },

            closeCanvas() {
                this.canvasFullscreen = false;
                document.body.style.overflow = '';
            },

            showTree() {
                this.viewMode = 'tree';
                this.$nextTick(() => {
                    this.$refs.treeTop?.scrollIntoView({
                        block: 'start',
                        behavior: this.prefersReducedMotion() ? 'auto' : 'smooth',
                    });
                });
            },

            canvasHas(person) {
                return !! (person && this.treeApi && this.hasGraph);
            },

            revealInCanvas(person) {
                const id = person?.id;
                this.selectedPerson = null;
                this.viewMode = 'tree';
                this.openCanvas();
                this.$nextTick(() => this.treeApi?.focusPerson(id));
            },

            // ── People ──────────────────────────────────────────────────────
            personName(person) {
                return (person?.name || '').trim() || '{{ __('genealogy.public.unnamed') }}';
            },

            // Calligraphic monogram — leading punctuation is skipped so
            // transliterations like "'Umar" don't render as an apostrophe.
            personInitial(person) {
                if (person?.monogram) return person.monogram;
                const name = this.personName(person);
                return (name.replace(/^[^\p{L}\p{N}]+/u, '') || name).charAt(0).toUpperCase() || '۞';
            },

            // Already era-formatted server-side ("53 ق.هـ" / "11 هـ").
            personDeathYear(person) {
                return person?.deathDateHijri || '';
            },

            prefersReducedMotion() {
                return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            },

            countUp(key, target) {
                if (this.prefersReducedMotion() || ! target) { this.displayStats[key] = target; return; }
                const durationMs = 900;
                const start = performance.now();
                const step = (now) => {
                    const progress = Math.min((now - start) / durationMs, 1);
                    const eased = 1 - Math.pow(1 - progress, 3); // ease-out cubic
                    this.displayStats[key] = Math.round(target * eased);
                    if (progress < 1) requestAnimationFrame(step);
                };
                requestAnimationFrame(step);
            },

            escapeHtml(str) {
                return (str || '').replace(/[&<>"']/g, (c) => ({
                    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
                }[c]));
            },

            highlightMatch(text) {
                const safeText = this.escapeHtml(text);
                const q = this.searchQuery.trim();
                if (! q) return safeText;
                const safeQuery = this.escapeHtml(q).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                return safeText.replace(new RegExp('(' + safeQuery + ')', 'ig'), '<mark class="bg-accent/30 text-inherit rounded-sm">$1</mark>');
            },

            // Membership comes from the server's own grouping — marriages from
            // the relationship rows, everything else derived from the descent
            // edges — never from a gender or name guess.
            matchesFilter(person) {
                if (this.activeFilter === 'all') return true;
                return this.groups[this.activeFilter]?.has(person.id) ?? true;
            },

            get filteredPeople() {
                const q = this.searchQuery.trim().toLowerCase();
                return this.people.filter((p) => {
                    if (! this.matchesFilter(p)) return false;
                    if (! q) return true;
                    return (p.name || '').toLowerCase().includes(q)
                        || (p.title || '').toLowerCase().includes(q)
                        || (p.relationLabel || '').toLowerCase().includes(q);
                });
            },

            /**
             * The directory, sectioned by kinship.
             *
             * `people` arrives from the server already in reading order, and
             * bucketing preserves it, so each section is internally ordered
             * (the nasab oldest-first, the children in their curated order)
             * without a second sort here.
             */
            get groupedPeople() {
                const buckets = new Map();

                for (const person of this.filteredPeople) {
                    // A mother in the nasab reads under the lineage she belongs
                    // to rather than as a section of her own.
                    const key = person.relation === 'ancestor_mother'
                        ? 'ancestor'
                        : (person.relation || 'relative');

                    if (! buckets.has(key)) buckets.set(key, []);
                    buckets.get(key).push(person);
                }

                return this.groupOrder
                    .filter((key) => buckets.has(key))
                    .map((key) => ({
                        key,
                        label: this.groupLabels[key] ?? key,
                        people: buckets.get(key),
                    }));
            },

            get searchResults() {
                const q = this.searchQuery.trim().toLowerCase();
                if (! q) return this.people.slice(0, 8);
                return this.people.filter((p) =>
                    (p.name || '').toLowerCase().includes(q) || (p.title || '').toLowerCase().includes(q)
                ).slice(0, 12);
            },

            get seerahUrl() {
                return this.seerahRoute;
            },

            sahabaProfileUrl(person) {
                return person?.slug ? `${this.sahabaBaseUrl}/${person.slug}` : '#';
            },

            hasDetails(person) {
                return !! (person && (person.birthDateHijri || person.deathDateHijri || person.title));
            },

            openPerson(id) {
                this.selectedPerson = this.people.find((p) => p.id === id) || null;
            },

            mergePeople(freshPeople) {
                const known = new Set(this.people.map((p) => p.id));
                const additions = (freshPeople || []).filter((p) => ! known.has(p.id));
                if (additions.length) this.people = this.people.concat(additions);
            },

            debouncedSearch() {
                clearTimeout(this.searchDebounceTimer);
                const q = this.searchQuery.trim();
                if (q.length < 2 || q === this.lastFetchedQuery) return;
                this.searchDebounceTimer = setTimeout(() => this.runServerSearch(q), 300);
            },

            // The embedded set is this page's own family. Searching reaches
            // past it into the wider corpus and merges whatever comes back.
            async runServerSearch(q) {
                this.searchLoading = true;
                try {
                    const url = new URL(this.peopleEndpoint, window.location.origin);
                    url.searchParams.set('q', q);
                    const response = await fetch(url, { headers: { Accept: 'application/json' } });
                    if (! response.ok) throw new Error('genealogy people fetch failed');
                    const result = await response.json();
                    // Someone found outside this family has no relation TO this
                    // family; labelling them anyway would be a claim the server
                    // never made.
                    this.mergePeople((result.data || []).map((p) => ({
                        ...p,
                        relation: 'relative',
                        relationLabel: p.title || '',
                    })));
                    this.lastFetchedQuery = q;
                } catch (e) {
                    // Client-side filtering of what's already loaded still works.
                } finally {
                    this.searchLoading = false;
                }
            },
        };
    }
</script>
@endpush
@endsection
