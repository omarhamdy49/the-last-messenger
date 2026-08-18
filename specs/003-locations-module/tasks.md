---
description: "Task list for 003-locations-module"
---

# Tasks: Locations Module

**Input**: Design documents from `/specs/003-locations-module/`
**Prerequisites**: [`plan.md`](plan.md), [`spec.md`](spec.md), [`research.md`](research.md), [`data-model.md`](data-model.md), [`contracts/admin-locations.md`](contracts/admin-locations.md), [`quickstart.md`](quickstart.md)

**Tests**: **REQUIRED, not optional.** Constitution VI is titled "Tests are part of the change
(NON-NEGOTIABLE)" and mandates feature tests covering success + 401 + 404 + 422 (and 403 where roles
apply) for every endpoint, plus unit tests for domain rules. The speckit default of optional tests
does not apply in this repository.

**Organization**: grouped by user story so each is independently implementable and testable.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: parallelizable — different file, no dependency on an incomplete task
- **[Story]**: `US1`…`US5`, mapping to the user stories in `spec.md`

## Path Conventions

Existing single Laravel application. Domain code under `app/Domain/`, admin at `/su/portal/v1/admin`,
tests under `tests/Feature/` and `tests/Unit/`. Paths below are repository-relative and exact.

## Surah note

`spec.md` has five user stories, none of which is "surahs". Surah work satisfies **SC-004** and
**FR-017/FR-019**, and is attached to the stories whose outcome it shares: the schema in Foundational,
deterministic resolution and localized rendering in **US3** (same outcome — a place name in the
reader's language), the admin select in **US4** (same outcome — an editor assigns a place).

---

## Phase 1: Setup

**Purpose**: the pieces every later phase imports. No project initialization — this is an existing
application and **no new package is added**.

- [X] T001 [P] Create `LocationType` enum with cases `city`, `region`, `country`, `mountain`, `valley`, `mosque`, `well`, `battlefield`, `landmark` plus `values()` and `label()` (Arabic) in `app/Domain/Shared/Enums/LocationType.php`
- [X] T002 [P] Create Arabic translation file with every key the admin screens and validation messages need in `resources/lang/ar/locations.php`
- [X] T003 [P] Create the four sibling translation files, keys identical to Arabic, in `resources/lang/en/locations.php`, `resources/lang/fr/locations.php`, `resources/lang/tr/locations.php`, `resources/lang/ur/locations.php`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: schema, models, data access and container bindings. **No user story can start until this
phase is complete.**

### Schema

- [X] T004 Create the `locations` table migration — `slug` unique, `type` varchar(20), `parent_id` self-FK `ON DELETE SET NULL`, `depth` tinyint default 0, `latitude`/`longitude` `decimal(10,7)` nullable, `aliases` json nullable, `is_active`, `display_order`, timestamps. Ship **only** the `slug` unique index and the FK index — no `(is_active, type)` composite, per constitution II. Driver-guarded and reversible, in `database/migrations/2026_08_18_000001_create_locations_table.php`
- [X] T005 Create the `location_translations` table migration mirroring `category_translations` — `location_id` FK `ON DELETE CASCADE`, `locale`, `name`, `description`, `is_ready`, unique `(location_id, locale)`, index `(locale, name)`, in `database/migrations/2026_08_18_000002_create_location_translations_table.php`
- [X] T006 Create one migration adding all three nullable `ON DELETE SET NULL` foreign keys — `seerah_events.location_id` after `location`, `muslims.birth_place_id` after `birth_place`, `quran_surahs.revelation_location_id` after `revelation_place`. **Drop or alter nothing.** `down()` removes exactly these three columns. In `database/migrations/2026_08_18_000003_add_location_foreign_keys.php`

### Models

- [X] T007 [P] Create `LocationTranslation` model — `$fillable`, `is_ready` boolean cast, `location()` belongsTo, `scopeByLocale`, `scopeReady`; copy the shape of `app/Domain/Blog/Models/CategoryTranslation.php`, in `app/Domain/Shared/Models/LocationTranslation.php`
- [X] T008 Create `Location` model using `HasLocalizedContent` — explicit `$fillable`, `LocationType` cast, `aliases` array cast, `getTranslationModelClass()`, `getTranslatableFields()` returning `['name','description']`, `parent()`/`children()` self-relations, `scopeActive`, `scopeRoots`. **Declare no relation to any domain model** — `Shared` is a leaf (constitution IV). In `app/Domain/Shared/Models/Location.php`
- [X] T009 Add the saving hook to `Location` that recomputes `depth` from the parent chain with a hop limit, and **rejects a cycle** including `parent_id === id`, throwing a domain exception. Mirror `Category::computeDepth()`. In `app/Domain/Shared/Models/Location.php`

### Typed boundaries

- [X] T010 [P] Create `final readonly` DTOs `CreateLocationData` and `UpdateLocationData` with named factories, carrying slug, type, parent, coordinates, aliases, flags and the per-locale translations, in `app/Domain/Shared/DTOs/CreateLocationData.php` and `app/Domain/Shared/DTOs/UpdateLocationData.php`

### Data access and services

- [X] T011 Create `LocationRepositoryInterface` declaring the read and write methods the service needs, in `app/Domain/Shared/Repositories/LocationRepositoryInterface.php`
- [X] T012 Implement `LocationRepository` extending `BaseRepository` — explicit `select()` on every list query, `with('translations:id,location_id,locale,name')` on reads, `DB::transaction()` **inside** the repository wrapping a location plus its translation rows, `clearCache()` calling `parent::clearCache()`. In `app/Domain/Shared/Repositories/LocationRepository.php`
- [X] T013 [P] Create `LocationUsageRegistry` holding `[table, column]` pairs with `register()` and `countReferences(int $locationId): array` returning a per-table breakdown via the query builder. **Names no domain model** — see `research.md` R-06. In `app/Domain/Shared/Services/LocationUsageRegistry.php`
- [X] T014 Create `LocationService` — CRUD orchestration against the interface, alias-conflict rejection, and a `delete()` that asks `LocationUsageRegistry` first and refuses with the reference breakdown, in `app/Domain/Shared/Services/LocationService.php`
- [X] T015 Bind `LocationRepositoryInterface` → `LocationRepository` and register `LocationUsageRegistry` as a singleton pre-loaded with the three `[table, column]` pairs, in `app/Providers/SharedServiceProvider.php`

### Foundational tests

- [X] T016 [P] Unit test the hierarchy rules — depth derived correctly at each level, self-parent rejected, a 2-node cycle rejected, a 3-node cycle rejected — in `tests/Unit/Domain/Shared/LocationHierarchyTest.php`
- [X] T017 [P] Feature test the schema against MySQL — self-referencing FK nulls the child on parent delete, translations cascade, `aliases` JSON round-trips Arabic unchanged, `(location_id, locale)` uniqueness is enforced — in `tests/Feature/Locations/LocationSchemaTest.php`
- [X] T018 [P] Feature test that `LocationUsageRegistry` counts references across all three registered tables and returns zero for an unreferenced location, in `tests/Feature/Locations/LocationUsageRegistryTest.php`

**Checkpoint**: `php artisan migrate` runs and rolls back cleanly, `composer deptrac` passes with **no new baseline entry**, and the three foundational tests are green. User stories can now begin.

---

## Phase 3: User Story 1 — Visitor sees where a Seerah event happened (Priority: P1) 🎯 MVP

**Goal**: Seerah event pages render a map and a localized place name, with the historian's
descriptive note preserved beneath.

**Independent Test**: open an event whose text names a known place; a map renders centred on that
place, the localized name shows, and the original prose is unchanged.

### Tests for User Story 1

> Write these first and confirm they fail before implementing.

- [X] T019 [P] [US1] Unit test the alias normaliser — tashkeel stripped, alef forms unified, `ة`→`ه`, `ى`→`ي`, leading `al-`/`as-` stripped, whitespace collapsed — in `tests/Unit/Domain/Shared/LocationNormaliserTest.php`
- [X] T020 [P] [US1] Unit test longest-alias-first selection using the worked examples in `data-model.md`: `المسجد النبوي بالمدينة` → mosque not city, `شِعب أبي طالب بمكة` → Makkah, `المنطقة المحايدة بين الجيشين` → null, `Makkah or Medinah` → null. In `tests/Unit/Domain/Shared/LocationResolverTest.php`
- [X] T021 [P] [US1] Feature test the map coordinate precedence — event coordinates win over the location's, the location's are used when the event has none, and neither present renders no map **and no error** — in `tests/Feature/Seerah/SeerahEventMapTest.php`
- [X] T022 [P] [US1] Feature test that an event's descriptive `location` prose is still rendered, exported and searchable after a place is assigned, in `tests/Feature/Seerah/SeerahEventLocationPreservationTest.php`

### Implementation for User Story 1

- [X] T023 [US1] Create `LocationResolver` — the normaliser, the cached vocabulary loader, and longest-alias-first selection returning a location id or null with a reason (`no-match` / `ambiguous`), in `app/Domain/Shared/Services/LocationResolver.php`
- [X] T024 [US1] Create `LocationSeeder` with ~55 canonical places per the tree in `data-model.md` — `ar` + `en` names minimum, coordinates on **every** place, `parent_id` hierarchy, alias lists covering every observed spelling. Idempotent, keyed on `slug`. In `database/seeders/LocationSeeder.php`
- [X] T025 [US1] Add the seeder self-check that **aborts** on a duplicate alias, an empty alias, a parent cycle, or a place missing an Arabic name — constitution X forbids picking a winner silently — in `database/seeders/LocationSeeder.php`
- [X] T026 [P] [US1] Add `location()` belongsTo, `location_id` to `$fillable`, and a `resolvedCoordinates()` accessor returning event coordinates first and the location's second, in `app/Domain/Seerah/Models/SeerahEvent.php`
- [X] T027 [US1] Create `BackfillLocationsCommand` (`locations:backfill {--dry-run}`) resolving Seerah events in chunks, writing `location_id` **only where NULL**, transaction per chunk, in `app/Console/Commands/BackfillLocationsCommand.php`
- [X] T028 [US1] Add the itemised report — examined / resolved / no-match / ambiguous / already-assigned per content type — that **fails loudly if the columns do not sum to examined**, in `app/Console/Commands/BackfillLocationsCommand.php`
- [X] T029 [US1] Change the map gate from event coordinates to event coordinates ?? location coordinates at `app/Http/Controllers/Site/SeerahController.php:530`, keeping the existing bounding-box embed unchanged
- [X] T030 [US1] Eager-load `location:id,slug,latitude,longitude` and `location.translations:id,location_id,locale,name` with `location_id` present in the parent select, on the event detail and timeline read paths in `app/Domain/Seerah/Repositories/SeerahEventRepository.php`
- [X] T031 [US1] Render the localized place name above the retained prose note on the event page in `resources/views/public/seerah/event.blade.php`, using logical properties only and verified in Arabic RTL
- [X] T032 [US1] Feature test the backfill's idempotency and write rules — a second run resolves zero, a manually assigned `location_id` survives, ambiguous rows stay NULL — in `tests/Feature/Console/BackfillLocationsCommandTest.php`

**Checkpoint**: run `php artisan migrate && php artisan db:seed --class=LocationSeeder && php artisan locations:backfill`, then quickstart check 2. Maps must rise from 2 to at least 180. **This alone is a shippable increment.**

**Measured on completion**: maps 2 → **206**. Companions **6,663 / 6,677 (99.8%)**. Surahs **114/114**. Madinah spellings collapsed to **1,419** rows on one place. 88 rows left for an editor (39 ambiguous + 35 no-match on Seerah, 14 ambiguous on companions).

**Two corrections made during implementation**, both recorded in `research.md` / `data-model.md`:

1. **Ranking is by hierarchy depth, not alias length.** `المدينة المنورة` (15 chars) is longer than `المسجد النبوي` (13), so longest-alias-first returned the *less* specific place. FR-010 asks for "most specific", which is depth; length is only the tiebreaker.
2. **The Seerah relation is `place()`, not `location()`.** `seerah_events.location` is an existing string column, so an Eloquent relation named `location` is shadowed by the attribute and never reached. Renaming the relation is one word; renaming the column would churn ~30 call sites.

A third rule was added that the plan did not anticipate: **alias subsumption**. When a lower-ranked hit's alias sits inside the matched alias (`منى` inside al-Aqabah's own name `العقبة بمنى`), it is not a second place. Without it, 3 events reported false ambiguity.

