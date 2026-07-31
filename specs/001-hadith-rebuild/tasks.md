---
description: "Task list for Hadith Module Rebuild"
---

# Tasks: Hadith Module Rebuild

**Input**: Design documents from `/specs/001-hadith-rebuild/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/

**Tests**: INCLUDED. Constitution principle VI is non-negotiable, and spec SC-008/SC-014 require
constraint and gate proofs. Schema-constraint tests land in Phase 2 *before* any data is imported.

**Organization**: Tasks are grouped by user story so each can be implemented, tested, and delivered
independently.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependency on incomplete work)
- **[Story]**: US1–US6, on user-story phases only

## Path Conventions

Single Laravel application at repository root: `app/`, `database/`, `resources/`, `routes/`, `tests/`.
Admin panel is served at `/su/portal/v1/admin`, not `/admin`.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: The guardrails that make every later phase safe to run. A mis-targeted `migrate:fresh`
destroyed the dev database during planning; T002–T004 exist so that cannot recur.

- [X] T001 Commit the current worktree as a checkpoint on branch `001-hadith-rebuild`; verify `git status --porcelain` is empty afterwards (23 files are currently untracked and would be destroyed irrecoverably by `rm -rf`)
- [X] T002 Create `.env.testing` with `DB_DATABASE=the_last_messenger_test` and `APP_ENV=testing`, since `--env=testing` silently falls back to `.env` when the file is absent
- [X] T003 Create `app/Console/Concerns/GuardsDatabaseTarget.php` exposing an `assertSafeDatabase(array $allowlist)` trait method that aborts when the resolved database name is not on the allowlist
- [X] T004 [P] Add a `DatabaseTargetGuardTest` in `tests/Unit/Console/DatabaseTargetGuardTest.php` asserting the guard aborts on `the_last_messenger` and passes on `the_last_messenger_test`
- [X] T005 [P] Add `hadith_source/` and `.env.testing` entries to `.gitignore`
- [X] T006 [P] Create the extraction output directory `storage/app/hadith_source/.gitkeep`

**Checkpoint**: Destructive commands cannot target the main database.

---

## Phase 2: Foundational (Blocking Prerequisites)

**⚠️ CRITICAL**: No user story work may begin until this phase is complete.

### 2a. Rewire every external consumer — BEFORE any deletion

The old module has 22 external consumers. Deleting it first leaves the container unable to boot.

- [X] T007 Remove the three Hadith service imports and their container bindings from `app/Providers/AppServiceProvider.php` (lines 10-12) — **boot-critical, do this first**
- [X] T008 Rewrite `app/Providers/HadithServiceProvider.php` to bind 3 repository interfaces and tag `HadithVisibility` as a `PublicVisibilityProviderInterface`
- [X] T009 [P] Update the Hadith model mapping in `app/Providers/TopicsServiceProvider.php` (line 37)
- [X] T010 [P] Update the favorable registry, eager-load hints, and mapper in `app/Providers/FavoriteServiceProvider.php` (lines 39, 56, 92)
- [X] T011 [P] Update the bulk-handler registration in `app/Providers/SharedServiceProvider.php` (line 71)
- [X] T012 [P] Update the Hadith model reference in `app/Domain/Shared/Enums/SearchModule.php` (line 116)
- [X] T013 [P] Update the Hadith labels in `app/Domain/Shared/Enums/ExportModule.php` (lines 42-43)
- [X] T014 [P] Update the model-class string map in `app/Domain/Shared/Models/ContentSchedule.php` (line 298)
- [X] T015 Update all 9 Hadith references in `app/Domain/Shared/Services/AnalyticsService.php` (lines 9, 10, 115, 238, 281, 385, 527, 619)
- [X] T016 Replace the raw `hadith_narrators` table query in `app/Domain/Sahaba/Models/Sahabi.php` (line 439) with a `Shared` contract call — this is a cross-module violation the deptrac baseline was masking
- [X] T017 Update the daily-hadith card in `app/Http/Controllers/Site/HomeController.php` (lines 9, 10, 30, 33, 73)
- [X] T018 [P] Update Hadith references in `app/Http/Controllers/Admin/ActivityLogController.php`
- [X] T019 [P] Update Hadith references in `app/Http/Controllers/Admin/ImportController.php`
- [X] T020 [P] Update Hadith references in `app/Http/Controllers/Admin/VersionController.php`
- [X] T021 [P] Update Hadith references in `app/Http/Controllers/Admin/Content/ImportStatusController.php`
- [X] T022 [P] Update Hadith references in `app/Jobs/ProcessImport.php`
- [X] T023 [P] Update Hadith references in `app/Console/Commands/IslamicData/ImportAllCommand.php` (line 36)
- [X] T024 [P] Update Hadith verification counts in `app/Console/Commands/IslamicData/VerifyCommand.php`
- [X] T025 [P] Update the `hadith_narrators` reference in `app/Console/Commands/DedupePeopleCommand.php` (line 46)
- [X] T026 [P] Update Hadith references in `app/Console/Commands/Portal/ExportPortalDataCommand.php`
- [X] T027 [P] Update Hadith references in `app/Console/Commands/Portal/VerifyPortalDataCommand.php`
- [X] T028 [P] Update the Hadith seeder call in `database/seeders/DatabaseSeeder.php` (line 46)
- [X] T029 [P] Update the Hadith topic mapping in `database/seeders/TopicSeeder.php` (line 183)
- [X] T030 Remove every stale `App\Domain\Hadith\*` entry from `deptrac.baseline.yaml` so the baseline cannot mask new violations
- [X] T031 Remove the 17 Hadith API routes from `routes/api.php`
- [X] T032 Remove the Hadith public routes from `routes/web.php` (lines 85-95)
- [X] T033 Delete the obsolete route file `routes/admin/hadith.php` and the dead commented narrator block in `routes/admin.php` (lines 175-181)
- [X] T034 [P] Update Hadith factory helpers in `tests/Helpers/TestHelper.php`
- [X] T035 Verify the application boots with no Hadith module present: `php artisan about`, `php artisan route:list`, `composer deptrac`

### 2b. Delete the old module

- [X] T036 Delete the entire old domain module directory `app/Domain/Hadith/` (125 files)
- [X] T037 [P] Delete `app/Http/Controllers/Admin/Hadith/`, `app/Http/Controllers/Api/HadithController.php`, `app/Http/Controllers/Site/HadithController.php`, and `app/Http/Controllers/Site/Concerns/NormalizesHadithGrading.php`
- [X] T038 [P] Delete `app/Http/Requests/Admin/Hadith/` (6 files) and the 6 `app/Http/Resources/Hadith*.php` files
- [X] T039 [P] Delete `resources/views/admin/hadith/`, `resources/views/admin/hadiths/`, and `resources/views/public/hadith/` (31 views)
- [X] T040 [P] Delete the obsolete commands `app/Console/Commands/DeriveHadithIsnadCommand.php`, `RebuildHadithCollectionCommand.php`, `SyncHadithFromJsonCommand.php`, `SyncHadithTextFromSunnahCommand.php`, `IslamicData/ImportHadithCommand.php`, and `app/Console/Concerns/DisplaysHadithSyncOutput.php`
- [X] T041 [P] Delete `app/Services/Hadith/JsonFileScanner.php` and `app/Services/Json/HadithJsonTransformer.php`, `HadithJsonValidator.php`, `HadithRecordExtractors.php`
- [X] T042 [P] Delete the 16 obsolete Hadith test files under `tests/Feature/`, `tests/Unit/`, and the `tests/Fixtures/HadithRebuild/` directory
- [X] T043 Delete every existing hadith migration in `database/migrations/`, including all 12 dated `2026_07_30_*`; only `2026_07_02_090001_rename_hadith_count_on_sahaba.php` survives because it touches the `sahaba` table
- [X] T044 [P] Delete the discarded data artifacts `storage/app/backups/*.sql`, `storage/app/hadith_reconcile/`, and `storage/app/hadith_sunnah_parity/`

### 2c. Verify extraction sources — BEFORE authoring the schema

Two columns and the whole grading subsystem depend on selectors that are not yet confirmed. These run
here, not later, because the schema is authored once (§2d) and must not be edited after being applied.
This is what satisfies FR-024: a column with no verified source is never created.

- [X] T045 Inspect a graded collection page (tirmidhi or abudawud) with the Chrome extension and record the **grade selector** in `specs/001-hadith-rebuild/research.md` §2 — without it every `raw_grade` is NULL and the grading subsystem ships dead
- [X] T046 Inspect a hadith page for the **English permalink identifier**; record the selector in research.md §2 or delete `sunnah_urn_en` from `data-model.md` §4 and its migration
- [X] T047 Write the source-selector → JSON-field → database-column matrix into `specs/001-hadith-rebuild/contracts/extraction-matrix.md`; any column with no source is removed from the schema

### 2d. Create the fresh 8-table schema

Authored once from `data-model.md`, in foreign-key dependency order. Each migration has a working
`down()`, and MySQL-only DDL is wrapped in a driver guard.

- [X] T048 [P] Create `database/migrations/2026_07_31_000001_create_hadith_collections_table.php` per data-model.md §1
- [X] T049 Create `database/migrations/2026_07_31_000002_create_hadith_books_table.php` per §2, including the `UNIQUE (id, collection_id)` parent key and `is_synthetic`
- [X] T050 Create `database/migrations/2026_07_31_000003_create_hadith_chapters_table.php` per §3, including the `UNIQUE (id, book_id)` parent key and RESTRICT deletion
- [X] T051 [P] Create `database/migrations/2026_07_31_000004_create_hadith_scholars_table.php` per §7
- [X] T052 Create `database/migrations/2026_07_31_000005_create_hadith_hadiths_table.php` per §4, including both composite foreign keys and the three publication-aware indexes
- [X] T053 Create `database/migrations/2026_07_31_000006_create_hadith_texts_table.php` per §5, with `FULLTEXT (search_body)` as the only full-text index
- [X] T054 [P] Create `database/migrations/2026_07_31_000007_create_hadith_gradings_table.php` per §6, with `raw_grade` as TEXT
- [X] T055 [P] Create `database/migrations/2026_07_31_000008_create_hadith_import_runs_table.php` per §8
- [X] T056 Apply the migrations to `the_last_messenger_test`, then to `the_last_messenger`; verify 8 tables exist and roll back by explicit migration name, never `--step=N`
- [X] T057 Verify no sunnah.com identifier leaked into the schema: `grep -rnE "HadithTable|BookData|ChapterData|arabicURN|englishURN" database/migrations` must return nothing
- [X] T058 Set `innodb_ft_min_token_size=2` in the MySQL container configuration, restart, and rebuild the FULLTEXT index; record the per-environment procedure in `specs/001-hadith-rebuild/quickstart.md`
- [X] T059 Create `database/seeders/Hadith/HadithCollectionSeeder.php` seeding all 17 collections with `code`, translated names, author, capability flags, and `reference_template`

### 2e. Domain core

Follows `app/Domain/Seerah/`. Three repository pairs; no `Support/` namespace.

- [X] T060 [P] Create `app/Domain/Hadith/Enums/HadithGrade.php` with cases sahih, hasan, daif, mawdu, unknown
- [X] T061 [P] Create `app/Domain/Hadith/Enums/GradeScope.php` with cases hadith, isnad
- [X] T062 [P] Create `app/Domain/Hadith/Enums/ImportStatus.php` with cases pending, running, completed, failed
- [X] T063 [P] Create `app/Domain/Hadith/Models/HadithCollection.php` with explicit `$fillable`, `$translatable`, and `$casts`
- [X] T064 [P] Create `app/Domain/Hadith/Models/HadithBook.php`
- [X] T065 [P] Create `app/Domain/Hadith/Models/HadithChapter.php`
- [X] T066 Create `app/Domain/Hadith/Models/Hadith.php` with relations to book, chapter, texts, and gradings, applying `HasAuditLog` and `HasVersions`
- [X] T067 [P] Create `app/Domain/Hadith/Models/HadithText.php`
- [X] T068 [P] Create `app/Domain/Hadith/Models/HadithGrading.php`
- [X] T069 [P] Create `app/Domain/Hadith/Models/HadithScholar.php`
- [X] T070 [P] Create `app/Domain/Hadith/Models/HadithImportRun.php`
- [X] T071 [P] Create the DTO `app/Domain/Hadith/DTOs/ReferenceSet.php` as `final readonly` with canonical, inBook, translation, and translationDeprecated
- [X] T072 [P] Create the DTO `app/Domain/Hadith/DTOs/HadithDetail.php`
- [X] T073 [P] Create the DTO `app/Domain/Hadith/DTOs/HadithListItem.php`
- [X] T074 [P] Create the DTO `app/Domain/Hadith/DTOs/SearchCriteria.php` with a `perPage` accessor capped at 100
- [X] T075 [P] Create `app/Domain/Hadith/Repositories/CollectionRepositoryInterface.php` and `CollectionRepository.php`, owning collection, book, and chapter reads with explicit column selection
- [X] T076 [P] Create `app/Domain/Hadith/Repositories/HadithRepositoryInterface.php` and `HadithRepository.php`, with transactions inside the repository
- [X] T077 [P] Create `app/Domain/Hadith/Repositories/ScholarRepositoryInterface.php` and `ScholarRepository.php`
- [X] T078 [P] Create `app/Domain/Hadith/Services/HadithCacheKeys.php` following the `<scope>:<key>` shape used by `app/Domain/Seerah/Services/SeerahCacheKeys.php`
- [X] T079 Create `app/Domain/Hadith/Services/HadithVisibility.php` as the single publication gate; verify `grep -rn "is_published" app/Domain/Hadith` matches only this class and the models
- [X] T080 [P] Create `app/Domain/Hadith/Services/HadithDashboardStatsProvider.php` implementing `DashboardStatsProviderInterface`
- [X] T081 [P] Create `app/Domain/Hadith/Services/HadithExportAdapter.php` implementing `ExportableModuleInterface`
- [X] T082 [P] Create `app/Domain/Hadith/Services/HadithBulkHandler.php` implementing `BulkOperationHandlerInterface`
- [X] T083 [P] Create `database/factories/Hadith/` factories for collection, book, chapter, hadith, text, grading, and scholar
- [X] T084 [P] Write `tests/Feature/Hadith/Schema/CompositeForeignKeyTest.php` asserting the database rejects a hadith whose book belongs to another collection, and whose chapter belongs to another book
- [X] T085 [P] Write `tests/Feature/Hadith/Schema/ChapterDeletionTest.php` asserting deleting a chapter that still has hadiths is rejected
- [X] T086 [P] Write `tests/Feature/Hadith/Schema/ArabicOnlyHadithTest.php` asserting an Arabic-only hadith persists with exactly one `hadith_texts` row
- [X] T087 [P] Write `tests/Feature/Hadith/Schema/UrnUniquenessTest.php` asserting many NULL urns coexist while duplicate non-null urns are rejected
- [X] T088 Run the Phase 2 gate: `php artisan test --filter=Hadith`, `./vendor/bin/pint`, `composer deptrac`

**Checkpoint**: Schema applied and constraints proven by tests. User stories may now begin.

---

## Phase 3: User Story 1 - Read one hadith with correct references (Priority: P1) 🎯 MVP

**Goal**: A reader opens `/hadith/bukhari:1` and sees Arabic text, English translation, and the three
scholarly reference lines exactly as the source presents them.

**Independent Test**: Extract Sahih al-Bukhari Book 1 (7 hadiths), import it, open `/hadith/bukhari:1`,
and diff the three reference lines character-for-character against `https://sunnah.com/bukhari:1`.

> Extraction-source discovery moved to Phase 2c (T045–T047): the schema is authored once and the
> selectors decide which columns exist, so they cannot be discovered after the migrations are applied.

### Tests for User Story 1 ⚠️

> Write these tests FIRST and confirm they FAIL before implementing.

- [X] T089 [P] [US1] Write `tests/Unit/Hadith/ArabicTextNormalizerTest.php` asserting the normalised diacritised Bukhari 1 body equals its undiacritised form, and that alef, hamza, ya, and ta-marbuta variants fold
- [X] T090 [P] [US1] Write `tests/Unit/Hadith/ReferenceBuilderTest.php` asserting Bukhari 1 yields the three exact reference strings and the deprecation flag, and that a non-deprecated collection omits the note
- [X] T091 [P] [US1] Write `tests/Unit/Hadith/GradeNormalizerTest.php` with a fixture of the 130 observed raw grade strings in `tests/Fixtures/Hadith/raw-grades.json`, asserting exact expected claim lists, that no input loses its `raw_grade`, and that ambiguous prose yields `unknown`
- [X] T092 [P] [US1] Write `tests/Unit/Hadith/GradeNormalizerMultiClaimTest.php` asserting `Sanad Da'if wal-Hadith Hasan` yields two claims and `1: Hasan<br>2: Da'if` yields two labelled claims
- [X] T093 [P] [US1] Write `tests/Unit/Hadith/HadithRecordMapperTest.php` against a book-page JSON fixture in `tests/Fixtures/Hadith/bukhari-book-1.json`
- [X] T094 [P] [US1] Write `tests/Feature/Hadith/Import/ProvenanceIsNotGradingTest.php` asserting an `authenticated_by` value naming the collection's compiler produces zero grading rows
- [X] T095 [P] [US1] Write `tests/Feature/Hadith/Import/ImportIdempotencyTest.php` asserting a second import of the same source leaves row counts unchanged
- [X] T096 [P] [US1] Write `tests/Feature/Web/Hadith/HadithDetailPageTest.php` asserting the canonical route renders both locales and the three reference lines, and 404s for an unknown number

### Implementation for User Story 1

- [X] T097 [US1] Create `app/Domain/Hadith/Services/ArabicTextNormalizer.php` stripping tashkeel and tatweel and folding alef, hamza, ya, and ta-marbuta variants
- [X] T098 [US1] Create `app/Domain/Hadith/Services/ReferenceBuilder.php` producing a `ReferenceSet` from the collection template, book number, and in-book number
- [X] T099 [US1] Create `app/Domain/Hadith/Import/GradeNormalizer.php` returning a **list** of claims, each with grade, scope, and optional `part_label`, recording uninterpretable inputs
- [X] T100 [US1] Create `app/Domain/Hadith/Import/SourceFileReader.php` reading a book JSON file with `json_decode` behind a file-size guard
- [X] T101 [US1] Create `app/Domain/Hadith/Import/HadithRecordMapper.php` mapping extracted JSON to DTOs per the T047 matrix, deriving `search_body` via the normalizer
- [X] T102 [US1] Create `app/Domain/Hadith/Import/HadithImporter.php` upserting on `(collection_id, hadith_number)`, reconciling child text and grading rows, and recomputing `display_grade` in the same transaction
- [X] T103 [US1] Add the source-owned versus editor-owned field declaration to `app/Domain/Hadith/Import/HadithImporter.php`, requiring `--reset` to overwrite editor-owned values
- [X] T104 [US1] Create `app/Console/Commands/HadithImportCommand.php` with `--collection=`, `--dry-run`, and `--reset`, using the T003 database guard
- [X] T105 [US1] Extract Sahih al-Bukhari Book 1 with the Chrome extension to `storage/app/hadith_source/bukhari/book_1.json`, writing to a temporary path and renaming on success
- [X] T106 [US1] Write the extraction manifest `storage/app/hadith_source/bukhari/book_1.manifest.json` recording extractor version, source URL, expected and actual counts, and a checksum
- [X] T107 [US1] Import `storage/app/hadith_source/bukhari/book_1.json` and confirm 7 hadiths with Arabic and English text rows plus their gradings
- [X] T108 [US1] Create `app/Domain/Hadith/Services/HadithQueryService.php` with `findByCollectionAndNumber()` returning a `HadithDetail` including its `ReferenceSet`
- [X] T109 [US1] Register the canonical route `/hadith/{collection}:{number}` in `routes/web.php`, declaring the static `/hadith/search` and `/hadith/urn/{urn}` routes first and constraining every dynamic segment
- [X] T110 [US1] Create `app/Http/Controllers/Site/HadithController.php` with a `show()` action calling exactly one service method
- [X] T111 [US1] Create `resources/views/public/hadith/show.blade.php` ported verbatim from `the-last-messenger-ui/hadith-single-enhanced.html`, using Amiri for hadith text and Cairo for UI
- [X] T112 [P] [US1] Add the Hadith translation keys used by the detail page to all five files `resources/lang/{ar,en,fr,tr,ur}/hadith.php`
- [X] T113 [US1] Run `npm run build` and verify `/hadith/bukhari:1` in Chrome at Arabic and English, light and dark, and 375, 768, and 1280 pixels wide

**Checkpoint**: US1 is independently shippable — one hadith renders with references matching the source.

---

## Phase 4: User Story 2 - Browse the collection hierarchy (Priority: P1)

**Goal**: A reader navigates hub → collection → book → chapter and reads hadiths in order with chapter
introductions and endings.

**Independent Test**: The hub lists 17 collections, `/hadith/bukhari` lists 97 books, and
`/hadith/bukhari/2` renders 51 hadiths under 43 chapter headings.

### Tests for User Story 2 ⚠️

- [X] T114 [P] [US2] Write `tests/Feature/Web/Hadith/HadithHubPageTest.php` asserting only published collections appear
- [X] T115 [P] [US2] Write `tests/Feature/Web/Hadith/HadithBookPageTest.php` asserting hadiths render under chapter headings in `in_book_number` order, and that unknown books 404
- [X] T116 [P] [US2] Write `tests/Feature/Web/Hadith/SyntheticBookHiddenTest.php` asserting a synthetic book never appears in public listings or counts
- [X] T117 [P] [US2] Write `tests/Feature/Web/Hadith/BookPageQueryCountTest.php` asserting the query count is identical at 20 and 100 hadiths per page

### Implementation for User Story 2

- [X] T118 [US2] Add `listPublishedCollections()`, `booksFor()`, and `chaptersFor()` to `app/Domain/Hadith/Repositories/CollectionRepository.php` with explicit column selection and `withCount` aggregates
- [X] T119 [US2] Add the synthetic-book exclusion to `app/Domain/Hadith/Services/HadithVisibility.php`
- [X] T120 [US2] Add `hub()`, `collection()`, and `book()` actions to `app/Http/Controllers/Site/HadithController.php`
- [X] T121 [US2] Register `/hadith`, `/hadith/{collection}`, and `/hadith/{collection}/{bookNumber}` in `routes/web.php` after the static routes
- [X] T122 [P] [US2] Create `resources/views/public/hadith/index.blade.php` ported from `the-last-messenger-ui/hadith-books-enhanced.html`
- [X] T123 [P] [US2] Create `resources/views/public/hadith/collection.blade.php` listing books in source order
- [X] T124 [P] [US2] Create `resources/views/public/hadith/book.blade.php` ported from `the-last-messenger-ui/hadith-book-single-enhanced.html`
- [X] T125 [P] [US2] Create `resources/views/public/hadith/partials/chapter.blade.php` ported from `the-last-messenger-ui/hadith-book-chapter-enhanced.html`, rendering chapter intro and ending
- [X] T126 [US2] Add empty and error states to every Hadith public view, using `resources/views/components/site/empty-state.blade.php`
- [X] T127 [P] [US2] Add the browse translation keys to all five files `resources/lang/{ar,en,fr,tr,ur}/hadith.php`
- [X] T128 [US2] Extract all 97 Bukhari books with the Chrome extension into `storage/app/hadith_source/bukhari/`, throttled to roughly one request per second with per-book manifests
- [X] T129 [US2] Import Bukhari fully and verify 7,277 hadiths, then confirm `/hadith/bukhari/2` shows 51 hadiths under 43 chapters
- [X] T130 [US2] Run `npm run build` and verify all four page types in Chrome across both locales, both themes, and three viewports

**Checkpoint**: A reader can browse Sahih al-Bukhari end to end.

---

## Phase 5: User Story 3 - Search in Arabic and English (Priority: P2)

**Goal**: A reader searches in either script and gets relevant hadiths, filterable by collection and grade.

**Independent Test**: A two-character Arabic query returns hits; the same query with and without
diacritics returns identical result sets.

### Tests for User Story 3 ⚠️

- [ ] T131 [P] [US3] Write `tests/Feature/Hadith/Search/ArabicSearchTest.php` asserting a two-character Arabic term returns results and that diacritised and undiacritised queries return identical sets
- [ ] T132 [P] [US3] Write `tests/Feature/Hadith/Search/SearchFilterTest.php` asserting collection, grade, and locale filters narrow results correctly
- [ ] T133 [P] [US3] Write `tests/Feature/Hadith/Search/SearchPageSizeCapTest.php` asserting an oversized `per_page` clamps to 100
- [ ] T134 [P] [US3] Write `tests/Feature/Web/Hadith/SearchEmptyStateTest.php` asserting a no-match search renders a meaningful empty state

### Implementation for User Story 3

- [ ] T135 [US3] Create `app/Domain/Hadith/Services/HadithSearchService.php` matching `MATCH(search_body) AGAINST (… IN BOOLEAN MODE)`, normalising the query through `ArabicTextNormalizer` before matching
- [ ] T136 [US3] Add `search()` to `app/Domain/Hadith/Repositories/HadithRepository.php` with explicit column selection and a capped page size
- [ ] T137 [US3] Add a `search()` action to `app/Http/Controllers/Site/HadithController.php` and apply `throttle:search` to the route in `routes/web.php`
- [ ] T138 [US3] Create `app/Http/Requests/Site/HadithSearchRequest.php` validating `q` at 2–200 characters and the optional filters
- [ ] T139 [US3] Create `resources/views/public/hadith/search.blade.php` ported from `the-last-messenger-ui/hadith-explorer.html`, reusing `components/site/filter-pills.blade.php`
- [ ] T140 [P] [US3] Add the search translation keys to all five files `resources/lang/{ar,en,fr,tr,ur}/hadith.php`
- [ ] T141 [US3] Run `EXPLAIN` on the search query against the imported corpus and record the plan in `specs/001-hadith-rebuild/quickstart.md`, confirming FULLTEXT index use with no full scan
- [ ] T142 [US3] Run `npm run build` and verify the search page in Chrome across both locales, both themes, and three viewports

**Checkpoint**: Arabic and English search work, with the token-size trap proven closed.

---

## Phase 6: User Story 4 - Editor curates content (Priority: P2)

**Goal**: An editor signs in, finds hadiths, corrects translations, records gradings, manages the
hierarchy, and controls public visibility.

**Independent Test**: Adding a grading updates the displayed grade per policy; unpublishing a hadith
makes it 404 publicly.

### Tests for User Story 4 ⚠️

- [ ] T143 [P] [US4] Write `tests/Feature/Admin/Hadith/AuthorizationTest.php` asserting every admin action denies an unauthorised user
- [ ] T144 [P] [US4] Write `tests/Feature/Admin/Hadith/DisplayGradeDerivationTest.php` asserting the documented policy — one distinct hadith-scope grade wins, conflicts and part-labelled claims yield `unknown` — and that `display_grade` is not directly writable
- [ ] T145 [P] [US4] Write `tests/Feature/Admin/Hadith/UnpublishHidesFromPublicTest.php` asserting an unpublished hadith 404s on its canonical URL
- [ ] T146 [P] [US4] Write `tests/Feature/Admin/Hadith/OptimisticLockingTest.php` asserting a stale-`updated_at` save is rejected
- [ ] T147 [P] [US4] Write `tests/Feature/Admin/Hadith/ValidationTest.php` asserting field-level 422 responses for each FormRequest

### Implementation for User Story 4

- [ ] T148 [US4] Create `app/Domain/Hadith/Services/HadithAdminService.php` owning the `display_grade` recomputation invariant inside a transaction
- [ ] T149 [US4] Create `routes/admin/hadith.php` declaring actions explicitly per resource — only collections have `featured`, only books and chapters have `sort_order`
- [ ] T150 [P] [US4] Create `app/Http/Controllers/Admin/Hadith/CollectionController.php`
- [ ] T151 [P] [US4] Create `app/Http/Controllers/Admin/Hadith/BookController.php`
- [ ] T152 [P] [US4] Create `app/Http/Controllers/Admin/Hadith/ChapterController.php`
- [ ] T153 [P] [US4] Create `app/Http/Controllers/Admin/Hadith/HadithController.php`
- [ ] T154 [P] [US4] Create `app/Http/Controllers/Admin/Hadith/ScholarController.php`
- [ ] T155 [P] [US4] Create the 10 FormRequests under `app/Http/Requests/Admin/Hadith/` — a Store and an Update per resource
- [ ] T156 [P] [US4] Create the collection admin views under `resources/views/admin/hadith/collections/` using `components/admin/data-table.blade.php` with server-side paging
- [ ] T157 [P] [US4] Create the book admin views under `resources/views/admin/hadith/books/`
- [ ] T158 [P] [US4] Create the chapter admin views under `resources/views/admin/hadith/chapters/`
- [ ] T159 [US4] Create the hadith admin views under `resources/views/admin/hadith/hadiths/`, editing grading rows and per-locale text with `display_grade` rendered read-only
- [ ] T160 [P] [US4] Create the scholar admin views under `resources/views/admin/hadith/scholars/`
- [ ] T161 [US4] Wire `components/admin/language-tabs.blade.php` into every translatable admin form with Arabic first
- [ ] T162 [US4] Create `resources/views/admin/hadith/import-runs/index.blade.php` surfacing `counts` and `unmapped_grades`
- [ ] T163 [US4] Add `updated_at` optimistic-locking checks to `app/Domain/Hadith/Services/HadithAdminService.php`
- [ ] T164 [US4] Register the Hadith admin navigation in `resources/views/components/admin/sidebar.blade.php`
- [ ] T165 [P] [US4] Add the admin translation keys to all five files `resources/lang/{ar,en,fr,tr,ur}/hadith.php`, defaulting admin views to Arabic
- [ ] T166 [US4] Run `npm run build` and verify the admin panel at `/su/portal/v1/admin/hadith` in Chrome

**Checkpoint**: An editor can curate without touching the database.

---

## Phase 7: User Story 5 - Complete corpus (Priority: P2)

**Goal**: All 17 collections extracted and imported, with reconciliation proving nothing was lost.

**Independent Test**: Every collection's imported count matches its manifest, and every skipped record is
itemised with a reason.

### Tests for User Story 5 ⚠️

- [ ] T167 [P] [US5] Write `tests/Feature/Hadith/Import/SyntheticBookImportTest.php` asserting a bookless collection imports against a hidden synthetic book
- [ ] T168 [P] [US5] Write `tests/Feature/Hadith/Import/ChildRowReconciliationTest.php` asserting a re-import removes child rows deleted from a corrected source
- [ ] T169 [P] [US5] Write `tests/Feature/Hadith/Import/EditorFieldPreservationTest.php` asserting a routine re-import preserves editor-owned fields and `--reset` overwrites them
- [ ] T170 [P] [US5] Write `tests/Feature/Hadith/Import/ManifestValidationTest.php` asserting a resumed run re-fetches a book whose manifest fails validation

### Implementation for User Story 5

- [ ] T171 [US5] Create the synthetic book for `nawawi40` in `database/seeders/Hadith/SyntheticBookSeeder.php` with `is_synthetic = true` and `book_number = '0'`
- [ ] T172 [US5] Add extraction fixtures for each layout variant to `tests/Fixtures/Hadith/layouts/` — standard, single-column Ahmad, the three forties, missing-English, chapterless, and combined numbers such as `1a`
- [ ] T173 [US5] Extract the remaining 15 collections with the Chrome extension into `storage/app/hadith_source/`, throttled, with a manifest per book
- [ ] T174 [US5] Import all remaining collections from `storage/app/hadith_source/` and record each run in the `hadith_import_runs` table
- [ ] T175 [US5] Create `app/Console/Commands/HadithReconcileCommand.php` producing a per-collection expected-versus-actual table with an itemised skip list
- [ ] T176 [US5] Create `app/Console/Commands/HadithDuplicateReportCommand.php` computing content hashes in-query and reporting duplicate groups without modifying data
- [ ] T177 [US5] Cross-check 20 sampled records against `prophet-data-generator/hadith/production/` and record itemised discrepancies in `specs/001-hadith-rebuild/quickstart.md`
- [ ] T178 [US5] Record the final reconciliation in `specs/001-hadith-rebuild/quickstart.md`: 17 collections, roughly 51,400 Arabic and 47,600 English text rows, and 607 manifests under `storage/app/hadith_source/`

**Checkpoint**: The full corpus is present and provably reconciled.

---

## Phase 8: User Story 6 - Third-party API access (Priority: P3)

**Goal**: A developer consumes a documented read-only API.

**Independent Test**: Every documented endpoint returns its documented shape, and error paths return the
documented codes.

### Tests for User Story 6 ⚠️

- [ ] T179 [P] [US6] Write `tests/Feature/Api/Hadith/CollectionEndpointsTest.php` covering 200 and 404 for the three collection endpoints
- [ ] T180 [P] [US6] Write `tests/Feature/Api/Hadith/BookChapterEndpointsTest.php` covering 200 and 404 for the book and chapter endpoints
- [ ] T181 [P] [US6] Write `tests/Feature/Api/Hadith/HadithEndpointsTest.php` covering the canonical lookup, the random endpoint, and permalink lookup including a clean 404 for a urn-less hadith
- [ ] T182 [P] [US6] Write `tests/Feature/Api/Hadith/SearchEndpointTest.php` covering 200, 422, and 429
- [ ] T183 [P] [US6] Write `tests/Feature/Api/Hadith/ApiQueryCountTest.php` asserting constant query counts at 20 and 100 per page

### Implementation for User Story 6

- [ ] T184 [US6] Create `app/Http/Controllers/Api/HadithController.php` with the ten actions from `contracts/hadith-api.md` §1–§10
- [ ] T185 [P] [US6] Create `app/Http/Resources/Hadith/HadithResource.php` returning the documented shape with only populated locales
- [ ] T186 [P] [US6] Create `app/Http/Resources/Hadith/HadithCollectionResource.php`, `HadithBookResource.php`, and `HadithChapterResource.php`
- [ ] T187 [US6] Register the ten routes under `/api/v1/hadith` in `routes/api.php`, reusing the single `throttle:search` limiter and `HadithSearchRequest` already defined for the web search surface in T137–T138 rather than declaring new ones
- [ ] T188 [US6] Implement the random endpoint in `app/Domain/Hadith/Repositories/HadithRepository.php` using the `(is_published, id)` index rather than `ORDER BY RAND()`
- [ ] T189 [US6] Document the API in `docs/HADITH-API-DOCUMENTATION.md`, stating explicitly that it is not drop-in compatible with sunnah.com's client contract

---

## Phase 9: Polish & Cross-Cutting Concerns

- [ ] T190 [P] Verify translation-key parity across `resources/lang/{ar,en,fr,tr,ur}/hadith.php` with zero missing keys
- [ ] T191 [P] Audit every new Blade view for logical properties only and flipped directional icons; fix any physical `left-`/`right-`/`pl-`/`pr-` usage
- [ ] T192 Run `EXPLAIN` on the five hot query paths against the full corpus and record the plans in `specs/001-hadith-rebuild/quickstart.md`; add composite indexes only where a plan demands one
- [ ] T193 Remove any index that T192 shows unused from the migrations in `database/migrations/`
- [ ] T194 [P] Update the Hadith section of the root `CLAUDE.md` to record Service + Repository, the 8-table list, and that Quran retains CQRS
- [ ] T195 [P] Update the Hadith rows of `WEB_APP_MODULES_ANALYSIS.md`
- [ ] T196 [P] Delete the superseded `docs/hadith-rebuild-plan.md` and `TODO_hadith-refactor.md`, whose content now lives in `specs/001-hadith-rebuild/`
- [ ] T197 Confirm the file-count and table-count targets from spec SC-013: under 45 files in `app/Domain/Hadith/` and 8 `hadith_*` tables
- [ ] T198 Run the full gate: `php artisan test`, `./vendor/bin/pint --test`, and `composer deptrac` with no Hadith entries in `deptrac.baseline.yaml`

---

## Dependencies & Execution Order

### Phase dependencies

- **Setup (Phase 1)**: no dependencies. T001–T003 must precede every destructive task.
- **Foundational (Phase 2)**: depends on Setup. Strictly ordered internally — 2a rewire (T007–T035) →
  2b delete (T036–T044) → 2c verify sources (T045–T047) → 2d schema (T048–T059) → 2e domain core
  (T060–T088). **Blocks all user stories.**
- **US1 (Phase 3)**: depends on Foundational in full. Its extraction groundwork now lives in 2c, because
  T046 and T047 can delete a column and must therefore precede T049/T050.
- **US2 (Phase 4)**: depends on US1's extraction and import pipeline.
- **US3 (Phase 5)**: depends on Foundational plus an imported corpus from US1 or US2.
- **US4 (Phase 6)**: depends on Foundational; richer once US2 has data.
- **US5 (Phase 7)**: depends on US1's pipeline and US2's pages for parity checking.
- **US6 (Phase 8)**: depends on Foundational plus an imported corpus.
- **Polish (Phase 9)**: depends on everything.

### Critical ordering rules

1. **T007 before T036.** `AppServiceProvider` imports Hadith services directly; deleting the module
   first makes the container fail to boot.
2. **All of 2a before any of 2b.** Rewire, then delete.
3. **T049 and T050 before T052.** The composite foreign keys need their parent unique keys to exist.
4. **T058 before T131.** Arabic search cannot pass before the token size is lowered and the index rebuilt.
5. **T045 before T099.** Without the grade selector the normalizer has nothing to normalise.
6. **T089–T096 before T097–T113.** Tests precede implementation within the story.
7. **Never `--step=N`** for any rollback; target the explicit migration name.

### Parallel opportunities

- Phase 1: T004, T005, T006 together.
- Phase 2a: T009–T014 together; T018–T029 together (distinct files).
- Phase 2b: T037–T042 together, after T036.
- Phase 2c: T045 and T046 together (different pages); T047 depends on both.
- Phase 2d: T048 and T051 together; T054 and T055 together.
- Phase 2e: T060–T062; T063–T065 and T067–T070; T071–T074; T075–T077; T080–T082; T084–T087.
- US1 tests: T089–T096 together.
- US2 views: T122–T125 together.
- US4 controllers: T150–T154 together; views T156–T158 and T160 together.
- US6 tests: T179–T183 together; resources T185–T186 together.
- Phase 9: T190, T191, T194, T195, T196 together.

---

## Implementation Strategy

### MVP scope

**Phases 1, 2, and 3 only** — Setup, Foundational, and User Story 1. This delivers one correctly
referenced, correctly graded, bilingual hadith rendered at its canonical URL, proving the entire
extract → import → render pipeline on a single book before any bulk work.

### Incremental delivery

1. Phases 1–2 → schema and module exist, constraints proven, application boots.
2. Phase 3 → **MVP**: one hadith readable and correctly cited.
3. Phase 4 → Sahih al-Bukhari fully browsable.
4. Phases 5–6 → search and editorial curation, deliverable in either order.
5. Phase 7 → the complete 17-collection corpus.
6. Phases 8–9 → public API and polish.

### Task summary

| Phase | Tasks | Count |
|---|---|---:|
| 1 — Setup | T001–T006 | 6 |
| 2 — Foundational | T007–T088 | 82 |
| 3 — US1 (P1, MVP) | T089–T113 | 25 |
| 4 — US2 (P1) | T114–T130 | 17 |
| 5 — US3 (P2) | T131–T142 | 12 |
| 6 — US4 (P2) | T143–T166 | 24 |
| 7 — US5 (P2) | T167–T178 | 12 |
| 8 — US6 (P3) | T179–T189 | 11 |
| 9 — Polish | T190–T198 | 9 |
| **Total** | | **198** |

Test tasks (creating files under `tests/`): 38. Tasks marked `[P]`: 114. Story-labelled tasks: 104.
