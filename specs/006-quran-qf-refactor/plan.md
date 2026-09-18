# Implementation Plan: Quran Module Rebuild on the Quran Foundation Content API

**Branch**: `006-quran-qf-refactor` | **Date**: 2026-09-17 | **Spec**: [spec.md](./spec.md) · design input: [design.md](./design.md)
**Input**: Feature specification from `/specs/006-quran-qf-refactor/spec.md`

## Summary

Replace the Quran module behind the public pages with a rebuild on the Quran Foundation
(QF) Content API v4: 23 tables keyed on QF ids, a rate-limited OAuth client, bootstrap
importers for the whole corpus (114 chapters, 6,236 verses × 11 scripts, ~83k words
with glyphs and per-word audio, every navigation axis, mushaf layouts), the QF Content
Sync protocol for translations / tafsirs / word glosses / mushafs / recitations /
articles, per-reciter on-demand fetch from QF or mp3quran by reference id, local
storage of images and fonts, a full admin CRUD surface with a sync dashboard, a
QF-shaped public API served from our DB, and the kept public pages enhanced with script
switching, mushaf fonts, tajweed, word-by-word, multi-translation/tafsir, chapter info,
ayah/word-synced audio, section pages, search, hadith references and reflections.
Ask-AI and playlists are kept and re-homed; Live moves to Media; Asbab, reciter
merging, the CQRS layer and the quran.com/everyayah/mp3quran/Wikidata importers are
deleted. Environments are rebuilt fresh via an allowlist-guarded scoped drop.

## Technical Context

**Language/Version**: PHP 8.5 (platform pinned 8.5.4)
**Primary Dependencies**: Laravel 13, spatie/laravel-permission, maatwebsite/excel, Blade + Alpine 3 + Tailwind, Laravel HTTP client (Guzzle). **No new package.**
**Storage**: MySQL 8 (InnoDB, utf8mb4); public disk for verse images, uploaded/downloaded audio, resource images; `public/fonts/quran` for QCF/Unicode fonts (git-ignored, deploy-copied)
**Testing**: PHPUnit via `php artisan test` on `the_last_messenger_test` (RefreshDatabase runs the new migrations); importer tests on `Http::fake()` fixtures under `tests/Fixtures/qf` and `tests/Fixtures/mp3quran`
**Target Platform**: Linux VPS (prod, supervisor queue workers), macOS dev; one Laravel app serves admin + public site + API
**Project Type**: web (single Laravel application, DDD modules under `app/Domain`)
**Performance Goals**: public reader pages ≤ today's query count (fixed per route, guarded by `NoNPlusOneQueriesTest`); API verse list ≤ 6 queries regardless of `per_page`; bootstrap ≈ 1,600 calls at 5 req/s (~10 min); nightly sync applies only deltas
**Constraints**: every `/api/v1` route (except health) behind `api.client` (key + secret), per-client rate limits and env/per-client CORS; no new packages; deptrac passes with no new baseline entries (two Quran entries removed); five locales, Arabic default, RTL-first; `npm run build` after Blade changes; audio streamed unless downloaded on request; secrets never logged; QF production endpoints; no runtime QF calls from public routes
**Scale/Scope**: 23 tables (~2–3 M rows with typical enabled resources), ~9 migrations new / 28 deleted / 6 edited, ~30 enums+models, ~16 repositories, ~20 services, 14 commands, ~16 admin controllers + ~45 admin views, 6 site controllers, ~15 kept public views enhanced + 4 new, 1 API controller family (~30 routes), ~60 test files, 5 lang files × 5 locales

## Constitution Check

*GATE: evaluated before Phase 0 and again after Phase 1 — both PASS with two tracked items.*

