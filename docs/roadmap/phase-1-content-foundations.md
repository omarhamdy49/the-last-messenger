# Phase 1 — Complete content workflows

[Plan index](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/docs/roadmap/README.md)

**Outcome:** approved content can move through import, editorial review, publication, browsing, reporting, and documented APIs. Preserve the existing domain services and visibility contracts.

Pull forward **R08.1–R08.3** from [Phase 3](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/docs/roadmap/phase-3-discovery.md): Hadith topic support is currently disabled across the type registry and service mapping. This repair does not need the full related-content feature.

## R01 — Hadith Admin Panel CRUD

**Rewritten outcome:** finish multilingual Hadith editing, narrator assignment, and bulk workflows.

**Assessment:** partial, with substantial CRUD already present. Collections, books, chapters, and Hadith records have controllers, routes, and views. Record lists filter and paginate server-side. The record request/schema exposes AR/EN fields and narrator text; this does not satisfy a five-language structured editor or complete ordered narrator assignment.

**Evidence:** [admin routes](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/routes/admin/hadith.php), [record controller](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/app/Http/Controllers/Admin/Hadith/HadithRecordController.php), [Hadith model](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/app/Domain/Hadith/Models/Hadith.php), [admin views](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/resources/views/admin/hadith/), [translation patterns](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/docs/TRANSLATION_PATTERNS.md).

**Priority / owner / estimate:** essential content workflow; backend + frontend + Hadith editor; 8–13 days, excluding translations and catalog cleanup. Requires an agreed canonical/localized field contract; publishing changes use the existing visibility rules.

### Action items

- [ ] **R01.1** Inventory CRUD acceptance against real forms and routes. Record what already works, permissions, filters, hierarchy navigation, and import state; keep usable pages.
- [ ] **R01.2** Design additive locale support consistent with repository translation conventions. Specify source Arabic, translated fields, locale uniqueness, source attribution, fallback, and readiness. Backfill AR/EN without losing current identifiers or requiring new translations to publish existing approved content.
- [ ] **R01.3** Add ordered narrator assignment using current People lookup contracts; preserve repeated narrator positions where the source contains them. Distinguish free-text attribution, structured chain, and grade source.
- [ ] **R01.4** Extend collection/book/chapter/record forms for the five current locales with validation and visible missing-translation status. Replace large unbounded selectors with searched/paginated choices only where catalog size requires it.
- [ ] **R01.5** Complete bulk publish/unpublish/regrade using existing operation handlers, preview, authorization, progress, failure reporting, and optional R05 notifications. Preserve raw/source grading alongside normalized labels.
- [ ] **R01.6** Expose import state and per-batch progress in the admin workflow; prevent incomplete imported collections becoming public through bulk or individual actions.
- [ ] **R01.7** Verify hierarchy integrity, duplicate references, invalid grade input, missing narrator targets, authorization, locale fallback, bulk partial failure, and rerun behavior.
- [ ] **R01.8** Have an editor exercise one collection → book → chapter → record → publish → correction journey in AR and one LTR locale; document the workflow.

**Acceptance:** all four levels remain editable; parent-child mismatches are rejected; canonical references survive edits; required locale fields/fallback are explicit; ordered narrators and grade provenance round-trip correctly; bulk changes obey permissions and visibility and report progress.

**Rollout / requirement revision:** introduce locale storage additively, backfill with a report, switch reads after parity checks, and delay dropping old columns. DataTables is not a completion requirement: retain the existing table if it supplies the required filtering, sorting, pagination, and usability. Five-language UI capacity is distinct from having approved translations of every record.

## R02 — Hadith Public Site Browser

**Rewritten outcome:** complete a consistent, localized, publication-safe Hadith browser.

**Assessment:** the core public browser exists, including collection/book navigation, record references, search, and public visibility scopes. The work is completeness, consistency, and validation.

**Evidence:** [public controller](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/app/Http/Controllers/Site/HadithController.php), [browse service](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/app/Domain/Hadith/Services/HadithBrowseService.php), [public views](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/resources/views/public/hadith/).

**Priority / owner / estimate:** essential reader workflow; backend + frontend + QA/editor; 4–7 days. Locale improvements depend on R01's contract; visibility/SEO/mobile verification can start immediately.

### Action items

