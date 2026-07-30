# Quickstart: Hadith Module Rebuild

Operational procedures and recorded measurements for `specs/001-hadith-rebuild`.

---

## 1. `innodb_ft_min_token_size = 2` (T058) — APPLIED 2026-07-30

MySQL's default is **3**, which silently makes every two-character Arabic token unindexable. Arabic
search returns wrong results rather than failing loudly, and it returns *different* results per
environment if this is applied inconsistently (FR-012).

`innodb_ft_min_token_size` is read-only at runtime — it needs a config change, a **server restart**,
and a **FULLTEXT index rebuild** (existing index entries are not retroactively re-tokenised).

### State

Was **3**; set to **2** on 2026-07-30, container restarted, index rebuilt in both databases, and a
two-character Arabic term proven to match. All four other databases on the container survived intact.

### The complication

The MySQL on host port 3308 is **not owned by this project**. It is the `dev-mysql-db` container of
the `local-dev-machine` compose project at `/Volumes/Data/local-dev-machine/docker-compose.yml`, and
it also serves `iac_ws`, `ihsan_academy` and `unlock_academy`. A restart interrupts those projects.

The config is a bind mount: `/Volumes/Data/local-dev-machine/mysql-db/my.cnf` → `/etc/mysql/my.cnf`.

### Procedure — dev

1. Append to `/Volumes/Data/local-dev-machine/mysql-db/my.cnf` under `[mysqld]`:

   ```ini
   # Arabic hadith search: two-character tokens are meaningful, and the default
   # of 3 makes them unindexable. See specs/001-hadith-rebuild (FR-012).
   innodb_ft_min_token_size = 2
   ```

2. Restart (this interrupts the other three databases on the container):

   ```bash
   docker compose -f /Volumes/Data/local-dev-machine/docker-compose.yml restart mysql-db
   ```

3. Confirm, then rebuild the index in **both** databases — the setting does not re-tokenise what is
   already indexed:

   ```bash
   docker exec dev-mysql-db mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -e \
     "SHOW VARIABLES LIKE 'innodb_ft_min_token_size';"

   for db in the_last_messenger the_last_messenger_test; do
     docker exec dev-mysql-db mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$db" -e \
       "ALTER TABLE hadith_texts DROP INDEX ft_hadith_texts_search;
        ALTER TABLE hadith_texts ADD FULLTEXT ft_hadith_texts_search (search_body);"
   done
   ```

4. Prove it with the assertion T131 depends on — a two-character Arabic term must return rows.

   **Verify through Laravel, not the `mysql` CLI.** `docker exec … mysql` negotiates a latin1
   connection, which mangles Arabic on both insert and query: it reported 0 matches for a
   two-character term *and* for a four-character one, while a three-character term matched. That is a
   charset artifact, not an index problem, and it will send you chasing the wrong bug.

   ```bash
   php artisan --env=testing tinker --execute="
     echo DB::table('hadith_texts')
       ->whereRaw('MATCH(search_body) AGAINST (? IN BOOLEAN MODE)', ['من'])
       ->count();
   "
   ```

   If you must use the CLI, pass `--default-character-set=utf8mb4`.

### Procedure — production

Same three steps, in the server's own `my.cnf` (or the managed instance's parameter group), applied
**before** the first import. Applying it afterwards requires the same index rebuild across ~51,400
rows.

The `ngram` parser must **not** be used: it is CJK-oriented and wrong for Arabic.

---

## 2. Schema application (T056) — DONE

Applied to `the_last_messenger_test` **and** `the_last_messenger` on 2026-07-30. Both verified:

| Check | Result |
|---|---|
| `hadith_*` tables | **8** |
| `hadith_hadiths` → `hadith_books (id, collection_id)` | present, composite |
| `hadith_hadiths` → `hadith_chapters (id, book_id)` | present, composite |
| FULLTEXT indexes on `hadith_texts` | exactly one — `ft_hadith_texts_search (search_body)` |
| sunnah.com identifiers in migrations (T057) | none |

Applied per migration path, never `migrate:fresh` — the command used:

```bash
php artisan migrate --path=database/migrations/2026_07_31_000001_create_hadith_collections_table.php \
                    --path=database/migrations/2026_07_31_000002_create_hadith_books_table.php \
                    --path=database/migrations/2026_07_31_000003_create_hadith_chapters_table.php \
                    --path=database/migrations/2026_07_31_000004_create_hadith_scholars_table.php \
                    --path=database/migrations/2026_07_31_000005_create_hadith_hadiths_table.php \
                    --path=database/migrations/2026_07_31_000006_create_hadith_texts_table.php \
                    --path=database/migrations/2026_07_31_000007_create_hadith_gradings_table.php \
                    --path=database/migrations/2026_07_31_000008_create_hadith_import_runs_table.php
```

Roll back **by explicit migration name only** — never `--step=N`, which counts migrations rather than
tables and will unwind unrelated work:

```bash
php artisan migrate:rollback --path=database/migrations/2026_07_31_000008_create_hadith_import_runs_table.php
```

`the_last_messenger` also has unrelated pending migrations (sahaba consolidation, home sections) that
predate this feature. `--path` keeps this feature's application independent of them.

---

## 3. Migration blocker found and cleared

`2026_06_24_100000_consolidate_genealogy_into_sahaba` could not run on a fresh database: it backfilled
`sahaba_id` onto `hadith_companions` and `hadith_narrators` by name matching, and both tables are
dropped by this rebuild. `linkHadithRoles()` and `deduplicateLinkedHadithRoles()` are now no-ops
alongside the already-neutered `addHadithLinks()`, and four helpers that became unreachable
(`deduplicateRole`, `buildSahabaNameIndex`, `resolveUniqueSahabi`, `indexExists`) were removed.
`hadith_scholars` declares `sahaba_id` UNIQUE in its own create migration.

---

## 4. Measurements still to record

- `EXPLAIN` for the search query (T141) and the five hot paths (T192)
- Per-collection reconciliation, expected vs actual (T178)
- The 20-record cross-check against `prophet-data-generator/hadith/production/` (T177)
