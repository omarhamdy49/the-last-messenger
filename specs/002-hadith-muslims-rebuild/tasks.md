# Tasks: Hadith and Muslim Identity Rebuild

**Input**: Design documents from `/specs/002-hadith-muslims-rebuild/`
**Prerequisites**: `plan.md`, `spec.md`, `research.md`, `data-model.md`, `contracts/`, `quickstart.md`

**Tests**: REQUIRED. Constitution VI ("Tests are part of the change") is marked NON-NEGOTIABLE, so
test tasks are not optional here.

**Organization**: Grouped by user story. Each story is independently implementable and testable.

---

## ⚠ Rules for the implementing model — read before starting

These exist because this feature has many places where a plausible-looking choice is wrong.

1. **`data-model.md` is the schema authority.** Copy column names, types, and nullability from it
   exactly. Do not add columns "that seem useful". Do not rename. Do not change a `NULL` to
   `NOT NULL` because it looks cleaner — several nullables are load-bearing and documented.
2. **Never invent data.** If a source record lacks Arabic text, import it unpublished. Do not
   substitute English, transliterate, or generate placeholder text. 38 records legitimately have no
   Arabic (`config/islamic-data.php:86-90`).
3. **Do not write `DROP TABLE` migrations.** The old tables are removed by deleting their migration
   *files*. If you find yourself writing a teardown migration, you have misread the plan.
4. **Do not add these columns back.** They were removed by explicit decision: `code`, `sunnah_urn`,
   `sunnah_urn_en`, `source_url`, `source_metadata`, `source_id`, any checksum, `sanad`, `matn`,
   `translator`, `volume_number`, `is_synthetic`, `person_type`, `is_sahabi`,
   `is_companion`, `tribe`, `status`.
5. **Reserved words**: `primary`, `order`, `from`, `to` are reserved in MySQL 8 — the schema uses
   `is_primary`, `sort_order`, `first_hadith_number`, `last_hadith_number`. `grade` is **not**
   reserved and is used as-is.
6. **Never use `Model::all()` or a bare `->get()`.** Every query names its columns via `->select([...])`
   and every list is paginated with `per_page` cast to `(int)` and capped (constitution II).
7. **Domain modules must not import each other.** `app/Domain/Hadith/` may not
   `use App\Domain\People\...`. Cross-module access goes through
   `app/Domain/Shared/Contracts/`. Database foreign keys crossing modules are fine — it is the PHP
   `use` statement that `composer deptrac` rejects.
8. **UI strings need all five locales** (`ar`, `en`, `fr`, `tr`, `ur`) via `__()`. Only hadith and
   person *content* narrows to `ar`/`en`. Admin views default to Arabic.
9. **Layout uses logical properties only** — `ms-`/`me-`/`ps-`/`pe-`/`start-`/`end-`. Never `ml-`,
   `mr-`, `pl-`, `pr-`, `left-`, `right-`.
10. **Run `npm run build` after any Blade change.** There is no Vite dev server here; new views
    silently 500 without it.
11. **If a task's premise looks wrong, stop and say so.** Do not improvise a workaround. Several
    tasks encode findings that cost this project real data once already.

**Path conventions**: Laravel app at repository root. Domain code in `app/Domain/{Module}/`, HTTP in
`app/Http/`, migrations in `database/migrations/`, tests in `tests/{Feature,Unit}/`.

---

## 🚦 Execution order — two tracks

**Owner direction (2026-08-06): defer Hadith, implement People first.** This also happens to be the
only executable order, because **T001 is failing**: the canonical hadith sources
(`../prophet-data-generator/hadith/production`) are not on this machine, so no hadith content can be
imported no matter how much code is written.

Task IDs below are **not renumbered** — they stay T001–T140 so every cross-reference and dependency
note remains valid. Work them in track order, not file order.

### Track A — People / identity (ACTIVE, unblocked)

Nothing in this track depends on the hadith sources. It is also a hard prerequisite for Track B:
`hadith_narrators.narrator_id` and `hadith_collections.compiler_id` both point at `muslims`.

| Order | Tasks | What it delivers |
|-------|-------|------------------|
| A1 | T003, T005, T006 | Checkpoint commit + constitution corrections |
| A2 | T008, T009ᴬ, T010ᴬ, T011ᴬ, T012ᴬ, T013 | Purge Sahaba + Genealogy only |
| A3 | T014, T015, T016 | `People` deptrac layer + `PersonReference` contract |
| A4 | T018, T019 | `Gender`, `RelationshipType` enums |
| A5 | T020–T025, T034ᴬ | `islamic_roles`, `muslims`, `muslims_roles`, `muslim_relationships` migrations |
| A6 | T035, T036, T037 | People models |
| A7 | T041ᴬ, T042ᴬ, T043, T044, T045ᴬ | People repository, seeder, factories |
| A8 | T060 | `ArabicNormalizer` (needed by `search_name_*`) |
| A9 | **US4** — T094, T096, T097, T098, T099, T101, T102, T103 | Person identity + profiles |
| A10 | **US5** — T105–T116 | Genealogy (fully unblocked) |
| A11 | **US6ᴬ** — T118, T119, T120, T121ᴬ, T123, T124, T125ᴬ, T127, T128, T129, T130, T131 | Muslim admin CRUD |
| A12 | T132ᴬ, T134, T135, T136, T137, T138 | Polish for the People surface |

ᴬ = do the People half of that task only; leave its Hadith half for Track B.

**Track A MVP = A1–A10.** That delivers unified person identity and a working family tree with no
hadith dependency at all.

### Track B — Hadith (BLOCKED on T001)

Do not start until `ls -d ../prophet-data-generator/hadith/production` succeeds.

| Order | Tasks |
|-------|-------|
| B1 | T001, T002, T004 — preflight stops |
| B2 | T007, and the Hadith half of T009–T012 |
| B3 | T017, T026–T033, T034ᴮ, T038–T040 |
| B4 | T041ᴮ, T042ᴮ, T045ᴮ, T046–T050 |
| B5 | **US1** — T051–T068 |
| B6 | **US2** — T069–T084 |
| B7 | **US3** — T085–T093 |
| B8 | Remainder of US4/US6 — T095, T100ᴮ, T104, T117, T122, T126 |
| B9 | T132ᴮ, T133, T139, T140 |

### Tasks blocked by T001

⛔ T001, T002, T004, T007, T017, T026–T033, T038, T039, T040, T046, T047, T048, T049, T050,
T051–T068, T069–T084, T085–T093, T095, T104, T117, T122, T126, T133, T139.

