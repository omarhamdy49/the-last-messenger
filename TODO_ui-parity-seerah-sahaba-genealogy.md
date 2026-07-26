# SEERAH, SAHABA and GENEALOGY public UI parity review
**Scope:** `seerah-timeline-enhanced.html`, `seerah-events-enhanced.html`, `seerah-event-single-article-enhanced.html`, `seerah-event-single-timeline-enhanced.html`, `sahabaah-single.html` plus the Sahaba carousel/card on `index-enhanced.html`, and `genealogy-enhanced.html`  ·  **Method:** static read of prototypes + Laravel code + migrations

## Executive summary

| Module | Prototype pages | Implemented | Verdict (OK / PARTIAL / MISSING) | Top gap |
|---|---:|---:|---|---|
| Seerah | 4 | 4 (two detail views share one route) | PARTIAL | A cached event can remain publicly visible after it is changed from published to draft. |
| Sahaba | 1 profile + index carousel/card | 1 profile + reusable card/carousel | PARTIAL | Public queries do not enforce the locale translation's `is_ready` publication flag. |
| Genealogy | 1 | 1 tree + paginated JSON people endpoint | PARTIAL | The recursive tree query is N+1 and public records are not readiness-filtered. |

- **P0 — stale draft exposure:** every normal Seerah public repository query starts from `published()`, but public/controller and repository detail caches are not invalidated by all status mutations; a just-unpublished event can remain accessible for up to 30 minutes. `app/Http/Controllers/Site/SeerahController.php:219 — detail payload is cached for 1,800 seconds`; `app/Domain/Seerah/Repositories/SeerahEventRepository.php:186 — bulk status mutation only calls the repository's version-cache clear path`.
- **P0 — unpublished Sahaba exposure:** `is_ready` exists and has a `ready()` scope, yet the public listing, detail, API, and Genealogy repository paths never apply it. `app/Domain/Sahaba/Models/SahabiTranslation.php:94 — the ready scope is defined`; `app/Domain/Sahaba/Repositories/SahabiRepository.php:239 — the public all-companions query does not use it`.
- The two single-event Blade layouts both exist and an unknown query value falls back safely, but layout selection is `?view=timeline`, not event data. `app/Http/Controllers/Site/SeerahController.php:208 — the comment and branch describe query-driven selection`; a repository-wide `rg -n "layout_style|event_layout|display_layout" app database resources routes` returned no matches.
- The Seerah events directory has DB-backed type pills, search, skeletons, empty state, and pagination; the prototype's era/milestone filters are absent, the visible sort is a no-op, and search cannot combine with type. `app/Http/Controllers/Site/SeerahController.php:156 — the mutually exclusive search/type branch has no era, milestone, or sort inputs`.
- The hero-journey landing is a meaningful milestone-based implementation, but it omits the prototype's event search/type filters, map, phase navigation, and vertical/horizontal timeline switch. `resources/views/public/seerah/index.blade.php:280 — Alpine state only switches journey/articles and the selected milestone`.
- Sahaba has biography, virtues, famous narrations, martyrdom, timeline, and related cards, but not kinship or related Seerah events; displayed narration and battle counts are not DB aggregates. `app/Http/Controllers/Site/SahabaController.php:254 — profile mapping uses loaded collections and the reported external count`.
- The shared Sahaba card is extracted and used, but drops the prototype's short biography, read time, and text CTA/footer. `resources/views/components/site/sahaba-card.blade.php:1 — the reusable component exists`; `resources/views/components/site/sahaba-card.blade.php:182 — it ends after the portrait treatment`.
- Genealogy's former “large stat, tiny reachable subset” problem is structurally addressed: the stat total comes from the paginator, initial data is paginated, and `/genealogy/people` supports server search and load-more. `app/Http/Controllers/Site/GenealogyController.php:20 — the page size is explicitly 30`; `routes/web.php:103 — the throttled people endpoint is registered`.
- Genealogy respects per-person/relationship show/order controls and max-count settings, but changing a max-count setting does not evict the one-hour full-page payload. `app/Http/Controllers/Site/GenealogyController.php:86 — the payload is cached for 3,600 seconds`; `app/Http/Controllers/Admin/Content/SiteSettingController.php:51 — setting mutations do not forget that key`.
- Public translation-key coverage for the keys referenced by these views/controllers is complete across `ar`, `en`, `fr`, `tr`, and `ur`; the I18N gaps below concern content fields read from untranslated base columns, not missing UI keys.

## Seerah hero journey landing

### `seerah-timeline-enhanced.html` → `GET /seerah` → `resources/views/public/seerah/index.blade.php`
**Status:** PARTIAL

**Page parity:** `routes/web.php:94 — GET /seerah routes to SeerahController@index`; `app/Http/Controllers/Site/SeerahController.php:32 — index builds and caches a journey payload`; `resources/views/public/seerah/index.blade.php:1 — the implemented public view exists`.

#### Sections & components

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Illustrated hero and aggregate stat cards | A hero, overview copy, journey/articles mode switch, and live stats are present. | Implemented | `../the-last-messenger-ui/seerah-timeline-enhanced.html:447 — prototype hero begins`; `resources/views/public/seerah/index.blade.php:43 — implemented hero/overview begins`. |
| Search field and event-type filter pills | No landing-page search or type-filter controls are rendered. | Missing | `../the-last-messenger-ui/seerah-timeline-enhanced.html:486 — prototype search/filter panel`; `resources/views/public/seerah/index.blade.php:280 — the complete Alpine state contains no search/category state`. |
| Conditional Hijaz map with event markers | No map container, coordinates, or marker UI is present on the landing. | Missing | `../the-last-messenger-ui/seerah-timeline-enhanced.html:555 — prototype map/markers`; `resources/views/public/seerah/index.blade.php:1 — inspection of the full view found no map element or map script`. |
| Phase navigation strip | Implemented as a DB-backed milestone/chapter rail rather than the prototype's era/phase strip. | Partial | `../the-last-messenger-ui/seerah-timeline-enhanced.html:583 — prototype phase navigation`; `resources/views/public/seerah/index.blade.php:134 — milestone rail/cards are rendered`. |
| Vertical alternating timeline | Milestone cards and their event lists provide chronological journey content, but not the prototype's alternating line/node visualization. | Partial | `../the-last-messenger-ui/seerah-timeline-enhanced.html:627 — prototype vertical timeline`; `resources/views/public/seerah/index.blade.php:172 — implementation renders milestone panels and event cards`. |
| Horizontal timeline mode | The implementation offers “journey” and “articles,” not vertical/horizontal visualization modes. | Missing | `../the-last-messenger-ui/seerah-timeline-enhanced.html:402 — prototype vertical/horizontal buttons`; `resources/views/public/seerah/index.blade.php:280 — only `mode: 'journey'` and `mode: 'articles'` exist`. |
| Loading/empty state and terminal CTA | Empty states and a CTA to the events directory are present; loading is server-rendered rather than simulated. | Implemented | `../the-last-messenger-ui/seerah-timeline-enhanced.html:788 — prototype empty state`; `../the-last-messenger-ui/seerah-timeline-enhanced.html:795 — prototype CTA`; `resources/views/public/seerah/index.blade.php:236 — implemented empty/CTA area`. |
| Reusable timeline/map/filter components | These larger patterns remain inline; no `site` timeline or map component was extracted. | Missing | A repository `rg -n "seerah|timeline|map" resources/views/components/site` returned no matching component; `resources/views/public/seerah/index.blade.php:134 — journey markup is inline`. |

#### Features & wiring

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Search and type filtering | Available on `/seerah/events`, not on this route, so the landing behavior is absent rather than backend-wired. | Missing | `../the-last-messenger-ui/seerah-timeline-enhanced.html:290 — prototype Alpine owns searchQuery and activeCategory`; `app/Http/Controllers/Site/SeerahController.php:32 — index accepts no Request/filter input`. |
| Map show/hide | Prototype toggles a fake Alpine map; the implementation has no equivalent control or endpoint. | Missing | `../the-last-messenger-ui/seerah-timeline-enhanced.html:420 — prototype map toggle`; `resources/views/public/seerah/index.blade.php:280 — implemented Alpine methods contain no map state`. |
| Timeline orientation | Prototype switches client-side between two presentations; implementation cannot switch orientation. | Missing | `../the-last-messenger-ui/seerah-timeline-enhanced.html:402 — orientation controls`; `resources/views/public/seerah/index.blade.php:280 — only content-mode switching is wired`. |
| Milestone selection and URL state | Clicking a milestone updates active chapter state and a URL query marker client-side. | Implemented | `resources/views/public/seerah/index.blade.php:308 — `selectMilestone()` updates state/history`; `app/Domain/Seerah/Repositories/SeerahMilestoneRepository.php:40 — active milestones are loaded with published events`. |
| Journey/articles switch | Real data is rendered in both modes, but the articles mode is capped at 500 events. | Partial | `resources/views/public/seerah/index.blade.php:280 — mode switch is Alpine-backed`; `app/Http/Controllers/Site/SeerahController.php:52 — index requests a fixed perPage: 500`. |

#### Schema

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Date-range eras/phases | `era`, `phase`, start/end Hijri year, statistics, and phase order exist on events. | Implemented | `database/migrations/2026_06_10_100000_add_taxonomy_and_statistics_to_seerah_events_table.php:10 — taxonomy/date/stat columns are added`; `app/Domain/Seerah/Models/SeerahEvent.php:104 — fields are cast/fillable`. |
| Journey milestones (الحقبة) | Dedicated milestone and translation tables plus event FK support curated chapters. | Implemented | `database/migrations/2026_06_19_100000_create_seerah_milestones_table.php:19 — milestone schema`; `database/migrations/2026_06_19_100001_add_milestone_id_to_seerah_events_table.php:17 — event FK`. |
| Event type | It is correctly DB-driven through `seerah_event_type_id`; the legacy enum column is dropped. | Implemented | `database/migrations/2026_06_18_100001_seed_event_types_and_link_seerah_events.php:68 — FK is added`; `database/migrations/2026_06_18_100001_seed_event_types_and_link_seerah_events.php:93 — legacy enum is dropped`. |
| Publication status | `published`/`draft` status exists and is indexed, and normal public event queries use the published scope. | Partial | `database/migrations/2026_06_18_100002_add_status_to_seerah_events_table.php:10 — status schema`; `app/Domain/Seerah/Models/SeerahEvent.php:303 — published scope`; cache invalidation remains unsafe as SSG-001 describes. |
| Map/media data | Events have latitude, longitude, map URL, image, thumbnail, and gallery fields, but the landing mapper/view does not expose a map. | Partial | `database/migrations/2025_10_27_130000_create_seerah_events_table.php:44 — location/media columns`; `app/Http/Controllers/Site/SeerahController.php:272 — timeline mapping omits coordinates and gallery`. |

#### Enhancement gaps

- `app/Domain/Seerah/Repositories/SeerahEventRepository.php:222 — list relations are constrained, but the base event query has no select projection`; violates `CLAUDE.md:914 — select only required columns and eager-load relationships` (SSG-020).
- `app/Http/Controllers/Site/SeerahController.php:52 — a hard 500-event fetch makes the landing's articles mode silently incomplete when the corpus grows beyond that cap`; conflicts with the prototype's reachable timeline and `CLAUDE.md:914 — efficient pagination` (SSG-029).
- `resources/views/public/seerah/index.blade.php:280 — no loading/error state exists around URL-state changes`; lower impact because the page is fully server rendered.

