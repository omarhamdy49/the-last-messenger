# Phase 1 Data Model — Hadith and Muslim Identity Rebuild

MySQL 8, InnoDB, `utf8mb4_unicode_ci`. Every table here is created by a **new** migration; no
migration in this set drops, alters, or references a pre-existing table (plan → Scope).

Column naming avoids MySQL 8 reserved words: `primary` → `is_primary`, `order` → `sort_order`,
`from`/`to` → `first_hadith_number`/`last_hadith_number`. `grade` is **not** reserved and is kept.
`auther_id` → `compiler_id` (typo, and "compiler" is the correct Hadith role); `has_chapter` →
`has_chapters`.

Migration order is FK-dependency order: `islamic_roles` → `muslims` → `muslims_roles` →
`muslim_relationships` → `hadith_collections` → `hadith_books` → `hadith_chapters` → `hadiths` →
`hadith_narrators`.

---

## 1. `islamic_roles`

A capacity a person held. Seeded, not imported.

| Column | Type | Notes |
|--------|------|-------|
| `id` | BIGINT UNSIGNED PK | |
| `slug` | VARCHAR(100) **UNIQUE** | `sahabi`, `narrator`, `scholar`, `grader`, `compiler` |
| `name_ar` | VARCHAR(150) NOT NULL | |
| `name_en` | VARCHAR(150) NOT NULL | |
| `created_at`, `updated_at` | TIMESTAMP NULL | |

**Seed**: the five slugs above at minimum, plus any companion category that names a genuine capacity
(plan → Owner Decision 6). The importer reports which categories it mapped to roles and which it
discarded, so a discard is never silent (constitution X).

> **Naming**: `islamic_roles` reads like a Spatie Permission authorization table, which it is not —
> it carries no permissions and gates nothing. `historical_roles` would be unambiguous. Kept as
> `islamic_roles` per the owner's schema; flagged, not changed.

---

## 2. `muslims`

The application-wide identity. One row per historical figure regardless of how many capacities they
appear in (FR-001).

| Column | Type | Notes |
|--------|------|-------|
| `id` | BIGINT UNSIGNED PK | |
| `slug` | VARCHAR(200) **UNIQUE** | public identity |
| `thumb` | VARCHAR(255) NULL | scalar portrait — **no** MediaLibrary (Owner Decision 4) |
| `name_ar` | VARCHAR(200) NULL | |
| `name_en` | VARCHAR(200) NULL | |
| `kunya` | JSON NULL | `{ar,en}` — Spatie Translatable |
| `bio` | JSON NULL | `{ar,en}` |
| `content` | JSON NULL | `{ar,en}` |
| `gender` | VARCHAR(10) NULL | PHP-enum backed |
| `birth_place` | JSON NULL | `{ar,en}` |
| `birth_year_hijri` | INT **SIGNED** NULL | signed — pre-Hijra years are negative |
| `death_year_hijri` | INT **SIGNED** NULL | signed |
| `generation` | VARCHAR(50) NULL | |
| `search_name_ar` | VARCHAR(255) NULL | normalised; see below |
| `search_name_en` | VARCHAR(255) NULL | normalised |
| `father_id` | BIGINT UNSIGNED NULL | FK → `muslims`, **RESTRICT** |
| `mother_id` | BIGINT UNSIGNED NULL | FK → `muslims`, **RESTRICT** |
| `tree_display_order` | INT UNSIGNED NULL | sibling ordering under one parent |
| `hide_from_tree` | BOOLEAN NOT NULL DEFAULT 0 | present in data, suppressed in render |
| `is_published` | BOOLEAN NOT NULL DEFAULT 0 | |
| `created_at`, `updated_at` | TIMESTAMP NULL | |

```sql
CONSTRAINT chk_muslims_has_name
  CHECK (name_ar IS NOT NULL OR name_en IS NOT NULL)
```

Source records legitimately carry only one language, so neither name column can be `NOT NULL`; the
CHECK forbids only the nameless row (FR-003).

**Indexes**

| Index | Serves |
|-------|--------|
| `(is_published, id)` | directory pagination, random selection |
| `(father_id)` | descendant lookup by father |
| `(mother_id)` | descendant lookup by mother |
| FULLTEXT `(search_name_ar)` | Arabic person search + admin picker |
| FULLTEXT `(search_name_en)` | English person search + admin picker |
| `(gender, is_published, id)` | **only if** the gender directory ships — not before (constitution II) |