Everything else is workable now.

---

## Phase 1: Setup — Preflight and Purge

**Purpose**: Verify the rebuild can succeed, then destroy the old modules.

> **Track split.** T003, T005, T006, T008, T013 and the Sahaba/Genealogy halves of T009–T012 are
> **Track A** and runnable now. T001, T002, T004, T007 and the Hadith halves are **Track B**.
> T001–T004 are **stops** for Track B only: if any fails, do not proceed with the Hadith purge.

- [ ] T001 ⛔**[Track B]** Verify canonical sources exist at the path in `config/islamic-data.php:16-20` by running `ls -d "$(dirname "$PWD")/prophet-data-generator/hadith/production"` — if absent, **STOP and report**; the purge is unrecoverable without them
- [ ] T002 ⛔**[Track B]** Verify the source workspace contains all 17 collection directories listed in `config/islamic-data.php:32-50` and record the file count per directory for later parity comparison
- [ ] T003 Commit the entire working tree including the untracked file `database/migrations/2026_08_05_000006_add_exact_hadith_reference_fields.php` with `git add -A && git commit -m "chore: checkpoint before hadith rebuild purge"` — constitution VIII requires a committed checkpoint before deleting untracked files
- [ ] T004 ⛔**[Track B]** Verify `innodb_ft_min_token_size = 2` via `SHOW VARIABLES LIKE 'innodb_ft_min_token_size'`; if it is 3 (the default), change it in the MySQL config, restart MySQL, and re-verify — this cannot be set per-session and changing it later requires rebuilding every FULLTEXT index
- [ ] T005 [P] Correct the stale runtime versions in `.specify/memory/constitution.md` Technology Constraints from "PHP 8.2+, Laravel 12" to "PHP 8.5, Laravel 13" and bump the version line at the file's end to 1.1.0 (root `CLAUDE.md` is already corrected)
- [ ] T006 [P] Amend `.specify/memory/constitution.md` Principle V to distinguish UI strings (five locales, unchanged) from translatable content (per-feature), so this feature's `ar`/`en` content narrowing is a stated allowance rather than a silent violation
- [ ] T007 ⛔**[Track B]** Delete the old Hadith migration files: all of `database/migrations/2026_07_31_0000*.php` and `database/migrations/2026_08_05_0000*.php`
- [ ] T008 Delete the old person migration files: `database/migrations/2025_10_27_135000_create_sahaba_table.php`, `database/migrations/2025_10_27_140001_create_relationships_table.php`, and every sahaba/genealogy migration matching `2026_03_09_200003`, `2026_03_17_000004`, `2026_06_24_100000`, `2026_06_24_110000`, `2026_07_02_09000*`, `2026_07_02_140001`, `2026_07_31_000015`
- [ ] T009 [P] Delete the old domain directories — **Track A**: `app/Domain/Sahaba/` and `app/Domain/Genealogy/`; ⛔**Track B**: `app/Domain/Hadith/`
- [ ] T010 [P] Delete the old HTTP layer — **Track A** takes the Sahabi/Genealogy entries, ⛔**Track B** the Hadith ones: `app/Http/Controllers/Admin/{CollectionController,NarratorController,ScholarController,GradingLevelController,ThemeController,SahabiController,GenealogyController}.php`, `app/Http/Controllers/Api/HadithController.php`, `app/Http/Controllers/Site/{HadithController,HadithNarratorController,SahabaController}.php`, and `app/Http/Resources/{Hadith*,Sahabi*,Genealogy*}.php`
- [ ] T011 [P] Delete the old views — **Track A** takes `admin/sahaba/` and `admin/genealogy/`, ⛔**Track B** the hadith views and tests: `resources/views/public/hadith/`, `resources/views/admin/hadith/`, `resources/views/admin/sahaba/`, `resources/views/admin/genealogy/` and the old tests `tests/Feature/Api/Hadith/`, `tests/Feature/Web/Hadith/`, `tests/Unit/Hadith/`
- [ ] T012 **Track A** removes the Sahaba route block; ⛔**Track B** removes the Hadith route blocks. Remove the Hadith route block at `routes/api.php:60-89`, the Sahaba route block at `routes/api.php:123-135`, and the Hadith route block at `routes/web.php:70-91`
- [ ] T013 Commit each track's purge as a single commit so the tree is never half-purged — Track A: `git commit -m "chore: purge sahaba and genealogy modules"`; Track B: `git commit -m "chore: purge hadith module"`

**Checkpoint**: `php artisan route:list` runs without fatal errors. The app will not boot fully yet — that is expected and fixed in Phase 2.

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Schema, enums, models, and the module boundary. **No user story work can begin until this phase completes.**

### Module boundary

- [X] T014 Add a `People` layer to `deptrac.yaml` collecting `App\Domain\People\*`, mirroring the structure of the existing `DomainHadith` layer at line 44; remove the now-dead `DomainSahaba` layer at line 54
- [X] T015 Add ruleset entries in `deptrac.yaml` allowing `People` to depend on `Shared` only, and allowing `Controllers` and `DomainHadith` to depend on `Shared` — `DomainHadith` must **not** be allowed to depend on `People`
- [X] T016 [P] Create `app/Domain/Shared/Contracts/PersonReference.php` — a `final readonly` DTO with `public function __construct(public int $id, public string $slug, public ?string $nameAr, public ?string $nameEn) {}` — this is how Hadith code refers to a person without importing the `People` module

### Enums

- [ ] T017 [P] ⛔**[Track B]** Create `app/Domain/Hadith/Enums/Grade.php` — a `string`-backed PHP enum with cases `Sahih='sahih'`, `Hasan='hasan'`, `Daif='daif'`, `Mawdu='mawdu'`, plus a `label(string $locale): string` method returning Arabic and English labels
- [X] T018 [P] Create `app/Domain/People/Enums/Gender.php` — a `string`-backed enum with cases `Male='male'`, `Female='female'`, plus a `label(string $locale): string` method
- [X] T019 [P] Create `app/Domain/People/Enums/RelationshipType.php` — a `string`-backed enum with cases `Spouse='spouse'`, `Sibling='sibling'`, `Uncle='uncle'`, `Aunt='aunt'`, `Cousin='cousin'`, plus `label(string $locale): string` and `reverseLabel(string $locale): string` (the reverse of `Uncle` is nephew/niece); include a class docblock stating that parent, child, and grandparent relations are derived from `father_id`/`mother_id` and are deliberately never stored as relationship rows

### Migrations — author in this exact order

