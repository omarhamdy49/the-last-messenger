# Contract: QF import, sync and reciter-fetch pipeline

## Configuration (`config/quran.php`)
```php
'qf' => [
    'env'        => env('QF_ENV', 'production'),            // prelive | production
    'auth_url'   => env('QURAN_AUTH_API_URL', 'https://oauth2.quran.foundation/oauth2/token'),
    'api_url'    => env('QURAN_API_BASE_URL', 'https://apis.quran.foundation/content/api/v4/'),
    'reflect_url'=> env('QURAN_REFLECT_API_URL', 'https://apis.quran.foundation/quran-reflect/v1/'),
    'client_id'  => env('QURAN_API_KEY'), 'client_secret' => env('QURAN_API_SECRET'),
    'audio_base' => 'https://verses.quran.foundation/', 'fonts_base' => 'https://verses.quran.foundation/fonts/quran/hafs/',
    'rate_limit_per_second' => 5, 'timeout' => 30, 'token_early_refresh_seconds' => 30,
],
'mp3quran' => ['api_url' => 'https://www.mp3quran.net/api/v3/', 'rate_limit_per_second' => 2],
'mushaf' => ['default_id' => 1],
'audio'  => ['max_file_mb' => 80, 'disk' => 'public', 'path' => 'quran/audio'],
'assets' => ['images_path' => 'quran/verse-images', 'fonts_public_path' => 'fonts/quran'],
'defaults' => ['translations' => ['ar' => [], 'en' => [131], 'fr' => [], 'tr' => [], 'ur' => []], 'wbw_language' => 'en'],
'sync' => ['per_page' => 100, 'schedule' => '03:15'],
```

## Client behaviour (`Import/QuranFoundation/Client`)
- Headers `x-auth-token`, `x-client-id`, `Accept: application/json`.
- Throttle: sleep so that calls never exceed `rate_limit_per_second`.
- 401 → `TokenProvider::forget()` then retry once; second 401 → `QfApiException(unauthorized)`.
- 429 / 502 / 503 / 504 → retry ≤ 3 with backoff `min(2^n + jitter, 30 s)`, `Retry-After` honoured.
- 400 / 403 / 404 / 422 → `QfApiException` with `type` from the body, no retry.
- Response bodies are decoded with `JSON_THROW_ON_ERROR`; missing required keys raise `QfPayloadException` naming the endpoint and key.
- Never logs headers or tokens; logs `{endpoint, status, type, attempt}` on failure.

## Commands (all `queued => true` in `config/artisan-commands.php`, group `quran`)
| Command | Options | Gate (aborts unless true) |
|---|---|---|
| `quran:qf:doctor` | | prints capability table: token, content, hadith, reflect |
| `quran:qf:bootstrap` | `--only=`, `--skip=`, `--dry-run` | each step's gate |
| `quran:qf:import-structure` | `--dry-run` | 114 chapters × 5 locales, 30/60/240/7/558 sections, 604 pages, ≥ 100 languages |
| `quran:qf:import-verses` | `--from=1 --to=114 --scripts=all --dry-run` | 6,236 verses; per chapter `count == verses_count`; every script column non-null for every verse |
| `quran:qf:import-words` | `--from --to --language=ar,en,fr,tr,ur --dry-run` | words per verse ≥ 1; `end` marker per verse; total ≥ 77,430 |
| `quran:qf:import-catalogue` | `--kinds=all --with-info` | every kind returns ≥ 1 row; unchanged rows untouched |
| `quran:qf:sync` | `--bootstrap`, `--resources=`, `--per-page=100`, `--dry-run` | snapshot `records` count == rows written + skipped locked |
| `quran:qf:import-chapter-infos` | `--resource=` | 114 rows or an itemised list of chapters with `chapter_info: null` |
| `quran:qf:import-timestamps` | `--reciter=` | per chapter: timestamps count == chapter `verses_count` |
| `quran:qf:import-hadith-references` | `--from --to` | requires `doctor` hadith OK |
| `quran:qf:import-reflections` | `--verified-only --from --to` | requires `doctor` reflect OK |
| `quran:qf:download-assets` | `--images`, `--fonts=v1,v2,hafs,indopak`, `--force` | files written == expected − already present; failures itemised |
| `quran:reciter:fetch` | `{resource} --data --audio --timestamps --download` | resource has `external_source` + `external_ref_id` |
| `quran:ai-prune` | kept | |

