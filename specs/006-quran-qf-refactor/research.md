# Phase 0 Research: Quran Module Rebuild on the QF Content API

Findings read from the repository and from the QF documentation portal on 2026-09-17
(96 endpoint pages + quickstart + font tutorial, via Chrome). No NEEDS CLARIFICATION
remains; R15 carries a runtime verification step.

## R1. Migrations: delete, edit, create

**Finding**: 28 migration files create or mutate `quran_*` tables (two are deliberate
no-ops, one has a `down()` that throws, one calls `Artisan::call('quran:group-reciters')`
during migration). Six shared migrations also touch quran tables:
`2025_10_31_075951_add_search_indexes_to_tables` (raw `CREATE INDEX` on quran_verses),
`2026_03_10_100003/100007/100008` (create/populate/drop-json for `reciter_translations`),
`2026_03_17_000001_add_composite_performance_indexes` (`idx_qvt_lang_verse`),
`2026_08_17_120000_add_missing_hot_path_indexes` (tafsir FULLTEXT),
`2026_08_18_000003_add_location_foreign_keys` (`quran_surahs.revelation_location_id`).

**Decision**: delete all 28 Quran-only files (the two `ai_inquir*` files stay). Edit the
six shared files to remove their quran blocks, except the locations one which is
re-targeted to `quran_chapters.revelation_location_id`. Create nine new files dated
`2026_09_18_0000NN` in FK order: (01) chapters + chapter_translations, (02) sections,
(03) verses, (04) words + word_glosses, (05) languages + resources +
resource_translations, (06) translations + tafsirs + chapter_infos, (07) mushafs +
mushaf_pages + mushaf_words, (08) audio_files + chapter_audio_files +
chapter_audio_timestamps, (09) verse_hadith_references + reflections (+references) +
articles (+translations) + sync_states + sync_runs, then (10) playlists rewrite. Every
FULLTEXT and `ALTER … ADD CONSTRAINT` is behind `DB::getDriverName() === 'mysql'`.
`word_glosses` references `resources`, so file 04 creates `words` only and file 05
appends `word_glosses` after `resources`.

**Rationale**: fresh rebuild (owner decision) means no environment keeps its
`migrations` rows for these tables, so create-then-alter history has no value; a clean
FK order removes the "create then constrain later" pattern that produced the no-op files.

**Alternatives rejected**: (a) keep old files and add a 29th "drop everything" migration
— leaves 28 dead files and an irreversible chain; (b) one giant migration — harder to
review and to reverse partially on the test DB.

## R2. QF client, token lifecycle, rate limiting

**Finding**: `config/services.php` already holds `quran.auth_url` (production token
endpoint), `client_id` = `QURAN_API_KEY`, `client_secret` = `QURAN_API_SECRET`,
`api_url` = production `/content/api/v4/`. The old `QuranSyncService` cached the token
under `quran_api_access_token` for 3500 s. The Hadith module's `SunnahApiClient` is the
house pattern for a rate-limited importer client (sleep between calls, paged fetch,
`RuntimeException` on malformed payloads).

**Decision**: new `App\Domain\Quran\Import\QuranFoundation\{TokenProvider, Client}`.
`TokenProvider` stores `{access_token, expires_at}` in the default cache under
`quran:qf:token`, refreshes when `< 30 s` remain, serialises refreshes with
`Cache::lock('quran:qf:token:lock', 10)`, and never logs the token. `Client` sends
`x-auth-token` + `x-client-id`, throttles to `config('quran.qf.rate_limit_per_second')`
(default 5) with a monotonic last-call clock, retries **once** on 401 after
`TokenProvider::forget()`, retries 429/502/503/504 up to 3× with exponential backoff +
jitter (honouring `Retry-After`), throws `QfApiException` typed by the `type` field on
400/403/404/422 without retry. `Endpoints` wraps every A1–A7 call in a typed method
returning arrays; mappers turn arrays into `final readonly` DTOs. New
`config/quran.php` (`qf.env` prelive|production, `qf.auth_url`, `qf.api_url`,
`qf.reflect_url`, `qf.client_id/secret` read from the existing env names, rate limit,
timeouts, asset paths, default mushaf 1, default resources per locale).

**Alternatives rejected**: reusing `Http::retry()` alone — it cannot express
"refresh token then retry once" or typed non-retryable errors; keeping
`QuranSyncService` — it rotates fake User-Agents and mixes sync with parsing.

## R3. Which endpoint feeds which table (call budget)

