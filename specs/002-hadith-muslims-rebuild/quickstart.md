# Rebuild Runbook

Executable sequence for the full purge and rebuild. Read [`plan.md`](plan.md) → Gate VIII before
running anything here: this procedure destroys the entire database, and two of its steps are
unrecoverable if taken out of order.

**Recovery model**: there is no snapshot rollback. Recovery is *run the whole thing again*. That only
works if the inputs still exist — which is what Preflight protects.

---

## Preflight — before any destructive action

Every check here is a stop. None is advisory.

### P1. The canonical sources exist ⚠

```bash
ls -d "$(dirname "$PWD")/prophet-data-generator/hadith/production"
```

**As of this writing, this directory does not exist on the development machine.**
`config/islamic-data.php:16-20` resolves hadith sources to `../prophet-data-generator/hadith/production`
— outside this repository.

The purge runs **before** the import. If the sources are absent, the result is an empty system with
nothing to rebuild from and no way back. This is the only failure mode in the plan with no recovery,
which is why it is check number one and not an assumption.

Confirm the workspace also matches the audited floors in `config/islamic-data.php:69-88` — 17
collections, **51,441** hadiths. Floors measured against different files are not floors.

### P2. Commit everything, including untracked files ⚠

```bash
git status --porcelain
```

Constitution VIII: *"Deleting untracked files requires a committed checkpoint first."*

Right now the working tree has **uncommitted modifications to seven Hadith files** and **one
untracked migration**, `database/migrations/2026_08_05_000006_add_exact_hadith_reference_fields.php`,
which the purge deletes. Commit them first or they are gone with no history to recover them from.

### P3. Confirm the target database

```bash
php artisan tinker --execute="echo config('database.connections.mysql.database');"
```

Constitution VIII: the resolved name must be asserted against an explicit allowlist and the command
must **abort** on mismatch. Printing the name is explicitly *not* a control — a human reading a
printed name is exactly the check that fails at 2am.

- Pass `DB_DATABASE` **explicitly**. Never infer it from `--env`, which silently falls back to `.env`
  when the matching env file is absent.
- The rebuild command implements the allowlist assertion itself. Do not rely on the operator.

### P4. Server variables for Arabic FULLTEXT

```sql
SHOW VARIABLES LIKE 'innodb_ft_min_token_size';  -- must be 2, default is 3
SHOW VARIABLES LIKE 'innodb_ft_enable_stopword'; -- OFF for Arabic
```

`innodb_ft_min_token_size` is a **server variable**: changing it needs a MySQL restart *and* a rebuild
of every FULLTEXT index. Setting it after the import means reindexing 51,441 rows. Setting it here
costs nothing.

At the default of 3, two-character Arabic searches return nothing — with no error. FR-029 fails
silently, which is the worst way for it to fail.

### P5. Decisions that block the schema

| Question | Blocks | Default if unanswered |
|----------|--------|-----------------------|
| Soft deletes on `muslims`? | the `muslims` migration | `deleted_at` with admin restore |
| `total_rows` on collections? | the `hadith_collections` migration | omitted; counts computed |

Both are cheap to answer and expensive to change after 51,441 rows exist.

---

## Phase 1 — Purge

```bash
# 1. Delete the old migration files. Confirm the list first.
git ls-files database/migrations | grep -E '2026_07_31_0000|2026_08_05_0000|2025_10_27_1[34]|2026_03_09_200003|2026_03_17_000004|2026_06_24_1[01]0000|2026_07_02_09000|2026_07_02_140001|2026_07_31_000015'

# 2. Delete old domain code
rm -rf app/Domain/Hadith app/Domain/Sahaba app/Domain/Genealogy

# 3. One commit, so the tree is never half-purged
git add -A && git commit -m "chore: purge hadith, sahaba, and genealogy modules"
```

No `DROP TABLE` migration is written. A table with no migration file does not exist after
`migrate:fresh` — deleting the file *is* the mechanism, which is why there is no drop order.

