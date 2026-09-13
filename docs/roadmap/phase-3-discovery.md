# Phase 3 — Connected knowledge and discovery

[Plan index](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/docs/roadmap/README.md)

**Outcome:** readers find relevant material and explore sourced relationships without hiding uncertainty or creating unsupported religious claims. The minimal Topics repair is pulled into Phase 1; the rest follows stable content contracts.

## R08 — Cross-Module Content Linking (Topics Enhancement)

**Rewritten outcome:** restore Hadith topics, then deliver curated related content across modules.

**Assessment:** a concrete integration gap plus a broader enhancement. `TopicService` returns an empty Hadith list; `TopicItemType` and the provider only register verse/article types; the unused Hadith mapper assumes obsolete translations, grade, hierarchy, and route fields. Merely removing the empty array will not repair the feature.

**Evidence:** [topic service](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/app/Domain/Topics/Services/TopicService.php), [type enum](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/app/Domain/Topics/Enums/TopicItemType.php), [repository](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/app/Domain/Topics/Repositories/TopicRepository.php), [provider](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/app/Providers/TopicsServiceProvider.php), [visibility registry](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/app/Domain/Shared/Services/PublicVisibilityRegistry.php).

**Priority / owner / estimate:** repair is essential; expansion is discovery work; backend + frontend + editor; 2–4 days repair, plus 5–8 days expansion, excluding curation. Repair depends on the current Hadith contract; broader work uses R01/R02 and existing Shared lookups.

### Action items

- [ ] **R08.1 — Phase 1 repair:** inventory persisted topic morph aliases, orphan links, and the current Hadith route/model contract. Define a migration/report for legacy aliases instead of dropping unknown rows silently.
- [ ] **R08.2 — Phase 1 repair:** restore Hadith type registration at the composition layer; implement current record mapping and counts through a domain-safe lookup/provider, with correct canonical URLs, grade fields, and localized text.
- [ ] **R08.3 — Phase 1 repair:** test display, counts, edit/save, hidden/importing collections, deleted records, missing locale, and orphan references. Confirm no forbidden cross-domain imports; remove obsolete dead mapping only after the replacement is covered.
- [ ] **R08.4** Define a related-content reference contract: type, canonical ID/URL, localized title/excerpt, visibility, relationship explanation/source, and reviewer/version. Add Seerah and People support through their providers.
- [ ] **R08.5** Extend admin topic assignment with searched selectors, ordering, duplicate handling, preview, and review/audit information; preserve existing verse/article behavior.
- [ ] **R08.6** Add bounded, paginated/lazy related-content surfaces to Quran, Hadith, Seerah, and companion pages. Keep public visibility identical in lists, counts, caching, and detail links.
- [ ] **R08.7** Curate at least 50 distinct reviewed relationships with source rationale and coverage across the requested modules; a reciprocal display of one edge is not two curated relationships.
- [ ] **R08.8** Test publication/unpublication invalidation, missing providers, empty results, bounded query counts, and locale-aware fallback; document the editorial workflow.

**Acceptance:** Hadith topic content can be assigned and appears when public; existing verse/article links remain intact; all requested modules participate; at least 50 sourced relationships are reviewed; hidden/deleted content disappears from surfaces and counts.

**Rollout / requirement revision:** ship the repair independently, then add types/surfaces incrementally. Keep legacy IDs with an explicit reconciliation report. Asynchronous loading remains a UX option where it helps; correctness and bounded queries matter more than forcing every page to fetch twice.

## R09 — Elasticsearch-Powered Unified Search

**Rewritten outcome:** improve search relevance and latency; select the engine from evidence.

**Assessment:** unified search already uses database full-text/LIKE queries, Arabic normalization, filters/facets, and history. Missing relevance behavior must be measured. Elasticsearch can provide language analysis, but deploying it alone does not deliver cross-language intent, curated synonyms, or correct visibility.

**Evidence:** [search service](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/app/Domain/Shared/Services/SearchService.php), [query builder](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/app/Domain/Shared/Search/SearchQueryBuilder.php), [normalizer](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/app/Domain/Shared/Support/ArabicNormalizer.php), [full-text tests](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/tests/Feature/Search/FullTextSearchTest.php).

**Priority / owner / estimate:** discovery improvement; search/backend owner + Arabic/content reviewers + operations if adopted; 5–8 days evaluation/improvement, plus 8–15 days if engine migration is justified. Depends on R20.1–R20.2 and current public content schemas; full R08 expansion is not required.

### Action items

