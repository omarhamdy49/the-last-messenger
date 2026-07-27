# Sahaba Schema — Optimization & Correctness Review

Source: database-architect review of `sahaba` + linked tables (`sahabi_translations`,
`sahabi_battles`, `sahabi_timeline_items`, `relationships`, `hadith_narrators`,
`hadith_companions`), cross-checked against `docs/SAHABA_SCHEMA_AND_FETCH_OPTIONS.md`
and **live dev data** (10,314 `sahaba` rows). Every claim below was verified against
the running DB or the migration history, not guessed from the schema alone.
Severities: **P0** real bug/data loss, **P1** dead weight/misleading column,
**P2** design improvement. Check the box when fixed.

---

## P0 — confirmed bugs (fix first)

- [x] **S-01 · The June 24 consolidation silently destroyed 29 grandparent/grandchild/nephew/niece relationship rows, reversing a feature built two weeks earlier.**
  `2026_06_10_110000_add_grandchild_relationship_types...php` widened the `relationships.relationship_type` enum specifically so "the Prophet's ﷺ grandchildren can be linked to their grandparents directly." Two weeks later, `2026_06_24_100000_consolidate_genealogy_into_sahaba.php::remapRelationships()` rebuilds the table through a `match()` that only maps `husband/wife→spouse`, `brother/sister→sibling`, `cousin`, `uncle`, `aunt` — every other legacy type (`father`, `mother`, `son`, `daughter`, `grandfather`, `grandmother`, `grandson`, `granddaughter`, `nephew`, `niece`) hits `default => null` and is `continue`d, i.e. dropped. Verified directly against `storage/app/backups/identity-before-sahaba-consolidation-2026-06-24.sql`: 7 grandfather, 1 grandmother, 4 grandson, 4 granddaughter, 12 nephew, 1 niece = **29 rows gone**, plus 60 father/30 mother/59 son/31 daughter (these are harmless — parentage is preserved separately in `sahaba.father_id`/`mother_id`). The current `RelationshipType` enum (`spouse|sibling|uncle|aunt|cousin`) and `Relationship::getInverseRelationshipType()` no longer even have cases to hold grandparent/grandchild data, and no code anywhere (`GenealogyService`, admin/site `GenealogyController`) derives it another way (`grep` for grandson/granddaughter/grandfather/grandmother/nephew/niece across the app returns zero hits). `down()` on that migration is a documented no-op ("cannot restore deleted duplicates").
  **Where:** `database/migrations/2026_06_24_100000_consolidate_genealogy_into_sahaba.php` (`remapRelationships`, ~L555); `app/Domain/Genealogy/Enums/RelationshipType.php`.
  **Fix:** decide deliberately, don't leave this as an accident: either (a) re-add `grandparent`/`grandchild`/`nephew`/`niece` to `RelationshipType` and derive+backfill them from `father_id`/`mother_id` two-hop chains (the source data no longer exists to restore verbatim, but grandparent/grandchild edges are 100% re-derivable from current `father_id`/`mother_id`, so nothing is actually lost forever — write a backfill command), or (b) keep the narrowed enum but add the two-hop derivation as an application-level `GenealogyService` method (`getGrandchildren()`, `getGrandparents()`) so the capability the June 10 migration built for still exists somewhere. Pick one and note the decision in `docs/SAHABA_SCHEMA_AND_FETCH_OPTIONS.md`.
  **Verify:** a query for "grandchildren of Sahabi X" returns the same people it would have returned before June 24 (cross-check against the backup for a Sahabi with `father_id` chains 2 deep, e.g. id 67's descendants).

- [x] **S-02 · `SahabiResource` exposes the wrong `profile_image`/`gallery` — the API can never return an uploaded photo.**
  The model fully implements Spatie MediaLibrary for images (`registerMediaCollections()`, `getProfileImageUrlAttribute()`, `getGalleryImagesAttribute()`), and the admin controller uploads through `addMedia()->toMediaCollection(...)`. But `SahabiResource` serializes the **raw legacy scalar columns** instead: `'profile_image' => $this->profile_image` and `'gallery' => $this->gallery`. Confirmed against live data: **0 of 10,314 rows have either column populated** — every admin-uploaded photo/gallery image is invisible to any API consumer, silently, because the resource reads a column nothing ever writes to.
  **Where:** `app/Http/Resources/SahabiResource.php:59-60`.
  **Fix:** replace with `'profile_image' => $this->profile_image_url` (or `avatar_url` for list views) and `'gallery' => $this->gallery_images`, using the accessors that already exist on the model. Ties directly into S-05/S-06 below (drop the dead columns once the resource no longer reads them).
  **Verify:** upload a photo via the admin Sahabi form → `GET /api/v1/sahaba/{slug}` returns a working URL.

- [x] **S-03 · `hadith_count` is a disconnected, misleading number — not a count of anything in this database.**
  `sahaba.hadith_count` sums to **4,150** across the table, sourced verbatim from the scraper's `islamic_history.hadith_count` (a bio-text claim from muslimscholars.info, e.g. "narrated ~5000 hadiths"). It has no relationship to this database's actual hadith graph: only **23 distinct Sahaba** are linked as narrators via the `hadith_narrators.sahaba_id` FK the June 24 migration built, touching just **186 `hadith_isnad` rows** total. `scopeTopNarrators()` and the public profile page (`SahabaController.php:304`, `'hadithCount' => $sahabi->hadith_count`) present this as if it reflects "hadiths in this app," which it does not — it's an unrelated external claim that happens to share a column name with a real, derivable concept.
  **Where:** `app/Domain/Sahaba/Models/Sahabi.php` (`scopeTopNarrators`), `app/Http/Controllers/Site/SahabaController.php:304`.
  **Fix:** rename the column to make its provenance explicit (e.g. `reported_hadith_count`) and add a genuinely derived count for anything used to rank/display "hadiths narrated in this database" — either `withCount(['hadithNarratorLinks as isnad_count'])` computed at read time (cheap: only 23 rows currently qualify), or a maintained counter updated the same way `SyncBookJob` maintains `total_hadith_count` on `hadith_books`. Don't keep a column named `hadith_count` that means "an external website's bio claim" — the name promises a live count.
  **Verify:** `EXPLAIN` the top-narrators query before/after; confirm the displayed number matches `hadith_isnad` rows actually reachable through `sahaba_id`.

---

## P1 — answers to the flagged columns (dead weight, confirmed by live data)

- [x] **S-04 · `external_id` — keep, it is load-bearing.** Not dead: it's the idempotency key for `php artisan sahaba:import-json`. `SahabaJsonImportService` looks up, upserts, and dedupes exclusively by `external_id` at 6+ call sites (`Sahabi::withTrashed()->where('external_id', ...)`). Removing it forces re-imports to rely on fuzzy Arabic name-normalization matching alone (`normalizeName()` in the consolidation migration already shows how fragile that is — diacritics, hamza forms, etc.). **No change.**

- [x] **S-05 · `profile_image` — drop the column.** Confirmed 0/10,314 rows populated. Real images live in Spatie's `media` table via the `profile_image` collection; the scalar column is legacy scaffolding from before MediaLibrary was wired in, and the only place it's still read is the `getFirstMediaUrl()` override's fallback branch (dead fallback — nothing ever sets it) and the now-buggy `SahabiResource` line fixed in S-02.
  **Fix:** after S-02 ships, `Schema::table('sahaba', fn ($t) => $t->dropColumn('profile_image'))`; remove it from `$fillable`/`$casts`/docblock on `Sahabi`, and from the fallback branch in `getFirstMediaUrl()`.

- [x] **S-06 · `gallery` (JSON column) — drop the column.** Same finding as S-05: 0/10,314 populated, real gallery images already live in the `gallery` media collection (`getGalleryImagesAttribute()`), the JSON column is unreachable dead weight once S-02 stops reading it.
  **Fix:** drop `sahaba.gallery`; remove from `$fillable`/`$casts`.

- [x] **S-07 · `related_article_id` — drop the column, it was never wired up.** 0/10,314 populated, and unlike Seerah's own `related_article_id` (fully wired: validated in `StoreSeerahEventRequest`/`UpdateSeerahEventRequest`, set in `SeerahEventService`, rendered in admin views), Sahaba's copy has **no validation rule** in `StoreSahabiRequest`/`UpdateSahabiRequest`, **no form field**, and **no controller handling** — it was scaffolded when `sahaba` was created (Oct 2025) and never connected to anything.
  **Fix:** `dropForeign` + `dropColumn('related_article_id')`; remove the `article()` relation and the `'article' => new ArticleResource(...)` line from `SahabiResource` unless there's a concrete near-term plan to wire it up (in which case, wire it up instead of leaving it dark).

- [x] **S-08 · `conversion_order` — keep, it's correctly modeled.** This is *As-Sabiqun al-Awwalun* ordering (who accepted Islam 1st/2nd/3rd...), a real, named historical concept. It's nullable, indexed, and backs `scopeEarlyMuslims()` / the docs' `early_muslims`-style filter. No redundancy, no dead weight. **No change** — just documenting what it is since the name alone doesn't make the domain concept obvious outside Islamic history.

---

## P2 — schema redesign (short, lowercase, no gender-baked-into-enum)

- [x] **S-09 · `person_type` bakes gender into the enum for no reason — collapse `companion_male`/`companion_female` into one value.** `gender` (`male`/`female`) and `is_sahabi` (bool) already exist as their own columns. Live data: `companion_male` 6,665 + `companion_female` 1,170 = 7,835 rows, **100% derivable** from `gender` + `is_sahabi` — the split is a leftover from the old `people` table (dropped in the same migration) and was carried over unchanged instead of being resolved. Full current distribution: `companion_male` 6,665, `companion_female` 1,170, `family` 2,447, `historical_figure` 22, `tribe_leader` 9, `prophet` 1.
  **Fix (short lowercase enum, no gender suffix):**
  ```
  prophet | sahabi | family | tribe | historical
  ```
  Migration: `MODIFY person_type ENUM('prophet','sahabi','family','tribe','historical') NOT NULL`, backfilled via
  `UPDATE sahaba SET person_type = 'sahabi' WHERE person_type IN ('companion_male','companion_female')`
  and `tribe_leader → tribe`, `historical_figure → historical`. Gender stays exactly where it already is (`gender` column) — this is a pure rename/collapse, not a feature change.
  **Verify:** `SELECT DISTINCT person_type FROM sahaba` returns only the 5 new values; admin genealogy filters that read `person_type` still group correctly (they were already ignoring the gender suffix for grouping, per `grep` — nothing keys off `_male`/`_female` specifically).

- [x] **S-10 · `status` forces a single value onto people who historically hold several at once — replace with a `sahabi_categories` lookup + pivot.** Live distribution: `badr_participant` 315, `uhud_participant` 194, `ten_promised_paradise` 17, `early_muslim` 141, `muhajir` 29, `ansar` 689 (plus 6,450 sitting at the bare default `prophet_companion` and 2,479 `genealogy_member` placeholders). These six meaningful tags are **not mutually exclusive in history** — a Muhajir who fought at Badr is both, an Ansari present at Uhud is both — but the single-enum column can only ever store one, which is why `CompanionStatus::rank()` exists at all (it's a workaround for "which one do we show" when several would apply). This mirrors exactly the pattern the team already solved for battles: `sahabi_battles` replaced a JSON `battles` column with a proper pivot + composite index specifically to avoid full-table JSON scans and to allow multi-value membership. Do the same here.
  **Fix:**
  ```php
  // sahabi_categories (lookup table)
  Schema::create('sahabi_categories', function (Blueprint $table) {
      $table->id();
      $table->string('key', 30)->unique();   // badr, uhud, ten_promised, early_muslim, muhajir, ansar, tabi
      $table->unsignedTinyInteger('rank')->default(0); // replaces CompanionStatus::rank() for default sort
      $table->timestamps();
  });

  // sahabi_category (pivot — mirrors sahabi_battles' shape)
  Schema::create('sahabi_category', function (Blueprint $table) {
      $table->id();
      $table->foreignId('sahabi_id')->constrained('sahaba')->cascadeOnDelete();
      $table->foreignId('category_id')->constrained('sahabi_categories')->cascadeOnDelete();
      $table->unique(['sahabi_id', 'category_id']);
      $table->index(['category_id', 'sahabi_id'], 'sahabi_category_cat_sahabi_idx'); // same access-pattern reasoning as sahabi_battles_battle_sahabi_idx
  });
  ```
  Backfill: one row per non-default, non-`genealogy_member` `status` value (1,385 rows total: 315+194+17+141+29+689). Rows currently at the bare `prophet_companion` default get **no** category row (that default was standing in for "no specific tag," not a real category — don't manufacture 6,450 meaningless pivot rows). Keep `sahaba.status` only long enough to run the backfill, then drop it; keep `is_sahabi`/`gender` as-is (unaffected). Update `SahabaController` (site) status-filter pills to count via the pivot (a Sahabi can now legitimately appear under 2+ pills — that's the point, not a bug to guard against).
  **Verify:** `SELECT COUNT(*) FROM sahabi_category` = 1,385 immediately after backfill; a known dual-status Sahabi (e.g. any Badr participant who was also Muhajir) shows both category chips on their profile.

- [ ] **S-11 · (informational, not a change) `roles` JSON has the identical modeling problem as old `status` — flagging for a separate, deliberate decision.** `roles` (e.g. `["Caliph","Commander","Narrator"]`) is unindexed free text with no referential integrity, and already contains rank-worthy values like `"Khalifah"`/`"Caliph"` that arguably belong in a structured category system rather than a JSON bag — but it's a different *dimension* (functional role vs. historical-rank category) from S-10, so don't fold it into `sahabi_categories` reflexively. Worth a deliberate follow-up decision, not a drive-by merge in this pass.

- [ ] **S-12 · (informational) `is_verified` and `generation` are populated but never read.** `is_verified` is true on 7,806/10,314 rows, `generation` is set on 1,010/10,314 — both come from import (`SahabaJsonImportService`) but no admin or public controller filters, displays, or gates on either. Not dead in the "never populated" sense like S-05/S-06/S-07 — just unused metadata. The docs already describe an `admin` response mode ("raw/import fields, placeholders, verification status, conflict visibility") that would be the natural home for `is_verified` (a data-quality review screen); `generation` would feed genealogy-depth display. Decide whether to wire these in or drop them — don't leave real, populated columns silently unused indefinitely.

---

## Suggested execution order

1. S-02 (fix the resource bug) → S-05/S-06/S-07 (drop the now-provably-dead columns) — safe, additive-free, no schema risk beyond a `dropColumn`.
2. S-03 (rename/re-derive `hadith_count`) — independent, no dependency on the others.
3. S-01 (decide grandparent/grandchild recovery strategy) — needs a product decision (re-add enum cases vs. derive in `GenealogyService`), do this before touching `relationships` further.
4. S-09 (`person_type` collapse) — pure rename, lowest risk of the P2 batch.
5. S-10 (`status` → category pivot) — biggest change, touches the public site's status filter pills; do last and behind its own test coverage (success/401/404/422 per `CLAUDE.md` §7, plus a feature test asserting a dual-category Sahabi appears under both pills).

---

## Shipped (2026-07-02)

All items above are implemented, migrated on the dev DB, tested, and browser-verified (admin create/edit + public listing/profile pages) — see migrations `2026_07_02_09000{0,1,2,3}_*`.

### Full form-field audit (2026-07-02, second pass)

A re-check found the admin create/edit forms only ever covered a subset of `sahaba` + `sahabi_translations` — some gaps predate this session (e.g. `laqab` was validated by `Store`/`UpdateSahabiRequest` but had no input; `virtues`/`famous_narrations` were in the controller's translatable-extraction list but had no input either), others were fields nobody had ever wired up (`title`, `occupation`, `notes`, `martyrdom`, `birth_date_gregorian`, `death_date_gregorian`, `age_at_death`, `conversion_date_hijri`, `conversion_order`, `roles`, `generation`, `is_verified`). All of the above are now in both forms, validated, and displayed on the show/preview page. `SahabiController::extractTranslatableData()` was also missing `title`/`occupation`/`martyrdom`/`notes` from its hardcoded field list — fixed alongside.

**Deliberately still not exposed in this form** (not oversights):
- `external_id` — import-only identity; a manual edit would break `sahaba:import-json` idempotency.
- `father_id` / `mother_id` — managed by the separate Genealogy admin module's own parent/child UI; duplicating that here would let the two disagree.

Covered by `tests/Feature/Admin/SahabiCrudTest.php` — asserts every field round-trips through `store()`, `update()`, and renders on the show/edit pages (not just "the input exists," but "the saved value is actually there after a real HTTP POST/PUT").

- **S-01** shipped as data-access-layer derivation (`PersonRepository::getGrandparents/getGrandchildren/getNephewsAndNieces`, exposed via `GenealogyService`) rather than re-widening the `relationships` enum — the 29 dropped rows can't be restored verbatim, but grandparent/grandchild edges are 100% re-derivable from `father_id`/`mother_id`, which the public `/genealogy` tree page (`getFamilyTree`, 3 generations) already does. **Scope note:** nothing in the app currently renders the `relationships` table's typed edges at all (verified — no controller/Blade reads `Sahabi::relationships()`/`relatedRelationships()`), so no new UI was built here; that would be a separate, deliberate design task (`/ui-ux-pro-max`), not a bug fix.
- **S-10** category keys ship as short lowercase strings (`ten_promised`, `badr`, `uhud`, `early_muslim`, `muhajir`, `ansar`, `tabi`) and `person_type` as `prophet|sahabi|family|tribe|historical`, per explicit direction to keep enum values short and lowercase.
- **Bug found during browser verification (not in the original list):** `UpdateSahabiRequest::rules()` read `$this->route('id')` for the slug-uniqueness `ignore()`, but the route parameter is actually named `{sahaba}` (`Route::resource('sahaba', ...)`) — so it was always `null`, meaning `Rule::unique(...)->ignore(null)` never actually excluded the record being edited. **Every edit that kept its existing slug (the default, since the form pre-fills it) failed validation with "slug already used."** Fixed to `$this->route('sahaba')`. Pre-existing, unrelated to the schema work, caught only because the browser-verification pass exercised a real edit-and-save round trip.
- **Two validated-but-unreachable fields wired up:** `burial_place` and `clan` had validation rules in `Store`/`UpdateSahabiRequest` but no corresponding input in `create.blade.php`/`edit.blade.php` — added, matching the existing field pattern.

### Hadith ↔ Sahaba FK linkage — investigated, already correct

Checked whether `hadith_narrators.sahaba_id` / `hadith_companions.sahaba_id` are only ever set by the one-time 2026-06-24 consolidation backfill, or whether ongoing hadith imports keep the link current, and whether the frontend would show a blank name for a narrator whose raw name columns were nulled out once linked. All three came back clean, no fix needed:

- **Every create/import path re-resolves identity, not just the historical backfill.** `SahabiIdentityMatcher::match()` (`app/Domain/Sahaba/Services/SahabiIdentityMatcher.php`) runs inside `HadithNarrator::resolveOrCreate()` and `Companion::findOrCreateByName()` on every call — verified directly in `HadithNarrator.php`. Callers include the live JSON importer (`HadithImportPersister.php`), the CSV/Excel importer (`HadithNarratorImporter.php`), the CQRS `CreateNarratorHandler`, and the admin narrator create form — so a hadith imported today still gets `sahaba_id` set if the name matches an existing Sahabi.
- **No blank-name risk.** `HadithNarrator`/`Companion` accessors (`getNameArAttribute` etc.) fall back to `$this->sahabi?->getTranslation(...)` when the raw column is null — verified directly. `HadithRepository::publicDetailRelations()` always eager-loads `sahabi.translations` alongside the narrator/companion, so the fallback has what it needs whenever the public hadith page or API resource renders a narrator.
- **Gap (not fixed, flagged for a decision):** there is no admin UI to manually attach/detach a `sahaba_id` on a narrator or companion — it's set exclusively by the automatic name matcher. If the matcher misses a real match (e.g. a spelling variant it doesn't normalize) or mismatches, an admin currently has no way to correct it by hand. Building that is a form + validation change, not a bug fix — worth a deliberate follow-up if this turns out to matter in practice.
