# Hadith public UI parity and gap review
**Scope:** `hadith-books-enhanced.html`, `hadith-book-single-enhanced.html`, `hadith-book-chapter-enhanced.html`, `hadith-single-enhanced.html`, `hadith-explorer.html`, `hadith-narration.html`  ·  **Method:** static read of prototypes + Laravel code + migrations

## Executive summary

| Module | Prototype pages | Implemented | Verdict (OK / PARTIAL / MISSING) | Top gap |
|---|---:|---:|---|---|
| Hadith public website | 6 | 6 routed Blade pages | PARTIAL | The explorer is a small collection/grade/text search rather than the prototype's faceted explorer; exact-text hashes also cannot model narration variants. `routes/web.php:81` — all six prototype destinations have concrete routes; `app/Http/Controllers/Site/HadithController.php:364` — the explorer advertises only full-text, collection, grading, sort, and pagination. |

- `routes/web.php:82` — every assigned prototype has a public page route; there is no missing-page P0 in the website route block.
- `resources/views/public/hadith/placeholder.blade.php:1` — the placeholder still exists, but `rg -n "public\\.hadith\\.placeholder|hadith/placeholder|placeholder\\.blade" routes app resources tests` returned no renderer/reference other than the file itself; no assigned route currently resolves to it.
- `app/Http/Controllers/Api/HadithController.php:123` — public JSON text search calls `HadithRepository::search()`, while `app/Domain/Hadith/Repositories/HadithRepository.php:151` queries a nonexistent `hadith_translations.translation` column; the actual migration defines only `text` at `database/migrations/2025_11_21_160005_create_hadith_translations_table.php:19`.
- `app/Http/Controllers/Api/HadithController.php:362` — the theme endpoint filters/selects `slug_ar`, `slug_en`, `name_ar`, and `name_en`, but `database/migrations/2025_11_21_160008_create_hadith_themes_table.php:15` creates only `id`, `theme_en`, and `theme_ar`; this public API path is not functional against the declared schema.
- `app/Domain/Hadith/Application/Handlers/GetParallelNarrationsHandler.php:47` — `/hadith/{id}/narrations` is implemented and resolves peers by exact `text_hash`; `app/Domain/Hadith/Services/HadithDeduplicationService.php:17` hashes only whitespace-normalized text, so it cannot find the prototype's “with addition” textual variants or isnad-only matches.
- `database/migrations/2026_03_09_000001_add_text_hash_to_hadith_hadiths_table.php:17` — narration lookup is indexed; the earlier claim that the comparison endpoint is absent is stale, but the claim that `text_hash` directly supports variant narrations is too strong.
- `app/Domain/Hadith/Services/HadithDeduplicationService.php:183` — deduplication retains duplicate rows and sets `merged_into_id`; `rg -n "merged_into_id|whereNull\\(['\\\"]merged" app/Http/Controllers/Site app/Http/Controllers/Api app/Domain/Hadith/Repositories app/Domain/Hadith/Application app/Domain/Hadith/Models/Hadith.php` found no public exclusion scope/query, so merged rows can remain independently listable and addressable.
- `app/Domain/Hadith/Repositories/HadithRepository.php:221` — explorer/related result rows select bounded columns and paginate, but eager-load all translation columns/languages while omitting `themes` and `book.collection`; `app/Domain/Hadith/Application/DTOs/HadithDTO.php:41` then reads those relations for every row, creating N+1 work at up to 50 results.
- `resources/views/public/hadith/explorer.blade.php:98` — the entire explorer UI is hardcoded Arabic and contains no `__()` call, despite five complete `resources/lang/*/hadith.php` key sets verified by static key comparison.
- `app/Http/Controllers/Site/HadithController.php:227` — chapter translation fallback is otherwise well designed: one page-wide existence query and one throttled batch endpoint replace per-row translation queries, and `app/Http/Controllers/Site/HadithController.php:657` falls back to Arabic/first available text.

## Hadith collections / books index

### hadith-books-enhanced.html → GET /hadith → resources/views/public/hadith/index.blade.php
**Status:** PARTIAL

`routes/web.php:82` — the route calls `App\Http\Controllers\Site\HadithController::index`; `app/Http/Controllers/Site/HadithController.php:97` renders `public.hadith.index`.

#### Sections & components

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Premium hero, title, subtitle, dark mode, global header/footer | Server-rendered localized hero and live totals inside the shared public layout. | Implemented | `../the-last-messenger-ui/hadith-books-enhanced.html:587` — prototype header begins; `resources/views/public/hadith/index.blade.php:154` — localized hero and stat chips. |
| Auto-playing featured carousel with hover pause, arrows, dots, rank and detailed slide | Featured collections render as a static responsive three-column rail; there is no slide state, timer, pause, arrows, dots, rank, or compilation-year panel. | Partial | `../the-last-messenger-ui/hadith-books-enhanced.html:640` — transform-based slide track; `../the-last-messenger-ui/hadith-books-enhanced.html:778` — dot controls; `resources/views/public/hadith/index.blade.php:198` — static featured grid. |
| Library search across title and author | Alpine filters title, subtitle, and author over the server payload. | Implemented | `../the-last-messenger-ui/hadith-books-enhanced.html:529` — prototype computed filter; `resources/views/public/hadith/index.blade.php:274` — debounced search input; `app/Http/Controllers/Site/HadithController.php:515` — mapped searchable collection fields. |
| Category pills with counts | Live categories/counts are derived from collection names and filter client-side. Categories differ from the prototype's curated early-book taxonomy. | Partial | `../the-last-messenger-ui/hadith-books-enhanced.html:812` — prototype pills; `app/Http/Controllers/Site/HadithController.php:753` — name-derived category mapping; `resources/views/public/hadith/index.blade.php:284` — pills and counts. |
| 3D book covers, spine/pages, hover description/CTA | Cards have cover/spine styling and hover lift, but not the prototype's full 3D pages/rotation overlay. | Partial | `../the-last-messenger-ui/hadith-books-enhanced.html:833` — prototype cover grid; `resources/views/public/hadith/index.blade.php:305` — implemented cover grid. |
| Collection metadata: Arabic/English title, author, description, counts, year, rank | Titles/descriptions/counts exist; author is always null and year/rank have no mapped fields. | Partial | `../the-last-messenger-ui/hadith-books-enhanced.html:642` — prototype slide consumes rich book data; `app/Domain/Hadith/Application/DTOs/CollectionDTO.php:45` — DTO sets `authorName` null and exposes no year/rank. |
| Search-empty/reset state | Localized empty state and reset button exist. | Implemented | `../the-last-messenger-ui/hadith-books-enhanced.html:898` — prototype empty state; `resources/views/public/hadith/index.blade.php:359` — implementation empty state. |

#### Features & wiring

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Collection cards open a real collection page | Links are generated from localized DB slugs to `public.hadith.book`. | Implemented | `resources/views/public/hadith/index.blade.php:212` — featured link; `routes/web.php:86` — collection route. |
| Search/category filtering | Real client-side filtering over all collections returned by the bounded index query; no backend endpoint is needed at the current 17-collection scale claimed in prior runtime docs. | Implemented | `app/Http/Controllers/Site/HadithController.php:60` — collections are fetched with `perPage: 50`; `resources/views/public/hadith/index.blade.php:274` — Alpine search. |
| Carousel autoplay and manual controls | No carousel behavior is wired. | Missing | `../the-last-messenger-ui/hadith-books-enhanced.html:556` — prototype advances `currentSlide`; `resources/views/public/hadith/index.blade.php:210` — implementation is a normal grid. |
| Dark-mode persistence | Delegated to the shared layout/store and consumed with `dark:` utilities. | Implemented | `resources/views/public/hadith/index.blade.php:151` — light/dark page shell; `resources/views/components/site/header.blade.php:64` — shared site header use. |
| Loading state | No asynchronous collection request occurs; server failure is converted to an empty payload, which is visually indistinguishable from no content. | Partial | `app/Http/Controllers/Site/HadithController.php:84` — exception returns empty books/categories/stats; `resources/views/public/hadith/index.blade.php:359` — only a search-empty state is rendered. |

#### Schema

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Collection identity and descriptions | `name_ar/name_en`, localized slugs, bilingual descriptions and total count exist. | Implemented | `database/migrations/2025_11_21_160000_create_hadith_collections_table.php:17` — collection content columns. |
| Featured ordering | `featured`, `active`, `display_order` and `(featured, display_order)` index exist. | Implemented | `database/migrations/2026_06_15_000000_add_featured_to_hadith_collections.php:20` — flags/order; `database/migrations/2026_06_15_000000_add_featured_to_hadith_collections.php:29` — composite index. |
| Author/compiler biography | Migration has no author FK/compiler/death/bio fields; DTO deliberately returns null. Stale model scopes still query nonexistent `compiler`. | Missing | `database/migrations/2025_11_21_160000_create_hadith_collections_table.php:15` — complete base table definition; `app/Domain/Hadith/Application/DTOs/CollectionDTO.php:52` — author is null; `app/Domain/Hadith/Models/HadithCollection.php:108` — stale compiler scope. |
| Compilation year and rank | No such columns are present in the collection migration or DTO. | Missing | `database/migrations/2025_11_21_160000_create_hadith_collections_table.php:15` — table fields end at totals/timestamps; `app/Domain/Hadith/Application/DTOs/CollectionDTO.php:17` — DTO field list has no compilation year/rank. |
| Five-locale collection copy | Collections use only Arabic/English columns and fall back to English then Arabic for every non-Arabic locale; there is no collection translation table. | Partial | `app/Domain/Hadith/Models/HadithCollection.php:82` — two-language accessor; `database/migrations/2026_03_10_100003_create_phase_4_translation_tables.php:98` — phase-4 creates source/theme/chapter translations, not collection translations. |
| Index query columns | Collection list adds `withCount` but does not call `select([...])`. | Partial | `app/Domain/Hadith/Repositories/CollectionRepository.php:188` — list builder; `app/Domain/Hadith/Repositories/CollectionRepository.php:224` — count then unqualified paginate. |

