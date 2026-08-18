# Module enhancement & optimisation plan — 17 Aug 2026

A remediation plan built on the measured production review of 16 Aug, extended with
five fresh audits: 12-factor/DevOps, architecture & SOLID, security, testing, and PHP
code quality.

> **Source documents**
> - [PRODUCTION-REVIEW-2026-08-16.md](PRODUCTION-REVIEW-2026-08-16.md) — RAM diagnosis, load/stress results, consolidated code review
> - [COLUMN-SELECTION-AUDIT-2026-08-16.md](COLUMN-SELECTION-AUDIT-2026-08-16.md) — per-module column/relation audit, 87-endpoint API verdicts

## How to read this

Findings are grouped two ways, deliberately.

**Cross-cutting workstreams (W1–W6)** are changes whose value comes from being applied
uniformly — one base class, one middleware constant, one CI job. Doing these
module-by-module is strictly more expensive and leaves the codebase inconsistent.

**Module plans (M1–M11)** are the work that is genuinely local to one bounded context.

Every task carries an effort estimate in developer-days for a **solo maintainer already
fluent in this codebase**, and a payoff statement. Where a task's payoff is "none, but
it prevents recurrence," that is stated rather than dressed up.

The last section lists what is **already correct and must not be touched**. That list is
load-bearing: the biggest risk to a codebase in this shape is not the defects, it is a
well-intentioned sweep that churns working code.

---

## 0. Codebase at a glance

| | |
|---|---|
| Size | 871 PHP files / **105,497 LOC**, 310 Blade views, 140 migrations |
| Largest units | Shared 18.7k LOC · Quran 16.6k (194 files) · Admin controllers 12.6k (49 files) |
| Tests | 108 test classes, **901 tests**, ~2:45 wall clock, 9 currently failing |
| `declare(strict_types=1)` | **866 / 871 files (99.4%)** |
| Repository interfaces | 42, against 45 repository classes |
| `deptrac` | **0 violations**, 1,366 allowed dependencies, 277 baselined |
| `pint --test` | 138 files fail — all whitespace/import-order, zero semantic |
| Measured capacity | p95 508 ms @ 18 VU → **1.69 s @ 60 VU**, 0 errors, 0 slow queries |

Two of these numbers should reset expectations before reading further.

**`strict_types` at 99.4% and deptrac at zero violations** mean the type-safety and
module-boundary foundations are genuinely sound. No workstream below targets them.

**901 tests that nothing runs** is the single most consequential fact in this document.
The suite exists, it is reasonably written, and CI gates deploys on `composer deptrac`
alone. Every defect in this plan shipped past a test suite that was never asked to
object.

---

## 1. P0 — broken right now

These are not debt. Each is a live fault on a reachable path, and each is small.
Nothing else in this plan should start before these land.

### P0.1 — Admin Quran verse create/update is dead (verified)

`app/Http/Requests/Admin/StoreVerseRequest.php:7,73` and
`UpdateVerseRequest.php:7,76` both call `SajdaType::values()` inside `rules()`.

```
App\Domain\Quran\Enums\SajdaType   →  class_exists() = false
app/Domain/Quran/Enums/            →  contains only RevelationPlace.php
```

Laravel evaluates `rules()` on **every** submission, so every save through
`admin.verses.store` / `admin.verses.update` (`routes/admin/quran.php:48`) fatals with
a class-not-found error.

Underneath it is a second bug: `sajda_type` is not a column. The migration
(`2025_11_29_100002_create_quran_verses_table.php:26`) and `Verse.php:52,133,196` all
use `sajdah_number`, and `isSajda()` derives from it. `is_sajda` is likewise not a
column.

**Fix:** delete the `sajda_type` rule, message and attribute entries from both files —
do not build the missing enum, nothing downstream reads it. **0.25 d.**

### P0.2 — Two more phantom exception classes, 22 sites (verified)

```
App\Exceptions\Domain\BusinessLogicException       →  MISSING
App\Exceptions\Domain\DuplicateResourceException   →  MISSING
App\Domain\Shared\Exceptions\BusinessRuleViolationException  →  EXISTS
```

`SchedulerController` + `SchedulerService` (9 sites) and `Quran\TafseerController` +
`TafseerService` (5 sites) `throw` the missing classes. Every legitimate business-rule
guard — "already has a pending schedule", "cannot be updated in its current status",
duplicate tafseer — **fatals instead of returning a handled 4xx**, and the controllers'
matching `catch` blocks are unreachable dead code.

**Fix:** mechanical swap to `BusinessRuleViolationException`, which already exists and
is used correctly in `ExportController`. **0.25 d.**

### P0.3 — The config-cache outage switch (verified on the live box)

```
.env        -rw------- deployer:deployer   (600, no ACL — group::--- other::---)
php-fpm     user = www-data, group = www-data
id www-data uid=33(www-data) gid=33(www-data) groups=33(www-data)
config.php  -rw-rw-r-- deployer:deployer   (664 — world-readable)
```

**php-fpm cannot read `.env` by any path.** The site works only because `deploy.sh`
bakes config into `bootstrap/cache/config.php` as `deployer`, and that file happens to
be world-readable.

`config/artisan-commands.php:98,101` whitelists `optimize:clear` and `config:clear`
with no `'queued' => true`, and `ArtisanCommandService::start()` runs non-queued
commands **inline in the web request, as www-data**:

```php
if ($meta['queued']) { RunArtisanCommandJob::dispatch($run->id); return $run; }
$this->execute($run);        // ← inline
```

An admin clicking **Clear Config Cache** deletes the only configuration source
`www-data` can read. Every subsequent request loses `APP_KEY` and the DB credentials.
Recovery requires SSH as `deployer`.

`deploy/stages/75-app.sh:33,36` *claims* to set `640 deployer:www-data`. It did not.

Same hole in `deploy/sync-database-to-production.sh:139-141` — runs `optimize:clear`,
never re-runs `config:cache`, unlike `deploy.sh`.

**Fix (three parts, all small):** correct the `.env` mode/group so www-data has a
working fallback; append `config:cache` to the DB-sync script; make the two clear
commands `'queued' => true` (workers run as `deployer`) or drop them from the
whitelist. **0.5 d.**

### P0.4 — Live `.env` drift

| Key | Template | **Live** | Consequence |
|---|---|---|---|
| `CACHE_STORE` | `redis` | **`file`** | 150 `Cache::remember()` sites on the filesystem; no atomic locks; no eviction |
| `DB_QUEUE_RETRY_AFTER` | `3700` | **missing** | resolves to **90 s** against a 3600 s job timeout — the stuck-import mechanism |

`deploy/stages/75-app.sh:25-32` writes `.env` only when absent, so no template edit has
reached the server since first provision.

**Fix:** set both keys on the live `.env`, then **one manual** `sudo supervisorctl
restart all` (`queue:restart` writes its flag to the cache store; workers booted under
`file` will never see a flag written to Redis). Then add a drift check to `deploy.sh`.
**0.5 d.**

### P0.5 — The four live application bugs already documented

Carried forward from the 16 Aug audit, unchanged:

| Bug | File | Fix |
|---|---|---|
| Quran API 500 on 4 endpoints | `TranslationResource.php:33-39` | bind `$edition = $this->resource->resource` once |
| Wrong surah number in API | `SurahResource.php:27` | `'number' => $this->id` |
| Role-creation page dead (TypeError 500) | `routes/admin.php:230` before `:247` | register `roles/create` first |
| PII leak — writer emails | `POST /api/v1/filters/{id}/apply` | reuse `asResources()` from `Api/ArticleController:164` |

**0.5 d for all four.**

### P0.6 — Bulk-operation preview throws on every module (verified on live schema)

`app/Domain/Shared/Services/AbstractBulkOperationHandler.php:61`

```php
->get(['id', 'title', 'name'])
```

Four handlers extend this base class — `ArticleBulkHandler`, `SeerahEventBulkHandler`,
`VerseBulkHandler`, `MediaProgramBulkHandler`. Checked against the **production**
`information_schema`:

| table | `title` | `name` |
|---|---|---|
| `articles` | yes | **no** |
| `seerah_events` | **no** | **no** |
| `quran_verses` | **no** | **no** |
| `media_programs` | **no** | **no** |

No table has both, so `preview()` raises `SQLSTATE[42S22]: Unknown column` for **all
four**. The `$item->title ?? $item->name ?? 'N/A'` fallback on the next line shows the
author anticipated a missing *attribute*; the failure happens earlier, in the SELECT.

This is precisely the dropped-column trap CLAUDE.md documents, sitting in a Shared base
class where it affects every module at once.

**Fix:** let each handler declare its own label column (`ArticleBulkHandler` → `title`,
the rest → their translation table), or select `['id']` and resolve the label through
the model's existing locale accessor. **0.5 d.**

**P0 total: ~2.5 developer-days.** Everything in it is a live fault.

---

## 2. Cross-cutting workstreams

### W1 — Capacity: make Cloudflare absorb the traffic

**The measured problem.** At 60 VUs p95 tripled to 1.69 s while `Slow_queries` stayed
at **0**, 2 GB of RAM stayed free, MySQL sat at 3 running threads, and 9 of 16 php-fpm
children never spawned. The constraint is PHP CPU on 2 vCPU — nothing else. Adding
RAM, workers, or a DB replica would each achieve exactly nothing.

The fix is therefore **less PHP work per request**, and the cheapest form of that is
not running PHP at all.

| Task | Detail | Effort | Payoff |
|---|---|---|---|
| **W1.1** Expand `CACHEABLE_ROUTES` | `CacheGuestPages.php:31` lists 2 routes. `/articles`, `/quran/*`, `/seerah/*`, `/hadith/*`, `/sahaba`, `/topics/*`, `/series/*` emit no `Cache-Control`, so Cloudflare will not cache them and ~95% of public HTML reaches PHP | **0.5 d** | Largest single capacity win in the codebase. One constant. |
| **W1.2** `CACHE_STORE=redis` | P0.4. Redis is installed, authenticated, idle (833 KB of 256 MB) | in P0 | Removes 150 filesystem round-trips per request path |
| **W1.3** Stampede protection | 150 `Cache::remember`, **0 `Cache::lock`, 0 `Cache::flexible`**. Worst: `Site/ArticleController:38` (rebuilds 500 articles), `Site/HomeController:42-140` (six keys incl. `inRandomOrder()` over ~38k hadiths) | **1 d** | Requires W1.2 first. `Cache::flexible()` (SWR) is the Laravel 11+ idiom |
| **W1.4** Route search through FULLTEXT | `SearchQueryBuilder.php:71` selects `MATCH/AGAINST` only if a model implements `getFullTextColumns()` — **no model does**, so 100% of search takes `applyBasicSearch()`. The indexes already exist and are ignored | **1 d** | One crawler on `/search?q=` currently saturates both cores |
| **W1.5** Missing indexes | `articles(status, created_at)`, `articles(status, views)`, `media_programs(is_published, created_at)`, FULLTEXT on `quran_verse_tafsirs.text`, `search_history(query(64))`, `user_interactions(interaction_type, created_at)`. Also drop 2 duplicate index pairs on `articles` | **0.5 d** | Verified absent against live `SHOW INDEX`, not against migrations |
| **W1.6** MySQL/php-fpm retune | buffer pool 1G→384M, `performance_schema=OFF`, `max_connections` 100→40, `pm.max_children` 16→10 | **0.5 d** | Frees ~800 MB–1 GB; removes a 4 GB overcommit against 3.7 GB RAM |

**W1 total: ~3.5 d.** This is the workstream that changes the capacity number.

### W2 — Column and relation discipline

The full evidence is in the companion audit; this is the plan shape.

**Root cause.** `BaseRepository` defaults `['*']` on all six read methods (`:51,69,83,
101,115,133`), four of which wrap the result in `Cache::remember` — so star-selected
rows are serialised into the cache too. `BaseService.php:50,62` doesn't even accept a
column list; it hardcodes `['*']`. **Every `$service->find()` in every module is an
unavoidable `SELECT *`.**

Changing that default is breaking. The order below is deliberate: establish
per-repository column constants first, change the default last.

| Task | Effort |
|---|---|
| **W2.1** Per-repository `LIST_COLUMNS` / `CARD_COLUMNS` constants (pattern already exists: `LiveChannelRepository::LIST_COLUMNS`, `SeriesService::ARTICLES_COLUMNS`) | per module, see M-plans |
| **W2.2** Then change the `BaseService`/`BaseRepository` `['*']` default | **0.5 d**, after W2.1 |

**The trap that governs this whole workstream.** A column-constrained query is only
correct if you check what the Resource actually reads — neither Eloquent nor Spatie
errors on a missing attribute, both return `null`/`""`. Six live endpoints already ship
silent nulls this way. Its mirror image: a constrained `with()` that omits the owning
foreign key returns an **empty relation**, silently.

Therefore **every W2 task must land with a test asserting a non-null field value**, not
just `assertJsonStructure`. See W4.2 — the two workstreams are coupled.

### W3 — Observability: stop failing silently

**193 `catch (\Throwable)` blocks against 80 `report()` calls.** The concentration is
located and the shape is uniform.

| Location | Catches | Shape |
|---|---|---|
| `app/Http/Controllers/Admin` | **103** | catch → translated `admin.*_failed` flash → **no log** |
| `app/Http/Controllers/Site` | 49 | same |
| `app/Http/Controllers/Api` | 19 | same |
| `DashboardService` | 4 | zeroed fallback, no `report()` — **the exact pattern CLAUDE.md documents, still open in the file that caused it** |
| `QuranReaderService` | 6 | deliberate resilient fallback, but no trace |

The consequence is concrete: any real bug in an admin CRUD action becomes an opaque
failure message with **zero trace in `laravel.log`**, so support reproduces blind.

| Task | Effort |
|---|---|
| **W3.1** Insert `report($e);` as the first statement of each unlogged catch. Script it, don't hand-edit — grep, insert, skip ones that already log | **1 d** |
| **W3.2** `AddRequestId` calls only `headers->set()`, never `Log::withContext()`, and is registered on the **api** group only (`bootstrap/app.php:52-56`). Add `Log::withContext(['request_id' => …])` and register for `web` | **0.25 d** |
| **W3.3** External uptime check on `/up` (the route exists; nothing polls it) + Sentry/GlitchTip free tier | **0.5 d** |
| **W3.4** Schedule `deploy/stages/90-healthcheck.sh` as cron with alerting — today it is manual-only | **0.25 d** |

**Current mean-time-to-detect for a production 500 is "whenever a human happens to
look," with no floor.** W3.3 alone changes that for ~15 minutes of work and no cost.

**Do not touch:** `AbstractBulkOperationHandler`'s 10 catches (legitimate per-item error
collection, returned to the caller) or `SeriesService:318` (same shape, commented).

### W4 — Testing and CI gates

The suite is not the problem. The gate is.

| Task | Detail | Effort |
|---|---|---|
| **W4.1** Triage the 9 failing tests | Several look like intentional behaviour changes with stale assertions (`ArticleSeriesIntegrationTest` redirect target). Verify and update — do not silence | **0.5 d** |
| **W4.2** Resource value-assertion pass | ~25 Resources × ~10 lines. Assert *values*, not structure. This is what catches the `SurahResource`/`TranslationResource` class of bug | **1 d** |
| **W4.3** Admin route-table smoke test | One test walking every registered admin GET route for an authorised user, asserting non-500. `ApiRouteSmokeTest` already exists for the API; nothing analogous for `admin.php`. Would have caught `roles/create` **and** P0.1 | **0.5 d** |
| **W4.4** `AuditServiceTest` + `TagTest` | The two zero-coverage classes named in the defect list | **0.5 d** |
| **W4.5** `HadithController` + `TopicController` feature tests | The two entirely untested public surfaces. Copy the Seerah/Media pattern | **1 d** |
| **W4.6** Add `test` job to CI with a MySQL service container; `deploy: needs: [architecture, test]` | ~4 min runtime | **0.5 d** |
| **W4.7** Add Larastan at **level 5** + `phpstan.neon`, and Pint to CI | PHPStan is not a declared dev dependency — it is only pulled in transitively by deptrac, and has no config. It would have caught all of P0.1 and P0.2 statically | **1 d** |

