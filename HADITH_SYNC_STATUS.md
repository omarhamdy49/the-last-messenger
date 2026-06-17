# Hadith Sync Status

Generated: 2026-06-16

Purpose: compare Sunnah.com collection metadata, production JSON, and current database import state.

## Status Meanings

- `SYNCED`: production JSON and DB hadith rows match; no missing/importable/collision rows.
- `SYNCED-WEB`: fixed against Sunnah.com web because API hadith routes are unreliable.
- `SYNCED-FALLBACK`: fixed using API plus documented fallback for Sunnah-listed rows unavailable through API/web hadith URLs.
- `SPECIAL`: curated/local collection whose Sunnah API collection metadata route is unavailable; source/DB rows still match.

## Collection Summary

| Collection | Sunnah/API slug | Sunnah hadiths | Sunnah books | JSON hadiths | JSON books | DB hadiths | DB books with hadiths | DB books total | Empty DB books | Status | Notes |
|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|---|---|
| `abudawud` | `abudawud` | 5,276 | 43 | 5,276 | 43 | 5,276 | 43 | 43 | 0 | `SYNCED` | Hadith rows clean. |
| `ahmed` | `ahmad` | 1,359 | 7 | 1,359 | 7 | 1,359 | 7 | 7 | 0 | `SYNCED` | Hadith rows clean. |
| `aladab_almufrad` | `adab` | 1,326 | 50 API / 57 web | 1,326 | 57 | 1,326 | 57 | 57 | 0 | `SYNCED-FALLBACK` | 141 Sunnah-listed rows unavailable via API/web hadith URLs; source fallback used. |
| `bukhari` | `bukhari` | 7,277 | 97 | 7,277 | 97 | 7,277 | 97 | 97 | 0 | `SYNCED` | Hadith rows clean. |
| `bulugh_almaram` | `bulugh` | 1,767 web / API incomplete | 16 web / 5 API | 1,767 | 16 | 1,767 | 16 | 16 | 0 | `SYNCED-WEB` | API book route is incomplete; synced from Sunnah web pages. |
| `darimi` | `darimi` | 3,406 web / 0 API available | 24 web / 0 API | 3,406 | 24 | 3,406 | 24 | 24 | 0 | `SYNCED-WEB` | API metadata/routes are unavailable for hadith rows; synced from Sunnah web pages `/darimi/introduction` and `/darimi/1..23`. |
| `ibnmajah` | `ibnmajah` | 4,345 | 38 | 4,345 | 38 | 4,345 | 38 | 38 | 0 | `SYNCED` | Hadith rows clean. |
| `malik` | `malik` | 1,973 | API book route unusable | 1,973 | 61 | 1,973 | 61 | 61 | 0 | `SYNCED-WEB` | Fixed from Sunnah web. |
| `mishkat_almasabih` | `mishkat` | 5,319 metadata / 5,014 routed | 28 | 5,014 | 28 | 5,014 | 28 | 28 | 0 | `SYNCED-FALLBACK` | Expanded to every API-routed Sunnah row. Books 25/26 routes are empty and individual hadiths 4628-5378 return 404 on API/web today. |
| `muslim` | `muslim` | 7,459 | 57 | 7,459 | 57 | 7,459 | 57 | 57 | 0 | `SYNCED` | Hadith rows clean. |
| `nasai` | `nasai` | 5,768 | 52 | 5,768 | 52 | 5,768 | 52 | 52 | 0 | `SYNCED` | Hadith rows clean. |
| `nawawi40` | `the-forty-hadith-of-imam-nawawi` | API unavailable | API unavailable | 42 | 1 | 42 | 1 | 1 | 0 | `SPECIAL` | Curated collection; source/DB clean. |
| `qudsi40` | `the-forty-hadith-qudsi` | API unavailable | API unavailable | 40 | 1 | 40 | 1 | 1 | 0 | `SPECIAL` | Curated collection; source/DB clean. |
| `riyad_assalihin` | `riyadussalihin` | 1,896 | 20 | 1,896 | 20 | 1,896 | 20 | 20 | 0 | `SYNCED` | Rebuilt from Sunnah API and corrected damaged local metadata/text mappings. |
| `shahwaliullah40` | `the-forty-hadith-of-shah-waliullah` | API unavailable | API unavailable | 40 | 1 | 40 | 1 | 1 | 0 | `SPECIAL` | Curated collection; source/DB clean. |
| `shamail_muhammadiyah` | `shamail` | Metadata says 402; routed unique rows 400 | 57 | 400 | 56 | 400 | 56 | 56 | 0 | `SYNCED` | Removed 2 stale duplicate source files; usable routed set is clean at 400. |
| `tirmidhi` | `tirmidhi` | 4,053 | 49 | 4,053 | 49 | 4,053 | 49 | 49 | 0 | `SYNCED` | Hadith rows clean. |

## Current Hadith Import Audit

All production JSON rows now match DB rows:

- JSON files: 51,441
- DB hadith rows: 51,441
- Matched rows: 51,441
- Invalid JSON: 0
- Unsupported rows: 0
- Same-text collisions: 0
- Different-text collisions: 0
- Importable missing rows: 0
- Missing production JSON collection source URLs: 0
- Missing production JSON nested Sunnah references: 0
- Missing production JSON nested source verification URLs: 0

Production JSON and DB rows now match exactly for every mapped hadith collection.

## Remaining Work

Hadith text/source/DB row sync:

- No remaining collections in the current hadith audit list.
- All imported hadith rows pass verifier floors.

Cleanup:

- Empty legacy DB books removed: 59.
- Empty book chapters removed by cascade: 209.
- Current cleanup dry-run: 0 empty books, 0 empty book chapters.
- Track Sunnah's `mishkat_almasabih` books 25/26 availability. They are included in collection-level metadata but currently return empty book routes and 404 individual/web hadith pages for 4628-5378.
- Track `darimi` as a web-only sync path because Sunnah API metadata reports 0 available rows even though Sunnah web exposes all 3,406 rows.

Quran:

- Still intentionally out of scope for this hadith report.
- Full `php artisan islamic-data:verify` still fails only on Quran translation duplicate counts.
