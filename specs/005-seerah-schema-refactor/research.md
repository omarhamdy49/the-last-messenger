# Phase 0 Research: Seerah Module & Schema Refactor

All findings below were read from the repository on 2026-09-17 (branch
`005-seerah-schema-refactor`), not assumed. No NEEDS CLARIFICATION remains.

## R1. How to "rewrite migrations in place" when the FK order is wrong

**Finding**: `seerah_events` is created on `2025_10_27`, but the tables it must
reference are created much later: `seerah_event_types` (`2026_06_18`),
`seerah_milestones` (`2026_06_19`), `media` (`2025_11_02_222813`), `locations`
(`2026_08_18`). Twenty-five migration files mention seerah; fourteen exist only to
mutate the first draft.

**Decision**: Three files survive, everything else Seerah-only is deleted.

| File | Action |
|---|---|
| `2025_10_27_130000_create_seerah_events_table.php` | **Rewrite + rename** → `2025_10_27_130000_create_seerah_tables.php`. Creates, in FK order: `seerah_milestones`, `seerah_milestone_translations`, `seerah_event_types`, `seerah_events` (without `location_id`), `seerah_event_translations` (+ driver-guarded FULLTEXT). `down()` drops in reverse. |
| `2026_03_09_200000_create_seerah_event_translations_table.php` | **Rewrite + rename** → `2026_03_09_200000_create_seerah_events_gallery_and_media_tables.php`. First Seerah slot dated after `media` exists. |
| `2026_08_18_000003_add_location_foreign_keys.php` | **Edit**: drop `->after('location')` on the `seerah_events.location_id` line (the column it sat after is gone). Still the owner of `location_id`, because `locations` does not exist earlier. |
| `2025_10_27_130001_create_seerah_participants_table.php`, `2026_03_09_200002_*`, `2026_03_09_200003_*`, `2026_06_10_100000_*`, `2026_06_10_100001_*`, `2026_06_11_250000_*`, `2026_06_18_100000_*`, `2026_06_18_100001_*`, `2026_06_18_100002_*`, `2026_06_19_100000_*`, `2026_06_19_100001_*`, `2026_08_13_100000_*`, `2026_08_15_120000_*` | **Delete** (13 files). |
| `2026_03_10_100003_*`, `2026_03_10_100007_*`, `2026_03_10_100008_*` | **Edit**: remove the `seerah_participants` / `seerah_participant_translations` blocks; other tables in these files are untouched. |
| `2025_10_31_075951_add_search_indexes_to_tables.php` | **Edit**: remove the seerah block. It indexes `importance_level` and `hijri_date` — one never existed, one is removed — inside `try/catch` that swallows the failure. |
| `2026_03_17_000001_add_composite_performance_indexes.php` | **Keep**: `idx_seerah_chronological (hijri_year, hijri_month, hijri_day)` — all three columns survive. |

**Rationale**: a fresh build reaches the target shape with zero create-then-alter
steps (FR-005). Renaming is safe because no environment keeps its `migrations`
rows (Q3: start fresh). `location_id` is the single column added later; moving
`locations` earlier would mean re-dating the whole 003 module for no gain.

**Alternatives rejected**: (a) keep one file per table under the old dates — the
events file would have to create FKs to tables that do not exist yet, or add them
later (create-then-alter). (b) one brand-new consolidated migration — the owner
said no new migrations.

## R2. Rolling out to a database that already ran the old migrations

**Decision**: a one-screen runbook in `quickstart.md`, not code: dump the Seerah
tables → drop the 11 Seerah tables by explicit name (FK checks off) → delete their
rows from `migrations` by explicit name → `php artisan migrate`. The resolved
database name is asserted against an allowlist before the drop (Constitution VIII).
Never `migrate:fresh`, never `--step`, never `--database=`.

**Rationale**: edited migrations do not re-run; `migrate:fresh` would wipe every
other module (it destroyed dev on 2026-09-13). Scoped drop is the only option that
satisfies FR-028. No script is committed — it is run once per environment.

## R3. Event gallery and media "same as articles"

**Finding**: `articles_gallery` = `article_id, image (path), media_id (nullable FK
media), caption (json per locale), order, timestamps`. `articles_media` =
`article_id, media_id, timestamps`, unique pair. Sync logic lives in
`app/Domain/Blog/Actions/{Create,Update}ArticleAction` — replace-on-submit, keyed
by `media_id`, `null` = tab untouched = leave alone, removed rows delete their
stored file. The form uses the `open-media-library` Alpine event with targets
`gallery` / `media_items` and posts `gallery[i]` / `media_items[i]` media ids.

**Decision**: copy the two table shapes exactly (FK column `event_id`). Reuse the
same form contract (`gallery[]`, `media_items[]` of media ids) and the same
replace-on-submit semantics. Files are stored via the Shared `PublicImageService`
(already used by the event controller) under `seerah-events/gallery`.

**Boundary problem**: `deptrac.yaml` allows `DomainSeerah` → `Shared`,
`Exceptions`, `SupportCode` only. `App\Models\Media` is in the `Models` layer, so
`belongsToMany(Media::class)` from `SeerahEvent` would be a **new** violation, and
the baseline may only record pre-existing debt. (The existing
`SeerahEvent → App\Models\Article` baseline entry disappears with
`related_article_id` — a net reduction.)

**Decision**: Seerah owns the link rows (`SeerahEventMedia`, a plain model over
`seerah_events_media`) and stores ids only. Resolving ids to displayable items
goes through a new Shared contract `MediaLibraryLookup` returning
`MediaReference` value objects — the same pattern as the existing
`PersonLookup` / `PersonReference`. The implementation lives at app level next to
`App\Models\Media` and is bound in `SeerahServiceProvider`'s sibling
`AppServiceProvider`. The DB-level FK to `media` is allowed (Constitution IV).

