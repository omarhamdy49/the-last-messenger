# Feature Specification: Quran Module Rebuild on the Quran Foundation Content API

**Feature Branch**: `006-quran-qf-refactor`
**Created**: 2026-09-17
**Status**: Draft — derived from the approved design in [design.md](./design.md) (the owner reviews and comments there)
**Input**: "Deep analysis of the Quran Foundation Content API, then a full refactor of DB, codebase, commands, admin blades and public pages so the platform has every feature the API offers, with full management and CRUD controls for everything. Keep only the public pages and enhance them."

## Context

The current Quran module mixes four external sources (quran.com v4, everyayah, mp3quran,
Wikidata), carries two generations of reciter model, 28 migrations (several no-op or
irreversible), an 83-file CQRS layer used by one controller, and exposes a fraction of
what the Quran Foundation (QF) Content API v4 offers (no word-by-word, no mushaf fonts,
no tajweed, no chapter info, no ayah/word timestamps, no sync, no hadith links).

The owner decided (2026-09-17): rebuild everything behind the public pages from scratch
on the QF Content API, keep and enhance the public Blade pages, keep Ask-Quran AI and
visitor playlists, move Live to the Media module, drop Asbab al-Nuzul and reciter
merging, use QF as the only bulk source (plus per-reciter on-demand fetch from QF or
mp3quran, and manual uploads), rebuild environments fresh with no data carried over,
store all data locally (text, layouts, catalogues, timestamps, images, fonts) and
stream audio from source unless an admin downloads it.

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Operator bootstraps the full Quran corpus from QF (Priority: P1)
An operator with production QF credentials runs one bootstrap command and ends with
114 chapters, 6,236 verses in 11 scripts, all words with glyphs and positions, every
navigation axis (juz, hizb, rubʿ, manzil, rukūʿ, page), languages, and the complete
resource catalogue (translations, tafsirs, chapter infos, recitations, chapter
reciters, verse media) marked "available, not enabled".

**Acceptance**: (1) every importer reports reconciled counts and aborts on parity
failure; (2) re-running is a no-op; (3) no secret appears in logs; (4) a 401 is retried
once after a token refresh, a 429 backs off, a 403 stops.

### User Story 2 — Editor enables content and keeps it fresh (Priority: P1)
An editor enables a translation, a tafsir, a chapter-info source, a word-by-word
language, a mushaf, an ayah recitation and a chapter reciter from the catalogue. A sync
run fetches their full copies; a nightly run applies only changes. Rows the editor
edited are locked and never overwritten; skipped rows are itemised in the run report.

### User Story 3 — Editor manages every Quran entity in the admin (Priority: P1)
Full CRUD where the entity is editorial (resources of every kind, translation/tafsir
rows, chapter infos, audio files, chapter audio files, timestamps, hadith references,
reflections, articles, languages, playlists, AI inquiries) and index/show/edit for the
fixed corpus (chapters, verses, words, sections, mushaf pages). Every form has five
language tabs, RTL-correct layout, and rejects unknown fields.

### User Story 4 — Editor adds a reciter from a source by reference id (Priority: P2)
On the reciter form the editor picks a source (Manual, Quran Foundation, mp3quran),
enters the source's id, presses Lookup and sees the name, style, qirat and file count.
After saving, "Fetch data", "Fetch audio files" and "Fetch timestamps" run as queued
jobs with progress and an itemised report; "store audio locally" downloads files to the
public disk, otherwise URLs are stored and streamed.

### User Story 5 — Visitor reads, listens and studies (Priority: P1)
The kept public pages work on the new data and gain: script switcher (Uthmani, simple,
Imlaei, IndoPak, Nastaleeq, QPC Hafs, tajweed-coloured, QCF V1/V2 page fonts), mushaf
page view with lines, word-by-word popovers with per-word audio, multiple simultaneous
translations with footnotes, multiple tafsirs, chapter info panel, ayah- and
surah-reciter players with ayah/word highlighting and click-to-seek, sajdah and section
markers, verse images, random verse, section pages for every axis, command-bar search
and a results page, reciter catalogues with style/qirat filters, hadith references and
reflections panels. Every page renders in Arabic first and in all five locales.

