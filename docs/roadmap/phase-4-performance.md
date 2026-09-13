# Phase 4 — Measured performance and delivery

[Plan index](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/docs/roadmap/README.md)

**Outcome:** optimize demonstrated bottlenecks and adopt infrastructure only with a tested operating model. **R20.1–R20.2 begin in Phase 0.** Release-blocking hotspots may be fixed immediately; the phase number does not defer a known outage or data-integrity problem.

Each optional migration requires a decision report, staging evidence, operating ownership, and a reversible cutover. Configuration support alone does not satisfy an infrastructure task.

## R20 — Query Optimization and Database Performance

**Rewritten outcome:** establish repeatable query budgets and fix measured bottlenecks.

**Assessment:** existing code already contains caching, selected columns, translation indexes, and optimization tests. Historical roadmap latency claims were not reproduced. Work starts with an explicit measurement harness.

**Evidence:** [column-selection audit](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/docs/COLUMN-SELECTION-AUDIT-2026-08-16.md), [search query builder](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/app/Domain/Shared/Search/SearchQueryBuilder.php), [domain repositories](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/app/Domain/), [cache config](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/config/cache.php).

**Priority / owner / estimate:** early measurement essential; optimization by impact; backend/performance owner + operations; 2–3 days baseline, 5–10 days initial fixes. No prior migration required.

### Action items

- [ ] **R20.1 — Phase 0:** define representative route journeys and data volumes: Quran reader/translations, Hadith browse/search, topics, dashboard, and relevant authenticated operations. Record hardware, versions, database sizes/indexes, locale mix, and cache configuration.
- [ ] **R20.2 — Phase 0:** run proposed 18- and 60-virtual-user scenarios with a documented arrival/think-time model, warm-up, sustained measurement window, cold/warm cache variants, and concurrency-safe test data. Record p50/p95/p99, throughput, error rate, query count/time, memory, and saturation.
- [ ] **R20.3** Enable bounded/sampled slow-query diagnostics for >100 ms queries with sensitive bindings removed; capture representative query plans. Set per-route query budgets from the baseline.
- [ ] **R20.4** Rank hotspots by user impact and total cost. Fix N+1 loads, excessive result sets, repeated lookups, and demonstrated missing indexes before adding a new distributed service.
- [ ] **R20.5** Validate candidate composite/covering indexes against actual filter/sort/join patterns and write cost. Inspect locale/publication/reference/slug indexes without adding redundant indexes indiscriminately.
- [ ] **R20.6** Test warm-cache mutation invalidation, concurrent requests, imports, and queue contention; avoid achieving fast stale/incorrect responses.
- [ ] **R20.7** Repeat the same scenarios after each meaningful optimization batch and compare results, including error/throughput regression. Retain scripts, fixtures, query plans, and baseline artifacts.
- [ ] **R20.8** Decide whether remaining limitations justify R16, R21, R17, or R09 engine work; document the expected improvement and how it will be verified.

**Acceptance:** no unexplained N+1/query-budget regression on selected routes; proposed HTTP p95 <200 ms at 18 users and <500 ms at 60 users under the agreed environment; failed targets have measured causes and a reviewed next action. These targets are not universal internet page-load guarantees.

**Rollout / requirement revision:** ship bounded query/index fixes with before/after evidence. Reversible application changes can roll back independently; large index creation requires a database-specific execution plan. Preserve the original slow-query/load goals while replacing unverified historical numbers with a reproducible baseline.

## R16 — Redis Caching Layer

**Rewritten outcome:** adopt Redis cache only where latency and invalidation tests justify it.

**Assessment:** Redis support and a client already exist; committed cache defaults to database. Existing versioned caching is a foundation. Runtime infrastructure was not inspected. Sessions, rate limits, and queues have different correctness and loss consequences from disposable public-page cache.

**Evidence:** [cache config](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/config/cache.php), [session config](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/config/session.php), [database/Redis config](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/config/database.php), [queue config](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/config/queue.php).