Every command prints a reconciliation table (`expected / written / updated / unchanged /
skipped_locked / failed`) and exits non-zero when `failed > 0` or a gate fails; failures
are itemised (constitution X).

## Sync algorithm (`Sync/ContentSyncService`)
```
filter = canonical(enabled resources)                     # "chapter_recitations:159;mushafs:1;tafsirs:151;translations:19"
state  = SyncState::firstOrCreate(filter)
mode   = bootstrap if state.sync_token is null else incremental
loop:  page = GET /resources/sync?{bootstrap=true|sync_token}&resources=filter&per_page=100  (or cursor from next_page_url)
       for each mutation: MutationApplier->apply(m)     # transaction per resource
       if !page.has_more: state.sync_token = page.next_sync_token; break
       follow page.next_page_url (prefixed with api root)
run.record(counts)
```
`MutationApplier`: RESOURCE_CREATE|INVALIDATE → `SnapshotImporter::for(group)->replace(resource_id)`; RESOURCE_DELETE → deactivate + reason; ROW_CREATE|UPDATE → `upsert(record_type, record_key, data)` unless locked; ROW_DELETE → delete by key; RESOURCE_UPDATE → touch `last_synced_at`. Unknown `record_type` → counted in `errors[]`, never silently ignored.

Snapshot importers map records to tables: translations → `quran_translations`; tafsirs → `quran_tafsirs`; word_by_word_translations/transliterations → `quran_word_glosses` (resource kind from group); mushafs → `quran_mushafs` + `_pages` + `_words` (word rows must reference existing `quran_words.id`; unknown ids itemised); recitations → `quran_audio_files` (+ compatible `chapter_audio_file` rows routed to the chapter reciter whose `qf_id = audio_recitation_id`); chapter_recitations → `quran_chapter_audio_files`; articles → `quran_articles` + translations.

## Reciter fetch (`Import/QuranFoundation/Reciters`)
```php
interface ReciterFetcher {
    public function lookup(string $refId, ResourceKind $kind): ReciterPreview;      // name, style, qirat, files_count, has_timestamps, preview_urls
    public function data(string $refId, ResourceKind $kind): ReciterData;           // names (+translated), style, qirat, info, image url
    public function files(string $refId, ResourceKind $kind): iterable<AudioFileRow>; // chapter files or ayah files (url absolute)
    public function timestamps(string $refId, int $chapter): iterable<TimestampRow>; // from/to ms, segments
}
```
`QfReciterFetcher` uses `/resources/chapter_reciters` | `/resources/recitations`, `/chapter_recitations/{id}` | `/quran/recitations/{id}`, `/chapter_recitations/{id}/{c}?segments=true`. `Mp3QuranReciterFetcher` uses `reciters?language=ar|eng` (match `moshaf.id == refId`), `{server}/{chapter:03}.mp3`, `ayat_timing/read/{id}/{chapter}` (ms from `start_time`/`end_time`). `FetchReciterFromSourceJob` (queue `imports`, `$timeout = 3600`, `ShouldBeUnique` per resource) runs the selected steps, downloads when `store_audio_locally || download`, and writes `last_fetch_report`.

## Test fixtures
`tests/Fixtures/qf/*.json`: one trimmed real response per endpoint family (chapters, sections, pages, verses-by-chapter with words, quran-verses-uthmani, resources catalogues, sync bootstrap page, sync incremental page, snapshots per group, chapter recitation with segments, hadith references, reflect feed) + `tests/Fixtures/mp3quran/{reciters,ayat_timing}.json`. Every importer test uses `Http::fake()` with these files and asserts the reconciliation table, idempotency on second run, and lock behaviour.
