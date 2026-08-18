# Execution task list — grouped by phase

Derived from [MODULE-ENHANCEMENT-PLAN-2026-08-17.md](MODULE-ENHANCEMENT-PLAN-2026-08-17.md).
Each task carries its plan ID, the files it touches, an effort estimate, and a
**done-when** that is checkable rather than a matter of opinion.

**Total: ~64 developer-days.** Phases 0–3 are **~16 days** and close every live fault,
every security finding and the entire measured capacity gap. Phase 4 is the largest
block and the least urgent — it optimises code that already works.

> Do not start a phase before its predecessor is complete. The ordering is load-bearing:
> Phase 1 exists so that Phases 2–6 are not refactoring code that fails silently, is
> ungated by CI, and is partly invisible to static analysis.

---

## Phase 0 — Stop the bleeding · 2.5 d

Six live faults. No dependencies. Nothing else starts until this is done.

- [x] **T0.1 · Delete the `sajda_type` validation rule** — `P0.1` · 0.25 d → **actual 1 d**
  **DONE 17 Aug.** Scope was larger than estimated: removing the fatal exposed three more
  defects underneath it, all of which had to be fixed for the feature to work at all.
  Admin verse create/update had **never** functioned. See the closing note at the end of
  this file.
  Touched: both verse `FormRequest`s, `Admin/Quran/VerseController.php`,
  `admin/verses/{create,edit}.blade.php`, `lang/ar/quran.php`, and a new
  `tests/Feature/Admin/Quran/VerseCrudTest.php` (7 cases).
  **Verified:** `grep -rn SajdaType app/` returns nothing; create and update both persist;
  suite 892 → 899 passing with no new failures.

- [x] **T0.2 · Replace the two phantom exception classes** — `P0.2` · 0.25 d
  `SchedulerController.php:9,188,282,317` · `SchedulerService.php:11,56,96,127,212,243`
  `Quran/TafseerController.php:126,227` · `Quran/TafseerService.php:10,104,125`
  Swap `BusinessLogicException` and `DuplicateResourceException` for the existing
  `App\Domain\Shared\Exceptions\BusinessRuleViolationException` (used correctly in
  `ExportController`). Update the `catch` blocks, which are currently unreachable.
  **Done when:** `grep -rn 'BusinessLogicException\|DuplicateResourceException' app/`
  returns 0 hits, and submitting a duplicate tafseer returns a handled 4xx, not a 500.

- [x] **T0.3 · Fix bulk-operation preview column selection** — `P0.6` · 0.5 d
  `app/Domain/Shared/Services/AbstractBulkOperationHandler.php:61,64`
  `->get(['id','title','name'])` throws on all four handlers — no table has both columns
  (`articles` has `title` only; `seerah_events`, `quran_verses`, `media_programs` have
  neither). Let each handler declare its own label column, or select `['id']` and resolve
  the label through the model's locale accessor.
  **Done when:** `preview()` returns labels for `ArticleBulkHandler`,
  `SeerahEventBulkHandler`, `VerseBulkHandler` and `MediaProgramBulkHandler`, each covered
  by a test.

- [x] **T0.4 · Close the config-cache outage switch** — `P0.3` · 0.5 d
  Three parts, all required:
  1. `deploy/stages/75-app.sh:33,36` — make `.env` actually land as `640
     deployer:www-data` (it claims to and does not; live is `600 deployer:deployer`), so
     `www-data` survives a cold config cache instead of hard-failing.
  2. `deploy/sync-database-to-production.sh:139-141` — append `artisan config:cache`
     after `optimize:clear`, mirroring `deploy.sh`.
  3. `config/artisan-commands.php:98,101` — set `'queued' => true` on `optimize:clear`
     and `config:clear` (workers run as `deployer`), or remove them from the whitelist.
  **Done when:** `sudo -u www-data test -r …/shared/.env` succeeds, and clearing the
  config cache from the admin panel leaves the site serving.

- [x] **T0.5 · Reconcile the live `.env`** — `P0.4` · 0.5 d
  Add `DB_QUEUE_RETRY_AFTER=3700` (currently missing → resolves to 90 s against a 3600 s
  job timeout) and set `CACHE_STORE=redis` (currently `file`). Then **one manual**
  `sudo supervisorctl restart all` — `queue:restart` writes its flag to the cache store,
  so workers booted under `file` will never see a flag written to Redis. Add a drift check
  to `deploy/deploy/deploy.sh` that warns when `shared/.env` lacks keys present in
  `env.production`.
  **Done when:** both keys are live, workers restarted, and a deliberate drift is caught
  by the deploy check.

- [x] **T0.6 · Fix the four shipped bugs** — `P0.5` · 0.5 d
  - `app/Http/Resources/SurahResource.php:27` → `'number' => $this->id`
  - `app/Http/Resources/TranslationResource.php:33-39` → bind
    `$edition = $this->resource->resource` once; widen
    `VerseRepository::TRANSLATION_RESOURCE_EAGER` to include `slug,name,language,is_default`
  - `routes/admin.php:230,247` → register `roles/create` **before** the `roles` resource
  - `POST /api/v1/filters/{id}/apply` → reuse `asResources()` from
    `Api/ArticleController:164` (currently returns raw models, leaking writer emails)
  **Done when:** the four Quran endpoints return 200, `/api/v1/quran/surahs` reports
  `number` 1 for Al-Fatihah, `GET admin/roles/create` renders, and no `users.email`
  appears in the filter-apply payload.

---

## Phase 1 — Make failure visible · 4.5 d

Instrumentation before optimisation. Everything after this phase depends on it.

- [x] **T1.1 · `report($e)` sweep across unlogged catch blocks** — `W3.1` · 1 d
  103 catches in `app/Http/Controllers/Admin`, 49 in `Site`, 19 in `Api`. The dominant
  shape catches `\Throwable`, flashes a translated `admin.*_failed` message and logs
  nothing. Script it: grep, insert `report($e);` as the first statement, skip ones that
  already log. **Do not touch** `AbstractBulkOperationHandler`'s 10 catches or
  `SeriesService:318` — those collect per-item errors and return them to the caller.
  **Done when:** every catch either logs or is on the documented exclusion list.

- [x] **T1.2 · Fix the dashboard's silent zeroing** — `M9.7` · 0.25 d
  `DashboardService.php:96,135,173,303` (4 catches, no `report()`) and
  `DashboardAnalyticsService::getMonthlyGrowthForTable:295` (the one straggler its
  siblings already fixed). This is the exact pattern CLAUDE.md documents, still open in
  the file that caused it. Also check whether any view still renders the permanent stubs
  at `DashboardService:229-236` and `DashboardAnalyticsService:203-210`.
  **Done when:** a deliberately broken query surfaces in `laravel.log` instead of
  rendering as 0.

- [x] **T1.3 · Log the Quran reader's resilient fallbacks** — `M1.13` · 0.1 d
  `QuranReaderService` — 6 catches degrading to `[]`/`''`. Keep the behaviour, add
  `report($e)` so a broken query stops looking identical to "no data for this surah."

- [x] **T1.4 · Put the request id into log context** — `W3.2` · 0.25 d
  `app/Http/Middleware/AddRequestId.php` sets headers only, never `Log::withContext()`,
  and `bootstrap/app.php:52-56` registers it on the **api** group alone. Add the context
  call and register for `web` too.
  **Done when:** a `production.ERROR` stack trace carries `request_id`.

- [ ] **T1.5 · External uptime monitoring + error tracking** — `W3.3` · 0.5 d
  Point a monitor (UptimeRobot / healthchecks.io / Cloudflare health checks) at `/up`,
  which exists and nothing polls. Add Sentry or GlitchTip free tier.
  **Done when:** killing php-fpm on a test window produces an alert. Current
  mean-time-to-detect for a 500 is "whenever a human looks," with no floor.

- [ ] **T1.6 · Schedule the health check** — `W3.4` · 0.25 d
  `deploy/stages/90-healthcheck.sh` is thorough and **manual-only**. Make it a cron that
  alerts when `fails > 0`.

- [x] **T1.7 · Triage the 9 failing tests** — `W4.1` · 0.5 d
  `ArticleCrudTest:28` · `ArticleSeriesIntegrationTest:51,87,109` ·
  `MuslimScholarsSahabaImportTest:205` · `ArticlePublicPagesTest:190,214` ·
  `ContactFormTest:54` · `ContentPublicPagesTest:222`.
  Several look like intentional behaviour changes with stale assertions — verify and
  update, **do not silence**. **Done when:** `php artisan test` is green.

- [x] **T1.8 · Admin route-table smoke test** — `W4.3` · 0.5 d
  One test walking every registered admin GET route for an authorised user, asserting
  non-500. `ApiRouteSmokeTest` already exists for the API; nothing analogous for
  `admin.php`. Would have caught both T0.1 and the dead `roles/create` page.

- [x] **T1.9 · Gate deploys on the test suite** — `W4.6` · 0.5 d
  Add a `test` job to `.github/workflows/deploy.yml` with a MySQL 8 service container
  (`phpunit.xml` sets `DB_CONNECTION=mysql`), then change
  `deploy: needs: architecture` → `needs: [architecture, test]`. ~4 min runtime.
  **Done when:** a deliberately failing test blocks a deploy.

