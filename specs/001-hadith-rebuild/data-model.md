# Data Model: Hadith Module Rebuild

8 tables, down from 22. All InnoDB `utf8mb4_unicode_ci`. Translatable columns are `json` via
spatie/laravel-translatable with Arabic fallback.

## Naming rule

Every table is `hadith_*`, snake_case, plural. Every column is snake_case. sunnah.com's CamelCase
identifiers must never appear in migrations, models, or queries.

| sunnah.com | ours |
|---|---|
| `Collections` | `hadith_collections` |
| `BookData` | `hadith_books` |
| `ChapterData` | `hadith_chapters` |
| `HadithTable` | `hadith_hadiths` |
| `ArabicHadithTable` / `EnglishHadithTable` | `hadith_texts` (one row per locale) |
| `matchtable` | *(none — `sunnah_urn` columns instead)* |
| `Narrators` | `hadith_scholars` |

Columns follow the same rule: `arabicURN` → `sunnah_urn`, `englishURN` → `sunnah_urn_en`,
`ourHadithNumber` → `in_book_number`, `babID` → `chapter_id`, `grade1` → `raw_grade`,
`hasbooks` → `has_books`.

The single deliberate exception is the `sunnah_urn` / `sunnah_urn_en` **column names** — the prefix marks
the value as an external system's key rather than ours.

## Relationships

```
hadith_collections
   └─< hadith_books
          └─< hadith_chapters
                 └─< hadith_hadiths
                        ├─< hadith_texts      (one row per locale that HAS text)
                        └─< hadith_gradings   >── hadith_scholars
hadith_scholars >── sahaba (nullable, cross-module, DB-level only)
hadith_import_runs
```

Tagging uses the **existing Topics module** — `TopicsServiceProvider` already maps
`TopicItemType::HADITH → Hadith::class`. No hadith-specific theme tables.

---

## 1. `hadith_collections`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `code` | varchar(50) UNIQUE | `bukhari` — the URL and API path segment |
| `name` | json | translatable |
| `author` | json NULL | translatable |
| `short_intro` | json NULL | translatable |
| `about` | json NULL | translatable, long |
| `has_books` | boolean default 1 | capability flag |
| `has_chapters` | boolean default 1 | |
| `has_volumes` | boolean default 0 | |
| `reference_template` | varchar(255) NULL | e.g. `Sahih al-Bukhari {hadithNumber}` |
| `is_published` | boolean default 0 | |
| `featured` | boolean default 0 | |
| `display_order` | unsigned int default 0 | |
| timestamps | | |

**No `total_hadiths` counter** — 17 rows; use `withCount`. Counters drift once admin CRUD exists.
**No composite index** — at 17 rows a scan beats an index (Constitution II: no speculative indexes).

Seeded with all 17 collections.

## 2. `hadith_books`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `collection_id` | FK → collections, RESTRICT | |
| `book_number` | varchar(20) | string: carries `35b`, `introduction` |
| `sort_order` | unsigned int default 0 | real ordering |
| `title` | json | translatable |
| `intro` | json NULL | translatable |
| `first_hadith_number` | varchar(50) NULL | |
| `last_hadith_number` | varchar(50) NULL | |
| `is_synthetic` | boolean default 0 | hidden placeholder for bookless collections |
| `is_published` | boolean default 0 | |
| timestamps | | |

`UNIQUE (collection_id, book_number)` · `INDEX (collection_id, sort_order)` ·
**`UNIQUE (id, collection_id)`** — parent key required by the composite FK on `hadith_hadiths`

No `hadith_count` counter — use `withCount`.

## 3. `hadith_chapters`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `book_id` | FK → books, **RESTRICT** | |
| `chapter_number` | varchar(20) | |
| `sort_order` | unsigned int default 0 | |
| `title` | json | translatable |
| `intro` | json NULL | translatable — preamble before the chapter's hadiths |
| `ending` | json NULL | translatable — closing passage |
| `is_published` | boolean default 0 | |
| timestamps | | |

`UNIQUE (book_id, chapter_number)` · `INDEX (book_id, sort_order)` ·
**`UNIQUE (id, book_id)`** — parent key for the composite FK

Deletion is **RESTRICT**, not `nullOnDelete`. Orphaning every hadith in a chapter because an editor
deleted it is not an acceptable outcome (FR-010).

