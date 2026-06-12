# Database Optimization Audit Report
**Project:** The Last Messenger Backend
**Date:** 2026-03-17
**Auditor:** Claude (Database Optimization Specialist)

---

## Executive Summary

The codebase is well-structured with solid foundations: column-constrained eager loads, FULLTEXT indexes on the primary search tables, and DB::transaction coverage on multi-step writes. The critical gaps are concentrated in five areas: a fatal Cache::tags mismatch with the file driver, unbounded collection queries on large tables, a 7-query loop in the sparkline, two-step pluck+whereIn patterns that should be joins, and missing composite indexes for the most common filter combinations.

---

## CRITICAL ISSUES (Fix Immediately)

---

### CRIT-1: Cache::tags Used With File Cache Driver — Will Silently Fail or Throw
**File:** `app/Domain/Shared/Repositories/BaseRepository.php` line 223
**Also affects:** `app/Domain/Shared/Services/AnalyticsService.php`, `app/Domain/Shared/Services/LanguageCacheService.php`, `app/Domain/Quran/Repositories/ReciterRepository.php`, `app/Domain/Genealogy/Repositories/PersonRepository.php`

**Problem:** `Cache::tags([$this->cachePrefix])->flush()` is called in `BaseRepository::clearCache()`. Cache tags are only supported by the `redis` and `memcached` drivers. The `.env` has `CACHE_STORE=file`, which does not support tags. On Laravel 10+ this throws `BadMethodCallException: This cache store does not support tagging.`

This means every create/update/delete operation that calls `clearCache()` through `BaseRepository` throws an exception at the cache-flush step, making those write paths silently broken if the exception is swallowed, or fatally broken if it is not.

**Fix:** Either switch to Redis (`CACHE_STORE=redis`) — the correct long-term fix for a production app of this scale — or replace `Cache::tags` with explicit key enumeration using `Cache::forget()`. A practical intermediate approach is to prefix all cache keys with the model name and `Cache::forget` each known key pattern in `clearCache()`.

**Performance Impact:** CRITICAL (broken cache invalidation on every write)

---

### CRIT-2: Sparkline Query Loop — 7 Separate COUNT Queries Per Dashboard Load
**File:** `app/Domain/Shared/Services/DashboardService.php` lines 274–283

**Problem:** `getSparklineDataForTable()` runs inside `getEnhancedStatistics()` which is cached for 5 minutes. However, each call to the method issues one `COUNT(*)` per day for 7 days:

```php
for ($i = $days - 1; $i >= 0; $i--) {
    $date   = now()->subDays($i)->toDateString();
    $data[] = DB::table($table)->whereDate('created_at', $date)->count();
}
```

`whereDate('created_at', $date)` applies `DATE(created_at)` to every row, which prevents the `created_at` index from being used (function on indexed column = full scan). This results in 7 full-scan COUNT queries fired on the `users` table on every dashboard cache miss.

**Fix:** Replace with a single grouped query:

```sql
SELECT DATE(created_at) as day, COUNT(*) as count
FROM users
WHERE created_at >= NOW() - INTERVAL 7 DAY
GROUP BY DATE(created_at)
```

Then build the result array from the map. This reduces 7 index-bypassing queries to 1 range-scan query.

**Performance Impact:** HIGH

---

## HIGH PRIORITY ISSUES

---

### HIGH-1: Hadith Full-Text Search Bypassed — LIKE Used on LONGTEXT Column
**Files:** `app/Domain/Hadith/Repositories/HadithRepository.php` lines 141–142, 180–181
**Table:** `hadith_translations` (FULLTEXT index exists as `ft_hadith_translations_text`)

**Problem:** The `hadith_translations.text` column has a FULLTEXT index added in migration `2025_11_21_160005`. However, both `search()` and `paginateWithFilters()` use `LIKE "%{$query}%"` on a LONGTEXT column, bypassing the FULLTEXT index entirely. With 2,798+ hadiths and multiple translations each, a LIKE scan on LONGTEXT columns is extremely slow.

```php
$subQuery->where('text', 'LIKE', "%{$query}%")
    ->orWhere('translation', 'LIKE', "%{$query}%");
```

Note: The `translation` column does not appear in the migration. This is likely a stale reference that will silently return no results or cause a SQL error.

**Fix:**
1. Verify the correct column names are `text` (the FULLTEXT-indexed column exists).
2. Replace LIKE with `MATCH ... AGAINST` for the primary text search:

```sql
MATCH(text) AGAINST(? IN BOOLEAN MODE)
```

In Eloquent: `->whereRaw('MATCH(text) AGAINST(? IN BOOLEAN MODE)', [$query])`

This uses the FULLTEXT index and is orders of magnitude faster on large Arabic text.

**Performance Impact:** HIGH (affects every hadith search with 2,798+ records)

---

### HIGH-2: Verse Search Uses Two-Step pluck+whereIn Instead of a Join
**File:** `app/Domain/Quran/Repositories/VerseRepository.php` lines 149–165, 172–188, 224–233, 292–299

**Problem:** All four search methods (`searchArabic`, `searchTranslation`, `paginateWithFilters`, `search`) follow the same pattern:

```php
$matchingVerseIds = VerseTranslation::query()
    ->select('verse_id')
    ->where('lang', $language)
    ->where(...)
    ->pluck('verse_id');  // Query 1: loads all matching IDs into PHP

return $this->model
    ->whereIn('id', $matchingVerseIds)  // Query 2: large IN clause
    ->paginate($perPage);
```

With 6,236 Quran verses, the `$matchingVerseIds` collection can contain thousands of IDs. Passing a large array to `whereIn` generates a very long SQL `IN (1, 2, 3, ...)` clause that is slow to parse and execute. More importantly, the FULLTEXT index on `quran_verse_translations` (`text`, `text_notashkeel`) is not being used because of the LIKE search.

**Fix:** Use a JOIN-based subquery instead of pluck+whereIn, and switch to FULLTEXT for the text search:

```php
$this->model
    ->select(self::VERSE_COLUMNS)
    ->join('quran_verse_translations as qvt', 'quran_verses.id', '=', 'qvt.verse_id')
    ->where('qvt.lang', $language)
    ->whereRaw('MATCH(qvt.text, qvt.text_notashkeel) AGAINST(? IN BOOLEAN MODE)', [$query])
    ->with([self::SURAH_EAGER])
    ->orderBy('quran_verses.surah_id')
    ->orderBy('quran_verses.verse_number')
    ->paginate($perPage);
```

This collapses two queries into one and leverages the existing FULLTEXT index.

**Performance Impact:** HIGH (all Quran verse search paths)

---

### HIGH-3: SeerahEvent defaultRelations Loads 6 Deep-Nested Relations on Every List Query
**File:** `app/Domain/Seerah/Repositories/SeerahEventRepository.php` lines 150–161

**Problem:** `defaultRelations()` is used on every list and paginate call, including `getByType`, `getByHijriYear`, `getChronological`, and `search`. It loads:

```php
'translations',
'article.locales',
'article.category',
'article.categories',
'article.tags',
'article.mediaItems',
'participants',
```

Loading `article.locales`, `article.categories`, `article.tags`, and `article.mediaItems` on every event list call is expensive because it fires 6+ separate queries per result page. List views rarely need the full article with all its relations — they typically need only the event title and date.

**Fix:** Split into two relation sets: a "list" set (just `translations`) and a "detail" set (the full set). Use the list set on paginated queries and reserve the detail set for single-record `findBySlug`.

**Performance Impact:** HIGH (all Seerah event list endpoints)

---

### HIGH-4: AnalyticsService getContentStats — Multiple Redundant COUNT Queries Not Combined
**File:** `app/Domain/Shared/Services/AnalyticsService.php` lines 60–100

**Problem:** `getContentStats()` fires 12+ separate COUNT queries per invocation (e.g., `Article::count()`, `Article::where('status','published')->count()`, `Article::where('status','draft')->count()`, `Article::where('status','archived')->count()`). These 4 Article counts could be a single `GROUP BY status` query. The same pattern repeats for `HadithNarrator` and others.

```php
'articles' => [
    'total'     => $query(Article::class)->count(),
    'published' => $query(Article::class)->where('status', 'published')->count(),
    'draft'     => $query(Article::class)->where('status', 'draft')->count(),
    'archived'  => $query(Article::class)->where('status', 'archived')->count(),
],
```

**Fix:** Use a single aggregated query per table:

```php
$articleCounts = Article::query()
    ->selectRaw("COUNT(*) as total, SUM(status='published') as published, SUM(status='draft') as draft, SUM(status='archived') as archived")
    ->first();
```