#### Enhancement gaps

- `resources/views/public/hadith/index.blade.php:210` — restore carousel controls/autoplay or intentionally simplify the prototype; current static rail violates the requested prototype parity (CLAUDE.md §5 frontend parity).
- `app/Http/Controllers/Site/HadithController.php:84` — add a distinct load-failure state rather than returning the same payload used for “no matching collections” (CLAUDE.md §5 loading/empty/error-state rule).
- `app/Domain/Hadith/Repositories/CollectionRepository.php:227` — add an explicit collection select list before pagination (CLAUDE.md §4 / task backend explicit-select rule).
- `resources/views/public/hadith/index.blade.php:198` — featured and library cards repeat substantial markup without a `resources/views/components/site/` Hadith collection-card component; only the site component inventory at `resources/views/components/site/pagination.blade.php:1` is reused (CLAUDE.md §5 reusable-component rule).

## Single collection / chapter list

### hadith-book-single-enhanced.html → GET /hadith/book/{slug} → resources/views/public/hadith/book.blade.php
**Status:** PARTIAL

`routes/web.php:86` — the route calls `App\Http\Controllers\Site\HadithController::book`; `app/Http/Controllers/Site/HadithController.php:165` renders `public.hadith.book`.

#### Sections & components

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Breadcrumb and collection hero | Present with title, alternate title, counts, description and actions. | Implemented | `../the-last-messenger-ui/hadith-book-single-enhanced.html:322` — prototype breadcrumb; `resources/views/public/hadith/book.blade.php:118` — implementation hero starts. |
| Hero authenticity, compilation year and bookmark | Counts and generic “high grade” copy render; compilation year is absent; bookmark is visual-only. | Partial | `../the-last-messenger-ui/hadith-book-single-enhanced.html:342` — prototype hero; `resources/views/public/hadith/book.blade.php:211` — browse/bookmark actions. |
| Four tabs: about, chapters, famous hadiths, author | Only about, chapters, and author exist. | Partial | `../the-last-messenger-ui/hadith-book-single-enhanced.html:433` — famous tab; `resources/views/public/hadith/book.blade.php:234` — three implemented tab buttons. |
| About statistics, description and characteristics | Present, but “high grade” and characteristics are generic localized marketing copy rather than collection data. | Partial | `../the-last-messenger-ui/hadith-book-single-enhanced.html:453` — prototype about section; `resources/views/public/hadith/book.blade.php:262` — implemented about section. |
| Searchable chapter/book grid | Present, with real counts/links and server pagination; search only filters the current 24-row page. | Partial | `../the-last-messenger-ui/hadith-book-single-enhanced.html:567` — prototype chapter section; `resources/views/public/hadith/book.blade.php:358` — grid; `app/Http/Controllers/Site/HadithController.php:123` — 24-row page. |
| Famous-hadith cards with narrator/source/bookmark/share | Entire tab/section is absent. | Missing | `../the-last-messenger-ui/hadith-book-single-enhanced.html:648` — prototype famous section; `rg -n "famous" resources/views/public/hadith/book.blade.php` returned no matches. |
| Author biography, dates, works, teachers, students and death | A generic paragraph assembled from collection counts renders; author identity and structured biography are absent. | Partial | `../the-last-messenger-ui/hadith-book-single-enhanced.html:769` — rich author section; `resources/views/public/hadith/book.blade.php:471` — generic author tab; `app/Domain/Hadith/Application/DTOs/CollectionDTO.php:52` — no author data. |
| Pagination and empty state | Shared server pagination and client-search empty state exist. | Implemented | `resources/views/public/hadith/book.blade.php:452` — empty reset; `resources/views/public/hadith/book.blade.php:460` — pagination. |

#### Features & wiring

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Browse chapters | Real links route to collection book-number pages. | Implemented | `resources/views/public/hadith/book.blade.php:436` — chapter-card link; `routes/web.php:87` — destination route. |
| Search chapters | Alpine filters only `$booksJson` for the current server page; matches outside page 1 cannot be found without navigating first. | Partial | `resources/views/public/hadith/book.blade.php:73` — client array filter; `app/Http/Controllers/Site/HadithController.php:126` — 24-row pagination. |
| Collection bookmark/toast | Alpine toggles local state and toast only; it neither persists nor initializes from server truth. The shared favorites registry supports `Hadith`, not `HadithCollection`. | Partial | `resources/views/public/hadith/book.blade.php:92` — local toggle; `app/Providers/FavoriteServiceProvider.php:39` — Hadith registration only. |
| Famous-hadith save/share | No section and no wiring. | Missing | `../the-last-messenger-ui/hadith-book-single-enhanced.html:647` — section begins; `resources/views/public/hadith/book.blade.php:471` — implementation proceeds directly from chapters to author. |
| Search/filter loading | No asynchronous request occurs, and pagination uses the shared page navigation. | Implemented | `resources/views/public/hadith/book.blade.php:460` — server pagination. |

#### Schema

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Books/chapters within a collection | `hadith_books.collection_id`, number/title/slug columns and counts support the grid. | Implemented | `database/migrations/2025_11_21_160001_create_hadith_books_table.php:17` — collection relationship and titles; `database/migrations/2026_05_19_000001_add_hadith_source_identity_and_review_fields.php:23` — book number and composite index. |
| Author relation and biography | No collection author FK or author metadata exists in migrations even though CQRS create/update handlers write `author_id`. | Missing | `app/Domain/Hadith/Application/Handlers/CreateCollectionHandler.php:57` — writes `author_id`; `database/migrations/2025_11_21_160000_create_hadith_collections_table.php:15` — no such column. |
| Famous/featured hadith curation | `Hadith` has no featured/famous flag, rank, or collection-curation pivot. | Missing | `app/Domain/Hadith/Models/Hadith.php:52` — fillable public/editorial fields include no curation marker; `database/migrations/2025_11_21_160004_create_hadith_hadiths_table.php:15` — base schema has none. |
| Rich book/collection translations | Only `title_ar/title_en` and `description_ar/description_en` exist; no collection/book translation table supports FR/TR/UR. | Partial | `database/migrations/2025_11_21_160001_create_hadith_books_table.php:18` — bilingual title fields; `database/migrations/2026_03_10_100003_create_phase_4_translation_tables.php:98` — no collection/book table in the Hadith translation group. |
| Bounded books query | Page size is 24, but `paginateByCollection()` has no explicit `select` and interpolates internal sort column/direction without a repository allowlist. | Partial | `app/Domain/Hadith/Repositories/BookRepository.php:197` — query method; `app/Domain/Hadith/Repositories/BookRepository.php:203` — no select and dynamic `orderBy`. |

#### Enhancement gaps

- `resources/views/public/hadith/book.blade.php:234` — add the prototype's famous-hadith tab backed by explicit editorial curation (CLAUDE.md §5 prototype-section parity).
- `resources/views/public/hadith/book.blade.php:73` — move chapter search into the paginated query so it searches all 406 books rather than one page (CLAUDE.md §4 database filtering/pagination rule).
- `resources/views/public/hadith/book.blade.php:92` — either wire bookmarks to a new allowlisted collection type or remove the false-persistence affordance (CLAUDE.md §6 truthful, authorized mutations).
- `app/Domain/Hadith/Repositories/BookRepository.php:203` — add an explicit select and allowlist `sortBy/sortDirection` at the repository boundary (CLAUDE.md §4 explicit-select and task dynamic-sort rules).

## Book/chapter Hadith listing

### hadith-book-chapter-enhanced.html → GET /hadith/book/{slug}/chapter/{chapter} → resources/views/public/hadith/chapter.blade.php
**Status:** PARTIAL

`routes/web.php:87` — the route calls `App\Http\Controllers\Site\HadithController::chapter`; `app/Http/Controllers/Site/HadithController.php:241` renders `public.hadith.chapter`.

#### Sections & components

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Breadcrumb, bilingual chapter header, description/count | Breadcrumb/title/count exist; there is no chapter description or separate English subtitle in the mapped context. | Partial | `../the-last-messenger-ui/hadith-book-chapter-enhanced.html:546` — prototype breadcrumb/header; `app/Http/Controllers/Site/HadithController.php:241` — mapped book-level chapter data. |
| Previous/index/next navigation | Real adjacent book-number navigation exists. | Implemented | `../the-last-messenger-ui/hadith-book-chapter-enhanced.html:562` — prototype controls; `resources/views/public/hadith/chapter.blade.php:174` — implementation navigation. |
| Search within chapter | GET search is applied in the database and preserves bounded pagination. | Implemented | `resources/views/public/hadith/chapter.blade.php:204` — GET toolbar; `app/Domain/Hadith/Repositories/HadithRepository.php:394` — scoped translation search. |
| Authenticity filters | All/Sahih/Hasan/Daif pills exist; controller allowlists the three values. | Implemented | `../the-last-messenger-ui/hadith-book-chapter-enhanced.html:374` — prototype filtering; `app/Http/Controllers/Site/HadithController.php:185` — whitelist; `resources/views/public/hadith/chapter.blade.php:229` — pills. |
| Compact/normal/expanded densities | Present and persisted in local storage. | Implemented | `../the-last-messenger-ui/hadith-book-chapter-enhanced.html:641` — prototype controls; `resources/views/public/hadith/chapter.blade.php:65` — persisted state; `resources/views/public/hadith/chapter.blade.php:262` — controls. |
| Hadith cards: text, number, narrator chain, theme, grade and actions | Number/grade/book/text/detail and lazy translation exist; narrator, chain, themes, bookmark, share and copy are absent. | Partial | `../the-last-messenger-ui/hadith-book-chapter-enhanced.html:682` — prototype cards; `resources/views/public/hadith/chapter.blade.php:308` — implemented cards; `resources/views/public/hadith/chapter.blade.php:381` — only detail action. |
| Prototype edit/delete buttons | Correctly omitted from the public page; exposing admin mutations here would be a security regression. | Implemented (intentional divergence) | `../the-last-messenger-ui/hadith-book-chapter-enhanced.html:754` — prototype edit/delete controls; `resources/views/public/hadith/chapter.blade.php:381` — public read-only action row. |
| Empty/loading states | Navigation skeleton and empty reset state exist. | Implemented | `resources/views/public/hadith/chapter.blade.php:292` — skeleton; `resources/views/public/hadith/chapter.blade.php:427` — empty state. |
| Advanced pagination/per-page | Server pagination, range summary and 10/15/20 sizes exist; prototype also offers 5. | Partial | `../the-last-messenger-ui/hadith-book-chapter-enhanced.html:803` — prototype pagination; `app/Http/Controllers/Site/HadithController.php:31` — allowed sizes; `resources/views/public/hadith/chapter.blade.php:392` — implementation. |
| Floating print/audio/download menu | Entire FAB is absent. | Missing | `../the-last-messenger-ui/hadith-book-chapter-enhanced.html:871` — prototype FAB; `resources/views/public/hadith/chapter.blade.php:446` — implementation ends after results. |