- [x] **T1.10 · Redeclare `$repository` in the 14 `BaseService` subclasses** — `W7.1` · 0.25 d
  None narrow the property, so each sees only `RepositoryInterface`'s 13 methods — **98
  repository calls are type-unchecked**. Fourteen one-line declarations, and a
  prerequisite for T5.4 (PHPStan) to see anything useful.

- [x] **T1.11 · Widen deptrac's collectors** — `W7.2` · 0.5 d
  The gate reports `Violations 0 · Uncovered 7333 · Allowed 1366` — it sees ~16% of the
  graph. `app/Jobs`, `app/Exceptions`, `app/Utils`, `app/Helpers` are in no collector, so
  `Shared → App\Jobs` (`BulkOperationService.php:12`) is invisible. Add the layers,
  re-baseline. Keep the baseline pattern — do **not** add `--fail-on-uncovered`.

---

## Phase 2 — Capacity · 4 d

The stress test located the constraint precisely: PHP CPU on 2 vCPU, with
`Slow_queries` at 0, 2 GB RAM free and 9 of 16 workers unused. More hardware changes
nothing. Every task here reduces PHP work per request.

- [x] **T2.1 · Expand `CACHEABLE_ROUTES`** — `W1.1` · 0.5 d
  `app/Http/Middleware/CacheGuestPages.php:31` lists 2 routes. Add `/articles`,
  `/quran/*`, `/seerah/*`, `/hadith/*`, `/sahaba`, `/topics/*`, `/series/*`.
  **The single highest-leverage change in the codebase** — Cloudflare will not cache HTML
  without an explicit directive, so ~95% of public traffic currently reaches PHP.
  **Done when:** those routes emit `Cache-Control`, and a second request shows a
  Cloudflare `cf-cache-status: HIT`.

- [x] **T2.2 · Stampede protection** — `W1.3` · 1 d · *needs T0.5*
  150 `Cache::remember` sites, **0 `Cache::lock`, 0 `Cache::flexible`**. Worst:
  `Site/ArticleController:38` (rebuilds 500 articles + tags + facets),
  `Site/HomeController:42-140` (six keys, incl. `inRandomOrder()` over ~38k hadiths).
  Convert the hot keys to `Cache::flexible()` (Laravel 11+ SWR).

- [x] **T2.3 · Route search through FULLTEXT** — `W1.4` · 1 d
  `SearchQueryBuilder.php:71` selects `MATCH/AGAINST` only if a model implements
  `getFullTextColumns()` — **no model does**, so 100% of search takes `applyBasicSearch()`
  (:190). The indexes exist and are ignored. `FullTextSearchHelper` already exists and is
  used correctly elsewhere. Also fix `VerseTranslation.php:126`, which ORs
  `MATCH…AGAINST` with `LIKE '%…%'` — the OR makes the index unusable.
  **Done when:** `EXPLAIN` on a `/search` query shows the fulltext index in use.

- [x] **T2.4 · Add missing indexes, drop duplicates** — `W1.5` · 0.5 d
  Add: `articles(status, created_at)`, `articles(status, views)`,
  `media_programs(is_published, created_at)`, FULLTEXT on `quran_verse_tafsirs.text`,
  `search_history(query(64))`, `user_interactions(interaction_type, created_at)`.
  Drop the two duplicate pairs on `articles`: `articles_status_index` vs
  `idx_articles_status`, and `articles_slug_unique` vs `articles_slug_index`.
  Verified against live `SHOW INDEX`, not migrations.

- [ ] **T2.5 · MySQL and php-fpm retune** — `W1.6` · 0.5 d
  `innodb_buffer_pool_size` 1G → **384M** (65,536 pages allocated, 48,014 free for a
  122 MB database), `performance_schema` → **OFF**, `max_connections` 100 → **40** (peak
  observed 8), `pm.max_children` 16 → **10** (16 × 256M = 4 GB overcommit on a 3.7 GB box
  with `vm.overcommit_memory=1`). Frees ~800 MB–1 GB. Off-peak; restarts MySQL.

- [ ] **T2.6 · nginx and fpm connection limits** — `M11.7` · 0.25 d
  `worker_connections` 768 → 4096 + `multi_accept on`; `backlog=1024` on `listen`;
  `listen.backlog = 4096` on the fpm pool; `fastcgi_buffers 16 32k` +
  `fastcgi_busy_buffers_size 64k`; `keepalive_requests` → 10000. Requires `restart`.

- [x] **T2.7 · Fix `/robots.txt`** — `M11.4` · 0.1 d
  Returns **404 in production** — found by the load test (all 137 "failures").
  `snippets/tlm-app.conf` has `location = /robots.txt` with no `try_files`. Check
  `/favicon.ico` for the same shape.

- [ ] **T2.8 · Re-measure** — 0.25 d
  `k6 run tests/load/k6.js --env SCENARIO=stress --env TARGET=origin --env PEAK=300`
  with `tests/load/monitor.sh` alongside. Compare to the 16 Aug baseline (p95 1.69 s at
  60 VU). **Do not assume the gain — measure it.**

---

## Phase 3 — Security · 5.6 d

- [x] **T3.1 · Sanitise rich text at write time** — `W6.1` · 1.5 d
  `SanitizesRichText` is applied to 6 request classes; **articles and media are not among
  them** — the two highest-volume rich-text paths. Add it to
  `Store/UpdateArticleRequest`, `Store/UpdateMediaProgramRequest`,
  `Store/UpdateMediaEpisodeRequest`, and call it in `YouTubeSyncService.php:381,412`
  before `syncTranslations()` (third-party playlist text currently lands raw and renders
  `{!! !!}`). Backfill existing rows once.
  **Done when:** a `<script>` payload submitted through the article editor is neutralised
  at rest. Note the editor role holds `create` + `publish articles`, so this is an
  editor → super_admin escalation path today.

- [x] **T3.2 · `permission:` middleware on admin route groups** — `W6.2` · 1 d
  **Zero occurrences in all of `routes/`.** The portal is one
  `role:super_admin|admin|editor,admin` gate; only 3 sub-groups narrow it. An `editor` can
  `POST /audit/prune` (destroy the audit trail), read analytics, revert versions, change
  site languages, and bulk export/import. The seeded permission map already exists.
  **Done when:** an editor receives 403 on audit, analytics, versions, settings and
  export routes.

- [x] **T3.3 · `chmod` in `render_template`** — `W6.3` · 0.25 d
  `deploy/lib/common.sh:114-129` writes via shell redirection → umask 0644.
  `/etc/redis/redis.conf.d/tlm.conf` holds `requirepass`, and `SESSION_DRIVER=redis`
  means that password forges admin sessions. Currently safe only by an inherited
  directory mode. One fix at the shared function covers all 14 callers.

- [x] **T3.4 · Bound the unthrottled amplifiers** — `W6.4` · 0.75 d
  - `/genealogy?root=` (`routes/web.php:143`) — no throttle, bypasses cache, recursive
    CTEs at depth 40. Add `throttle:search`, cache per root, constrain the slug.
  - `/pages/{slug}` — `StaticPageService:36` uses `rememberForever`, and **Laravel's
    implementation has no null check** (the code comment claiming otherwise is wrong), so
    every miss writes a permanent entry. Under `allkeys-lru` this evicts live sessions.
  - `/hadith/search` (`routes/web.php:80`) and `/topics*` (`:164-165`) — add `throttle:search`.
  - `Site/SeerahController:225` — clamp the `?q=` and `?page=` cache-key dimensions.

- [x] **T3.5 · Scope bulk-operation results to their owner** — `W6.5` · 0.25 d
  `BulkOperationController::download/status/destroy` take a raw `{id}` with no ownership
  check, while `index()`/`stats()` in the same file scope by user at `:229-231`.

- [x] **T3.6 · Fix the JSON-LD breakout** — `W6.6` · 0.1 d
  `public/hadith/show.blade.php:22-30` uses `JSON_UNESCAPED_SLASHES` inside
  `<script type="application/ld+json">`, on text imported from sunnah.com. Drop it; add
  `JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT`, as
  `public/genealogy/index.blade.php:287` already does.

- [ ] **T3.7 · Security headers** — `W6.7` · 0.5 d · *after T3.1*
  `nginx-app.conf:42-47` declares `add_header` inside the static-asset location, and
  nginx's `add_header` **replaces** the inherited set — so every JS/CSS/image response
  ships without HSTS or `nosniff`. Re-emit them there. Then add CSP report-only.

- [x] **T3.8 · Auth hardening** — `W6.8` · 1 d
  Admin-created accounts use `min:8` with no complexity — the **weakest** policy in the
  app, against `Password::min(12)` with symbols on the API. Add failed-login logging
  (`AdminAuthController:57-67` emits nothing) and a per-account lockout. Fix the timing
  oracle at `Api/AuthController:75-82`, which skips `Auth::attempt` so bcrypt never runs
  on an unknown email.

- [x] **T3.9 · Make the Cloudflare real-IP fetch fail closed** — `M11.5` · 0.25 d
  `deploy/stages/65-nginx.sh` fetches CF ranges live and, on failure, **warns and
  continues**. Without real-IP restoration the rate limits key on the edge IP, so every
  visitor behind a CF datacenter shares one 20-connection budget — mass 503s that look
  exactly like an origin outage, precisely under peak traffic.

---

## Phase 4 — Module optimisation · ~30 d

Ordered slowest-and-densest first. Two items are single-file and independent and may be
pulled forward at any time: **T4.9** (`BaseExporter`) and **T4.1** (deleting dead code).