| Data | Endpoint | Calls | Notes |
|---|---|---|---|
| Chapters + translated names | `/chapters?language={ar,en,fr,tr,ur}` | 5 | `pages[0..1]` → `first_page/last_page` |
| Sections (5 axes) | `/juzs`, `/hizbs`, `/rub_el_hizbs`, `/manzils`, `/rukus` | 5 | `verse_mapping` stored as JSON |
| Pages (default mushaf) | `/pages?mushaf=1` | 1 | other mushafs come from the mushaf snapshot |
| Verse scripts | `/quran/verses/{script}` for 11 scripts | 11 | whole Quran per call; `v1_image` gives `image_url` |
| Verse metadata (axis numbers, sajdah, image width, verse_index) | `/verses/by_chapter/{n}?per_page=50&fields=…` | ≈ 250 | paginated |
| Words (+ glyphs, positions, per-word audio, wbw text for one language) | `/verses/by_chapter/{n}?words=true&word_fields=<all>&language=X&per_page=50` | ≈ 250 per language | glosses for ar/en/fr/tr/ur → 1,250 calls; replaced by wbw snapshots when the resource exists |
| Languages | `/resources/languages` | 1 | |
| Catalogue | `/resources/{translations,tafsirs,chapter_infos,verse_media,recitations,chapter_reciters,recitation_styles}` (+ `?language=` × 5 for translated names) + `/resources/*/{id}/info` | ≈ 35 + one per resource | info is nullable |
| Translations / tafsirs / wbw / mushafs / ayah audio / chapter audio / articles | `/resources/sync` + `/resources/snapshots/{group}/{id}` | 1 + one per enabled resource | the bulk path; `per_page=100` |
| Chapter infos | `/chapters/{id}/info?resource_id=&include_resources=true` | 114 per enabled resource | |
| Chapter audio timestamps | `/chapter_recitations/{r}/{c}?segments=true` | 114 per chapter reciter | |
| Hadith references | `/hadiths/{key}/references` | 6,236 | phase 7 |
| Reflections | `/quran-reflect/v1/posts/feed?tab=qdc&filter[verifiedOnly]=1&filter[references][0]…` | 6,236 × pages | phase 7, `post.read` |
| Verse images | `image_url` per verse | 6,236 downloads | `download-assets --images` |
| Fonts | CDN patterns (design §A8) | 604 × {v1,v2} + 2 Unicode | `download-assets --fonts` |

**Decision**: prefer whole-corpus endpoints and snapshots; paginate only where no bulk
path exists; every importer accepts `--from/--to` chapter bounds and resumes.

## R4. Identity: QF ids as primary keys for the corpus

**Finding**: QF verse ids are 1–6236 in mushaf order, word ids are stable across scripts
and mushafs (mushaf snapshots reference `word_id`), page/section rows carry ids.

**Decision**: chapters, verses, words, sections (own PK but unique `(type, number)`),
mushafs, mushaf pages use QF ids as PK. Resource rows use our own PK + nullable unique
`qf_id` per kind so manual resources can exist. Content rows (translations, tafsirs,
audio files, …) use our own PK + nullable `qf_id` for row-level sync keys.

**Rationale**: sync mutations address rows by `(group, resource_id, record_type,
record_key)` — record_key is the QF row id; keeping it as a column makes upsert trivial
without making manual rows impossible.

## R5. One `quran_resources` table and one `quran_sections` table

**Finding**: the seven QF catalogues share `{id, name, author_name, slug,
language_name, translated_name}`; recitation catalogues add `style` and (chapter
reciters) `qirat`. The five section axes share `{number, verse_mapping,
first_verse_id, last_verse_id, verses_count}`; rukūʿ adds `surah_ruku_number`.

**Decision**: single `quran_resources` with a `kind` enum and nullable kind-specific
columns; single `quran_sections` with a `type` enum. Admin exposes one menu entry per
kind via route defaults on one controller (`ResourceController@index` with
`{kind}`), so each kind still has its own CRUD screens.

**Alternatives rejected**: eight tables and eight controllers with identical code
(constitution IX); polymorphic `resourceable` — nothing needs it.

## R6. Sync semantics: canonical filter, token, locked rows

**Decision**: the canonical filter is built from the DB (`kind → group` map, enabled
resources sorted by group then id, joined `group:id,id;…`). `quran_sync_states` keys on
that string; enabling a resource changes the filter, which forces a bootstrap for the
new filter (the API requires the same filter per token). Mutations are applied inside
one transaction per resource; a `RESOURCE_CREATE|INVALIDATE` snapshot replace deletes
and reinserts that resource's rows except rows with `is_locked = 1`, which are kept and
counted in `rows_skipped_locked`. `RESOURCE_DELETE` sets `is_active = 0` and stores
`unavailable_reason` in `metadata`. `next_sync_token` is persisted only when
`has_more = false` and every page applied without exception.

