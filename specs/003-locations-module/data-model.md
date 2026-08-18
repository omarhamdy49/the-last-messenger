# Phase 1 Data Model: Locations Module

**Feature**: `003-locations-module` | **Date**: 2026-08-18
**Engine**: MySQL 8, InnoDB, `utf8mb4_unicode_ci`

---

## Entity overview

```
                       ┌──────────────────────┐
                       │      locations       │
                       │  (Shared, leaf)      │
                       │                      │
                  ┌───▶│  parent_id ──────────┼──┐  self-referencing
                  │    │  type (enum)         │  │  SET NULL
                  │    │  latitude/longitude  │◀─┘
                  │    │  aliases (json)      │
                  │    └──────────┬───────────┘
                  │               │ 1..5
                  │               ▼
                  │    ┌──────────────────────┐
                  │    │ location_translations│
                  │    │  locale, name, desc  │
                  │    └──────────────────────┘
                  │
    ┌─────────────┼─────────────────┬────────────────────┐
    │             │                 │                    │
seerah_events   muslims        quran_surahs        (future consumers)
 .location_id   .birth_place_id .revelation_location_id

Arrows point INTO Shared. `locations` names none of these tables in PHP —
constitution IV. Reference counting goes through LocationUsageRegistry.
```

---

## Table: `locations`

| Column | Type | Null | Default | Notes |
|---|---|---|---|---|
| `id` | `bigint unsigned` | no | auto | |
| `slug` | `varchar(120)` | no | — | **unique**. Stable public identifier, e.g. `makkah`, `masjid-al-haram`. |
| `type` | `varchar(20)` | no | — | Backed by `LocationType` enum. See values below. |
| `parent_id` | `bigint unsigned` | yes | `null` | FK → `locations.id`, `ON DELETE SET NULL`. |
| `depth` | `tinyint unsigned` | no | `0` | Derived from the parent chain on save, never set by hand. Mirrors `categories.depth`. |
| `latitude` | `decimal(10,7)` | yes | `null` | Matches `seerah_events.latitude` precision exactly. |
| `longitude` | `decimal(10,7)` | yes | `null` | |
| `aliases` | `json` | yes | `null` | Array of alternate spellings. See matching rules. |
| `is_active` | `boolean` | no | `true` | Inactive places keep existing references, are not offered for new ones. |
| `display_order` | `int` | no | `0` | |
| `created_at` / `updated_at` | `timestamp` | yes | | |

**Indexes shipped**: `unique(slug)`; the index MySQL creates for the `parent_id` foreign key.

**Indexes deliberately NOT shipped**: `(is_active, type)`. No query in this design filters on both
columns, and constitution II forbids shipping an index before `EXPLAIN` shows it used. Add it only
if the admin index screen's `EXPLAIN` demands it.

**Column deliberately NOT shipped**: `zoom`. The existing map embed in
`SeerahController::mapEventDetail()` computes a bounding box (`latitude ± 0.04`), not a zoom level.
A `zoom` column would have had no reader — constitution IX: a column with no data source or consumer
is dropped, not shipped empty.

### `LocationType` values

`city` · `region` · `country` · `mountain` · `valley` · `mosque` · `well` · `battlefield` ·
`landmark`

Stored as `varchar(20)` rather than a MySQL `ENUM` so that adding a kind is a code change, not a
migration — consistent with how the repo stores other enum-backed columns.

### Hierarchy rules

- `parent_id` forms a tree. **A cycle must be rejected on write**, including the self-parent case
  (`parent_id = id`). Enforced in the model's saving hook, asserted by unit test.
- `depth` is recomputed whenever `parent_id` changes, by walking the parent chain — the same
  approach `Category::computeDepth()` already uses. The walk is bounded by a hop limit so a
  pre-existing bad row cannot hang the request.
- Expected depth is 0–3 (`الجزيرة العربية` → `الحجاز` → `مكة المكرمة` → `المسجد الحرام`).

---

## Table: `location_translations`

Mirrors `category_translations` exactly, so `HasLocalizedContent` works without special casing.

| Column | Type | Null | Notes |
|---|---|---|---|
| `id` | `bigint unsigned` | no | |
| `location_id` | `bigint unsigned` | no | FK → `locations.id`, `ON DELETE CASCADE`. |
| `locale` | `varchar(5)` | no | One of `ar`, `en`, `fr`, `tr`, `ur`. |
| `name` | `varchar(255)` | no | |
| `description` | `text` | yes | |
| `is_ready` | `boolean` | no | Default `false`. Matches the sibling translation tables. |
| `created_at` / `updated_at` | `timestamp` | yes | |

**Indexes**: `unique(location_id, locale)`; index `(locale, name)` for name lookup in the admin
select.

**Translatable fields**: `name`, `description` — declared by
`Location::getTranslatableFields()`.

Arabic is the default **and** the fallback locale (constitution V). A place with only an Arabic
translation renders in Arabic in every locale rather than rendering blank.

---

## Foreign keys added to existing tables

All three are nullable with `ON DELETE SET NULL`, added in a single migration. **No existing column
is dropped or altered.**

| Table | Column | Placed after | Retained sibling column |
|---|---|---|---|
| `seerah_events` | `location_id` | `location` | `location` (varchar), `latitude`, `longitude` — all unchanged |
| `muslims` | `birth_place_id` | `birth_place` | `birth_place` (json) — unchanged |
| `quran_surahs` | `revelation_location_id` | `revelation_place` | `revelation_place` (enum) — unchanged, permanently |

