# Quickstart: Seerah Module & Schema Refactor

## Fresh environment

```bash
composer install && npm install
php artisan migrate            # reaches the target shape directly
php artisan db:seed            # includes SeerahEventTypeSeeder (11 types)
npm run build                  # required after any Blade/asset change
```

Milestones and events are authored in the admin portal
(`/su/portal/v1/admin/seerah-milestones`, `…/seerah-events`).

## Verify

```bash
php artisan test --filter=Seerah      # then the full suite: php artisan test
./vendor/bin/pint --test
composer deptrac                      # SeerahEvent → Article baseline entry must be GONE, nothing new added

# No live reference to anything removed (expect no output):
grep -rnE "seerah_event_type_id|seerah_milestone_id|display_order|order_index|is_pivotal|views_count|hijri_label_|related_article_id|timeline_items|seerah_participants|EventImportance|EventPhase|ParticipantRole|lessonsFor" \
  app/Domain/Seerah app/Http resources/views/admin/seerah-* resources/views/public/seerah routes database/factories/SeerahEventFactory.php tests
```

Manual walk, **in Arabic first**, then English, light + dark:
journey → milestone events → events directory (filters: milestone, type, era,
disputed, search) → event page (both layouts) with **zero events**, then with one
fully-populated bilingual event (thumb, cover, place, dispute note, key/value
details, sources, 3 gallery images, 1 media item). Reopen the event in the admin:
every value round-trips; save untouched → nothing blanked.

## Existing environment (already ran the old migrations)

Edited migrations do not re-run. **Never** `migrate:fresh`, `--step`, or
`--database=` here (dev was wiped this way on 2026-09-13).

```bash
# 1. Assert the target — abort if it is not the database you mean
php artisan tinker --execute='$db = DB::connection()->getDatabaseName();
  in_array($db, ["the_last_messenger"], true) || throw new RuntimeException("refusing: $db"); echo $db;'

# 2. Backup the Seerah tables only (FR-028)
mysqldump -h 127.0.0.1 -P 3308 -u root -p the_last_messenger \
  $(mysql -h 127.0.0.1 -P 3308 -u root -p -N -e "SHOW TABLES LIKE 'seerah\_%'" the_last_messenger) \
  > ~/seerah-backup-$(date +%F).sql

# 3. Drop the Seerah tables by name + forget their migration rows
#    (SET FOREIGN_KEY_CHECKS=0; DROP TABLE IF EXISTS seerah_…  — the 11 old tables, listed explicitly;
#     DELETE FROM migrations WHERE migration IN (… the Seerah migration names, listed explicitly …);
#     ALTER TABLE favorites / content_schedules: delete rows whose morph type is SeerahEvent)

# 4. Rebuild
php artisan migrate
php artisan db:seed --class=SeerahEventTypeSeeder
php artisan cache:clear        # only if SeerahCacheKeys version bump is not enough; never Cache::flush() in code
```

The exact table and migration-name lists for step 3 are produced by the
implementation task that finalises the migration files, so they cannot drift.
Production: same steps in a maintenance window, after confirming the backup
restores into a scratch database.
