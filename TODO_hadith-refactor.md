# TODO — Hadith Module: Full Greenfield Rebuild

> **Status:** v2 — all 15 blocking review items resolved. **Awaiting approval.**
> **Date:** 2026-07-30 · **Scope:** schema + domain module + admin UI + public pages
> **Review:** `docs/hadith-refactor-review-2026-07-30.md` (Codex 0.144.1 — v1 verdict `needs-rework`)

Task IDs (`HR-xxx`) are stable. Do not renumber.

---

## 0. What changed from v1

Codex reviewed v1 against the actual repo and found **15 blocking defects**. I independently
verified its four most consequential claims — all four held:

| Claim | Verified |
|---|---|
| `AppServiceProvider.php:10-12` imports 3 Hadith services, missing from the purge list | ✅ container boot would fail |
| v1's diagram promises `primary_narrator_id`; the migration has no such column | ✅ no narrator column exists |
| `--step=12` counts migration *files*, not tables | ✅ would roll back a non-Hadith migration |
| `HasAuditLog`/`HasVersions`/`ContentVersion` already exist | ✅ all present in `Domain/Shared` |

**The two corrections that change the design:**

1. **`php artisan hadith:extract` was unimplementable.** Artisan runs server-side and cannot drive a
   user's Chrome; server-side HTTP gets 403 from Cloudflare. Extraction is now **agent-driven through
   the Chrome tools**, with Artisan only validating and importing. (Phase 2)
2. **The FULLTEXT index did not match the query.** MySQL requires `MATCH()`'s column list to match a
   FULLTEXT index exactly, so `FULLTEXT (body, body_normalized)` cannot serve
   `MATCH(body_normalized)` — and indexing the same Arabic text twice inflates term frequencies and
   corrupts relevance. Replaced by a single `search_body` column. (§4.5)

**Two further inputs folded in:**

- **`docs/hadith-rebuild-plan.md`** — prior work already in this repo that v1 was written without. It
  documents the `authenticated_by` trap (§3.4) and the lowercasing rule (§1.1). Its own percentages
  conflate *key presence* with *populated*; corrected in §3.3.
- **Re-measurement** of `grading.*` sub-fields v1 never examined — `albani_grade`, `agreement`.

---

## 1. Context

**Code.** 125 files in `app/Domain/Hadith/` implementing full CQRS — 11 Commands, 18 Queries, 24
Handlers — most handlers a single delegating call. `Domain/Seerah` does the same job in 36 files.

**Schema.** 22 tables carrying dead concepts: `hadith_keywords` (tokenised Arabic stopwords), three
redundant `*_translations` side tables, `hadith_grading_levels` beside a free-text grade column,
`hadith_companions` and `hadith_narrators` describing the same entity.

**Data.** None. A `2026_07_29` migration had already cut `hadith_hadiths` to 42 rows. Then, this
session, I ran `migrate:fresh --env=testing` without an explicit `DB_DATABASE`; with no
`.env.testing` present it resolved to the main dev database and dropped every table. That was my
error — root cause and fail-closed fix in §12.1. Old data and backups are discarded by direction.

**Goal.** A small, correct schema using sunnah.com's *domain concepts* but not its physical design,
populated from sunnah.com's book pages via the Chrome extension, with module, admin, and public pages
rebuilt.

---

## 2. Decisions

| # | Decision | Status |
|---|---|---|
| D1 | Text as normalised per-locale rows (`hadith_texts`) | settled |
| D2 | Core hierarchy + references; `hadith_scholars` a flat lookup | settled |
| D3 | Old data and backups discarded | settled |
| D4 | MySQL FULLTEXT, no new infrastructure | settled |
| D5 | CQRS dropped → Service + Repository | settled |
| D6 | Source: **Chrome extension (agent-driven) → JSON → Artisan import** | **revised** |
| D7 | Old module deleted wholesale | settled |
| D8 | Public URLs `/hadith/{collection}:{number}` | settled |
| D9 | Arabic + English only (607 book pages) | settled |
| D10 | Admin full CRUD on all six entities | ⚠ **see §14.1 — Codex dissents** |
| D11 | `hadith_notes` dropped | settled |
| D12 | `hadith_cross_refs` + `hadith_related_verses` **also dropped** — no extraction source | **new** |
| D13 | Reuse existing `HasAuditLog`/`HasVersions`; no new audit columns, no soft deletes | **new** |
| D14 | **All hadith migrations authored fresh** from §4 as one clean set. The 12 written on 2026-07-30 are **deleted, not amended**; no incremental follow-up migrations | **new** |
| D15 | **Our own naming.** Every table is `hadith_*` snake_case plural. sunnah.com's CamelCase table names are **never** reused — see §4.0 | **new** |

---

## 3. Evidence base

### 3.1 sunnah.com as it is

Flask API + `SQLAlchemy.db.reflect()`, 4 models, 14 read-only endpoints keyed on `collectionName`
**string**. Six legacy tables, **no foreign keys**, mixed MyISAM/InnoDB. `HadithTable` is one
denormalised row per hadith holding `arabicURN, arabicText, arabicgrade1, arabicBabName` **and**
`englishURN, englishText, englishgrade1, englishBabName` side by side; per-language source tables are
stitched by `matchtable`.

**Live reference block**, confirmed on `/bukhari:1`:

```
Reference                       : Sahih al-Bukhari 1
In-book reference               : Book 1, Hadith 1
USC-MSA web (English) reference : Vol. 1, Book 1, Hadith 1
                                  (deprecated numbering scheme)
```

Built in `{Arabic,English}Hadith::populateReferences()`, branching on `Book.status`, substituting
`Collections.reference_template`.

**Reject:** parallel per-language columns · `decimal(3,1)` book ids and magic `-1`/`-35` ids · zero
FKs · MyISAM · `varchar(3)` holding `'yes'` · `Book.status` overloaded as verified+layout+progress ·
CSV teacher/student columns · `grade1` that is sometimes a string and sometimes JSON.

### 3.2 Corpus inventory