**W4 total: ~5 d.** W4.6 + W4.7 are what stop this plan's findings from regenerating.

Explicitly **not** recommended: Infection (the real problem is shallow assertions, which
W4.2 fixes far cheaper), Paratest (165 s is tolerable), Dusk/Playwright (Blade + Alpine;
feature tests already assert rendered output).

### W5 — 12-factor compliance

| Factor | Verdict | Evidence |
|---|---|---|
| I. Codebase | **PASS** | one repo, one deploy target; one dead `Sahaba` import in `bootstrap/app.php:5` |
| II. Dependencies | PARTIAL | `maatwebsite/excel: ^4.0@dev` resolved to **`4.x-dev`** — an unpinned dev branch in production |
| III. Config | **FAIL** | P0.3 + P0.4. `.env` never reconciled; live drift; perms block the runtime user |
| IV. Backing services | PASS | swappable via env; queue deliberately kept off Redis because `allkeys-lru` would evict jobs — a good documented decision |
| V. Build/release/run | PARTIAL | clean separation, atomic symlink flip; but **releases pruned to 1**, and `KEEP_RELEASES=5` in `/etc/tlm-deploy.env` is read by nothing |
| VI. Processes | **FAIL** | `FILESYSTEM_DISK=local`; no object storage. Architecturally single-node |
| VII. Port binding | PASS | php-fpm socket + nginx |
| VIII. Concurrency | PARTIAL | good per-queue process split (default/sync/tracking/scheduler); single VPS only |
| IX. Disposability | PARTIAL | strong graceful start/stop (`startsecs=10`, `stopwaitsecs=3600`, `queue:restart`); no rollback target |
| X. Dev/prod parity | **FAIL** | dev is **SQLite** + file cache; prod is MySQL 8.4 + Redis. Sail installed, never configured, no compose file |
| XI. Logs | PARTIAL | rotated files, no aggregation, no structured logging, request id not in log context |
| XII. Admin processes | PASS | genuinely well-built whitelisted command runner; one inconsistency (P0.3) |

| Task | Effort |
|---|---|
| **W5.1** Fix Factor III — P0.3 + P0.4 + `.env` drift check in `deploy.sh` | in P0 |
| **W5.2** Honour `KEEP_RELEASES` (keep 3–5) + a `rollback.sh` that re-flips `current` | **0.5 d** |
| **W5.3** `docker-compose.yml` for local dev: MySQL 8 + Redis at prod major versions, wired to the already-installed Sail | **1 d** |
| **W5.4** Pin `maatwebsite/excel` to a tagged release | **0.25 d** |
| **W5.5** Nightly `mysqldump --single-transaction --quick \| gzip` **with an off-box copy** and a restore test | **1 d** |

On **W5.3**: SQLite-vs-MySQL is the sharpest parity gap here, not a theoretical one.
This codebase's known bug classes — `JSON_EXTRACT`, `FULLTEXT`, dropped columns,
`whereDate` sargability — are all MySQL-specific behaviours that a SQLite dev
environment cannot reproduce before merge.

On **W5.5**: there is no `mysqldump` anywhere in the deploy path. The only dumps that
exist are side effects of a developer manually running the destructive DB-sync script,
stored on the same 38 GB volume as the database. A disk failure loses both.
`crontab -l` on the box returns nothing.

**Not recommended:** containerising *production*. The native Ubuntu stack is working,
well-tuned and well-documented. W5.3 closes the parity gap from the dev side at a
fraction of the risk.

### W6 — Security

`composer audit` is clean. The one `npm audit` high (`nanoid <3.3.18`) is a transitive
dev dependency via Vite and never ships. SQL injection, mass assignment, command
injection, path traversal, SSRF, session handling and upload validation were each
reviewed in full and are **clean** — details in the do-not-touch list.

The real exposure is concentrated in two places: HTML sanitisation, and admin
authorisation.

| Task | Finding | Effort |
|---|---|---|
| **W6.1** Sanitise rich text at write time | `SanitizesRichText` is used by Content, Seerah and Quran-inquiry requests — **not by articles or media**. `StoreArticleRequest.php:50` validates the body as bare `required`/`nullable`; it renders raw in 4 views. `YouTubeSyncService.php:381,412` writes third-party YouTube descriptions raw, rendered `{!! !!}` on `/media/{slug}`. Add the trait to both article requests and the media requests, and run it in `YouTubeSyncService` before `syncTranslations()`. Backfill existing rows once | **1.5 d** |
| **W6.2** `permission:` middleware on admin groups | **Zero occurrences in all of `routes/`.** The portal is one `role:…|editor` gate; only 3 sub-groups narrow it. An editor can `POST /audit/prune`, read analytics, revert versions, add/remove site languages, and bulk export/import. The seeded permission map already exists — wire it to the router | **1 d** |
| **W6.3** `chmod` in `render_template` | `deploy/lib/common.sh:114-129` writes via shell redirection → umask 0644. `/etc/redis/redis.conf.d/tlm.conf` holds `requirepass`. Currently safe only by an inherited directory mode, not by construction. One fix at the shared function covers all 14 callers | **0.25 d** |
| **W6.4** Bound the unthrottled amplifiers | `/genealogy?root=` — no throttle, bypasses cache, runs recursive CTEs at depth 40. `/pages/{slug}` — no throttle, `rememberForever` **does** store nulls (verified against the framework source; the code comment claiming otherwise is wrong). `/hadith/search` — no throttle, double `MATCH…AGAINST` over ~37k rows. `/topics/*` — no throttle | **0.75 d** |
| **W6.5** IDOR on bulk operations | `BulkOperationController::download/status/destroy` take a raw `{id}` with no ownership check, while `index()`/`stats()` in the same controller *do* scope by user — an oversight, not a design choice | **0.25 d** |
| **W6.6** JSON-LD breakout | `public/hadith/show.blade.php:22-30` uses `JSON_UNESCAPED_SLASHES` inside `<script type="application/ld+json">`, removing the one escape that stops `</script>` from terminating the block — on text imported from sunnah.com. `public/genealogy/index.blade.php:287` already does this correctly with `JSON_HEX_TAG` | **0.1 d** |
| **W6.7** Security headers | No CSP anywhere. Worse, `nginx-app.conf:42-47` declares `add_header` inside the static-asset location, and nginx's `add_header` **replaces** the inherited set — so every JS/CSS/image response ships without HSTS or `nosniff`. Re-emit the inherited headers there; add CSP report-only after W6.1 | **0.5 d** |
| **W6.8** Auth hardening | Admin-created accounts have the **weakest** policy in the app (`min:8`, no complexity) while public registration uses `Password::min(8)->mixedCase()->numbers()` and the API uses `min(12)` with symbols. Failed admin logins emit no log or audit row. `Api\AuthController:75-82` skips `Auth::attempt`, so bcrypt never runs on an unknown email — a timing oracle for account enumeration | **1 d** |

**W6 total: ~5.3 d.** W6.1 and W6.2 are the two that matter; the rest is bounded cleanup.

### W7 — Architecture: restore the contracts that already exist

The boundary discipline is real — **zero cross-domain imports across all nine modules**,
verified by grep. What has decayed is the layer *below* that: type contracts and gate
coverage.