---

## Phase 4: User Story 2 — Editor curates the canonical list of places (Priority: P1)

**Goal**: a content editor can create, edit, deactivate and safely delete places, in Arabic, without
a developer.

**Independent Test**: create a place with names in all five languages, a coordinate, a type and a
parent; it appears nested in the list and is selectable wherever content records a place.

### Tests for User Story 2

- [X] T033 [P] [US2] Feature test `admin.locations.index` and `show` — success, 401 unauthenticated, 403 unauthorized, 404 unknown id — in `tests/Feature/Admin/LocationControllerTest.php`
- [X] T034 [P] [US2] Feature test `store` and `update` — success, 422 on missing Arabic name, 422 on duplicate slug, 422 on an alias already owned by another place, 422 on a parent that would create a cycle, 401, 403 — in `tests/Feature/Admin/LocationControllerTest.php`
- [X] T035 [P] [US2] Feature test `destroy` — 302 when unreferenced, **422 with the reference count** when referenced, 404, 401, 403 — in `tests/Feature/Admin/LocationDeleteGuardTest.php`
- [X] T036 [P] [US2] Feature test that the index filter and sort parameters reject any value outside the allowlist rather than interpolating it, in `tests/Feature/Security/LocationFilterAllowlistTest.php`

### Implementation for User Story 2

