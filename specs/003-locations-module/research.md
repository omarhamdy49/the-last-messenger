# Phase 0 Research: Locations Module

**Feature**: `003-locations-module` | **Date**: 2026-08-18

Every decision below was taken against the live database and the current source tree, not against
assumption. Measurements are reproduced so they can be re-checked.

---

## R-01 — Module placement

**Decision**: `Location` and `LocationTranslation` live in `app/Domain/Shared/Models/`, with the
repository and services in the corresponding `Shared` directories. There is no
`app/Domain/Locations/`.

**Rationale**: Three separate domains consume places — Seerah, People and Quran. Constitution IV
forbids a domain module from importing a sibling, so a `Domain/Locations` would force all three
through a `LocationLookup` contract in `Shared`, the same tax `PersonLookup` pays for Hadith →
People. That contract returns hydrated value objects, which costs `with('location')` eager loading
and `whereHas` filtering — both of which this feature needs on the Seerah listing queries that
already work hard to avoid N+1 (constitution II).

A place is reference data with a bounded vocabulary, not a bounded context with its own behaviour.
The direct precedent in the repo is `Shared\Models\Language`. Placement in `Shared` requires **no
`deptrac.yaml` change**, because every domain may already import `Shared`, and `Shared` continues to
import no domain module.

**Alternatives considered**:
- *`app/Domain/Locations/` with a `LocationLookup` contract* — rejected: pays the contract tax three
  times over, loses eager loading, and adds a `deptrac` layer for reference data.
- *Duplicate the vocabulary per domain* — rejected: it is precisely the drift this feature exists to
  remove.

---

## R-02 — Keep `seerah_events.location` rather than replace it

**Decision**: the existing `location` string column is retained unchanged and continues to be
displayed, exported, imported and searched. `location_id` is added beside it.

**Rationale**: the column does not hold place names. Sampled from the live table:

```
حمراء الأسد على نحو 8 أميال جنوب المدينة
المنطقة المحايدة بين الجيشين
جبل عَيْنَيْن (المعروف اليوم بجبل الرماة، مقابل جبل أُحُد)
من مكة إلى الحبشة عبر ميناء الشعيبة
```

215 distinct values across 279 filled rows — nearly every value is unique. Replacing the column with
a foreign key deletes editorial content that no place record can reconstruct.

Keeping it also means `SeerahEventExporter`, `SeerahEventImporter`, `CreateSeerahEventData`,
`UpdateSeerahEventData`, `StoreSeerahEventRequest`, `UpdateSeerahEventRequest`,
`SearchModule::SEERAH` and the six `SeerahEventRepository` selects need **no change** — roughly 30
call sites left untouched.

**Alternatives considered**:
- *Replace with `location_id`* — rejected: destroys content, and rewrites ~30 call sites.
- *Rename to `location_note`* — rejected: same information preserved, but churns every one of those
  call sites for a cosmetic gain.

---

## R-03 — Journeys are not modelled as a relation

**Decision**: an event references at most one place. Route-shaped text points at its principal or
destination place; the full route stays in the retained descriptive column.

**Rationale**: measured, **9 of 279** filled values are route-shaped (matching `من … إلى …`):

```
من مكة إلى الحبشة عبر ميناء الشعيبة        من المدينة إلى أذرعات الشام
من المسجد الحرام إلى المسجد الأقصى …        من مكة إلى الجُحفة فبدر
من المدينة المنورة إلى مكة المكرمة          من الحبشة إلى مكة
```

A `seerah_event_location` pivot with a role column would add a table, a model, sync paths on create
and update, and eager-load complexity on every read — for nine rows. Constitution IX: three similar
lines beat a premature abstraction.

**Alternatives considered**:
- *Many-to-many pivot with `role` (`primary`/`from`/`to`/`via`)* — rejected on the count above.
  Recorded as the upgrade path if journey content later grows.

---

## R-04 — Aliases as a JSON column, matched in PHP

**Decision**: `locations.aliases` is a JSON array. Matching happens in PHP over the cached
vocabulary, not in SQL.

**Rationale**: two reasons, both measured.

First, correctness. The source text carries Arabic diacritics — `شِعب أبي طالب بمكة`, `جبل أُحُد`,
`بُصرى الشام`. MySQL collation folding is not dependable across these, so a SQL `LIKE` would miss
matches that a normalising PHP comparison catches. Normalisation must strip tashkeel, unify
alef forms (`أ إ آ` → `ا`), unify `ة`→`ه` and `ى`→`ي`, and collapse whitespace.

Second, cost. The vocabulary is ~55 rows and is cached. Matching 7,070 source rows against 55
cached entries in PHP is a single pass with zero additional queries. A `location_aliases` table
would add a table and a join to save nothing.

**Alternatives considered**:
- *`location_aliases` table with `unique(alias)`* — rejected on constitution IX. The uniqueness it
  would enforce is instead asserted by the seeder self-check, which aborts on a duplicate.
- *SQL `LIKE` matching against the alias table* — rejected on the diacritics correctness problem
  above, which is the harder of the two failures because it is silent.

---

## R-05 — Resolution strategy: longest-alias-first, ambiguity left unresolved

**Decision**: for each source string, test every alias in the vocabulary and take the **longest**
alias that matches. Where the string names two places or is explicitly uncertain, leave the foreign
key NULL.

**Rationale**: place names nest, so shortest-match is actively wrong. `"المسجد النبوي بالمدينة"`
contains both `المسجد النبوي` and `المدينة`; the correct answer is the mosque. Longest-alias-first
picks it deterministically without needing a scoring model.

