# Feature Specification: Hadith and Muslim Identity Rebuild

**Feature Branch**: `002-hadith-muslims-rebuild`
**Created**: 2026-08-06
**Status**: Draft
**Input**: User description: "002-hadith-muslims-rebuild"

## Overview

The platform currently keeps the same historical figure in several unrelated places: a companion is
one record, a hadith narrator is another, a scholar who graded that hadith is a third, and a node in
the family tree is a fourth. Nothing connects them, so a reader who opens a companion's profile
cannot see the hadiths they narrated, and an editor who corrects a name must find and correct it in
up to four places.

Hadith content has the mirror problem: the reference a reader would cite is assembled at display
time from several sources, the same hadith can be reachable by more than one number, and a hadith's
authenticity verdict is stored as a set of competing claims that the site has no rule for resolving
into the single verdict it actually displays.

This feature rebuilds both areas from the canonical source files as a single replacement. Every
historical person becomes **one record with roles** — companion, narrator, scholar, grader, compiler
— and every hadith gets **one stable public address** and **one displayed verdict** derived by a
written, repeatable rule.

## Clarifications

### Session 2026-08-06

- **Q: Do companion battle participation, per-person timelines, and companion categories survive the
  rebuild?** → **A: All three are retired.** Battle filtering, the "participated in" profile block,
  per-person timelines, and category browsing are removed along with the public pages and endpoints
  that expose them. See FR-047 and Out of Scope.
- **Q: Do the chain-of-narration text, hadith body text, translator, and volume designation survive
  on a hadith?** → **A: All four are retired**, together with their admin inputs and public output
  fields. Volume-based navigation is removed. See FR-027 and Out of Scope.
- **Q: Do person profiles keep an image gallery, or only a single portrait?** → **A: Single portrait
  only.** Existing companion galleries and their generated sizes are not carried forward, and profile
  layouts that render a gallery are reworked. See FR-048 and Out of Scope.

- **Owner instruction, same session:** *"don't consider upgrade — will fully purge existing codebase
  and db schema and existing database migrations and create from scratch new one."* → **No upgrade
  path exists.** The rebuild starts from an empty system; the previous structure is not converted or
  torn down step by step, it is simply no longer described. See FR-036a, FR-042, and Out of Scope.

All three answers reduce scope, and the purge instruction removes an entire class of work
(conversion, teardown sequencing, and reference cleanup). Nothing that a reader can reach today is
retired silently — each removal is stated as a requirement and listed in Out of Scope so it is
visible during planning.

**One consequence worth deciding explicitly:** starting from empty discards *all* stored content,
not only hadith and person records — editorial articles, categories, tags, media records, topics,
favourites, and user accounts included. That is what makes the reference-cleanup problem disappear.
If anything in those areas is worth keeping, it has to be exported before the rebuild and restored
afterwards by readable key rather than numeric identifier (FR-041, FR-042a).

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Rebuild the corpus from canonical sources (Priority: P1)

A content operator rebuilds the system from nothing. The previous structure is not upgraded,
converted, or torn down step by step — it simply ceases to be described, and the system is built
from empty against the canonical source files. The operator watches a report that states, per
collection, how many collections, books, chapters, hadiths, narration links, and people were
expected and how many were produced. Any mismatch stops the run before anything is made public, and
the fix is to correct the problem and run the whole thing again.

**Why this priority**: Nothing else in this feature exists without it. It is also the step that
discards everything currently stored, so it must be the most carefully verified.

**Independent Test**: Run the rebuild against an empty system and confirm the report shows a
per-collection match, that the run stops with a failure signal when a source file is deliberately
truncated, and that running it a second time from empty produces an identical corpus.

**Acceptance Scenarios**:

1. **Given** an empty system and complete canonical source files, **When** the operator runs the
   rebuild, **Then** every collection reports matching expected and produced counts and the run
   finishes successfully.
2. **Given** a source file with a deliberately removed record, **When** the operator runs the
   rebuild, **Then** the run reports the specific shortfall and finishes with a failure signal, and
   no content is published.
3. **Given** a completed rebuild, **When** the operator runs the whole rebuild again from empty,
   **Then** the resulting corpus is identical — no duplicated or differing hadiths, people, or
   narration links.
4. **Given** a rebuild that failed at any step, **When** the operator corrects the cause and runs it
   again from empty, **Then** it completes without any manual cleanup of partial results.