- [X] T037 [P] [US2] Create `StoreLocationRequest` with the rules in `contracts/admin-locations.md`, rejecting unexpected fields and requiring `translations.ar.name`, in `app/Http/Requests/Admin/StoreLocationRequest.php`
- [X] T038 [P] [US2] Create `UpdateLocationRequest` — same rules with `unique:locations,slug,{id}` and the self-parent check, in `app/Http/Requests/Admin/UpdateLocationRequest.php`
- [X] T039 [P] [US2] Create `LocationResource` exposing id, slug, localized name, type, parent name, coordinates and active flag, in `app/Http/Resources/LocationResource.php`
- [X] T040 [US2] Create `LocationController` modelled on `app/Http/Controllers/Admin/Seerah/SeerahEventTypeController.php` — authorize every action, one service call per action, content-negotiate with `$request->expectsJson()`, allowlist every sort and filter name, cap `per_page` as `(int)`. In `app/Http/Controllers/Admin/LocationController.php`
- [X] T041 [US2] Add `search`, `bulkDelete` and `toggleActive` actions to `app/Http/Controllers/Admin/LocationController.php`
- [X] T042 [US2] Create the admin route file registering `/search`, `/bulk-delete`, `/{id}/toggle-active` and `/create` **before** `Route::resource` so they do not resolve as `{id}`, in `routes/admin/shared.php`, and include it wherever `routes/admin/seerah.php` is included
- [X] T043 [P] [US2] Build the index view as a parent tree showing localized name, type, parent, whether coordinates exist and the reference count, in `resources/views/admin/locations/index.blade.php`
- [X] T044 [P] [US2] Build the create and edit views with five language tabs (Arabic first and selected by default), type, parent, coordinates and a repeatable aliases field, in `resources/views/admin/locations/create.blade.php` and `resources/views/admin/locations/edit.blade.php`
- [X] T045 [P] [US2] Build the show view in `resources/views/admin/locations/show.blade.php`
- [X] T046 [US2] Add the Locations entry to the admin sidebar in `resources/views/admin/layouts/sidebar.blade.php`, using the translation key not a hardcoded string
- [X] T047 [US2] Run `npm run build` — there is no Vite dev server in this setup and new Blade views silently break without it

