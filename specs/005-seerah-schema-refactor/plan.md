# Implementation Plan: Seerah Module & Schema Refactor

**Branch**: `005-seerah-schema-refactor` | **Date**: 2026-09-17 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/005-seerah-schema-refactor/spec.md`

## Summary

Bring the Seerah module to the owner's seven-table shape by rewriting its
migrations in place (25 files touching seerah → 3 that define it), then adapting
every consumer traced in the spec: the Seerah domain, three admin CRUD areas, five
public pages, the `v1.seerah.*` API, and ~20 cross-module touchpoints. Participants,
timeline items, lessons, importance/phase/pivotal/featured, view counting and the
related-article link are deleted outright. Event gallery and media mirror the
article tables; media items are resolved through a new Shared lookup contract
because Seerah may not import `App\Models`. No data is converted — environments are
rebuilt on the new shape by a scoped, backed-up drop (never `migrate:fresh`).

## Technical Context

**Language/Version**: PHP 8.5 (platform pinned 8.5.4)
**Primary Dependencies**: Laravel 13, spatie/laravel-permission, maatwebsite/excel, Blade + Alpine 3 + Tailwind. **No new package.**
**Storage**: MySQL 8 (InnoDB, utf8mb4); public disk for thumb/cover/gallery files
**Testing**: PHPUnit via `php artisan test` against MySQL `the_last_messenger_test` (RefreshDatabase — this is what exercises the rewritten migrations)
**Target Platform**: Linux VPS (prod), macOS dev; same Laravel app serves admin + public site + API
**Project Type**: web (single Laravel application, DDD modules under `app/Domain`)
**Performance Goals**: public Seerah pages no slower than today; list endpoints stay at a fixed query count (existing `NoNPlusOneQueriesTest` guards this)
**Constraints**: no new migration files; deptrac must pass with no new baseline entries; five locales, Arabic default, RTL-first; `npm run build` after Blade changes
**Scale/Scope**: 7 tables; ~36 Seerah domain files, 6 controllers, 6 FormRequests, 2 Resources, ~18 Blade views, 5 lang files × 5 locales, ~20 test files, 8 seeders, 25 migration files touched (13 deleted)

## Constitution Check

*GATE: evaluated before Phase 0 and again after Phase 1 — both PASS with one tracked item.*

| Principle | Status | How this plan complies |
|---|---|---|
| I. Layered data flow | PASS | Controllers keep one service call. Gallery/media sync and image storage move out of `SeerahEventController` (today it has 75 lines of path handling) into `SeerahEventService` → repository; multi-step writes transact inside `SeerahEventRepository`. |
| II. Query discipline | PASS | Every rewritten list query keeps an explicit select incl. FKs (`event_type`, `sm_id`, `location_id`); gallery/media load on `show` only; `MediaLibraryLookup::findMany` is one `whereIn`; Gregorian-year filter is a range, not `YEAR()`; indexes limited to R10 and each `EXPLAIN`-checked. |
| III. Typed boundaries | PASS | `CreateSeerahEventData` / `UpdateSeerahEventData` rewritten as `final readonly`; milestone and type payloads get the same; `MediaReference` is a readonly value object; status/era are enums. |
| IV. Module isolation | PASS | Net improvement: the `SeerahEvent → App\Models\Article` baseline entry is removed. Media goes through a Shared contract; no new baseline entry. |
| V. Arabic-first | PASS* | All new strings keyed in all five `seerah.php` files; removed keys deleted from all five; logical properties only. *Constitution text says "Spatie JSON columns"; the project guide and codebase use translation tables — plan follows the guide (research R11). Event-type `locale` JSON is the owner's explicit exception (R5). |
| VI. Tests | PASS | API: 200/401/404/422 per kept endpoint + removed `featured` → 404 + empty-state; admin CRUD round-trip incl. "untouched section is not blanked"; FULLTEXT and FK behaviour tested on MySQL; obsolete tests deleted, not skipped. |
| VII. Security | PASS | FormRequests reject the removed fields; admin permissions unchanged; rich text still through `RichTextSanitizer`; upload rules (mime, 5 MB) on thumb/cover. |
| VIII. Destructive ops | PASS | Rollout is a named-table drop behind a database-name allowlist assertion, after a dump (quickstart). No `migrate:fresh`, `--step`, `--database`. Work starts from a committed checkpoint because 13 migration files are deleted. |
| IX. Simplicity | TRACKED | One interface with one implementation (`MediaLibraryLookup`) — see Complexity Tracking. Both new tables are populated by the event form in the same change. |
| X. Honest reporting | PASS | The `try/catch`-swallowed seerah index block in `add_search_indexes_to_tables` is deleted; the seeder reports counts; the final grep gate itemises any leftover reference. |

## Project Structure

### Documentation (this feature)

```text
specs/005-seerah-schema-refactor/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
│   ├── admin-forms.md
│   └── public-api.md
├── checklists/requirements.md
└── tasks.md                      # /speckit.tasks
```

### Source Code (repository root)

```text
database/
├── migrations/                   # R1: 2 rewritten+renamed, 5 edited, 13 deleted
├── factories/SeerahEventFactory.php          # rewrite (+ milestone/type factories if present)
└── seeders/
    ├── SeerahEventTypeSeeder.php             # NEW — replaces the seed-in-migration
    ├── Seerah/*                              # DELETE (5 content seeders + base)
    ├── DevContentFixturesSeeder.php          # remove Seerah block
    └── DatabaseSeeder.php                    # register the type seeder

app/Domain/Seerah/
├── Models/        # rewrite SeerahEvent, SeerahEventTranslation, SeerahMilestone(+Translation), SeerahEventType
│                  # NEW SeerahEventGalleryItem, SeerahEventMedia
│                  # DELETE Participant*, TimelineItem*, EventTypeTranslation
├── Enums/         # keep EventEra, SeerahEventStatus; DELETE EventImportance, EventPhase, EventType, ParticipantRole
├── DTOs/          # rewrite Create/UpdateSeerahEventData; add milestone + type data objects
├── Repositories/  # rewrite selects, facets, related-events, map; transactions live here
├── Services/      # SeerahEventService (drop views/lessons/participants/timeline; add thumb/cover/gallery/media),
│                  # SeerahDashboardStatsProvider (drop views), SeerahCacheKeys (drop featured)
├── Exporters/ Importers/          # new column list
└── …

app/Domain/Shared/
├── Contracts/MediaLibraryLookup.php, MediaReference.php   # NEW
├── Enums/SearchModule.php                                 # fix filterable/likeOnly fields
└── SharedServiceProvider (LocationUsageRegistry entry stays)

app/Services/MediaLibraryLookupService.php   # NEW impl, bound in AppServiceProvider
app/Models/Article.php                       # drop seerahEvents()
app/Console/Commands/                        # DELETE LinkSeerahParticipantsCommand; trim BackfillLocationsCommand
app/Http/
├── Controllers/Admin/Seerah/{SeerahEvent,SeerahMilestone,SeerahEventType}Controller.php
├── Controllers/Site/SeerahController.php
├── Controllers/Api/SeerahEventController.php
├── Controllers/Admin/Blog/ArticleController.php          # drop seerahEvents eager load
├── Requests/Admin/{Store,Update}Seerah{Event,Milestone,EventType}Request.php
└── Resources/SeerahEventResource.php (rewrite), SeerahParticipantResource.php (DELETE), ArticleResource.php (trim)

routes/api.php, routes/admin/seerah.php      # drop featured + articles picker routes

resources/views/
├── admin/seerah-events/{create,edit,_advanced,index,show}.blade.php
├── admin/seerah-milestones/{_form,index,show}.blade.php
├── admin/seerah-event-types/{_form,index,show}.blade.php
├── components/admin/form/key-value.blade.php             # NEW repeatable key/value (metadata); sources reuses it
└── public/seerah/{index,events,event,event-timeline,_event-navigator}.blade.php
resources/lang/{ar,en,fr,tr,ur}/seerah.php

tests/Feature/{Admin,Seerah,Api,Web,Repositories,Locations,Search,Console}/…   # rewrite ~16, delete ~4
deptrac.baseline.yaml                         # remove SeerahEvent → Article
CLAUDE.md, docs/SEERAH-REBUILD.md             # schema description
```

**Structure Decision**: existing single-app DDD layout, no new directories. The
event form's gallery and media tabs copy the article wizard's Alpine contract
(`open-media-library` with targets `gallery` / `media_items`) rather than the
current 8 fixed upload slots.

## Implementation Phases (input to `/speckit.tasks`)

Order matters: the suite cannot run until A + B compile together, so they land as
one commit series before anything is verified.

- **A. Schema** — commit checkpoint; rewrite/rename/edit/delete migrations per R1;
  run the migration set on the test DB; MySQL tests for FKs, uniques, FULLTEXT guard.
- **B. Domain** — models, enums, DTOs, repositories, services, exporter/importer,
  factory, `SeerahEventTypeSeeder`, Shared contract + impl. Delete dead classes.
- **C. Admin (US1, US3, US4)** — FormRequests, controllers, views, key/value
  component, gallery/media tabs, lang keys. Tests: CRUD round-trip, untouched-section
  rule, type in-use guard, bulk actions.
- **D. Public site (US2)** — `Site\SeerahController` mapping, five views, empty
  states, related-events rule, lang keys removed in all five locales. `npm run build`.
- **E. API (US5)** — resource, routes, tests per contract.
- **F. Cross-module (US5)** — SearchModule fields, Article relation + admin article
  controller, Locations tests/registry/backfill, analytics, dashboard tile, export
  columns, favourites/schedule graceful degradation, MediaRepository back-link.
- **G. Gates** — full suite, pint, deptrac (baseline shrinks), quickstart grep gate
  returns nothing, Arabic + English walk in both themes, update CLAUDE.md/docs.
- **H. Rollout (US6)** — quickstart runbook on dev; prod in a maintenance window.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| IX — `MediaLibraryLookup` is an interface with one implementation | `seerah_events_media` links to the media library, whose model is `App\Models\Media`; deptrac forbids `DomainSeerah → Models` and the baseline may not admit new violations | `belongsToMany(Media::class)` on `SeerahEvent` (what `Article` does) is a new Principle IV violation — `Article` is not in a domain module, `SeerahEvent` is. Resolving in controllers breaks Principle I and triplicates the lookup (admin, site, API). Same shape as the existing `PersonLookup`. |
| FR-001 — timestamps on 4 tables where the owner's list omits them | project DB convention ("always include timestamps") and exact parity with the mirrored article tables | Omitting them is trivially possible; flagged in `data-model.md` for the owner to veto. |