- [ ] **R09.1** Build a proposed 100-query judged set covering exact references, Arabic with/without diacritics, morphology, names, transliteration, current languages, cross-language intent, misspellings, and no-result cases. Review expected results and relevance grades.
- [ ] **R09.2** Record current top-result accuracy, recall@10/nDCG@10 where labels support them, no-result rate, endpoint p95, and query cost on a named corpus and load mix. Separate each language/module.
- [ ] **R09.3** Fix demonstrated query/visibility/count defects and tune current indexes/ranking first. Add curated synonyms and autocomplete with explicit query length/rate limits and privacy-aware analytics.
- [ ] **R09.4** Run a bounded Elasticsearch comparison only if important cases remain unmet. Test Arabic normalization/stemming, proper names, exact canonical references, language fields, and curated synonym behavior against the same judged set.
- [ ] **R09.5** Record the engine decision using measured relevance/latency, resource cost, freshness, availability, and maintenance effort. If the current engine meets the agreed requirements, close the evaluation with an explicit proposal to revise the original Elasticsearch-specific scope.
- [ ] **R09.6 — if adopted** Implement index schemas/versioning, initial backfill, after-commit update/delete processing, idempotent reindex, lag monitoring, and an alias switch. Recheck public visibility before returning stale indexed results, especially after unpublication.
- [ ] **R09.7** Explain cross-language matching behavior explicitly: aligned translations/approved aliases first; evaluate additional translation/semantic retrieval separately. An Arabic analyzer is not a cross-language translator.
- [ ] **R09.8** Validate the agreed <200 ms p95 target, relevance thresholds set after baseline, freshness, engine outage, zero results, and stale publication state; retain the current search path for rollback.

**Acceptance:** a reproducible report demonstrates the selected implementation meets agreed user-query cases and latency targets without visibility leaks. Proposed exact-reference target: the correct public record is rank 1 in all exact-reference fixtures. Other thresholds must be agreed from the labeled baseline before tuning.

