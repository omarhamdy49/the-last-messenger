# Dev rollout — 006-quran-qf-refactor

Database `the_last_messenger` on 127.0.0.1:3308. Run on 2026-09-17 from the worktree
`.claude/worktrees/006-quran-qf-refactor`.

## 1. Drop the old Quran tables (T136)

The owner asked for the old Quran tables and migration rows to go once the code was done.
Followed `quickstart.md` §Existing environment, never `migrate:fresh`.

- **Asserted the target** first: `DB::connection()->getDatabaseName()` returned
  `the_last_messenger`, and the scripts refuse to run against any other name.
- **Backed up only the replaced tables** with `mysqldump` to `~/quran-backup-2026-09-17.sql`
  (245 MB, 19 `CREATE TABLE` statements). The password is read from `.env` inside the script, so
  it never reaches a command line.
- **Dropped by explicit name**, with foreign key checks off, exactly the 18 `quran_*` tables plus
  `reciter_translations` that `SHOW TABLES` listed. `ai_inquiries` and `ai_inquiry_usages` were
  not in the list and were left alone.
- Deleted the Quran rows from `migrations` (excluding `ai_inquir*`) and the orphaned `favorites`
  rows of type `quran` and `reciter`.
- After the drop, the only matching tables left were `ai_inquiries` and `ai_inquiry_usages`, and
  no Quran migration rows remained.

**One unrelated blocker:** `2026_03_09_200000_create_seerah_events_gallery_and_media_tables` was
pending while its two tables already existed — drift left by the 005 Seerah rebuild on this
database, not by this work. Both tables matched the migration's definition column for column, so
the migration was recorded as run (batch 11) rather than re-created or dropped.

`php artisan migrate` then created the 12 pending tables: the 10 new `2026_09_18_*` Quran
migrations, `api_clients`, and the rebuilt playlist tables.

Seeded `QuranMushafSeeder` (8 mushafs) and `RolePermissionSeeder`.

## 2. Doctor

`php artisan quran:qf:doctor` against **production** (`https://apis.quran.foundation/content/api/v4/`):

| capability | status |
|---|---|
| token | OK |
| content | OK |
| hadith | not_found |
| reflect | insufficient_scope |

Hadith references and Quran Reflect posts stay unimplemented (US5b) until those scopes are
granted, per the constitution rule that a table ships only with its importer.

## 3. Bootstrap (SC-001)

`php artisan quran:qf:bootstrap`, parity gates as printed:

**structure** — 2263 rows written, 0 failed.

| gate | result |
|---|---|
| chapters | 114 of 114 |
| chapter names | 570 of 570 |
| juz / hizb / rubʿ / manzil / rukūʿ | 30 / 60 / 240 / 7 / 558, each stored in full |
| pages (mushaf 1) | 604 of 604 |
| languages | 80 returned (at least 50 expected) |

**verses** — 6236 of 6236 stored; the per-chapter `verses_count` matched for all 114 chapters;
no verse was missing `text_uthmani`, `text_uthmani_simple` or `code_v2`.

**words** — the first attempt died at PHP's CLI default of 128M mid-upsert, after 58,623 words
and 113,641 glosses. That is a real defect in the commands, not the data: an import that moves
~77k words per language is a batch job and must not depend on the ambient `memory_limit`. Fixed
by `ReportsImport::raiseMemoryLimit()`, which every QF import command now calls first; it raises
the limit to 1G and never lowers an already-higher one. Re-run resumed cleanly because every
importer is idempotent.

On the re-run: 83,665 words stored against a floor of 77,430, no verse left without words, and
every verse had exactly one verse-end mark. Word glosses came to 244,101 rows across the enabled
languages.

**catalogue** — 227 rows written, 0 failed: 144 translations, 23 tafsirs, 9 chapter infos, 1
verse-media, 12 ayah recitations and 21 chapter reciters, all inactive until an editor enables
them. The importer also reported what QF has available to sync: 13 mushafs, 16 word-by-word
translations, 1 transliteration and 2 chapter-recitation sets.

## 3b. Enabled resources (T137)

What an editor would tick, applied on dev: one translation per locale (Tafsir Al-Muyasser ar,
Saheeh International en as default, Hamidullah fr, Elmalili tr, Junagarhi ur) plus the
transliteration (QF 57), two Arabic tafsirs (Ibn Kathir, al-Tabari), one chapter-info resource
(Maududi), English word-by-word translation and transliteration, two chapter reciters (Abdul
Basit) and one ayah recitation (AbdulBaset AbdulSamad). Totals: 6 translations, 2 tafsirs, 1
chapter info, 1 + 1 word-by-word, 2 chapter reciters, 1 recitation.

## 3c. Sync, audio, chapter infos, assets (T137)

`quran:qf:sync --bootstrap` — run #1 applied 9 mutations from 9 snapshots: 132,524 rows written,
605 updated, 0 failed, 0 skipped. The state row for the canonical filter
`chapter_recitations:1,2;mushafs:1;recitations:1;tafsirs:14,15;translations:20,31,52,54,57,1014`
ended `idle` at sequence 433,775. Totals after it: 36,458 translation rows, 12,401 tafsir rows,
392,448 word glosses, 604 mushaf pages and 83,665 mushaf words.

