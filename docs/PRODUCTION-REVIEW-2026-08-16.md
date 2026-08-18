# rasoolallah.com — production review, 16 Aug 2026

RAM diagnosis, measured load-test results, and a consolidated code review across
the data, HTTP, memory/storage and infrastructure layers.

> Companion document: **[COLUMN-SELECTION-AUDIT-2026-08-16.md](COLUMN-SELECTION-AUDIT-2026-08-16.md)**
> — module-by-module and endpoint-by-endpoint audit of column/relation selection and
> unbounded fetches. It contains two live production bugs verified against real data
> (a 500 on four Quran API endpoints, and `/api/v1/quran/surahs` reporting the wrong
> surah number) plus a PII leak on `/api/v1/filters/{id}/apply`.

Server: OVH VPS `vps-510cdeaa`, Ubuntu 26.04, 3.7 GiB RAM / 2 vCPU / 38 GB disk,
MySQL 8.4, PHP 8.5, nginx, Cloudflare in front.

---

## 1. The RAM question — answered

**Nothing is leaking. The memory is configuration, not consumption.**

At rest: 1.6 GiB used, 2.2 GiB available, swap 0 B of 2 GiB, load 0.19.
No OOM events in the kernel log or journal, across any boot.

Measured attribution (PSS, so shared pages are counted once):

| Process group | Cost | Note |
|---|---|---|
| `mysqld` | **890 MB** | Almost entirely private. See below. |
| 4 queue workers + scheduler | 223 MB | ~44 MB each; RSS reads 72 MB but 28 MB of that is shared |
| php-fpm (master + 4–6 children) | ~200 MB | ~192 MB is the *shared* opcache + JIT segment, counted once |
| fail2ban, journald, systemd, misc | ~250 MB | |

### Where the waste actually is

**The InnoDB buffer pool is 1 GB. The entire database is 122 MB.**

```
Innodb_buffer_pool_pages_total = 65536   (1 GB)
Innodb_buffer_pool_pages_data  = 17516   (~274 MB actually holding data)
Innodb_buffer_pool_pages_free  = 48014   (~750 MB allocated, empty, never used)
```

750 MB — a fifth of the machine — is reserved and idle. On top of that,
`performance_schema=ON` costs another ~150–250 MB, and it auto-sizes off
`max_connections=100` while `Max_used_connections` since boot is **8**.

Real connection ceiling is arithmetic, not guesswork: 16 fpm children + 4 queue
workers + ~3 scheduler subprocesses + admin shells ≈ 23.

### The other resource problem: disk

`/var/log` is 685 MB, of which **627 MB is `/var/log/journal`** with no
`SystemMaxUse` cap. nginx logs are 7.1 MB and rotate correctly — journald is the
whole story.

### Fixes, in order of payoff

| Change | Frees | Where |
|---|---|---|
| `innodb_buffer_pool_size` 1G → **384M** | ~600 MB RAM | `deploy/config.env.example` |
| `performance_schema` → **OFF** | ~150–250 MB RAM | `deploy/templates/mysqld-tuning.cnf` |
| `max_connections` 100 → **40** | per-thread buffers | `deploy/config.env.example` |
| journald `SystemMaxUse=200M` | ~430 MB disk | new, in `deploy/stages/85-logrotate.sh` |
| `pm.max_children` 16 → **10** | removes a 4 GB overcommit | `deploy/config.env.example` |

That last one matters more than it looks: 16 × `memory_limit=256M` = 4 GB of
theoretical allocation on a 3.7 GB box, and stage 10 sets
`vm.overcommit_memory=1`, so the kernel never refuses an allocation — it
OOM-kills instead, and the fattest process is MySQL. On 2 vCPU, 16 concurrent PHP
processes is queueing, not throughput.

Apply MySQL changes with `sudo FORCE=1 ./provision.sh 55-mysql` (restarts MySQL —
do it off-peak).

---

## 2. Measured load-test results

Suite lives in `tests/load/` — see its README for the Cloudflare and rate-limiter
caveats, which are not optional reading.

### Baseline — through Cloudflare (smoke, 2 VUs)

All thresholds green, 0 errors, 0 5xx. `p(95)=612 ms`, Cloudflare cache hit 25%.

### Sustained load — direct to origin, Cloudflare bypassed (18 VUs, 3810 requests, 16 min)

| Metric | Result |
|---|---|
| `http_req_duration` p95 | **508 ms** ✅ |
| p99 / max | 910 ms / 4.11 s |
| TTFB p95 | 223 ms |
| **5xx errors** | **0** ✅ |
| Rate-limited (429/503) | **0** |
| 404s | 137 (3.59%) — **all `/robots.txt`**, see finding H1 |
| Per-group p95 | static 84 ms · json 158 ms · article 409 ms · home 439 ms · quran 570 ms · nav 772 ms |