| Task | Finding | Effort |
|---|---|---|
| **W7.1** Redeclare `$repository` in the 14 `BaseService` subclasses | None of them narrow the property, so each sees only `RepositoryInterface`'s 13 methods — **98 distinct repository calls are type-unchecked**, invisible to PHPStan and the IDE. Fourteen one-line declarations. Best value-per-character in the audit | **0.25 d** |
| **W7.2** Widen deptrac's collectors | The gate reports `Violations 0 · Uncovered 7333 · Allowed 1366` — it sees roughly **16% of the graph**. `app/Jobs`, `app/Exceptions`, `app/Utils`, `app/Helpers` are in no collector, so `Shared → App\Jobs` (`BulkOperationService.php:12`) is neither baselined nor enforced — it is invisible. Add the missing layers, re-baseline | **0.5 d** |
| **W7.3** Delete the `DomainSahaba` layer | `deptrac.yaml:47-52` still defines a layer for two directories deleted in the People migration | **0.1 d** |
| **W7.4** Correct CLAUDE.md | Stale on three counts: it says Hadith has no public UI (it has five live routes), it describes the Quran CQRS consumption backwards, and it mandates Spatie JSON columns for models whose columns were dropped. The constitution is what future work will be measured against — a stale one actively misleads | **0.5 d** |

**W7 total: ~1.35 d**, and W7.1 alone unlocks meaningful static-analysis coverage for
W4.7.

**Dual repository locations — leave them.** The split is not duplication (there is
none), and the real difference is semantic, not locational: 41 of 41 domain repositories
have interfaces against 1 of 4 app-level ones, and 20 inherit transparent 900 s caching
the app-level four do not. Moving `ArticleRepository` while `Article` stays in
`app/Models/` relocates the inconsistency rather than resolving it. Fix the CLAUDE.md
paragraph instead — "repositories move when their model moves" is already true of all
four. **Do not spend a sprint on this.**

---

## 3. Module plans

Ordered by measured user impact first, defect density second. Per-group p95 under
stress: **nav 2.19 s · quran 1.69 s · home 1.67 s · article 1.46 s · json 289 ms ·
static 127 ms.**

### M1 — Quran · 194 files, 16.6k LOC · *slowest read path*

The largest module, the slowest public group, and the densest defect list. It also
contains the only CQRS layer in the codebase.

**State.** Enums are idiomatic, DTOs are `readonly` with `fromModel()` factories,
`RecitationRepository` and `VerseRepository::getMushafWordsBySurah` are the reference
patterns for the whole codebase. The problems are all in the read path.

| # | Task | Effort |
|---|---|---|
| M1.1 | **P0.1** — delete the `sajda_type` rule (phantom enum) | 0.25 d |
| M1.2 | **P0.2** — `DuplicateResourceException` → `BusinessRuleViolationException` in `TafseerController`/`TafseerService` | 0.1 d |
| M1.3 | **P0.5** — `TranslationResource` 500 + `SurahResource` wrong number | 0.25 d |
| M1.4 | **The `load()` epidemic — 16 calls across the CQRS handlers.** `getBySurah()` eager-loads translations *with an explicit column list*, then `ListVersesBySurahHandler:29` calls `->load('translations')`, which issues a second query **and replaces the constrained relation with `SELECT *`**. Same in the Page/Juz/Hizb/Verse/Sajda/Random handlers | **1.5 d** |
| M1.5 | `VerseRepository:71` — `TRANSLATIONS_EAGER` selects `text_notashkeel`, which exists only to feed the FULLTEXT index and is rendered nowhere. Split the constant | 0.25 d |
| M1.6 | `VerseRepository:135,175,265,292,305` — translations loaded with no language filter: every edition of every language where one locale renders. Add `whereIn('lang', ['ar', $locale])` | 0.5 d |
| M1.7 | `VerseRepository:332` — admin verse index eager-loads only the surah while the Blade reads `$verse->translations` → **15 lazy `SELECT *` per page** | 0.25 d |
| M1.8 | `TafseerRepository` — 8 bare `SELECT *` reads on a `longText` table + unconstrained `with(['verse.surah'])`. Plus `:343` `clearCache()` never calls `parent::clearCache()`, so the version counter never bumps and reads serve pre-edit data for 900 s | 0.75 d |
| M1.9 | `ReciterRepository` ×5 — no `select()`, no `limit`, **no `translations` eager load**, so `HasLocalizedContent` lazy-loads per model. The public page fetches `perPage: 200` × every locale × a `longText` description to render a name | 0.5 d |
| M1.10 | `QuranSyncService:148` — `beginTransaction()` held across **three remote HTTP calls** with 0.8–2.5 s deliberate delays and up to 60 s retry sleeps, then ~2900 statements. Fetch first, then wrap only the writes | 0.5 d |
| M1.11 | `QuranSyncService:271,289,306` — writes `resource_id = NULL` while matching on `(verse_id, lang)`; the unique is now `(verse_id, resource_id)`. **Rows written by a sync are invisible to the reader** | 0.5 d |
| M1.12 | `Site/QuranController` (973 LOC — the largest class in the app): `:648` unbounded `getFeatured()` whose result is recomputed at `:684` and never used → delete; `:713,722` direct Eloquent in a controller | 0.5 d |
| M1.13 | `QuranReaderService` — 6 catches degrade to `[]`/`''` with **zero logging**. Keep the resilient fallback, add `report($e)` | 0.1 d |
| M1.14 | Add `phpstan.neon` FULLTEXT index on `quran_verse_tafsirs.text` (**verified absent** on the live schema) | 0.25 d |

**M1 total: ~6 d.** M1.4 is the highest measured-latency payoff in the codebase.

**Architectural decision — resolved: delete the CQRS write side, keep the read side.**

CLAUDE.md's description is wrong in both directions. An admin controller *does* consume
the query stack, and the write half has no consumers at all:

```
QuranCommandService (495 LOC, 15 constructor deps)  →  0 consumers
CommandBus / QueryBus                                →  0 usages anywhere
13 domain events                                     →  fire into nothing
```

Verified by grep: the only hit for `QuranCommandService` is its own class declaration.
**49 files / ~2,057 LOC are transitively unreachable.**

The completion argument does not survive contact with the code either: the 12 duplicated
read paths duplicate only *wrappers*, not query shapes, so the usual "they will drift"
justification does not apply. Completing the migration means weeks of work to add
indirection; deleting the write side is a clean subtraction.

| # | Task | Effort |
|---|---|---|
| M1.15 | Delete `QuranCommandService`, the unused buses, the 13 dead events and their transitively unreachable dependents (~2,057 LOC across 49 files) | **1 d** |
| M1.16 | **Quran admin writes never invalidate the public reader cache.** `QuranCacheKeys::flushContent()` exists and is called from no admin write path — edit a verse and the public surah page serves stale text for up to an hour | **0.5 d** |

M1.4 can proceed immediately; it touches the read handlers, which stay.

### M2 — Blog / Articles · 45 files, 5.3k LOC · *mid-migration*

**State.** `articles` is the only table still carrying live Spatie JSON columns
*alongside* the authoritative `article_locale` table — so unconstrained reads drag every
body **twice**. `ArticleRepository::getPublishedForPublicIndex():243` already has the
correct column list; it simply was not applied to the eleven siblings.

| # | Task | Effort |
|---|---|---|
| M2.1 | **W6.1** — `SanitizesRichText` on `StoreArticleRequest`/`UpdateArticleRequest` + backfill | in W6 |
| M2.2 | One `CARD_COLUMNS` constant applied to the **eleven** unconstrained builders (`:58,83,103,135,152,165,210,606,624,674,828`). `:58` serves both the admin index and `Api\ArticleController::index()` | **1 d** |
| M2.3 | `:590` locales closure has no column list (its five siblings do); `:638` selects a whole row to read `article_id` → `->value()`; `:205` hydrates article + categories + tags to `pluck('id')` (`:291` already does the narrow version); `:765` `getFormTags()` fetches every tag unbounded on every edit render | 0.75 d |
| M2.4 | `Admin/Blog/ArticleController` (828 LOC): `:71` re-runs the identical four eager loads the repository just performed; `:134,232` load 14 relations then return a redirect that discards them; `:615` `->fresh()->views` re-reads the row to print an integer `incrementViews()` already returned | 0.5 d |
| M2.5 | `Site/ArticleController:274,333` — the same unbounded full-series fetch runs **twice per article page**; on a 60-article series that is 120 full bodies to render two links. `Site/SeriesController:88` is the worst payload-to-render ratio found | 0.5 d |
| M2.6 | `Tag.php:70,79,108` reads the dropped `tags.name`; `?? '{}'` swallows it so every tag name is silently `null` in every locale, and `static::creating` slugs the literal `'tag'`. `Category:230` was migrated properly; `Tag` was missed. `TagResource:33` has the same read | 0.5 d |
| M2.7 | `TagRepository:33,281,310` — no `select()`, no `translations`, so every view resolving `$tag->name` lazy-loads per row | 0.25 d |
| M2.8 | `CategoryService:339` — `findOrFail()` defaults to `['*']` **and** eager-loads `articles` unbounded with full JSON bodies, for a page that shows a count (both controllers then run `->count()` anyway); `:426` fetches all siblings to compute a `max()` in PHP; `:461`+`Category:120,302` fire three parent-chain ladders on one save | 0.75 d |
| M2.9 | Delete dead Blade: `_form-new` (1,289 loc), `_form` (803), `_form-example` (352), `index-example` (320) — **all unreferenced**; `create`/`edit` include only `_form-wizard` | 0.25 d |

