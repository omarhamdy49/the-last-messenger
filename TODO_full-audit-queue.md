# Full Audit Queue — public site + admin portal

Goal: a fully working public frontend and admin portal, with no missing, mocked, or
incorrect data.

Baseline: `2d5f334` on `fix/parity-p0` — 802 tests pass, deptrac 0 violations,
pint clean across app code.

## Who does what

Codex cannot drive a browser and its relay sandbox has no network, so the work splits:

| Item | Owner | Why |
|---|---|---|
| API correctness per module | **Codex** | Code + in-process HTTP tests |
| FAANG code-quality pass | **Codex** | Static review + refactor |
| Static/mock data removal | **Codex** | Grep + wire to real repositories |
| Home page component settings | **Codex** | Backend feature + admin CRUD |
| Seerah timeline correctness | **Codex** | Backend + Blade |
| Colour/token unification | **Codex** (applies) | Mechanical sweep, from my spec |
| Admin CRUD + relation testing | **Claude** | Chrome extension — Codex has no browser |
| Frontend data-reflection testing | **Claude** | Chrome extension |
| Hadith/Quran content accuracy | **Claude** | Needs network to sunnah.com / quran.com |
| UX premium + colour spec | **Claude** | Design skills load into my context, not Codex's |

## Status

| # | Task | Owner | Status | Commit |
|---|---|---|---|---|
| T1 | API smoke coverage over every `/api/v1/*` route | Codex | at-implementer | — |
| T4a | Replace §844's fabricated articles with real published ones | Codex | brief ready | — |
| T4b | Hero cards → DB via `placement` on `home_module_cards` | Codex | brief ready | — |
| T4c | `home_sections` framework: visibility + order + headings, all 8 | Codex | brief ready | — |
| T2 | Fix what T1 surfaces | Codex | queued | — |
| T3 | Static/mock sweep across the remaining public pages | Codex | queued | — |
| T5 | Seerah timeline correctness | Codex | queued | — |
| T6 | Colour/token unification | Codex | queued | — |
| C1 | Admin CRUD + relations via Chrome | Claude | queued | — |
| C2 | Frontend data reflection via Chrome | Claude | queued | — |
| C3 | Hadith/Quran content accuracy | Claude | queued | — |

## Needs your eyes

- **Two stale tests left failing at baseline.** `ArticlePublicPagesTest` asserts
  synchronous view counting and an always-rendered related-articles section. Both
  behaviours were deliberately changed (view counting moved to the async
  `registerView` endpoint so it survives the 1h page cache; the related section is
  hidden when empty). The tests describe the old design, not a regression — they
  need rewriting to match, which I have not done unasked.
- **3 stale deptrac skip entries** in `deptrac.yaml` reference violations that no
  longer exist. Harmless, but they make `composer deptrac` exit non-zero.
- **10 pint style issues** in `scripts/*.php` — one-off data-repair scripts, not app
  code. Pre-existing.

## Per-task notes

### C3 — Hadith/Quran content accuracy · **Quran half PASSED**

`php artisan quran:verify-arabic` checked all **6,236 verses against quran.com**,
verse by verse over the live API: **6,236 matched, 0 mismatched, 0 missing.** This is
exhaustive, not sampled.

`php artisan islamic-data:verify` also passes clean:
- Quran — 114 surahs, 6,236 verses, 0 missing Arabic, `verses_count` consistent for
  every surah, all four default translations (en/fr/tr/ur) complete at 6,236 each,
  0 orphaned translations, 0 duplicates.
- Hadith — **51,441** rows across 17 collections, every collection matching its source
  file count exactly (bukhari 7,277 · muslim 7,459 · nasai 5,768 · abudawud 5,276 ·
  mishkat 5,014 · tirmidhi 4,053 · ibnmajah 4,345 · darimi 3,406 · …). 0 hadiths
  without a book, 47,168 linked to a chapter, 38 missing Arabic text (known source gap).