**Priority / owner / estimate:** conditional infrastructure; backend + operations; 5–9 days if adopted, excluding provisioning lead time. Depends on R20 evidence and R19 recovery readiness.

### Action items

- [ ] **R16.1** Identify specific expensive cacheable queries/routes from R20; compare the present cache with Redis in staging, including connection overhead, hit/miss behavior, and memory.
- [ ] **R16.2** Document topology, credentials/network access, capacity, eviction, persistence, health monitoring, ownership, and cost. Separate disposable cache from durable queues/session state; different logical databases alone do not isolate instance-wide eviction or failure.
- [ ] **R16.3** Specify cache keys by content version, locale, relevant query inputs, visibility, and authorization where applicable. Limit whole-response caching to appropriate public responses.
- [ ] **R16.4** Prove mutation/import/unpublication invalidation and stampede control. Preserve versioned invalidation; do not use global flush as normal application behavior.
- [ ] **R16.5** Introduce the cache change behind environment/configuration control and verify a bounded database-recompute path or controlled unavailable response during Redis failure. Prevent recompute overload.
- [ ] **R16.6** Evaluate session and rate-limit migration separately: coordinated multi-instance state, logout behavior, limiter guarantees, and outage policy. Do not silently switch these to per-host files when Redis fails.
- [ ] **R16.7** Measure hit ratio, eviction, memory, command latency, database load, and the R20 p95 scenarios; exercise node outage and recovery.
- [ ] **R16.8** Cut over selected public caches first, observe, and expand only after evidence. Record an explicit accepted/deferred decision for each proposed session/limiter change.

**Acceptance:** measured benefit for the selected workload; visibility/locale correctness under hits and invalidation; documented, exercised outage behavior; monitored capacity; no accidental eviction of durable workloads or bypass of rate-limit semantics.

**Rollout / requirement revision:** retain database-backed configuration as a deliberate rollback path after compatibility checks. The original “all defaults to Redis” and automatic file fallback are revised into separate decisions; neither is required to improve public caching. A decision to defer migration should keep the original card pending or explicitly rescope it.

## R21 — Queue Migration to Redis

**Rewritten outcome:** improve queue observability and isolate workloads; migrate if justified.

**Assessment:** database and Redis queue connections, batching, failed-job storage, retry settings, workers, and job backoff already exist. Replacing the driver is not necessary for queued mail. The deploy script also contains worker/configuration checks that should be reused.

**Evidence:** [queue config](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/config/queue.php), [bulk job](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/app/Jobs/ProcessBulkOperation.php), [deployment worker handling](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/deploy/deploy/deploy.sh).

**Priority / owner / estimate:** observability/correctness essential where gaps are found; Redis migration conditional; backend + operations; 4–7 days if adopted. Depends on workload measurements and a durable Redis operating decision coordinated with R16; does not depend on changing the cache driver.

### Action items

- [ ] **R21.1** Inventory jobs and queues: duration, priority, idempotency key, retry/backoff, timeout, side effects, and latency expectations. Separate short user-facing work from long imports and tracking.
- [ ] **R21.2** Measure enqueue-to-start time, queue depth/oldest age, attempts, terminal failures, and worker utilization on the current driver; fix absent consumers and configuration mismatches first.
- [ ] **R21.3** Design worker allocation that reserves capacity for short important work without starving other queues. Verify every named queue has a consumer; long imports do not belong in a blanket high-priority stream.
- [ ] **R21.4** Align effective job/worker timeouts, `retry_after`, and supervisor shutdown grace. Use workload-specific connections where incompatible timeout requirements justify them.
- [ ] **R21.5** Exercise idempotent side effects, retry/backoff, after-commit dispatch, worker death, duplicate delivery, poison jobs, and authorized failed-job replay. Do not promise exactly-once execution from a driver switch.
- [ ] **R21.6 — if adopted** Provision durable Redis capacity separately from evictable cache, migrate dispatch in a controlled window, and drain/consume old database queues until empty. Record which driver owns in-flight jobs.
- [ ] **R21.7** Verify short-priority-job p95 pickup <1 second under the agreed workload, worker restart/deploy recovery, no stranded jobs, and monitored error/age thresholds.
- [ ] **R21.8** Document rollback: stop new dispatch to the failing destination, keep appropriate workers draining both stores, reconcile in-flight IDs, and preserve failed-job evidence.

