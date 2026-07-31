# Hadith API

Ten read-only, unauthenticated endpoints over the 17-collection hadith corpus.

**Base URL:** `/api/v1/hadith`

> **Not compatible with sunnah.com's API.** The endpoint layout is shaped after
> theirs so the concepts map cleanly, but the envelope, field names, pagination
> and error shapes are this project's own. A sunnah.com client will not work
> against this API unchanged, and no attempt is made to keep it working.

---

## Conventions

### Success envelope

```json
{
  "success": true,
  "data": {},
  "message": "…",
  "meta": { "current_page": 1, "per_page": 20, "total": 0 }
}
```

`meta` is present on paginated endpoints only (§6 and §10).

### Error envelope

```json
{ "success": false, "message": "…", "errors": { "field": ["…"] } }
```

### Rules that apply everywhere

| Rule | Behaviour |
|---|---|
| Collections | Addressed by `code` (`bukhari`), never by numeric id |
| Books and chapters | Addressed by their **string** number (`2`, `35b`, `introduction`) |
| Hadith numbers | May be non-numeric (`1a`), or book-scoped (`11.3`) for Malik and Darimi |
| `per_page` | Cast to int and **clamped to 100** — an oversized value narrows, it does not error |
| Locale | From `Accept-Language`, validated against `ar, en, fr, tr, ur`, defaulting to `ar` |
| Missing translation | Falls back to Arabic, which every record has |
| Unpublished records | Return **404**, never an empty 200 |
| `X-Request-Id` | Present on every response |

**There is no `401`.** Every endpoint is an unauthenticated read, so there is no
credential to omit. `plan.md` records this deviation from Constitution VI; the
401 requirement is met by the admin routes.

---

## 1. `GET /collections`

Published collections, `featured` first then `display_order`.

```json
{
  "success": true,
  "data": [
    {
      "code": "bukhari",
      "name": "صحيح البخاري",
      "author": "محمد بن إسماعيل البخاري",
      "has_books": true,
      "has_chapters": true,
      "has_volumes": true,
      "total_hadiths": 7277,
      "featured": true
    }
  ]
}
```

`total_hadiths` is computed, never a stored counter. `about` is omitted here.

## 2. `GET /collections/{code}`

As above plus `about`.

**404** — unknown or unpublished `code`.

## 3. `GET /collections/{code}/books`

Books in `sort_order`. **Synthetic books are excluded** — the three "forty"
collections have no books of their own and carry a hidden placeholder purely to
satisfy the hierarchy.

**200** — `book_number`, `title`, `first_hadith_number`, `last_hadith_number`,
`hadith_count`.

## 4. `GET /collections/{code}/books/{bookNumber}`

As above plus `intro`. **404** — unknown collection or book.

## 5. `GET /collections/{code}/books/{bookNumber}/chapters`

Chapters in `sort_order`: `chapter_number`, `title`, `intro`, `ending`.

`chapter_number` is **omitted** where the source gives none — six Bukhari
chapters are Quranic-verse headings with no number.

**200 with an empty `data`** where the collection has `has_chapters = false`.
That is not a 404: the book exists, it simply has no chapters.

## 6. `GET /collections/{code}/books/{bookNumber}/hadiths`

Paginated, ordered by `in_book_number`. Query count is constant with respect to
`per_page`.

## 7. `GET /collections/{code}/hadiths/{hadithNumber}`

The canonical lookup.

**A hadith number does not uniquely identify a hadith.** Jami` at-Tirmidhi lists
52 numbers that each cover two genuinely different hadiths — 1322 is both
"Go and judge between the people" and "The judges are three". This endpoint
returns the **first in reading order**, matching what sunnah.com's own
`/tirmidhi:1322` shows. Use §8 to address a specific one.

## 8. `GET /hadiths/{urn}` — permalink

Resolves an external sunnah.com identifier against **either** the Arabic or the
English urn.

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
    { "locale": "ar", "body": "…", "sanad": "…" },
    { "locale": "en", "body": "…", "narrated_by": "Narrated 'Umar bin Al-Khattab:" }
  ],
  "gradings": [
    { "grade": "sahih", "scope": "hadith", "part_label": null, "raw_grade": "Sahih (Al-Albani)", "scholar": "Al-Albani" }
  ]
}
```

`texts` contains **only locales that have text** — an Arabic-only hadith has one
entry, never an entry with an empty body.

`gradings` may hold several entries for one hadith, including two derived from a
single source string: `"Sanad Da'if wal-Hadith Hasan"` asserts one verdict about
the chain and another about the text. `raw_grade` is always the verbatim source
string.

`reference.translation_deprecated` is true only for bukhari, muslim and malik,
matching sunnah.com's own note.

**404** — no hadith carries that urn. About 35% of records have none, so this is
an ordinary outcome, not an error condition.

## 9. `GET /hadiths/random`

One random published hadith, in §8's shape. Uses the `(is_published, id)` index
rather than `ORDER BY RAND()`.

## 10. `GET /search`

| Param | Type | Notes |
|---|---|---|
| `q` | string, **required**, 2–200 chars | Normalised before matching |
| `collection` | string, optional | Collection `code`; must exist |
| `grade` | enum, optional | `sahih` \| `hasan` \| `daif` \| `mawdu` \| `unknown` |
| `locale` | enum, optional | Restricts which text rows are searched |
| `page`, `per_page` | int, optional | `per_page` capped at 100 |

Rate limited to **30 requests/minute** (`throttle:search`).

Results carry the §8 hadith shape plus `score`.

- **422** — `q` missing, shorter than 2 characters, longer than 200, or a filter
  that does not resolve.
- **429** — rate limit exceeded.

### Search behaviour

The query is folded through the **same normaliser that produced the index**, so a
diacritised query and its plain form return identical result sets: `الأَعْمَال`
and `الاعمال` match the same records, and `نوى` finds text stored as `نوي`.

Every term is required — adding words narrows the result set.

Two-character Arabic terms work, which requires `innodb_ft_min_token_size = 2` on
the server. See `specs/001-hadith-rebuild/quickstart.md` §1.

Boolean-mode operators typed by a caller (`+ - " ( ) ~ *`) are neutralised rather
than passed through, so an unbalanced quote is an empty result, not a 500.

---

## Grades

`display_grade` is **derived**, never edited:

1. Only `hadith`-scope gradings count; a verdict on the chain says nothing about
   the text.
2. Exactly one distinct grade → that grade.
3. Zero rows, conflicting grades, or any part-labelled claim → `unknown`.

`unknown` is common and correct. Sahih al-Bukhari carries **no** per-hadith
grading at all — the collection is graded by inclusion — so all 7,277 of its
records report `unknown` while Tirmidhi reports a real verdict for 96% of its.

## Corpus

17 collections · 612 books (3 synthetic, hidden) · 15,239 chapters ·
51,775 hadiths · 99,991 text rows · 30,508 gradings.