**Checkpoint**: an editor can complete the full place lifecycle in Arabic, and a referenced place cannot be deleted.

---

## Phase 5: User Story 3 — Visitor reads a companion's birthplace in Arabic (Priority: P2)

**Goal**: companion profiles — and surah pages — show the place in the language being read.

**Independent Test**: open a companion whose birthplace was `Medinah`, `Medina` or `Madinah`; all
three render **المدينة المنورة** in Arabic.

### Tests for User Story 3

- [X] T048 [P] [US3] Feature test that the three Madinah spellings resolve to one location id and render identically, and that an ambiguous birthplace renders its original text unchanged, in `tests/Feature/People/MuslimBirthPlaceTest.php`
- [X] T049 [P] [US3] Feature test that all 114 surahs resolve to a location **and** that `revelation_place` is byte-identical before and after the backfill, in `tests/Feature/Quran/SurahRevelationLocationTest.php`

### Implementation for User Story 3

- [X] T050 [P] [US3] Add `birthPlace()` belongsTo on `birth_place_id` and add the column to `$fillable`, in `app/Domain/People/Models/Muslim.php`
- [X] T051 [P] [US3] Add `revelationLocation()` belongsTo on `revelation_location_id` and add the column to `$fillable`. **Do not touch `revelation_place`, `scopeMakki` or `scopeMadani`.** In `app/Domain/Quran/Models/Surah.php`
- [X] T052 [US3] Extend `BackfillLocationsCommand` with the companion pass — normalized exact match, ambiguous multi-place values left NULL — in `app/Console/Commands/BackfillLocationsCommand.php`
- [X] T053 [US3] Extend `BackfillLocationsCommand` with the surah pass — deterministic `makkah`→Makkah, `madinah`→Madinah, no alias matching — in `app/Console/Commands/BackfillLocationsCommand.php`
- [X] T054 [US3] Eager-load the birthplace with named columns on the profile and directory read paths, keeping `birth_place_id` in the parent select, in `app/Domain/People/Repositories/` and `app/Http/Controllers/Site/SahabaController.php`
- [X] T055 [P] [US3] Render the localized birthplace, falling back to the original recorded text when unresolved and omitting the section entirely when neither exists, in `resources/views/public/sahaba/show.blade.php`
- [X] T056 [P] [US3] Render the localized revelation place beside the unchanged مكية/مدنية badge on the surah page in `resources/views/public/quran/`
- [X] T057 [US3] Run `npm run build`