## Seerah events directory

### `seerah-events-enhanced.html` → `GET /seerah/events` → `resources/views/public/seerah/events.blade.php`
**Status:** PARTIAL

**Page parity:** `routes/web.php:95 — GET /seerah/events routes to SeerahController@events`; `app/Http/Controllers/Site/SeerahController.php:134 — events handles the directory`; `resources/views/public/seerah/events.blade.php:1 — the Blade page exists`.

#### Sections & components

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Hero and aggregate cards | Hero copy and a total-events summary are present, with a less elaborate stat treatment. | Partial | `../the-last-messenger-ui/seerah-events-enhanced.html:600 — prototype hero/stats`; `resources/views/public/seerah/events.blade.php:273 — implemented hero/summary`. |
| Search input | A real GET search form is rendered. | Implemented | `../the-last-messenger-ui/seerah-events-enhanced.html:633 — prototype search`; `resources/views/public/seerah/events.blade.php:311 — implemented GET form`. |
| Era dropdown | No era control is rendered or accepted. | Missing | `../the-last-messenger-ui/seerah-events-enhanced.html:648 — prototype era dropdown`; `app/Http/Controllers/Site/SeerahController.php:134 — inspected request inputs are only page, q, and category`. |
| Milestone/حقبة filter | The schema has milestones, but the directory exposes no milestone control/query. | Missing | `database/migrations/2026_06_19_100001_add_milestone_id_to_seerah_events_table.php:17 — event milestone FK exists`; `resources/views/public/seerah/events.blade.php:311 — filter form/pills have no milestone input`. |
| Chronology sort | A newest/oldest select is visible but has no name, submit handler, or sorting implementation. | Partial | `../the-last-messenger-ui/seerah-events-enhanced.html:662 — prototype sort`; `resources/views/public/seerah/events.blade.php:340 — select only binds local sortOrder`; `app/Http/Controllers/Site/SeerahController.php:134 — controller reads no sort value`. |
| DB-backed event-type pills and counts | Active event-type slugs generate pills and link to server filtering. Counts, however, include drafts. | Partial | `resources/views/public/seerah/events.blade.php:349 — pills are rendered`; `app/Http/Controllers/Site/SeerahController.php:142 — slugs are whitelisted from DB`; `app/Domain/Seerah/Repositories/SeerahEventRepository.php:289 — stats/count query lacks the published scope`. |
| Result summary/reset | Both are implemented. | Implemented | `../the-last-messenger-ui/seerah-events-enhanced.html:690 — prototype summary/reset`; `resources/views/public/seerah/events.blade.php:371 — implementation`. |
| Skeleton/loading state | Alpine skeleton cards display briefly before server-rendered results. | Implemented | `../the-last-messenger-ui/seerah-events-enhanced.html:717 — prototype skeleton`; `resources/views/public/seerah/events.blade.php:388 — implementation`. |
| Event list cards | Cards show type, date, title, excerpt, location, and stats from real mapped data. | Implemented | `../the-last-messenger-ui/seerah-events-enhanced.html:733 — prototype list`; `resources/views/public/seerah/events.blade.php:425 — implementation`. |
| Empty state | A translated resettable empty state is present. | Implemented | `../the-last-messenger-ui/seerah-events-enhanced.html:809 — prototype empty`; `resources/views/public/seerah/events.blade.php:402 — implementation`. |
| Numbered pagination | Laravel pagination is present and retains query parameters. | Implemented | `../the-last-messenger-ui/seerah-events-enhanced.html:821 — prototype pagination`; `resources/views/public/seerah/events.blade.php:508 — paginator links`; `app/Http/Controllers/Site/SeerahController.php:191 — current query is attached`. |
| Scroll-to-top control | The prototype floating control is absent. | Missing | `../the-last-messenger-ui/seerah-events-enhanced.html:898 — scroll-top control`; `resources/views/public/seerah/events.blade.php:1 — full-view inspection found no equivalent`. |

#### Features & wiring

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Text search | Server-wired to repository full-text/LIKE search through `q`. | Implemented | `app/Http/Controllers/Site/SeerahController.php:158 — search service path`; `app/Domain/Seerah/Repositories/SeerahEventRepository.php:114 — public search starts with published events`. |
| Type filtering | Server-wired to active DB type slugs, but ignored whenever search is non-empty. | Partial | `app/Http/Controllers/Site/SeerahController.php:142 — whitelist`; `app/Http/Controllers/Site/SeerahController.php:158 — `if/elseif` makes search and category mutually exclusive`. |
| Era/milestone filtering | No query parameters, service methods, or UI wiring on this page. | Missing | `app/Http/Controllers/Site/SeerahController.php:134 — complete handler lacks both inputs`; `app/Domain/Seerah/Repositories/SeerahEventRepository.php:34 — available public filters include type/year/featured but no era/milestone directory query`. |
| Sort newest/oldest | Fake client state: changing it has no observable effect. | Missing | `resources/views/public/seerah/events.blade.php:340 — `x-model` is not consumed`; `app/Http/Controllers/Site/SeerahController.php:156 — all branches retain repository-default chronology`. |
| Pagination/loading | Pagination is backend-wired; the skeleton is cosmetic and not tied to navigation state. | Partial | `resources/views/public/seerah/events.blade.php:388 — timed Alpine skeleton`; `resources/views/public/seerah/events.blade.php:508 — real paginator`. |

#### Schema

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Type filter relation | Dedicated type/translation tables and an event FK fully support it. | Implemented | `database/migrations/2026_06_18_100000_create_seerah_event_types_table.php:21 — type tables`; `app/Domain/Seerah/Models/SeerahEvent.php:166 — model relation`. |
| Era/phase/milestone filters | Required fields and milestone FK exist; the gap is query/UI wiring, not schema. | Implemented | `database/migrations/2026_06_10_100000_add_taxonomy_and_statistics_to_seerah_events_table.php:15 — era/phase fields`; `database/migrations/2026_06_19_100001_add_milestone_id_to_seerah_events_table.php:17 — milestone FK`. |
| Search translations | Event title/description are normalized by locale with unique and full-text indexes. | Implemented | `database/migrations/2026_03_09_200000_create_seerah_event_translations_table.php:14 — translation table`; `database/migrations/2026_03_09_200000_create_seerah_event_translations_table.php:26 — unique/full-text indexes`. |
| Public chronology index | The index covers Hijri date only; it does not lead with publication status for the page's `status + chronological ORDER BY` access path. | Partial | `database/migrations/2026_03_17_000001_add_composite_performance_indexes.php:63 — chronological index is only hijri_year/month/day`; `app/Domain/Seerah/Repositories/SeerahEventRepository.php:132 — public chronology applies status then date order`. |

#### Enhancement gaps

- `resources/views/public/seerah/events.blade.php:323 — the search input does not use the required mobile `text-base sm:text-sm` sizing`; violates the requested mobile rule and `CLAUDE.md:489 — responsive/RTL-safe frontend` (part of SSG-027).
- `resources/views/public/seerah/events.blade.php:490 — the read-more arrow uses a hard-coded negative X translation even though the icon flips by locale`; violates `CLAUDE.md:489 — RTL must work without overrides` (SSG-026).
- `resources/views/public/seerah/events.blade.php:459 — secondary card text uses dark:text-gray-400 rather than the specified dark:text-gray-300`; contrast normalization is needed (SSG-027).
- `app/Http/Controllers/Site/SeerahController.php:148 — directory payloads cache for 15 minutes under keys that admin event mutations do not explicitly forget`; publication safety is covered by SSG-001.

## Seerah single event — article layout

### `seerah-event-single-article-enhanced.html` → `GET /seerah/event/{slug}` (default) → `resources/views/public/seerah/event.blade.php`
**Status:** PARTIAL

**Page parity:** `routes/web.php:96 — GET /seerah/event/{slug} routes to SeerahController@event`; `app/Http/Controllers/Site/SeerahController.php:212 — event selects the detail view`; `resources/views/public/seerah/event.blade.php:1 — the default article Blade exists`.

#### Sections & components

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Reading-progress bar | Implemented with scroll-based Alpine state. | Implemented | `../the-last-messenger-ui/seerah-event-single-article-enhanced.html:269 — prototype progress state`; `resources/views/public/seerah/event.blade.php:285 — implementation initializes progress tracking`. |
| Image hero, breadcrumbs, title/date/type/summary | Present, but the hero uses one static asset rather than event media. | Partial | `../the-last-messenger-ui/seerah-event-single-article-enhanced.html:445 — prototype hero`; `resources/views/public/seerah/event.blade.php:376 — hard-coded assets/event/event-img.png`. |
| Event stats strip | Implemented from event statistics. | Implemented | `../the-last-messenger-ui/seerah-event-single-article-enhanced.html:511 — prototype stats`; `resources/views/public/seerah/event.blade.php:409 — stats are rendered`. |
| Introduction and historical context/Quran callout | Intro/context copy is rendered when mapped; there is no structured Quran-reference callout. | Partial | `../the-last-messenger-ui/seerah-event-single-article-enhanced.html:535 — prototype introduction/context`; `../the-last-messenger-ui/seerah-event-single-article-enhanced.html:551 — Quran callout`; `resources/views/public/seerah/event.blade.php:450 — story/context sections only`. |
| Expandable event timeline | Real translated timeline items are rendered with Alpine expand/collapse. | Implemented | `../the-last-messenger-ui/seerah-event-single-article-enhanced.html:576 — prototype timeline`; `resources/views/public/seerah/event.blade.php:491 — implementation`. |
| Lessons/key takeaways | The `#lessons` anchor actually contains participant/character cards; no lesson content exists. | Missing | `../the-last-messenger-ui/seerah-event-single-article-enhanced.html:746 — prototype lessons`; `resources/views/public/seerah/event.blade.php:552 — `id="lessons"` wraps characters`. |
| Sources/references | No sources bibliography or reference metadata is rendered. | Missing | `../the-last-messenger-ui/seerah-event-single-article-enhanced.html:806 — prototype sources`; `resources/views/public/seerah/event.blade.php:1 — full-view inspection found no sources section`. |
| Sticky table of contents | Present for the sections that exist. | Implemented | `../the-last-messenger-ui/seerah-event-single-article-enhanced.html:841 — prototype TOC`; `resources/views/public/seerah/event.blade.php:596 — sticky sidebar TOC`. |
| Key-character cards | Present from Seerah participants, but they occupy the lessons anchor. | Partial | `../the-last-messenger-ui/seerah-event-single-article-enhanced.html:897 — prototype character cards`; `resources/views/public/seerah/event.blade.php:552 — implemented cards`. |
| Event map | A static generic image is rendered; stored coordinates/map URL are ignored. | Partial | `../the-last-messenger-ui/seerah-event-single-article-enhanced.html:943 — prototype map`; `resources/views/public/seerah/event.blade.php:681 — static map asset`. |
| Related events | Related real events are shown. | Implemented | `../the-last-messenger-ui/seerah-event-single-article-enhanced.html:964 — prototype related items`; `resources/views/public/seerah/event.blade.php:697 — implementation`. |
| Gallery/lightbox | The event schema supports a gallery, but this article view has no gallery UI. | Missing | `database/migrations/2025_10_27_130000_create_seerah_events_table.php:55 — `gallery_images` exists`; `resources/views/public/seerah/event.blade.php:1 — full-view inspection found no gallery/lightbox`. |

