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
        --rel-ancestor: #0056b3;
        --rel-ancestor_mother: #be185d;
        --rel-self: #218838;
        --rel-wife: #be185d;
        --rel-child: #0f766e;
        --rel-grandchild: #0e7490;
        --rel-sibling: #6d28d9;
        --rel-uncle: #6d28d9;
        --rel-cousin: #b45309;
        --rel-relative: #475569;
    }
    .dark .genealogy {
        --rel-ancestor: #7cb2f0;
        --rel-ancestor_mother: #f9a8d4;
        --rel-self: #64CCC5;
        --rel-wife: #f9a8d4;
        --rel-child: #5eead4;
        --rel-grandchild: #67e8f9;
        --rel-sibling: #c4b5fd;
        --rel-uncle: #c4b5fd;
        --rel-cousin: #fcd34d;
        --rel-relative: #cbd5e1;
    }

    /* ══════════════════════════════════════════════════════════════════════
       THE NASAB SPINE  (.gs-*)  — the default reading on phones and tablets.
       Server-rendered; no canvas, no gestures, no JavaScript required to read.
       ══════════════════════════════════════════════════════════════════════ */
    .gs { position: relative; }

    .gs-rail {
        position: relative;
        margin: 0;
        padding: 0 0 0.5rem;
        list-style: none;
    }
    /* The vertical rail the generations hang from. */
    .gs-rail::before {
        content: '';
        position: absolute;
        inset-block: 0.75rem;
        inset-inline-start: 1.09rem;
        width: 2px;
        border-radius: 2px;
        background: linear-gradient(180deg,
            rgba(0, 86, 179, .12) 0%,
            rgba(0, 86, 179, .38) 35%,
            rgba(33, 136, 56, .5) 100%);
    }
    .dark .gs-rail::before {
        background: linear-gradient(180deg,
            rgba(100, 204, 197, .12) 0%,
            rgba(100, 204, 197, .35) 35%,
            rgba(100, 204, 197, .55) 100%);
    }

    .gs-row {
        position: relative;
        display: flex;
        align-items: flex-start;
        gap: .75rem;
        padding-block: .3rem;
    }

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
        color: #0056b3;
        background: #fff;
        border: 2px solid rgba(0, 86, 179, .3);
        box-shadow: 0 2px 8px -3px rgba(0, 86, 179, .45);
    }
    .dark .gs-marker {
        color: #64CCC5;
        background: #16213e;
        border-color: rgba(100, 204, 197, .35);
    }
    /* `.dark .gs-marker` is two classes and would otherwise outrank this one,
       which is how the focus marker and card lost their gradient in dark mode
       while looking correct in light. Every `--focus` / `--spouse` modifier
       below carries its dark twin for the same reason. */
    .gs-marker--focus,
    .dark .gs-marker--focus {
        color: #fff;
        border-color: transparent;
        background: linear-gradient(135deg, #0056b3, #218838);
        box-shadow: 0 6px 18px -6px rgba(0, 86, 179, .8);
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
        background: #fff;
        border: 1px solid rgba(0, 0, 0, .07);
        box-shadow: 0 4px 14px -10px rgba(0, 0, 0, .4);
        transition: border-color .2s ease, box-shadow .2s ease, background-color .2s ease;
    }
    .gs-card:hover { border-color: rgba(0, 86, 179, .4); box-shadow: 0 10px 24px -14px rgba(0, 86, 179, .6); }
    .gs-card:active { background: #f6f9fd; }
    .gs-card:focus-visible { outline: 2px solid #0056b3; outline-offset: 2px; }
    .dark .gs-card { background: #16213e; border-color: rgba(255, 255, 255, .09); }
    .dark .gs-card:hover { border-color: rgba(100, 204, 197, .45); }
    .dark .gs-card:active { background: #1d2743; }

    .gs-card--focus,
    .dark .gs-card--focus {
        padding: .9rem 1rem;
        border-color: transparent;
        background: linear-gradient(135deg, #0056b3 0%, #1a7a6d 55%, #218838 100%);
        box-shadow: 0 18px 40px -20px rgba(0, 86, 179, .8);
    }
    .gs-card--focus:active,
    .dark .gs-card--focus:active { background: linear-gradient(135deg, #004c9e 0%, #166b60 55%, #1c7430 100%); }
    .gs-card--focus .gs-name { color: #fff; font-size: 1.15rem; }
    .gs-card--focus .gs-go { color: rgba(255, 255, 255, .8); }

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
        background: linear-gradient(135deg, #0056b3, #3b82f6);
        box-shadow: inset 0 0 0 2px rgba(255, 255, 255, .3);
        user-select: none;
    }
    .gs-ava--focus {
        width: 3.1rem;
        height: 3.1rem;
        font-size: 1.4rem;
        background: rgba(255, 255, 255, .2);
        box-shadow: inset 0 0 0 2px rgba(255, 255, 255, .45);
    }

    .gs-meta { min-width: 0; flex: 1; display: flex; flex-direction: column; gap: .3rem; }
    .gs-name {
        font-weight: 700;
        font-size: .95rem;
        line-height: 1.3;
        color: #111827;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .dark .gs-name { color: #fff; }

    .gs-tags { display: flex; flex-wrap: wrap; gap: .3rem; }
    .gs-tag {
        padding: .1rem .5rem;
        border-radius: 9999px;
        font-size: .65rem;
        font-weight: 700;
        color: var(--rel-ancestor);
        background: rgba(0, 86, 179, .09);
    }
    .dark .gs-tag { background: rgba(100, 204, 197, .12); }
    .gs-tag--soft {
        color: #64748b;
        background: rgba(100, 116, 139, .1);
        max-width: 14rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .dark .gs-tag--soft { color: #cbd5e1; background: rgba(203, 213, 225, .1); }
    .gs-tag--root { color: #b45309; background: rgba(180, 83, 9, .1); }
    .dark .gs-tag--root { color: #fcd34d; background: rgba(252, 211, 77, .12); }
    .gs-tag--focus,
    .dark .gs-tag--focus { color: #fff; background: rgba(255, 255, 255, .22); }

    .gs-go { width: 1.1rem; height: 1.1rem; flex-shrink: 0; color: #9ca3af; }

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
        color: #6b7280;
        white-space: nowrap;
    }
    .dark .gs-life { color: #9ca3af; }
    .gs-card--focus .gs-life, .dark .gs-card--focus .gs-life { color: rgba(255, 255, 255, .85); }
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
        transition: border-color .2s ease, background-color .2s ease;
    }
    .gs-mother {
        color: var(--rel-wife);
        background: rgba(190, 24, 93, .05);
        border: 1px dashed rgba(190, 24, 93, .3);
    }
    .gs-mother:hover { border-color: rgba(190, 24, 93, .6); }
    .dark .gs-mother { background: rgba(249, 168, 212, .07); border-color: rgba(249, 168, 212, .28); }
    .gs-mother-name { font-weight: 800; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .gs-mother-rel { opacity: .75; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

    .gs-branches { margin-top: .35rem; }
    .gs-branch-toggle {
        color: #334155;
        background: rgba(100, 116, 139, .07);
        border: 1px solid rgba(100, 116, 139, .18);
    }
    .gs-branch-toggle:hover { border-color: rgba(0, 86, 179, .45); }
    .dark .gs-branch-toggle { color: #e2e8f0; background: rgba(226, 232, 240, .06); border-color: rgba(226, 232, 240, .14); }
    .gs-caret { margin-inline-start: auto; transition: transform .2s ease; }
    .gs-caret--open { transform: rotate(180deg); }

    .gs-branch-list {
        display: flex;
        flex-direction: column;
        gap: .4rem;
        margin-top: .45rem;
        padding-inline-start: .6rem;
        border-inline-start: 2px dashed rgba(0, 86, 179, .2);
    }
    .dark .gs-branch-list { border-inline-start-color: rgba(100, 204, 197, .22); }

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
        background: #fff;
        border: 1px solid rgba(0, 0, 0, .06);
        transition: border-color .2s ease;
    }
    .gs-chip:hover { border-color: rgba(0, 86, 179, .45); }
    .dark .gs-chip { background: #1d2743; border-color: rgba(255, 255, 255, .08); }
    .gs-chip--spouse { border-style: dashed; border-color: rgba(190, 24, 93, .35); }
    .dark .gs-chip--spouse { border-color: rgba(244, 114, 182, .4); }

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
        background: linear-gradient(135deg, #218838, #64CCC5);
    }
    .gs-chip--spouse .gs-chip-ava { background: linear-gradient(135deg, #be185d, #e91e63); }
    .gs-chip-text { min-width: 0; display: flex; flex-direction: column; }
    .gs-chip-name {
        font-size: .8rem;
        font-weight: 700;
        line-height: 1.3;
        color: #111827;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .dark .gs-chip-name { color: #fff; }
    .gs-chip-sub {
        font-size: .65rem;
        font-weight: 600;
        color: #0f766e;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .dark .gs-chip-sub { color: #64CCC5; }

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
        color: #334155;
        background: rgba(100, 116, 139, .1);
        transition: background-color .2s ease, color .2s ease;
    }
    .gs-kid:hover { color: #0056b3; background: rgba(0, 86, 179, .12); }
    .dark .gs-kid { color: #cbd5e1; background: rgba(203, 213, 225, .1); }
    .dark .gs-kid:hover { color: #64CCC5; background: rgba(100, 204, 197, .15); }

    .gs-fold { display: flex; padding: .35rem 0 .35rem 0; }
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
        color: #0056b3;
        background: #fff;
        border: 1px dashed rgba(0, 86, 179, .4);
        transition: background-color .2s ease, color .2s ease;
    }
    .gs-fold-btn:hover { color: #fff; background: #0056b3; }
    .dark .gs-fold-btn { color: #64CCC5; background: #16213e; border-color: rgba(100, 204, 197, .4); }
    .dark .gs-fold-btn:hover { color: #0f3460; background: #64CCC5; }

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

        .gs-card--focus, .dark .gs-card--focus { padding: 1.25rem 1.25rem; }
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

    .gs-note {
        display: flex;
        align-items: flex-start;
        gap: .5rem;
        margin-top: 1rem;
        padding: .7rem .9rem;
        border-radius: .75rem;
        font-size: .75rem;
        color: #92400e;
        background: rgba(245, 158, 11, .1);
        border: 1px solid rgba(245, 158, 11, .25);
    }
    .dark .gs-note { color: #fcd34d; }

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
        color: #111827;
    }
    .dark .gl-heading { color: #fff; }
    .gl-heading-dot { width: .55rem; height: .55rem; border-radius: 9999px; background: currentColor; }
    .gl-heading-count {
        padding: .05rem .5rem;
        border-radius: 9999px;
        font-size: .7rem;
        font-weight: 700;
        color: #64748b;
        background: rgba(100, 116, 139, .12);
    }
    .dark .gl-heading-count { color: #cbd5e1; background: rgba(203, 213, 225, .12); }

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
        background: #fff;
        border: 1px solid rgba(0, 0, 0, .07);
        box-shadow: 0 4px 14px -10px rgba(0, 0, 0, .35);
        transition: transform .2s cubic-bezier(.22, 1, .36, 1), border-color .2s ease, box-shadow .2s ease;
    }
    .gl-card:hover {
        transform: translateY(-2px);
        border-color: rgba(0, 86, 179, .4);
        box-shadow: 0 14px 28px -16px rgba(0, 86, 179, .55);
    }
    .gl-card:focus-visible { outline: 2px solid #0056b3; outline-offset: 2px; }
    .dark .gl-card { background: #16213e; border-color: rgba(255, 255, 255, .09); }
    .dark .gl-card:hover { border-color: rgba(100, 204, 197, .45); }

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
        background: var(--rel-relative);
        box-shadow: inset 0 0 0 2px rgba(255, 255, 255, .28);
        user-select: none;
    }
    .gl-ava--ancestor        { background: linear-gradient(135deg, #0056b3, #3b82f6); }
    .gl-ava--ancestor_mother,
    .gl-ava--wife            { background: linear-gradient(135deg, #be185d, #ec4899); }
    .gl-ava--self            { background: linear-gradient(135deg, #0056b3, #218838); }
    .gl-ava--child           { background: linear-gradient(135deg, #0f766e, #14b8a6); }
    .gl-ava--grandchild      { background: linear-gradient(135deg, #0e7490, #22d3ee); }
    .gl-ava--sibling,
    .gl-ava--uncle           { background: linear-gradient(135deg, #6d28d9, #a78bfa); }
    .gl-ava--cousin          { background: linear-gradient(135deg, #b45309, #f59e0b); }
    .gl-ava--relative        { background: linear-gradient(135deg, #475569, #94a3b8); }

    .gl-meta { min-width: 0; flex: 1; display: flex; flex-direction: column; gap: .3rem; }
    .gl-name {
        font-weight: 700;
        font-size: .9rem;
        line-height: 1.3;
        color: #111827;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .dark .gl-name { color: #fff; }

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
        color: #6b7280;
    }
    .dark .gl-date { color: #9ca3af; }
    .gl-go { width: 1.1rem; height: 1.1rem; flex-shrink: 0; color: #9ca3af; }

    /* ══════════════════════════════════════════════════════════════════════
       THE CANVAS  (.gt-*)  — wide viewports, and the mobile fullscreen sheet.

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
            radial-gradient(90% 70% at 50% 0%, rgba(0, 86, 179, .06) 0%, transparent 60%),
            linear-gradient(180deg, #fdfefe 0%, #f4f8fb 100%);
        border: 1px solid rgba(0, 0, 0, .06);
        box-shadow: 0 24px 60px -30px rgba(0, 86, 179, .35), inset 0 1px 0 rgba(255, 255, 255, .8);
    }
    .dark .gt-stage {
        background:
            radial-gradient(90% 70% at 50% 0%, rgba(100, 204, 197, .07) 0%, transparent 60%),
            linear-gradient(180deg, #1c2440 0%, #141a30 100%);
        border-color: rgba(255, 255, 255, .08);
        box-shadow: 0 24px 60px -30px rgba(0, 0, 0, .8);
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
        background-image: radial-gradient(rgba(0, 86, 179, .13) 1px, transparent 1px);
        background-size: 26px 26px;
        pointer-events: none;
    }
    .dark .gt-stage::before { background-image: radial-gradient(rgba(100, 204, 197, .1) 1px, transparent 1px); }

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
    .gt-link { fill: none; stroke: rgba(0, 86, 179, .3); stroke-width: 2; }
    .gt-link--collateral { stroke: rgba(0, 86, 179, .16); stroke-dasharray: 5 4; }
    .gt-link--focus { stroke: rgba(33, 136, 56, .5); stroke-width: 2.5; }
    .gt-link--spouse { stroke: rgba(233, 30, 99, .35); stroke-dasharray: 4 4; }
    .dark .gt-link { stroke: rgba(100, 204, 197, .3); }
    .dark .gt-link--collateral { stroke: rgba(100, 204, 197, .15); }
    .dark .gt-link--focus { stroke: rgba(100, 204, 197, .55); }
    .dark .gt-link--spouse { stroke: rgba(244, 114, 182, .4); }

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
        background: #fff;
        border: 1px solid rgba(0, 0, 0, .07);
        box-shadow: 0 6px 18px -10px rgba(0, 0, 0, .35);
        transition: transform .25s cubic-bezier(.22, 1, .36, 1), box-shadow .25s ease, border-color .25s ease;
    }
    .gt-card:hover {
        transform: translateY(-3px);
        border-color: rgba(0, 86, 179, .45);
        box-shadow: 0 16px 30px -14px rgba(0, 86, 179, .5);
    }
    .gt-card:focus-visible { outline: 2px solid #0056b3; outline-offset: 2px; }
    .dark .gt-card { background: #1d2743; border-color: rgba(255, 255, 255, .1); }
    .dark .gt-card:hover { border-color: rgba(100, 204, 197, .5); box-shadow: 0 16px 30px -14px rgba(0, 0, 0, .7); }

    .gt-card--ancestor { border-inline-start: 3px solid rgba(0, 86, 179, .55); }
    .dark .gt-card--ancestor { border-inline-start-color: rgba(100, 204, 197, .55); }
    .gt-card--collateral { opacity: .88; }
    .gt-card--spouse { border-inline-start: 3px solid rgba(233, 30, 99, .5); }
    .dark .gt-card--spouse { border-inline-start-color: rgba(244, 114, 182, .5); }
    .gt-card--spouse:hover { border-color: rgba(233, 30, 99, .5); box-shadow: 0 16px 30px -14px rgba(233, 30, 99, .45); }
    .gt-card--spouse .gt-ava { background: linear-gradient(135deg, #be185d, #e91e63); }
    .gt-card--spouse .gt-sub { color: #be185d; }
    .dark .gt-card--spouse .gt-sub { color: #f9a8d4; }

    /* Same specificity trap as the spine: without the `.dark` twin, the focus
       card on the canvas rendered as a plain dark card in dark mode. */
    .gt-card--focus,
    .dark .gt-card--focus {
        background: linear-gradient(135deg, #0056b3 0%, #1a7a6d 55%, #218838 100%);
        border-color: transparent;
        box-shadow: 0 18px 40px -16px rgba(0, 86, 179, .65);
    }
    .gt-card--focus:hover { box-shadow: 0 24px 50px -16px rgba(0, 86, 179, .8); }
    .gt-card--focus .gt-name { color: #fff; font-size: .95rem; }
    .gt-card--focus .gt-sub { color: rgba(255, 255, 255, .85); }
    .gt-card--focus .gt-spouse { color: rgba(255, 255, 255, .75); }

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
        background: linear-gradient(135deg, #0056b3, #3b82f6);
        box-shadow: inset 0 0 0 2px rgba(255, 255, 255, .35);
        user-select: none;
    }
    .gt-card--collateral .gt-ava { background: linear-gradient(135deg, #218838, #64CCC5); }
    .gt-ava--focus {
        width: 2.9rem;
        height: 2.9rem;
        font-size: 1.5rem;
        background: rgba(255, 255, 255, .18);
        box-shadow: inset 0 0 0 2px rgba(255, 255, 255, .4);
    }

    .gt-meta { min-width: 0; display: flex; flex-direction: column; gap: .1rem; }
    .gt-name {
        font-weight: 700;
        font-size: .8rem;
        line-height: 1.25;
        color: #111827;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .dark .gt-name { color: #fff; }
    .gt-sub {
        font-size: .62rem;
        font-weight: 700;
        color: #0f766e;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .dark .gt-sub { color: #64CCC5; }
    .gt-spouse {
        display: inline-flex;
        align-items: center;
        gap: .25rem;
        font-size: .62rem;
        font-weight: 600;
        color: #be185d;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .dark .gt-spouse { color: #f9a8d4; }
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
        background: linear-gradient(135deg, #f59e0b, #eab308);
        box-shadow: 0 4px 10px -3px rgba(245, 158, 11, .7);
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
        cursor: pointer;
        color: #0056b3;
        background: #fff;
        border: 1.5px solid rgba(0, 86, 179, .4);
        box-shadow: 0 3px 8px -3px rgba(0, 0, 0, .35);
        transition: background .2s ease, color .2s ease;
    }
    .gt-toggle:hover { background: #0056b3; color: #fff; }
    .dark .gt-toggle { background: #1d2743; color: #64CCC5; border-color: rgba(100, 204, 197, .45); }
    .dark .gt-toggle:hover { background: #64CCC5; color: #141a30; }

    /* The ancestor chip rides the TOP edge — the direction its people lie in. */
    .gt-toggle--up {
        top: auto;
        bottom: 100%;
        transform: translate(-50%, 50%);
        color: #6d28d9;
        border-color: rgba(109, 40, 217, .4);
    }
    .gt-toggle--up:hover { background: #6d28d9; color: #fff; }
    .dark .gt-toggle--up { color: #c4b5fd; border-color: rgba(196, 181, 253, .45); }
    .dark .gt-toggle--up:hover { background: #c4b5fd; color: #141a30; }

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
        color: #374151;
        background: rgba(255, 255, 255, .94);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(0, 0, 0, .07);
        box-shadow: 0 6px 16px -8px rgba(0, 0, 0, .4);
        transition: color .2s ease, background-color .2s ease;
    }
    .gt-ctrl:hover { color: #0056b3; }
    .dark .gt-ctrl { background: rgba(29, 39, 67, .94); color: #d1d5db; border-color: rgba(255, 255, 255, .1); }
    .dark .gt-ctrl:hover { color: #64CCC5; }
    .gt-ctrl--close { color: #b91c1c; }
    .dark .gt-ctrl--close { color: #fca5a5; }

    /* ── Shared entrance ── */
    @keyframes genealogy-fade-in-up {
        from { opacity: 0; transform: translateY(10px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .card-enter {
        animation: genealogy-fade-in-up .4s cubic-bezier(.16, 1, .3, 1) both;
        animation-delay: var(--enter-delay, 0ms);
    }

    @media (prefers-reduced-motion: reduce) {
        .card-enter { animation: none !important; }
        .gt-card, .gt-toggle, .gt-ctrl, .gs-card, .gl-card, .gs-caret { transition: none !important; }
        .gt-card:hover, .gl-card:hover { transform: none !important; }
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
    class="genealogy min-h-dvh"
    x-data="genealogyPage({{ $peopleJson }}, {{ $groupIdsJson }}, {{ $groupLabelsJson }})"
    x-init="init()">

    {{-- ═══════════════════ HERO ═══════════════════
         Deliberately short on a phone. The previous hero ran 1,300px before
         the tree began — a badge, a four-line headline, a paragraph, two
         stacked full-width buttons and a stat panel, all above the thing the
         page is for. The buttons are gone below `sm` because the view switcher
         sits ten pixels below them and says the same thing. --}}
    <section class="relative overflow-hidden py-6 sm:py-10
                    bg-gradient-to-br from-[#f2f7fd] via-[#f4faf6] to-[#eefaf9]
                    dark:from-[#1a1a2e] dark:via-[#16213e] dark:to-[#0f3460]">

        {{-- Eight-point-star tessellation, decorative only. --}}
        <svg class="absolute inset-0 w-full h-full opacity-[0.05] dark:opacity-[0.08]
                    text-[#0056b3] dark:text-[#64CCC5] pointer-events-none" aria-hidden="true">
            <defs>
                <pattern id="nasab-motif" width="60" height="60" patternUnits="userSpaceOnUse">
                    <g fill="none" stroke="currentColor" stroke-width="1.1">
                        <rect x="15" y="15" width="30" height="30"/>
                        <rect x="15" y="15" width="30" height="30" transform="rotate(45 30 30)"/>
                        <circle cx="30" cy="30" r="4.5"/>
                    </g>
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#nasab-motif)"/>
        </svg>
        <div class="absolute inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
            <div class="absolute -top-32 -end-24 w-80 h-80 bg-primary/10 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-36 -start-24 w-80 h-80 bg-secondary/10 rounded-full blur-3xl"></div>
        </div>

        <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="mb-3 sm:mb-5">
                <x-site.breadcrumb :items="[['label' => __('genealogy.public.hero_title')]]" />
            </div>

            <div class="flex flex-col lg:flex-row lg:items-center gap-5 lg:gap-10">
                <div class="lg:flex-1 min-w-0">
                    <span class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full mb-2.5
                                 bg-white/80 dark:bg-white/10 backdrop-blur
                                 ring-1 ring-black/5 dark:ring-white/10
                                 text-[11px] sm:text-xs font-bold tracking-wide
                                 text-gray-700 dark:text-gray-200">
                        <i data-lucide="sparkles" class="w-3.5 h-3.5 text-primary dark:text-accent" aria-hidden="true"></i>
                        {{ __('genealogy.public.hero_badge') }}
                    </span>

                    <h1 class="text-2xl sm:text-4xl lg:text-[2.6rem] leading-[1.2] font-black
                               text-gray-900 dark:text-white">
                        {{ __('genealogy.public.hero_title') }}
                        <span class="text-gradient">{{ __('genealogy.public.hero_subtitle') }}</span>
                    </h1>

                    {{-- Clamped on a phone: the full lead is three lines of
                         context before the reader reaches the tree. --}}
                    <p class="mt-2 sm:mt-3 text-sm sm:text-[15px] max-w-xl line-clamp-2 sm:line-clamp-none
                              text-gray-600 dark:text-gray-300 leading-relaxed">
                        {{ __('genealogy.public.hero_lead') }}
                    </p>

                    {{-- No buttons here on purpose.
                         This row used to hold "استكشف السلسلة" and "عرض القائمة"
                         — the exact two actions the sticky switcher offers
                         about 150px further down. Two controls for one choice
                         is a choice the reader has to make twice. The switcher
                         is the single place the view changes; the hero now
                         spends the space on the one thing the switcher cannot
                         say: what the chain actually is. --}}
                    @if ($oldestAncestor && $hasSpine)
                        <p class="mt-4 flex flex-wrap items-center gap-x-2 gap-y-1
                                  text-[13px] font-bold text-gray-500 dark:text-gray-400">
                            <span class="text-gray-900 dark:text-white">{{ $oldestAncestor }}</span>
                            <span class="text-primary/50 dark:text-accent/50" aria-hidden="true">←</span>
                            <span class="tabular-nums px-2 py-0.5 rounded-full
                                         bg-primary/10 dark:bg-accent/10
                                         text-primary dark:text-accent">{{ (int) $stats['generations'] }}</span>
                            <span class="text-primary/50 dark:text-accent/50" aria-hidden="true">←</span>
                            <span class="text-gray-900 dark:text-white">{{ $root?->name(app()->getLocale()) }}</span>
                        </p>
                    @endif

                </div>

                {{-- Stat strip — every figure is a count this page renders. --}}
                <div class="shrink-0 grid grid-cols-3 lg:grid-cols-1 rounded-2xl overflow-hidden
                            bg-white/75 dark:bg-white/[0.06] backdrop-blur
                            ring-1 ring-black/5 dark:ring-white/10
                            shadow-[0_10px_36px_-20px_rgba(0,86,179,.5)]
                            divide-x lg:divide-x-0 lg:divide-y divide-black/5 dark:divide-white/10 rtl:divide-x-reverse">
                    <div class="px-3 py-2.5 lg:px-7 lg:py-3.5 flex flex-col lg:flex-row lg:items-center gap-0.5 lg:gap-3">
                        <span class="text-lg sm:text-2xl font-black text-gradient tabular-nums leading-none"
                              x-text="displayStats.generations"
                              x-init="countUp('generations', {{ (int) $stats['generations'] }})">0</span>
                        <span class="text-[10px] sm:text-[11px] font-semibold text-gray-600 dark:text-gray-400">
                            {{ __('genealogy.public.stat_generations') }}
                        </span>
                    </div>
                    <div class="px-3 py-2.5 lg:px-7 lg:py-3.5 flex flex-col lg:flex-row lg:items-center gap-0.5 lg:gap-3">
                        <span class="text-lg sm:text-2xl font-black text-gradient tabular-nums leading-none"
                              x-text="displayStats.people"
                              x-init="countUp('people', {{ (int) $stats['people'] }})">0</span>
                        <span class="text-[10px] sm:text-[11px] font-semibold text-gray-600 dark:text-gray-400">
                            {{ __('genealogy.public.stat_people') }}
                        </span>
                    </div>
                    <div class="px-3 py-2.5 lg:px-7 lg:py-3.5 flex flex-col lg:flex-row lg:items-center gap-0.5 lg:gap-3">
                        @if ((int) $stats['children'] > 0)
                            <span class="text-lg sm:text-2xl font-black text-gradient tabular-nums leading-none"
                                  x-text="displayStats.children"
                                  x-init="countUp('children', {{ (int) $stats['children'] }})">0</span>
                            <span class="text-[10px] sm:text-[11px] font-semibold text-gray-600 dark:text-gray-400">
                                {{ __('genealogy.public.stat_children') }}
                            </span>
                        @else
                            <span class="text-sm font-black text-gradient leading-tight truncate"
                                  title="{{ $oldestAncestor ?? '—' }}">{{ $oldestAncestor ?? '—' }}</span>
                            <span class="text-[10px] sm:text-[11px] font-semibold text-gray-600 dark:text-gray-400">
                                {{ __('genealogy.public.stat_oldest_ancestor') }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="relative">

    {{-- ═══════════════════ CONTROLS (sticky) ═══════════════════
         `top-20`, matching the h-20 site header. It was `top-16`, which parked
         the bar four pixels under the header and let it slide beneath. --}}
    <section class="sticky top-20 z-30 border-y border-gray-200 dark:border-gray-800
                    bg-white/90 dark:bg-[#16213e]/90 backdrop-blur">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-2">
            <div class="flex items-center justify-between gap-2">

                @if ($hasGraph || $hasSpine)
                    {{-- Labelled, not icon-only: two unlabelled glyphs gave no
                         clue which was which. --}}
                    <div class="flex items-center gap-1 p-1 rounded-xl bg-gray-100 dark:bg-[#0f3460]">
                        <button type="button" @click="showTree()"
                                :aria-pressed="viewMode === 'tree'"
                                :class="viewMode === 'tree' ? 'bg-gradient-to-l from-primary to-secondary text-white shadow' : 'text-gray-600 dark:text-gray-300'"
                                class="inline-flex items-center gap-1.5 min-h-[2.25rem] px-3 rounded-lg text-xs font-bold transition-all">
                            <i data-lucide="git-branch-plus" class="w-4 h-4 shrink-0" aria-hidden="true"></i>
                            <span>{{ __('genealogy.public.view_tree') }}</span>
                        </button>
                        <button type="button" @click="viewMode = 'list'"
                                :aria-pressed="viewMode === 'list'"
                                :class="viewMode === 'list' ? 'bg-gradient-to-l from-primary to-secondary text-white shadow' : 'text-gray-600 dark:text-gray-300'"
                                class="inline-flex items-center gap-1.5 min-h-[2.25rem] px-3 rounded-lg text-xs font-bold transition-all">
                            <i data-lucide="list" class="w-4 h-4 shrink-0" aria-hidden="true"></i>
                            <span>{{ __('genealogy.public.view_list') }}</span>
                        </button>
                    </div>
                @endif

                <button type="button" @click="searchOpen = true; $nextTick(() => $refs.searchInput?.focus())"
                        class="inline-flex items-center gap-2 min-h-[2.5rem] px-3.5 rounded-lg text-sm font-semibold
                               bg-gray-100 dark:bg-[#0f3460] text-gray-700 dark:text-gray-200
                               hover:bg-gray-200 dark:hover:bg-[#16213e] transition-all">
                    <i data-lucide="search" class="w-4 h-4 shrink-0" aria-hidden="true"></i>
                    <span class="hidden sm:inline">{{ __('genealogy.public.search') }}</span>
                </button>
            </div>

            {{-- Filter pills get their own scrollable row instead of competing
                 with the switcher for one line at 375px. --}}
            <div x-show="viewMode === 'list'" x-cloak
                 class="flex items-center gap-2 pt-2 -mx-4 px-4 sm:mx-0 sm:px-0 overflow-x-auto scrollbar-hide">
                <template x-for="pill in filters" :key="pill.value">
                    <button type="button" @click="activeFilter = pill.value"
                            :aria-pressed="activeFilter === pill.value"
                            :class="activeFilter === pill.value
                                ? 'bg-gradient-to-l from-primary to-secondary text-white shadow-md'
                                : 'bg-white dark:bg-[#16213e] text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-700 hover:border-primary/50'"
                            class="whitespace-nowrap shrink-0 min-h-[2.25rem] px-3.5 rounded-full text-xs font-bold transition-all"
                            x-text="pill.label"></button>
                </template>
            </div>
        </div>
    </section>

    {{-- ═══════════════════ MAIN ═══════════════════ --}}
    <main class="py-6 sm:py-10 bg-[#F8F9FA] dark:bg-[#1a1a2e]">

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
                <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto w-full max-w-2xl">
                    <x-public.genealogy.spine-view
                        :spine="$spine ?? []"
                        :isRtl="$isRtl"
                        :lineageComplete="$lineageComplete ?? true" />

                    @if ($hasGraph)
                        <button type="button" @click="openCanvas()"
                                class="mt-5 inline-flex items-center justify-center gap-2 w-full min-h-[3rem] px-6 rounded-xl
                                       text-sm font-bold text-white active:scale-[0.98]
                                       bg-gradient-to-l from-primary to-secondary
                                       shadow-[0_10px_26px_-12px_rgba(0,86,179,.7)] transition-transform">
                            <i data-lucide="maximize-2" class="w-4 h-4 shrink-0" aria-hidden="true"></i>
                            <span>{{ __('genealogy.public.open_canvas') }}</span>
                        </button>

                        <p x-show="canvasError" x-cloak role="alert" class="gs-note mt-2">
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
                            <i data-lucide="loader-2" class="w-8 h-8 text-primary dark:text-accent animate-spin" aria-hidden="true"></i>
                            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">
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
        <div class="w-full sm:max-w-2xl max-h-[88vh] overflow-y-auto
                    rounded-t-3xl sm:rounded-3xl bg-white dark:bg-[#16213e] shadow-2xl"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-6 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95">

            <div class="sticky top-0 z-10 flex items-center justify-between gap-3 p-4 sm:p-6
                        border-b border-gray-200 dark:border-gray-800 bg-white/95 dark:bg-[#16213e]/95 backdrop-blur">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-full flex items-center justify-center shadow-lg shrink-0
                                bg-gradient-to-br from-primary to-secondary">
                        <i :data-lucide="selectedPerson?.isProphet ? 'crown' : 'user'" class="w-6 h-6 sm:w-7 sm:h-7 text-white" aria-hidden="true"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-lg sm:text-2xl font-bold text-gray-900 dark:text-white truncate" x-text="selectedPerson?.name"></h3>
                        {{-- The relation leads here too — it is the reason this
                             person is on this page. --}}
                        <p class="text-xs sm:text-sm font-bold truncate"
                           :class="`gl-rel--${selectedPerson?.relation}`"
                           x-text="selectedPerson?.relationLabel || selectedPerson?.title || ''"></p>
                    </div>
                </div>
                <button type="button" @click="selectedPerson = null"
                        class="shrink-0 w-11 h-11 flex items-center justify-center rounded-full text-gray-500 dark:text-gray-300
                               hover:bg-gray-100 dark:hover:bg-[#0f3460] transition-all"
                        aria-label="{{ __('genealogy.public.close') }}">
                    <i data-lucide="x" class="w-5 h-5" aria-hidden="true"></i>
                </button>
            </div>

            <div class="p-4 sm:p-6 space-y-5">
                <div class="flex flex-wrap items-center gap-2" x-show="hasDetails(selectedPerson)">
                    <template x-if="selectedPerson?.birthDateHijri">
                        <x-site.badge variant="default" icon="calendar-plus">
                            <span x-text="'{{ __('genealogy.public.born') }} ' + selectedPerson?.birthDateHijri"></span>
                        </x-site.badge>
                    </template>
                    <template x-if="selectedPerson?.deathDateHijri">
                        <x-site.badge variant="default" icon="calendar-minus">
                            <span x-text="'{{ __('genealogy.public.died') }} ' + selectedPerson?.deathDateHijri"></span>
                        </x-site.badge>
                    </template>
                    <template x-if="selectedPerson?.title">
                        <x-site.badge variant="accent" icon="scroll-text">
                            <span x-text="selectedPerson?.title"></span>
                        </x-site.badge>
                    </template>
                </div>

                <div>
                    <h4 class="flex items-center gap-2 text-base font-bold mb-2 text-gray-900 dark:text-white">
                        <i data-lucide="info" class="w-4 h-4 text-primary" aria-hidden="true"></i>
                        {{ __('genealogy.public.historical_note') }}
                    </h4>
                    <p class="text-sm leading-relaxed text-gray-700 dark:text-gray-300 whitespace-pre-line"
                       x-text="(selectedPerson?.biography || selectedPerson?.notes) || '{{ __('genealogy.public.no_biography') }}'"></p>
                </div>

                {{-- Marriages. Chips navigate: tapping علي on فاطمة's card
                     opens علي. --}}
                <template x-if="selectedPerson?.spouses?.length">
                    <div>
                        <h4 class="flex items-center gap-2 text-sm font-bold mb-2.5 text-gray-900 dark:text-white">
                            <i data-lucide="heart" class="w-4 h-4 text-[#e91e63]" aria-hidden="true"></i>
                            {{ __('genealogy.public.marriages') }}
                            <span class="tabular-nums text-xs font-bold text-gray-400" x-text="`(${selectedPerson.spouses.length})`"></span>
                        </h4>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="spouse in selectedPerson.spouses" :key="'sp-' + spouse.id">
                                <button type="button" @click="openPerson(spouse.id)"
                                        class="inline-flex items-center gap-2 max-w-full min-h-[2.5rem] ps-1.5 pe-3 rounded-full
                                               bg-pink-50 dark:bg-pink-500/10
                                               border border-dashed border-pink-300/70 dark:border-pink-400/30
                                               hover:border-pink-400 transition-colors
                                               focus:outline-none focus-visible:ring-2 focus-visible:ring-pink-400">
                                    <span class="shrink-0 w-6 h-6 rounded-full flex items-center justify-center
                                                 bg-gradient-to-br from-[#be185d] to-[#e91e63]">
                                        <span class="text-[11px] font-bold leading-none text-white" x-text="personInitial(spouse)"></span>
                                    </span>
                                    <span class="text-xs font-bold truncate text-[#be185d] dark:text-pink-300" x-text="spouse.name"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>

                <div class="flex flex-col gap-2">
                    <template x-if="selectedPerson?.hasProfile">
                        <a :href="sahabaProfileUrl(selectedPerson)"
                           class="inline-flex items-center justify-center gap-2 w-full min-h-[3rem] px-6 rounded-xl font-bold text-white active:scale-[0.97]
                                  bg-gradient-to-l from-primary to-secondary hover:shadow-lg transition-all">
                            <i data-lucide="book-open" class="w-5 h-5 shrink-0" aria-hidden="true"></i>
                            <span class="truncate" x-text="'{{ __('genealogy.public.know_more_about') }} ' + selectedPerson?.name"></span>
                        </a>
                    </template>

                    {{-- Only offered when the canvas is actually mounted and
                         holds this person; otherwise it is a dead button. --}}
                    <template x-if="canvasHas(selectedPerson)">
                        <button type="button" @click="revealInCanvas(selectedPerson)"
                                class="inline-flex items-center justify-center gap-2 w-full min-h-[3rem] px-6 rounded-xl font-bold
                                       text-primary dark:text-accent bg-primary/10 dark:bg-accent/10
                                       hover:bg-primary/20 dark:hover:bg-accent/20 transition-all">
                            <i data-lucide="crosshair" class="w-5 h-5 shrink-0" aria-hidden="true"></i>
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
        <div class="w-full max-w-2xl rounded-2xl bg-white dark:bg-[#16213e] shadow-2xl overflow-hidden"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-3"
             x-transition:enter-end="opacity-100 translate-y-0">
            <div class="p-4 sm:p-6">
                <div class="relative">
                    {{-- text-base, not text-sm: iOS zooms the whole page when a
                         focused input is under 16px. --}}
                    <input type="search" x-ref="searchInput" x-model="searchQuery"
                           @input="viewMode = 'list'"
                           enterkeyhint="search"
                           placeholder="{{ __('genealogy.public.search_placeholder') }}"
                           aria-label="{{ __('genealogy.public.search_title') }}"
                           class="w-full rounded-xl border-2 border-gray-300 dark:border-gray-700 bg-transparent
                                  py-3.5 ps-12 pe-12 text-base text-gray-900 dark:text-white
                                  placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:border-primary transition-colors" />
                    <i x-show="!searchLoading" data-lucide="search" class="absolute start-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" aria-hidden="true"></i>
                    <i x-show="searchLoading" data-lucide="loader-2" class="absolute start-4 top-1/2 -translate-y-1/2 w-5 h-5 text-primary animate-spin" aria-hidden="true"></i>
                    <button type="button" @click="searchOpen = false"
                            class="absolute end-2 top-1/2 -translate-y-1/2 w-9 h-9 flex items-center justify-center rounded-lg
                                   text-gray-400 hover:bg-gray-100 dark:hover:bg-[#0f3460] transition-all"
                            aria-label="{{ __('genealogy.public.close') }}">
                        <i data-lucide="x" class="w-5 h-5" aria-hidden="true"></i>
                    </button>
                </div>
                <p class="mt-3 text-xs sm:text-sm text-gray-500 dark:text-gray-400">{{ __('genealogy.public.search_hint') }}</p>
            </div>

            <div class="border-t border-gray-200 dark:border-gray-800 max-h-[50vh] overflow-y-auto">
                <template x-for="(person, idx) in searchResults" :key="'s-' + person.id">
                    <button type="button" @click="searchOpen = false; openPerson(person.id)"
                            class="w-full flex items-center gap-3 px-4 sm:px-6 py-3 min-h-[3.25rem] text-start
                                   active:bg-gray-100 dark:active:bg-[#0f3460]
                                   hover:bg-gray-100 dark:hover:bg-[#0f3460] transition-all card-enter"
                            :style="`--enter-delay: ${Math.min(idx, 8) * 30}ms`">
                        <i :data-lucide="person.isProphet ? 'crown' : 'user'" class="w-5 h-5 text-gray-400 shrink-0" aria-hidden="true"></i>
                        <span class="min-w-0 flex-1">
                            <span class="block font-semibold text-gray-900 dark:text-white truncate" x-html="highlightMatch(person.name)"></span>
                            <span class="block text-xs font-bold truncate"
                                  :class="`gl-rel--${person.relation}`"
                                  x-text="person.relationLabel || person.title || ''"></span>
                        </span>
                    </button>
                </template>
                <div x-show="searchQuery.length > 0 && !searchLoading && searchResults.length === 0"
                     class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
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
