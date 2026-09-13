# Architecture decisions and failure modes

[Plan index](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/docs/roadmap/README.md)

All decisions below are **proposed for review**, dated 13 September 2026. They guide the rewritten plan and do not claim approved procurement, production configuration, or completed migrations. Local source findings establish the current baseline; performance and operating-cost assumptions still need measurement.

## ADR-001 — Extend the existing modular application

**Context:** Hadith CRUD, Quran importers/player, unified search, engagement metrics, and domain visibility/lookups already exist. Replacing them would duplicate behavior and compound the current boundary debt.

**Decision:** extend current domain services/repositories and expose cross-module reads through Shared contracts registered in composition providers. Keep a single shared audio player. Use the existing MySQL application as the authoritative content and user-state store.

**Alternatives:** duplicate feature-specific services are quick initially but diverge in visibility/state; a distributed service split adds deployment, consistency, and failure-management work without evidence that it is needed.

**Consequences:** preserves routes, data, and tests; requires deliberate adapter work for legacy schema assumptions. New shared contracts must remain narrow and not become a generic cross-domain query escape hatch.

**Validation:** R08 repair tests and deptrac demonstrate publication-safe mapping without forbidden imports; R15 proves one authoritative playback state. Revisit only if measured ownership/scaling constraints cannot be solved within current boundaries.

## ADR-002 — Make infrastructure changes evidence-based and separable

**Context:** Redis and S3 support exist; current defaults are database-backed cache/queue. Unified search already works on MySQL. No production benchmark or operating budget was established by this review.

**Decision:** perform R20 baseline and R09 relevance evaluation before selecting migrations. Redis cache, Redis queues, sessions/limiters, Elasticsearch, and CDN/object storage each require their own benefit, failure model, owner, and rollback. One adoption does not force the others.

**Alternatives:** adopt the entire generated stack immediately, increasing service cost and deployment complexity; retain the current stack permanently, risking avoidable performance/relevance limitations. The proposed middle path uses measured decision gates.

**Consequences:** less speculative implementation, but some product acceptance must wait for evaluation. If a candidate is declined, document the scope revision instead of falsely marking its original card Done.

**Validation:** reproducible before/after scenarios, cost envelope, index/cache freshness, cutover rehearsal, and ownership. Session/queue data must not share eviction assumptions with disposable caches; logical database prefixes alone do not provide failure isolation.

## ADR-003 — Keep canonical content, provenance, and publication state authoritative

**Context:** reader annotations, translations, search indexes, narrator graphs, maps, AI retrieval, and offline downloads all derive from religious content. Inconsistent references or invented certainty would undermine multiple features at once.

**Decision:** use stable content IDs and explicit edition/source/version metadata. Distinguish canonical text from display markup and search-normalized text. Apply shared public-visibility rules to every online projection; queue/index/cache consumers process publication changes and reconcile stale records.

**Alternatives:** copy whole model payloads independently into every feature, which makes schema drift likely; infer missing text/grades/coordinates automatically, which does not establish provenance.

**Consequences:** content manifests and editor review are delivery work, not post-launch polish. Search and offline projections may lag; online visibility checks prevent stale-index disclosure, while an offline public copy cannot be recalled instantly.

**Validation:** R07 corpus checks, R08 visibility tests, R13 exact text round-trip, R10/R27 editorial review, and R12 citation evaluation. Unknown metadata remains explicitly unknown.

## ADR-004 — Use durable events with idempotent consumers

**Context:** imports, bulk results, notifications, indexes, and offline sync may be retried or interrupted. A queue transport cannot guarantee that an external effect happens only once in every failure scenario.

**Decision:** persist canonical state first; dispatch after commit; use event/operation IDs and idempotent handlers for repeated delivery. Keep terminal status, user-visible inbox state, and transport delivery state distinguishable. Reconcile in-flight work during driver changes.

**Alternatives:** synchronous fan-out increases request latency and coupling; relying on queue uniqueness alone does not cover failures after a side effect but before acknowledgement. Introduce an outbox only for specific events whose demonstrated transaction/dispatch gap requires it, rather than adding one universally.

**Consequences:** more explicit state and recovery tests; less duplicate mail/content/index churn. A failed mail transport must not turn a successful content operation into a failure.

