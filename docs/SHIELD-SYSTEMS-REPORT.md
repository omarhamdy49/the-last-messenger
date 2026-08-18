# 🛡️ Shield-Systems Audit — Full Report

**Repo:** The Last Messenger (Laravel 13 / PHP 8.5 / MySQL, DDD)
**Date:** 2026-08-18
**Command:** `/shield-systems` (whole-codebase, 3 active lanes: Security · Performance · Database) + Snyk dependency scan
**Status:** All 11 findings fixed · dependencies remediated · nothing committed/pushed/migrated

---

## 1. Executive summary

11 verified findings across three lanes (DB lane checked against the **live database**; Security/Performance were static reads). No Critical in-code; three High-severity issues, all now fixed. Separately, a **Critical dependency CVE** (`laravel/socialite`, CVSS 9.2) was patched via an upstream backport.

| Lane | High | Medium | Low |
|------|------|--------|-----|
| Security | 2 | — | 1 |
| Database | 1 | 3 | 1 |
| Performance | — | 2 | 1 |

---

## 2. Findings & outcomes (all fixed)

| # | Lane | Sev | Location | Issue | Fix applied | Verified |
|---|------|-----|----------|-------|-------------|----------|
| 1 | Sec | High | `Site/SocialAuthController.php` | Verified-email gate was Google-only; Facebook enabled → auto-link to existing account by email → **account takeover** | Single `providerVerifiedEmail()` guard at both trust points; non-attesting providers refused auto-link | `php -l`, pint, path trace |
| 2 | Sec | High | `UpdateArticleRequest.php` + rich-HTML `{!! !!}` sinks | Stored XSS: rich HTML saved with only `trim()`, rendered raw | **Live paths already sanitized** (uncommitted `RichTextSanitizer` + `SanitizesRichText`, HTMLPurifier); hardened 2 dead duplicate request classes | tinker: `<script>` stripped, embed blots survive |
| 3 | DB | High | `quran_verses.surah_id` FK | `ON DELETE CASCADE` → deleting a surah wipes all verses→translations/tafsirs/words | New migration → `RESTRICT`; no admin delete route exists to guard | `migrate --pretend`, `SHOW INDEX` |
| 4 | DB | Med | `article_locale (locale,slug)` | No unique behind SELECT-then-insert | New migration: dedup + `unique(locale,slug)` | `migrate --pretend` |
| 5 | DB | Med | `quran_verse_tafsirs` | Nullable `resource_id` in unique → duplicate tafsirs | New migration: dedup + `unique(verse_id,lang)` | `migrate --pretend` |
| 6 | DB | Med | `quran_verse_translations` | Same nullable-`resource_id` dup gap | New migration: dedup + `unique(verse_id,lang)` | `migrate --pretend` |
| 7 | Perf | Med | 8 Quran read paths + `QuranService.php` | `load('translations')` pulls every locale, renders one | Shared `Verse::readerTranslationsEagerLoad($locale)`, column-constrained; output byte-identical | 132 Quran tests pass |
| 8 | Perf | Med | `Admin/Blog/CategoryController.php` | Bulk-delete ~6N queries | Set-based: `whereIn`+`withCount`, one delete → **2 queries** | 32 Category tests pass |
| 9 | Sec | Low | 4× `*DashboardStatsProvider.php` | `catch(\Throwable)` zeroes dashboard, no `report()` | Added `report($e)` to all four (matches fixed siblings) | pint |
| 10 | DB | Low | legacy `people`/`sahaba`/`relationships`/`sahabi_translations` | Dead pre-rebuild tables | **NOT dropped** — 3 of 4 still have live readers; flagged for follow-up | grep |
| 11 | Perf | Low | `Site/MediaController.php` | Featured reciters fetched unbounded, trimmed in PHP | `limit(30)` + column-constrained translations; name-fold kept in PHP | pint |

**Severity note:** #2 and #3 were elevated Medium→High during consolidation (XSS reaches super_admin browser; surah-delete loss is unrecoverable).

---

## 3. ⚠️ Migrations — written, NOT applied (read before `migrate`)

Four new migration files created and `--pretend`-validated; **not run**. Two of them **delete real rows** (live DB checked):

- `quran_verse_translations`: **24,944 duplicate groups**
- `quran_verse_tafsirs`: **10,595 duplicate groups**
- `article_locale`: 0 (no-op)

Dedup keeps the **lowest `id`** per `(verse_id, lang)` — an arbitrary rule. **Inspect the duplicates and decide the keep-rule before applying to prod.**

