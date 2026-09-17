# Data Model: Quran Module Rebuild on the QF Content API

23 tables, all InnoDB / utf8mb4, `timestamps()` everywhere (project convention).
Identity rules: corpus tables use QF ids as PK (R4); editorial/catalogue tables use our
own PK + nullable `qf_id`. FULLTEXT indexes are MySQL-only (driver guard). Column order
below is the migration order. Enum-typed columns are `string` in the DB, cast to PHP
enums on the model.

## Corpus

### quran_chapters (PK = chapter number)
| Column | Type | Notes |
|---|---|---|
| id | unsignedSmallInteger PK | 1–114, QF id |
| revelation_place | string(10) | `RevelationPlace` (makkah/madinah) |
| revelation_order | unsignedSmallInteger | |
| bismillah_pre | boolean | |
| name_simple | string(100) | |
| name_complex | string(100) | |
| name_arabic | string(100) | |
| verses_count | unsignedSmallInteger | |
| first_page / last_page | unsignedSmallInteger | from `pages[0..1]` |
| revelation_location_id | FK → locations, nullable, nullOnDelete | added by the edited 003 migration |
| cover_image | string, nullable | public-disk path |
| is_published | boolean default true | |
Indexes: `revelation_place`, `revelation_order`. Model `Chapter` uses `HasLocalizedContent`.

### quran_chapter_translations
`id`, `chapter_id` FK cascade, `locale` string(5), `name` string(150); unique `(chapter_id, locale)`.

### quran_sections
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| type | string(12) | `SectionType`: juz, hizb, rub_el_hizb, manzil, ruku |
| number | unsignedSmallInteger | |
| surah_ruku_number | unsignedSmallInteger, nullable | ruku only |
| first_verse_id / last_verse_id | unsignedSmallInteger | |
| verses_count | unsignedSmallInteger | |
| verse_mapping | json | `{"2": "1-141"}` |
Unique `(type, number)`.

### quran_verses (PK = QF verse id)
| Column | Type | Notes |
|---|---|---|
| id | unsignedSmallInteger PK | 1–6236 |
| chapter_id | FK → quran_chapters, restrict | |
| verse_number | unsignedSmallInteger | |
| verse_key | string(8) unique | `"2:255"` |
| verse_index | unsignedSmallInteger | |
| juz_number, hizb_number, rub_el_hizb_number, ruku_number, manzil_number | unsignedSmallInteger | |
| page_number | unsignedSmallInteger | default mushaf |
| sajdah_type | string(12), nullable | recommended / obligatory |
| sajdah_number | unsignedTinyInteger, nullable | |
| image_url | string, nullable | remote |
| image_path | string, nullable | local copy |
| image_width | unsignedSmallInteger, nullable | |
| text_uthmani, text_uthmani_simple, text_uthmani_tajweed, text_imlaei, text_imlaei_simple, text_indopak, text_indopak_nastaleeq, text_qpc_hafs, text_qpc_nastaleeq | text, nullable | tajweed holds HTML |
| code_v1, code_v2 | text, nullable | glyph strings |
| v1_page, v2_page | unsignedSmallInteger, nullable | |
| is_locked | boolean default false | sync skips |
Unique `(chapter_id, verse_number)`; indexes on `juz_number`, `hizb_number`, `rub_el_hizb_number`, `ruku_number`, `manzil_number`, `page_number`, `sajdah_number`; FULLTEXT `qv_text_ft (text_uthmani_simple, text_imlaei_simple)`.

### quran_words (PK = QF word id)
| Column | Type | Notes |
|---|---|---|
| id | unsignedInteger PK | |
| verse_id | FK → quran_verses, cascade | |
| position | unsignedSmallInteger | 1-based in verse |
| location | string(16) unique | `"2:255:3"` |
| char_type | string(12) | `CharType`: word, end, pause, sajdah, rub_el_hizb |
| text_uthmani, text_uthmani_simple, text_indopak, text_imlaei, text_qpc_hafs | text, nullable | |
| code_v1, code_v2 | string(32), nullable | |
| v1_page, v2_page | unsignedSmallInteger, nullable | |
| page_number | unsignedSmallInteger | default mushaf |
| line_number | unsignedTinyInteger | |
| audio_url | string, nullable | absolute after import |
Index `(verse_id, position)`, `(page_number, line_number, position)`.

