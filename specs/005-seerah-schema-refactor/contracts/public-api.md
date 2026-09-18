# Contract: Public API `v1.seerah.*` (breaking change, same version)

Auth unchanged: Sanctum bearer + `api:access` + `throttle:api`. Envelope unchanged.

## Routes

| Route | Change |
|---|---|
| `v1.seerah.index`, `.search`, `.show`, `.by-type`, `.by-hijri-year` | kept, new resource shape |
| `v1.seerah.by-gregorian-year` | kept; filters `gregorian_date` by year **range** |
| `v1.seerah.map` | kept; events whose `place` has coordinates |
| `v1.seerah.featured` | **removed** (404) |

`index` filters: `type` (slug), `milestone` (slug), `era`. `status` is never
client-settable: every public read returns only events that are published AND have
a ready translation in the request locale. Removed query params: `importance`,
`pivotal`, `featured`. `per_page` is an integer 1–100; anything else is 422
(unchanged from before the refactor).

## `SeerahEventResource`

```json
{
  "id": 1,
  "slug": "battle-of-badr",
  "title": "…", "description": "…",
  "content": "… (show only)",
  "dispute_note": "… | null (show only)",
  "metadata": [{"key": "…", "value": "…"}],
  "sources": [{"title": "…", "author": "…", "reference": "…"}],
  "hijri_year": 2, "hijri_month": 9, "hijri_day": 17,
  "formatted_hijri_date": "17 رمضان 2 هـ",
  "gregorian_date": "0624-03-13", "formatted_gregorian_date": "…",
  "era": "medina", "era_label": "…",
  "status": "published", "status_label": "…",
  "order": 12,
  "event_type": {"id": 3, "slug": "battle", "name": "غزوة", "icon": "swords", "color": "red", "gradient": "…"},
  "milestone": {"id": 6, "slug": "…", "title": "…", "order": 5},
  "location": {"id": 9, "slug": "badr", "name": "بدر", "latitude": 23.78, "longitude": 38.79},
  "has_location": true,
  "thumb_url": "https://…", "cover_url": "https://…",
  "gallery": [{"image": "https://…", "caption": "…", "order": 0}],
  "media": [{"id": 5, "type": "video", "title": "…", "url": "https://…", "thumb_url": "https://…"}],
  "statistics": [{"label": "…", "value": "…"}],
  "created_at": "…", "updated_at": "…"
}
```

`gallery`, `media`, `metadata`, `sources`, `content`, `dispute_note`, `seo`
(`{title, description}` for the request locale), `external_links` (`[{title, url}]`)
and `links` (`{verse|hadith|muslim|article|episode|tag|category: [{id, title, subtitle, url}]}`,
public items only) appear on `show` only; list endpoints never load them (no N+1, no unbounded payload).

**Removed fields**: `hijri_date`, `gregorian_year`, `event_type_label`,
`event_type_icon`, `event_type_color`, `event_type_gradient` (now nested),
`importance`, `importance_label`, `importance_weight`, top-level `latitude` /
`longitude`, `featured_image`, `views_count`, `display_order`, `is_featured`,
`article`, `participants`.

## Tests required (Constitution VI)

Per kept endpoint: 200 (+ shape assertion), 401 without token, 404 unknown
slug/type, 422 invalid params. `featured` → 404. Empty dataset → 200 with `data: []`.
