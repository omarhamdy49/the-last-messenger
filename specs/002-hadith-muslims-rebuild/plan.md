# Full Hadith and Muslim Identity Rebuild — v4 (agreed)

**Status:** reviewed by Claude (repo verification) and Codex CLI (full read-only repo audit,
thread `019fd763-f421-7a03-9ba3-8d94a6681a9b`). Both reviewers' blocking findings are folded in.
Codex's original verdict on v2 was **No — not safe as written**; the findings that produced that
verdict are either resolved below or listed as remaining owner decisions.

**Companion document:** [`spec.md`](spec.md) states the same work as capabilities and testable
requirements, with `checklists/requirements.md` recording its validation. This file is the technical
plan; `spec.md` is what the requirements are checked against.

**v4 changes.** Owner answers folded in: companion battles, timelines, and categories retired
(Decision 6); `sanad`, `matn`, `translator`, `volume_number` retired (Decision 2); scalar portrait
only, no media library on `Muslim` (Decision 4). And the execution model changed — **full purge, no
upgrade path**: the old migration files are deleted rather than superseded by teardown migrations,
and the database is rebuilt with `migrate:fresh`. Blocking findings 6 and 7 dissolve as a result.

## Summary

Rebuild the Hadith module and every person/identity table from scratch, from canonical source files,
with no upgrade path. Every historical figure becomes one `muslims` row carrying roles (companion,
narrator, scholar, grader, compiler) instead of up to four unrelated records; every hadith gets one
stable public address (`reference_key`) and one displayed verdict derived by a written rule. The old
migration files are deleted rather than superseded, and the database is built by `migrate:fresh`.

Requirements and acceptance criteria: [`spec.md`](spec.md). Research decisions:
[`research.md`](research.md). Schema: [`data-model.md`](data-model.md). Endpoint replacement matrix:
[`contracts/`](contracts/). Runbook: [`quickstart.md`](quickstart.md).

## Technical Context

**Language/Version**: PHP 8.5 (`composer.json` requires `^8.5`, `config.platform.php` pinned to
`8.5.4`) — **not 8.2**, which is what CLAUDE.md and the constitution still claim.
**Primary Dependencies**: Laravel 13 (`^13.0`), spatie/laravel-translatable,
spatie/laravel-permission, spatie/laravel-medialibrary (Quran/Blog only after this change —
see Owner Decision 4), laravel/sanctum.
**Storage**: MySQL 8 — InnoDB, `utf8mb4`. Uses CHECK constraints, STORED generated columns, composite
foreign keys, FULLTEXT indexes, and recursive CTEs. All are MySQL-8-specific and all are load-bearing.
**Testing**: PHPUnit against MySQL (`phpunit.xml` → `DB_CONNECTION=mysql`,
`DB_DATABASE=the_last_messenger_test`), so every MySQL-specific behaviour above is directly
assertable and, per constitution VI, must be asserted rather than assumed.
**Target Platform**: Linux server, `docker compose`. Blade + Alpine 3 + Tailwind, server-rendered —
not a SPA. `npm run build` required after any Blade or asset change (no Vite dev server here).
**Project Type**: Single Laravel application, DDD modules under `app/Domain/`, admin at
`/su/portal/v1/admin`.
**Performance Goals**: <200ms API response, <2s page load. Fixed query counts on every read path; no
N+1; `EXPLAIN`-verified index usage on browse, search, and random.
**Constraints**: Arabic FULLTEXT requires `innodb_ft_min_token_size = 2` (a **server variable**,
requiring restart and index rebuild — not settable per-session, so it is an environment prerequisite,
not a migration step). The ngram parser must not be used: it is CJK-oriented and wrong for Arabic.
InnoDB FULLTEXT is not transaction-visible in the current test arrangement, so search tests need
committed or truncated fixtures rather than transactional ones.
**Scale/Scope**: 17 collections, **51,441 hadiths** (sum of `config/islamic-data.php`
`expected_hadiths`), 47,168 chapter-linked, 38 with no Arabic text by documented source gap. Plus
narrators, scholars, and companions collapsing into one `muslims` table, and the full admin CRUD,
public site, and API surface for all of it.

**Source dependency (execution blocker, not a planning blocker):** canonical files live **outside
this repository** at `../prophet-data-generator/hadith/production`
(`config/islamic-data.php:16-20`). **That directory does not exist on this machine.** The rebuild
cannot be executed until it is present, and since the purge happens first, discovering its absence
afterwards would leave the system empty with no way back. Verifying it is step 0 of the runbook.

## Constitution Check

*GATE: evaluated against `.specify/memory/constitution.md` v1.0.0.*

| # | Principle | Verdict | Note |
|---|-----------|---------|------|
| I | Layered data flow | **Pass** | Controller → Service → RepositoryInterface → Eloquent repo retained. Transactions inside repositories; per-collection import transaction lives in the import repository, not the command. |
| II | Query discipline | **Pass with obligation** | Every index in `data-model.md` names the query that justifies it, and none ships until `EXPLAIN` shows it used. The plan already deletes two speculative indexes from the owner's draft (`(book_id, chapter_number)`, `UNIQUE(hadith_id, narrator_id)`). |
| III | Typed boundaries | **Pass** | `final readonly` DTOs across layers, PHP enums for `grade`, `gender`, `relationship_type`, explicit `$fillable`. |
| IV | Module isolation | **⚠ Needs a decision — see Complexity Tracking #1** | `Muslim` is used by Hadith (narrators, compilers), Genealogy, and the companion directory. If it lives in any one domain, the others must import it, which principle IV forbids and `composer deptrac` will fail on. |
| V | Arabic-first and multilingual | **⚠ Deviation — see Complexity Tracking #2 and #3** | Constitution mandates five locales for content; Decision D narrows hadith and person **content** to `ar`/`en`. Decision B additionally stores hadith text in flat columns rather than Spatie JSON. |
| VI | Tests are part of the change | **Pass** | Tests already run on MySQL, so CHECK constraints, generated columns, composite FKs, and FULLTEXT are asserted, not assumed. See the test plan below. |
| VII | Security by default | **Pass** | FormRequests on every mutating admin endpoint, allowlisted sort/filter columns, `throttle:search` retained on both search surfaces, generic client errors. |
| VIII | Destructive operations guarded | **⚠ Directly engaged — see Gate VIII below** | This feature's central act is `migrate:fresh`. The constitution's destructive-operations rules are not incidental here; they are the main safety requirement. |
| IX | Simplicity | **Pass** | Every table has a populator. `muslim_relationships` is populated by admin rather than import — acceptable, but it must ship with its admin screen, not before it. |
| X | Honest reporting | **Pass** | Per-collection parity is itemised, never averaged; discarded categories, unmapped grades, and unresolved people are each reported with counts; non-zero exit on any mismatch. |

