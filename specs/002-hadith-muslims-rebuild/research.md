# Phase 0 Research — Hadith and Muslim Identity Rebuild

Decisions that had to be settled before the schema could be written. Each records what was chosen,
why, and what was rejected. Findings are cited to the repository; nothing here is asserted from
general principle alone.

---

## R1. Runtime baseline

**Decision**: PHP 8.5, Laravel 13. Correct CLAUDE.md and the constitution as part of this work.

**Rationale**: `composer.json` requires `"php": "^8.5"` and `"laravel/framework": "^13.0"`, with
`config.platform.php` pinned to `8.5.4`. CLAUDE.md ("PHP 8.2+, Laravel 12.0") and the constitution's
Technology Constraints ("PHP 8.2+, Laravel 12") are both stale. Anything generated against 8.2 fails
dependency resolution before the rebuild starts.

**Alternatives considered**: Trusting the documented baseline — rejected, it is contradicted by the
lockfile-adjacent platform pin, which is the authoritative statement of what this project runs.

---

## R2. `reference_key` — the source contract (resolves Blocking 3)

This was the largest open dependency. Without it, `(collection_id, reference_key)` is an assumption
rather than a guarantee, and the idempotency claim in FR-040 is unfounded.

**Decision**: `reference_key` is produced by the source-normalisation step as an explicit, immutable
value, from **source structural position**, not from the displayed number. Its uniqueness is
validated across the entire corpus *before* any table is created. It is opaque to readers — it
appears in URLs but carries no scholarly meaning, and nothing may parse it to recover a number.

**Rationale** — the repository records two separate incidents proving displayed numbers cannot
identify a row:

- `2026_07_31_000011` records **56 Tirmidhi records lost** when uniqueness was assumed on
  `hadith_number`.
- `2026_07_31_000012` records that **Malik** needs source book *position* to separate otherwise
  identical records.

`ReferenceBuilder` composes display citations and explicitly does not derive identity — its own
docblock states references are *"rebuilt, never stored"* precisely so a corrected book number cannot
disagree with a stale line (`app/Domain/Hadith/Services/ReferenceBuilder.php:10-26`). Identity and
citation are therefore different concerns and must come from different places.

`config/islamic-data.php:63-68` further warns that file counts overstate content: collections contain
in-source duplicate files (the same hadith split across files — shamail has 402 files for 400
hadiths), so a file path alone is not a key either. The verified floors were established against
**distinct source `(book_number, hadith_number)` pairs**, which is the composite the corpus is known
to converge on.

**Chosen derivation**: `{sourceBookPosition}-{sourceHadithNumber}` scoped to the collection, taken
from the source's own structure, with a deterministic suffix appended only where the corpus proves a
genuine collision. The validator reports every collision it resolves, so no suffix is applied
silently (constitution X).

**Mandatory gate**: a `ReferenceKeyValidator` runs across all 17 collections and exits non-zero on
any duplicate **before** `migrate:fresh`. Finding a collision after the import costs the import;
finding it before costs a re-run of a validator.

**Alternatives considered**:
- *Displayed `hadith_number`* — rejected; cost 56 records once already.
- *Content hash* — rejected; a typo correction silently changes every reader's saved URL, and the
  38 Arabic-less records would hash on English alone.
- *Auto-increment ID in the URL* — rejected; not reproducible across rebuilds, so FR-040's
  "same corpus every time" would be unverifiable, and the whole point of the purge model is that the
  build is repeatable.

---

## R3. Grade projection rule (resolves Blocking 4)

**Decision**: adopt the existing deterministic rule, and keep `GradeNormalizer` as an import-time
projector and unmapped-grade reporter rather than a persistence layer.

- Consider only unlabelled, **hadith-scoped** verdicts.
- Exactly one distinct verdict → that verdict. Conflict, isnad-only, part-specific, or unmapped →
  `NULL`.
