# Articles Module — Defects To Fix

Source: full E2E audit + deep field test + public-design pass (June 2026).
Full evidence: `docs/ARTICLES-E2E-TEST-CASES.md`. Severities: **P0** blocking/security, **P1** functional, **P2** polish.
Check the box when fixed; each item is self-contained and verifiable.

---

## P0 — blocking / security (fix first)

- [ ] **D-01 · Arabic-first create is silently un-submittable.**
  Every language's title input is `required`, but only `title.ar` is required server-side, so a valid Arabic-only article cannot be submitted (hidden EN/FR/TR/UR inputs fail `checkValidity()`; browser blocks submit with no error).
  **Where:** `resources/views/admin/articles/_form-wizard.blade.php:548` (`<input … required>` + `*` inside `@foreach($locales)`).
  **Fix:** render `required` and the `*` only when `$locale === 'ar'` (mirror `StoreArticleRequest`). Check the content block for the same pattern.
  **Verify:** create with only Arabic title+content → 201.

- [ ] **D-02 · Trash page returns 404 (feature broken).**
  `GET /su/portal/v1/admin/articles/trash` → 404 (`No query results for model [Article] trash`) because `articles/{article}` is registered before the literal `trash` route. Restore/force-delete are unreachable as a result.
  **Where:** `routes/admin/blog.php:44` (trash route is after `Route::resource('articles')` on line 28).
  **Fix:** move `articles/trash` into the `Route::prefix('articles')` group (lines 12–25), before the resource. Add routes for `restore()` / `forceDelete()` while there.
  **Verify:** المحذوفات opens; soft-deleted article appears and restores.

- [ ] **D-03 · Public API serves draft articles (content leak).**
  `GET /api/v1/articles` returns `status=draft` rows. The public "published" filter ignores the `status` column and gates only on per-locale `is_ready`; a draft with a ready locale is public, and a published article with no ready locale is hidden.
  **Where:** `app/Repositories/ArticleRepository.php` `applyFilters()` (~L684) + `getArticlesByStatus()` (status ⇒ `whereHas('locales', is_ready=true)`).
  **Fix:** require `status='published'` AND a ready locale for public reads (or make the admin badge reflect `is_ready` so the two signals agree). Pick one source of truth.
  **Verify:** draft id no longer in `/api/v1/articles`; published+ready still present.

- [ ] **D-14 · Scheduling & "pending" are unsavable.**
  The status `<select>` offers `pending` + `scheduled` (with a `scheduled_at` picker), but `ArticleStatus` enum has only `draft/published/archived`, so both → **422**. The scheduled-publish feature is dead; `published_at` is never set.
  **Where:** `app/Domain/Blog/Enums/ArticleStatus.php` (3 cases) vs `_form-wizard.blade.php:302,304`. The DB column enum already allows 5 values.
  **Fix:** add `PENDING`/`SCHEDULED` cases; set `published_at` on publish and honor `scheduled_at`.
  **Verify:** save status=scheduled with a future `scheduled_at` → 200.

---

## P1 — functional

- [ ] **D-08 · Four registered admin routes 500 (dead handlers).**
  `GET /articles/api`, `GET /articles/stats`, `POST /articles/bulk-archive`, `POST /articles/bulk-action` point to controller methods that don't exist → **500**.
  **Where:** `routes/admin/blog.php:14,15,18,21`.
  **Fix:** implement the methods or remove the routes (`GET /articles/search` works and stays).
  **Verify:** none of the four return 500.