Each migration is a new file with a timestamp later than every surviving migration. Follow the
column list in `data-model.md` exactly. `down()` is `dropIfExists` for that table only.

- [X] T020 Create `database/migrations/2026_08_10_000001_create_islamic_roles_table.php` per `data-model.md` §1 — columns `id`, `slug` VARCHAR(100) unique, `name_ar` VARCHAR(150), `name_en` VARCHAR(150), timestamps
- [X] T021 Create `database/migrations/2026_08_10_000002_create_muslims_table.php` per `data-model.md` §2 — include `birth_year_hijri` and `death_year_hijri` as **signed** `integer()` (pre-Hijra years are negative), `father_id` and `mother_id` as nullable self-referencing foreign keys with `restrictOnDelete()`, and `search_name_ar`/`search_name_en` VARCHAR(255) nullable
- [X] T022 Add the name CHECK constraint to the `muslims` migration (T021) after `Schema::create`, using raw SQL because Blueprint cannot express it: `DB::statement('ALTER TABLE muslims ADD CONSTRAINT chk_muslims_has_name CHECK (name_ar IS NOT NULL OR name_en IS NOT NULL)')`
- [X] T023 Add two **separate** FULLTEXT indexes to the `muslims` migration (T021) — `$table->fullText('search_name_ar')` and `$table->fullText('search_name_en')` as distinct single-column indexes; do **not** create one composite fulltext index, because MySQL requires a `MATCH` column list to exactly equal an index and search issues separate Arabic and English branches
- [X] T024 Create `database/migrations/2026_08_10_000003_create_muslims_roles_table.php` per `data-model.md` §3 — composite primary key `(muslim_id, role_id)`, `is_featured`, `sort_order`, and the index `(role_id, is_featured, sort_order, muslim_id)`
- [X] T025 Create `database/migrations/2026_08_10_000004_create_muslim_relationships_table.php` per `data-model.md` §4 — unique `(muslim_id, related_muslim_id, relationship_type)`, indexes `(muslim_id, relationship_type)` and `(related_muslim_id)`, plus the raw-SQL CHECK `chk_muslim_relationships_not_self CHECK (muslim_id <> related_muslim_id)`
- [ ] T026 ⛔**[Track B]** Create `database/migrations/2026_08_10_000005_create_hadith_collections_table.php` per `data-model.md` §5 — include `reference_template` VARCHAR(255) nullable (load-bearing; `ReferenceBuilder` substitutes the number into its placeholder), `compiler_id` nullable FK to `muslims` with `restrictOnDelete()`, and the raw-SQL CHECK `chk_collections_chapters_need_books CHECK (has_chapters = 0 OR has_books = 1)`; include `total_hadith` as `unsignedInteger()->nullable()` holding the **source-declared** total (a fact about the book, never a row count); do **not** add a `code` column
- [ ] T027 ⛔**[Track B]** Create `database/migrations/2026_08_10_000006_create_hadith_books_table.php` per `data-model.md` §6 — `book_number` is VARCHAR(50) **not** an integer (real values include `35b`, `introduction`, `letter`); include the unique key `(id, collection_id)` which exists solely as the parent key for a composite foreign key
- [ ] T028 ⛔**[Track B]** Create `database/migrations/2026_08_10_000007_create_hadith_chapters_table.php` per `data-model.md` §7 — `chapter_number` VARCHAR(50) and **not unique** (real books repeat chapter numbers); include the unique key `(id, book_id)` as a composite-FK parent key; do not add a `(book_id, chapter_number)` index
- [ ] T029 ⛔**[Track B]** Create `database/migrations/2026_08_10_000008_create_hadiths_table.php` per `data-model.md` §8 — `collection_id` NOT NULL, `book_id` and `chapter_id` **nullable**, `text_ar`/`search_ar` **nullable**, `narrated_by` as `text()` not `string()` (values observed up to 2,806 characters), unique `(collection_id, reference_key)` and `(collection_id, sort_order)`
- [ ] T030 ⛔**[Track B]** Add the composite foreign keys and CHECK constraints to the `hadiths` migration (T029) in a second `Schema::table` call, declaring the child indexes explicitly rather than relying on InnoDB — see the exact DDL block in `data-model.md` §8 "Constraints", including `chk_hadiths_chapter_requires_book` and `chk_hadiths_publishable`
- [ ] T031 ⛔**[Track B]** Add two separate FULLTEXT indexes to the `hadiths` migration (T029) — `$table->fullText('search_ar')` and `$table->fullText('search_en')` as distinct indexes, and the six B-tree indexes listed in `data-model.md` §8 "Indexes"; keep **both** `(is_published, grade, id)` and `(is_published, id)` — the latter is what random selection uses
- [ ] T032 ⛔**[Track B]** Create `database/migrations/2026_08_10_000009_create_hadith_narrators_table.php` per `data-model.md` §9 — primary key `(hadith_id, position)`, `position` as `unsignedSmallInteger` (not tinyint), `is_primary` boolean, index `(narrator_id, is_primary, hadith_id)`; do **not** add `UNIQUE(hadith_id, narrator_id)`
- [ ] T033 ⛔**[Track B]** Add the single-primary-narrator generated column to the `hadith_narrators` migration (T032) via raw SQL, since Blueprint cannot express a STORED generated column with a CASE expression: `ALTER TABLE hadith_narrators ADD COLUMN primary_hadith_id BIGINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN is_primary = 1 THEN hadith_id ELSE NULL END) STORED, ADD UNIQUE KEY uq_hadith_narrators_one_primary (primary_hadith_id)` — a UNIQUE index permits multiple NULLs in MySQL, so non-primary rows never collide
- [X] T034 Wrap every raw-SQL statement in a driver guard — **Track A** covers T022 and T025, ⛔**Track B** covers T026, T030, and T033. From T022, T025, T026, T030, and T033 in a driver guard (`if (DB::connection()->getDriverName() === 'mysql')`) per constitution VI, so the migrations do not become undiagnosably broken if the connection driver ever changes

### Models