- `raw_grade` precedence: English if present, else Arabic; warn when the two disagree.
- `authenticated_by` produces **no** grade.

**Rationale**: source strings assert multiple scoped verdicts in one field (`"Sanad Da'if
wal-Hadith Hasan"` — the chain is weak, the hadith is sound; these are different claims about
different things). Collapsing them by picking either one at random would make repeated imports
disagree, breaking FR-040. `authenticated_by` names the compiler who included the hadith — membership,
not a verdict — and treating it as one previously generated roughly **18,000 bogus rows**
(9,379 + 8,863), documented in `2026_07_31_000007`. Preserving `raw_grade` verbatim means the
scholarly nuance the single enum discards is still readable even though it is no longer queryable.

**Alternatives considered**:
- *Severity precedence (weakest wins)* — rejected; it invents a scholarly judgement the sources do
  not make, and would mark a sound hadith weak because its chain was questioned.
- *Keeping the claims table* — closed by Decision A. Its consequence is accepted and recorded.

---

## R4. Collection key: `slug` replaces `code` — and what `code` was silently doing

**Decision**: one key, `slug`. The route regex widens to `[a-z0-9_-]+`. **But** the
directory-name → key mapping must survive into the importer, because it is not derivable.

**Rationale**: `config/islamic-data.php:22-50` maps production directory names to collection keys and
warns that the key *"is also the sunnah.com path segment, verified live on 2026-07-30: six of these
differ from the directory name, and `/ahmed` 404s outright, so nothing may treat a directory name as
a collection key."* Six mappings genuinely differ: `ahmed→ahmad`, `aladab_almufrad→adab`,
`bulugh_almaram→bulugh`, `mishkat_almasabih→mishkat`, `riyad_assalihin→riyadussalihin`,
`shamail_muhammadiyah→shamail`.

Decision F drops the `code` column, which is correct — one public key is better than two. The trap is
that dropping the column could be read as dropping the *mapping*, which lives in config, not in the
database. **The mapping stays in `config/islamic-data.php` and the importer reads it to assign
`slug`.** A rebuild that assumed directory names would produce six wrong URLs.

`ReferenceBuilder` also uses `code` for its deprecated-numbering list
(`['bukhari', 'muslim', 'malik']`) and as the canonical-line fallback; both switch to `slug`, and
since those three slugs equal their old codes, the list is unaffected.

**Alternatives considered**: keeping both `code` and `slug` — rejected by Decision F; two keys for
one concept is what created the ambiguity being removed.

---

## R5. Arabic search: normalisation and FULLTEXT

**Decision**: maintained `search_ar`/`search_en` columns written at import **and on every admin
write**, each with its own FULLTEXT index. Separate `MATCH(search_ar)` and `MATCH(search_en)`
branches, merged by score. `innodb_ft_min_token_size = 2`. No ngram parser.

**Rationale**:
- MySQL requires the `MATCH` column list to **exactly** equal an index. `MATCH(search_ar, search_en)`
  against two separate single-column indexes is an error, not a slow query.
- Default `innodb_ft_min_token_size` is 3, which silently discards two-character Arabic tokens —
  common and meaningful in Arabic. This is a **server variable**: it needs a restart and a FULLTEXT
  index rebuild. It is an environment prerequisite, not something a migration can arrange, so it
  belongs in the runbook's preflight.
- The ngram parser is CJK-oriented. Applying it to Arabic produces character-level tokens and wrecks
  relevance.
- Normalising at query time (`MATCH(strip_tashkeel(text_ar))`) is not SARGable and defeats the index
  entirely — constitution II forbids functions on indexed columns on hot paths.
- The normalisation needs PHP-side Arabic folding (tashkeel, tatweel, alef/ya/ta-marbuta variants),
  which a MySQL generated column cannot express — hence a maintained column, not a generated one.

**Regenerating on admin write is the part most likely to be missed.** The current system builds
search text at import; if the rebuild does the same, every editor correction becomes invisible to
search (FR-035 exists specifically to prevent this).

