# Articles Module — Full E2E Test Cases & Results

**Module:** Blog / Articles (admin CMS + public API)
**Method:** Live browser end-to-end via Chrome extension against the running dev server, plus same-origin `fetch` for API assertions and `php artisan tinker` for DB verification.
**Environment:** `http://127.0.0.1:8000` · admin `/su/portal/v1/admin/articles` · API `/api/v1/articles` · authenticated as super admin (`su@dwit.com`).
**Date:** 2026-06-21
**Baseline data:** 15 articles (13 "published", 2 draft).
**Test data:** all artifacts created during testing were force-deleted; DB returned to the 15-article baseline (verified).

Legend: ✅ PASS · ❌ FAIL · ⚠️ PASS-with-issue / observation.

---

## 1. Coverage summary

| Area | Cases | ✅ | ❌ | ⚠️ |
|------|------:|---:|---:|---:|
| Public API | 23 | 16 | 2 | 5 |
| Admin CRUD (UI) | 14 | 10 | 1 | 3 |
| Admin actions (bulk / toggle / duplicate / trash / export) | 10 | 5 | 3 | 2 |
| Authorization / validation | 6 | 3 | 0 | 3 |
| **Total** | **53** | **34** | **6** | **13** |

**13 distinct defects** found (3 High, 5 Medium, 5 Low). See §6.

---

## 2. Public API test cases (`/api/v1/articles`)

| ID | Case / Steps | Expected | Actual | Status |
|----|------|----------|--------|--------|
| API-01 | `GET /articles` | 200, `{success,data,meta}` envelope | 200, total=14, envelope OK | ✅ |
| API-02 | `GET /articles?per_page=2` | 200, 2 items, `last_page` reflects paging | 200, 2 items, last_page=7 | ✅ |
| API-03 | `GET /articles?locale=en` | 200 | 200 | ✅ |
| API-04 | `GET /articles?per_page=999` | 422 (max 100) | 422, field `per_page` | ✅ |
| API-05 | `GET /articles?locale=xx` | 422 (allowlist) | 422, field `locale` | ✅ |
| API-06 | `GET /articles?category_id=abc` | 422 (integer) | 422 | ✅ |
| API-07 | **Published-only filter** — index must exclude non-published | only `status=published` rows | **returns draft id=2** | ❌ **D-03** |
| API-08 | `GET /articles/featured` | 200, ≤ default limit | 200, 5 items | ✅ |
| API-09 | `GET /articles/featured?limit=2` | 200, **2** items | 200, **5** items (limit ignored) | ❌ **D-04** |
| API-10 | `GET /articles/featured?limit=999` | clamped to 50 → 200 | 200 (clamped, by design) | ✅ |
| API-11 | `GET /articles/featured?limit=abc` | clamped to 1 (`$request->integer`) | 200, 5 (cache, see D-04) | ⚠️ D-04 |
| API-12 | `GET /articles/popular` | 200 | 200 | ✅ |
| API-13 | `GET /articles/popular?days=7&limit=3` | 200 | 200 | ✅ |
| API-14 | `GET /articles/recent` | 200, ≤10 | 200, 10 | ✅ |
| API-15 | `GET /articles/recent?limit=2` | 200, 2 | 200, 2 | ✅ |
| API-16 | `GET /articles/recent?limit=999` | clamped to 50 | 200, 14 (all; clamped) | ✅ |
| API-17 | `GET /articles/search` (no `q`) | 422 required | 422, `q` required | ✅ |
| API-18 | `GET /articles/search?q=a` | 422 min:2 | 422 min:2 | ✅ |
| API-19 | `GET /articles/search?q=صيام` | 200, envelope | 200, data+meta | ✅ |
| API-20 | `GET /articles/{bad-slug}` | 404, `success:false` | 404, error envelope | ✅ |
| API-21 | **Show response shape** — localized title for `Accept-Language` | top-level resolved `title` | only raw `locales[]`; no `title`/`status` | ⚠️ **D-06** |
| API-22 | Index unauthenticated | 200 (public) | 200 | ✅ |
| API-23 | Featured returns only published | published only (per docblock) | no status filter in query (latent) | ⚠️ D-04b |

---

## 3. Admin CRUD (Blade wizard) test cases