**Alternative rejected**: resolve media in the controller — breaks "controller
calls exactly one service method" and duplicates the lookup in admin + site + API.

## R4. Translation FK names `sm_id` / `event_id`

**Finding**: `HasLocalizedContent::getTranslationForeignKey()` defaults to
`getForeignKey()` and is documented as overridable.

**Decision**: override it on `SeerahMilestone` (`sm_id`) and `SeerahEvent`
(`event_id`). `syncTranslations()` and `translations()` need no change.
`seerah_event_translations.metadata` and `.sources` are added to
`getTranslatableFields()` and cast to `array` on the translation model, replacing
the bespoke `syncLocaleArrays()` path that existed for `lessons`/`sources`.

## R5. Event type names as a `locale` JSON column

**Decision**: `seerah_event_types.locale` is a JSON object `{ar, en, tr, fr, ur}`,
cast `array`. `SeerahEventType::name(?string $locale = null)` returns the locale's
value, falling back to `ar`. No `HasLocalizedContent`, no Spatie trait. The
`SeerahEventTypeTranslation` model and table are deleted. Ordering is by the Arabic
name in PHP — the table has 11 rows and is read through the cached repository.

**Note**: this is the one place the project uses a JSON translation column by
design. It is the owner's explicit choice for a one-word lookup; it must not be
copied to content tables.

## R6. Thumbnail and cover

**Finding**: `featured_image` is a public-disk path written by
`PublicImageService::store(UploadedFile|int $source, $directory)`, which accepts an
upload or a media-library id. `SeerahEvent` also declares Spatie collections and
overrides `getFirstMediaUrl()` to fall back to the column — wired, never used
(0 rows).

**Decision**: `thumb_url` and `cover_url` hold public-disk paths written by the
same service (`seerah-events/thumbs`, `seerah-events/covers`), using two
`<x-admin.image-upload>` inputs (`thumb` / `thumb_id`, `cover` / `cover_id`). The
Spatie `HasMedia` wiring, conversions and the `getFirstMediaUrl()` override are
removed from `SeerahEvent`. Display rule: card = thumb ?? cover; page = cover ?? thumb.

## R7. `status` and `era` "enum from codebase not db"

**Decision**: `string(20)` columns, cast to the existing `SeerahEventStatus`
(`draft|published`) and `EventEra` (`pre_birth|childhood|pre_revelation|mecca|medina`)
enums; validation uses `Rule::enum()`. No DB `ENUM`. `EventImportance`,
`EventPhase`, legacy `EventType` and `ParticipantRole` enums are deleted.

## R8. Dates

**Decision**: `hijri_year/month/day` (smallint/tinyint, nullable, year may be
negative = before Hijrah) + `gregorian_date` (date, nullable). The formatted Hijri
string is composed by one model method from whatever parts exist. Gregorian year,
where shown or filtered (`v1.seerah.by-gregorian-year`), uses a SARGable range on
`gregorian_date` (`>= Y-01-01 AND < Y+1-01-01`), never `YEAR(col)`.
Milestones keep year integers: `hijri_from/to`, `gregorian_from/to`; the two
`*RangeLabel()` methods derive text from numbers, and `hijri_label_*` is dropped.

## R9. What replaces the removed behaviours

| Removed | Replacement |
|---|---|
| Related events by `phase` / `is_featured` | same milestone, then same era, nearest by chronology |
| Public importance + pivotal filters, facets, chips, badges, stats | removed; remaining filters: milestone, type, era, disputed, search |
| `incrementViews()` on every page view | removed; dashboard tile shows counts by status only |
| API `featured` | route + test removed |
| API `map`, `has_location`, `latitude/longitude` | derived from the eager-loaded `place` (location) |
| `Article::seerahEvents()` + admin article picker on events | removed with `related_article_id` (0 rows used) |
| `LinkSeerahParticipantsCommand`, `BackfillLocationsCommand` seerah branch | deleted |
| Event-type rows seeded inside a migration | `SeerahEventTypeSeeder` (idempotent upsert by slug, 11 types, names carried from the legacy enum labels before it is deleted) |
| Five event content seeders + base, Seerah block of `DevContentFixturesSeeder` | deleted (Q3) |

Milestones get no seeder: they are authored in the admin portal (the chronology
JSON the August re-cut was built from is not in this checkout, and the owner chose
a fresh start). Spec US6 wording was aligned to this.

## R10. Indexes (Constitution II — no speculative indexes)

Ship only what a known query path uses; each is confirmed with `EXPLAIN` in the
task that writes the query:

- `seerah_events`: `slug` unique; FKs `event_type`, `sm_id`, (`location_id` in the
  003 file); `(sm_id, status, order)` — public milestone listing;
  `(status, era)` — directory filter; `idx_seerah_chronological` (existing file).
- `seerah_event_translations`: unique `(event_id, locale)`; FULLTEXT
  `(title, description)` behind a MySQL driver guard.
- `seerah_milestone_translations`: unique `(sm_id, locale)`.
- `seerah_milestones`: `slug` unique; `(is_active, order)`.
- `seerah_event_types`: `slug` unique.
- `seerah_events_gallery`: `(event_id, order)`. `seerah_events_media`: unique
  `(event_id, media_id)`.

Dropped with their columns: nine single-column indexes on the old events table.

## R11. Constitution vs. project guide drift

Constitution V says translatable content uses Spatie JSON columns; the project
guide (newer, and what the code does) says translation tables via
`HasLocalizedContent`. This plan follows the project guide. Constitution
"Technology Constraints" also lags (PHP 8.2 / Laravel 12 vs. 8.5 / 13). Neither is
amended here; both are flagged for a constitution version bump.
