# Research: Hadith Module Rebuild

Findings that drove the design. Everything here was verified against source code, the live site via the
Chrome extension, or measured from the local corpus — not assumed.

## 1. sunnah.com as it actually is

Read from `sunnah-com/api@master` and `sunnah-com/website@master`.

**API** — Flask + `SQLAlchemy.db.reflect()`, 4 models, 14 read-only endpoints, keyed on
`collectionName` **string** rather than an id.

**Schema** — 6 legacy tables, **no foreign keys anywhere**, mixed MyISAM and InnoDB:

| Table | Role | Engine |
|---|---|---|
| `Collections` | 18 rows, PK `collectionID` | MyISAM |
| `BookData` | PK `(collection, englishBookID)` | MyISAM |
| `ChapterData` | PK `(collection, arabicBookID, babID)` | MyISAM |
| `HadithTable` | denormalised bilingual join, PK `arabicURN` | InnoDB |
| `ArabicHadithTable` / `EnglishHadithTable` | per-language source of truth | MyISAM |
| `matchtable` | maps the per-language URNs together | MyISAM |

`HadithTable` holds one row per hadith carrying `arabicURN, arabicText, arabicgrade1, arabicBabName`
**and** `englishURN, englishText, englishgrade1, englishBabName` side by side.

**Live reference block**, confirmed on `https://sunnah.com/bukhari:1`:

```
Reference                       : Sahih al-Bukhari 1
In-book reference               : Book 1, Hadith 1
USC-MSA web (English) reference : Vol. 1, Book 1, Hadith 1
                                  (deprecated numbering scheme)
```

Constructed in `ArabicHadith::populateReferences()` / `EnglishHadith::populateReferences()`, branching on
`Book.status` and substituting `Collections.reference_template`. The deprecation note applies to
bukhari, muslim, and malik only.

**URL scheme** — `/<collection>:<hadithNumber>` canonical · `/<collection>/<bookNumber>` book page ·
`/urn/<n>` permalink · `/narrator/<id>` · `/search?q=` · `/ajax/<lang>/<collection>/<bookID>`

**Arabic text carries shortcodes** — `[matn]`, `[prematn]`, `[postmatn]`, `[commentary]`,
`[narrator id=…]`, `[quran sura=…]`, parsed with `Thunder\Shortcode`. This is how chain/saying separation
and inline narrator links are represented.

**Rijal module** (newer, partially shipped) — `Narrators` with teachers and students as CSV columns and
critic opinions as JSON, plus `narrator_ahadith` and `clusters` (parallel-narration grouping with a
prominence score). Detail pages are live and rich; the `/narrators` index still renders "coming soon".

**Search** is Elasticsearch. **Caching** is Memcached per-entity keys plus Cloudflare edge with a shared
cache tag.

### Decisions taken from this

**Adopt:** the collection → book → chapter → hadith hierarchy · string hadith, book, and chapter numbers ·
a per-collection reference template · capability flags for volumes/books/chapters · chapter introductions
and endings · chain/saying separation · the canonical URL scheme.

**Reject, with reasons:**

| sunnah.com does | Why we don't |
|---|---|
| Parallel per-language columns | Each new language needs 3 more columns in `BookData`, a new table, and a new switch arm. Dead end at 5 locales |
| `decimal(3,1)` book ids, `decimal(6,1)` chapter ids | Encodes "book 35b" in a numeric type |
| Magic ids `-1` = introduction, `-35` = "35b" | `Book::id_number_map` exists solely to undo this |
| Zero foreign keys | No referential integrity at all |
| MyISAM | No transactions, table-level locking |
| `hasvolumes varchar(3)` holding `'yes'` | Should be boolean |
| `Book.status` int meaning verified **and** layout **and** progress | Three concerns in one column |
| Teachers/students as CSV strings | Unqueryable |
| `grade1` sometimes a string, sometimes JSON | Type depends on the row; their own model try/excepts to discover which |

## 2. Extraction feasibility

