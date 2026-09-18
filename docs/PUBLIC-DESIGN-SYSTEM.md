# Public design system (`sx`)

One visual world for every public surface. Defined in `resources/css/public/editorial.css`
(imported by `resources/css/app.css`) and used through `resources/views/components/public/editorial/*`.

**Rule:** a public page composes this vocabulary. It does not invent colours, fonts,
radii, shadows or hero shapes of its own. No page-scoped `<style>` block unless it
styles something genuinely unique to that page (a mushaf line, a map, an embed).

## Tokens

Declared on `:root` and `.dark` — usable anywhere, no wrapper required. `<body>`
carries `.sx`, which paints the page background and ink.

| Token | Use |
| --- | --- |
| `--sx-accent` / `--sx-accent-soft` | the single accent (brand blue) and its 8% wash |
| `--sx-grad` / `--sx-grad-soft` | blue→green signature gradient; fills and glyph plates only, never text |
| `--sx-teal`, `--sx-green` | inside the gradient only |
| `--sx-danger` / `--sx-danger-soft` | errors, disputes, destructive |
| `--sx-warn` / `--sx-warn-soft` | an incomplete step, not a failure (`.sx-chip.is-warn`, `.sx-note.is-warn`) |
| `--sx-bg`, `--sx-surface`, `--sx-surface-2` | page, raised paper, flat panel |
| `--sx-line`, `--sx-line-strong` | hairlines |
| `--sx-ink`, `--sx-ink-2`, `--sx-muted` | headings, body, secondary |
| `--sx-shadow` | the one elevation (offset + soft blur) |
| `--sx-ease` | `cubic-bezier(.16,1,.3,1)` — all motion |

Never use `text-gray-*`, `bg-white`, `dark:bg-[#16213e]`, `#0056b3` literals, or
`card-premium` on a public page. Use `text-[color:var(--sx-ink)]`,
`bg-[color:var(--sx-surface)]`, `.sx-surface`, etc. Tailwind utilities still win
over these classes (the CSS is in `@layer components`), so `!`-prefixes are rarely needed.

## Type

- Interface: **Readex Pro** (inherited — never set `font-['Cairo']` or a `font-family`).
- Revealed text (Quran, hadith matn, du'a): `.sx-scripture`, or `font-amiri` for inline.
- Display: `.sx-display` (page titles), `.sx-hero-title`, `.sx-section-title`,
  `.sx-lead` / `.sx-hero-lead` (intro copy), `.sx-eyebrow` (one kicker per page, not per section).
- Body copy: `.sx-prose` (capped measure, 2.05 line-height). Clamp long strings with
  `.sx-clamp-1/2/3` **and** a `title` attribute so nothing is silently truncated.
- Numbers in columns/counters: `tabular-nums`.

## Layout

- `.sx-wrap` — the page container (max 80rem + responsive gutters). Replaces `container mx-auto px-4 …`.
- `.sx-reading` — reading column + sticky `.sx-aside` (stacks under 1100px).
- `.sx-article` — the paper a long read sits on.
- `.sx-grid` (+ `.is-tight` / `.is-wide`) — auto-fill card grids.
- Page shell: `<div class="sx min-h-[100dvh] pb-20">` then `.sx-wrap`.

## Components (Blade)

| Component | For |
| --- | --- |
| `<x-public.editorial.page-hero>` | **every index/directory header**: eyebrow, title, lead, optional `stats` ledger (real counts only), default slot for a search box |
| `<x-public.editorial.hero>` | **every detail header**: full-bleed image, `variant="story\|reading"`, slots `top`, `chips`, `meta`, `actions` |
| `<x-public.editorial.card>` | every card: `url, title, excerpt, image, icon, badge, featured, big/bigSuffix, meta[], variant="tile\|row", eager` |
| `<x-public.editorial.section>` | titled section: `id, icon, title, index` |
| `<x-public.editorial.stats>` | figures that count up when scrolled into view |
| `<x-public.editorial.details>` / `.sources` / `.gallery` / `.media` | key–value facts, numbered citations, bento gallery, attachments |
| `<x-public.editorial.related>` | compact tile list (`numbered`, `current` supported) |
| `<x-public.editorial.toc>` / `.pager` / `.pagination` / `.empty-state` / `.fact-ribbon` | on-this-page, prev/next, paginator, empty state, hero-edge facts |
| `<x-public.editorial.reader-dock>` | floating reader toolbar + progress; requires `x-data="editorialReader({...})"` |

Module-specific wrappers map a payload onto the generic card — see
`components/public/articles/card.blade.php` and `components/public/seerah/event-card.blade.php`.
Add one per module rather than inlining a mapping twice.

## Classes worth knowing

`.sx-surface` (raised paper) · `.sx-panel` (flat, for panels **inside** a surface — never a card in a card) ·
`.sx-btn` (`.is-primary`, `.is-light`, `.is-glass`) · `.sx-chip` (`.is-active`, `.is-danger`, `.sx-count`) ·
`.sx-glass-chip` (on imagery) · `.sx-input` · `.sx-filters` / `.sx-filter-row` · `.sx-segment` (view switch) ·
`.sx-tabs` / `.sx-tab` · `.sx-tile` / `.sx-tile-list` / `.sx-tile-icon` / `.sx-tile-title` / `.sx-tile-sub` ·
`.sx-avatar` (`.is-lg`, `.is-square`) · `.sx-note` · `.sx-skeleton` ·
`.sx-label` (+ `.sx-req`) / `.sx-hint` / `.sx-error` / `aria-invalid` / `:disabled` / `.sx-btn.is-busy` ·
`.sx-stats` / `.sx-stat` · `.sx-ledger` · `.sx-path` / `.sx-path-step` (numbered journey) ·
`.sx-spotlight` (one featured item) · `.sx-pager` / `.sx-pages` / `.sx-page` · `.sx-divider` · `.sx-scroller`.

## Motion

One authored moment per page, transform/opacity/blur/clip only, all disabled under
`prefers-reduced-motion`:

- `.sx-rise` — staggered entrance for a header's children.
- `.sx-reveal` — sections settle in as they enter (driven by `editorialReader`).
- **Cross-page morph:** clicking a `.sx-card` carries its `.sx-card-media img` into the
  next page's `<x-public.editorial.hero>` image (shared `view-transition-name: sx-hero`,
  assigned at click time in `resources/js/app.js`). Requires nothing per page beyond
  using the card and hero components.
- Reading progress + dock: `editorialReader` (`resources/views/components/public/editorial/reader-dock.blade.php`).

## Chrome

`components/site/header.blade.php` — one active state (`.sx-nav-link.is-active` +
`aria-current="page"`) and a single travelling `.sx-nav-indicator` positioned from the
active item. `components/site/footer.blade.php` uses the same tokens.

## Accessibility floor

Body text ≥4.5:1 against its surface; secondary text on tinted surfaces is tinted,
never gray. Every icon-only control has `aria-label` + `title`. Focus is visible
(`:focus-visible` rings ship with `.sx-btn`, `.sx-chip`, `.sx-card`, dock). Touch
targets ≥44px. Real states: hover, disabled, loading (`.sx-skeleton`), empty, error.
