# Quickstart: Quran Module Rebuild on the QF Content API

## Prerequisites
- `.env`: `QURAN_API_KEY`, `QURAN_API_SECRET` (production, `content` scope), `QF_ENV=production`, `QUEUE_CONNECTION=database`, a queue worker with `--timeout=3600` (see `composer dev`).
- `php artisan quran:qf:doctor` prints `token OK · content OK · hadith OK|insufficient_scope · reflect OK|insufficient_scope`. Nothing else runs until `content OK`.

## Fresh environment
```bash
composer install && npm install
php artisan migrate                       # new quran_* shape directly
php artisan db:seed --class=QuranMushafSeeder --class=RolePermissionSeeder   # mushaf catalogue rows + "sync quran" permission
php artisan quran:qf:bootstrap            # structure → verses → words → catalogue (≈ 1,600 calls, ~10 min at 5 req/s)
# enable resources in the admin (Translations / Tafsirs / Chapter infos / WBW / Ayah recitations / Chapter reciters / Mushafs)
php artisan quran:qf:sync --bootstrap     # snapshots for every enabled resource
php artisan quran:qf:import-chapter-infos
php artisan quran:qf:import-timestamps    # every enabled chapter reciter
php artisan quran:qf:download-assets --images --fonts=v1,v2,hafs,indopak
npm run build
```
Expected counts after bootstrap (parity gates print them): chapters 114, chapter
translations 570, sections 30/60/240/7/558, pages 604, verses 6,236, words ≥ 77,430,
languages ≥ 100, resources ≥ 1 per kind.

## Public API access
```bash
php artisan api:client:create "Mobile app" --scopes=quran --origins=https://app.example.com --limit=120
# prints KEY and SECRET once — store them in the consumer's secret store
curl -s https://example.com/api/v1/quran/chapters -H "X-Api-Key: $KEY" -H "X-Api-Secret: $SECRET"
curl -s https://example.com/api/v1/quran/chapters                      # → 401 invalid_api_credentials
curl -s -X OPTIONS https://example.com/api/v1/quran/chapters -H "Origin: https://evil.example" -H "Access-Control-Request-Method: GET" -i | head -5   # no CORS headers
```
`.env`: `CORS_ALLOWED_ORIGINS=https://app.example.com,https://admin.example.com`.

## Verify
```bash
php artisan test --filter=Quran           # then the full suite
./vendor/bin/pint --test
composer deptrac                          # Quran DTO→Request baseline entries gone, nothing new
grep -rnE "quran_surahs|quran_reciters|quran_recording_sources|reciter_translations|everyayah|Mp3QuranCatalogue|RevelationContext|ReciterProfile|Domain\\\\Quran\\\\Application" \
  app routes resources config database tests   # expect: only Mp3QuranReciterFetcher hits
```
Manual walk **in Arabic first**, then English, light + dark: home → chapter grid →
surah (translation view: 2 translations + footnote popover + tafsir panel + chapter info;
mushaf view: QCF V2 page fonts from `/fonts/quran/v2/`, tajweed script, word popover +
word audio) → ayah reciter auto-advance → surah reciter with ayah highlight and
click-to-seek → juz / hizb / rubʿ / manzil / rukūʿ / page pages → reciters catalogues →
reciter page → search command bar + results page → random verse → playlists wizard →
Ask page. Admin: enable a translation from the catalogue → sync → row appears on the
public page; edit + lock one translation row → sync again → row unchanged and listed
under "skipped (locked)"; create a chapter reciter from mp3quran by id → Lookup →
Fetch audio → files listed → plays on the surah page.

## Existing environment (already ran the old migrations)
Edited migrations do not re-run. **Never** `migrate:fresh`, `--step`, or `--database=`
(memory: dev was wiped this way on 2026-09-13). Production: same steps in a maintenance
window after the backup restores into a scratch database; prod is resynced from dev per
the standing "purge + resync" practice rather than by compensating migrations.

```bash
# 1. Assert the target — abort if it is not the database you mean
php artisan tinker --execute='$db = DB::connection()->getDatabaseName();
  in_array($db, ["the_last_messenger"], true) || throw new RuntimeException("refusing: $db"); echo $db;'

# 2. Backup only the tables being replaced
mysqldump -h 127.0.0.1 -P 3308 -u root -p the_last_messenger \
  $(mysql -h 127.0.0.1 -P 3308 -u root -p -N -e "SHOW TABLES WHERE Tables_in_the_last_messenger LIKE 'quran\_%' OR Tables_in_the_last_messenger = 'reciter_translations'" the_last_messenger) \
  > ~/quran-backup-$(date +%F).sql

# 3. Drop by explicit name (FK checks off), delete their migration rows by explicit name
mysql -h 127.0.0.1 -P 3308 -u root -p the_last_messenger <<'SQL'
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS quran_playlist_items, quran_playlists, quran_reciter_aliases, quran_reciter_profile_translations,
  quran_reciter_profiles, quran_recording_sources, quran_recitations, reciter_translations, quran_reciters,
  quran_revelation_context_translations, quran_revelation_contexts, quran_words, quran_verse_tafsirs,
  quran_verse_translations, quran_tafsir_resources, quran_translation_resources, quran_verses, quran_surah_info, quran_surahs;
SET FOREIGN_KEY_CHECKS = 1;
DELETE FROM migrations WHERE migration LIKE '%quran%' AND migration NOT LIKE '%ai_inquir%';
DELETE FROM favorites WHERE favorable_type IN ('quran', 'reciter');
SQL

# 4. Migrate + bootstrap (fresh-environment steps above from `php artisan migrate`)
```
The `DROP TABLE` list is the exact set produced by `SHOW TABLES LIKE 'quran\_%'` on
2026-09-17 plus `reciter_translations`; re-run the `SHOW TABLES` first and reconcile
before executing. `ai_inquiries` / `ai_inquiry_usages` are **not** in the list.