Caveat worth stating plainly: this proves the DB matches the **local production source
files**, and that the Quran matches **quran.com**. It does not yet prove the hadith
source files themselves match sunnah.com. Prior reconciliation scripts exist for that
(`scripts/fix_*_from_sunnah_*.php`, `scripts/repair_*_db_from_source.php`), so several
collections have already been through it — but not all 17, and not under my observation.

### C2 — Public route smoke (via HTTP, ahead of full Chrome pass)

**31 of 32 static public routes return 200.** Dynamic routes verified against real
slugs: seerah event, media program, live channel, topic, hadith show, hadith
narrations all 200.

Two apparent failures, both investigated and **not defects**:
- `/hadith/translations` returns 302. It is a JSON AJAX endpoint requiring `ids`+`lang`,
  not a page. Minor smell only: a JSON endpoint should answer a bad request with 422
  JSON rather than redirecting to `/`.
- `/articles/{english-slug}` returns 404 bare, but **200 with `?lang=en`** — which is
  exactly what the page's own `hreflang` alternates emit. Per-locale slugs work.
- `/sahaba/12-sons` returns 404 correctly: that row is `person_type=family`,
  `is_sahabi=0`, so the public Sahaba surface rightly excludes it.

Real gaps found:
- **26 of 608 hadith books have an empty `slug_en`** (and 2 an empty `slug_ar`), so those
  books are unreachable by slug URL in English.
- The sitemap emits only Arabic article slugs — no `?lang=` alternates.

### Home page audit — **section-by-section data provenance**

Correcting an earlier claim of mine in this file: I first reported the public views as
essentially free of static/mock data. That was wrong. I had grepped for `$var = [`
arrays and lorem-style markers, which misses content hardcoded directly as markup plus
translation keys — which is exactly how the worst case here is written.

| § (line) | Section | Data source | Admin control |
|---|---|---|---|
| 143 | Hero search + filter chips | `quick_filter_chips` (DB) | ✅ `/admin/quick-filters` |
| 211 | **Hero grid: Seerah / Hadith / Media cards** | **hardcoded Blade + lang keys + static image** | ❌ none |
| 267 | Sahaba carousel | `sahaba` where `is_featured` (DB) | ~ flag only |
| 390 | "Seal of the Prophets" band | **hardcoded lang keys** | ❌ none |
| 570 | Daily verse + daily hadith | DB, random, cached per day | ❌ no curation |
| 756 | `#explore` module cards | `home_module_cards` (DB) | ✅ `/admin/home-cards` |
| 844 | **"Featured Content"** | **FABRICATED — 3 fake articles** | ❌ none |
| 923 | Join-community CTA | **hardcoded lang keys** | ❌ none |

**The mock data is real and confirmed.** §844 renders three invented articles whose
titles and excerpts live in `resources/lang/{ar,en,fr,tr,ur}/site.php` as
`article_1_title` … `article_3_excerpt`, paired with static
`images/site/article-{1,2,3}.webp`, linking to generic module routes rather than to any
article. Meanwhile the database holds 18 articles, 14 of them published.

This is also what confused the provenance question: there are **two unrelated card
systems** on this page. The `#explore` cards at §756 are DB-driven and admin-managed via
`home_module_cards`; the hero cards at §211 that look similar are hardcoded Blade. Same
visual language, completely different origin, no admin control on the hero one.

Follow-up split into three briefs:
- **T4a** — replace §844's fake articles with real published ones, delete the orphaned
  lang keys. (Brief written, queued behind T1.)
- **T4b** — move the §211 hero cards into the DB with admin CRUD, following the
  `home_module_cards` pattern that already exists.
- **T4c** — per-section visibility/ordering/heading settings, following the existing
  `genealogy_tree_max_*` precedent in `site_settings`.

### C1 — Admin testing · **BLOCKED, needs you**

`/su/portal/v1/admin/*` correctly redirects to login (good — the guard works). I cannot
type passwords into a login form; that is a standing rule I hold regardless of task.
**Please log into the admin in the Chrome tab I have open**, then say go — from there I
can drive every module's CRUD, relation pickers, and link inputs without ever handling
the credential itself.