Reduces 4 queries to 1. Apply this pattern across all modules.

**Performance Impact:** HIGH (dashboard analytics; cached but cold-cache is expensive)

---

### HIGH-5: ArticleRepository Search Uses LIKE on article_locale.description and JSON Column
**File:** `app/Repositories/ArticleRepository.php` lines 53–57 and 439–443

**Problem:** `searchArticles()` and `applySearch()` both use `LIKE "%{$query}%"` on `title`, `excerpt`, `description`, and even `JSON_EXTRACT(content, '$.body')`. The `article_locale` table has a FULLTEXT index on `(title, excerpt)` via the migration, but `description` is not in it, and the `JSON_EXTRACT` search is guaranteed to do a full-table scan since MySQL cannot index JSON path expressions.

```php
->orWhere('description', 'LIKE', "%{$query}%")
->orWhereRaw("JSON_EXTRACT(content, '$.body') LIKE ?", ["%{$query}%"]);
```

**Fix:**
1. Add `description` to the existing FULLTEXT index: `ALTER TABLE article_locale DROP INDEX ft_search; ALTER TABLE article_locale ADD FULLTEXT INDEX ft_search (title, excerpt, description);`
2. Replace the LIKE approach with `MATCH(title, excerpt, description) AGAINST(? IN BOOLEAN MODE)`.
3. Remove the `JSON_EXTRACT` search — content body searches should be handled by a dedicated search service (e.g., Meilisearch or Elasticsearch) or a denormalized `content_text` column with its own FULLTEXT index.

**Performance Impact:** HIGH (primary article search path)

---

## MEDIUM PRIORITY ISSUES

---

### MED-1: hadith_hadiths Missing Composite Index for the Most Common Filter Combination
**Migration:** `2025_11_21_160004_create_hadith_hadiths_table.php`
**Repository:** `app/Domain/Hadith/Repositories/HadithRepository.php`

**Problem:** The `paginateWithFilters()` method frequently filters by `(book_id, grading_status)` together. Currently separate single-column indexes exist on both columns, but a composite index `(book_id, grading_status)` would allow MySQL to satisfy both filter conditions and the `ORDER BY hadith_number` from a single index scan (a covering index opportunity).

The `(book_id, hadith_number)` unique constraint added in the deduplication migration is correctly placed, but the filter query pattern `WHERE book_id = ? AND grading_status = ? ORDER BY hadith_number` still requires two index lookups.

**Fix:** Add migration:

```php
$table->index(['book_id', 'grading_status', 'hadith_number'], 'idx_hadith_book_grading_number');
```

This turns the most common admin list query from a two-index merge into a single range scan.

**Performance Impact:** MEDIUM-HIGH

---

### MED-2: article_locale Missing Composite Index on (locale, is_ready)
**Migration:** `2025_10_27_120001_create_article_locale_table.php`

**Problem:** Nearly every article query filters `article_locale` on both `locale` and `is_ready`:

```php
$q->where('locale', $locale)->where('is_ready', true)
```

Separate indexes on `locale` and `is_ready` exist, but MySQL will only use one at a time via index merge (less efficient). A composite index `(locale, is_ready)` enables a direct range scan with both conditions satisfied in one pass.

**Fix:** Add migration:

```php
Schema::table('article_locale', function (Blueprint $table) {
    $table->index(['locale', 'is_ready'], 'idx_article_locale_locale_ready');
});
```

The `article_id` filter is covered by the existing unique `(article_id, locale)` index.

**Performance Impact:** MEDIUM (used by every article filtering and status check)

---

### MED-3: quran_verse_translations Missing Composite Index on (lang, verse_id)
**Migration:** `2025_11_29_100003_create_quran_verse_translations_table.php`

**Problem:** All verse search paths filter `quran_verse_translations` by `WHERE lang = ? AND (text LIKE ... OR text_notashkeel LIKE ...)`. The existing unique index is `(verse_id, lang)` — leading with `verse_id`, which is not selective for a lang-first filter. The separate `lang` index can be used, but the query always needs both columns.

**Fix:** Add migration:

```php
Schema::table('quran_verse_translations', function (Blueprint $table) {
    $table->index(['lang', 'verse_id'], 'idx_qvt_lang_verse');
});
```

**Performance Impact:** MEDIUM

---