**M2 total: ~4.5 d.**

### M3 — Media · 45 files, 5k LOC

**State.** Two media systems, both intentional (Spatie for attachments, `MediaService`
for the encrypted library). The encryption is genuinely streaming and correct. The
defects are N+1s and unbounded renders.

| # | Task | Effort |
|---|---|---|
| M3.1 | **W6.1** — sanitise YouTube descriptions/transcripts at sync time (C1/M3 in the security audit) | in W6 |
| M3.2 | `MediaProgram:193` `firstEpisodeVideoId()` — poster/banner accessors fire **2 queries per program**, and because they run at *serialization* time the repository's cache buys nothing. A 24-program `/media` payload is 48 extra round trips. `Site/MediaController:314` compounds it: 4 accessors × 12 cards × 6 rows | **1 d** |
| M3.3 | `Site/MediaController:186,228` — maps **every** episode into a card on both the program and player pages. A 900-episode synced program renders 900 cards on a page showing one video | 0.5 d |
| M3.4 | `MediaEpisodeRepository` ×7 and `MediaProgramRepository` ×10 — `'media'` eager-loaded with no column list. **Keep `generated_conversions` in the fix** or `getFirstMediaUrl(…, 'thumb')` silently falls back to the original | 0.5 d |
| M3.5 | `MediaProgramService:177` deletes the row *then* calls `clearCacheForModel($id)`, which opens with `find($id)` and **returns early when the row is gone** — a deleted program keeps rendering on `/media` for the full 30-min TTL and 404s from a live link. `LiveChannelService:131` gets the order right | 0.25 d |
| M3.6 | `Media.php:336` `publicRouteKey()` does a `saveQuietly()` when `public_key` is blank — **a write executed during a read**, up to 50 UPDATEs inside one GET, and `MediaResource:21,27` calls it again per variant | 0.5 d |
| M3.7 | `MediaProgram:108` deleting hook hydrates every Spatie media row and issues one DELETE each — an 800-episode program is 1,600 statements in-request. `MediaEpisodeRepository:220,261` one UPDATE per episode on reorder (`:234` is an O(n²) PHP scan inside the loop) | 0.5 d |
| M3.8 | `MediaService.php:599` — `file_get_contents()` + `decodePath()` **before** `scaleDown()`, so GD allocates the full bitmap with no pixel guard (`StoreMediaRequest:128` caps bytes only). A 10 MB low-entropy PNG exceeds 100 Mpx = 400 MB+ against a 256 M limit, inline in the FPM request. A `memory_limit` fatal does not unwind, so the `finally` never runs and the plaintext temp copy leaks | 0.5 d |
| M3.9 | `MediaService.php:257-281` — encrypted media decrypts to a fresh temp file **per request** and sets `Cache-Control: private`, the exact token Cloudflare honours by not caching. Reached from two public unthrottled routes. Cache the decrypted variant by content hash | 0.75 d |
| M3.10 | `config/media-library.php:32` — `queue_conversions_by_default` is `false` and `env.production` never sets it, so every conversion (incl. `avifenc -j all`, which saturates both cores) runs in the FPM request. `:15` `max_file_size` is `1024*1024*102400` = **100 GiB**, not 100 MB | 0.25 d |

**M3 total: ~5 d.**

### M4 — Seerah · 36 files, 4.4k LOC

**State.** `publicDetailRelations():405` is correct and locale-scoped. Its twin
`detailRelations():560` is the un-migrated version — still used by the API `show` and the
admin controller. Most of this module is applying the correct twin everywhere.

| # | Task | Effort |
|---|---|---|
| M4.1 | `SeerahEventRepository:78,91,104,117,160,229,247` — seven paginated reads with a carefully constrained `listRelations()` but **no `select()` on the base table**, so every row still ships `gallery`, `statistics`, `era`, `phase`, `is_pivotal` | 0.5 d |
| M4.2 | `:390` `listRelations()` is not locale-filtered (60 translation rows to render 12); `:560` `detailRelations()` loads `longText content` + 2 JSON blobs for **all five locales** — replace with the `:405` pattern; `:354` `exportRelations()` pulls fields the exporter never reads | 0.75 d |
| M4.3 | `:368` `findBySlug()` takes `$columns = ['*']` and every API caller uses the default | 0.25 d |
| M4.4 | `SeerahMilestoneRepository:45` eager-loads **all 548 published events per milestone** purely to compute three integers in PHP. Three `withCount` subqueries replace the entire load | 0.25 d |
| M4.5 | `SeerahEventService.php:63,81` — synchronous hot-row `UPDATE` on every page view. The read is cached 30 min; the write still fires, so every concurrent reader of a popular event serialises on one InnoDB row lock. **The Media domain already solved this** — route through `MediaViewRecorder` on the `tracking` queue | 0.5 d |
| M4.6 | `SeerahEventResource:25,26` — `getTranslations()` emits every locale on every list endpoint, which is what locks the wide eager load in place | 0.25 d |
| M4.7 | `Site/SeerahController:225` builds cache keys from unbounded `?q=` and uncapped `?page=` | 0.25 d |
| M4.8 | `Admin/Seerah/SeerahEventController:139,190,254` — `find()` star-selects; bulk delete star-selects **per iteration** to read one string; the audit diff calls `toArray()` on the whole row twice per update | 0.25 d |

**M4 total: ~3 d.**

### M5 — People (companions + genealogy) · 27 files, 3.1k LOC · *slowest group is here*

`nav` p95 was **2.19 s (p99 6.77 s, max 12.53 s)** — the worst group measured, and
`/genealogy` is its heaviest member.

| # | Task | Effort |
|---|---|---|
| M5.1 | **W6.4** — throttle `/genealogy?root=` and cache every root, not just the default. Today any non-empty `?root=` bypasses the cache and runs recursive CTEs at depth 40, unauthenticated and unthrottled | in W6 |
| M5.2 | `Site/GenealogyController` (876 LOC): **six** unconstrained `Muslim` fetches (`:234,422,476,581,615,796`), four unbounded, all feeding a `mapPerson()` that reads 11 columns. One shared `NODE_COLUMNS` constant fixes all six | 0.75 d |
| M5.3 | `:648` is a **duplicate** of `findMostConnectedRootId`'s correlated-subquery scan, copied into the controller — fixing the repository version leaves this one broken. `:407` spouse edges as unbounded `SELECT *`; `:602` loads every edge with both counterparts hydrated then keeps only spouses in PHP | 0.5 d |
| M5.4 | `GenealogyTreeService:146` filters `is_published`/`hide_from_tree` **in PHP after hydrating both counterparts** — the discard rate is 80 published of 7,837 rows. `descendants():113` bounds depth but not rows, and `UNION ALL` emits a person once per distinct path — combinatorial in a corpus with endemic cousin marriage. Add `LIMIT` + a `nodes_capped` flag | 0.75 d |
| M5.5 | `EloquentMuslimRelationshipRepository:17` — counterpart columns disciplined, edge row `SELECT *`, **no LIMIT**, on the table whose own design note is that it fans out combinatorially | 0.25 d |
| M5.6 | `Admin/SahabiController:84,330` and `Site/SahabaController:186` — three/four full COUNT passes over 7,837 rows per index load. One `selectRaw` with conditional sums replaces them; `EloquentMuslimRepository::treeCounts:191` is already the reference implementation | 0.5 d |
| M5.7 | `Site/SahabaController:168` fetches 5 rows to keep 4, filters self in PHP when `MuslimListFilters::excludeId` already exists and the repository already honours it, and runs a full paginator COUNT to render four cards | 0.25 d |
| M5.8 | `MuslimResource:34,39` reads `bio` and `birth_place`, neither of which `LIST_COLUMNS` selects → `""` for all 7,837 people. Drop the fields from the list Resource (`whenHas`) rather than widening the select | 0.25 d |