- [X] T035 [P] Create `app/Domain/People/Models/IslamicRole.php` with explicit `$fillable` (never `$guarded = []`) and a `muslims()` belongsToMany relation through `muslims_roles`
- [X] T036 [P] Create `app/Domain/People/Models/Muslim.php` — `use HasTranslations` with `$translatable = ['kunya', 'bio', 'content', 'birth_place']`, explicit `$fillable`, casts for `gender` to the `Gender` enum and `is_published`/`hide_from_tree` to boolean, and relations `roles()`, `father()`, `mother()`, `children()`, `relationships()`
- [X] T037 [P] Create `app/Domain/People/Models/MuslimRelationship.php` with explicit `$fillable`, a cast for `relationship_type` to the `RelationshipType` enum, and `muslim()`/`relatedMuslim()` relations
- [ ] T038 [P] ⛔**[Track B]** Create `app/Domain/Hadith/Models/HadithCollection.php` with explicit `$fillable` and relations `books()`, `hadiths()`; the compiler is referenced by `compiler_id` but is **not** an Eloquent relation to a `People` model — expose it through the `PersonReference` contract to keep deptrac passing
- [ ] T039 [P] ⛔**[Track B]** Create `app/Domain/Hadith/Models/HadithBook.php` and `app/Domain/Hadith/Models/HadithChapter.php` with explicit `$fillable`, `intro`/`ending` in `$translatable`, and their parent/child relations
- [ ] T040 [P] ⛔**[Track B]** Create `app/Domain/Hadith/Models/Hadith.php` with explicit `$fillable`, a cast for `grade` to the `Grade` enum, boolean cast for `is_published`, and relations `collection()`, `book()`, `chapter()`, `narrators()`

### Repository interfaces and seeder

- [X] T041 [P] Create `app/Domain/People/Repositories/MuslimRepositoryInterface.php` (**Track A**) and `app/Domain/Hadith/Repositories/HadithRepositoryInterface.php` (⛔**Track B**) declaring the read and write methods each story needs; services inject these interfaces, never the concrete classes
- [X] T042 [P] Create the Eloquent implementations `app/Domain/People/Repositories/EloquentMuslimRepository.php` (**Track A**) and `app/Domain/Hadith/Repositories/EloquentHadithRepository.php` (⛔**Track B**); every method uses `->select([...])` with named columns, and any multi-step write is wrapped in `DB::transaction()` **inside the repository**
- [X] T043 Bind both interfaces to their implementations in `app/Providers/AppServiceProvider.php`
- [X] T044 Create `database/seeders/IslamicRoleSeeder.php` seeding the five roles `sahabi`, `narrator`, `scholar`, `grader`, `compiler` with Arabic and English names, using `updateOrCreate` on `slug` so re-seeding is idempotent
- [X] T045 [P] Create model factories — **Track A**: `database/factories/{MuslimFactory,IslamicRoleFactory,MuslimRelationshipFactory}.php`; ⛔**Track B**: `database/factories/{HadithCollectionFactory,HadithBookFactory,HadithChapterFactory,HadithFactory}.php` — the Hadith factory must produce a valid `reference_key` and default `is_published` to false

### Foundational fixes to code outside these modules

These currently reference deleted tables and will fatal on boot.

- [ ] T046 [P] ⛔**[Track B]** Rewrite `app/Providers/FavoriteServiceProvider.php` — it eager-loads `texts`, `book`, and `collection` and builds number-based URLs; the `texts` relation no longer exists and URLs now use `reference_key`
- [ ] T047 [P] ⛔**[Track B]** Rewrite the Hadith adapter in `app/Domain/Topics/Repositories/TopicRepository.php` to query the new `hadiths` table; Topics remains the live cross-module theme capability, so do **not** build a Hadith themes table
- [ ] T048 [P] ⛔**[Track B]** Rewrite `app/Domain/Shared/Search/SearchQueryBuilder.php` to target `hadiths.search_ar`/`search_en` instead of the deleted `hadith_texts` table
- [ ] T049 [P] ⛔**[Track B]** Update the hardcoded old table lists in `app/Console/Commands/Portal/ExportPortalDataCommand.php` and `app/Console/Commands/Portal/VerifyPortalDataCommand.php`
- [ ] T050 [P] ⛔**[Track B]** Update `app/Models/ArticleLocale.php` to stop embedding `hadith_id` and `sahabi_id` in content blocks

**Checkpoint**: `php artisan migrate:fresh` succeeds, `php artisan db:seed --class=IslamicRoleSeeder` succeeds, `composer deptrac` passes, and the app boots. User story work can now begin.

---

## Phase 3: User Story 1 — Rebuild the corpus from canonical sources (Priority: P1) 🎯 MVP

**Goal**: An operator rebuilds the entire corpus from canonical files with verified per-collection parity.

**Independent Test**: Run the rebuild against an empty database; the report shows a per-collection match; deliberately truncating a source file makes the run exit non-zero; running it twice produces an identical corpus.

### Tests for User Story 1 (write first, confirm they fail)

- [ ] T051 [P] [US1] Schema constraint tests in `tests/Feature/Hadith/SchemaConstraintTest.php` — assert a composite-FK mismatch is rejected (hadith naming a book from another collection), deleting a referenced parent is restricted, a bookless hadith is accepted, a chapter without a book is rejected, repeated hadith numbers and repeated chapter numbers are accepted, and `reference_key` is unique per collection
- [ ] T052 [P] [US1] Publishable CHECK test in `tests/Feature/Hadith/PublishableConstraintTest.php` — assert publishing a hadith with `text_ar = null` is rejected at the database level, and that the same row saves fine while unpublished
- [ ] T053 [P] [US1] Single-primary-narrator test in `tests/Feature/Hadith/PrimaryNarratorTest.php` — assert two narrators with `is_primary = 1` on one hadith is rejected, while many `is_primary = 0` rows on the same hadith are accepted
- [ ] T054 [P] [US1] Grade projection unit tests in `tests/Unit/Hadith/GradeNormalizerTest.php` — one verdict projects; isnad + hadith claim projects the hadith verdict; a numbered-part claim yields null; conflicting claims yield null; unmapped wording yields null with `raw_grade` retained; English raw takes precedence over Arabic and a disagreement emits a warning; `authenticated_by` produces no grade at all
- [ ] T055 [P] [US1] Reference key validator test in `tests/Unit/Hadith/ReferenceKeyValidatorTest.php` — assert a duplicate key across the corpus is detected and reported, and that each collision the validator resolves is reported rather than silently suffixed
- [ ] T056 [P] [US1] Import parity test in `tests/Feature/Hadith/ImportParityTest.php` — assert a per-collection count mismatch produces a non-zero exit code and publishes nothing, and that the known-missing-Arabic tally is exactly 38

### Implementation for User Story 1