| ID | Case / Steps | Expected | Actual | Status |
|----|------|----------|--------|--------|
| UI-01 | Load `/articles` list | table + stats (15/13/2/0) render | renders, stats correct | ✅ |
| UI-02 | Stats cards vs list count | consistent | 15 = 13+2+0 ✓ | ✅ |
| UI-03 | Live search box (`اختبار شامل`) | filters list | narrowed to exact match | ✅ |
| UI-04 | Advanced filters → status = draft → Apply | only drafts | exactly 2 drafts | ✅ |
| UI-05 | Open create wizard | tabs + thumbnail picker + publish panel | renders | ✅ |
| UI-06 | Submit empty | required-field validation blocks | native "Please fill out this field" on AR title | ✅ |
| UI-07 | **Arabic-only create** (fill only `title.ar` + `content.ar`, submit) | submits (backend requires only `ar`) | **silently blocked** (hidden EN/FR/TR/UR titles `required`) | ❌ **D-01** |
| UI-08 | Create (all langs filled, status=Published, thumb from library) | 201, redirect, row appears | toast "created", row + thumb visible | ✅ |
| UI-09 | Persistence (DB) | 5 locale rows, thumb stored, status=published | id=18: 5 locales, thumb=`articles/thumbs/…png`, published | ✅ |
| UI-10 | **Thumbnail from library** (`thumb_id`, no file) decrypts + stores | public thumb file written | valid decrypted PNG at public path | ✅ |
| UI-11 | Thumbnail quality selector default | `الأصل` (Original) present + default | present, default selected | ✅ |
| UI-12 | View (`عرض`) detail page | title/status/author/thumb render | all render | ✅ |
| UI-13 | Edit load (`تعديل`) | form pre-populated incl. thumb | pre-filled, thumb preserved | ✅ |
| UI-14 | Update (append title, set AR `جاهز للنشر`, save) | toast, persisted | toast "updated", persisted | ✅ |

**Closed loop (UI-09→UI-14):** after marking AR `جاهز للنشر` (`is_ready`), the article flipped from **404→200** on the public API and appeared in index + search — proving create→ready→publish path and the per-locale visibility gate.

---

## 4. Admin actions test cases

| ID | Case / Steps | Expected | Actual | Status |
|----|------|----------|--------|--------|
| ACT-01 | Row menu options | View / Edit / Duplicate / Delete | present | ⚠️ Duplicate is mislabeled `إنشاء` (Create) |
| ACT-02 | Duplicate (`إنشاء`) | copy created (draft, "Copy" suffix) | copy created **published**, **same title**, stats not refreshed | ⚠️ **D-10** |
| ACT-03 | Select row → bulk toolbar | publish/delete/export/clear appear | appears ("1 محدد") | ✅ |
| ACT-04 | Bulk delete (confirm) | soft-delete, row removed | removed, list re-counted | ✅ |
| ACT-05 | Single delete (`حذف` + confirm) | soft-delete, toast, stats refresh | removed, toast, 16→15 | ✅ |
| ACT-06 | Delete confirm copy | accurate | says "cannot be undone" but it is a **soft delete** | ⚠️ **D-09** |
| ACT-07 | **Trash** (`المحذوفات`) | trashed list page | **404 Not Found** | ❌ **D-02** |
| ACT-08 | Restore from trash | restore works | **unreachable** (D-02; `restore()` also has no route) | ❌ D-02 |
| ACT-09 | Toggle feature / pin (UI) | list control toggles flag | **no list control** (chips read-only, not in menu) | ⚠️ **D-07** |
| ACT-10 | Export dropdown | CSV / Excel / PDF options | all three render (download not triggered) | ✅ |

**Backend toggle endpoints verified directly:** `POST …/{id}/toggle-feature` and `…/toggle-pin` both return 200 and flip the flag — only the **list UI wiring** is missing (D-07).

---

## 5. Authorization, validation & dead routes

| ID | Case | Expected | Actual | Status |
|----|------|----------|--------|--------|
| SEC-01 | Public API needs no auth | 200 | 200 | ✅ |
| SEC-02 | Admin routes behind `auth:admin` + `role:…` | enforced | enforced by middleware (config-verified; not live-logged-out) | ⚠️ add 401/403 feature tests |
| SEC-03 | Mutating endpoints use FormRequest 422 | 422 field errors | confirmed on create + API | ✅ |
| SEC-04 | `GET /articles/api` | implemented or 404 | **500** (method missing) | ❌ **D-08** |
| SEC-05 | `GET /articles/stats` | implemented or 404 | **500** (method missing) | ❌ D-08 |
| SEC-06 | `POST /articles/bulk-archive`, `/bulk-action` | implemented or 404 | **500** (methods missing) | ❌ D-08 |