### Gate VIII — destructive operations, applied to this feature

Constitution VIII is written for exactly this situation and each clause has a concrete obligation
here. None is optional:

1. **Database-name allowlist.** `migrate:fresh` must resolve `DB_DATABASE` and assert it against an
   explicit allowlist, aborting on mismatch. Printing the name is explicitly *not* a control. A
   rebuild command pointed at production by a stale `.env` is the one unrecoverable failure in this
   plan.
2. **`DB_DATABASE` passed explicitly**, never inferred from `--env` — which silently falls back to
   `.env` when the matching env file is absent.
3. **`--step=N` is forbidden.** Not applicable to `migrate:fresh`, but it rules out "roll back N
   migrations" as a recovery path. Recovery is re-running the whole build.
4. **Deleting untracked files requires a committed checkpoint first.** The purge deletes
   `database/migrations/2026_08_05_000006_add_exact_hadith_reference_fields.php`, which is
   **currently untracked**, along with uncommitted working-tree changes to seven Hadith files. These
   must be committed before the deletion commit, or they are unrecoverable.

### Constitution amendments this feature requires

Two statements in the constitution are factually wrong against the repository and will mislead
anyone generating code from them. Per Governance, correcting them needs a recorded decision and a
version bump:

- **Technology Constraints says "PHP 8.2+, Laravel 12".** `composer.json` requires `^8.5` and
  `^13.0` with the platform pinned to `8.5.4`. Code or CI targeting 8.2 fails dependency resolution
  before this rebuild starts. Same error in CLAUDE.md.
- **Principle V's five-locale rule** does not distinguish UI strings from translatable content. This
  feature keeps five locales for UI strings and narrows content to two; the principle should say so
  rather than being silently violated. See Complexity Tracking #2.

## Project Structure

### Documentation (this feature)

```text
specs/002-hadith-muslims-rebuild/
├── spec.md                  # Requirements and acceptance criteria
├── plan.md                  # This file — technical plan
├── plan-v1-original.md      # Superseded first draft, kept for provenance
├── research.md              # Phase 0 — decisions with rationale
├── data-model.md            # Phase 1 — schema, constraints, indexes
├── quickstart.md            # Phase 1 — the rebuild runbook
├── contracts/
│   ├── README.md            # Endpoint replacement matrix (old → new → verdict)
│   ├── hadith-api.md        # Hadith read API contract
│   └── muslims-api.md       # Muslim/genealogy API contract
└── checklists/
    └── requirements.md      # Spec quality validation
```

### Source Code (repository root)

```text
app/Domain/
├── People/                          # NEW — owns Muslim identity (Complexity Tracking #1)
│   ├── Models/{Muslim,IslamicRole,MuslimRelationship}.php
│   ├── Enums/{Gender,RelationshipType}.php
│   ├── Repositories/{MuslimRepositoryInterface,EloquentMuslimRepository}.php
│   ├── Services/{MuslimDirectoryService,GenealogyTreeService}.php
│   └── ValueObjects/HijriYear.php
├── Hadith/                          # REBUILT — old contents deleted
│   ├── Application/{Commands,Queries,Handlers,DTOs}/
│   ├── Models/{HadithCollection,HadithBook,HadithChapter,Hadith}.php
│   ├── Enums/Grade.php
│   ├── Repositories/, Services/
│   └── Import/{CanonicalHadithImporter,GradeNormalizer,ReferenceKeyValidator}.php
├── Shared/
│   └── Contracts/PersonReference.php   # How Hadith reaches People without importing it
├── Sahaba/                          # DELETED entirely
└── Genealogy/                       # DELETED — absorbed into People/

app/Http/Controllers/{Admin,Api,Site}/   # Hadith + Muslim controllers rewritten
app/Http/Resources/                      # Hadith*, Muslim* resources rewritten
database/migrations/                     # Old Hadith/person migrations DELETED, new set authored
database/seeders/IslamicRoleSeeder.php   # NEW
resources/views/{public,admin}/{hadith,muslims}/
tests/{Feature,Unit}/{Hadith,People}/
deptrac.yaml                             # NEW People layer
```