**Rollout / requirement revision:** migration is conditional, not silently canceled. A decision to keep MySQL changes the original implementation requirement and must be recorded for review; the original card is not Done just because a benchmark exists. Built-in Arabic analysis supports normalization/stemming, which informs the comparison rather than proving relevance. [Elastic language analyzer documentation](https://www.elastic.co/docs/reference/text-analysis/analysis-lang-analyzer).

## R10 — Visual Hadith Narrator Chain Explorer

**Rewritten outcome:** render sourced narrator chains with accessible text and graph views.

**Assessment:** ordered narrator relations and a Shared People lookup exist; the interactive explorer is new. The chain schema is keyed by position and can represent repeated people. Reliability and chronological data must come from sources rather than inferred labels.

**Evidence:** [Hadith model](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/app/Domain/Hadith/Models/Hadith.php), [Person lookup](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/app/Domain/Shared/Contracts/PersonLookup.php), [Person reference](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/app/Domain/Shared/Contracts/PersonReference.php).

**Priority / owner / estimate:** discovery enhancement; backend + visualization/frontend + Hadith editor; 6–10 days, excluding missing metadata. Requires R01 narrator editing and reviewed chain records; R08 aids related navigation but is not a hard prerequisite.

### Action items

- [ ] **R10.1** Inventory chain sources, order conventions, missing narrators, alternative chains, repeated positions, and availability/provenance of birth/death/place/reliability data.
- [ ] **R10.2** Define a response contract preserving source order and per-position identity; link multiple positions to the same person without merging the chain nodes.
- [ ] **R10.3** Build a server-rendered ordered text view first, with names, known metadata, source attribution, and clear unknown/uncertain values.
- [ ] **R10.4** Add a progressive graph with keyboard-accessible node actions, pan/zoom/reset, responsive layout, focus management, and reduced motion; link to existing person profiles.
- [ ] **R10.5** Distinguish narrator assessments from a Hadith's grade and distinguish multiple source assessments. Describe recorded completeness/missing segments without an invented completeness percentage.
- [ ] **R10.6** Test empty/long/branched/repeated-person chains, missing profiles, uncertain dates, private/unpublished records, RTL/LTR labels, touch controls, and JavaScript-disabled fallback.
- [ ] **R10.7** Have an editor verify sample chain order and displayed reliability statements before enabling the graph for the full supported catalog.

**Acceptance:** graph and text represent the same ordered source data; repeated narrators remain correctly positioned; profile links work; unknown metadata stays unknown; the explorer is usable without color or JavaScript.

**Rollout / requirement revision:** start with verified chains and display coverage honestly. Retain the text view if graph initialization fails. The original completeness requirement becomes an evidence-based description, not a computed theological assertion.

## R12 — AI-Powered Inquiry Expansion

**Rewritten outcome:** add grounded Hadith retrieval and follow-ups within tested cost limits.

**Assessment:** Quran inquiries, source-oriented responses, history, quota/usage records, and provider price configuration already exist. Defaults remain 10 monthly inquiries and 200 input characters. Extend existing services and reporting rather than assuming the whole inquiry stack or cost accounting is absent.

**Evidence:** [inquiry service](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/app/Domain/Quran/Services/QuranAiInquiryService.php), [retrieval service](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/app/Domain/Quran/Services/QuranInquirySearchService.php), [admin service](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/app/Domain/Quran/Services/AiInquiryAdminService.php), [config](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/config/services.php), [quota tests](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/tests/Feature/Quran/AiInquiryQuotaTest.php).

**Priority / owner / estimate:** evaluated enhancement; backend/AI owner + Hadith/Quran reviewers + QA; 10–16 days plus review time. Depends on R01/R02 content contracts and R28 data/retention policy. R09 evaluation supplies relevance cases; Elasticsearch is not required.

### Action items

- [ ] **R12.1** Map current retrieval, provider fallbacks, quota reservation, token/cost recording, history retention, and admin reporting. Identify actual gaps before adding new stores.
- [ ] **R12.2** Add Hadith retrieval through a Shared contract that returns canonical references, text/translation, source grade, collection visibility, and provenance; bound retrieval size and use current approved content.
- [ ] **R12.3** Add user-owned conversation/follow-up context with maximum turns/tokens, explicit reset, and retention/export/delete integration. Treat retrieved documents as content rather than instructions.
- [ ] **R12.4** Validate citation IDs against retrieved records; prevent invented links or a claim of source support when the cited passage does not substantiate the answer. Explain insufficient evidence and disputed/unknown grading.
- [ ] **R12.5** Build a reviewer-scored evaluation set covering Quran/Hadith questions, follow-ups, ambiguous prompts, missing evidence, misleading source text, citation mismatches, and unsupported languages.
- [ ] **R12.6** Extend existing cost/usage reporting for both retrieval types and conversations. Define per-user quota, token caps, provider timeout/retry, monthly budget alerts, and accounting behavior for failed/retried requests.
- [ ] **R12.7** Evaluate the proposed 30 monthly inquiries and 500 input characters against measured quality/cost/abuse scenarios; make the change configurable and enable it only after the gate is met.
- [ ] **R12.8** Verify cross-user isolation, concurrent quota requests, provider outage/fallback, unpublication, export/deletion, and safe error feedback; pilot with reviewed users before wider exposure.

**Acceptance:** all citations in the evaluation resolve to authorized retrieved sources; reviewers sign off the agreed grounding rubric; limits remain enforced under concurrency; cost accounting includes retries/fallbacks; follow-up history is isolated and deletable. Store the rubric, scores, and failure examples with the release.

**Rollout / requirement revision:** keep current Quran inquiry and limits as the fallback. Hadith retrieval and quota expansion use separate switches. The 30/500 requirement is retained as a proposed gated product change, not a default configuration tweak. No finite evaluation proves every future answer correct.

## R27 — Interactive Seerah Map Experience

**Rewritten outcome:** present verified event geography and uncertain dates accurately.

**Assessment:** locations, event timelines, and map links exist; the geographic event map with chronological navigation is an addition. Content accuracy and the map/date contract are prerequisites to an attractive visualization.

**Evidence:** [location design](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/docs/LOCATIONS-MODULE-DESIGN-2026-08-18.md), [Seerah domain](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/app/Domain/Seerah/), [Seerah gap report](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/docs/SEERAH-M01-GAP-REPORT.md).

**Priority / owner / estimate:** discovery enhancement; frontend/map owner + backend + Seerah editor; 7–12 days excluding curation. Depends on reviewed locations/event dates; use R08 for related-content links where available.

### Action items

- [ ] **R27.1** Inventory event-to-location relationships and date representations. Record precise/approximate/unknown coordinates, date ranges/calendar conventions, source evidence, and missing geography.
- [ ] **R27.2** Curate the required 50 events with verified locations or explicitly approximate regions; omit unsupported exact pins rather than guessing.
- [ ] **R27.3** Define a lightweight public map API with bounds, filters, clustering inputs, localized labels, provenance, and publication-safe related references.
- [ ] **R27.4** Select a map renderer/tile source using attribution, permitted usage, accessibility, payload, privacy, and operating cost. Document the choice before enabling third-party requests.
- [ ] **R27.5** Add chronological navigation that handles Hijri/date ranges and uncertain order correctly; label journeys as documented routes or schematic connections according to evidence.
- [ ] **R27.6** Build marker details, related links, filters, clustering, zoom/touch controls, and an equivalent sortable event list; support RTL/LTR labels and reduced motion.
- [ ] **R27.7** Test overlapping/absent coordinates, large marker sets, narrow screens, tile outage, missing translations, hidden events, and range filtering. Obtain editorial approval of displayed geography and dates.

**Acceptance:** at least 50 reviewed events are available under the agreed precision policy; filters/timeline/list agree; no hidden content leaks; source uncertainty remains visible; list navigation works when maps fail.

**Rollout / requirement revision:** retain existing event/timeline pages and add the map progressively. An event with uncertain geography may use a region or remain in the list; “50 verified” does not justify fabricating precision.