5. **Given** source records that legitimately carry no Arabic text, **When** the rebuild runs,
   **Then** those records are created but withheld from publication, and their count is reported and
   matches the documented known figure rather than being treated as an error.
6. **Given** a rebuild that fails partway through one collection, **When** the operator inspects the
   result, **Then** that collection is left whole — either fully imported or not imported — rather
   than half-written.

---

### User Story 2 - Read and cite a hadith at a stable address (Priority: P2)

A reader opens a collection, browses to a book and chapter, opens a hadith, and reads it in Arabic
and English with its scholarly citation lines and its authenticity verdict. They copy the page
address into a message; anyone who opens it lands on the same hadith.

**Why this priority**: This is the primary public value of the whole module. It is what most visitors
come for.

**Independent Test**: With a single imported collection, navigate collection → book → chapter →
hadith, confirm the address resolves consistently, and confirm two hadiths that share a displayed
scholarly number remain separately reachable.

**Acceptance Scenarios**:

1. **Given** a published collection, **When** the reader opens it, **Then** they see its books in
   source order, or its hadiths directly when the collection has no books.
2. **Given** a published hadith, **When** the reader opens its address, **Then** they see the Arabic
   text, the English text, the citation lines exactly as the source prints them, and the verdict
   where one exists.
3. **Given** two hadiths in one collection that display the same scholarly number, **When** the
   reader opens each, **Then** each resolves to its own distinct hadith.
4. **Given** a hadith whose collection, book, or chapter is unpublished, **When** anyone requests it
   publicly, **Then** it is not shown, regardless of the hadith's own publication state.
5. **Given** a hadith whose source asserts conflicting verdicts, **When** the reader opens it,
   **Then** no verdict badge is shown, and the verbatim source wording is still available.
6. **Given** an address that used the retired identifier scheme, **When** it is requested, **Then**
   the system responds "not found" — retired addresses are removed, not redirected.

---

### User Story 3 - Search in Arabic or English (Priority: P3)

A reader searches for a phrase. Arabic searches match whether or not the reader typed diacritics, and
whether they used a variant letter form. English searches match the translation. Results are ordered
by relevance and can be narrowed by collection and by verdict.

**Why this priority**: Search is the second-most-used entry point after browsing, and Arabic search
that fails on diacritics is perceived as broken.

**Independent Test**: With one imported collection, run the same Arabic query with and without
diacritics and confirm identical result sets; run a two-character Arabic query and confirm it
returns results.

**Acceptance Scenarios**:

1. **Given** a hadith containing a diacritised Arabic phrase, **When** the reader searches the same
   phrase without diacritics, **Then** that hadith appears in the results.
2. **Given** an Arabic query using a variant letter form, **When** the reader searches, **Then**
   matching hadiths using the other form still appear.
3. **Given** a two-character Arabic search term, **When** the reader searches, **Then** results are
   returned rather than an empty set.
4. **Given** search results, **When** the reader filters by collection or by verdict, **Then** only
   matching hadiths remain and result counts reflect the filter.
5. **Given** unpublished hadiths matching the query, **When** the reader searches publicly, **Then**
   they do not appear.

---

### User Story 4 - One person, many roles (Priority: P4)

A reader opens a historical figure's profile and sees one page: who they were, which roles they held
(companion, narrator, scholar, grader, compiler), and the hadiths they narrated. An editor correcting
that person's name corrects it once.

**Why this priority**: This is the structural payoff of the rebuild — it removes the duplicate-person
problem that makes cross-linking impossible today.

**Independent Test**: Create one person holding three roles, confirm they appear in all three role
directories, confirm their profile lists their narrations, and confirm a name correction propagates
everywhere at once.

**Acceptance Scenarios**:

1. **Given** a person who was a companion, narrated hadith, and graded hadith, **When** their profile
   is opened, **Then** it is a single profile listing all three roles.
2. **Given** that person, **When** a reader browses the companions directory and the narrators
   directory, **Then** the same person appears in both and both link to the same profile.
3. **Given** a person who narrated hadiths, **When** their profile is opened, **Then** the hadiths
   they narrated are listed and each links to the hadith.
4. **Given** an editor corrects a person's name, **When** they save, **Then** the corrected name
   appears everywhere that person is referenced, without a second edit.
5. **Given** a person record that is not published, **When** a reader requests their profile,
   **Then** it is not shown, while the person remains usable internally as a narrator, parent, or
   relation.

---

### User Story 5 - Explore the family tree from any person (Priority: P5)