**M5 total: ~3.25 d.**

### M6 — Hadith · 38 files, 3.2k LOC

**State.** The import pipeline (30 files under `Import/Sunnah/**`) is genuinely
excellent — zero direct DB access, chunked upserts, count-parity gates, drift detection
— and is well tested. **CLAUDE.md is stale**: it says there is no public UI, but
`routes/web.php:74-90` serves five public Hadith routes, and they have **zero tests**.

| # | Task | Effort |
|---|---|---|
| M6.1 | **W6.4** — throttle `/hadith/search` (double `MATCH…AGAINST` over ~37k rows, uncached, currently unthrottled unlike every other search surface) | in W6 |
| M6.2 | **W6.6** — JSON-LD `JSON_UNESCAPED_SLASHES` breakout on `public/hadith/show.blade.php:22-30` | in W6 |
| M6.3 | **W4.5** — feature tests for all five public routes, especially the `{reference}` vs `{slug}` vs `{slug}/{bookNumber}` disambiguation | in W4 |
| M6.4 | `hadiths` carries four unbounded columns — `text_ar`, `text_en`, `search_ar`, `search_en`; the last two are folded duplicates existing only to be `MATCH`ed and never rendered. `HadithBrowseService:99` selects all four on every detail view (`search():137` gets this right) | 0.5 d |
| M6.5 | `HadithBrowseService:33,40,68,78` — `collections()`, `findCollection()`, `findBook()`, `chapters()` lack `select()`, shipping `mediumText intro_ar/intro_en` and JSON no Blade renders. `:33` is the payload of a 1-hour cache entry; `chapters()` is unbounded | 0.5 d |
| M6.6 | `Admin/Blog/ArticleModuleController:198` — the editor's hadith picker runs `LIKE '%term%'` across **both `longText` bodies on every keystroke** (full scan of 37k rows) while `ft_hadiths_search_ar/_en` sit unused. It also searches unfolded text, so `الصلاة` misses rows stored as `الصلاه` | 0.5 d |
| M6.7 | Update CLAUDE.md — the module has a public UI and the doc says it does not | 0.1 d |

**M6 total: ~1.6 d.**

### M7 — Content · 45 files, 3.8k LOC · *the reference module*

**This is the standard the others are measured against.** Explicit column constants,
column-constrained translation loads, per-locale keys, complete flush sets, and **zero
`catch (\Throwable)` in the domain layer**.

| # | Task | Effort |
|---|---|---|
| M7.1 | **W6.4** — `StaticPageService:36` `rememberForever` on a lookup that does not skip nulls (verified against the framework source; the comment claiming it does is factually wrong). Unbounded public slug → permanent cache entries → session eviction under `allkeys-lru` | in W6 |

**M7 total: ~0 d beyond W6.** Otherwise: do not touch.

### M8 — Topics · 7 files, 677 LOC

| # | Task | Effort |
|---|---|---|
| M8.1 | **W4.5** — zero HTTP tests. `TopicController` and the morph-map/`PublicVisibilityRegistry` wiring are entirely untested; if a domain is added to the morph map and forgets a visibility scope, nothing fails | in W4 |
| M8.2 | `TopicRepository:95` — `morphWith()` constrains the *nested* relations but leaves the itemable itself `SELECT *` (full articles incl. `content` JSON) where the mapper reads `thumb` + `locales`. **Adding `MorphTo::constrain()` requires keeping `id` in every per-type column list**, or every topic page renders empty | 0.5 d |
| M8.3 | `TopicService::mapArticle:202` — `thumbUrl('card')` does a `Storage::exists()` filesystem stat **per article** inside the map: 200 stats per cache miss | 0.25 d |
| M8.4 | **W6.4** — throttle `/topics` and `/topics/{slug}` | in W6 |

**M8 total: ~0.75 d.**

### M9 — Shared · 122 files, 18.7k LOC · *highest leverage per change*

| # | Task | Effort |
|---|---|---|
| M9.1 | **P0.2** — `BusinessLogicException` → `BusinessRuleViolationException` in `SchedulerService`/`SchedulerController` (9 sites) | in P0 |
| M9.2 | **`BaseExporter` → `FromQuery` + `WithChunkReading`, drop `ShouldAutoSize`.** One file; fixes memory bounds for **all seven exporters** at once, and removes the double-collection build in `ExportController:61,66`. Also `:102-114` has two `'font'` keys in one array — the second silently overwrites the first | **1 d** |
| M9.3 | `BaseRepository::all()` caches `SELECT *` of whole tables into the cache store; `SurahRepository:49` caches under a **fixed key while accepting `$columns`/`$relations`** — run the export first and the key holds 114 surahs plus 6,236 verse models (~5 MB) that every public request then unserializes; run the list first and the export silently emits surahs with no verses. Same shape at `SeerahEventRepository:368` | 0.75 d |
| M9.4 | `BaseImporter:114` — `Excel::toArray()` reads the whole sheet and keeps it; `:284` wraps every row in **one** transaction; `:210,311` append the entire row to an unbounded error array; `parseFile()` re-runs up to 3× across the wizard. A 20k-row sheet is 60–100 MB. Chunk both the parse (generator) and the transaction (per 500) | 1 d |
| M9.5 | `AuditService:130-165` — `->getCollection()` on a **paginator** (default 15/page), so the "monthly audit report" silently aggregates only page 1. Zero test coverage on this class | 0.5 d |
| M9.6 | `SearchService:411` — `SELECT *` across **five models per request** (up to 100 rows each) with bare eager loads; `SeerahEventResource` then lazy-loads `type` per row | 0.75 d |
| M9.7 | `DashboardService:96,135,173,303` — 4 catches into zeroed fallbacks, **none calling `report()`**. This is the exact pattern CLAUDE.md documents, still open in the file that caused it. `DashboardAnalyticsService` fixed 2 of 3; `:295` is still silent. `:229-236` and `:203-210` are permanent stubs returning `[]` — check whether a view still renders them | 0.5 d |
| M9.8 | `AnalyticsService:175,347` queries `favorites.favorable_type` with `getMorphClass()` (`'article'`) while the column stores FQCNs → article favourite counts on the dashboard are **permanently 0**. `:355` `$item->title['ar']` on a dropped column, swallowed by a `catch` that empties the whole `top_content` payload | 0.5 d |
| M9.9 | `InteractionService:145` — read-modify-write on `total_time_spent_seconds` (lost update); `:86` runs `afterResponse()` work **inline in the fpm worker** — 6–8 UPDATEs plus a full COUNT | 0.25 d |
| M9.10 | `SetLocale:109,151` — up to 5 cache reads per request. Memoize per request | 0.25 d |

**M9 total: ~5.5 d.** M9.2 is the best effort-to-payoff ratio in the document.

### M10 — HTTP / Admin layer · 49 controllers, 12.6k LOC

