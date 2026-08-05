# API Contracts: Hadith (read-only)

Base: `/api/v1/hadith`. All endpoints are public and read-only. **Ten endpoints** (§1–§10).

Shaped after sunnah.com's endpoint layout so its concepts map cleanly, but this is **our** API — the
envelope, field names, pagination, and error shapes differ, so drop-in client compatibility is not
claimed.

## Conventions

**Success envelope** (project standard):

```json
{ "success": true, "data": {}, "message": null, "meta": { "current_page": 1, "per_page": 20, "total": 0 } }
```

**Error envelope:**

```json
{ "success": false, "message": "…", "errors": { "field": ["…"] } }
```

- `per_page` is cast to `(int)` and **capped at 100**; larger values clamp rather than error.
- Collections are addressed by `code` (`bukhari`), never by numeric id.
- Books and chapters are addressed by their **string** number (`2`, `35b`, `introduction`).
- Locale comes from `Accept-Language`, validated against an allowlist, defaulting to `ar`.
- Text falls back to Arabic when the requested locale has no row.
- Unpublished records are invisible: they return 404, never an empty 200.
- Every response carries `X-Request-Id`.
- `422` uses field-level messages; `404` is returned for unknown or unpublished resources.

---

## 1. `GET /collections`

Published collections, ordered by `featured` then `display_order`.

**200** — `data[]`: `code`, `name`, `author`, `short_intro`, `has_books`, `has_chapters`,
`has_volumes`, `total_hadiths` (computed), `featured`.

## 2. `GET /collections/{code}`

**200** — the above plus `about`.
**404** — unknown or unpublished `code`.

## 3. `GET /collections/{code}/books`

Books in `sort_order`. **Synthetic books are excluded.**

**200** — `data[]`: `book_number`, `title`, `first_hadith_number`, `last_hadith_number`,
`hadith_count` (computed).
**404** — unknown collection.

## 4. `GET /collections/{code}/books/{bookNumber}`

**200** — the above plus `intro`.
**404** — unknown collection or book.

## 5. `GET /collections/{code}/books/{bookNumber}/chapters`

Chapters in `sort_order`.

**200** — `data[]`: `chapter_number`, `title`, `intro`, `ending`.
**404** — unknown collection or book; **200 with empty `data`** where the collection has
`has_chapters = false`.

## 6. `GET /collections/{code}/books/{bookNumber}/hadiths`

Paginated, ordered by `in_book_number`.

**200** — `data[]` per §8's hadith shape.
**404** — unknown collection or book.

Query count must be constant with respect to `per_page`.

## 7. `GET /collections/{code}/hadiths/{hadithNumber}`

The canonical lookup, matching `UNIQUE (collection_id, hadith_number)`.

**200** — §8's hadith shape.
**404** — unknown collection, or no such number in it.

`hadithNumber` may be non-numeric (`1a`).

## 8. `GET /hadiths/{urn}` — permalink lookup

Resolves an external `sunnah_urn`.

**200** — hadith shape:

```json
{
  "collection": "bukhari",
  "book_number": "1",
  "chapter_number": "1",
  "hadith_number": "1",
  "in_book_number": 1,
  "display_grade": "sahih",
  "reference": {
    "canonical": "Sahih al-Bukhari 1",
    "in_book": "Book 1, Hadith 1",
    "translation": "Vol. 1, Book 1, Hadith 1",
    "translation_deprecated": true
  },
  "texts": [
    { "locale": "ar", "body": "…", "narrated_by": null, "sanad": "…" },
    { "locale": "en", "body": "…", "narrated_by": "Narrated 'Umar bin Al-Khattab:" }
  ],
  "gradings": [
    { "grade": "sahih", "scope": "hadith", "part_label": null, "raw_grade": "Sahih", "scholar": null }
  ]
}
```

`texts` contains **only locales that have text** — an Arabic-only hadith has one entry.
`gradings` may contain several entries for one hadith, including two derived from a single source string.
`display_grade` is `null` when the source provides no single, unambiguous Hadith-level verdict.

**404** — no hadith carries that `urn`. Around 35% of records have none, so this is an ordinary outcome,
not an error condition.

## 9. `GET /hadiths/random`

One random published hadith.

**200** — §8's hadith shape.

Must use the `(is_published, id)` index rather than `ORDER BY RAND()` over the full table.

## 10. `GET /search`

| Param | Type | Notes |
|---|---|---|
| `q` | string, required, 2–200 chars | normalised before matching |
| `collection` | string, optional | collection `code` |
| `grade` | enum, optional | `sahih`\|`hasan`\|`daif`\|`mawdu` |
| `locale` | enum, optional | restricts which text rows are searched |
| `page`, `per_page` | int, optional | `per_page` capped at 100 |

Rate limited to **30 requests/minute** (`throttle:search`).

**200** — `data[]`: hadith shape plus `snippet` and `score`. Empty `data` with a populated `meta` when
nothing matches.
**422** — `q` missing, too short, or too long.
**429** — rate limit exceeded.

### Search behaviour requirements

- Matches `MATCH(search_body) AGAINST (… IN BOOLEAN MODE)` — the `MATCH()` column list must equal the
  FULLTEXT index exactly.
- The query is normalised through the same Arabic normaliser as the indexed text, so a diacritised query
  and its undiacritised form return **identical** result sets.
- Two-character Arabic terms must return results, which requires `innodb_ft_min_token_size = 2`.

---

## Test matrix

Every endpoint needs: **200** happy path · **404** unknown resource · **404** unpublished resource ·
**422** invalid parameters where any exist · constant query count at `per_page` 20 and 100.
`/search` additionally needs **429**.

Endpoints are public, so there is no credential to omit and no `401` to assert. Constitution VI mandates
a 401 case per endpoint; that deviation is **recorded in `plan.md` under Complexity Tracking** rather than
waived here, and the 401 requirement is met by the admin routes tested in US4 (T143). If authentication is
ever added to these endpoints, the 401 case becomes mandatory and that row must be removed.