### Quran · 7 d
- [ ] **T4.1 · Delete the dead CQRS write side** — `M1.15` · 1 d · *do this first*
  `QuranCommandService` (495 LOC, 15 constructor deps) has **zero consumers** — the only
  reference is its own class declaration. `CommandBus`/`QueryBus` have zero usages; 13
  events fire into nothing. ~2,057 LOC across 49 files, transitively unreachable.
- [ ] **T4.2 · Fix the `load()` epidemic** — `M1.4` · 1.5 d
  16 handler calls. `getBySurah()` eager-loads translations *with an explicit column
  list*; `ListVersesBySurahHandler:29` then calls `->load('translations')`, issuing a
  second query **and** replacing the constrained relation with `SELECT *`. Same in the
  Page/Juz/Hizb/Verse/Sajda/Random handlers. **Highest measured latency payoff.**
- [ ] **T4.3 · Narrow the translation eager loads** — `M1.5`, `M1.6`, `M1.7` · 1 d
  Split `TRANSLATIONS_EAGER` so `text_notashkeel` (FULLTEXT feed, rendered nowhere) stops
  doubling every read; filter by `whereIn('lang', ['ar', $locale])` at `:135,175,265,292,305`;
  fix the 15 lazy `SELECT *` per admin verse page at `:332`.
- [ ] **T4.4 · Tafseer and Reciter repositories** — `M1.8`, `M1.9` · 1.25 d
  8 bare `SELECT *` on a `longText` table; `TafseerRepository:343` `clearCache()` never
  calls `parent::clearCache()`, so reads serve pre-edit data for 900 s. `ReciterRepository`
  ×5 with no `select()`, no `limit` and no `translations` eager load.
- [ ] **T4.5 · Quran sync correctness** — `M1.10`, `M1.11` · 1 d
  `QuranSyncService:148` holds a transaction across **three remote HTTP calls** with
  deliberate delays; `:271,289,306` write `resource_id = NULL` while the unique is now
  `(verse_id, resource_id)`, so synced rows are invisible to the reader.
- [ ] **T4.6 · Controller cleanup + cache invalidation** — `M1.12`, `M1.16`, `M1.14` · 1.25 d
  `Site/QuranController` (973 LOC): delete the unbounded `getFeatured()` at `:648` whose
  result is recomputed at `:684`; move direct Eloquent out of `:713,722`. Wire
  `QuranCacheKeys::flushContent()` into admin writes — it exists and is never called, so
  editing a verse leaves the public page stale for up to an hour.

### Shared · 5.5 d
- [ ] **T4.7 · `BaseExporter` → `FromQuery` + `WithChunkReading`** — `M9.2` · 1 d
  One file; fixes memory bounds for **all seven exporters** and removes the double
  collection build in `ExportController:61,66`. Drop `ShouldAutoSize`. Also merge the two
  duplicate `'font'` keys at `:102-114`.
- [ ] **T4.8 · Fix the argument-ignoring cache keys** — `M9.3` · 0.75 d
  `SurahRepository:49` caches under a fixed key while accepting `$columns`/`$relations` —
  run the export first and the key holds 114 surahs plus 6,236 verse models; run the list
  first and the export emits surahs with no verses. Same at `SeerahEventRepository:368`.
  Refuse to cache an unbounded `select *` in `BaseRepository::all()`.
- [ ] **T4.9 · Chunk the importer** — `M9.4` · 1 d
  `BaseImporter:114` reads the whole sheet and keeps it; `:284` wraps every row in one
  transaction; `:210,311` append entire rows to an unbounded error array; `parseFile()`
  re-runs up to 3× across the wizard.
- [ ] **T4.10 · Audit, analytics and interaction fixes** — `M9.5`, `M9.8`, `M9.9`, `M9.10` · 1.5 d
  `AuditService:130-165` calls `->getCollection()` on a paginator, so monthly reports
  aggregate page 1 only. `AnalyticsService:175,347` queries morph values with
  `getMorphClass()` against stored FQCNs → article favourite counts permanently 0.
  `InteractionService:145` read-modify-write (lost update) and `:86` inline
  `afterResponse()` work. `SetLocale:109,151` — memoize the 5 cache reads per request.
- [ ] **T4.11 · Bound the unified search** — `M9.6` · 0.75 d
  `SearchService:411` — `SELECT *` across five models per request (up to 100 rows each)
  with bare eager loads and per-row lazy loads in the Resources.

### Media · 4.75 d
- [ ] **T4.12 · Kill the poster-accessor N+1** — `M3.2` · 1 d
  `MediaProgram:193` fires 2 queries per program at *serialization* time, so the
  repository cache buys nothing. `Site/MediaController:314` compounds it: 4 accessors ×
  12 cards × 6 rows.
- [ ] **T4.13 · Bound the episode renders** — `M3.3` · 0.5 d
  `Site/MediaController:186,228` map **every** episode into a card on both the program and
  player pages — a 900-episode program renders 900 cards on a page showing one video.
- [ ] **T4.14 · Column-constrain the media eager loads** — `M3.4` · 0.5 d
  `MediaEpisodeRepository` ×7 and `MediaProgramRepository` ×10 load `'media'` with no
  column list. **Keep `generated_conversions`** or `getFirstMediaUrl(…, 'thumb')` silently
  falls back to the original.
- [ ] **T4.15 · Fix delete-order and read-time writes** — `M3.5`, `M3.6`, `M3.7` · 1.25 d
  `MediaProgramService:177` clears cache *after* deleting, and the clear returns early
  when the row is gone → deleted programs render for 30 min then 404. `Media.php:336`
  `saveQuietly()` during a read. `MediaProgram:108` deleting hook = 1,600 statements for
  an 800-episode program.
- [ ] **T4.16 · Image OOM guard and decrypt cache** — `M3.8`, `M3.9`, `M3.10` · 1.5 d
  `MediaService:599` decodes before `scaleDown()` with no pixel guard — a 10 MB PNG can
  exceed 400 MB against a 256 M limit, inline in the request, and the `memory_limit` fatal
  leaks the plaintext temp copy. `:257-281` decrypts to a fresh temp file per request and
  marks it `private`, which Cloudflare honours by not caching. Flip
  `queue_conversions_by_default` to `true`; fix `max_file_size` (currently **100 GiB**).

### Blog · 4.5 d
- [ ] **T4.17 · One `CARD_COLUMNS` constant, eleven call sites** — `M2.2` · 1 d
  `ArticleRepository` `:58,83,103,135,152,165,210,606,624,674,828`. Because `articles` is
  mid-migration it still carries live JSON bodies, so a 100-row page drags 100 full
  articles **twice**. `getPublishedForPublicIndex:243` already has the right list.
- [ ] **T4.18 · Repository and controller waste** — `M2.3`, `M2.4`, `M2.5` · 1.75 d
  `:590` locales closure with no column list; `:638` whole row to read one FK; `:205`
  hydrates everything to `pluck('id')`; `:765` unbounded tag fetch per edit render.
  `Admin/Blog/ArticleController:71,134,232,615`. The duplicated full-series fetch running
  **twice per article page** at `Site/ArticleController:274,333`.
- [ ] **T4.19 · Finish the Tag column migration** — `M2.6`, `M2.7` · 0.75 d
  `Tag.php:70,79,108` reads the dropped `tags.name`; `?? '{}'` swallows it so every tag
  name is silently `null` and auto-slugs become the literal `'tag'`. `TagResource:33` has
  the same read. `Category:230` was migrated properly.
- [ ] **T4.20 · Category service fixes** — `M2.8` · 0.75 d
  `:339` `findOrFail()` defaults to `['*']` **and** eager-loads every article unbounded,
  for a page showing a count. `:426` fetches all siblings for a `max()`. `:461` +
  `Category:120,302` fire three parent-chain ladders on one save.
- [ ] **T4.21 · Delete dead Blade** — `M2.9` · 0.25 d
  `_form-new` (1,289 loc), `_form` (803), `_form-example` (352), `index-example` (320) —
  all unreferenced; `create`/`edit` include only `_form-wizard`.

### People · 3.25 d
- [ ] **T4.22 · One `NODE_COLUMNS` constant, six fetches** — `M5.2`, `M5.3` · 1.25 d
  `Site/GenealogyController:234,422,476,581,615,796` — four unbounded, all feeding a
  `mapPerson()` that reads 11 columns. `:648` is a **duplicate** of the repository's
  `findMostConnectedRootId`, so fixing the repository leaves this one broken.
- [ ] **T4.23 · Push tree filtering into SQL and bound it** — `M5.4`, `M5.5` · 1 d
  `GenealogyTreeService:146` filters in PHP after hydrating both counterparts — 80 of
  7,837 rows are published. `descendants():113` bounds depth but not rows, and `UNION ALL`
  emits a person once per path. Add `LIMIT` + a `nodes_capped` flag.
- [ ] **T4.24 · Collapse the repeated COUNT passes** — `M5.6`, `M5.7`, `M5.8` · 1 d
  Three/four full COUNTs over 7,837 rows per index load at `Admin/SahabiController:84,330`
  and `Site/SahabaController:186`; `EloquentMuslimRepository::treeCounts:191` is already
  the reference conditional aggregate. Drop `bio`/`birth_place` from `MuslimResource` —
  neither is selected, so both are `""` for all 7,837 people.

