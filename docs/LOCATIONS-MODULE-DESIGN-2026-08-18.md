# Locations Module — Design

**Date:** 2026-08-18
**Status:** Implemented on branch `003-locations-module`. See §9 for what changed during the build.
**Scope:** Lookup + map data. No public `/locations` routes, no place detail pages.

---

## 1. Problem

Three tables carry place information as unconstrained text. Measured against the
live database, not inferred:

| Column | Type | Rows filled | Distinct | Condition |
|---|---|---|---|---|
| `seerah_events.location` | `string` nullable | 279 / 578 | 215 | Descriptive Arabic prose, not place names |
| `muslims.birth_place` | `json` (Spatie) | 6,677 / 7,837 | 32 | English only, multiple spellings per place |
| `quran_surahs.revelation_place` | `enum` | 114 / 114 | 2 | Clean, but carries no coordinates or Arabic name |

Three consequences:

**1. `muslims.birth_place` has collapsed into spelling drift.** `Medinah` (743),
`Medina` (675) and `Madinah` (1) are one place stored three ways. So are
`Hijaz`/`HIjaz`, `Yemen`/`Yeman`, `Ta'if`/`al-Ta'if`, `Najd`/`Nejd`,
`Sarat`/`as-Surat`. Every value is English, on an Arabic-first site — 6,677
people have no Arabic birthplace at all. Some values are not single places:
`Yemen/Makkah`, `Makkah or Medinah`, `Baha, Yemen`.

**2. The Seerah map is dead.** `SeerahController::mapEventDetail()` builds an
OpenStreetMap embed gated on the event's own `latitude`/`longitude`. Only **2 of
578** events have coordinates, so 576 event pages silently render no map — including
all 279 that do name a place in text.

**3. Nothing can be filtered or grouped by place.** `SeerahEvent::scopeLocation()`
is a `LIKE '%…%'` over prose. There is no way to ask for "events at Badr" or
"companions born in Makkah".

### Not in scope

These matched a naive column scan and are deliberately excluded:

- `menu_items.location` — navigation slot (`header`, `footer`), not geography.
- `quran_words.location` — verse coordinate string, `"2:255:3"`.
- `home_module_cards.placement` — layout slot.
- `quran_reciters.country` — already ISO-2 (`SA` 17, `EG` 9, `KW` 2, 28 of 64
  rows). Clean and low-value; explicitly left alone.
- `muslims.profile` → `birth_region`, `death_place`, `mission_place` — 2 rows
  populated in total. Not worth a migration.

---

## 2. Placement and module boundary

`Location` and `LocationTranslation` live in **`app/Domain/Shared/Models/`**, with
`LocationRepository` + `LocationRepositoryInterface` in
`app/Domain/Shared/Repositories/`.

Not `app/Domain/Locations/`. Three separate domains consume this — Seerah, People
and Quran — and constitution IV forbids any domain from importing a sibling. A
`Domain/Locations` would therefore force all three through a `LocationLookup`
array-contract, the same tax `PersonLookup` pays for Hadith → People. That
contract returns hydrated value objects, which means no `with('location')` eager
loading and no `whereHas` — costly on the Seerah listing queries that already
work hard to avoid N+1.

A location is reference data, not a bounded context. The existing precedent is
`Shared\Models\Language`. Placement in `Shared` requires **no `deptrac.yaml`
change**, because every domain may already import `Shared`, and `Shared` still
imports from no domain — the invariant that protects module isolation.

---

## 3. Schema

### 3.1 New tables

**`locations`**

| Column | Type | Notes |
|---|---|---|
| `id` | `bigIncrements` | |
| `slug` | `string(120)` | unique |
| `type` | `enum` | `city`, `region`, `country`, `mountain`, `valley`, `mosque`, `well`, `battlefield`, `landmark` |
| `parent_id` | `foreignId` nullable | self-FK, `nullOnDelete` |
| `latitude` | `decimal(10,7)` nullable | |
| `longitude` | `decimal(10,7)` nullable | |
| `aliases` | `json` nullable | matching + admin autocomplete |
| `is_active` | `boolean` default `true` | |
| `display_order` | `integer` default `0` | |
| timestamps | | |

