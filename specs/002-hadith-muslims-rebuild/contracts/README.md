# Endpoint Replacement Matrix

The plan requires this to be endpoint-by-endpoint: *"capabilities are preserved" is not executable.*
Every route that exists today appears below with an explicit verdict. Nothing is left unmentioned,
because an unmentioned route is one that silently 500s after the rebuild.

**Verdicts**: `→` replaced · `✂` deleted (returns 404) · `≡` unchanged in shape

---

## Hadith API — `routes/api.php:60-89` (ten endpoints)

| # | Today | After | Verdict |
|---|-------|-------|---------|
| 1 | `GET /api/v1/hadith/search` | `GET /api/v1/hadith/search` | `≡` shape kept; locale narrows to `ar`/`en`; keeps `throttle:search` |
| 2 | `GET /api/v1/hadith/hadiths/random` | `GET /api/v1/hadith/hadiths/random` | `≡` |
| 3 | `GET /api/v1/hadith/hadiths/{urn}` | — | `✂` **deleted** (Decision G). `sunnah_urn` is an external identifier removed by Decision E. Route, action, resource field, tests, and Blade `route()` callers all go. |
| 4 | `GET /api/v1/hadith/collections` | `GET /api/v1/hadith/collections` | `≡` |
| 5 | `GET /api/v1/hadith/collections/{code}` | `GET /api/v1/hadith/collections/{slug}` | `→` regex widens `[a-z0-9_]+` → `[a-z0-9_-]+` |
| 6 | `…/{code}/books` | `…/{slug}/books` | `→` same widening |
| 7 | `…/{code}/books/{bookNumber}` | `…/{slug}/books/{bookNumber}` | `→` `bookNumber` regex unchanged (`35b`, `introduction`, `letter` are real) |
| 8 | `…/{code}/books/{bookNumber}/chapters` | `…/{slug}/books/{bookNumber}/chapters` | `→` |
| 9 | `…/{code}/books/{bookNumber}/hadiths` | `…/{slug}/books/{bookNumber}/hadiths` | `→` |
| 10 | `…/{code}/hadiths/{hadithNumber}` | `…/{slug}/hadiths/{referenceKey}` | `→` **identity change.** Lookup moves from the displayed number to `reference_key` (Decision C). This is the breaking change readers feel. |

**Route ordering must be preserved.** Static segments are declared before dynamic ones so `random`
can never be read as a urn and `search` never as a collection key. Deleting #3 removes the urn
hazard but not the `search`/`{slug}` one — keep the ordering.

---

## Hadith web — `routes/web.php:70-91`

| Today | After | Verdict |
|-------|-------|---------|
| `GET /hadith` | `GET /hadith` | `≡` hub |
| `GET /hadith/search` | `GET /hadith/search` | `≡` keeps `throttle:search` |
| `GET /hadith/urn/{urn}` | — | `✂` **deleted** → 404 |
| `GET /hadith/narrators/{slug}` | `GET /muslims/{slug}` | `→` narrator profiles become Muslim profiles. A narrator is a `muslims` row with the `narrator` role, so a dedicated narrator route is a second identity for the same person — exactly what this rebuild removes. |
| `GET /hadith/{collection}:{number}` | `GET /hadith/{collectionSlug}:{referenceKey}` | `→` see the collision note below |
| `GET /hadith/{collection}` | `GET /hadith/{collectionSlug}` | `→` regex widens |
| `GET /hadith/{collection}/{bookNumber}` | `GET /hadith/{collectionSlug}/{bookNumber}` | `→` regex widens |

### ⚠ Routing collision — keep the colon separator

The plan states the public URL as `{collectionSlug}/{referenceKey}`. **A slash cannot be used here.**
`/hadith/{collectionSlug}/{bookNumber}` already occupies that shape, so `/hadith/bukhari/12` would be
ambiguous between book 12 and reference key 12 — and both patterns match the same regex class.

The current schema already solved this with a **colon**: `/hadith/{collection}:{number}`. Keep it:

```
/hadith/bukhari:1-1      → hadith with reference_key "1-1" in collection "bukhari"
/hadith/bukhari/1        → book 1 of collection "bukhari"
```

`reference_key`'s regex must therefore exclude `/` and `:`. `[A-Za-z0-9._-]+` (today's `number`
pattern) is sufficient and already proven against real source values.

---

## Sahaba API — `routes/api.php:123-135` (nine endpoints, all retired or replaced)

Per Owner Decision 6, battles, timelines, and categories are retired. These routes are **deleted with
their actions, resources, and tests** — not left pointing at absent data.

| Today | After | Verdict |
|-------|-------|---------|
| `GET /api/v1/sahaba` | `GET /api/v1/muslims?role=sahabi` | `→` role-filtered directory |
| `GET /api/v1/sahaba/featured` | `GET /api/v1/muslims?role=sahabi&featured=1` | `→` `muslims_roles.is_featured` |
| `GET /api/v1/sahaba/early-muslims` | — | `✂` **deleted.** Derived from a retired category. Restorable later as `?role=early-convert` **only if** that category is seeded as a role. |
| `GET /api/v1/sahaba/top-narrators` | `GET /api/v1/muslims?role=narrator&sort=narrations` | `→` ordering by narration count, computed not stored |
| `GET /api/v1/sahaba/search` | `GET /api/v1/muslims/search` | `→` one person search for all roles |
| `GET /api/v1/sahaba/status/{status}` | — | `✂` **deleted** unless the status values are seeded as roles |
| `GET /api/v1/sahaba/tribe/{tribe}` | — | `✂` **deleted.** No tribe column survives; `generation` is the only lineage-adjacent field kept. |
| `GET /api/v1/sahaba/battle/{battle}` | — | `✂` **deleted** — battles retired outright |
| `GET /api/v1/sahaba/{slug}` | `GET /api/v1/muslims/{slug}` | `→` unified profile |

**Three of these nine have no replacement at all** (`early-muslims`, `status`, `tribe`) plus `battle`
— four deletions. That is the concrete cost of Owner Decision 6, stated here rather than discovered
during implementation.

---

## New endpoints

| Endpoint | Purpose | Requirement |
|----------|---------|-------------|
| `GET /api/v1/muslims` | Role/featured/gender/publication-filtered directory | FR-004 |
| `GET /api/v1/muslims/search` | Bilingual person search over `search_name_*` | FR-004 |
| `GET /api/v1/muslims/{slug}` | Unified profile: roles, bio, narrations | FR-001, FR-002 |
| `GET /api/v1/muslims/{slug}/hadiths` | Hadiths this person narrated | User Story 4 |
| `GET /api/v1/muslims/{slug}/tree` | Family tree rooted here, depth-capped | FR-010 |
| `GET /api/v1/roles` | Available roles for directory navigation | FR-002 |
| `GET /su/portal/v1/admin/muslims/picker` | Admin person picker — **includes unpublished** | FR-046 |

---

## Cross-cutting

- **Locale negotiation narrows from five to two** (`ar`/`en`) at
  `app/Http/Controllers/Api/HadithController.php:32-42`. UI strings keep all five locales; only
  content narrows (plan → Complexity Tracking #2).
- **Publication gating** applies to the hadith **and every ancestor** — collection, book, chapter —
  consistently across detail, browse, random, search, topics, and favourites (FR-033). This is the
  single easiest thing to implement inconsistently, so it is asserted on every read path, not once.
- **Deleted routes assert 404 in tests**, not merely "no longer registered" — a route that silently
  falls through to a catch-all is a different bug than one that 404s.
- Every endpoint ships feature tests for success + 404 + 422, and 401/403 where auth applies
  (constitution VI).