Server during that run:

| | Peak | Ceiling |
|---|---|---|
| Memory used | 1703 MB | 3.7 GiB |
| Memory available | 2110 MB | — |
| Swap used | **0 B** | 2 GiB |
| load1 | 0.74 | 2 cores |
| php-fpm children | **6** | 16 |
| fpm socket backlog | **0** | — |
| MySQL threads running | 3 | 100 |
| mysqld RSS drift | 876 → 892 MB | — |

**Read:** at 18 concurrent users the box is barely working — 6 of 16 workers, 20%
of one core's worth of load, zero queueing, zero memory pressure. The application
is not the constraint at this level. `lat_nav` (the module landing pages) is the
slowest group and the first thing to optimise.

### Stress — direct to origin, ramp 15 → 60 VUs (8,989 requests, ~13 min)

| Metric | 18 VUs | **60 VUs** |
|---|---|---|
| p95 | 508 ms | **1.69 s** |
| p99 | 910 ms | 3.01 s |
| max | 4.11 s | 12.53 s |
| TTFB p95 | 223 ms | 303 ms |
| **5xx** | 0 | **0** ✅ |
| Rate-limited | 0 | **0** |
| checks passed | — | 98.71% |

Per-group p95 at 60 VUs: static 127 ms · json 289 ms · article 1.46 s · home 1.67 s ·
quran 1.69 s · **nav 2.19 s (p99 6.77 s, max 12.53 s)**.

Server at peak:

| | Peak | Ceiling | Headroom |
|---|---|---|---|
| **load1** | **1.15** | 2 cores | **the binding constraint** |
| php-fpm children | 7 | 16 | 56% unused |
| Memory available | never below **2076 MB** | — | untouched |
| Swap | 0 B | 2 GiB | untouched |
| MySQL threads running | 3 | — | idle |
| MySQL threads connected | 9 | 100 | 91% unused |
| **MySQL slow queries** | **0** | — | `long_query_time=1s` |
| mysqld RSS drift | 876 → 897 MB | — | stable, no leak |

### What the stress test proves

**The bottleneck is PHP CPU on 2 vCPU. It is not memory, not MySQL, and not
`pm.max_children`.** Latency tripled while 2 GB of RAM stayed free, MySQL sat at 3
running threads of a 100 connection budget, and only 7 of 16 fpm children were ever
spawned.

Three consequences that should redirect the tuning effort:

1. **Raising `pm.max_children` would do nothing** — 9 of the 16 were never used.
   Lowering it to 10 (§1) is confirmed safe by measurement, not just arithmetic.
2. **Adding RAM would do nothing.** The memory fixes in §1 are about removing an
   OOM-kill *risk* and reclaiming waste, not about relieving current pressure.
3. **`Slow_queries` stayed at 0 for the entire run.** No individual query exceeded
   one second. So the latency is not one bad query — it is a large number of
   individually-fast queries plus PHP work per request. That is the exact signature
   of the N+1 and per-item-lazy-load findings in §4 (C12, C13, H9, H15) and of C7,
   where 95% of public HTML reaches PHP at all because it carries no cache headers.

The fix for capacity here is **less PHP work per request**, in this order:
C7 (cache headers → Cloudflare absorbs the traffic), then C14/C2 (Redis + stampede
protection), then C4/C12/C13 (search FULLTEXT, poster N+1, hot-row write).

### Important ceiling

nginx applies `limit_conn perip 20` and `limit_req zone=api rate=30r/s`. A test
from a single IP cannot exceed 20 concurrent connections — past that you measure
the shaper, not the app. Real traffic arrives from many IPs, so this does not
bind in production. To honestly simulate "huge concurrent traffic" you must run
distributed (k6 Cloud or several VMs); the README documents the alternative of
temporarily allowlisting a test IP.

There is a related **critical** infra risk in `deploy/stages/65-nginx.sh`: it
fetches Cloudflare's IP ranges live and, on failure, warns and continues. Without
real-IP restoration those rate limits key on the *Cloudflare edge IP*, so every
visitor behind a given CF datacenter shares one 20-connection budget — mass 503s
that look exactly like an origin outage, precisely under peak traffic. Make it
fail closed.

---

## 3. Live configuration drift — found by diffing the server against the repo

`deploy/stages/75-app.sh` writes `.env` **only if it does not already exist**, so
every change to `deploy/templates/env.production` since first provision has been a
no-op on the server. Nothing reconciles them.