### MED-4: personal_access_tokens Missing Index on (tokenable_id, last_used_at)
**Migration:** `2025_10_27_091646_create_personal_access_tokens_table.php`
**Used in:** `app/Domain/Shared/Services/AnalyticsService.php` line 498–505

**Problem:** The active-users count query:

```php
DB::table('personal_access_tokens')
    ->where('last_used_at', '>=', now()->subDays(30))
    ->distinct('tokenable_id')
    ->count('tokenable_id');
```

The table only has indexes on `token` (unique) and `expires_at`. A range scan on `last_used_at` with a DISTINCT on `tokenable_id` requires a full scan of the non-indexed `last_used_at` column. The `morphs('tokenable')` call creates an index on `(tokenable_type, tokenable_id)` but not on `last_used_at`.

**Fix:** Add migration:

```php
Schema::table('personal_access_tokens', function (Blueprint $table) {
    $table->index(['last_used_at', 'tokenable_id'], 'idx_pat_last_used_tokenable');
});
```

**Performance Impact:** MEDIUM (analytics dashboard; hits on every uncached analytics load)

---

### MED-5: SeerahEvent Chronological Sort Uses Three-Column ORDER BY Without Composite Index
**Migration:** `2025_10_27_130000_create_seerah_events_table.php`
**Model:** `app/Domain/Seerah/Models/SeerahEvent.php` — `scopeChronological()`

**Problem:** The `chronological` scope orders by `hijri_year, hijri_month, hijri_day, display_order`. Individual indexes exist on `hijri_year`, `hijri_month`, and `display_order`, but there is no composite index covering the sort. MySQL can only use one index per table per query for ORDER BY; without the composite index, it performs a filesort on every chronological listing.

**Fix:** Add migration:

```php
Schema::table('seerah_events', function (Blueprint $table) {
    $table->index(['hijri_year', 'hijri_month', 'hijri_day', 'display_order'], 'idx_seerah_chronological');
});
```

**Performance Impact:** MEDIUM (every timeline/chronological endpoint)

---

### MED-6: Sahabi battles Column Searched With whereJsonContains — No Index Possible
**Model:** `app/Domain/Sahaba/Models/Sahabi.php` — `scopeParticipatedIn()`
**Table:** `sahaba.battles` (JSON column)

**Problem:** `whereJsonContains('battles', $battle)` performs a full-table scan every time because MySQL cannot create a standard B-tree index on a JSON array element. The `sahaba` table will grow over time.

**Fix:** Create a normalized `sahabi_battles` pivot table:

```sql
CREATE TABLE sahabi_battles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sahabi_id BIGINT UNSIGNED NOT NULL,
    battle_name VARCHAR(255) NOT NULL,
    INDEX idx_sahabi_battles_sahabi (sahabi_id),
    INDEX idx_sahabi_battles_name (battle_name),
    FOREIGN KEY (sahabi_id) REFERENCES sahaba(id) ON DELETE CASCADE
);
```

This enables an indexed lookup by battle name and supports counting participants per battle efficiently. Retain the JSON column for backward compatibility during transition.

**Performance Impact:** MEDIUM (getByBattle endpoint; grows with data)

---

### MED-7: ArticleRepository getStatistics Issues Multiple Independent COUNT Queries
**File:** `app/Repositories/ArticleRepository.php` lines 192–212

**Problem:** Inside the `article_statistics` cache (30 min TTL), `getStatistics()` fires:
- `Article::count()`
- `Article::whereHas('locales',...)->count()` (published)
- `Article::where('is_featured', true)->count()`
- `Article::where('is_pinned', true)->count()`
- `Article::sum('views')`
- `Article::avg('read_time')`
- `Article::where('created_at', '>=', now()->subDays(7))->count()`
- Two more calls to sub-methods that fire additional queries

This is 7+ queries where a single `SELECT COUNT(*), SUM(is_featured), SUM(is_pinned), SUM(views), AVG(read_time), SUM(created_at >= ?)` aggregation query would suffice.

**Fix:** Combine into one DB::select aggregation for the articles table, then handle the `published` count via a separate subquery on `article_locale`:

```php
$stats = DB::table('articles')
    ->selectRaw('COUNT(*) as total, SUM(is_featured) as featured, SUM(is_pinned) as pinned, SUM(views) as total_views, AVG(read_time) as avg_read_time, SUM(created_at >= ?) as recent', [now()->subDays(7)])
    ->whereNull('deleted_at')
    ->first();
```

