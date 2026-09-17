# Feature Specification: Seerah Module & Schema Refactor

**Feature Branch**: `005-seerah-schema-refactor`
**Created**: 2026-09-17
**Status**: Draft
**Input**: User description: "need to make full refactor to seerah module and database schema (seerah_milestones, seerah_milestone_translation, seerah_event_types, seerah_events, seerah_event_translations, seerah_events_gallery, seerah_events_media) — remove current migrations from table and update old, don't create new migrations — trace the impacted modules, pages, frontend and all to meet the below schema"

## Context

The Seerah module grew by accretion: eighteen schema changes layered on a first
draft, leaving the event record carrying fields that overlap, contradict or no
longer serve a purpose. Measured against the current code and local data:

- **The same fact is stored two or three ways.** An event's date lives in a
  free-text Hijri string, three Hijri number fields, a Gregorian date and a
  separate Gregorian year. Its place lives in a free-text field, a pair of
  coordinates and a link to the shared Locations list. Its picture lives in a
  path field *and* a half-wired media-library collection, with a fallback
  between them.
- **Editorial flags overlap.** `importance`, `is_pivotal`, `is_featured` and
  `phase` all try to say "this event matters" or "where it sits"; milestones and
  `era` already say where it sits.
- **Naming is inconsistent** between the three Seerah record families
  (`order_index` vs `display_order`; three separate look-and-feel fields on
  milestones and types; a whole translation table for a one-word type name).
- **Image fields are empty everywhere** (0 of 125 local events have a featured
  image or gallery), so the image model can be replaced without migrating files.

The owner has specified a target shape for the module. This feature brings the
stored data, the admin portal, the public Seerah pages, the public API and every
other module that touches Seerah into line with that shape — with no leftover
reference to a removed field anywhere.

### Target shape (as specified by the owner)

| Record | Fields |
|---|---|
| **Milestone** | id, slug, order, look-and-feel metadata (icon, colour, gradient), Hijri year from/to, Gregorian from/to, is_active, timestamps |
| **Milestone translation** | milestone link, locale, title, subtitle, description, is_ready, timestamps |
| **Event type** | id, slug, look-and-feel metadata (icon, colour, gradient), names per language held on the type itself, is_active |
| **Event** | id, slug, event type link, milestone link, order, thumbnail image, cover image, Hijri year / month / day, Gregorian date, location link (shared Locations), status (fixed list defined by the application), era (fixed list defined by the application), statistics, timestamps |
| **Event translation** | id, event link, locale, title, description, content, dispute note, is_ready, metadata (repeatable key/value pairs), sources |
| **Event gallery** | same as the article gallery: event link, image, originating media-library item, caption per language, order, timestamps |
| **Event media** | same as the article media link: event link, media-library item link, timestamps; one item attaches to an event once |

### What leaves the module

Removed from events: `importance`, `phase`, `is_pivotal`, `is_featured`,
`views_count`, related-article link, free-text Hijri date, separate Gregorian
year, free-text location, own coordinates, single featured image, inline gallery
list. Removed from milestones: free-text Hijri labels, separate icon / colour /
gradient fields (folded into metadata). Removed from event types: display order,
the separate translations table, separate icon / colour / gradient fields.
Removed from event translations: `lessons`. Removed entirely: participants (and
their translations) and event timeline items (and their translations), with
every screen, page section, API field, command and test built on them.

### Decisions (2026-09-17)

- **Q1 → drop** participants, timeline items and lessons.
- **Q2 → mirror articles**: event gallery and event media copy the article
  gallery and article media records and their relations one-for-one.
- **Q3 → start fresh**: rewrite the schema definitions in place and adapt the
  code and templates. Existing Seerah data is not carried over; losing seeded
  data is accepted.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Editor manages events on the new shape (Priority: P1)

A content editor opens the admin portal, creates or edits a Seerah event, and
sees only the fields that exist in the target shape: type, milestone, order,
thumbnail, cover, Hijri year/month/day, Gregorian date, a place picked from the
shared Locations list, status, era, statistics, and — per language — title,
description, content, dispute note, sources, readiness, and a repeatable
key/value list for extra details.