- [ ] T057 [US1] Create `app/Domain/Hadith/Import/ReferenceKeyValidator.php` — derives `reference_key` from source structural position as `{sourceBookPosition}-{sourceHadithNumber}` scoped to the collection (see `research.md` R2), validates uniqueness across all 17 collections, appends a deterministic suffix only on a proven collision, and reports every collision it resolves
- [ ] T058 [US1] Create `app/Console/Commands/ValidateReferenceKeysCommand.php` registered as `hadith:validate-reference-keys` — runs the validator across the whole corpus and exits non-zero on any duplicate; this must be runnable **before** `migrate:fresh`
- [ ] T059 [US1] Create `app/Domain/Hadith/Import/GradeNormalizer.php` implementing exactly the rule in `research.md` R3 — consider only unlabelled hadith-scoped verdicts; exactly one distinct verdict wins; conflict, isnad-only, part-specific, or unmapped yields null; `raw_grade` takes English then Arabic and warns on disagreement; `authenticated_by` never produces a grade
- [X] T060 **[Track A — needed by `search_name_*`, do this early]** Create `app/Domain/Shared/Support/ArabicNormalizer.php` — strips tashkeel and tatweel and folds letter variants (alef forms, ya/alef-maqsura, ta-marbuta/ha); this exact function is used both when writing `search_*` columns and when folding a search query, and the two must never diverge
- [ ] T061 [US1] Create `app/Domain/People/Import/MuslimImporter.php` — imports narrators, scholars, graders, compilers, and companions as `muslims` rows with role assignments, deduplicating so one person is one row; populates `search_name_ar`/`search_name_en` from name and kunya via `ArabicNormalizer`
- [ ] T062 [US1] Create `app/Domain/Hadith/Import/CanonicalHadithImporter.php` — reads the production directory, maps directory names to collection slugs using the map at `config/islamic-data.php:32-50` (six differ from their directory name, so never derive a slug from a directory name), and imports collections, books, chapters, hadiths, and narrator links
- [ ] T063 [US1] Make the importer transactional **per collection** so a failure leaves that collection either fully imported or not at all, wrapping the transaction inside the repository per constitution I
- [ ] T064 [US1] Import records lacking Arabic text as unpublished rather than skipping them, and count them; the tally must equal `config('islamic-data.known_missing_arabic')` exactly
- [ ] T065 [US1] Create `app/Console/Commands/ImportMuslimsCommand.php` (`islamic-data:import-muslims`) and `app/Console/Commands/ImportHadithCommand.php` (`islamic-data:import-hadith --collection=`)
- [ ] T066 [US1] Create `app/Console/Commands/VerifyImportCommand.php` (`islamic-data:verify`) reporting per collection: expected vs produced counts for collections, books, chapters, hadiths, narrator links, and muslims; unmapped grades; unresolved people; discarded categories; and the missing-Arabic tally — itemised, never averaged, exiting non-zero on any mismatch
- [ ] T067 [US1] Add the database-name allowlist assertion required by constitution VIII to a `RebuildCommand` wrapper — resolve `DB_DATABASE` explicitly, compare against an allowlist in config, and **abort** on mismatch; printing the name is explicitly not a control
- [ ] T068 [US1] Map any companion category that names a genuine capacity to an `islamic_roles` row during import, discard thematic categories, and report both lists so the discard is visible (constitution X)

**Checkpoint**: `php artisan migrate:fresh && php artisan db:seed --class=IslamicRoleSeeder && php artisan islamic-data:import-hadith && php artisan islamic-data:verify` completes with matching counts. Running it a second time produces identical results.

---

## Phase 4: User Story 2 — Read and cite a hadith at a stable address (Priority: P2)

**Goal**: A reader browses collection → book → chapter → hadith and gets a stable citable URL.

**Independent Test**: With one imported collection, navigate the full path, confirm the address resolves, and confirm two hadiths sharing a displayed number stay separately reachable.

### Tests for User Story 2

- [ ] T069 [P] [US2] Web route tests in `tests/Feature/Web/Hadith/HadithBrowseTest.php` — hub, collection, book, and hadith detail render; two hadiths sharing a `number` resolve separately by `reference_key`
- [ ] T070 [P] [US2] Publication gating tests in `tests/Feature/Web/Hadith/PublicationGatingTest.php` — a published hadith under an unpublished collection, book, or chapter returns 404 in **every** case; assert the detail, browse, and random paths separately
- [ ] T071 [P] [US2] Deleted route tests in `tests/Feature/Api/Hadith/DeletedRoutesTest.php` — `/hadith/urn/{urn}` and `/api/v1/hadith/hadiths/{urn}` return **404**, not a catch-all fallthrough
- [ ] T072 [P] [US2] Reference builder unit tests in `tests/Unit/Hadith/ReferenceBuilderTest.php` — the number is substituted into the template placeholder and never appended; a null template falls back to `slug + number`; `translation_deprecated` is true only for `bukhari`, `muslim`, and `malik`
- [ ] T073 [P] [US2] API contract tests in `tests/Feature/Api/Hadith/HadithApiTest.php` covering all nine surviving endpoints in `contracts/hadith-api.md`, each with success, 404, and 422 cases

### Implementation for User Story 2

- [ ] T074 [US2] Create `app/Domain/Hadith/Services/ReferenceBuilder.php` — builds the three citation lines on read from `reference_template` and the stored reference columns, substituting the number into the placeholder; keep `DEPRECATED_TRANSLATION_NUMBERING = ['bukhari', 'muslim', 'malik']` keyed on `slug` now that `code` is gone
- [ ] T075 [US2] Create `app/Domain/Hadith/Services/HadithBrowseService.php` — collection, book, and chapter browsing; the bookless path must be **rewritten** to handle `book_id IS NULL` rather than ported, because synthetic books no longer exist
- [ ] T076 [US2] Create `app/Http/Resources/HadithResource.php` and `app/Http/Resources/HadithCollectionResource.php` matching the exact shapes in `contracts/hadith-api.md`, with counts computed via `withCount` under a published constraint rather than read from a stored column
- [ ] T077 [P] [US2] Create `app/Http/Resources/{HadithBookResource,HadithChapterResource}.php`
- [ ] T078 [US2] Create `app/Http/Controllers/Api/HadithController.php` implementing the nine endpoints in `contracts/hadith-api.md`; narrow locale negotiation to `ar`/`en` only
- [ ] T079 [US2] Create `app/Http/Controllers/Site/HadithController.php` for the web surface — hub, collection, book, and hadith detail
- [ ] T080 [US2] Register the routes in `routes/api.php` and `routes/web.php`, declaring **static segments before dynamic ones** so `search` and `random` can never be read as a collection slug; widen the collection regex to `[a-z0-9_-]+` and constrain `{referenceKey}` to `[A-Za-z0-9._-]+`
- [ ] T081 [US2] Use the **colon** separator for the hadith detail web route — `/hadith/{collectionSlug}:{referenceKey}` — because `/hadith/{collectionSlug}/{bookNumber}` already occupies the slash form and `/hadith/bukhari/12` would otherwise be ambiguous between book 12 and reference key 12 (see `contracts/README.md`)
- [ ] T082 [P] [US2] Create the Blade views `resources/views/public/hadith/{hub,collection,book,show}.blade.php` in Arabic by default, using logical properties only, rendering hadith text in Amiri and UI text in Cairo
- [ ] T083 [US2] Apply publication gating in a single shared scope used by every read path — detail, browse, random, search, topics, and favourites — so it cannot be implemented inconsistently
- [ ] T084 [US2] Run `npm run build` after the Blade work