| Principle | Status | How this plan complies |
|---|---|---|
| I. Layered data flow | PASS | Controllers: FormRequest → one service call → view/Resource. Importers and sync live under `Domain/Quran/Import`, invoked by commands/jobs, writing through repositories; transactions inside repositories (`replaceResourceRows`, `upsertChunk`). `Site\QuranController` (1,050 lines) is split into Reader / Navigation / Reciters / Search / Ask controllers. |
| II. Query discipline | PASS | Every list query has an explicit select incl. FKs; words/translations/tafsirs/audio are eager-loaded with column lists keyed by `verse_id`, constrained to the requested resource ids; counts via `withCount`; `per_page` cast and capped at 50; indexes limited to those in `data-model.md`, each verified with `EXPLAIN` on the seeded test DB before merge; Arabic search hits FULLTEXT on the simple-script columns (no leading-wildcard LIKE). |
| III. Typed boundaries | PASS | `final readonly` DTOs for every write (`ResourceData`, `TranslationRowData`, …) built from validated arrays in controllers — the old `fromRequest(FormRequest)` pattern (a baseline violation) is not carried over. Enums for kinds, scripts, sections, sync types. `$fillable` on every model. |
| IV. Module isolation | PASS | Quran imports only `Shared`. Hadith links via new `Shared\Contracts\HadithReferenceLookup` (impl in Hadith, bound in `AppServiceProvider`); media via `MediaLibraryLookup`; favourites via `FavorableRegistry`; locations via Shared tables. Cross-module FKs: `quran_chapters.revelation_location_id` (existing pattern) only. |
| V. Arabic-first | PASS* | All new keys in five `quran.php` files; removed keys deleted; logical properties only; Amiri/UthmanicHafs for Quran text; every page verified in Arabic. *Translatable content uses translation tables (`quran_chapter_translations`, `quran_resource_translations`) per CLAUDE.md, not Spatie JSON — same documented deviation as 005. |
| VI. Tests | PASS | Per API route 200/401/404/422; admin 302/403/422 per mutation; importer idempotency + gates + lock behaviour on fixtures; sync mutation matrix; FULLTEXT and FK behaviour on MySQL; obsolete Quran tests deleted with their code. |
| VII. Security | PASS | All `/api/v1` routes require `X-Api-Key` + `X-Api-Secret` (hashed, `hash_equals`, cached lookup), scopes, per-client rate limits, IP limit on failed auth, CORS from env + per-client origins with `supports_credentials=false`; `view/edit/sync quran` permissions on every admin route; FormRequests reject unknown fields; uploads mime/size checked; downloaded audio size-capped and sniffed; rich text sanitised (tajweed tag allow-listed only for verse text); QF secret read from env, never logged; search routes throttled; sort/filter columns allow-listed. |
| VIII. Destructive ops | PASS | Rollout = named-table drop behind a database-name assertion after a dump (quickstart). No `migrate:fresh`, `--step`, `--database`. Old migration files are deleted only from a committed checkpoint. `download-assets --force` and `bootstrap` are `danger` in the command registry. |
| IX. Simplicity | TRACKED | One `ReciterFetcher` interface with two implementations (QF, mp3quran) — justified. `HadithReferenceLookup` interface with one implementation — same shape as `PersonLookup`/`MediaLibraryLookup`, required by IV. Single `quran_resources` and `quran_sections` tables instead of 13 near-identical ones. Reflections/articles tables ship only in phase 7 when their importer exists ("a table ships only when something populates it"). |
| X. Honest reporting | PASS | Every importer prints a reconciliation table and itemises failures/skips; sync runs persist counts; `RESOURCE_DELETE` reasons stored; `quran:qf:doctor` reports scope availability per capability. No empty catch. |

## Project Structure

### Documentation (this feature)

```text
specs/006-quran-qf-refactor/
├── design.md            # owner-reviewed design (input; comments go here)
├── spec.md              # derived specification
├── plan.md              # this file
├── research.md          # Phase 0 (R1–R15)
├── data-model.md        # Phase 1
├── quickstart.md        # Phase 1 — fresh + existing-environment runbooks
├── contracts/
│   ├── public-api.md    # v1.quran.* routes and shapes
│   ├── admin-forms.md   # admin routes and payloads
│   └── qf-import.md     # config, client, commands, sync algorithm, fetchers, fixtures
└── tasks.md             # /speckit.tasks (not created here)
```

### Source Code (repository root)

