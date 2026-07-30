# Codex review — Hadith refactor plan v1

> **Reviewer:** Codex CLI 0.144.1 (`codex exec`, sandbox `read-only`)
> **Date:** 2026-07-30
> **Subject:** `TODO_hadith-refactor.md` v1
> **Verdict:** `needs-rework` — 15 blocking issues
>
> Commissioned as an adversarial review: Codex was told to find what was wrong, that a review
> finding nothing is a failed review, and that the plan's author had already lost a database this
> session through a careless command. It read the repo directly rather than trusting the plan text —
> see its own `verified` section at the end for what it checked versus took on trust.
>
> Claude independently re-verified the four most consequential claims; all four held:
>
> | Claim | Result |
> |---|---|
> | `AppServiceProvider.php:10-12` imports 3 Hadith services, missing from the purge list | confirmed — container boot would fail |
> | v1's diagram promises `primary_narrator_id`; no such column exists in the migration | confirmed |
> | `--step=12` counts migration *files*, not tables | confirmed — would roll back a non-Hadith migration |
> | `HasAuditLog` / `HasVersions` / `ContentVersion` already exist | confirmed, all present in `Domain/Shared` |
>
> Resolutions are tracked in `TODO_hadith-refactor.md` v2. Verbatim review follows.

---

## verdict

**needs-rework.** The plan is not safe to execute as written: it contains a destructive rollback-count error, an incomplete purge inventory, unenforced hierarchy invariants, an unexecutable extraction command, and extraction/schema contracts that cannot populate several promised fields. The overall direction—Service + Repository, per-locale text rows, MySQL FULLTEXT, and staged import—is reasonable once these blockers are corrected.

## blocking

