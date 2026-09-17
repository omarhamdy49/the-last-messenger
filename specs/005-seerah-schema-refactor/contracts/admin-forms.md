# Contract: Admin form payloads

Route names are unchanged (`admin.seerah-events.*`, `admin.seerah-event-types.*`,
`admin.seerah-milestones.*`); only the payloads change. Every FormRequest rejects
fields not listed here (Constitution VII). `PUT` is sent as a real method or a
form-encoded `_method`, never `_method` inside JSON.

## Event — `POST admin.seerah-events.store` / `PUT admin.seerah-events.update`

| Field | Rule |
|---|---|
| `slug` | required on store, string, max 255, unique `seerah_events.slug` |
| `event_type` | required, exists `seerah_event_types.id` where `is_active` |
| `sm_id` | nullable, exists `seerah_milestones.id` |
| `order` | **required**, integer ≥ 0 |
| `thumb` / `thumb_id` | nullable image upload ≤ 5 MB / nullable exists `media.id` |
| `cover` / `cover_id` | same |
| `remove_thumb`, `remove_cover` | nullable boolean |
| `hijri_year` | nullable integer −100…100 |
| `hijri_month` | nullable integer 1…12 |
| `hijri_day` | nullable integer 1…30 |
| `gregorian_date` | nullable date |
| `location_id` | nullable, exists `locations.id` |
| `status` | required, `Rule::enum(SeerahEventStatus)` |
| `era` | nullable, `Rule::enum(EventEra)` |
| `statistics` | nullable array max 12; `.*.label`, `.*.value` string max 120 |
| `title.{locale}` | `ar` required, string max 500 |
| `description.{locale}`, `content.{locale}`, `dispute_note.{locale}` | nullable string (rich text sanitised) |
| `is_ready.{locale}` | nullable boolean |
| `metadata.{locale}` | nullable array max 20; `.*.key` string max 255, `.*.value` string max 1000 |
| `sources.{locale}` | nullable array max 20; `.*.title`, `.*.author`, `.*.reference` string max 255 |
| `gallery` | nullable array max 24 of `media.id` (ordered) |
| `gallery_captions.{media_id}.{locale}` | nullable string max 255 |
| `media_items` | nullable array of distinct `media.id` |

Absent `metadata` / `sources` / `gallery` / `media_items` = leave stored value
untouched. Present-but-empty = clear.

Responses: HTML form → 302 to index with flash; `expectsJson()` → JSON envelope.
422 on validation, 403 without permission, 404 on unknown id.

**Removed fields (now rejected)**: `hijri_date`, `gregorian_year`,
`seerah_event_type_id`, `seerah_milestone_id`, `display_order`, `importance`,
`phase`, `is_pivotal`, `is_featured`, `latitude`, `longitude`, `location`,
`related_article_id`, `featured_image`, `featured_image_id`, `gallery_ids`,
`existing_gallery`, `lessons`, `timeline_items`, `participants`.

`admin.seerah-events.articles` (article picker endpoint) is removed.

## Milestone — store / update

`slug` (unique), `order` (required int), `metadata.icon` / `metadata.color` /
`metadata.gradient` (nullable string, allowlisted by the existing pickers),
`hijri_from`, `hijri_to`, `gregorian_from`, `gregorian_to` (nullable int, `to ≥ from`),
`is_active` bool, `title.{locale}` (`ar` required), `subtitle.{locale}`,
`description.{locale}`, `is_ready.{locale}`.
Removed: `order_index`, `icon`, `color`, `gradient`, `hijri_label_from/to`,
`hijri_year_from/to`, `gregorian_year_from/to`.

## Event type — store / update

`slug` (unique), `metadata.icon|color|gradient`, `locale.ar` (required, max 255),
`locale.{en,tr,fr,ur}` (nullable, max 255), `is_active`.
Removed: `icon`, `color`, `gradient`, `display_order`, `name.{locale}`.
Delete while in use → refused (unchanged).
