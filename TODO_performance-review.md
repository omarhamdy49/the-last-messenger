# Performance Review — FCP 2.0s / Speed Index 4.7s (mobile)

**Reviewer verdict:** The slow FCP/SI is **not** caused by database queries or the
page render. I measured every public page's server response time against live
data and the server is fast both warm and cold. The query layer already follows
the column-selection / eager-loading rules in CLAUDE.md §4.2–4.3. The FCP/SI
budget is spent on the **network path** (PSI's throttled mobile profile + the
Cloudflare→OVH-France origin round-trips) and the **render-blocking CSS** — not
on the backend.

> Don't optimize the wrong thing. The data below is why.

---

## 1. Measured per-page server response time (TTFB), live data

Measured on the server (localhost, so no client-network noise), `curl` →
nginx → php-fpm, full Blade render. Two states:

| Page | Warm (cache hit) | Cold (cache cleared) |
|------|-----------------:|---------------------:|
| `/` (home)        | 45 ms | 102 ms |
| `/seerah`         | 41 ms | 33 ms |
| `/seerah/events`  | 34 ms | 42 ms |
| `/hadith`         | 27 ms | 35 ms |
| `/hadith/explorer`| 39 ms | 27 ms |
| `/quran`          | 44 ms | 29 ms |
| `/sahaba`         | 38 ms | 39 ms |
| `/genealogy`      | 29 ms | 33 ms |
| `/articles`       | 42 ms | 40 ms |
| `/media`          | 48 ms | 49 ms |
| `/topics`         | 31 ms | 38 ms |
| `/faq`            | 27 ms | 34 ms |
| `/search?q=…`     | 56 ms | 47 ms |

**Every page renders in ≤ 102 ms server-side, even on a full cache miss.** The
~250 ms TTFB seen from a laptop is the laptop→France network leg, not the server.

### Why the homepage specifically does ~0 queries
`HomeController::index` wraps every data source in `Cache::remember`
(featured sahaba 30 min, daily verse/hadith 24 h, module stats 1 h, cards/chips
forever-per-locale). Steady-state homepage = cache reads only. Its 45 ms is
**Blade render + framework boot**, not SQL.

---

## 2. Where FCP 2.0s / SI 4.7s actually comes from

Server time is ~45 ms, so the remaining ~1.95 s of FCP is the critical-path
network under PSI's emulated **slow-4G (≈150 ms RTT, 1.6 Mbps)**:

1. Connection setup to Cloudflare (DNS+TCP+TLS ≈ 3 RTT).
2. HTML request proxied Cloudflare → **OVH origin in France** → back (real
   geographic latency, not throttled but additive).
3. **Render-blocking `app.css`** (29 KB gz) = one more RTT + throttled transfer
   before first paint.
4. Speed Index then tracks how fast the viewport fills — already fixed by the
   JS/font/image/animation work (SI 4.7s→2.7s, score 94→96).

None of these are query/render-time on the server.

---

## 3. Query / column / eager-vs-lazy review (what you asked for)

I scanned every public-facing repository (`app/Domain/*/Repositories`,
`app/Repositories`) for the §4.2–4.3 anti-patterns. **The layer is compliant:**

- **No unconstrained eager loads.** Relations are loaded with explicit columns,
  e.g. `ArticleRepository::getAllWithFilters` (the `/articles` index):
  ```php
  ->with([
    'locales:id,article_id,locale,title,slug,is_ready',
    'writer:id,name,email',
    'category:id,slug,type,status',
    'category.translations:id,category_id,locale,name',
    'categories:id,slug,type,status',
    'categories.translations:id,category_id,locale,name',
  ])->paginate($perPage);
  ```
  This is exactly right — column-pinned eager loads, no N+1, no `SELECT *`.
- **No repository missing `->select()`** on its `get()/paginate()/first()` paths.
- The `::all()` / `->all()` hits in the scan are PHP **Collection** conversions
  (`$rows->all()`), not `Model::all()` table scans.

### Eager vs lazy — the right call is already made
For these read paths the relations rendered per row (translations, writer,
category…) are **eager-loaded** — correct, because lazy loading them inside the
Blade loop is exactly what creates N+1. **Nothing here should be switched to
lazy.** (The "lazy load" that helps your scores is the *frontend* image
`loading="lazy"`, already applied to below-the-fold images.)

### Minor hygiene items (do NOT affect FCP/SI; fix for scale/consistency)
- [ ] **PERF-1** `SahabiRepository::getFeatured` — parent query has no
  `->select([...])` (SELECT * on `sahabis`) and `listingRelations()` loads
  `'article'` with no column closure. It's cached 30 min so runtime cost is
  ~nil, but add an explicit select + `'article:id,sahabi_id,slug,…'` to match the
  rest of the codebase. (file: `app/Domain/Sahaba/Repositories/SahabiRepository.php`)
- [ ] **PERF-2** Audit the other `->with($this->listingRelations())` helpers the
  same way (Seerah/Media) — ensure every relation in the helper pins columns.

---

## 4. Actual prioritized actions to push FCP/SI further (toward 98+)

Ordered by impact on the metrics that are actually gating the score.

- [ ] **PERF-A (highest) — Edge-cache the HTML for guests.** The biggest lever is
  removing the Cloudflare→France origin round-trip from FCP. Enable Cloudflare
  **APO** (or a "Cache Everything" rule for anonymous GETs) so HTML is served
  from the nearest edge. **Caveat:** the page embeds a per-session CSRF meta
  token — cache only for cookie-less visitors and/or move CSRF to a
  JS-hydrated/cookie source so a cached token is never shared. Expected: TTFB at
  the PSI location drops from "France round-trip" to edge (~tens of ms) → FCP/LCP
  down several hundred ms.
- [ ] **PERF-B — Inline critical CSS, defer the rest.** `app.css` (29 KB gz) is
  the one render-blocking request left. Inline above-the-fold CSS and load the
  full sheet with `media="print"`/`onload` swap (or a build-time critical-CSS
  step). Removes ~1 RTT + transfer from first paint.
- [ ] **PERF-C — Trim homepage HTML.** The document is ~131 KB raw (large DOM:
  full 5-language nav duplicated for desktop+mobile, many sections). Fewer nodes
  = faster parse + paint. Lazy-render the mobile menu / language lists with
  Alpine `x-if` instead of shipping them in initial HTML.
- [ ] **PERF-D — Cloudflare transport wins:** ensure Brotli on, Tiered Cache on,
  and (if on a paid plan) Polish/Mirage for automatic image optimization.
- [ ] **PERF-E — Keep the origin warm** (already true): the cache strategy is
  good; just confirm scheduled cache warmers if any TTL gaps cause cold spikes.
- [ ] **PERF-F (done)** — JS bundle 579→102 KB, self-hosted fonts, WebP+lazy
  images, transform-only hero entrance. These took mobile 94→96, SI 4.7→2.7.

---

## TL;DR
Backend is healthy and fast (≤102 ms cold). Queries/columns/eager-loading are
already done right (no fixes needed beyond two cosmetic items). To move FCP/SI
further, work the **network + render path** — edge-cache HTML, inline critical
CSS, trim HTML — not the database.