### User Story 6 — API consumer reads QF-shaped data from our server (Priority: P2)
The bearer-token API exposes the same route families and payload shapes as the QF
Content API (chapters, verses by every axis, sections, pages, resources, raw scripts,
translations, tafsirs, recitations, chapter recitations, audio timestamps, hadith
references, search), served only from our database.

### User Story 6b — Every public API call is authenticated, rate-limited and CORS-controlled (Priority: P1)
An integrator receives an API key and a one-time secret from the admin; every call to
`/api/v1/*` (except health) must present both headers, is limited per client per minute,
is refused from origins outside the client's allow-list, and is scoped (a Quran-only
client cannot read other modules). Admins can rotate secrets, change limits and origins,
and revoke a client, with immediate effect.

### User Story 7 — Nothing else on the site breaks (Priority: P1)
Favourites, article verse/surah embeds, topics, locations, the dashboard tile, exports,
the admin command runner, playlists and Ask-AI keep working; Live is reachable under
Media; removed pages return 404/301 as specified.

### User Story 8 — Environments start fresh on the new shape (Priority: P1)
Dev and prod are rebuilt by an allowlist-guarded scoped drop, `migrate`, bootstrap and
asset download — never `migrate:fresh`.

### Edge Cases
- QF returns relative audio URLs → stored absolute. QF `duration` may be negative → stored as-is, displayed as `to - from`.
- A QF resource disappears (`RESOURCE_DELETE`) → hidden, not deleted; admin sees the reason.
- Mushaf-aware page numbers can exceed 604 for non-default mushafs.
- Word `char_type` `end`/`pause`/`sajdah` markers render with the Unicode font, never QCF.
- Reciter present in both QF and mp3quran → two resources; the admin may hide one.
- Locked row + sync snapshot replace → the lock wins, the skip is reported.
- Scope not granted (`post.read`, hadith endpoints) → importer fails with a clear
  `insufficient_scope` message and nothing is written.

## Requirements *(mandatory)*

### Functional Requirements
- **FR-001** All `quran_*` tables and `reciter_translations` are replaced by the 23-table model in `data-model.md`; `ai_inquiries*` are untouched.
- **FR-002** Corpus tables (chapters, verses, words, sections, mushafs, pages, mushaf words) use QF ids as primary keys and have no create/delete in the admin.
- **FR-003** One `quran_resources` catalogue holds every resource kind with `qf_id`, `source` (qf|manual), `external_source`/`external_ref_id`, activation, default, order, sync flags and translated names.
- **FR-004** A QF client with cached client-credentials token (refresh 30 s early, single in-flight refresh), `x-auth-token`/`x-client-id` headers, 401 retry-once, 429/5xx bounded backoff, typed errors, configurable rate limit; production URLs by default.
- **FR-005** Importers for structure, verses (11 scripts), words (+ glosses per language), catalogue (+ info), chapter infos, timestamps (+ segments), hadith references, reflections, assets (verse images, fonts); each idempotent, chunked, dry-run, count-reconciled.
- **FR-006** Content Sync: bootstrap and incremental runs over enabled resources, canonical filter, token stored only from the final page, every mutation type applied, snapshot replace transactional, run log with itemised skips.
- **FR-007** Per-reciter source fetch (QF or mp3quran) by reference id: lookup preview, fetch data/audio/timestamps as queued jobs, optional local download with mime/size checks, idempotent, reported.
- **FR-008** Admin CRUD matrix per design §C5 with permissions `view quran`, `edit quran`, `sync quran`; FormRequests reject unknown fields; typed DTOs cross the controller→service boundary.
- **FR-009** Public read stack: repositories with explicit selects, versioned cache keys, `ReaderService` payload for the kept views, no N+1 on any list route.
- **FR-010** Public page enhancements per design §C7; Live moves to `public.media.live.index` with a 301 from `/quran/live`; Asbab page removed.
- **FR-011** Public API per `contracts/public-api.md`, served from the DB; old 11 routes removed.
- **FR-012** Local search (quick + advanced) over verses (simple scripts), translations and chapter names with MySQL FULLTEXT; QF Search API is not called at runtime.
- **FR-013** Cross-module re-pointing: favourites, article embeds, topics morph map, locations registry, dashboard stats, export adapters, command registry; hadith linking through a new Shared `HadithReferenceLookup` contract.
- **FR-014** Deletion of the old domain, commands, admin controllers/views, tests, `config/quran-audio.php`, `config/quran-editions.php`; new `config/quran.php`.
- **FR-015** Rollout runbook: allowlisted scoped drop, migrate, bootstrap, verify counts, download assets; orphaned favourites removed.
- **FR-016** Every user-facing string keyed in all five `quran.php` lang files; Quran text in Amiri/UthmanicHafs, UI in Cairo; logical CSS properties only.
- **FR-017** Every `/api/v1` route except `GET /health` requires a valid `X-Api-Key` + `X-Api-Secret` pair from an active, unexpired `api_clients` row; wrong or missing credentials → 401 with the uniform error body; secrets are stored hashed and never logged.
- **FR-018** Per-client rate limiting (`rate_limit_per_minute`, admin-editable) with `X-RateLimit-*` headers and a uniform 429 body; unauthenticated attempts limited by IP; search and mutation routes carry stricter limiters.
- **FR-019** CORS for `api/*` is configured from env (`CORS_ALLOWED_ORIGINS`) and per-client `allowed_origins`; disallowed origins fail preflight and get 403 on actual requests; credentials are not supported for API-key clients.
- **FR-020** Clients carry scopes; Quran routes require `quran` (or `*`); admins manage clients (create with one-time secret, rotate, edit, revoke) under Settings with a dedicated permission; a console command can create a client.

