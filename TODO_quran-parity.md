# TODO — Quran Module: quran.com Parity Roadmap

> **Role:** system/backend/database-architect (combined)
> **Generated:** 2026-06-14 · **Statuses verified against the live DB on 2026-06-14.**
> **Goal:** Feature parity with [quran.com](https://quran.com) (Quran Foundation).

## Product decisions locked

| Decision | Choice | Consequence |
|---|---|---|
| Scope | **Full parity roadmap** | All phases in scope. |
| Translation/tafsir model | **Keep one-per-language** | ⚠️ Cap = 5 translations + 5 tafsirs. **F1/F2 side-by-side multi-source DEFERRED.** Upgrade path = [Q-UP1](#q-up1). |
| Content source | **Quran Foundation / QUL** ([qul.tarteel.ai](https://qul.tarteel.ai), [API v4](https://api-docs.quran.com)) | Import, don't author. |

**Deferred per existing product decisions (UI hooks only):** community reflections (comments), reading streaks/plans (gamification), live streaming.

---

## ✅ Verified-complete (live DB, 2026-06-14) — do NOT rebuild

The earlier gap docs (`QURAN_MODULE_IMPROVEMENT_PLAN.md`, `WEB_APP_MODULES_ANALYSIS.md`) were **stale**. Confirmed present:

- 114 surahs · 6,236 verses · **37,416 translations** (ar/en/fr/tr/ur **+ `en-tl` transliteration**) · 6,236 tafsirs (Arabic Muyassar).
- **11 reciters, all with `everyayah_folder`** · **1,254 per-surah recitations** (11×114) with `audio_url`.
- `VerseAudioUrlResolver` (per-verse everyayah URLs) is **wired** into `Site/QuranController` + `surah.blade.php`.
- ⇒ **Q-P1-01 (audio) and Q-P1-02 (transliteration) are DONE.**

---

## PHASE 1 — remaining

### [ ] Q-P1-03 🟠 Translation footnotes (F9)
- Nullable `footnotes` JSON column on `quran_verse_translations`; parse footnote markup on import. Driver-guarded, reversible.

### [ ] Q-P1-04 🟠 Tafsir for EN/FR/TR/UR (one per language)
- Only Arabic (Muyassar) tafsir exists. Import one canonical tafsir per other language from QUL (EN = Ibn Kathir abridged, etc.). Honors one-per-language cap.

---

## PHASE 2 — quran.com-grade reading experience

### [x] Q-P2-04 🟢 Navigational / command search + autocomplete (F11) — **DONE**
- API: `GET /api/v1/quran/search/suggest?q=&limit=&locale=` (throttle:search). Parses `2:255`, `juz N`, `page N`, `hizb N`, surah number, surah name → structured jump targets. Arabic/Urdu digit normalization, diacritic-insensitive name match. Service: `QuranNavigationService`. Wired into `public/quran/index.blade.php` autocomplete (debounce, keyboard nav, loading/empty/error, RTL, dark mode). E2E verified.

### [x] Q-P2-06 🟢 Juz / Hizb / Page browsing (public) — **DONE**
- Routes `/quran/juz/{juz}` (1-30, paginated), `/quran/hizb/{hizb}` (1-60, paginated), `/quran/page/{page}` (1-604) → `Site/QuranController`. Shared reader partial `_reader.blade.php` (verses grouped by surah, font/transliteration/translation controls). Quick-jump Juz/Hizb/Page selectors on the index + autocomplete juz/hizb/page jumps. Tafsir intentionally omitted on these reader views (avoids per-verse N+1; tafsir stays on the surah page). E2E verified.
- **Hizb data fix:** `hizb_number` was unpopulated (all = 1). Added `php artisan quran:backfill-hizb` (derives hizb = ceil(rub_el_hizb/4) from the correct rub data; re-run after any Quran import). Backfilled to 1-60.

### [x] RTL — Arabic verse alignment fix — **DONE**
- Verse text used `text-end` + `dir=rtl` → rendered **left**-aligned (logical `end` = left in RTL). Changed to `text-start` (→ right) in `_reader.blade.php` and `surah.blade.php`.

### [x] Pre-existing admin bugs found during E2E & fixed — **DONE**
- `admin/quran` (verses) 500: inherited `QuranService::paginate()` used the *surah* repository → view's `$verse->translations` was null. Added `QuranService::paginateVerses()` (verse repo + surah/translations eager-load, no N+1); controller uses it + paginator `total()` for the count stat.
- `admin/surahs` 500: `SurahRepository::getTotalVersesCount(): int` returned PDO `sum()` as string → cast to `(int)`.

### [ ] Q-P2-01 🔴 Word-by-word corpus (`quran_words`) (G3/F3)
- ~77k rows from QUL: text_uthmani/indopak, transliteration, per-word translation (EN first), root, lemma, grammar, glyph code, line/page. Prereq for Q-P2-02 and Q-P3-01.

### [ ] Q-P2-02 🔴 Audio segment timings + word highlighting (G4/F4)
- `quran_audio_segments` (recitation_id, verse_id, word_position, start_ms, end_ms). Start with one reciter. Drives synced word highlighting.

### [ ] Q-P2-03 🟠 Audio repeat/loop + speed controls (F10)
- Frontend-only (Alpine) on top of existing audio. Repeat verse ×N / range / surah; 0.75–2× speed.

### [-] Q-P2-05 🟢 Multiple side-by-side translations/tafsir (F1/F2) — **DEFERRED** (see [Q-UP1](#q-up1)).

---

## PHASE 3 — Mushaf fidelity & discovery

### [ ] Q-P3-01 🟠 Mushaf page mode (QCF/QPC glyph fonts) (F6) — needs Q-P2-01 line/glyph data + 604 page fonts.
### [ ] Q-P3-02 🟠 Tajweed colored script + legend (F7).
### [ ] Q-P3-03 🟠 IndoPak script variant (F8) — uses `text_indopak` from words table.
### [ ] Q-P3-04 🟠 Topics / themes ontology (F12) — **NOTE: an `app/Domain/Topics/` module already exists — extend it, verify before adding tables.**
### [ ] Q-P3-05 🟢 Related / similar verses (F13).

---

## PHASE 4 — Account-bound features (respect deferrals)

### [ ] Q-P4-01 🟠 Server-side bookmarks (F14) — verify existing favorites/interactions system first; reuse. IDOR-safe.
### [ ] Q-P4-02 🟠 Personal notes per verse (F14).
### [ ] Q-P4-03 🟠 Collections (F14).
### [ ] Q-P4-04 🟠 Last-read position sync (F14).
### [-] Q-P4-05 🟢 Reading goals/streaks (F15) — **DEFERRED (gamification)**.
### [-] Q-P4-06 🟢 Community reflections (F16) — **DEFERRED (comments)**.
### [ ] Q-P4-07 🟢 Share verse as image + rich copy (F17).
### [ ] Q-P4-08 🟢 Offline audio download (F18).

---

## Cross-cutting

### [x] Q-AUDIO — Multi-reciter audio playback — **DONE**
- Surah-level player now switches with the reciter dropdown (per-reciter `surahAudioUrl` from `quran_recitations`; reset on change). Per-verse audio already used the everyayah resolver per reciter. E2E verified (Mishary→Abdul Basit URL switch, playing).

### [x] Q-FULLSURAH — Surah reader: full surah, no pagination, mushaf-page dividers — **DONE**
- `Site/QuranController@surah` loads all verses (no `page`/`per_page`); verses grouped by `page_number` with mushaf-page dividers in both verse-card and reading views. Short URL `/quran/{n}`. Prev/next surah nav. Verse-jump anchors (`#v{n}`). Scroll-based progress bar. Note: Al-Baqarah payload ~2.9MB (all 3 tafsirs inline) — future optimization: lazy-load tafsir.

### [x] Q-PLAYER — Premium audio player (ui-ux-pro-max) — **DONE**
- Gradient header (now-playing surah + reciter), seekable progress bar with current/total time (tabular), prev/play/next-surah transport, playback-speed cycle (0.75–2×), reciter selector, auto-advance to next surah on end. E2E verified.

### [x] Q-CORRECTNESS — Surah text matches quran.com — **DONE**
- Found: first verses carried the Basmala (2:1 was "بِسْمِ… الٓمٓ"; correct "الٓمٓ"). Added `php artisan quran:sync-arabic` (Quran.com Uthmani API) and synced all 6,236 verses; 2:1 now "الٓمٓ", text matches quran.com.

### [x] Q-READING — Verse-by-verse ↔ continuous reading toggle — **DONE**
- Added to surah page + juz/hizb/page reader (`_reader.blade.php`); flowing whole-surah Arabic with inline verse ornaments. Also fixed `/quran/{n}` short URL (404 before). 5-language i18n.

### [x] Q-UP1 — Multi-source TAFSIR — **DONE (quran.com-style)**
- ✅ Schema: `quran_tafsir_resources` + `resource_id` on `quran_verse_tafsirs`; UNIQUE swapped to `(verse_id, resource_id)` (MySQL-guarded, SQLite-safe). `TafsirResource` model + `VerseTafsir::resource()`.
- ✅ Import: `php artisan quran:import-tafsir {id}` (Quran.com API v4). Imported **3 sources**: Al-Muyassar (ar, 6,236), Ibn Kathir abridged (en, 1,895), As-Saadi (ar, 6,177).
- ✅ Reader: tafsir-source selector on the surah page; per-verse tafsir batch-loaded by resource (no N+1); `dir="auto"` for EN/AR. E2E verified (switch → text changes).
- ✅ Admin: `tafsir-resources` CRUD module (index/create/edit/delete) at `/su/portal/v1/admin/tafsir-resources`, RTL + dark mode. E2E verified.
- Note: translations (F1) still single-per-language — see Q-UP1b.

### <a name="q-up1-translations"></a>[ ] Q-UP1b — Multi-source translations (un-defer F1)
- Add `quran_translation_resources` + `quran_tafsir_resources`; add `resource_id`; swap `UNIQUE(verse_id, lang)` → `UNIQUE(verse_id, resource_id)`; backfill existing rows to a default resource per lang. Reversible. No Phase-1/2 task blocks this.

### [ ] Q-X-01 — Public API parity (F20)
- Add: `?include=words`, audio segments, topics, related verses, **search/suggest (this session)**.

### [ ] Q-X-02 — Gates on every task
- Explicit `->select([...])`; no N+1; `withCount`; nullable+default columns on big tables; `EXPLAIN` at full volume; cache keys via `QuranCacheKeys`; tests success+401+404+422 (+403 for account); `pint` + `deptrac` + `php artisan test` green.

---

## Import dependency order (QUL / API v4)
footnotes (Q-P1-03) → tafsir langs (Q-P1-04) → words (Q-P2-01) → audio segments (Q-P2-02) → QCF fonts/layout (Q-P3-01) → tajweed/IndoPak (Q-P3-02/03) → topics/relations (Q-P3-04/05).

> Keep this file updated as gaps close (CLAUDE.md §10).