Two **separate** FULLTEXT indexes, never one composite: MySQL requires the `MATCH` column list to
exactly equal an index, and search issues separate Arabic and English branches (research → R5).

**`search_name_*` maintenance.** Built from **every** retained name field — `name_*` and `kunya` —
with Arabic folding (tashkeel, tatweel, alef/ya/ta-marbuta variants). Regenerated on **every admin
write**, not only at import. Import-only generation is the failure mode FR-035 exists to prevent: an
editor's correction would silently never become findable.

**Roles, not columns.** `person_type`, `is_sahabi`, and `is_companion` do not exist here. A companion
is a `muslims` row with the `sahabi` role.

---

## 3. `muslims_roles`

| Column | Type | Notes |
|--------|------|-------|
| `muslim_id` | BIGINT UNSIGNED | FK → `muslims` CASCADE |
| `role_id` | BIGINT UNSIGNED | FK → `islamic_roles` RESTRICT |
| `is_featured` | BOOLEAN NOT NULL DEFAULT 0 | |
| `sort_order` | INT UNSIGNED NOT NULL DEFAULT 0 | |
| `created_at`, `updated_at` | TIMESTAMP NULL | |

- **PK** `(muslim_id, role_id)` — a person holds a role once.
- Index `(role_id, is_featured, sort_order, muslim_id)` — the role-directory query, ordered and
  covering.

A person who was a companion, narrated hadith, and graded them is **one** row in `muslims` with three
rows here (FR-002).

---

## 4. `muslim_relationships`

Only the edges descent cannot express. Parent/child/grandparent are **never** stored here — they are
derived from `father_id`/`mother_id` (research → R8).

| Column | Type | Notes |
|--------|------|-------|
| `id` | BIGINT UNSIGNED PK | |
| `muslim_id` | BIGINT UNSIGNED | FK → `muslims` CASCADE |
| `related_muslim_id` | BIGINT UNSIGNED | FK → `muslims` CASCADE |
| `relationship_type` | VARCHAR(20) | `spouse`, `sibling`, `uncle`, `aunt`, `cousin` |
| `notes` | TEXT NULL | |
| `sort_order` | INT UNSIGNED NOT NULL DEFAULT 0 | |
| `hide_from_tree` | BOOLEAN NOT NULL DEFAULT 0 | |
| `created_at`, `updated_at` | TIMESTAMP NULL | |

```sql
UNIQUE KEY uq_muslim_relationships (muslim_id, related_muslim_id, relationship_type),
CONSTRAINT chk_muslim_relationships_not_self CHECK (muslim_id <> related_muslim_id)
```

- Index `(muslim_id, relationship_type)` and `(related_muslim_id)`.
- **Stored once.** Every type is symmetric, so the row is written in one direction and read with
  `muslim_id = ? OR related_muslim_id = ?`. Storing both directions doubles rows and lets the pair
  drift. The reading layer supplies the reverse label (a row read from the other side of `uncle` is
  nephew/niece).

**What the constraints cannot do**: the UNIQUE catches an exact duplicate, but `(A,B,sibling)` and
`(B,A,sibling)` are distinct rows to MySQL. The repository normalises pair order on write —
constraint plus service, because the constraint alone is insufficient.

---

## 5. `hadith_collections`

| Column | Type | Notes |
|--------|------|-------|
| `id` | BIGINT UNSIGNED PK | |
| `slug` | VARCHAR(200) **UNIQUE** | the single public key; `code` does not exist (Decision F) |
| `thumb` | VARCHAR(255) NULL | |
| `reference_template` | VARCHAR(255) NULL | **load-bearing** — see below |
| `title_ar` | VARCHAR(200) NOT NULL | |
| `title_en` | VARCHAR(200) NOT NULL | |
| `intro_ar` | MEDIUMTEXT NULL | |
| `intro_en` | MEDIUMTEXT NULL | |
| `compiler_id` | BIGINT UNSIGNED NULL | FK → `muslims` RESTRICT |
| `total_hadith` | INT UNSIGNED NULL | **source-declared total** — see below |
| `has_books` | BOOLEAN NOT NULL | |
| `has_chapters` | BOOLEAN NOT NULL | |
| `sort_order` | INT UNSIGNED NOT NULL DEFAULT 0 | |
| `is_featured` | BOOLEAN NOT NULL DEFAULT 0 | |
| `is_published` | BOOLEAN NOT NULL DEFAULT 0 | |
| `created_at`, `updated_at` | TIMESTAMP NULL | |