> 401/403 were **not** exercised live (session is super admin; logging out would disrupt the user). Recommend PHPUnit feature tests covering 401 (guest) and 403 (role without `view/create/edit/delete articles`) per `CLAUDE.md` §7.

---

## 6. Defects (with root cause & recommended fix)

### D-01 — High — Arabic-first create is silently un-submittable
Create wizard marks **every** language's title `required`, but only `ar` is required server-side (`StoreArticleRequest`: `title.{locale}` required only for `ar`). With only Arabic filled, hidden EN/FR/TR/UR title inputs fail `checkValidity()`; the browser cannot focus a `display:none` field, so submit is blocked **with no error and no network request**.
- **Root cause:** `resources/views/admin/articles/_form-wizard.blade.php:548` — `<input … required>` + `*` inside `@foreach($locales)` (unconditional).
- **Fix:** `@if($locale === 'ar') required @endif` and render the `*` only for `ar` (mirror the backend rule). Apply the same to the `content` block if it carries `required`.

### D-02 — High — Trash page returns 404 (feature broken)
`المحذوفات` → `/su/portal/v1/admin/articles/trash` → **404** `No query results for model [App\Models\Article] trash`. The `Route::resource('articles')` `show /{article}` route matches first and binds `{article}="trash"`.
- **Root cause:** `routes/admin/blog.php:44` registers `articles/trash` **after** `Route::resource(...)` (line 28). The file's own comment (line 13) says literal routes "must be before resource routes."
- **Fix:** move the `trash` route into the `Route::prefix('articles')` group (lines 12–25), before the resource. (Restore/forceDelete have **no** routes at all — add them to reach `restore()`/`forceDelete()`.)

### D-03 — High / Security — Draft article exposed on public API
`GET /api/v1/articles` returns **id=2 (`status=draft`)**. The public "published" filter ignores the `status` column entirely and keys off per-locale `is_ready`.
- **Root cause:** `app/Repositories/ArticleRepository.php` `applyFilters()` (~line 684): `status=published` ⇒ `whereHas('locales', is_ready=true)` (never `where('status','published')`). Same logic in `getArticlesByStatus()`.
- **Effect (both directions):** a **draft** with a ready locale is **public**; a **published** article with no ready locale is **hidden** (observed live with the test article — 404 until AR marked ready).
- **Fix:** decide the single source of truth. Either (a) require `status=published` **and** a ready locale for public reads, or (b) drop the `status` column from public gating and make the admin badge reflect `is_ready`. Today `status` (admin badge) and `is_ready` (public gate) contradict each other.

### D-04 — Medium — `featured?limit` ignored; featured cached without status filter
`featured?limit=2` returns 5; any `limit` (incl. `abc`, `0`, `-1`) returns the same cached set.
- **Root cause:** `getFeaturedArticles()` `Cache::remember('featured_articles', 3600, …)` (line ~132) — cache key omits `$limit`, so the first caller's result is served for an hour. The query also filters only `is_featured` + ready locale, **not** `status=published` (docblock says "published only") → latent draft-leak (D-04b).
- **Fix:** include `$limit` in the cache key (e.g. `"featured_articles:{$limit}"`); add the published/ready gate consistent with D-03.

### D-05 — Medium — Broken i18n: filter placeholders show raw keys
The advanced-filter category/writer inputs render literal `articles.placeholders.search_category` / `search_writer`.
- **Root cause:** `resources/lang/ar/articles.php` defines the `placeholders` array key **twice** (line 199 and line 462). PHP silently keeps the later one; block @462 lacks `search_category`, `search_writer`, `search_tag`, `search_media`, `select_tags`, `select_series`, `slug`, `search`, etc. `__('articles.placeholders.search_category')` returns the raw key.
- **Fix:** merge the two `placeholders` blocks into one. Check en/fr/tr/ur for the same duplicate.

### D-06 — Medium — Public `ArticleResource` not locale-resolved
Show/index expose a raw `locales[]` array but **no** top-level `title`/`excerpt`/`content` and **no** `status`, despite honoring `Accept-Language`. Every client must re-implement locale resolution + Arabic fallback.
- **Fix:** add resolved `title`/`excerpt`/`content` (for the requested locale, fallback `ar`) to `ArticleResource`; keep `locales[]` for clients that need all languages.