Ambiguity is real and must not be guessed. From `muslims.birth_place`: `Yemen/Makkah` (5 rows),
`Makkah/Abyssinia` (4), `Makkah or Medinah` (1), `Abyssinia or Medina` (1), `Yeman/Hijaz` (2),
`Hijaz/Makkah` (2), `Yemen/Hadramawt` (2), `Hijaz/Azd Shanawat` (1), `Yemen/al-Ta'if` (1). These are
~19 rows, and a wrong birthplace on a companion of the Prophet ﷺ is a content-accuracy defect, not
a rounding error. They stay NULL and surface in the editor queue.

**Alternatives considered**:
- *Fuzzy / Levenshtein matching* — rejected: it would resolve the ambiguous cases by silently
  picking one, which is exactly the failure mode being avoided, and it is unnecessary for a
  vocabulary whose variants are known and enumerable.
- *First-match-wins* — rejected: resolves `"المسجد النبوي بالمدينة"` to the city.

---

## R-06 — Delete guard via a usage registry

**Decision**: `LocationUsageRegistry` in `Shared\Services` holds `[table, column]` pairs registered
by each consuming module's service provider. `LocationService::delete()` asks it for the reference
count and refuses the delete when the count is non-zero.

**Rationale**: the obvious implementation — `hasMany(SeerahEvent)`, `hasMany(Muslim)`,
`hasMany(Surah)` on `Location` — would make `Shared` import three domain modules and break the leaf
invariant that constitution IV calls "the invariant that actually protects module isolation".
`deptrac` would fail, and the baseline may never be used to admit a new violation.

The registry keeps the dependency arrow pointing inward: Seerah tells `Shared` that
`seerah_events.location_id` exists; `Shared` never names Seerah. The count is an `EXISTS`/`COUNT`
per registered pair through the query builder, satisfying constitution II. This mirrors the existing
`PublicVisibilityRegistry` pattern.

**Alternatives considered**:
- *Three `hasMany` relations* — rejected: fails `deptrac`, breaks constitution IV.
- *Hard-coded table names inside `LocationService`* — rejected: passes `deptrac` but hides real
  coupling from the reader and silently misses any fourth consumer added later.
- *Database `RESTRICT` foreign keys* — rejected: the delete would fail as a raw SQL error rather
  than a message naming how many records block it (constitution VII: generic errors to clients, but
  the editor needs an actionable count).

---

## R-07 — Surahs keep the مكية/مدنية classification

**Decision**: `quran_surahs.revelation_place` and the `RevelationPlace` enum are retained
permanently. `revelation_location_id` is additive. The classification is never derived from, nor
overwritten by, the place.

**Rationale**: `RevelationPlace::label()` returns **مكية / مدنية** — a classification of the
*period* of revelation, before or after the Hijra, not a coordinate. Some Makki surahs were revealed
at Taif or in transit, so the two are not interchangeable claims. The enum is also read in 14
files: `Surah::scopeMakki`, `scopeMadani`, three cached counts in `SurahRepository`,
`SurahExporter`, `QuranSurahImporter`, `SurahResource`, `Admin\Quran\SurahController`,
`Api\QuranController`, `UpdateSurahRequest` and `SurahRepositoryInterface`.

The data is also already clean — 86 `makkah`, 28 `madinah`, zero nulls — so there is nothing to
normalise. What it lacks is coordinates and a localized name, which the foreign key supplies.

**Alternatives considered**:
- *Replace the enum with the foreign key* — rejected: rewrites 14 files, and either loses مكية/مدنية
  or requires re-adding it under another name.
- *Skip surahs entirely* — rejected: explicitly requested, and the seeded mapping is deterministic
  and free.

---

## R-08 — Reciter country of origin excluded

**Decision**: `quran_reciters.country` is left exactly as it is.

**Rationale**: it already holds ISO-3166 alpha-2 codes with no drift — `SA` (17), `EG` (9), `KW` (2)
across 28 of 64 rows. There is no normalisation problem to solve. It was in an earlier draft and was
removed at the requester's direction.

**Alternatives considered**:
- *Map `country` to a country-typed location* — deferred, not rejected. It would gain Arabic country
  names on reciter cards. Recorded as a candidate follow-up, out of scope here.

---

## R-09 — Columns that look like locations and are not

**Decision**: excluded from this feature, documented so a future scan does not re-open them.

| Column | What it actually is |
|---|---|
| `menu_items.location` | Navigation slot — `header`, `footer`, `sidebar`. |
| `quran_words.location` | Verse coordinate string, `"surah:verse:position"`, e.g. `"2:255:3"`. Unique-indexed. |
| `home_module_cards.placement` | Layout slot on the home page. |
| `media.original_filename` | Matched a substring scan only. |
| `muslims.profile` → `birth_region`, `death_place`, `mission_place` | Real place fields, but populated on **2 records in total** across 7,837. Constitution IX — not worth a migration. |

---

## Deferred, with the trigger that would reopen each

- **Public place pages.** Out of scope by the requester's decision. Reopen if places are wanted as a
  browsable entry point into the encyclopedia.
- **An all-events Seerah map.** Becomes cheap once coordinates exist, but is a new surface.
- **Dropping `muslims.birth_place`.** Only after nothing reads it, as a separate change.
- **A journey/route relation.** Reopen if route-shaped events grow well beyond the current 9.
- **`quran_reciters.country` → location.** See R-08.
