{{--
    Public styling for cross-module embeds (Quran verse / hadith / seerah event)
    produced by the admin editor's moduleEmbed blot.

    Shared partial so every page that renders editor HTML looks the same — the
    rules used to live only in public/articles/show.blade.php, so the identical
    markup rendered unstyled everywhere else.

    Usage: @include('partials.module-embed-styles') inside a @push('head') block.
--}}
<style>
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
</style>