#### Features & wiring

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Bookmark | Only toggles local Alpine state; there is no persistence endpoint or localStorage key. | Missing | `../the-last-messenger-ui/seerah-event-single-article-enhanced.html:291 — prototype also uses local state`; `resources/views/public/seerah/event.blade.php:305 — `bookmarked` is only a boolean`; `routes/web.php:93 — the complete Seerah public route block has no bookmark mutation`. |
| Native share / copy fallback | Native sharing works. The fallback displays a “copied” toast without writing to the clipboard. | Partial | `resources/views/public/seerah/event.blade.php:329 — native share branch`; `resources/views/public/seerah/event.blade.php:336 — fallback only changes toast text`; the prototype has the same defect at `../the-last-messenger-ui/seerah-event-single-article-enhanced.html:334`. |
| Timeline expansion | Wired entirely in Alpine against server-rendered timeline data. | Implemented | `resources/views/public/seerah/event.blade.php:491 — timeline controls/data`. |
| Switch to timeline layout | A link adds `?view=timeline`; it is not chosen from event data. | Partial | `resources/views/public/seerah/event.blade.php:641 — alternate-layout link`; `app/Http/Controllers/Site/SeerahController.php:216 — query string decides the layout`. |
| Map interaction | No map library, directions action, or coordinate-backed interaction is wired. | Missing | `resources/views/public/seerah/event.blade.php:681 — only an `<img>` is rendered`; `app/Http/Controllers/Site/SeerahController.php:323 — detail mapping omits map coordinates/URL`. |

#### Schema

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Translated article text | Title, description, detailed description, causes, context, result, and significance are locale rows. | Implemented | `database/migrations/2026_03_09_200000_create_seerah_event_translations_table.php:14 — translated event fields`; `app/Domain/Seerah/Models/SeerahEvent.php:202 — translation accessor`. |
| Timeline entries | Normalized item and translation tables with per-event ordering fully support the section. | Implemented | `database/migrations/2026_06_10_100001_create_seerah_event_timeline_items_table.php:21 — item table`; `database/migrations/2026_06_10_100001_create_seerah_event_timeline_items_table.php:32 — translation table`. |
| Participants | Participant rows and translations exist, but `participant_id` is a nullable generic integer with no Sahaba FK. | Partial | `database/migrations/2025_10_27_130001_create_seerah_participants_table.php:16 — participant schema`; `database/migrations/2025_10_27_130001_create_seerah_participants_table.php:25 — generic ID has no constraint`. |
| Lessons, Quran references, and sources | No normalized columns/tables were found for these prototype data sets. | Missing | `app/Domain/Seerah/Models/SeerahEvent.php:72 — fillable event fields contain none`; `database/migrations/2026_03_09_200000_create_seerah_event_translations_table.php:14 — translation fields contain none`; repository-wide inspection of matching Seerah migrations found no lesson/source/reference schema. |
| Media/map | Base event columns support featured images, thumbnails, gallery JSON, latitude/longitude, map URL, and location. | Implemented | `database/migrations/2025_10_27_130000_create_seerah_events_table.php:44 — location/media schema`; mapping/rendering is the gap. |
| Layout choice | No event layout column/cast exists. | Missing | `app/Domain/Seerah/Models/SeerahEvent.php:72 — fillable/casts have no layout field`; `rg -n "layout_style|event_layout|display_layout" app database resources routes` returned no matches. |

#### Enhancement gaps

- `app/Domain/Seerah/Repositories/SeerahEventRepository.php:238 — detail eager-loading pulls the related Article's author/categories/tags/translations even though `mapEventDetail()` does not expose article data`; violates `CLAUDE.md:914 — load only required relationships` (SSG-020).
- `resources/views/public/seerah/event.blade.php:336 — share fallback claims success without attempting clipboard write`; misleading behavior is covered by SSG-010.
- `resources/views/public/seerah/event.blade.php:376 — static hero media`; `resources/views/public/seerah/event.blade.php:681 — static map media`; both discard schema-backed data (SSG-009).
- `resources/views/public/seerah/event.blade.php:552 — a participant section is mislabeled with `id="lessons"`, making the table-of-contents target semantically wrong`; accessibility/content structure is covered by SSG-008.

## Seerah single event — timeline layout

### `seerah-event-single-timeline-enhanced.html` → `GET /seerah/event/{slug}?view=timeline` → `resources/views/public/seerah/event-timeline.blade.php`
**Status:** PARTIAL

**Page parity:** `app/Http/Controllers/Site/SeerahController.php:216 — exact timeline query value selects the alternate view`; `app/Http/Controllers/Site/SeerahController.php:257 — it returns public.seerah.event-timeline`; `resources/views/public/seerah/event-timeline.blade.php:1 — the Blade view exists`.

#### Sections & components

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Cinematic hero | Present from event data, but still falls back to shared/static presentation rather than mapping all event media. | Partial | `../the-last-messenger-ui/seerah-event-single-timeline-enhanced.html:737 — prototype hero`; `resources/views/public/seerah/event-timeline.blade.php:249 — implemented hero`. |
| Event statistics | Implemented from mapped statistics. | Implemented | `../the-last-messenger-ui/seerah-event-single-timeline-enhanced.html:797 — prototype stats`; `resources/views/public/seerah/event-timeline.blade.php:330 — implementation`. |
| Chronological journey/timeline | Real translated timeline items are rendered. | Implemented | `../the-last-messenger-ui/seerah-event-single-timeline-enhanced.html:850 — prototype timeline`; `resources/views/public/seerah/event-timeline.blade.php:372 — implementation`. |
| Lessons cards | No lessons section exists. | Missing | `../the-last-messenger-ui/seerah-event-single-timeline-enhanced.html:897 — prototype lessons`; `resources/views/public/seerah/event-timeline.blade.php:1 — full-view inspection found no lessons markup`. |
| Participant cards and detail modal | Cards and an Alpine modal are present from participant data. | Implemented | `../the-last-messenger-ui/seerah-event-single-timeline-enhanced.html:968 — prototype participants`; `resources/views/public/seerah/event-timeline.blade.php:404 — cards`; `resources/views/public/seerah/event-timeline.blade.php:496 — modal`. |
| Interactive location map | Entire map/directions section is absent. | Missing | `../the-last-messenger-ui/seerah-event-single-timeline-enhanced.html:1013 — prototype map`; `resources/views/public/seerah/event-timeline.blade.php:1 — no map container/script in the full view`. |
| Floating actions (share, print, like, article switch) | Share, like, and article switch are rendered; print is absent and like is ephemeral. | Partial | `../the-last-messenger-ui/seerah-event-single-timeline-enhanced.html:1069 — prototype FAB group`; `resources/views/public/seerah/event-timeline.blade.php:520 — implemented actions`. |
| Gallery and lightbox | Entirely absent despite gallery schema. | Missing | `../the-last-messenger-ui/seerah-event-single-timeline-enhanced.html:1181 — prototype lightbox`; `database/migrations/2025_10_27_130000_create_seerah_events_table.php:55 — gallery JSON exists`; `resources/views/public/seerah/event-timeline.blade.php:1 — no gallery UI`. |
| Participant modal semantics | It has `role="dialog"`/`aria-modal` and bounded scrolling, but lacks an accessible-name association, focus trap/return, and Escape close. | Partial | `resources/views/public/seerah/event-timeline.blade.php:496 — modal begins`; `resources/views/public/seerah/event-timeline.blade.php:502 — dialog role/aria are present`; compare prototype modal at `../the-last-messenger-ui/seerah-event-single-timeline-enhanced.html:1142`. |

#### Features & wiring

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Share/copy | Uses native sharing and a real clipboard fallback. | Implemented | `resources/views/public/seerah/event-timeline.blade.php:188 — share function`; `resources/views/public/seerah/event-timeline.blade.php:201 — clipboard write`. |
| Like/favorite | Client-only boolean; refresh loses it and no endpoint is called. | Missing | `resources/views/public/seerah/event-timeline.blade.php:455 — button toggles local liked state`; `routes/web.php:93 — no Seerah engagement route`. |
| Print | Prototype invokes print; no timeline-view print control exists. | Missing | `../the-last-messenger-ui/seerah-event-single-timeline-enhanced.html:1107 — prototype print`; `resources/views/public/seerah/event-timeline.blade.php:520 — full action group omits it`. |
| Gallery/lightbox | Prototype code owns lightbox state; implementation has no equivalent. | Missing | `../the-last-messenger-ui/seerah-event-single-timeline-enhanced.html:560 — prototype lightbox methods`; `resources/views/public/seerah/event-timeline.blade.php:108 — Alpine data has no gallery/lightbox state`. |
| Switch to article layout | Real link returns to the same event without the timeline query. | Implemented | `resources/views/public/seerah/event-timeline.blade.php:447 — article link`. |
| Data-driven layout selection and fallback | Unknown query values degrade to article correctly, but the persisted event never controls layout. | Partial | `app/Http/Controllers/Site/SeerahController.php:216 — only exact query value selects timeline`; schema absence is documented above. |

#### Schema

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Timeline/statistics | Both are supported by normalized timeline tables and event statistics JSON. | Implemented | `database/migrations/2026_06_10_100001_create_seerah_event_timeline_items_table.php:21 — timeline`; `database/migrations/2026_06_10_100000_add_taxonomy_and_statistics_to_seerah_events_table.php:38 — statistics`. |
| Participant modal | Participant translated name/role/description is supported; no canonical Sahabi relation is enforced. | Partial | `database/migrations/2026_03_10_100003_create_phase_4_translation_tables.php:66 — participant translations`; `database/migrations/2025_10_27_130001_create_seerah_participants_table.php:25 — unconstrained participant ID`. |
| Lessons/map/gallery | Map and gallery base columns exist; lesson content does not. None is mapped into this view. | Partial | `database/migrations/2025_10_27_130000_create_seerah_events_table.php:44 — map/gallery schema`; `app/Http/Controllers/Site/SeerahController.php:323 — detail mapper omits it`; `app/Domain/Seerah/Models/SeerahEvent.php:72 — no lesson field`. |
| Layout choice | The required article/timeline discriminator is absent from schema/model. | Missing | `app/Domain/Seerah/Models/SeerahEvent.php:72 — no field/cast`; exact repository-wide layout-field grep returned no matches. |

#### Enhancement gaps

- `resources/views/public/seerah/event-timeline.blade.php:496 — the participant modal has base dialog semantics but lacks an accessible-name association, Escape close, focus trap, and focus return`; violates accessibility basics in the requested §5 review (SSG-025).
- `resources/views/public/seerah/event-timeline.blade.php:455 — a heart control implies saved state but only flips memory-local Alpine state`; misleading engagement behavior (SSG-010).
- `app/Http/Controllers/Site/SeerahController.php:219 — both detail layouts share a 30-minute public cache that status changes do not reliably invalidate`; violates `CLAUDE.md:794 — private/unpublished data must not leak` (SSG-001).
- Prototype-only defects were not counted against Laravel: `../the-last-messenger-ui/seerah-event-single-timeline-enhanced.html:737 — hero references showFabMenu while the FAB owns showMenu`; `../the-last-messenger-ui/seerah-event-single-timeline-enhanced.html:560 — lightbox methods exist but the gallery markup has no visible trigger`.

## Sahabi profile and reusable carousel card