**Checkpoint**: quickstart check 3 passes — zero unresolved across the three Madinah spellings, at least 1,419 resolved. **Measured: 1,419 exactly.**

**Note on T052/T053**: both landed with US1 — the backfill command was written with all three passes (Seerah, companions, surahs) in one file rather than extended twice.

**Correction found here**: `RevelationPlace`'s own docblock, and this feature's `research.md`, both name `Surah::scopeMakki` / `scopeMadani` as readers of the enum. **Those scopes do not exist.** The filtering lives in `SurahRepository::getByRevelationPlace()`, and the enum has **23** readers, not the 14 first claimed. `research.md` R-07 now records this.

---

## Phase 6: User Story 4 — Editor resolves what the system could not (Priority: P2)

**Goal**: every unresolved record is findable and assignable by hand, and the assignment survives a
re-run.

**Independent Test**: filter to unresolved records, assign a place to one, confirm it leaves the
filtered view and survives `locations:backfill`.

### Tests for User Story 4

- [X] T058 [P] [US4] Feature test `?unmapped=1` on both index screens — returns only rows with place text and a NULL foreign key, and excludes rows with no place text at all — in `tests/Feature/Admin/UnmappedLocationFilterTest.php`
- [X] T059 [P] [US4] Feature test `admin.locations.search` — matches translated names **and** aliases so `Medina` finds المدينة المنورة, returns only active places, caps `limit`, and covers 401/403 — in `tests/Feature/Admin/LocationSearchTest.php`

### Implementation for User Story 4

