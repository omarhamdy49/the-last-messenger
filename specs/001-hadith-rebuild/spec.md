# Feature Specification: Hadith Module Rebuild

**Feature Branch**: `001-hadith-rebuild`
**Created**: 2026-07-30
**Status**: Draft
**Input**: Replace the existing Hadith module and schema from scratch, modelled on sunnah.com's domain
concepts, populated by extracting sunnah.com's book pages through the Chrome extension. Rebuild the
domain module, the admin UI, and the public pages.

## Why this exists

Three problems, in order of severity:

1. **The schema encodes junk.** `hadith_keywords` stores tokenised Arabic stopwords. A
   `hadith_grading_levels` lookup grew **566 taxonomy rows** like
   `sahih_(darussalam)_[bukhari_3615_and_muslim_2009]` because the old importer lowercased free-text
   grade strings into a table. Three `*_translations` side tables duplicate spatie JSON columns.
   Separate `hadith_companions` and `hadith_narrators` tables describe the same kind of entity.
2. **The module is over-built.** 125 files implementing CQRS — 11 Commands, 18 Queries, 24 Handlers —
   where most handlers are a single delegating call. A sibling module does the same job in 36 files.
3. **There is no data to preserve.** An earlier migration had already reduced the hadith table to 42
   rows; a subsequent mis-targeted `migrate:fresh` dropped the remainder. All old data and backups are
   discarded by explicit direction, so this is a greenfield rebuild rather than a migration.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Read one hadith with correct scholarly references (Priority: P1) 🎯 MVP

A reader opens a hadith by its canonical reference and sees the Arabic text, the English translation,
and the scholarly reference lines exactly as the authoritative source presents them.

**Why this priority**: It is the thinnest slice that exercises the whole pipeline — extract → JSON →
import → repository → rendered page — on a single book. Every later story reuses that pipeline. It also
proves the hardest correctness requirement (reference construction) before any bulk work.

**Independent Test**: Extract one book (Sahih al-Bukhari, Book 1 — 7 hadiths), import it, open
`/hadith/bukhari:1`, and diff the three reference lines against the live source page. Delivers a
readable, correctly-cited hadith with no other feature present.

**Acceptance Scenarios**:

1. **Given** Book 1 has been imported, **When** a reader opens `/hadith/bukhari:1`, **Then** the page
   shows the Arabic body, the English body, and three labelled reference lines: a canonical reference
   (`Sahih al-Bukhari 1`), an in-book reference (`Book 1, Hadith 1`), and a translation reference
   (`Vol. 1, Book 1, Hadith 1`) carrying a deprecated-numbering note.
2. **Given** a hadith has Arabic text but no English translation, **When** a reader opens it, **Then**
   the Arabic renders and the page does not error or show an empty English block.
3. **Given** a hadith carries a grade string from the source, **When** it is imported, **Then** the
   original string is stored verbatim and a normalised grade is derived from it.
4. **Given** a grade string asserts two different verdicts, **When** it is imported, **Then** both are
   recorded as separate claims rather than one silently discarded.
5. **Given** an unknown collection or hadith number, **When** a reader requests it, **Then** the site
   returns 404 rather than an error page.

### User Story 2 - Browse the collection hierarchy (Priority: P1)

A reader navigates from a hub of collections, into a collection, into a book, and reads its hadiths in
order with chapter headings, chapter introductions, and closing passages.

**Why this priority**: Reading a single hadith is only useful if it can be found. This is the primary
discovery path and completes the reading experience.

**Independent Test**: With one collection fully imported, confirm the hub lists all collections, the
collection page lists its books, and a book page renders its hadiths grouped under chapter headings in
source order.

**Acceptance Scenarios**:

1. **Given** collections are published, **When** a reader opens the hadith hub, **Then** all published
   collections are listed with their names and hadith counts.
2. **Given** a collection with books, **When** a reader opens it, **Then** its books are listed in
   source order with their titles and hadith ranges.