| Key | Repo template | **Live server** | Consequence |
|---|---|---|---|
| `CACHE_STORE` | `redis` | **`file`** | 160 `Cache::remember()` sites doing `fopen`+`unserialize`; no atomic locks → stampedes; no eviction → unbounded disk |
| `DB_QUEUE_RETRY_AFTER` | `3700` | **missing** | falls back to **90 s** |
| `YOUTUBE_MAX_EPISODES` | set | missing | default applies |
| `YT_DLP_TIMEOUT` | set | missing | default applies |

### C1 — `retry_after=90` vs a 3600 s job (live, verified)

```
queue.default                        = database
queue.connections.database.retry_after = 90     ← live value
SyncYouTubePlaylistJob::$timeout       = 3600
RunArtisanCommandJob::$timeout         = 3600
```

The queue considers any sync job abandoned after 90 seconds and releases it while
it is still running. This is the mechanism behind the stuck-YouTube-import
history. Add `DB_QUEUE_RETRY_AFTER=3700` to the live `.env`, and make the safe
value the *default* in `config/queue.php:43` rather than an env override.

### C2 — Redis is installed, authenticated, and idle

`used_memory 833 KB` of `maxmemory 256 MB`, `allkeys-lru`, 35 keys — sessions
only. Meanwhile the cache runs on files. The switch is safe against current code
(checked: `LanguageCacheService` gates on `method_exists(…, 'tags')`; every other
site uses version-counter keys; `MediaViewRecorder` relies on `Cache::add`
atomicity, which Redis strictly improves; no `Cache::flush()` anywhere).

**Migration gotcha:** `queue:restart` writes its flag to the cache store. Workers
booted under `file` will never see a flag written to Redis. After the edit you
must `sudo supervisorctl restart all` **once** by hand.

Then close the hole — add a drift check to `deploy/deploy/deploy.sh` that warns
when `shared/.env` is missing keys present in `env.production`.

### C3 — no database backups, one release retained

No `mysqldump` anywhere in `deploy/`. The prune loop keeps **only** `current`, and
`rollback()` only fires on the `ERR` trap *during* a deploy. A deploy that passes
the health probe but is broken has no way back. Keep 3 releases; add a nightly
dump (122 MB → a few MB gzipped) **and an off-box copy**.

---

## 4. Code review — consolidated

Four parallel reviews (data layer, HTTP layer, memory/storage, infrastructure).
Deduped and ranked by production impact. File:line references are exact.

### Provenance and confidence

Findings here come from automated review agents. Treat that as a starting point, not
a verdict — during this review one agent retracted its own report as fabricated, then
turned out to have been correct (its sub-agents had completed; it had lost track of
them). The reverse can also happen. So:

- **Independently verified by direct reading during this session** — C1, C2, C7, C10,
  C11, C12, C13, C15, C16, H1, H2, H5, H6, H8, H11, plus every finding in the
  companion column-selection audit's §0 and §1, plus the whole index table above
  (checked against `SHOW INDEX` on the live database). These are safe to act on.
- **Reported but not individually re-read by me** — the remainder. They are
  corroborated in that several were found independently by more than one agent, but
  open the exact range before you change it.
- One claim in an earlier draft of the index table was **wrong because of my own
  over-broad grep**, not the agent's: I briefly recorded the tafsir FULLTEXT index as
  existing. The live schema says it does not. Corrected above.

Two findings were narrowed on re-verification and should be read in their corrected
form:

- **H8 is narrower than stated.** `TafseerRepository:343` is the *only* `clearCache()`
  override missing its `parent::clearCache()` call. `CategoryRepository:253` and
  `SeriesRepository:147,153` all call it correctly. Fix the one, don't sweep the rest.
- **The missing-index list is shorter than first drafted.** After checking the live
  schema, the genuinely absent indexes are the five named in the table above — index
  coverage on `hadiths`, `quran_verses`, `quran_words`, `ai_inquiries`,
  `media_episodes`, `episode_watch_progress`, `muslims`, `muslim_relationships`,
  `topic_items` and `tags.usage_count` all match their hot paths and need nothing.

One additional correctness bug surfaced during re-verification and is not listed
elsewhere: **`AuditService.php:130-165`** calls `->getCollection()` on a **paginator**,
so a "monthly audit report" silently aggregates only the first page of results.

### CRITICAL