Also delete in this commit: Hadith/Sahabi admin controllers, API resources, Blade views, factories,
tests, and the routes in `routes/api.php:60-89`, `routes/api.php:123-135`, `routes/web.php:70-91`.

## Phase 2 — Author the new schema

Migration order (`data-model.md`):

```
islamic_roles → muslims → muslims_roles → muslim_relationships
→ hadith_collections → hadith_books → hadith_chapters → hadiths → hadith_narrators
```

Nothing in this set may drop, alter, or reference a pre-existing table. `down()` is `dropIfExists`
for the new tables only — it rolls back the new schema, never toward the old one.

Fix in the same phase, or the app fatals on boot:

- `deptrac.yaml` — add the `People` layer (plan → Complexity Tracking #1)
- `app/Providers/FavoriteServiceProvider.php` — eager-loads `texts`, `book`, `collection`; builds
  number-based URLs
- `app/Domain/Topics/Repositories/TopicRepository.php`
- `app/Domain/Shared/Search/SearchQueryBuilder.php`
- `app/Console/Commands/Portal/{ExportPortalDataCommand,VerifyPortalDataCommand}.php` — hardcoded old
  table lists
- `app/Models/ArticleLocale.php` — embeds `hadith_id` / `sahabi_id`
- `CLAUDE.md` and `.specify/memory/constitution.md` — PHP 8.5 / Laravel 13, not 8.2 / 12

## Phase 3 — Validate before building

```bash
php artisan hadith:validate-reference-keys
```

Runs across all 17 collections and exits non-zero on any duplicate `reference_key`. **Before**
`migrate:fresh`, not after — a collision found here costs a validator re-run; found after the import
it costs the import (research → R2).

## Phase 4 — Build

```bash
DB_DATABASE=the_last_messenger php artisan migrate:fresh   # allowlist-asserted
php artisan db:seed --class=IslamicRoleSeeder
```

## Phase 5 — Import

```bash
php artisan islamic-data:import-muslims
php artisan islamic-data:import-hadith --collection=bukhari   # per collection, transactional
```

Each collection imports inside one transaction, so a failure leaves that collection whole — fully
imported or not at all (FR-039).

## Phase 6 — Parity

```bash
php artisan islamic-data:verify
```

Reports, per collection: expected vs produced counts for collections, books, chapters, hadiths,
narrator links, and muslims; unmapped grades; unresolved people; discarded categories; and the
known-missing-Arabic tally, which must be **exactly 38**.

Counts are itemised, never averaged (constitution X). Non-zero exit on any mismatch, and nothing is
published while it fails.

## Phase 7 — Publish

```bash
php artisan optimize:clear && npm run build
```

`npm run build` is required — there is no Vite dev server in this setup, and new Blade views silently
break without it.

---

## Verification

```bash
php artisan test
./vendor/bin/pint
composer deptrac
```

Then, per constitution VI, confirm the MySQL-specific behaviour is actually asserted rather than
assumed:

- CHECK: publishing a hadith with no Arabic is rejected; a nameless muslim is rejected
- Composite FK: a hadith naming a book from another collection is rejected
- Generated column: a second primary narrator on one hadith is rejected
- FULLTEXT: two-character Arabic term returns results; diacritic and undiacritised queries agree
- Recursive CTE: depth cap honoured and `depth_capped` reported
- Deleted routes return **404**, not a catch-all fallthrough

Search tests use **committed or truncated fixtures** — InnoDB FULLTEXT is not transaction-visible in
the current test arrangement, so a transactional fixture produces an empty result set that looks like
a broken query.

---

## Idempotency check

The purge model's core claim is that the build is repeatable (FR-040, SC-012). Prove it:

```bash
php artisan migrate:fresh && php artisan db:seed --class=IslamicRoleSeeder \
  && php artisan islamic-data:import-muslims && php artisan islamic-data:import-hadith
php artisan islamic-data:verify   # identical counts, zero duplicates, no manual cleanup
```

If two runs disagree, `reference_key` derivation is not deterministic and R2 needs revisiting before
anything is published.
