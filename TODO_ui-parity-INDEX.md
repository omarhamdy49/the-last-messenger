# UI Prototype Parity Review — Index & Synthesis

**Date:** 2026-07-26
**Scope:** all 30 prototypes in `../the-last-messenger-ui/` compared against this Laravel app —
pages, components, features, schema, and quality per module.
**Method:** five parallel static analysis passes (OpenAI Codex CLI, `codex-cli 0.144.1`), each reading the
prototype HTML in full and tracing route → controller → service/handler → repository → model → migration →
Blade view → components. Findings were then spot-verified against the code by the orchestrating agent.
**Runtime verification:** the five cluster passes were static (MySQL was down while they ran). The P0s were
subsequently re-checked against the live MySQL database and through real in-process HTTP requests, and the PERF
findings were measured with `EXPLAIN` and timings — see [Runtime verification](#runtime-verification-2026-07-26)
and [PERF verification](#perf-verification-with-explain-2026-07-26) below.

## Remediation status (2026-07-26)

A six-task queue was run through the Codex CLI with per-task review, gates, and mutation testing of every new
test. Suite went 780 → **802 passing** over the run.

| Finding | Status | Commit |
|---|---|---|
| **INFRA-001** — suite on MySQL, not sqlite | ✅ landed | `dd373c8` |
| **HAD-001** — `/api/v1/hadith/search` 500 | ✅ landed | `15d2373` |
| **HAD-002** — `/api/v1/hadith/by-theme` 500 | ✅ landed | `15d2373` |
| **CORE-016** — social signups roleless | ✅ landed | `6eee23f` |
| **SSG-001** — draft survives in public cache | ✅ landed | `21fdd8d` |
| **CORE-001** — draft articles in unified search | ✅ done, **uncommitted** | blocked, see below |
| **CORE-002** — draft articles via topic hubs | ✅ done, **uncommitted** | blocked |
| **MED-002** — unpublished episodes via API | ✅ done, **uncommitted** | blocked |
| **SSG-002** — unready sahaba publicly listed | ✅ done, **uncommitted** | blocked |
| **HAD-003** — merged duplicate hadiths | ✅ done, **uncommitted** | blocked |
| **PERF-001** — `LENGTH(hadith_number)` sort | ⏸ deferred | blocked |

**Why five are done but uncommitted, and PERF-001 deferred.** They live in files that the in-flight
genealogy→sahaba consolidation also modifies (`SahabiRepository` 14 hunks, `HadithRepository` 20 hunks, same
methods), and that work depends on an uncommitted migration which drops `people`/`person_translations`
one-way. Splitting hunk-by-hunk was not safely possible, and committing the consolidation was not the
orchestrator's call. **Unblock by committing the consolidation; the gating work is intact in the working tree
and PERF-001 can then run.** PERF-001 touches 5 of its 8 call sites in `HadithRepository`, so running it before
that commit would deepen the same entanglement.

Also fixed in passing, beyond the filed findings: two further registration paths had duplicated role logic
(`Api/AuthController` was a third copy); `HadithResource`/`HadithDTO` read the same nonexistent columns as the
two broken endpoints, so hadith detail carried the same latent 1054s; and `by-theme` needed database-level
pagination — unbounded it measured 25.65MB of JSON at 163MB peak for the largest theme (4,604 hadiths), which
dies at PHP's default 128M limit.

Still open and worth noting: `deptrac.baseline.yaml` now has **three** stale unmatched entries (violations
remain 0). Two came from the consolidation, one because routing Eloquent out of `AuthController` genuinely
removed that violation. Regenerate the baseline once the consolidation lands. The two red
`ArticlePublicPagesTest` view-count assertions (TEST-001) remain untouched by design.

## Cluster reports

| Report | Prototypes | Findings | P0 |
|---|---|---|---|
| [TODO_ui-parity-core.md](TODO_ui-parity-core.md) — Home, Auth/Profile, Articles, Topics, Search | 8 | 35 | 2 |
| [TODO_ui-parity-hadith.md](TODO_ui-parity-hadith.md) | 6 | 28 | 2 |
| [TODO_ui-parity-quran.md](TODO_ui-parity-quran.md) | 5 | 28 | 1 |
| [TODO_ui-parity-seerah-sahaba-genealogy.md](TODO_ui-parity-seerah-sahaba-genealogy.md) | 6 | 32 | 2 |
| [TODO_ui-parity-media.md](TODO_ui-parity-media.md) | 4 | 28 | 2 |
| **Total** | **29 + carousel** | **151** | **9** |

By severity: P0 9 · P1 70 · P2 56 · P3 16.
By type: MISSING 29 · PARTIAL 26 · ENHANCEMENT 24 · SCHEMA 21 · SECURITY 19 · PERF 18 · I18N 6 · A11Y 6 · RTL 2.

Headline: **every prototype page has a real implementation.** There are no missing pages. The gaps are
publication-gating holes, schema/code contract drift, and prototype interactions that shipped as visual
shells. The RTL/i18n/dark-mode work that dominated earlier rounds is largely done — only 8 findings total.

## Verification status of the P0s

Spot-checked against code by the orchestrator. Two were re-filed.

| ID | Claim | Verdict |
|---|---|---|
| HAD-001 | `/api/v1/hadith/search` queries a nonexistent `translation` column | **CONFIRMED** |
| HAD-002 | `/api/v1/hadith/by-theme/{theme}` selects nonexistent `slug_ar`/`name_ar` columns | **CONFIRMED** |
| CORE-001 | Unified search returns unpublished content | **CONFIRMED** |
| CORE-016 | Social signups get no `user` role | **CONFIRMED** |
| MED-002 | Public episodes API exposes unpublished episodes + `video_url` | **CONFIRMED** |
| SSG-002 | Public Sahaba/Genealogy lists ignore `is_ready` | **CONFIRMED** (sahaba list path) |
| SSG-001 | Draft Seerah events survive in public caches | **CONFIRMED, but bounded** — see below |
| MED-001 | Stored XSS via program description | **RE-FILED → P2, project-wide** — see below |
| QUR-001 | Surah header/verse mismatch from `revelation_order` | **RE-FILED → P1, reframed** — see below |

## Runtime verification (2026-07-26)

Run against the live MySQL database (`the_last_messenger`, port 3308) after the static passes completed. Every
P0 was either proven, downgraded, or reframed on evidence.

### Proven in a real request

An in-process sweep through the HTTP kernel (`Kernel::handle(Request::create(...))`) against MySQL:

| Endpoint | Status |
|---|---|
| `/api/v1/hadith/search?q=test` | **500** |
| `/api/v1/hadith/by-theme/x` | **500** |
| `/api/v1/articles`, `/api/v1/hadith`, `/api/v1/hadith/sahih`, `/api/v1/quran/surahs`, `/api/v1/sahaba`, `/api/v1/media/programs` | 200 |

HAD-001 and HAD-002 are not theoretical: both return **500** today. Direct calls confirm the cause —
`SQLSTATE[42S22] 1054 Unknown column 'translation'` and `1054 Unknown column 'name_ar'`. Live schema:
`hadith_translations` = `id, hadith_id, language_code, text, translator_name, source_url, urn, verified,
is_primary, notes, created_at, updated_at` (no `translation`); `hadith_themes` = `id, theme_en, theme_ar,
review_status, source_reference, name, description` (no `slug_ar`, `slug_en`, `name_ar`, `name_en` — note it
*does* have plain `name`/`description`, which is probably the intended target).

**CORE-001 is a live leak.** Non-published article IDs are `1, 2, 3, 30`. Unified search via
`SearchService::searchWithin('article', 'مقال', [], ['per_page' => 20])` returns **id 2** — a `status=draft`
article. Searching `'ا'` returns both **2 and 3**. Drafts are publicly searchable right now.

### The root cause behind HAD-001/HAD-002 — a testing blind spot, not two typos

Both broken endpoints **already have tests**, and those tests pass:
`tests/Feature/Api/HadithApiTest.php:145` (`test_hadith_search_accepts_valid_query`) asserts **200** on the
endpoint that returns 500 in production, and it passes. The whole Hadith suite is green: **80 passed, 2
skipped**.

The reason is a SQLite misfeature. Tests run on `sqlite :memory:` (`phpunit.xml`), and Laravel's SQLite
grammar quotes identifiers with double quotes. SQLite, for backwards compatibility, silently treats a
double-quoted identifier that matches no column **as a string literal**. Proven on a migrated sqlite schema:

```
SELECT "translation" LIKE '%test%' AS a, "translation" AS lit;   ->  a = 0, lit = 'translation'
```

So `orWhere('translation', 'LIKE', ...)` compiles to `'translation' LIKE '%test%'` — constant false, no error.
`HadithRepository::search()` runs cleanly on sqlite (returns 0 rows) and throws 1054 on MySQL. Confirmed the
sqlite schema genuinely lacks the column too, so this is not a migration-drift artifact.

**Consequence: the test suite structurally cannot catch any unknown-column reference in a WHERE clause.**
Adding a test for these two endpoints (as HAD-001/HAD-002 propose) would *not* have caught them and will not
prevent the next one. This is a P0 infrastructure item in its own right:

- [ ] **INFRA-001** [P0] Make unknown-column bugs detectable by the test suite
      - **Module:** Testing infrastructure  ·  **Type:** CORRECTNESS
      - **Evidence:** `phpunit.xml` (`DB_CONNECTION=sqlite`, `:memory:`); `tests/Feature/Api/HadithApiTest.php:145` passes while the same route 500s on MySQL; sqlite literal quirk demonstrated above.
      - **Decision (owner, 2026-07-26): drop sqlite — run the suite on MySQL.** sqlite is not used anywhere in this project.
      - **What to do:** point `phpunit.xml` at MySQL (`DB_CONNECTION=mysql`) and **add `DB_DATABASE=the_last_messenger_test`** — a dedicated schema, created once. Then: create that schema, run the suite, and fix what the real driver surfaces. Expect fallout worth having — the ~20 `DB::getDriverName() === 'mysql'` migration guards will execute their MySQL branches for the first time under test, FULLTEXT/`MATCH … AGAINST` paths become exercisable (so `CORE-020`'s LIKE-fallback claim becomes testable), and any other unknown-column reference will now fail loudly. Once green, simplify or delete driver guards that only existed to keep sqlite working, and update CLAUDE.md §3, which currently documents the non-MySQL test driver as a constraint to code around.
      - **⚠️ Do not skip the separate schema.** The feature suite uses `RefreshDatabase`; if `DB_DATABASE` resolves to the development database, running the tests **drops every table in it** — 50,713 hadiths, 6,236 verses, 10,316 sahaba. Set the test schema explicitly in `phpunit.xml` (not only in `.env.testing`, which is easy to have missing locally) before the first run.
      - **Why it matters / trade-offs:** Two public endpoints shipped broken *with passing tests*; until the driver matches production, a green suite is not evidence that a query runs. The suite will get slower than 45s in-memory — that is the price of the guarantee, and it is the right trade here.

### Downgraded on evidence — real code defects, no live exposure

| ID | Data | New severity |
|---|---|---|
| MED-002 | 0 of 114 `media_episodes` rows are unpublished | **P1 latent** — becomes a live leak the moment an editor drafts one |
| HAD-003 | 0 hadiths have `merged_into_id` set (the dedup merge has never been run) | **P2 latent** — 80 duplicate groups are detected but unmerged, so nothing is exposed yet |
| QUR-001 | `SELECT COUNT(*) FROM quran_surahs WHERE id <> revelation_order` = **0 of 114**; row 1 = Al-Faatiha | **P1** — no live bug; the reframing below is the correct read |

### Retracted

While checking SSG-002 I suspected the public `/sahaba` list rendered nameless cards (its
`ORDER BY COALESCE(name_loc.name, name_fb.name) ASC` sorts NULLs first in MySQL, and 2,441 of 10,316 rows have
no `ar` translation row). It does not. `resources/views/components/site/sahaba-card.blade.php:31` falls back
via `getTranslation($locale) ?: getTranslation('ar')`, no row lacks a name in *all* locales, and 0 of the first
50 cards render empty. Not a defect.

## PERF verification with EXPLAIN (2026-07-26)

The 18 PERF findings were measured against the live database. **Result: the static passes found
plausible-but-immaterial issues and missed the two that actually cost time.** This is exactly what CLAUDE.md
§9 "measure before tuning" is for — filesort cost is invisible to static reading.

### The two real hot spots — neither was filed

- [ ] **PERF-001** [P1] Replace the `LENGTH(hadith_number)` natural sort with an indexed numeric column
      - **Module:** Hadith (all public list paths)  ·  **Type:** PERF
      - **Evidence:** measured — `WHERE grading_status='sahih' ORDER BY LENGTH(hadith_number), hadith_number LIMIT 20` = **35.6 ms**; the same query with a plain indexed `ORDER BY hadith_number` = **1.5 ms** (~24× faster). `EXPLAIN` on the current form: `type=ALL, key=NULL, rows=49938, Using where; Using filesort` — a full table scan, sorting all **33,009** matching rows to return 20. `grading_status` *is* indexed (`hadith_hadiths_grading_status_index`); the index is unusable because `LENGTH()` on a column can never be. The pattern is in **8 call sites**: `HadithRepository.php:66,136,169,309,401`, `BookRepository.php:91,106`, `ChapterRepository.php:55`.
      - **What to do:** add a generated/stored `hadith_number_int UNSIGNED` column (or zero-padded `hadith_number_sort`) with an index, backfill it, and replace all 8 `orderByRaw('LENGTH(hadith_number)')->orderBy('hadith_number')` pairs with `orderBy('hadith_number_int')`. Composite `(grading_status, hadith_number_int)` and `(book_id, hadith_number_int)` indexes then serve the filtered lists directly. Keep `hadith_number` as the display value.
      - **Why it matters / trade-offs:** this is the single most expensive query shape in the app and it sits on every public hadith list, book page, chapter page, and search result. Cost grows linearly with the corpus (50,713 today). 35 ms is not an emergency, but a 24× win for one generated column and eight one-line edits is the best ratio in this whole review. Note the irony: `paginateWithFilters()` carries a careful comment about keeping the FULLTEXT path SARGable, and the `ORDER BY` immediately below it defeats indexing on every call.

- [ ] **PERF-002** [P2] Stop ordering the public Sahaba list by a LEFT JOINed translation column
      - **Module:** Sahaba  ·  **Type:** PERF
      - **Evidence:** measured — a 12-row page takes **20.9 ms**. `EXPLAIN`: `type=index, rows=10290, Using index; Using temporary; Using filesort`, i.e. all 10,290 rows are materialized and sorted per page. Cause is `SahabiRepository::applyLocaleNameOrder()` (`app/Domain/Sahaba/Repositories/SahabiRepository.php:216-229`): `ORDER BY COALESCE(name_loc.name, name_fb.name)` over two LEFT JOINs cannot use an index.
      - **What to do:** denormalize a per-row `sort_name` on `sahaba` (populated from the locale-preferred translation on write, or one indexed column per active locale) and order by it; or accept the cost and cache the ordered ID list. Do not add an index to the join columns — ordering on a `COALESCE` of two joined nullable columns is unindexable by construction.
      - **Why it matters / trade-offs:** 7,835 public rows today. Denormalizing adds a write-path invariant to maintain, which is why this is P2 and not P1 — the current cost is tolerable.

### Filed PERF findings that measurement does not support

| ID | Claim | Measured | Verdict |
|---|---|---|---|
| QUR-016 | Missing composite indexes on Juz/Hizb/page order paths | juz filter: `ref`, **126 rows** + filesort; page filter: **9 rows** | **P3** — the filesort is real but trivial at Quran scale. Adding indexes here is speculative tuning, which CLAUDE.md §4.5 forbids. |
| QUR-018 | Non-SARGable leading-wildcard `LIKE` on integer `revelation_order` | `type=index, Using index`, **114 rows** — a covering scan of a 114-row table | **P3 / won't-fix** — textbook-correct, materially irrelevant. |
| HAD-013 | Unbounded chapter/theme Hadith lists | largest chapter = **510** hadiths; the lookup itself is `ref` + `Using index` | **Still valid, but reclassify** — this is a *payload/pagination* problem (510 hadiths × all translations serialized), not an index problem. |
| CORE-022 | Articles catalogue capped at 500 rows | **18** articles exist | **P3 latent** — revisit when the catalogue approaches the cap. |
| CORE-021 | Unbounded `get()` per topic content tab | max **25** items per topic per type | **P3 latent** — bounded in practice by curation. |
| SSG-020 | Seerah public list projections | `type=ALL` on **88** rows — but `status` *is* indexed (`seerah_events_status_index`); the optimizer correctly prefers a scan at this size | **P3** — not a missing index. |

### One more thing EXPLAIN surfaced

`seerah_events` carries **~20 indexes on 88 rows**, several redundant — `slug` is indexed twice
(`seerah_events_slug_unique` *and* `seerah_events_slug_index`), and `hijri_year` has both a standalone index
and the leading position of `idx_seerah_chronological`. Redundant indexes cost write throughput and buy no
reads. CLAUDE.md §4.5 forbids speculative and duplicate indexes; this table is the counterexample. **P3,
cleanup** — drop the duplicate `slug` index and the standalone `hijri_year` index.

### Corrections to the cluster reports

**MED-001 is mis-scoped, not media-specific.** There is no HTML purifier in this project at all — the only
sanitization is `strip_tags` in `app/Support/Utilities/TextSanitizer.php`. Raw CMS rendering is the house
convention, not a Media slip: `resources/views/public/articles/show.blade.php:401` and
`resources/views/public/pages/show.blade.php:32` do exactly the same, while Seerah does it correctly via
`nl2br(e(...))` (`resources/views/public/seerah/event.blade.php:481`). The content is admin-authored, so this
is **one project-wide P2 hardening item covering articles + static pages + media programs**, not a media P0.
Fix it in one place (a shared purifier + allowlist applied in `prepareForValidation()` across all three write
paths) or accept it as a documented trusted-content decision — but do not fix it for Media alone.

**QUR-001 is real but reframed.** The report claims the reader can pair one surah's header with another's
verses. That only bites if `quran_surahs.id != quran_surahs.revelation_order` — **measured: 0 of 114 rows
differ**, and row 1 is Al-Faatiha, so there is no live mismatch. The codebase deliberately treats
`revelation_order` **as** the canonical mushaf number:
`app/Domain/Quran/Models/Surah.php:107` (`getNumberAttribute()` returns it) and
`app/Http/Resources/SurahResource.php:27` (emits it as `number`). So the defect is not a live mismatch — it is
that (a) the column is misnamed, (b) nothing enforces `id == revelation_order`, and (c) true revelation
chronology has nowhere to live, since `SurahResource` returns the same value for both `number` and
`revelation_order`. **P1, schema/naming** — an invariant nothing enforces, plus a data model that cannot express
revelation chronology at all. Add a check constraint or a rename, and a separate column if chronology is ever
needed.

**SSG-001 is bounded to a stale-cache window, not an open leak.** `SeerahEventRepository::findBySlug()` does
apply `->published()` (line ~205). The exposure is that a published→draft flip stays visible for up to the
1,800s TTL because the direct keys (`seerah_event_slug_{slug}`, `public_seerah_event_{slug}_{locale}`) are not
explicitly evicted. Real, and worth fixing, but it is a 30-minute window on a formerly-public row — P1.

### Two findings the reports missed

**Seerah event views are counted only on a cache miss.**
`SeerahEventService::getEventBySlug()` calls `$event->incrementViews()`
(`app/Domain/Seerah/Services/SeerahEventService.php:51`), and `SeerahController::event()` invokes that service
*from inside* its own `Cache::remember("public_seerah_event_{slug}_{locale}", 1800, …)` closure
(`app/Http/Controllers/Site/SeerahController.php:219`). On a cache hit the closure never runs, so the
increment never fires — views tick at most once per slug per locale per 30 minutes. Counts are substantially
understated and any "most viewed" ordering built on them is wrong. Move the increment outside the cached
payload (as Articles already does via its async endpoint). **P2, correctness.**

**Two tests in the suite are stale and currently red** (pre-existing; not caused by this review).
`tests/Feature/Web/ArticlePublicPagesTest.php:212,216` assert that a plain `GET` of an article page increments
`articles.views`. View counting was since moved to an async client-called endpoint
(`ArticleController::registerView()`, `app/Http/Controllers/Site/ArticleController.php:168`, session-guarded),
so a page GET legitimately no longer increments. The tests encode the old synchronous behavior and should be
rewritten against `POST /articles/{id}/view` — including the session-guard and new-session cases they were
clearly written to cover. **P2, test-debt.** Leaving them red erodes the suite's signal.

## Cross-cutting themes — fix these once, not 19 times

The 19 SECURITY findings are overwhelmingly **one architectural gap wearing five hats**, and the same is true
of the PERF and SCHEMA clusters. Treating them as a list of 151 tickets would mean fixing the same bug five
times in five modules.

**1. Publication/readiness gating lives in individual repository methods, not in the domain.**
Every module has a correct publication predicate available — `Article::status`, `MediaEpisode::scopePublished()`,
`SahabiTranslation::scopeReady()`, `SeerahEvent::published()`, `Hadith::merged_into_id` — and every module has
at least one public read path that forgets to apply it. Unified search builds from bare `$modelClass::query()`
(`app/Domain/Shared/Services/SearchService.php:402`); Topics loads itemables unfiltered; the Media episodes API
skips `published()`; the Sahaba list skips `ready()`; the Hadith lists skip `whereNull('merged_into_id')`.
**The fix is a Shared contract** — a `PublicReadableModule` / `publicQuery(locale)` contributor interface that
search, topic aggregation, sitemap, and API list paths all route through, so "what is publicly visible" is
defined once per module and cannot be forgotten by the next list method. This also satisfies CLAUDE.md §4.4
module isolation, since search and topics currently reach across modules to decide visibility.

**2. Cache invalidation is not publication-aware.** Same root cause, one layer up, and a *recurring* defect in
this codebase — the `public_hadith_show_*` / `public_genealogy_index_*` eviction bug has already been fixed
once. It is back in Seerah (SSG-001) and Sahaba (SSG-002). Stop patching per-key: version the
`public_*` key namespace per module and bump the version on any publish-state mutation, including bulk
status/ready updates. One mechanism retires an entire class of finding.

**3. Schema/code contract drift is invisible to the test suite.** HAD-001 and HAD-002 return 500 on every
call — and both **already have passing tests**. The cause is not missing coverage but the test driver: SQLite
silently reinterprets an unknown double-quoted identifier as a string literal, so a nonexistent column is a
constant-false predicate under sqlite and a fatal 1054 under MySQL (demonstrated in
[Runtime verification](#runtime-verification-2026-07-26)). `revelation_order` (QUR-001) and `articles.views`
vs `views_count` (CORE-003) are milder cases of the same drift. **The systemic fix is INFRA-001, not more
tests** — tests written against sqlite cannot see this class of bug at all.

**4. Prototype interactions shipped as visual shells.** A consistent pattern across clusters: the control
exists, animates, and has correct styling, but no backend. Reciter "Listen now" receives a null URL
(QUR-002); the episode player never saves or resumes position though the endpoints exist (MED-003); home
bookmark/share and the footer newsletter are inert (CORE-018); the Hadith explorer is missing most of its
facets (HAD-005). These are honest P1 feature gaps, not defects — but they are the findings most likely to be
what a user means by "the site feels unfinished," so they should be triaged by visibility, not by module.

**5. The project's own status docs overstate completion.** Each report has a `## Doc corrections` section, and
they agree: `PROJECT_PROGRESS.md` claims home sections are DB-driven (much of `home.blade.php` is static
editorial markup), claims Topics admin exists (no admin controller or routes were found — `TopicSeeder` is the
only writer), and claims search covers media with full-text (media is excluded from `PUBLIC_MODULES`, and the
LIKE fallback is taken because no model implements `getFullTextColumns`). `WEB_APP_MODULES_ANALYSIS.md` is
stale in the opposite direction — it lists as missing several things now built. Reconcile both, plus the
`KNOWN_ISSUES.md` rows listed Open that later rows say are Fixed.

## Suggested sequencing

Revised after runtime verification — the two items proven broken in production come first.

1. **HAD-001 + HAD-002** — two public endpoints return 500 on every call. Smallest diff, largest win.
2. **INFRA-001** — close the sqlite/MySQL driver gap, *before* writing the tests HAD-001/002 ask for.
   Written against sqlite, those tests would pass while the endpoints stayed broken.
3. **CORE-001** — drafts are publicly searchable right now (verified: article id 2 returned by search).
   Fix via the Shared `publicQuery` contract below, not a local patch.
4. **The publication-gating contract** — the Shared `publicQuery(locale)` contributor interface, applied to
   search, topics, media API, sahaba, genealogy, and hadith canonical filtering. Closes CORE-002, MED-002,
   SSG-002 and HAD-003 together, and prevents the next list method from forgetting.
5. **CORE-016** social role parity — a few lines, silently breaks role-gated features today.
6. **Publication-aware cache versioning** — closes SSG-001 and retires the recurring eviction pitfall.
7. Fix the two stale red tests so the suite means something again.
8. **PERF-001** — a generated column plus eight one-line edits for a measured 24× on every hadith list path.
   Best effort-to-payoff ratio in the review; do it before any other PERF item.
9. Then triage the P1 shells (theme 4) by user visibility. The remaining filed PERF findings are measured as
   immaterial — see the table above; do not spend time on them.

## Gate status (2026-07-26, MySQL up)

`php artisan test` — **780 passed, 2 failed, 6 skipped** (2,635 assertions, 45.9s). Both failures are the
stale view-increment tests described above and are **pre-existing** — this review added only markdown files and
changed no code. `pint` and `deptrac` were not run, for the same reason: there is no PHP in this changeset.

Read the gate result with theme 3 in mind: a 780-green suite coexisted with two public endpoints that 500 on
every request. The suite's coverage is real, but its *driver* is not, so green is currently weaker evidence
than it looks.

## Caveats

- The PERF findings have now been measured (see above) — six of the filed ones are immaterial at current data
  volumes, and the two costly query shapes were found only by measuring. Treat any *unmeasured* perf claim in
  the cluster reports the same way: `EXPLAIN` it before acting.
- Correctness and parity findings outside the P0 set remain **static**. They were traced through code but not
  exercised at runtime.
- OAuth, mail delivery, queue behavior, and signed verification links cannot be proven statically; wiring is
  present.
- Findings were produced by five independent passes, so a small number of cross-cluster duplicates are
  expected (notably the shared-component and cache-invalidation items). Dedupe when converting to tickets.