| # | Task | Effort |
|---|---|---|
| M10.1 | **W6.2** — `permission:` middleware per route group | in W6 |
| M10.2 | **W3.1** — `report($e)` in the 103 unlogged catches | in W3 |
| M10.3 | **P0.5** — route shadowing: `routes/admin.php:287` registers `/{module}` before `/history`; `:230` registers `roles` `only(['index','show'])` before `:247` `roles/create`, so `GET roles/create` hits `show(int $id)` → TypeError. **The role-creation page is dead** | in P0 |
| M10.4 | Unclamped `per_page` on ~20 admin endpoints — `?per_page=999999` is a one-request OOM against 6,236 verses × translations. `Admin/UserController:45` already clamps correctly; the public API validates `max:100` everywhere. Add one `perPage()` helper to the base controller | 0.5 d |
| M10.5 | `AuditController::exportLogs:181` builds the whole table as a single PHP string; every filter is optional and it is a **GET** route, so a browser prefetch can fire it. `$format` is never validated. Stream with `chunkById` + `fputcsv` | 0.5 d |
| M10.6 | `bootstrap/app.php:5` — dead `Sahaba` exception import and its `render()` block. Doesn't fatal, but People 404s fall through and return **422** | 0.1 d |
| M10.7 | Localization: `ar` has 5,497 keys; `tr`/`fr`/`ur` have **2,227 each**, and 9–11 lang files are missing per non-Arabic locale — including `validation.php` and `pagination.php`, so those locales fall back to English framework strings. 471 hardcoded Arabic literals sit outside `__()` in Blade | **2 d** |

**M10 total: ~3 d.**

### M11 — Infrastructure & deploy

| # | Task | Effort |
|---|---|---|
| M11.1 | **P0.3 / P0.4** — `.env` perms, config-cache recovery, live drift, drift check in `deploy.sh` | in P0 |
| M11.2 | **W5.2** — honour `KEEP_RELEASES` (set to 5 on the box, read by nothing) + a `rollback.sh` | 0.5 d |
| M11.3 | **W5.5** — nightly off-box `mysqldump` with a restore test. There is none today, and the only dumps that exist sit on the same 38 GB volume as the database | 1 d |
| M11.4 | `/robots.txt` returns **404 in production** — found by the load test (all 137 "failures"). `snippets/tlm-app.conf` has `location = /robots.txt` with no `try_files`, so nginx 404s before Laravel sees the dynamic route. Check `/favicon.ico` for the same shape | 0.1 d |
| M11.5 | `deploy/stages/65-nginx.sh` fetches Cloudflare's IP ranges live and, on failure, **warns and continues**. Without real-IP restoration the rate limits key on the Cloudflare edge IP, so every visitor behind a given CF datacenter shares one 20-connection budget — mass 503s that look exactly like an origin outage, precisely under peak traffic. Make it fail closed | 0.25 d |
| M11.6 | `deploy.sh` runs `optimize:clear` mid-deploy, which includes `cache:clear`, and `storage` is symlinked to `shared/` — so it wipes the cache the **currently-live** release is serving from, before the symlink flips. Use the four individual `*:clear` commands; add an OPcache warmup after the fpm reload | 0.25 d |
| M11.7 | `worker_connections 768` (Ubuntu default, never templated) — behind Cloudflare, origin connections are long-lived keepalives from many edge PoPs. Raise to 4096 + `multi_accept on`, `backlog=1024` on `listen`, `listen.backlog = 4096` on the fpm pool. Requires `restart`, not `reload` | 0.25 d |
| M11.8 | `sync` queue runs yt-dlp/ffmpeg at normal priority on 2 cores — an admin clicking "import playlist" during a peak competes head-on with php-fpm. Prefix with `nice -n 15 ionice -c2 -n7` | 0.1 d |
| M11.9 | Retention: `media:prune-view-events` exists but is **deliberately not scheduled**; `search_history` and `user_interactions` have no prune at all and grow per public search/view forever; `queue:prune-failed`/`prune-batches` unscheduled; `CleanupFailedImports` is **never dispatched anywhere**. journald is 627 MB with no `SystemMaxUse` cap | 0.5 d |
| M11.10 | `composer install` missing `--classmap-authoritative` — the release tree is immutable, so the PSR-4 filesystem fallback can only produce failed `stat()` calls | 0.1 d |

**M11 total: ~3 d.**

---

## 4. Principles scorecard

| Principle | Grade | Evidence |
|---|---|---|
| **Type safety** | **A** | `strict_types` in 866/871 files; 42 repository interfaces for 45 classes; `readonly` in 278 places |
| **Module boundaries** | **A** | `deptrac`: 0 violations, 1,366 allowed dependencies. Cross-domain access goes through Shared contracts (`PersonLookup`, morph map, `PublicVisibilityRegistry`) |
| **Modern PHP idiom** | **A** | `match` in 113 files vs `switch` in 2; backed enums carrying behaviour; promoted readonly DTOs with `fromModel()` factories |
| **DRY** | **C** | Six near-identical `Muslim` fetches in one controller; `findMostConnectedRootId` duplicated between repository and controller; the same series fetch twice per article page; `getPublishedForPublicIndex`'s correct column list not applied to its eleven siblings |
| **SRP** | **C** | `Site/QuranController` 973 LOC, `ArticleRepository` 963, `AnalyticsService` 961, `Site/GenealogyController` 876, `Admin/Blog/ArticleController` 828. Controllers holding direct Eloquent (`Site/QuranController:713,722`) |
| **Fail loudly** | **D** | 193 `catch (\Throwable)` against 80 `report()`. 103 of them in admin controllers, the dominant shape logging nothing |
| **Least privilege** | **D** | `permission:` middleware appears **zero times** in `routes/`; an editor can prune the audit trail |
| **Input sanitisation** | **D** | `SanitizesRichText` on 6 request classes; articles and media — the two highest-volume rich-text paths — are not among them |
| **12-factor** | **C** | 5 PASS, 4 PARTIAL, 3 FAIL (Config, Processes, Dev/prod parity) |
| **Test discipline** | **C** | 901 real tests, good factory/`Http::fake` hygiene — but 9 red on `main`, near-zero depth on serialization, and **CI never runs them** |
| **DIP** | **B–** | 42 interfaces for 45 repositories — but all 14 `BaseService` subclasses fail to redeclare `$repository`, so **98 repository calls are type-unchecked** |
| **Layering** | **B** | Zero cross-domain imports across nine modules; no business logic in 118 `Http/Requests` classes; correct route ordering. Weak spot is controllers holding direct Eloquent |
| **YAGNI** | **D** | ~2,057 LOC of unreachable Quran CQRS write-side; `CommandBus`/`QueryBus` with zero usages; 13 events firing into nothing; 2,764 lines of dead Blade |

The shape is consistent: **the engineering foundations are strong and the operational
disciplines are weak.** Types, boundaries and idiom are near-exemplary — module
isolation in particular is genuinely intact, which is the expensive thing to retrofit
and the easy thing to lose. What is missing is everything that makes failure *visible*:
logging, authorisation gates, sanitisation, and a CI gate that runs the tests that
already exist.

The YAGNI row is the one genuine over-engineering finding, and it is concentrated in a
single place. Everything else in this plan removes defects; M1.15 removes *code*.

---

## 5. Sequenced roadmap

Effort assumes one developer fluent in this codebase. Phases are ordered so that each
one's prerequisites are complete.

### Phase 0 — Stop the bleeding · ~2.5 d · do this first

P0.1–P0.6. Four live fatals, one outage switch, one config drift, four shipped bugs.
No dependencies. Nothing else should start before this lands.

### Phase 1 — Make failure visible · ~4.5 d

W3 (`report()` sweep, request-id context, uptime check, health-check cron), W4.1/W4.6
(fix the 9 red tests, add the CI test job), and W7.1/W7.2 (repository type contracts,
deptrac coverage). **Deliberately before the optimisation work**: every subsequent phase
is a refactor of code paths that currently fail silently, are not gated by CI, and are
partly invisible to static analysis. Doing W2 or M1 first means refactoring blind.

W7.1 belongs here rather than in Phase 6 because it is a prerequisite for W4.7 — 98
type-unchecked repository calls is most of what PHPStan would otherwise be unable to see.

### Phase 2 — Capacity · ~4 d

