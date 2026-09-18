# Contract: Admin routes and form payloads

Base `/su/portal/v1/admin/quran`, route file `routes/admin/quran.php`, names
`admin.quran.*`. Middleware `auth:admin` + `permission:view quran` on reads,
`permission:edit quran` on writes, `permission:sync quran` on sync/fetch/asset actions
(new permission seeded by `RolePermissionSeeder`). Every FormRequest rejects unknown
fields; `PUT`/`PATCH` are real methods or form-encoded `_method`. JSON is returned only
when `expectsJson()`. Old routes `admin.surahs.*`, `admin.verses.*`, `admin.tafseer.*`,
`admin.reciters.*`, `admin.reciter-profiles.*`, `admin.tafsir-resources.*`,
`admin.translation-resources.*`, `admin.revelation-contexts.*` are removed;
`admin.playlists.*` and `admin.quran.ai-inquiries.*` are kept unchanged.

## Route map

| Group | Routes |
|---|---|
| Chapters | `chapters.index`, `.show`, `.edit`, `.update`, `.search` |
| Verses | `verses.index` (filters `chapter`, `juz`, `page`, `sajdah`, `q`), `.show`, `.edit`, `.update`, `.lock` (PATCH toggle), `.bulk-lock` |
| Words | `verses.words.index` (per verse), `words.update` (glosses) |
| Sections | `sections.index/{type}`, `.show`, `sections.reimport` (POST) |
| Mushafs | `mushafs.index`, `.show` (page viewer `?page=`), `.update`, `.fonts` (POST download), `.resync` (POST) |
| Languages | resource `languages` (except show) + `.toggle` |
| Resources | `resources.index/{kind}`, `.create/{kind}`, `.store`, `.show`, `.edit`, `.update`, `.destroy`, `.toggle/{field}` (active\|default\|sync_enabled), `.reorder`, `.sync` (POST one resource snapshot), `.catalogue/{kind}` (QF picker: rows not yet imported), `.import-from-qf` (POST qf_id) |
| Reciter source actions (kinds recitation, chapter_reciter) | `resources.lookup` (POST, preview only), `.fetch` (POST `{data, audio, timestamps, download}`), `.fetch-status` (GET json) |
| Translation rows | `resources.translations.index` (per resource `?chapter=`), `.store`, `.update`, `.destroy`, `.import` (POST file) |
| Tafsir rows | `resources.tafsirs.*` same |
| Chapter infos | `resources.chapter-infos.index`, `.store`, `.update`, `.destroy` |
| Ayah audio files | `resources.audio-files.index` (`?chapter=`), `.store`, `.update`, `.destroy`, `.fill` (POST url template) |
| Chapter audio files | `resources.chapter-audio.index`, `.store`, `.update`, `.destroy`, `.timestamps.edit`, `.timestamps.update`, `.timestamps.import` |
| Hadith references | `verses.hadith-references.index`, `.store`, `.update`, `.destroy` |
| Reflections | `reflections.index`, `.show`, `.update`, `.destroy`, `.toggle` (published) |
| Articles | `articles.index`, `.show`, `.edit`, `.update`, `.destroy`, `.toggle` |
| Sync | `sync.index` (dashboard), `sync.runs.show`, `sync.bootstrap` (POST), `sync.incremental` (POST), `sync.assets` (POST `{images, fonts[]}`) |

## API clients (Settings hub, `admin.settings.api-clients.*`, permission `manage api clients`)
Routes: `index` (usage: last used, limit), `create`, `store` (response page shows the secret **once**), `edit`, `update`, `rotate` (POST → new secret shown once), `toggle` (active), `destroy`.
Payload (`store`/`update`): `name` required max 100; `scopes` required array of `*|quran|hadith|seerah|media|articles|user`; `allowed_origins` nullable array of valid origins (`scheme://host[:port]`, no path); `rate_limit_per_minute` integer 1–6000; `expires_at` nullable date after today; `is_active` boolean. `key` and `secret` are never writable.

## Payloads

### Chapter — `PUT chapters.update`
| Field | Rule |
|---|---|
| `name.{locale}` | `ar` required, string max 150 |
| `is_published` | boolean |
| `revelation_location_id` | nullable, exists `locations.id` |
| `cover` / `remove_cover` | nullable image ≤ 5 MB / boolean |

