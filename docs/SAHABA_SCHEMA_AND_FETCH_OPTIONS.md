# Sahaba schema and fetch options

This document is the contract between:

- the Sahaba scraper at `prophet-data-generator/sabaha/scrape_sahaba.py`
- the generated JSON files at `prophet-data-generator/sabaha/output/*.json`
- the Laravel importer command `php artisan sahaba:import-json`
- the canonical Sahaba and family-tree database schema

The current design intentionally keeps Sahaba identity and genealogy in one canonical module. There is no separate public `people` identity store for this module. Family members who are not Sahaba are stored in `sahaba` as non-Sahabi genealogy placeholders.

## Canonical database schema

### `sahaba`

Canonical person table for:

- real Sahaba/Sahabiyyat
- Prophet/family genealogy records needed for tree edges
- imported placeholders required to connect family relationships

Important columns:

| Column | Type | Purpose |
| --- | --- | --- |
| `id` | bigint | Internal primary key |
| `external_id` | string, unique, nullable | Stable source identity, e.g. `muslimscholars:6933`; used for idempotent imports |
| `slug` | string, unique | URL/display slug |
| `gender` | enum | `male`, `female` |
| `person_type` | enum/string | `prophet`, `sahabi`, `family`, `tribe`, `historical` — gender is never encoded here, it's the `gender` column above |
| `is_sahabi` | boolean | `true` for Sahaba records; `false` for genealogy-only placeholders |
| `generation` | integer, nullable | Generation number when source provides it |
| `is_verified` | boolean | Source/import verification flag |
| `father_id` | FK to `sahaba.id`, nullable | Canonical father relationship |
| `mother_id` | FK to `sahaba.id`, nullable | Canonical mother relationship |
| `birth_date_hijri` / `death_date_hijri` | string, nullable | Hijri dates as source text |
| `birth_date_gregorian` / `death_date_gregorian` | date, nullable | Gregorian exact dates only, `YYYY-MM-DD` |
| `age_at_death` | integer, nullable | Derived/source age |
| `birth_place`, `death_place`, `burial_place` | string, nullable | English fallback/search fields; localized values live in translations |
| `tribe`, `clan` | string, nullable | Tribal grouping |
| `conversion_date_hijri` | string, nullable | Conversion date if known |
| `conversion_order` | integer, nullable | Early-Muslim ordering when known |
| `roles` | JSON, nullable | Roles/tags such as `Narrator`, `Commander` |
| `reported_hadith_count` | integer | The **source's claimed** hadith count (import-time text, e.g. from muslimscholars.info bio) — not a live count from this database's own hadith graph. For a live count of hadiths actually linked via `hadith_narrators.sahaba_id`, use `Sahabi::scopeWithNarratorLinkCount()`. |
| `views_count`, `is_featured`, `importance_rank` | metadata | App sorting and display |

Historical-rank categories (badr, ansar, muhajir, ten_promised, uhud, early_muslim, tabi) are **not** a column on `sahaba` — a Sahabi can hold several at once (e.g. a Muhajir who also fought at Badr), so they live in a lookup + pivot instead of a single-value enum:

### `sahabi_categories` / `sahabi_category`

| Table | Purpose |
| --- | --- |
| `sahabi_categories` | Lookup: `key` (short lowercase, e.g. `badr`), `rank` (default-sort weight) |
| `sahabi_category` | Pivot: `sahabi_id`, `category_id`, composite index `(category_id, sahabi_id)` — mirrors the `sahabi_battles` pattern |

Allowed category keys:

```text
ten_promised
badr
uhud
early_muslim
muhajir
ansar
tabi
```

`sahaba` with no special category (the old bare `prophet_companion` default) simply have zero rows in `sahabi_category` — `is_sahabi` already distinguishes a real companion from a genealogy-only placeholder, so no placeholder category value is needed.

Allowed `person_type` values:

```text
prophet
sahabi
family
tribe
historical
```

Note: the scraper JSON contract below still emits the legacy `person_type`/`status` vocabulary (`companion_male`, `badr_participant`, ...) — `SahabaJsonImportService` is the anti-corruption layer that translates it to the short internal values on import (`PERSON_TYPE_MAP` / `STATUS_TO_CATEGORY_MAP`). The JSON shape does not need to change.

### `sahabi_translations`

Localized text table. One row per Sahabi/person per locale.

