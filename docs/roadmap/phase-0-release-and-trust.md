# Phase 0 — Release confidence and user data

[Plan index](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/docs/roadmap/README.md)

**Outcome:** a reviewable release with reliable gates, a tested recovery path, truthful notifications, and defined control over user data. Begin R20.1–R20.2 measurements during this phase. Content discovery can proceed while staging work is arranged.

## R03 — Fix Failing Tests and Test Gate

**Rewritten outcome:** maintain a reproducible test baseline and enforce it before deployment.

**Assessment:** Done in Auto-Claude. Tests already gate deployment; the earlier patch repaired a stale recitation test and added meaningful regression coverage. The fixed “901 tests / nine failures” premise is obsolete. The earlier isolated full run passed 1,251 tests; subsequent edits require verification on the final revision.

**Evidence:** [workflow](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/.github/workflows/deploy.yml), [recitation test](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/tests/Feature/Admin/RecitationAdminTest.php), [audit results](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/docs/ROADMAP-REVIEW-2026-09-13.md).

**Priority / owner / estimate:** essential release work; backend owner + QA; 1–2 days for final verification, excluding newly discovered defects. No implementation prerequisite; coordinate the shared checkout.

### Action items

- [x] **R03.1** Repair the old recitation expectation to exercise the paginated rows endpoint and establish an isolated MySQL test run.
- [ ] **R03.2** Record the final commit and local diff, isolate the database from concurrent tasks, and run the full required suite once changes stabilize.
- [ ] **R03.3** Classify any failure as a reproducible code defect, fixture/expectation mismatch, or environment collision; fix the cause without skipping coverage.
- [ ] **R03.4** Record skipped/incomplete tests with reasons and owners; capture test runtime and database/PHP versions.
- [ ] **R03.5** Verify CI fails on a deliberately failing test in a disposable branch/check fixture and that the deploy dependency cannot bypass it.

**Acceptance:** zero unexplained failures on the delivered revision; no new unexplained skips; tests run against the intended MySQL engine; deployment cannot start after a failed test job. Runtime is recorded rather than inferred from test count.

**Rollout / requirement revision:** preserve the existing gate. If a release fails tests, retain the current deployed version. Replace the numerical test-count requirement with reproducible success and explicit coverage changes.

## R05 — Bulk Operation Email Notifications

**Rewritten outcome:** validate opted-in completion mail through the real queue and transport.

**Assessment:** Done in Auto-Claude; implementation and local regressions exist. Notification preference is persisted; small and queued operations share terminal-state behavior. Mail uses Arabic/English snapshots and authenticated error details. Production/staging transport delivery has not been verified.

**Evidence:** [job](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/app/Jobs/ProcessBulkOperation.php), [notification](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/app/Notifications/BulkOperationFinished.php), [regression tests](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/tests/Feature/Admin/RoadmapBulkOperationTest.php).

**Priority / owner / estimate:** essential validation; backend + operations + QA; 1–2 days. Requires a controlled staging mail transport and worker; does not require Redis or the R23 inbox.

### Action items

- [x] **R05.1** Persist opt-in, repair enum/status defects, queue localized terminal mail, and keep raw errors behind the authenticated operation log.
- [ ] **R05.2** Use staging fixtures to exercise success, partial failure, terminal failure after retries, no opt-in, and dry-run paths; inspect captured messages before any controlled delivery.
- [ ] **R05.3** Verify the notification worker consumes the actual configured queue; exercise transport outage and recovery without changing a successfully completed operation to failed.
- [ ] **R05.4** Confirm AR fallback, AR RTL/EN LTR rendering, counts, branding, and an authenticated details link that denies another user's access.
- [ ] **R05.5** Check retried operation/notification delivery for duplicate terminal messages; add a persisted deduplication guard only if the exercised delivery path can repeat.
- [ ] **R05.6** Document mail/worker setup, observable delivery failures, and the difference between “queued” and “delivered.”

**Acceptance:** selected recipients receive the correct terminal outcome; opt-out/dry-run send none; transient operation retries do not send premature failure mail; transport failure is observable and does not corrupt operation status.

**Rollout / requirement revision:** original success/failure, translation, branding, and async requirements are retained. Error detail means a safe summary plus an authorized log link. Use controlled staging delivery and retain the opt-in switch if a transport problem appears.

## R06 — Code Quality Baseline (Pint Lint Fix)

**Rewritten outcome:** keep formatting clean and mandatory in CI.

**Assessment:** Done in Auto-Claude. The actual baseline involved 18 files rather than the roadmap's 138. Formatting cleanup and the mandatory deployment prerequisite were implemented earlier.

**Evidence:** [workflow style job](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/.github/workflows/deploy.yml), [verification record](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/docs/ROADMAP-REVIEW-2026-09-13.md).

**Priority / owner / estimate:** essential maintenance; engineering owner; 0.5 day. Depends only on the finalized working tree.

### Action items

- [x] **R06.1** Clear the existing formatter baseline with the repository's Pint rules.
- [x] **R06.2** Make the style job a required deployment dependency.
- [ ] **R06.3** Verify the final shared revision still passes `vendor/bin/pint --test`.
- [ ] **R06.4** Keep formatting-only changes distinguishable in review and document the local command; a local hook is optional.

**Acceptance:** repository-wide formatter success; CI rejects a formatter violation; no intentional behavior change is hidden in formatting work.

**Rollout / requirement revision:** retain a blocking style gate. The weaker “warning-only” wording in R03/R19 is superseded by this accepted quality baseline. No new formatter framework is needed.

## R19 — CI/CD Pipeline Hardening

**Rewritten outcome:** strengthen existing quality gates, readiness checks, and release recovery.