**Structure Decision**: Single Laravel application, existing DDD layout under `app/Domain/`. One new
module — `People` — is added because `Muslim` is genuinely cross-cutting and has no correct home in
any existing domain module (Complexity Tracking #1). `Sahaba` and `Genealogy` are deleted outright,
their capability absorbed into `People` and roles.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| **1. New `app/Domain/People/` module, plus a `Shared` contract for Hadith to reach it** | `Muslim` is referenced by Hadith (`hadith_narrators.narrator_id`, `hadith_collections.compiler_id`), by genealogy, and by the companion directory. Constitution IV forbids domain modules importing one another, and `deptrac` currently has **no layer owning `Muslim`** (Blocking 5). | Putting `Muslim` in `Hadith` makes Genealogy import Hadith. Putting it in `Genealogy` makes Hadith import Genealogy. Both fail deptrac. Putting it in `Shared` overloads a layer the constitution defines as a leaf of contracts, not an owner of domain models with their own admin CRUD. A dedicated module is the smallest arrangement that satisfies IV. |
| **2. Hadith and person *content* is `ar`/`en` only, not the mandated five locales** | Owner Decision D. The canonical sources carry Arabic and English only — there is no `fr`/`tr`/`ur` hadith text to import, and constitution IX forbids shipping a column with no data source. | Keeping five content locales means three permanently empty columns on 51,441 rows, and a locale negotiator that advertises languages the corpus cannot serve. **UI strings keep all five locales** — this narrows content only, and the constitution should be amended to draw that distinction explicitly. |
| **3. Hadith text stored in flat `text_ar`/`text_en` columns instead of Spatie Translatable JSON** | MySQL FULLTEXT cannot index inside a JSON column, and full-text search over Arabic and English is a core requirement (FR-028 – FR-031). | Spatie JSON everywhere would make search impossible without a duplicate shadow column per locale — strictly more storage and more drift risk than flat columns. Decision B confines this to fields MySQL must index; `bio`, `content`, and `intro` stay Spatie JSON as the constitution intends. |
| **4. Denormalised `collection_id` on `hadiths` (reachable via `book_id`)** | The hottest query path is collection-scoped browsing and `(collection, reference_key)` lookup; joining through books on every read is the cost being avoided. Bookless collections also need a direct parent. | Normalising forces a join on every public read. The denormalisation is made safe by composite FKs `(book_id, collection_id)` and `(chapter_id, book_id)`, which make a mismatched triple unstorable — this is the existing schema's reasoning (`2026_07_31_000005:9-19`) and it still holds. |
| **5. `search_ar`/`search_en` duplicate `text_ar`/`text_en` in normalised form** | Arabic search must match irrespective of tashkeel, tatweel, and letter-variant spelling. Normalising at query time is not SARGable and would defeat FULLTEXT entirely (constitution II). | Normalising on read means a full scan per search. A generated column cannot express the normalisation (it needs PHP-side Arabic folding), so a maintained column written at import and on every admin write is the only option that keeps search indexed. |

## Scope

**Full purge and rebuild from scratch. There is no upgrade path.** The old Hadith and person
migration *files* are deleted from the repository — not superseded by `DROP` migrations, not left in
place with a later teardown. The schema is authored fresh, and the database is rebuilt with
`migrate:fresh`. Nothing in the new migration set references, drops, alters, or is aware of a
previous table.

Consequences that follow directly and are not optional:

- **No `DROP TABLE` migration is written.** A table that no longer has a migration file does not
  exist after `migrate:fresh`. Writing teardown migrations would be writing an upgrade path.
- **No dependency-ordered teardown, no drop order to get right.** There is nothing to drop.
- **No data migration, no ID remapping, no preserved IDs, no compatibility adapters, no legacy
  routes, no redirects.**
- **`down()` methods are `dropIfExists` for the new tables only** — they roll back the new schema,
  not toward the old one.
- Canonical source files are the sole source of truth for content.

`muslims` becomes the application-wide identity for companions, narrators, scholars, graders,
compilers, and genealogy participants.

Because nothing is migrated, the entire "preserve existing column X" class of concern is out of
scope. What remains in scope is: (1) does the new schema still *support* the capabilities the site
offers, (2) can the importer actually produce it from the source files, and (3) what other code
fatals when the old tables no longer exist.

> **Flag — confirm before the first `migrate:fresh`.** `migrate:fresh` empties the whole database,
> not only the Hadith and person tables. Articles, categories, tags, media records, topics,
> favourites, users, and roles go with it. That is consistent with "purge and rebuild", and it is
> what makes Blocking 6 disappear. But if any editorial content in Blog/Articles or any real user
> account is worth keeping, it must be exported before the purge and re-imported after — the rebuild
> itself will not preserve it. Named here because it is the one irreversible consequence of the purge
> that lies outside the Hadith and person modules.

### Tables that do not exist in the new schema

No migration file creates these, so they are simply absent after the rebuild. There is no `sahaba`
table in the target schema — a companion is a row in `muslims` carrying the `sahabi` role.

| Gone | Its capability now lives in |
|------|----------------------------|
| `sahaba` | `muslims` + `muslims_roles` (`sahabi` role) |
| `sahabi_translations` | JSON `{ar,en}` columns on `muslims` |
| `sahabi_categories`, `sahabi_category` | **retired.** A category naming a genuine capacity becomes an `islamic_roles` row; thematic categories are not carried forward |
| `sahabi_battles` | **retired** — nothing replaces it |
| `sahabi_timeline_items`, `sahabi_timeline_item_translations` | **retired** — nothing replaces it |
| `relationships` (genealogy) | `muslims.father_id`/`mother_id` + `muslim_relationships` — see Genealogy below. Its FK already points at the removed `people` table, so it is orphaned today regardless. |
| `hadith_scholars` | `muslims` + `scholar` role |
| `hadith_gradings` | `hadiths.grade` + `hadiths.raw_grade` (decision A) |
| `hadith_texts` | `hadiths.text_ar` / `text_en` (decision D) |
| `hadith_import_runs` | console output + exit code (decision H) |

The migration files that create every table above are **deleted from the repository**, along with
every later patch migration against them (`2026_08_05_*`, the consolidation migrations, the
sahaba/genealogy series). Deleting the file is the whole mechanism — there is no drop order to
sequence, because `migrate:fresh` never creates these tables in the first place.

Migration files to delete: the `2026_07_31_0000*` Hadith series and its `2026_08_05_0000*` patches
(including the untracked `2026_08_05_000006_add_exact_hadith_reference_fields.php`), the
`2025_10_27_13*`/`14*` sahaba and relationship series, and the sahaba/genealogy migrations at
`2026_03_09_200003`, `2026_03_17_000004`, `2026_06_24_100000`, `2026_06_24_110000`,
`2026_07_02_09000*`, `2026_07_02_140001`, and `2026_07_31_000015`. Confirm the final list with
`git ls-files database/migrations` before deleting, and delete in one commit so the tree is never in
a half-purged state.

### Roles

`person_type`, `is_sahabi`, `is_companion`, and the scholar/narrator/grader/compiler distinctions all
collapse into `islamic_roles` rows assigned through `muslims_roles`. Seed at minimum: `sahabi`,
`narrator`, `scholar`, `grader`, `compiler`. A person holds as many as apply — a companion who
narrated hadith and graded them is one `muslims` row with three role assignments, not three records.

## Closed Decisions

Owner decisions, reaffirmed after the downsides were stated. Not open for re-litigation.

| # | Decision | Consequence accepted |
|---|----------|---------------------|
| A | **One nullable `grade` enum on `hadiths`.** No `hadith_gradings` claims table. | Loses per-grader attribution, isnad-vs-hadith scope, and part-specific claims. Requires an explicit projection rule — see Blocking 4. |
| B | **Hybrid text storage.** JSON `{ar,en}` for `bio`, `content`, `intro`. Flat columns only where MySQL needs an index or FULLTEXT. | Matches Spatie Translatable used project-wide; FULLTEXT cannot reach inside JSON. |
| C | **`reference_key`, unique per collection, is the public URL identity.** | URL is `{collectionSlug}/{referenceKey}`. `number` stays as the displayed scholarly number and may repeat. Requires a source contract — see Blocking 3. |
| D | **Text folded into `hadiths`.** No `hadith_texts` locale table. | Arabic and English only. FR/TR/UR retire; the API locale validator narrows to two. |
| E | **No external-source storage anywhere.** No identifier table, no `source_url`, `source_metadata`, `source_id`, or checksum. | One-time import, so provider attribution and change detection have no consumer. Scholarly citation strings are display data and stay. |
| F | **One collection key: `slug`.** Today's `code` column is dropped; every route segment reads `slug`. | Route regexes widen from `[a-z0-9_]+` to `[a-z0-9_-]+`. |
| G | **All `urn` routes are deleted**, not redirected. | `sunnah_urn` is an external identifier removed by E. |
| H | **No `hadith_import_runs` table.** | The import is a one-time rebuild run by an operator watching the output. Parity results, warnings, unmapped grades, and failures are reported to stdout and the log, not persisted. Nothing queries an import history afterwards. If a later phase adds recurring imports, the table comes back then. |

## Blocking — must be settled before any migration is written

1. **Runtime baseline is PHP 8.5 / Laravel 13, not 8.2 / 12.**
   `composer.json` requires `php: ^8.5`, `laravel/framework: ^13.0`, with `config.platform.php`
   pinned to `8.5.4`. CLAUDE.md's stated 8.2/12 is stale. Any runbook, CI image, or generated code
   targeting 8.2 fails dependency resolution before the rebuild begins. Fix CLAUDE.md as part of
   this work.

2. **`text_ar` must be nullable — 38 rows legitimately have no Arabic.**
   `config/islamic-data.php:90` records `'known_missing_arabic' => 38` with an explicit instruction
   not to invent Arabic text. A `NOT NULL text_ar` either drops those 38 rows or fabricates
   scripture. Correct shape:
   ```sql
   text_ar   LONGTEXT NULL,
   search_ar LONGTEXT NULL,
   CONSTRAINT chk_hadiths_publishable
     CHECK (is_published = 0 OR (text_ar IS NOT NULL AND search_ar IS NOT NULL))
   ```
   Import those rows unpublished, and carry the known-missing count into the parity assertion.

3. **`reference_key` needs a source contract before it can be called idempotent.**
   Displayed numbers cannot identify a row: `2026_07_31_000011` records 56 Tirmidhi records lost
   when uniqueness was assumed on `hadith_number`, and `2026_07_31_000012` records that Malik needs
   source book *position* to separate otherwise identical records. `ReferenceBuilder` composes
   display citations; it does not derive identity. **Requirement:** every normalized canonical
   record carries an explicit, immutable `reference_key` produced by the source-normalization step,
   and uniqueness is validated across the whole corpus *before* any table is created. Until that
   step exists, `(collection_id, reference_key)` is an assumption, not a guarantee.

4. **The single `grade` needs a deterministic projection rule, written down.**
   Source strings assert multiple scoped verdicts (`"Sanad Da'if wal-Hadith Hasan"`), and the source
   may carry different Arabic and English raw strings for one record. Adopt the existing, already
   deterministic rule so repeated imports agree:
   - Consider only unlabelled, hadith-scoped verdicts.
   - Exactly one distinct verdict → that verdict. Conflict, isnad-only, part-specific, or unmapped →
     `NULL`.
   - `raw_grade` precedence: English if present, else Arabic; warn when the two disagree.
   - `authenticated_by` produces **no** grade. It names the collection's compiler — membership, not
     a verdict. Treating it as one previously generated ~18k bogus rows.
   `GradeNormalizer` survives as an import-time projector and unmapped-grade reporter, not a
   persistence layer.

5. **Code outside the Hadith and person modules queries the tables being deleted and will fatal.**
   Not data — code. Each needs rewriting or deleting in the same rebuild:
   `app/Providers/FavoriteServiceProvider.php` (eager-loads `texts`, `book`, `collection`; builds
   number-based URLs), `app/Domain/Topics/Repositories/TopicRepository.php`,
   `app/Domain/Shared/Search/SearchQueryBuilder.php`,
   `app/Console/Commands/Portal/ExportPortalDataCommand.php` and `VerifyPortalDataCommand.php`
   (hardcoded old table lists), `app/Models/ArticleLocale.php` (embeds `hadith_id` / `sahabi_id`),
   and `deptrac.yaml` (has no layer owning `Muslim`).

6. ~~**Dependent rows in surviving modules point at IDs that will not exist.**~~
   **Dissolved by the full purge.** `topic_items`, `favorites`, and `user_interactions` store raw
   `itemable_id` values with no FK, and `ArticleLocale` embeds numeric IDs in content blocks — all of
   which would have gone dangling under an in-place cutover. `migrate:fresh` empties those tables
   too, so there is nothing to clean and nothing to assert.
   **What survives of this finding:** if any content is exported before the purge and re-imported
   after (see the Scope flag), the re-import **must not** carry old Hadith or Sahabi IDs. A reused ID
   silently points at the *wrong* new record, which is worse than a dangling one. Re-imported content
   is matched on `reference_key` and `slug`, never on a numeric ID.

7. ~~**The cutover cannot be one atomic transaction.**~~
   **Dissolved by the full purge.** The concern was MySQL's implicit `COMMIT` on `CREATE`, `ALTER`,
   `DROP`, and `RENAME` (documented in this repo at
   `2026_06_24_100000_consolidate_genealogy_into_sahaba.php:34-39`), which makes an in-place
   transition unrollbackable midway. There is no in-place transition: the database is built from an
   empty state, so a failed build is recovered by fixing the migration and running `migrate:fresh`
   again. The rollback is "run it again", not a snapshot.
   **What survives of this finding:** the *content import* still runs against a live schema and is
   still per-collection transactional, so a failed collection leaves no partial rows. And the Scope
   flag stands — anything worth keeping is exported **before** the first `migrate:fresh`, because
   after it there is nothing to export.

## Target Schema

### Muslim identity

**`islamic_roles`** — consider naming it `historical_roles`; `islamic_roles` reads as a Spatie
Permission authorization table.
- `id`, `slug varchar(100) UNIQUE`, `name_ar varchar(150)`, `name_en varchar(150)`, timestamps.

**`muslims`**
- `id`, `thumb varchar(255) nullable`, `slug varchar(200) UNIQUE`.
- `name_ar varchar(200) nullable`, `name_en varchar(200) nullable`, with
  `CHECK (name_ar IS NOT NULL OR name_en IS NOT NULL)` — source records legitimately carry only one.
- `kunya json {ar,en} nullable`, `bio json {ar,en} nullable`, `content json {ar,en} nullable`.
- `gender` nullable, PHP-enum backed.
- `birth_place json {ar,en} nullable`.
- `birth_year_hijri int SIGNED nullable`, `death_year_hijri int SIGNED nullable` — signed, pre-Hijra
  years are negative.
- `generation varchar(50) nullable`.
- `search_name_ar varchar(255) nullable`, `search_name_en varchar(255) nullable` — normalized, built
  from **every** retained name field (name + kunya), regenerated on admin write, not only at import.
- `is_published boolean default false`, timestamps.
- Index `(is_published, id)`; separate FULLTEXT on each search-name column.
- Genealogy columns — see Genealogy below: `father_id`, `mother_id`, `tree_display_order`,
  `hide_from_tree`.

**`muslims_roles`**
- `muslim_id`, `role_id`, `is_featured boolean default false`, `sort_order unsigned int default 0`,
  timestamps. Composite PK `(muslim_id, role_id)`.
- Index `(role_id, is_featured, sort_order, muslim_id)`.

Companion (`sahabi`), narrator, grader, scholar, and compiler are **roles**, never separate person
tables. See "Roles" under Scope.

### Genealogy

The tree is built entirely from `muslims`. Any person in the table can be selected as a parent, as a
relation, or as the root a tree is rendered from — there is no separate person table and no
"genealogy person" concept.

**Descent lives on `muslims`** — two nullable self-referencing FKs, not rows in a relationship table:

- `father_id`, `mother_id` — nullable FK → `muslims`, **restrict on delete** (deleting someone who is
  a parent must fail loudly, not silently orphan a subtree).
- `tree_display_order unsigned int nullable` — sibling ordering within one parent.
- `hide_from_tree boolean default false` — present in the data, suppressed in the rendering.
- Index `(father_id)` and `(mother_id)` — these are the descendant-lookup paths.

This is the existing design's rule, kept deliberately: *parent, child, and grandparent relations are
derived from `father_id`/`mother_id` and are never duplicated as relationship rows.* One father, one
mother, enforced by the column itself rather than by a constraint on a table of edges.

**`muslim_relationships`** holds only the edges descent cannot express:

- `id`, `muslim_id` FK → `muslims` cascade, `related_muslim_id` FK → `muslims` cascade.
- `relationship_type` enum: `spouse`, `sibling`, `uncle`, `aunt`, `cousin`.
- `notes text nullable`, `sort_order unsigned int default 0`,
  `hide_from_tree boolean default false`, timestamps.
- Unique `(muslim_id, related_muslim_id, relationship_type)`.
- `CHECK (muslim_id <> related_muslim_id)` — nobody is their own sibling.
- Index `(muslim_id, relationship_type)` and `(related_muslim_id)`.
- Every type here is **symmetric**, so a relationship is stored **once**, in one direction, and read
  with `muslim_id = ? OR related_muslim_id = ?`. Storing both directions doubles the rows and lets
  the pair drift out of sync. (`uncle`/`aunt` are symmetric in the sense that the reverse edge —
  nephew/niece — is implied by the same row read from the other side; the reading layer labels it.)

**Tree queries** use a MySQL 8 recursive CTE rooted at a selected `muslims.slug` — ancestors by
walking `father_id`/`mother_id` upward, descendants by walking the `(father_id)`/`(mother_id)`
indexes downward. The CTE carries a **depth cap** so a bad edge cannot run away.

**Cycle prevention is a service concern, not a constraint.** No CHECK can express "this person is not
already my ancestor". Before writing `father_id` or `mother_id`, the domain service walks ancestors
and rejects the assignment if the target appears; the recursive CTE keeps its depth cap as the
backstop. Both paths need a test.

**Admin selection.** Father, mother, and each relationship target are chosen through a searchable
`muslims` picker that queries `search_name_ar`/`search_name_en`, excludes the record being edited,
and **includes unpublished people** — an unpublished person is still a valid ancestor, and the plan
already treats Muslim identity as usable internally while the profile stays hidden. The tree render
is what respects publication and `hide_from_tree`, not the picker.

### Hadith hierarchy

**`hadith_collections`**
- `id`, `thumb varchar(255) nullable`, `slug varchar(200) UNIQUE`.
- `reference_template varchar(255) nullable` — **load-bearing.** `ReferenceBuilder` substitutes the
  number into a placeholder (`"Sahih al-Bukhari {hadithNumber}"`,
  `"Book {bookNumber}, Hadith {hadithNumber}"`) because collections print it mid-line. Without this
  there is no canonical citation line.
- `title_ar varchar(200)`, `title_en varchar(200)`.
- `intro_ar mediumtext nullable`, `intro_en mediumtext nullable`.
- `compiler_id` nullable FK → `muslims`, restrict on delete.
- `has_books boolean`, `has_chapters boolean`, `sort_order unsigned int default 0`,
  `is_featured boolean default false`, `is_published boolean default false`, timestamps.
- `CHECK (has_chapters = 0 OR has_books = 1)`.
- Index `(is_published, sort_order, id)` and `(is_published, is_featured, sort_order, id)`.
- `total_hadith unsigned int nullable` — the **source-declared** total for the collection, not a row
  count. Set at import, admin-editable, never recomputed. The readable count stays computed and is
  exposed separately as `available_hadiths_count` (Owner Decision 1, `research.md` R11).

**`hadith_books`**
- `id`, `collection_id` FK restrict.
- `book_number varchar(50)` — string; source numbering is not reliably numeric.
- `title_ar varchar(500)`, `title_en varchar(500) nullable`, `intro json {ar,en} nullable`.
- `first_hadith_number varchar(50) nullable`, `last_hadith_number varchar(50) nullable`.
- `sort_order unsigned int`, `is_published boolean default false`, timestamps.
- Unique `(collection_id, book_number)`, `(collection_id, sort_order)`, `(id, collection_id)`.
- Index `(collection_id, is_published, sort_order)`.
- No `is_synthetic` — nullable `hadiths.book_id` replaces synthetic books, which means the bookless
  browse path in `HadithBrowseService` must be rewritten rather than ported.

**`hadith_chapters`**
- `id`, `book_id` FK restrict.
- `chapter_number varchar(50)` — **not unique**; real books repeat chapter numbers.
- `title_ar varchar(500)`, `title_en varchar(500) nullable`, `intro json {ar,en} nullable`,
  `ending json {ar,en} nullable`.
- `sort_order unsigned int`, `is_published boolean default false`, timestamps.
- Unique `(book_id, sort_order)`, `(id, book_id)`; index `(book_id, is_published, sort_order)`.
- No `(book_id, chapter_number)` index — chapter browsing is by `book_id` ordered on `sort_order`.
  Add it only if a chapter-number lookup ships.

### `hadiths`

- `id`, `collection_id NOT NULL`, `book_id NULL`, `chapter_id NULL`.
- `reference_key varchar(100) NOT NULL` — public identity (Blocking 3).
- `number varchar(50)` — displayed scholarly number, duplicates allowed.
- `in_book_number varchar(50) nullable`, `sort_order unsigned int NOT NULL` (collection-scoped).
- No `volume_number` — volume navigation is retired (Owner Decision 2).
- `grade` nullable enum `sahih|hasan|daif|mawdu`; `raw_grade text nullable` (verbatim source string,
  precedence per Blocking 4).
- `text_ar longtext NULL`, `text_en longtext NULL`, `search_ar longtext NULL`,
  `search_en longtext NULL` — nullable per Blocking 2, gated by the publishable CHECK.
- `narrated_by text nullable` — TEXT, not varchar: observed up to 2,806 characters.
  No `sanad`, `matn`, or `translator` — retired with their admin inputs and API keys
  (Owner Decision 2). The chain survives as `hadith_narrators` rows, not as free text.
- `canonical_reference varchar(255) nullable`, `in_book_reference varchar(255) nullable`,
  `translation_reference varchar(255) nullable` — stored once at import from the canonical files,
  with `reference_template` as the canonical-line fallback. (Today these are *rebuilt on read* so a
  corrected book number cannot disagree with a stale line; storing them accepts that risk in
  exchange for deleting provenance.)
- `is_published boolean default false`, timestamps.
- Unique `(collection_id, reference_key)`, `(collection_id, sort_order)`.
- Index `(collection_id, number)` — scholarly-reference lookup, non-unique.
- Index `(collection_id, is_published, sort_order)` — bookless/direct collection browse.
- Index `(book_id, is_published, sort_order)`, `(chapter_id, is_published, sort_order)`.
- Index `(is_published, grade, id)` **and** `(is_published, id)` — the latter is what random
  selection uses; do not drop it without an `EXPLAIN` proving the composite serves it.
- Separate FULLTEXT on `search_ar` and on `search_en`.
- Composite integrity — declare child indexes explicitly rather than relying on InnoDB:
  ```sql
  KEY ix_hadiths_book_collection  (book_id, collection_id),
  KEY ix_hadiths_chapter_book     (chapter_id, book_id),
  CONSTRAINT fk_hadiths_book_collection FOREIGN KEY (book_id, collection_id)
      REFERENCES hadith_books (id, collection_id) ON DELETE RESTRICT,
  CONSTRAINT fk_hadiths_chapter_book FOREIGN KEY (chapter_id, book_id)
      REFERENCES hadith_chapters (id, book_id) ON DELETE RESTRICT,
  CONSTRAINT chk_hadiths_chapter_requires_book
      CHECK (chapter_id IS NULL OR book_id IS NOT NULL)
  ```
  A nullable component skips the composite FK check, which is correct for genuinely bookless rows;
  the CHECK closes the chapter-without-book case.

### `hadith_narrators`

- `hadith_id`, `narrator_id` FK → `muslims`, `position UNSIGNED SMALLINT` (not tinyint — the current
  pivot needs the width), `is_primary`, `source_name_ar varchar(255) nullable`,
  `source_name_en varchar(255) nullable`, timestamps.
- PK `(hadith_id, position)`; index `(narrator_id, is_primary, hadith_id)` for primary-narrator
  profiles.
- **Do not** add `UNIQUE(hadith_id, narrator_id)` unless corpus validation proves a narrator never
  recurs in a chain. Validate before constraining.
- One primary narrator per hadith, via generated column (valid in MySQL 8; a unique index permits
  multiple NULLs):
  ```sql
  is_primary TINYINT(1) NOT NULL DEFAULT 0,
  primary_hadith_id BIGINT UNSIGNED
      GENERATED ALWAYS AS (CASE WHEN is_primary = 1 THEN hadith_id ELSE NULL END) STORED,
  UNIQUE KEY uq_hadith_narrators_one_primary (primary_hadith_id)
  ```
  The domain service validates transactionally as well.

### Import reporting (no table)

The importer emits per-collection parity to stdout and the log: expected vs actual counts per entity
(collections, books, chapters, hadiths, narrator links, muslims), unmapped grades, unresolved people,
and the known-missing-Arabic tally. A non-zero exit code on any mismatch is what gates the operator
from proceeding — there is no persisted run history to consult.

## Naming

`primary`, `order`, `from`, and `to` are reserved words in MySQL 8 and are renamed to `is_primary`,
`sort_order`, `first_hadith_number`, `last_hadith_number`. `grade` is **not** reserved and stays.
`auther_id` → `compiler_id` (typo, and "compiler" is the correct Hadith role). `has_chapter` →
`has_chapters`.

## Application and Interface Changes

- Delete and rewrite: Hadith domain models, repositories, services, import commands, admin
  controllers, API resources, Blade pages, factories, tests. Same for `Sahabi`, `hadith_scholars`,
  and Genealogy `Person` → `Muslim`.
- Public URLs and API lookups: `{collectionSlug}/{referenceKey}`. Every `{code}` route segment
  becomes `{slug}` with regex `[a-z0-9_-]+` (`routes/api.php:76-88`, `routes/web.php:80-86`). The two
  `urn` routes (`routes/api.php:73`, `routes/web.php:76`) are deleted with their actions, tests, and
  Blade `route()` callers.
- Provide an **endpoint-by-endpoint replacement matrix** for the ten current API endpoints
  (`routes/api.php:60-89`) and the public web routes (`routes/web.php:70-91`) — "capabilities are
  preserved" is not executable.
- API locale negotiation narrows from five locales to `ar`/`en`
  (`app/Http/Controllers/Api/HadithController.php:32-42`).
- Muslim directory and profile endpoints filter by role, featured, gender, publication. Narrator
  hadith listings hang off the Muslim profile. Add `(gender, is_published, id)` if the gender
  directory ships.
- **Retired surface, deleted not redirected** (Owner Decisions 2, 4, 6): the Sahaba battle, timeline,
  and category routes and blocks (`app/Http/Controllers/Site/SahabaController.php`,
  `routes/api.php:123-135`); person image galleries and their conversions; and the `sanad`, `matn`,
  `translator`, and `volume_number` admin fields, API keys, and Blade output, including volume
  navigation. Their actions, resources, views, factories, and tests go with them. The replacement
  matrix must list each retired endpoint as *deleted*, not leave it unmentioned.
- Public visibility requires the hadith and every applicable ancestor published, applied consistently
  in detail, browse, random, search, topics, and favorites.
- The readable hadith count is computed, not stored; `total_hadith` is a separate stored field holding the book's own declared total (Owner Decision 1). Pagination capped, relation sets
  eager-loaded as fixed lists, no N+1 on any read path.
- Arabic normalization strips tashkeel and tatweel and folds letter variants. MySQL needs
  `innodb_ft_min_token_size = 2`; the ngram parser must **not** be used (CJK-oriented, wrong for
  Arabic). Bilingual search issues separate `MATCH(search_ar)` and `MATCH(search_en)` branches and
  merges scores — never `MATCH(search_ar, search_en)` without a matching composite FULLTEXT index.
- The grading admin screen becomes single-grade assignment; the public grade block loses scholar,
  scope, and part labels. Retain optimistic locking.
- The live cross-module Topics aggregation (`routes/web.php:132`) keeps its Hadith adapter, rewritten.
  Do **not** build Hadith theme or grading-level tables — the grading-level table is archive-only
  (`2026_07_31_000000:34-55`); Topics is the live theme capability.

## Rebuild and Test Plan

Build from empty. There is no cutover runbook because there is nothing to cut over from:

1. Export anything worth keeping that lives outside these modules (see the Scope flag). This is the
   last moment it exists.
2. Delete the old migration files and the old Hadith/Sahabi/Genealogy code in one commit.
3. Author the new migration set. Nothing in it drops, alters, or references a previous table.
4. `migrate:fresh` — the database is built from empty. A failure here is fixed in the migration and
   re-run; that is the entire rollback story.
5. Seed `islamic_roles`.
6. Validate `reference_key` uniqueness across the whole corpus **before** importing anything
   (Blocking 3). A duplicate found here costs a re-run; found later it costs the import.
7. Import Muslims, then Hadith per collection, transactional per collection.
8. Assert parity: per-entity expected vs produced counts, the 38 known-missing-Arabic rows, unmapped
   grades, unresolved people, discarded categories. Non-zero exit on any mismatch.
9. Re-import anything exported at step 1, matched on `reference_key`/`slug` and never on a numeric
   ID.
10. Publish, clear caches.

Steps 4–8 are repeatable end to end. That repeatability replaces the snapshot: correctness is proven
by running the whole thing again and getting the same corpus, not by keeping a way back.

Tests run on MySQL already (`phpunit.xml`: `DB_CONNECTION=mysql`), so CHECK constraints, generated
columns, composite FKs, and FULLTEXT are all genuinely assertable.

- **Schema:** composite FK mismatch rejected, restricted deletion, bookless hadith accepted,
  chapter-without-book rejected, repeated hadith and chapter numbers accepted, `reference_key`
  unique per collection, single-primary-narrator enforced, publishable CHECK blocks publishing a
  row with no Arabic.
- **Grade projection:** isnad + hadith claim → hadith wins; numbered parts → null; conflicting →
  null; unmapped → null with `raw_grade` retained; English/Arabic raw precedence; `authenticated_by`
  produces nothing.
- **Import:** idempotent on `reference_key`, per-collection rollback, missing English text, missing
  Arabic (exactly 38), duplicate displayed numbers, unresolved people, row-count parity.
- **Genealogy:** self-parent rejected, direct cycle rejected (A→B→A), deep cycle rejected via the
  ancestor walk, recursive CTE honors its depth cap, deleting a parent is restricted, sibling
  ordering by `tree_display_order`, `hide_from_tree` and unpublished ancestors suppressed in the
  render but still selectable in the admin picker, symmetric relationship readable from both sides
  with one stored row.
- **Search:** Arabic with and without diacritics, two-character terms, English search, collection and
  grade filters, unpublished ancestors excluded, relevance ordering, pagination caps. Use
  committed/truncated fixtures — InnoDB FULLTEXT is not transaction-visible in the current test
  arrangement.
- **API/web/admin:** new route keys and resource shapes, deleted `urn` routes assert 404, publication
  gating, narrator profiles, bilingual fallback, CRUD validation, fixed query counts.
- **Retired surface:** every deleted Sahaba route (battle, timeline, category, featured, early
  converts, top narrators, status, tribe) asserts 404; no API resource emits `sanad`, `matn`,
  `translator`, `volume_number`, or a gallery key; the category-to-role import reports which
  categories it mapped and which it discarded.
- Full PHPUnit suite, deptrac (add a layer owning `Muslim`), migrate-from-empty, and `EXPLAIN`
  assertions on browse, search, and random.

## Remaining Owner Decisions

1. ~~`total_hadith`.~~ **Resolved — kept, as the source-declared total.** It stores what the
   collection contains *according to the book*, not a count of our rows. My earlier objection was
   about a cached row count going stale on publish/unpublish; that does not apply here, because this
   number is a fact about the book that our rows cannot produce (we may hold fewer). Set at import,
   admin-editable, never recomputed from rows. The count of what a reader can actually open remains
   computed via `withCount`, exposed separately as `available_hadiths_count` so the two can never be
   confused. See `research.md` R11.
2. ~~`sanad`, `matn`, `translator`, `volume_number`.~~ **Resolved — all four retired.** Deleted from
   `hadiths` along with their admin form fields, API resource keys, and Blade output. Volume-based
   navigation is withdrawn. `narrated_by` is kept as TEXT; the chain of narration lives in
   `hadith_narrators` as ordered rows, which is where it was always better represented.
3. ~~Genealogy depth.~~ **Resolved** — see Genealogy. Descent on `muslims`, non-descent edges in
   `muslim_relationships`, any muslim selectable as parent, relation, or tree root.
4. ~~Media.~~ **Resolved — scalar `thumb` only.** `Muslim` does **not** implement Spatie `HasMedia`.
   Companion galleries and their conversions are not carried forward; profile layouts rendering a
   gallery are reworked to the single portrait.
5. **Soft deletes.** Still open. Not specified on `muslims`. Permanent deletion, or `deleted_at` with
   admin restore? The spec assumes reversible deletion (`spec.md` → Assumptions), because a person is
   referenced by hadiths, collections, and the tree; confirm or override.
6. ~~Sahabi satellite capabilities.~~ **Resolved — all three retired.** Battles, per-person
   timelines, and companion categories are deleted with their tables, and no replacement is built.
   Explicitly in scope for deletion, not merely absent:
   - `app/Http/Controllers/Site/SahabaController.php` and its Blade views — the battle filter, the
     "participated in" block, the timeline block, and category browsing.
   - `routes/api.php:123-135` — the featured, early-converts, top-narrators, category, status,
     tribe, and battle routes, together with their actions, resources, and tests. Deleted, not
     redirected; a request to a retired address returns 404.
   - A category that names a genuine capacity (e.g. a narrator or scribe grouping) is seeded as an
     `islamic_roles` row during the import. A category that is a thematic label is dropped. The
     import reports which categories it mapped and which it discarded, so the discard is visible
     rather than silent.
   Role directories are the only surviving way to group people.