3. **Given** a book with chapters, **When** a reader opens it, **Then** hadiths appear under their
   chapter headings, in order, with chapter introductions and endings where present.
4. **Given** a collection with no book structure, **When** a reader browses it, **Then** its hadiths
   are reachable and no placeholder book is exposed in the interface.
5. **Given** a book page of any size, **When** it renders, **Then** the number of database queries does
   not grow with the number of hadiths shown.

### User Story 3 - Search in Arabic and English (Priority: P2)

A reader searches for a word or phrase in either Arabic or English and gets relevant hadiths, narrowed
by collection and grade.

**Why this priority**: Search is how most readers actually find a hadith, but it depends on a populated
corpus, so it follows reading and browsing.

**Independent Test**: Search a short Arabic term and confirm hits are returned; search the same term
with and without diacritics and confirm identical result sets.

**Acceptance Scenarios**:

1. **Given** an imported corpus, **When** a reader searches an English word, **Then** matching hadiths
   are returned ranked by relevance.
2. **Given** an imported corpus, **When** a reader searches a two-character Arabic word, **Then**
   matching hadiths are returned — short Arabic tokens must not be silently unsearchable.
3. **Given** a query written with diacritics, **When** it is searched, **Then** it returns the same
   results as the same query without diacritics.
4. **Given** a search with a collection or grade filter, **When** it runs, **Then** only matching
   hadiths are returned.
5. **Given** an absurdly large page-size request, **When** it is submitted, **Then** the page size is
   capped rather than honoured.
6. **Given** a search that matches nothing, **When** it runs, **Then** a meaningful empty state with a
   way to clear filters is shown.

### User Story 4 - Editor curates content (Priority: P2)

A content editor signs into the admin panel, finds hadiths, corrects translations, records gradings,
manages the hierarchy, and controls what is publicly visible.

**Why this priority**: Imported data is a starting point, not a finished product. Scholarly review
requires editorial tooling, but only once there is content to review.

**Independent Test**: As an editor, add a grading to a hadith and confirm the displayed grade updates
according to the defined policy; unpublish a hadith and confirm it is no longer publicly reachable.

**Acceptance Scenarios**:

1. **Given** an editor with permission, **When** they open the hadith list, **Then** results are
   paginated and filterable — a 51,000-row table is never rendered whole.
2. **Given** an editor edits translatable fields, **When** they open the form, **Then** all five locales
   are editable with Arabic presented first.
3. **Given** an editor adds or changes a grading, **When** they save, **Then** the hadith's displayed
   grade is recomputed from its gradings and is not directly editable.
4. **Given** an editor unpublishes a hadith, **When** a reader requests it, **Then** it returns 404.
5. **Given** a user without permission, **When** they attempt any admin action, **Then** it is denied.
6. **Given** two editors open the same record, **When** the second saves over a newer version,
   **Then** the conflict is detected rather than silently overwriting.
7. **Given** an import recorded unmapped grade strings, **When** an editor views the import log,
   **Then** those strings are visible so the mapping can be improved.

### User Story 5 - Complete corpus (Priority: P2)

All 17 collections are extracted and imported, with per-collection reconciliation proving nothing was
silently lost.

**Why this priority**: Completeness is the product promise, but it is mechanical repetition of a
pipeline that must be proven correct on one collection first.

**Independent Test**: For every collection, the imported row count matches the recorded expected count,
and any skipped record is itemised with a reason.

**Acceptance Scenarios**:

1. **Given** all collections extracted, **When** each is imported, **Then** its row counts match the
   expected counts recorded at extraction time.
2. **Given** any record could not be imported, **When** the run finishes, **Then** it is listed
   individually with a reason — never summarised as a count.
3. **Given** a collection is re-imported, **When** the run completes, **Then** row counts are unchanged
   and no duplicates are created.
4. **Given** a corrected source file removes a child record, **When** it is re-imported, **Then** the
   removed child is deleted rather than orphaned.
5. **Given** editors have curated a record, **When** a routine re-import runs, **Then** their edits are
   preserved unless an explicit reset is requested.
