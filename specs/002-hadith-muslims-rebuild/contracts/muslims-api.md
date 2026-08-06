# Muslim and Genealogy API Contract

Base `/api/v1/muslims`. Replaces the entire `/api/v1/sahaba` surface and the
`/hadith/narrators/{slug}` web route. One person, one profile, many roles (FR-001, FR-002).

---

## Resource shapes

### `MuslimResource`

```json
{
  "slug": "abu-hurayrah",
  "name": "أبو هريرة",
  "kunya": "أبو هريرة",
  "thumb": "/storage/muslims/abu-hurayrah.jpg",
  "gender": "male",
  "birth_place": "اليمن",
  "birth_year_hijri": -21,
  "death_year_hijri": 59,
  "generation": "صحابي",
  "roles": [
    { "slug": "sahabi",   "name": "صحابي",  "is_featured": true },
    { "slug": "narrator", "name": "راوي",   "is_featured": false }
  ],
  "bio": "…",
  "narrations_count": 5374
}
```

`birth_year_hijri` is **signed** — pre-Hijra years are negative, and this is the only correct
representation for companions born before the Hijra.

`thumb` is a **single scalar path**. There is no `gallery`, no `media[]`, and no conversion set
(Owner Decision 4).

**Absent by decision**: `is_sahabi`, `is_companion`, `person_type` — all collapsed into `roles[]`;
`battles[]`, `timeline[]`, `categories[]` — retired (Owner Decision 6); `tribe`, `status` — no
surviving column.

### `MuslimTreeResource`

```json
{
  "root": { "slug": "abu-hurayrah", "name": "أبو هريرة" },
  "ancestors": [
    { "slug": "…", "name": "…", "relation": "father", "depth": 1 }
  ],
  "descendants": [
    { "slug": "…", "name": "…", "relation": "child",
      "depth": 1, "tree_display_order": 0 }
  ],
  "relations": [
    { "slug": "…", "name": "…", "type": "spouse", "notes": null }
  ],
  "depth_reached": 4,
  "depth_capped": false
}
```

`depth_capped` is reported rather than silently truncating (constitution X). A capped tree that
looked complete would be indistinguishable from a real one.

`relations[]` is read from `muslim_relationships` with `muslim_id = ? OR related_muslim_id = ?`,
since each relationship is stored once. Where the row is matched from the far side, the reading layer
supplies the reverse label — an `uncle` row read from the other end renders as nephew/niece.

---

## Endpoints

| Method | Path | Notes |
|--------|------|-------|
| `GET` | `/roles` | Available roles, for directory navigation |
| `GET` | `/muslims` | `?role=`, `?featured=`, `?gender=`, `?page=`. Uses `(role_id, is_featured, sort_order, muslim_id)`. |
| `GET` | `/muslims/search` | `q` min 2 chars, FULLTEXT over `search_name_ar`/`search_name_en` |
| `GET` | `/muslims/{slug}` | Unified profile |
| `GET` | `/muslims/{slug}/hadiths` | Hadiths narrated. Uses `(narrator_id, is_primary, hadith_id)`. Paginated. |
| `GET` | `/muslims/{slug}/tree` | `?depth=` capped server-side regardless of what is requested |

Public endpoints serve **published people only**. `?gender=` ships only once
`(gender, is_published, id)` exists and `EXPLAIN` shows it used — not before (constitution II).

---

## Admin picker

`GET /su/portal/v1/admin/muslims/picker?q=…&exclude={id}`

The one endpoint that deliberately **includes unpublished people** (FR-046). An unpublished person is
still a valid ancestor, narrator, or compiler; publication governs whether their *profile* renders,
not whether they may be referenced.

- Searches `search_name_ar` and `search_name_en`.
- `exclude` removes the record being edited — a person cannot be their own parent or relation.
- Returns `{ slug, name_ar, name_en, is_published }` only. `is_published` is returned so the picker
  can visually mark unpublished entries; an editor should know they are linking to a hidden profile.
- Requires admin authorization (constitution VII) — this is the only surface exposing unpublished
  people, so it is the one that must not leak.

---

## Write rules the API enforces

| Rule | Response | Enforced where |
|------|----------|----------------|
| Person needs at least one name | 422 | `chk_muslims_has_name` + FormRequest |
| Nobody is their own parent | 422 | FormRequest (`exclude`) + service |
| No ancestor cycle, at any depth | 422 | **domain service ancestor walk** — no constraint can do this |
| Nobody is their own sibling | 422 | `chk_muslim_relationships_not_self` |
| Deleting a parent | 409 | `RESTRICT` on `father_id`/`mother_id` |
| Duplicate relationship | 422 | `uq_muslim_relationships` + pair-order normalisation in the repository |

Two rules here are **not** enforceable by the database and are therefore the ones most likely to be
missed:

1. **Cycle prevention at depth.** `A → B → C → A` passes every constraint. The service walks
   ancestors before writing `father_id`/`mother_id` and rejects if the target appears. The CTE depth
   cap is the backstop, not the check.
2. **Symmetric duplicate.** `(A, B, sibling)` and `(B, A, sibling)` are distinct rows to MySQL. The
   repository normalises pair order on write so the UNIQUE index can actually catch it.

---

## Errors

| Status | When |
|--------|------|
| 404 | Unknown slug; unpublished person on any public endpoint |
| 409 | Deleting a person who is another person's parent |
| 422 | Validation failures above; `q` under 2 chars |
| 401/403 | Admin picker without admin authorization |
