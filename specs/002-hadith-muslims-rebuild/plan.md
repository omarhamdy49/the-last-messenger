# Full Hadith and Muslim Identity Rebuild

## Summary

Replace the existing Hadith and person implementations with a clean, end-to-end schema and module. `muslims` becomes the application-wide identity source for companions, narrators, scholars, graders, compilers, and genealogy participants.

The replacement will use explicit Arabic/English columns, preserve scholarly grading claims and source provenance, support repeated source numbering safely, and rebuild the import, admin, public web, API, and test surfaces together.

## Target Schema and Indexes

### Muslim identity

- `islamic_roles`
  - `id`, `slug varchar(100) UNIQUE`, `name_ar`, `name_en`, timestamps.
  - Do not add standalone indexes on translated names unless role search is implemented.

- `muslims`
  - `id`, `thumbnail_path nullable`, `slug varchar(200) UNIQUE`.
  - `name_ar`, `name_en`, nullable `kunya_ar`, `kunya_en`.
  - Nullable `gender` backed by a PHP enum.
  - Nullable `birth_place_ar`, `birth_place_en`, signed `birth_year_hijri`, `death_year_hijri`, and `generation`.
  - Nullable `bio_ar`, `bio_en` as medium text and `content_ar`, `content_en` as long text.
  - Normalized `search_name_ar` and `search_name_en`.
  - `is_published default false`, timestamps.
  - Index `(is_published, id)` and separate Arabic/English FULLTEXT indexes on normalized search names.

- `muslims_roles`
  - `muslim_id`, `role_id`, `is_featured default false`, `sort_order default 0`, timestamps.
  - Composite primary key `(muslim_id, role_id)`.
  - Index `(role_id, is_featured, sort_order, muslim_id)` for role directories and featured people.

- `muslim_external_identifiers`
  - Preserve import identity without coupling canonical records to one provider.
  - `muslim_id`, `source`, `external_id`, nullable `source_url`, nullable JSON metadata.
  - Unique `(source, external_id)` and index `(muslim_id)`.

- Re-key genealogy relationships, timelines, and other person-owned data to `muslim_id`. Represent historical classifications such as companion, narrator, grader, scholar, and compiler through roles instead of duplicate person tables.

### Hadith hierarchy

- `hadith_collections`
  - Rename `thumb` to `thumbnail_path`, `auther_id` to `compiler_id`, `order` to `sort_order`, and `has_chapter` to `has_chapters`.
  - Keep explicit titles and nullable medium-text introductions for Arabic and English.
  - `compiler_id` is a nullable FK to `muslims` with restricted deletion.
  - Keep `has_books`, `has_chapters`, `is_featured`, and `is_published`.
  - Add source URL/metadata and timestamps.
  - Enforce `has_chapters = false OR has_books = true`.
  - Remove `total_hadith`; return a derived `hadith_count` using grouped counts or cached query results.
  - Index `(is_published, sort_order, id)` and `(is_published, is_featured, sort_order, id)`. The unique slug already supplies its index.

- `hadith_books`
  - Use string `book_number`; source numbering is not reliably numeric.
  - Replace `from`/`to` with nullable string `first_hadith_number` and `last_hadith_number`.
  - Add `sort_order`, bilingual titles/intros, source provenance, publication flag, and timestamps.
  - Restrict deletion while chapters or hadiths reference the book.
  - Unique `(collection_id, book_number)` and `(collection_id, sort_order)`.
  - Index `(collection_id, is_published, sort_order)`.
  - Add unique `(id, collection_id)` as the parent key for composite integrity.

- `hadith_chapters`
  - Use string `chapter_number`; do not make it unique because real books repeat chapter numbers.
  - Add `sort_order`, bilingual title, nullable bilingual intro/ending, provenance, publication flag, and timestamps.
  - Unique `(book_id, sort_order)`.
  - Index `(book_id, chapter_number)` and `(book_id, is_published, sort_order)`.
  - Add unique `(id, book_id)` for composite integrity and restrict deletion while referenced.

### Hadith records

- `hadiths`
  - Use the conventional plural table name.
  - Required `collection_id`; nullable `book_id` and `chapter_id` permit genuinely bookless collections without synthetic records.
  - Add `reference_key varchar(100)` as the stable public identifier, unique within a collection.
  - Keep `number varchar(50)` as the displayed scholarly number and allow duplicates.
  - Add nullable `in_book_number` and required collection-scoped `sort_order`.
  - Store `display_grade` as a nullable enum derived from grading claims.
  - `text_ar longtext` required; `text_en longtext` nullable.
  - Maintain normalized `search_ar` and nullable `search_en`, each with its own FULLTEXT index.
  - Add exact source identifier, URL, canonical/in-book/translation references, SHA-256 checksum, and optional JSON metadata.
  - Add `is_published default false` and timestamps.
  - Unique `(collection_id, reference_key)`, `(collection_id, sort_order)`, and nullable `(collection_id, source_id)`.
  - Non-unique `(collection_id, number)` for scholarly-reference lookup.
  - Reading indexes `(book_id, is_published, sort_order)` and `(chapter_id, is_published, sort_order)`.
  - Filtering index `(is_published, display_grade, id)`.
  - Composite FKs ensure the selected book belongs to the collection and chapter belongs to the book.
  - Add a check preventing `chapter_id` without `book_id`.