**Server-side fetching is impossible.** sunnah.com sits behind Cloudflare and returns **403** to
non-browser clients — verified. Extraction must run through the Chrome extension, in a real browser
session. An Artisan command cannot do this; it is a server-side process with no access to the user's
browser.

**Volume is manageable.** `/<collection>/<bookNumber>` renders an entire book. `/bukhari/2` returned
**51 hadiths and 43 chapter headings in a single response**. So the corpus is **607 book pages**, not
51,441 hadith pages — roughly ten minutes at one request per second.

**Official API access is gated by request** (`sunnah-com/api` →
`.github/ISSUE_TEMPLATE/request-for-api-access.md`). A key would remove this pipeline entirely and is the
courteous path; worth filing in parallel with extraction work.

### DOM map, verified on `/bukhari/2`

| Selector | Count | Yields |
|---|---:|---|
| `.actualHadithContainer` | 51 | **the record boundary** |
| `.hadith_narrated` | 51 | narrator preamble |
| `.arabic_hadith_full` / `.english_hadith_full` | 51 | bodies |
| `.arabic_sanad` | — | chain of transmission |
| `.hadith_reference` | 51 | the three reference lines |
| `.hadith_permalink` | 51 | `/urn/<n>` → the external identifier |
| `.deprecated_reference` | — | the deprecation flag |
| `.chapter`, `.englishchapter`/`.arabicchapter`, `.echapno`/`.achapno`, `.echapintro`/`.achapintro` | 43 | chapter data |
| `.book_page_english_name` / `_arabic_name` / `.book_page_number` | 1 each | book data |

`.col1` marks Musnad Ahmad's single-column layout — a different rendering that fixtures must cover.

### The two gating selectors — resolved 2026-07-30 (T045, T046)

Both were verified live through the Chrome extension. **Neither column is dropped.**

**Grade (T045)** — verified on `/tirmidhi/1` and `/abudawud/1`:

```
.actualHadithContainer .gradetable tr        → one row per grade claim
    td.english_grade                         → the English pair
    td.arabic_grade                          → the Arabic pair
```

Each row holds a label cell and a value cell for each language. **Cell order is not stable across
languages:** English is `[label, value]` (`"Grade:"`, `"Sahih (Darussalam)"`), Arabic is
`[value, label]` (`"حسن صحيح (الألباني)"`, `"حكم :"`) because the Arabic table renders RTL in source
order. An extractor that indexes cells positionally silently swaps label and verdict on every Arabic
row. Take the cell that is *not* the label instead.

Measured on `/abudawud/1` (390 records): 369 English grade values, 389 Arabic grade values. The
attributing scholar is embedded in the value in parentheses — `(Al-Albani)`, `(Darussalam)` — so it is
part of `raw_grade`, not a separate field. `.gradetable tr` count per record was 0 or 1 across every
page sampled; the multi-claim strings of §5 are carried *inside one value*, not as extra rows.

**English permalink (T046)** — the documented `.hadith_permalink` carries no `<a href="/urn/…">` on
book pages; it renders Report/Share/Copy controls. Both identifiers are in the report handler:

```
.actualHadithContainer .reportlink[onclick]  → reportHadith(<arabic_urn>, 'h<english_urn>')
```