### Seerah · 3 d
- [ ] **T4.25 · Apply the correct relation set everywhere** — `M4.1`–`M4.4` · 1.75 d
  Seven paginated reads with no base-table `select()`; `listRelations()` not
  locale-filtered; `detailRelations():560` loads `longText` + 2 JSON blobs for all five
  locales while its correct twin `publicDetailRelations():405` exists;
  `SeerahMilestoneRepository:45` eager-loads all 548 events to compute three integers.
- [ ] **T4.26 · Queue the view counter** — `M4.5` · 0.5 d
  `SeerahEventService:63,81` fires a synchronous hot-row `UPDATE` on every view; every
  concurrent reader of a popular event serialises on one InnoDB row lock. Route through
  `MediaViewRecorder` on the `tracking` queue, as the Media domain already does.
- [ ] **T4.27 · Trim the Seerah resources and admin paths** — `M4.6`, `M4.8` · 0.75 d

### Hadith · 1.6 d
- [ ] **T4.28 · Stop shipping the unbounded columns** — `M6.4`, `M6.5` · 1 d
  `hadiths` carries four unbounded columns; `search_ar`/`search_en` are folded duplicates
  never rendered. `HadithBrowseService:99` selects all four per detail view (`:137` gets
  it right). `:33,40,68,78` ship `mediumText` intros no Blade renders; `:33` is a 1-hour
  cache payload; `chapters()` is unbounded.
- [ ] **T4.29 · Fix the editor's hadith picker** — `M6.6` · 0.5 d
  `Admin/Blog/ArticleModuleController:198` runs `LIKE '%term%'` across **both `longText`
  bodies on every keystroke** — a full scan of 37k rows while the fulltext indexes sit
  unused. It also searches unfolded text, so `الصلاة` misses `الصلاه`.
- [ ] **T4.30 · Correct CLAUDE.md's Hadith section** — `M6.7` · 0.1 d

### Topics · 0.75 d
- [ ] **T4.31 · Constrain the morph load** — `M8.2` · 0.5 d
  `TopicRepository:95` constrains nested relations but leaves the itemable `SELECT *`.
  **Keep `id` in every per-type column list** or every topic page renders empty.
- [ ] **T4.32 · Remove the per-article filesystem stat** — `M8.3` · 0.25 d
  `TopicService::mapArticle:202` — 200 `Storage::exists()` calls per cache miss.

---

## Phase 5 — Durability, parity and quality gates · ~12 d

- [ ] **T5.1 · Resource value-assertion pass** — `W4.2` · 1 d
  ~25 Resources × ~10 lines. Assert *values*, not structure — every current Quran test
  checks only `assertJsonPath('success', true)`, which is why the wrong-surah-number bug
  passes them all.
- [ ] **T5.2 · Close the zero-coverage gaps** — `W4.4`, `W4.5` · 1.5 d
  `AuditServiceTest` and `TagTest` (both classes have none), plus feature tests for
  `Site\HadithController` (5 routes) and `TopicController` — the two entirely untested
  public surfaces.
- [ ] **T5.3 · Nightly off-box backups** — `W5.5` · 1 d
  No `mysqldump` anywhere in the deploy path; no cron on the box; the only dumps that
  exist are side effects of the manual DB-sync script, on the same 38 GB volume as the
  database. Add `mysqldump --single-transaction --quick | gzip`, ship it off-box, and
  verify with a periodic restore test.
- [ ] **T5.4 · Larastan + Pint in CI** — `W4.7` · 1 d · *needs T1.10*
  PHPStan is not a declared dev dependency — it is pulled in transitively by deptrac and
  has no config. Add `larastan/larastan` + `phpstan.neon` at **level 5** (level 8 on day
  one will drown in noise). It would have caught every phantom class in Phase 0
  statically. Add `pint --test` too — 138 files fail, all whitespace and import order.
- [ ] **T5.5 · Release retention and rollback** — `W5.2` · 0.5 d
  `deploy.sh:154-167` prunes to exactly one release, and `KEEP_RELEASES=5` in
  `/etc/tlm-deploy.env` is read by nothing. Keep 3–5; add a `rollback.sh` that re-flips
  `current`.
- [ ] **T5.6 · Local dev parity** — `W5.3` · 1 d
  `composer dev` runs against **SQLite** while production is MySQL 8.4. Add a
  `docker-compose.yml` with MySQL 8 + Redis at prod major versions, wired to the already
  installed Sail. SQLite cannot reproduce this codebase's known bug classes —
  `JSON_EXTRACT`, `FULLTEXT`, dropped columns, `whereDate` sargability.
- [ ] **T5.7 · Pin `maatwebsite/excel`** — `W5.4` · 0.25 d
  `composer.json:18` is `^4.0@dev`, resolved to `4.x-dev` — an unpinned dev branch in
  production, handling admin-supplied files.
- [ ] **T5.8 · Clamp `per_page` and stream the audit export** — `M10.4`, `M10.5` · 1 d
  ~20 admin endpoints take `$request->integer('per_page', N)` with no ceiling —
  `?per_page=999999` is a one-request OOM. `Admin/UserController:45` already clamps;
  the public API validates `max:100`. `AuditController::exportLogs:181` builds the whole
  table as one string on a **GET** route, so a browser prefetch can fire it.
- [ ] **T5.9 · Remove the dead exception handler** — `M10.6` · 0.1 d
  `bootstrap/app.php:5` imports from the deleted `App\Domain\Sahaba` namespace, so People
  404s fall through and return **422**.
- [ ] **T5.10 · Translation completeness** — `M10.7` · 2 d
  `ar` has 5,497 keys; `tr`/`fr`/`ur` have **2,227 each**, and 9–11 lang files are missing
  per non-Arabic locale — including `validation.php` and `pagination.php`, so those
  locales fall back to English framework strings. 471 hardcoded Arabic literals sit
  outside `__()` in Blade.
- [ ] **T5.11 · Deploy and retention hygiene** — `M11.6`, `M11.8`, `M11.9`, `M11.10` · 1.25 d
  `deploy.sh` runs `optimize:clear` mid-deploy, wiping the cache the **currently-live**
  release is serving from. `sync` queue runs yt-dlp/ffmpeg at normal priority on 2 cores.
  Schedule `media:prune-view-events` (exists, deliberately unscheduled),
  `queue:prune-failed`, `queue:prune-batches`; add prunes for `search_history` and
  `user_interactions`; either dispatch or delete `CleanupFailedImports` (never dispatched
  anywhere). Cap journald at 200M (627 MB uncapped). Add `--classmap-authoritative`.

---

## Phase 6 — Structural refactoring · 5.6 d

Last on purpose: maintenance cost, not user-facing risk. Splitting a 973-line controller
before Phase 1 means a large refactor against code that fails silently and is ungated.

- [ ] **T6.1 · Split the five god classes** — 5 d
  `Site/QuranController` 973 · `ArticleRepository` 963 · `AnalyticsService` 961 ·
  `Site/GenealogyController` 876 · `Admin/Blog/ArticleController` 828.
  Extract by responsibility, not by line count. Several will shrink substantially from
  Phase 4 work alone — **re-measure before starting.**
- [ ] **T6.2 · Delete the `DomainSahaba` deptrac layer** — `W7.3` · 0.1 d
  `deptrac.yaml:47-52` still defines a layer for two directories deleted in the People
  migration.
- [ ] **T6.3 · Correct CLAUDE.md** — `W7.4` · 0.5 d
  Stale on three counts: Hadith's public UI, the Quran CQRS consumption description
  (backwards), and Spatie JSON columns mandated for models whose columns were dropped.
  The constitution is what future work gets measured against.

**Explicitly not in this phase:** relocating the dual repository locations. The split is
semantic (41/41 domain repos have interfaces vs 1/4 app-level; 20 inherit transparent
caching), and moving `ArticleRepository` while `Article` stays in `app/Models/` relocates
the inconsistency rather than resolving it.

---

## Effort summary

| Phase | Days | Closes |
|---|---:|---|
| 0 · Stop the bleeding | 2.5 | 6 live faults |
| 1 · Make failure visible | 4.5 | silent failure, ungated CI |
| 2 · Capacity | 4.1 | the measured p95 gap |
| 3 · Security | 5.6 | 2 critical + 4 high findings |
| 4 · Module optimisation | ~30 | query and payload waste |
| 5 · Durability & gates | ~12 | backups, parity, static analysis |
| 6 · Structural | 5.6 | maintainability |
| **Total** | **~64** | |

**Phases 0–3 are ~16 days and carry nearly all the risk reduction.** Everything after
that is improvement to a system that, by then, works and tells you when it doesn't.

---

## Execution log

### T0.1 — completed 17 Aug 2026 · estimated 0.25 d, actual ~1 d

Removing the `SajdaType` fatal exposed three further defects beneath it. **Admin verse
create/update had never worked** — this was not a regression. All four had to be fixed
together for the feature to function.