### `sahabaah-single.html` → `GET /sahaba/{slug}` → `resources/views/public/sahaba/show.blade.php` (plus `GET /sahaba`)
**Status:** PARTIAL

**Page parity:** `routes/web.php:99 — GET /sahaba routes to SahabaController@index`; `routes/web.php:100 — GET /sahaba/{slug} routes to SahabaController@show`; `app/Http/Controllers/Site/SahabaController.php:73 — show maps the profile`; `resources/views/public/sahaba/show.blade.php:1 — the profile view exists`. The reusable carousel surface is `resources/views/public/home.blade.php:278 — home Sahaba rail` using `resources/views/components/site/sahaba-card.blade.php:1 — shared card`.

#### Sections & components

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Reading progress and profile hero | Both are implemented; hero shows portrait, title/name, summary, dates, and badges. | Implemented | `../the-last-messenger-ui/sahabaah-single.html:217 — prototype Alpine/progress`; `../the-last-messenger-ui/sahabaah-single.html:370 — profile hero`; `resources/views/public/sahaba/show.blade.php:159 — progress CSS`; `resources/views/public/sahaba/show.blade.php:267 — hero`. |
| Summary statistics | Age, battle count, and narrated-hadith count are shown, but two counts are not DB relationship aggregates. | Partial | `../the-last-messenger-ui/sahabaah-single.html:406 — prototype stats`; `resources/views/public/sahaba/show.blade.php:310 — implementation`; `app/Http/Controllers/Site/SahabaController.php:271 — battle count is from a loaded collection`; `app/Http/Controllers/Site/SahabaController.php:305 — hadith count is the reported external field`. |
| Profile facts / kinship | Tribe, status, birth/death facts are shown; father, mother, spouses/children, and relationship labels are absent even though relations exist. | Partial | `../the-last-messenger-ui/sahabaah-single.html:423 — prototype facts/kinship`; `resources/views/public/sahaba/show.blade.php:327 — facts grid`; `app/Domain/Sahaba/Models/Sahabi.php:174 — father/mother relations`; `app/Domain/Sahaba/Models/Sahabi.php:223 — genealogy relationships`. |
| Bookmark/share actions | Bookmark persists to localStorage and share has native/clipboard behavior; neither creates a server-side user favorite. | Partial | `../the-last-messenger-ui/sahabaah-single.html:478 — prototype controls are only buttons`; `resources/views/public/sahaba/show.blade.php:215 — Alpine/localStorage state`; `resources/views/public/sahaba/show.blade.php:349 — controls`. |
| Section navigation pills | Present and linked to page anchors. | Implemented | `../the-last-messenger-ui/sahabaah-single.html:497 — prototype pills`; `resources/views/public/sahaba/show.blade.php:378 — implementation`. |
| Biography/story | Real translated biography is rendered. | Implemented | `../the-last-messenger-ui/sahabaah-single.html:535 — prototype story`; `resources/views/public/sahaba/show.blade.php:409 — implementation`. |
| Virtues/heroism | Real translated virtues are rendered, but not structured into the prototype's multiple feature blocks. | Partial | `../the-last-messenger-ui/sahabaah-single.html:581 — prototype heroism`; `resources/views/public/sahaba/show.blade.php:431 — translated virtues prose`. |
| Martyrdom/death section | Present with translated martyrdom details and base date/place facts. | Implemented | `../the-last-messenger-ui/sahabaah-single.html:633 — prototype martyrdom`; `resources/views/public/sahaba/show.blade.php:472 — implementation`. |
| Life timeline | Normalized, translated timeline entries are rendered. | Implemented | `../the-last-messenger-ui/sahabaah-single.html:674 — prototype timeline`; `resources/views/public/sahaba/show.blade.php:502 — implementation`. |
| Related Seerah events/articles | The page renders an article slot, but controller always maps it to `null`; no related Seerah events are queried. | Missing | `../the-last-messenger-ui/sahabaah-single.html:750 — prototype related content`; `app/Http/Controllers/Site/SahabaController.php:310 — `article` is hard-coded null`; `resources/views/public/sahaba/show.blade.php:536 — related-content area`. |
| Related Sahaba | Cards are present, but they are merely a featured set excluding the current profile, not kinship/semantic relationships. | Partial | `../the-last-messenger-ui/sahabaah-single.html:772 — prototype related people`; `app/Http/Controllers/Site/SahabaController.php:92 — comment identifies featured-set behavior`; `resources/views/public/sahaba/show.blade.php:557 — cards`. |
| Homepage carousel | Accessible horizontal rail, RTL-aware arrow controls, and view-all link are implemented with real records. | Implemented | `../the-last-messenger-ui/index-enhanced.html:652 — prototype Sahaba section`; `../the-last-messenger-ui/index-enhanced.html:664 — carousel state`; `resources/views/public/home.blade.php:278 — implementation`; `app/Http/Controllers/Site/HomeController.php:38 — eight featured companions are loaded`. |
| Reusable Sahaba card | Extracted and used by home/index/related surfaces, but only renders portrait/name/badges/facts; prototype bio excerpt, read time, and footer CTA are absent. | Partial | `../the-last-messenger-ui/index-enhanced.html:704 — prototype card includes excerpt/read metadata`; `resources/views/components/site/sahaba-card.blade.php:1 — component`; `resources/views/components/site/sahaba-card.blade.php:182 — component ends without that footer`. |
| Sahaba directory filters/pagination | Search, all/featured/Ansar/Muhajir, page-size options, empty state, and pagination exist; tribe, battle, gender, and top-narrator filters do not. | Partial | `resources/views/public/sahaba/index.blade.php:111 — search`; `resources/views/public/sahaba/index.blade.php:151 — pills`; `resources/views/public/sahaba/index.blade.php:178 — empty state`; `resources/views/public/sahaba/index.blade.php:205 — pagination`; `app/Http/Controllers/Site/SahabaController.php:37 — allowed filters are only four values`. |

#### Features & wiring

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Search/filter/pagination | Search and four filters are server-wired. Featured mode loads at most 48 rows, then creates an in-memory paginator, so later featured rows are unreachable. | Partial | `app/Http/Controllers/Site/SahabaController.php:109 — paginator dispatch`; `app/Http/Controllers/Site/SahabaController.php:139 — featured fetch cap/in-memory pagination`; `resources/views/public/sahaba/index.blade.php:205 — rendered pagination`. |
| Bookmark | LocalStorage persistence works on the browser, with no account-synced endpoint. | Partial | `resources/views/public/sahaba/show.blade.php:223 — storage key is read/written`; `routes/web.php:98 — Sahaba route block contains no mutation`. |
| Share/copy | Native share and clipboard fallback are implemented. | Implemented | `resources/views/public/sahaba/show.blade.php:238 — share handler`; `resources/views/public/sahaba/show.blade.php:247 — clipboard fallback`. |
| Narrated-hadith count | Displays a curated/imported claim, not the available `hadith_isnad`/narrator-link DB aggregate. | Partial | `app/Http/Controllers/Site/SahabaController.php:305 — maps reported_hadith_count`; `app/Domain/Sahaba/Models/Sahabi.php:410 — docs distinguish the external claim`; `app/Domain/Sahaba/Models/Sahabi.php:426 — DB aggregate scope already exists but is not used`. |
| Related events and kinship | No public controller/repository query wires either feature. | Missing | `app/Http/Controllers/Site/SahabaController.php:83 — loaded relations omit father, mother, relationships, and events`; repository-wide `rg -n "related.*event|events\\(|seerah" app/Domain/Sahaba app/Http/Controllers/Site/SahabaController.php` returned no Sahaba-event relation/query. |
| Carousel arrows | Scroll real server-rendered cards and correctly account for RTL. | Implemented | `resources/views/public/home.blade.php:326 — previous/next controls`; `resources/views/public/home.blade.php:350 — scroll function uses computed direction`. |

#### Schema

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Biography, virtues, narrations, martyrdom | Locale rows support biography, virtues, famous narrations, and martyrdom details. | Implemented | `database/migrations/2026_03_09_200001_create_sahabi_translations_table.php:14 — core translated profile fields`; `database/migrations/2026_06_10_100001_add_martyrdom_to_sahabi_translations_table.php:9 — martyrdom fields`. |
| Timeline | Dedicated item and translation tables support ordered events. | Implemented | `database/migrations/2026_06_10_100002_create_sahabi_timeline_items_table.php:9 — item/translation schema`. |
| Parentage/kinship | Canonical `sahaba` rows have father/mother FKs, and `relationships` has typed edges, show/order fields, and uniqueness. | Implemented | `database/migrations/2026_06_24_100000_consolidate_genealogy_into_sahaba.php:233 — legacy parentage is remapped`; `database/migrations/2026_06_24_100000_consolidate_genealogy_into_sahaba.php:501 — canonical self-FKs are enforced`; `database/migrations/2025_10_27_140001_create_relationships_table.php:16 — edge schema`; `database/migrations/2026_07_02_140002_add_display_order_and_hide_from_tree_to_relationships_table.php:17 — curation columns`. |
| Hadith count | Both an external `reported_hadith_count` and canonical narrator links exist; the UI chooses the former. | Partial | `database/migrations/2026_07_02_090001_rename_hadith_count_on_sahaba.php:9 — explicitly renames the external claim`; `database/migrations/2026_06_24_100000_consolidate_genealogy_into_sahaba.php:100 — canonical narrator links gain sahaba_id`. |
| Battles | Normalized battle rows support `withCount`; controller instead counts the loaded collection. | Partial | `database/migrations/2026_03_17_000002_create_sahabi_battles_table.php:10 — battle schema`; `app/Domain/Sahaba/Models/Sahabi.php:190 — relationship`; `app/Http/Controllers/Site/SahabaController.php:271 — collection count`. |
| Related Seerah events | `seerah_participants.participant_id` is untyped/unconstrained, and there is no canonical Sahabi-event pivot/model relationship. | Missing | `database/migrations/2025_10_27_130001_create_seerah_participants_table.php:25 — nullable integer only`; `app/Domain/Sahaba/Models/Sahabi.php:174 — complete relationship block has no Seerah events relation`. |
| Related article/profile media | Scalar `profile_image`, gallery, and related-article fields were deliberately dropped because media moved to MediaLibrary and the article FK was unwired; current related-article needs have no canonical backing. | Partial | `database/migrations/2026_07_02_090000_drop_dead_columns_from_sahaba.php:10 — comments and drops identify these fields`; `app/Http/Controllers/Site/SahabaController.php:310 — article remains null`. |
| Read-time card metadata | Biography text exists and a read time could be derived; no authoritative read-time field is stored. | Partial | `database/migrations/2026_03_09_200001_create_sahabi_translations_table.php:14 — biography exists`; `app/Domain/Sahaba/Models/Sahabi.php:72 — fillable fields contain no read-time value`. |
| Publication/readiness | `is_ready` is per translation, but public query paths fail to use its scope. | Partial | `database/migrations/2026_03_09_200001_create_sahabi_translations_table.php:31 — readiness column`; `app/Domain/Sahaba/Models/SahabiTranslation.php:94 — scope`; `app/Domain/Sahaba/Repositories/SahabiRepository.php:239 — public list does not apply it`. |

#### Enhancement gaps