The element ids on the same record (`h<english_urn>`, `htc<english_urn>`, `t<english_urn>`) confirm the
second argument. Verified that `/urn/660010` and `/urn/700010` both resolve to Jami` at-Tirmidhi 1, so
each is an independently valid permalink. `sunnah_urn_en` therefore **stays in the schema**.

`.sharelink[onclick]` yields the canonical path (`share(this, '/tirmidhi:1')`) — a second, independent
source for `(collection, hadith_number)`.

### Collection codes — the directory names are NOT the codes (verified 2026-07-30)

`/ahmed` returns **404**. `prophet-data-generator`'s directory names are not sunnah.com path segments,
so nothing may key on them:

| production directory | sunnah.com code | | production directory | sunnah.com code |
|---|---|---|---|---|
| `ahmed` | **`ahmad`** | | `mishkat_almasabih` | **`mishkat`** |
| `aladab_almufrad` | **`adab`** | | `riyad_assalihin` | **`riyadussalihin`** |
| `shamail_muhammadiyah` | **`shamail`** | | `bulugh_almaram` | **`bulugh`** |

The remaining eleven match: bukhari, muslim, nasai, abudawud, tirmidhi, ibnmajah, malik, darimi,
nawawi40, qudsi40, shahwaliullah40.

### Live book inventory differs from the reference corpus

Book lists include an `about` link that is **not** a book, and some collections carry a non-numeric
book (`introduction` for darimi/riyadussalihin/mishkat/muslim/ibnmajah, `letter` for abudawud) that
**is** one. Excluding `about`, live counts: bukhari 97, malik 61, muslim 57, adab 57, shamail 57,
nasai 52, tirmidhi 49, abudawud 44, ibnmajah 38, mishkat 29, darimi 24, riyadussalihin 20, bulugh 16,
ahmad 8, and **0 for all three forties** — roughly 609 book pages.

**All three forties are bookless, not only `nawawi40`.** `/nawawi40`, `/qudsi40` and
`/shahwaliullah40` expose no book links at all, yet `/nawawi40/1` returns a single hadith — the path
segment is the hadith number, not a book number. The plan and `data-model.md` assume one synthetic
book for `nawawi40`; **three** are required, one per forty.

## 3. Corpus inventory

`prophet-data-generator/hadith/production/` — **51,441 JSON files, 17 collections, ~320 MB**.
**Reference corpus for cross-checking only; not the import source.**

| Collection | Books | Hadiths | | Collection | Books | Hadiths |
|---|---:|---:|---|---|---:|---:|
| bukhari | 97 | 7,277 | | mishkat_almasabih | 28 | 5,014 |
| malik | 61 | 1,973 | | darimi | 24 | 3,406 |
| muslim | 57 | 7,459 | | riyad_assalihin | 20 | 1,896 |
| aladab_almufrad | 57 | 1,326 | | bulugh_almaram | 16 | 1,767 |
| shamail_muhammadiyah | 56 | 400 | | ahmed | 7 | 1,359 |
| nasai | 52 | 5,768 | | qudsi40 | 1 | 40 |
| tirmidhi | 49 | 4,053 | | shahwaliullah40 | 1 | 40 |
| abudawud | 43 | 5,276 | | **nawawi40** | **0** | 42 |
| ibnmajah | 38 | 4,345 | | **TOTAL** | **607** | **51,441** |

`nawawi40` has **no book structure**, yet the hierarchy requires a book — hence the hidden synthetic
book. sunnah.com serves the three forties as one collection with three book ids.

## 4. Measured field fill rates — 3,000-record random sample, seed 7

**Presence of a key is not presence of data.** Both are reported, because conflating them is how earlier
estimates went wrong:

| Field | key present | populated | Consequence |
|---|---:|---:|---|
| Arabic body | 99.9% | 99.9% | required |
| English body | — | 92.7% | **omit the locale row** when absent |
| `ar_notashkeel` | — | 72.8% | seeds `search_body`; generate the remaining 27% |
| permalink identifier | — | 65.4% | nullable UNIQUE, never identity |
| English permalink identifier | — | 58.2% | nullable UNIQUE |
| `grading.status` | 99.9% | **90.7%** | free text, 130+ variants |
| `grading.authenticated_by` | 99.0% | **40.4%** | **provenance, not a grading** |
| `grading.notes_en` / `notes_ar` | 95.8% | 38.8% | grading notes |
| `grading.agreement` | 89.2% | **11.0%** | free-text prose, not an enum |
| `grading.albani_grade` | 27.8% | **8.9%** | a **second** grading row |
| `grading.verified` | 64.7% | 64.7% | boolean |
| themes | — | 30.8% | no extraction source; Topics module covers this |
| `keywords` | 100% | 33.1% | **junk** — tokenised stopwords |
| `related_verses` | 78% | **1.9%** | dropped: no extraction source |
| `applications` / `scholarly_comments` | 78% | **5.6% / 5.5%** | dropped: no extraction source |

Legacy field variants appear in 0.1–0.4% of files (`status_en`, `scholars`, `chain_notes`,
`parallel_narrations`, `graded_by`, `notes`) — batch drift from different enrichment runs. Handle
explicitly or log and skip; never silently ignore.

## 5. The two grading traps

### Trap 1 — provenance masquerading as scholarship

`grading.authenticated_by` is dominated by `Imam al-Bukhari` (529 of 3,000) and `Imam Muslim` (515) —
i.e. the compiler of the collection the hadith already sits in. That is membership, not an independent
verdict. A previous importer created one grading row per entry and generated **9,379 + 8,863 bogus
rows**. It must generate none.

### Trap 2 — free-text grades

`grading.status` has **130+ distinct values in 3,000 records**, extrapolating to 400+ across the corpus:

- casing and transliteration variants of the same verdict
- the same verdicts written in Arabic
- **scope-qualified** forms distinguishing a verdict on the chain from one on the hadith
- publisher citations embedded in the verdict
- parsing artifacts with stray brackets and periods
- **multiple verdicts in one string**, sometimes numbered
- full prose explaining a mixed judgement
- 9.3% null

The previous importer lowercased each distinct raw string into a `hadith_grading_levels` row, producing
**566 junk taxonomy entries**. The replacement keeps `raw_grade` verbatim, normalises to a five-value
enum, emits one row per distinct claim, and reports anything it cannot interpret rather than inventing a
category.

## 6. Existing code to reuse

| Asset | Path | Use |
|---|---|---|
| Structural reference | `app/Domain/Seerah/` | 36 files, Service + Repository — the shape to copy |
| Cross-module contracts | `app/Domain/Shared/Contracts/` | 7 interfaces a domain module implements |
| Cache key convention | `app/Domain/Seerah/Services/SeerahCacheKeys.php` | `<scope>:<key>` shape |
| Audit and versioning | `HasAuditLog`, `HasVersions`, `ContentVersion`, `AuditLog` | already exist — do **not** add audit columns |
| Admin components | `resources/views/components/admin/` | `data-table` (server-side paging, filters, bulk actions), `language-tabs` + `language-tab-panel`, `form-builder`, `confirm-modal`, `stats-card`, `toast-container` |
| Public components | `resources/views/components/site/` | `breadcrumb`, `pagination`, `empty-state`, `skeleton`, `badge`, `filter-pills` |
| Page designs | `the-last-messenger-ui/hadith-*.html` | 5 prototypes, ported verbatim — no invented layouts |
| Tagging | `TopicsServiceProvider` | already maps hadiths into the Topics taxonomy |

## 7. External consumers of the outgoing module

22 files outside `app/Domain/Hadith/` reference it and must be rewired **before** deletion. The
boot-critical one is `app/Providers/AppServiceProvider.php`, which imports three Hadith services
directly — deleting the module first leaves the container unable to boot.

Nine were missed in the first pass and found only by re-running the search: `AppServiceProvider`,
`Domain/Sahaba/Models/Sahabi.php` (which queries the old narrator table raw — a cross-module violation
the deptrac baseline was masking), both Portal export/verify commands, `DedupePeopleCommand`,
`DatabaseSeeder`, `TopicSeeder`, `IslamicData/ImportAllCommand`, and `deptrac.baseline.yaml` itself.

## 8. Prior art in this repo

`docs/hadith-rebuild-plan.md` predates this work and contains the `authenticated_by` trap and a
lowercasing rule worth keeping: lowercase identifiers and enums only (`slug`, `code`, `locale`, `grade`,
`scope`), never text bodies, raw grades, or personal names — case folding on names would damage English
proper nouns while doing nothing for Arabic.

Its own percentages conflate key presence with populated values, which is corrected in §4 above.

`docs/hadith-refactor-review-2026-07-30.md` records an adversarial review of the first version of this
plan: 15 blocking defects, all resolved here. The four most consequential were an incomplete purge
inventory, a rollback instruction that counted files rather than tables, a FULLTEXT index that could not
serve its own query, and an extraction command that could not have run at all.