`prophet-data-generator/hadith/production/` — **51,441 JSON files, 17 collections, ~320 MB**
(v1 said 1.7 GB; that was the whole `hadith/` tree including `audit/`, `raw/`, `output/`, `final/`).
**Reference corpus only — not the import source.**

| Collection | Books | Hadiths | | Collection | Books | Hadiths |
|---|---:|---:|---|---|---:|---:|
| bukhari | 97 | 7,277 | | mishkat_almasabih | 28 | 5,014 |
| malik | 61 | 1,973 | | darimi | 24 | 3,406 |
| muslim | 57 | 7,459 | | riyad_assalihin | 20 | 1,896 |
| aladab_almufrad | 57 | 1,326 | | bulugh_almaram | 16 | 1,767 |
| shamail_muhammadiyah | 56 | 400 | | ahmed | 7 | 1,359 |
| nasai | 52 | 5,768 | | qudsi40 | 1 | 40 |
| tirmidhi | 49 | 4,053 | | shahwaliullah40 | 1 | 40 |
| abudawud | 43 | 5,276 | | nawawi40 | **0** | 42 |
| ibnmajah | 38 | 4,345 | | **TOTAL** | **607** | **51,441** |

`nawawi40` has **no book structure** — see HR-104 (synthetic book).

### 3.3 Field fill rates — measured, 3,000-record sample, seed 7

**Presence of a key is not presence of data.** Both columns given:

| Field | key present | populated | Consequence |
|---|---:|---:|---|
| Arabic body | 99.9% | 99.9% | required |
| English body | — | 92.7% | **omit the locale row** when absent (§4.5) |
| `ar_notashkeel` | — | 72.8% | seeds `search_body`; generate the other 27% |
| `sunnah_reference.urn` | — | 65.4% | nullable UNIQUE, never identity |
| `sunnah_reference.urn_en` | — | 58.2% | nullable UNIQUE — but see HR-201 |
| `grading.status` | 99.9% | **90.7%** | free text, 130+ variants |
| `grading.authenticated_by` | 99.0% | **40.4%** | ⚠ **collection provenance — §3.4** |
| `grading.notes_en` / `notes_ar` | 95.8% | 38.8% | grading notes |
| `grading.agreement` | 89.2% | **11.0%** | free-text prose, not an enum |
| `grading.albani_grade` | 27.8% | **8.9%** | **second grading row**, scholar = al-Albani |
| `grading.verified` | 64.7% | 64.7% | boolean |
| `themes_en` | — | 30.8% | ⚠ no extraction source — §4.9 |
| `keywords` | 100% | 33.1% | **junk, not modelled** — stopwords (`"حدثنا"`, `"قتيبة،"`) |
| `related_verses` | 78% | **1.9%** | **dropped (D12)** |
| `applications` / `scholarly_comments` | 78% | **5.6% / 5.5%** | **dropped (D11)** |

Legacy variants at 0.1–0.4%: `status_en`, `scholars`, `chain_notes`, `parallel_narrations`,
`graded_by`, `notes`. Handle explicitly or log-and-skip — **never silently ignore**.

### 3.4 The two grading traps

**Trap 1 — `authenticated_by` is collection provenance, not a rijal grading.** Top values are
`Imam al-Bukhari` (529/3000) and `Imam Muslim` (515/3000): a hadith *in* Sahih al-Bukhari, not an
independent scholarly verdict. The previous importer created a grading row per entry and produced
**9,379 + 8,863 bogus rows**. Do **not** synthesise a grading scholar from collection membership.

**Trap 2 — `grading.status` is free text**, 130+ distinct values in 3,000 records (400+ expected
across 51k):

- casing/transliteration — `sahih` (44.7%), `Sahih` (16.9%), `Sahīh`, `Da'if`, `Da’if`, `Da'īf`
- Arabic — `صَحِيح`, `ضَعِيف`, `حسن`, `مَوْضُوع`, `مُتَّفَقٌ عَلَيْهِ`, `لم تتمّ دراسته`
- **scope-qualified** — `Sahih Isnād`, `Da'if in chain`, `Sanad Da'if wal-Hadīth Hasan`
- publisher citations — `Sahih (Darussalam) [, Muslim (1409)]`
- parse artifacts — `Sahih (Darussalam) [.`, `[Hasan (Darussalam) []`
- **multi-claim in one string** — `1: Hasan<br>2: Da'if`, `صَحِيح, ضَعِيف`
- prose — `Hasan because of corroborating evidence; this is a da'eef isnad because …`

The old importer lowercased each raw string into a `hadith_grading_levels` row, producing **566 junk
taxonomy entries** like `sahih_(darussalam)_[bukhari_3615_and_muslim_2009]`. §4.6 prevents a repeat.

---

## 4. Target schema — 10 tables

Down from 22. All InnoDB `utf8mb4_unicode_ci`. Translatable columns are `json` via
spatie/laravel-translatable, Arabic fallback.

### 4.0 Naming — ours, not sunnah.com's (D15)

We borrow sunnah.com's **concepts**, never its identifiers. Every table is `hadith_*`, snake_case,
plural; every column is snake_case. sunnah.com's CamelCase names must not appear anywhere in
migrations, models, or queries.

| sunnah.com | ours |
|---|---|
| `Collections` | `hadith_collections` |
| `BookData` | `hadith_books` |
| `ChapterData` | `hadith_chapters` |
| `HadithTable` | `hadith_hadiths` |
| `ArabicHadithTable` / `EnglishHadithTable` | `hadith_texts` (one row per locale) |
| `matchtable` | *(none — `sunnah_urn` / `sunnah_urn_en` columns instead)* |
| `Narrators` | `hadith_scholars` |
| `narrator_ahadith`, `clusters` | *(none — out of scope, D2)* |

Column naming follows the same rule: `arabicURN` → `sunnah_urn`, `englishURN` → `sunnah_urn_en`,
`ourHadithNumber` → `in_book_number`, `babID` → `chapter_id`, `hadithNumber` → `hadith_number`,
`grade1` → `raw_grade`, `hasbooks` → `has_books`, `xrefs` → *(dropped, D12)*.