A reader opens a person and views the family tree rooted at them: ancestors upward, descendants
downward, with spouses, siblings, and extended relations shown alongside. An editor building the tree
picks any person in the system as a parent or relation from a searchable list.

**Why this priority**: Genealogy is a distinct feature area that depends on unified identity but is
not required for hadith reading or search to work.

**Independent Test**: Build a three-generation family, render the tree from a middle person, and
confirm both ancestors and descendants appear and are correctly ordered.

**Acceptance Scenarios**:

1. **Given** any person in the system, **When** a reader views their tree, **Then** it renders rooted
   at that person with ancestors and descendants.
2. **Given** an editor assigning a parent, **When** they open the picker, **Then** they can search
   any person by Arabic or English name, including unpublished people, and cannot select the person
   being edited.
3. **Given** an editor tries to make a person their own ancestor, directly or through a chain,
   **When** they save, **Then** the assignment is rejected with an explanation.
4. **Given** a person marked as hidden from the tree, or an unpublished person, **When** the tree is
   rendered publicly, **Then** they are omitted from the rendering while remaining selectable to
   editors.
5. **Given** siblings under one parent, **When** the tree is rendered, **Then** they appear in the
   order the editor set.
6. **Given** a person who is someone's parent, **When** an editor tries to delete them, **Then** the
   deletion is refused rather than silently detaching the subtree.
7. **Given** a spouse or sibling relationship recorded once, **When** either person's tree is viewed,
   **Then** the relationship appears from both sides.

---

### User Story 6 - Manage content after the rebuild (Priority: P6)

An editor creates, edits, publishes, and unpublishes collections, books, chapters, hadiths, and
people through the admin panel, in Arabic and English, with Arabic as the default interface language.

**Why this priority**: Ongoing correction matters, but only after the corpus exists and is readable.

**Independent Test**: Create a collection, add a book, add a hadith, publish the chain, and confirm
it appears publicly; unpublish the collection and confirm the hadith disappears.

**Acceptance Scenarios**:

1. **Given** an editor, **When** they create or edit a hadith or person, **Then** they can enter both
   Arabic and English content, with the admin interface presented in Arabic by default.
2. **Given** an editor edits a name or hadith text, **When** they save, **Then** search finds the
   record by the new wording without a separate rebuild step.
3. **Given** two editors open the same hadith, **When** the second saves over the first's change,
   **Then** they are warned rather than silently overwriting.
4. **Given** an editor assigns a verdict to a hadith, **When** they save, **Then** exactly one
   verdict is recorded and displayed.
5. **Given** an editor publishes a hadith whose Arabic text is missing, **When** they save, **Then**
   publication is refused with an explanation.

---

### Edge Cases

- **Missing Arabic text.** A documented set of source records has no Arabic. They are imported,
  withheld from publication, and counted — never fabricated, never silently dropped.
- **Repeated displayed numbers.** Two hadiths in one collection may print the same scholarly number,
  and two chapters in one book may print the same chapter number. Both must remain individually
  reachable.
- **Collections with no books, and books with no chapters.** Both are real. Browsing must work
  without inventing placeholder structure that a reader can see.
- **A chapter with no parent book.** Structurally impossible and must be refused.
- **Conflicting or scoped verdicts.** When a source asserts one verdict about the chain of narration
  and another about the hadith body, or asserts a verdict about a numbered part only, or asserts two
  different verdicts, no verdict is displayed and the verbatim source wording is retained.
- **Compiler attribution mistaken for a verdict.** A statement naming who included a hadith in their
  collection is membership, not authenticity, and must never produce a verdict.
- **Arabic and English source verdicts disagree.** One is chosen by a fixed precedence and the
  disagreement is reported to the operator.
- **The same narrator appearing twice in one chain.** Must not be rejected until the corpus is
  verified to never contain it.
- **Family tree cycles.** Self-parenting, direct cycles, and deep cycles must all be refused, and
  tree rendering must be bounded so a bad edge cannot render forever.
- **Content elsewhere pointing at removed records.** Starting from empty leaves nothing pointing
  anywhere. The risk moves to restoration: content deliberately exported and brought back must be
  re-matched by stable public address, because a numeric identifier that still exists after the
  rebuild almost certainly belongs to a *different* record than it did before — a silently wrong link
  is worse than a broken one.
- **Rebuild interrupted midway.** There is no way back and none is needed: the recovery is to fix the
  cause and run the whole rebuild again from empty. What must not happen is a second attempt
  inheriting anything from the first.