```sql
CONSTRAINT chk_collections_chapters_need_books
  CHECK (has_chapters = 0 OR has_books = 1)
```

- Index `(is_published, sort_order, id)` and `(is_published, is_featured, sort_order, id)`.

**`total_hadith` is the book's own declared total, not a row count.** It records what the collection
contains according to the source — "Sahih al-Bukhari contains 7,563 hadith" — which is a fact about
the book and cannot be derived from our rows, since we may hold fewer. Set at import, admin-editable,
and **never** recomputed from a row count.

The number of hadiths a reader can actually open is a **different** number and stays computed via
`withCount` under a published constraint. Resources expose the two under distinct names —
`total_hadith` and `available_hadiths_count` — so a template cannot confuse them and claim content
the site does not serve (research → R11).

**`reference_template` is not decoration.** Collections print the number *mid-line*
(`"Book {bookNumber}, Hadith {hadithNumber}"`, `"Hadith {hadithNumber}, 40 Hadith an-Nawawi"`), so the
number is substituted into a placeholder and never appended. Without this column there is no
canonical citation line (`ReferenceBuilder.php:10-26`).

**`slug` values come from `config/islamic-data.php`**, not from directory names. Six of the seventeen
differ (`ahmed→ahmad`, `aladab_almufrad→adab`, `bulugh_almaram→bulugh`, `mishkat_almasabih→mishkat`,
`riyad_assalihin→riyadussalihin`, `shamail_muhammadiyah→shamail`). Deriving slugs from directory
names produces six wrong public URLs (research → R4).

---

## 6. `hadith_books`

| Column | Type | Notes |
|--------|------|-------|
| `id` | BIGINT UNSIGNED PK | |
| `collection_id` | BIGINT UNSIGNED | FK → `hadith_collections` RESTRICT |
| `book_number` | VARCHAR(50) NOT NULL | **string** — source numbering is not reliably numeric (`35b`, `introduction`, `letter`) |
| `title_ar` | VARCHAR(500) NOT NULL | |
| `title_en` | VARCHAR(500) NULL | |
| `intro` | JSON NULL | `{ar,en}` |
| `first_hadith_number` | VARCHAR(50) NULL | was `from` (reserved) |
| `last_hadith_number` | VARCHAR(50) NULL | was `to` (reserved) |
| `sort_order` | INT UNSIGNED NOT NULL | |
| `is_published` | BOOLEAN NOT NULL DEFAULT 0 | |
| `created_at`, `updated_at` | TIMESTAMP NULL | |

- UNIQUE `(collection_id, book_number)`, `(collection_id, sort_order)`, **`(id, collection_id)`**.
- Index `(collection_id, is_published, sort_order)`.

`(id, collection_id)` exists solely as the parent key for the composite FK on `hadiths` — MySQL
requires a matching unique key on the referenced side. It is not a query index.

**No `is_synthetic`.** Nullable `hadiths.book_id` replaces synthetic books, which means the bookless
browse path in `HadithBrowseService` is **rewritten, not ported** — it currently relies on a hidden
synthetic book existing.

---

## 7. `hadith_chapters`

| Column | Type | Notes |
|--------|------|-------|
| `id` | BIGINT UNSIGNED PK | |
| `book_id` | BIGINT UNSIGNED | FK → `hadith_books` RESTRICT |
| `chapter_number` | VARCHAR(50) NOT NULL | **not unique** — real books repeat chapter numbers |
| `title_ar` | VARCHAR(500) NOT NULL | |
| `title_en` | VARCHAR(500) NULL | |
| `intro` | JSON NULL | `{ar,en}` |
| `ending` | JSON NULL | `{ar,en}` |
| `sort_order` | INT UNSIGNED NOT NULL | |
| `is_published` | BOOLEAN NOT NULL DEFAULT 0 | |
| `created_at`, `updated_at` | TIMESTAMP NULL | |

- UNIQUE `(book_id, sort_order)`, **`(id, book_id)`** (composite-FK parent key).
- Index `(book_id, is_published, sort_order)`.
- **No `(book_id, chapter_number)` index** — chapter browsing is by `book_id` ordered on
  `sort_order`. Add it only when a chapter-number lookup actually ships (constitution II).