The only place a sunnah.com identifier survives is the `sunnah_urn` / `sunnah_urn_en` **column
names**, where the prefix is deliberate: it marks the value as an external system's key, not ours.

```
hadith_collections
   └─< hadith_books
          └─< hadith_chapters
                 └─< hadith_hadiths
                        ├─< hadith_texts          (one row per locale that HAS text)
                        ├─< hadith_gradings       >── hadith_scholars
                        └─< hadith_hadith_themes  >── hadith_themes
hadith_scholars >── sahaba (nullable)
hadith_import_runs
```

> **No narrator FK.** v1's diagram promised `primary_narrator_id`; no such column exists and no
> extraction source resolves narrator identity. The preamble ("Narrated Ibn 'Umar:") is
> **locale-specific text**, so it lives in `hadith_texts.narrated_by`.

### 4.1 `hadith_collections`

`id` · `code` varchar(50) UNIQUE (`bukhari` — URL/API segment) · `name` json · `author` json NULL ·
`short_intro` json NULL · `about` json NULL · `has_books` bool · `has_chapters` bool ·
`has_volumes` bool · `reference_template` varchar(255) NULL · `is_published` bool · `featured` bool ·
`display_order` uint · timestamps

**No `total_hadiths` counter** — 17 rows; use `withCount` (counters drift under CRUD).
**No composite index** — at 17 rows a scan beats an index. Add only if HR-608 shows otherwise.

### 4.2 `hadith_books`

`id` · `collection_id` FK restrict · `book_number` varchar(20) · `sort_order` uint · `title` json ·
`intro` json NULL · `first_hadith_number` / `last_hadith_number` varchar(50) NULL ·
`is_synthetic` bool default 0 · `is_published` bool · timestamps

`UNIQUE (collection_id, book_number)` · `INDEX (collection_id, sort_order)` ·
**`UNIQUE (id, collection_id)`** — parent key for the composite FK in §4.4

`is_synthetic` marks the placeholder book for bookless collections (HR-104). No `hadith_count`
counter — use `withCount`.

### 4.3 `hadith_chapters`

`id` · `book_id` FK **restrict** · `chapter_number` varchar(20) · `sort_order` uint · `title` json ·
`intro` json NULL · `ending` json NULL · `is_published` bool · timestamps

`UNIQUE (book_id, chapter_number)` · `INDEX (book_id, sort_order)` ·
**`UNIQUE (id, book_id)`** — parent key for the composite FK in §4.4

Deletion is **RESTRICT**, not `nullOnDelete`: silently orphaning every hadith in a chapter because an
admin deleted it is not an acceptable outcome.

### 4.4 `hadith_hadiths` — identity and structure only

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `collection_id` | FK restrict | denormalised for collection-scoped queries |
| `book_id` | FK restrict **NOT NULL** | synthetic book for bookless collections |
| `chapter_id` | FK **restrict** NULL | NULL where `has_chapters = 0` |
| `hadith_number` | varchar(50) | canonical, e.g. `7533`, `1a` |
| `in_book_number` | uint NULL | → "In-book reference" |
| `volume_number` | varchar(20) NULL | USC-MSA refs |
| `sunnah_urn` / `sunnah_urn_en` | uint UNIQUE NULL | 65% / 58% fill |
| `display_grade` | enum(sahih,hasan,daif,mawdu,unknown) default unknown | **renamed** from `grade` — §4.6 |
| `is_published` | bool | |
| timestamps | | |

**Composite FKs — the integrity fix.** Without these, a hadith can name collection A, a book from
collection B, and a chapter from a third book:

```sql
FOREIGN KEY (book_id, collection_id) REFERENCES hadith_books    (id, collection_id)
FOREIGN KEY (chapter_id, book_id)    REFERENCES hadith_chapters (id, book_id)
```

`UNIQUE (collection_id, hadith_number)`
`INDEX (book_id, is_published, in_book_number)` — public book reading order
`INDEX (chapter_id, is_published, in_book_number)` — public chapter reading order
`INDEX (is_published, id)` — random / pagination

**No `text_hash` column.** It existed only for a one-time dedupe report — compute it in the report
query (HR-311) rather than storing and indexing 51k hashes forever.

### 4.5 `hadith_texts` — one row per locale **that has text**

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `hadith_id` | FK cascade | |
| `locale` | enum(ar,en,fr,tr,ur) | |
| `body` | longtext NOT NULL | as displayed, diacritics intact |
| `search_body` | longtext NOT NULL | **the only FULLTEXT column** |
| `narrated_by` | varchar(500) NULL | locale-specific narrator preamble |
| `sanad` | text NULL | chain portion |
| `matn` | longtext NULL | the saying |
| `translator` | varchar(255) NULL | |
| `source_url` | varchar(500) NULL | |
| timestamps | | |

`UNIQUE (hadith_id, locale)` · **`FULLTEXT ft_hadith_texts_search (search_body)`**

**Why one `search_body`.** MySQL requires the `MATCH()` column list to match a FULLTEXT index
exactly, so `FULLTEXT (body, search_body)` cannot serve `MATCH(search_body)`. Indexing both also
indexes the same Arabic text twice, inflating term frequencies and corrupting relevance ranking. The
importer populates it per locale: **normalised Arabic** for `ar` (diacritics stripped,
alef/hamza/ya folded), **the original body** for Latin-script locales.

**No `chapter_title`** — the verified selectors read chapter headings, not a distinct per-record
override; two values that can only drift. **No `notes`** — no source, no editorial workflow.

**Missing English is normal (7.3%): omit the row.** `body` is `NOT NULL`, so an Arabic-only hadith
has exactly one row. Every read path must tolerate a missing locale and fall back to Arabic.