- **Content outside these modules.** The rebuild discards everything stored, not only hadith and
  person records. Editorial articles, media, and accounts are gone unless exported beforehand, and
  the operator must be warned before the point of no return rather than after.

## Requirements *(mandatory)*

### Functional Requirements

#### Identity and roles

- **FR-001**: The system MUST represent each historical figure as a single record, regardless of how
  many capacities they appear in.
- **FR-002**: The system MUST allow a person to hold any combination of the roles companion,
  narrator, scholar, grader, and compiler, and MUST allow further roles to be added without new
  person records.
- **FR-003**: The system MUST allow a person to be listed with only an Arabic name or only an English
  name, and MUST refuse a person with neither.
- **FR-004**: The system MUST support per-role directories that can be filtered by featured status,
  gender, and publication state, and ordered for display.
- **FR-005**: The system MUST let a person be created and referenced internally while their public
  profile remains hidden.
- **FR-006**: The system MUST record birth and death years on the Islamic calendar, including years
  before the Hijra.

#### Genealogy

- **FR-007**: The system MUST record at most one father and one mother per person, each selected from
  the same person records used everywhere else.
- **FR-008**: The system MUST record spouse, sibling, uncle, aunt, and cousin relationships, storing
  each relationship once and presenting it from both people's perspectives.
- **FR-009**: The system MUST refuse any parent assignment that would make a person their own
  ancestor, whether directly or through a chain of any length.
- **FR-010**: The system MUST render a family tree rooted at any selected person, showing ancestors
  and descendants, bounded by a maximum depth.
- **FR-011**: The system MUST let editors control sibling display order and mark individuals as
  hidden from the rendered tree while keeping them selectable in editing.
- **FR-012**: The system MUST refuse deletion of a person who is recorded as another person's parent.

#### Hadith structure and identity

- **FR-013**: The system MUST give every hadith one stable public address, unique within its
  collection, that does not change when displayed numbering is corrected.
- **FR-014**: The system MUST allow the displayed scholarly number to repeat within a collection
  without affecting addressability.
- **FR-015**: The system MUST verify that public addresses are unique across the entire corpus before
  the corpus is created, and MUST stop the rebuild if they are not.
- **FR-016**: The system MUST support collections with and without books, and books with and without
  chapters, without exposing placeholder structure to readers.
- **FR-017**: The system MUST refuse structurally impossible records — a chapter without a book, or a
  hadith attached to a book or chapter belonging to a different collection.
- **FR-018**: The system MUST identify collections publicly by a single readable key, and MUST
  respond "not found" for the retired identifier-based addresses.

#### Hadith content and grading

- **FR-019**: The system MUST store Arabic and English hadith text, and MUST allow either to be
  absent.
- **FR-020**: The system MUST refuse to publish a hadith that has no Arabic text.
- **FR-021**: The system MUST record at most one authenticity verdict per hadith, drawn from a fixed
  set, and MUST allow no verdict.
- **FR-022**: The system MUST derive that verdict from the source by a written, deterministic rule:
  only unlabelled hadith-scoped assertions are considered; exactly one distinct assertion yields that
  verdict; conflict, chain-only scope, part-specific scope, or unrecognised wording yields no
  verdict; compiler attribution never yields a verdict.
- **FR-023**: The system MUST retain the verbatim source wording of the verdict alongside the derived
  verdict, and MUST report every unrecognised wording to the operator.
- **FR-024**: The system MUST reproduce scholarly citation lines exactly as the source prints them,
  including the case where the number appears mid-line, and MUST NOT fabricate a citation line that
  the source does not provide.
- **FR-025**: The system MUST record each hadith's chain of narrators in order, mark one narrator as
  primary, and refuse more than one primary per hadith.
- **FR-026**: The system MUST preserve the narrator name exactly as the source wrote it alongside the
  link to the person record.
- **FR-027**: The system MUST NOT carry forward the separate chain-of-narration text, hadith body
  text, translator, or volume designation. Their admin inputs and public output fields are removed
  with them, and volume-based navigation is withdrawn. The narrator chain remains available as
  ordered links to people (FR-025) and the source's own wording of each narrator name is preserved
  (FR-026).

#### Search and reading

- **FR-028**: The system MUST match Arabic searches irrespective of diacritics, elongation marks, and
  common letter-form variants.