#### Features & wiring

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Server-side filters/pagination | Real and bounded at 10/15/20. | Implemented | `app/Http/Controllers/Site/HadithController.php:190` — page-size allowlist; `app/Domain/Hadith/Repositories/HadithRepository.php:400` — paginator. |
| Lazy alternate-language translation | One throttled endpoint validates max 60 IDs/language and returns a batch map; no per-row query loop. | Implemented | `routes/web.php:84` — throttled route; `app/Http/Controllers/Site/HadithController.php:276` — validation; `app/Domain/Hadith/Repositories/HadithRepository.php:525` — one selected query. |
| Translation failure feedback | Fetch errors/non-2xx responses are silently swallowed; opened translation panels remain blank with no error/retry message. | Partial | `resources/views/public/hadith/chapter.blade.php:83` — loader; `resources/views/public/hadith/chapter.blade.php:96` — empty catch. |
| Bookmark/share/copy | Absent from cards despite a secure shared Hadith favorite endpoint existing. | Missing | `../the-last-messenger-ui/hadith-book-chapter-enhanced.html:472` — prototype bookmarks; `app/Providers/FavoriteServiceProvider.php:39` — backend Hadith favorite support; `resources/views/public/hadith/chapter.blade.php:381` — no actions. |
| Print | No chapter print action or chapter-specific print stylesheet. | Missing | `../the-last-messenger-ui/hadith-book-chapter-enhanced.html:492` — prototype print method; `rg -n \"@media print|window.print\" resources/views/public/hadith/chapter.blade.php` returned no matches. |
| Audio/download | No backend URL, schema field, or frontend control. | Missing | `../the-last-messenger-ui/hadith-book-chapter-enhanced.html:871` — prototype FAB; `database/migrations/2025_11_21_160004_create_hadith_hadiths_table.php:15` — no audio/export field. |
| Public sub-chapter navigation | The controller treats a `hadith_books` row as the public “chapter” and never uses `hadith_hadiths.chapter_id`; actual `hadith_chapters` are available only in the API detail path. | Partial | `app/Http/Controllers/Site/HadithController.php:172` — book-level design comment; `database/migrations/2026_06_11_000001_add_chapter_id_to_hadith_hadiths_table.php:23` — real chapter link; `routes/api.php:72` — chapter API. |

#### Schema

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Book-level Hadith list | `book_id`, number and grading indexes support the current public query. | Implemented | `database/migrations/2025_11_21_160004_create_hadith_hadiths_table.php:17` — columns; `database/migrations/2025_11_21_160004_create_hadith_hadiths_table.php:32` — indexes. |
| True chapter relationship | Nullable `chapter_id` and an index/FK exist; public page does not consume them. | Partial | `database/migrations/2026_06_11_000001_add_chapter_id_to_hadith_hadiths_table.php:23` — link; `app/Domain/Hadith/Models/Hadith.php:140` — relationship. |
| Chapter translations/descriptions | A five-locale translation table exists, but the public route maps book titles instead of `HadithChapterTranslation`. | Partial | `database/migrations/2026_03_10_100003_create_phase_4_translation_tables.php:132` — chapter translation table; `app/Http/Controllers/Site/HadithController.php:243` — book DTO mapping. |
| Card narrator/theme data | Relationships and indexed pivots exist, and the repository already loads themes; the view drops them and the chapter query does not load isnad/companion display data. | Partial | `database/migrations/2025_11_21_160009_create_hadith_hadith_themes_table.php:17` — theme pivot; `database/migrations/2025_11_21_160007_create_hadith_isnad_table.php:17` — isnad; `app/Domain/Hadith/Repositories/HadithRepository.php:376` — query relations. |
| Translation fallback | Hadith text correctly lives in `hadith_translations`, unique on `(hadith_id, language_code)` and full-text indexed. | Implemented | `database/migrations/2025_11_21_160005_create_hadith_translations_table.php:15` — translation schema; `database/migrations/2025_11_21_160005_create_hadith_translations_table.php:27` — unique pair. |

#### Enhancement gaps

- `app/Http/Controllers/Site/HadithController.php:177` — use the now-present `chapter_id` graph or explicitly rename the UI/route unit to “book/kitab”; the comment claiming chapters carry no linkage is stale (CLAUDE.md §4 domain/schema accuracy).
- `resources/views/public/hadith/chapter.blade.php:381` — add narrator/theme metadata and secure bookmark/share/copy controls; keep prototype edit/delete omitted (CLAUDE.md §5 component/feature parity and §6 mutation safety).
- `resources/views/public/hadith/chapter.blade.php:96` — expose a translated retry/error state for failed lazy translations (CLAUDE.md §5 error states).
- `resources/views/public/hadith/chapter.blade.php:308` — extract the repeated Hadith result card/action bar for reuse by explorer/related lists (CLAUDE.md §5 reusable components).

## Single Hadith detail

### hadith-single-enhanced.html → GET /hadith/{id} → resources/views/public/hadith/show.blade.php
**Status:** PARTIAL

`routes/web.php:91` — the route calls `App\Http\Controllers\Site\HadithController::show`; `app/Http/Controllers/Site/HadithController.php:329` renders `public.hadith.show`.

#### Sections & components

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Breadcrumb/hero with grade, collection, number, chapter and views | Grade/collection/number/book exist; the breadcrumb is shorter and no view count is shown. | Partial | `../the-last-messenger-ui/hadith-single-enhanced.html:313` — prototype breadcrumb; `../the-last-messenger-ui/hadith-single-enhanced.html:369` — view count; `resources/views/public/hadith/show.blade.php:152` — implementation hero. |
| Language tabs AR/EN/FR/TR/UR | Tabs render only translations with nonempty DB content, with Arabic/available fallback. | Implemented | `../the-last-messenger-ui/hadith-single-enhanced.html:420` — prototype tabs; `resources/views/public/hadith/show.blade.php:220` — dynamic tabs; `app/Http/Controllers/Site/HadithController.php:690` — removes empty translations. |
| Hadith matn, source/takhrij and theme badges | Matn/themes/basic collection+number source render; detailed takhrij, scholar/source reference and URL do not. | Partial | `../the-last-messenger-ui/hadith-single-enhanced.html:439` — prototype text/takhrij; `resources/views/public/hadith/show.blade.php:253` — text; `resources/views/public/hadith/show.blade.php:262` — basic source only. |
| Audio player | Absent. The prototype itself marks its timer as a production placeholder, but it is still a visible assigned component. | Missing | `../the-last-messenger-ui/hadith-single-enhanced.html:202` — simulated audio comment; `rg -n \"audio|play\" resources/views/public/hadith/show.blade.php` returned no matches. |
| Commentary accordions | Real commentaries are grouped by scholar/author and rendered as accordions. | Implemented | `../the-last-messenger-ui/hadith-single-enhanced.html:494` — prototype accordions; `resources/views/public/hadith/show.blade.php:381` — DB-driven groups. |
| Significance/historical context panels | Additional DB-driven accordions exist beyond the prototype. | Implemented/enhanced | `resources/views/public/hadith/show.blade.php:324` — optional context panels; `database/migrations/2025_11_21_160004_create_hadith_hadiths_table.php:22` — backing bilingual columns. |
| Related Hadith grid | Present, but “related” means the first six other rows from the same book, not theme/text/curated similarity. | Partial | `../the-last-messenger-ui/hadith-single-enhanced.html:585` — prototype related grid; `resources/views/public/hadith/show.blade.php:434` — implementation; `app/Http/Controllers/Site/HadithController.php:443` — same-book algorithm. |
| Isnad timeline and narrator modal | Real isnad/narrator biography/reliability data render with a modal. | Implemented | `../the-last-messenger-ui/hadith-single-enhanced.html:643` — prototype timeline; `resources/views/public/hadith/show.blade.php:484` — implementation; `resources/views/public/hadith/show.blade.php:673` — modal. |
| Quick stats/information and scholar gradings | Collection, number, grade, book, agreement and scholar/grade badges render; grading source/notes/level details are dropped. | Partial | `../the-last-messenger-ui/hadith-single-enhanced.html:690` — prototype stats; `resources/views/public/hadith/show.blade.php:528` — info; `resources/views/public/hadith/show.blade.php:570` — gradings. |
| Share/copy/print modal/actions | Twitter, Facebook, WhatsApp, copy and print are wired client-side. | Implemented | `../the-last-messenger-ui/hadith-single-enhanced.html:373` — prototype actions; `resources/views/public/hadith/show.blade.php:125` — implementations; `resources/views/public/hadith/show.blade.php:629` — modal. |

