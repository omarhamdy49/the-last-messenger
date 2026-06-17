# Quran Reader Redesign — Design Specification

> Scope: `resources/views/public/quran/surah.blade.php` (+ a shared player component and a
> small JSON endpoint). Goal stated by the product owner: **make the page clearer, not more
> complex** — a real-mushaf reading preview, tools that are always within reach, a shared audio
> player with reciter switching, bottom-only surah navigation, and useful surah modals.
> Arabic-first, full RTL, dark/light, responsive. **No implementation in this doc — design only.**

---

## 1. Problems with the current page (observed)

| # | Today | Pain |
|---|-------|------|
| 1 | "Reading" view is plain flowing text with square sans verse badges | Doesn't feel like a mushaf; Latin digits even in Arabic |
| 2 | Display mode + layout + font + translation edition + tafsir source all sit in **one wrapping toolbar** | Tools (how I read) and filters (what content) are mixed; wraps on mobile; visually noisy |
| 3 | A large **audio box** is pinned in the sidebar | Eats vertical space, duplicates controls, not reachable while scrolling |
| 4 | Prev/Next surah appear **three times** (sidebar player, sidebar nav card, bottom band) | Redundant, cluttered |
| 5 | "Related links" panel (Reciters / Live / Asbab / All surahs) | Generic, low value, not contextual to the surah being read |

---

## 2. Design principles for the redesign

1. **Tools vs. Filters are different things, shown differently.**
   - **Tools** = how you read right now (layout, reading mode, font size, play). Always-on **floating rail**.
   - **Filters/Settings** = which translation / tafsir edition, transliteration on/off. Tucked into a **Settings popover** opened from the rail — not on screen permanently.
2. **One audio surface, shared with admin.** Reuse the `$store.audioPlayer` Spotify-style bottom bar; extend it with a reciter picker. Remove the sidebar audio box.
3. **Navigation lives at the bottom only.** A single Prev/Next surah band at the end of the reading column. Remove the sidebar duplicates.
4. **The mushaf is the hero.** Continuous Uthmani-style text, justified, ornate ayah-end roundels, Arabic-Indic verse numbers in Arabic/Urdu locales, parchment surface.
5. **Replace "quick links" with contextual surah actions:** Surah info modal, Surah switcher modal (lazy), and an "Ask about this surah" deep link that prefills the AI input.

---

## 3. Layout (information architecture)

```
┌──────────────────────────────────────────────────────────┐
│  Breadcrumb · Surah ornamental header (name, meta chips)   │
├──────────────────────────────────────────────────────────┐ ┌─────┐
│                                                            │ │ ▢   │  ← Floating Tools Rail
│   READING COLUMN (centered, max-w-4xl)                     │ │ Aa  │    (fixed, inline-end,
│   ┌────────────────────────────────────────────────────┐  │ │ ☷   │     vertically centered)
│   │  Bismillah (centered, ornamental)                  │  │ │ ▶   │
│   │  ﴿ … آية … ۝١ … آية … ۝٢ … آية … ۝٣ ﴾  (mushaf)     │  │ │ ⚙   │
│   │  justified, Amiri/QPC, ayah-end roundels           │  │ ├─────┤
│   └────────────────────────────────────────────────────┘  │ │ ▦   │  ← surahs
│                                                            │ │ ⓘ   │  ← info
│   [ ← السورة السابقة ]            [ السورة التالية → ]      │ │ ✦   │  ← ask
└──────────────────────────────────────────────────────────┘ └─────┘
        (Prev/Next band — bottom only)

  ┌──────────────────────────────────────────────────────────────┐
  │ ♪  الفاتحة · المنشاوي   ▶  0:12 ▬▬▬▬▬▬▬▬▬ 1:24   👤 reciter  ✕ │  ← Shared player
  └──────────────────────────────────────────────────────────────┘     (bottom, only when open)
```

The 3-column desktop grid (content + sidebar) collapses to a **single reading column**. The
sidebar's useful data (surah meta, description) moves into the **Info modal**; its audio + links
are replaced as above. This is what removes the clutter.

---

## 4. Component specs

### 4.1 Mushaf reading view (the hero) — match quran.com reading mode exactly

Reference target (user-supplied screenshot of `quran.com/al-baqarah?readingMode=arabic`). The
look to reproduce, element by element:

1. **Clean, frameless page.** No parchment, **no decorative border frame**, no card chrome — plain
   page background (white / dark), text-forward. (Drop the earlier parchment + inner-frame idea.)
