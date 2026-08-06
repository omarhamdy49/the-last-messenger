# Hadith Read API Contract

Read-only. Base `/api/v1/hadith`. Locale via `?locale=ar|en` or `Accept-Language`, defaulting to
`ar`. Envelope follows the project standard: `{ success, data, message, meta }`.

Only published content is served, and a hadith is published only when **it and every ancestor** —
collection, book, chapter — are published (FR-033).

---

## Resource shapes

### `HadithResource`

```json
{
  "reference_key": "1-1",
  "number": "1",
  "in_book_number": "1",
  "collection": { "slug": "bukhari", "title": "صحيح البخاري" },
  "book":       { "book_number": "1", "title": "بدء الوحي" },
  "chapter":    { "chapter_number": "1", "title": "كيف كان بدء الوحي" },
  "text_ar": "…",
  "text_en": "…",
  "narrated_by": "…",
  "grade": "sahih",
  "raw_grade": "Sahih (Al-Albani)",
  "references": {
    "canonical": "Sahih al-Bukhari 1",
    "in_book": "Book 1, Hadith 1",
    "translation": "Vol. 1, Book 1, Hadith 1",
    "translation_deprecated": true
  },
  "narrators": [
    { "slug": "umar-ibn-al-khattab", "name": "عمر بن الخطاب",
      "position": 1, "is_primary": true, "source_name": "عمر بن الخطاب رضي الله عنه" }
  ],
  "url": "/hadith/bukhari:1-1"
}
```

**Absent by decision** — do not add back without reopening the decision:
`urn`, `sunnah_urn`, `sunnah_urn_en`, `source_url`, `source_metadata` (Decision E, G);
`sanad`, `matn`, `translator`, `volume_number` (Owner Decision 2);
`gradings[]` — replaced by the scalar `grade` + `raw_grade` (Decision A).

`text_ar` may be `null` for 38 records — but those are never published, so the API never serves one.
The nullability exists for the admin surface, not the public one.

`references` is **built on read** from `reference_template` and the stored reference columns, never
recomputed from structural numbers. `translation_deprecated` is true only for `bukhari`, `muslim`,
and `malik` — a property of sunnah.com's translation history, hardcoded rather than stored.

### `HadithCollectionResource`

```json
{
  "slug": "bukhari",
  "title": "صحيح البخاري",
  "intro": "…",
  "compiler": { "slug": "muhammad-al-bukhari", "name": "محمد البخاري" },
  "has_books": true,
  "has_chapters": true,
  "books_count": 97,
  "hadiths_count": 7277,
  "is_featured": true
}
```

Counts are **computed with a published constraint** (`withCount`), never read from a stored column
(research → R11). `compiler` is a `muslims` reference — the compiler is a person like any other.

---

## Endpoints

| Method | Path | Notes |
|--------|------|-------|
| `GET` | `/search` | `q` required, min 2 chars. `?collection=`, `?grade=`, `?locale=`, `?page=`. `throttle:search`. |
| `GET` | `/hadiths/random` | One published hadith. Uses `(is_published, id)`. |
| `GET` | `/collections` | Ordered by `(is_published, sort_order, id)`. |
| `GET` | `/collections/{slug}` | |
| `GET` | `/collections/{slug}/books` | |
| `GET` | `/collections/{slug}/books/{bookNumber}` | |
| `GET` | `/collections/{slug}/books/{bookNumber}/chapters` | |
| `GET` | `/collections/{slug}/books/{bookNumber}/hadiths` | Paginated, `per_page` cast to `(int)` and capped. |
| `GET` | `/collections/{slug}/hadiths/{referenceKey}` | **Canonical lookup.** Hits `uq_hadiths_reference`. |

**Deleted**: `GET /hadiths/{urn}` → 404.

Route constraints: `{slug}` is `[a-z0-9_-]+` (widened from `[a-z0-9_]+` for Decision F);
`{bookNumber}` is `[A-Za-z0-9_-]+`; `{referenceKey}` is `[A-Za-z0-9._-]+` — must exclude `/` and `:`
so the web colon form stays parseable.

**Static before dynamic.** `/search` and `/hadiths/random` are declared before `/collections/{slug}`
and `/hadiths/{referenceKey}` so a literal segment can never be read as a key.

---

## Search behaviour

Separate `MATCH(search_ar)` and `MATCH(search_en)` branches, merged by score — never
`MATCH(search_ar, search_en)`, which MySQL rejects unless a composite FULLTEXT index exists with
exactly that column list (research → R5).

The query string is folded with the same Arabic normalisation applied to `search_ar` at write time.
Both sides of the comparison must use the identical folding, or diacritic-insensitive matching
silently fails in one direction only.

Two-character Arabic terms must return results, which requires `innodb_ft_min_token_size = 2` on the
server. This is an **environment prerequisite**, not a migration step — a fresh server with the
default of 3 makes FR-029 fail with no error message.

---

## Errors

| Status | When |
|--------|------|
| 404 | Unknown slug, book number, or reference key; any unpublished ancestor; every deleted route |
| 422 | `q` shorter than 2 chars, unknown `grade`, unsupported `locale` |
| 429 | Search rate limit |

Errors are generic: no stack traces, SQL, or internal paths (constitution VII). A 404 for
"unpublished" is deliberately indistinguishable from a 404 for "does not exist" — the difference
would leak the existence of unpublished content.