| Column | Purpose |
| --- | --- |
| `sahabi_id` | FK to `sahaba.id` |
| `locale` | Locale code, usually `ar` or `en` |
| `name` | Display name |
| `full_name` | Full lineage/name |
| `kunya` | Abu/Umm name |
| `laqab` | Honorific |
| `title` | Optional title |
| `biography` | Long biography |
| `birth_place`, `death_place`, `burial_place` | Localized places |
| `occupation` | Source role text |
| `notes` | Extra notes |
| `virtues` | Virtues/merits text |
| `famous_narrations` | Famous narrations text |
| `martyrdom` | Martyrdom/death-cause text |
| `is_ready` | Translation is displayable |

Uniqueness:

```text
unique(sahabi_id, locale)
```

### `relationships`

Stores only non-parent family edges that cannot be represented cleanly by `father_id` and `mother_id`.

| Column | Purpose |
| --- | --- |
| `person_id` | FK to `sahaba.id` |
| `related_person_id` | FK to `sahaba.id` |
| `relationship_type` | Stored relationship edge type |
| `notes` | Optional notes |

Current stored relationship types:

```text
spouse
sibling
uncle
aunt
cousin
```

Do not duplicate parent/child edges here. Parentage is canonical in `sahaba.father_id` and `sahaba.mother_id`; children are derived by querying those columns.

Symmetric relationship types are normalized:

```text
spouse, sibling, cousin => min(id), max(id)
```

That prevents duplicate reverse edges.

## Hadith narrator linkage

Hadith modules should relate to Sahaba instead of copying Sahaba identity fields.

Current linkage:

- `hadith_companions.sahaba_id -> sahaba.id`
- `hadith_narrators.sahaba_id -> sahaba.id`

Rule:

- if the narrator/companion is a known Sahabi, store `sahaba_id`
- do not duplicate name, biography, dates, or virtues into hadith narrator rows
- non-Sahabi narrators may remain independent narrator records

## Import JSON schema

Each source file is one Sahabi profile:

```text
prophet-data-generator/sabaha/output/06933.json
```

Required root fields:

```json
{
  "schema_version": "1.0",
  "entity": "sahabi",
  "external_id": "muslimscholars:6933",
  "slug": "asam-al-nmyry",
  "classification": {},
  "translations": {}
}
```

At least one translated name is required in:

```text
translations.en.name
translations.ar.name
```

### Full JSON shape

```json
{
  "schema_version": "1.0",
  "entity": "sahabi",
  "external_id": "muslimscholars:{id}",
  "slug": "stable-url-slug",
  "classification": {
    "is_sahabi": true,
    "person_type": "companion_male",
    "gender": "male",
    "status": "prophet_companion",
    "generation": null,
    "is_verified": true,
    "label_raw": "Comp.(RA)"
  },
  "translations": {
    "ar": {
      "name": "الاسم العربي",
      "full_name": null,
      "kunya": null,
      "laqab": null,
      "title": null,
      "biography": null,
      "birth_place": null,
      "death_place": null,
      "burial_place": null,
      "occupation": null,
      "notes": null,
      "virtues": null,
      "famous_narrations": null,
      "martyrdom": null
    },
    "en": {
      "name": "English Name",
      "full_name": "English Full Name",
      "kunya": null,
      "laqab": null,
      "title": null,
      "biography": null,
      "birth_place": null,
      "death_place": null,
      "burial_place": null,
      "occupation": "Narrator",
      "notes": null,
      "virtues": null,
      "famous_narrations": null,
      "martyrdom": null
    }
  },
  "life": {
    "birth_date_hijri": null,
    "death_date_hijri": null,
    "birth_date_gregorian": null,
    "death_date_gregorian": null,
    "birth_year_gregorian": null,
    "death_year_gregorian": null,
    "age_at_death": null,
    "birth_raw": null,
    "death_raw": null,
    "death_cause": null,
    "places_of_stay": []
  },
  "lineage": {
    "father_ref": null,
    "father_name": null,
    "mother_ref": null,
    "mother_name": null
  },
  "tribal_affiliation": {
    "tribe": null,
    "clan": null
  },
  "islamic_history": {
    "conversion_date_hijri": null,
    "conversion_order": null,
    "roles": [],
    "hadith_count": null,
    "tags": []
  },
  "relationships": [
    {
      "type": "spouse",
      "related_ref": "muslimscholars:1",
      "related_name": "Related Name",
      "notes": null
    }
  ],
  "genealogy_hints": {
    "children": [
      {
        "name": "Child Name",
        "external_id": "muslimscholars:2"
      }
    ]
  },
  "hadith_network": {
    "teachers": [
      {
        "name": "Muhammad (saw)",
        "external_id": "muslimscholars:1"
      }
    ],
    "students": []
  },
  "media": {
    "profile_image": null,
    "gallery": []
  },
  "last_updated": "2010-08-06"
}
```