**Why this priority**: Content is authored exclusively through the admin portal.
If the editor's form and the stored shape disagree, every save either fails or
silently drops data — the module's historic failure mode.

**Independent Test**: Create an event with every field filled in two languages,
reopen it, and confirm every value round-trips unchanged; confirm no removed
field appears anywhere in the list, create, edit or detail screens.

**Acceptance Scenarios**:

1. **Given** the event form, **When** an editor saves an event with every target
   field filled, **Then** reopening the event shows every value exactly as entered.
2. **Given** an event translation, **When** the editor adds, reorders and removes
   key/value rows, **Then** the saved list matches what was on screen, per language.
3. **Given** an existing event, **When** the editor saves without touching
   sources, the dispute note or key/value rows, **Then** their existing values
   are preserved — an untouched field is never blanked.
4. **Given** the event form, **When** an editor uploads a thumbnail and a cover,
   **Then** both are stored independently and either can be replaced or removed
   without affecting the other.
5. **Given** the event list, **When** an editor filters, sorts and bulk-changes
   status, **Then** everything works with no reference to importance, pivotal,
   featured or view counts.
6. **Given** the dispute note — stored today but editable nowhere — **When** an
   editor opens an event, **Then** the note is editable per language.

---

### User Story 2 - Visitor reads the Seerah with nothing broken (Priority: P1)

A visitor browses the Seerah journey, a milestone's events, the events directory
and an event page. Everything renders from the new shape: dates from the Hijri
numbers and Gregorian date, the place and map from the linked location, the
cover on the event page and the thumbnail on cards, and the dispute note where
one exists.

**Why this priority**: The public Seerah pages are the product. A schema
refactor that leaves a page erroring, or quietly showing an empty section, is a
regression the visitor sees first.

**Independent Test**: Walk every public Seerah page in Arabic and English on a
populated dataset; no page errors, no section renders empty where data exists,
and no control refers to a removed concept.

**Acceptance Scenarios**:

1. **Given** the events directory, **When** a visitor opens the filters, **Then**
   the importance and pivotal filters are gone and the remaining filters
   (milestone, type, era, disputed, search) behave as before.
2. **Given** an event linked to a location with coordinates, **When** the visitor
   opens it, **Then** the map renders from that location.
3. **Given** an event with a dispute note in the visitor's language, **When** the
   page renders, **Then** the note is shown; with no note, no empty block appears.
4. **Given** an event with key/value details in the visitor's language, **When**
   the page renders, **Then** they appear as a labelled list.
5. **Given** an event with no thumbnail or cover, **When** it appears on a card
   or its own page, **Then** the existing placeholder treatment is used.
6. **Given** an event, **When** the page suggests related events, **Then** the
   suggestions come from the same milestone/era rather than the removed `phase`
   and `featured` fields.
7. **Given** the journey page, **When** milestones render, **Then** their order,
   icon, colour, gradient and Hijri/Gregorian ranges all display, with ranges
   derived from the year numbers.

---

### User Story 3 - Editor manages milestones and event types (Priority: P2)

An editor maintains the fifteen-or-so milestones and eleven event types on their
simplified shape: one look-and-feel group (icon, colour, gradient), one order
field on milestones, and type names entered per language directly on the type.

**Why this priority**: These are small, rarely edited lookups, but every event
and every public page depends on them rendering correctly.

**Independent Test**: Edit a milestone's icon, colour, gradient, order and year
range, and a type's names in three languages; confirm the journey page and event
cards reflect each change.

**Acceptance Scenarios**:

1. **Given** a milestone, **When** the editor sets icon, colour and gradient,
   **Then** the public journey page shows them.
2. **Given** an event type, **When** the editor enters its name in several
   languages, **Then** each public page shows the name in the visitor's language,
   falling back to Arabic.
3. **Given** an event type in use by events, **When** an editor tries to delete
   it, **Then** the deletion is refused with an explanation, as today.
4. **Given** an inactive milestone or type, **When** public pages render,
   **Then** it is not offered as a filter, as today.

---

### User Story 4 - Editor attaches a gallery and media to an event (Priority: P2)