## 4. `hadith_hadiths` — identity and structure only, no text

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `collection_id` | FK → collections, RESTRICT | denormalised: the canonical lookup and every collection-scoped list must not join through books |
| `book_id` | FK → books, RESTRICT, **NOT NULL** | synthetic book for bookless collections |
| `chapter_id` | FK → chapters, **RESTRICT**, NULL | NULL where `has_chapters = 0` |
| `hadith_number` | varchar(50) | canonical, e.g. `7533`, `1a` |
| `in_book_number` | unsigned int NULL | drives the in-book reference line |
| `volume_number` | varchar(20) NULL | for the translation reference line |
| `sunnah_urn` | unsigned int UNIQUE NULL | ~65% populated |
| `sunnah_urn_en` | unsigned int UNIQUE NULL | ~58% populated |
| `display_grade` | enum(sahih,hasan,daif,mawdu) NULL | **derived, never edited**; NULL when no single Hadith-level verdict is available |
| `is_published` | boolean default 0 | |
| timestamps | | |

### Composite foreign keys — the integrity requirement (FR-009)

```sql
FOREIGN KEY (book_id, collection_id) REFERENCES hadith_books    (id, collection_id),
FOREIGN KEY (chapter_id, book_id)    REFERENCES hadith_chapters (id, book_id)
```

Without these, a hadith can name collection A, a book belonging to collection B, and a chapter belonging
to a third book. Requires the `UNIQUE (id, collection_id)` and `UNIQUE (id, book_id)` parent keys above.

### Keys and indexes

`UNIQUE (collection_id, hadith_number)` — the canonical identity
`INDEX (book_id, is_published, in_book_number)` — public book reading order
`INDEX (chapter_id, is_published, in_book_number)` — public chapter reading order
`INDEX (is_published, id)` — random selection and pagination

**No `text_hash` column.** Duplicate detection is a one-time report; the hash is computed in the report
query rather than stored and indexed across 51k rows.

### `display_grade` derivation (FR-005)

1. Consider only `hadith_gradings` rows with `scope = 'hadith'`.
2. Exactly one distinct `grade` → that grade.
3. Zero rows, conflicting grades, an `unknown` normalisation, or any row with a non-null `part_label` → `NULL`.

Recomputed inside the same transaction as any grading write, owned by `HadithAdminService`. Never
directly editable in the admin.

## 5. `hadith_texts` — one row per locale **that has text**

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `hadith_id` | FK → hadiths, CASCADE | |
| `locale` | enum(ar,en,fr,tr,ur) | |
| `body` | longtext **NOT NULL** | as displayed, diacritics intact |
| `search_body` | longtext **NOT NULL** | the only FULLTEXT-indexed column |
| `narrated_by` | varchar(500) NULL | locale-specific narrator preamble |
| `sanad` | text NULL | chain of transmission |
| `matn` | longtext NULL | the saying itself |
| `translator` | varchar(255) NULL | |
| `source_url` | varchar(500) NULL | |
| timestamps | | |

`UNIQUE (hadith_id, locale)` · **`FULLTEXT ft_hadith_texts_search (search_body)`**

### Why one indexed column, not a composite

MySQL requires the `MATCH()` column list to match a FULLTEXT index exactly, so
`FULLTEXT (body, search_body)` cannot serve `MATCH(search_body)`. Indexing both would additionally index
the same Arabic text twice, inflating term frequencies and distorting relevance ranking.

`search_body` is populated per locale by the importer: **normalised Arabic** for `ar` (diacritics and
tatweel stripped; alef, hamza, ya, and ta-marbuta variants folded), **the original body** for
Latin-script locales.

### Missing text is normal

English is absent for ~7% of records. `body` is `NOT NULL`, so an Arabic-only hadith has exactly **one**
row — a text row is omitted rather than written empty (FR-015). Every read path falls back to Arabic.

## 6. `hadith_gradings` — one row per **claim** (FR-002, FR-003, FR-004)

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `hadith_id` | FK → hadiths, CASCADE | |
| `scholar_id` | FK → scholars, RESTRICT, NULL | |
| `grade` | enum(sahih,hasan,daif,mawdu,unknown) | normalised verdict |
| `scope` | enum(hadith,isnad) default hadith | a verdict on the chain is a different claim |
| `part_label` | varchar(100) NULL | which part a multi-verdict string addresses |
| `authority_rank` | tinyint unsigned default 100 | lower wins |
| `raw_grade` | **text** | verbatim source string, never discarded |
| `locale` | enum(ar,en,fr,tr,ur) NULL | language the raw string was written in |
| `notes` | text NULL | |
| `source_reference` | varchar(255) NULL | |
| timestamps | | |

