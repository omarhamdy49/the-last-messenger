# Contract: Admin Locations Surface

**Feature**: `003-locations-module` | **Date**: 2026-08-18

There is **no public API surface** in this feature — the spec's Out of Scope section excludes public
place routes. Everything below is admin, behind the admin guard, server-rendered Blade.

Route file: `routes/admin/shared.php` (new), registered alongside `routes/admin/seerah.php`.
Prefix: `/su/portal/v1/admin`. Route-name prefix: `admin.locations.`.
Template to copy: `App\Http\Controllers\Admin\Seerah\SeerahEventTypeController` — the closest
existing shape (DB-driven taxonomy with translations, toggle-active and bulk-delete).

---

## Routes

| Method | Path | Name | Purpose |
|---|---|---|---|
| GET | `/locations` | `admin.locations.index` | Tree list, filterable |
| GET | `/locations/search` | `admin.locations.search` | JSON autocomplete for the form selects |
| POST | `/locations/bulk-delete` | `admin.locations.bulk-delete` | Guarded by reference count |
| POST | `/locations/{id}/toggle-active` | `admin.locations.toggle-active` | |
| GET | `/locations/create` | `admin.locations.create` | |
| POST | `/locations` | `admin.locations.store` | |
| GET | `/locations/{id}` | `admin.locations.show` | |
| GET | `/locations/{id}/edit` | `admin.locations.edit` | |
| PUT/PATCH | `/locations/{id}` | `admin.locations.update` | |
| DELETE | `/locations/{id}` | `admin.locations.destroy` | Refuses while referenced |

**Route order matters** (CLAUDE.md): `/search`, `/bulk-delete` and `/create` are registered
**before** `Route::resource`, or they resolve as `{id}`.

---

## `GET /locations` — index

Query parameters, all optional. Every filter name is checked against an allowlist before use;
none is interpolated into query structure (constitution VII).

| Param | Type | Allowed values |
|---|---|---|
| `q` | string | Free text, matched against translated `name` and `aliases` |
| `type` | string | A `LocationType` value |
| `parent_id` | int | |
| `is_active` | `0`\|`1` | |
| `has_coordinates` | `0`\|`1` | |
| `sort` | string | `display_order`, `name`, `type`, `created_at` — **allowlist only** |
| `direction` | string | `asc`, `desc` |
| `per_page` | int | Cast to `(int)`, capped at 100 |

Renders the tree with each row showing its localized name, type, parent, whether it has coordinates,
and its reference count from `LocationUsageRegistry`.

---

## `GET /locations/search` — autocomplete

Feeds the three form selects. Returns JSON.

**Request**: `?q=<term>&limit=<n>` — `limit` cast to `(int)`, capped at 50.

**Response `200`**:

```json
{
  "success": true,
  "data": [
    {
      "id": 12,
      "slug": "al-madinah",
      "name": "المدينة المنورة",
      "type": "city",
      "parent_name": "الحجاز",
      "has_coordinates": true
    }
  ]
}
```

Matching covers translated names **and** registered aliases (FR-021), so typing `Medina` finds
المدينة المنورة. Only `is_active` places are returned.

---

## `POST /locations` — store

Validated by `StoreLocationRequest`. Unknown fields are rejected, not ignored.

| Field | Rules |
|---|---|
| `slug` | `required, string, max:120, unique:locations,slug, regex:/^[a-z0-9-]+$/` |
| `type` | `required, Rule::enum(LocationType::class)` |
| `parent_id` | `nullable, integer, exists:locations,id` |
| `latitude` | `nullable, numeric, between:-90,90` |
| `longitude` | `nullable, numeric, between:-180,180` |
| `aliases` | `nullable, array, max:50`; each `string, max:255`, distinct |
| `is_active` | `boolean` |
| `display_order` | `integer, min:0` |
| `translations.ar.name` | `required, string, max:255` — Arabic is mandatory |
| `translations.{en,fr,tr,ur}.name` | `nullable, string, max:255` |
| `translations.*.description` | `nullable, string, max:5000` |

Rules enforced beyond validation, in the service:

- `parent_id` must not create a cycle, and must not equal the record's own id.
- An alias must not already belong to a different location — rejected with a message naming the
  conflicting place.

**Responses**: `302` to `admin.locations.index` on success · `422` on validation failure ·
`401` unauthenticated · `403` unauthorized.

---

## `PUT /locations/{id}` — update

Same rules as store, except `slug` is `unique:locations,slug,{id}`. Same cycle and alias-conflict
checks, plus the self-parent case.

**Responses**: `302` · `404` unknown id · `422` · `401` · `403`.

**Note**: send a real `PUT`. Method spoofing via `_method` in a JSON body does not work in this
application (CLAUDE.md) — the request stays `POST` and 405s.

---

## `DELETE /locations/{id}` — destroy

Guarded. `LocationService::delete()` asks `LocationUsageRegistry` for the reference count first.

**Response `422`** when referenced — the count is actionable, so it is named:

```json
{
  "success": false,
  "message": "لا يمكن حذف هذا المكان لأنه مستخدم في محتوى قائم.",
  "errors": {
    "location": ["مرتبط بـ 31 حدثًا و 743 شخصية."]
  }
}
```

**Responses**: `302` on success · `422` while referenced · `404` · `401` · `403`.

Controllers content-negotiate with `$request->expectsJson()` — an HTML form must not receive raw
JSON (CLAUDE.md).

---

## Changes to existing admin screens

| Screen | Change |
|---|---|
| `admin/seerah-events/edit.blade.php` (~line 198) and `create` | Add a place select beside the existing text input. Relabel that input **وصف المكان**. `StoreSeerahEventRequest` / `UpdateSeerahEventRequest` gain `location_id` → `nullable, integer, exists:locations,id`. |
| `admin/sahaba/_form.blade.php` | Birth place text input becomes a searchable select writing `birth_place_id`. The existing `birth_place` value is shown read-only beneath it until it is dropped in a later change. |
| Admin surah form | Add a revelation place select writing `revelation_location_id`. The مكية/مدنية field is **not touched**. |
| `admin.seerah-events.index` | New `?unmapped=1` filter — `WHERE location_id IS NULL AND location <> ''`. Allowlisted like every other filter. |
| `admin.sahaba.index` | Same filter over `birth_place_id` / `birth_place`. |

---

## Console contract

```
php artisan locations:backfill [--dry-run]
```

Exit `0` on success, non-zero if the vocabulary is missing (seeder not run) or a chunk fails.

Output is itemised per content type — constitution X forbids a summarised discrepancy:

```
Seerah events      examined 279   resolved 187   no-match 73   ambiguous 9   already-assigned 10
Companions        examined 6677  resolved 6598  no-match 60   ambiguous 19  already-assigned 0
Surahs            examined 114   resolved 114   no-match 0    ambiguous 0   already-assigned 0
```

Every row of every table must appear in exactly one column; the command fails loudly if the
columns do not sum to `examined`.

`--dry-run` prints the identical report and writes nothing.

---

## Localisation

`resources/lang/{ar,en,fr,tr,ur}/locations.php` — every key present in **all five** files
(constitution V). Arabic is authored first and is the fallback. Admin views use logical properties
only (`ms-`/`me-`/`ps-`/`pe-`/`start-`/`end-`); physical `left-`/`right-` are forbidden for layout.
Every screen is verified in Arabic RTL, not only English.

`npm run build` is required after these Blade changes — there is no Vite dev server in this setup,
and new views silently break without it.