**Testing note**: InnoDB FULLTEXT is not transaction-visible in the current test arrangement, so
search tests use committed or truncated fixtures rather than transactional ones.

---

## R6. Nullable Arabic text (resolves Blocking 2)

**Decision**: `text_ar` and `search_ar` nullable, gated by a CHECK that blocks publication:

```sql
CONSTRAINT chk_hadiths_publishable
  CHECK (is_published = 0 OR (text_ar IS NOT NULL AND search_ar IS NOT NULL))
```

**Rationale**: `config/islamic-data.php:86-90` records `'known_missing_arabic' => 38` with the
explicit instruction *"Documented source gap — do not invent Arabic text."* `NOT NULL` leaves two
options, both wrong: drop 38 real records, or fabricate scripture. The CHECK expresses the actual
rule — the records exist, they are simply not publishable — and the count is carried into the parity
assertion so the gap stays visible rather than becoming folklore.

---

## R7. Where `Muslim` lives (constitution IV)

**Decision**: a new `app/Domain/People/` module owns `Muslim`, `IslamicRole`, and
`MuslimRelationship`. Hadith reaches it through a `Shared/Contracts/PersonReference` contract. A
`People` layer is added to `deptrac.yaml`.

**Rationale**: `Muslim` is referenced by Hadith (`hadith_narrators.narrator_id`,
`hadith_collections.compiler_id`), by genealogy, and by the companion directory. Constitution IV
forbids domain modules importing one another, and Blocking 5 found that deptrac has **no layer
owning `Muslim`** — so the violation would surface as a deptrac failure at merge, not at design time.

Database-level foreign keys may cross modules (the constitution says so explicitly, since deptrac
cannot see them). It is the **PHP import** that must not. So `hadith_narrators.narrator_id` → `muslims`
is a legitimate FK; `use App\Domain\People\Models\Muslim` inside `app/Domain/Hadith/` is not.

**Alternatives considered**: `Muslim` in `Hadith` (forces Genealogy → Hadith import), in `Genealogy`
(forces Hadith → Genealogy), or in `Shared` (overloads a layer the constitution defines as a leaf of
contracts, not an owner of models with their own admin CRUD). All three fail.

---

## R8. Genealogy: descent on the row, edges in a table

**Decision**: `father_id`/`mother_id` as nullable self-FKs on `muslims` with **restrict on delete**;
everything else (`spouse`, `sibling`, `uncle`, `aunt`, `cousin`) in `muslim_relationships`, stored
**once** per pair and read with `muslim_id = ? OR related_muslim_id = ?`.

**Rationale**: this is the existing codebase's rule, kept deliberately. `RelationshipType`'s docblock
states that *"parent, child and grandparent relations are derived from father_id/mother_id and are
deliberately not duplicated in the relationships table."* One father and one mother is enforced by
the column itself; expressing it in a table of edges would need a partial unique index MySQL does not
have.

Every remaining type is symmetric, so storing both directions doubles the rows and lets the pair
drift out of sync. `uncle`/`aunt` are symmetric in the sense that the reverse edge (nephew/niece) is
the same row read from the other side; the reading layer supplies the label.

**Cycle prevention is a service concern.** No CHECK constraint can express "this person is not
already my ancestor" — it requires a traversal. The domain service walks ancestors before writing a
parent; the recursive CTE's depth cap is the backstop. Both need tests, because the constraint layer
cannot help here.

**Note**: the existing `relationships` table has FKs pointing at `people`, dropped by
`2026_06_24_100000:43-44` — it is already orphaned today, independent of this rebuild.

---

## R9. Purge mechanics (constitution VIII)

**Decision**: delete the old migration *files*; author a fresh set; build with `migrate:fresh` behind
a database-name allowlist assertion. No `DROP TABLE` migrations are written.

