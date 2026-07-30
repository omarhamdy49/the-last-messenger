# Implementation Plan: Hadith Module Rebuild

**Branch**: `001-hadith-rebuild` | **Date**: 2026-07-30 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `specs/001-hadith-rebuild/spec.md`

## Summary

Delete the 125-file CQRS Hadith module and its 22-table schema; rebuild as an 8-table schema and a
~38-file Service + Repository module mirroring `app/Domain/Seerah/`. Populate it by extracting
sunnah.com's 607 book pages through the Chrome extension into JSON on disk, then importing those files
with Artisan. Rebuild the public pages from the existing prototypes and the admin panel with full CRUD.

## Technical Context

**Language/Version**: PHP 8.2+
**Primary Dependencies**: Laravel 12, spatie/laravel-translatable ^6.11, spatie/laravel-permission ^6.21,
spatie/laravel-medialibrary ^11.17, laravel/sanctum ^4.2
**Storage**: MySQL 8.0.46 (Docker, host port 3308, db `the_last_messenger`); Redis available for cache
**Testing**: PHPUnit 11 against MySQL (`the_last_messenger_test`, configured in `phpunit.xml`)
**Target Platform**: Linux server (VPS), served over HTTP; Blade-rendered pages, no SPA
**Project Type**: Web application — single Laravel app serving public site, admin panel, and API
**Performance Goals**: <200 ms API response, <2 s page load; constant query count per page regardless of
result count
**Constraints**: Arabic-first with full RTL, 5 locales (ar default+fallback, en, fr, tr, ur); MySQL
FULLTEXT only — no new search infrastructure; no Vite dev server, so `npm run build` is mandatory after
Blade changes
**Scale/Scope**: ~51,400 hadiths across 17 collections, 607 books; 8 tables, ~38 module files, ~24 admin
views, 5 public pages, 10 API endpoints

### Data source

sunnah.com sits behind Cloudflare and returns **403 to non-browser clients**. Extraction therefore runs
**agent-driven through the Chrome extension**, not from an Artisan command — an Artisan process is
server-side and cannot drive the user's browser. Extraction writes JSON to
`storage/app/hadith_source/<collection>/book_<n>.json`; Artisan validates and imports those files.
Extraction and import are separate steps so a parsing fix never requires re-fetching.

`/<collection>/<bookNumber>` renders an entire book, so the fetch volume is **607 requests**, not 51,441.
Throttled at roughly one per second, that is about ten minutes.

Official API access is gated by request (`sunnah-com/api` →
`.github/ISSUE_TEMPLATE/request-for-api-access.md`) and would remove this pipeline entirely. Worth
filing in parallel.

## Constitution Check

*GATE: checked before task generation.*

| Principle | Compliance |
|---|---|
| I. Layered data flow | Controller → Service → RepositoryInterface → Eloquent. 3 repository pairs; transactions inside repositories |
| II. Query discipline | Explicit `select()` everywhere; `withCount` for counters instead of stored columns; three publication-aware composite indexes; **no speculative indexes** — 17-row collection table gets none |
| III. Typed boundaries | 4 `final readonly` DTOs; enums for grade, scope, import status; explicit `$fillable` |
| IV. Module isolation | `sahaba_id` is a DB-level FK only; no PHP import of `Domain\Sahaba`. Deptrac baseline entries for this module are removed, not carried |
| V. Arabic-first | One text row per locale; Arabic fallback; logical properties only; Amiri for hadith text |
| VI. Tests | Schema-constraint tests land **before** any import (Phase 2d). MySQL test connection means FULLTEXT and composite FKs are directly tested |
| VII. Security | FormRequest + authorize on every admin action; `throttle:search`; page size capped |
| VIII. Destructive ops | `.env.testing` + fail-closed database allowlist precede all deletion; rollback by explicit migration name |
| IX. Simplicity | 22 → 8 tables; 125 → ~38 files; CQRS dropped; 4 tables dropped for having **no data source** |
| X. Honest reporting | Per-collection reconciliation with itemised skips; `unmapped_grades` surfaced in the admin |

### Complexity Tracking

Deviations from the constitution or from the simplest possible design, with justification:

| Deviation | Why needed | Simpler alternative rejected because |
|---|---|---|
| `Domain/Hadith` uses Service + Repository while `Domain/Quran` retains CQRS | Root `CLAUDE.md` §2 prescribes CQRS for both. 24 handlers that each make one delegating call is pure overhead for a read-heavy catalogue | Keeping CQRS would add ~45 scaffolding files with no behavioural benefit. `CLAUDE.md` §2 is amended instead |
| `collection_id` denormalised onto `hadith_hadiths` though reachable via `book_id` | The canonical lookup `(collection, hadith_number)` and every collection-scoped list would otherwise join through books | Accepting the join was viable, but composite FKs make the denormalisation safe, and this is the hottest query path |
| Full admin CRUD on the hierarchy | Chosen by the product owner | Restricting books/chapters to publication-only was recommended in review — canonical URLs depend on that numbering — and was explicitly overruled |
| Extraction is agent-driven rather than a repeatable server-side job | Cloudflare 403s non-browser clients | A server-side fetcher does not work at all; an API key would replace this and is being requested in parallel |
| Public API tests omit the 401 case that Constitution VI mandates | All ten endpoints in `contracts/hadith-api.md` are unauthenticated reads, so there is no credential to omit and no 401 to assert. The 401 requirement is satisfied by the admin routes, which are tested in US4 (T143) | Adding Sanctum to public read endpoints purely to make a 401 assertable would be security theatre and would break the third-party access the story exists to enable |