### quran_word_glosses (created after quran_resources)
`id`, `word_id` FK cascade, `resource_id` FK cascade, `kind` string(16) (translation|transliteration), `language_iso` string(5), `text` string(500), `priority` unsignedTinyInteger nullable, `qf_id` unsignedBigInteger nullable; unique `(word_id, resource_id)`; index `(resource_id, word_id)`.

## Mushaf layouts

### quran_mushafs (PK = QF mushaf id)
`id` unsignedSmallInteger PK, `name` string(100), `description` string, nullable, `pages_count` unsignedSmallInteger, `lines_per_page` unsignedTinyInteger, `default_font_name` string(20), `mapping_mode` string(20), `qirat_name` string(50) nullable, `font_version` string(10) nullable (v1|v2|hafs|indopak → font folder), `is_default` boolean, `is_active` boolean default false, `fonts_downloaded_at` timestamp nullable, `records_count` unsignedInteger default 0, `last_synced_at` nullable. Seeded rows: 1 QCF V2 (default), 2 QCF V1, 3/6/7 IndoPak, 4 Uthmani, 5 QPC Hafs, 19 QCF V4 Tajweed.

### quran_mushaf_pages
`id` bigint PK (QF page record id), `mushaf_id` FK cascade, `page_number` unsignedSmallInteger, `first_verse_id`, `last_verse_id`, `first_word_id` unsignedInteger, `last_word_id`, `verses_count`, `verse_mapping` json; unique `(mushaf_id, page_number)`.

### quran_mushaf_words
`id` bigint PK (QF record id), `mushaf_id` FK cascade, `word_id` FK → quran_words cascade, `verse_id` unsignedSmallInteger, `text` string(64) nullable, `char_type` string(12), `page_number` unsignedSmallInteger, `line_number` unsignedTinyInteger, `position_in_verse`, `position_in_line`, `position_in_page` unsignedSmallInteger, `css_class` string(64) nullable, `css_style` string(255) nullable; unique `(mushaf_id, word_id)`; index `mw_render_idx (mushaf_id, page_number, line_number, position_in_line)`.

## Catalogues & content

### quran_languages (PK = QF id)
`id` unsignedSmallInteger PK, `name` string(60), `native_name` string(60), `iso_code` string(10) unique, `direction` string(3), `translations_count` unsignedSmallInteger default 0, `is_active` boolean default false.

### quran_resources
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| kind | string(24) | `ResourceKind`: translation, tafsir, chapter_info, verse_media, recitation, chapter_reciter, word_translation, word_transliteration |
| qf_id | unsignedInteger, nullable | QF resource id (sync `resource_id`) |
| qf_content_id | unsignedInteger, nullable | `resource_content_id` |
| source | string(10) | `ResourceSource`: qf, manual |
| external_source | string(12), nullable | `ExternalSource`: qf, mp3quran (reciter kinds) |
| external_ref_id | string(50), nullable | QF recitation/reciter id or mp3quran read id |
| slug | string(120) | |
| name | string(200) | English/canonical name |
| author_name | string(200), nullable | |
| language_iso | string(10), nullable | |
| style | string(20), nullable | `RecitationStyle`: murattal, mujawwad, muallim |
| qirat | string(40), nullable | |
| info | longText, nullable | HTML blurb |
| image | string, nullable | public-disk path |
| is_active | boolean default false | visible publicly |
| is_default | boolean default false | per kind + language |
| display_order | unsignedSmallInteger default 0 | |
| sync_enabled | boolean default false | included in the sync filter |
| store_audio_locally | boolean default false | reciter kinds |
| records_count | unsignedInteger default 0 | |
| last_synced_at / last_fetched_at | timestamp, nullable | |
| last_fetch_report | json, nullable | `{files_found, written, skipped, failed[], finished_at}` |
| metadata | json, nullable | `unavailable_reason`, mp3quran moshaf payload, etc. |
Unique `(kind, qf_id)`, `(kind, slug)`, `(kind, external_source, external_ref_id)`; index `(kind, is_active, display_order)`, `(kind, language_iso, is_default)`. Model `Resource` uses `HasLocalizedContent`.