An editor adds an ordered, captioned set of images to an event's gallery and
attaches media-library items to it, using the same controls and behaviour the
article form already has. Visitors see the gallery and media on the event page.

**Why this priority**: Replaces the inline image list with proper records. Every
event currently has no images, so nothing is lost by sequencing this after the
core shape.

**Independent Test**: Add five gallery images to an event, reorder them, remove
one; attach media; confirm the public event page reflects each change.

**Acceptance Scenarios**:

1. **Given** an event, **When** the editor adds, reorders and removes gallery
   images, **Then** the public page shows exactly that set in that order.
2. **Given** an event with no gallery or media, **When** the page renders,
   **Then** neither section appears.
3. **Given** an event that is deleted, **When** deletion completes, **Then** its
   gallery and media records are removed with it.
4. **Given** an event, **When** the editor attaches items from the media
   library, **Then** they are linked to the event exactly as they are for an
   article, and the same item cannot be attached twice.
5. **Given** a gallery image picked from the media library, **When** the event is
   reopened for editing, **Then** the picker shows the same library item
   selected — the round trip is lossless, as it is for articles.

---

### User Story 5 - Every other part of the site keeps working (Priority: P1)

Search, favourites, topics, the home page, article embeds of Seerah events, the
Locations admin (usage counts, unmapped filter, delete guard), import/export,
bulk operations, analytics, the admin dashboard and the public API all continue
to work against the new shape.

**Why this priority**: Seerah is referenced from more than twenty places outside
its own module. A leftover reference to a removed field fails only when that
line runs — the exact class of defect this codebase has been burned by.

**Independent Test**: The full automated suite passes; a search of the codebase
for every removed field name returns no live reference in Seerah contexts.

**Acceptance Scenarios**:

1. **Given** site search, **When** a visitor searches Seerah content or filters
   results, **Then** results return with no reference to removed fields.
2. **Given** an article that embeds a Seerah event, **When** it renders, **Then**
   the embed shows the event's title, date and thumbnail.
3. **Given** the Locations admin, **When** an editor views a location's usage or
   tries to delete one in use by events, **Then** counts and the guard still work.
4. **Given** the public API, **When** a client requests events, **Then** the
   response carries the new shape only, and endpoints built solely on removed
   fields (featured, by-Gregorian-year) are retired or re-based.
5. **Given** the admin dashboard, **When** it loads, **Then** the Seerah tile
   shows real counts and no "views" figure sourced from a removed field.
6. **Given** export and import of events, **When** run, **Then** the file columns
   match the new shape.

---

### User Story 6 - The module starts fresh on the new shape (Priority: P1)

A developer sets the project up from scratch and gets the target shape directly,
with the event types seeded and ready; editors author milestones and events
through the admin portal. No legacy Seerah data is converted.

**Why this priority**: The owner chose a clean start over a conversion. The
value is a schema history that says what the module *is*, not how it got there.

**Independent Test**: Build a from-scratch environment, run the seed step, open
the admin portal and the public Seerah pages: event types exist, the milestone
and event lists are empty, and every page renders its empty state without error.

**Acceptance Scenarios**:

1. **Given** a brand-new environment, **When** it is set up from scratch,
   **Then** it arrives at the target shape directly, with no intermediate legacy
   shape created and then altered, and no participants or timeline-item records.
2. **Given** the seed step, **When** it runs, **Then** it succeeds on the new
   shape; seed content that described the old shape is removed rather than
   converted.
3. **Given** a Seerah module with no events yet, **When** a visitor opens any
   public Seerah page, **Then** a proper empty state renders — no error, no
   broken layout.
4. **Given** an environment that already holds Seerah data, **When** the refactor
   is rolled out there, **Then** its Seerah records are rebuilt empty on the new
   shape after a backup copy has been taken.

### Edge Cases

- An event has an unknown day or month: Hijri month/day stay empty and the date
  renders from whatever parts exist ("year 2 AH"), never as a fabricated full date.
- A milestone's free-text Hijri labels ("قبل البعثة") cannot be derived from year
  numbers for pre-Hijrah ranges; negative/"before Hijrah" years must render
  sensibly from numbers alone.