### Key Entities
Chapter, Section, Verse, Word, WordGloss, Mushaf, MushafPage, MushafWord, Language,
Resource (kinds: translation, tafsir, chapter_info, verse_media, recitation,
chapter_reciter, word_translation, word_transliteration), Translation, Tafsir,
ChapterInfo, AudioFile, ChapterAudioFile, ChapterAudioTimestamp, VerseHadithReference,
Reflection, Article, SyncState, SyncRun, Playlist, PlaylistItem, AiInquiry.

## Success Criteria *(mandatory)*
- **SC-001** Bootstrap on production credentials yields 114 / 6,236 / ≥ 77,430 words / 30 / 60 / 240 / 7 / 558 / 604 rows respectively, verified by the parity gates.
- **SC-002** A second bootstrap and a second sync run write zero rows.
- **SC-003** Every public API route has tests for 200, 401, 404 and 422; every admin mutation has 302/403/422 tests; `NoNPlusOneQueriesTest` covers every new list route.
- **SC-004** Every kept public page renders in `ar` and `en` with the new payload; the mushaf view loads page fonts from the local path; ayah highlighting follows the surah mp3 within 250 ms of the stored timestamp.
- **SC-005** `php artisan test`, `./vendor/bin/pint --test`, `composer deptrac` (no new baseline entries; Quran DTO→Request entries removed), `npm run build` all pass.
- **SC-007** Every `/api/v1` route has tests for missing key, wrong secret, wrong scope, disallowed origin and per-client 429; CORS preflight tests pass for an allowed origin and fail for a disallowed one.
- **SC-006** No file under `app/`, `routes/`, `resources/`, `config/`, `database/` references `quran_surahs`, `quran_reciters`, `everyayah`, `mp3quran` (outside the reciter fetcher), `RevelationContext`, `ReciterProfile`, `RecordingSource`, `Application\` (Quran).

## Assumptions
- Production QF client credentials exist in `.env` as `QURAN_API_KEY` / `QURAN_API_SECRET` and are approved for the `content` scope; `post.read` is requested separately.
- QF rate limits are undocumented; the client defaults to 5 req/s and importers are resumable.
- Fonts (~120 MB) and verse images (~200 MB) live on the public disk; a CDN is out of scope.
- Tafsir volume is controlled by which resources the editor enables.

## Dependencies
- Existing Shared building blocks: `HasLocalizedContent`, `BaseRepository` cache pattern, `ContentReferenceLookup`, `MediaLibraryLookup`, `FavorableRegistry`, `RunArtisanCommandJob`, `config/artisan-commands.php`, locations tables.
- Hadith module data (sunnah.com import) for resolving hadith references.
- Media module `LiveChannel` for the moved Live page.
