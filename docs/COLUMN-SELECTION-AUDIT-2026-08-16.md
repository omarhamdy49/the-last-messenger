# Column & relation selection audit — module by module, 16 Aug 2026

Companion to [PRODUCTION-REVIEW-2026-08-16.md](PRODUCTION-REVIEW-2026-08-16.md).

Scope: does every query select only the columns and relations it actually uses, and
never fetch unbounded rows? Five parallel sweeps — Quran, Hadith+Seerah,
People+Topics, Blog+Media+Content, and the full API surface endpoint by endpoint.

The stress test is what makes this the right thing to audit: at 60 VUs, `Slow_queries`
stayed at **0** while p95 tripled. No single query is slow. There are simply far too
many of them, each hauling far more than it needs.

---

## 0. Two production bugs found on the way — verified against live data

### B1 — `/api/v1/quran/surahs` reports the wrong surah number

`app/Http/Resources/SurahResource.php:27`

```php
'number' => $this->revelation_order,     // and revelation_order is ALSO emitted at :30
```

The surah number **is** the id (`SurahRepository:65-68` documents this). Verified on
production:

| id | name | `revelation_order` | API reports `number` as |
|---|---|---|---|
| 1 | الفاتحة | 5 | **5** |
| 2 | البقرة | 87 | **87** |
| 114 | الناس | 21 | **21** |

Every consumer of this endpoint has been getting the revelation order in the `number`
field. Fix: `'number' => $this->id`.

### B2 — `TranslationResource` throws a 500 on four Quran endpoints

`app/Http/Resources/TranslationResource.php:33-39`

```php
'edition' => $this->whenLoaded('resource', fn () => [
    'name' => $this->resource->getName(app()->getLocale()),   // ← boom
    …
]),
```

Inside a `JsonResource`, `$this->resource` is the **wrapped model** (the
`VerseTranslation`), not the `resource()` belongsTo. Verified on production:
`getName()` exists on `App\Domain\Quran\Models\TranslationResource` (the model) and
**does not exist on `VerseTranslation`** — so the call falls through
`Model::__call` → `Builder::__call` → `BadMethodCallException`.

The guard does not save it: `VerseRepository::TRANSLATION_RESOURCE_EAGER` (`:77`)
eager-loads `translations.resource` on `getBySurah`, `getBySurahAndVerse`,
`searchArabic`, `searchTranslation` and `getRandomVerse` — so `whenLoaded('resource')`
passes and the closure runs. Affected: `/api/v1/quran/surahs/{n}/verses`,
`/verses/{v}`, `/random`, `/search`.

`tests/Feature/Api/QuranApiTest.php` runs against an empty database, so every path
404s before reaching the resource. That is why this has never been caught.

```php
$edition = $this->resource->resource;   // bind once, then read from $edition
```

…and widen the eager load, which currently omits the two columns `getName()` reads:

```php
private const TRANSLATION_RESOURCE_EAGER = 'translations.resource:id,slug,name,language,is_default';
```

---

## 1. The pattern that matters most: constrained query, unconstrained Resource

**A column-constrained query is only correct if you check what the Resource actually
reads.** Neither Eloquent nor Spatie errors on a missing attribute — both return
`null`/`''`. So this failure is always silent, and always ships to production.

Six confirmed live instances:

| Endpoint | Query selects | Resource emits | Result |
|---|---|---|---|
| `/api/v1/sahaba`, `/sahaba/search` | `LIST_COLUMNS` — no `bio`, no `birth_place` | `MuslimResource:34,39` reads both | `""` for all 7,837 people |
| `/api/v1/seerah/map` | 7 columns | `SeerahEventResource` emits 38 fields | 16 fields `null` + a per-row media N+1 |
| `/api/v1/media/programs` (+ popular, recent) | `translations:…,title,is_ready` | `MediaProgramResource:21,22` reads `description`, `host` | `{ar: null, en: null}` |
| `/api/v1/media/programs/popular` | select omits `is_featured`, `is_trending`, `total_seasons`, `total_episodes` | resource emits all four | `null` |
| `/api/v1/quran/*` | `VERSE_COLUMNS` / `SURAH_COLUMNS` omit timestamps | `VerseResource:42-43`, `SurahResource:36-37` | `created_at`/`updated_at` always `null` |
| `/api/v1/articles` | — | `ArticleResource` category | `category.name` `null` (dropped column) |