- `app/Http/Controllers/Site/SahabaController.php:271 — battles are counted after loading`; `app/Http/Controllers/Site/SahabaController.php:305 — narrated count uses a denormalized external claim`; violates the explicit DB-aggregate requirement and `CLAUDE.md:914 — prevent N+1/load only needed data` (SSG-013).
- `app/Http/Controllers/Site/SahabaController.php:205 — stats obtains featured records then performs a PHP collection count`; the explicit task requires `withCount`/DB aggregates (SSG-013).
- `app/Http/Controllers/Site/SahabaController.php:295 — birth/death places are read from base columns even though translated place fields exist`; `app/Domain/Sahaba/Models/SahabiTranslation.php:32 — translation properties include both places`; violates `CLAUDE.md:500 — user-facing content should use locale resources/data` (SSG-021).
- `resources/views/public/sahaba/show.blade.php:159 — progress uses physical transform-origin: left` and `resources/views/public/sahaba/show.blade.php:485 — martyrdom uses border-r-4`; violates `CLAUDE.md:489 — use logical RTL properties` (SSG-026).
- `resources/views/public/sahaba/show.blade.php:279 — secondary text repeatedly uses dark:text-gray-400 rather than the specified dark:text-gray-300`; contrast normalization is covered by SSG-027.
- `app/Domain/Sahaba/Repositories/SahabiRepository.php:194 — search/list paths eager-load relations but most do not constrain the base `sahaba` projection`; violates `CLAUDE.md:914` (SSG-020).

## Prophetic genealogy tree

### `genealogy-enhanced.html` → `GET /genealogy` (+ `GET /genealogy/people`) → `resources/views/public/genealogy/index.blade.php`
**Status:** PARTIAL

**Page parity:** `routes/web.php:103 — GET /genealogy routes to GenealogyController@index`; `routes/web.php:104 — GET /genealogy/people is a throttled JSON load/search endpoint`; `app/Http/Controllers/Site/GenealogyController.php:79 — index builds the payload`; `app/Http/Controllers/Site/GenealogyController.php:272 — people validates and paginates JSON requests`; `resources/views/public/genealogy/index.blade.php:1 — the tree/list view exists`; `resources/views/public/genealogy/_node.blade.php:1 — recursive node partial exists`.

#### Sections & components

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Hero and stats | Present with live total, rendered child count, and bounded generation count rather than hard-coded prototype totals. | Implemented | `../the-last-messenger-ui/genealogy-enhanced.html:274 — prototype hero/stats`; `resources/views/public/genealogy/index.blade.php:77 — implementation`; `app/Http/Controllers/Site/GenealogyController.php:163 — stats derive from paginator/tree`. |
| Tree/list mode controls | Both modes are implemented. | Implemented | `../the-last-messenger-ui/genealogy-enhanced.html:189 — prototype header modes`; `resources/views/public/genealogy/index.blade.php:118 — implementation`. |
| Search modal | Implemented with debounced server search and result navigation, not the prototype's tiny client-only set. | Implemented | `../the-last-messenger-ui/genealogy-enhanced.html:728 — prototype search modal`; `resources/views/public/genealogy/index.blade.php:466 — implementation`; `resources/views/public/genealogy/index.blade.php:690 — fetches the people endpoint`. |
| Zoom controls | Real Alpine/CSS transform zoom is implemented. | Implemented | `../the-last-messenger-ui/genealogy-enhanced.html:338 — prototype controls`; `resources/views/public/genealogy/index.blade.php:138 — implementation`; `resources/views/public/genealogy/index.blade.php:626 — zoom state/methods`. |
| Relationship filter | Spouses, children, and grandchildren filters operate on IDs assembled from the curated tree. | Implemented | `../the-last-messenger-ui/genealogy-enhanced.html:365 — prototype filter`; `resources/views/public/genealogy/index.blade.php:170 — controls`; `resources/views/public/genealogy/index.blade.php:608 — filtering logic`. |
| Info control | Not implemented, but the prototype control itself has no rendered panel/behavior; this is a prototype defect, not an app parity finding. | OK | `../the-last-messenger-ui/genealogy-enhanced.html:392 — button only toggles showInfo`; `../the-last-messenger-ui/genealogy-enhanced.html:827 — state exists`; full-prototype inspection found no consuming panel. |
| Curated spouse/root/descendant tree | Real records, spouse ordering/visibility, child ordering/visibility, and max counts are respected. | Implemented | `resources/views/public/genealogy/index.blade.php:214 — tree section`; `app/Domain/Genealogy/Repositories/PersonRepository.php:195 — descendants query honors child curation/limit`; `app/Domain/Genealogy/Repositories/PersonRepository.php:384 — spouse query honors curation/limit`. |
| Recursive node cards | `_node` recursively renders already-built arrays; it does not issue queries itself. | Implemented | `resources/views/public/genealogy/_node.blade.php:56 — recursion consumes `children` arrays`; the N+1 occurs earlier in `PersonRepository`, not in Blade. |
| Complete people list and load-more | Initial 30 records, load-more, result total, loading, and empty state are present. | Implemented | `resources/views/public/genealogy/index.blade.php:297 — list`; `resources/views/public/genealogy/index.blade.php:341 — load-more`; `app/Http/Controllers/Site/GenealogyController.php:20 — page size`. |
| Person detail modal | Shows name/title, biography, dates, tribe, notes, and profile link. Prototype relation-to-root, virtues, and share action are missing. | Partial | `../the-last-messenger-ui/genealogy-enhanced.html:653 — prototype modal`; `resources/views/public/genealogy/index.blade.php:375 — implementation`; `app/Http/Controllers/Site/GenealogyController.php:303 — mapped fields omit relation/virtues/share metadata`. |
| Modal accessibility/error treatment | Both person/search modals have scroll bounds, but no dialog semantics/focus trap; list/search network failure has no visible retry state. | Partial | `resources/views/public/genealogy/index.blade.php:375 — person modal`; `resources/views/public/genealogy/index.blade.php:466 — search modal`; `resources/views/public/genealogy/index.blade.php:670 — fetch errors only stop loading`. |

#### Features & wiring

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Large-stat dataset reachability | Structurally fixed: the same paginator supplies total and pages; merged tree records are deduplicated into initial data, and all remaining pages are loadable. Actual DB totals require runtime verification. | Implemented | `app/Http/Controllers/Site/GenealogyController.php:108 — one paginator builds payload`; `app/Http/Controllers/Site/GenealogyController.php:136 — tree/spouse/initial rows are merged and deduplicated`; `resources/views/public/genealogy/index.blade.php:670 — paged append`. |
| Server search | `q` and page are validated/clamped, endpoint is throttled, and results are paginated. | Implemented | `routes/web.php:104 — throttle:search is applied`; `app/Http/Controllers/Site/GenealogyController.php:272 — request validation`; `app/Domain/Genealogy/Repositories/PersonRepository.php:494 — DB search`. |
| Load-more | Real endpoint appends and deduplicates rows until `hasMore` is false. | Implemented | `resources/views/public/genealogy/index.blade.php:670 — fetch/append`; `app/Http/Controllers/Site/GenealogyController.php:288 — endpoint emits pagination metadata`. |
| Tree show/hide, order, max children/spouses | All are wired to repository queries and site settings. | Implemented | `app/Http/Controllers/Site/GenealogyController.php:42 — max settings`; `app/Domain/Genealogy/Repositories/PersonRepository.php:195 — child query`; `app/Domain/Genealogy/Repositories/PersonRepository.php:384 — spouse query`. |
| Setting-change freshness | Person/relationship admin changes evict the public payload, but Site Settings changes do not; max-count changes can remain stale for one hour. | Partial | `app/Http/Controllers/Admin/Genealogy/GenealogyController.php:289 — genealogy mutations forget locale keys`; `app/Http/Controllers/Admin/Content/SiteSettingController.php:51 — setting mutations omit that eviction`; `app/Http/Controllers/Site/GenealogyController.php:86 — one-hour cache`. |
| Person share/profile | Profile link is real. Modal share is absent. | Partial | `resources/views/public/genealogy/index.blade.php:443 — profile link`; `../the-last-messenger-ui/genealogy-enhanced.html:713 — prototype share/full-profile actions`; implemented modal has no share control. |

#### Schema

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Canonical people | Genealogy `Person` is now an alias of canonical `Sahabi`; the old people records/relations were consolidated into Sahaba. | Implemented | `app/Domain/Genealogy/Models/Person.php:11 — alias extends Sahabi`; `database/migrations/2026_06_24_100000_consolidate_genealogy_into_sahaba.php:11 — consolidation`; `database/migrations/2026_06_24_100000_consolidate_genealogy_into_sahaba.php:501 — canonical FKs/uniques are enforced`. |
| Parent tree | Father/mother self-FKs support descendants, with admin validation guarding self/cyclic edits. | Implemented | `database/migrations/2026_06_24_100000_consolidate_genealogy_into_sahaba.php:501 — parent FKs`; `app/Http/Requests/Admin/UpdateGenealogyRequest.php:99 — validation rejects cycles`. |
| Spouse/typed relationships | Relationship table supports type, display order, and hide flag. | Implemented | `database/migrations/2025_10_27_140001_create_relationships_table.php:16 — typed edge`; `database/migrations/2026_07_02_140002_add_display_order_and_hide_from_tree_to_relationships_table.php:17 — curation`. |
| Per-person tree visibility/order | Canonical person has `hide_from_tree` and `tree_display_order`. | Implemented | `database/migrations/2026_07_02_140001_add_tree_display_order_and_hide_from_tree_to_sahaba_table.php:19 — columns are added`. |
| Max-count settings | Seeded admin settings back the public controller caps. | Implemented | `database/seeders/SiteSettingsSeeder.php:154 — settings group`; `app/Http/Controllers/Site/GenealogyController.php:48 — clamped reads`. |
| Relation-to-root and structured virtues | Generic typed edges and translated virtues exist, but the modal mapper does not calculate relation-to-root or expose virtues. | Partial | `app/Domain/Sahaba/Models/Sahabi.php:146 — translation fields include virtues`; `app/Http/Controllers/Site/GenealogyController.php:303 — map output omits both`. |
| Readiness | Same per-locale `is_ready` field exists, but all-people/search/tree queries ignore it. | Partial | `app/Domain/Sahaba/Models/SahabiTranslation.php:94 — ready scope`; `app/Domain/Genealogy/Repositories/PersonRepository.php:49 — public paginator has no readiness condition`; `app/Domain/Genealogy/Repositories/PersonRepository.php:494 — search has none`. |
| Tree-query indexes | Parent IDs have individual indexes, but the new curation fields are unindexed; no composite index matches `(father_id, hide_from_tree, tree_display_order)` or relationship spouse curation ordering. | Partial | `database/migrations/2025_10_27_135000_create_sahaba_table.php:97 — parent indexes`; `database/migrations/2026_07_02_140001_add_tree_display_order_and_hide_from_tree_to_sahaba_table.php:19 — curation columns add no index`; `app/Domain/Genealogy/Repositories/PersonRepository.php:195 — combined child predicate/order`; `database/migrations/2026_07_02_140002_add_display_order_and_hide_from_tree_to_relationships_table.php:17 — relationship curation fields add no index`. |

#### Enhancement gaps