**Checkpoint**: A reader can browse and open any hadith; deleted routes 404; citations render correctly.

---

## Phase 5: User Story 3 — Search in Arabic or English (Priority: P3)

**Goal**: Bilingual search that matches Arabic regardless of diacritics and handles two-character terms.

**Independent Test**: The same Arabic query with and without diacritics returns identical result sets; a two-character Arabic query returns results.

### Tests for User Story 3

- [ ] T085 [P] [US3] Arabic search tests in `tests/Feature/Hadith/ArabicSearchTest.php` — diacritised and undiacritised queries return the same hadiths; variant letter forms match; a two-character term returns results; use **committed or truncated fixtures**, because InnoDB FULLTEXT is not transaction-visible in this test arrangement and a transactional fixture yields an empty result set that looks like a broken query
- [ ] T086 [P] [US3] English search and filter tests in `tests/Feature/Hadith/SearchFilterTest.php` — collection and grade filters narrow results and counts; unpublished hadiths never appear; pagination is capped
- [ ] T087 [P] [US3] Search reindex test in `tests/Feature/Hadith/SearchReindexTest.php` — editing a hadith's text makes it findable by the new wording without a separate reindex step

### Implementation for User Story 3

- [ ] T088 [US3] Create `app/Domain/Hadith/Services/HadithSearchService.php` issuing **separate** `MATCH(search_ar)` and `MATCH(search_en)` branches and merging by score; never `MATCH(search_ar, search_en)`, which MySQL rejects without a composite FULLTEXT index of exactly that column list
- [ ] T089 [US3] Fold the incoming query with the same `ArabicNormalizer` used to write `search_ar`; both sides of the comparison must use identical folding or diacritic-insensitive matching fails in one direction only
- [ ] T090 [US3] Populate `search_ar`/`search_en` on every write path — import **and** admin save — via a model observer or repository hook; import-only population is the failure mode FR-035 exists to prevent
- [ ] T091 [US3] Add search endpoints to `app/Http/Controllers/Api/HadithController.php` and `app/Http/Controllers/Site/HadithController.php`, both reusing one `HadithSearchRequest` and the shared `throttle:search` limiter
- [ ] T092 [P] [US3] Create the search Blade view `resources/views/public/hadith/search.blade.php` with collection and grade filters, Arabic-first
- [ ] T093 [US3] Cap `per_page`, cast it to `(int)`, and allowlist the sortable columns so no user input reaches query structure (constitution VII)

**Checkpoint**: Bilingual search works with correct Arabic behaviour and enforced limits.

---

## Phase 6: User Story 4 — One person, many roles (Priority: P4)

**Goal**: A single profile per historical figure, listing every role and their narrations.

**Independent Test**: One person with three roles appears in all three directories, their profile lists their narrations, and one name edit propagates everywhere.

### Tests for User Story 4

- [ ] T094 [P] [US4] Unified identity tests in `tests/Feature/People/MuslimProfileTest.php` — a person with three roles has exactly one profile and appears in all three role directories; a single name edit changes every reference
- [ ] T095 [P] [US4] ⛔**[Track B]** Narration listing tests in `tests/Feature/People/MuslimNarrationsTest.php` — the profile lists narrated hadiths, paginated, with a fixed query count and no N+1
- [ ] T096 [P] [US4] Muslim API contract tests in `tests/Feature/Api/People/MuslimApiTest.php` covering every endpoint in `contracts/muslims-api.md` with success, 404, and 422 cases
- [ ] T097 [P] [US4] Unpublished visibility tests in `tests/Feature/People/MuslimVisibilityTest.php` — an unpublished person 404s publicly but remains usable as a narrator, parent, and compiler

### Implementation for User Story 4

- [X] T098 [US4] Create `app/Domain/People/Services/MuslimDirectoryService.php` — role, featured, gender, and publication filtering, using the index `(role_id, is_featured, sort_order, muslim_id)`
- [ ] T099 [P] [US4] Create `app/Http/Resources/MuslimResource.php` and `app/Http/Resources/IslamicRoleResource.php` matching `contracts/muslims-api.md`; `thumb` is a single scalar path with no gallery, no `media[]`, and no conversions
- [ ] T100 [US4] Create `app/Http/Controllers/Api/MuslimController.php` implementing `/roles`, `/muslims`, `/muslims/search`, and `/muslims/{slug}` (**Track A**); add `/muslims/{slug}/hadiths` in ⛔**Track B**
- [ ] T101 [US4] Create `app/Http/Controllers/Site/MuslimController.php` and the profile view `resources/views/public/muslims/show.blade.php`, replacing the deleted narrator and companion profile pages
- [ ] T102 [US4] Register the Muslim routes in `routes/api.php` and `routes/web.php`, including the `/hadith/narrators/{slug}` → `/muslims/{slug}` replacement
- [ ] T103 [US4] Add the person-search endpoint using FULLTEXT over `search_name_ar`/`search_name_en` with separate branches per language
- [ ] T104 [US4] ⛔**[Track B]** Eager-load relations as fixed lists on every read path so query counts stay constant, and assert those counts in the tests from T095

**Checkpoint**: One person, one profile, all roles, narrations listed.

---

## Phase 7: User Story 5 — Explore the family tree from any person (Priority: P5)

**Goal**: A tree rendered from any selected person, with cycle-safe editing.

**Independent Test**: Build a three-generation family, render from a middle person, and confirm both ancestors and descendants appear in the right order.

### Tests for User Story 5