**Acceptance:** every queue is consumed and observable; timeout/retry settings do not permit premature redelivery of the tested workloads; retries do not duplicate committed business effects; cutover and rollback account for every queued job.

**Rollout / requirement revision:** deliver useful monitoring/worker fixes on the current driver first. Redis adoption remains conditional. Laravel requires worker timeouts to be shorter than retry visibility, which is why the plan validates both rather than only changing `QUEUE_CONNECTION`. [Laravel queue timeouts](https://laravel.com/framework/docs/13.x/queues#job-expirations-and-timeouts).

## R17 — CDN Integration (S3 + CloudFront)

**Rewritten outcome:** migrate eligible media through compatible storage and delivery adapters.

**Assessment:** S3 configuration and media conversion foundations exist, but media follows two paths: Spatie integration and a custom libsodium-encrypted local media service. That custom path uses local filesystem paths for encrypted reads/writes. A disk-default switch is insufficient and may break access or transformation.

**Evidence:** [filesystem config](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/config/filesystems.php), [custom media service](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/app/Services/MediaService.php), [custom media model](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/app/Models/Media.php), [media configuration](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/config/media-library.php).

**Priority / owner / estimate:** conditional delivery migration; backend/media owner + operations; 10–18 days excluding large transfer time and provisioning. Requires R20 bandwidth/latency evidence, an asset/rights inventory, and R19 recovery planning.

### Action items

- [ ] **R17.1** Inventory assets and call sites: Spatie originals/conversions, custom encrypted originals/variants, public images, private/user uploads, and Quran audio/source ownership. Record URLs, byte size, checksum, access rules, and whether caching/redistribution is permitted.
- [ ] **R17.2** Prove an adapter for the custom encrypted path using bounded streaming or controlled temporary files, correct encryption metadata, cleanup, and authenticated delivery. Keep decrypt keys server-side and distinguish object encryption from public CDN response caching.
- [ ] **R17.3** Design public versus private origins, access checks/signed delivery where needed, object keys/versioning, cache headers, content types, and origin restrictions. Avoid caching authenticated plaintext as public content.
- [ ] **R17.4** Implement a manifest-based copy/verify migration: original ID, source/destination, checksum, bytes, variant state, retry status, and URL mapping. Pilot both media systems before bulk transfer.
- [ ] **R17.5** Validate thumbnails/WebP, conversion retries, audio range requests/seeking, correct 206 responses, content length, CORS where required, and old-URL compatibility. Confirm encrypted media remains readable and private assets remain protected.
- [ ] **R17.6** Switch new uploads and then verified existing batches using per-asset disk/location metadata; support dual reads during migration. Retain source files until verification and the retention window pass.
- [ ] **R17.7** Measure origin load, transfer latency, cache hit ratio, errors, storage/request/egress cost, and invalidation behavior. Configure actionable budget alerts.
- [ ] **R17.8** Exercise partial transfer, corrupt object, unavailable origin/CDN, expired access, deletion propagation, and rollback to local reads; produce an operations runbook.

**Acceptance:** both media paths pass upload/read/transform/delete checks; eligible old URLs remain valid; private media is never publicly cached; audio seeking works; every migrated asset has a verified manifest entry; costs and performance are measured.

**Rollout / requirement revision:** “all media” means all eligible inventoried assets under their access/rights model, not mirroring third-party URLs blindly. S3/CloudFront remain the original candidate stack, subject to the adapter and operating-cost decision. Do not delete old files merely because the copy command returned success.