- `app/Domain/Genealogy/Repositories/PersonRepository.php:195 — each descendant recursively issues a child query`; at depth 3 and cap 10 this can issue roughly 111 descendant queries before lazy translation access. This violates `CLAUDE.md:914 — eager load to prevent N+1` (SSG-018).
- `app/Domain/Genealogy/Repositories/PersonRepository.php:195 — depth is bounded, but the public builder carries no visited-ID set`; corrupt legacy cycles terminate only because depth reaches zero and can duplicate nodes (SSG-018).
- `resources/views/public/genealogy/_node.blade.php:56 — the Blade recursion itself is query-free and bounded by its prebuilt arrays`; this explicitly corrects the suspected “recursive partial lazy-loads children” pattern.
- `app/Domain/Genealogy/Repositories/PersonRepository.php:49 — list loads full person rows and controller-supplied father/mother/translations though `mapPerson()` does not use parents`; violates `CLAUDE.md:914 — required columns/relations only` (SSG-020).
- `resources/views/public/genealogy/index.blade.php:670 — load-more/search catches leave only `loading=false`, with no visible error or retry message`; violates the requested error-state rule (SSG-024).
- `resources/views/public/genealogy/index.blade.php:375 — person modal lacks dialog semantics/focus management`; `resources/views/public/genealogy/index.blade.php:466 — search modal has the same gap` (SSG-025).

## Findings checklist

- [ ] **SSG-001** [P0] Prevent unpublished Seerah events from surviving in public caches
      - **Module:** Seerah  ·  **Type:** SECURITY
      - **Evidence:** `app/Http/Controllers/Site/SeerahController.php:219 — public event details are cached for 1,800 seconds`; `app/Domain/Seerah/Repositories/SeerahEventRepository.php:200 — slug details also use a direct cache key`; `app/Domain/Seerah/Repositories/SeerahEventRepository.php:186 — bulk status updates only invoke generic version-cache clearing`.
      - **What to do:** Centralize publication-aware cache eviction in `SeerahEventService`/repository. After single update, delete, and `bulkUpdateStatus`, forget every public page/detail key (or version all `public_seerah_*` keys), including arbitrary featured/map limits and slug keys; make a cached detail re-check `status === published` before rendering. Add feature tests for published→draft against `/seerah`, `/seerah/events`, `/seerah/event/{slug}`, and `/api/v1/seerah*`.
      - **Why it matters / trade-offs:** This is a public-content authorization boundary: a draft must disappear immediately, not after a 15–30 minute TTL. Versioned keys avoid broad cache-store scans.

- [ ] **SSG-002** [P0] Enforce locale readiness on every public Sahaba and Genealogy query
      - **Module:** Sahaba / Genealogy  ·  **Type:** SECURITY
      - **Evidence:** `app/Domain/Sahaba/Models/SahabiTranslation.php:94 — `scopeReady()` exists`; `app/Domain/Sahaba/Repositories/SahabiRepository.php:239 — public list query does not apply it`; `app/Domain/Genealogy/Repositories/PersonRepository.php:49 — genealogy paginator does not apply it`; `app/Domain/Sahaba/Repositories/SahabiRepository.php:313 — bulk ready changes do not invalidate direct public caches`.
      - **What to do:** Introduce a locale-aware public scope using `whereHas('translations', locale + is_ready)` and apply it to all list, search, featured, detail, tree, related, stats, Site, and API paths. Evict/version `public_sahaba_*`, direct slug, home, and `public_genealogy_index_v3_*` caches after ready mutations. Add negative public/API tests for an unready current-locale row.
      - **Why it matters / trade-offs:** `is_ready` is the publication control; ignoring it exposes editorially unpublished or incomplete profiles. Locale-aware filtering may intentionally hide a person in one language while retaining it in another.

- [ ] **SSG-003** [P1] Make the single-event layout a validated event attribute
      - **Module:** Seerah  ·  **Type:** SCHEMA
      - **Evidence:** `app/Http/Controllers/Site/SeerahController.php:216 — `?view=timeline` alone chooses the layout`; `app/Domain/Seerah/Models/SeerahEvent.php:72 — no layout field/cast exists`; repository-wide `rg -n "layout_style|event_layout|display_layout" app database resources routes` returned no matches.
      - **What to do:** Add `display_layout` (`article`/`timeline`, non-null default `article`, indexed only if admin filtering needs it) to `seerah_events`; add a backed enum/value validation, model cast/fillable entry, admin FormRequest/control, mapper value, and controller view selection. Treat null/unknown legacy values as article, while optionally allowing an explicitly named preview override only to authorized admins.
      - **Why it matters / trade-offs:** Both designs are shipped, but visitors cannot receive the editor-intended one. A conservative article fallback prevents bad data from breaking the route.

- [ ] **SSG-004** [P1] Add composable era and milestone filters to the Seerah directory
      - **Module:** Seerah  ·  **Type:** MISSING
      - **Evidence:** `../the-last-messenger-ui/seerah-events-enhanced.html:648 — prototype exposes era filtering`; `database/migrations/2026_06_19_100001_add_milestone_id_to_seerah_events_table.php:17 — milestone FK exists`; `app/Http/Controllers/Site/SeerahController.php:134 — handler only reads page, q, and category`.
      - **What to do:** Extend the events repository/service with one published query that accepts whitelisted `era`, `seerah_milestone_id`/slug, type, text, and sort together; populate translated active options; add GET controls/hidden query preservation to `resources/views/public/seerah/events.blade.php`; include normalized filters in cache keys and pagination.
      - **Why it matters / trade-offs:** Users cannot navigate the prototype's primary historical grouping despite complete supporting schema. One composable query avoids an explosion of specialized repository methods.

- [ ] **SSG-005** [P1] Wire the visible Seerah chronology sort
      - **Module:** Seerah  ·  **Type:** PARTIAL
      - **Evidence:** `resources/views/public/seerah/events.blade.php:340 — sort select has only local x-model state`; `app/Http/Controllers/Site/SeerahController.php:134 — no sort input is read`; `../the-last-messenger-ui/seerah-events-enhanced.html:662 — prototype actually reverses result order`.
      - **What to do:** Give the select a `sort` name and form submission/change handler; validate against `asc|desc`; apply only fixed chronological order clauses in the repository; retain it through other filters and pagination; add both directions to the cache key and tests.
      - **Why it matters / trade-offs:** A visible no-op control damages trust. A two-value whitelist avoids dynamic-column SQL risk.

- [ ] **SSG-006** [P1] Allow Seerah search and type filters to work together
      - **Module:** Seerah  ·  **Type:** PARTIAL
      - **Evidence:** `resources/views/public/seerah/events.blade.php:317 — search form preserves category`; `app/Http/Controllers/Site/SeerahController.php:158 — search/type are an `if/elseif`, so the preserved category is ignored`.
      - **What to do:** Replace branch-specific service calls with a filter DTO/query builder that always applies every populated whitelisted filter to a `published()` base query. Test search+type, search+era, and all-filter combinations, including pagination URLs and empty states.
      - **Why it matters / trade-offs:** The UI implies composability but silently broadens results as soon as text is entered.

- [ ] **SSG-007** [P1] Restore the landing's exploration controls or explicitly retire them from the product spec
      - **Module:** Seerah  ·  **Type:** MISSING
      - **Evidence:** `../the-last-messenger-ui/seerah-timeline-enhanced.html:402 — orientation controls`; `../the-last-messenger-ui/seerah-timeline-enhanced.html:486 — search/type controls`; `../the-last-messenger-ui/seerah-timeline-enhanced.html:555 — map`; `resources/views/public/seerah/index.blade.php:280 — implementation only switches journey/articles and milestone`.
      - **What to do:** Product-select the intended hero-journey behavior. For parity, add reusable search/type controls, a coordinate-backed map, era/phase navigation, and vertical/horizontal renderers using the existing published event payload; otherwise document the milestone chapter design as an approved replacement and remove these elements from the prototype acceptance target.
      - **Why it matters / trade-offs:** The current page is polished but is a materially different exploration model. Map/two-orientation work is substantial, so an explicit product decision is preferable to accidental scope.

- [ ] **SSG-008** [P1] Add structured, localized event lessons and references
      - **Module:** Seerah  ·  **Type:** SCHEMA
      - **Evidence:** `../the-last-messenger-ui/seerah-event-single-article-enhanced.html:746 — lessons section`; `../the-last-messenger-ui/seerah-event-single-article-enhanced.html:806 — sources`; `resources/views/public/seerah/event.blade.php:552 — the lessons anchor incorrectly contains characters`; `database/migrations/2026_03_09_200000_create_seerah_event_translations_table.php:14 — neither data set exists`.
      - **What to do:** Add ordered event lesson and source/reference tables with locale translation rows (and structured Quran surah/ayah references where applicable), repository relations and constrained eager loads, admin CRUD/validation, detail mapping, and sections in both event layouts. Rename the participant anchor immediately even if content work is phased.
      - **Why it matters / trade-offs:** These are major educational sections in both prototypes; normalized rows support ordering/citation integrity better than opaque JSON.

- [ ] **SSG-009** [P1] Render event-owned hero media, gallery, and coordinates in both layouts
      - **Module:** Seerah  ·  **Type:** PARTIAL
      - **Evidence:** `database/migrations/2025_10_27_130000_create_seerah_events_table.php:44 — image/gallery/map fields exist`; `resources/views/public/seerah/event.blade.php:376 — article hero is a static asset`; `resources/views/public/seerah/event.blade.php:681 — article map is static`; `resources/views/public/seerah/event-timeline.blade.php:1 — no map/gallery appears in the inspected full view`.
      - **What to do:** Add only required media/location fields to `mapEventDetail()`, validate/sanitize gallery URLs, render responsive image fallbacks, implement an accessible gallery/lightbox, and use coordinates/map URL for the map/directions section. Extract shared event hero/map/gallery components used by both layouts.
      - **Why it matters / trade-offs:** Editors already store this content, but visitors receive generic imagery and lose the timeline prototype's map/gallery.

- [ ] **SSG-010** [P1] Make event engagement actions honest and persistent
      - **Module:** Seerah  ·  **Type:** MISSING
      - **Evidence:** `resources/views/public/seerah/event.blade.php:305 — bookmark is memory-local`; `resources/views/public/seerah/event.blade.php:336 — copy fallback reports success without clipboard write`; `resources/views/public/seerah/event-timeline.blade.php:455 — like is memory-local`; `routes/web.php:93 — no Seerah engagement mutation exists`.
      - **What to do:** At minimum use localStorage and a real clipboard attempt with failure feedback; for authenticated favorites add throttled, CSRF-protected routes, FormRequests, a unique user/event pivot, policies, and optimistic UI rollback. Label a non-persistent reaction accurately if server persistence is out of scope.
      - **Why it matters / trade-offs:** Current controls imply successful saved/copied state that does not exist. Account persistence introduces privacy/auth scope; localStorage is the lower-cost honest baseline.

- [ ] **SSG-011** [P1] Render real kinship on the Sahabi profile
      - **Module:** Sahaba  ·  **Type:** MISSING
      - **Evidence:** `app/Domain/Sahaba/Models/Sahabi.php:174 — father/mother relations exist`; `app/Domain/Sahaba/Models/Sahabi.php:223 — typed genealogy relationships exist`; `app/Http/Controllers/Site/SahabaController.php:83 — show does not load them`; `resources/views/public/sahaba/show.blade.php:327 — facts grid omits them`.
      - **What to do:** Add a constrained, readiness-aware kinship query for parents and curated relationship edges; map translated relation labels and profile URLs; render a compact kinship section with empty handling; cap/eager-load it rather than resolving relatives inside Blade.
      - **Why it matters / trade-offs:** Kinship is a core prototype/profile section and the underlying curated data is already present.