- [X] T105 [P] [US5] Cycle prevention tests in `tests/Feature/People/GenealogyCycleTest.php` — self-parent rejected, direct cycle A→B→A rejected, and a **deep** cycle A→B→C→A rejected via the ancestor walk; the deep case is the one no constraint can catch
- [X] T106 [P] [US5] Tree query tests in `tests/Feature/People/GenealogyTreeTest.php` — the recursive CTE returns ancestors and descendants, honours its depth cap, and reports `depth_capped` rather than silently truncating
- [X] T107 [P] [US5] Tree visibility tests in `tests/Feature/People/GenealogyVisibilityTest.php` — `hide_from_tree` and unpublished ancestors are suppressed in the render but still selectable in the admin picker; siblings order by `tree_display_order`
- [X] T108 [P] [US5] Relationship symmetry tests in `tests/Feature/People/RelationshipSymmetryTest.php` — one stored row is readable from both sides with the correct reverse label; `(A,B,sibling)` then `(B,A,sibling)` is rejected as a duplicate
- [X] T109 [P] [US5] Parent deletion test in `tests/Feature/People/ParentDeletionTest.php` — deleting a person who is another person's parent is refused with 409, not silently orphaning the subtree

### Implementation for User Story 5

- [X] T110 [US5] Create `app/Domain/People/Services/GenealogyTreeService.php` — a MySQL 8 recursive CTE rooted at a `muslims.slug`, walking `father_id`/`mother_id` upward for ancestors and the `(father_id)`/`(mother_id)` indexes downward for descendants, with a depth cap read from config
- [X] T111 [US5] Implement the ancestor-walk cycle check in `GenealogyTreeService` — before writing `father_id` or `mother_id`, walk the target's ancestors and reject if the record being edited appears; no CHECK constraint can express this, so the service is the only real guard and the CTE depth cap is the backstop
- [X] T112 [US5] Normalise pair order in the relationship repository before writing, so `uq_muslim_relationships` can actually catch `(A,B,sibling)` versus `(B,A,sibling)` — the unique index alone treats them as distinct rows
- [X] T113 [US5] Apply publication and `hide_from_tree` filtering at **render** time only, never at selection time
- [ ] T114 [P] [US5] Create `app/Http/Resources/MuslimTreeResource.php` matching `contracts/muslims-api.md`, including the `depth_reached` and `depth_capped` fields
- [ ] T115 [US5] Add `GET /muslims/{slug}/tree` to `app/Http/Controllers/Api/MuslimController.php`, capping the requested depth server-side regardless of the value supplied
- [ ] T116 [P] [US5] Create the tree view `resources/views/public/muslims/tree.blade.php`, Arabic-first with logical properties, rendering right-to-left correctly

**Checkpoint**: Trees render from any person; no cycle can be saved at any depth.

---

## Phase 8: User Story 6 — Manage content after the rebuild (Priority: P6)

**Goal**: Full admin CRUD in Arabic by default, with concurrent-edit protection.

**Independent Test**: Create a collection, book, and hadith, publish the chain, confirm it appears publicly, then unpublish the collection and confirm it disappears.

### Tests for User Story 6

- [ ] T117 [P] [US6] ⛔**[Track B]** Admin CRUD tests in `tests/Feature/Admin/HadithAdminTest.php` — create, edit, publish, and unpublish for collections, books, chapters, and hadiths, each with 401, 403, 404, and 422 cases
- [ ] T118 [P] [US6] Admin person CRUD tests in `tests/Feature/Admin/MuslimAdminTest.php` including role assignment and relationship editing
- [ ] T119 [P] [US6] Optimistic locking test in `tests/Feature/Admin/ConcurrentEditTest.php` — a second save over a concurrent change warns instead of silently overwriting
- [ ] T120 [P] [US6] Picker test in `tests/Feature/Admin/MuslimPickerTest.php` — the picker returns unpublished people, excludes the record being edited, and requires admin authorization

### Implementation for User Story 6

- [ ] T121 [P] [US6] Create the FormRequests — **Track A**: the Muslim and Relationship requests; ⛔**Track B**: the Hadith requests. `app/Http/Requests/Admin/{StoreHadithRequest,UpdateHadithRequest,StoreMuslimRequest,UpdateMuslimRequest,StoreRelationshipRequest}.php`, each rejecting unexpected fields
- [ ] T122 [US6] ⛔**[Track B]** Create `app/Http/Controllers/Admin/HadithCollectionController.php`, `HadithBookController.php`, `HadithChapterController.php`, and `HadithController.php` under `/su/portal/v1/admin`, each calling exactly one service method and containing no Eloquent
- [ ] T123 [US6] Create `app/Http/Controllers/Admin/MuslimController.php` and `app/Http/Controllers/Admin/IslamicRoleController.php`
- [ ] T124 [US6] Create `app/Http/Controllers/Admin/MuslimPickerController.php` serving `GET /su/portal/v1/admin/muslims/picker` — searches both `search_name_*` columns, **includes unpublished people**, excludes the edited record, returns only `{slug, name_ar, name_en, is_published}`, and requires admin authorization
- [ ] T125 [P] [US6] Create the admin Blade views — **Track A**: `resources/views/admin/muslims/`; ⛔**Track B**: `resources/views/admin/hadith/`. `resources/views/admin/hadith/{index,create,edit}.blade.php` and `resources/views/admin/muslims/{index,create,edit}.blade.php` with Arabic/English language tabs, Arabic as the default active tab
- [ ] T126 [US6] ⛔**[Track B]** Add the single-grade assignment control to the hadith admin form — one nullable grade, no per-scholar attribution, no scope or part labels
- [ ] T127 [US6] Retain optimistic locking on hadith and person edits so concurrent saves warn rather than overwrite
- [ ] T128 [US6] Regenerate `search_name_*` and `search_*` on every admin save, wiring the hook from T090
- [ ] T129 [US6] Register the admin routes in `routes/web.php` under the `/su/portal/v1/admin` prefix with auth and role middleware
- [ ] T130 [US6] Add all admin UI translation keys to `resources/lang/{ar,en,fr,tr,ur}/` — all five locales; a missing key in any one of them is a defect
- [ ] T131 [US6] Run `npm run build`

**Checkpoint**: Editors can manage all content; publication changes are immediately visible publicly.

---

## Phase 9: Polish & Cross-Cutting Concerns

