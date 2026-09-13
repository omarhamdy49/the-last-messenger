# Phase 5 — Engagement, offline use, and contribution

[Plan index](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/docs/roadmap/README.md)

**Outcome:** readers can build habits, receive chosen updates, use selected content offline, and contribute through a controlled editorial process. These features follow trustworthy content and user-data controls.

Sequencing: R11 goals and R25 localization can progress independently. R22 provides the shell for R24. R23 begins with a durable inbox; only its push extension needs PWA support. R26 uses stable content/locale contracts and does not require launching all three new languages first.

## R11 — User Engagement System (Streaks, Goals, Progress)

**Rewritten outcome:** extend current metrics with clear goals and optional motivation features.

**Assessment:** interaction tracking, engagement metrics, streaks, and profile presentation already exist. Daily goals, grace policy, per-module completion, badges, and optional ranking need a coherent definition rather than a second counter implementation.

**Evidence:** [metrics model](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/app/Domain/Shared/Models/UserEngagementMetric.php), [repository](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/app/Domain/Shared/Repositories/UserEngagementMetricRepository.php), [profile](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/app/Http/Controllers/Site/ProfileController.php), [ownership tests](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/tests/Feature/Api/EngagementOwnershipTest.php).

**Priority / owner / estimate:** engagement enhancement; backend + frontend + product/QA; 7–11 days. Depends on R28 data controls and stable content IDs; reminders depend on R23, but goals do not.

### Action items

- [ ] **R11.1** Document existing event/streak behavior and define what counts as reading/listening/completion by module. A page view is not automatically verified reading.
- [ ] **R11.2** Add user-selected goals with accessible controls, timezone/day boundaries, progress periods, opt-out, and sensible defaults; keep existing metrics authoritative.
- [ ] **R11.3** Define grace/freeze/missed-day rules and test daylight-saving/timezone changes, late events, duplicate events, multiple devices, and deliberate goal edits.
- [ ] **R11.4** Track per-module progress using stable references and idempotent events; explain partially completed or unavailable content. Integrate export/deletion/retention.
- [ ] **R11.5** Add a small, explicit badge rule set with deterministic evaluation and private-by-default visibility; prevent users replaying events to inflate progress.
- [ ] **R11.6** Introduce leaderboard participation only by separate opt-in, with a chosen display identity, withdrawal, and abuse controls. Avoid implying scores measure religious merit.
- [ ] **R11.7** Add optional daily reminders through R23 preferences, including quiet hours/timezone and deduplication; goals remain usable without notification permission.
- [ ] **R11.8** Test user ownership, deletion, opt-out, backfill accuracy, and understandable empty/error states; evaluate actual usefulness before adding more reward mechanics.

**Acceptance:** goals/progress are consistent across repeated events and devices; streak/grace rules are explainable; no one appears on a public ranking by default; opt-out/export/deletion work; reminder choices are respected.

**Rollout / requirement revision:** ship goals and trustworthy progress first, then badges, then optional ranking/reminders as separately verified slices. Existing streak values must be reconciled before backfilling. Original motivation features remain in scope but need not launch together.

## R22 — Progressive Web App (PWA)

**Rewritten outcome:** deliver an installable shell with safe, versioned offline behavior.

**Assessment:** no public manifest/service-worker implementation was found. PWA shell, downloaded content, and notification delivery are related but separate concerns. An install prompt is not guaranteed to appear identically across browsers.

**Evidence:** [public assets](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/public/), [frontend resources](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/resources/), [build configuration](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/vite.config.js).

**Priority / owner / estimate:** new client capability; frontend + QA; 5–8 days. Depends on R28's local-data boundaries and a stable asset/release contract; R24 expands offline content later.

### Action items