#### Features & wiring

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Language switching/font size | Alpine language switching and 80–150% font control work; font size is not persisted. | Implemented/partial | `resources/views/public/hadith/show.blade.php:118` — controls; `resources/views/public/hadith/show.blade.php:220` — tabs. |
| Bookmark | Local Alpine boolean only; existing authenticated favorite POST/DELETE endpoints are unused. | Partial | `resources/views/public/hadith/show.blade.php:123` — local toggle; `routes/web.php:204` — secure favorite endpoints. |
| Share/copy/print | Real browser/platform actions; clipboard errors have no feedback path, while success state is visual. | Implemented | `resources/views/public/hadith/show.blade.php:125` — share/copy methods; `resources/views/public/hadith/show.blade.php:306` — print. |
| Audio | No endpoint/player/source; prototype audio was itself fake. | Missing | `../the-last-messenger-ui/hadith-single-enhanced.html:203` — prototype says to connect production player; `resources/views/components/site/audio-player.blade.php:1` — a shared player exists but the Hadith view does not invoke it. |
| Narrator details | Modal is backed by eager-loaded isnad/narrator data, capped at 30 links. | Implemented | `app/Domain/Hadith/Repositories/HadithRepository.php:466` — selected/capped isnad; `resources/views/public/hadith/show.blade.php:673` — modal. |
| Compare narrations | Link is shown when exact-hash peers exist and opens a real page. | Partial | `resources/views/public/hadith/show.blade.php:561` — conditional link; `app/Domain/Hadith/Application/Handlers/GetHadithDetailHandler.php:44` — exact-hash count. |
| Translation fallback/query behavior | One detail eager load fetches all available translations; UI shows only actual content and falls back Arabic/first available without per-tab queries. | Implemented | `app/Domain/Hadith/Repositories/HadithRepository.php:461` — one relation query; `app/Http/Controllers/Site/HadithController.php:558` — fallback order. |

#### Schema

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Five-locale matn/translation | Translation table and model support ar/en/tr/fr/ur. Static code cannot prove live row coverage. | Implemented schema / coverage open | `app/Domain/Hadith/Models/HadithTranslation.php:69` — five supported codes; `database/migrations/2025_11_21_160005_create_hadith_translations_table.php:17` — per-Hadith language rows. |
| Isnad/narrator biography | Indexed isnad and narrator profile fields support the sidebar/modal. Reliability is a categorical string, not the prototype's percentage. | Partial | `database/migrations/2025_11_21_160007_create_hadith_isnad_table.php:17` — chain; `database/migrations/2025_11_21_160006_create_hadith_narrators_table.php:21` — narrator fields. |
| Scholar grading provenance | Schema has scholar, grading level, notes, `source_reference`, `source_url`, and `source_name`; public relation select/DTO omit the source fields. | Partial | `database/migrations/2025_11_21_160013_create_hadith_gradings_table.php:17` — grading relations/reference; `database/migrations/2026_05_19_000001_add_hadith_source_identity_and_review_fields.php:99` — URL/name; `app/Domain/Hadith/Repositories/HadithRepository.php:477` — omitted columns. |
| Takhrij/general sources | `hadith_sources` supports title/author/reference/URL but public detail does not load the relation. | Partial | `database/migrations/2025_11_21_160015_create_hadith_sources_table.php:15` — source schema; `app/Domain/Hadith/Repositories/HadithRepository.php:456` — public relation list has no `sources`. |
| Audio and view count | No Hadith audio/source-duration column or view-count column/interaction wiring is part of the detail payload. | Missing | `database/migrations/2025_11_21_160004_create_hadith_hadiths_table.php:15` — full base field list; `app/Http/Controllers/Site/HadithController.php:556` — mapped detail fields. |
| Significance/context localization | Stored in AR/EN columns rather than translation rows, so FR/TR/UR receive English/Arabic fallback. | Partial | `database/migrations/2025_11_21_160004_create_hadith_hadiths_table.php:22` — bilingual columns; `app/Domain/Hadith/Application/DTOs/HadithDTO.php:60` — all non-Arabic locales use English. |

#### Enhancement gaps

- `app/Domain/Hadith/Repositories/HadithRepository.php:477` — select and map grading source reference/name/URL and general takhrij sources instead of reducing each verdict to scholar+grade (CLAUDE.md §4 explicit data contracts; prototype grading parity).
- `resources/views/public/hadith/show.blade.php:123` — initialize and persist bookmarks through the existing IDOR-safe favorite service (CLAUDE.md §6 authenticated, validated mutations).
- `app/Http/Controllers/Site/HadithController.php:443` — define true relatedness by shared themes/text group/curation rather than first same-book rows (CLAUDE.md §4 truthful domain behavior).
- `resources/views/public/hadith/show.blade.php:220` — persist the user-selected font size if the prototype preference is retained, and surface copy failures accessibly (CLAUDE.md §5 resilient interaction feedback).

## Hadith explorer / advanced browse

### hadith-explorer.html → GET /hadith/explorer → resources/views/public/hadith/explorer.blade.php
**Status:** PARTIAL

`routes/web.php:83` — the route calls `App\Http\Controllers\Site\HadithController::explorer`; `app/Http/Controllers/Site/HadithController.php:425` renders `public.hadith.explorer`.

#### Sections & components

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Explorer hero | Present, but every string is hardcoded Arabic. | Partial | `../the-last-messenger-ui/hadith-explorer.html:184` — prototype begins quick filters after hero; `resources/views/public/hadith/explorer.blade.php:93` — hardcoded hero. |
| Sticky quick filters with counts for all, major collections and themes | All+collections render; only “all” gets the current result count, and there are no theme pills/facet counts. | Partial | `../the-last-messenger-ui/hadith-explorer.html:188` — all/Bukhari/Muslim/theme pills; `resources/views/public/hadith/explorer.blade.php:106` — all collections only. |
| Text search with autocomplete suggestions | Server text search exists; suggestions/autocomplete are absent. | Partial | `../the-last-messenger-ui/hadith-explorer.html:282` — search/suggestion UI; `resources/views/public/hadith/explorer.blade.php:163` — plain input. |
| Theme/topic filter | Missing from the Blade/controller. | Missing | `../the-last-messenger-ui/hadith-explorer.html:349` — topic selector; `app/Http/Controllers/Site/HadithController.php:371` — accepted explorer parameters omit theme. |
| Collection and book filters | One dropdown labelled “book” actually submits a collection ID; there is no book-within-collection filter. | Partial | `../the-last-messenger-ui/hadith-explorer.html:369` — prototype book filter; `resources/views/public/hadith/explorer.blade.php:191` — dropdown named `collection`. |
| Narrator filter | Missing. | Missing | `../the-last-messenger-ui/hadith-explorer.html:389` — narrator input; `app/Http/Controllers/Site/HadithController.php:371` — no narrator parameter. |
| Multi-select grading | A single radio group permits only one grade; prototype checkboxes allow multiple grades. | Partial | `../the-last-messenger-ui/hadith-explorer.html:403` — three checkboxes; `resources/views/public/hadith/explorer.blade.php:208` — radios. |
| Saved searches list/save action | Missing despite a generic `saved_filters` table. | Missing | `../the-last-messenger-ui/hadith-explorer.html:429` — save button/list; `database/migrations/2025_10_31_075950_create_saved_filters_table.php:16` — backing schema; `resources/views/public/hadith/explorer.blade.php:257` — only apply/reset. |
| Popular quick search | Present as hardcoded Arabic terms. | Implemented/partial | `../the-last-messenger-ui/hadith-explorer.html:472` — quick search; `resources/views/public/hadith/explorer.blade.php:278` — implementation. |
| Result header: query chip/count/sorts | Query chip/count exist; only ascending/descending number are offered, not relevance/book/narrator. | Partial | `../the-last-messenger-ui/hadith-explorer.html:539` — prototype header; `resources/views/public/hadith/explorer.blade.php:357` — implementation. |
| Card/compact view and 10/25/50/100 page sizes | No view toggle; only card layout; allowed sizes are 10/20/30/50. | Partial | `../the-last-messenger-ui/hadith-explorer.html:601` — view/page-size controls; `app/Http/Controllers/Site/HadithController.php:28` — implementation sizes. |
| Result cards with themes, narrator, reference, detail/save/share | Cards show collection/book/number/grade/text/basic reference and a detail action; themes, narrator, save/share are absent. | Partial | `../the-last-messenger-ui/hadith-explorer.html:665` — result cards; `resources/views/public/hadith/explorer.blade.php:421` — implementation. |
| Advanced pagination/jump | Range, jump, first/prev/numbers/next/last exist; prototype quick page shortcuts are absent. | Partial | `../the-last-messenger-ui/hadith-explorer.html:917` — prototype pagination; `resources/views/public/hadith/explorer.blade.php:511` — implementation. |
| Prompt/empty/loading/error states | Prompt and empty exist; no loading state, and backend exceptions collapse to “no results.” | Partial | `resources/views/public/hadith/explorer.blade.php:322` — prompt/empty branches; `app/Http/Controllers/Site/HadithController.php:417` — exception becomes empty results. |