### Verse — `PUT verses.update`
`text_uthmani`, `text_uthmani_simple`, `text_uthmani_tajweed`, `text_imlaei`, `text_imlaei_simple`, `text_indopak`, `text_indopak_nastaleeq`, `text_qpc_hafs`, `text_qpc_nastaleeq` (nullable string; tajweed sanitised with `tajweed` tag allowed), `sajdah_type` (nullable in recommended|obligatory), `sajdah_number` (nullable int 1–15), `is_locked` (boolean). Saving any text sets `is_locked = true` unless the request sends `is_locked = false` explicitly.

### Word glosses — `PUT words.update`
`glosses[]` array; `.*.resource_id` exists (kind word_translation|word_transliteration), `.*.text` string max 500.

### Resource — `POST resources.store/{kind}` / `PUT resources.update`
| Field | Rule |
|---|---|
| `slug` | required on store, alpha-dash max 120, unique per kind |
| `name` | required, max 200 |
| `name_translations.{locale}`, `description_translations.{locale}` | nullable |
| `author_name` | nullable max 200 |
| `language_iso` | nullable, exists `quran_languages.iso_code` |
| `style` | nullable `Rule::enum(RecitationStyle)` (reciter kinds only) |
| `qirat` | nullable max 40 |
| `info` | nullable rich text |
| `image` / `remove_image` | nullable image ≤ 5 MB / boolean |
| `is_active`, `is_default`, `sync_enabled` | boolean |
| `display_order` | integer ≥ 0 |
| `external_source` | nullable `Rule::enum(ExternalSource)` (reciter kinds only) |
| `external_ref_id` | required_with `external_source`, max 50, unique `(kind, external_source, external_ref_id)` |
| `store_audio_locally` | boolean |
`kind` comes from the route, never from the body. `qf_id` is never writable.

### Reciter lookup — `POST resources.lookup` `{external_source, external_ref_id, kind}` → 200 `{name, style, qirat, files_count, has_timestamps, preview_urls[3]}` or 422 `{errors: {external_ref_id: [...]}}`.
### Reciter fetch — `POST resources.fetch/{resource}` `{data: bool, audio: bool, timestamps: bool, download: bool}` → 202 + `{run_id}`; `GET resources.fetch-status/{resource}` → `{status, report}`.

### Translation / tafsir / chapter-info rows
`verse_key` (required on store, exists), `chapter_id` (chapter infos), `text` (required rich text), `foot_notes` (nullable array of `{id: text}`, translations only), `short_text`, `source`, `language_iso` (chapter infos), `is_locked`. Import file: JSON `[{verse_key, text, foot_notes?}]` or CSV `verse_key,text`, ≤ 20 MB.

### Audio rows
Ayah: `verse_key`, `url` (url or upload `file` audio/mpeg ≤ max MB), `format`, `duration_ms`, `segments` (nullable JSON `[[int,int,int]]`). Chapter: `chapter_id`, `url`/`file`, `format`, `file_size`, `duration_ms`. Fill: `url_template` containing `{chapter:03}` or `{surah:03}{ayah:03}`. Timestamps: `timestamps` JSON `[{verse_key, from, to, segments?}]` — every verse of the chapter must be present once.

### Hadith reference — `collection` (max 40), `hadith_number` (max 20), `ayah_start`/`ayah_end` (ints within chapter), `position`, `is_active`.
### Reflection — `PUT`: `body` (rich text), `is_published`, `post_type`. Article — `title.{lang}`, `text.{lang}`, `is_published`.
### Sync — `sync.bootstrap` `{resources?: string}`; `sync.incremental` `{}`; `sync.assets` `{images: bool, fonts: [v1,v2,hafs,indopak]}`. All three dispatch `RunArtisanCommandJob` and redirect to `sync.index` with the run id.

## Responses
Store/update/destroy → 302 back with flash (`quran.flash.*` keys) or JSON `{success, data}` when `expectsJson()`. Validation → 422 (JSON) / redirect with errors. Missing permission → 403. Corpus create/delete routes do not exist (404).