- [ ] **SSG-012** [P1] Normalize Sahabi-to-Seerah participation and expose related events
      - **Module:** Sahaba / Seerah  ·  **Type:** SCHEMA
      - **Evidence:** `database/migrations/2025_10_27_130001_create_seerah_participants_table.php:25 — `participant_id` is an unconstrained integer`; `app/Domain/Sahaba/Models/Sahabi.php:174 — relationship set has no Seerah events`; `app/Http/Controllers/Site/SahabaController.php:83 — no event relation is loaded`.
      - **What to do:** Prefer a nullable `sahabi_id` FK on `seerah_participants` (retaining free-text participants when needed), backfill verified identities, add inverse model relations and indexes, and query a limited published related-event set for the profile. Validate canonical/free-text participant exclusivity in admin requests.
      - **Why it matters / trade-offs:** Without referential identity, related events cannot be reliable and names can drift. Keeping free-text support covers non-Sahaba participants.

- [ ] **SSG-013** [P1] Replace Sahaba PHP/claimed counts with database aggregates
      - **Module:** Sahaba  ·  **Type:** PERF
      - **Evidence:** `app/Http/Controllers/Site/SahabaController.php:271 — battle count is count() on loaded battles`; `app/Http/Controllers/Site/SahabaController.php:205 — featured stats count a fetched collection`; `app/Http/Controllers/Site/SahabaController.php:305 — narrated count reads reported_hadith_count`; `app/Domain/Sahaba/Models/Sahabi.php:426 — a narrator-link count scope already exists`.
      - **What to do:** Fetch detail with `withCount('battles')` and `withNarratorLinkCount()`; use direct aggregate queries for index stats, including featured count; map the live link count as narrated hadiths and label the external reported value separately if retained. Add count consistency tests.
      - **Why it matters / trade-offs:** The displayed count can disagree with linked records and collection counting loads avoidable rows. Keeping the historical claim separately preserves source fidelity.

- [ ] **SSG-014** [P1] Base related Sahaba/content on real relationships
      - **Module:** Sahaba  ·  **Type:** PARTIAL
      - **Evidence:** `app/Http/Controllers/Site/SahabaController.php:92 — related companions are described as a featured set`; `app/Http/Controllers/Site/SahabaController.php:310 — article is always null`; `database/migrations/2026_07_02_090000_drop_dead_columns_from_sahaba.php:10 — the unwired related-article column was removed and scalar media moved to MediaLibrary`.
      - **What to do:** Rank readiness-aware related companions from curated genealogy/category/battle overlap with deterministic limits; connect related articles through an explicit content tagging/pivot strategy instead of restoring a single ad-hoc FK; render a genuine empty state when neither exists.
      - **Why it matters / trade-offs:** “Related” currently means popular, while the related article shell is guaranteed empty. A tagging/pivot model scales beyond one manually chosen article.

- [ ] **SSG-015** [P1] Complete the shared Sahaba card content contract
      - **Module:** Sahaba  ·  **Type:** PARTIAL
      - **Evidence:** `../the-last-messenger-ui/index-enhanced.html:704 — prototype cards include a short biography, read time, and explicit read action`; `resources/views/components/site/sahaba-card.blade.php:1 — shared component exists`; `resources/views/components/site/sahaba-card.blade.php:182 — it ends after portrait/facts without that content`.
      - **What to do:** Extend the controller card mapper with a safely truncated translated biography and derived word-count read time (or remove read time by product decision); add the excerpt/footer CTA to the component while preserving fixed-height carousel layout and accessible whole-card link behavior.
      - **Why it matters / trade-offs:** The extracted component is reusable but visually/informationally thinner than the prototype on every consuming surface.

- [ ] **SSG-016** [P1] Complete Sahaba directory filters and database pagination
      - **Module:** Sahaba  ·  **Type:** PARTIAL
      - **Evidence:** `app/Http/Controllers/Site/SahabaController.php:37 — only all/featured/Ansar/Muhajir are allowed`; `app/Http/Controllers/Site/SahabaController.php:139 — featured mode fetches at most 48 before in-memory pagination`; `resources/views/public/sahaba/index.blade.php:205 — a paginator implies complete reachability`.
      - **What to do:** Add whitelisted tribe, battle, gender, and top-narrator filters backed by composable repository scopes; convert featured mode to a `LengthAwarePaginator` query without a fixed pre-cap; retain all GET inputs and page size; use DB `withCount` only where count filtering/sorting requires it.
      - **Why it matters / trade-offs:** Users cannot perform the expected discovery tasks, and records beyond the hidden featured cap are unreachable.

- [ ] **SSG-017** [P1] Complete the Genealogy person modal
      - **Module:** Genealogy  ·  **Type:** MISSING
      - **Evidence:** `../the-last-messenger-ui/genealogy-enhanced.html:653 — prototype includes relation, biography/virtues, share, and full profile`; `app/Http/Controllers/Site/GenealogyController.php:303 — mapped person omits relation and virtues`; `resources/views/public/genealogy/index.blade.php:443 — only profile navigation is implemented`.
      - **What to do:** Map translated virtues and a bounded, precomputed relationship-to-root label/path; render both with empty states; add native share plus clipboard failure feedback; preserve the existing real profile URL.
      - **Why it matters / trade-offs:** The modal identifies a person but omits the genealogical explanation that gives the tree meaning. Computing paths should be batched/cached, not recursive per modal open.

- [ ] **SSG-018** [P2] Batch-build the Genealogy tree and add render-time cycle protection
      - **Module:** Genealogy  ·  **Type:** PERF
      - **Evidence:** `app/Domain/Genealogy/Repositories/PersonRepository.php:195 — recursive descendant construction runs a child query at each node`; `resources/views/public/genealogy/_node.blade.php:56 — Blade recursively consumes the resulting arrays without a visited guard`; `app/Http/Requests/Admin/UpdateGenealogyRequest.php:99 — only admin updates receive explicit cycle validation`.
      - **What to do:** Fetch the bounded descendant candidate set and required locale translations in bulk, index by `father_id`, then build arrays in memory while carrying a visited-ID set and respecting display order/hide/max/depth. Log/skip an encountered cycle and add query-count plus corrupt-cycle tests.
      - **Why it matters / trade-offs:** At cap 10/depth 3, the current approach can issue roughly 111 child queries before translation lazy loads. Bulk loading uses more memory predictably but is far cheaper and safer.

- [ ] **SSG-019** [P2] Make all public Seerah counts publication-correct
      - **Module:** Seerah  ·  **Type:** PARTIAL
      - **Evidence:** `app/Domain/Seerah/Repositories/SeerahEventRepository.php:289 — getIndexStats() counts all events and category rows without the published scope`; `app/Http/Controllers/Site/SeerahController.php:176 — those counts feed public category pills`.
      - **What to do:** Start every public aggregate from `SeerahEvent::query()->published()`, join the DB event-type table for current slugs/names, and add tests where draft rows do not change totals/pill counts. Keep separate admin aggregates if drafts must be visible there.
      - **Why it matters / trade-offs:** Even without opening a draft, public metadata currently reveals/overstates unpublished inventory and creates zero-result pill mismatches.

- [ ] **SSG-020** [P2] Constrain public list projections and eager loads
      - **Module:** Seerah / Sahaba / Genealogy  ·  **Type:** PERF
      - **Evidence:** `app/Domain/Seerah/Repositories/SeerahEventRepository.php:222 — list relation columns are selected but base event rows remain wildcard projections`; `app/Domain/Seerah/Repositories/SeerahEventRepository.php:238 — detail loads an unused Article graph`; `app/Domain/Sahaba/Repositories/SahabiRepository.php:194 — search loads full base records`; `app/Domain/Genealogy/Repositories/PersonRepository.php:49 — list loads controller-requested parents that mapPerson() does not use`.
      - **What to do:** Define mapper-specific base `select([...])` projections including required keys/FKs, remove unused detail relations, and constrain translated/related columns consistently. Add query/log assertions or profiling snapshots so later mapper additions fail visibly rather than causing lazy loads.
      - **Why it matters / trade-offs:** The public high-cardinality paths transfer and hydrate substantially more data than rendered. Explicit projections require coordination when a mapper gains a field.

- [ ] **SSG-021** [P2] Read localized content fields instead of untranslated base values
      - **Module:** Sahaba / Seerah  ·  **Type:** I18N
      - **Evidence:** `app/Http/Controllers/Site/SahabaController.php:295 — birth/death places are read from the base model`; `app/Domain/Sahaba/Models/SahabiTranslation.php:32 — translated place fields exist`; `database/migrations/2026_06_10_100000_add_taxonomy_and_statistics_to_seerah_events_table.php:38 — statistics remains base JSON`; `database/migrations/2025_10_27_130001_create_seerah_participants_table.php:22 — participant role is base content`.
      - **What to do:** Map Sahaba places through locale translations/fallback; audit user-visible tribe/clan/battle labels, Seerah location/stat labels, and participant roles; move genuinely linguistic content into translation rows or reference tables with translations. Preserve numeric statistics as locale-neutral values and translate only labels.
      - **Why it matters / trade-offs:** The five-locale UI can still emit Arabic/base-language domain content inside translated pages. Not every proper noun needs duplication, so migrate selectively.

- [ ] **SSG-022** [P2] Add composite indexes for actual public timeline/tree access paths
      - **Module:** Seerah / Genealogy  ·  **Type:** SCHEMA
      - **Evidence:** `database/migrations/2026_03_17_000001_add_composite_performance_indexes.php:63 — Seerah chronology index omits status`; `app/Domain/Seerah/Repositories/SeerahEventRepository.php:132 — query filters published then orders Hijri date`; `app/Domain/Genealogy/Repositories/PersonRepository.php:195 — children filter/order combines parent, hidden flag, and order`; `database/migrations/2026_07_02_140001_add_tree_display_order_and_hide_from_tree_to_sahaba_table.php:19 — curation fields are added without an index`.
      - **What to do:** Verify with production-like `EXPLAIN`; likely add `seerah_events(status,hijri_year,hijri_month,hijri_day)`, `sahaba(father_id,hide_from_tree,tree_display_order)`, and a relationship index aligned to `(relationship_type,hide_from_tree,display_order,person_id|related_person_id)`. Avoid redundant indexes after checking existing FK/unique coverage.
      - **Why it matters / trade-offs:** These are dominant WHERE/ORDER paths. Composite indexes speed reads but increase write/storage cost, so `EXPLAIN` should decide exact column order.

- [ ] **SSG-023** [P2] Evict the Genealogy page payload when max-count settings change
      - **Module:** Genealogy  ·  **Type:** ENHANCEMENT
      - **Evidence:** `app/Http/Controllers/Site/GenealogyController.php:86 — full payload is cached for one hour`; `app/Http/Controllers/Admin/Genealogy/GenealogyController.php:289 — person mutations evict it`; `app/Http/Controllers/Admin/Content/SiteSettingController.php:51 — site-setting mutations do not`.
      - **What to do:** In the setting service/observer, detect `genealogy_tree_max_children_per_level` and `genealogy_tree_max_spouses_shown`, then forget/version all locale `public_genealogy_index_v3_*` keys after create/update/delete/reset. Add a feature test that changes each setting and immediately observes the new cap.
      - **Why it matters / trade-offs:** Admin curation appears ineffective for up to an hour, causing support confusion. Central service invalidation covers every mutation surface.

