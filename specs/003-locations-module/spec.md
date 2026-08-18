# Feature Specification: Locations Module

**Feature Branch**: `003-locations-module`
**Created**: 2026-08-18
**Status**: Draft
**Input**: User description: "Locations module: a shared locations lookup that normalizes the three free-text place columns (seerah_events.location, muslims.birth_place, quran_surahs.revelation_place) onto canonical locations with translations, hierarchy, aliases and coordinates. Lookup and map data only; no public location routes. Approved design at docs/LOCATIONS-MODULE-DESIGN-2026-08-18.md."

## Context

Places are currently recorded as unconstrained text in three parts of the
encyclopedia, with no shared vocabulary between them. Measured against live
content:

- **Seerah events** — 279 of 578 events name a place, in 215 distinct phrasings.
  The text is editorial prose (`"حمراء الأسد على نحو 8 أميال جنوب المدينة"`),
  not a place name.
- **Companion profiles** — 6,677 of 7,837 people record a birthplace, all of it
  in English only, across 32 spellings. `Medinah` (743), `Medina` (675) and
  `Madinah` (1) are the same place stored three ways; so are `Hijaz`/`HIjaz`,
  `Yemen`/`Yeman`, `Ta'if`/`al-Ta'if`, `Najd`/`Nejd`.
- **Surahs** — each of the 114 surahs carries a مكية/مدنية classification, but
  no place record, so no coordinates and no localized place name.

Three problems follow. Event pages are built to show a map but almost never do,
because a map needs coordinates and only 2 of 578 events have them. Companion
profiles show an English birthplace on an Arabic-first site. And nothing —
neither a visitor nor an editor — can ask the encyclopedia for "everything that
happened at Badr" or "companions born in Makkah", because there is no place to
ask about, only strings.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Visitor sees where a Seerah event happened (Priority: P1)

A visitor reading about the Battle of Uhud sees a map showing where it took
place, and the place named properly in their own language, with the historian's
descriptive note preserved beneath it.

**Why this priority**: This is the largest visible gap. The event page already
has a map region that is designed and built, and it renders for 2 events out of
578. Every other reader gets a blank where a map was intended. Fixing it needs
nothing from the editorial team beyond the initial place list.

**Independent Test**: Open any Seerah event whose text names a known place and
confirm a map renders centred on that place, with the localized place name and
the original descriptive note both visible.

**Acceptance Scenarios**:

1. **Given** an event whose text names a known place and which has no coordinates
   of its own, **When** a visitor opens the event page, **Then** a map renders
   centred on that place.
2. **Given** an event that has its own recorded coordinates, **When** a visitor
   opens the event page, **Then** the map uses the event's own coordinates, not
   the place's.
3. **Given** an event whose text names no known place, **When** a visitor opens
   the event page, **Then** no map renders and the descriptive text is still
   shown — the page behaves exactly as it does today.
4. **Given** an event resolved to a place, **When** the page renders in Arabic,
   English, Turkish, French or Urdu, **Then** the place name appears in that
   language and the descriptive note appears unchanged.

---

### User Story 2 - Editor curates the canonical list of places (Priority: P1)

A content editor maintains the encyclopedia's list of places: adding a place,
naming it in each supported language, recording where it is, saying what kind of
place it is, and situating it inside a larger place.

**Why this priority**: Every other story depends on this list existing and being
correctable. It is also the only durable fix for the drift — without a curated
vocabulary, the next content import reintroduces free text.

**Independent Test**: Create a place with names in all five languages, a
coordinate, a type and a parent; confirm it appears in the place list and can be
selected wherever content records a place.

**Acceptance Scenarios**:

1. **Given** the admin panel, **When** an editor creates a place with an Arabic
   name, a type and a parent place, **Then** it is saved and appears nested under
   its parent in the place list.
2. **Given** a place already referenced by events or people, **When** an editor
   attempts to delete it, **Then** deletion is refused and the number of
   referencing records is shown.
3. **Given** a place, **When** an editor records alternate spellings for it,
   **Then** those spellings are matched when content is resolved and when the
   place is searched for by name.
4. **Given** any admin screen for places, **When** it is opened, **Then** it
   renders in Arabic with right-to-left layout by default.

---

### User Story 3 - Visitor reads a companion's birthplace in Arabic (Priority: P2)

A visitor browsing a companion's profile sees the birthplace written in the
language they are reading, not as an English transliteration.

**Why this priority**: It affects 6,677 profiles — the largest body of content in
the system — and the current state is a visible defect on an Arabic-first site.
It ranks below the map because the existing English text is at least readable,
whereas the missing map is a blank.

**Independent Test**: Open a companion whose birthplace is recorded and confirm
the place renders in the active language, and that companions previously stored
under different spellings of the same place now show the same name.

**Acceptance Scenarios**:

1. **Given** a companion whose birthplace was recorded as `Medinah`, `Medina` or
   `Madinah`, **When** a visitor opens the profile in Arabic, **Then** all three
   display **المدينة المنورة**.
2. **Given** a companion whose recorded birthplace names two places or is
   explicitly uncertain, **When** a visitor opens the profile, **Then** the
   original recorded text is displayed unchanged rather than a guessed place.
3. **Given** a companion with no recorded birthplace, **When** a visitor opens
   the profile, **Then** no birthplace section is shown.

---

### User Story 4 - Editor resolves the records the system could not (Priority: P2)

An editor finds every record that names a place in text but has not been matched
to a canonical place, and assigns each one by hand.

**Why this priority**: Automatic matching will never reach 100% — some Seerah
text describes a position rather than a place (`"المنطقة المحايدة بين الجيشين"`)
and some birthplaces are ambiguous. Without a way to see the remainder, the tail
is invisible and never gets finished.

**Independent Test**: Filter the Seerah event and companion lists to unresolved
records, assign a place to one, and confirm it leaves the filtered view and the
assignment survives a re-run of the automatic resolution.

**Acceptance Scenarios**:

1. **Given** content that names a place in text but has no assigned place,
   **When** an editor filters for unresolved records, **Then** only those records
   are listed.
2. **Given** an editor has manually assigned a place to a record, **When**
   automatic resolution runs again, **Then** the manual assignment is left
   untouched.
3. **Given** an editor is assigning a place, **When** they type part of a name or
   a known alternate spelling, **Then** matching places are offered.

---

### User Story 5 - Content is grouped and found by place (Priority: P3)

An editor or visitor can treat a place as a way into the content: searching for a
place name finds the events that happened there, including events whose own text
names the place only indirectly.

**Why this priority**: A genuine capability gain, but it builds on the other four
and delivers less than they do on their own.

**Independent Test**: Search for a place name and confirm results include content
resolved to that place, including records whose text uses a different wording.

**Acceptance Scenarios**:

1. **Given** an event whose descriptive text reads
   `"حصون بني قريظة جنوب المدينة"` and which is resolved to المدينة المنورة,
   **When** a visitor searches for **المدينة**, **Then** the event is returned.
2. **Given** the admin event list, **When** an editor filters by a place, **Then**
   only events resolved to that place are listed.

---

### Edge Cases

- **A place name is a substring of another.** Text reading
  `"المسجد النبوي بالمدينة"` must resolve to the mosque, not to the city, even
  though both names appear.
- **The text names two places.** Values like `Yemen/Makkah`, `Makkah or Medinah`
  and `من مكة إلى الحبشة عبر ميناء الشعيبة` are not resolved automatically; they
  are left unresolved for an editor, and the original text is still displayed.
- **The text names no place at all.** `"المنطقة المحايدة بين الجيشين"` describes
  a position. It stays unresolved permanently and the page behaves as it does
  today.
- **A place is deleted while content references it.** Deletion is refused.
- **A place is made inactive.** Content already resolved to it keeps its
  reference and keeps rendering; the place is no longer offered for new
  assignments.
- **A place is made its own ancestor.** Rejected — the hierarchy must stay a
  tree.
- **Resolution is run twice.** The second run changes nothing.
- **A place has no coordinates.** Content resolved to it shows the place name but
  no map, rather than an empty or wrongly-centred map.
- **A surah's classification and its place disagree** — for example a surah
  classified مكية but recorded as revealed at الطائف. Both are shown; the
  classification is not derived from the place and is never overwritten by it.

## Requirements *(mandatory)*

### Functional Requirements

**Canonical places**

- **FR-001**: The system MUST maintain a single list of canonical places shared
  by the Seerah, companions and Quran content.
- **FR-002**: Each place MUST carry a name in each supported language (Arabic,
  English, Turkish, French, Urdu), with Arabic as the default and fallback.
- **FR-003**: Each place MUST record what kind of place it is — among city,
  region, country, mountain, valley, mosque, well, battlefield and landmark.
- **FR-004**: Each place MUST be able to sit inside a larger place, forming a
  hierarchy (for example the Sacred Mosque inside Makkah inside the Hijaz), and
  the hierarchy MUST NOT be allowed to contain a cycle.
- **FR-005**: Each place MAY record geographic coordinates, and MUST behave
  correctly when it does not.
- **FR-006**: Each place MUST be able to record alternate spellings and historical
  variants, which are used both to resolve existing content and to find the place
  by name.
- **FR-007**: A place MUST NOT be deletable while any content references it; the
  system MUST report how many records do.
- **FR-008**: A place MUST be able to be deactivated without affecting content
  already resolved to it.

**Resolving existing content**

- **FR-009**: The system MUST resolve existing Seerah event text, companion
  birthplaces and surah classifications to canonical places without an editor
  entering them by hand.
- **FR-010**: Resolution MUST prefer the most specific matching place when more
  than one place name appears in the same text.
