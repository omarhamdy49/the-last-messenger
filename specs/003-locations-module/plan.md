# Implementation Plan: Locations Module

**Branch**: `003-locations-module` | **Date**: 2026-08-18 | **Spec**: [`spec.md`](spec.md)
**Input**: Feature specification from `specs/003-locations-module/spec.md`

**Companion documents:** approved design [`../../docs/LOCATIONS-MODULE-DESIGN-2026-08-18.md`](../../docs/LOCATIONS-MODULE-DESIGN-2026-08-18.md) ·
research decisions [`research.md`](research.md) · schema [`data-model.md`](data-model.md) ·
admin surface [`contracts/`](contracts/) · runbook [`quickstart.md`](quickstart.md).

## Summary

Introduce one canonical place vocabulary shared by Seerah, People and Quran, and resolve the
existing free-text place columns onto it without discarding any of them.

Two tables (`locations`, `location_translations`) in the `Shared` module, three nullable foreign
keys (`seerah_events.location_id`, `muslims.birth_place_id`, `quran_surahs.revelation_location_id`),
a seeder of ~55 canonical places with alternate spellings and coordinates, an idempotent resolver
command, an admin CRUD plus three form selects and an unresolved-records queue, and a one-condition
change to the Seerah event map gate.

The load-bearing decision is that **nothing is replaced**. `seerah_events.location` keeps its
editorial prose, `muslims.birth_place` keeps its recorded text, and `quran_surahs.revelation_place`
keeps its مكية/مدنية classification permanently. The foreign keys are additive, so roughly 30
existing call sites — exporters, importers, DTOs, form requests, the search enum, repository
selects — need no change at all.

## Technical Context

**Language/Version**: PHP 8.5 (`composer.json` requires `^8.5`, `config.platform.php` pinned to
`8.5.4`) — **not 8.2**, which is what the constitution's Technology Constraints section still claims.

**Primary Dependencies**: Laravel 13 (`^13.0`). This feature adds **no new package**. Translation
handling uses the in-repo `App\Domain\Shared\Traits\HasLocalizedContent` trait, not
spatie/laravel-translatable — see Complexity Tracking.

**Storage**: MySQL 8, InnoDB, `utf8mb4`. Uses a self-referencing foreign key and a JSON column. No
FULLTEXT index is added: place-name lookup is a bounded ~55-row set that is cached, and Arabic
FULLTEXT would need the `innodb_ft_min_token_size` environment prerequisite for no gain here.

**Testing**: PHPUnit against MySQL (`phpunit.xml` → `DB_DATABASE=the_last_messenger_test`). Per
constitution VI the MySQL-specific behaviour used here — self-referencing FK with `ON DELETE SET
NULL`, JSON column round-tripping, and `utf8mb4` Arabic alias matching — is asserted, not assumed.

**Target Platform**: Linux server, `docker compose`. Blade + Alpine 3 + Tailwind, server-rendered.
`npm run build` is required after the Blade changes — there is no Vite dev server in this setup.

**Project Type**: Single Laravel application. DDD modules under `app/Domain/`, admin at
`/su/portal/v1/admin`.

**Performance Goals**: No page renders slower than today (SC-009). Concretely: the Seerah event
timeline and the companion directory must keep their current query counts. The place vocabulary is
~55 rows and is loaded once per request from cache, so place-name rendering adds **zero** queries to
a list page when the relation is eager-loaded with named columns.

**Constraints**:
- `Shared` is a leaf module (constitution IV): `Location` may **not** import or declare an Eloquent
  relation to `SeerahEvent`, `Muslim` or `Surah`. Consumers point at `Location`, never the reverse.
  This is what forces the usage-registry design below rather than a `hasMany` count.
- Alias matching runs over Arabic text. Comparison must be done in PHP over a cached vocabulary with
  Unicode-aware normalisation, not via SQL `LIKE` — MySQL collation folding is not reliable for the
  Arabic diacritics present in the source (`شِعب`, `أُحُد`, `بُصرى`).
- Constitution II forbids speculative indexes. Only the unique `slug`, the unique
  `(location_id, locale)` and the indexes MySQL creates for the foreign keys ship in the first
  migration. Anything else waits for an `EXPLAIN` on a real query path.

**Scale/Scope**: ~55 locations × 5 locales = ~275 translation rows. Resolver processes 279 Seerah
events, 6,677 companions and 114 surahs — 7,070 rows total, a single chunked pass. Admin surface:
one new CRUD (7 routes + 2 extras), three existing forms gain a select, two existing index screens
gain a filter.

## Constitution Check

*GATE: evaluated before Phase 0, re-evaluated after Phase 1 design. Result recorded at each.*