### JSON to database mapping

| JSON path | Database target |
| --- | --- |
| `external_id` | `sahaba.external_id` |
| `slug` | `sahaba.slug` |
| `classification.gender` | `sahaba.gender` |
| `classification.status` | `sahabi_category` pivot row (mapped via `STATUS_TO_CATEGORY_MAP`), added not replaced |
| `classification.person_type` | `sahaba.person_type` (mapped via `PERSON_TYPE_MAP`) |
| `classification.is_sahabi` | `sahaba.is_sahabi` |
| `classification.generation` | `sahaba.generation` |
| `classification.is_verified` | `sahaba.is_verified` |
| `translations.{locale}.*` | `sahabi_translations` |
| `life.birth_date_hijri` | `sahaba.birth_date_hijri` |
| `life.death_date_hijri` | `sahaba.death_date_hijri` |
| `life.birth_date_gregorian` | `sahaba.birth_date_gregorian` |
| `life.death_date_gregorian` | `sahaba.death_date_gregorian` |
| `life.age_at_death` | `sahaba.age_at_death` |
| `translations.en.birth_place` | `sahaba.birth_place` and translated row |
| `translations.en.death_place` | `sahaba.death_place` and translated row |
| `translations.en.burial_place` | `sahaba.burial_place` and translated row |
| `tribal_affiliation.tribe` | `sahaba.tribe` |
| `tribal_affiliation.clan` | `sahaba.clan` |
| `islamic_history.conversion_date_hijri` | `sahaba.conversion_date_hijri` |
| `islamic_history.conversion_order` | `sahaba.conversion_order` |
| `islamic_history.roles` | `sahaba.roles` |
| `islamic_history.hadith_count` | `sahaba.reported_hadith_count` |
| `lineage.father_ref` / `father_name` | resolves/creates `sahaba` row, then sets `father_id` |
| `lineage.mother_ref` / `mother_name` | resolves/creates `sahaba` row, then sets `mother_id` |
| `relationships[]` | `relationships` table |
| `genealogy_hints.children[]` | resolves child and assigns this record as father/mother based on current record gender |

Fields currently kept in JSON but not imported into first-class columns:

- `life.birth_year_gregorian`
- `life.death_year_gregorian`
- `life.birth_raw`
- `life.death_raw`
- `life.death_cause`, except when mapped by scraper to `translations.en.martyrdom`
- `life.places_of_stay`
- `islamic_history.tags`
- `hadith_network.teachers`
- `hadith_network.students`
- `media.profile_image`
- `media.gallery`
- `last_updated`

Keep those fields in JSON because they may be useful later for UI filters, audit screens, hadith graph linking, or media enrichment.

## Import behavior

Command:

```bash
cd the-last-messenger-be
php artisan sahaba:import-json /absolute/path/to/prophet-data-generator/sabaha/output --overwrite-relations --stop-on-error
```

Options:

| Option | Meaning |
| --- | --- |
| `{path}` | Directory containing one JSON file per Sahabi |
| `--dry-run` | Validate JSON only; no DB writes |
| `--overwrite-relations` | Replace conflicting `father_id`/`mother_id` values |
| `--stop-on-error` | Stop on first invalid file or database error |

Import phases:

1. Read every JSON file except files beginning with `_`.
2. Upsert all full identities by `external_id`.
3. Upsert translations by `(sahabi_id, locale)`.
4. Resolve explicit lineage.
5. Resolve relationship edges and child hints.
6. Create missing relation targets as placeholders:
   - `is_sahabi = false`
   - `status = genealogy_member`
   - `person_type = family`

Conflict behavior:

- Without `--overwrite-relations`, existing parent links are preserved and conflicts are counted.
- With `--overwrite-relations`, inferred child links are applied first, then explicit lineage is applied last so explicit source lineage wins.
- Self-parent relations are ignored.
- Missing related external IDs are derived from names as `derived-name:{sha1(normalized-name)}`.

## Scraper fetch options

Script:

```bash
cd prophet-data-generator/sabaha
python3 scrape_sahaba.py
```

Default behavior:

- discovers Sahaba IDs from `https://muslimscholars.info/manage.php?ylist=1&submit=Go`
- fetches profile pages from `https://muslimscholars.info/m/manage.php?submit=scholar&ID={id}`
- writes JSON files to `prophet-data-generator/sabaha/output`
- writes `_manifest.json`
- skips existing files unless `--force` is passed
- excludes source/reference sections from output

Available options:

| Option | Default | Purpose |
| --- | --- | --- |
| `--output-dir` | `sabaha/output` | Output directory |
| `--ids` | none | Comma-separated IDs/ranges, e.g. `2,3,53-60` |
| `--limit` | none | Only process first N discovered IDs |
| `--workers` | `2` | Concurrent workers; allowed `1..8` |
| `--delay` | `0.75` | Global minimum delay between HTTP requests |
| `--timeout` | `120.0` | Request timeout |
| `--force` | false | Overwrite existing JSON files |
| `--index-cache` | `.cache/companions-index.html` | Companion index cache path |
| `--refresh-index` | false | Re-download the companion index |
| `--verbose` | false | Debug logging |

Useful runs:

```bash
# Smoke test selected profiles
python3 scrape_sahaba.py --ids 899,9982 --output-dir data-test --force

# Fetch first 100 discovered profiles
python3 scrape_sahaba.py --limit 100

# Fetch explicit ranges
python3 scrape_sahaba.py --ids 2,3,53-60

# Rebuild index cache and crawl all missing records
python3 scrape_sahaba.py --refresh-index
```

## Webapp fetch/query options

Recommended API/filter capabilities for the webapp:

| Option | Maps to | Notes |
| --- | --- | --- |
| `locale` | `sahabi_translations.locale` | `ar`, `en`; fallback to any available name |
| `q` | translation full-text/name search | Search `name`, `full_name`, `kunya`, `laqab` |
| `is_sahabi` | `sahaba.is_sahabi` | Usually `true` for public Sahaba listing; `false` only for genealogy tree support |
| `gender` | `sahaba.gender` | `male`, `female` |
| `category` | `sahabi_category` pivot (via `Sahabi::scopeInCategory`) | Filter by Badr, Ansar, Muhajir, etc. — a Sahabi may match more than one |
| `person_type` | `sahaba.person_type` | Useful for genealogy/admin screens |
| `tribe` | `sahaba.tribe` | Tribe search/filter |
| `clan` | `sahaba.clan` | Clan search/filter |
| `role` | `sahaba.roles` | JSON role containment or indexed derived table if this becomes hot |
| `has_father` / `has_mother` | `father_id`, `mother_id` | Genealogy quality filters |
| `related_to` | `relationships` + parent columns | Family graph/profile relation panels |
| `battle` | `sahabi_battles.battle_name` | Use existing battle pivot, not old JSON |
| `top_narrators` | `reported_hadith_count > 0` | Sort by `reported_hadith_count desc` — this is the source's claim, not a live in-app count (see `scopeWithNarratorLinkCount` for that) |
| `featured` | `is_featured` | Homepage/curated lists |
| `sort` | `importance_rank`, `reported_hadith_count`, `views_count`, `name` | Keep default `importance_rank desc` for browse |
| `include` | relations | e.g. `translations,father,mother,children,relationships,battles,timeline` |

Recommended response modes:

| Mode | Intended use | Includes |
| --- | --- | --- |
| `list` | Browse/search pages | ID, slug, localized name, status, gender, tribe, hadith count |
| `detail` | Profile page | list fields plus all translations, biography, lineage, battles, timeline |
| `tree` | Family tree | compact nodes, `father_id`, `mother_id`, relationship edges |
| `admin` | Data review | raw/import fields, placeholders, verification status, conflict visibility |

## Data quality rules

- `external_id` is the primary import identity. Do not merge two different external IDs just because names match.
- Keep source/reference blocks out of generated JSON.
- Store bilingual content in `sahabi_translations`, not JSON blobs on `sahaba`.
- Store father/mother once in `sahaba`; derive children from those columns.
- Store only non-parent edges in `relationships`.
- Do not create separate duplicate people records for genealogy.
- Hadith narrator/companion rows should link to `sahaba_id` when the person is a Sahabi.
- Treat scraper output as historical source data requiring editorial review before public claims are emphasized.

