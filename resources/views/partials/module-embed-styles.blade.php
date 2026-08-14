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
        position: relative;
        display: block;
        margin: 1.75rem 0;
        padding: 1.35rem 1.35rem 1.1rem;
        border-radius: 1.125rem;
        overflow: hidden;
        /* The kind of citation is carried by its label and tint, not by a
           slab of colour down one edge. A hairline in the same hue keeps the
           distinction without the accent-bar tell. */
        border: 1px solid color-mix(in srgb, var(--embed-accent, #0056b3) 26%, transparent);
        background: var(--embed-bg, #f8fafc);
        box-shadow: 0 1px 2px rgba(2, 30, 68, .04), 0 10px 30px -22px color-mix(in srgb, var(--embed-accent, #0056b3) 55%, transparent);
    }
    /* Light dash across the top edge — the site's gradient signature. */
    .module-embed::before {
        content: '';
        position: absolute;
        top: 0; inset-inline: 18%;
        height: 2px; border-radius: 2px;
        background: linear-gradient(90deg, transparent, color-mix(in srgb, var(--embed-accent, #64CCC5) 75%, #64CCC5), transparent);
    }
    .module-embed--verse  { --embed-accent: #218838; --embed-bg: linear-gradient(170deg, #f2fdf5 0%, #eefcf6 100%); }
    .module-embed--hadith { --embed-accent: #0056b3; --embed-bg: linear-gradient(170deg, #f0f7ff 0%, #eef6fd 100%); }
    .module-embed--sahabi { --embed-accent: #0d9488; --embed-bg: #f0fdfa; }
    .module-embed--seerah { --embed-accent: #7c3aed; --embed-bg: #f5f3ff; }
    .dark .module-embed {
        background: linear-gradient(170deg, rgba(255, 255, 255, .045) 0%, rgba(255, 255, 255, .02) 100%);
        box-shadow: 0 10px 34px -22px color-mix(in srgb, var(--embed-accent, #0056b3) 65%, transparent);
    }
    .module-embed__label {
        display: inline-flex; align-items: center; gap: .4rem;
        font-size: .72rem; font-weight: 700; letter-spacing: .02em;
        padding: .3rem .8rem; border-radius: 9999px; margin-bottom: .9rem;
        color: #fff;
        background: linear-gradient(120deg, var(--embed-accent, #0056b3), color-mix(in srgb, var(--embed-accent, #0056b3) 60%, #64CCC5));
        box-shadow: 0 2px 8px -2px color-mix(in srgb, var(--embed-accent, #0056b3) 60%, transparent);
    }
    .module-embed__label i, .module-embed__link i { width: 1rem; height: 1rem; }
    .module-embed__body { font-size: 1rem; line-height: 1.85; }
    .module-embed__arabic { font-family: 'Amiri', serif; font-size: 1.45rem; line-height: 2.35; }
    .module-embed__arabic p { margin: 0 0 .35rem; }
    .verse-number {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 1.7rem; height: 1.7rem; padding: 0 .35rem; margin-inline: .3rem .15rem;
        border-radius: 9999px; font-size: .78rem; font-weight: 700;
        font-family: 'Cairo', sans-serif; font-feature-settings: 'tnum';
        vertical-align: .2em;
        color: var(--embed-accent, #218838);
        background: color-mix(in srgb, var(--embed-accent, #218838) 10%, transparent);
        box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--embed-accent, #218838) 38%, transparent);
    }
    .dark .verse-number { color: #64CCC5; box-shadow: inset 0 0 0 1px rgba(100, 204, 197, .45); }
    .module-embed__footer { display: flex; align-items: center; gap: .65rem; flex-wrap: wrap; margin-top: 1rem; }
    /* Footer reference — a quiet pill; the icon is CSS, never content. */
    .module-embed__footer a,
    .module-embed__link {
        display: inline-flex; align-items: center; gap: .45rem; margin-top: .9rem;
        padding: .3rem .8rem; border-radius: 9999px;
        font-size: .82rem; font-weight: 700; text-decoration: none;
        color: var(--embed-accent, #0056b3);
        background: color-mix(in srgb, var(--embed-accent, #0056b3) 8%, transparent);
        box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--embed-accent, #0056b3) 25%, transparent);
        transition: background-color .2s ease, box-shadow .2s ease;
    }
    .module-embed__footer a { margin-top: 0; }
    .module-embed__footer a:hover,
    .module-embed__link:hover {
        background: color-mix(in srgb, var(--embed-accent, #0056b3) 15%, transparent);
        box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--embed-accent, #0056b3) 45%, transparent);
    }
    .dark .module-embed__footer a, .dark .module-embed__link { color: #64CCC5; }
    .module-embed__footer a::before {
        content: '';
        width: .95rem; height: .95rem; flex-shrink: 0;
        background: currentColor;
        -webkit-mask: var(--embed-glyph) center / contain no-repeat;
        mask: var(--embed-glyph) center / contain no-repeat;
    }
    /* Skip the CSS glyph when the link already carries a lucide icon (articles). */
    .module-embed__footer a.module-embed__link::before { content: none; }
    .module-embed--verse {
        --embed-glyph: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z'/%3E%3Cpath d='M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z'/%3E%3C/svg%3E");
    }
    .module-embed--hadith {
        --embed-glyph: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M15 12h-5'/%3E%3Cpath d='M15 8h-5'/%3E%3Cpath d='M19 17V5a2 2 0 0 0-2-2H4'/%3E%3Cpath d='M8 21h12a2 2 0 0 0 2-2v-1a1 1 0 0 0-1-1H11a1 1 0 0 0-1 1v1a2 2 0 1 1-4 0V5a2 2 0 1 0-4 0v2a1 1 0 0 0 1 1h3'/%3E%3C/svg%3E");
    }
    .module-embed--seerah {
        --embed-glyph: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z'/%3E%3Ccircle cx='12' cy='10' r='3'/%3E%3C/svg%3E");
    }
    .module-embed__source { display: inline-block; margin-top: .9rem; font-size: .85rem; font-weight: 600; color: #6b7280; }
    .module-embed__kunya, .module-embed__year { color: #6b7280; font-weight: 500; }
    .dark .module-embed__source, .dark .module-embed__kunya, .dark .module-embed__year { color: #9ca3af; }
    .module-embed__grade { font-size: .7rem; font-weight: 700; padding: .15rem .55rem; border-radius: 9999px; }
    .module-embed__grade--sahih, .module-embed__grade--صحيح { background: #dcfce7; color: #166534; }
    .module-embed__grade--hasan, .module-embed__grade--حسن { background: #fef9c3; color: #854d0e; }
    .module-embed__grade--daif, .module-embed__grade--ضعيف,
    .module-embed__grade--mawdu, .module-embed__grade--موضوع { background: #fee2e2; color: #991b1b; }
</style>