| # | Principle | Verdict | How this design satisfies it |
|---|---|---|---|
| I | Layered data flow | **PASS** | `LocationController` → `LocationService` → `LocationRepositoryInterface` → `LocationRepository` → DB. Controller validates via FormRequest, calls exactly one service method, returns a view or Resource. The `DB::transaction()` for a location plus its five translation rows lives **inside** the repository. |
| II | Query discipline | **PASS** | Every list query carries an explicit `select()`. Consumers eager-load `location:id,slug,latitude,longitude` plus `location.translations:id,location_id,locale,name`, and the parent select includes the foreign key. The place vocabulary is cached, so rendering a 50-row event list adds zero per-row queries. Delete-guard counts use `EXISTS` per registered table, not `count()` on a collection. No speculative indexes. |
| III | Typed boundaries | **PASS** | `LocationType` enum for the nine kinds. `CreateLocationData` / `UpdateLocationData` `final readonly` DTOs with named factories. `ResolutionOutcome` DTO from the resolver. Explicit `$fillable` on both models. No `array $data` crosses a layer boundary. |
| IV | Module isolation | **PASS** | `Location` lives in `Shared`, which every domain may already import, so **no `deptrac.yaml` change is required**. `Shared` stays a leaf: `Location` declares no relation to any domain model. Cross-module foreign keys are permitted at the database level and are enforced in review, exactly as `hadith_narrators.narrator_id → muslims` already is. |
| V | Arabic-first and multilingual | **PASS with recorded deviation** | Five locales, `ar` default and fallback, every string through `__()` present in all five files, logical properties only, verified in Arabic. The deviation is the storage mechanism — translation tables rather than Spatie JSON columns. See Complexity Tracking. |
| VI | Tests are part of the change | **PASS** | Feature tests per admin endpoint covering success, 401, 403, 404 and 422. Unit tests for alias resolution, hierarchy cycle rejection and the map-gate precedence. The resolver's idempotency is asserted by running it twice and diffing. |
| VII | Security by default | **PASS** | Every admin action authorized, deny by default. FormRequest on all mutating endpoints rejecting unexpected fields. Sort and filter column names come from an allowlist — `?unmapped=1` and the place filter are never interpolated into query structure. Generic client errors. |
| VIII | Destructive operations guarded | **PASS — guard not applicable, justified** | `locations:backfill` performs no `DROP`, `TRUNCATE` or `DELETE`; it writes a foreign key only where it `IS NULL`. It therefore does not trip the destructive-command rule. It nevertheless ships `--dry-run` and is idempotent. No migration in this feature drops a column. |
| IX | Simplicity | **PASS** | Three things were cut for this principle and are recorded in `research.md`: the `zoom` column (nothing reads it — the existing embed uses a bounding box), the `location_aliases` table (a JSON column serves a PHP-side matcher), and the `seerah_event_location` pivot (9 of 279 rows do not justify a table, a model and sync paths). |
| X | Honest reporting | **PASS** | The resolver itemises per content type: rows examined, rows resolved, rows left unresolved **and why** (no match / ambiguous / already assigned). Nothing is averaged away, nothing is silently skipped. The seeder aborts on a duplicate alias rather than picking a winner. |

**Gate result (pre-Phase 0): PASS.** One deviation recorded, no violation requiring redesign.

## Project Structure

### Documentation (this feature)

```text
specs/003-locations-module/
├── plan.md              # This file
├── spec.md              # Requirements and acceptance criteria
├── research.md          # Phase 0 — decisions and rejected alternatives
├── data-model.md        # Phase 1 — schema, constraints, migrations
├── quickstart.md        # Phase 1 — runbook for applying and verifying
├── contracts/
│   └── admin-locations.md   # Phase 1 — admin route contract and payloads
├── checklists/
│   └── requirements.md      # Spec quality validation (complete)
└── tasks.md             # Phase 2 — created by /speckit.tasks, NOT by this command
```

### Source code