- [X] T060 [US4] Add the `?unmapped=1` filter (`location_id IS NULL AND location <> ''`) to the admin event index query, allowlisted like every other filter, in `app/Domain/Seerah/Repositories/SeerahEventRepository.php` and the admin controller
- [X] T061 [US4] Add the equivalent `?unmapped=1` filter over `birth_place_id` / `birth_place` to the companion index in `app/Http/Controllers/Admin/SahabiController.php` and its repository
- [X] T062 [P] [US4] Add `location_id` → `nullable, integer, exists:locations,id` to `app/Http/Requests/Admin/StoreSeerahEventRequest.php` and `app/Http/Requests/Admin/UpdateSeerahEventRequest.php`, and thread it through `CreateSeerahEventData`, `UpdateSeerahEventData` and `SeerahEventService`
- [X] T063 [US4] Add the place select beside the existing text input and relabel that input **وصف المكان**, in `resources/views/admin/seerah-events/edit.blade.php` (~line 198) and the matching create view
- [X] T064 [US4] Replace the birth place text input with a searchable select writing `birth_place_id`, showing the existing `birth_place` text read-only beneath it, in `resources/views/admin/sahaba/_form.blade.php`; add validation to the Sahabi store and update requests
- [X] T065 [US4] Add the revelation place select writing `revelation_location_id` to the admin surah form and `app/Http/Controllers/Admin/Quran/SurahController.php`, leaving the مكية/مدنية field untouched
- [X] T066 [P] [US4] Build the shared Alpine place-picker partial backed by `admin.locations.search`, in `resources/views/components/`
- [X] T067 [US4] Add the unresolved-count badge to the Seerah and Sahaba index headers so the remaining tail is visible rather than silent (constitution X), in `resources/views/admin/seerah-events/index.blade.php` and `resources/views/admin/sahaba/index.blade.php`
- [X] T068 [US4] Run `npm run build`

**Checkpoint**: the full editorial loop works — see the tail, assign it, and it survives a re-run.

---

## Phase 7: User Story 5 — Content is grouped and found by place (Priority: P3)

**Goal**: a place name is a way into the content, including where the content words it differently.

**Independent Test**: search **المدينة**; an event whose prose reads `حصون بني قريظة جنوب المدينة`
and which resolves to المدينة المنورة is returned.

### Tests for User Story 5

- [X] T069 [P] [US5] Feature test that searching a place name returns content resolved to that place even when the content's own text words it differently, and that alias terms work, in `tests/Feature/Search/LocationSearchResolutionTest.php`

### Implementation for User Story 5

- [X] T070 [US5] Add place resolution to the Seerah search path so a place term also matches on `location_id`, keeping the existing `location` prose match — `SearchModule::SEERAH::searchableFields()` is unchanged — in `app/Domain/Shared/Enums/SearchModule.php` and the search query builder
- [X] T071 [US5] Add a place filter to the admin event index, allowlisted, in `app/Domain/Seerah/Repositories/SeerahEventRepository.php`
- [X] T072 [US5] Verify with `EXPLAIN` that the place filter uses the foreign-key index, and **only then** add any further index — constitution II forbids shipping one before that

**Checkpoint**: all five stories complete.

**T072 EXPLAIN result** — all three place-driven queries already use the foreign-key index, so **no additional index ships**:

```
place filter on seerah_events    type=ref  key=seerah_events_location_id_foreign   rows=48
unmapped queue on seerah_events  type=ref  key=seerah_events_location_id_foreign   rows=373
unmapped queue on muslims        type=ref  key=muslims_birth_place_id_foreign      rows=1174
```

---

## Phase 8: Polish & Cross-Cutting Concerns

- [X] T073 [P] Add the Locations screens to `tests/Feature/NoNPlusOneQueriesTest.php`, and assert the Seerah timeline and companion directory query counts are **unchanged** from the pre-feature baseline (SC-009)
- [X] T074 [P] Verify every key in `resources/lang/ar/locations.php` exists in the other four files and that no user-visible string is hardcoded (constitution V)
- [X] T075 [P] Audit every new Blade view for physical `left-`/`right-`/`pl-`/`pr-` layout classes and replace with logical properties; verify each screen in Arabic RTL and in both themes
- [X] T076 Run `composer deptrac` and confirm it passes **with no new baseline entry** — a new entry means `Shared` acquired a domain import and `research.md` R-06 was violated
- [X] T077 Run `./vendor/bin/pint` and `php artisan test`; both must be green
- [X] T078 Walk `quickstart.md` end to end on a fresh database, recording actual numbers against SC-001 through SC-009 — report what was run, not what was assumed (constitution X)
- [X] T079 Verify the migration rolls back by explicit migration name and returns the schema to its exact prior state; **do not use `--step=N`** (constitution VIII)
- [X] T080 Update `docs/LOCATIONS-MODULE-DESIGN-2026-08-18.md` with any decision that changed during implementation, so the design document does not drift from the code