2. **Narrow centered measure.** Mushaf column `max-w-[42rem]` `mx-auto`; empty margins hold focus;
   the floating rail lives in the margin. Mobile: full-width-minus-gutters.
3. **Mushaf lines from layout data, justified to fill the line.** Words are grouped into lines by
   `quran_words.line_number` (see chosen path below) and each line is justified to fill the page
   width like a printed mushaf line — not free-flowing wrap, not a single justified block. Generous
   `line-height` (~2.2–2.4). Page order follows `page_number`.
4. **Bismillah block:** ornamental Arabic Bismillah centered, with its **translation subtitle**
   directly under it in muted gray (e.g. "In the Name of Allah—the Most Compassionate, Most
   Merciful"), localized. Shown when `surah.bismillahPre` (hidden for At-Tawbah / 9).
5. **Ornate rosette ayah markers** with the verse number inside, in **Arabic-Indic digits**
   (`١ ٢ ٣ …`) for ar/ur. Use the KFGQPC end-of-ayah glyph (`۝`, U+06DD) rendered in the mushaf
   font, or an SVG rosette with the number overlaid — not a plain circle.
6. **Coloured muqaṭṭaʿāt / special letters:** the opening letters (e.g. `الٓمٓ`) render in the brand
   teal `#64CCC5`, matching the reference's coloured letters. (Optional nicety; data-driven.)
7. **Active-verse highlight:** the current verse (hovered / playing / deep-linked) gets a **subtle
   full-width gray band** behind it — exactly like the reference's highlighted first verse.
8. **Mushaf page number** centered **below** the page text (Arabic-Indic in ar/ur), with a thin
   divider rule under it. Verses are grouped by `page_number` (already on the `Verse` model).
9. **Per-verse interaction:** tapping a verse highlights it and reveals a small inline action
   popover (Play this verse · Copy · Tafsir · Bookmark-hook). No permanent per-verse button row.
10. **"Verses" view** (cards) stays available for translation/tafsir study — toggled from the rail.
    Mushaf is the default for `readingMode = 'arabic'`.

#### VERIFIED how quran.com does it (live DOM inspection, 2026-06-17)

Inspected `quran.com/al-baqarah?readingMode=arabic` via browser. Confirmed mechanism — **not images,
not plain-font CSS on Arabic Unicode**, but the **QCF "code_v2"** technique:
- Each word = an element with `data-word-location="2:1:1"`; its text is a **single Private-Use
  glyph** (`ﱁ` = `U+FC41`), not the Arabic letters.
- That glyph renders only with a **page-specific font `p{page}-v2`** (e.g. `p2-v2`); 604 fonts total,
  **lazy-loaded one page at a time** (only `p2-v2` was loaded on the open page).
- Lines are flex rows classed `code_v2-line-width-{n}` with stretched `justify-content`, so each line
  fills a printed-mushaf line exactly — driven by per-word `line_number` (1–15) + `page_number`.
- A single-font `UthmanicHafs` (KFGQPC HAFS) path also exists on the page — quran.com's *alternate*
  mode; that is the one-font approximation, NOT what produces the exact-line reference look.

**Conclusion:** the reference's exact line-for-line look REQUIRES the glyph-font path (Tier B).
Tier A (single Uthmani font) is only an approximation and will not match line breaks. Data + fonts
are openly available from **QUL (qul.tarteel.ai)** and the quran.com API
(`/verses/by_page/{n}?words=true&word_fields=code_v2,line_number,page_number`).

#### Fidelity tiers — the one decision that changes scope

True **pixel-identical** quran.com rendering breaks every line at the *exact* word the printed
mushaf does. That is produced by page-specific glyph fonts + word-level line data, **neither of
which exists in this project today** (we have `page_number` per verse and plain Uthmani text, Amiri
+ Cairo fonts only). Two ways to deliver:

- **Tier A — "looks like the mushaf" (no new data, ships now).** Add a proper **Uthmani web font**
  (KFGQPC HAFS Uthmanic Script, or Amiri Quran), centered lines, rosette markers, Bismillah +
  translation, page number, active highlight, coloured muqaṭṭaʿāt. Lines wrap **naturally** within
  the page, grouped by `page_number`. ~95% of the visual feel; **line breaks won't match the
  printed page exactly.** View-layer + one font asset.
- **Tier B — "byte-identical to the printed mushaf".** Import the **QUL / KFGQPC mushaf-layout
  dataset**: a new `quran_words` table (word text in `code_v1`/`code_v2` glyphs + `line_number` +
  `page_number`), and the **604 page glyph fonts** (`p1.woff2`…`p604.woff2`). Render each page with
  its own font so lines break exactly like the reference. This is a **data-import + schema +
  asset** effort (new migration, importer command, ~604 font files), not just CSS.

> **DECISION (product owner): mushaf LAYOUT only — keep our current font (Amiri), NO font
> downloads.** Use the open word-layout data (line + page + order) to reproduce the mushaf's line
> grouping and page order exactly, rendered in the site's existing Uthmani Unicode font.

**What's identical vs. not (be explicit):** which words sit on each line and where pages break **are
identical** to the printed mushaf (data-driven). Line *widths* are **not pixel-perfect** — Amiri's
metrics differ from KFGQPC, so justification stretches each line differently. We reproduce structure
(alignment, word order, pagination), not the glyph shapes. **No `code_v2` glyphs, no `p{n}-v2`
fonts, nothing downloaded.**

**Data source (open):** quran.com API v4, page-by-page — request the **Unicode** text + layout:
`GET https://api.quran.com/api/v4/verses/by_page/{page}?words=true&word_fields=text_uthmani,line_number,page_number,location,char_type_name`
(or the QUL export). We take `text_uthmani` (real Arabic word), `line_number` (1–15), `page_number`
(1–604), `location` ("2:1:1"), `char_type_name` (`word` | `end` | `pause` | `sajdah`). We **ignore**
`code_v2`/glyph fields.

**Schema (new):** `quran_words` —
`id, verse_id (FK verses), surah_number, ayah_number, position, location (unique),
text_uthmani (string), char_type (enum: word|end|pause|sajdah), line_number (u tinyint),
page_number (u smallint, indexed), index([page_number, line_number, position])`.
~77k rows. One driver-guarded migration.

**Importer:** `php artisan quran:import-mushaf-layout {--pages=1-604}` — fetches each page, upserts
`quran_words` keyed by `location` (idempotent). In `app/Domain/Quran` via a repository method; no
logic in the command.

**Renderer (`public/quran/_mushaf.blade.php` + Alpine):**
- Group the surah's words by `page_number` → for each page, by `line_number`.
- Each **line** = a flex row of word spans (Amiri / current `font-uthmani`), justified to fill the
  line width (`justify-content: space-between` / `text-justify`), so it reads as a mushaf line.
- Each **word** = a span with `data-word-location`, text = `text_uthmani`. `char_type = end` →
  render our own **rosette ayah marker** (SVG rosette or `۝` U+06DD) with `toLocaleDigits(ayah)` —
  since we're NOT using the glyph font, the numbered rosette is ours, not from the font.
- After each page's lines: centered **page number** (Arabic-Indic in ar/ur) + thin divider.
- Tap a word/verse → highlight + action popover (play/copy/tafsir). `prefers-reduced-motion` safe.
- **Optional perf:** `IntersectionObserver` to mount page blocks lazily for long surahs — but **no
  per-page font loading** (one font for the whole page), so this is just DOM virtualization, optional.

**Backend delivery:** surah controller passes words grouped by page (one indexed query:
`where page_number = ? order by line_number, position`, explicit `->select([...])`, no N+1).
Optional `GET /quran/surah/{n}/page/{p}/words` JSON only if we virtualize.

**a11y/copy:** `text_uthmani` is the real text, so copy + screen readers work natively (no PUA
fallback gymnastics needed — a benefit of keeping our font).

> Everything else (floating rail, shared player + reciter, surah modals, bottom nav, Ask prefill,
> `/quran/surahs` endpoint) is unchanged — only §4.1 gains the line/page layout from `quran_words`.

### 4.2 Floating Tools Rail (always reachable)

**Desktop (≥1024px):** a vertical glass pill, `position: fixed`, `inset-inline-end: 1.25rem`,
`top: 50%; translateY(-50%)`, `z-40`. 44×44px icon buttons, tooltip on hover, brand active state.

| Button | Icon | Action |
|--------|------|--------|
| Layout | `book-open` / `layout-grid` | Toggle **Mushaf ↔ Verses** |
| Reading mode | `languages` | Popover: Arabic / Translation / Both |
| Font size | `Aa` | Popover with −/＋ stepper + slider (reuses `adjustFontSize`) |
| **Play** | `play` | Opens the **shared player** & starts current surah |
| Settings | `sliders` | Popover: translation edition, tafsir source, transliteration toggle |
| — divider — | | |
| Surahs | `layout-grid` | Opens **Surah switcher modal** (lazy) |
| Info | `info` | Opens **Surah info modal** |
| Ask | `sparkles` | Go to `/quran/ask?q=سورة <name>: ` (prefilled) |

**Mobile (<1024px):** the rail becomes a **sticky bottom mini tool-bar** (thumb zone) with the 4
primary actions — Mushaf toggle · Aa · Play · **More** — where "More" opens a bottom **tool sheet**
(`max-h-[80vh] overflow-y-auto`, `max-w-[85vw]` not needed since full-width sheet) carrying Settings,
Surahs, Info, Ask. When the player is open it docks **above** this bar (stacked, safe-area aware).

> This is the core of requirement #2: **Tools = rail (always there); Filters = Settings popover.**

### 4.3 Shared audio player (reuse admin component + reciter switching)

- **Reuse** the existing `Alpine.store('audioPlayer')` and the bottom-bar markup from
  `components/admin/audio-player.blade.php`. Promote it to a **shared component**
  (`components/shared/audio-player.blade.php`) included by both the admin layout and the public
  site layout. Same store, same persistence, same RTL-aware seek.
- **Extend the store (additive, back-compatible):**
  - `reciters: []`, `reciterId: null` — optional; when present the bar shows a **reciter button**.
  - `playByReciter(id)` — swaps `track.url` to the selected reciter's surah audio and resumes from
    the same `currentTime` (or 0 if track changes surah). Persists `reciterId` to localStorage.
  - `track` gains `meta: { surah, reciterName }` for the subtitle line.
- **Player controls (final set):** play/pause · seek bar + times · **speed** (1× → 1.25× → 1.5× →
  2× → 0.75×, port `cycleSpeed`) · **reciter** button (opens a small reciter picker popover) ·
  volume (desktop) · close. **No prev/next inside the player** — surah navigation is the bottom band.
- The page's **Play rail button** calls `$store.audioPlayer.play({ url, title, subtitle, meta })`
  with the selected reciter's surah URL. Per-verse "Play this verse" routes the per-verse URL
  through the same store, so there's exactly one audio surface.
- Remove the entire sidebar audio box (`surah.blade.php` lines ~828–902) and its bespoke
  `surahAudio*` Alpine state — superseded by the store.

### 4.4 Bottom Prev/Next band (only navigation)

- Keep the existing bottom band (`surah.blade.php` lines ~742–764), but **simplify each button to
  just an arrow + the surah name** (Amiri). Drop the "السورة السابقة" / "السورة التالية" caption
  lines entirely — the directional chevron already signals prev vs. next.
  - Result: `[ ← الأنعام ]` and `[ الأنفال → ]` (chevrons RTL-flipped, names truncated).
  - Keep `title`/`aria-label` = "السورة السابقة: الأنعام" for accessibility even though the caption
    is hidden visually.
- **Remove** the sidebar player's prev/next and the sidebar "Surah navigation" card (the duplicates).

### 4.5 Surah info modal (replaces sidebar info + "quick links")

- Trigger: rail **Info** button. Centered modal, `max-h-[80vh] overflow-y-auto`, scrim 45–60%.
- Content: ornamental header (Arabic name Amiri + English), meta chips (Makki/Madani, verses count,
  juz, revelation order), then the **full** `surah.info` description (currently truncated in the
  sidebar — here it's shown in full with `word-break`). Loading/empty/error states honored.

### 4.6 Surah switcher modal (lazy-loaded, 114 surahs)

- Trigger: rail **Surahs** button. On **first open**, fetch the list from a new lightweight JSON
  endpoint (see §5) and cache it in Alpine for the session (lazy — not shipped in initial HTML).
- UI: sticky search box (filters by number, Arabic name, transliteration, English name — client-side),
  scrollable list. Each row: number badge (localized digits), Arabic name (Amiri), English name +
  verses + Makki/Madani chip. **Active surah highlighted.** Keyboard navigable; `Escape` closes.
- Selecting a row → `window.location = route('public.quran.surah', number)` (full navigation; simple,
  shareable, matches current routing). 114 rows render fine without virtualization.

### 4.7 "Ask about this surah" (prefilled AI)

- Trigger: rail **Ask** button (`sparkles`). Links to the existing Ask page, which already reads a
  `?q=` prefill (`QuranController::ask()` → `$initialQuery`).
- Link target: `route('public.quran.ask', ['q' => 'سورة '.$surahDisplayName.': '])`.
  Renders the Ask composer with `سورة الأعراف: ` pre-filled and the caret after the colon, exactly
  as requested. (Localize the `سورة` prefix label per active locale; keep the surah name in Arabic.)

---

## 5. Backend touchpoints (minimal)

| Need | Change | Notes |
|------|--------|-------|
| Lazy surah list for switcher | **New** `GET /quran/surahs` → JSON (`public.quran.surahs.json`) | Reuse `index()`'s cached `listSurahs` mapping (number, nameAr, nameEn, transliteration, verses, type, juz). Controller → one query-service call → Resource/array. No business logic in controller. |
| Reciter surah URLs for player | Already provided to `surah.blade.php` (`$reciters`, `surahAudioUrls`) | Pass through to the rail Play button + player store; no new query. |
| Ask prefill | None | `ask()` already honors `?q=`. |

Everything else is **view-layer only**. No schema changes, no N+1 (the surah list is a single cached
query; the player uses data already on the page). Keep `->select([...])` discipline in the new
endpoint's query-service path.

---

## 6. RTL / i18n / hardening checklist (per CLAUDE.md §5)

- All new strings via `__('quran.public.*')` keys added to **all 5** locale files (ar, en, fr, tr, ur).
  New keys: `tools`, `display_settings`, `switch_surah`, `surah_description`, `ask_about_surah`,
  `reciter`, `search_surahs`, `mushaf_view`, plus reuse existing mode/font/listen keys.
- Logical properties only (`inset-inline-end`, `ps-`, `pe-`, `start-`, `end-`); chevrons flip in RTL.
- Arabic-Indic digits in ar/ur via `toLocaleDigits()`; Amiri for Quran text, Cairo for UI.
- States: loading skeleton (mushaf lines shimmer), empty (no verses → message), error (audio/list
  fetch → retry). Player & modal buttons disable while pending. Modals `max-h-[80vh] overflow-y-auto`.
- Mobile: tool sheet inputs `text-base sm:text-sm`; action rows `flex-wrap`; safe-area padding for the
  stacked player + tool-bar; touch targets ≥44px.
- Dark + light verified; secondary text `text-gray-300` (dark) / `text-gray-600` (light).
- `prefers-reduced-motion`: disable the verse-target pulse and ornament animations.

---

## 7. What gets removed / changed in `surah.blade.php`

- **Remove:** wrapping reading-controls toolbar (lines ~476–562) → replaced by floating rail +
  Settings popover.
- **Remove:** sidebar audio box (~828–902) and `surahAudio*` state → shared player store.
- **Remove:** sidebar "Surah navigation" card (~905–932) and "Related links" panel (~935–981).
- **Keep & restyle:** mushaf/verses rendering (upgrade ayah markers + Arabic digits + parchment),
  bottom Prev/Next band, deep-link verse pulse, progress bar.
- **Add:** floating tools rail, Settings popover, surah info modal, surah switcher modal, Ask link,
  shared player include.

---

## 8. Success criteria (verify before "done")

- [ ] Mushaf view reads like quran.com reading mode: justified Uthmani text, ornate ayah roundels,
      Arabic-Indic numbers in ar/ur, centered Bismillah.
- [ ] Tools reachable at all scroll positions on desktop (rail) and mobile (bottom bar); filters live
      only in the Settings popover.
- [ ] No sidebar audio box; Play opens the shared bottom player; reciter can be changed and audio
      resumes; speed/seek/volume work; RTL seek correct.
- [ ] Prev/Next appears exactly once (bottom band).
- [ ] Info modal shows full description; Surah switcher lazy-loads, searches, and redirects; Ask
      opens with `سورة <name>: ` prefilled.
- [ ] All 5 locales have keys; RTL + dark/light verified; reduced-motion respected; ≥44px targets.
- [ ] No new N+1; new `/quran/surahs` endpoint is a single cached query; `pint` clean.

---

*Design only. Implementation should run under `/laravel-generator` + `/faang-engineer` (backend
endpoint) and `/ui-ux-pro-max` (views), one component at a time, with the §8 criteria as the gate.*