---

## 8. `hadiths`

The central table. 51,441 rows.

| Column | Type | Notes |
|--------|------|-------|
| `id` | BIGINT UNSIGNED PK | |
| `collection_id` | BIGINT UNSIGNED **NOT NULL** | denormalised — see Complexity Tracking #4 |
| `book_id` | BIGINT UNSIGNED **NULL** | null for bookless collections |
| `chapter_id` | BIGINT UNSIGNED **NULL** | |
| `reference_key` | VARCHAR(100) NOT NULL | **public identity** (research → R2) |
| `number` | VARCHAR(50) NOT NULL | displayed scholarly number; **duplicates allowed** |
| `in_book_number` | VARCHAR(50) NULL | |
| `sort_order` | INT UNSIGNED NOT NULL | collection-scoped reading order |
| `grade` | VARCHAR(10) NULL | `sahih`/`hasan`/`daif`/`mawdu`, PHP-enum backed |
| `raw_grade` | TEXT NULL | verbatim source string |
| `text_ar` | LONGTEXT **NULL** | 38 records legitimately have none |
| `text_en` | LONGTEXT NULL | |
| `search_ar` | LONGTEXT NULL | normalised |
| `search_en` | LONGTEXT NULL | normalised |
| `narrated_by` | TEXT NULL | **TEXT, not VARCHAR** — observed up to 2,806 characters |
| `canonical_reference` | VARCHAR(255) NULL | |
| `in_book_reference` | VARCHAR(255) NULL | |
| `translation_reference` | VARCHAR(255) NULL | |
| `is_published` | BOOLEAN NOT NULL DEFAULT 0 | |
| `created_at`, `updated_at` | TIMESTAMP NULL | |

**Retired** (Owner Decision 2): `sanad`, `matn`, `translator`, `volume_number` — deleted with their
admin inputs, API keys, and Blade output. **Absent by Decision E**: `source_url`, `source_metadata`,
`source_id`, `sunnah_urn`, `sunnah_urn_en`, checksums.

### Constraints

```sql
UNIQUE KEY uq_hadiths_reference (collection_id, reference_key),
UNIQUE KEY uq_hadiths_order     (collection_id, sort_order),

KEY ix_hadiths_book_collection  (book_id, collection_id),
KEY ix_hadiths_chapter_book     (chapter_id, book_id),

CONSTRAINT fk_hadiths_book_collection FOREIGN KEY (book_id, collection_id)
    REFERENCES hadith_books (id, collection_id) ON DELETE RESTRICT,
CONSTRAINT fk_hadiths_chapter_book FOREIGN KEY (chapter_id, book_id)
    REFERENCES hadith_chapters (id, book_id) ON DELETE RESTRICT,

CONSTRAINT chk_hadiths_chapter_requires_book
    CHECK (chapter_id IS NULL OR book_id IS NOT NULL),

CONSTRAINT chk_hadiths_publishable
    CHECK (is_published = 0 OR (text_ar IS NOT NULL AND search_ar IS NOT NULL))
```

The composite FKs are what make the denormalised `collection_id` safe: without them a hadith could
name collection A, a book in collection B, and a chapter in a third book entirely
(`2026_07_31_000005:57-60`). Child indexes are declared **explicitly** rather than relying on InnoDB
to create them.

A NULL component skips the composite FK check — correct for genuinely bookless rows — so
`chk_hadiths_chapter_requires_book` closes the chapter-without-book case the FK cannot see.

### Indexes

| Index | Serves |
|-------|--------|
| `(collection_id, number)` | scholarly-reference lookup — **non-unique**, numbers repeat |
| `(collection_id, is_published, sort_order)` | bookless / direct collection browse |
| `(book_id, is_published, sort_order)` | book browse |
| `(chapter_id, is_published, sort_order)` | chapter browse |
| `(is_published, grade, id)` | grade-filtered listing |
| `(is_published, id)` | **random selection + pagination** |
| FULLTEXT `(search_ar)` | Arabic search |
| FULLTEXT `(search_en)` | English search |

`(is_published, id)` is retained **alongside** `(is_published, grade, id)`. It is what random
selection uses; do not drop it without an `EXPLAIN` proving the composite serves that query.

---

## 9. `hadith_narrators`