- [ ] **R02.1** Trace collection → book → chapter → record paths and empty states against published, hidden, importing, and partially translated fixtures.
- [ ] **R02.2** Present source Arabic, selected translation or explicitly labeled fallback, grade with its source, narrator attribution, and hierarchy breadcrumbs without mixing editions or implying an absent translation exists.
- [ ] **R02.3** Connect structured narrator profiles when available through the Shared lookup contract; preserve a readable text chain without waiting for R10.
- [ ] **R02.4** Audit per-record canonical URLs, unique titles/descriptions, locale alternates, sharing references, and pagination SEO. Fix stale ID-based links against the actual reference route contract.
- [ ] **R02.5** Verify search queries for each current supported content language, collection filtering, Arabic normalization, empty results, and counts. Record relevance gaps for R09 rather than creating a second search engine.
- [ ] **R02.6** Exercise Arabic RTL and LTR layouts at 360 px, tablet, and desktop widths; check long Arabic text, keyboard navigation, focus, loading/error states, and no horizontal clipping.
- [ ] **R02.7** Add regressions for public visibility across detail/list/search/counts and for missing-locale behavior; have an editor validate sample rendered records and references.

**Acceptance:** the full browsing journey works with stable shareable URLs; hidden/importing records cannot be exposed through alternate entry points; reference/grade/translation information is accurate; supported small-screen and keyboard flows are usable.

**Rollout / requirement revision:** keep current routes and introduce redirects only for deliberate canonical changes. All original functional requirements are retained, with explicit content-availability limits. A tested core browser is not evidence that every collection's production translations are complete.

## R04 — Dashboard Real Data Integration

**Rewritten outcome:** complete module metrics with truthful failure states and bounded query cost.

**Assessment:** the active dashboard already uses real providers. Earlier fixes added Hadith/companion counts and repaired avatar selection. Remaining items include surah totals, content growth beyond users, genuine quick metrics, and latency evidence. A separate sample controller is not the active data source.

**Evidence:** [dashboard service](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/app/Domain/Shared/Services/DashboardService.php), [stats contract](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/app/Domain/Shared/Contracts/DashboardStatsProviderInterface.php), [Hadith provider](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/app/Domain/Hadith/Services/HadithDashboardStatsProvider.php), [People provider](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/app/Domain/People/Services/PeopleDashboardStatsProvider.php), [regression tests](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/tests/Feature/Admin/RoadmapDashboardTest.php).

**Priority / owner / estimate:** essential admin accuracy; backend + frontend; 3–5 days. Requires agreed metric definitions; R20 baseline informs performance checks.

### Action items

- [x] **R04.1** Restore missing Hadith/companion providers and real activity avatars.
- [ ] **R04.2** Define every card's count and scope: all admin records, published records, translations/editions, distinct surahs, companions versus all people. Display labels that match those definitions.
- [ ] **R04.3** Implement missing series/quick metrics through the provider contract, including seven-day content activity and content growth; distinguish creation from publication and choose a consistent timezone.
- [ ] **R04.4** Replace synthetic constants and misleading fallback zeros on active paths. Return a distinguishable “unavailable” state for failed providers and report the exception with module context.
- [ ] **R04.5** Apply bounded caching and mutation invalidation to expensive aggregates; verify refresh after import/publication. Inspect the unused sample controller's references before any cleanup.
- [ ] **R04.6** Test empty data, mixed publication state, provider failure, timezone boundaries, cache refresh, and no N+1 activity avatars. Measure the agreed dashboard load scenario.

**Acceptance:** displayed figures match fixture queries and labels; a failed provider is not shown as a real zero; activity windows are correct; cached page/server response meets the explicitly measured budget. Original <1-second cached dashboard target remains a target with the measurement boundary recorded.

**Rollout / requirement revision:** extend the active provider system. Do not replace working dashboard architecture because an unused controller contains sample data.

## R07 — Complete Quran Content Import Services

**Rewritten outcome:** prove approved edition coverage, safe reruns, and visible import progress.

**Assessment:** translation, tafsir, reciter, recitation, and aggregate CLI importers exist. Legacy generic JSON methods now fail explicitly instead of returning empty success. Code presence does not establish the requested 10 translations, 3 tafsirs, and 5 reciters' actual coverage.

**Evidence:** [translation importer](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/app/Console/Commands/Quran/ImportTranslationCommand.php), [tafsir importer](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/app/Console/Commands/Quran/ImportTafsirCommand.php), [reciter importer](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/app/Console/Commands/Quran/ImportQuranRecitersCommand.php), [aggregate importer](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/app/Console/Commands/Quran/ImportQuranAllCommand.php), [service](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/app/Domain/Quran/Services/QuranService.php).