```
database/migrations/2026_08_18_114346_restrict_delete_on_quran_verses_surah_id_foreign.php
database/migrations/2026_08_18_114347_add_unique_locale_slug_to_article_locale_table.php
database/migrations/2026_08_18_114348_add_unique_verse_id_lang_to_quran_verse_tafsirs_table.php
database/migrations/2026_08_18_114349_add_unique_verse_id_lang_to_quran_verse_translations_table.php
```

---

## 4. Dependency remediation (Snyk)

Scanned PHP first, then npm. `composer audit` clean; full test suite **955 passed, 0 failed**.

| Package | Was | Now | Result |
|---------|-----|-----|--------|
| `laravel/socialite` | 5.29.0 | 5.29.0 **+ patch** | ✅ **Critical CVE-2026-73683 (Replay Attack, CVSS 9.2)** fixed via upstream backport (PR #789 / commit `caf714f`) through `composer-patches`; documented in `.snyk`. Snyk re-test: no vulnerable paths |
| `symfony/yaml` | 7.4.1 | v8.1.2 | ✅ High+2 Med cleared; deptrac runs clean |
| `quill` (npm) | 2.0.2 | ^2.0.3 | 🟡 Still flagged (SNYK-JS-QUILL-14927397, no upstream fix); server-side `RichTextSanitizer` mitigates |

**SAST (`snyk code test`):** ⛔ blocked — Snyk Code not enabled on the org (`403 SNYK-CODE-0005`). Enable Snyk Code to scan source, not just dependencies.

**Snyk dashboard:** two monitored projects created (`composer.lock` + `package.json`) for continuous CVE alerts.

### socialite fix — how it re-applies & when to remove
- Patch: `patches/laravel-socialite-CVE-2026-73683.patch`, applied by `cweagans/composer-patches` on every `composer install` (`composer-exit-on-patch-failure: true`).
- `.snyk` ignore documents the backport, expires **2026-11-16**.
- **When Laravel tags a release with PR #789:** bump `laravel/socialite`, delete the patch entry + file, drop the `.snyk` ignore.

---

## 5. Deferred / follow-ups (need your decision)

- **DB-05 discrepancy:** `sahaba`, `sahabi_translations`, `relationships` still read by `UpdateSahabiRequest.php:25` + portal export/verify commands, despite CLAUDE.md saying Sahaba is gone. Migrate readers onto `Muslim` before dropping.
- **Duplicate-row keep-rule:** decide before running the two dedup migrations.
- **SEC-02 leftover:** the two dead root-namespace `Admin\{Store,Update}ArticleRequest` classes can just be deleted (nothing routes to them).
- **PERF-02 DRY debt:** `CategoryController` duplicates 5 cache-key `forget` literals; cleaner is a `CategoryRepository::bulkDelete()` on the service.
- **SEC-01 UX:** optional explicit "link account" confirmation flow for Facebook users with an existing password account.
- **npm `quill`:** watch for a fixed release.
- **Enable Snyk Code** for source-level SAST.

---

## 6. Commands to run

```bash
# 1. Full test suite (already green at time of report)
php artisan test

# 2. Preview the migrations' SQL (does not apply)
php artisan migrate --pretend

# 3. Only AFTER reviewing the dedup migrations:
php artisan migrate

# 4. Re-verify dependencies anytime
snyk test --file=composer.lock
snyk test --file=package-lock.json
```

---

## 7. Files changed (all uncommitted — nothing pushed)

**Code fixes:** `SocialAuthController.php`, `Admin/StoreArticleRequest.php`, `Admin/UpdateArticleRequest.php`, 8 Quran handlers + `Verse.php` + `QuranService.php`, `Admin/Blog/CategoryController.php`, 4× `*DashboardStatsProvider.php`, `Site/MediaController.php`
**Migrations (new):** 4 files listed in §3
**Dependencies:** `composer.json`, `composer.lock`, `package.json`, `package-lock.json`
**New security artifacts:** `patches/laravel-socialite-CVE-2026-73683.patch`, `.snyk`

---

## 8. Tooling changes made this session

- **`/shield-systems` is now a single global 7-lane command** (`~/.claude/commands/shield-systems.md`): Security · Performance · Database · Architecture · Clean Code & Principles · API Design · Naming Conventions. The separate `/shield-architecture` was merged in and deleted; the project-scoped copy was removed.
- **Snyk step baked into the command:** consent-gated, PHP-manifest-first then npm, avoids `--all-projects` noise, notes SAST org-gating — all lessons from this run.