#### Features & wiring

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Text search | Real server full-text query against `hadith_translations.text`; numeric strings search number via LIKE. | Implemented | `app/Domain/Hadith/Repositories/HadithRepository.php:184` — split search paths; `database/migrations/2025_11_21_160005_create_hadith_translations_table.php:35` — full-text index. |
| Collection filter | Real `whereHas(book.collection_id)` filter. | Implemented | `app/Domain/Hadith/Repositories/HadithRepository.php:212` — collection predicate. |
| Grade filter | Real equality filter but controller does not validate against its displayed whitelist; arbitrary strings are bound safely and yield empty results. | Partial | `app/Http/Controllers/Site/HadithController.php:373` — raw query string; `app/Domain/Hadith/Repositories/HadithRepository.php:204` — bound equality. |
| Theme/book/narrator filters | Not accepted or passed. The generic repository also ignores `narrator_id` and `theme_ids` even when API/CQRS callers supply them. | Missing | `app/Http/Controllers/Api/HadithController.php:64` — narrator filter passed; `app/Domain/Hadith/Repositories/HadithRepository.php:199` — handled filters omit narrator/theme; `app/Domain/Hadith/Application/Handlers/ListHadithsByThemeHandler.php:52` — ignored `theme_ids`. |
| Sorting | `sort` is controller-whitelisted to asc/desc and `sortBy` is hardcoded/allowlisted, preventing column injection; however direction is never passed by the handler/repository, so “desc” still orders ascending. | Partial | `app/Http/Controllers/Site/HadithController.php:374` — direction whitelist; `app/Domain/Hadith/Repositories/HadithRepository.php:216` — column allowlist and ascending-only `orderBy`; `app/Domain/Hadith/Application/Handlers/SearchHadithHandler.php:34` — direction omitted. |
| Pagination | Real `LengthAwarePaginator`, max 50; current page resolves from Laravel's request paginator even though the CQRS handler ignores its explicit page field. | Implemented/partial | `app/Http/Controllers/Site/HadithController.php:375` — size whitelist; `app/Domain/Hadith/Repositories/HadithRepository.php:223` — pagination; `app/Domain/Hadith/Application/Queries/SearchHadithQuery.php:19` — page field not consumed by handler. |
| Saved search/history | No website endpoint/controller wiring; generic schema and API search-history facilities exist elsewhere. | Missing | `database/migrations/2025_10_31_075950_create_saved_filters_table.php:21` — module-scoped filters; `routes/api.php:166` — generic authenticated history; `routes/web.php:81` — no Hadith saved-filter route. |
| Result bookmarks/share | Absent, despite an existing Hadith favorite backend. | Missing | `app/Providers/FavoriteServiceProvider.php:39` — Hadith is registered; `resources/views/public/hadith/explorer.blade.php:499` — only detail action. |
| Public API keyword search | `/api/v1/hadith/search` is wired but its repository adds `OR translation LIKE`, a column absent from schema. | Broken | `routes/api.php:63` — endpoint; `app/Http/Controllers/Api/HadithController.php:123` — repository call; `app/Domain/Hadith/Repositories/HadithRepository.php:151` — invalid column. |
| Public API theme browse | `/api/v1/hadith/by-theme/{theme}` is wired but queries nonexistent theme slug/name columns. | Broken | `routes/api.php:76` — endpoint; `app/Http/Controllers/Api/HadithController.php:362` — invalid fields; `database/migrations/2025_11_21_160008_create_hadith_themes_table.php:15` — actual schema. |

#### Schema

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Text search | Full-text index on translation `text` supports the website explorer. | Implemented | `database/migrations/2025_11_21_160005_create_hadith_translations_table.php:37` — MySQL full-text index. |
| Collection/book filter | Indexed `hadith_books.collection_id` and `(collection_id, book_number)` exist. | Implemented | `database/migrations/2025_11_21_160001_create_hadith_books_table.php:27` — collection index; `database/migrations/2026_05_19_000001_add_hadith_source_identity_and_review_fields.php:26` — composite index. |
| Narrator filter | Isnad has indexes on `hadith_id` and `narrator_id`; schema is adequate, repository filter is missing. | Implemented schema / missing query | `database/migrations/2025_11_21_160007_create_hadith_isnad_table.php:24` — indexes. |
| Theme filter | Pivot indexes exist, but the theme base schema/DTO/API disagree on names, slugs, hierarchy and timestamps. | Broken schema contract | `database/migrations/2025_11_21_160009_create_hadith_hadith_themes_table.php:21` — pivot indexes; `app/Domain/Hadith/Application/DTOs/ThemeDTO.php:49` — reads nonexistent fields; `database/migrations/2025_11_21_160008_create_hadith_themes_table.php:15` — actual columns. |
| Multi-grade filter | `grading_status` is indexed; query layer needs `whereIn`/validated array semantics. | Implemented schema / missing query | `database/migrations/2025_11_21_160004_create_hadith_hadiths_table.php:35` — grade index; `app/Domain/Hadith/Repositories/HadithRepository.php:204` — scalar equality only. |
| Saved searches | Generic `saved_filters` fully supports module, JSON filters/sort, public/default and usage data; no Hadith website workflow consumes it. | Implemented schema / missing feature | `database/migrations/2025_10_31_075950_create_saved_filters_table.php:16` — schema. |
| Sort/index alignment | Number and grade have individual indexes; collection filtering joins through indexed FK. Relevance requires full-text score selection, which is not exposed. | Partial | `database/migrations/2025_11_21_160004_create_hadith_hadiths_table.php:34` — number index; `app/Domain/Hadith/Repositories/HadithRepository.php:217` — allowed sorts omit relevance/narrator. |

#### Enhancement gaps

- `app/Http/Controllers/Site/HadithController.php:371` — add validated theme, book, narrator and multi-grade parameters plus DB-backed facet counts/saved searches (CLAUDE.md §4 validated query contracts; prototype feature parity).
- `app/Domain/Hadith/Application/Handlers/SearchHadithHandler.php:34` — propagate a whitelisted sort direction and implement explicit relevance/book/narrator sort modes without interpolating request columns (CLAUDE.md §6 dynamic-sort safety).
- `app/Domain/Hadith/Repositories/HadithRepository.php:221` — constrain translation columns/languages and eager-load `themes` plus `book.collection`; DTO mapping at `app/Domain/Hadith/Application/DTOs/HadithDTO.php:41` otherwise lazy-loads per row (CLAUDE.md §4 N+1/explicit-select rules).
- `resources/views/public/hadith/explorer.blade.php:98` — replace all hardcoded Arabic chrome/popular terms with `hadith.php` keys across AR/EN/FR/TR/UR (CLAUDE.md:500 — multi-language content).
- `resources/views/public/hadith/explorer.blade.php:466` — replace physical `border-r-4` and fixed Arabic pagination chevrons with logical borders/locale-aware icons (CLAUDE.md:489 — RTL/LTR support).
- `resources/views/public/hadith/explorer.blade.php:290` — remove the duplicated nested `<form>` opening and add a real failure/loading state (CLAUDE.md §5 valid, accessible and resilient frontend).

## Narration comparison

### hadith-narration.html → GET /hadith/{id}/narrations → resources/views/public/hadith/narrations.blade.php
**Status:** PARTIAL

`routes/web.php:89` — the route calls `App\Http\Controllers\Site\HadithController::narrations`; `app/Http/Controllers/Site/HadithController.php:360` renders `public.hadith.narrations`.

#### Sections & components

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Breadcrumb/introduction | Localized breadcrumb, title, subtitle and back link exist. | Implemented | `../the-last-messenger-ui/hadith-narration.html:86` — prototype breadcrumb; `resources/views/public/hadith/narrations.blade.php:51` — implementation. |
| Context summary card | Prototype's shared subject/context sentence is absent. | Missing | `../the-last-messenger-ui/hadith-narration.html:114` — intro/context; `resources/views/public/hadith/narrations.blade.php:81` — header proceeds directly to comparison. |
| Three responsive side-by-side narration cards | Responsive 1/2/3-column cards render base plus up to 12 peers. | Implemented | `../the-last-messenger-ui/hadith-narration.html:136` — prototype columns; `resources/views/public/hadith/narrations.blade.php:96` — responsive grid. |
| Source collection/book/number and base/parallel label | Present and DB-driven. | Implemented | `resources/views/public/hadith/narrations.blade.php:103` — source header/badges. |
| Textual additions highlighted per variant | Full texts render, but no diff or added-text highlighting exists; exact-hash peers should normally be text-identical after whitespace normalization. | Missing | `../the-last-messenger-ui/hadith-narration.html:178` — “narration with addition”; `resources/views/public/hadith/narrations.blade.php:137` — plain blockquote. |
| Isnad summary/chain | Full eager-loaded narrator chain renders when present, improving on the prototype's compact summary. | Implemented/enhanced | `../the-last-messenger-ui/hadith-narration.html:156` — prototype isnad summary; `resources/views/public/hadith/narrations.blade.php:151` — chain. |
| Grade and grading scholar | Overall badge plus up to four scholar gradings render; sources/levels/notes are dropped. | Partial | `../the-last-messenger-ui/hadith-narration.html:166` — prototype grade; `resources/views/public/hadith/narrations.blade.php:174` — scholar list. |
| No-parallel state | Localized unique-narration empty state exists. | Implemented | `resources/views/public/hadith/narrations.blade.php:196` — empty state. |

#### Features & wiring

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Endpoint and route | Fully routed and cached for one hour. | Implemented | `routes/web.php:89` — route; `app/Http/Controllers/Site/HadithController.php:340` — cached query. |
| Parallel resolution | Exact equality on `text_hash`, excluding only the current ID, capped at 12. It does not compare isnad, narrator, theme, reference or fuzzy text. | Partial | `app/Domain/Hadith/Application/Handlers/GetParallelNarrationsHandler.php:47` — hash resolution; `app/Domain/Hadith/Repositories/HadithRepository.php:423` — query/cap. |
| Variant diff/highlighting | No diff computation or variation metadata. | Missing | `resources/views/public/hadith/narrations.blade.php:139` — raw text output only. |
| Duplicate treatment | The query does not exclude `merged_into_id` rows, so deduplicated aliases can become the “parallel narrations” displayed. | Partial | `app/Domain/Hadith/Repositories/HadithRepository.php:425` — hash query has no merged filter; `app/Domain/Hadith/Services/HadithDeduplicationService.php:183` — duplicates are retained. |
| Loading/error state | Server-rendered unique state exists; query exceptions become 404, so operational failure and unknown Hadith are indistinguishable. | Partial | `app/Http/Controllers/Site/HadithController.php:340` — catches every throwable and returns null; `app/Http/Controllers/Site/HadithController.php:356` — aborts 404. |