| # | Defect | Fix |
|---|---|---|
| 1 | `SajdaType::values()` in `rules()` → class-not-found fatal on every submission | Rule deleted. `sajda_type` was never a column; `sajdah_number` is. |
| 2 | `rub_el_hizb_number` is **NOT NULL**, absent from the form, and the request validated a misnamed `rub_number` → the insert could never satisfy the constraint | Rule renamed to the real column, `required`; input added to both forms. |
| 3 | `ruku_number` was submitted by the form but had **no rule**, so `validated()` dropped it — another NOT NULL violation. `hizb_number` was `nullable` against a NOT NULL column. | Both now `required`, matching the schema. |
| 4 | The requests **required** `text_uthmani`, `text_simple`, `translations.*`, `transliteration`, `audio_urls`, `words_count`, `letters_count` — none of which are columns on `quran_verses`. `Verse::create()` discarded them via `$fillable`, and the forms never submitted them, so validation failed regardless. | Rules removed. Verse text lives per-word in `quran_words.text_uthmani`; translations live in `quran_verse_translations` (needs a `resource_id`). |

Also fixed in passing: `VerseController:113` read `$verse->is_sajda`, which is neither a
column nor an accessor — it returned `null`, so `QuranCacheKeys::sajdaVerses()` was never
actually cleared on create. Now `$verse->hasSajdah()`.

**Tests added** — `tests/Feature/Admin/Quran/VerseCrudTest.php`, 7 cases. There were
previously **zero** tests on this controller, which is why all of the above shipped. Two
of them are drift guards that reflect on `rules()` and assert the form carries an input
for every `required` field — the specific failure mode that caused defects 2 and 3.

**Suite:** 892 → **899 passing**, same 9 pre-existing failures, none in Quran.

**Follow-up not in scope:** `VerseController::show():146-147` reads
`$verse->words_count` / `$verse->letters_count`, neither of which are columns, so the
admin verse stats card always shows 0. Non-fatal (`?? 0` swallows it), same defect class.

**Open question for the owner:** the Quran is a fixed corpus of 6,236 verses, imported via
`QuranSyncService` / `SyncQuranCommand`. Manual verse *creation* through the admin panel
is arguably a data-integrity hazard that should not exist — the useful operation is
*editing* an imported verse's metadata. Consider removing `create`/`store` and keeping
`edit`/`update`. That is a product decision, so it was not made here.

### T0.2, T0.3, T0.6 — completed 17 Aug 2026

**T0.2 · phantom exception classes** (estimate 0.25 d, actual ~0.5 d)
All 22 sites swapped to `App\Domain\Shared\Exceptions\BusinessRuleViolationException`.
The controllers only ever read `getMessage()` and hardcode their own status, so the swap
is behaviour-identical.

Refinement on the earlier characterisation — there were **two** pre-fix failure modes,
not one:
- `SchedulerController::update` / `cancel` have no `\Throwable` fallback, so the
  class-not-found `Error` propagated → genuine **500**.
- `SchedulerController::store` and both Tafseer paths *do* have one, so the guard returned
  a generic 400/422 with the internal `Class "…" not found` text **leaked to the user**.

Tests: `tests/Feature/Admin/BusinessRuleGuardsTest.php` (4 cases), including a structural
guard asserting every `use App\…` in the four files resolves to a real class — the defect
class, not just these instances.

**T0.3 · bulk-operation preview** (estimate 0.5 d, actual ~0.5 d)
Replaced the one-size-fits-all `->get(['id','title','name'])` with two overridable hooks,
`previewQuery()` and `previewLabel()`, keeping the handlers thin and declarative as before.
Each of the four now declares its own label source: `articles.title` (Spatie JSON, read via
`getTranslation()`), `quran_verses.verse_key`, and column-constrained `translations` eager
loads for Seerah and Media — with the owning FK kept in the select, or the relation comes
back empty and every label silently falls back to the id.

Tests: `tests/Feature/Admin/BulkOperationPreviewTest.php` (6 cases), covering all four
handlers, the 10-row cap, and the id fallback.

**T0.6 · four shipped bugs** (estimate 0.5 d, actual ~1 d)
1. `SurahResource` now emits `id` as `number`.
2. `TranslationResource` takes the edition from the `whenLoaded()` closure argument. Root
   cause: inside a `JsonResource`, `$this->resource` is Laravel's own property holding the
   *wrapped model*, not the `resource()` belongsTo. Also widened
   `VerseRepository::TRANSLATION_RESOURCE_EAGER` to `id,slug,name,language,is_default` —
   it omitted the two columns `getName()` reads, so the fix alone would have returned null.
3. `roles/create` is now registered before `roles/{role}`, in its own `role:super_admin`
   group (the two blocks carry different middleware and could not simply be merged), and
   excluded from the later resource so no duplicate route name exists. Verified by
   resolving the URL through the router: `admin.roles.create → RoleController@create`.
4. `FilterController::apply` maps results through `SearchResultResource`, reusing what
   `SearchController::searchWithin` already does with the same service output.

**The leak was worse than reported.** Reverting the fix and re-running the test showed the
response carried the writer's entire user record — `email`, `email_verified_at`, `provider`,
`provider_id`, `language`, `preferences`, `last_login_at` — plus every article column
including full `content` JSON in all locales, `statistics`, `references` and all `locales[]`
rows. The audit described it as a writer-email leak.

Tests: `tests/Feature/Api/ShippedBugRegressionTest.php` (4 cases). The leak test was
**verified to fail without the fix**, and carries a `meta.total > 0` assertion so it can
never pass vacuously — the first draft did exactly that, because the article had no
`is_ready` locale row and `ArticlePublicVisibility` filtered it out.

**Suite: 892 → 916 passing.** Same 9 pre-existing failures, none newly introduced.
`deptrac` 0 violations, Pint clean.

> **Test-database note.** The shared `the_last_messenger_test` database collides when two
> sessions run `RefreshDatabase` concurrently (`Failed to open the referenced table
> 'users'`). These runs used a separate `tlm_test_s2` database. Worth folding into T5.6
> (dev parity): give each worker its own database, or adopt Paratest properly.

---

### Phase 1 — completed 17 Aug 2026 (9 of 11; T1.5/T1.6 need production access)

**Suite: 892 passing / 9 failing → 929 passing / 0 failing** (1 incomplete, 1 skipped, both
documented below). `deptrac` 0 violations. The green suite is now a CI gate.

| Task | Outcome |
|---|---|
| T1.1 | **79** broad `catch` blocks instrumented with `report($e)` across 26 controllers. `report()` calls 80 → 170. |
| T1.2 | `DashboardService` ×4 + `DashboardAnalyticsService::getMonthlyGrowthForTable` — the pattern CLAUDE.md documents, closed in the file that caused it. |
| T1.3 | `QuranReaderService` ×6 — resilient fallbacks kept, failures now traceable. |
| T1.4 | `AddRequestId` calls `Log::withContext()` and is registered on `web`, not just `api`. |
| T1.7 | All 9 long-standing failures triaged (detail below). |
| T1.8 | `AdminRouteSmokeTest` — 129 static admin GET routes, **found a live 500 on its first run**. |
| T1.9 | CI `test` job with a MySQL 8 service; `deploy` now `needs: [architecture, test]`. |
| T1.10 | `BaseService` made generic over its repository; 13 subclasses annotated. |
| T1.11 | deptrac coverage widened from ~16% of the graph; 21 real violations surfaced and baselined. |

#### Corrections to the audit findings

**T1.10 — the recommended fix would have fataled the app.** The architecture audit called for
"14 one-line redeclarations" of `protected XRepositoryInterface $repository`. PHP property
types are **invariant**: a child narrowing a typed property is a fatal error
(`Type of CatSvc::$repository must be RepoI`), verified before writing any of it. Implemented
as PHPStan generics instead — `@template TRepository` on `BaseService`, `@extends
BaseService<XRepositoryInterface>` on each child. Zero runtime change; the ~98 repository
calls become checkable once Larastan lands (T5.4), which is the only reason this task exists.

**`InteractionService` was never a `BaseService`.** It holds two repositories and never
assigned `$this->repository`, so all nine inherited methods would have raised "typed property
must not be accessed before initialization". Nothing called them, so the trap was latent. The
`extends` is removed rather than papered over by picking one of its two repositories.

**T1.1 — 81 unlogged, not "a majority of 103".** Counted precisely: 53 Admin, 15 Site, 13 Api.
Of those, 79 are broad `\Throwable`/`\Exception` swallows and were instrumented; the one
`\ValueError` and all narrow domain-exception catches were left alone, since
`catch (ResourceNotFoundException) → abort(404)` is expected control flow, not an error.

**T1.11 — my first ruleset was wrong, and baselining it would have been worse.** Declaring the
missing layers surfaced 325 violations, but 206 were artefacts of my own over-strict ruleset:
`Controllers → Requests` (119) and `Controllers → Resources` (87) are *the* Laravel idiom.
Baselining those would have enshrined "controllers may not type-hint FormRequests" as a rule
and hidden it. After correcting the ruleset, 22 genuine violations remained; 21 are baselined
as known debt (16 domain-DTO → FormRequest inversions, 5 domain-service → concrete Job), and
`Controllers → Jobs` was allowed as idiomatic dispatch. See the header comment in
`deptrac.yaml`.

#### T1.8 found a live 500 on its first run

`GET su/portal/v1/admin/components-demo` returned `View [admin.components-demo] not found`.
The route was dev scaffolding returning a view that was never created — a guaranteed 500 for
its entire life. Route removed. 129 of 130 static admin GET routes were already fine.

#### T1.7 — the nine failures, resolved

Every one was a **stale test**, not a live defect. Verified against the code in each case:

- **`ArticleSeriesIntegrationTest` ×3** — `SeriesController` deliberately redirects to
  `admin.series.show` after store/update (matching `admin.media-programs.show`); the tests
  asserted `.index`. Tests updated.