**Validation:** R05 retry/outage scenarios, R21 worker-death/driver-drain checks, R23 duplicate event tests, and R24 repeatable sync. Configure effective worker timeouts below retry visibility with a margin. [Laravel queue timeout guidance](https://laravel.com/framework/docs/13.x/queues#job-expirations-and-timeouts).

## ADR-005 — Separate installable shell, public downloads, and private account state

**Context:** R22, R24, and R23 overlap in browser features but have different data needs. Service-worker caches can retain responses across sessions, while browser storage is subject to quotas and eviction.

**Decision:** one service worker manages a bounded public shell; versioned IndexedDB packages handle explicit content downloads and queued personal mutations. Never cache personalized HTML or token-bearing API responses generically. Partition account data and clear it on account transitions. Inbox delivery begins with durable server state and can use polling; push is optional.

**Alternatives:** cache every page for easy offline support, risking stale/private data; build separate offline stores for each module, creating conflicting migrations and cleanup; make WebSocket/push availability necessary for core inbox use.

**Consequences:** more deliberate storage schemas and recovery UX; simpler privacy and logout reasoning. Reconnection in the foreground must work even without background sync.

**Validation:** cross-account/logout tests, eviction/quota recovery, old-worker/new-assets scenarios, and conflicting offline mutations. Storage estimates are approximate and persistence cannot be assumed. [MDN storage behavior](https://developer.mozilla.org/en-US/docs/Web/API/Storage_API/Storage_quotas_and_eviction_criteria).

## ADR-006 — Recover releases using existing deployment structure

**Context:** the deployment script already supports release directories, a symlink switch, workers, health probing, and error rollback. The gaps include a permissive redirect probe and immediate previous-release pruning.

**Decision:** improve the existing path with bounded readiness verification, known-good release retention, trusted SSH host verification, and staged failure drills. Distinguish application liveness from required-dependency readiness. Require backward-compatible schema transitions during a rollback window.

**Alternatives:** add an independent deployment system, duplicating operations; assume a home-page redirect proves readiness; reverse migrations automatically with code rollback, risking data loss.

**Consequences:** additional retained disk space and explicit migration discipline; usable recovery after a failure discovered just after initial success. A proposed 24-hour observation window requires capacity review.

**Validation:** R19 simulates failure before/after activation and verifies the intended compatible version serves within the target five-minute recovery window. First-deploy failure must be reported without pretending a previous release exists.

## ADR-007 — Build data controls and editorial review into growth features

**Context:** deletion exists, export does not, and engagement/search/AI already collect user-linked data. Community revisions may need public attribution while accounts can be deleted.

**Decision:** maintain a purpose/retention/export/deletion register and extend it with every new user-data feature. Keep public rankings, reminders, and push optional. Contributions remain immutable pending suggestions until an authorized editor publishes a revision. Reuse current account controls rather than building a competing identity workflow.

**Alternatives:** add broad “no tracking”/certification claims before inventory; publish contributions directly based on reputation; delete revision provenance implicitly with accounts. These approaches conflict with accurate product behavior or accountable editing.

**Consequences:** explicit policy and schema choices are needed for retained public contributions, backups, and processor records. The product must state actual practices rather than claim that every retained record disappears immediately.

**Validation:** R28 fixture-based exports/deletion coverage, R11 consent/ownership checks, and R26 versioned approval/rollback. This decision describes product behavior and evidence; it does not certify legal compliance.

## Non-functional requirements and failure review

| Failure or constraint | Detection / required response | Owning tasks |
|---|---|---|
| Hidden content appears through stale projections | Visibility fixtures across counts/search/related/download publication; invalidation and source recheck on online results | R02, R08, R09, R16, R24 |
| Wrong Quran text after annotation/copy | Corpus identity checks; fall back to canonical unannotated text | R07, R13, R14 |
| Missing/inaccurate source metadata | Manifest/editor review; show unknown or exclude unsupported claim | R01, R07, R10, R12, R27 |
| Import interruption or duplicate delivery | Resumable checkpoints and stable IDs; preserve unpublished state until validation | R01, R07, R21 |
| Cache outage creates a database surge | Bounded recompute/locking and explicit degradation; observe capacity | R16, R20 |
| Queue cutover strands jobs | Track dispatch destination, consume old/new stores, reconcile IDs | R21 |
| Mail/push transport fails | Durable result/inbox remains; observable retry/failure and preference-aware delivery | R05, R23 |
| New release responds but is unusable | Expected readiness response and release identity, retained compatible release, rehearsed rollback | R19 |
| Encrypted media breaks on object storage | Adapter pilot, byte/checksum verification, controlled decrypt path and dual reads | R17 |
| AI produces unsupported citations | Validate identifiers, reviewed evaluation rubric, bounded retrieval, insufficient-evidence response | R12 |
| Offline package is evicted or corrupt | Version/checksum markers, recoverable download manager, reconnect sync | R22, R24 |
| Account deletion races with background work | Ownership recheck, idempotent cleanup, no deleted-user recreation, explicit retained-data policy | R28, R11, R23, R26 |
| Contributor edits stale canonical content | Optimistic version check and reviewer conflict resolution; immutable revisions | R26 |
| Latency improves by dropping correctness | Load tests include errors, visibility, mutation freshness, and representative query results | R09, R16, R20 |
| Content/moderation workload exceeds capacity | Separate editorial estimates, limited pilots, visible backlog and launch coverage gates | R07, R25, R26, R27 |

## Review record

Proposed decisions are ready for review against the linked task plans. When a decision changes, record the accepted alternative, rationale, affected action IDs, and revised acceptance checks. Do not overwrite the factual baseline or turn an unresolved decision into a checked implementation action.