- [ ] **R22.1** Define the supported browser/device matrix and install/offline scope. Create a valid manifest, icons, names, colors, start URL, and scope; verify deployment under HTTPS.
- [ ] **R22.2** Add one versioned service worker for approved static assets and an offline navigation fallback. Explicitly bypass admin, auth, profile/export, token-bearing API traffic, and personalized HTML.
- [ ] **R22.3** Coordinate worker activation/cache versioning with deployment. Avoid forcing an update that discards in-progress reading; provide an update/reload action when needed.
- [ ] **R22.4** Provide bounded recently viewed public Quran content for offline use under a clear cache contract, then share data/version APIs with R24 rather than creating competing stores.
- [ ] **R22.5** Test installation/launch on the supported Android/iOS/desktop paths, direct deep links, online→offline navigation, cold start, old-worker/new-assets combinations, and storage clearing.
- [ ] **R22.6** Add push permission only through an explicit user action in R23; never request it on initial page load. Document browser support and the non-push fallback.
- [ ] **R22.7** Provide a recovery/release path for a faulty worker: serve an updated worker that unregisters/cleans affected caches when appropriate, verify recovery on reload, and retain normal web navigation.

**Acceptance:** users on the supported matrix can install or follow the documented platform path; the shell and declared recently viewed content work offline; private responses are not cached; updates/recovery do not strand users on broken assets.

