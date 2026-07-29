# Hadith rebuild plan — per collection

Status: data purged 2026-07-29 (backup `portal-data/_backup/hadith-full-20260729.sql.gz`, 31M, verified).
Schema review: [hadith-schema-review.md](hadith-schema-review.md). Live schema: [hadith-schema.json](hadith-schema.json).

## Pipeline (one pass per collection, 17 iterations)

```
prophet-data-generator/hadith/final/<collection>/*.json   ← source of record (enrichment)
        ↓ normalize: lowercase IDENTIFIERS only, unify field variants
portal-data/<collection>/{collection,books,chapters,hadiths,narrators,gradings,...}.json
        ↓ import
MySQL  (field-mapping assertions run per table)
        ↓ verify
Chrome extension → sunnah.com/<slug>/<book> → text parity report
```

Sunnah.com is the **verification** authority for text, not the source: it supplies text/grade/URN/URL
only, and its transformer sets `narrator_chain => null`. All isnad, themes, keywords, commentaries,
related verses and applications exist **only** in the local JSON.

## Source inventory — 50,883 files, 17 collections

| # | collection dir | files | sunnah slug | notes |
|---|---|---:|---|---|
| 1 | `nawawi40` | 42 | the-forty-hadith-of-imam-nawawi | smallest — use as pipeline pilot |
| 2 | `qudsi40` | 40 | the-forty-hadith-qudsi | |
| 3 | `shahwaliullah40` | 40 | the-forty-hadith-of-shah-waliullah | |
| 4 | `shamail_muhammadiyah` | 402 | shamail | |
| 5 | `aladab_almufrad` | 1326 | adab | |
| 6 | `ahmed` | 1374 | musnad-ahmad-ibn-hanbal | partial musnad |
| 7 | `bulugh_almaram` | 1767 | bulugh | |
| 8 | `riyad_assalihin` | 1896 | riyadussalihin | |
| 9 | `malik` | 1985 | malik | |
| 10 | `darimi` | 3406 | darimi | |
| 11 | `tirmidhi` | 4053 | tirmidhi | |
| 12 | `ibnmajah` | 4345 | ibnmajah | |
| 13 | `mishkat_almasabih` | 4427 | mishkat | |
| 14 | `abudawud` | 5276 | abudawud | |
| 15 | `nasai` | 5768 | nasai | |
| 16 | `bukhari` | 7277 | bukhari | |
| 17 | `muslim` | 7459 | muslim | |

Run order is smallest-first: the 42-file `nawawi40` exercises every mapping branch at a size where a
full manual diff is possible. Do not start `bukhari`/`muslim` until a small collection has passed.

**Reconciliation gap:** 50,883 files vs 51,436 pre-purge rows = 553 rows with no source file. Resolve
during import (expected: dedup merges), do not silently accept a short count.

## Field mapping — JSON → column

Percentages are key-presence across a 400-file random sample (seed 7). "present but empty" means the
key exists as `[]` in most files.

### hadith_hadiths
| JSON path | column | % | note |
|---|---|---:|---|
| `metadata.id` | `local_source_id` | 100 | |
| `collections[].hadith_number` | `hadith_number` | 99 | string, keep leading zeros |
| `verification_status` | `verification_status` | 100 | **lowercase** |
| `uncertainty_notes` | `uncertainty_notes` | 100 | free text — do NOT lowercase |
| `significance_ar` / `significance_en` | `significance_ar/_en` | 78 | |
| `source_verification.sunnah.url` | → `hadith_sources.url` | 79 | provenance belongs on sources |
| `metadata.sunnah_reference.urn` | `sunnah_urn_*` | **9** | only 9% carry a URN |
| sha256(`hadith_text.ar`) | `text_hash` | 100 | dedup key |

### hadith_translations
| `hadith_text.ar` → `text` + `language_code='ar'` | 100% |
| `hadith_text.en` → `text` + `language_code='en'` | 100% |
| `hadith_text.ar_notashkeel` → `text` + `language_code='ar_notashkeel'` | 59% |

Per the review, `ar_notashkeel` is a derived Arabic variant, not a locale — target it at a
`search_text` column rather than a third translation row.

### hadith_isnad + hadith_narrators — **two field conventions, this is the trap**
`sanad.narrators[]` exists in 100% of files but is **empty in ~83%**. Populated entries use either
convention, sometimes both:

| variant A | variant B | → column |
|---|---|---|
| `name_ar` (17%) | `narrator_ar` (1%) | `hadith_narrators.name_ar` |
| — | `narrator_en` (17%) | `hadith_narrators.name_en` |
| `full_name_ar` (15%) | — | `full_name_ar` |
| `order` (17%) | — | `hadith_isnad.position_in_chain` |
| `reliability` (17%) / `reliability_en` (16%) | — | `reliability_grade` — **lowercase** |
| `narrated_from` (16%) / `narrated_from_en` (15%) | — | chain edge, not a column today |
| `bio_ar`/`bio_en` (15%) | — | `biography` |
| `death_year_hijri` (17%) | — | `death_year_hijri` |

A mapper that reads only `name_ar` silently drops every variant-B narrator. Assert non-empty output
count against input count per file.

Chain direction is **unresolved** (review open question 5): the dump says position 1 = nearest the
Prophet, model/repository assert the opposite. Decide before import — it is not fixable afterwards
without re-reading `order`.

### hadith_gradings
| JSON path | column | % |
|---|---|---:|
| `grading.status` | `raw_grade` (verbatim) + derived lowercase `grade_key` | 100 |
| `grading.authenticated_by[]` | one row per scholar → `scholar_id` | 99 |
| `grading.albani_grade` | separate grading row, scholar = al-Albani | 36 |
| `grading.agreement` | `grading_agreement` | 88 |
| `grading.notes_ar` / `notes_en` | `grading_notes` | 96 |

**This is where the 566 junk levels came from.** `grading.status` is free text; the old importer
lowercased the raw string and made each distinct result a `hadith_grading_levels` row, so
`sahih_(darussalam)_[bukhari_3615_and_muslim_2009]` became a taxonomy entry. Map to the five-key
enum (`sahih`, `hasan`, `daif`, `mawdu`, `unknown`), keep `raw_grade` verbatim, and **fail the import
on an unmapped status** rather than inventing a key.

`authenticated_by` naming al-Bukhari/Muslim is collection provenance, not a rijal grading — do not
synthesize a grading scholar from collection membership (that produced 9,379 + 8,863 bogus rows).

### Mostly-empty arrays — expect low yields, assert them
| JSON | key present | fields populated | target |
|---|---:|---:|---|
| `themes_ar[]` / `themes_en[]` | 100% | — | `hadith_themes` + pivot |
| `keywords[]` | 100% | — | `hadith_keywords` |
| `related_verses[]` | 78% | **2%** | `hadith_related_verses` |
| `applications[]` | 78% | **6%** | `hadith_applications` |
| `scholarly_comments[]` | 78% | **6%** | `hadith_commentaries` |
| `companion` | 79% | 11–18% | narrator identity (companions table merged away) |

Presence of the key is not presence of data. Import assertions must count populated rows, not files.

### Legacy variants — <0.5% of files
`_metadata.*`, `collection_name`, `local_id`, `context_ar/en`, `legal_maxims[]`, `subject_matter.*`,
`note`, `processed_at`, `grading.notes`, `scholarly_comments[].scholar`. These are batch drift from
different enrichment runs. Handle explicitly or log-and-skip — never silently ignore.

## Lowercasing rule

Lowercase (identifiers/enums only): `slug*`, `canonical_code`, `source_slug`, `language_code`,
`locale`, `review_status`, `verification_status`, `source_type`, `grade_key`, `reliability_grade`,
`transmission_method`, keyword tokens.

Never lowercase: `hadith_text.*`, translations, `raw_grade`, narrator/scholar/companion names,
biographies, commentary text, `significance_*`, `uncertainty_notes`, `relevance`, source titles and
references. Arabic is unaffected by case; the rule exists to protect English proper nouns.

## Per-collection acceptance gate

A collection is done only when all hold:

- [ ] `portal-data/<collection>/` written, file count matches source dir count
- [ ] import run logged to `hadith_sync_logs` with type `json_import`
- [ ] row counts per table match the normalizer's predicted counts (no silent drops)
- [ ] zero unmapped `grading.status` values
- [ ] narrator output count == populated `sanad.narrators[]` input count
- [ ] no `hadith_grading_levels`-style taxonomy growth: distinct `grade_key` ⊆ the 5-key enum
- [ ] Chrome parity check on a sample of that collection's book pages vs sunnah.com — text matches
      or mismatches are logged with URL
- [ ] `php artisan test --filter=Hadith` passes; `./vendor/bin/pint` clean

## Open items blocking import (from the review)

1. Isnad chain direction — position 1 = Prophet or compiler?
2. Schema migration 23 → 16 tables — apply before import, or import into current schema and migrate after?
3. `ar_notashkeel` as a locale row vs a `search_text` column.
4. `HadithSyncService` is dead code (`DB::table('hadiths')`, column `matn` — neither exists). Retire
   it or fix it; it cannot be the loop's importer as-is.