- **FR-029**: The system MUST return results for Arabic search terms as short as two characters.
- **FR-030**: The system MUST search Arabic and English independently and merge the results by
  relevance.
- **FR-031**: The system MUST allow search results to be narrowed by collection and by verdict.
- **FR-032**: The system MUST offer a random published hadith.
- **FR-033**: The system MUST hide a hadith from every public path — reading, browsing, search,
  random, topic groupings, and favourites — unless the hadith and every one of its parent records are
  published.
- **FR-034**: The system MUST serve public content in Arabic and English only.
- **FR-035**: The system MUST update search results to reflect an editor's change without a separate
  reindexing step.

#### Rebuild from scratch

- **FR-036**: The system MUST build the entire corpus from the canonical source files, treating them
  as the sole source of truth, with no preservation of existing records or identifiers.
- **FR-036a**: The rebuild MUST start from an empty system. It MUST NOT convert, adapt, or tear down
  the previous structure step by step — the previous structure is simply no longer described. No part
  of the new definition may reference the old.
- **FR-037**: The system MUST report, per collection, expected versus produced counts for
  collections, books, chapters, hadiths, narration links, and people, plus unrecognised verdicts,
  unresolved people, and the count of records legitimately lacking Arabic.
- **FR-038**: The system MUST finish with a failure signal on any count mismatch, and MUST NOT
  publish content when it does.
- **FR-039**: The system MUST leave each collection either fully imported or not imported, never
  partially written.
- **FR-040**: The system MUST produce the same corpus when run repeatedly, without duplicating
  records.
- **FR-041**: Because the rebuild starts from empty, no saved favourite, topic entry, or article
  passage may survive pointing at a replaced record. Where content is deliberately exported before
  the rebuild and restored afterwards, it MUST be matched back by stable public address or readable
  key, never by a numeric identifier — a reused identifier would point at a different record than it
  did before.
- **FR-042**: The rebuild MUST be repeatable end to end from an empty system, and repeating it MUST
  be the recovery path for any failure. No step may require manual cleanup of a previous attempt.
- **FR-042a**: The operator MUST be told, before the first destructive action, that the rebuild
  discards all stored content and not only hadith and person records, so that anything worth keeping
  is exported first.

#### Administration

- **FR-043**: Editors MUST be able to create, edit, publish, and unpublish collections, books,
  chapters, hadiths, people, roles, and family relationships.
- **FR-044**: The admin interface MUST be presented in Arabic by default, with English available, and
  MUST support entering both languages for every translatable field.
- **FR-045**: The system MUST warn an editor whose save would overwrite another editor's concurrent
  change.
- **FR-046**: The system MUST select parents and relations through a searchable person picker that
  searches both Arabic and English names, includes unpublished people, and excludes the record being
  edited.
- **FR-047**: The system MUST retire companion battle participation, per-person timelines, and
  companion categories. The public pages, directory filters, and endpoints that expose them are
  removed rather than left pointing at absent content, and requesting a retired address MUST return
  "not found". Role directories (FR-004) are the only remaining way to group people.
- **FR-048**: The system MUST support exactly one portrait image per person and MUST NOT provide a
  gallery of additional images. Profile layouts that render a gallery today are reworked to the
  single portrait.

### Key Entities

- **Muslim** — a historical figure. Holds names and biography in Arabic and English, honorific,
  gender, birth and death years, birthplace, generation, a portrait, publication state, and descent
  links to a father and a mother. The single identity used by hadith narration, grading, collection
  compilation, companion directories, and genealogy.
- **Role** — a capacity a person held: companion, narrator, scholar, grader, compiler. Named in
  Arabic and English.
- **Role Assignment** — the link between a person and a role, carrying featured status and display
  order within that role's directory.
- **Family Relationship** — a non-descent link between two people: spouse, sibling, uncle, aunt, or
  cousin. Stored once, readable from both sides, with optional notes and display order.
- **Hadith Collection** — a named body of hadith with a compiler, an introduction, a citation format,
  and flags for whether it uses books and chapters.
- **Book** — a division of a collection, with a source number, title, introduction, and the range of
  hadith numbers it covers.
- **Chapter** — a division of a book, with a source number that may repeat, a title, an introduction,
  and a closing note.
- **Hadith** — a single narration. Carries its stable public address, its displayed scholarly number,
  its position within collection and book, Arabic and English text, its verdict and the verbatim
  source wording, its citation lines, and publication state.