```text
app/Domain/Shared/
├── Enums/
│   └── LocationType.php                     # NEW — city|region|country|mountain|valley|
│                                            #       mosque|well|battlefield|landmark
├── Models/
│   ├── Location.php                         # NEW — HasLocalizedContent, self-referencing parent
│   └── LocationTranslation.php              # NEW — mirrors CategoryTranslation exactly
├── DTOs/
│   ├── CreateLocationData.php               # NEW
│   └── UpdateLocationData.php               # NEW
├── Repositories/
│   ├── LocationRepositoryInterface.php      # NEW
│   └── LocationRepository.php               # NEW — extends BaseRepository (versioned cache)
└── Services/
    ├── LocationService.php                  # NEW — CRUD orchestration, delete guard
    ├── LocationUsageRegistry.php            # NEW — [table, column] pairs; keeps Shared a leaf
    └── LocationResolver.php                 # NEW — alias matching, used by the command

app/Console/Commands/
└── BackfillLocationsCommand.php             # NEW — locations:backfill {--dry-run}

database/
├── migrations/
│   ├── ...create_locations_table.php        # NEW
│   ├── ...create_location_translations_table.php  # NEW
│   └── ...add_location_foreign_keys.php     # NEW — the three FKs, one migration
└── seeders/
    └── LocationSeeder.php                   # NEW — ~55 places + aliases + coordinates

app/Http/
├── Controllers/Admin/
│   └── LocationController.php               # NEW — modelled on SeerahEventTypeController
├── Requests/Admin/
│   ├── StoreLocationRequest.php             # NEW
│   └── UpdateLocationRequest.php            # NEW
└── Resources/
    └── LocationResource.php                 # NEW

routes/admin/
└── shared.php                               # NEW route file, registered like seerah.php

resources/
├── views/admin/locations/                   # NEW — index (tree), create, edit, show
├── views/admin/seerah-events/edit.blade.php # MODIFIED — add place select at line ~198
├── views/admin/sahaba/_form.blade.php       # MODIFIED — birth place text → select
└── lang/{ar,en,fr,tr,ur}/locations.php      # NEW — all five, ar authored first

MODIFIED (existing files):
app/Domain/Seerah/Models/SeerahEvent.php             # belongsTo Location, $fillable, coordinate accessor
app/Domain/Seerah/Repositories/SeerahEventRepository.php  # eager load + named columns
app/Domain/People/Models/Muslim.php                  # belongsTo Location
app/Domain/Quran/Models/Surah.php                    # belongsTo Location
app/Http/Controllers/Site/SeerahController.php:530   # map gate: event coords ?? location coords
app/Http/Controllers/Admin/Quran/SurahController.php # place select handling
app/Providers/SharedServiceProvider.php              # bind repository + usage registry
```

**Structure Decision**: Existing single-application DDD layout, unchanged. The only structural
choice is placing `Location` under `app/Domain/Shared/` rather than creating
`app/Domain/Locations/` — justified in `research.md` and required to avoid a `LocationLookup`
contract for three separate consumers.

## Phase 0 — Research

Complete. See [`research.md`](research.md). Nine decisions recorded, all `NEEDS CLARIFICATION`
resolved. The three that most shape the build:

- **R-01** Placement in `Shared`, not a new domain module.
- **R-04** Aliases as a JSON column matched in PHP, not a table matched in SQL.
- **R-06** Delete-guard via a usage registry, because `Shared` may not reference domain models.

## Phase 1 — Design

Complete. Artifacts: [`data-model.md`](data-model.md), [`contracts/admin-locations.md`](contracts/admin-locations.md),
[`quickstart.md`](quickstart.md).

### Post-design Constitution re-check

Re-evaluated after the schema and contract were written. **PASS, unchanged.** Two points were
tightened by the design pass rather than loosened:

- Principle II — the first draft carried an `(is_active, type)` composite index. Dropped: no query
  in the design filters on both, and constitution II forbids shipping an index before `EXPLAIN`
  shows it used.
- Principle IV — the first draft gave `Location` a `hasMany(SeerahEvent)` for the delete-guard
  count. That would have made `Shared` import `Domain\Seerah` and broken the leaf invariant.
  Replaced by `LocationUsageRegistry`, which holds `[table, column]` pairs and counts through the
  query builder. `deptrac` still passes with no baseline entry.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|---|---|---|
| Constitution V says "Translatable content uses spatie/laravel-translatable JSON columns"; this feature uses a `location_translations` table with `HasLocalizedContent`. | The constitution text is **stale on this point**. The 2026-03-10 migrations dropped the Spatie JSON columns from `categories`, `tags`, `series` and the Seerah tables, and `CLAUDE.md` now documents translation tables as the convention. Every comparable model in the codebase (`Category`, `Tag`, `SeerahEventType`, `MediaProgram`, `Reciter`) uses the table form. | Using Spatie JSON columns would make `Location` the only translatable model in the repo storing translations differently, break `HasLocalizedContent`'s `syncTranslations()` write path, and — per `CLAUDE.md` — declaring `$translatable` for a column pattern the rest of the schema abandoned is exactly the class of bug that caused the historical failures this convention was introduced to stop. Recommend amending constitution V in a separate change. |
| `LocationUsageRegistry` is an indirection over what could be three `hasMany` relations. | Constitution IV makes `Shared` a leaf: it imports no domain module. `Location` therefore cannot name `SeerahEvent`, `Muslim` or `Surah` in a relation. The registry holds `[table, column]` pairs supplied by the consuming providers, so the dependency arrow keeps pointing inward. | Three `hasMany` relations on `Location` would make `Shared` import three domain modules and fail `deptrac`, which the constitution says the baseline may never be used to admit. Hard-coding the three table names as strings inside `LocationService` would pass `deptrac` but hide a real coupling from both the type system and the reader, and would silently miss a fourth consumer added later. |