## R7. Audio: URL normalisation, mp3quran fetcher, local download

**Finding**: ayah files come back relative (`Alafasy/mp3/001001.mp3`) or absolute
(`https://verses.quran.foundation/...`); chapter files are absolute
(`download.quranicaudio.com`). Word audio is relative to `https://verses.quran.foundation/`.
mp3quran v3: `GET /api/v3/reciters?language=ar|eng` → `reciters[].moshaf[] {id, name,
server, surah_total, surah_list, moshaf_type}`; chapter file = `{server}/{surah:03}.mp3`;
timings `GET /api/v3/ayat_timing/read/{read_id}/{surah}` → `[{ayah, start_time, end_time}]`
(used today by `Mp3QuranCatalogue` / `AyahTimingService`).

**Decision**: `AudioService::absolutize()` prefixes relative paths with
`config('quran.qf.audio_base')`. `ReciterFetcher` contract with `QfReciterFetcher` and
`Mp3QuranReciterFetcher`; `ReciterFetchService` orchestrates lookup / data / files /
timestamps and writes `last_fetch_report`. Local download (`store_audio_locally`)
streams each file to `storage/app/public/quran/audio/{resource_id}/{chapter:03}.mp3`
with size cap (`config('quran.audio.max_file_mb')`, default 80) and `audio/mpeg`
sniffing; the row's `url` becomes the public-disk URL. Jobs run on the `imports`
queue with `timeout = 3600` (memory: queue:listen default 60 s killed long syncs).

**Alternatives rejected**: an `AudioProvider` abstraction in the player (the old three-
provider model) — the player only needs a URL and timestamps.

## R8. Fonts and images stored locally

**Decision**: `download-assets` writes fonts to `public/fonts/quran/{v1,v2}/woff2/p{n}.woff2`,
`public/fonts/quran/hafs/UthmanicHafs1Ver18.woff2`, `public/fonts/quran/indopak/…woff2`
(woff2 only; ttf/woff skipped — every supported browser takes woff2) and verse images to
`storage/app/public/quran/verse-images/{s}_{v}.png` with `quran_verses.image_path`.
The reader emits `@font-face` per visible page lazily (`FontFace` API) from the local
path; a missing local font falls back to the CDN URL so the page never breaks mid-import.
Sizes ≈ 60 MB per QCF version, ≈ 200 MB images; both are excluded from git
(`.gitignore`) and listed in the deploy checklist.

## R9. Local search

**Decision**: `SearchService` implements QF's two modes over MySQL FULLTEXT:
`quran_verses (text_uthmani_simple, text_imlaei_simple)`, `quran_translations (text)`,
`quran_chapter_translations (name)`. Quick mode: parse `s:v`, `s`, `juz N`, `page N`,
chapter-name prefix (any locale) → `navigation[]`; FULLTEXT `IN BOOLEAN MODE` for verses
limited to `versesResultsNumber`. Advanced mode: paginated, `translation_ids` filter,
`exact_matches_only` → phrase search, `highlight` via `<em>` wrapping of matched terms.
Arabic input is normalised (hamza forms → ا, ى → ي, strip tashkeel) before matching
against the simple scripts. The QF Search API is not called at runtime.

## R10. Dropping the CQRS Application layer

**Finding**: 83 files (18 commands, 22 queries, 43 handlers) consumed only by
`Site\QuranController` and `Site\HomeController`; admin controllers already bypass them.

**Decision**: services + repositories like every other module. Recorded in Complexity
Tracking as a deliberate deviation from the CLAUDE.md note; CLAUDE.md §6 is rewritten.

## R11. Cross-module touchpoints

| Touchpoint | Today | Change |
|---|---|---|
| `FavoriteServiceProvider` | `QURAN → Verse` (URL uses `revelation_order` — bug), `RECITER → ReciterProfile` | `QURAN → Chapter/Verse` URL by `chapter_id` + `#verse-{key}`; `RECITER → Resource` (kinds recitation/chapter_reciter) |
| `TopicsServiceProvider` morph map | `TopicItemType::VERSE → Verse::class` | same class name, new namespace path unchanged (`App\Domain\Quran\Models\Verse`) |
| `ContentReferenceLookup` impl (article embeds, `ArticleModuleController@surahs|verses`) | reads `Surah`/`Verse` | reads `Chapter`/`Verse`, labels from chapter translations |
| `SharedServiceProvider` LocationUsageRegistry | `quran_surahs.revelation_location_id` | `quran_chapters.revelation_location_id` |
| `AppServiceProvider` | dashboard stats provider + 4 export adapters | rewritten adapters (chapters, verses, translations, tafsirs, reciters) |
| `deptrac.baseline.yaml` | Quran `CreateReciterData`/`UpdateReciterData → Http\Requests` | removed (controllers build DTOs from validated arrays); no new entries |
| `config/artisan-commands.php` | 13 old commands | replaced by the new command list |
| Hadith linking | none | new Shared `HadithReferenceLookup` (interface in Shared, impl in Hadith domain, bound in `AppServiceProvider`) |