**C4 — Public `/search` does full-table `LIKE '%…%'` scans; the FULLTEXT indexes are never used.**
`app/Domain/Shared/Search/SearchQueryBuilder.php:71` selects MATCH/AGAINST only if a
model implements `getFullTextColumns()` — **no model does**. So 100% of unified
search takes `applyBasicSearch()` (:190). Per request that is 4 modules × (COUNT +
SELECT) plus correlated `orWhereHas` LIKEs over `article_locale.content` (JSON) and
`quran_verse_translations` (~6236 verses × N editions). These indexes already exist
and are ignored: `article_locale ft_search`, `quran_verse_translations(text,…)`,
`seerah_event_translations`, `muslims ft_muslims_search_*`. `FullTextSearchHelper`
already exists and is used correctly elsewhere. One bot crawling `/search?q=`
saturates both cores.

**C5 — Image upload can OOM a worker synchronously.**
`app/Services/MediaService.php:599` runs `file_get_contents()` then `decodePath()`
**before** `scaleDown()`, so GD allocates the full source bitmap. There is no pixel
guard — `StoreMediaRequest.php:128` caps only bytes (10 MB). A 10 MB PNG of
low-entropy content can exceed 100 Mpx = 400 MB+ against a 256 M limit. Runs
inline in the FPM request (`MediaController.php:80`; `:99` bulk-uploads 10 files).
A `memory_limit` fatal does not unwind, so the `finally` at `:92` never runs and the
plaintext temp copy leaks. Add a `getimagesize()` pixel guard before decode.

**C6 — Encrypted media decrypts to a fresh temp file on every request, and is marked uncacheable.**
`MediaService.php:257-281`, reached from two *public, unthrottled* routes
(`routes/web.php:30` home-card images, `:107` article media). `Cache-Control: private`
is exactly the token Cloudflare honours by not caching, so every home-card image on
the edge-cached homepage fans back to origin. N concurrent views = N full-size
plaintext copies in `/tmp`; `deleteFileAfterSend` fires on kernel terminate only, so
any fatal leaks the file permanently. Cache the decrypted variant on disk keyed by
content hash and make public variants publicly cacheable (paths already carry a
random SHA-256 filename).

**C7 — Only 2 routes emit cache headers.**
`app/Http/Middleware/CacheGuestPages.php:31` — `CACHEABLE_ROUTES = ['public.home',
'public.media']`. Every other public surface (`/articles`, `/quran/*`, `/seerah/*`,
`/hadith/*`, `/sahaba`, `/topics/*`, `/series/*`) emits no `Cache-Control`, no
`ETag`, no `Last-Modified`. Cloudflare will not cache HTML without an explicit
directive, so ~95% of public traffic reaches PHP even though the payloads are
already `Cache::remember` blobs. **This is the single highest-leverage change for
traffic capacity** and it is one constant.

**C8 — Exports materialise the whole dataset twice, in a web request.**
`ExportController.php:61` calls `$exporter->collection()->count()`, then `:66` builds
the collection **again**. `BaseExporter.php:22` uses `FromCollection` +
`ShouldAutoSize`; `VerseExporter.php:57` is `all(['*'], ['surah'])` — all 6236 verses
with translations, hydrated, then every cell measured. Switch to `FromQuery` +
`WithChunkReading`, count with `getCountForPagination()`, drop `ShouldAutoSize`.

**C9 — `BaseRepository::all()` caches `SELECT *` of whole tables into the file cache.**
`app/Domain/Shared/Repositories/BaseRepository.php:51`. Reached with `['*']` from
`TafseerExporter.php:63` (every `quran_verse_tafsirs` row incl. `longText`) and
`VerseExporter.php:57`. OOMs on the way in; if it succeeds, every later hit pays the
unserialize. Refuse to cache an unbounded `select *`.

**C10 — `SurahRepository::all()` cache key ignores its arguments.**
`app/Domain/Quran/Repositories/SurahRepository.php:49` caches under a fixed key while
accepting `$columns`/`$relations`. Run the export first (`SurahExporter.php:49` passes
`['verses']`) and the key holds 114 surahs **plus 6236 verse models** (~5 MB), which
every public surah-list request then unserializes. Run the list first and the export
silently emits surahs with no verses. Same shape: `SeerahEventRepository.php:368`.

**C11 — `QuranSyncService` holds a transaction open across three remote API calls.**
`app/Domain/Quran/Services/QuranSyncService.php:148` — `beginTransaction()`, then
three HTTP fetches with 0.8–2.5 s deliberate delays and up to 60 s retry sleeps,
then ~2900 statements. Al-Baqarah holds one of 100 connections and row locks on
three tables for minutes. Fetch first, then `DB::transaction()` around writes only.