6. **Given** the corpus is imported, **When** duplicates are checked, **Then** a report lists them
   without modifying any data.

### User Story 6 - Third-party API access (Priority: P3)

A developer consumes a documented read-only API for collections, books, chapters, hadiths, search,
random selection, and permalink lookup.

**Why this priority**: Valuable for reuse and mobile clients, but no internal consumer is blocked on it.

**Independent Test**: Each documented endpoint returns the documented shape; unauthorized, missing, and
invalid requests return 401, 404, and 422 respectively.

**Acceptance Scenarios**:

1. **Given** a valid request, **When** any listed endpoint is called, **Then** it returns 200 with the
   documented envelope and pagination metadata.
2. **Given** an invalid parameter, **When** an endpoint is called, **Then** it returns 422 with
   field-level messages.
3. **Given** a permalink identifier that does not exist, **When** it is requested, **Then** it returns
   404 cleanly.
4. **Given** a list endpoint, **When** page size varies, **Then** the query count stays constant.
5. **Given** repeated search requests, **When** the rate limit is exceeded, **Then** requests are
   throttled.

### Edge Cases

- A collection with **no book structure** — the hierarchy requires a book, so a hidden placeholder is
  needed, and it must never surface in the interface or counts.
- Hadith numbers that are **not integers** (`1a`, `35b`) and numbers containing separators.
- Roughly **35% of records carry no permalink identifier** — permalink lookup must 404 cleanly for them
  rather than assume presence.
- **7% of records have no English translation** — pages must fall back to Arabic without empty blocks.
- Grade strings that are **prose**, contain publisher citations, or contain parsing artifacts — these
  must survive verbatim and normalise to "unknown" rather than be forced into a category.
- Grade strings naming the compiler of the collection the hadith already belongs to — this is
  provenance, not an independent verdict, and must not become a scholarly grading record.
- One collection renders in a **different single-column layout** than the others.
- A hadith whose chapter is deleted by an editor — it must not be silently orphaned.
- Interrupted extraction leaving a **partially written** file — a resumed run must not treat it as
  complete.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The system MUST store hadith text as one record per locale, so adding a language requires
  data rather than schema change.
- **FR-002**: The system MUST store each source grade string verbatim, permanently, alongside any
  normalised interpretation of it.
- **FR-003**: The system MUST represent a single grade string that asserts multiple verdicts as
  multiple distinct grading records.
- **FR-004**: The system MUST distinguish a verdict about a hadith from a verdict about its chain of
  transmission.
- **FR-005**: The system MUST derive each hadith's displayed grade from its grading records by a
  deterministic, documented rule, and MUST NOT allow that displayed value to be edited directly.
- **FR-006**: The system MUST NOT create a grading record from a collection-provenance attribution.
- **FR-007**: The system MUST report every grade string it could not interpret, per import run.
- **FR-008**: The system MUST construct and display the canonical, in-book, and translation reference
  lines for a hadith, including a deprecation note where the source marks one.
- **FR-009**: The system MUST prevent a hadith from referencing a book and a chapter that belong to
  different parents.
- **FR-010**: The system MUST refuse to delete a chapter that still has hadiths, rather than orphan them.
- **FR-011**: The system MUST support full-text search over Arabic and English text, where Arabic
  matching is insensitive to diacritics and to orthographic variants of the same letter.
- **FR-012**: The system MUST return results for Arabic search terms as short as two characters.
- **FR-013**: The system MUST cap client-supplied page sizes.
- **FR-014**: The system MUST expose a stable canonical URL per hadith based on its collection and
  number, and MUST resolve source permalink identifiers to it where one exists.
- **FR-015**: The system MUST fall back to Arabic when a requested locale has no text for a record.
- **FR-016**: Extraction MUST record, per unit fetched, the expected and actual record counts, the
  source location, and an integrity checksum, and a resumed run MUST validate these before skipping.
- **FR-017**: Extraction MUST write output atomically so an interrupted run cannot be mistaken for a
  complete one.