### 4.6 `hadith_gradings` — one row per **claim**

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `hadith_id` | FK cascade | |
| `scholar_id` | FK restrict NULL | |
| `grade` | enum(sahih,hasan,daif,mawdu,unknown) | normalised |
| `scope` | enum(hadith,isnad) default hadith | `Sahih Isnād` ≠ `Sahih` |
| `part_label` | varchar(100) NULL | multi-claim: `"1"`, `"2"`, or a quoted phrase |
| `authority_rank` | tinyint unsigned default 100 | lower wins; drives `display_grade` |
| `raw_grade` | **text** | verbatim, never discarded (`varchar(1000)` truncates the prose cases) |
| `locale` | enum(ar,en,fr,tr,ur) NULL | |
| `notes` | text NULL | |
| `source_reference` | varchar(255) NULL | |
| timestamps | | |

`INDEX (hadith_id)` · `INDEX (grade, hadith_id)`

**One source string can assert several claims.** `1: Hasan<br>2: Da'if` and
`Sanad Da'if wal-Hadīth Hasan` each produce **two rows** — the latter as `(daif, isnad)` +
`(hasan, hadith)` — sharing one `raw_grade`. v1's single-`(grade, scope)` normaliser silently
discarded one claim per such record.

**`display_grade` policy** (deterministic; v1 asserted an invariant with no rank column to enforce it):

1. Consider only `scope = 'hadith'` rows.
2. Exactly one distinct grade → that grade.
3. Zero rows, conflicting grades, or any `part_label IS NOT NULL` → `unknown`.

Recomputed in the same transaction as any grading write, in `HadithAdminService`. The admin edits
gradings; **`display_grade` is never directly editable.**

**`albani_grade` (8.9%) → a second grading row**, scholar = al-Albani.
**`authenticated_by` → no grading rows** (§3.4 Trap 1).

### 4.7 `hadith_scholars` — narrators and graders in one table

`id` · `slug` varchar(255) UNIQUE · `name` json · `full_name` json NULL · `kunya` json NULL ·
`biography` json NULL · `birth_year_hijri` / `death_year_hijri` int NULL · `generation` varchar(50)
NULL · `reliability` varchar(100) NULL · `is_companion` bool · `sahaba_id` FK → `sahaba`
nullOnDelete UNIQUE NULL · timestamps

Both are scholars and al-Bukhari is both, so two tables would duplicate him. **No index on
`is_companion`** (low cardinality) or `death_year_hijri` — speculative until a query needs them.

### 4.8 `hadith_import_runs`

`id` · `collection_code` varchar(50) · `source_path` varchar(500) · `status`
enum(pending,running,completed,failed) · `counts` json · `unmapped_grades` json ·
`error_message` text · `started_at` · `completed_at` · timestamps ·
`INDEX (collection_code, status)`

### 4.9 `hadith_themes` + `hadith_hadith_themes` — ⚠ open question

`hadith_themes`: `id`, `slug` UNIQUE, `name` json, `is_published`, timestamps.
Pivot: `PRIMARY (hadith_id, theme_id)` · `INDEX (theme_id, hadith_id)`.

**sunnah.com book pages carry no themes**, so these ship empty and admin-curated. **They may also be
redundant:** the Topics module already links topics to hadiths
(`TopicsServiceProvider:37` maps `TopicItemType::HADITH → Hadith::class`). Resolve before building —
either drop both tables and use Topics, or state why hadith themes are a distinct taxonomy.
**Blocks HR-113 and HR-500.**

### 4.10 Dropped, with reasons

| Dropped | Reason |
|---|---|
| `hadith_keywords` | Tokenised stopwords. Modelling junk is worse than lacking the feature |
| `hadith_isnad` | Out of scope (D2); source is `completeness: partial`, self-declared unverified |
| `hadith_grading_levels` | Caused the 566 junk taxonomy rows. Replaced by the 5-value enum |
| `hadith_companions` | → `hadith_scholars.is_companion` |
| `hadith_narrators` | → `hadith_scholars` |
| `hadith_commentaries`, `hadith_applications`, `hadith_fiqh_rulings`, `hadith_notes` | No source (D11) |
| `hadith_cross_refs`, `hadith_related_verses` | **No extraction source (D12)** — v1 would ship two empty tables and a dead "parallel narrations" page |
| `hadith_sources` | → `sunnah_urn`, `hadith_texts.source_url`, `hadith_import_runs` |
| `hadith_*_translations` (3) | Redundant with spatie translatable json |
| `hadith_sync_logs` | → `hadith_import_runs` |

---

## Phase 0 — Safety, then rewire, then delete

> **Order reversed from v1**, which deleted the module first and rewired after, leaving the app
> un-bootable mid-phase. Nothing is deleted until every consumer is rewired.

### 0.1 Safety gates — before touching anything

- [ ] **HR-000** Commit the current worktree on a dedicated branch. **23 files are untracked**
      (including `app/Domain/Hadith/Models/HadithApplication.php`, `HadithFiqhRuling.php`,
      `HadithRebuildRepository.php`, and all 12 new migrations) — `rm -rf` destroys untracked files
      **irrecoverably**.
      → *verify:* `git status --porcelain` empty; `git log -1` is the checkpoint
- [ ] **HR-001** Create `.env.testing` with `DB_DATABASE=the_last_messenger_test`. Root cause of this
      session's data loss: `--env=testing` silently falls back to `.env`, and `phpunit.xml` scopes
      `DB_DATABASE` to PHPUnit only, never Artisan.
      → *verify:* `php artisan tinker --env=testing --execute="echo DB::connection()->getDatabaseName();"`
- [ ] **HR-002** Add a **fail-closed** guard: destructive commands assert the resolved database name
      is on an explicit allowlist and abort otherwise. "Print it first" is not a control.
      → *verify:* the guard aborts when pointed at `the_last_messenger`

### 0.2 Rewire every consumer — **complete** inventory

v1 listed 13 files. The verified list adds nine more (★):

- [ ] **HR-010** ★ `app/Providers/AppServiceProvider.php:10-12` — imports
      `HadithDashboardStatsProvider`, `HadithExportAdapter`, `HadithNarratorExportAdapter`.
      **Container boot fails without this.**