**C12 — Media poster accessors fire 2 queries per program, and the cache does not help.**
`app/Domain/Media/Models/MediaProgram.php:193` — `$this->episodes()->value('video_url')`.
`getFirstMediaUrl('poster')` is always `''` for YouTube-imported programs, so the
chain always falls through. `MediaProgramResource.php:34-35` emits both `poster_url`
and `banner_url`. The repository caches the *collection*, but the accessor runs at
serialization time **after** the cache hit — a warm cache buys nothing. A 24-program
`/media` payload = 48 extra round trips per request.

**C13 — Synchronous hot-row `UPDATE` on every Seerah page view.**
`app/Domain/Seerah/Services/SeerahEventService.php:63,81` → `incrementViews()`
(`SeerahEvent.php:331`). The read is cached for 30 min; the write still fires. Every
concurrent reader of a popular event serialises on one InnoDB row lock. The Media
domain already solved this — queued and deduped via `MediaViewRecorder` on the
`tracking` queue. Route Seerah through the same recorder.

**C14 — Cache stampede: 160 `Cache::remember()` sites, zero locks, file driver.**
No `Cache::lock` or `Cache::flexible` anywhere. Worst: `Site/ArticleController.php:38`
(rebuilds 500 articles + tags + facets), `Site/HomeController.php:42-140` (six keys,
including `inRandomOrder()` over ~38k hadiths). When a TTL lapses, up to 16 workers
rebuild simultaneously. `Cache::flexible()` (Laravel 11+ SWR) fixes this once Redis
is in place.

**C15 — `/genealogy?root=` bypasses the cache entirely.**
`app/Http/Controllers/Site/GenealogyController.php:65` caches only the default root.
The uncached path issues **one query per node, recursively, to depth 3** (:672), and
the route has **no throttle**. `SahabaController::tree` redirects every
`/sahaba/{slug}/tree` into it, so `?root=` is crawlable and enumerable. Separately,
`GenealogyTreeService::descendants()` (`:113`) bounds depth at 60 but not rows, and
`UNION ALL` emits a person once per distinct path — combinatorial in a corpus with
endemic cousin marriage. Cache every root; add `LIMIT` + a `nodes_capped` flag.

**C16 — Deleting a media program never invalidates its public caches.**
`MediaProgramService.php:177` deletes the row, *then* calls `clearCacheForModel($id)`,
which opens with `MediaProgram::find($id)` and **returns early when the row is gone**
(`MediaProgramRepository.php:393`). A deleted program keeps rendering on `/media` for
the full 30-minute TTL and 404s from a live link. `LiveChannelService::deleteChannel:131`
gets the order right — this is the outlier. Clear before deleting.

### HIGH

**H1 — `/robots.txt` returns 404 in production.** Found by the load test (all 137
"failures"). `routes/web.php:96` defines it dynamically, but
`snippets/tlm-app.conf` has `location = /robots.txt { … }` with **no `try_files`**,
so nginx looks for `public/robots.txt`, which does not exist, and 404s before Laravel
sees it. Search engines are getting a 404 for robots.txt. Add `try_files $uri
/index.php?$query_string;` to that location block (`/favicon.ico` has the same shape
— verify it too).

**H2 — Two live route-shadowing bugs.**
- `routes/admin.php:287` registers `/{module}` before `:293` `/history` →
  `GET …/import/history` resolves to `importForm('history')`; `admin.import.history`
  is unreachable.
- `routes/admin.php:230` registers `roles` resource `only(['index','show'])` — hence
  `GET roles/{role}` — before `:247` registers `roles/create`. `GET …/roles/create`
  resolves to `RoleController::show()`, whose signature is `show(int $id)` → TypeError
  500. **The role-creation page is dead.**

**H3 — Unclamped `per_page` on ~20 admin endpoints.** `$request->integer('per_page', N)`
with no ceiling: `Quran/VerseController.php:43,300` (6236 verses × translations),
`Seerah/SeerahEventController.php:56,316`, `Quran/TafseerController.php:43`,
`AuditController.php:51,106,249,275`, `ActivityLogController.php:42`,
`Blog/CategoryController.php:45`. `?per_page=999999` is a one-request OOM.
`Admin/UserController.php:45` already clamps correctly — the pattern exists, just
isn't applied. Add one `perPage()` helper to the base controller.

**H4 — `AuditController::exportLogs():181` builds the whole table as a single string.**
Every filter is optional, so `GET …/audit/export` with no parameters serialises the
entire `audit_logs` table into one PHP string. `$format` is never validated. A GET
route means a browser prefetch can fire it. Stream it with `chunkById` + `fputcsv`.

**H5 — Spatie conversions run inline in the web request.**
`config/media-library.php:32` — `queue_conversions_by_default` defaults to `false` and
`env.production` never sets it. Every conversion (plus the `Pdf`, `Video`, `Avif`
generators) runs in the FPM request, and `avifenc -j all` saturates every core. Flip
to `true`; the `default` queue already has recycling workers.