### quran_resource_translations
`id`, `resource_id` FK cascade, `locale` string(5), `name` string(200), `description` text nullable; unique `(resource_id, locale)`.

### quran_translations
`id` bigint PK, `resource_id` FK cascade, `verse_id` FK cascade, `qf_id` unsignedBigInteger nullable, `text` text, `foot_notes` json nullable (`{"12": "..."}`), `is_locked` boolean default false; unique `(resource_id, verse_id)`; index `(verse_id)`; FULLTEXT `qt_text_ft (text)`.

### quran_tafsirs
Same as translations with `text` longText and no `foot_notes`; FULLTEXT `qtf_text_ft (text)`.

### quran_chapter_infos
`id`, `resource_id` FK cascade, `chapter_id` FK cascade, `qf_id` nullable, `language_iso` string(10), `text` longText, `short_text` text nullable, `source` string(255) nullable, `is_locked`; unique `(resource_id, chapter_id)`.

## Audio

### quran_audio_files (ayah-by-ayah)
`id`, `resource_id` FK cascade (kind recitation), `verse_id` FK cascade, `qf_id` nullable, `url` string(500), `format` string(8) nullable, `duration_ms` unsignedInteger nullable, `segments` json nullable, `is_local` boolean default false; unique `(resource_id, verse_id)`.

### quran_chapter_audio_files
`id`, `resource_id` FK cascade (kind chapter_reciter), `chapter_id` FK cascade, `qf_id` nullable, `url` string(500), `file_size` unsignedBigInteger nullable, `format` string(8) default 'mp3', `duration_ms` nullable, `is_local` boolean default false, `has_timestamps` boolean default false; unique `(resource_id, chapter_id)`.

### quran_chapter_audio_timestamps
`id`, `chapter_audio_file_id` FK cascade, `verse_id` FK cascade, `timestamp_from` unsignedInteger, `timestamp_to` unsignedInteger, `duration` integer (may be negative in source), `segments` json nullable (`[[word_index, start_ms, end_ms]]`); unique `(chapter_audio_file_id, verse_id)`.

## Cross-content

### quran_verse_hadith_references
`id`, `verse_id` FK cascade, `qf_id` unsignedInteger, `collection` string(40), `hadith_number` string(20), `our_hadith_number` unsignedInteger nullable, `arabic_urn` unsignedInteger nullable, `english_urn` unsignedInteger nullable, `ayah_start` unsignedSmallInteger, `ayah_end` unsignedSmallInteger, `position` unsignedSmallInteger, `is_active` boolean default true; unique `(verse_id, qf_id)`; index `(collection, hadith_number)`.

### quran_reflections
`id`, `qf_post_id` unsignedBigInteger unique, `post_type` string(12) (`ReflectionPostType`: reflection, lesson), `author_name` string(120), `author_handle` string(120) nullable, `language_iso` string(10), `body` longText, `likes_count`, `comments_count` unsignedInteger default 0, `is_verified` boolean, `published_at` timestamp nullable, `is_published` boolean default false, `raw` json nullable. Index `(is_published, published_at)`.

### quran_reflection_references
`id`, `reflection_id` FK cascade, `chapter_id` FK cascade, `verse_from` unsignedSmallInteger, `verse_to` unsignedSmallInteger; index `(chapter_id, verse_from, verse_to)`.