## R12. Live, Asbab, playlists

**Decision**: `public.quran.live` (`/quran/live`) becomes a 301 to the existing
`public.media.live.index` (`/media/live`); `live.blade.php` under quran is deleted (the
Media module already has its own live views). `/quran/asbab-al-nuzul` is removed (404)
and the `asbab.blade.php` + revelation-context tables/admin are deleted. Playlist items
re-key to `chapter_id` + `resource_id` (chapter reciter); `PlaylistService::assign()`
picks among active chapter reciters with a file for that chapter.

## R13. Rollout

**Decision**: quickstart runbook mirrors 005: allowlist assertion → dump `quran_%` +
`reciter_translations` → drop by explicit name (FK checks off) → delete their
`migrations` rows by name → `php artisan migrate` → `quran:qf:doctor` →
`quran:qf:bootstrap` → enable resources in admin → `quran:qf:sync --bootstrap` →
`quran:qf:download-assets`. Prod follows the memory "prod DB resync over migrations"
(purge + resync from dev, not compensating migrations). Orphaned `favorites` rows for
types `quran`/`reciter` are deleted in the same window.

## R14. Queued work

**Finding**: `RunArtisanCommandJob` already runs registry commands from the admin
settings page with progress polling; `QUEUE_CONNECTION=database`.

**Decision**: every import/sync command is registered in the `quran` group as
`queued => true` (bootstrap and asset download `danger => true`); the reciter fetch
actions dispatch `FetchReciterFromSourceJob` (queue `imports`, timeout 3600, unique per
resource) and the admin polls the resource's `last_fetch_report`.

## R16. Public API security: API key + secret, rate limiting, CORS

**Finding**: `/api/v1` routes use Sanctum bearer tokens (`auth:sanctum` +
`abilities:api:access`) and `throttle:api` (60/min by user or IP); register, login and
health are anonymous. `config/cors.php` allows `api/*` with a hard-coded origin list and
`supports_credentials = true`. Nothing identifies the *calling application*.

**Decision**: add an application credential layer in Shared: `api_clients` table, an
`AuthenticateApiClient` middleware (`X-Api-Key` + `X-Api-Secret`, secret compared with
`hash_equals` against an HMAC-SHA256 hash under `APP_KEY`, client cached by key for
5 min, origin allow-list, `last_used_at` throttled), an `api.scope:{scope}` middleware,
a per-client `RateLimiter::for('api_client')` plus an IP limiter for failed
authentication, and `config/cors.php` driven by `CORS_ALLOWED_ORIGINS` with
`supports_credentials = false`. The middleware group wraps the whole v1 group so every
existing route (favourites, auth, search, hadith, seerah, media, quran) gains it in one
place; Sanctum stays for user-bound routes and runs after the client check. Admin CRUD
under Settings with a `manage api clients` permission; `api:client:create` for CLI.

**Rationale**: the owner requires key + secret on all public APIs; a per-app credential
also makes per-client rate limits and per-client CORS origins possible, which IP-based
limits cannot. HMAC hashing (not bcrypt) keeps per-request cost negligible; the cache
avoids a DB hit per call. Applying the group once in `routes/api.php` avoids drift.

**Alternatives rejected**: HMAC request signing (timestamp + body signature) — stronger
against replay but every consumer must implement signing; can be added later as a
per-client option without changing the table. Reusing Sanctum tokens as app keys — they
are user tokens, cannot express origins/limits, and must not be shared by an app.

## R15. Scope availability (verified at Phase 0 runtime)

**Decision**: `quran:qf:doctor` requests a token and calls `/chapters`,
`/hadiths/1:1/references` and `/quran-reflect/v1/posts/feed?limit=1`, printing
OK / `insufficient_scope` per capability without secrets. Phase 7 importers refuse to
run when their capability is not OK. This keeps phases 1–6 independent of scopes we may
not hold yet.