- **`ContactFormTest`** — the payload contained `،` and a full stop, which
  `StoreContactMessageRequest` rejects **by design**: its own comment reads *"Blocking . : / @
  kills the URLs every lead-gen spam carries"*, alongside a honeypot. The rule is a deliberate
  trade-off (a visitor cannot end a sentence with a full stop) and is the owner's call, so the
  payload was fixed and a test added pinning the anti-spam behaviour.
- **`ArticlePublicPagesTest` ×2** — (a) asserted the related-articles heading on a lone
  article, but `show.blade.php:1031` guards that section with `@if (! empty($related))`, so its
  absence is correct and related articles have their own passing test; (b) asserted a plain GET
  increments views, but counting moved to a session-guarded, throttled
  `POST /articles/{id}/view` precisely to stop bots inflating it. Rewritten against the real
  endpoint, now also asserting that rendering alone does *not* count.
- **`ContentPublicPagesTest`** — asserted the header nav contains `المواضيع` (Topics), which
  `MenuSeeder` has never seeded. Now asserts labels the seeder actually creates.
- **`MuslimScholarsSahabaImportTest`** — depends on an untracked 7,806-row CSV under
  `storage/app/` and hard-asserts counts from that snapshot; it can never pass on a fresh clone
  or in CI. Now **skips** with an explicit reason when the artefact is absent.
- **`ArticleCrudTest`** — marked **incomplete**, needs your decision. `git log -S` shows the
  assertions and the standalone `openModuleSelector` buttons they forbid arrived in the *same
  commit* (f4e703a), so it was committed already-red. `_form-wizard.blade.php:600-612` still
  renders four block buttons alongside the in-editor `ql-module-btn--*` toolbar — two insertion
  paths, and only the Quill one goes through the registered blot. Either finish removing the
  buttons (the stated intent) or delete the test. Deleting live admin UI is not a test-triage
  decision.

#### Not done — needs access you have and I don't

- **T1.5** external uptime monitor on `/up` + error tracking (Sentry/GlitchTip). Needs an
  account. **This is the single highest-value remaining item in Phase 1**: mean-time-to-detect
  for a production 500 is currently "whenever a human looks", with no floor.
- **T1.6** schedule `deploy/stages/90-healthcheck.sh` as cron with alerting. Needs production.

#### Pint is informational, not blocking

`pint --test` fails on ~110 pre-existing files (`database/`, `config/`, `resources/lang/`, parts
of `app/`) on whitespace, import order and fully-qualified-type rules only — nothing semantic.
The `style` job runs with `continue-on-error: true` and is **not** in `deploy.needs`. Gating on
it today would fail every deploy, and reformatting 110 files in one sweep would collide with
in-flight work. Run `vendor/bin/pint` as its own commit in Phase 5, then add `style` to the gate.

---

### Phase 2 — completed 17 Aug 2026 (5 of 8; T2.5/T2.6/T2.8 need production)

**Suite: 929 → 945 passing, 0 failing.** deptrac 0 violations.

| Task | Outcome |
|---|---|
| T2.1 | `CACHEABLE_ROUTES` **2 → 38**. Module landing pages (the slowest measured group, p95 2.19 s), the whole Quran reader (1.69 s), counter-free detail pages and static pages. |
| T2.2 | `Cache::flexible` on the 6 expensive fixed-key rebuilds in Home/Article controllers. |
| T2.3 | Unified search now uses the FULLTEXT indexes; 3 dead search scopes deleted. |
| T2.4 | Migration adding 6 missing indexes and dropping 2 duplicate pairs — reversible and idempotent. |
| T2.7 | `/robots.txt` nginx fix **plus** a full rewrite as an allow-list (separate request). |

#### T2.1 — what governs the allow-list