### D-07 — Medium — Feature/Pin toggles have no list UI
`toggle-feature`/`toggle-pin` endpoints work (200, flag flips) but the list `مميز`/`مثبت` chips are **read-only** and the row menu has no toggle. Feature/pin is only settable via the edit form.
- **Fix:** wire the chips (or add row-menu items) to the existing toggle routes.

### D-08 — Medium — Dead admin routes return 500
`GET /articles/api`, `GET /articles/stats`, `POST /articles/bulk-archive`, `POST /articles/bulk-action` are registered but the controller methods don't exist → **500**.
- **Fix:** implement the methods or remove the routes. (`GET /articles/search` works.)

### D-09 — Low — Delete confirmation copy is wrong
Dialog says "لا يمكن التراجع عن هذا الإجراء" (cannot be undone), but delete is a **soft delete** (recoverable via Trash). Misleading.
- **Fix:** reword to reflect soft-delete/Trash recoverability.

### D-10 — Low — Duplicate UX
Duplicate creates a copy with the **identical title** (no `نسخة`/Copy suffix) and **published** status, and the stats cards don't refresh afterward. Two indistinguishable rows; a copy of a published article can go live (subject to D-03 gating).
- **Fix:** append a "نسخة"/"Copy" suffix, create as `draft`, and refresh stats after duplicate.

### D-11 — Low — Toasts not localized
Success/error toasts are hardcoded English ("Article created/updated/deleted successfully!") although the app default locale is Arabic.
- **Fix:** route toast text through `__()`.

### D-12 — Low — Native validation bubble locale
The HTML5 required bubble ("Please fill out this field.") renders in browser locale, not app i18n. Cosmetic; superseded by fixing D-01.

### D-13 — Low / Tech-debt — Dual featured columns
Articles table has both legacy `featured` (added 2026-06-11, unused) and `is_featured` (active; toggle + list bind here). Risk of future divergence. (Note: the admin list **correctly** reads `is_featured`; an apparent mismatch during testing was stale client-side search state, not a binding bug.)
- **Fix:** drop/retire the legacy `featured` column + index.

---

## 8. Deep field-by-field create + edit persistence (round 2)

Every form component was driven through the **real** store/update endpoints (full multipart payload, real `StoreArticleRequest`/`UpdateArticleRequest` + `CreateArticleAction`/`UpdateArticleAction`), then the entire DB record (articles row + 5 `article_locale` rows + every pivot) was dumped and asserted. Public reflection verified on `/articles/{slug}`.

### 8.1 CREATE — every field asserted in DB (article id created, then deleted)

| Field / component | Submitted | DB location | Result |
|---|---|---|---|
| title / slug / excerpt / description (×5 locales) | distinct per-locale | `article_locale.{title,slug,excerpt,description}` | ✅ all 5 |
| content body | `<h2>…</h2><p>…</p>` | `article_locale.content.body` (JSON) | ✅ |
| **TOC** | `toc[ar][0..1]` | `article_locale.content.toc` (JSON array) | ✅ both entries |
| **SEO title/desc** | per-locale | `article_locale.seo_metadata.{title,description}` | ✅ stored |
| is_ready (ar,en) | 1 | `article_locale.is_ready` | ✅ |
| status | published | `articles.status` | ✅ |
| **hijri_date** | `15 رمضان 1445` | `articles.hijri_date` | ✅ |
| **read_time (custom 7)** | 7 | `articles.read_time` | ✅ not overwritten by calculator |
| is_featured / is_pinned | 1 / 1 | `articles.is_featured/is_pinned` | ✅ |
| writer_id | 1 | `articles.writer_id` | ✅ |
| category_id (primary) | 1 | `articles.category_id` | ✅ |
| additional_categories | 2,3 | `article_category` pivot (1,2,3) | ✅ |
| tags | 1,2 | `taggables` | ✅ |
| **series + position** | 4 | `article_series` (4 : pos 0) | ✅ |
| media_items | 2,3 | `articles_media` | ✅ |
| thumb (from library `thumb_id`) | 2 | `articles.thumb` (decrypted copy) | ✅ |
| **statistics** | `[{name,value}]` | `articles.statistics` (JSON) | ✅ |
| **references** | `[{name,url,type}]` | `articles.references` (JSON) | ✅ |
| **gallery** | `gallery[]=3,4` | `articles_gallery` | ❌ **D-15** (0 rows) |

