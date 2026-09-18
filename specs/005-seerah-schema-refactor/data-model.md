# Data Model: Seerah Module & Schema Refactor

Seven tables. Column names follow the owner's list; typos normalised
(`subtitle`, `gregorian_to`, `seerah_events_media`). All tables InnoDB / utf8mb4.

**Deviation from the owner's list (one)**: `seerah_event_types`,
`seerah_event_translations`, `seerah_events_gallery` and `seerah_events_media`
carry `created_at` / `updated_at` although the list omits them for types and
event translations — the project convention is "always include timestamps", and
the article tables being mirrored have them. Remove on request.

## seerah_milestones

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| slug | string, unique | |
| order | int, default 0 | was `order_index` |
| metadata | json, nullable | `{icon, color, gradient}` — was three columns |
| hijri_from / hijri_to | smallint, nullable | years; negative = before Hijrah |
| gregorian_from / gregorian_to | smallint, nullable | years |
| is_active | bool, default true | |
| timestamps | | |

Index: `(is_active, order)`. Dropped: `hijri_label_from/to`.
Model: `SeerahMilestone` — `metadata` cast `array`; accessors `icon()`, `color()`,
`gradient()` read the JSON so views change one call, not their logic;
`getTranslationForeignKey(): 'sm_id'`; `scopeOrdered` = `order`, then `hijri_from`.

## seerah_milestone_translations

`id`, `sm_id` FK → milestones (cascade), `locale` (5), `title` (500), `subtitle`
(500), `description` (text), `is_ready` bool, timestamps. Unique `(sm_id, locale)`.

## seerah_event_types

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| slug | string, unique | |
| metadata | json, nullable | `{icon, color, gradient}` |
| locale | json | `{ar, en, tr, fr, ur}` → display name; `ar` required |
| is_active | bool, default true | |
| timestamps | | see deviation note |

Dropped: `display_order`, table `seerah_event_type_translations`.
Model: `SeerahEventType` — casts `metadata`, `locale` → `array`;
`name(?string $locale = null): string` with `ar` fallback. No translation trait.
Delete rule: refused while any event references it (`SeerahEventTypeInUseException`, unchanged).

## seerah_events

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| slug | string, unique | |
| event_type | FK → seerah_event_types, restrict | was `seerah_event_type_id` |
| sm_id | FK → seerah_milestones, nullable, null on delete | was `seerah_milestone_id` |
| order | int, default 0 | was `display_order`; **always posted explicitly** by the form |
| thumb_url | string, nullable | public-disk path |
| cover_url | string, nullable | public-disk path |
| hijri_year | smallint, nullable | |
| hijri_month | tinyint, nullable | 1–12 |
| hijri_day | tinyint, nullable | 1–30 |
| gregorian_date | date, nullable | |
| location_id | FK → locations, nullable, null on delete | added by the existing 003 migration |
| status | string(20), default `draft` | `SeerahEventStatus` enum in code |
| era | string(20), nullable | `EventEra` enum in code |
| statistics | json, nullable | list of `{label, value}`, max 12 |
| timestamps | | |

Indexes: `(sm_id, status, order)`, `(status, era)`, `idx_seerah_chronological`
(existing file). Stable public ordering: `order`, `hijri_year`, `hijri_month`,
`hijri_day`, `id`.

Removed: `hijri_date`, `gregorian_year`, `importance`, `phase`, `is_pivotal`,
`is_featured`, `views_count`, `related_article_id`, `location`, `latitude`,
`longitude`, `featured_image`, `gallery`.

Relations: `type()`, `milestone()`, `place()` (Shared `Location`), `translations()`,
`gallery()` (ordered), `mediaLinks()`.
Validation: `slug` unique; `event_type` exists + active; `sm_id` exists;
`location_id` exists; `status`/`era` `Rule::enum`; month/day ranges; `order` required integer.

State: `draft → published → draft`. Public visibility is unchanged —
`SeerahPublicVisibility` (`published` + a ready translation).

## seerah_event_translations

`id`, `event_id` FK → events (cascade), `locale` (5), `title` (500), `description`
(text), `content` (longtext), `dispute_note` (text, nullable), `is_ready` bool,
`metadata` json nullable, `sources` json nullable, timestamps.
Unique `(event_id, locale)`; FULLTEXT `(title, description)` (MySQL guard).

- `metadata`: ordered list of `{key, value}`, max 20, both strings ≤ 255 / ≤ 1000.
- `sources`: ordered list of `{title, author, reference}`, max 20 (unchanged shape).
- `seo_metadata`: `{title ≤70, description ≤160}` for that locale — same shape as `article_locale`.
  Public `<title>`/meta description fall back to the event title/description.
- `external_links`: ordered list of `{title, url}` (http/https only), max 20.
- Removed: `lessons`.
- **Save rule**: a repeatable section that was not submitted (`null`) leaves the
  stored value alone; an explicitly submitted empty list clears it. Same rule the
  article gallery uses; it closes the historic "empty inputs blank the sources" trap.

## seerah_events_gallery *(mirror of `articles_gallery`)*

`id`, `event_id` FK → events (cascade), `image` (public-disk path), `media_id`
nullable FK → media (null on delete), `caption` json `{ar,en,fr,tr,ur}` nullable,
`order` int default 0, timestamps. Index `(event_id, order)`.
Model: `SeerahEventGalleryItem`.

## seerah_events_media *(mirror of `articles_media`)*

`id`, `event_id` FK → events (cascade), `media_id` FK → media (cascade),
timestamps. Unique `(event_id, media_id)`.
Model: `SeerahEventMedia` (ids only — see Shared contract below).

## seerah_event_links *(added 2026-09-17)*

`id`, `event_id` FK → events (cascade), `type` string(20) (`LinkedContentType`:
verse | hadith | muslim | article | episode | tag | category), `target_id`,
`order` smallint, timestamps. Unique `(event_id, type, target_id)`.
Model: `SeerahEventLink`. One table for every cross-module link: the event holds ids
only and resolves them through the Shared `ContentReferenceLookup` (no FK to the
target — a deleted target is skipped at render). Episodes are the video queue in
`order`; tags/categories are unordered.

Migration: `2026_08_18_000004_create_seerah_event_relation_tables` (gallery, media,
links) — renamed from `2026_03_09_200000_create_seerah_events_gallery_and_media_tables`.

## Shared contract (new)

```php
namespace App\Domain\Shared\Contracts;

interface MediaLibraryLookup
{
    /** @param list<int> $ids  @return array<int, MediaReference> keyed by id, missing ids omitted */
    public function findMany(array $ids): array;
}

final readonly class MediaReference   // app/Domain/Shared/Contracts
{
    public function __construct(
        public int $id, public string $type, public ?string $title,
        public string $url, public ?string $thumbUrl,
    ) {}
}
```

One query per call (`whereIn`), explicit select. Implementation at app level beside
`App\Models\Media`; bound in `AppServiceProvider`.

## Deleted

Tables: `seerah_participants`, `seerah_participant_translations`,
`seerah_event_timeline_items`, `seerah_event_timeline_item_translations`,
`seerah_event_type_translations`.
Models: `SeerahParticipant`, `SeerahParticipantTranslation`,
`SeerahEventTimelineItem`, `SeerahEventTimelineItemTranslation`,
`SeerahEventTypeTranslation`.
Enums: `EventImportance`, `EventPhase`, `EventType` (legacy), `ParticipantRole`.
Other: `SeerahParticipantResource`, `LinkSeerahParticipantsCommand`,
`Article::seerahEvents()`, the five event content seeders + base.
