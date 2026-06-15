# Quran Data — Editions, Tafsir & Reciter Audio

> Full migration of Quran translations + tafsir editions from the quran.ai MCP catalog
> (upstream: Quran.com API v4) into the DB, plus reciter audio management. Scope confirmed
> with the user: all editions for the 5 app languages (ar/en/fr/tr/ur); word-morphology
> deferred; reciter audio kept surah-level with an upgraded admin profile.

## Status legend
✅ done & verified · 🔄 in progress · ⬜ todo · ⏭️ deferred (out of agreed scope)

---

## A. Translation multi-edition foundation
- [x] ✅ `config/quran-editions.php` — verified catalog: 16 translation + 10 tafsir editions mapped to Quran.com numeric IDs, default-per-language flags.
- [x] ✅ Migration `…130001_create_quran_translation_resources_table` (slug, name json, author, language, direction, source, is_default, is_active, display_order).
- [x] ✅ Migration `…130002_add_resource_id_to_quran_verse_translations_table` — backfills existing langs to catalog-matching default resources, swaps unique `(verse_id,lang)`→`(verse_id,resource_id)` (MySQL-guarded, reversible).
- [x] ✅ `TranslationResource` model + `VerseTranslation` updated (resource_id fillable/cast/relation/scope).
- [x] ✅ `quran:import-translation {slug|id}` (Quran.com `/quran/translations/{id}`, idempotent upsert on verse+resource, one-default-per-lang).
- [x] ✅ `quran:import-editions` orchestrator (drives the whole catalog; `--translations` / `--tafsirs` / `--only=`).
- [x] ✅ Grounding-verified: imported Pickthall 1:5 byte-matches the MCP canonical.

## B. Data imported (run via `quran:import-editions`)
- [x] ✅ Translations: **18 resources / 112,248 rows** — en×7, fr×3, tr×2, ur×4 + ar source + transliteration. Defaults verified (en→Sahih, fr→Hamidullah, tr→Diyanet, ur→Junagarhi).
- [x] ✅ **Tafsir: COMPLETE** (disk freed, import finished). **13 editions / 63,627 rows** — Arabic ×9 (muyassar, ibn-kathir, tabari, qurtubi, baghawi, saadi, al-wasit + 2 legacy) and English ×4 (ibn-kathir, maarif-ul-quran, tazkirul + 1 legacy). Grounding-verified: `ar-muyassar` 1:2 matches the MCP canonical character-for-character.
- [x] ✅ **Total MySQL storage ≈ 261 MB** (`quran_verse_tafsirs` 200.8 MB · `quran_verse_translations` 56 MB · rest <6 MB), in db `the_last_messenger` @ 127.0.0.1:3308.

## C. Read-path reflection (frontend + API)
- [x] ✅ `VerseRepository` eager-loads `resource:id,is_default,language` with translations.
- [x] ✅ `VerseDTO::fromModel` resolves the lang-keyed map to the **default** edition deterministically (unit-tested, 3 cases).
- [x] ✅ Site `QuranController@surah`: batch maps `translations_by_resource` (no N+1) + `getTranslationResources()`; passes `translationResources` to the view.
- [x] ✅ `public/quran/surah.blade.php`: translation **edition switcher** (mirrors the tafsir switcher) — shown when a language has >1 edition; persists `selectedTranslationId`; falls back to language default. HTTP 200 verified.
- [x] ✅ API `Http/Resources/TranslationResource`: exposes `resource_id` + `edition{}` (whenLoaded).
- [x] ✅ Tafsir editions auto-reflect — the reader already reads `tafsirResources` + `verse.tafsirs[resource_id]`; new tafsir resources appear with no code change.
- [x] ✅ Lang key `quran.public.translation_edition` added to all 5 files.

## D. Admin — translation editions
- [x] ✅ `Admin/Quran/TranslationResourceController` (full CRUD, one-default-per-lang in a transaction).
- [x] ✅ Views `admin/quran/translation-resources/{index,create,edit,_form}` (Arabic-first, RTL, dark/light, mobile, flex-wrap).
- [x] ✅ Routes `admin.translation-resources.*` in `routes/admin/quran.php`.
- [x] ✅ Sidebar links for **translation-resources** + **tafsir-resources** (the latter was previously unlinked).
- [x] ✅ Admin lang keys added (ar+en full; fr/tr/ur minimal nav keys — the whole Quran admin block is ar/en-only, pre-existing).

## E. Admin — reciter audio profile  ✅ (background agent)
- [x] ✅ `RecitationRepository`/`RecitationService` (2-query merge, IDOR ownership check), `RecitationController` (store/update/destroy), `Store/UpdateRecitationRequest`, reciter `show` lists 114 surahs with add/edit/replace/delete + duration/size, routes `admin.reciters.recitations.*`, lang keys ×5. **9 feature tests pass.**

## F. Verification gates
- [x] ✅ Pint clean on all changed files.
- [x] ✅ Tests: DTO unit (3) + recitation admin (9) = 12 pass; Quran suite 48 pass / 3 skipped (MySQL FULLTEXT) — no regressions.
- [x] ✅ `composer deptrac` — 0 errors, 0 warnings (module isolation holds).
- [x] ✅ Frontend smoke: surah reader HTTP 200; translation-edition switcher present; default English = Saheeh International via live read path.