- **What:** After deleting migration `000009`, 11 migration files create 12 tables, but the plan rolls back 12 migrations. **Where:** `TODO_hadith-refactor.md:419,464-470,796-799`. **Why:** Laravel’s `--step=12` counts migration files, not tables, so it would also roll back the preceding non-Hadith migration, currently `2026_07_27_000001_create_home_sections_tables.php`; this behavior is explicit in the [Laravel 12 migration documentation](https://laravel.com/docs/12.x/migrations#rolling-back-migrations). **Fix:** Roll back the verified batch containing exactly the 11 target migration names on a disposable test database; remove every `--step=12` instruction.

- **What:** The destructive purge has no mandatory clean-worktree checkpoint or fail-closed database guard. **Where:** Phase 0 HR-001/010/011 and §12.6. **Why:** The repository is on `fix/parity-p0` but currently has extensive modified, deleted, and untracked work; `rm -rf` would permanently remove untracked work, while `.env.testing` is absent and `phpunit.xml` does not protect Artisan commands. **Fix:** Require a committed checkpoint on a dedicated branch, an reviewed `git status`, a real `.env.testing`, and an exact allowlist assertion on the resolved database name before every destructive command; “print it first” is insufficient.

- **What:** The Phase 0 integration inventory is incomplete, and deletion is ordered before rewiring. **Where:** HR-001 before HR-020–032. **Why:** `app/Providers/AppServiceProvider.php:10-12` imports three Hadith services and is missing from the list, so deleting the module makes container boot fail. Additional stale consumers include `app/Console/Commands/IslamicData/ImportAllCommand.php:36`, `DedupePeopleCommand.php:46`, both Portal export/verify commands, `database/seeders/DatabaseSeeder.php:46`, `TopicSeeder.php:183`, `app/Domain/Sahaba/Models/Sahabi.php:439`, `routes/api.php`, `routes/web.php`, and `routes/admin/hadith.php`. **Fix:** Inventory both class references and old table names, rewire or disable all providers/routes/commands first, and delete the old module last.

- **What:** The hierarchy permits internally contradictory records. **Where:** `2026_07_30_000005_create_hadith_hadiths_table.php:21-25`. **Why:** A hadith can reference collection A, a book from collection B, and a chapter from another book; deleting a chapter also silently nulls every hadith’s chapter despite admin CRUD. **Fix:** Keep denormalized `collection_id` only with composite FKs `(book_id, collection_id)` and `(chapter_id, book_id)`, and change chapter deletion to `RESTRICT`. Removing `collection_id` and accepting the cheap join is also sound, but the current unconstrained duplication is not.

- **What:** `book_id` is mandatory although the plan says `nawawi40` has no book structure. **Where:** Plan §3.3 and migration `000005:22`. **Why:** Its 42 hadiths cannot be inserted without inventing a book, yet no synthetic-book rule exists. **Fix:** Explicitly define and test an internal synthetic book for bookless collections, or make `book_id` nullable and update every affected query.

- **What:** Missing English is declared normal while `hadith_texts.body` is non-null. **Where:** `TODO_hadith-refactor.md:169-170,577-578`; migration `000006:19`. **Why:** Importing the planned `text.en = null` either fails or requires undocumented row-skipping behavior. **Fix:** Prefer omitting the locale row when no body exists; state that rule in the mapper/importer contract and test Arabic-only records.

- **What:** The narrator model promised by the diagram does not exist in the schema or mapper. **Where:** `TODO_hadith-refactor.md:207,331-348,597`; migration `000005`. **Why:** The diagram names `primary_narrator_id`, JSON emits `narrated_by`, but no column stores either; `hadith_scholars` therefore has no narrator relationship except grading provenance. **Fix:** At minimum store the locale-specific narrator preamble on `hadith_texts`; if normalized primary-narrator browsing is required, add the missing nullable FK and extraction/resolution rules.

- **What:** The grading schema and normalizer cannot represent the source data described by the plan. **Where:** §4.6 and HR-300. **Why:** `GradeNormalizer` returns one `(grade, scope)`, so strings such as `Sanad Da'if wal-Hadith Hasan` and `1: Hasan<br>2: Da'if` lose one or more structured claims; preserving only `raw_grade` does not make filtering or badges correct. **Fix:** Return a list of claims and write one grading row per claim, with a nullable subject/part label; use `TEXT` for the shared raw value.

- **What:** The denormalized `hadith_hadiths.grade` invariant is undefined and unenforceable. **Where:** `TODO_hadith-refactor.md:283,291-292,621,723`. **Why:** There is no authority/rank or primary flag in `hadith_gradings`, yet the rollup claims to mirror the “highest-authority” row; the admin edits the rollup directly, guaranteeing drift. **Fix:** Define it as an explicit display grade with a deterministic policy—single unambiguous hadith-scope claim wins, conflicts/part-specific claims become `unknown`—and update it transactionally with grading rows.

- **What:** The FULLTEXT index does not match the planned query. **Where:** migration `000006:40-43`, HR-117, §12.3. **Why:** The plan says to query `MATCH(body_normalized)`, but the only index is on `(body, body_normalized)`; MySQL requires the `MATCH()` column list to match a FULLTEXT index. Matching both also indexes duplicated Arabic text and changes document statistics/relevance, which MySQL calculates across all indexed columns and rows. See the [MySQL FULLTEXT documentation](https://dev.mysql.com/doc/refman/8.0/en/fulltext-natural-language.html). **Fix:** Use one importer-populated `search_body` column—normalized Arabic, original Latin text—with `FULLTEXT(search_body)`.

- **What:** `hadith:extract` cannot perform the extraction mechanism the plan requires. **Where:** HR-200 versus §2.1/D6. **Why:** An Artisan command is a server-side process and cannot use a user’s Chrome extension; the plan already states server-side HTTP receives 403, and no browser-driving dependency or bridge exists. **Fix:** Make extraction browser-side and have it write/download the JSON files; keep Artisan responsible only for validating and importing them.

- **What:** The extraction contract omits fields required by the schema and features. **Where:** §2.2–2.5 and HR-302–304. **Why:** There is no grade selector, English URN extraction, theme, cross-reference, related-verse, scholar, or primary-narrator mapping; `hadith_cross_refs`, `hadith_related_verses`, themes, and `sunnah_urn_en` will remain empty. **Fix:** Produce an explicit source-selector → JSON → table-column matrix; implement each mapping or remove the unused table/feature before freezing the schema.

- **What:** Resume/idempotency checks can accept silently incomplete data. **Where:** HR-201–203 and HR-303. **Why:** Existing files are skipped without schema-version, checksum, expected-record-count, or selector-count validation; root upserts also do not remove child texts/gradings/themes that disappeared from a corrected source file. **Fix:** Add a per-book manifest with extractor version, URL, counts, checksum and validation status, then reconcile child rows transactionally on re-import.

- **What:** Public route collision analysis checks the wrong collision. **Where:** HR-410 and routes in §4.2. **Why:** `/hadith/{collection}:{number}` conflicts with `/hadith/{collection}`, while `/hadith/urn/{urn}` conflicts with `/hadith/{collection}/{bookNumber}` and `/hadith/search` can be captured as a collection; segment count already prevents the claimed colon/book collision. **Fix:** Register static routes first and constrain every dynamic segment; test search, URN, collection, book, `1a`, and comma-containing hadith numbers.

- **What:** Phase 5 requests operations and repositories that the planned schema cannot support. **Where:** HR-113 and HR-500. **Why:** The five repository pairs omit Theme despite Theme CRUD, while “reorder/toggle-published/toggle-featured for all six” targets entities that lack those columns. **Fix:** Name a real Theme persistence path and list actions per resource rather than generating the same endpoints for all six.

## non-blocking

- **What:** Nullable unique URNs are sound. **Where:** migration `000005:36-37`. **Why:** MySQL permits multiple `NULL` values in a unique index while rejecting duplicate non-null URNs, as documented in [MySQL CREATE INDEX](https://dev.mysql.com/doc/refman/8.0/en/create-index.html#idm140651343554000). **Fix:** Keep them, but add extraction and collision tests for both URN namespaces.

- **What:** `chapter_title` duplicates chapter localization without a demonstrated override source. **Where:** migration `000006:28-30`; JSON shape line 587. **Why:** The listed selectors read chapter headings, not a separate per-hadith bab override, creating two values that can drift. **Fix:** Remove it unless a distinct per-record selector is verified; otherwise rename it `chapter_title_override` and define precedence.

- **What:** Publication-aware reading indexes are missing. **Where:** migration `000005:49-53`. **Why:** Public book/chapter queries always add `is_published`, and random requires a published candidate set. **Fix:** Validate replacements `(book_id,is_published,in_book_number)`, `(chapter_id,is_published,in_book_number)`, and `(is_published,id)` with `EXPLAIN`; add the collection/grade composite only after the exact query is written.

- **What:** Some indexes are speculative. **Where:** collection migration `000001:38` and scholar migration `000004:42-43`. **Why:** Indexing a 17-row collection table and low-cardinality `is_companion` has no measurable value yet increases write/index surface. **Fix:** Remove them unless HR-608 demonstrates use; retain unique, FK, reading-order, and FULLTEXT indexes.

- **What:** Counter columns can drift under admin CRUD. **Where:** `total_hadiths`, `hadith_count`, HR-304. **Why:** Only the importer is assigned maintenance, while Phase 5 permits create/delete/move operations. **Fix:** Prefer `withCount`/conditional aggregates for 17 collections and 607 books, or update counters in every repository transaction.

- **What:** Import versus editorial ownership is unspecified. **Where:** HR-303 and HR-508. **Why:** A later idempotent import can overwrite human-edited text, grade, themes, and publication state. **Fix:** Declare source-owned versus editor-owned fields and never overwrite editor-owned values without an explicit reset option.

- **What:** Revision history is warranted, but new audit columns are not. **Where:** schema §4. **Why:** Canonical religious text is edited through admin, and the repository already has `HasAuditLog`, `HasVersions`, `ContentVersion`, and `updated_at`. **Fix:** Reuse existing audit/version infrastructure for admin writes and compare `updated_at` for optimistic locking; suppress version generation during bulk import. Soft deletes are unnecessary if hard deletion of imported records is disallowed and unpublishing is available.

- **What:** Tests arrive after destructive import work. **Where:** Phase 6 after Phases 2–3. **Why:** The pilot can write thousands of rows before migration constraints, cross-layout selectors, idempotency, and dry-run behavior have durable tests. **Fix:** Move those tests into their owning phases; add admin authorization/validation tests, not only API tests.

- **What:** The `≥95%` grade-normalization target rewards confident misclassification. **Where:** HR-300. **Why:** Ambiguous prose and multi-claim grades should remain `unknown`, not be forced into an enum to satisfy coverage. **Fix:** Assert exact expected outcomes for the curated fixture and report coverage without a pass threshold.

- **What:** “Base-URL swap compatibility” is unsubstantiated. **Where:** §4.1. **Why:** Similar route names do not guarantee sunnah.com response envelopes, field names, pagination, errors, or identifiers. **Fix:** Call this a project API unless contract fixtures prove compatibility.

- **What:** Changing `innodb_ft_min_token_size` only on `dev-mysql-db` leaves environment drift. **Where:** HR-101 and §12.4. **Why:** Test, staging, and production indexes will tokenize differently; the setting also affects every schema on the shared server. **Fix:** Document configuration and index rebuild steps per environment before search acceptance.

- **What:** One JSON file per book is a good boundary, but streaming each file is unnecessary. **Where:** HR-301. **Why:** It already bounds memory to one book and no streaming JSON dependency is installed. **Fix:** Use ordinary decode with a file-size guard; add streaming only if a measured book exceeds the memory budget.

- **What:** The reported reference-corpus size is factually stale. **Where:** §3.3. **Why:** The local corpus contains exactly 51,441 JSON files but occupies about 320 MB, not 1.7 GB. **Fix:** Correct the size or state what different measurement produced 1.7 GB.

- **What:** Phase 1 collection seeding before import, followed by public/admin work, is coherent. **Where:** HR-102 and Phases 3–5. **Why:** The importer needs stable collection identities, while UI implementation does not need to precede data loading. **Fix:** None.

## cuts

- Remove `hadith_related_verses` until the chosen import source actually emits verified verse links; add it with that extractor later.

- Remove `hadith_cross_refs` and the parallel-narrations page until xrefs are extracted and validated; otherwise they are an empty table and dead page.

- Remove `chapter_title` and `hadith_texts.notes`; the former duplicates chapters and the latter has no source or defined editorial workflow.

- Remove stored collection/book counters; use aggregate counts over this modest dataset.

- Remove separate Book and Chapter repository pairs; let the Collection repository own its hierarchy, while retaining Hadith, Scholar, and a real Theme repository.

- Remove the `Support/` namespace; Seerah already places dashboard/export/bulk adapters under `Services/`.

- Remove `SearchResult`; return Laravel’s paginator through the existing typed service boundary.

- Remove streaming JSON machinery until a book file demonstrably exceeds the memory limit.

- Remove `text_hash` plus its permanent index if HR-309 is a one-time report; compute hashes during the report.

- Remove generic reorder/publish/feature routes and server-rendered Blade “loading states” where no asynchronous operation exists.

## missing

- A mandatory committed Git checkpoint and clean-worktree gate before Phase 0.

- A fail-closed Artisan database-name allowlist and real `.env.testing`.

- An explicit browser-side extraction architecture; Artisan cannot substitute for Chrome.

- Representative extraction fixtures for standard, single-column Ahmad, forty collections, missing-English, chapterless, and combined-number records.

- A versioned extraction manifest with expected book/hadith counts, checksums, source URLs, and selector counts.

- A complete source-field → JSON-field → database-column mapping.

- Structured multi-claim grading and a deterministic display-grade policy.

- Import/editor field ownership and child-row reconciliation rules.

- Composite hierarchy constraints and a chapter-deletion policy.

- Phase 0 removal or temporary disabling of old public, API, and admin Hadith routes.

- Migration/schema/import tests before the pilot import, plus admin authorization and validation tests.

- Production/test configuration for FULLTEXT token size and index rebuilds.

## disagreements

- D10’s full CRUD on imported collection/book/chapter identity is actively harmful: editors can renumber or delete source hierarchy and destabilize canonical references; those entities should be read-only apart from publication and localized presentation metadata.

- No disagreement with CQRS removal, MySQL FULLTEXT, Arabic/English source scope, discarded old data, or the settled public URL scheme.

## verified

- Read `TODO_hadith-refactor.md` in full, both relevant `CLAUDE.md` files, the Seerah module structure and representative repositories/services, all seven Shared contracts, all `2026_07_30_0000*` migrations, route files, service providers, existing audit/version infrastructure, and both admin/site component directories.

- Ran the requested `grep -rn "Domain\\\\Hadith" app routes tests config database`, plus broader class-reference and old-table-name searches. The exact class search found 14 test files and the missing `AppServiceProvider`; broader searches exposed the commands, seeders, routes, Portal tooling, and Sahaba raw-table dependency that the plan misses.

- Confirmed this is a Git repository on branch `fix/parity-p0` with a heavily dirty worktree and untracked target migrations.

- Confirmed `phpunit.xml` selects MySQL database `the_last_messenger_test`, `.env.testing` is absent, and `.env.example` still defaults to SQLite.

- Confirmed the target directory currently contains 12 migration files; deleting `000009` leaves 11 files creating 12 tables.

- Confirmed the local reference corpus has 51,441 JSON records and inspected representative URN, theme, grading, narrator, and multi-grade records.

- Checked MySQL FULLTEXT column/relevance semantics, nullable unique-index behavior, Laravel rollback semantics, and Spatie JSON/fallback behavior against official documentation.

- Did not run migrations, tests, Artisan, browser extraction, or live-site checks. I took the plan’s sunnah.com selector counts, 3,000-record statistics, GitHub source-schema summary, MySQL 8.0.46 round-trip claim, and live 403 behavior on trust. No file was modified, created, or deleted.