## Project Structure

### Documentation (this feature)

```
specs/001-hadith-rebuild/
├── spec.md            # WHAT and WHY — 6 user stories, 25 functional requirements
├── plan.md            # this file
├── data-model.md      # 8 tables: columns, keys, indexes, invariants
├── research.md        # sunnah.com analysis, measured field fill rates, the two grading traps
├── contracts/
│   └── hadith-api.md  # 10 read-only endpoints
└── tasks.md           # generated by /speckit.tasks
```

### Source Code (repository root)

```
app/Domain/Hadith/
├── Enums/           HadithGrade, GradeScope, ImportStatus
├── Models/          HadithCollection, HadithBook, HadithChapter, Hadith,
│                    HadithText, HadithGrading, HadithScholar, HadithImportRun
├── DTOs/            HadithListItem, HadithDetail, ReferenceSet, SearchCriteria
├── Repositories/    CollectionRepository{,Interface}   ← owns book + chapter reads
│                    HadithRepository{,Interface}
│                    ScholarRepository{,Interface}
├── Services/        HadithQueryService, HadithAdminService, ReferenceBuilder,
│                    HadithSearchService, ArabicTextNormalizer, HadithVisibility,
│                    HadithCacheKeys, HadithDashboardStatsProvider,
│                    HadithExportAdapter, HadithBulkHandler
└── Import/          SourceFileReader, HadithRecordMapper, GradeNormalizer, HadithImporter

app/Http/Controllers/Admin/Hadith/     5 controllers (collection, book, chapter, hadith, scholar)
app/Http/Controllers/Api/HadithController.php
app/Http/Controllers/Site/HadithController.php
app/Http/Requests/Admin/Hadith/        10 FormRequests (store + update per resource)
app/Http/Resources/                    Hadith*, HadithCollection*, HadithBook*, HadithChapter*
app/Console/Commands/                  HadithImportCommand

database/migrations/                   8 fresh migrations
resources/views/public/hadith/         5 pages ported from the-last-messenger-ui prototypes
resources/views/admin/hadith/          ~24 views
resources/lang/{ar,en,fr,tr,ur}/hadith.php
routes/admin/hadith.php
tests/Feature/{Api,Web,Admin}/Hadith/  · tests/Unit/Hadith/
```

**Structure Decision**: Web application, single Laravel app. Follows the existing DDD layout under
`app/Domain/`, using `app/Domain/Seerah/` (36 files, Service + Repository) as the structural reference
rather than the outgoing CQRS shape.

## Phasing intent for task generation

Tasks must be organised so each user story ships independently. Two phases block everything:

1. **Setup** — `.env.testing`, the fail-closed database allowlist, and the spec-kit artifacts. These
   exist because a mis-targeted `migrate:fresh` destroyed the dev database earlier in this work; they
   must precede any destructive step.
2. **Foundational** — in strict order: *(a)* rewire all 22 external consumers of the old module,
   *(b)* only then delete it, *(c)* create the 8 fresh migrations, *(d)* build the domain core plus its
   schema-constraint tests. Deleting before rewiring leaves the application unable to boot;
   `app/Providers/AppServiceProvider.php` imports three Hadith services directly.

US1 then delivers the MVP by extracting and importing **one book** rather than the whole corpus, proving
the pipeline end to end before scale. US2–US4 can proceed in parallel once US1 lands. US5 is the bulk
import. US6 and polish follow.

### Known traps that tasks must encode

- **Grade strings are free text** with 130+ distinct values in a 3,000-record sample — casing variants,
  Arabic, transliteration, publisher citations, parse artifacts, multi-verdict strings, and prose. The
  previous importer turned these into 566 junk taxonomy rows.
- **Provenance is not a grading.** The `authenticated_by` field names the compiler of the collection the
  hadith already belongs to; treating it as a scholarly verdict previously produced 9,379 + 8,863 bogus
  rows.
- **MySQL FULLTEXT requires the `MATCH()` column list to match an index exactly**, so a single
  search-optimised column is indexed rather than a composite of body and normalised body — indexing both
  would also index Arabic twice and distort relevance.
- **`innodb_ft_min_token_size` defaults to 3**, which silently makes two-character Arabic tokens
  unindexable. It must be set to 2, with a restart and index rebuild, consistently across environments.
- **One collection has no books** but the hierarchy requires one — a hidden placeholder is needed.
- **Roughly 35% of records have no permalink identifier**, so it can never be an identity column.
- **7% of records have no English text**, so a text row is omitted rather than written empty.