`INDEX (hadith_id)` · `INDEX (grade, hadith_id)`

`raw_grade` is `text`, not `varchar`: the prose cases run long enough to truncate.

### One source string may assert several claims

`Sanad Da'if wal-Hadith Hasan` → two rows: `(daif, isnad)` and `(hasan, hadith)`.
`1: Hasan<br>2: Da'if` → two rows with `part_label` `"1"` and `"2"`.
Both share one `raw_grade`. A normaliser returning a single verdict silently discards one claim per such
record.

### Provenance is not a grading (FR-006)

The source's `authenticated_by` field names the compiler of the collection the hadith already belongs
to — in a 3,000-record sample, `Imam al-Bukhari` 529 times and `Imam Muslim` 515 times. It is collection
membership, not an independent verdict. Creating a grading row per entry previously produced
**9,379 + 8,863 bogus rows**. It must produce none.

## 7. `hadith_scholars` — narrators and graders in one table

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `slug` | varchar(255) UNIQUE | |
| `name` | json | translatable |
| `full_name` | json NULL | translatable |
| `kunya` | json NULL | translatable |
| `biography` | json NULL | translatable |
| `birth_year_hijri` | int NULL | |
| `death_year_hijri` | int NULL | |
| `generation` | varchar(50) NULL | tabaqa |
| `reliability` | varchar(100) NULL | |
| `is_companion` | boolean default 0 | |
| `sahaba_id` | FK → `sahaba`, nullOnDelete, UNIQUE NULL | cross-module, DB-level only |
| timestamps | | |

One table because both roles describe the same entity, and al-Bukhari is both a narrator and a grader —
two tables would duplicate his name, patronymic, and death year.

**No index on `is_companion`** (low cardinality) or `death_year_hijri` — speculative until a query needs
them.

`sahaba_id` is a **database** foreign key only. No PHP in `Domain/Hadith` may import `Domain/Sahaba`;
companion resolution goes through a `Shared` contract.

## 8. `hadith_import_runs`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `collection_code` | varchar(50) | |
| `source_path` | varchar(500) NULL | |
| `status` | enum(pending,running,completed,failed) default pending | |
| `counts` | json NULL | per-table row counts, expected vs actual |
| `unmapped_grades` | json NULL | every raw string the normaliser could not interpret (FR-007) |
| `error_message` | text NULL | |
| `started_at` | timestamp NULL | |
| `completed_at` | timestamp NULL | |
| timestamps | | |

`INDEX (collection_code, status)`

`unmapped_grades` is surfaced in the admin so the mapping rules can be improved without re-reading 51k
records.

---

## Dropped from the previous schema

| Dropped | Reason |
|---|---|
| `hadith_keywords` | Source values are tokenised Arabic stopwords. Modelling junk is worse than lacking the feature |
| `hadith_isnad` | Out of scope; source chain data is self-declared partial and unverified |
| `hadith_grading_levels` | Produced 566 junk taxonomy rows. Replaced by a 5-value enum plus `raw_grade` |
| `hadith_companions` | → `hadith_scholars.is_companion` |
| `hadith_narrators` | → `hadith_scholars` |
| `hadith_commentaries`, `hadith_applications`, `hadith_fiqh_rulings`, `hadith_notes` | No data source |
| `hadith_cross_refs`, `hadith_related_verses` | No extraction source — would ship empty (FR-024) |
| `hadith_themes`, `hadith_hadith_themes` | Duplicates the existing Topics module |
| `hadith_sources` | → `sunnah_urn`, `hadith_texts.source_url`, `hadith_import_runs` |
| `hadith_source_translations`, `hadith_theme_translations`, `hadith_chapter_translations` | Redundant with spatie translatable JSON columns |
| `hadith_sync_logs` | → `hadith_import_runs` |

## Environment requirement

`innodb_ft_min_token_size = 2` (default is 3, which silently makes two-character Arabic tokens
unindexable — FR-012). Requires a MySQL restart **and** a FULLTEXT index rebuild, applied consistently
across dev, test, and production or search returns different results per environment. The `ngram` parser
must **not** be used — it is CJK-oriented and wrong for Arabic.