**Audio needed the reciter fetch, not the sync.** The sync feed's `chapter_recitations` group is
keyed by its own ids, so the two enabled chapter reciters (QF catalogue ids 1 and 2) came back
empty from it. `quran:reciter:fetch 196 --data --audio --timestamps` wrote 6,350 rows — 114
chapter files and 6,236 timings — and `quran:reciter:fetch 184 --data --audio` wrote the 6,236
ayah files. Worth knowing for the prod run: enable the reciter, then fetch it.

`quran:qf:import-chapter-infos` wrote all 114 rows. `quran:qf:download-assets --fonts=…` pulled
the QCF page fonts to `public/fonts/quran`.

**Reader check (chapter 2, Arabic UI, `php artisan serve`)**: the page renders 1.8 MB of real
content — the Arabic text, the Saheeh International translation, word-by-word data, the reciter
picker with remote audio URLs, the tajweed styles, the QCF V2 glyph font hooks, and the chapter
info panel with its source line ("Tafhim al-Qur'an").

## 3d. Re-run behaviour (SC-002)

- `quran:qf:sync` a second time: **run #2, 0 mutations, 0 snapshots, 0 rows**. The token stored
  by run #1 is doing its job.
- `quran:qf:import-structure` a second time: 0 written, **604 updated**, 1,659 unchanged. The 604
  are the mushaf pages, which the mushaf snapshot had rewritten during the sync; the structure
  import put them back in the `/pages` shape. A third run reported **0 written, 0 updated, 2,263
  unchanged**, so the two writers agree from there on. Worth knowing before prod: run the
  structure import once after the first sync, then it is a true no-op.

## 4. API boundary (T138)

`php artisan api:client:create "Mobile app (dev)" --scopes=quran --scopes=user
--origins=http://localhost:3000 --limit=120` printed the key and secret once (client #1). A
second client with only the `seerah` scope was created to prove the scope check. Set
`CORS_ALLOWED_ORIGINS=http://localhost:3000,http://localhost:5173` in `.env`.

Against `php artisan serve --port=8123`:

| case | result |
|---|---|
| no credentials | 401, `type: invalid_api_credentials` |
| valid key, wrong secret | 401, same body |
| valid client, no bearer token | 401 `Unauthenticated.` — content routes sit behind Sanctum too |
| valid client + bearer, `GET /quran/chapters` | 200, QF-shaped `{success, data.chapters[]}` |
| valid client + bearer, `GET /quran/verses/by_key/2:255?words=true` | 200, verse 262 with its words and word audio URLs |
| client scoped to `seerah` only | 403, `type: insufficient_scope` |
| `GET /health` with no credentials | 200 — the only anonymous route |
| preflight from `http://localhost:5173` (allowed) | 204 with `Access-Control-Allow-Origin: http://localhost:5173` |
| preflight from `http://evil.example` | 204 with **no** `Access-Control-Allow-Origin` |

One nuance worth knowing: when exactly one origin is configured, the CORS middleware returns
that origin's header statically for any preflight. The browser still blocks the response because
the header does not match the requesting origin, and with two or more origins configured the
header is omitted for a disallowed origin, as the table above shows.

## 5. Browser walkthrough (T140)

Chrome, Arabic UI, `http://127.0.0.1:8123/quran/2`:

- Reading (mushaf) view renders the page as continuous Arabic text with round verse markers, a
  basmala band, and the header showing البقرة / The Cow — Al-Baqarah with مدنية, آية 286, الجزء 1
  and ترتيب النزول 87.
- The tools rail sits on the inline-start side and switches between آيات (verse cards) and العرض,
  with font size, listening, display settings, chapter switch and chapter info.
- Verse-card view shows the Arabic, the transliteration ("Alif, Lām, Meem."), the Saheeh
  International translation and its numbered footnote expanded underneath, plus a verse-image
  link — so the footnote JSON and the verse media both resolve.
- Dark mode repaints the whole reader, header and rail.
- No console errors or exceptions on the page.
- `grep -rnE "\b(pl|pr|ml|mr|left|right)-[0-9]" resources/views/public/quran resources/views/admin/quran`
  returns nothing, so every spacing utility is logical (RTL-safe).
- `/quran/page/42`, `/quran/juz/3`, `/quran/reciters` and `/quran/search?q=الرحمن` all return 200
  with real content; page 42 renders Ayat al-Kursi with its transliteration.

Assets: `quran:qf:download-assets --fonts=v1,v2,hafs,indopak` wrote all 1,210 font files, 0
failed — 142 MB under `public/fonts/quran`.

## 6. Final gates (T142)

| gate | result |
|---|---|
| `php artisan test` | 1,293 passed, 1 failed, 1 incomplete, 1 skipped — the failure is the pre-existing `ArticleSeriesIntegrationTest` case recorded in `baseline.txt` |
| `./vendor/bin/pint --test` | passed |
| `composer deptrac` | 0 violations, 0 errors |
| `npm run build` | built |
| RTL grep gate | empty |

Production (T139) is not run from here: it needs a maintenance window and follows the standing
purge-and-resync practice.
