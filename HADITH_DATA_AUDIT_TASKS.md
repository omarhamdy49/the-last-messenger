# Hadith Data Audit Tasks

Goal: fix production hadith data source-first, collection by collection, using Sunnah.com as the reference.

Core commands:

```bash
php scripts/audit_sunnah_api_parity.php --collections=<collection> --delay-ms=<ms>
php artisan islamic-data:import-hadith --collection=<collection> --chunk=100
php artisan islamic-data:verify
```

Collection-specific repair commands:

```bash
php scripts/fix_malik_from_sunnah_web.php --apply
php scripts/repair_malik_db_from_source.php
php scripts/fix_bukhari_from_sunnah_api.php --apply --delay-ms=250
php scripts/repair_bukhari_db_from_source.php
php scripts/fix_muslim_from_sunnah_api.php --apply --delay-ms=250
php scripts/repair_muslim_db_from_source.php
php scripts/fix_abudawud_from_sunnah_api.php --apply --delay-ms=250
php scripts/repair_abudawud_db_from_source.php
php scripts/fix_tirmidhi_from_sunnah_api.php --apply --delay-ms=250
php scripts/repair_tirmidhi_db_from_source.php
php scripts/fix_nasai_from_sunnah_api.php --apply --delay-ms=250
php scripts/repair_nasai_db_from_source.php
php scripts/fix_ibnmajah_from_sunnah_api.php --apply --delay-ms=250
php scripts/repair_ibnmajah_db_from_source.php
php scripts/fix_ahmed_from_sunnah_api.php --apply --delay-ms=1800
php scripts/repair_ahmed_db_from_source.php
php scripts/fix_aladab_from_sunnah_api.php --apply --delay-ms=900
php scripts/repair_aladab_db_from_source.php
php scripts/fix_shamail_from_sunnah_api.php --apply --delay-ms=600
php scripts/repair_shamail_db_from_source.php
php scripts/fix_mishkat_from_sunnah_api.php --apply --delay-ms=1800
php scripts/repair_mishkat_db_from_source.php
php scripts/fix_darimi_from_sunnah_web.php --apply --delay-ms=750
php scripts/repair_darimi_db_from_source.php
php scripts/fix_riyad_from_sunnah_api.php --apply --delay-ms=600
php scripts/repair_riyad_db_from_source.php
php scripts/fix_bulugh_from_sunnah_web.php --apply --delay-ms=250
php scripts/repair_bulugh_db_from_source.php
php scripts/normalize_hadith_sunnah_metadata.php --apply
php scripts/cleanup_empty_hadith_books.php --apply
```

Report files:

- `storage/app/hadith_sunnah_parity/summary.json`
- `storage/app/hadith_sunnah_parity/issues.json`

## Current Verified State

- Hadith coverage verification passes: 51,441 hadith rows, 47,168 chapter-linked rows.
- `nawawi40`: fixed and parity-clean against Sunnah.com.
- `qudsi40`: fixed and parity-clean against Sunnah.com.
- `shahwaliullah40`: parity-clean in the initial pass.
- `malik`: fixed and web-parity-clean against Sunnah.com.
- `bukhari`: fixed and API/web-fallback parity-clean against Sunnah.com.
- `muslim`: fixed and API-parity-clean against Sunnah.com.
- `abudawud`: fixed and API-parity-clean against Sunnah.com.
- `tirmidhi`: fixed and API/fallback parity-clean against Sunnah.com.
- `nasai`: fixed and API-parity-clean against Sunnah.com.
- `ibnmajah`: fixed and API-parity-clean against Sunnah.com.
- `ahmed`: fixed and API-parity-clean against Sunnah.com.
- `aladab_almufrad`: fixed and API/source-fallback parity-clean against Sunnah.com.
- `shamail_muhammadiyah`: fixed and API-parity-clean against Sunnah.com.
- `mishkat_almasabih`: expanded and API-routed parity-clean against Sunnah.com.
- `darimi`: fixed and web-parity-clean against Sunnah.com.
- `riyad_assalihin`: fixed and API-parity-clean against Sunnah.com.
- `bulugh_almaram`: fixed and web-parity-clean against Sunnah.com.
- Production JSON Sunnah metadata: complete for all 51,441 files (`collections[0].source_url`, `metadata.sunnah_reference.url`, and `source_verification.sunnah.url`).
- Empty legacy books cleanup: complete; 0 empty hadith books remain.
- Active collection: none; hadith audit list is complete.