Indexes: `slug` (unique), `parent_id`, `(is_active, type)`.

**`location_translations`** — mirrors `category_translations` exactly:
`id`, `location_id`, `locale`, `name`, `description` nullable, `is_ready`,
timestamps. Unique `(location_id, locale)`, index `(locale, name)`.

`Location` uses the `HasLocalizedContent` trait. It does **not** use Spatie
`HasTranslations` — per CLAUDE.md, declaring `$translatable` for a column the
table lacks fatals on first write.

Aliases are a JSON column rather than a fourth table: matching runs in PHP over
~8,500 rows, so no join is needed, and alias uniqueness is asserted by the
seeder self-check (§6).

### 3.2 Foreign keys added

All nullable, all `nullOnDelete`. No existing column is dropped in this design.

| Table | New column | Existing column |
|---|---|---|
| `seerah_events` | `location_id` | `location` string — **kept**, becomes the descriptive note |
| `muslims` | `birth_place_id` | `birth_place` JSON — kept until phase 4 |
| `quran_surahs` | `revelation_location_id` | `revelation_place` enum — **kept permanently** |

### 3.3 Why `seerah_events.location` is kept

The column does not hold place names. It holds editorial prose:

```
حمراء الأسد على نحو 8 أميال جنوب المدينة
المنطقة المحايدة بين الجيشين
من مكة إلى الحبشة عبر ميناء الشعيبة
جبل عَيْنَيْن (المعروف اليوم بجبل الرماة، مقابل جبل أُحُد)
```

Replacing it with an FK deletes real content. Keeping it also means the exporter,
importer, DTOs, form requests, search enum and repository — roughly 30 call sites
— need no change at all.

**Routes are not modelled as a relation.** Only **9 of 279** distinct values are
route-shaped (`من … إلى …`). A `seerah_event_location` pivot with a role column
would be a table, a model and a set of sync paths built for nine rows. Those nine
events get the FK pointed at their primary or destination place, and the prose
carries the rest.

`seerah_events.latitude`/`longitude` are retained as a per-event override.

### 3.4 Why `quran_surahs.revelation_place` is kept

`RevelationPlace::label()` returns **مكية / مدنية**. That is a classification of
the *period* of revelation — before or after the Hijra — not a set of
coordinates. Some Makki surahs were revealed at Taif or in transit. The enum is
also read in 14 files: `scopeMakki`, `scopeMadani`, three cached counts in
`SurahRepository`, `SurahExporter`, `QuranSurahImporter`, `SurahResource`,
`Admin\Quran\SurahController`, `Api\QuranController` and `UpdateSurahRequest`.

The FK is therefore additive. The enum keeps driving the badges, scopes, filters
and exports; the FK supplies coordinates and a localized place name, and allows a
surah to later point at a finer place (`غار حراء`, `الطائف`, `بيت المقدس`)
without disturbing its classification.

---

## 4. Seed and backfill

### 4.1 Seeder

`LocationSeeder` — roughly 55 canonical places authored from the observed data,
each with `ar` + `en` names, coordinates, a `type`, a `parent_id` and an alias
list covering every spelling seen in the three source columns.

Hierarchy, e.g.:

```
الجزيرة العربية (region)
└── الحجاز (region)
    ├── مكة المكرمة (city)
    │   ├── المسجد الحرام (mosque)
    │   ├── غار حراء (landmark)
    │   ├── غار ثور (landmark)
    │   └── منى · عرفات · الصفا (landmark)
    ├── المدينة المنورة (city)
    │   ├── المسجد النبوي (mosque)
    │   ├── قباء (landmark)
    │   └── أُحُد (mountain)
    ├── الطائف (city)
    ├── بدر (battlefield)
    ├── خيبر (city)
    ├── الحديبية (landmark)
    ├── حنين (valley)
    └── تبوك (city)
الشام · اليمن · الحبشة · مصر · فارس · الروم · نجد (country/region)
```

### 4.2 Backfill command

`php artisan locations:backfill {--dry-run}`

- **Seerah** — longest-alias-first substring match against `location`, so
  `المسجد النبوي` wins over `المدينة` on `"المسجد النبوي بالمدينة"`.