#### Schema

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Exact-text grouping | `text_hash CHAR(64)` has a dedicated index, so the equality resolution query is indexed. | Implemented | `database/migrations/2026_03_09_000001_add_text_hash_to_hadith_hadiths_table.php:16` — field and index. |
| Hash semantics | Hash normalization collapses whitespace only; it does not normalize Arabic orthography, strip isnad, or produce a canonical narration identity. | Partial | `app/Domain/Hadith/Services/HadithDeduplicationService.php:17` — normalization implementation. |
| Variant-group identity | No canonical narration/variant group, similarity score, base narration, additions/diff, or chain-signature field/table exists. | Missing | `database/migrations/2025_11_21_160004_create_hadith_hadiths_table.php:15` — base schema; `database/migrations/2026_03_09_000001_add_text_hash_to_hadith_hadiths_table.php:14` — only hash added. |
| Merge lineage | `merged_into_id` is indexed and self-referential, but public queries do not represent redirects/aliases. | Partial | `database/migrations/2026_03_09_000002_add_merged_into_id_to_hadith_hadiths_table.php:16` — lineage field; `app/Domain/Hadith/Models/Hadith.php:153` — relations. |
| Isnad matching | Indexed narrator links and ordered positions exist, so a chain-signature/grouping algorithm is feasible but not implemented. | Implemented schema / missing algorithm | `database/migrations/2025_11_21_160007_create_hadith_isnad_table.php:17` — chain columns; `database/migrations/2025_11_21_160007_create_hadith_isnad_table.php:27` — unique ordered link. |

#### Enhancement gaps

- `app/Domain/Hadith/Application/Handlers/GetParallelNarrationsHandler.php:47` — introduce a canonical narration group/variant relation (or documented isnad/text similarity resolver) and compute safe textual diffs; retain indexed exact hash for dedup, not semantic comparison (CLAUDE.md §4 truthful domain modeling).
- `app/Domain/Hadith/Repositories/HadithRepository.php:430` — exclude merged aliases and optionally require distinct collection/book sources before calling a record “parallel” (CLAUDE.md §4 correct public query semantics).
- `app/Domain/Hadith/Repositories/HadithRepository.php:477` — include grading source/level fields in comparison cards (prototype grading/takhrij parity).
- `app/Http/Controllers/Site/HadithController.php:341` — distinguish not-found from query failure rather than converting all throwables to 404 (CLAUDE.md §5 error states).

## Findings checklist

- [ ] **HAD-001** [P0] Remove the nonexistent `translation` column from public API Hadith search
      - **Module:** Hadith explorer/API  ·  **Type:** SCHEMA
      - **Evidence:** `app/Domain/Hadith/Repositories/HadithRepository.php:151` — `/api/v1/hadith/search` adds `OR translation LIKE`; `database/migrations/2025_11_21_160005_create_hadith_translations_table.php:19` — only `text` exists.
      - **What to do:** In `HadithRepository::search()`, search only selected, indexed `hadith_translations.text` (or add a deliberately modeled column and migration); remove `$t->translation` from `HadithDTO`/`HadithResource`, add an endpoint feature test against the migrated schema, and keep result pagination bounded.
      - **Why it matters / trade-offs:** Text queries can fail with an unknown-column SQL error. One canonical text column per language is simpler than two ambiguous text fields.

- [ ] **HAD-002** [P0] Reconcile the Hadith theme schema, model, DTO, repository and public API
      - **Module:** Hadith explorer/API  ·  **Type:** SCHEMA
      - **Evidence:** `app/Http/Controllers/Api/HadithController.php:362` — endpoint selects slug/name columns; `database/migrations/2025_11_21_160008_create_hadith_themes_table.php:15` — those columns do not exist; `app/Domain/Hadith/Application/DTOs/ThemeDTO.php:49` — DTO also reads nonexistent fields.
      - **What to do:** Choose one contract: keep `theme_ar/theme_en` plus `hadith_theme_translations`, add explicit `slug`, `parent_id`, `sort_order` and timestamps migrations if required, update `ThemeDTO`, `ThemeRepository`, API resource/controller to actual columns, and add migration-backed tests for `/api/v1/hadith/by-theme/{theme}`.
      - **Why it matters / trade-offs:** The public theme endpoint is structurally broken and blocks the explorer's theme facet. Adding slugs/hierarchy costs a migration but creates stable URLs and indexed filters.

- [ ] **HAD-003** [P1] Exclude or redirect merged duplicate Hadiths on every public read path
      - **Module:** Hadith public module  ·  **Type:** PARTIAL
      - **Evidence:** `app/Domain/Hadith/Services/HadithDeduplicationService.php:183` — merged rows are retained with `merged_into_id`; `app/Domain/Hadith/Repositories/HadithRepository.php:182` — public list base query has no `whereNull('merged_into_id')`.
      - **What to do:** Add a reusable `canonical()` scope (`whereNull('merged_into_id')`) and apply it to website/API lists, counts, related results and narration counts; for `/hadith/{id}`, 301 to the canonical ID or render an explicit alias state; add duplicate regression tests.
      - **Why it matters / trade-offs:** Users can see the same retained duplicate as separate search/card/detail results and inflated totals. Redirects preserve old links while canonical filtering fixes lists.

- [ ] **HAD-004** [P1] Model and resolve genuine narration variants instead of exact-text duplicates
      - **Module:** Narration comparison  ·  **Type:** SCHEMA
      - **Evidence:** `app/Domain/Hadith/Services/HadithDeduplicationService.php:17` — hash normalizes whitespace only; `../the-last-messenger-ui/hadith-narration.html:178` — prototype compares versions “with addition.”
      - **What to do:** Add a canonical narration-group/variant table (canonical ID, Hadith ID, relation type, confidence, resolver version), build indexed resolver input from normalized matn plus isnad/companion/source signals, review ambiguous matches, and render a token/phrase diff.
      - **Why it matters / trade-offs:** Exact hashes are excellent for deduplication but cannot discover wording additions—the primary value of this page. Similarity matching needs editorial review to avoid false equivalence.

- [ ] **HAD-005** [P1] Complete the explorer's missing facets, saved searches, views and result actions
      - **Module:** Hadith explorer  ·  **Type:** MISSING
      - **Evidence:** `../the-last-messenger-ui/hadith-explorer.html:349` — prototype theme/book/narrator/multi-grade filters; `resources/views/public/hadith/explorer.blade.php:191` — implementation has only collection, grade, number sort and size controls.
      - **What to do:** Add validated theme, collection, book, narrator and grading-array inputs; DB facet counts; suggestions; authenticated saved-filter CRUD using `saved_filters`; card/compact view; 10/25/50/100 options; and narrator/theme/bookmark/share fields/actions in `explorer.blade.php`.
      - **Why it matters / trade-offs:** The assigned “advanced explorer” currently behaves like a basic search form. More facets increase query complexity, so add composite/index review and query-count tests with 50k-scale fixtures.

- [ ] **HAD-006** [P1] Make descending and prototype explorer sorts functional and safely allowlisted
      - **Module:** Hadith explorer  ·  **Type:** PARTIAL
      - **Evidence:** `app/Http/Controllers/Site/HadithController.php:407` — controller passes sort direction; `app/Domain/Hadith/Application/Handlers/SearchHadithHandler.php:34` — handler drops it; `app/Domain/Hadith/Repositories/HadithRepository.php:219` — always ascending.
      - **What to do:** Extend the repository contract to accept an enum/allowlisted direction, map public sort tokens to fixed SQL expressions for relevance/number/book/narrator, and test every mode; never pass raw request column names.
      - **Why it matters / trade-offs:** The visible “descending” option lies today. Fixed token mapping preserves SQL-injection safety while enabling richer sorting.

- [ ] **HAD-007** [P1] Remove explorer and related-list N+1 queries and unconstrained translation loads
      - **Module:** Hadith explorer/detail  ·  **Type:** PERF
      - **Evidence:** `app/Domain/Hadith/Repositories/HadithRepository.php:221` — loads all translations and book but not themes/collection; `app/Domain/Hadith/Application/DTOs/HadithDTO.php:41` — per-row mapping reads translations, themes and `book.collection`.
      - **What to do:** Select `id,hadith_id,language_code,text` and only active locale+Arabic; eager-load `themes:id,theme_ar,theme_en` and `book.collection:id,name_ar,name_en`; add a query-count test that maps 50 DTOs, not merely fetches the paginator.
      - **Why it matters / trade-offs:** At the allowed 50 rows, lazy relations can add roughly two queries per row and all-language text multiplies payload size. Constraining locales trades immediate tab availability for predictable list performance.

- [ ] **HAD-008** [P1] Expose the real Hadith chapter hierarchy on the public site
      - **Module:** Book/chapter reader  ·  **Type:** PARTIAL
      - **Evidence:** `database/migrations/2026_06_11_000001_add_chapter_id_to_hadith_hadiths_table.php:23` — real linkage exists; `app/Http/Controllers/Site/HadithController.php:177` — public route still treats a book as a chapter and claims no linkage exists.
      - **What to do:** Add a chapter-list/query DTO under each book, route by stable chapter ID/number, paginate `where chapter_id = ?`, map `hadith_chapter_translations`, and retain a book-level “all Hadiths” view for unlinked/source-without-chapter rows.
      - **Why it matters / trade-offs:** The imported 9,024-chapter structure is invisible, limiting precise browse/navigation. Unlinked rows require an explicit fallback bucket.