- **FR-011**: Resolution MUST leave a record unresolved rather than guess when
  the text names more than one place or is explicitly uncertain.
- **FR-012**: Resolution MUST be repeatable with no further effect — running it
  again MUST NOT change any record.
- **FR-013**: Resolution MUST NOT overwrite a place an editor assigned by hand.
- **FR-014**: Resolution MUST offer a preview mode that reports what it would
  change without changing anything.
- **FR-015**: Resolution MUST report, per content type, how many records it
  resolved and how many it left unresolved.

**Preserving what exists**

- **FR-016**: The original descriptive place text on Seerah events MUST be
  retained and continue to be displayed, exported, imported and searched.
- **FR-017**: The مكية/مدنية classification of a surah MUST be retained
  unchanged, and MUST NOT be derived from, or overwritten by, its place.
- **FR-018**: No existing recorded place text MAY be discarded as part of this
  feature.

**Editing content**

- **FR-019**: Editors MUST be able to assign a canonical place when creating or
  editing a Seerah event, a companion profile or a surah.
- **FR-020**: Editors MUST be able to list content that names a place in text but
  has no canonical place assigned.
- **FR-021**: Place selection MUST allow finding a place by any of its names or
  recorded alternate spellings.
- **FR-022**: All administrative screens for places MUST render in Arabic with
  right-to-left layout by default, and support the other four languages.

**Reading content**

- **FR-023**: A Seerah event page MUST show a map when either the event or its
  resolved place has coordinates, preferring the event's own.
- **FR-024**: A Seerah event page MUST show neither a map nor an error when
  neither the event nor its place has coordinates.
- **FR-025**: Content resolved to a place MUST display that place's name in the
  language being read.
- **FR-026**: Content not resolved to a place MUST display its original recorded
  text, unchanged.
- **FR-027**: Searching for a place name MUST return content resolved to that
  place, including content whose own text words the place differently.

### Key Entities

- **Place**: A named geographic location in the encyclopedia's shared vocabulary.
  Has a name per language, a kind, an optional position on the earth, an optional
  containing place, alternate spellings, and an active flag.
- **Seerah event**: Gains an optional reference to a Place, alongside its existing
  descriptive place text and its own optional coordinates.
- **Companion profile**: Gains an optional reference to a Place for birthplace,
  alongside its existing recorded birthplace text.
- **Surah**: Gains an optional reference to a Place for where it was revealed,
  alongside its existing and unchanged مكية/مدنية classification.

### Out of Scope

- Public browsable pages for places — no place index, no place detail page, no
  place-level SEO or sitemap entries.
- An all-events map showing the whole Seerah on one view.
- Reciter country of origin, which is already recorded consistently.
- Navigation slot names, verse position references and layout placement fields,
  which use the word "location" for unrelated purposes.
- The seldom-used extended biographical place fields on companion profiles
  (populated on 2 records in total).
- Removing any superseded place text column; that is a separate later decision.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Seerah event pages that display a map rise from **2** to at least
  **180** of the 279 events that name a place — from under 1% of all events to
  roughly a third.
- **SC-002**: At least **95%** of the 6,677 companions with a recorded birthplace
  display that birthplace in Arabic, where today **none** do.
- **SC-003**: The 1,419 companions recorded under three different spellings of
  Madinah all display the same place name, and are returned by a single query for
  that place.
- **SC-004**: All 114 surahs are associated with a place carrying coordinates and
  a name in all five languages.
- **SC-005**: An editor can see every unresolved record across Seerah events and
  companion profiles from a single filtered view per content type, in no more than
  two clicks from the module's main screen.
- **SC-006**: Running resolution a second time changes zero records, and changes
  zero records that an editor has assigned by hand.
- **SC-007**: Zero existing place text is lost — every descriptive phrase readable
  on a Seerah event page before the change is readable after it.
- **SC-008**: An editor can add a newly discovered place and assign it to content
  entirely from the admin panel, with no developer involvement.
- **SC-009**: No Seerah event, companion or surah page renders more slowly than it
  does today, measured on the event timeline and the companion directory.

## Assumptions

- The initial canonical list is roughly 55 places, sufficient to cover the places
  named in the existing content plus the major regions surrounding them.
  Coordinates are drawn from public geographic data.
- Automatic resolution is expected to reach roughly two-thirds of Seerah events
  that name a place and roughly 95% of companion birthplaces. The remainder is
  editorial work, not a defect.
- Editors, not visitors, resolve the unmatched tail. There is no public
  contribution or suggestion flow.
- Places are historical and stable. There is no requirement for a place to change
  over time, or to record a name that was valid only during one period.
- A record references at most one place. Journeys described as travelling from one
  place to another — 9 of the 279 Seerah phrasings — reference their principal or
  destination place, with the full route conveyed by the retained descriptive
  text.
- Supported languages match the rest of the platform: Arabic, English, Turkish,
  French and Urdu, with Arabic as default and fallback.