### `darimi`

Sunnah.com API caveat:

- API collection metadata reports 3,406 total hadiths but 0 available hadiths.
- API book routes return empty data and individual API hadith routes return 404.
- Sunnah web exposes all rows: `/darimi/introduction` for book 0 hadiths 1-649, and `/darimi/1` through `/darimi/23` for hadiths 650-3406.
- Darimi is Arabic-only on Sunnah.com today; English text remains intentionally empty.

Final parity:

- Sunnah.com web rows: 3,406
- Production source rows: 3,406
- DB rows: 3,406
- Text diffs: 0
- Metadata diffs fixed: 3,406 source URL/book metadata rows, plus 2,757 chapter metadata rows
- Missing web rows: 0
- Missing source rows: 0

Applied fixes:

- Rewrote Darimi production JSON metadata/source URLs from Sunnah web without changing Arabic text.
- Normalized source URLs from old URN links to canonical `https://sunnah.com/darimi:{hadith}` URLs.
- Restored chapter metadata for books 1-23 while leaving book 0 introduction rows unlinked, matching existing DB chapter structure.
- Repaired DB rows from corrected source files: 3,406 updated, 0 created, 0 stale rows.
- Local import integrity is clean: 3,406 source files, 3,406 DB rows, 3,406 matched, zero collisions, zero missing.

### `mishkat_almasabih`

Sunnah.com API caveat:

- Collection metadata reports 5,319 available Mishkat hadiths.
- API book routes expose 5,014 rows across introduction, books 1-24, and books 27-29.
- Books 25 and 26 return empty API book routes.
- Individual API/web hadith URLs in the 4628-5378 range return 404 today, so those 305 metadata-only rows cannot be safely invented.
- Mishkat intentionally contains combined Sunnah hadith numbers such as `20, 21`; these must be preserved exactly, not split.

Final parity:

- Sunnah.com API-routed rows: 5,014
- Production source rows: 5,014
- DB rows: 5,014
- Text diffs: 0
- Metadata diffs fixed: 3,875
- Missing API-routed rows added: 587
- Missing source rows after repair: 0

Applied fixes:

- Normalized 3,875 existing Mishkat production JSON files to current Sunnah API metadata/URN/source fields.
- Added 587 new production JSON files for missing API-routed rows, including books 27-29 and the missing book 22 rows.
- Repaired DB rows from corrected source files: 4,427 updated, 587 created, 0 stale rows.
- Updated Mishkat expected floor to 5,014 and chapter-linked baseline to 48,220.
- Local import integrity is clean: 5,014 source files, 5,014 DB rows, 5,014 matched, zero collisions, zero missing.

### `riyad_assalihin`

Findings:

- Sunnah.com API exposes the complete Riyad as-Salihin collection: 1,896 rows across `introduction` and books 1-19.
- Existing production JSON had damaged book/reference metadata and many text mismatches, including only 1,319 unique current collection references.
- The repair had to trust existing refs only when text agreed, then use text-aware and ordered fallback mapping for damaged duplicate slots.

Final parity:

- Sunnah.com API rows: 1,896
- Production source rows: 1,896
- DB rows: 1,896
- Missing API rows: 0
- Missing source rows: 0
- Invalid rows: 0

Applied fixes:

- Rebuilt all 1,896 Riyad production JSON files from Sunnah.com API data.
- Canonicalized `introduction` plus books 1-19, source URLs, Arabic text, English text, Arabic-without-tashkeel text, and Sunnah metadata.
- Repaired DB rows from corrected source files: 1,896 updated, 0 created, 0 stale deleted.
- Local import integrity is clean: 1,896 source files, 1,896 DB rows, 1,896 matched, zero collisions, zero missing.

### `bulugh_almaram`

Sunnah.com API caveat:

- API collection metadata reports 1,767 rows, but API books route exposes only 5 books and many individual API routes return 404.
- Sunnah web exposes the complete collection: 1,767 rows across 16 book pages.
- Bulugh source must therefore be synced from Sunnah web, not the API.

Final parity:

- Sunnah.com web rows: 1,767
- Production source rows: 1,767
- DB rows: 1,767
- Missing web rows: 0
- Missing source rows: 0
- Blank Sunnah references after final repair: 0

Applied fixes:

- Rebuilt Bulugh production JSON text and metadata from Sunnah.com web pages.
- Canonicalized all 1,767 source rows to the 16 Sunnah web book groups, including `metadata.sunnah_reference` and `source_verification.sunnah`.
- Completed a second metadata repair for 225 rows where the top-level collection metadata was corrected but nested Sunnah reference metadata was still missing.
- Repaired DB rows from corrected source files: 1,767 updated, 0 created, 0 stale deleted.
- Local import integrity is clean: 1,767 source files, 1,767 DB rows, 1,767 matched, zero collisions, zero missing.

### Metadata normalization

Applied fixes:

- Backfilled nested `metadata.sunnah_reference` for older production JSON rows whose canonical Sunnah URL already existed in `collections[0].source_url`.
- Backfilled `source_verification.sunnah` with collection slug, book number, hadith number, and canonical Sunnah URL for all 51,441 production JSON files.
- Preserved existing URNs where present.
- Final metadata audit is clean: no missing collection source URLs, no missing nested Sunnah references, and no missing nested source verification URLs.

## Fixed Collections

### `nawawi40`

Final parity:

- Local rows: 42
- Sunnah.com pages scanned: 42
- Fetch failures: 0
- Missing local keys: 0
- Extra local keys: 0
- Hard text mismatches: 0
- Text variants: 0
- URN mismatches: 0

Fixes applied:

- Rewrote all 42 production JSON files from the live Sunnah.com pages.
- Normalized to one canonical collection/book row.
- Removed stale duplicate/suffixed rows created by historical importer collisions.
- Restored the 30 valid chapter links for hadiths 11-14 and 17-42.
- Updated the curated source fixer so future Nawawi rewrites preserve chapter metadata.

### `qudsi40`

Final parity:

- Local rows: 40
- Sunnah.com pages scanned: 40
- Fetch failures: 0
- Missing local keys: 0
- Extra local keys: 0
- Hard text mismatches: 0
- Text variants: 0
- URN mismatches: 0

Fixes applied:

- Rewrote all 40 production JSON files from the live Sunnah.com pages.
- Normalized to one canonical collection/book row.
- Removed stale duplicate/suffixed rows created by historical importer collisions.

### `shahwaliullah40`

Initial parity:

- Local rows: 40
- Sunnah.com pages scanned: 40
- Fetch failures: 0
- Missing local keys: 0
- Extra local keys: 0
- Hard text mismatches: 0
- Text variants: 0
- URN mismatches: 0

### `malik`

Sunnah.com API caveat:

- API collection metadata reports 1,973 available Malik hadiths.
- API book route returns empty data for Malik.
- API individual route returns 404 for Malik.
- Malik must use Sunnah.com web pages (`/malik/{book}/{book-local-hadith}`), not the generic API/book auditor.

Final parity:

- Sunnah.com web rows: 1,973
- Production source rows: 1,973
- DB rows: 1,973
- Text diffs: 0
- Metadata diffs: 0
- Missing web rows: 0
- Missing source rows: 0

Applied fixes:

- Scraped the Malik web index and 61 book pages.
- Rewrote production JSON for source hadiths 1-1973 from Sunnah.com web text and canonical book metadata.
- Repaired existing DB rows in place by `source_file` to avoid importer duplicate rows.
- Canonical DB state now has 61 Malik books, 1,973 `source_verified` mapped rows, zero stale/suffixed Malik numbers, and all Malik rows chapter-linked.
- Removed local/source rows 1974-1985 because they are not present in Sunnah.com's Malik collection.
- Updated Malik expected count to 1,973.
- Updated verified baselines for remaining missing Arabic rows and chapter-linked rows.
- Full verifier passes after cleanup.

### `bukhari`

Sunnah.com API caveat:

- API collection metadata reports 7,277 available Bukhari hadiths.
- API book routes return most rows but omit 110 rows from books 89 and 96.
- API individual route recovered 98 of those rows.
- Sunnah.com web pages recovered the remaining 12 rows: 1 row in book 89 and 11 rows in book 96.
- Source filenames are local sequence IDs (`hadith_1_final.json` through `hadith_7277_final.json`), while Sunnah references run through 7563 with gaps. Bukhari must be mapped by canonical Sunnah order, not by filename number.

Final parity:

- Sunnah.com canonical rows: 7,277
- Production source rows: 7,277
- DB rows: 7,277
- Text diffs: 0
- Metadata diffs: 0
- Missing Sunnah rows: 0
- Missing source rows: 0