For the list endpoints the right fix is usually to **drop the field from the list
Resource**, not to widen the select — `$this->whenHas('bio', …)` keeps the detail
endpoint honest while the list stops shipping nulls.

**The mirror-image trap:** a column-constrained `with()` that omits the owning foreign
key returns an **empty relation**, silently. Two places where the fix must not
introduce it:

- `TopicRepository:95` — adding `MorphTo::constrain()` requires keeping `id` in every
  per-type column list, or every topic page renders empty.
- `MediaEpisodeRepository` — the `media` column list must keep `generated_conversions`,
  or `getFirstMediaUrl($collection, 'thumb')` silently falls back to the original.

---

## 2. Systemic root cause

`app/Domain/Shared/Services/BaseService.php:50,62`

```php
return $this->repository->find($id, ['*'], $relations);        // hardcoded — not forwarded
return $this->repository->findOrFail($id, ['*'], $relations);
```

`BaseService` doesn't even accept a column list. And `BaseRepository` defaults `['*']`
on all six read methods — `all` :51, `paginate` :69, `find` :83, `findOrFail` :101,
`findBy` :115, `findAllBy` :133 — four of which wrap the result in `Cache::remember`,
so the star-selected row is serialised into the cache store too.

**Every `$service->find()` in every module is an unavoidable `SELECT *`.** That one
default produces the star-selects independently flagged in `MediaProgramExporter:27`,
`SeriesService:91`, `CategoryService:342`, `MediaProgramController:150`,
`MediaEpisodeController:136`, `MediaEpisodeService:192/209/227`, and
`Admin/Seerah/SeerahEventController:139`.

Changing the default is breaking. The cheaper move is a `DEFAULT_COLUMNS` constant per
repository — `LiveChannelRepository::LIST_COLUMNS` and `SeriesService::ARTICLES_COLUMNS`
already demonstrate the shape.

---

## 3. Quran

**The `load()` epidemic — 16 calls across the CQRS handlers.** `getBySurah()` already
eager-loads translations *with an explicit column list*; then
`ListVersesBySurahHandler:29` calls `$verses->load('translations')`, which both issues a
**second** query and **replaces** the constrained relation with `SELECT *`.

```
ListVersesBySurahHandler:29    load('translations')        ← public surah reader hot path
ListVersesByPageHandler:29     load('translations')
ListVersesByJuzHandler:26      load('translations')
ListVersesByHizbHandler:26     load('translations')
GetVerseByKeyHandler:36,40,43  translations, tafsir, surah    ← surah already loaded
GetSajdaVersesHandler:29,32    translations, surah            ← surah already loaded
GetRandomVerseHandler:30,33    translations, surah            ← surah already loaded
GetSurahByNumberHandler:34     load(['verses','verses.translations'])  ← unbounded
GetSurahByIdHandler:34         find($id, ['*'], $relations)   ← + cached
```

Other findings:

- **`VerseRepository:71`** — `TRANSLATIONS_EAGER` selects `text_notashkeel`, which exists
  only to feed the FULLTEXT index and is rendered nowhere. Doubles the bytes on every
  surah/juz/page/search read. Split the constant; only the API path needs it.
- **`VerseRepository:175,265,292,305`** — same unfiltered translations load as the
  already-reported `:135`: every edition in every language where one locale renders.
- **`VerseRepository:332`** — `paginateWithFilters` eager-loads only the surah, but
  `admin/verses/index.blade.php:228` reads `$verse->translations` → **15 lazy `SELECT *`
  per page**, then discards all but the Arabic row.
- **`VerseRepository:119`** — `pluck('id')` round-trip feeding a `whereIn`; belongs as a
  subquery.
- **`TafseerRepository:120,137,153,183,196,209,221,295`** — eight further bare
  `SELECT *` reads on a `longText` table, each with an unconstrained `with(['verse.surah'])`.
- **`ReciterRepository:90,112,134,147,169`** — five methods with no `select()`, no
  `limit`, and **no `translations` eager load**, so `HasLocalizedContent` lazy-loads per
  model. The public reciters page fetches `perPage: 200` × every locale × `longText
  description` to render a name. `:169` backs an admin autocomplete, unbounded.
- **`SurahService:63`** — `findOrFail($id, ['*'], ['verses'])` loads up to 286 verses for
  an admin view whose Blade reads only `$surah->verses_count`.
- **`VerseService:106`** — explicit `['*']` plus an unconstrained `tafsirs` (`longText`,
  all editions).