**Rollout / requirement revision:** replace “Lighthouse PWA score ≥90” with explicit installability, offline, update, accessibility, and performance checks. Lighthouse removed its PWA category in v12. [Official Lighthouse release notes](https://github.com/GoogleChrome/lighthouse/releases/tag/v12.0.0). Full selected-content downloads and bookmark sync are completed in R24, not assumed from service-worker registration.

## R23 — Real-Time Notifications System

**Rewritten outcome:** add durable subscriptions, preferences, and an inbox before live transport.

**Assessment:** existing framework/email notifications do not provide a complete subscriptions/unread/history system. R05's bulk-result semantics can be reused; a new WebSocket service is not the first deliverable.

**Evidence:** [existing notifications](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/app/Notifications/), [bulk terminal job](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/app/Jobs/ProcessBulkOperation.php), [user-data controls](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/app/Http/Controllers/Site/ProfileController.php).

**Priority / owner / estimate:** broader engagement feature; backend + frontend + operations if live transport is adopted; 8–13 days, plus 3–5 days for optional WebSocket/push delivery. Depends on R28 and content publication events; optional push needs R22.

### Action items

- [ ] **R23.1** Define a bounded event catalog: newly published subscribed content, approved admin operation results, and chosen reminders. Specify recipient eligibility, deduplication key, event version, and visibility checks.
- [ ] **R23.2** Store durable per-user notifications/subscriptions with read state, pagination, expiration, and indexes. Dispatch after content/operation commit and recheck eligibility before delivery.
- [ ] **R23.3** Add an accessible bell/inbox, unread count, mark-one/all-read, empty/error states, and safe links. Reconcile counts across tabs/devices using server state.
- [ ] **R23.4** Add preferences per category/channel, unsubscribe, quiet hours/timezone, and browser-push opt-in; distinguish existing bulk-operation opt-in from a new global subscription.
- [ ] **R23.5** Begin with bounded polling when adequate. Evaluate live delivery need/latency before choosing WebSockets; keep authenticated channels and polling fallback if adopted.
- [ ] **R23.6** Add PWA push as an optional transport with explicit permission, subscription lifecycle, revoked/expired endpoint cleanup, and minimal lock-screen payloads.
- [ ] **R23.7** Test duplicate events/retries, deleted/unpublished targets, cross-user channel access, opt-out during fan-out, unread races, transport outage, and deleted accounts.
- [ ] **R23.8** Observe dispatch lag, retries/failures, duplicate rate, and queue load; integrate retention/export/deletion and document operating ownership.

**Acceptance:** one logical event produces the intended durable inbox item; unread state survives reconnects; preferences are honored at delivery; other users cannot read channels/items; transport outages do not lose the inbox event.

**Rollout / requirement revision:** inbox/preferences first, live transport second, push third. The original real-time requirement remains a separate completion slice: polling-only delivery is not labeled WebSocket implementation. Limit broad fan-out until queue capacity is measured.

## R24 — Offline Content Access

**Rewritten outcome:** deliver explicit content downloads, storage controls, and recoverable sync.

**Assessment:** no IndexedDB/download-manager implementation was found. Offline content needs edition/version identity, quota recovery, privacy, and a conflict model beyond the R22 static shell.

**Evidence:** [reader](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/resources/views/public/quran/surah.blade.php), [profile/favorites controller](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/app/Http/Controllers/Site/ProfileController.php), [frontend resources](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/resources/).

**Priority / owner / estimate:** substantial client feature; frontend + backend + QA; 10–16 days. Depends on R22 shell, R07 content/version contract, R18 relevant API contract, and R28 local-data policy.

### Action items

- [ ] **R24.1** Define downloadable packages: selected surahs/juz and editions, bookmarked Hadith, and supported favorite content from other modules. Specify IDs, schema version, checksum, publication/version metadata, estimated bytes, and excluded asset types.
- [ ] **R24.2** Create an IndexedDB schema and package manifest with migrations, atomic completion markers, progress/cancel/resume, and retry. Keep incomplete or corrupt packages out of “available offline.”
- [ ] **R24.3** Add a download manager showing actual/estimated size, available-space estimate, selected editions, download state, offline badge, and manual remove/clear controls.
- [ ] **R24.4** Define storage policy for pinned versus recent content, quota failure, and browser eviction. Request persistence only with a clear user benefit; do not promise browser storage is permanent.
- [ ] **R24.5** Scope personal favorites/progress by account and clear/invalidate them on logout/delete/account switch. Separate public downloaded text from account activity; explain what stays locally.
- [ ] **R24.6** Define mutation sync with operation IDs, server versions, tombstones, and deterministic favorite add/remove conflict rules. Queue offline actions and reconcile when the app reconnects; background sync is an enhancement, not the only path.
- [ ] **R24.7** Refresh content versions and revoked publication on reconnect. Explain that a previously downloaded public copy cannot be instantly recalled while a device remains offline.
- [ ] **R24.8** Test airplane-mode cold launch, partial downloads, quota exhaustion, complete browser eviction, schema upgrade, clock skew, two-device conflicting changes, expired login, deletion, and recovery.

**Acceptance:** only complete verified packages appear offline-ready; the chosen content remains readable in an offline cold start on supported browsers; sync retries do not duplicate mutations; account data does not leak; storage loss and conflicts are recoverable.

**Rollout / requirement revision:** start with selected public Quran text/translations, then Hadith/favorites, then mutation sync. Large audio downloads need a separately measured scope/rights decision. Browser quotas and eviction vary and storage writes can fail, so the plan includes explicit recovery. [MDN storage quotas and eviction](https://developer.mozilla.org/en-US/docs/Web/API/Storage_API/Storage_quotas_and_eviction_criteria).

## R25 — Additional Language Support (Indonesian, Malay, Bengali)

**Rewritten outcome:** launch each language only with reviewed UI and meaningful content coverage.

**Assessment:** the repository centers on the current five languages; runtime language records may affect the switcher. Adding `id`, `ms`, and `bn` rows alone is not a usable localized product.

**Evidence:** [language resources](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/resources/lang/), [translation patterns](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/docs/TRANSLATION_PATTERNS.md), [Quran importers](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/app/Console/Commands/Quran/), [Hadith admin](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/app/Http/Controllers/Admin/Hadith/).

**Priority / owner / estimate:** content-led expansion; localization lead + editors + frontend/backend; 5–9 engineering days, with translation effort estimated from the actual word/key inventory. Requires R01/R07 locale/content contracts; R26 is optional sourcing, not a blocker.

### Action items

- [ ] **R25.1** Inventory runtime locale configuration, translation keys, translated content tables, hard-coded strings, email/API messages, dates/numbers, fonts, and search behavior.
- [ ] **R25.2** Set a launch coverage matrix per language and module: complete navigation/account/security/admin UI, named initial content editions, source reviewers, and explicit fallback policy.
- [ ] **R25.3** Source and review Indonesian, Malay, and Bengali UI/content translations; keep source/translator attribution and approved edition IDs. Use placeholders only in internal development, never as claimed coverage.
- [ ] **R25.4** Implement locale configuration, switcher persistence, routes/alternate metadata, fonts, pluralization/formatting, and LTR UI with Arabic excerpts maintaining their own direction.
- [ ] **R25.5** Extend imports/search/indexes and test mixed Arabic/local-language content, missing translations, long labels, and language-specific normalization where relevant.
- [ ] **R25.6** Verify all admin forms, account/export/delete controls, emails, reader flows, offline manifests, and validation messages through the coverage matrix.
- [ ] **R25.7** Have native-language reviewers sign off each locale; launch languages independently when their matrix is satisfied and keep incomplete ones out of the public switcher.
- [ ] **R25.8** Establish maintenance/review ownership and connect to R26 if volunteer translation is chosen.

**Acceptance:** all three locales eventually meet their approved coverage matrix; no untranslated key names appear in required UI; fallback language is labeled accurately; approved content and native review exist for each launch.

**Rollout / requirement revision:** preserve existing locales and launch one new locale at a time. Retain the three-language and admin-translation scope; distinguish UI readiness from complete translation of the entire historical corpus.

## R26 — Community Contribution and Translation System

**Rewritten outcome:** add reviewed, attributable content suggestions with revision history.

**Assessment:** a new editorial workflow. The critical design is immutable suggestions against a known content version, reviewer permissions, and traceable publication. Direct publishing by reputation should not be the first implementation.

**Evidence:** [content translation conventions](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/docs/TRANSLATION_PATTERNS.md), [Hadith admin](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/app/Http/Controllers/Admin/Hadith/), [domain modules](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/app/Domain/).

**Priority / owner / estimate:** substantial moderated feature; backend + frontend + editorial lead + QA; 12–20 days excluding ongoing moderation staffing. Depends on R01/R07 stable IDs/locale readiness and R28 attribution/retention decisions. R23 can later notify review outcomes.

### Action items

- [ ] **R26.1** Define eligible contribution types, source/licensing terms, attribution choices, locales, review roles, and publication authority. Begin with bounded corrections/translations rather than every content field.
- [ ] **R26.2** Store immutable submissions with target/version, original/proposed text, source rationale, locale, contributor, timestamps, and state history; keep canonical text unchanged while pending.
- [ ] **R26.3** Add authenticated submission UX with preview, validation, duplicate/spam controls, rate limits, and an explicit status page. Avoid exposing private reviewer notes.
- [ ] **R26.4** Build the reviewer queue with assignment, diffs, approve/edit/reject/request-changes, conflict detection when source content changed, and logged rationale.
- [ ] **R26.5** Publish approved revisions atomically through existing domain services; apply cache/search/offline version invalidation and retain a reversible revision record.
- [ ] **R26.6** Add attribution consistent with contributor choices and R28 deletion/retention policy; distinguish deleting an account from retaining an approved public editorial revision.
- [ ] **R26.7** Implement a modest translation memory from approved segments with provenance and human selection; prevent automatic reuse from misrepresenting context or overwriting reviewed text.
- [ ] **R26.8** Add optional contributor recognition/leaderboard with consent and abuse review. Define any later trusted-contributor privilege separately, with scope, audit, revocation, and evidence of reliable moderation.
- [ ] **R26.9** Test authorization, concurrent reviewers, stale target versions, malicious markup, duplicate submissions, rollback, withdrawn attribution, deleted accounts, and notification preferences.
- [ ] **R26.10** Pilot with a limited contributor/editor group, measure review backlog/turnaround/reversal rates, and expand only with enough editorial capacity.

**Acceptance:** public content cannot change without authorized review; every published change maps to source submission/reviewer/version; conflicting edits are detected; rollback and attribution are coherent; translations are sourced and reviewable.

**Rollout / requirement revision:** retain original submissions, review, attribution, anti-spam, recognition, and translation-memory goals. Automatic trusted-user publishing is explicitly deferred for a later decision, not silently delivered through a broad admin permission. Disable new submissions if moderation capacity is exceeded while preserving pending work.

