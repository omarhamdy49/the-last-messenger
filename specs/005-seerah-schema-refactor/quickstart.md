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
`--database=` here (dev was wiped this way on 2026-09-13). Production: same steps
in a maintenance window, after confirming the backup restores into a scratch
database.

```bash
# 1. Assert the target — abort if it is not the database you mean
php artisan tinker --execute='$db = DB::connection()->getDatabaseName();
  in_array($db, ["the_last_messenger"], true) || throw new RuntimeException("refusing: $db"); echo $db;'

# 2. Backup the Seerah tables only (FR-028)
mysqldump -h 127.0.0.1 -P 3308 -u root -p the_last_messenger \
  $(mysql -h 127.0.0.1 -P 3308 -u root -p -N -e "SHOW TABLES LIKE 'seerah\_%'" the_last_messenger) \
  > ~/seerah-backup-$(date +%F).sql

# 3. Drop the old Seerah tables and forget their migration rows (explicit names, no wildcards)
mysql -h 127.0.0.1 -P 3308 -u root -p the_last_messenger <<'SQL'
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS
  seerah_events_media, seerah_events_gallery,
  seerah_event_timeline_item_translations, seerah_event_timeline_items,
  seerah_participant_translations, seerah_participants,
  seerah_event_translations, seerah_events,
  seerah_event_type_translations, seerah_event_types,
  seerah_milestone_translations, seerah_milestones;
SET FOREIGN_KEY_CHECKS = 1;

DELETE FROM migrations WHERE migration IN (
  '2025_10_27_130000_create_seerah_events_table',
  '2025_10_27_130001_create_seerah_participants_table',
  '2026_03_09_200000_create_seerah_event_translations_table',
  '2026_03_09_200002_migrate_seerah_and_sahabi_translations_data',
  '2026_03_09_200003_drop_json_columns_from_seerah',
  '2026_06_10_100000_add_taxonomy_and_statistics_to_seerah_events_table',
  '2026_06_10_100001_create_seerah_event_timeline_items_table',
  '2026_06_11_250000_add_missing_unique_to_seerah_participant_translations',
  '2026_06_18_100000_create_seerah_event_types_table',
  '2026_06_18_100001_seed_event_types_and_link_seerah_events',
  '2026_06_18_100002_add_status_to_seerah_events_table',
  '2026_06_19_100000_create_seerah_milestones_table',
  '2026_06_19_100001_add_milestone_id_to_seerah_events_table',
  '2026_08_13_100000_add_lessons_and_sources_to_seerah_event_translations',
  '2026_08_15_120000_add_dispute_note_to_seerah_event_translations'
);

-- Polymorphic rows that pointed at the dropped events. SeerahEvent is not in the
-- morph map, so the stored type is the class name.
DELETE FROM favorites         WHERE favorable_type    = 'App\\Domain\\Seerah\\Models\\SeerahEvent';
DELETE FROM user_interactions WHERE interactable_type = 'App\\Domain\\Seerah\\Models\\SeerahEvent';
DELETE FROM content_schedules WHERE schedulable_type  = 'App\\Domain\\Seerah\\Models\\SeerahEvent';
SQL

# 4. Rebuild: only the two rewritten Seerah migrations should be pending
php artisan migrate --pretend | grep -i seerah
php artisan migrate

# 5. 2026_08_18_000003_add_location_foreign_keys already ran on this database, so
#    location_id is NOT re-added by step 4. Add it by hand, matching that migration:
mysql -h 127.0.0.1 -P 3308 -u root -p the_last_messenger -e "
  ALTER TABLE seerah_events
    ADD COLUMN location_id BIGINT UNSIGNED NULL AFTER sm_id,
    ADD CONSTRAINT seerah_events_location_id_foreign
      FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL;"

# 6. Lookup seed + cache
php artisan db:seed --class=SeerahEventTypeSeeder
php artisan cache:clear
```

Check afterwards: `SHOW COLUMNS FROM seerah_events` lists the 17 columns in
data-model.md (including `location_id`), and `php artisan migrate:status | grep -i seerah`
shows both Seerah migrations as Ran.