W1 in order: cache headers → Redis → stampede protection → FULLTEXT search → indexes →
MySQL/fpm retune. This is the phase that moves the p95 number. Re-run
`tests/load/k6.js --env SCENARIO=stress --env TARGET=origin` afterwards and compare
against the 16 Aug baseline rather than assuming the gain.

### Phase 3 — Security · ~5 d

W6, in the audit's order: sanitisation (W6.1) → authorisation (W6.2) → secret perms
(W6.3) → throttles and cache-key bounds (W6.4) → the rest. CSP (W6.7) goes last because
it is only meaningful once W6.1 closes the injection sources.

### Phase 4 — Module optimisation · ~30 d

M1 → M9 → M3 → M2 → M5 → M4 → M6 → M8, i.e. slowest-and-densest first. Two items can be
pulled forward at any point because they are single-file and independent: M9.2
(`BaseExporter`, which fixes all seven exporters at once) and M1.15 (deleting the dead
CQRS write side, which only subtracts).

Start M1.15 *before* M1.4 — there is no sense optimising read handlers in a module while
2,057 lines of unreachable code still obscure which paths are live.

### Phase 5 — Durability & parity · ~12 d

W5.2 (release retention + rollback), W5.3 (docker-compose dev parity), W5.5 (off-box
backups), W4.7 (Larastan + Pint in CI), M10.7 (translation completeness).

### Phase 6 — Structural refactoring · ~6 d

The five god classes (`Site/QuranController` 973 LOC, `ArticleRepository` 963,
`AnalyticsService` 961, `Site/GenealogyController` 876, `Admin/Blog/ArticleController`
828) plus W7.3/W7.4. These belong last: they are maintenance cost, not user-facing risk,
and splitting an 973-line controller before Phase 1 means a large refactor against code
that fails silently and is not gated by CI.

**Explicitly not in this phase:** relocating the dual repositories. See W7 — the split
is semantic, not accidental, and moving files would relocate the inconsistency rather
than resolve it.

**Total: ~64 developer-days** to the end of Phase 6, of which **the first ~7 (Phases 0–1)
carry almost all the risk reduction**. Phases 0–3 alone — about 16 days — close every
live fault, every security finding, and the entire measured capacity gap.

> **Correction.** An earlier revision of this document gave the total as ~45 days. That
> figure summed the phase headline estimates rather than the line items beneath them, and
> undercounted Phase 4 (stated 22 d, actually ~30 d once each module's local items are
> totalled) and Phase 5 (stated 5 d, actually ~12 d). The per-item estimates were correct
> throughout and are unchanged; only the roll-up was wrong. The ~16-day figure for Phases
> 0–3 was computed from the items and holds.
>
> The task-level breakdown is in **[TASKS.md](TASKS.md)**, which is the authoritative
> checklist for execution.

---

## 6. Do not touch

Regressing any of these would be a net loss. Several are unusual enough that a
well-intentioned sweep would "fix" them.

**Correct by design:**
- **Module isolation.** Zero cross-domain imports across all nine domains, verified by
  grep. This is the expensive property to retrofit and the easy one to lose — every task
  in this plan stays inside its own bounded context or goes through a Shared contract.
- **`Http/Requests`** — 118 classes, no business logic in any of them.
- **Blog's `SlugGeneratorService` delegates** to Shared's rather than duplicating it.
- **Seerah's two cache systems share a version counter** correctly — this looks like
  duplication and is not.
- **`Site/MediaController`'s maps vs the Resources** serve different contracts; they are
  not redundant.
- **Shared's `User` imports** are a deptrac *configuration* gap (W7.2), not a layering
  violation to refactor.
- **OPcache + deploys.** `validate_timestamps=0` *plus* `systemctl reload php-fpm` per
  release *plus* `SCRIPT_FILENAME $realpath_root$…` so entries key on the resolved path.
  Stale-code-after-deploy cannot happen here. This is the most commonly botched thing in
  this stack and it is right.
- **The queue stays on MySQL.** Redis runs `allkeys-lru` instance-wide, so queued jobs
  would become evictable — a durable queue traded for a lossy one. MySQL peaked at 8
  connections under stress. The decision is documented in the template and correct.
- **Media encryption is genuinely streaming** — 1 MiB chunked `fread`/`fwrite` with
  libsodium secretstream. No whole-file buffering.
- **`Cache::flush()` appears nowhere** — only in comments explaining its removal. The
  `VersionedCacheKey` pattern is used correctly throughout.
- **Graceful worker lifecycle** — `startsecs=10`/`startretries=20` (fixing a real
  reboot-order FATAL), `stopwaitsecs=3600`, `queue:restart` rather than a hard restart.
- **The Sunnah.com import pipeline** — 30 files, zero direct DB access, chunked upserts,
  count-parity gates, SHA-256 drift detection, and well tested.
- **`AbstractBulkOperationHandler`'s 10 catches** and `SeriesService:318` — legitimate
  per-item error collection returned to the caller, not silent swallows.
- **`People`'s recursive-CTE genealogy** — the right choice for its access pattern, with
  an explicit depth cap. Not a `LazyCollection` candidate.
- **`Content`, `Hadith`, `People`, `Topics` domain layers** — zero `catch (\Throwable)`;
  they let exceptions reach the central handler. This is the better default.
- **deptrac's current baseline config** — failing only on *new* violations is the right
  call for a mid-migration codebase. Do not add `--fail-on-uncovered`.
- **`Http::fake()` discipline in tests** — no test touches the network. Keep it.

**Verified clean in the security audit — do not re-litigate:** SQL injection (all 110
raw-SQL sites reviewed; every interpolated identifier is a constant, enum value or
config-derived, every value bound), mass assignment (zero models without
`$fillable`/`$guarded`), command injection (`new Process([...])`, no shell; host-locked
YouTube URLs), path traversal, SSRF (every outbound URL config-derived), session
handling (regenerate on login, invalidate on logout, separate admin guard, credentials
validated before any session is created), upload validation (content-sniffed MIME *and*
extension *and* dimensions), and log hygiene (no bodies, no tokens, no PII).

**Do not "modernise":** `match`/enum usage, `strict_types` coverage, the DTO immutability
pattern, or `mixed` parameters where the value genuinely is variant. All three would be
pure churn.

**Do not containerise production.** The native stack is working, tuned and documented.
W5.3 closes the parity gap from the dev side at a fraction of the risk.

---

## 7. Provenance and confidence

Findings carry different weights and are labelled accordingly.

**Verified directly during this session** (commands run, output read): the four phantom
classes (`class_exists()` returned false for each); the `.env`/`www-data` permission
arithmetic and the inline-execution branch in `ArtisanCommandService`; `releases/`
holding exactly 1 directory and `KEEP_RELEASES` appearing 0 times in `deploy.sh`;
`permission:` appearing 0 times in `routes/`; `SanitizesRichText`'s 6 users and the
absence of articles among them; Laravel's `rememberForever` having no null check;
`deptrac` at 0 violations / **7,333 uncovered**; `pint --test` at 138 style-only
failures; `QuranCommandService`, `CommandBus` and `QueryBus` each having **zero**
consumers; **the `title`/`name` column matrix for all four bulk-handler tables, queried
against the production `information_schema`**; and the LOC, test, `strict_types`,
catch/report and localization counts in §0.

**Reported by audit agents and internally consistent, but not individually re-read by
me:** the per-file line references in the module plans that originate in the 16 Aug
column audit and the four new audits. Several were found independently by more than one
agent. Open the exact range before changing it.

**One correction carried forward:** an earlier draft of the index table recorded the
tafsir FULLTEXT index as existing, from my own over-broad grep. The live schema says it
does not. Corrected here and in the source document.

**Not verified, deliberately:** the precise error surfaced by P0.3. Triggering it would
*be* the outage. The permission arithmetic and the code path are both confirmed; only
which error appears first is inferred.

**Load-test numbers** are from a single test host against a rate-limited origin. They
bound *this* configuration honestly; they do not extrapolate to distributed traffic. Any
capacity claim after Phase 2 should be re-measured, not assumed.
