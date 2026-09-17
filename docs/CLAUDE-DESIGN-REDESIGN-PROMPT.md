# Claude Design prompt — The Last Messenger frontend redesign

Paste everything below the line into Claude Design. Attach screenshots of the current
home page (light + dark, desktop + mobile) if you have them; the prompt works without.

---

## Role

You are the lead product designer for **خاتَمُ المُرْسَلِين / The Last Messenger**, an
Islamic digital encyclopedia about Prophet Muhammad ﷺ: Quran reader with reciters and
playlists, Hadith collections, Seerah timeline, Companions (Sahaba) with a genealogy
tree, Islamic media programs and live channels, articles, topics and search.

Redesign the public website with **premium editorial polish**. The result must feel
like a flagship cultural institution site (think a national museum or a top-tier
manuscript archive), not a template, not a SaaS landing page, and not a mosque
brochure. Reverent, calm, confident, modern.

Produce a multi-artboard canvas. Home page first and in the most depth; then the
secondary pages listed at the end.

## Non-negotiable constraints

1. **Arabic-first, RTL by default.** Every artboard is designed in Arabic RTL first.
   Provide one LTR (English) variant of the home page only. All layout must be
   mirror-safe: use logical start/end, never hard-coded left/right.
2. **Light and dark themes.** Deliver both for the home page. Dark is not inverted
   light; it is a deep navy/ink surface with warmed highlights (current dark surfaces
   are `#1a1a2e`, `#16213e`, `#0f3460`, `#0a2647` — refine, don't discard).
3. **Existing brand tokens — keep them, elevate them:**
   - Primary blue `#0056b3`, secondary green `#218838`, accent teal `#64CCC5`
   - Brand gradient `linear-gradient(135deg, #0056b3 0%, #218838 100%)`
   - Neutrals `#F8F9FA`, `#E9ECEF`, text `#333`
   - You may add **one** warm metallic (muted gold / brass, e.g. `#C9A96E`) for
     hairlines, ornament and emphasis only. No other new hues.
   - Reduce the gradient's role: it is currently used as a background everywhere.
     Use it sparingly (a single CTA, a thin band, an accent stroke), never as the
     page wash.
4. **Typography, already loaded:**
   - UI/body: **Cairo** (weights 300/400/600/700/900), with **Readex Pro** as the
     alternative sans
   - Quran and Hadith text: **Amiri** (400/700)
   - Do not introduce a new font family. Establish a strict type scale (display,
     h1–h4, body, caption, Arabic scripture) with explicit sizes, line-heights and
     letter-spacing for both Arabic and Latin.
5. **Performance discipline.** Hero image is the LCP element and is preloaded;
   keep exactly one large hero image. No full-bleed video. Decorative effects must
   be CSS-achievable (gradients, masks, SVG geometric patterns, subtle grain).
6. **Accessibility:** WCAG AA contrast on every text/background pair in both themes,
   44px minimum touch targets, visible focus rings, reduced-motion variants.
7. **Responsive:** every artboard at 1440 desktop and 390 mobile. Tablet may be
   inferred.
8. Tech target is **Tailwind CSS + Alpine.js Blade views**. Do not design anything
   that needs a canvas/WebGL/3D library.

## Design direction

- **Islamic geometric craft, restrained.** Use an 8-fold or 12-fold star lattice as
  a faint background pattern (max 4–6% opacity), thin gold hairlines, arched card
  tops or subtle muqarnas-inspired corner details on at most one component type.
  No stock mosque photos, no crescent clip-art, no calligraphy as decoration.
- **Editorial rhythm.** Generous whitespace, a real baseline grid, large numerals
  for stats, pull-quote treatment for the daily verse/hadith with Amiri at display
  size and clear tashkeel rendering.
- **Depth via layering, not shadows.** Prefer surface tone steps, 1px borders with
  10–15% alpha, and soft inner glows over heavy drop shadows. Glassmorphism is
  allowed only on the sticky header and the hero search bar.
- **Motion:** specify entrance and hover choreography in a notes panel per
  section (durations, easings, staggers). Keep it under 400ms, spring-free,
  reduced-motion fallback = fade only.

## Home page — sections (in current order; you may reorder if you justify it)

Section keys are CMS-managed (`hero`, `hero_cards`, `sahaba_carousel`, `seal_band`,
`daily`, `explore`, `featured_content`, `cta`). Keep every section; each can be
toggled by admins so each must stand alone visually.

1. **Hero** — one large background image (Ken Burns + light parallax already exist),
   title, subtitle, a **premium search bar** (global search across Quran/Hadith/
   Seerah/Sahaba/Media), and a row of quick-filter chips managed from the DB.
   Design goal: an instant sense of scholarship and calm. The search bar is the
   primary action; make it unmistakable and beautiful in both themes.
2. **Hero module cards** — 4–6 cards linking to the modules (Quran, Hadith, Seerah,
   Sahaba, Media, Genealogy). Each has an image, title, short line, and count.
   Overlaps the bottom of the hero.
3. **Sahaba carousel** — horizontally scrolling companion portraits (name, role,
   era); has prev/next arrows that must mirror in RTL; "view all N" link.
4. **Seal band** — a dark "identity card" of the Prophet ﷺ: full name, birth date
   and place, start of the mission, death date and place, plus timeline stats and
   two action buttons. Currently dark navy with decorative circles. This is the
   signature section; make it museum-grade.
5. **Daily** — two side-by-side panels: Verse of the Day (Arabic text in Amiri,
   translation, surah reference, play-audio control) and Hadith of the Day (Arabic
   text, narrator, grade badge, collection reference, share/copy/favorite).
6. **Explore** — grid of module cards with badge, corner accent and hover quick
   actions. Currently the busiest section; simplify and unify with section 2 so the
   two card families share a system.
7. **Featured content** — 3–6 featured articles/programs with cover, category,
   title, excerpt, date (Hijri + Gregorian).
8. **CTA** — register / start reading. Full-width, this is the one place the brand
   gradient may appear as a background.
9. **Sticky header** (logo, module nav, search icon, language switch, theme toggle,
   account) and **footer** (tagline, quick links, module links, social, legal).

Also include: a **stats row** (surahs, seerah events, hadiths, companions) with
large numerals; empty/skeleton states for the carousel and daily panels; and the
signed-in variant of the header.

## Deliverables

- Artboard 1–4: Home, Arabic RTL, light + dark, 1440 + 390.
- Artboard 5: Home, English LTR, light, 1440.
- Artboard 6: **Design system sheet** — color tokens (light/dark), type scale,
  spacing scale, radius, borders, elevation, the geometric pattern tile, the
  card family (module card, content card, daily panel, sahabi card), buttons
  (primary/secondary/ghost/icon) with all states, chips, badges (hadith grades
  sahih/hasan/daif/mawdu), inputs, the search bar, and header/footer.
- Artboard 7+: secondary pages, Arabic RTL light, 1440 (mobile optional):
  Quran index, Surah reader (mushaf + translation + tafsir + audio player bar),
  Hadith collection index, single hadith, Seerah timeline, Sahabi profile, Media
  program page with episode list, Search results, Login/Register.
- A **notes artboard**: rationale for the direction, the Tailwind token mapping
  (extend `colors`, `fontFamily`, `backgroundImage`), and per-section motion specs.

## Quality bar

Before finishing, check each artboard against: does it look like a template? Is
the gradient over-used? Is any text under AA contrast? Does anything break when
mirrored to LTR? Is the hero search the obvious first action? Would a scholar and
a 19-year-old both find it credible? Fix anything that fails.