**Performance Impact:** MEDIUM

---

### MED-8: getByAuthentic and getByGrading Return Unbounded Collections
**File:** `app/Domain/Hadith/Repositories/HadithRepository.php` lines 70–92

**Problem:** `getByGrading()` and `getAuthentic()` call `->get()` with no LIMIT. The `sahih` grading bucket alone likely contains hundreds of hadiths. Loading the entire collection into memory plus all their translations is memory-intensive:

```php
public function getAuthentic(): Collection
{
    return Cache::remember('hadiths_authentic', self::CACHE_TTL,
        fn () => $this->model->select([...])->authentic()->with('translations')->get()
    );
}
```

The cached value will be a potentially multi-MB serialized collection stored in the file cache, and deserialized on every cache hit.

**Fix:** Add pagination to these methods or enforce a hard cap with `->limit(500)`. For API consumption, these should always return paginated results. Update the `HadithRepositoryInterface` to add `int $perPage` parameters.

**Performance Impact:** MEDIUM (memory and cache storage impact)

---

### MED-9: BookRepository countHadiths Calls find() Then hadiths()->count()
**File:** `app/Domain/Hadith/Repositories/BookRepository.php` lines 150–156

**Problem:**

```php
fn () => $this->model->find($bookId)?->hadiths()->count() ?? 0
```

This fires two queries: one to load the book model, then one to count its hadiths. It should be:

```php
fn () => Hadith::where('book_id', $bookId)->count()
```

A single COUNT query with the indexed `book_id` column.

**Performance Impact:** LOW-MEDIUM (repeated on book list views with withCount missing)

---

## LOW PRIORITY ISSUES

---

### LOW-1: hadith_books Missing book_number Index
**Migration:** `2025_11_21_160001_create_hadith_books_table.php`
**Repository:** `app/Domain/Hadith/Repositories/BookRepository.php` lines 162–168

**Problem:** `getByCollectionAndNumber()` filters on `WHERE collection_id = ? AND book_number = ?`. `collection_id` is indexed, `book_number` is not. The query must scan all books in a collection to find the right `book_number`.

**Fix:** Add migration:

```php
$table->index(['collection_id', 'book_number'], 'idx_hadith_books_collection_number');
```

**Performance Impact:** LOW (small table, but a common lookup in collection navigation)

---

### LOW-2: quran_verse_tafsirs verse_key Column Is Redundant With verse_id
**Migration:** `2025_11_29_100004_create_quran_verse_tafsirs_table.php`

**Problem:** The table stores both `verse_id` (FK to `quran_verses.id`) and `verse_key` (e.g., "2:255"). These are denormalized duplicates. `verse_key` can always be derived from the `quran_verses` join. Having a separate index on `verse_key` means two indexes are maintained for what is effectively the same lookup. Any update to a verse key requires updating both tables.

**Fix:** Remove `verse_key` from `quran_verse_tafsirs` and `quran_verse_translations`. Look up by `verse_id` (FK) and join to `quran_verses` when the key string is needed.

**Performance Impact:** LOW (data integrity and storage; not a query hotspot today)

---

### LOW-3: article_series Missing Index on article_id
**Migration:** `2026_03_09_162423_create_article_series_table.php`

**Problem:** The `article_series` pivot has a unique index on `(article_id, series_id)` and a separate index on `series_id`, but no standalone index on `article_id`. The unique composite index `(article_id, series_id)` does cover `article_id` lookups (MySQL uses the leftmost prefix), so this is a minor redundancy concern, but it is worth confirming the unique index is being used for the `WHERE article_id = ?` path in `Article::series()`.

**Assessment:** Already covered by the leftmost prefix of the unique composite index. No action needed.

---

### LOW-4: categories Nested Set lft/rgt Values Not Being Maintained
**Migration:** `2025_11_07_200000_enhance_categories_table.php`

**Problem:** Nested set model columns `lft`, `rgt`, `depth` are added to `categories` with default value `0` for all rows. No model trait (e.g., Baum or Kalnoy nested sets) or maintenance logic was found in the codebase. If these columns are all zero, the nested set index `[lft, rgt]` is useless and the hierarchical query optimization is not happening.

**Fix:** Either implement a nested set package (e.g., `lazychaser/laravel-nestedset`) that maintains these values, or remove the columns and the `[lft, rgt]` index if hierarchy is handled through `parent_id` queries only.