- **Muslims** — normalized exact match on `birth_place` (lowercase, strip `al-`
  prefix, resolve the known typos through aliases).
- **Surahs** — deterministic: `makkah` → Makkah location, `madinah` → Madinah
  location. All 114 rows, no ambiguity.
- **Ambiguous values are left NULL**: `Yemen/Makkah`, `Makkah or Medinah`,
  `Abyssinia or Medina`, `Hijaz/Azd Shanawat`. They are not guessed.
- **Idempotent** — writes only where the FK `IS NULL`, so an editor's manual
  correction is never overwritten and the command is safely re-runnable.
- Prints a coverage report per table on completion, and under `--dry-run` writes
  nothing.

Projected coverage: ~6,600 of 6,677 people (Hijaz 4,355 + Medinah variants 1,419
+ Makkah 758 + Ta'if 51 + Yemen 31 + tail), 114 of 114 surahs, and roughly
two-thirds of the 279 Seerah events that name a place. The remainder stays NULL, which renders exactly as
today.

---

## 5. Surfaces

### 5.1 Admin

- **New CRUD** at `/su/portal/v1/admin/locations` — index rendered as a parent
  tree, create/edit with 5-locale name tabs (ar default), `type`, `parent`,
  coordinates, aliases. Delete is blocked while any row references the
  location, with the usage count shown.
- **Seerah event form** (`resources/views/admin/seerah-events/edit.blade.php:198`)
  — add a searchable location select beside the existing text input, and relabel
  that input **وصف المكان**.
- **Sahaba form** (`resources/views/admin/sahaba/_form.blade.php`) — birth place
  free text becomes a searchable select.
- **Surah form** — add a revelation location select. The مكية/مدنية field is
  untouched.
- **Review queue** — an `?unmapped=1` filter on the Seerah events and Sahaba
  index screens listing rows that have location text but no FK. This is how the
  backfill tail gets finished by an editor.

### 5.2 Public

- **Seerah event page — the headline fix.** The map gate in
  `SeerahController::mapEventDetail()` changes from *event coordinates* to
  *event coordinates ?? location coordinates*, taking working maps from 2 to roughly
  185 — that is, most of the 279 events that name a place. The remaining 299
  events name no place at all and are unaffected.
- Event pages render the localized canonical place name, with the prose note
  beneath it.
- Sahaba profiles and `resources/views/public/sahaba/show.blade.php` render
  **المدينة المنورة** instead of `Medinah`.
- Surah pages show the revelation place name and map pin alongside the existing
  مكية/مدنية badge, which is unchanged.
- **Search** — `SearchModule::SEERAH` keeps `location` in `searchableFields()`
  and gains alias resolution, so searching *المدينة* also matches events whose
  note reads `حصون بني قريظة جنوب المدينة`.
- No new public routes.

---

## 6. Testing

One runnable check per non-trivial piece:

- **Seeder self-check** — no alias appears under two locations; every alias
  resolves to exactly one location; `parent_id` contains no cycle.
- **Backfill** — a fixture asserting that `Medinah`, `Medina` and `Madinah`
  collapse to a single `location_id`; that `Makkah or Medinah` stays NULL; that
  `"المسجد النبوي بالمدينة"` resolves to the mosque and not the city; and that a
  second run changes nothing.
- **Map resolution precedence** — an event with its own coordinates wins; an
  event without them falls back to the location; an event with neither renders
  no map.

---

## 7. Phasing

1. **Data layer.** Migrations, `Location` + `LocationTranslation`, repository,
   seeder, backfill command, and the `SeerahController` map-gate fix. Ships
   user-visible value with no UI work: the Seerah map starts working.
2. **Admin.** Locations CRUD, the three form selects, the unmapped review queue.
3. **Public.** Localized place-name rendering, search alias resolution.
4. **Cleanup (separate decision, later).** Drop `muslims.birth_place` once
   nothing reads it. `quran_surahs.revelation_place` and
   `quran_reciters.country` are **not** dropped.

---

## 8. Risks

- **Mis-mapping religious content.** Mitigated by `--dry-run`, by never
  overwriting a non-NULL FK, and by leaving ambiguous values NULL rather than
  guessing.
- **A permanent unmapped tail.** Expected. Prose like
  `المنطقة المحايدة بين الجيشين` corresponds to no place. NULL means "no map",
  which is the current behaviour for 576 of 578 events anyway.
- **Two ways to read a place** (`location` prose vs `location_id`) during phases
  1–3. Bounded: the note is display text, the FK is the queryable value, and
  nothing derives one from the other at runtime.

---

## 9. What changed during implementation

The design above is left as written. Seven things turned out differently once it
met the code; each is recorded here so the document does not drift from what
shipped. Specs, plan and tests carry the same corrections.

### 9.1 Resolution ranks by hierarchy depth, not alias length

§4.2 said "longest-alias-first". **That is wrong.** `المدينة المنورة` is 15
characters against the Prophet's Mosque's `المسجد النبوي` at 13, so ranking by
string length returns the *less* specific place. Length proxies for specificity
only until a landmark sits inside a city with a two-word name — the common shape
in this dataset. The resolver ranks by hierarchy depth and uses alias length only
to break ties. Pinned by
`tests/Unit/Domain/Shared/LocationResolverTest::test_the_deepest_matching_place_wins_over_its_own_ancestor`.

### 9.2 A third matching rule: alias subsumption

Not anticipated. When a lower-ranked hit's alias sits *inside* the matched alias
— `منى` inside al-Aqabah's own registered name `العقبة بمنى` — it is not a second
place being named. Without this rule three events reported false ambiguity.

### 9.3 The Seerah relation is `place()`, not `location()`

`seerah_events.location` is an existing string column, so an Eloquent relation
named `location` is shadowed by the attribute and never reached — `$event->location`
returns the prose. Renaming the relation is one word; renaming the column would
have churned ~30 call sites for nothing. The column and the foreign key keep the
names in §3.2.

### 9.4 One `LocationData` DTO, not a Create/Update pair

The create and update payloads are field-for-field identical; the only difference
is the `unique:locations,slug,{id}` rule, which belongs to the FormRequest. A
second class with no differing field is the speculative abstraction constitution
IX forbids.

### 9.5 Reference counting goes through a registry, not relations

`Shared` is a leaf module: it imports no domain module. So `Location` cannot
declare `hasMany(SeerahEvent)` for the delete guard — that would fail `deptrac`.
`LocationUsageRegistry` holds `[table, column]` pairs registered by each
consumer's service provider and counts through the query builder, keeping the
dependency arrow pointing inward.

### 9.6 The `zoom` column was dropped before it shipped

The existing OSM embed computes a bounding box (`lat ± 0.04`), not a zoom level,
so the column would have shipped with no reader.

### 9.7 `RevelationPlace::scopeMakki`/`scopeMadani` do not exist

The enum's **own docblock** claims `Surah::scopeMakki` and `scopeMadani` rely on
it, and §3.4 above repeated that claim. Verified by grep: those scopes are not
defined anywhere. The filtering lives in `SurahRepository::getByRevelationPlace()`.
The enum has **23** readers, not the 14 first stated — more than claimed, not
fewer, so nothing in the decision changes.

---

## 10. Measured outcome

Reproduced end to end on a full rollback and re-apply:

| Criterion | Target | Actual |
|---|---|---|
| Seerah event pages that can render a map | ≥ 180 | **2 → 206** |
| Companions resolved to a place | ≥ 95% | **6,663 / 6,677 = 99.8%** |
| Rows collapsed onto one Madinah | ≥ 1,419 | **1,419** |
| Surahs carrying a place | 114 | **114 / 114** |
| Canonical places seeded | ~55 | **59**, every one with coordinates |
| Original place text lost | 0 | **0** — 279 / 6,677 / 114 all intact |

**88 rows are deliberately left unresolved**: 39 ambiguous + 35 no-match on
Seerah, 14 ambiguous on companions. These are not failures — FR-011 requires text
that names two places (`Makkah or Medinah`, `من مكة إلى المدينة`) or no place at
all (`المنطقة المحايدة بين الجيشين`) to be left for an editor rather than
guessed. They surface in the admin `?unmapped=1` queue.

Gates at completion: **1,077 tests passing**, `pint` clean, `deptrac` 0 violations
with no new baseline entry, migrations reversible with every original column
intact after rollback.