Applied fixes:

- Rewrote all 7,277 Bukhari production JSON files from Sunnah.com API data, with narrow web fallback for the 12 API-missing rows.
- Canonicalized all book numbers, book names, chapter metadata, source URLs, Arabic text, English text, Arabic-without-tashkeel text, and Sunnah URNs where available.
- Repaired DB rows from corrected source files: 7,152 updated, 125 created, 315 stale conflicts moved out of canonical slots.
- Local import integrity is clean: 7,277 source files, 7,277 DB rows, 7,277 matched, zero collisions, zero missing.
- Updated Bukhari expected count to 7,277 and chapter-linked baseline to 49,205.
- Full verifier passes with stricter Bukhari and total hadith floors.

### `muslim`

Sunnah.com API caveat:

- API collection metadata reports 7,459 available Muslim hadiths, matching the source file count.
- Muslim references include spaced suffixes (`8 a`) and introduction references (`Introduction 8`), so matching must normalize reference spacing but preserve canonical API text.
- Source order stores books 1-56 first and the introduction at the end; API book metadata lists introduction after book 1. Muslim must be matched by normalized `(book_number, hadith_number)`, not global file order.
- Two source files had duplicate old references and were remapped by text match:
  - `hadith_10934_final.json`: from duplicate `13|1115 c` to `20|1504 i`.
  - `hadith_14618_final.json`: from duplicate `39|2170 b, c` to `56|3019 c`.

Final parity:

- Sunnah.com API rows: 7,459
- Production source rows: 7,459
- DB rows: 7,459
- Text diffs: 0
- Metadata diffs: 0
- Missing Sunnah rows: 0
- Missing source rows: 0

Applied fixes:

- Rewrote all 7,459 Muslim production JSON files from Sunnah.com API data.
- Canonicalized book numbers, book names, chapter metadata, source URLs, Arabic text, English text, Arabic-without-tashkeel text, and Sunnah URNs where available.
- Repaired DB rows from corrected source files: 7,459 updated, 0 created, 0 stale conflicts.
- Local import integrity is clean: 7,459 source files, 7,459 DB rows, 7,459 matched, zero collisions, zero missing.
- Updated chapter-linked baseline to 49,297.
- Full verifier passes after repair.

### `abudawud`

Findings:

- API collection metadata reports 5,276 available Abu Dawud hadiths, matching the production source file count.
- Source had seven duplicate reference collisions and one swapped adjacent pair:
  - `hadith_22489_final.json`: `11|1985` -> `11|1984`.
  - `hadith_22875_final.json`: `14|2371` -> `14|2370`.
  - `hadith_23088_final.json`: `15|2584` -> `15|2583`.
  - `hadith_23113_final.json`: `15|2609` -> `15|2608`.
  - `hadith_24153_final.json`: `26|3649` -> `26|3648`.
  - `hadith_24154_final.json`: `26|3648` -> `26|3649`.
  - `hadith_24485_final.json`: `32|3981` -> `32|3980`.
  - `hadith_24806_final.json`: `37|4251` -> `39|4300`.
  - `hadith_25775_final.json`: `29|3871` -> `43|5269`.
- Text-only duplicate matching was unsafe for Abu Dawud because unrelated short hadiths share identical text. The fixed script maps by source file order when API/source counts match exactly.

Final parity:

- Sunnah.com API rows: 5,276
- Production source rows: 5,276
- DB rows: 5,276
- Text diffs: 0
- Metadata diffs: 0
- Missing Sunnah rows: 0
- Missing source rows: 0

Applied fixes:

- Rewrote 4,220 Abu Dawud production JSON files from Sunnah.com API data.
- Canonicalized book numbers, hadith numbers, book names, chapter metadata, source URLs, Arabic text, English text, Arabic-without-tashkeel text, and Sunnah URNs where available.
- Repaired DB rows from corrected source files: 5,269 updated, 7 created, 3 stale conflicts moved, 0 stale deleted.
- Local import integrity is clean: 5,276 source files, 5,276 DB rows, 5,276 matched, zero collisions, zero missing.
- Updated Abu Dawud expected count to 5,276 and chapter-linked baseline to 49,304.
- Full verifier passes after repair.

### `tirmidhi`

Findings:

- API collection metadata reports 4,053 available Tirmidhi hadiths, matching the production source file count.
- Tirmidhi has many valid repeated printed references for separate "another chain" narrations. Production source must be mapped by ordered source/API position, not by `(book_number, hadith_number)` uniqueness.
- Sunnah API book endpoint for book 25 returns zero rows, but individual hadith endpoints work for most rows. Seven book-25 rows are not individually addressable and were preserved from the existing Sunnah-derived source text.
- DB has a unique `(book_id, hadith_number)` constraint, so repeated printed references must keep existing DB disambiguators such as `391 a`; source JSON remains canonical to Sunnah.

Final parity:

- Sunnah.com API/fallback rows: 4,053
- Production source rows: 4,053
- DB rows: 4,053
- Text diffs: 0
- Metadata diffs: 0
- Missing Sunnah rows: 0
- Missing source rows: 0

Applied fixes:

- Rewrote 856 Tirmidhi production JSON files from Sunnah.com API data, with 71 individual API fallbacks and 7 source-preserved fallbacks for the broken book-25 endpoint.
- Canonicalized book numbers, hadith numbers, book names, chapter metadata, source URLs, Arabic text, English text, Arabic-without-tashkeel text, and Sunnah URNs where available.
- Repaired DB rows from corrected source files: 4,052 updated, 1 created, 0 stale deleted.
- Local import integrity is clean: 4,053 source files, 4,053 DB rows, 4,053 matched, zero collisions, zero missing.
- Updated Tirmidhi expected count to 4,053 and chapter-linked baseline to 49,362.
- Full verifier passes after repair.

### `nasai`

Findings:

- API collection metadata reports 5,768 available Nasai hadiths, matching the production source file count.
- Nasai has repeated printed references. Some are exact duplicate text and some are separate texts under the same printed number, so the source must be mapped by ordered source/API position, not by unique `(book_number, hadith_number)` keys.
- Two Sunnah API rows in book 6 return an empty `hadithNumber`; the repair preserves the existing source hadith number for those API defects instead of writing blank references.
- DB has a unique `(book_id, hadith_number)` constraint, so repeated printed references keep DB disambiguators while source JSON remains canonical to Sunnah.

Final parity:

- Sunnah.com API rows: 5,768
- Production source rows: 5,768
- DB rows: 5,768
- Text diffs: 0
- Metadata diffs: 0
- Missing Sunnah rows: 0
- Missing source rows: 0

Applied fixes:

- Rewrote 290 Nasai production JSON files from Sunnah.com API data.
- Canonicalized book numbers, hadith numbers, book names, chapter metadata, source URLs, Arabic text, English text, Arabic-without-tashkeel text, and Sunnah URNs where available.
- Repaired DB rows from corrected source files: 5,751 updated, 17 created, 0 stale deleted.
- Local import integrity is clean: 5,768 source files, 5,768 DB rows, 5,768 matched, zero collisions, zero missing.
- Updated Nasai expected count to 5,768 and chapter-linked baseline to 49,384.
- Full verifier passes after repair.

### `ibnmajah`

Findings:

- API collection metadata reports 4,345 available Ibn Majah hadiths, matching the production source file count.
- Ibn Majah source stores `introduction` at the end, while the API lists `introduction` after book 1. It must be matched by normalized `(book_number, hadith_number)`, not by global source/API order.
- Source had seven repeated printed references; four rows required deterministic duplicate text remapping. The later duplicated source occurrence was remapped while the first occurrence was preserved.
- DB has a unique `(book_id, hadith_number)` constraint, so repeated printed references keep DB disambiguators while source JSON remains canonical to Sunnah.

Final parity:

- Sunnah.com API rows: 4,345
- Production source rows: 4,345
- DB rows: 4,345
- Text diffs: 0
- Metadata diffs: 0
- Missing Sunnah rows: 0
- Missing source rows: 0

Applied fixes:

- Rewrote 752 Ibn Majah production JSON files from Sunnah.com API data across two apply passes.
- Canonicalized book numbers, hadith numbers, book names, chapter metadata, source URLs, Arabic text, English text, Arabic-without-tashkeel text, and Sunnah URNs where available.
- Repaired DB rows from corrected source files: 4,342 updated and 3 created, then a final 4,345-row sync after duplicate-remap stabilization.
- Local import integrity is clean: 4,345 source files, 4,345 DB rows, 4,345 matched, zero collisions, zero missing.
- Updated Ibn Majah expected count to 4,345 and chapter-linked baseline to 49,391.
- Hadith verifier rows pass after repair; full verifier is currently blocked by unrelated Quran translation duplicate counts in the DB.