**H6 — `config/media-library.php:15` — `max_file_size` is 100 **GiB**, not 100 MB.**
`1024 * 1024 * 102400` — `102400` is already MB, so the extra multiplier makes Spatie's
guard useless. Only `client_max_body_size` and `upload_max_filesize` remain.

**H7 — No retention on the fastest-growing tables.** `media:prune-view-events` exists
(`routes/console.php:97`) but is **deliberately not scheduled** (:95). `search_history`
and `user_interactions` have no prune at all and grow by one row per public
search/view forever. `queue:prune-failed` and `queue:prune-batches` are not scheduled.
`app/Jobs/CleanupFailedImports.php` is **never dispatched anywhere** — dead code, so
uploaded import files are never removed.

**H8 — `TafseerRepository::clearCache():343` never calls `parent::clearCache()`.**
The `VersionedCacheKey` counter is never bumped, so every inherited cached read serves
pre-edit data for 900 s after any write. Exactly the failure CLAUDE.md warns about.
`CategoryRepository` and `SeriesRepository` both do it correctly.

**H9 — Verse translations are eager-loaded with no language filter.**
`VerseRepository.php:135` loads *every* translation edition of *every* language, both
`text` and `text_notashkeel`, into the file cache, on the public reader path. Filter
by `whereIn('lang', ['ar', $locale])`.

**H10 — Unbounded public work with no throttle.** `routes/web.php:159` `/media/{slug}`
→ `MediaEpisodeService::getByProgram()` has **no limit** (a YouTube-synced program with
hundreds of episodes loads them all, uncached). `:109` `/articles/{slug}` never caches
the 404 result, so unbounded distinct slugs = unbounded DB hits. Same shape at `:165`
`/topics/{slug}`.

**H11 — `Tag` still reads a dropped column.** `app/Domain/Blog/Models/Tag.php:70,79,108`
reads `getAttributes()['name']`, but `tags.name` was dropped by
`2026_03_10_100002_drop_json_columns_from_blog_tables.php:22`. The `?? '{}'` swallows
it, so instead of a 500: `getNameInLanguage()` returns `null` for every tag in every
locale, and `static::creating` slugs the literal `'tag'` for every auto-slugged tag.
`Category:230` was migrated properly; `Tag` was missed.

**H12 — `/search?sort=popular` silently returns nothing.**
`SearchQueryBuilder.php:40` maps `popular` → `views_count`. `articles` has `views`;
`quran_verses` and `muslims` have neither. Produces `Unknown column 'views_count'`,
swallowed by the controller `catch` into an empty results page. User-reachable.

**H13 — Quran sync writes rows the reader can never see.**
`QuranSyncService.php:271,289,306` writes `resource_id = NULL` while matching on
`(verse_id, lang)`. That unique was replaced by `(verse_id, resource_id)`
(`2026_06_15_130002:63`). Rows written by a sync are invisible to
`VerseRepository::getSurahTranslationByResource():123`.

**H14 — Importers hold one transaction across the entire file, fully in memory.**
`BaseImporter.php:114` `Excel::toArray()` reads the whole sheet and keeps it for the
object's lifetime; `:284` wraps every row in **one** transaction; every failure appends
the *entire row* to an unbounded error array (`:210,311`); and `parseFile()` re-runs up
to three times across the wizard. A 20k-row sheet is 60–100 MB before errors. Chunk
both the parse (generator) and the transaction (per 500).

**H15 — `FavoriteServiceProvider.php:71` defeats deliberate eager loading.**
`Article::getArticleLocale()` (`app/Models/Article.php:300`) is
`$this->locales()->where(...)->first()` — always a fresh query, ignoring the loaded
relation that `FavoriteRepository:21` explicitly eager-loads "so listing mixed
bookmarks never N+1s". Up to 2 queries per bookmark on a paginated public page. Use
`$model->locales->firstWhere('locale', $locale)`.

**H16 — `sync` queue runs yt-dlp/ffmpeg at normal priority on 2 cores.** An admin
clicking "import playlist" during a traffic peak competes head-on with php-fpm. Prefix
the supervisor command with `nice -n 15 ionice -c2 -n7`.

**H17 — `deploy.sh` runs `optimize:clear` mid-deploy.** That includes `cache:clear`, and
`storage` is symlinked to `shared/`, so it wipes the cache the **currently-live**
release is serving from — before the symlink has even flipped. Under load that is a
stampede against a cold MySQL while the box is also running `composer install`. Use the
four individual `*:clear` commands instead, and add an OPcache warmup after the fpm
reload.