Three things disqualify a route, and the reasoning is now in `CacheGuestPages` so nobody
re-derives it. Per-*user* variation is fine (the middleware only marks a response cacheable
for guests, and authenticated responses get `private, no-store`); what breaks caching is
per-*guest* variation, a `@csrf` form (the cacheable branch strips `Set-Cookie`, so any token
in the HTML is a stale one belonging to whoever's render got cached), or a write on the GET
path (an edge hit never reaches PHP, so a view counter simply stops counting — queueing does
not help if the request never arrives).

Excluded with reasons recorded: `public.seerah.event` (the last synchronous GET-path counter,
`SeerahEventService:66,84` — add it after M4.5), the three media detail routes, search, and
all auth/per-user pages.

**`public.articles.show` is excluded, and this one is worth acting on.** Its reader already
records views via an async POST *specifically* so the page could be cached — the code comment
says so. But that POST is CSRF-protected and takes its token from `<meta name="csrf-token">`
in the cached HTML, so a cookieless visitor would 419 and the counter would silently stop.
Exempting that one route from CSRF (it is already session-guarded and throttled 30/min) makes
the whole `article` group cacheable — p95 1.46 s under stress. I left it alone because
changing a CSRF exemption is a security decision, not a caching one.

Tests: `GuestPageCachingTest` (5 cases) pins the guarantees rather than the list — cookies
dropped, authenticated never shared, non-default-locale never shared, every listed route name
actually registered, and the reasoned exclusions still excluded.

#### T2.3 — the plan's premise was wrong, and the real fix was elsewhere

The plan said to make models implement `getFullTextColumns()`. **That could never have
worked.** `MATCH()` requires an index covering exactly the listed columns and cannot span
tables — and for every content module the searchable text lives in a per-locale translation
table. Verified against the live schema: only `hadiths` and `muslims` carry FULLTEXT on the
base table, and those are *separate single-column* indexes, so `MATCH(search_name_ar,
search_name_en)` would be a MySQL error.

So the MATCH has to go *inside* the relationship subquery. `SearchModule::fullTextGroups()`
now carries that mapping and `likeOnlyFields()` keeps the columns no index covers on LIKE, so
narrowing coverage never silently drops a field from search. Result per module: Quran, Media
and Sahaba are pure MATCH; Article and Seerah are MATCH plus one LIKE (`locales.content`,
`location`).

Also deleted three `scopeSearch` methods with **zero callers** — every `->search()` in the
codebase is on a repository or service. One of them, `Reciter::scopeSearch`, did
`JSON_EXTRACT(name, '$.ar')` against a table (`quran_reciters`) that has no `name` column, so
it would have thrown if ever called. The plan listed `VerseTranslation:126`'s OR'd MATCH/LIKE
as a live index-defeating query; it was dead code.

#### The FULLTEXT change has a real cost, and it is in the test suite

**InnoDB does not populate a FULLTEXT index until the inserting transaction COMMITS.**
`RefreshDatabase` wraps each test in a transaction it rolls back, so `MATCH` against
just-seeded rows matches nothing — a valid query silently returning zero rows, which reads as
a search bug. Five classes had to move to truncation.

That created a second, subtler failure worth recording. Truncation wipes tables that **data
migrations** seeded, and `RefreshDatabase` runs migrations once for the whole suite, so
nothing restores them. Wiping `seerah_event_types` made `SeerahEventRepositoryTest` fail with
0 results several classes later, for no visible reason.

`tests/Support/CommitsForFullTextSearch` handles both halves: it truncates in `tearDown` too
(the built-in trait only truncates *before* each test, leaving the last test's rows behind),
and it excludes the migration-seeded reference tables. Any future FULLTEXT test should use it.

#### T2.4 — indexes

Added: `articles(status, created_at)`, `articles(status, views)`,
`media_programs(is_published, created_at)`, FULLTEXT on `quran_verse_tafsirs.text`,
`search_history(query(64))`, `user_interactions(interaction_type, created_at)`.
Dropped: `idx_articles_status` and `articles_slug_index`, duplicates of
`articles_status_index` and the `slug` UNIQUE — four indexes doing the work of two on the
busiest table.

Every entry verified against the live production schema, not the migration history. Guarded
with existence checks throughout, and rollback + re-apply were both tested.

#### T2.7 — robots.txt

The nginx fix: `location = /robots.txt` had no `try_files`, so nginx looked for a static
`public/robots.txt` that does not exist and 404'd before Laravel's dynamic route ran. `.txt`
is not in the static-asset regex either, so nothing else matched. Every crawler was getting a
404. (Found by the load test — all 137 of its "failures" were this one URL.)

Then rewritten as an **allow-list** on request: named search engines and AI agents may crawl,
`User-agent: *` is denied everything, and admin/auth/per-user paths are closed even to allowed
agents. Lists live in `config/robots.php` with search engines and AI agents kept separate, so
the position on AI training can change without touching search indexing. Two correctness
details: no trailing empty `Disallow:` (it would contradict the specific rules in the same
group, and parsers differ on how they resolve that), and `/search` rather than `/search?`
(path matching is prefix-based and parsers disagree about whether the query string
participates). 6 tests. **robots.txt is advisory** — compliant crawlers honour it, scrapers
ignore it; nginx rate limiting and Cloudflare bot rules are what actually stop abuse.

#### Not done — production access required

- **T2.5** MySQL retune (buffer pool 1G→384M, `performance_schema` off, `max_connections`
  100→40) and `pm.max_children` 16→10. Frees ~800 MB–1 GB. Restarts MySQL; off-peak.
- **T2.6** nginx `worker_connections` 768→4096, listen backlogs, fastcgi buffers. Needs
  `restart`, not `reload`.
- **T2.8** Re-run the stress scenario and compare against the 16 Aug baseline (p95 1.69 s at
  60 VU). **The capacity numbers in this plan stay projections until this runs.**

Also still pending from Phase 0: **T0.4** and **T0.5**. T2.2's `Cache::flexible` works on the
live `file` driver today (`FileStore` implements `LockProvider`, so it was never blocked on
Redis as the plan assumed), but Redis makes the locks atomic rather than filesystem-based.

---

### Production application — 17 Aug 2026

Applied during a quiet window (load1 0.04). Every file changed on the box was backed up
first. Suite 946 passing / 0 failing, deptrac clean, before and after.

#### The access constraint that shaped this

`deployer` has `(ALL:ALL) ALL` in sudoers but **NOPASSWD only for**
`systemctl reload php8.5-fpm`, `systemctl reload nginx`, and `supervisorctl`. Everything
else — writing root-owned config under `/etc`, `systemctl restart mysql|nginx` — needs a
password. So the work split into what could be applied and what is staged in the deploy
scripts awaiting one privileged run.

One thing that turned out to be possible after checking rather than assuming: **`deployer`
is a member of the `www-data` group (33)**, so it can `chgrp www-data` its own files with no
root at all. That is what made the outage fix applicable immediately.

#### APPLIED and verified

**T0.4 — the config-cache outage switch is closed.**
`shared/.env` was `600 deployer:deployer`; now `640 deployer:www-data`. Verified at the
actual runtime user, not by inference: a throwaway probe served through php-fpm reported
`runas=www-data, readable=yes`, then deleted itself via a shell trap. php-fpm can now rebuild
config from `.env`, so clearing the config cache no longer takes the site down.

**T0.5 — live `.env` drift closed.**
`CACHE_STORE` `file` → `redis`; `DB_QUEUE_RETRY_AFTER=3700` added (it was absent, so the
queue was resolving 90s against a 3600s job timeout — the stuck-import mechanism). Then
`config:cache`, then **one manual `supervisorctl restart all`** — required because
`queue:restart` writes its flag to the cache store, and workers booted under `file` would
never see a flag written to Redis.

Verified: `cache.default=redis`, `queue.connections.database.retry_after=3700`, all five
supervisor programs RUNNING, and Redis key count rising as pages are served (cache is
genuinely landing there, not just configured).

**T2.4 — indexes applied.** Migration run on production, **59 seconds**. Adding the first
FULLTEXT index to a table forces an InnoDB rebuild, and `quran_verse_tafsirs` is 288 MB /
66k rows — that is where the time went. load1 peaked 1.29 during it and settled straight
back to 0.06.

All six indexes present with FULLTEXT correctly typed, both duplicate pairs dropped, and
**`articles_slug_unique` verified still intact** (`NON_UNIQUE=0`) — dropping the wrong one
of that pair would have removed uniqueness. `EXPLAIN` on the public listing now reports
`articles_status_created_at_index` with *"Backward index scan; Using index"*; the filesort
is gone.

#### STAGED in the deploy scripts — one privileged run away

| Change | File | Why it needs root |
|---|---|---|
| buffer pool 1G→384M, `max_connections` 100→40, `performance_schema` OFF | `config.env.example`, `templates/mysqld-tuning.cnf` | root-owned `/etc/mysql`, restarts MySQL |
| `pm.max_children` 16→10, `listen.backlog = 4096` | `config.env.example`, `templates/php-fpm-pool.conf` | root-owned pool config |
| `worker_connections` 768→4096, `multi_accept on` | `stages/65-nginx.sh` (idempotent in-place patch, same style as the existing `server_tokens` one) | root-owned `nginx.conf`; **read at startup, so `reload` will not apply it** |
| `keepalive_requests 10000`, `fastcgi_buffers 16 32k`, `fastcgi_busy_buffers_size 64k` | `templates/nginx-global.conf`, `templates/nginx-app.conf` | root-owned `/etc/nginx` |
| `/robots.txt` `try_files` | `templates/nginx-app.conf` | root-owned snippet |

Self-healing changes that need no privileged run, they just take effect on the next deploy:

- `deploy.sh` re-asserts `.env` mode/group on **every** deploy. The root cause was never the
  provision stage (it does set `640 deployer:www-data`) — it was that a hand-edit of
  `shared/.env` resets mode and owner and nothing put them back.
- `deploy.sh` warns when `shared/.env` is missing any key present in `env.production`
  (warn-only; a missing key must not block a deploy, but it must stop being invisible).
  Logic dry-run tested.
- `sync-database-to-production.sh` now rebuilds all four caches after its `optimize:clear`.
  It previously left the box with **no** `bootstrap/cache/config.php` until the next code
  deploy happened to recreate it.
- `config/artisan-commands.php`: all ten cache commands are now `'queued' => true`, so they
  run in a worker (as the deploy user) instead of inline as www-data. A test pins this —
  `ArtisanCommandsSettingsTest::test_cache_clearing_commands_are_queued_never_run_in_the_request`
  — and the pre-existing inline test moved from `cache:clear` to `migrate:status`.

#### Discovery: Cloudflare is serving its own robots.txt

The new allow-list robots.txt **will not reach crawlers as things stand**:

| | Status | Server | Bytes | Sitemap directive |
|---|---|---|---|---|
| Through Cloudflare | 200 | `cloudflare` | 1836 | **absent** |
| Direct to origin | **404** | `nginx` | 146 | — |

Cloudflare's managed robots.txt (its AI content-signals feature) intercepts the path
entirely. So both things are true: the nginx `try_files` bug is real at origin (which is
why the origin-direct load test saw 137 404s), and fixing it changes nothing at the edge
until the managed file is turned off in the Cloudflare dashboard — **AI Crawl Control →
manage robots.txt**. Worth noting Cloudflare's version publishes no `Sitemap:` line, so
right now no sitemap is being advertised to any crawler at all.

#### Not yet visible in production

`CACHEABLE_ROUTES` (2 → 38) is application code, so `/articles` still answers
`cache-control: no-cache, private` / `cf-cache-status: DYNAMIC`. It takes effect on the next
deploy — and that is the single largest capacity change in the plan.

---

### Phase 3 — completed 18 Aug 2026 (8 of 9; CSP deferred)

**Suite: 949 → 955 passing, 0 failing.** deptrac 0 violations.

| Task | Outcome |
|---|---|
| T3.1 | Articles + media sanitised on input; YouTube text sanitised at write time |
| T3.2 | `permission:`/`role:` gates on 8 admin route groups + 2 bypass routes |
| T3.3 | `render_template` installs with an explicit mode instead of inheriting umask |
| T3.4 | 3 amplifier routes throttled; `rememberForever` null-caching removed |
| T3.5 | Bulk-operation IDOR closed on `download`/`status`/`destroy` |
| T3.6 | JSON-LD `</script>` breakout closed |
| T3.8 | Admin password policy, failed-login logging, timing oracle |
| T3.9 | Cloudflare real-IP now fails closed |

#### T3.1 — the fix needed an architectural move first

`YouTubeSyncService` is domain code and the purifier lived in `App\Http\Requests`. Using it
there would have added a **new** Domain → Http violation — one of the exact 16 already
baselined. So the allowlist moved to `App\Domain\Shared\Services\RichTextSanitizer`, the
FormRequest trait became a thin adapter over it, and both layers now share one invariant:
nothing reaches a rich-text column without passing through it. deptrac stayed at 0.

Articles needed their own concern rather than the generic `sanitizeRichTextField()`, because
`content[$locale]` is **either** a legacy HTML string **or** an array of blocks. Only
`wysiwyg` blocks carry author markup — verse/hadith/sahabi/seerah blocks are re-rendered
server-side through `e()`, so purifying them would strip the embed structure the reader
depends on.

**A pre-existing bug surfaced while verifying this.** `data-list` on `<li>` — which Quill 2
uses to distinguish bullet from ordered — was being stripped on *every* save, despite a code
comment stating it "must survive sanitization to round-trip". `addAttribute()` alone does not
do it: `HTML.Allowed` builds its own attribute whitelist and filters out anything absent from
it. The embed `<div>` survived only because its attributes were spelled out there. Fixed by
listing `li[data-list]`. This was not a regression from the refactor — the original config had
the same flaw, and no test covered it.

#### T3.2 — closed with existing primitives, no re-seed

`view analytics` and `manage settings` exist in the seeder, so analytics and language
settings are gated by permission. Audit, versions, scheduler, export, import and activity
logs have no dedicated permission, so they are gated by role — the point is that an `editor`
cannot reach them, and role is the primitive that already exists. Inventing permissions would
have required re-seeding production.

Two routes bypassed their own group and were missed by the plan: the standalone
`admin.analytics` alias (a way straight past the analytics gate) and
`POST dashboard/cache/clear` (a write inside the editor-readable dashboard group).

Verified behaviourally in `AdminAuthorizationTest`, not by reading middleware lists — the
outer group's `...|editor` string is present on every route regardless of what inner gate also
applies, so static inspection cannot tell you anything. The test also asserts the mirror
image: admins still reach all six surfaces, and editors keep their content access.

#### T3.8 — the dummy hash had to be real

Equalising the login timing needs a *valid* bcrypt hash; my first hand-written constant was
malformed, so `password_verify` returned instantly and the oracle would have stayed open. Now
generated with `password_hash(..., cost 12)` and verified through the class constant itself:
60 chars, `algo=bcrypt`, **243 ms** of real work, never matches.

Admin-created accounts had the weakest policy in the application — bare `min:8`, weaker than
public registration — despite reaching `/su/portal/v1/admin`. Now 12 chars with mixed case,
numbers and symbols. Two test fixtures used `Secret123` and were updated.

#### T3.4 — the false comment

`StaticPageService` carried the comment *"rememberForever skips storing null, so misses stay
uncached"*. Laravel's implementation has no null check, so every miss on the public
`/pages/{slug}` route wrote a **permanent** entry; under Redis with `allkeys-lru` that evicts
real cache and live sessions, logging out every user. Now a hit caches for an hour and a miss
caches nothing.

#### NOT done — T3.7 CSP

Security headers were fixed (`nginx-app.conf`'s asset block was silently discarding all six
inherited headers, because declaring *any* `add_header` in a nested block drops the enclosing
server's set — so every .js/.css/image shipped without HSTS or nosniff). But **Content-Security-Policy
is deliberately not added yet.**

The app inlines Alpine `x-data`/`@click` attributes throughout, so any workable policy needs
`'unsafe-inline'` for scripts — which defeats most of the point. A real CSP here means either
nonces threaded through every inline handler, or moving Alpine initialisation into bundled
files. That is its own piece of work, and it belongs after T3.1 has been running long enough
to trust that injection is closed at the source. Starting it as a `Content-Security-Policy-Report-Only`
header is the sensible first step.

#### Applying these

All of Phase 3 is code and deploy-script changes — **none of it is live yet**. The nginx
pieces (T3.3 mode fix, T3.6 headers, T3.9 fail-closed) need the same privileged provision run
already outstanding from the last session:

    sudo FORCE=1 ./provision.sh 65-nginx && sudo systemctl restart nginx

The application changes (T3.1, T3.2, T3.4, T3.5, T3.8) ship on the next deploy.

---

### Incident — nginx stage 65 dropped HTTPS, 18 Aug 2026

**~11:25–11:35 UTC. Cloudflare 521, origin refusing :443. Port 80 stayed up.**

`provision.sh 65-nginx` is the **HTTP-first** stage. It renders `nginx-http.conf` over
`sites-available/<domain>` on purpose, so the site is reachable for ACME before a
certificate exists; stage **70-ssl** then layers the TLS server block back on. Running 65
alone on an already-HTTPS box overwrites the TLS block and unbinds 443.

The stage said so in its own output — *"Nginx serving HTTP for rasoolallah.com (run stage
70-ssl for HTTPS)"* — and I had read and edited `65-nginx.sh` closely enough to add the
`worker_connections` patch to it. I recommended running it anyway.

**Recovery:** restore the backup the stage itself had just written, then reload.

    cp /etc/nginx/sites-available/rasoolallah.com.bak.<stamp> \
       /etc/nginx/sites-available/rasoolallah.com && nginx -t && systemctl reload nginx

Everything else stage 65 changed lives in other files, so the restore cost nothing:
`worker_connections 4096` and `multi_accept` are in `nginx.conf`; the security headers,
Cloudflare real-IP (20 ranges) and the `/robots.txt` `try_files` are in `tlm-global.conf`,
`tlm-app.conf` and `tlm-cloudflare-realip.conf`. Origin `/robots.txt` went 404 → **200**.

#### Why the verification did not catch it

The apply script checked `nginx -t`, `systemctl is-active nginx`, and that
`worker_connections` had landed. All three pass on a valid HTTP-only config with no TLS
block at all. **It verified the things I changed instead of the thing that mattered** — that
the site still answers on 443. The `--all` path had a homepage check at the end; the
nginx-only path I actually recommended did not.

#### A worse hazard found while auditing the remaining stages

Stage 55-mysql issues:

    ALTER USER 'root'@'localhost'   IDENTIFIED ... BY '${DB_ROOT_PASSWORD}';
    ALTER USER '<db_user>'@'...'    IDENTIFIED ... BY '${DB_PASSWORD}';

`resolve_secret()` resolves config.env → `/root/.tlm-credentials` → **generate**. So if the
credentials file is missing a key, or holds a value that has drifted from the password the
application actually uses in `shared/.env`, stage 55 rewrites the database user's password
and the app loses access the instant MySQL restarts. Checking only that the key *exists* is
not enough — it has to *match*.

Stage 60-redis restarts Redis, and `SESSION_DRIVER=redis` with `save ""` / `appendonly no`
means every signed-in user is logged out and the cache is cold.

#### `~/tlm-provision/apply.sh` rewritten

- **Stage 65 is no longer offered at all**, with the reason recorded inline.
- `verify_site()` runs before and after every step and fails the script unless origin HTTPS
  returns 200 — the check whose absence caused the outage.
- MySQL is gated on `DB_PASSWORD` in `/root/.tlm-credentials` **matching** the app's live
  `.env`, not merely existing, and afterwards proves the *application* can query the
  database rather than just that mysqld is running.
- Redis carries an explicit session-loss warning.
- Each stage is opt-in: `--php`, `--mysql`, `--redis`.

**Standing lesson:** verify the user-visible contract, not the change. A provisioning stage
that reports success, passes its own config test and leaves the service "active" can still
have removed the thing everyone depends on.

#### Follow-on: php-fpm template broke on a comment character (18 Aug, no downtime)

The `listen.backlog` note I added to `php-fpm-pool.conf` used `#` comments. FPM pool files
are INI — comments must start with `;`, and a `#` line is parsed as a config entry:

    ERROR: [/etc/php/8.5/fpm/pool.d/www.conf:10] value is NULL for a ZEND_INI_PARSER_ENTRY

**No outage.** Stage 40 runs `php-fpm -t` *before* restarting, so it aborted at validation
and the running service kept serving from the config already in memory. The on-disk file was
left broken though — a reboot or deploy would have failed to start php-fpm.

Fixed to `;`, and the other two PHP-syntax templates (`php-tuning.ini`, `opcache.ini`) swept
clean. nginx / redis / mysql templates legitimately use `#`; mixing the dialects was the bug.

**The check that would have caught it, now the standard for any templated config:** render
the template with `envsubst` and parse it the way the consuming daemon does, locally, before
it reaches a server. Two seconds:

    envsubst '...' < deploy/templates/php-fpm-pool.conf > /tmp/www.conf
    # then assert every non-comment line is a valid `key = value`

Both of today's failures share one root cause — changing infrastructure config without
exercising the parser that actually reads it.

#### Applied to production, 18 Aug 2026

| Change | State |
|---|---|
| nginx `worker_connections` 768 → 4096, `multi_accept` | live |
| Cloudflare real-IP (fail-closed, 20 ranges) | live |
| Security headers re-emitted on static assets | live |
| `/robots.txt` at origin | 404 → **200** |
| `render_template` explicit 0640 mode | live |
| php-fpm `max_children` 16 → 10, `listen.backlog` 4096 | live |
| MySQL tuning (buffer pool, max_connections, perf_schema) | live (see below) |
| Redis config perms + `maxmemory` | live (see below) |

Post-change health: 8/8 public endpoints 200 (38–167 ms), 6 live fpm children of 10, socket
Recv-Q 0, 1.5 GB memory available. MySQL still holds the 1 GB buffer pool, so ~700 MB of that
is recoverable whenever `--mysql` runs.


#### MySQL + Redis applied, 18 Aug 2026

`apply.sh --mysql --redis`, run by the operator as root. Verified independently
afterwards through the application's own credentials, not just `systemctl is-active`.

| Setting | Before | After |
|---|---|---|
| `innodb_buffer_pool_size` | 1 GB | **384 MB** |
| `max_connections` | 151 | **40** |
| `performance_schema` | ON | **OFF** |
| `mysqld` RSS | ~1.2 GB | **509 MB** |
| Memory available | ~1.5 GB | **2.48 GB** |
| Redis `maxmemory` | unbounded | **256 MB** (using 1.28 MB) |

Checks that actually prove it, rather than proving the daemon restarted:

- `DB::select("SELECT @@innodb_buffer_pool_size …")` through the app's `.env`
  credentials — confirms stage 55's `ALTER USER` did not drift the app's DB password.
  `DB::table("users")->count()` returns 5.
- `Cache::store("redis")->put/get` roundtrip returns `'ok'`; `session.driver = redis`.
- `stat /etc/redis/redis.conf.d/tlm.conf` as deployer now returns **Permission denied** —
  that is stage 60 succeeding, not failing.
- 8/8 public endpoints 200 (90–507 ms; `/hadith` is the slow one at 507 ms and is the
  next profiling target).

**Blocker recorded for future infra work.** Neither of these could be run from this
session, and it is not a transient failure:

- `deployer` is granted `(ALL : ALL) ALL` in sudoers but its password is **locked**
  (`passwd -S deployer` → `L`), so `sudo` can never authenticate it.
- The only NOPASSWD grants are `systemctl reload php8.5-fpm`, `systemctl reload nginx`,
  and `supervisorctl` — none reach MySQL or Redis.
- Root SSH rejects the deploy key (`Permission denied (publickey)`), despite
  `PermitRootLogin prohibit-password` winning in `00-hardening.conf`.

Every privileged stage therefore needs a human at a root shell. Setting a password for
`deployer` (once, as root) would make its existing sudo grant usable and remove the
handoff; adding the deploy key to `/root/.ssh/authorized_keys` would do the same more
broadly. Both are the operator's call.