**Assessment:** partial. CI already gates on tests and deptrac, with Pint now mandatory. The server script already deploys release directories, switches a symlink, restarts workers, probes the app, and rolls back on failure. Remaining gaps are substantive: it accepts home-page 301/302 responses, curl lacks a bounded request timeout, old releases are pruned immediately, and SSH disables host verification. Static-analysis/audit gates were not found in the workflow. Recheck the earlier eight playlist boundary violations on the final revision.

**Evidence:** [workflow](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/.github/workflows/deploy.yml), [deployment script](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/deploy/deploy/deploy.sh), [framework health route](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/bootstrap/app.php), [API liveness route](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/routes/api.php).

**Priority / owner / estimate:** essential; backend + operations; 5–9 days including a staging drill. Requires R03/R06 baseline. Production runtime/configuration remains to be inspected by the deployment owner.

### Action items

- [ ] **R19.1** Re-run deptrac and resolve remaining playlist violations through existing Shared contracts; do not widen forbidden dependencies or hide new violations in a baseline.
- [ ] **R19.2** Introduce PHPStan/Larastan at a compatible, reviewed level toward level 5+, and dependency auditing with explicit high/critical finding handling. Track legacy static-analysis debt without accepting new violations; account for existing verified patches rather than blanket ignores.
- [ ] **R19.3** Upgrade the existing probe: separate liveness from readiness, require expected status/body, set connection/request timeouts, bounded retries, and confirm the intended release is being served. Keep dependency details private. Preserve `/up` or adapt it deliberately; adding a second unneeded health system is not the objective.
- [ ] **R19.4** Retain at least the previous known-good release through a proposed 24-hour observation window with disk limits; verify first-deploy behavior, failure before/after the symlink switch, relative/absolute symlink handling, and worker restart behavior.
- [ ] **R19.5** Rehearse failed deployment and recovery in staging with compatible schema changes. Target restoration within five minutes; document that code rollback does not undo migrations or external side effects.
- [ ] **R19.6** Pin the server host key from a trusted source and enable verification for SSH/SCP. Check provisioned PHP version against the application requirement; the script's fallback is 8.4 while CI selects 8.5.
- [ ] **R19.7** Add concise failure notifications containing release/check IDs and no secrets; track total CI runtime toward the original <10-minute target.
- [ ] **R19.8** Store a release checklist covering artifact identity, gates, migrations, readiness, worker state, observation, recovery, and retention.

**Acceptance:** a failed quality/security gate blocks deployment; a redirect-only or wrong-release response cannot pass readiness; an induced failure restores the retained compatible release; worker/schema assumptions are verified; no host verification bypass is needed.

**Rollout / requirement revision:** improve the existing script incrementally. Keep the old deploy path available until the new probe/retention behavior is rehearsed. The original “add rollback from scratch” premise is rejected by source evidence. Proposed 24-hour retention is a decision to review against available disk space.

## R28 — Privacy and Trust Dashboard

**Rewritten outcome:** add data export, verify deletion completeness, and explain actual data practices.

**Assessment:** partial. Account deletion already checks the password, removes Sanctum tokens and the user transactionally, deletes avatar files, and invalidates the current session. Profile export is explicitly disabled. The data model includes engagement, search, AI history and third-party integrations, so the generated “no tracking/no profiling” wording is not an accurate default.

**Evidence:** [profile controller](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/app/Http/Controllers/Site/ProfileController.php), [delete request](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/app/Http/Requests/Site/DeleteAccountRequest.php), [profile security tests](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/tests/Feature/Web/ProfileSecurityTest.php), [AI retention/configuration](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/config/services.php).

**Priority / owner / estimate:** essential user control; backend + product/content + QA; 6–10 days. Inventory first; coordinate exports with the existing queue. Later R11/R23/R24/R26 must extend this inventory when they add user data.

### Action items

- [ ] **R28.1** Create a data register: profile, tokens/sessions, favorites/playlists, interactions/progress, search history, AI inquiry/usage, uploads, notifications, logs, backups, and external processors. Record purpose, location, ownership key, retention, export, and deletion behavior.
- [ ] **R28.2** Extend existing deletion tests against that register. Verify foreign-key cascades, non-FK records, other sessions, delayed jobs that reference deleted users, avatar failures after DB commit, and recoverable cleanup without recreating the account.
- [ ] **R28.3** Build a versioned export containing the user's included data and a manifest explaining exclusions/retention. Use a queued job for large exports, private storage, expiration, and an authenticated download bound to the requesting user.
- [ ] **R28.4** Add export/deletion progress and understandable errors to the existing settings page; require appropriate recent authentication for sensitive actions and prevent one account retrieving another's export.
- [ ] **R28.5** Rewrite policy/security copy from the register: cookies, engagement measurements, third-party media/AI, retention, and deletion limitations. Publish no unverified certification, compliance guarantee, or blanket data-practice claim.
- [ ] **R28.6** Translate the controls into the currently supported locales and explain local/offline data separately. Add cookie/preferences controls where actual optional features require them; inventory actual behavior before choosing a consent design.
- [ ] **R28.7** Test export expiration, retry, revoked access, deletion during export, cleanup recovery, and backup-retention documentation. Give each retained category an explicit owner and reason.

**Acceptance:** a fixture account's exported data matches the register; another account is denied; deletion removes declared live data and invalidates access; retained records and backup windows are explained accurately; expired files are removed; current deletion UX remains functional.

**Rollout / requirement revision:** retain the existing deletion endpoint and add coverage before restructuring it. Introduce export behind a controlled rollout, then enable it after access/cleanup checks. Replace unsupported “no tracking” and certification promises with verifiable statements. This is an implementation plan, not a legal-compliance determination.