- [ ] **HR-011** `app/Providers/HadithServiceProvider.php` — rewrite bindings
- [ ] **HR-012** `app/Providers/{Topics,Favorite,Shared}ServiceProvider.php`
- [ ] **HR-013** `app/Domain/Shared/Enums/SearchModule.php:116`, `ExportModule.php:42-43`
- [ ] **HR-014** `app/Domain/Shared/Models/ContentSchedule.php:298`
- [ ] **HR-015** `app/Domain/Shared/Services/AnalyticsService.php` — 9 references
- [ ] **HR-016** ★ `app/Domain/Sahaba/Models/Sahabi.php:439` — raw `hadith_narrators` dependency.
      **A cross-module violation the deptrac baseline is masking**
- [ ] **HR-017** `app/Http/Controllers/Site/HomeController.php` — daily-hadith card
- [ ] **HR-018** `Admin/{ActivityLog,Import,Version}Controller.php`,
      `Admin/Content/ImportStatusController.php`, `app/Jobs/ProcessImport.php`
- [ ] **HR-019** ★ `Console/Commands/IslamicData/{ImportAll,Verify}Command.php`,
      ★ `DedupePeopleCommand.php:46`, ★ `Portal/{ExportPortalData,VerifyPortalData}Command.php`
- [ ] **HR-020** ★ `database/seeders/DatabaseSeeder.php:46`, ★ `TopicSeeder.php:183`
- [ ] **HR-021** ★ `deptrac.baseline.yaml` — remove stale `App\Domain\Hadith\*` entries, else the
      baseline masks new violations
- [ ] **HR-022** ★ Remove/disable old routes **first**: `routes/api.php` (17 hadith routes),
      `routes/web.php:85-95`, `routes/admin/hadith.php`, and the dead block at `routes/admin.php:175-181`
- [ ] **HR-023** `tests/Helpers/TestHelper.php`

**Verify 0.2:** `php artisan about` boots · `route:list` clean · `composer deptrac` passes

### 0.3 Delete — last

- [ ] **HR-030** `app/Domain/Hadith/` · admin+api+site controllers · 6 FormRequests · 6 Resources ·
      31 views · 5 commands + helpers · 16 test files + fixtures
- [ ] **HR-031** Delete **every** hadith migration, including all 12 written on 2026-07-30 (D14).
      Nothing is amended and nothing is kept. Only
      `2026_07_02_090001_rename_hadith_count_on_sahaba.php` survives — it touches the `sahaba` table,
      not a `hadith_*` one
      → *verify:* `ls database/migrations | grep -i hadith` returns only the sahaba rename;
      `grep -rl "Schema::create('hadith_" database/migrations` is empty
- [ ] **HR-032** Delete `storage/app/backups/*.sql`, `storage/app/hadith_reconcile/`,
      `storage/app/hadith_sunnah_parity/`, and the session scratchpad backup (D3)

---

## Phase 1 — Schema + domain module

- [ ] **HR-100** Author **10 fresh migrations** directly from §4 (D14) — one clean set, written once,
      in FK-dependency order. Not a diff against anything that previously existed:

      1. `hadith_collections`   6. `hadith_gradings`
      2. `hadith_books`         7. `hadith_themes` + `hadith_hadith_themes` (one file, two tables)
      3. `hadith_chapters`      8. `hadith_import_runs`
      4. `hadith_scholars`
      5. `hadith_hadiths`       *(+ `hadith_texts` after `hadith_hadiths`)*

      Every table `hadith_*` snake_case per §4.0. Each migration has a working `down()`. MySQL-only
      DDL (`FULLTEXT`, composite FKs) wrapped in `DB::getDriverName() === 'mysql'` guards.
      → *verify:* 10 tables created; no CamelCase identifier anywhere —
      `grep -rnE "HadithTable|BookData|ChapterData|arabicURN|englishURN" database/migrations` empty
- [ ] **HR-101** Apply on the **test** database first, then the dev database.
      → *verify:* 10 tables. **Roll back by explicit migration name, never `--step=N`** — N counts
      files, not tables; that coupling is exactly what broke v1
- [ ] **HR-102** `innodb_ft_min_token_size=2` (default 3 makes 2-char Arabic tokens silently
      unindexable). Document restart + index rebuild **per environment** — dev, test, and production
      must tokenise identically
- [ ] **HR-103** Seed 17 collections: `code`, names, author, capability flags, `reference_template`
- [ ] **HR-104** Define and test the **synthetic book** for `nawawi40`
      (`is_synthetic = true`, `book_number = '0'`), since `book_id` is NOT NULL
      → *verify:* 42 hadiths insert; the book is hidden from public listings
- [ ] **HR-105** Resolve §4.9 (themes vs Topics) — **blocks HR-113, HR-500**

Structure — follow `app/Domain/Seerah/`:

```
app/Domain/Hadith/
├── Enums/        HadithGrade, GradeScope, ImportStatus
├── Models/       HadithCollection, HadithBook, HadithChapter, Hadith, HadithText,
│                 HadithGrading, HadithScholar, HadithTheme, HadithImportRun
├── DTOs/         HadithListItem, HadithDetail, ReferenceSet, SearchCriteria
├── Repositories/ Collection (owns book+chapter reads), Hadith, Scholar, Theme
└── Services/     HadithQueryService, HadithAdminService, ReferenceBuilder,
                  HadithSearchService, ArabicTextNormalizer, HadithVisibility,
                  HadithCacheKeys, HadithDashboardStatsProvider, HadithExportAdapter,
                  HadithBulkHandler
```