**Priority / owner / estimate:** essential content integrity; backend + Quran editor + operations; 6–10 days, excluding source acquisition/full imports. Needs approved source fixtures and a documented canonical verse/edition identity.

### Action items

- [ ] **R07.1** Produce a source manifest: edition ID, language, author/reciter, attribution/rights, format/version/checksum, expected coverage, current coverage, and gaps. Map the proposed Sahih International, Pickthall, Yusuf Ali, Turkish/French/Urdu and remaining translations explicitly.
- [ ] **R07.2** Trace existing commands and supported inputs; document one supported operator path for each content type. Keep generic JSON unsupported unless a concrete user workflow requires a defined schema.
- [ ] **R07.3** Test a bounded import twice and after interruption. Verify stable unique keys, preserved curated edits, resumability, invalid-row quarantine, and no duplicate editions/verses/audio.
- [ ] **R07.4** Add preflight validation for source identity, verse references, expected counts, missing/duplicate rows, and text integrity. Separate staging/imported state from public readiness.
- [ ] **R07.5** Connect import jobs/progress to existing admin operations: current phase, processed/total where knowable, errors, retry/resume, terminal result, and optional notification. Never show 100% before validation/publication finishes.
- [ ] **R07.6** Fill the approved inventory toward 10 translations, 3 tafsirs, and 5 reciters; report per-edition completeness and audio URL/timing availability rather than counting empty catalog rows.
- [ ] **R07.7** Validate representative text with an editor, check full canonical coverage programmatically, and reconcile browser/search/cache behavior after publication.
- [ ] **R07.8** Document recovery: isolate a failed edition/version, resume safe batches, restore prior content mapping, and retain an import manifest/audit report.

**Acceptance:** every counted edition meets its declared corpus coverage and attribution; reruns do not duplicate or silently overwrite protected corrections; interrupted imports recover; progress is truthful; invalid/incomplete content stays unpublished.

**Rollout / requirement revision:** preserve CLI pipelines. Import a small source fixture, then one complete edition, then the remaining approved catalog. The original numerical targets remain editorial deliverables; this plan does not manufacture missing translations or authorize arbitrary source ingestion.

## R18 — API Documentation (OpenAPI/Swagger)

**Rewritten outcome:** publish an accurate, tested contract for enabled API routes.

**Assessment:** no comprehensive OpenAPI document/UI was found. The API has a secure-by-default Sanctum group and a small explicit public allowlist; documentation must preserve that distinction.

**Evidence:** [API routes](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/routes/api.php), [API resources](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/app/Http/Resources/), [API tests](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/tests/Feature/Api/).

**Priority / owner / estimate:** essential integration contract; backend + QA; 4–7 days. Inventory can begin immediately; final schemas follow R01/R02/R07 changes where API behavior is affected.

### Action items

- [ ] **R18.1** Inventory enabled versioned routes by method, path, authorization, token abilities, throttling, locale, pagination, and response resource. Separate browser/admin endpoints and disabled future routes.
- [ ] **R18.2** Choose a supported OpenAPI 3.x authoring/validation approach after a small route/resource pilot; document why annotations, generated extraction, or a maintained spec best match the code.
- [ ] **R18.3** Describe request constraints, success and error envelopes, 401/403/404/422/429 responses, filters, sorting, IDs, and canonical references. Preserve the difference between public liveness and authenticated content APIs.
- [ ] **R18.4** Create sanitized examples for each current supported locale, including fallback/missing translation, pagination, and visibility exclusions. Never embed live tokens or users' content.
- [ ] **R18.5** Add spec validation and representative request/response contract checks in CI; compare route inventory against documented operations to catch drift.
- [ ] **R18.6** Publish a versioned spec and documentation UI. Configure “Try it” for an explicit environment with user-supplied credentials; label destructive operations and keep server-side authorization authoritative.
- [ ] **R18.7** Add version/change notes and an owner/update rule for every API behavior change.

**Acceptance:** all enabled v1 operations are accounted for; examples validate against actual responses; auth and throttle behavior are accurate; the UI/spec can be opened and tested without exposing secrets or advertising nonexistent endpoints.

**Rollout / requirement revision:** retain OpenAPI/Swagger's usable-contract goal; choose generator mechanics from a pilot instead of assuming annotations guarantee accuracy. Deploy the spec read-only before enabling interactive requests.