- [ ] **SSG-024** [P2] Show recoverable Genealogy load/search errors
      - **Module:** Genealogy  ·  **Type:** ENHANCEMENT
      - **Evidence:** `resources/views/public/genealogy/index.blade.php:670 — load-more fetch catches only reset loading state`; `resources/views/public/genealogy/index.blade.php:690 — search fetch has no user-facing error/retry state`.
      - **What to do:** Add separate translated list/search error state, live-region messaging, retry buttons that retain page/query, and response-status handling; do not convert a failed search into a false “no results” state.
      - **Why it matters / trade-offs:** Network/server failures are currently silent and indistinguishable from no data.

- [ ] **SSG-025** [P2] Add accessible dialog behavior to event and Genealogy modals
      - **Module:** Seerah / Genealogy  ·  **Type:** A11Y
      - **Evidence:** `resources/views/public/seerah/event-timeline.blade.php:496 — participant modal has role/aria but no accessible-name binding, Escape close, or focus management`; `resources/views/public/genealogy/index.blade.php:375 — person modal lacks role/aria/focus management`; `resources/views/public/genealogy/index.blade.php:466 — search modal has the same gap`.
      - **What to do:** Extract/use an accessible modal primitive with `role="dialog"`, `aria-modal`, `aria-labelledby`, initial focus, focus trap, Escape/backdrop close, body-scroll locking, and focus return. Preserve existing `max-h`/overflow behavior and test keyboard-only flows.
      - **Why it matters / trade-offs:** Visual overlays currently strand or leak keyboard/screen-reader focus into background content.

- [ ] **SSG-026** [P2] Replace physical RTL styling and directional motion
      - **Module:** Seerah / Sahaba  ·  **Type:** RTL
      - **Evidence:** `resources/views/public/sahaba/show.blade.php:159 — progress origin is physically left`; `resources/views/public/sahaba/show.blade.php:485 — martyrdom accent is border-r-4`; `resources/views/public/seerah/events.blade.php:490 — hover translation is hard-coded negative X`.
      - **What to do:** Use locale/direction-aware transform origin, logical `border-e-*`/`border-s-*`, and direction-aware translate utilities/conditional classes; exercise Arabic and at least one LTR locale in visual tests.
      - **Why it matters / trade-offs:** These details animate/accent the wrong edge in one writing direction, contrary to `CLAUDE.md:489`.

- [ ] **SSG-027** [P2] Normalize mobile form sizing and dark secondary-text contrast
      - **Module:** Seerah / Sahaba  ·  **Type:** ENHANCEMENT
      - **Evidence:** `resources/views/public/seerah/events.blade.php:323 — search input lacks text-base sm:text-sm`; `resources/views/public/seerah/events.blade.php:459 — secondary text uses dark:text-gray-400`; `resources/views/public/sahaba/show.blade.php:279 — profile secondary text repeats the same dark tone`.
      - **What to do:** Apply `text-base sm:text-sm` to mobile inputs/selects, audit action-row wrapping, and normalize secondary text to the specified `text-gray-600 dark:text-gray-300` tokens while checking contrast in both themes.
      - **Why it matters / trade-offs:** Correct mobile font sizing prevents iOS zoom and the stronger dark token improves readability; tokenizing avoids one-off class churn.

- [ ] **SSG-028** [P2] Validate and cap public Seerah/Sahaba API pagination
      - **Module:** Seerah / Sahaba  ·  **Type:** SECURITY
      - **Evidence:** `app/Http/Controllers/Api/SeerahEventController.php:28 — public list reads caller pagination inputs directly`; `app/Http/Controllers/Api/SahabiController.php:28 — Sahaba list/featured endpoints accept caller limits`; `routes/api.php:100 — public Seerah group`; `routes/api.php:113 — public Sahaba group`.
      - **What to do:** Use dedicated query FormRequests (or explicit validation for GET) to clamp page/per-page/limit, whitelist sort/filter values, bound search length, and apply appropriate throttles to search-heavy endpoints. Add boundary/invalid-input tests.
      - **Why it matters / trade-offs:** Unbounded limits can amplify DB/memory work; validation also prevents future dynamic-sort SQL mistakes. Avoid overly tight caps that break legitimate clients.

- [ ] **SSG-029** [P3] Remove the fixed 500-event ceiling from the landing's articles mode
      - **Module:** Seerah  ·  **Type:** ENHANCEMENT
      - **Evidence:** `app/Http/Controllers/Site/SeerahController.php:52 — landing calls getAllEvents(perPage: 500)`; `resources/views/public/seerah/index.blade.php:211 — articles mode renders only that supplied set`.
      - **What to do:** Either paginate/load-more the articles mode through the existing events directory/query endpoint or explicitly display a limited “latest/highlighted” set with a total and view-all CTA; do not imply it is the complete corpus.
      - **Why it matters / trade-offs:** It is not currently user-visible at the documented small corpus, but becomes a silent completeness bug as content grows.

- [ ] **SSG-030** [P3] Remove the obsolete Seerah EventType enum and correct the module map
      - **Module:** Seerah  ·  **Type:** ENHANCEMENT
      - **Evidence:** `app/Domain/Seerah/Enums/EventType.php:11 — a legacy backed enum remains`; `database/migrations/2026_06_18_100001_seed_event_types_and_link_seerah_events.php:68 — runtime categorization moved to a DB FK`; a repository `rg -n "EventType" app routes database resources tests` found no use outside the enum file/doc references.
      - **What to do:** Delete the unused enum after a final static/test reference check, update `CLAUDE.md` and any generated docs to name the DB-managed type relation, and keep request validation against active type slugs/IDs.
      - **Why it matters / trade-offs:** Dead domain vocabulary invites future code to bypass admin-managed types. Removal is low risk because static search found no caller.

- [ ] **SSG-031** [P3] Restore the events-directory scroll-to-top affordance
      - **Module:** Seerah  ·  **Type:** MISSING
      - **Evidence:** `../the-last-messenger-ui/seerah-events-enhanced.html:898 — prototype renders a floating scroll-to-top control`; `resources/views/public/seerah/events.blade.php:1 — full-view inspection found no equivalent`.
      - **What to do:** Add a progressively enhanced, translated, keyboard-focusable scroll-to-top button that appears after meaningful scroll distance, respects reduced motion, and does not overlap mobile pagination/actions.
      - **Why it matters / trade-offs:** This is convenience polish on a potentially long paginated page, not a blocker; native scrolling remains functional.

- [ ] **SSG-032** [P3] Add the timeline layout's print action and print stylesheet
      - **Module:** Seerah  ·  **Type:** MISSING
      - **Evidence:** `../the-last-messenger-ui/seerah-event-single-timeline-enhanced.html:1107 — prototype exposes print`; `resources/views/public/seerah/event-timeline.blade.php:444 — implemented FAB group contains article, like, and share only`.
      - **What to do:** Add a translated print control calling `window.print()` and print CSS that hides FAB/navigation/modals, expands useful timeline content, preserves source URLs, and avoids splitting cards where practical.
      - **Why it matters / trade-offs:** Educational long-form content is commonly saved/printed, but this is a polish feature and browser print remains manually available.

## Doc corrections

- `CLAUDE.md:381 — describes Enums for event categorization`; `database/migrations/2026_06_18_100001_seed_event_types_and_link_seerah_events.php:68 — replaced the enum with a DB FK`; `app/Domain/Seerah/Enums/EventType.php:11 — a dead legacy enum file still exists`. The standards/module map should say DB-managed event types and the unused enum should be handled in a separate code task.
- `CLAUDE.md:405 — lists Person as a separate Genealogy aggregate`; `app/Domain/Genealogy/Models/Person.php:11 — it is now only an alias of Sahabi` after consolidation.
- `../WEB_APP_MODULES_ANALYSIS.md:372 — says Seerah has no rich inner timeline`; `database/migrations/2026_06_10_100001_create_seerah_event_timeline_items_table.php:21 — timeline schema now exists`; `resources/views/public/seerah/event.blade.php:491 — renders it`. The lessons/sources concern in the same document remains valid.
- `../WEB_APP_MODULES_ANALYSIS.md:395 — reports the Sahaba timeline/martyrdom implementation missing`; `resources/views/public/sahaba/show.blade.php:472 — martyrdom exists`; `resources/views/public/sahaba/show.blade.php:502 — timeline exists`. Its related-content concern remains valid.
- `../WEB_APP_MODULES_ANALYSIS.md:422 — says Genealogy has no usable public endpoint/tree linkage`; `routes/web.php:103 — tree and people routes exist`; `resources/views/public/genealogy/index.blade.php:670 — load-more is wired`. There is still no `/api/v1/genealogy` group; this review did not infer that one is required.
- `../TASK_BOARD.md:35 — marks the second event style done`; both views do exist, but `app/Http/Controllers/Site/SeerahController.php:216 — selection remains query-driven`, so the data-driven acceptance criterion is not closed.
- `../DESIGN_REVIEW.md:74 — claims all public inputs use mobile-safe sizing`; `../DESIGN_REVIEW.md:78 — claims logical RTL throughout`; `resources/views/public/seerah/events.blade.php:323 — input sizing contradicts the first claim`; `resources/views/public/sahaba/show.blade.php:485 — physical border contradicts the second`.
- `MODULES-AND-PAGES-INVENTORY.md:181 — documents event paths including /seerah/events/{slug} and /seerah/timeline`; `routes/web.php:93 — the actual routes are /seerah, /seerah/events, and singular /seerah/event/{slug} only`.
- `MODULES-AND-PAGES-INVENTORY.md:219 — documents obsolete Genealogy/admin/API surfaces`; `routes/web.php:102 — actual public surfaces are /genealogy and /genealogy/people`; canonical admin routing should be re-inventoried from `routes/admin.php` and `routes/admin/`.
- `MODULES-AND-PAGES-INVENTORY.md:318 — documents /api/v1/sahaba/{id}/hadiths`; `routes/api.php:113 — the actual Sahaba API block has no such route`.
- `../PROJECT_PROGRESS.md:22 — hard-codes old dataset counts and calls these areas production-ready`; `app/Http/Controllers/Site/GenealogyController.php:20 — current code explicitly targets a 10k+ people corpus`, while the findings above show functional/publication gaps. Live counts should be generated, dated, or removed from status prose.

## Open questions

- Actual record totals, readiness distribution, and whether every currently published/ready record is reachable cannot be proven without querying the running database; no DB, migrations, or seeders were run. The Genealogy code path is structurally paginated and its displayed total comes from the same paginator.
- Cache-driver capabilities and currently resident keys were not inspected. The P0/P2 cache findings follow deterministically from explicit key construction, TTLs, and mutation paths, but runtime tests should measure each configured driver.
- Visual behavior, dark-mode contrast ratios, responsive breakpoints, keyboard focus, map-provider availability, and Alpine console errors need a running-browser pass; this task was intentionally static.
- Editorial intent is needed to decide whether Seerah's milestone chapter landing is an approved redesign replacing the prototype's map/orientation/filter experience or an incomplete implementation.
- Product policy is needed for bookmarks/likes: browser-local persistence versus authenticated cross-device favorites materially changes schema, privacy, authorization, and rate-limit scope.
- Production query plans/cardinality are needed before finalizing the composite indexes in SSG-022; the recommended order follows the statically observed predicates and ordering.
- No assigned prototype received a thinner pass; all six prototype files, the homepage carousel/card surface, routes, controllers, relevant views/components, domain paths, and matching migrations were read.