- [ ] **D-15 · Gallery is never persisted.**
  `gallery[]` is validated (`exists:media,id`) but absent from `CreateArticleData`/`UpdateArticleData` and both Actions → silently dropped; `articles_gallery` stays empty. (That table stores `image` path + caption, not `media_id`, so the form payload wouldn't map even if handled.)
  **Where:** `app/Domain/Blog/Actions/CreateArticleAction.php` / `UpdateArticleAction.php` (no gallery handling); DTOs.
  **Fix:** persist gallery in the Action (map selected media → `articles_gallery` rows), or remove the non-functional Gallery tab.
  **Verify:** create with gallery → rows in `articles_gallery`.

- [ ] **D-16 · SEO title/description have no public effect.**
  `seo_title`/`seo_description` persist to `article_locale.seo_metadata` but the public reader ignores them — `<title>` uses the article title and `meta description`/`og:description` use the excerpt.
  **Where:** `app/Http/Controllers/Site/ArticleController.php::mapDetail` (doesn't read `seo_metadata`); `resources/views/public/articles/show.blade.php` (`@section('title', $article['title'])`, meta from excerpt).
  **Fix:** surface `seoTitle`/`seoDescription` from `seo_metadata` and use them in the head (fallback to title/excerpt).
  **Verify:** page `<title>`/meta reflect the SEO fields when set.

- [ ] **D-04 · Featured `limit` ignored + cache key collision + no published filter.**
  `/api/v1/articles/featured?limit=N` returns a fixed set regardless of `limit` (cache key omits limit), and the query filters only `is_featured` + ready locale, not `status=published` (docblock says "published only").
  **Where:** `app/Repositories/ArticleRepository.php::getFeaturedArticles` (~L132, `Cache::remember('featured_articles', …)`).
  **Fix:** include `$limit` in the cache key (`"featured_articles:{$limit}"`); add the published/ready gate (ties to D-03).
  **Verify:** `featured?limit=2` returns ≤2.

- [ ] **D-06 · Public `ArticleResource` is not locale-resolved.**
  Show/index expose a raw `locales[]` array but no top-level locale-resolved `title`/`excerpt`/`content` and no `status`, despite honoring `Accept-Language`. Every client must re-implement locale resolution + Arabic fallback.
  **Where:** `app/Http/Resources/ArticleResource.php`.
  **Fix:** add resolved `title`/`excerpt`/`content` (requested locale → `ar` fallback); keep `locales[]` for multi-language clients.
  **Verify:** `/api/v1/articles/{slug}` returns a usable `title` for `Accept-Language: ar`.

- [ ] **D-07 · Feature/Pin toggles have no list UI.**
  `toggle-feature`/`toggle-pin` endpoints work (200, flag flips) but the list مميز/مثبت chips are read-only and the row menu has no toggle — settable only via the edit form.
  **Where:** admin article list (`resources/views/admin/articles/index.blade.php`) chips/row-menu.
  **Fix:** wire the chips (or add row-menu items) to the existing toggle routes.
  **Verify:** clicking the chip flips the flag and persists.

---

## P2 — polish / debt

- [ ] **D-05 · Duplicate `placeholders` array key breaks filter i18n.**
  `resources/lang/ar/articles.php` defines `placeholders` twice (L199 & L462); the second silently clobbers the first, so the advanced-filter inputs render raw keys (`articles.placeholders.search_category`, `search_writer`, …). Check en/fr/tr/ur for the same duplicate.
  **Fix:** merge the two `placeholders` blocks into one in each affected lang file.
  **Verify:** filter placeholders render translated text.

- [ ] **D-10 · Duplicate UX.** Duplicate keeps the identical title (no "نسخة/Copy" suffix), is created `published`, and stats cards don't refresh afterward.
  **Where:** `ArticleController::duplicate()`.
  **Fix:** append a copy suffix, create as `draft`, refresh stats.

- [ ] **D-11 · Admin toasts hardcoded English.** "Article created/updated/deleted successfully!" despite Arabic default.
  **Where:** `ArticleController` store/update/destroy JSON responses.
  **Fix:** route messages through `__()`.

- [ ] **D-09 · Delete confirm copy is wrong.** Dialog says "لا يمكن التراجع عن هذا الإجراء" but delete is a recoverable soft delete.
  **Fix:** reword to reflect Trash recoverability.

- [ ] **D-18 · Image fallback path mismatch (NEW, this session).** `resolveImage()` falls back to `asset('images/'.$fallback)` but the files live in `public/images/site/` → broken hero/card image when an article has no thumbnail.
  **Where:** `app/Http/Controllers/Site/ArticleController.php::resolveImage` (~L235).
  **Fix:** `asset('images/site/'.$fallback)` (or move the files).
  **Verify:** a thumbless article shows a real fallback image.

- [ ] **D-13 · Dual featured columns.** Legacy `featured` (unused) coexists with `is_featured` (active). Risk of divergence.
  **Fix:** drop/retire the legacy `featured` column + index.
  *(Note: the admin list correctly binds `is_featured` — no live mismatch today.)*

- [ ] **D-12 · Native required bubble locale.** HTML5 "Please fill out this field." shows in browser locale. Cosmetic; superseded by fixing D-01.

---

## Resolved this session (design pass)

- [x] **D-17 (partial) · references & statistics now render publicly.** `Site\ArticleController::mapDetail` now passes normalized `references` + `statistics`, and the redesigned `public/articles/show.blade.php` renders them as the **Sources** and **Quick Stats** sections.
  **Still open:** `media_items` (videos/episodes) are stored + in the API but not yet rendered on the public reader — fold into a future media-embed task.

---

## Not covered (recommend PHPUnit feature tests — CLAUDE.md §7)
- 401 (guest) / 403 (role without `view|create|edit|delete articles`) on every admin endpoint.
- `scheduled`/`archived` status behavior (after D-14), slug-uniqueness 422, preview endpoint, series/gallery ordering.