### `ahmed`

Final parity:

- Sunnah API rows: 1,359
- Production source rows: 1,359
- DB rows: 1,359
- Text diffs: 0
- Metadata diffs: 0
- Missing Sunnah rows: 0
- Missing source rows: 0

Applied fixes:

- Rewrote 1,359 Musnad Ahmad production JSON files from Sunnah.com API data using the official API slug `ahmad`.
- Filled missing collection metadata in the early source files that previously had no canonical collection row.
- Canonicalized collection title, book numbers, hadith numbers, book names, source URLs, Arabic text, English text, Arabic-without-tashkeel text, and Sunnah URNs where available.
- Removed 15 stale duplicate production JSON files that were appended after Sunnah's available Ahmed sequence.
- Repaired DB rows from corrected source files: 1,359 updated, 0 created, 14 stale DB rows deleted.
- Updated Ahmed verifier mapping/count from `musnad-ahmad-ibn-hanbal`/1,373 to `ahmed`/1,359.
- Updated chapter-linked baseline to 48,018 because Sunnah API marks Musnad Ahmad as having books but no chapters.
- Local import integrity is clean: 1,359 source files, 1,359 DB rows, 1,359 matched, zero collisions, zero missing.
- Hadith verifier rows pass after repair; full verifier is currently blocked by unrelated Quran translation duplicate counts in the DB.

### `aladab_almufrad`

Final parity:

- Sunnah API/book route rows: 1,185
- Source fallback rows for Sunnah-unavailable book ranges: 141
- Production source rows: 1,326
- DB rows: 1,326
- Text diffs: 0
- Metadata diffs: 0
- Missing Sunnah/source rows: 0

Applied fixes:

- Rewrote all 1,326 Al-Adab Al-Mufrad production JSON files using Sunnah API slug `adab`.
- Used Sunnah API book routes for 1,185 rows.
- Used source fallbacks for 141 rows from Sunnah-listed book ranges that are omitted from API book routes and return unavailable on individual API/web hadith URLs.
- Filled missing book titles for API-omitted ranges from the Sunnah.com Al-Adab book list.
- Canonicalized collection title, book numbers, hadith numbers, book names, chapter metadata, source URLs, Arabic text, English text, Arabic-without-tashkeel text, and Sunnah URNs where available.
- Repaired DB rows from corrected source files: 1,315 updated and 11 created.
- Local import integrity is clean: 1,326 source files, 1,326 DB rows, 1,326 matched, zero collisions, zero missing.
- Updated Al-Adab expected count to 1,326 and chapter-linked baseline to 48,031.
- Hadith verifier rows pass after repair; full verifier is currently blocked by unrelated Quran translation duplicate counts in the DB.

### `shamail_muhammadiyah`

Final parity:

- Sunnah API rows: 400
- Production source rows: 400
- DB rows: 400
- Text diffs: 0
- Metadata diffs: 0
- Missing Sunnah rows: 0
- Missing source rows: 0

Applied fixes:

- Rewrote 400 Ash-Shama'il Al-Muhammadiyah production JSON files from Sunnah.com API data using API slug `shamail`.
- Sorted API rows by hadith number because the API books listing places book `-8` out of hadith-number order.
- Removed 2 stale duplicate production JSON files for repeated printed references `70` and `71`.
- Canonicalized collection title, book numbers, hadith numbers, book names, source URLs, Arabic text, English text, Arabic-without-tashkeel text, and Sunnah URNs where available.
- Repaired DB rows from corrected source files: 398 updated, 2 created, and 2 stale DB rows deleted.
- Local import integrity is clean: 400 source files, 400 DB rows, 400 matched, zero collisions, zero missing.
- Updated chapter-linked baseline to 47,632 because Sunnah API marks Shamail as having books but no chapters.
- Hadith verifier rows pass after repair; full verifier is currently blocked by unrelated Quran translation duplicate counts in the DB.

## Remaining Audit Order

No remaining hadith collections in the current audit list.

For each collection:

- Audit against Sunnah.com.
- Fix production JSON first.
- Re-import only that collection.
- Remove stale duplicate rows only when the importer created collision suffixes.
- Run parity again until missing, extra, mismatch, variant, and URN counts are all zero.
- Run the full verifier before moving to the next collection.