```text
config/quran.php                                   # NEW (qf, mp3quran, mushaf, audio, assets, defaults, sync)
config/{quran-audio,quran-editions}.php            # DELETE
config/artisan-commands.php                        # quran group rewritten
config/services.php                                # quran block trimmed to the env keys used by config/quran.php

database/migrations/
├── 2026_09_18_000001..000010_*.php                # NEW (R1 order); 28 quran files DELETED; 6 shared files EDITED
database/seeders/{QuranMushafSeeder,RolePermissionSeeder}.php   # NEW seeder; +"sync quran"
database/factories/Quran/*                         # Chapter, Verse, Word, Resource, Translation, Tafsir, AudioFile, ChapterAudioFile

app/Domain/Quran/
├── Enums/         RevelationPlace (kept) + SectionType, CharType, Script, ResourceKind, ResourceSource,
│                  ExternalSource, RecitationStyle, SyncMutationType, SyncResourceGroup, SyncRunMode, ReflectionPostType
├── Models/        Chapter(+Translation), Section, Verse, Word, WordGloss, Mushaf, MushafPage, MushafWord, Language,
│                  Resource(+Translation), Translation, Tafsir, ChapterInfo, AudioFile, ChapterAudioFile,
│                  ChapterAudioTimestamp, VerseHadithReference, Reflection(+Reference), Article(+Translation),
│                  SyncState, SyncRun, Playlist, PlaylistItem (kept, edited), AiInquiry, AiInquiryUsage (kept)
├── DTOs/          ReaderOptions, SearchOptions, ChapterData, VerseEditData, ResourceData, TranslationRowData,
│                  TafsirRowData, ChapterInfoData, AudioFileData, ChapterAudioFileData, TimestampRowData,
│                  HadithReferenceData, ReflectionData, ReciterPreview, ReciterData, PlaylistData (kept), ImportReport
├── Repositories/  {Chapter,Section,Verse,Word,Mushaf,Language,Resource,Translation,Tafsir,ChapterInfo,Audio,
│                  HadithReference,Reflection,Article,Sync}RepositoryInterface + Eloquent impls; Playlist + AiInquiry kept
├── Services/      ChapterService, NavigationService, ReaderService, VerseService, ResourceService, TranslationService,
│                  TafsirService, ChapterInfoService, AudioService, MushafService, SearchService, RandomVerseService,
│                  HadithReferenceService, ReflectionService, ArticleService, QuranCacheKeys (kept, scopes renamed),
│                  QuranDashboardStatsProvider, Export/{Chapter,Verse,Translation,Tafsir,Reciter}ExportAdapter,
│                  Playlist* (kept), Ask-AI services (kept, repos re-pointed)
├── Import/QuranFoundation/
│   ├── TokenProvider, Client, Endpoints, Exceptions/{QfApiException,QfPayloadException}
│   ├── Mappers/*, Importers/* (per contracts/qf-import.md), Sync/{ContentSyncService,MutationApplier,SnapshotImporters/*}
│   └── Reciters/{ReciterFetcher,QfReciterFetcher,Mp3QuranReciterFetcher,ReciterFetchService}
├── Infrastructure/{Llm,Mcp}/*                     # kept
└── (DELETE) Application/*, Importers/*, Exporters/*, Infrastructure/{Audio,Mp3Quran}/*, old Services/*, old Models/*

app/Models/ApiClient.php, database/migrations/2026_09_18_000000_create_api_clients_table.php   # NEW (Shared, app-level)
app/Http/Middleware/{AuthenticateApiClient,EnsureApiClientScope}.php      # NEW; registered as api.client / api.scope in bootstrap/app.php
app/Providers/AppServiceProvider.php                            # RateLimiter::for('api_client'), 'api_auth_failed', 'api_mutations'
config/cors.php                                                 # env-driven origins, headers, supports_credentials=false
app/Http/Controllers/Admin/Settings/ApiClientController.php + resources/views/admin/settings/api-clients/*   # NEW
app/Console/Commands/Api/CreateApiClientCommand.php             # NEW (api:client:create)
routes/api.php                                                  # v1 group wrapped in ['api.client', 'throttle:api_client']
app/Domain/Shared/Contracts/HadithReferenceLookup.php           # NEW
app/Domain/Hadith/Services/HadithReferenceLookupService.php     # NEW impl (bound in AppServiceProvider)
app/Jobs/FetchReciterFromSourceJob.php                          # NEW
app/Console/Commands/Quran/*                                    # 14 commands (13 NEW + PruneAiInquiriesCommand kept); 15 DELETED

app/Http/
├── Controllers/Admin/Quran/{Chapter,Verse,Word,Section,Mushaf,Language,Resource,ResourceRow,AudioFile,ChapterAudio,
│                            HadithReference,Reflection,Article,Sync,ReciterSource}Controller.php  # NEW; Playlist + AiInquiry kept
├── Controllers/Site/Quran/{Reader,Navigation,Reciter,Search,Ask}Controller.php  # split of QuranController; QuranPlaylistController kept
├── Controllers/Api/Quran/{Chapter,Verse,Section,Page,Script,Resource,Audio,Hadith,Search}Controller.php
├── Requests/Admin/Quran/*                                      # one per mutation in contracts/admin-forms.md
└── Resources/Quran/*                                           # per contracts/public-api.md; old Surah/Verse/Tafseer/Translation resources DELETED

routes/admin/quran.php, routes/api.php (quran block), routes/web.php (quran block + /quran/live 301)

resources/views/
├── admin/quran/{chapters,verses,words,sections,mushafs,languages,resources,resource-rows,audio,hadith-references,
│               reflections,articles,sync}/*.blade.php           # NEW; admin/{surahs,verses,tafseer,reciters,quran/revelation-contexts,
│                                                                  quran/tafsir-resources,quran/translation-resources} DELETED
├── public/quran/{index,surah,_reader,_mushaf,section,page,reciters,reciter,search,translation,tafsir,ask}.blade.php
│               + playlists/* (kept)  — juz/hizb merged into section.blade.php; live + asbab DELETED
├── components/public/quran/*  (kept + new: script-switcher, mushaf-page, word-popover, translation-picker,
│                               chapter-info-panel, hadith-panel, reflections-panel, command-bar)
└── components/audio-player.blade.php  (timestamps/segments support)
resources/css/quran-fonts.css                                   # @font-face for local Unicode fonts; QCF pages via FontFace API
resources/lang/{ar,en,fr,tr,ur}/quran.php
public/fonts/quran/**  (git-ignored)                           # download-assets output

app/Providers/{AppServiceProvider,FavoriteServiceProvider,TopicsServiceProvider,SharedServiceProvider}.php  # re-pointed
deptrac.baseline.yaml                                           # two Quran DTO entries removed
CLAUDE.md §6, docs/QURAN-QF-SYNC.md (NEW)
tests/Feature/{Quran,Admin/Quran,Api/Quran,Console/Quran,Web}/*, tests/Unit/Domain/Quran/*, tests/Fixtures/{qf,mp3quran}/*
```