- An event type has a name in Arabic only: other languages fall back to Arabic.
- Saving an event form with empty repeatable rows must not blank existing
  sources or key/value details (known historic trap).
- Two events in one milestone share the same order value: a stable secondary
  ordering (date, then id) keeps the public sequence deterministic.
- Favourites, scheduled-content entries and article embeds that point at an event
  which no longer exists after the fresh start must degrade gracefully (hidden or
  shown as unavailable), never error.
- The same media-library item is attached to an event twice: refused.
- A media-library item used in an event's gallery or media is deleted from the
  library: the event's link is removed with it, as for articles.
- Cached public pages rendered from the old shape must be invalidated on rollout
  by targeted invalidation, not a global cache wipe.

## Requirements *(mandatory)*

### Functional Requirements

**Shape**

- **FR-001**: The stored Seerah records MUST match the target shape in the
  Context section exactly — no removed field may remain, and no field outside
  the target shape may be added without being listed in this spec.
- **FR-002**: Obvious typos in the owner's field list are normalised:
  `substitle` → subtitle, `gregrian_to` → Gregorian-to, `seerah_evnets_media` →
  event media. Link-field names follow the owner's naming (`sm_id`, `event_id`,
  `event_type`).
- **FR-003**: Event status and era MUST be constrained by fixed lists owned by
  the application, not by the data store, so a list can change without a
  schema change.
- **FR-004**: The schema definition history MUST be rewritten in place: the
  existing Seerah schema definitions are consolidated/edited to produce the
  target shape directly, superseded ones are removed, and no new additive
  schema-change step is introduced. The shared Locations definitions keep
  working with the rewritten Seerah ones (the event → location link remains;
  the free-text event location it sat beside does not).
- **FR-005**: A fresh environment set up from scratch MUST arrive at the target
  shape, and the full automated test suite MUST run against it.

**Admin portal**

- **FR-006**: Event list, create, edit and detail screens MUST present exactly
  the target fields, Arabic by default, RTL/LTR correct, following the existing
  admin design system and form components.
- **FR-007**: Thumbnail and cover MUST each use the existing admin image input
  (upload or pick from the media library) and be independently replaceable and
  removable.
- **FR-008**: Each event translation MUST offer a repeatable key/value editor
  (add, remove, reorder) and a sources editor, per language; untouched
  repeatable sections MUST preserve their stored values on save.
- **FR-009**: The dispute note MUST be editable per language in the event form.
- **FR-010**: Milestone screens MUST edit order, the look-and-feel group, Hijri
  from/to years, Gregorian from/to and active state; the free-text Hijri label
  inputs are removed and range labels are derived from the numbers.
- **FR-011**: Event-type screens MUST edit slug, look-and-feel group, names per
  supported language, and active state; the display-order input is removed and
  types list in a stable alphabetical order by localized name.
- **FR-012**: The in-use guard on deleting an event type, and bulk delete /
  bulk status / toggle-active actions, MUST keep working.
- **FR-013**: The place of an event MUST be chosen only from the shared
  Locations list.
- **FR-014**: Participants, timeline items and lessons MUST be removed entirely:
  their admin form sections and validation, their public event-page sections
  (characters, timeline, lessons) and navigation anchors, the timeline-count
  badge on the admin list, their API fields, the participant-linking command,
  their fixed lists, their translations in all five languages, and their tests.

**Public site**

- **FR-015**: Every public Seerah page (journey, milestone events, events
  directory, event page in both layouts, event navigator, articles feed) MUST
  render from the new shape with no error and no orphaned control or label.
- **FR-016**: The importance filter, pivotal filter/chips/counters, importance
  badges and stat, and any view-count display MUST be removed from public pages,
  together with their translations in all five languages.
- **FR-017**: Event pages MUST show the dispute note and the key/value details
  when present in the visitor's language.
- **FR-018**: Event dates MUST be composed from Hijri year/month/day and the
  Gregorian date; wherever a Gregorian year alone was shown or filtered on, it
  is derived from the Gregorian date.