**H18 — `worker_connections 768`.** Ubuntu default, never templated. With
`worker_processes auto` (2) the ceiling is 1536 slots, but each proxied request uses one
for the client *and* one for the FastCGI upstream — a real client ceiling of ~768. Behind
Cloudflare, origin connections are long-lived keepalives from many edge PoPs. Raise to
4096 with `multi_accept on`; add `backlog=1024` to the `listen` directives and
`listen.backlog = 4096` to the fpm pool. Requires `restart`, not `reload`.

**H19 — Unbounded cache keys built from user input.** `Site/SeerahController.php:225`
builds keys from `?q=` (unbounded length) and `?page=` (no ceiling). On the **file**
driver each distinct value writes a new file that nothing evicts.
`StaticPageService.php:36` is worse — `rememberForever` on a lookup that does *not* skip
nulls, so `/pages/{random}` in a loop fills the disk permanently. Clamp both dimensions;
Redis + `allkeys-lru` makes it survivable.

### MEDIUM — the short list

- **Missing indexes** — verified against `SHOW INDEX` on the **production** database,
  not just the migrations:
  - `articles(status, created_at)` and `(status, views)` — absent. Every public
    listing is `WHERE status='published' … ORDER BY created_at DESC` and filesorts.
  - `media_programs` — has `(is_published, publish_at)` but **not** `(is_published,
    created_at)`, so `getRecent():124` (the one row-builder with no cache) cannot use
    it for its ordering.
  - **`FULLTEXT` on `quran_verse_tafsirs.text` — absent.** Confirmed on the live
    schema: that table carries only PRIMARY, `unique(verse_id, resource_id)`, and
    plain indexes on `verse_id`, `verse_key`, `lang`, `active`, `resource_id`. So
    `TafseerRepository:94,219` `LIKE '%…%'` a `longText` column with nothing to fall
    back on.
  - `search_history(query(64))`, `user_interactions(interaction_type, created_at)` —
    absent.
  - **Redundant indexes are worse than first reported.** Live `articles` has *two*
    duplicate pairs: `articles_status_index(status)` **and** `idx_articles_status(status)`,
    plus `articles_slug_unique(slug)` **and** `articles_slug_index(slug)`. Four indexes
    doing the work of two — write amplification on the busiest table.
- **Non-sargable predicates:** ~10 repositories wrap indexed date columns in
  `whereDate()`, discarding the index — including `SeerahEventRepository:329,333` where
  `gregorian_date` *is* indexed. Plus `ArticleRepository:92`
  (`JSON_EXTRACT(content,'$.body') LIKE`), `:698` (`orderByRaw('views / DATEDIFF(…)')`),
  and `VerseTranslation.php:126` which ORs `MATCH…AGAINST` with `LIKE '%…%'` — the OR
  makes the FULLTEXT index unusable, so the index costs storage and buys nothing.
- **Dropped-column landmines:** `Reciter.php:215` `JSON_EXTRACT(name,'$.ar')`;
  `CategoryService.php:323` `$category->name['en']` → TypeError on the
  validation-failure path; `AnalyticsService.php:355` `$item->title['ar']`, swallowed
  by a `catch (\Throwable)` that empties the whole `top_content` payload.
- **`DashboardService.php:96-100,135,165`** — `catch (\Throwable)` into a zeroed
  fallback with **no `report()`**. Exactly the pattern CLAUDE.md flags as how a renamed
  table silently zeroed the dashboard.
- **`ArticleResource.php:48,58`** — `$this->category->name` on a model whose `name`
  column was dropped and which registers no accessor: silently `null` in every API
  article payload. `slug` on the same line still resolves, which is why it reads as
  working.
- **`bootstrap/app.php:5`** imports `App\Domain\Sahaba\Exceptions\SahabiNotFoundException`
  from a domain that no longer exists. Doesn't fatal (reflection + `is_a` returns false),
  but the handler is dead — People 404s fall through and return **422**.
- **Morph-value split:** `favorites.favorable_type` stores FQCNs while
  `AnalyticsService.php:175,347` queries with `getMorphClass()` (`'article'`). Article
  favourite counts on the dashboard are permanently 0.
- **`InteractionService.php:145`** — read-modify-write on `total_time_spent_seconds`;
  classic lost update. Use `->increment()`. And `:86` runs `afterResponse()` work
  *inline in the fpm worker* — 6–8 UPDATEs plus a full COUNT, direct throughput loss.
- **`SetLocale.php:109,151`** — up to 5 cache reads per request (filesystem round-trips
  on the file driver). Memoize per request.
- **`composer install`** is missing `--classmap-authoritative`; the release tree is
  immutable so the PSR-4 filesystem fallback can only produce failed `stat()` calls.