- [ ] **HAD-009** [P1] Add curated famous Hadiths and the missing collection tab
      - **Module:** Single collection  ·  **Type:** MISSING
      - **Evidence:** `../the-last-messenger-ui/hadith-book-single-enhanced.html:648` — famous-Hadith section; `resources/views/public/hadith/book.blade.php:234` — only about/chapters/author tabs.
      - **What to do:** Add an editorial curation pivot/flag with rank, bounded repository query selecting card fields/narrator/source, render the fourth tab, and wire detail/bookmark/share actions.
      - **Why it matters / trade-offs:** This prominent discovery surface is wholly absent. Editorial curation is safer than inventing popularity from unavailable view data.

- [ ] **HAD-010** [P1] Wire Hadith and collection bookmarks to durable server state
      - **Module:** Collection/detail/chapter/explorer  ·  **Type:** PARTIAL
      - **Evidence:** `resources/views/public/hadith/show.blade.php:123` — local-only toggle; `routes/web.php:204` — authenticated rate-limited favorites exist; `app/Providers/FavoriteServiceProvider.php:39` — only individual Hadiths are registered.
      - **What to do:** Initialize favorite IDs in controller payloads, call POST/DELETE `/favorites` with CSRF and pending/error states, handle guest login, and either register/map `HadithCollection` as a distinct safe type or remove its bookmark control.
      - **Why it matters / trade-offs:** Current “saved” states disappear on navigation and contradict the profile favorites feature. Collection support expands the enum/mapper contract.

- [ ] **HAD-011** [P1] Restore chapter card metadata/actions and the print/audio/download menu
      - **Module:** Chapter reader  ·  **Type:** MISSING
      - **Evidence:** `../the-last-messenger-ui/hadith-book-chapter-enhanced.html:782` — bookmark/action cards; `../the-last-messenger-ui/hadith-book-chapter-enhanced.html:871` — FAB; `resources/views/public/hadith/chapter.blade.php:381` — detail is the sole action.
      - **What to do:** Load/map bounded narrator/theme summaries, reuse secure bookmarks/share/copy, add print CSS/action, define a streamed chapter audio source before showing audio, and add a bounded queued/streamed export endpoint for download.
      - **Why it matters / trade-offs:** Users lose the prototype's core study/share workflow. Audio/export should remain hidden until real source/licensing and resource limits exist.

- [ ] **HAD-012** [P1] Surface complete grading and takhrij provenance
      - **Module:** Hadith detail/narrations  ·  **Type:** PARTIAL
      - **Evidence:** `database/migrations/2026_05_19_000001_add_hadith_source_identity_and_review_fields.php:99` — grading source fields exist; `app/Domain/Hadith/Application/DTOs/HadithDetailDTO.php:94` — DTO returns only scholar/grade/rank/notes.
      - **What to do:** Select/map `source_reference`, `source_url`, `source_name`, grading-level labels and notes; load bounded verified `hadith_sources`; render accessible source citations on detail and comparison cards.
      - **Why it matters / trade-offs:** A grade without scholar/source reference is weaker scholarly provenance than the prototype promises. External URLs need protocol validation and safe link attributes.

- [ ] **HAD-013** [P1] Paginate unbounded public API chapter/theme Hadith lists
      - **Module:** Hadith API  ·  **Type:** PERF
      - **Evidence:** `app/Http/Controllers/Api/HadithController.php:273` — chapter eager-loads every Hadith and all translations; `app/Domain/Hadith/Repositories/HadithRepository.php:114` — theme query ends in unbounded `get()`.
      - **What to do:** Return chapter metadata separately and paginate Hadith resources with max 100; replace `getByTheme()` collection return with a paginator; select active locale+Arabic translation columns and preserve pagination metadata.
      - **Why it matters / trade-offs:** At 50,716 rows, large chapters/themes can exhaust memory and response budgets. Pagination changes API shape, so version or document it.

- [ ] **HAD-014** [P1] Apply search/general API rate limiters to Hadith endpoints
      - **Module:** Hadith API  ·  **Type:** SECURITY
      - **Evidence:** `routes/api.php:61` — Hadith group has no throttle middleware; `app/Providers/AppServiceProvider.php:109` — `search` and `api` limiters are defined but unused here; `bootstrap/app.php:29` — API stack adds no throttle middleware.
      - **What to do:** Apply `throttle:search` to `/hadith/search` and `throttle:api` to the public group (or an equivalent global API limiter), key authenticated traffic appropriately, return standard 429 responses, and load-test limits.
      - **Why it matters / trade-offs:** Unrestricted full-text/search and unbounded legacy reads are abuse/availability risks. Limits must allow legitimate research clients and can be tiered.

- [ ] **HAD-015** [P1] Provide real Hadith audio or remove the promised player surface
      - **Module:** Hadith detail/chapter  ·  **Type:** MISSING
      - **Evidence:** `../the-last-messenger-ui/hadith-single-enhanced.html:203` — prototype explicitly marks audio as not production-wired; `rg -n "audio|play" resources/views/public/hadith/show.blade.php` returned no matches.
      - **What to do:** Define licensed audio source/voice/locale/duration records, stream URLs through a bounded service, reuse `resources/views/components/site/audio-player.blade.php`, and add unavailable/error states; otherwise document/remove audio from parity scope.
      - **Why it matters / trade-offs:** A fake timer must not ship, but the assigned UI includes audio prominently. Real audio entails storage, licensing and accessibility costs.

- [ ] **HAD-016** [P1] Align collection author/year metadata schema and broken CQRS writes
      - **Module:** Collections/books  ·  **Type:** SCHEMA
      - **Evidence:** `app/Domain/Hadith/Application/Handlers/CreateCollectionHandler.php:57` — writes `slug`, `author_id`, `hadith_count`, `is_active`; `database/migrations/2025_11_21_160000_create_hadith_collections_table.php:17` — actual columns are localized slugs, `total_hadiths`, and later `active`; `app/Domain/Hadith/Application/DTOs/CollectionDTO.php:52` — author forced null.
      - **What to do:** Add a compiler-scholar FK and compilation year if editorially authoritative, migrate/backfill, fix create/update/sync handlers to actual names, select/map author biography fields, and add CQRS persistence tests on a migrated database.
      - **Why it matters / trade-offs:** Prototype author/year sections cannot be real, and stale command/sync paths can attempt unknown columns. A scholar FK avoids duplicated biography data.

- [ ] **HAD-017** [P1] Restore the featured collection carousel behavior
      - **Module:** Collections index  ·  **Type:** MISSING
      - **Evidence:** `../the-last-messenger-ui/hadith-books-enhanced.html:556` — autoplay/slide methods; `resources/views/public/hadith/index.blade.php:210` — featured items are a static grid.
      - **What to do:** Add bounded Alpine carousel state, pause-on-hover/focus, prev/next, dots, reduced-motion handling and keyboard/ARIA semantics while retaining server-rendered links.
      - **Why it matters / trade-offs:** It is the prototype's dominant discovery component. Motion must respect reduced-motion and should not hide content from no-JS users.

- [ ] **HAD-018** [P2] Make collection chapter search cover all paginated rows
      - **Module:** Single collection  ·  **Type:** PARTIAL
      - **Evidence:** `resources/views/public/hadith/book.blade.php:73` — filters only the serialized current page; `app/Http/Controllers/Site/HadithController.php:126` — the server page is capped at 24.
      - **What to do:** Accept/validate `q`, apply it in `BookRepository::paginateByCollection`, preserve it in pagination links, and replace the current-page Alpine filter or label it explicitly.
      - **Why it matters / trade-offs:** Users can be told “no chapter” while a matching chapter exists on another page. Server search adds a query but remains bounded/indexable.

- [ ] **HAD-019** [P2] Localize the explorer's complete UI in all five locales
      - **Module:** Hadith explorer  ·  **Type:** I18N
      - **Evidence:** `resources/views/public/hadith/explorer.blade.php:98` — hero and subsequent controls are literal Arabic; `rg -n "__\\(" resources/views/public/hadith/explorer.blade.php` returned no matches.
      - **What to do:** Add explorer keys to all five `resources/lang/{ar,en,fr,tr,ur}/hadith.php` files, replace every label/placeholder/state/count phrase/popular term with `__()`, and add a static key-parity test.
      - **Why it matters / trade-offs:** EN/FR/TR visitors receive Arabic chrome despite the module advertising five locales. Popular terms may need locale-specific editorial lists.

- [ ] **HAD-020** [P2] Replace explorer/book physical direction styling with logical RTL-aware behavior
      - **Module:** Hadith explorer/book  ·  **Type:** RTL
      - **Evidence:** `resources/views/public/hadith/explorer.blade.php:466` — uses `border-r-4`; `resources/views/public/hadith/explorer.blade.php:572` — hardcoded Arabic-direction chevron; `resources/views/public/hadith/book.blade.php:135` — unflipped breadcrumb chevron.
      - **What to do:** Use `border-s-4`, locale-aware/ltr-rotated chevrons and logical transforms; test AR/UR and EN/FR/TR at mobile/desktop breakpoints.
      - **Why it matters / trade-offs:** Physical right/left styling reverses meaning after explorer localization. Logical utilities keep one markup path.

- [ ] **HAD-021** [P2] Move all Hadith public translatable content to locale rows
      - **Module:** Collections/books/detail  ·  **Type:** SCHEMA
      - **Evidence:** `database/migrations/2025_11_21_160000_create_hadith_collections_table.php:17` — collection copy is AR/EN columns; `database/migrations/2025_11_21_160004_create_hadith_hadiths_table.php:22` — significance/context are AR/EN columns; `database/migrations/2026_03_10_100003_create_phase_4_translation_tables.php:98` — no collection/book content translation tables.
      - **What to do:** Add collection/book locale tables and move significance/context into an appropriate Hadith locale/content table, backfill AR/EN, use readiness/fallback semantics for FR/TR/UR, and update DTO selects/accessors.
      - **Why it matters / trade-offs:** Three supported locales can never receive native collection/book/context copy. Migration complexity is real, but it removes expanding language-specific columns.