- [ ] T132 [P] Add `EXPLAIN` assertions — **Track A** in `tests/Feature/People/IndexUsageTest.php` for the Muslim directory, person search, and tree; ⛔**Track B** in `tests/Feature/Hadith/IndexUsageTest.php` for hadith browse, search, and random; **delete any index no query uses** (constitution II forbids speculative indexes)
- [ ] T133 [P] Add the `(gender, is_published, id)` index only if the gender directory actually shipped and `EXPLAIN` shows it used
- [ ] T134 Run `composer deptrac` and confirm zero new violations; the baseline records pre-existing debt only and must never be used to admit a new one
- [ ] T135 [P] Run `./vendor/bin/pint` and fix all style issues
- [ ] T136 Verify every RTL page in Arabic, both themes, confirming no physical layout properties leaked in
- [ ] T137 [P] Confirm rate limits on search and all mutating endpoints, and that no client-facing error exposes SQL, stack traces, or internal paths
- [ ] T138 Run the full `php artisan test` suite and confirm green
- [ ] T139 ⛔**[Track B]** Execute `quickstart.md` end to end on a clean database, including the idempotency check — two full rebuilds must produce identical counts with zero manual cleanup between them
- [ ] T140 [P] Update `CLAUDE.md` module descriptions to replace the Sahaba and Genealogy sections with the new People module and the rebuilt Hadith module

---

## Dependencies & Execution Order

### Track A critical path (active)

```
T003 (checkpoint commit)
  → T008-T013ᴬ (purge Sahaba + Genealogy)
    → T014-T016 (People deptrac layer + PersonReference)
      → T018, T019 (enums)  →  T020-T025 (migrations, strict FK order)
        → T035-T037 (models) → T041ᴬ-T045ᴬ (repo, seeder, factories)
          → T060 (ArabicNormalizer — needed before search_name_* is populated)
            → US4 (identity) → US5 (genealogy)  ← Track A MVP ends here
              → US6ᴬ (Muslim admin) → T132ᴬ-T138 (polish)
```

### Track B critical path (blocked)

```
T001 ⛔ sources missing — nothing below can start
  → T002, T004 (preflight) → T007, T009ᴮ-T012ᴮ (purge Hadith)
    → T026-T034ᴮ (hadith migrations) → T038-T040 (models) → T046-T050 (outside-module fixes)
      → US1 (import) → US2 (read) → US3 (search) → B8 remainder → T139
```

### Cross-track dependency

Track B depends on Track A, not the reverse: `hadith_narrators.narrator_id` and
`hadith_collections.compiler_id` are foreign keys into `muslims`, so the People migrations must exist
before the Hadith ones can run. Doing Track A first is therefore correct regardless of the T001
blocker.

### Migration order is not negotiable

`islamic_roles` → `muslims` → `muslims_roles` → `muslim_relationships` (Track A) →
`hadith_collections` → `hadith_books` → `hadith_chapters` → `hadiths` → `hadith_narrators`
(Track B). Each depends on foreign keys created by the ones before it.

### Within each story

Tests → models → services → resources → controllers → routes → views. Confirm tests fail before implementing.

---

## Parallel Opportunities

```bash
# Track A — purge, after T003 commits the checkpoint:
T009ᴬ "Delete app/Domain/{Sahaba,Genealogy}/"
T010ᴬ "Delete Sahabi + Genealogy controllers and resources"
T011ᴬ "Delete admin/sahaba + admin/genealogy views"

# Track A — enums, independent files:
T018 "Gender enum"   T019 "RelationshipType enum"

# Track A — models, after migrations land:
T035 "IslamicRole"   T036 "Muslim"   T037 "MuslimRelationship"

# Track A — US5 genealogy tests, all independent files:
T105 T106 T107 T108 T109

# Track B — US1 tests, all independent files (once unblocked):
T051 T052 T053 T054 T055 T056
```

**Not parallelizable**: migrations T020–T025 and T026–T034 (strict FK order), and any two tasks
touching the same file — T021, T022, and T023 all edit the `muslims` migration, and T029, T030, and
T031 all edit the `hadiths` migration, so each trio runs in sequence.

---

## Implementation Strategy

### Current MVP = Track A, steps A1–A10

Unified person identity plus a working family tree — every historical figure as one record with
roles, selectable as a parent, relation, or tree root. **No hadith dependency at all**, so it ships
while the canonical sources are still missing.

**Stop and validate here**: create one person holding three roles and confirm a single profile serves
all three directories; build a three-generation family and confirm a deep cycle (A→B→C→A) is refused.
Those two checks prove the identity collapse and the cycle guard, which are the whole point of
Track A.

### Deferred MVP = Track B, US1

Once the sources arrive: a rebuilt, verified corpus with proven parity. **Validate by running the
rebuild twice** — if two runs disagree, `reference_key` derivation is not deterministic and
`research.md` R2 needs revisiting before anything is published.

### Incremental delivery

1. Phases 1–2 → schema exists, app boots, deptrac passes
2. Phase 3 → corpus imported and verified **(MVP)**
3. Track A US5 → genealogy trees from any person
4. Track A US6ᴬ → Muslim admin management **← everything above ships without hadith sources**
5. *(sources arrive)* Track B US1 → corpus imported and verified
6. Track B US2 → readers can browse and cite
7. Track B US3 → search
8. Track B B8 → narrator listings and hadith admin complete the deferred halves of US4/US6
9. Polish

### Task counts

| Phase | Story | Total | Track A (now) | Track B (blocked) |
|-------|-------|-------|---------------|-------------------|
| 1 | Setup / Purge | 13 | 9 | 4 |
| 2 | Foundational | 37 | 19 | 18 |
| 3 | US1 — Rebuild (P1) | 18 | 1 (T060) | 17 |
| 4 | US2 — Read & cite (P2) | 16 | 0 | 16 |
| 5 | US3 — Search (P3) | 9 | 0 | 9 |
| 6 | US4 — Person identity (P4) | 11 | 8 | 3 |
| 7 | US5 — Genealogy (P5) | 12 | 12 | 0 |
| 8 | US6 — Admin (P6) | 15 | 12 | 3 |
| 9 | Polish | 9 | 6 | 3 |
| | **Total** | **140** | **67** | **73** |

Roughly half the work is executable today. Tasks split across both tracks are counted in both, so the
track columns sum slightly above 140.

---

## Open decisions that block specific tasks

| Question | Blocks | Default if unanswered |
|----------|--------|-----------------------|
| Soft deletes on `muslims`? | T021 (**Track A, imminent**) | Add `deleted_at` with admin restore |
| Is `../prophet-data-generator` obtainable? | T001, and all of **Track B** | None — hard stop for Track B only; Track A proceeds regardless |

**Resolved since the plan was written**: `total_hadith` is kept as the source-declared total (Owner
Decision 1 → `research.md` R11); battles, timelines, and categories retired (Decision 6); `sanad`,
`matn`, `translator`, `volume_number` retired (Decision 2); scalar portrait only (Decision 4).