- **`render_template`** (`deploy/lib/common.sh:101`) writes via shell redirection, so
  output gets umask 0644 — that makes `/etc/redis/redis.conf.d/tlm.conf`
  **world-readable with `requirepass` in it**, and `cp -a` propagates it to every
  `.bak`. `chmod 0640` + `chown root:redis`. Also prune the unbounded `.bak` pile.
- **`fastcgi_buffers 16 16k`** (288 KB total) — Blade pages with inlined critical CSS
  overflow to `fastcgi_temp` on disk and hold the fpm child open until the response
  drains. Raise to `16 32k` + `fastcgi_busy_buffers_size 64k`.
- **`keepalive_requests`** at the nginx default 1000 forces a TLS renegotiation per 1000
  requests per Cloudflare edge connection. Raise to 10000.

### Verified sound — do not "fix" these

Stated explicitly so nobody regresses them:

- **OPcache + deploys are correct.** `validate_timestamps=0` *plus* `systemctl reload
  php-fpm` on every release *plus* `fastcgi_param SCRIPT_FILENAME $realpath_root$…` so
  cache entries key on the resolved release path. Stale-code-after-deploy cannot happen
  here. This is the most commonly botched thing in this stack and it is right.
- Media encryption/decryption **is** genuinely streaming — 1 MiB chunked `fread`/`fwrite`
  with libsodium secretstream (`MediaService.php:627,697`). No whole-file buffering.
- The hadith import (`SunnahCollectionImportService::stream()`) and the portal export
  (`ExportPortalDataCommand`) are both correctly chunked and streaming.
- `Cache::flush()` appears **nowhere** — only in comments explaining its removal. The
  `VersionedCacheKey` pattern is used correctly throughout.
- All four caches are built before the symlink flip; the flip is atomic; the health probe
  gates it; `queue:restart` is used instead of `supervisorctl restart all`.
- The API is secure-by-default behind `auth:sanctum` + `abilities:api:access`; no CSRF
  bypass; no `$request->all()` mass assignment; admin CRUD returns redirects to HTML forms
  and content-negotiates correctly.
- nginx is otherwise well-configured: gzip types, `open_file_cache`, TLS session
  resumption + stapling, rate-limit zones, 30d immutable static caching, `.env`/`.bak`
  deny rules, Cloudflare real-IP.
- The Content domain (`HomeContentRepository`, `MenuItemRepository`, `FaqRepository`, …)
  uses explicit column lists, column-constrained `with()`, per-locale keys and complete
  flush sets. **This is the pattern the other domains should be measured against.**
- Queue jobs all declare `$tries`/`$timeout`/`$backoff`; all four supervisor programs
  recycle and use `startsecs=10 startretries=20`.
- **Do not move the queue to Redis.** `maxmemory-policy allkeys-lru` is instance-wide, so
  queued jobs would become evictable — a durable queue traded for a lossy one. MySQL is
  idle (peak 8 connections) and 3 workers polling at `--sleep=3..5` is ~60 trivial
  indexed queries per minute. The current setup is correct.

---

## 5. Recommended order

**Today — zero risk, large payoff**
1. Live `.env`: add `DB_QUEUE_RETRY_AFTER=3700` (C1), set `CACHE_STORE=redis` (C2),
   then **one manual** `sudo supervisorctl restart all`.
2. journald cap → reclaims ~430 MB disk immediately.
3. Fix `/robots.txt` (H1) — one `try_files` line.

**This week — capacity**
4. Expand `CACHEABLE_ROUTES` (C7). Biggest single traffic-capacity win in the codebase.
5. MySQL retune: buffer pool 384M, `performance_schema=OFF`, `max_connections=40`.
   Frees ~800 MB–1 GB. Brief restart, off-peak.
6. php-fpm `max_children` 16 → 10, `listen.backlog`, nginx `worker_connections`.
7. Route search through `FullTextSearchHelper` (C4) + add the missing indexes.

**This sprint — correctness**
8. C5/C6 (image OOM guard, media decrypt cache), C8–C11 (export/cache/transaction bombs),
   C12/C13 (N+1 and hot-row write), C16 (delete-order cache bug).
9. H2 (route shadowing — the roles page is dead), H3 (`per_page` clamp), H11/H12/H13
   (dropped-column and silent-empty-result bugs).

**Before you call it production-ready**
10. Nightly `mysqldump` **with an off-box copy**, and keep 3 releases (C3).
11. Make the Cloudflare real-IP fetch fail closed (`deploy/stages/65-nginx.sh`).
12. Add the `.env`-drift check to `deploy.sh` so this section never regenerates.