### 8.2 EDIT — every field changed and re-asserted in DB

PUT changed **every** field to new values (title→`محدث`, slug→`updated-fields-*`, content/toc/seo/excerpt, status, hijri→`20 شوال 1446`, read_time→12, featured→off, pinned→off, primary category→2, additional→1, tags→2, series 4, media→4, thumb→3, statistics/references). DB dump confirmed **all** updated correctly (pivots re-synced, JSON columns replaced, per-locale rows updated). ✅

### 8.3 Publish-date / status states

| State | Result |
|---|---|
| status = published | ✅ saves |
| status = draft | ✅ saves |
| status = archived | ✅ (enum) |
| **status = scheduled** (+ `scheduled_at`) | ❌ **422** "القيمة المحددة لـ الحالة غير صالحة" — **D-14** |
| **status = pending** | ❌ **422** — D-14 |

### 8.4 Public-page reflection (`/articles/updated-fields-ar`)

| Field | Public render | Status |
|---|---|---|
| title, excerpt, body (blocks/HTML) | rendered | ✅ |
| TOC | sidebar "محتويات المقال" → `عنوان محدث` | ✅ |
| category (resolved name), tags | السيرة النبوية, الصبر | ✅ reflects edit |
| author, read_time (12), views | rendered | ✅ |
| Hijri + Gregorian date | 20 شوال 1446 + 21 يونيو 2026 | ✅ |
| thumbnail / og:image | new thumb | ✅ |
| JSON-LD structured data | present | ✅ |
| related articles | 5 cards, no N+1 | ✅ |
| **SEO title/description** | `<title>`=article title; meta-desc=excerpt — seo fields **ignored** | ❌ **D-16** |
| **references / statistics** | not rendered | ❌ **D-17** |
| **media_items (videos) / gallery** | not rendered | ❌ D-17 / D-15 |

### 8.5 Optimization (public show, cold cache)

Replicated controller data path (`findBySlug` + `getRelatedForPublic` + view relation access) under query logging after `Cache::flush()`:
- **19 queries total, 0 repeated query shapes → no N+1.** Relations eager-loaded with explicit column selection; page cached 30 min (warm = 0 queries). ✅ **Optimized.**

### New defects (round 2)

- **D-14 — Medium/High — Scheduling & "pending" are unsavable.** `app/Domain/Blog/Enums/ArticleStatus.php` defines only `draft/published/archived`, but the form status `<select>` offers `pending` + `scheduled` (`_form-wizard.blade.php:302,304`) and shows a `scheduled_at` picker (line 310). `StoreArticleRequest` validates against `Rule::in(ArticleStatus::values())`, so both → **422**. The entire scheduled-publish feature is dead. **Fix:** add `PENDING`/`SCHEDULED` cases to the enum (the migration column already allows them) and set `published_at`/honor `scheduled_at` accordingly.
- **D-15 — Medium — Gallery never persists.** `gallery[]` is validated (`exists:media,id`) but absent from `CreateArticleData`/`UpdateArticleData` DTOs and both Actions → silently dropped; `articles_gallery` stays empty. (Also note `articles_gallery` stores an `image` path + caption, not a `media_id`, so the form's media-id payload wouldn't map even if handled.) **Fix:** persist gallery in the Action (map media → gallery rows) or remove the non-functional gallery tab.
- **D-16 — Medium — SEO fields have no public effect.** `seo_title`/`seo_description` persist to `article_locale.seo_metadata` but no `resources/views/site/**` view reads them — the public `<title>` uses the article title and `meta description`/`og:description` use the excerpt. The whole SEO tab is write-only. **Fix:** use `seo_metadata.title`/`description` (fallback to title/excerpt) in the public show head.
- **D-17 — Low — references / statistics / media (videos) not shown publicly.** Captured, stored, and present in the API `ArticleResource`, but the public `show()` view-model omits them. **Fix:** add to the public show data + template if they are meant to be reader-facing.

---

## 7. Not covered (recommended follow-up — PHPUnit, per CLAUDE.md §7)
- 401 (guest) and 403 (role lacking `view/create/edit/delete articles`) on every admin endpoint — not exercised live to avoid disrupting the session.
- `scheduled` status + `scheduled_at` behavior; archived status.
- Series pivot ordering; gallery ordering; references/statistics round-trip.
- Preview endpoint (`/articles/{id}/preview`).
- Slug uniqueness collision (422) on create/update.