---

## Dependencies

```
Phase 1 Setup (T001–T003)
        │
        ▼
Phase 2 Foundational (T004–T018)   ◀── BLOCKS EVERYTHING
        │
        ├──────────────┬──────────────┬──────────────┐
        ▼              ▼              ▼              │
   US1 (T019–T032)  US2 (T033–T047)   │              │
   🎯 MVP           independent of US1 │              │
        │                              │              │
        │  T023 LocationResolver ──────┼─────────────►│ US3 needs it
        │  T024 LocationSeeder   ──────┼─────────────►│ US3, US4 need it
        │  T027 backfill command ──────┼─────────────►│ US3 extends it
        │                              │              │
        ▼                              ▼              ▼
   US3 (T048–T057)               US4 (T058–T068) needs US2's search endpoint (T041)
        │                              │
        └──────────────┬───────────────┘
                       ▼
                  US5 (T069–T072)
                       │
                       ▼
              Phase 8 Polish (T073–T080)
```

**Hard dependencies**

- Everything depends on Phase 2. The migrations (T004–T006) gate the models, which gate the rest.
- **US1 and US2 are genuinely independent** and can run concurrently by two people once Phase 2 lands.
- US3 depends on US1's `LocationResolver` (T023), `LocationSeeder` (T024) and backfill command (T027) — it extends the command rather than duplicating it.
- US4 depends on US2's `admin.locations.search` endpoint (T041) for its place pickers.
- US5 depends on US1 (events must be resolved before they are findable by place).

**Within Phase 2**: T004→T005→T006 are ordered (same schema, FKs need the tables). T007 is parallel to the migrations. T008 needs T007. T009 needs T008. T011→T012→T014 are ordered. T013 is parallel to all of them.

---

## Parallel Execution Examples

**Phase 1 — all three at once:**

```
T001 LocationType enum   ·   T002 ar/locations.php   ·   T003 the four sibling lang files
```

**Phase 2 — after T006 lands:**

```
T007 LocationTranslation   ·   T010 DTOs   ·   T013 LocationUsageRegistry
```

**Phase 2 tests — all three at once:**

```
T016 hierarchy unit   ·   T017 schema feature   ·   T018 usage registry feature
```

**US1 tests — write all four before implementing:**

```
T019 normaliser   ·   T020 resolver selection   ·   T021 map precedence   ·   T022 prose preservation
```

**US2 — after T040 lands:**

```
T037 StoreRequest   ·   T038 UpdateRequest   ·   T039 Resource
T043 index view     ·   T044 create/edit views   ·   T045 show view
```

**Two people after Phase 2**: one takes US1 (T019–T032, the map), the other takes US2 (T033–T047, the admin CRUD). They touch no shared file.

---

## Implementation Strategy

### MVP — User Story 1 only

Phase 1 + Phase 2 + Phase 3 is a complete, shippable increment: **T001–T032, 32 tasks.**

It needs no admin work and no editorial effort, and it fixes the largest visible defect — Seerah
event pages that render a blank where a map was designed to be, currently 576 of 578. Ship it, then
decide whether to continue.

### Incremental delivery after that

1. **+US2** — editors gain control of the vocabulary. Together with the MVP this is the durable fix:
   without it, the next content import reintroduces free text.
2. **+US3** — 6,677 companion profiles stop showing English on an Arabic-first site, and all 114
   surahs gain a place.
3. **+US4** — the ~92 records automatic resolution deliberately left alone become finishable.
4. **+US5** — places become a way into the content.

### What must not drift

Three invariants that several tasks depend on. If any is broken, stop rather than working around it:

- **Nothing is replaced.** `seerah_events.location`, `muslims.birth_place` and
  `quran_surahs.revelation_place` all survive this feature intact. T022 and T049 assert it.
- **`Shared` stays a leaf.** `Location` names no domain model. T076 asserts it.
- **Ambiguity is never guessed.** ~19 companions and ~73 events stay NULL by design. T020 and T032
  assert it.