- **FR-018**: Import MUST be idempotent on a natural key and MUST reconcile child records, deleting
  those removed from a corrected source.
- **FR-019**: Import MUST distinguish source-owned from editor-owned fields and MUST NOT overwrite
  editor-owned values without an explicit reset instruction.
- **FR-020**: The system MUST provide administrative create, read, update, and delete for collections,
  books, chapters, hadiths, and scholars, with authorization enforced on every action.
- **FR-021**: The system MUST detect concurrent edits to the same record rather than silently
  overwriting.
- **FR-022**: The system MUST gate all public visibility through a single mechanism.
- **FR-023**: The system MUST provide a read-only public API for collections, books, chapters, hadiths,
  search, random selection, and permalink lookup.
- **FR-024**: The system MUST NOT create any table or column that no data source populates.
- **FR-025**: Every user-facing string MUST be translatable, with keys present in all five locales.

### Key Entities

- **Collection** — a named hadith compilation (e.g. Sahih al-Bukhari). Has a stable short code used in
  URLs, a display name and author per locale, flags for whether it uses volumes, books, and chapters,
  and a template describing how its canonical reference is written.
- **Book** — a division of a collection, identified by a number that is not necessarily numeric. Has an
  ordering, a title and introduction per locale, and a hadith range. May be a hidden placeholder for
  collections with no real book structure.
- **Chapter** — a division of a book, with a number, ordering, title, introduction, and closing passage
  per locale.
- **Hadith** — the identity and position of a narration: its collection, book, chapter, canonical
  number, in-book number, optional volume, optional external permalink identifiers, publication state,
  and a derived displayed grade. Carries no text itself.
- **HadithText** — the text of one hadith in one locale: the body as displayed, a search-optimised form
  of that body, the narrator preamble, and optionally the chain and the saying separately. Exists only
  for locales that actually have text.
- **Grading** — one scholarly claim about one hadith: the verbatim source string, a normalised verdict,
  whether it concerns the hadith or its chain, an optional label identifying which part it addresses,
  an authority rank, and the scholar who made it.
- **Scholar** — a person who narrated or graded: name, full name, patronymic, biography, birth and
  death years, generation, reliability, whether they were a Companion, and an optional link to the
  canonical companion record in another module.
- **ImportRun** — the record of one import: which collection, its source, status, counts, the grade
  strings it could not interpret, and any error.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: The three reference lines rendered for a hadith match the authoritative source exactly,
  character for character, for a sampled set across every collection.
- **SC-002**: Total imported hadiths reconcile against expected counts per collection, with every
  discrepancy itemised and explained. Target: 17 collections, ~51,400 Arabic records, ~47,600 English.
- **SC-003**: Zero grade strings are lost — every imported grading retains its verbatim source string,
  and every uninterpretable string appears in an import report.
- **SC-004**: No scholar receives grading records derived from collection provenance. Specifically, the
  compilers of the two largest collections have no mass-attributed gradings.
- **SC-005**: An Arabic query of two characters returns results, and diacritised and undiacritised
  forms of the same query return identical result sets.
- **SC-006**: Query count on list and detail pages is constant with respect to result count, verified at
  two different page sizes.
- **SC-007**: The schema contains no table or column without a populating data source.
- **SC-008**: Deletion of a chapter with hadiths is rejected by the database, and a hadith cannot be
  saved referencing mismatched parents — both proven by tests.
- **SC-009**: Re-importing any collection twice leaves row counts unchanged.
- **SC-010**: A re-import preserves editor-curated fields unless reset is explicitly requested.
- **SC-011**: Every public page renders correctly in Arabic and English, in light and dark themes, at
  375, 768, and 1280 pixels wide.
- **SC-012**: Translation keys are present in all five locale files, with zero missing.
- **SC-013**: The rebuilt module is materially smaller than the one it replaces — target under 45 files
  against the current 125, and 8 tables against the current 22.
- **SC-014**: The full test suite, the formatter, and the architecture boundary checker all pass, with
  the boundary baseline containing no entries for this module.
