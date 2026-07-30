# Extraction Matrix: source selector → JSON field → database column

Every column in the rebuilt schema must appear here with a **verified** selector. A column with no
verified source is removed from `data-model.md` and never migrated (FR-024).

Verified live through the Chrome extension on 2026-07-30 against `/bukhari/1`, `/bukhari/2`,
`/tirmidhi/1`, `/abudawud/1`, `/nawawi40/1` and the 17 collection index pages.

Record boundary: **`.actualHadithContainer`** — one element per hadith.

---

## 1. Book page → `hadith_books`

Source: `GET /<code>/<bookNumber>`

| Selector | JSON field | Column |
|---|---|---|
| `.book_page_number` | `book.number` | `book_number` |
| `.book_page_english_name` | `book.title.en` | `title` (`en`) |
| `.book_page_arabic_name` | `book.title.ar` | `title` (`ar`) |
| index-page link order | `book.sort_order` | `sort_order` |
| — | — | `intro` · **no source on the book page**; stays NULL |
| first/last record's `in_book_number` | `book.first_hadith_number` / `last_hadith_number` | same |

`about` is a metadata page, not a book — exclude it. `introduction` and `letter` **are** books.

## 2. Chapter blocks → `hadith_chapters`

| Selector | JSON field | Column |
|---|---|---|
| `.chapter` | record boundary | — |
| `.echapno` / `.achapno` | `chapter.number` | `chapter_number` |
| `.englishchapter` / `.arabicchapter` | `chapter.title.{en,ar}` | `title` |
| `.echapintro` / `.achapintro` | `chapter.intro.{en,ar}` | `intro` |
| document order | `chapter.sort_order` | `sort_order` |

`ending` has **no verified selector**. It stays NULL until one is found; it is not dropped because
`data-model.md` §3 declares it and a NULL translatable column costs nothing.

## 3. Record identity → `hadith_hadiths`

| Selector | JSON field | Column |
|---|---|---|
| `.sharelink[onclick]` → `share(this, '/<code>:<number>')` | `hadith_number` | `hadith_number` |
| `.reportlink[onclick]` → `reportHadith(<n>, 'h…')` arg 1 | `sunnah_urn` | `sunnah_urn` |
| `.reportlink[onclick]` → arg 2, minus the `h` prefix | `sunnah_urn_en` | `sunnah_urn_en` |
| `.hadith_reference` row `In-book reference` | `in_book_number` | `in_book_number` |
| `.hadith_reference` row 3 `Vol. N, …` | `volume_number` | `volume_number` |
| enclosing `.chapter` block | `chapter_id` | `chapter_id` |
| page URL | `collection_id`, `book_id` | same |
| derived from `hadith_gradings` | — | `display_grade` (never extracted) |

The element ids `h<urn_en>` / `htc<urn_en>` / `t<urn_en>` corroborate arg 2.

## 4. Text → `hadith_texts` (one row per locale **that has text**)

| Selector | JSON field | Column |
|---|---|---|
| `.arabic_hadith_full` | `texts.ar.body` | `body` |
| `.english_hadith_full` | `texts.en.body` | `body` |
| `.hadith_narrated` | `texts.<l>.narrated_by` | `narrated_by` |
| `.arabic_sanad` | `texts.ar.sanad` | `sanad` |
| Arabic body after the `[matn]` shortcode | `texts.ar.matn` | `matn` |
| page URL | `texts.<l>.source_url` | `source_url` |
| `ArabicTextNormalizer(body)` for `ar`, `body` for Latin locales | — | `search_body` (derived) |

`translator` has **no selector on the book page** — stays NULL. An empty body writes **no row**.

## 5. Grades → `hadith_gradings`

| Selector | JSON field | Column |
|---|---|---|
| `.gradetable tr` | one entry per row | — |
| `td.english_grade` non-label cell | `gradings[].raw_grade` (`locale: en`) | `raw_grade` |
| `td.arabic_grade` non-label cell | `gradings[].raw_grade` (`locale: ar`) | `raw_grade` |
| `GradeNormalizer(raw_grade)` | — | `grade`, `scope`, `part_label` (derived) |
| parenthetical inside the value, e.g. `(Al-Albani)` | `gradings[].attributed_to` | resolves `scholar_id` |

**Cell order differs by language.** English rows are `[label, value]`; Arabic rows are `[value, label]`
because the Arabic table renders RTL in source order. Select the cell that is not the label — never by
index. Indexing positionally writes `"حكم :"` into `raw_grade` for every Arabic row.

`authority_rank`, `notes`, `source_reference` have **no selector** — defaults / NULL.

## 6. Reference lines → `ReferenceBuilder` (not stored)

| Selector | Yields |
|---|---|
| `.hadith_reference tr` rows 1–3 | canonical · in-book · translation |
| `.deprecated_reference` | `translation_deprecated` flag |

Row labels observed: `Reference`, `In-book reference`, then either `USC-MSA web (English) reference`
(bukhari, muslim, malik — carries `.deprecated_reference`) or `English translation` (tirmidhi and
others). The three lines are rebuilt from `reference_template` + numbers, not stored verbatim.

---

## Columns with no verified source

Kept as NULL-able because `data-model.md` declares them and they cost nothing:
`hadith_books.intro` · `hadith_chapters.ending` · `hadith_texts.translator` ·
`hadith_gradings.notes` / `source_reference` · every `hadith_scholars` biography field.

Nothing in the 8-table schema is created **solely** on an unverified source, so no column is dropped
by this matrix.

## Deviations from plan.md this matrix forces

1. **Three synthetic books, not one.** `nawawi40`, `qudsi40` and `shahwaliullah40` all expose zero
   books. T171 provisions one; it needs three.
2. **Collection codes are not the production directory names.** `/ahmed` 404s — the code is `ahmad`.
   Also `adab`, `shamail`, `mishkat`, `riyadussalihin`, `bulugh`. Nothing may key on directory names.
3. **The forties are extracted per hadith, not per book.** `/<forty>/<n>` returns a single record, so
   their extraction loop is bounded by hadith count (40, 40, 42), not by a book list.