**Performance Impact:** LOW (wasted index maintenance overhead on writes if values are maintained; no benefit if they are zero)

---

### LOW-5: audit_logs and admin_logs Missing Composite Index on (user_id, created_at)
**Migration:** `2025_10_31_075931_create_audit_logs_table.php`

**Problem:** The most common audit log query pattern (per `DashboardService::getRecentActivity`) is:

```php
AdminLog::orderBy('created_at', 'desc')->limit($limit)->get()
```

The `created_at` index exists and covers this. However, filtering by `user_id` with a date range (a common admin panel query) requires two separate index lookups. A composite `(user_id, created_at)` index would serve both purposes.

**Fix:** Add migration:

```php
$table->index(['user_id', 'created_at'], 'idx_audit_logs_user_date');
```

**Performance Impact:** LOW (audit logs are not on critical request paths)

---

## SCHEMA-LEVEL FINDINGS (Full-Text Search Coverage)

| Table | Columns Searched | FULLTEXT Index Exists | Status |
|---|---|---|---|
| `hadith_translations` | `text` | YES (`ft_hadith_translations_text`) | LIKE used instead — fix in HIGH-1 |
| `article_locale` | `title`, `excerpt`, `description` | PARTIAL (`ft_search` on title+excerpt only) | Fix in HIGH-5 |
| `quran_verse_translations` | `text`, `text_notashkeel` | YES | LIKE used — fix in HIGH-2 |
| `hadith_books` | `title_ar`, `title_en` | YES (`ft_hadith_books_titles`) | LIKE used — candidate for FULLTEXT switch |
| `sahabi_translations` | `name`, `full_name`, `kunya`, `laqab` | YES (`fulltext_name_fields`) | LIKE used in SahabiRepository::search() — switch to MATCH |
| `seerah_event_translations` | `title`, `description` | YES (`fulltext_title_description`) | LIKE used in SeerahEventRepository::search() — switch to MATCH |
| `category_translations` | `name`, `description` | YES | Not audited in detail |
| `series_translations` | `title`, `description` | YES | Not audited in detail |

**Pattern finding:** Every repository that has a FULLTEXT index available is using LIKE instead. This is a systemic issue — a shared search helper using MATCH AGAINST should be created and used by all translation-based search methods.

---

## TRANSACTION COVERAGE AUDIT

| Multi-Step Write Operation | Wrapped in DB::transaction? | Status |
|---|---|---|
| `CreateArticleAction::execute()` | YES | Good |
| `UpdateArticleAction::execute()` | YES | Good |
| `CreateHadithHandler` | YES (verified in file list) | Good |
| `UpdateHadithHandler` | YES | Good |
| `HadithImportService` | YES (batch imports) | Good |
| `HadithDeduplicationService::mergeDuplicates()` | Needs verification | Check |
| `BaseRepository::create()` | Single insert — no transaction needed | OK |
| `Admin controllers` (CollectionController, NarratorController, etc.) | YES | Good |

Overall transaction coverage for multi-step writes is solid. The article creation and update flows are the most complex and both are correctly wrapped.

---

## PRIORITIZED RECOMMENDATIONS

### Immediate (Week 1)

| Priority | ID | Fix | Estimated Effort |
|---|---|---|---|
| 1 | CRIT-1 | Fix Cache::tags — switch to Redis or replace with explicit key flushing | 2 hours |
| 2 | CRIT-2 | Replace sparkline 7-query loop with single GROUP BY query | 1 hour |
| 3 | HIGH-1 | Switch hadith text search from LIKE to MATCH AGAINST | 2 hours |
| 4 | HIGH-2 | Replace verse pluck+whereIn with JOIN + MATCH AGAINST | 3 hours |

### Short Term (Week 2)

| Priority | ID | Fix | Estimated Effort |
|---|---|---|---|
| 5 | HIGH-3 | Split SeerahEvent defaultRelations into list/detail sets | 1 hour |
| 6 | HIGH-4 | Combine AnalyticsService multi-count queries to single aggregation | 2 hours |
| 7 | HIGH-5 | Fix article_locale FULLTEXT to include description; drop JSON_EXTRACT search | 2 hours |
| 8 | MED-1 | Add composite index (book_id, grading_status, hadith_number) | 30 min |
| 9 | MED-2 | Add composite index (locale, is_ready) on article_locale | 30 min |
| 10 | MED-5 | Add composite index (hijri_year, hijri_month, hijri_day, display_order) | 30 min |