**Rationale**: writing teardown migrations would be writing an upgrade path, which the owner
explicitly ruled out. A table with no migration file simply does not exist after `migrate:fresh` —
deleting the file *is* the mechanism, which is why there is no drop order to sequence.

Constitution VIII's clauses bind directly:
- The DB-name allowlist assert is the only real control; printing the name is explicitly not one.
- `DB_DATABASE` must be passed explicitly — inferring it from `--env` silently falls back to `.env`
  when the matching env file is absent, which is exactly how a dev command reaches production.
- **The untracked file problem is live right now**:
  `2026_08_05_000006_add_exact_hadith_reference_fields.php` is untracked and seven Hadith files have
  uncommitted modifications. Constitution VIII requires a committed checkpoint before deleting
  untracked files. They must be committed *before* the purge commit or they are unrecoverable.

**Dissolved by this decision**: Blocking 6 (dangling IDs in `topic_items`/`favorites`/
`user_interactions`) and Blocking 7 (MySQL's implicit COMMIT on DDL making an in-place cutover
unrollbackable). Both were artefacts of a cutover that no longer happens. What survives of 6: content
restored after the rebuild must match on `reference_key`/`slug`, never on a numeric ID — a reused ID
points at a *different* record, which is worse than a dangling one.

---

## R10. Canonical source availability

**Decision**: verifying the source workspace is **step 0** of the runbook, before any destructive
action.

**Rationale**: `config/islamic-data.php:16-20` resolves hadith sources to
`../prophet-data-generator/hadith/production`, outside this repository. **That directory is not
present on this machine.** Because the purge runs before the import, discovering its absence
afterwards leaves the system empty with no way back — the one failure mode in this plan with no
recovery. The parity floors in `expected_hadiths` (17 collections, **51,441** hadiths) are only
meaningful if the files they were measured against are the files being imported.

---

## R11. Two different counts, and why both exist

**Decision** (owner, 2026-08-06): `hadith_collections.total_hadith` **is stored**, and it holds the
**total the source book itself declares** — not a count of our rows.

These are two genuinely different numbers and the schema carries both:

| | `total_hadith` (stored) | available count (computed) |
|---|---|---|
| Means | what the book contains, per the source | what this site currently publishes |
| Source | the collection's own scholarly total, set at import | `withCount` under a published constraint |
| Changes when | a scholar's edition is corrected | an editor publishes or unpublishes one hadith |
| Recomputed from rows | **never** | every read |

**Rationale**: my earlier objection — that a stored counter goes stale the moment an editor
unpublishes a hadith — applies only to a *cached row count*. It does not apply here. "Sahih
al-Bukhari contains 7,563 hadith" is a fact about the book, not a summary of our table; no amount of
publishing or unpublishing changes it, and it cannot be derived from our rows at all (we may hold
fewer). Storing it is the only way to have it.

**The failure mode to prevent is conflation.** If a template renders `total_hadith` where a reader
expects "how many can I read here", the site claims content it does not serve. So:

- `total_hadith` is set at import from the source and is admin-editable. It is **never** recomputed
  from row counts.
- The count of what a reader can actually open stays computed via `withCount` with a published
  constraint (constitution II permits this — it is DB-level aggregation, not a PHP-collection
  `count()`).
- Resources expose them under distinct names — `total_hadith` and `available_hadiths_count` — so a
  template cannot pick the wrong one by accident.

**Alternatives considered**: naming it `total_rows` (my earlier suggestion) — rejected, it describes
our storage rather than the book, which is the opposite of what the column means.

---

## Open questions carried into implementation

| # | Question | Blocks |
|---|----------|--------|
| 1 | ~~`total_hadith`~~ — **resolved**: kept as the source-declared total (R11) | — |
| 2 | Soft deletes on `muslims` — permanent, or `deleted_at` with restore? | The `muslims` migration (Track A, imminent) |
| 3 | Is the `../prophet-data-generator` workspace obtainable, and does it match the audited floors? | **All of Track B.** Track A (People, genealogy, admin) proceeds regardless |