- **Narration Link** — the ordered link between a hadith and a person in its chain, marking one as
  primary and preserving the source's spelling of the name.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Every hadith in the canonical sources is present after the rebuild — per-collection
  expected and produced counts match exactly, with the sole documented exception being the known set
  of records lacking Arabic text, which are present but unpublished.
- **SC-002**: Every published hadith is reachable at exactly one stable public address, and no two
  hadiths in a collection share one; verified across 100% of the corpus before publication.
- **SC-003**: A reader can go from a collection to a specific hadith in no more than three steps.
- **SC-004**: An Arabic search typed without diacritics returns the same hadiths as the identical
  search typed with them, for 100% of tested phrases.
- **SC-005**: Search results appear fast enough to feel immediate for a reader on a typical
  connection, and the perceived speed does not degrade as more collections are added.
- **SC-006**: Every person who appears in more than one capacity has exactly one profile — zero
  duplicate person records across companions, narrators, scholars, graders, and compilers.
- **SC-007**: Correcting a person's name requires exactly one edit and is reflected everywhere that
  person appears.
- **SC-008**: No hadith with an unpublished collection, book, or chapter is reachable through any
  public path — reading, browsing, search, random, topics, or favourites.
- **SC-009**: Every displayed authenticity verdict is reproducible from the source by the written
  rule, and re-running the rebuild produces an identical set of verdicts.
- **SC-010**: A family tree renders from any person in the system, and no cyclic parent assignment
  can be saved.
- **SC-011**: Zero references anywhere in the system point at a record that no longer exists, and
  zero references point at a record different from the one they described before the rebuild.
- **SC-012**: The rebuild runs end to end from an empty system and produces an identical corpus every
  time — zero duplicate or differing hadiths, people, or narration links between two runs, and zero
  manual cleanup steps required between them.

## Assumptions

Reasonable defaults applied where the source material did not decide the question. Each can be
overridden without reshaping the feature.

- **Published counts are calculated, not stored.** A stored total cannot stay correct once an editor
  publishes or unpublishes a single hadith, so counts shown to readers are computed at read time.
- **People are deleted reversibly.** Deletion marks a person as removed and can be undone by an
  administrator, rather than erasing the record. This is safer given that people are referenced from
  hadiths, collections, and the family tree.
- **Grouping people is done by role, not by category.** With companion categories retired (FR-047),
  any grouping worth keeping is expressed as a role. A category that named a genuine capacity —
  "narrator", "scribe" — becomes a role during the rebuild; a category that was a thematic label is
  not carried forward.
- **Public content is Arabic and English only.** French, Turkish, and Urdu hadith content is retired
  with this rebuild.
- **No provenance is retained.** Because this is a one-time rebuild, no external source addresses,
  external identifiers, or change-detection fingerprints are kept. Scholarly citation strings are
  display content and are kept.
- **Verdict precedence is English first.** Where a source carries different Arabic and English
  wording for the same verdict, the English wording is authoritative and the disagreement is reported
  to the operator.
- **Family tree depth is bounded** at a fixed maximum, chosen to comfortably exceed the deepest real
  lineage in the corpus.

## Out of Scope

- Migrating, preserving, or remapping any existing record identifiers.
- Any upgrade path from the current structure: no conversion, no step-by-step teardown, no
  intermediate state in which both structures exist. The system is rebuilt from empty.
- Backward-compatible addresses, redirects, or compatibility layers for the current structure.
- Recurring or scheduled imports, and any persistent history of import runs — the rebuild is a
  one-time operator-supervised event.
- Per-scholar attribution of authenticity verdicts, and verdicts scoped to a chain or to a numbered
  part of a hadith. Only a single hadith-level verdict is represented.
- Hadith theme and grading-level catalogues as standalone features; thematic grouping is served by
  the existing cross-module topics capability.

Retired with this rebuild — features reachable today that will not exist afterwards:

- **Companion battle participation** — battle filtering and the "participated in" profile block.
- **Per-person timelines** — the timeline of events attached to an individual.
- **Companion categories** — the multi-valued companion taxonomy and category browsing, except where
  a category names a genuine capacity and becomes a role.
- **The public pages, directory filters, and endpoints** built on those three — featured companions,
  early converts, top narrators, and browsing by category, status, tribe, or battle.
- **Person image galleries** — one portrait per person replaces them.
- **Separate chain-of-narration text, hadith body text, translator, and volume fields**, and the
  volume-based navigation built on the last of these.