- `hadith_narrators`
  - `hadith_id`, `narrator_id FK -> muslims`, `position`, `is_primary`, optional bilingual source display names, and timestamps.
  - Primary key `(hadith_id, position)` and unique `(hadith_id, narrator_id)`.
  - Index `(narrator_id, hadith_id)` for narrator profiles.
  - Enforce at most one primary narrator per hadith through a nullable generated primary slot plus a unique constraint; also validate transactionally in the domain service.
  - Rename `primary` to `is_primary`.

- `hadith_gradings`
  - Store one sourced scholarly claim per row: `hadith_id`, nullable `grader_id FK -> muslims`, nullable normalized `grade`, `scope` (`hadith` or `isnad`), nullable `part_label`, `raw_grade`, locale, authority rank, notes, source reference/URL, and timestamps.
  - Preserve unrecognized claims with `grade = null` and their verbatim `raw_grade`.
  - Derive `hadiths.display_grade` only when all normalized, whole-hadith, non-part claims agree; otherwise store `null`.
  - Index `(hadith_id, scope, authority_rank)`, `(grade, hadith_id)`, and `(grader_id, hadith_id)`.

- `hadith_import_runs`
  - Record collection, source path/checksum, status, expected/actual counts, warnings, errors, and start/completion timestamps.
  - Index `(collection_id, status, created_at)`.
  - Imports run transactionally per collection, use source identifiers/reference keys for idempotency, regenerate normalized search fields, recompute display grades, and verify source/database parity before publishing.

## Application and Interface Changes

- Replace the Hadith domain models, repositories, services, import commands, admin controllers, API resources, Blade pages, factories, and tests as one cutover.
- Replace Sahaba/scholar/person models with `Muslim`; update genealogy and all foreign keys atomically without compatibility adapters.
- Public Hadith URLs and API lookups use `{collectionSlug}/{referenceKey}`. The displayed `number` remains searchable but is never assumed unique.
- Add Muslim directory/profile endpoints with role, featured, gender, and publication filters; narrator Hadith listings are exposed through the Muslim profile.
- Preserve collection, book, chapter, search, random Hadith, and detail capabilities with new response resources.
- Public visibility requires the Hadith and all applicable ancestors to be published. Unpublished Muslim profiles remain hidden even when their identity is used internally.
- Cap pagination, eager-load fixed relation sets, return derived counts, and avoid N+1 queries.
- Arabic normalization removes tashkeel/tatweel and folds relevant letter variants. Configure MySQL FULLTEXT for two-character Arabic tokens and rebuild indexes after configuration changes.
- Admin workflows manage Muslims/roles, hierarchy, bilingual texts, narrator ordering, grading claims, publication, and import audits through transactional services.

## Cutover and Test Plan

- Take an external database snapshot, enter maintenance mode, pause workers, drop legacy Hadith/person tables in dependency order, create the new schema, import canonical Muslim and Hadith data, verify parity, then enable publication and clear caches.
- Do not preserve legacy IDs or maintain legacy compatibility tables/routes.
- Schema tests cover composite FK mismatches, restricted deletion, nullable bookless records, chapter-without-book rejection, repeated Hadith/chapter numbers, unique reference keys, pivot uniqueness, and single-primary-narrator enforcement.
- Grading tests cover agreement, disagreement, isnad-only claims, part-specific claims, unknown raw grades, and transactional display-grade recomputation.
- Import tests cover repeated idempotent imports, rollback on failure, missing English text, duplicate displayed numbers, source checksum changes, unresolved people, and exact row-count parity.
- Search tests cover Arabic with/without diacritics, two-character terms, English search, collection/grade filters, unpublished ancestors, relevance ordering, and pagination limits.
- API/web/admin tests cover the new route keys and resource shapes, 404 behavior, publication gating, narrator profiles, bilingual fallback behavior, CRUD validation, and fixed query counts.
- Run the full PHPUnit suite, dependency-boundary analysis, migration-from-empty tests, MySQL-specific constraint/FULLTEXT tests, and `EXPLAIN` assertions for the primary browse/search queries.

## Assumptions

- This is intentionally destructive and greenfield; canonical source files, not existing database rows, are the source of truth.
- Arabic and English are the only supported content locales for this version.
- The current uncommitted Hadith work is superseded by this replacement; unrelated workspace changes must remain untouched.
- No standalone indexes will be added for low-cardinality booleans; they appear only inside query-driven composite indexes.