## G. Data-hygiene  ✅
- [x] ✅ Removed the 3 legacy duplicate tafsir resources, keeping the catalog slugs only: `ar-muyassar` (preserved the complete 6,236-row per-verse data over the range-grouped 5,278), `ar-saadi`, `en-ibn-kathir`. Now **10 tafsir editions, zero duplicates, zero orphan rows** (`verse_tafsirs` 63,627 → 50,277).

## H. Reader performance fix (regression the full tafsir import exposed)  ✅
- [x] ✅ The surah reader inlined **every** tafsir edition's full text for **every** verse into the page JSON — fine with 3 editions, but with 10 (Tabari etc.) it exhausted PHP's 128 MB limit (500 on large surahs). Switched tafsir to **lazy per-edition loading**: new endpoint `GET /quran/surah/{n}/tafsir/{resourceId}` (`public.quran.surah.tafsir`, cached) + Alpine `loadTafsir`/`tafsirCache`. surah/2 now 200 (2.5 MB page + 434 KB on-demand tafsir) vs the prior memory-exhaustion 500. Translations stay inline (small).
- [x] ✅ Reader hardening: `x-init="boot()"` guarantees init runs; a guard resets a **stale/removed tafsir selection** in localStorage to a valid edition. Verified in-browser: stale id auto-corrects, tafsir auto-loads and renders on page open.
- [x] ✅ **Translations made lazy too** (symmetry with tafsir): the surah page no longer inlines all 17 translation editions per verse. Only the **default translation per language** is inlined (instant first render, no fetch); non-default editions load on demand via `GET /quran/surah/{n}/translation/{resourceId}` (`public.quran.surah.translation`, cached). **Result: surah/2 payload 2.5 MB → 0.79 MB (−69%)**; a non-default edition is 68 KB on demand. The cached surah payload (file cache, ×114 surahs×locales) shrinks proportionally — the storage win. Verified in-browser: default = instant/no-fetch, switching to Pickthall lazy-fetches only that edition and renders.

### Architecture cleanup  ✅
- [x] ✅ Site reader is now **§4.1-compliant** — `Site/QuranController` has **0 direct Eloquent calls** and imports only services/handlers. Queries + caching moved into repositories: `RecitationRepository::getSurahAudioUrls`, `TafseerRepository::getActiveResources` / `getSurahTextByResource`, `VerseRepository::getActiveTranslationResources` / `getSurahTranslationByResource`. New `QuranReaderService` orchestrates them and returns plain locale-mapped arrays (controller never touches `Domain\Models`). 60 tests pass; reader verified in-browser.
- Note: `composer deptrac` stays at 290 violations — those are **pre-existing** `App\Domain\*\Actions → App\Models\*` deps (Blog etc.), the "Models" layer being the legacy `App\Models` namespace, **not** `App\Domain\Quran\Models`. This refactor neither caused nor fixes them (separate cleanup).

### Tafsir loads per verse, on demand  ✅
- [x] ✅ Replaced the whole-surah tafsir fetch with a **per-verse endpoint** `GET /quran/verse/{verseId}/tafsir/{resourceId}` (`public.quran.verse.tafsir` → `TafseerRepository::getVerseText`, a single unique-index lookup, **not cached** so no large tafsir blobs sit in the cache — a storage win too). The reader fetches a verse's tafsir **only when its accordion is opened**; `boot()` fetches nothing. Switching the tafsir source re-fetches only the currently-open verses. Worst case (Tabari × one verse) is ~70 KB vs the old 16.7 MB whole-surah blob. Toggle is always visible per verse with loading / empty (`no_tafsir`) states. Verified in-browser: 0 fetched on load, exactly 1 verse fetched on open.

---

## Follow-ups / deferred

### 4 Arabic tafsir editions NOT on Quran.com API v4
Present in the MCP corpus but unavailable via the bulk REST path
(`config('quran-editions.unavailable_via_api')`):
`ar-jalalayn`, `ar-kashaf` (Al-Kashshāf / Zamakhshari), `ar-nathm-aldurar` (Al-Biqāʿī),
`ar-tahrir-wa-tanwir` (Ibn ʿĀshūr).
**To import:** either (a) per-verse via the MCP `fetch_tafsir` tool (6,236 calls/edition — feasible
as a one-off batched job, not a live request), or (b) an alternate dataset (e.g. alquran.cloud has
`ar.jalalayn`; github `spa5k/tafsir_api` covers several). Each just needs a `TafsirResource` row +
the per-verse text keyed by `(verse_id, resource_id)`.

### Deferred by agreed scope
- ⏭️ Word-level morphology / concordance / paradigm module (MCP `fetch_word_*`, ~77k words).
- ⏭️ Per-ayah reciter audio (everyayah deterministic URLs) — surah-level kept.
- ⏭️ Translations for languages the app does not render (de, es, he, id, it, ja, ru, sv).

### Test-driver note
Multi-edition uniqueness `(verse_id, resource_id)` is MySQL-only (the swap is driver-guarded, matching
the existing tafsir migration). On the SQLite test driver the original `(verse_id, lang)` unique
remains, so feature tests must not insert two same-language editions for one verse; the DTO
default-selection logic is covered by pure unit tests instead.