- **`Verse.php:125` / `Surah.php:84`** — accessors that ignore an eager-loaded relation
  and issue one `SELECT *` per model (siblings of the reported `Verse.php:100`).
- **`Site/QuranController:648`** — an unbounded `getFeatured()` fetch whose result is
  narrowed in PHP and then **never used** (`'featured'` is recomputed at `:684`). Delete.
- **`Site/QuranController:713,722`** — direct Eloquent in a controller, `SELECT *` +
  unconstrained `with('translations')`; recitations sorted in PHP on `surah_id`.
- **`Admin/Quran/VerseController:335`** — `$v->translations->first()?->text`: every
  edition in every language loaded, then whichever row MySQL happened to return first is
  kept. Non-deterministic *and* the reason the eager load can't be narrowed.
- **`AiInquiryRepository:54`** — selects `answer` (`longText`) for a listing that never
  renders it.
- **Exporters** — `SurahExporter:36` hydrates 6,236 verses so `map()` can `count()` them
  (that's a `withCount`); `TafseerExporter:36,60` and `VerseExporter:36,54` are
  `SELECT *` + unconstrained relations + unchunked `get()`.

**Clean:** `RecitationRepository` (entire file), `VerseRepository::getMushafWordsBySurah`
(:453 — the reference pattern for the module, with a comment explaining the memory
failure it fixes), `getActiveTranslationResources`, `TafseerRepository::getVerseText`
(`->value()`), `ReciterRepository::paginateWithFilters`, `QuranNavigationService`,
`SearchVersesHandler`, `ListTafsirBySurahHandler`.

---

## 4. Hadith & Seerah

`hadiths` carries **four unbounded columns** — `text_ar`, `text_en`, `search_ar`,
`search_en`. The last two are folded duplicates that exist only to be `MATCH`ed against
and are never rendered.

- **`HadithBrowseService:99`** — `findHadith()` selects all four on every detail view.
  `search():137` gets this right; the detail path doesn't.
- **`HadithBrowseService:33,40,68,78`** — `collections()`, `findCollection()`,
  `findBook()`, `chapters()` all lack `select()`, shipping `mediumText intro_ar/intro_en`
  and `json intro`/`ending` that no Blade renders. `:33` is also the payload of a 1-hour
  cache entry, so the blob is serialised and deserialised on every hit. `chapters()` is
  unbounded.
- **`Admin/Blog/ArticleModuleController:198`** — the editor's hadith picker runs
  `LIKE '%term%'` across **both `longText` bodies** on every keystroke: a full scan over
  37k rows, while `ft_hadiths_search_ar`/`_en` sit unused. It also searches unfolded
  text, so typing `الصلاة` misses rows stored as `الصلاه`. `:181` additionally selects
  both full bodies for a truncated preview.
- **`Api/InteractionController:229`** — `visibleContent()` hydrates a full row as an
  *existence check*, and `getTopContent()` calls it **once per item**, up to 100 times.

Seerah:

- **`SeerahEventRepository:78,91,104,117,160,229,247`** — seven paginated list reads with
  a carefully column-constrained `listRelations()` but **no `select()` on the base
  table**, so every row still ships `gallery`, `statistics`, `era`, `phase`, `is_pivotal`
  and timestamps.
- **`SeerahEventRepository:390`** — `listRelations()` isn't locale-filtered: a 12-row page
  loads 60 translation rows carrying `text description` to render 12.
- **`SeerahEventRepository:560`** — `detailRelations()` loads `longText content` + two
  JSON blobs for **all five locales**. Its twin `publicDetailRelations():405` does this
  correctly with a locale closure — `detailRelations()` is the un-migrated version, still
  used by the API `show` endpoint and the admin controller.
- **`SeerahEventRepository:354`** — `exportRelations()` pulls `content`/`lessons`/`sources`
  across five locales; `SeerahEventExporter::map()` reads none of them (and truncates
  `description` to 300 chars).
- **`SeerahEventRepository:368`** — `findBySlug()` takes `$columns = ['*']` and every API
  caller uses the default.
- **`SeerahMilestoneRepository:45`** — eager-loads all 548 published events per milestone
  **purely to compute three integers** in PHP (`SeerahController:181,185,186`). Three
  `withCount` subqueries replace the entire load.
- **`SeerahEventResource:25,26`** — `getTranslations()` emits every locale on every list
  endpoint, which is what locks the wide eager load in place.
- **`Admin/Seerah/SeerahEventController:139,254,190`** — `find()` star-selects; bulk
  delete star-selects **per iteration** to read one `featured_image` string; the audit
  diff calls `toArray()` on the whole row twice per update.

**Clean:** `HadithBrowseService::books/neighbours/search/gradeCounts`, all 30 files under
`Import/Sunnah/**` (zero direct DB access, chunked `upsert`), `SeerahEventRepository`'s
`getPublicPage`/`publicDetailRelations`/`getAdjacentForPublic`/`getRelatedForPublic`
(explicit columns, locale closures, FKs present, `limit()` on related),
`paginateWithFilters`, and every aggregate method. `Site\HadithController` holds no
queries at all.

---

## 5. People & Topics

- **`Site/GenealogyController`** — **six** unconstrained `Muslim` fetches (`:234`, `:422`,
  `:476`, `:581`, `:615`, `:796`), four of them unbounded, all feeding a `mapPerson()`
  that reads 11 columns — while each query hauls `content` + `birth_place` JSON,
  `profile`, `search_name_*`, and `islamic_roles.*` with pivot timestamps. One shared
  `NODE_COLUMNS` constant fixes all six.
- **`Site/GenealogyController:648`** — a **duplicate** of the `findMostConnectedRootId`
  correlated-subquery scan, copied into the controller. Fixing the repository version
  leaves this one broken.
- **`Site/GenealogyController:407`** — spouse edges fetched as `SELECT *`, unbounded,
  across the whole graph.
- **`Site/GenealogyController:602`** — loads *every* edge with both counterparts hydrated,
  then keeps only the spouses in PHP.
- **`EloquentMuslimRelationshipRepository:17`** — counterpart columns disciplined, edge row
  `SELECT *`, **no LIMIT**, on the table whose own design note is that it fans out
  combinatorially.
- **`GenealogyTreeService:146`** — `is_published` / `hide_from_tree` filtered in PHP after
  hydrating both counterparts. The discard rate is high: 80 of 7,837 rows are published.
- **`Admin/SahabiController:84`** — three full COUNT passes over 7,837 rows per index load
  (one `selectRaw` with conditional sums replaces them). Same shape at `:330` and
  `Site/SahabaController:186` (four passes).
- **`Site/SahabaController:168`** — fetches 5 rows to keep 4 and filters self in PHP, when
  `MuslimListFilters::excludeId` already exists and the repository already honours it —
  and runs a full paginator COUNT to render four cards.
- **`TopicRepository:95`** — `morphWith()` constrains the *nested* relations but leaves the
  itemable itself `SELECT *` (full articles including `content`/`title`/`excerpt` JSON,
  where the mapper reads `thumb` + `locales`).
- **`TopicService::mapArticle:202`** — `thumbUrl('card')` does a `Storage::exists()`
  filesystem stat **per article** inside the map; 200 stats per cache miss.

**Clean:** `EloquentMuslimRepository::treeCounts` (:191 — the reference conditional
aggregate), `parentLinksFor`, `childrenOf`, `paginate`, the three bulk writes;
`MuslimPersonLookup:31` (exactly the right cross-domain contract shape);
`MuslimRelationshipService::existsBetween` (`->exists()`, both directions in SQL);
`Admin/SahabiController:319` `withCount` block and `browse()`; `Api\SahabiController`
(holds no Eloquent); `TopicRepository::itemableRelations` (every nested relation carries
its owning FK) and `itemCounts` (:105).

---

## 6. Blog, Media & Content

- **`ArticleRepository`** — eleven query builders with **no `select()`** (`:58`, `:83`,
  `:103`, `:135`, `:152`, `:165`, `:210`, `:606`, `:624`, `:674`, `:828`). Because
  `articles` is mid-migration it still carries live JSON `title`, `excerpt`, `content`,
  `seo_title`, `seo_description` — so a 100-row page drags 100 full article bodies
  **twice** (JSON column + `article_locale.content`) to render titles. `:58` is both the
  admin index *and* `Api\ArticleController::index()`. `getPublishedForPublicIndex():243`
  already has the right column list — it just wasn't applied to the other eleven.
- **`ArticleRepository:590`** — the one article endpoint whose `locales` closure has **no
  column list**, dragging `content` and `seo_metadata` per row; its five siblings all use
  `'locales:id,article_id,locale,title,slug,is_ready'`.
- **`ArticleRepository:638`** — selects a whole `article_locale` row including `content`
  purely to read `article_id`. `->value('article_id')`.
- **`ArticleRepository:205`** — hydrates the article, every category and every tag so it
  can `pluck('id')` off them. `:291` already does the narrow version.
- **`ArticleRepository:765`** — `getFormTags()` fetches **every** article tag, unbounded,
  on every article create/edit render.
- **`Admin/Blog/ArticleController:71`** — re-runs the *identical* four eager-loads the
  repository performed one statement earlier. Four wasted queries per index render.
- **`Admin/Blog/ArticleController:134,232`** — loads 14 relations unconditionally after
  store/update, then returns a redirect that discards them.
- **`Admin/Blog/ArticleController:615`** — `$article->fresh()->views` re-reads the whole
  row to print one integer that `incrementViews()` already returned.
- **`Site/SeriesController:88`** — public series page: every article, no `select()`, no
  limit, `locales` with no column list — to render a title, a 180-char excerpt and a read
  time. Worst payload-to-render ratio found. `:39` is an unpaginated `get()` over the
  whole series table.
- **`Site/ArticleController:274` and `:333`** — the *same* unbounded full-series article
  fetch runs **twice per article page**. On a 60-article series that's 120 full bodies for
  two links.
- **`CategoryService:339`** — `findOrFail()` defaults to `['*']` **and** eager-loads
  `'articles'` — every article in the category, unbounded, with full JSON bodies — for a
  page that shows a count. Both controllers then run a separate `->count()` anyway.
- **`CategoryService:426`** — fetches all siblings (`['*']`, cached) to compute a `max()`
  in PHP.
- **`CategoryService:461` + `Category:120` + `Category:302`** — three separate per-level
  parent-chain ladders fire on a single category save.
- **`Category:380`** — `getTree()` loads `description` in all five locales for every
  category when `buildTreeNodes()` reads seven fields.
- **`TagRepository:33`** — `getBaseQuery()` has no `select()` and no `translations`, so
  every view resolving `$tag->name` lazy-loads one query per row. Same at `:281`
  (autocomplete) and `:310`.
- **`TagResource:33`** — reads the **dropped** `tags.name`/`tags.description` columns;
  `Tag` declares no accessor, so the API emits `translations.name: null` for every tag.
- **`MediaProgram:108`** — the `deleting` hook hydrates every Spatie media row (four JSON
  columns each) and issues one `DELETE` per row: deleting an 800-episode program is 800
  fat selects + 800 deletes inside the request.
- **`MediaEpisodeRepository`** ×7 and **`MediaProgramRepository`** ×10 — `'media'`
  eager-loaded with **no column list**, pulling four Spatie JSON columns per attachment.
  (Keep `generated_conversions` in the fix or the `thumb` conversion silently falls back.)
- **`MediaEpisodeRepository:220,261`** — one `UPDATE` per episode on reorder/assign;
  reordering a 500-episode program is 501 statements holding a write transaction open.
  `:234` is an O(n²) PHP scan inside the loop.
- **`Site/MediaController:186,228`** — maps **every** episode into a card on the program
  page, and again on the player page. A 900-episode synced program renders 900 cards on a
  page showing one video. `:200` derives the season list in PHP from the full collection.
- **`Site/MediaController:314`** — `mapProgramCard()` reads four accessors that each fall
  through to `MediaProgram::firstEpisodeVideoId():193` → up to **three extra queries per
  card**, × 12 cards × 6 rows on the hub page.
- **`Admin/Media/MediaProgramController:150`** — `loadMissing(['translations',
  'episodes.translations'])`: every episode of the program and every translation of every
  episode, uncolumned, for one admin show page. `:222` and `:239` load the same row three
  times per delete.
- **`MediaRepository:16`** — the media-library grid, the highest-volume admin listing, has
  no `select()`. `:228` fires **seven separate COUNT/SUM queries** where one pass does it.
- **`Media.php:336`** — `publicRouteKey()` does a `saveQuietly()` when `public_key` is
  blank: **a write executed during a read**, once per row. A 50-row page of legacy media
  issues up to 50 UPDATEs inside a GET. `MediaResource:21,27` then calls it again per
  variant — up to 250 invocations on a 50-image picker page.
- **`MediaProgramExporter:27`** — `all()` → explicit `select(['*'])` over the whole table,
  cached, with no relations, then `1 + 2N` lazy translation loads in `map()`.
- **`ArticleResource:29`** — `statistics` and `references` JSON emitted on **every item of
  every list response**.
- **`SeriesResource:34`** — nests the full `ArticleResource` (statistics + references +
  tags + locales) for every article in the series, unbounded.

**Clean:** the entire **Content domain** — `HomeContentRepository`, `HomeSectionRepository`,
`MenuItemRepository`, `StaticPageRepository`, `SiteSettingRepository`, `FaqRepository`,
`ContactMessageRepository` all define explicit column constants and column-constrained
translation loads. **This is the pattern the other domains should be measured against.**
Also clean: `ArticleRepository::getPublishedForPublicIndex/getPublishedForSitemap/
getRelatedForPublic/getAdjacentForPublic/getPublishedCategoryFacets/getPublishedTagFacets`,
the entire `FavoriteRepository`, `MediaEpisodeRepository::getContinueWatching` (:330 — the
best-instrumented query in the codebase), `MediaProgramRepository`'s program column lists,
`LiveChannelRepository::LIST_COLUMNS`, `CategoryRepository::listRoots/findBySlug`,
`MediaViewRecorder`.

---

## 7. API surface — endpoint verdicts

87 routes traced (`routes/api.php` plus the JSON-returning `routes/web.php` routes).

| Verdict | Count | Notes |
|---|---|---|
| CLEAN | 48 | |
| WASTEFUL | 23 | fetches columns/relations it never serialises |
| BROKEN (silent null) | 6 | the §1 pattern |
| N+1 | 12 | mostly `getFirstMediaUrl` and unloaded `type` relations |
| UNBOUNDED | 5 | no pagination, no cap |
| **500** | **4** | `TranslationResource` — see B2 |

Worth singling out:

- **`POST /api/v1/filters/{id}/apply`** returns **raw Eloquent models** —
  `ApiResponse::paginatedResponse` serialises `$paginator->items()` verbatim, and
  `getDefaultRelations(ARTICLE)` eager-loads `writer`. `users.email` is **not** in
  `User::$hidden`. So this endpoint leaks writer email addresses plus every article
  column including all locale `content` and `seo_metadata` JSON.
  `Api/ArticleController:164` already documents this exact hazard and works around it
  with `asResources()` — reuse that.
- **`POST /api/v1/search`** — `SearchService:411` is `SELECT *` across **five models per
  request** (up to 100 rows each) with bare eager loads; `SeerahEventResource` then
  lazy-loads `type` per row and `MediaProgramResource` lazy-loads `media` per program.
- **`GET /api/v1/quran/surahs/{n}/verses`** — unpaginated (286 verses × every translation
  edition). `paginateBySurah()` already exists two methods below, unused by the API.
- **`GET /api/v1/engagement/export`**, **`/filters`**, **`/filters/public`** — `->get()`
  with no cap.
- **`GET /api/v1/categories`** — root rows are correctly guarded, but nested children are
  rendered with no `children` loaded, so `hasChildren()` + `isLeaf()` each fire a query
  per child. `withExists('children')` fixes it — `hasChildren():208` already reads
  `children_exists`, the attribute is just never set.
- **`UserInteractionRepository:36`** — `with('interactable')` (a bare morphTo) where
  `UserInteractionResource` only ever emits `class_basename($this->interactable_type)`.
  Deleting the `with()` is the entire fix.

**Clean and worth not regressing:** every `per_page` on the public API is validated
`min:1|max:100` or clamped (`SearchQueryBuilder::resolvePerPage:153`,
`MuslimListFilters::fromRequest:49`) — the unclamped instances are all in admin
controllers. `/api/v1/quran/juz|page|sajda`, `/media/programs/{slug}/episodes*`,
`/media/continue-watching`, `/roles`, `/search/suggestions|trending|modules`, and all the
engagement aggregate endpoints are correct end to end.

---

## 8. Suggested order

1. **B1 + B2** — two live bugs, both one-line fixes, one of which is a 500.
2. **`/api/v1/filters/{id}/apply`** — the PII leak.
3. **The six silent-null endpoints** (§1) — decide per endpoint whether to widen the
   select or drop the field, then add a test that asserts a non-null value.
4. **The Quran `load()` epidemic** (§3) — highest measured latency payoff; `lat_quran`
   p95 was 1.69 s under stress and this is the mechanism.
5. **`ArticleRepository` `CARD_COLUMNS`** (§6) — eleven call sites, one constant.
6. **`SeerahEventRepository::LIST_COLUMNS` + locale-filtered `listRelations()`** (§4).
7. **`BaseService`/`BaseRepository` `['*']` default** (§2) — do this after 4–6, since
   those fixes establish the per-repository column constants it needs.
8. The unbounded endpoints and the per-row `UPDATE` loops.