| Column | Type | Notes |
|--------|------|-------|
| `hadith_id` | BIGINT UNSIGNED | FK → `hadiths` CASCADE |
| `narrator_id` | BIGINT UNSIGNED | FK → `muslims` RESTRICT |
| `position` | **UNSIGNED SMALLINT** | not TINYINT — the current pivot needs the width |
| `is_primary` | TINYINT(1) NOT NULL DEFAULT 0 | was `primary` (reserved) |
| `source_name_ar` | VARCHAR(255) NULL | the source's own spelling |
| `source_name_en` | VARCHAR(255) NULL | |
| `created_at`, `updated_at` | TIMESTAMP NULL | |

```sql
PRIMARY KEY (hadith_id, position),

is_primary TINYINT(1) NOT NULL DEFAULT 0,
primary_hadith_id BIGINT UNSIGNED
    GENERATED ALWAYS AS (CASE WHEN is_primary = 1 THEN hadith_id ELSE NULL END) STORED,
UNIQUE KEY uq_hadith_narrators_one_primary (primary_hadith_id)
```

The generated column enforces one primary narrator per hadith: a UNIQUE index permits multiple NULLs
in MySQL, so non-primary rows never collide while two primaries on one hadith do. The domain service
validates transactionally as well — the constraint is the backstop, not the only check.

- Index `(narrator_id, is_primary, hadith_id)` — primary-narrator profile listings.
- **No `UNIQUE(hadith_id, narrator_id)`** until corpus validation proves a narrator never recurs in a
  chain. Validate before constraining; the Tirmidhi incident (research → R2) is what assuming
  uniqueness costs.

`source_name_*` is kept alongside the FK because the source's spelling is evidence: it is how the
link can be audited after the fact (FR-026).

---

## Entity relationships

```
islamic_roles ──< muslims_roles >── muslims ──┐
                                       │  │   │
                     father_id/mother_id  │   └──< muslim_relationships >── muslims
                            (self, RESTRICT) │
                                             ├──< hadith_narrators >── hadiths
                                             └──  hadith_collections.compiler_id

hadith_collections ──< hadith_books ──< hadith_chapters
        │                    │                  │
        └────────────────────┴──────────────────┴──< hadiths
              (composite FKs enforce the triple is consistent)
```

## Validation rules by requirement

| Rule | Enforced by | Requirement |
|------|-------------|-------------|
| A person has at least one name | `chk_muslims_has_name` | FR-003 |
| Publishing needs Arabic text | `chk_hadiths_publishable` | FR-020 |
| Chapters need a book | `chk_hadiths_chapter_requires_book` | FR-017 |
| Chapters need books at collection level | `chk_collections_chapters_need_books` | FR-016 |
| Hadith/book/chapter belong to one collection | composite FKs | FR-017 |
| One public address per hadith | `uq_hadiths_reference` + pre-import validator | FR-013, FR-015 |
| Displayed numbers may repeat | `(collection_id, number)` non-unique | FR-014 |
| One primary narrator | generated column + UNIQUE | FR-025 |
| Nobody is their own sibling | `chk_muslim_relationships_not_self` | FR-008 |
| No ancestor cycles | **domain service** + CTE depth cap | FR-009 |
| Parents cannot be deleted | `RESTRICT` on `father_id`/`mother_id` | FR-012 |
| One role per person per role | PK `(muslim_id, role_id)` | FR-002 |

Only one rule in this table has no database enforcement: **cycle prevention**. No CHECK can express
"is not already my ancestor" — it needs a traversal. That is why it gets two tests (direct cycle and
deep cycle) rather than one.

## Genealogy tree query

MySQL 8 recursive CTE rooted at a selected `muslims.slug`, walking `father_id`/`mother_id` upward for
ancestors and the `(father_id)`/`(mother_id)` indexes downward for descendants. The CTE carries a
**depth cap** so a bad edge cannot run away — the backstop for the service-level cycle check.

Publication and `hide_from_tree` are applied **at render**, not at selection: the admin picker
deliberately includes unpublished people, because an unpublished person is still a valid ancestor
(FR-046).

## Driver note

CHECK constraints, STORED generated columns, composite FKs, and FULLTEXT are all MySQL-specific.
Tests run on MySQL (`phpunit.xml`), so per constitution VI these are asserted rather than assumed.
Constitution VI also requires driver-specific DDL to be wrapped in a driver guard — statements issued
via raw SQL rather than Blueprint should check the connection driver so the migration does not become
undiagnosably broken if the test connection ever changes.