### quran_articles / quran_article_translations
`quran_articles`: `id`, `qf_id` unsignedBigInteger unique, `slug` string(150) unique, `is_published` boolean default false. `quran_article_translations`: `id`, `article_id` FK cascade, `lang` string(10), `title` string(300), `text` longText; unique `(article_id, lang)`.

## Public API clients (Shared)

### api_clients
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| name | string(100) | |
| key | string(40) unique | random 32 chars, public identifier |
| secret_hash | string(64) | HMAC-SHA256(secret, APP_KEY); plain secret shown once |
| scopes | json | e.g. `["quran"]`, `["*"]` |
| allowed_origins | json, nullable | exact origins; null = env list only |
| rate_limit_per_minute | unsignedSmallInteger default 60 | |
| is_active | boolean default true | |
| expires_at | timestamp, nullable | |
| last_used_at | timestamp, nullable | touched ≤ once a minute |
| created_by | FK → users, nullOnDelete | |
Index `(is_active, expires_at)`. Model `App\Models\ApiClient` (app-level, like `User`); cached by key for 5 min under `api_client:{key}`, forgotten on every write.

## Sync bookkeeping

### quran_sync_states
`id`, `resources_filter` string(1000) unique (canonical), `sync_token` string(500) nullable, `last_sequence` unsignedBigInteger default 0, `last_run_at` timestamp nullable, `status` string(12) (idle|running|failed), `last_error` text nullable.

### quran_sync_runs
`id`, `mode` string(12) (bootstrap|incremental|snapshot|fetch), `resources_filter` string(1000) nullable, `started_at`, `finished_at` nullable, `mutations_applied`, `snapshots_fetched`, `rows_upserted`, `rows_deleted`, `rows_skipped_locked` unsignedInteger default 0, `errors` json nullable, `triggered_by` FK → users nullOnDelete. Index `(started_at)`.

## Kept features (rewritten FKs)

### quran_playlists (unchanged shape) / quran_playlist_items
`quran_playlist_items`: `id`, `playlist_id` FK cascade, `chapter_id` FK → quran_chapters cascade, `resource_id` FK → quran_resources nullOnDelete (kind chapter_reciter), timestamps; unique `(playlist_id, chapter_id)`.

### ai_inquiries / ai_inquiry_usages — untouched.

## Enums (PHP)
`RevelationPlace` (kept), `SectionType`, `CharType`, `Script` (11 values ↔ verse columns; `fromExternal()` accepts QF aliases like `text_uthmani`), `ResourceKind` (+ `syncGroup(): ?SyncResourceGroup`, `isReciter()`), `ResourceSource`, `ExternalSource`, `RecitationStyle`, `SyncMutationType` (7 values), `SyncResourceGroup` (8 values), `ReflectionPostType`, `SyncRunMode`.

## Validation rules (enforced in FormRequests + DTOs)
- `verse_key` matches `/^\d{1,3}:\d{1,3}$/` and exists.
- `kind` immutable after create; `external_ref_id` required when `external_source` set; unique per `(kind, external_source, external_ref_id)`.
- `is_default` allowed only when `is_active`; at most one default per `(kind, language_iso)` — enforced in the service, not the DB.
- Uploads: audio `audio/mpeg`, ≤ `quran.audio.max_file_mb`; images ≤ 5 MB; timestamps JSON validated against `[{verse_key, from, to, segments?}]` and verse keys must belong to the chapter.
- Text fields pass through `RichTextSanitizer` (tafsir/translation/chapter info/reflection bodies) with the `tajweed` tag allow-listed for verse text.

## State transitions
- Resource: `available` (catalogue row, `is_active=0, sync_enabled=0`) → `enabled` (`sync_enabled=1`) → `synced` (`records_count>0, last_synced_at`) → `active` (`is_active=1`, public) → `unavailable` (`RESOURCE_DELETE`, `is_active=0`, metadata reason).
- Sync state: `idle` → `running` → `idle` (token stored) | `failed` (token unchanged).
- Reflection: imported `is_published=0` → moderated `1`.