- [ ] **HAD-022** [P2] Add explicit selects and sort allowlists to collection/book pagination
      - **Module:** Collections/books  ·  **Type:** PERF
      - **Evidence:** `app/Domain/Hadith/Repositories/CollectionRepository.php:227` — paginates without select; `app/Domain/Hadith/Repositories/BookRepository.php:203` — paginates without select and passes dynamic sort column/direction.
      - **What to do:** Select only DTO-required fields, map sort tokens through fixed allowlists/enums, validate directions, and add query-shape tests.
      - **Why it matters / trade-offs:** Current collections are small, but explicit contracts prevent accidental wide rows and unsafe future callers. Additional fields must be deliberately added to the DTO query.

- [ ] **HAD-023** [P2] Validate explorer grades and separate query errors from empty results
      - **Module:** Hadith explorer  ·  **Type:** ENHANCEMENT
      - **Evidence:** `app/Http/Controllers/Site/HadithController.php:373` — grade is accepted raw; `app/Http/Controllers/Site/HadithController.php:417` — any query exception becomes zero results.
      - **What to do:** Use a dedicated FormRequest or explicit validator with canonical grades/per-page/sort tokens; pass a translated error state with retry when the query fails; keep true empty results separate.
      - **Why it matters / trade-offs:** Bound parameters prevent injection, but invalid URLs and outages currently look like legitimate zero-match searches.

- [ ] **HAD-024** [P2] Honor narrator and theme filters already advertised by API/CQRS contracts
      - **Module:** Hadith query layer  ·  **Type:** PARTIAL
      - **Evidence:** `app/Http/Controllers/Api/HadithController.php:64` — passes `narrator_id`; `app/Domain/Hadith/Application/Handlers/ListHadithsByThemeHandler.php:52` — passes `theme_ids`; `app/Domain/Hadith/Repositories/HadithRepository.php:199` — ignores both.
      - **What to do:** Implement indexed `whereHas(isnads narrator_id)` and theme-pivot `whereIn` predicates, decide companion-vs-any-isnad semantics, validate IDs, and add positive/negative tests.
      - **Why it matters / trade-offs:** Existing callers silently receive unrelated Hadiths. Isnad narrator filtering is more accurate but costlier than `companion_id`; document the chosen meaning.

- [ ] **HAD-025** [P2] Define semantic related-Hadith selection
      - **Module:** Hadith detail  ·  **Type:** ENHANCEMENT
      - **Evidence:** `app/Http/Controllers/Site/HadithController.php:443` — related items are simply the first seven from the same book.
      - **What to do:** Rank canonical, non-merged candidates by shared themes, narration group, chapter and curated links; exclude current ID and cap/select/eager-load card fields.
      - **Why it matters / trade-offs:** “Related” currently means positional proximity, not topical relation. Ranking adds query complexity and should be measured.

- [ ] **HAD-026** [P2] Show retryable lazy-translation failures
      - **Module:** Chapter reader  ·  **Type:** ENHANCEMENT
      - **Evidence:** `resources/views/public/hadith/chapter.blade.php:90` — fetches a batch; `resources/views/public/hadith/chapter.blade.php:96` — silently swallows failures while translation panels can remain open.
      - **What to do:** Track `translationsError`, close or annotate affected panels, render a translated retry control/live-region message, and distinguish 429/network/server errors.
      - **Why it matters / trade-offs:** A blank panel looks like missing translation content rather than a transient failure. Retry state adds small Alpine complexity.

- [ ] **HAD-027** [P3] Extract reusable public Hadith cards, grading badges and action bars
      - **Module:** Hadith public views  ·  **Type:** ENHANCEMENT
      - **Evidence:** `resources/views/public/hadith/chapter.blade.php:308` — inline card; `resources/views/public/hadith/explorer.blade.php:424` — second inline card; `resources/views/public/hadith/show.blade.php:447` — third related card; `find resources/views/components/site -maxdepth 1 -type f` contains no Hadith card component.
      - **What to do:** Create focused Blade components for grading badge, Hadith summary card, source line and favorite/share actions with density/locale slots; keep query DTOs page-specific.
      - **Why it matters / trade-offs:** Repetition has already produced parity drift. Avoid a single over-configurable “god component”; extract only stable visual behavior.

- [ ] **HAD-028** [P3] Repair explorer form semantics and modal focus behavior
      - **Module:** Hadith explorer/detail  ·  **Type:** A11Y
      - **Evidence:** `resources/views/public/hadith/explorer.blade.php:290` — duplicate nested quick-search `<form>` opening; `resources/views/public/hadith/show.blade.php:629` — modal supports Escape/ARIA but has no focus trap/initial focus/restore.
      - **What to do:** Remove the duplicate form, associate all controls with labels, implement focus trap and trigger-focus restoration for share/narrator dialogs, and test keyboard-only navigation.
      - **Why it matters / trade-offs:** Invalid form nesting can change submission behavior, and keyboard/screen-reader users can lose context in dialogs.

## Doc corrections

- `../WEB_APP_MODULES_ANALYSIS.md:53` — “narration-comparison endpoint missing” is stale; `routes/web.php:89` and `app/Http/Controllers/Site/HadithController.php:336` implement it.
- `../WEB_APP_MODULES_ANALYSIS.md:268` — “text_hash + merged_into_id directly supports finding parallel narrations” is misleading; `app/Domain/Hadith/Services/HadithDeduplicationService.php:17` proves it groups exact whitespace-normalized text, not variants with additions.
- `../WEB_APP_MODULES_ANALYSIS.md:280` — the suggested missing endpoint now exists and is indexed by `database/migrations/2026_03_09_000001_add_text_hash_to_hadith_hadiths_table.php:17`.
- `../KNOWN_ISSUES.md:18` — open KI-6 says placeholder routes/chapter/narration pages are missing, but the same document marks it fixed at `../KNOWN_ISSUES.md:40`; current `routes/web.php:81` confirms all assigned routes are concrete and no route references the placeholder.
- `../PROJECT_PROGRESS.md:90` — “UI matches prototypes (parity-audited)” is too broad: `resources/views/public/hadith/explorer.blade.php:191` lacks the prototype's theme/narrator/book/multi-grade/saved-search controls, and `resources/views/public/hadith/book.blade.php:234` lacks the famous tab.
- `../PROJECT_PROGRESS.md:79` — “search facets at zero extra queries” is not supported by the current explorer path: `app/Domain/Hadith/Repositories/HadithRepository.php:221` omits relations that `app/Domain/Hadith/Application/DTOs/HadithDTO.php:48` accesses per row; the existing query-count test at `tests/Feature/Repositories/HadithRepositoryTest.php:134` only fetches the paginator and never maps DTOs.
- `MODULES-AND-PAGES-INVENTORY.md:57` — “fully migrated to translation tables” is inaccurate for collections/books/significance/context; the base columns remain in `database/migrations/2025_11_21_160000_create_hadith_collections_table.php:17` and `database/migrations/2025_11_21_160004_create_hadith_hadiths_table.php:22`.
- `MODULES-AND-PAGES-INVENTORY.md:81` — documented query parameters (`book`, `grading`, `narrator`, `search`) do not match the current API controller's `collection_id`, `book_id`, `grade`, `narrator_id`, and separate `/search`; `app/Http/Controllers/Api/HadithController.php:34` is the current contract.
- `MODULES-AND-PAGES-INVENTORY.md:93` — the stated 2,798 Hadiths/eight books is stale relative to the later runtime verification claim at `../PROJECT_PROGRESS.md:21`; this static review did not query the live DB.
- `../KNOWN_ISSUES.md:20` — the “~2.1 languages/Hadith” content claim cannot be re-proven from migrations/code; static evidence only establishes five supported codes at `app/Domain/Hadith/Models/HadithTranslation.php:69`.

## Open questions

- Which locales have nonempty production `hadith_translations.text` rows, and what are per-locale verified/primary counts? Static code proves schema/fallback behavior but not live content distribution; `app/Domain/Hadith/Models/HadithTranslation.php:69` is only a supported-code declaration.
- How many current `hadith_hadiths` rows have `merged_into_id IS NOT NULL`, and how often do they appear in public results? The migration/service prove the possibility at `database/migrations/2026_03_09_000002_add_merged_into_id_to_hadith_hadiths_table.php:16`, but action safety prohibited DB inspection.
- Does production lazy loading throw rather than issue N+1 queries? The static query/DTO mismatch is definite at `app/Domain/Hadith/Repositories/HadithRepository.php:221` and `app/Domain/Hadith/Application/DTOs/HadithDTO.php:48`; environment behavior needs a running request/query log.
- What editorial definition should “parallel narration” use: same canonical saying, same companion, overlapping isnad, or a curated equivalence? Exact hash behavior is known at `app/Domain/Hadith/Application/Handlers/GetParallelNarrationsHandler.php:47`; the target semantics need scholarly/product ownership.
- Are audio rights and per-Hadith recordings available? The assigned prototype explicitly marks its audio fake at `../the-last-messenger-ui/hadith-single-enhanced.html:203`, and the current schema has no source.
- Should collection-level bookmarks be supported as a new favorite type, or should the prototype control be removed? `app/Domain/Shared/Enums/FavorableType.php:20` includes Hadith but no Hadith collection type.
- Browser/runtime verification was intentionally not performed, and no database, migration, seeder or test suite was executed; this report is a static read as requested.