- **FR-019**: Event maps and place names MUST come solely from the linked
  location.
- **FR-020**: Related-event suggestions MUST be based on shared milestone, then
  era, in chronological proximity.
- **FR-021**: Event pages MUST stop recording a view count on each visit.
- **FR-022**: Gallery and media sections MUST render from the new gallery and
  media records, and be absent when empty.
- **FR-022a**: Event gallery and event media MUST mirror the article gallery and
  article media one-for-one — same fields, same relations, same admin controls,
  same picker round-trip, same cascade on deleting the parent or the library
  item — reusing the article implementation rather than introducing a variant.

**Rest of the platform**

- **FR-023**: Search (indexing, filterable fields, results), favourites, topics,
  home page cards, article embeds and the article ↔ event relationship,
  scheduled content, analytics, dashboard stats, bulk operations, import, export,
  the Locations usage registry / backfill / delete guard, and guest page caching
  MUST all work against the new shape. The article → related-events link, which
  depended on the removed related-article field (unused: 0 of 125 events), is
  removed along with its admin picker.
- **FR-024**: The public API MUST expose the new shape only. Endpoints whose
  sole basis is a removed field are retired (`featured`) or re-based
  (`by-gregorian-year` derives from the Gregorian date; `map` derives from the
  linked location). The change is delivered as a breaking change to the single
  existing API version, with the API documentation updated.
- **FR-025**: Seed content written for the old event shape (the five event
  content seeders and their base, and the Seerah events in the dev fixtures) MUST
  be removed, not converted. The event-type lookup seed (previously performed inside a schema step), the
  sample-data generator used by tests, and the automated tests MUST be rewritten
  to the new shape; tests whose subject was removed are deleted, not skipped.
- **FR-026**: No live code, view, translation file, test or configuration may
  reference a removed Seerah field, record type or fixed list after the change.
  Project documentation describing the Seerah shape (including the project guide)
  MUST be updated.

**Fresh start**

- **FR-027**: No conversion or carry-over of existing Seerah data is required.
  Environments are rebuilt on the new shape; the Locations backfill of event
  places is no longer needed for Seerah and its Seerah branch is removed.
- **FR-028**: Before an environment that holds Seerah data is rebuilt, a backup
  copy of its Seerah records MUST be taken, and the rebuild MUST be scoped so it
  cannot remove any other module's data.
- **FR-029**: Public Seerah pages, the admin lists, the dashboard tile and the
  API MUST each render a correct empty state when there are no events.

### Key Entities

- **Milestone**: A chapter of the Seerah journey. Ordered; has a look-and-feel
  group, a Hijri and Gregorian year range, an active flag; translated (title,
  subtitle, description, readiness). Has many events.
- **Event type**: A category of event (battle, treaty, revelation…). Slug,
  look-and-feel group, names per language on the record itself, active flag.
  Has many events.
- **Event**: One occurrence in the Seerah. Belongs to a type, a milestone and
  optionally a shared Location; ordered within its milestone; thumbnail and
  cover; Hijri year/month/day and Gregorian date; status and era from fixed
  application lists; free-form statistics. Translated.
- **Event translation**: Per-language title, description, content, dispute note,
  readiness, sources, and repeatable key/value details.
- **Event gallery item**: An ordered image belonging to an event, with a caption
  per language and a link to the media-library item it was picked from —
  identical to an article gallery item.
- **Event media item**: A link between an event and a media-library item, unique
  per pair — identical to an article media link.
- **Location** (existing, shared): The canonical place an event links to; source
  of place name and coordinates.

## Impact Scope *(traced 2026-09-17)*

Areas that reference a removed or renamed Seerah field and must change:

| Area | What is affected |
|---|---|
| Seerah domain | Event, translation, milestone, type models; importance / phase / legacy type / participant-role fixed lists; create & update data objects; event, type and milestone repositories (list, map, facet, detail, related-events queries); event service (create/update payloads, view counting, gallery merge, lessons); dashboard stats (views total); cache keys (featured); exporter & importer |
| Admin portal | Event create / edit / advanced / index / show screens; milestone form / index / show; event-type form / index / show; store & update validation for all three; event controller image handling; module-selector picker (search endpoint only) |
| Public site | Seerah controller (stats, journey shells, filters, facets, chips, card and detail mapping); journey, events directory, both event-page layouts, event navigator; Seerah translation files in ar / en / fr / tr / ur |
| Public API | Event resource (15 of its fields are removed or renamed), participant resource, featured / map / by-type / by-Gregorian-year endpoints, article resource's related-events block |
| Other modules | Search module definition (filterable fields — one already stale), article model & admin article controller (related events), Locations usage registry, locations backfill command, participant-linking command, analytics, export module list, bulk operations, favourites mapper, scheduled-content labels, media repository back-link |
| Data tooling | Event factory, five event seeders + seeder base, dev fixtures seeder, extraction / audit scripts |
| Tests | ~20 files: admin event CRUD, milestone, type & bulk, milestone events, article picker, participant link, event map, location preservation, event repository, Seerah API, API smoke, search API, N+1 guard, translation E2E, public pages, Locations schema / usage registry / backfill |
| Docs | Project guide, Seerah rebuild & gap reports, Locations module spec/design, column-selection audit |

Not affected (checked): Seerah public-visibility rule, front-end scripts (no
script reads a removed field; page behaviour is inline in the templates),
stylesheets (styling only).

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A from-scratch environment reaches the target shape and completes
  its seed step with 0 errors, and contains 0 records of any removed type.
- **SC-002**: With no events, every public Seerah page, admin Seerah screen and
  Seerah API endpoint returns a valid empty state (0 errors).
- **SC-003**: Every public Seerah page and every admin Seerah screen loads
  without error in Arabic and English on a populated dataset (0 failures across
  the full page walk).
- **SC-004**: A search of the codebase for each removed field, record type and
  fixed list returns 0 live references in Seerah contexts.
- **SC-005**: The full automated test suite passes on a from-scratch environment,
  with no Seerah test skipped.
- **SC-006**: An editor can create a complete bilingual event — including
  thumbnail, cover, place, sources and key/value details — in one pass without
  encountering a field that fails to save or reloads blank.
- **SC-007**: The event form presents at least 20 fewer inputs than today, with
  no loss of any capability retained in the target shape.
- **SC-008**: Public Seerah pages load at least as fast as before the change
  (no measurable regression on the journey, directory and event pages).

## Assumptions

- The owner's field list is the complete target: any current field not listed is
  intentionally removed, together with the screens, filters, badges, API fields
  and translations that exist only for it (importance, phase, pivotal, featured,
  view counts, related article, free-text Hijri date, Gregorian year, free-text
  location, own coordinates). Participants, timeline items and lessons are removed by
  the owner's explicit decision (Q1).
- "locale {ar,en,…}" on event types means the type's display names per language
  are kept on the type record itself, replacing its translations table.
- "status / era — enum from codebase not db" means the allowed values are
  governed by the application's existing status and era lists; the stored field
  is unconstrained text.
- Milestone "Gregorian from/to" are years, like the Hijri pair, matching today's
  data.
- Thumbnail and cover are two independent images replacing the single featured
  image; cards use the thumbnail, the event page uses the cover, each falling
  back to the other.
- "Same as article tables and relation" (Q2) covers the two records the owner
  listed — gallery and media. The article's separate ordered queue of Media-module
  episodes is not part of this feature.
- "Start fresh" (Q3) applies to every environment, including any that holds the
  rebuilt 578-event chronology: that content is re-authored through the admin
  portal, not converted. A backup is still taken first (FR-028) because it costs
  nothing and the decision is otherwise irreversible.
- The API has one consumer family (the project's own front ends); a breaking
  change to v1, shipped together with them, is acceptable.
- Supported languages remain Arabic (default), English, Turkish, French, Urdu.

## Dependencies

- Shared Locations module (`003-locations-module`): the event → location link is
  the only place field after this change. Its schema step that adds the link, its
  usage registry and its delete guard stay; its free-text backfill for Seerah goes.
- Article gallery and article media: the reference implementation the event
  gallery and event media mirror.