Each column gets the index MySQL creates for its foreign key, which also serves the `?unmapped=1`
filter (`WHERE location_id IS NULL AND location <> ''`).

**Cross-module foreign keys are permitted.** Constitution IV: "Database-level foreign keys may cross
modules — deptrac cannot see them, so the PHP-import rule is enforced in review." The existing
`hadith_narrators.narrator_id → muslims` is the same shape.

**Migration reversibility**: `down()` drops the three foreign keys and their columns, then the two
new tables. Nothing else is touched, so a rollback returns the schema to its exact prior state.
Driver-guarded per constitution VI.

---

## Alias matching rules

Normalisation applied to **both** sides before comparison:

1. Strip Arabic diacritics (tashkeel, `U+064B`–`U+0652`) and the tatweel `ـ`.
2. Unify alef forms: `أ إ آ ٱ` → `ا`.
3. Unify `ة` → `ه`, `ى` → `ي`.
4. Lowercase, and strip a leading `al-` / `Al-` / `as-` on Latin text.
5. Collapse runs of whitespace to one space, trim.

Selection: test every alias against the normalised source string and take the **longest** alias that
matches. Ties are impossible because aliases are unique across the vocabulary — the seeder aborts if
they are not.

Worked examples, drawn from live rows:

| Source text | Resolves to | Why |
|---|---|---|
| `المسجد النبوي بالمدينة` | `masjid-an-nabawi` | Longest match wins over `المدينة`. |
| `شِعب أبي طالب بمكة` | `makkah` | Diacritics stripped; `مكة` matched via the `بمكة` alias. |
| `حصون بني قريظة جنوب المدينة` | `al-madinah` | Only `المدينة` matches. |
| `Medinah` / `Medina` / `Madinah` | `al-madinah` | All three are registered aliases. |
| `al-Ta'if` / `Ta'if` | `at-taif` | Leading `al-` stripped. |
| `Makkah or Medinah` | **NULL** | Two places named — ambiguous, never guessed. |
| `المنطقة المحايدة بين الجيشين` | **NULL** | Names no place. |

---

## Resolver write rules

1. Writes a foreign key **only** where it is currently `NULL`. An editor's assignment is never
   overwritten (FR-013).
2. Ambiguous or unmatched source text leaves the key `NULL` (FR-011).
3. Surahs are deterministic, not alias-matched: `revelation_place = 'makkah'` → the Makkah location,
   `'madinah'` → the Madinah location. All 114 rows, no ambiguity.
4. Runs in chunks inside a transaction per chunk; a failure rolls that chunk back, and the command
   is safe to re-run (FR-012).
5. Reports per content type: examined, resolved, unresolved-no-match, unresolved-ambiguous,
   skipped-already-assigned. Counts must reconcile to the examined total — constitution X forbids
   averaging a discrepancy away.

---

## Seed data shape

~55 places. Every row carries `ar` and `en` names at minimum; `fr`, `tr`, `ur` where a settled name
exists, otherwise the Arabic value is inherited by fallback rather than transliterated by guess.

```
الجزيرة العربية (region)
├── الحجاز (region)
│   ├── مكة المكرمة (city)
│   │   ├── المسجد الحرام (mosque)   ├── غار حراء (landmark)
│   │   ├── غار ثور (landmark)        ├── منى · عرفات · مزدلفة (landmark)
│   │   └── الصفا والمروة (landmark)
│   ├── المدينة المنورة (city)
│   │   ├── المسجد النبوي (mosque)    ├── قباء (landmark)
│   │   ├── أُحُد (mountain)          └── بقيع الغرقد (landmark)
│   ├── الطائف (city)      ├── بدر (battlefield)   ├── خيبر (city)
│   ├── الحديبية (landmark) ├── حنين (valley)       ├── تبوك (city)
│   ├── ذو الحليفة (landmark) └── الجُحفة (landmark)
├── نجد (region)          └── اليمن (region) ── حضرموت · نجران
الشام (region) ── بُصرى · أذرعات · بيت المقدس ── المسجد الأقصى (mosque)
الحبشة (country) · مصر (country) · فارس (country) · الروم (country)
```

**Seeder self-check** (constitution X — abort, do not average away):
- No alias appears under two locations.
- No alias is empty or collides with a slug of a different place.
- `parent_id` contains no cycle and no reference to a place the seeder did not create.
- Every place has at minimum an `ar` name.

The seeder is idempotent, keyed on `slug`, so re-running it updates rather than duplicates.

---

## What consumers read

| Reader | Eager load | Notes |
|---|---|---|
| Seerah event page | `location:id,slug,latitude,longitude` + `location.translations:id,location_id,locale,name` | Parent select must include `location_id`. |
| Seerah event list / timeline | same | Constitution II: named columns, no `with('location')` bare. |
| Companion profile | `birthPlace:id,slug` + its translations | |
| Companion directory | same | The directory is the hot path — verify query count is unchanged. |
| Surah page | `revelationLocation:id,slug,latitude,longitude` + translations | |
| Admin place list | `parent:id` + `translations:id,location_id,locale,name`, `withCount` per registry pair | |

**Map coordinate precedence** (FR-023, FR-024):

```
event.latitude/longitude present  →  use them
else location.latitude/longitude present  →  use them
else  →  render no map, and no error
```