Cuts from v1: separate Book and Chapter repository pairs (folded into Collection — only ever read
within a collection context) · the `Support/` namespace (Seerah keeps adapters in `Services/`) ·
the `SearchResult` DTO (return Laravel's paginator).

- [ ] **HR-110** Enums
- [ ] **HR-111** Models: explicit `$fillable` (no `$guarded = []`), `$translatable`, `$casts`. Apply
      `HasAuditLog` + `HasVersions` (D13) — **suppress version generation during bulk import**
- [ ] **HR-112** `final readonly` DTOs; no `array $data` crosses a layer boundary
- [ ] **HR-113** 4 repository pairs. Every list query has explicit `->select([...])`; every eager
      load names columns and the parent select includes the FK
- [ ] **HR-114** `ReferenceBuilder` → the three reference lines
      → *verify:* `bukhari:1` yields exactly the §3.1 strings
- [ ] **HR-115** `ArabicTextNormalizer` — strip tashkeel/tatweel, fold `أ إ آ → ا`, `ى → ي`, `ة → ه`
      → *verify:* normalising the diacritised bukhari:1 body equals its `ar_notashkeel`
- [ ] **HR-116** `HadithVisibility` — the **single** publication gate
      → *verify:* `grep -rn "is_published" app/Domain/Hadith` hits only this class + models
- [ ] **HR-117** `HadithSearchService` — `MATCH(search_body) AGAINST (… IN BOOLEAN MODE)`, query
      normalised first, `per_page` capped and cast to `(int)`
- [ ] **HR-118** Multi-step writes in `DB::transaction()` **inside repositories**
- [ ] **HR-119** Implement the Shared contracts; follow `SeerahCacheKeys` for cache-key shape
- [ ] **HR-120** **Schema-constraint and normaliser tests land here, before any import** — composite
      FK rejection, chapter RESTRICT, Arabic-only insert, multi-claim grading

### 1.1 Lowercasing rule (from `docs/hadith-rebuild-plan.md`)

**Lowercase** — identifiers and enums only: `slug`, `code`, `locale`, `grade`, `scope`, `status`,
`transmission_method`, `reliability`.

**Never lowercase** — `body`, `search_body` (Arabic is caseless; folding protects English proper
nouns), `raw_grade`, scholar and narrator names, biographies, `narrated_by`, source titles and
references.

---

## Phase 2 — Extraction: agent-driven browser → JSON

> **Corrected architecture (D6).** v1's `php artisan hadith:extract` was unimplementable: Artisan
> runs server-side and cannot drive the user's Chrome, and server-side HTTP gets **403** from
> Cloudflare. **I drive the Chrome tools directly**, writing JSON; Artisan validates and imports
> (Phase 3). The two stay separate so a parse fix never re-fetches.

**Volume:** 607 book pages. `/<collection>/<bookNumber>` renders the whole book — `/bukhari/2`
returned 51 hadiths and 43 chapters in one response. Throttled ~1/sec ≈ 10 minutes.

Official API access is gated by request (`sunnah-com/api` →
`.github/ISSUE_TEMPLATE/request-for-api-access.md`). **Worth requesting in parallel** — a key removes
this pipeline entirely and is the courteous path.

### 2.1 DOM map — verified on `/bukhari/2`

| Selector | Count | Yields |
|---|---:|---|
| `.actualHadithContainer` | 51 | **record boundary** |
| `.hadith_narrated` | 51 | → `hadith_texts.narrated_by` |
| `.english_hadith_full` / `.arabic_hadith_full` | 51 | → `body` |
| `.arabic_sanad` | — | → `sanad` |
| `.hadith_reference` | 51 | the three reference lines |
| `.hadith_permalink` | 51 | `/urn/<n>` → `sunnah_urn` |
| `.deprecated_reference` | — | USC-MSA deprecation flag |
| `.chapter`, `.englishchapter`/`.arabicchapter`, `.echapno`/`.achapno`, `.echapintro`/`.achapintro` | 43 | chapter data |
| `.book_page_english_name`/`_arabic_name`/`.book_page_number` | 1 | book data |

`.col1` marks Musnad Ahmad's single-column layout.

- [ ] **HR-200** **Find the grade selector.** v1's DOM map had none, yet grading is the richest part
      of the schema. Inspect a graded collection (tirmidhi/abudawud carry al-Albani gradings) and add
      the selector before extracting anything
      → *verify:* a known-graded hadith yields its `raw_grade` verbatim
- [ ] **HR-201** **Find the English URN**, or drop the column. `sunnah_urn_en` has no selector in v1
      and would ship 100% NULL
- [ ] **HR-202** Build the **source → JSON → column matrix**. Any column with no source is dropped,
      not shipped empty
- [ ] **HR-203** Extract per book to `storage/app/hadith_source/<collection>/book_<n>.json`,
      temp-file-then-rename so a resumed run cannot read a truncated file
- [ ] **HR-204** Write a **manifest** per book: extractor version, source URL, expected vs actual
      hadith/chapter counts, checksum, validation status. Resume skips a book **only** when its
      manifest validates — v1 skipped on file existence alone, which accepts silent partial data
- [ ] **HR-205** Fixtures for every layout: standard (bukhari), single-column (ahmed), the three
      forties, missing-English, chapterless, combined numbers (`1a`)

### 2.2 Extraction rules

- `raw_grade` **verbatim**; normalise only at import.
- Arabic keeps diacritics; `search_body` derived at import.
- Missing Arabic body → **report as an error** (99.9% expected), never skip silently.
- Missing English → **omit the locale row** (§4.5).
- Record the `.deprecated_reference` flag as a boolean, not by re-parsing text.

### 2.3 Per-record JSON shape

```json
{
  "collection": "bukhari",
  "book_number": "2",
  "chapter_number": "1",
  "chapter_title": { "en": "...", "ar": "..." },
  "hadith_number": "8",
  "in_book_number": 1,
  "volume_number": "1",
  "sunnah_urn": 65,
  "sunnah_urn_en": null,
  "reference": {
    "canonical": "Sahih al-Bukhari 8",
    "in_book": "Book 2, Hadith 1",
    "translation": "Vol. 1, Book 2, Hadith 7",
    "translation_deprecated": true
  },
  "texts": [
    { "locale": "ar", "body": "...", "narrated_by": null, "sanad": "..." },
    { "locale": "en", "body": "...", "narrated_by": "Narrated Ibn 'Umar:" }
  ],
  "raw_grades": []
}
```

`texts` is an **array of present locales** — an Arabic-only hadith has one entry. `raw_grades` is an
array, since one hadith can carry several grade strings.

---

## Phase 3 — Import

- [ ] **HR-300** `GradeNormalizer` returns a **list of claims** (`grade`, `scope`, `part_label`), one
      row each. Handles casing, Arabic, transliteration, `(Darussalam)` citations, bracket artifacts,
      scope-qualified and multi-claim strings
      → *verify:* fixture of the 130 observed raw values asserts **exact expected outcomes**; **zero**
      lose `raw_grade`. **No coverage threshold** — a target rewards confident misclassification;
      ambiguous prose must stay `unknown`
- [ ] **HR-301** `authenticated_by` produces **no** grading rows (§3.4 Trap 1)
      → *verify:* no scholar named `Imam al-Bukhari` receives ~9,000 grading rows
- [ ] **HR-302** `albani_grade` → a second grading row, scholar = al-Albani
- [ ] **HR-303** `HadithRecordMapper` — JSON → DTOs, per the HR-202 matrix
- [ ] **HR-304** `HadithImporter` — upsert on `(collection_id, hadith_number)`; **reconcile child
      rows transactionally** (texts, gradings, themes that vanished from a corrected source file must
      be deleted, not orphaned). Plain `json_decode` with a file-size guard — no streaming machinery
      until a book measurably exceeds the memory budget
- [ ] **HR-305** Declare **import-owned vs editor-owned** fields. A re-import must never silently
      overwrite human edits (`is_published`, curated themes, edited text) without an explicit `--reset`
- [ ] **HR-306** Recompute `display_grade` per §4.6 in the same transaction
- [ ] **HR-307** `php artisan hadith:import {--collection=} {--dry-run} {--reset}`
- [ ] **HR-308** **Pilot:** `nawawi40` (42, synthetic book), then `bukhari` (7,277)
- [ ] **HR-309** Remaining 15 collections
- [ ] **HR-310** Cross-check 20 records against the reference corpus; itemise discrepancies
- [ ] **HR-311** Dedupe report — hash computed in-query, detect only, no merging

### 3.1 Per-collection acceptance gate (from `docs/hadith-rebuild-plan.md`)

A collection is done only when **all** hold:

- [ ] Manifest count matches extracted record count
- [ ] Import logged to `hadith_import_runs`
- [ ] Row counts match predictions — **no silent drops**
- [ ] Every unmapped `raw_grade` reported, not swallowed
- [ ] Distinct `grade` values ⊆ the 5-value enum — no taxonomy growth
- [ ] Chrome parity check on sampled book pages; mismatches logged **with URL**
- [ ] `php artisan test --filter=Hadith` passes; `pint` clean

---

## Phase 4 — API + public frontend

```
GET /api/v1/hadith/collections[/{code}]
GET /api/v1/hadith/collections/{code}/books[/{bookNumber}]
GET /api/v1/hadith/collections/{code}/books/{bookNumber}/chapters[/{chapterNumber}]
GET /api/v1/hadith/collections/{code}/books/{bookNumber}/hadiths
GET /api/v1/hadith/collections/{code}/hadiths/{hadithNumber}
GET /api/v1/hadith/hadiths/random · /hadiths/by-urn/{urn} · /search
```

This is **our** API, shaped like sunnah.com's. v1 claimed "base-URL swap compatibility" — unproven;
envelopes, field names, pagination, and error shapes all differ.

- [ ] **HR-400** Controllers: FormRequest, authorize, **one** service call, return a Resource
- [ ] **HR-401** Resources — no per-row relation access
      → *verify:* constant query count at `per_page` 20 and 100
- [ ] **HR-402** `throttle:search` (30/min); `per_page` capped

### 4.1 Routes — **corrected collision analysis**

v1 checked the wrong collision. The real ones: `/hadith/{collection}:{n}` collides with
`/hadith/{collection}` (both single-segment); `/hadith/urn/{urn}` collides with
`/hadith/{collection}/{bookNumber}`; `/hadith/search` can be captured as a collection.

- [ ] **HR-410** Register **static routes first** (`/hadith/search`, `/hadith/urn/{urn}`), then
      constrain every dynamic segment
      → *verify:* request tests for search, urn, collection, book, `1a`, comma-containing numbers
- [ ] **HR-411** `/hadith/urn/{urn}` 404s cleanly for the 35% with no URN

### 4.2 Pages — port the prototypes verbatim

| Prototype | Route |
|---|---|
| `hadith-books-enhanced.html` | `/hadith`, `/hadith/{collection}` |
| `hadith-book-single-enhanced.html` | `/hadith/{collection}/{bookNumber}` |
| `hadith-book-chapter-enhanced.html` | chapter section |
| `hadith-single-enhanced.html` | `/hadith/{collection}:{n}` |
| `hadith-explorer.html` | `/hadith/search` |

`hadith-narration.html` is **deferred** — needs `hadith_cross_refs`, dropped under D12.

- [ ] **HR-420** Port five pages; reuse `components/site/`: `breadcrumb`, `pagination`,
      `empty-state`, `skeleton`, `badge`, `filter-pills`
- [ ] **HR-421** Empty + error states everywhere. **Loading skeletons only where something is
      genuinely async** — server-rendered Blade has no loading state
- [ ] **HR-422** Logical properties only; flipped icons; both themes; `text-base sm:text-sm`
- [ ] **HR-423** Amiri for hadith text, Cairo for UI. Every page falls back to Arabic when a locale
      row is absent
- [ ] **HR-424** `npm run build` — no Vite dev server here; new views silently break without it

---

## Phase 5 — Admin frontend

- [ ] **HR-500** Routes per resource — **actions listed explicitly**, not generated uniformly. v1
      gave all six `reorder`/`toggle-featured`/`toggle-published`; only collections have `featured`,
      only books/chapters have `sort_order`
- [ ] **HR-501** Controllers: FormRequest, authorize, one service call
- [ ] **HR-502** FormRequests with field-level 422s
- [ ] **HR-503** Index views on `data-table`, **server-side paging** — 51k hadiths must never render
      unpaginated
- [ ] **HR-504** Create/edit on `language-tabs` + `language-tab-panel` (5 locales, Arabic first)
- [ ] **HR-505** Show views use `withCount`, never per-row queries
- [ ] **HR-506** Hadith edit: gradings (rows, not the rollup), themes, `is_published`, per-locale
      text. **`display_grade` is not directly editable** (§4.6)
- [ ] **HR-507** `hadith_import_runs` view exposing counts and `unmapped_grades`
- [ ] **HR-508** Optimistic locking via `updated_at` comparison on admin writes (D13)
- [ ] **HR-509** Register nav in `components/admin/sidebar.blade.php`
- [ ] **HR-510** Authorize every action, deny by default. **Admin authorization and validation tests**

---

## Phase 6 — i18n, tests, gates

- [ ] **HR-600** Rewrite `resources/lang/{ar,en,fr,tr,ur}/hadith.php`; all five in parity; Arabic
      default and fallback; admin views Arabic by default
- [ ] **HR-601** Feature tests per API endpoint: 200/201 + 401 + 404 + 422 (+403 where roles apply)
- [ ] **HR-602** Unit tests: `GradeNormalizer` (exact outcomes), `ArabicTextNormalizer`,
      `ReferenceBuilder`, `HadithRecordMapper`
- [ ] **HR-603** FULLTEXT integration test — tests run on MySQL, so `MATCH … AGAINST` is directly
      testable. *(Project memory claiming a sqlite test driver is stale.)*
- [ ] **HR-604** N+1 assertions on list and detail endpoints
- [ ] **HR-605** Route tests per HR-410
- [ ] **HR-606** `composer deptrac` passes with the **baseline cleaned** (HR-021)
- [ ] **HR-607** `pint` clean; `php artisan test` green
- [ ] **HR-608** `EXPLAIN` the five hot paths at ~51k rows; confirm index use. Add
      `(collection_id, …)` composites **only if a plan demands them**. Paste plans into §15
- [ ] **HR-609** Update root `CLAUDE.md` §2 (Hadith → Service + Repository; Quran retains CQRS) and
      the table list; update `WEB_APP_MODULES_ANALYSIS.md`; supersede `docs/hadith-rebuild-plan.md`

---

## 12. Risks

**12.1 Destructive-command discipline.** Root cause of this session's loss: `--env=testing` falls
back to `.env` with no `.env.testing`, and `phpunit.xml` scopes `DB_DATABASE` to PHPUnit only. Fixed
by HR-000/001/002 — committed checkpoint, real `.env.testing`, fail-closed allowlist. **Also: never
`--step=N`** for rollback; N counts files, not tables, which alone would have rolled back a
non-Hadith migration.

**12.2 Grade normalisation is lossy by nature.** 400+ variants cannot map cleanly onto 5 values.
`raw_grade` always survives, `unknown` is a valid terminal state, multi-claim strings become multiple
rows, every unmapped value is reported.

**12.3 Arabic FULLTEXT fails silently.** `innodb_ft_min_token_size=2` and matching `search_body`
(never `body`) are both required; wrong settings return *fewer rows* rather than erroring. Config
drift across dev/test/prod produces different results per environment (HR-102).

**12.4 Extraction brittleness.** Selectors are verified on one page of one collection. HR-205's
six-layout fixtures exist because Musnad Ahmad, the forties, and chapterless collections differ.

**12.5 Cross-module FKs.** `hadith_scholars.sahaba_id` crosses a boundary at the DB level, invisible
to deptrac. `Sahabi.php:439` already reaches into `hadith_narrators` raw — a real violation the
baseline was masking (HR-016, HR-021).

**12.6 Re-import vs editorial work.** Once editors curate, a naive re-import overwrites them (HR-305).

**12.7 Frontend build.** New Blade views need `npm run build`.

---

## 13. Verification

```bash
# Phase 0 — safety first
git status --porcelain                             # empty after HR-000
php artisan tinker --env=testing --execute="echo DB::connection()->getDatabaseName();"
php artisan about && php artisan route:list        # boots, routes clean
composer deptrac                                   # baseline cleaned

# Phase 1 — test DB first, explicit migration names, never --step=N
php artisan migrate --force --env=testing          # 10 tables
docker exec dev-mysql-db mysql -e "SHOW VARIABLES LIKE 'innodb_ft_min_token_size'"   # 2

# Phase 2 — agent-driven Chrome extraction, NOT artisan
#   607 book pages → storage/app/hadith_source/<collection>/book_<n>.json + manifests

# Phase 3
php artisan hadith:import --collection=nawawi40 --dry-run
php artisan hadith:import --collection=nawawi40    # 42, synthetic book
php artisan hadith:import --collection=bukhari     # 7,277; then the remaining 15

# Phases 4-5 — Chrome extension
#   /hadith/bukhari:1 → three reference lines match sunnah.com exactly
#   AR + EN · dark + light · 375 / 768 / 1280
#   /hadith/search, /hadith/urn/65, /hadith/bukhari, /hadith/bukhari/2, /hadith/bukhari:1a
npm run build

# Phase 6
php artisan test && ./vendor/bin/pint --test
```

**Reconciliation**, itemised per collection against §3.2 — no silent losses:
607 book files + manifests · ~51,400 `ar` rows · ~47,600 `en` rows · 17 collections.

---

## 14. Open decisions — need your call

**14.1 D10, full admin CRUD — Codex dissents.** Its argument: editors with create/delete/renumber
rights over collections, books, and chapters can destabilise canonical references, because
`/hadith/bukhari:8` and every external citation depend on that hierarchy matching sunnah.com exactly.
It recommends hierarchy entities be read-only apart from publication and localised presentation
metadata, with full CRUD kept for hadiths, scholars, and themes.

You chose full CRUD explicitly, so it stays as D10 unless you say otherwise. Worth deciding
consciously — far cheaper to restrict now than after an editor has renumbered a book.

**14.2 §4.9 — do `hadith_themes` duplicate the existing Topics module?** `TopicsServiceProvider:37`
already links topics to hadiths. Resolve before HR-113/HR-500.

**14.3 Request official sunnah.com API access?** It would remove the scraping pipeline entirely.

---

## 15. Query plans

*Populate under HR-608.*