### Medium Term (Month 1)

| Priority | ID | Fix | Estimated Effort |
|---|---|---|---|
| 11 | MED-3 | Add (lang, verse_id) index on quran_verse_translations | 30 min |
| 12 | MED-4 | Add (last_used_at, tokenable_id) index on personal_access_tokens | 30 min |
| 13 | MED-6 | Normalize sahabi_battles JSON column to pivot table | 4 hours |
| 14 | MED-7 | Combine ArticleRepository::getStatistics() into single aggregation | 1 hour |
| 15 | MED-8 | Add pagination to getByGrading/getAuthentic | 1 hour |
| 16 | ALL-FT | Systematically replace LIKE with MATCH AGAINST in all repositories that have FULLTEXT indexes | 4 hours |

---

## MIGRATION TEMPLATES

### Migration 1: CRIT-2 + MED-1 + MED-2 + MED-3 + MED-4 + MED-5 Combined

```php
// database/migrations/2026_03_17_000001_add_composite_performance_indexes.php

public function up(): void
{
    // MED-1: hadith filter composite index
    Schema::table('hadith_hadiths', function (Blueprint $table) {
        $table->index(['book_id', 'grading_status', 'hadith_number'], 'idx_hadith_book_grading_number');
    });

    // MED-2: article_locale locale+ready composite
    Schema::table('article_locale', function (Blueprint $table) {
        $table->index(['locale', 'is_ready'], 'idx_article_locale_locale_ready');
    });

    // MED-3: quran_verse_translations lang-first index
    Schema::table('quran_verse_translations', function (Blueprint $table) {
        $table->index(['lang', 'verse_id'], 'idx_qvt_lang_verse');
    });

    // MED-4: personal_access_tokens last_used index
    Schema::table('personal_access_tokens', function (Blueprint $table) {
        $table->index(['last_used_at', 'tokenable_id'], 'idx_pat_last_used_tokenable');
    });

    // MED-5: seerah chronological composite
    Schema::table('seerah_events', function (Blueprint $table) {
        $table->index(['hijri_year', 'hijri_month', 'hijri_day', 'display_order'], 'idx_seerah_chronological');
    });

    // LOW-1: hadith_books collection+number composite
    Schema::table('hadith_books', function (Blueprint $table) {
        $table->index(['collection_id', 'book_number'], 'idx_hadith_books_collection_number');
    });

    // LOW-5: audit_logs user+date composite
    Schema::table('audit_logs', function (Blueprint $table) {
        $table->index(['user_id', 'created_at'], 'idx_audit_logs_user_date');
    });
}
```

### Migration 2: article_locale FULLTEXT Fix

```php
// database/migrations/2026_03_17_000002_extend_article_locale_fulltext.php

public function up(): void
{
    if (config('database.default') === 'mysql') {
        DB::statement('ALTER TABLE article_locale DROP INDEX ft_search');
        DB::statement('ALTER TABLE article_locale ADD FULLTEXT INDEX ft_search (title, excerpt, description)');
    }
}
```

---

## CACHE ARCHITECTURE RECOMMENDATION

The current setup uses the file cache driver with `Cache::remember()` keys scattered across repositories and services. This works for simple key-based gets, but is incompatible with `Cache::tags()` (used in BaseRepository). For production, the recommended architecture is:

1. **Redis as cache driver** — Set `CACHE_STORE=redis` and configure `REDIS_HOST`. This unblocks CRIT-1 and enables tag-based invalidation.
2. **Cache key strategy** — The existing pattern of prefixed keys (e.g., `hadith_book_{id}`, `hadiths_authentic`) is sound. Add a version prefix to enable bulk invalidation without tags: `v1:hadith_book_{id}`.
3. **Cache warming** — For Quran data (114 surahs, fixed), cache the `all()` result on application boot via a seeder or console command, not on first request.
4. **Invalidation on writes** — The `BaseRepository::clearCache()` -> `Cache::tags()->flush()` pattern is architecturally correct but requires Redis. With the current file driver, only explicit `Cache::forget($key)` calls work.

---

*End of audit. All file paths reference the Laravel backend at `/Volumes/Data/projects/the-last-massenger/the-last-messenger-be/`.*