**Structure Decision**: existing single-app DDD layout; the only new directories are
`app/Domain/Quran/Import/QuranFoundation` (mirrors `app/Domain/Hadith/Import/Sunnah`),
the per-area admin/site/API controller sub-folders, and `tests/Fixtures`. The kept public
views keep their file names and route names so bookmarks and the sitemap survive.

## Implementation Phases (input to `/speckit.tasks`)

Each phase ends green (`php artisan test`, pint, deptrac, `npm run build`). Phases 1–3
compile alongside the old code (new namespaces, new tables) so the suite keeps running;
old code is deleted in phase 6.

- **P0. Spec & baseline** — commit checkpoint; `baseline.txt` (route list, `SHOW TABLES`, test list); `quran:qf:doctor`; request `post.read`.
- **P1. Foundation + corpus** — `config/quran.php`; migrations 01–05, 07, 09 (sync tables), seeders, factories; enums/models; `TokenProvider`/`Client`/`Endpoints` + tests; importers structure/verses/words/catalogue; `download-assets`; `bootstrap` orchestrator; fixtures; gates.
- **P2. Content sync** — migration 06; `ContentSyncService`, `MutationApplier`, snapshot importers (translations, tafsirs, glosses, mushafs, articles*); `quran:qf:sync`; chapter infos importer; scheduler entry; lock semantics tests.
- **P3. Audio** — migration 08; recitation/chapter-recitation snapshot importers; timestamps importer; `AudioService` (absolutize, resolve, upload); `ReciterFetcher` + QF/mp3quran fetchers + `ReciterFetchService` + `FetchReciterFromSourceJob` + `quran:reciter:fetch`; migration 10 (playlists) + `PlaylistService::assign()` on chapter reciters.
- **P4. Admin** — permissions; `routes/admin/quran.php`; FormRequests + DTOs; controllers; views per contract (DataTables, language tabs, pickers, timestamps editor, sync dashboard, reciter source form with Lookup/Fetch); command registry; export adapters; dashboard stats; feature tests.
- **P4b. API security layer** — `api_clients` migration/model/factory; `AuthenticateApiClient` + `EnsureApiClientScope` middleware and aliases; rate limiters; `config/cors.php`; wrap the whole `routes/api.php` v1 group; Settings → API clients admin CRUD + permission + `api:client:create`; tests (401/403/429/CORS preflight) run against every existing v1 route family, not only Quran.
- **P5. Public read stack, pages, API** — repositories (explicit selects, cache keys), `ReaderService`, `NavigationService`, `SearchService`, `MushafService`; site controllers split; kept views re-wired + enhancements (C7 list in design.md); new views (section, page, search, translation, tafsir); audio-player timestamps/segments; fonts CSS; API controllers/resources/routes + tests; `NoNPlusOneQueriesTest` entries.
- **P6. Cross-module & deletion** — favourites, article embeds (`ContentReferenceLookup` impl), topics morph map, locations registry, Live 301, Asbab removal; Ask-AI re-pointed; delete old domain/commands/admin/views/resources/tests/configs; deptrac baseline shrink; CLAUDE.md + docs; memory note.
- **P7. Cross-content (scope-gated)** — `HadithReferenceLookup` (Shared + Hadith impl); hadith references importer + panel + API; reflections tables/importer/moderation/panel; QF articles tables/importer/admin/page. Tables in this phase are created by phase-7 migrations only.
- **P8. Rollout** — quickstart runbook on dev; verify counts; prod purge + resync in a maintenance window; asset download; cache clear; Arabic walkthrough.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| IX — `ReciterFetcher` interface with two implementations; `HadithReferenceLookup` interface with one | Owner requires per-reciter fetch from **two** sources behind one admin action; hadith linking must not import the Hadith domain (IV) | A `match` on source inside one service duplicates the lookup/data/files/timestamps quartet twice in one 300+-line class; a direct `Hadith::where()` from Quran is a new deptrac violation |
| CLAUDE.md §6 "prefer the CQRS query stack" | The 83-file Application layer serves one controller; every other module and the constitution's layering (I) use service + repository | Keeping CQRS for the rebuild doubles every read path (admin already bypasses it) and contradicts IX; CLAUDE.md is updated in P6 |
| IX — nine text/script columns on `quran_verses` (denormalised) | Every reader request needs exactly one script column; 6,236 rows × 9 columns is ~15 MB | A `quran_verse_texts (verse_id, script, text)` table costs a join per page and a pivot in every payload builder for no storage benefit |
| Public-disk fonts/images (~320 MB) checked into deploy, not git | Owner requires all data local; fonts are per-page files the reader loads lazily | Serving from the QF CDN violates the "served from our codebase" decision; a CDN bucket is out of scope for this feature |

## Post-Design Constitution Re-check

Re-evaluated after Phase 1 artifacts: all rows above hold. Two additions surfaced
during design and are already reflected: (a) `is_locked` on synced rows keeps X honest
about editorial overrides; (b) phase-7 tables move to phase-7 migrations so IX's
"ships only when populated" rule holds if the scopes are never granted.
