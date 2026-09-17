# Quran module full refactor — aligned to Quran Foundation Content API v4

**Status:** PLAN ONLY. Nothing implemented. User decisions captured 2026-09-17:
- Full refactor: forget the existing backend. **Keep only the public Quran pages** (Blade) and enhance them.
- Keep **Ask-Quran AI** and **visitor playlists** (re-homed on the new schema). **Live channels moves to the Media module.** Everything else in the old Quran domain is rebuilt or dropped (Asbab al-Nuzul, reciter-profile merging, mp3quran/everyayah/Wikidata importers are dropped).
- Audio: **Quran Foundation (QF) only + manual uploads**. Added 2026-09-17: the admin reciter create form also takes a **source (QF | mp3quran) + reference id** so a single reciter's data and audio files can be fetched on demand (manual action, no bulk mp3quran importer).
- Rollout: **fresh rebuild, no data carried** (allowlist-guarded drop, then re-import from QF **production**).
- **All data is extracted and stored locally** (text, words, glosses, mushaf layouts, catalogues, timestamps, verse images, fonts). Public endpoints and pages are served **only from our DB** — no runtime calls to QF. **Audio files stay remote-streamed** (QF/quranicaudio URLs) unless an admin uploads a file.
- Every Content API capability is in scope (user: "include all thing in content api").
- Added 2026-09-17: **every public API route is secured by an API key + secret, per-client rate limiting, and CORS configuration** (see C6.1).

**Source analysed (Chrome, 2026-09-17):** all 96 pages under `api-docs.quran.foundation/docs/content_apis_versioned/`, quickstart (manual auth, token management, first call), font-rendering tutorial, Search API v1 page. Codebase inventory by an Explore agent (28 quran migrations, ~220 domain files, 12 admin controllers, 3 site controllers, 1 API controller, 16 commands, 15 public views + 11 components).

**Proposed spec folder:** `specs/006-quran-qf-refactor/` (`spec.md`, `plan.md`, `data-model.md`, `research.md`, `quickstart.md`, `contracts/{admin-forms,public-api}.md`, `tasks.md`) — same layout as `specs/005-seerah-schema-refactor/`, with the Constitution Check table (`.specify/memory/constitution.md`).

---

## Part A — What the Quran Foundation Content API offers (feature inventory)

### A0. Access model (drives the importer design)
- OAuth2 **client_credentials**, scope `content`. `POST {auth}/oauth2/token`, HTTP Basic `client_id:client_secret`, body `grant_type=client_credentials&scope=content`. Token TTL 3600 s, **no refresh token**; re-request ~30 s before expiry; single in-flight refresh; on 401 clear + retry once; 429/5xx bounded backoff with jitter; 403 = wrong env/scope → never loop.
- Every call sends `x-auth-token` + `x-client-id`. Server-only.
- Envs: prelive `https://prelive-oauth2.quran.foundation` / `https://apis-prelive.quran.foundation` (dataset = surah 1–2 only); **production** `https://oauth2.quran.foundation` / `https://apis.quran.foundation`. Content base `/content/api/v4`. Our `config/services.php` already points at production and uses `QURAN_API_KEY` / `QURAN_API_SECRET` — reuse those env names.
- Uniform error body `{message, type, success:false}`; `type` ∈ gateway_timeout, service_unavailable, bad_gateway, internal_server_error, unprocessable_entity, not_found, forbidden, unauthorized, invalid_request, invalid_token, insufficient_scope, service_error, invalid_path, rate_limit_exceeded.
- Sibling specs, same auth, other scopes: Search API (scope `search`, `/api/v1/search?mode=quick|advanced`), Quran Reflect posts (scopes `post.read`, `comment.read`, gateway `/quran-reflect/v1/…`).

### A1. Structural navigation — the 9 axes
Each axis has list + single endpoints returning `verse_mapping {chapter: "from-to"}`, `first_verse_id`, `last_verse_id`, `verses_count`; every content endpoint filters by any axis.

| Axis | Range | Endpoints | Extra fields |
|---|---|---|---|
| Chapter | 1–114 | `/chapters`, `/chapters/{id}`, `/chapters/{id}/info` | `revelation_place` makkah/madinah, `revelation_order`, `bismillah_pre`, `name_simple`, `name_complex`, `name_arabic`, `verses_count`, `pages [first,last]`, `translated_name` (per `language`, falls back to en) |
| Juz | 1–30 | `/juzs`, `/juzs/{id}` | `mushaf` param |
| Hizb | 1–60 | `/hizbs`, `/hizbs/{id}` | |
| Rubʿ al-Ḥizb | 1–240 | `/rub_el_hizbs`, `/rub_el_hizbs/{id}` | |
| Manzil | 1–7 | `/manzils`, `/manzils/{id}` | |
| Rukūʿ | 1–558 | `/rukus`, `/rukus/{id}` | `surah_ruku_number` |
| Page (mushaf-aware) | 1–604 (default mushaf) | `/pages`, `/pages/{n}`, `/pages/lookup` | `mushaf` param; lookup maps any axis or `from`/`to` → page → verse range |
| Range | any | `/verses/by_range?from=&to=` | |
| Key | `s:v` | `/verses/by_key/{key}`, `/verses/random` (accepts any axis filter) | |

### A2. Verse payload — `/verses/by_{chapter|page|juz|hizb|rub_el_hizb|manzil|ruku|range|key}`, `/verses/random`
Params: `language` (word-by-word language), `words`, `translations` (csv resource ids; **57 = full-ayah transliteration**), `tafsirs` (csv), `audio` (ayah-recitation id), `fields`, `word_fields`, `translation_fields`, `tafsir_fields`, `page`, `per_page ≤ 50`, `mushaf` (by_page).

Verse: `id`, `chapter_id`, `verse_number`, `verse_key`, `verse_index`, `juz_number`, `hizb_number`, `rub_el_hizb_number`, `page_number`, `ruku_number`, `manzil_number`, `sajdah_type`, `sajdah_number`, `image_url`, `image_width`, scripts `text_uthmani`, `text_uthmani_simple`, `text_uthmani_tajweed` (HTML `<tajweed class=…>`), `text_imlaei`, `text_imlaei_simple`, `text_indopak`, `text_indopak_nastaleeq`, `text_qpc_hafs`, `text_qpc_nastaleeq`; glyphs `code_v1`, `code_v2`, `v1_page`, `v2_page`.
Nested: `words[]` {id, position, text_uthmani/indopak/imlaei/qpc_hafs, verse_key, page_number, line_number, audio_url (`wbw/001_001_001.mp3`), location, char_type_name (word|end|pause|sajdah…), code_v1/code_v2, v1_page/v2_page, translation{text,language_name}, transliteration{text,language_name}}; `audio {verse_key,url}`; `translations[]` {resource_id, resource_name, id, text (HTML with `<sup>` footnotes), language_id/name, foot_notes{id:text}}; `tafsirs[]` {id, resource_id, name, language_name, text (HTML)}; `pagination`.

### A3. Raw script endpoints — `/quran/verses/{script}` (+ legacy per-script routes)
`script` ∈ uthmani, uthmani_simple, uthmani_tajweed, indopak, indopak_nastaleeq, imlaei, imlaei_simple, qpc_hafs, qpc_nastaleeq, code_v1, code_v2, v1_image. Any axis filter or `verse_key`. **Whole Quran in one response** → the bulk path for Arabic text.

### A4. Translations, tafsirs, chapter info, languages, footnotes, verse media
- Catalogues `/resources/translations`, `/resources/tafsirs`, `/resources/chapter_infos`, `/resources/verse_media` → `{id, name, author_name, slug, language_name, translated_name}`; `/resources/{translations|tafsirs|recitations}/{id}/info` → HTML blurb.
- Full text: `/quran/translations/{id}` (whole Quran, `foot_notes=true`), `/quran/tafsirs/{id}` (paginated ≤50). Per-axis: `/translations/{id}/by_*`, `/tafsirs/{id}/by_*`. `/foot_notes/{id}`.
- `/resources/languages?language=` → {id, name, native_name, iso_code, direction, translations_count}.
- `/chapters/{id}/info?resource_id=&include_resources=true` → {chapter_id, text (HTML), short_text, language_name, source, resource_id} + `resources[]` that actually have content.

### A5. Audio — two independent ID spaces (never mixed)
- **Ayah-by-ayah recitations** `/resources/recitations` {id, reciter_name, style Murattal|Mujawwad|Muallim, translated_name}, `/resources/recitation_styles`; files `/quran/recitations/{id}?<axis>&fields=segments,duration,format` → `audio_files[]{verse_key,url}` + `meta{reciter_name,recitation_style}`; per-axis paginated `/recitations/{id}/by_*`. Relative URLs → prefix `https://verses.quran.foundation/`.
- **Chapter reciters** `/resources/chapter_reciters` {id, name, style{name}, qirat{name}, translated_name}; `/chapter_recitations/{reciter_id}` → 114 files {id, chapter_id, file_size, format, audio_url}; `/chapter_recitations/{reciter_id}/{chapter}?segments=true` → `timestamps[]{verse_key, timestamp_from, timestamp_to, duration, segments[[word_index,start_ms,end_ms]]}`.
- `/audio/reciters/{id}/timestamp?chapter_number|verse_key&word|word_from,word_to`, `/audio/reciters/{id}/lookup?timestamp=&segments=` (karaoke + click-to-seek).

### A6. Content Sync (keeps a local copy fresh)
- `/resources/sync?resources=group:id;…&bootstrap=true | sync_token=&per_page≤100` → `sync{sync_until_sequence, has_more, next_page_url, next_sync_token, mutations[]}`; mutation `{sequence, type, resource_group, resource_id, resource_content_id, record_type, record_key, source_record_id, changed_at, data, snapshot_url, unavailable_reason}`.
- `RESOURCE_CREATE`/`RESOURCE_INVALIDATE` → fetch `snapshot_url`, replace all rows; `RESOURCE_DELETE` → hide; `ROW_CREATE`/`ROW_UPDATE` → upsert by `(group, resource_id, record_type, record_key)`; `ROW_DELETE`; `RESOURCE_UPDATE` → freshness only.
- Groups: `mushafs`, `translations`, `tafsirs`, `recitations`, `chapter_recitations`, `word_by_word_translations`, `word_by_word_transliterations`, `articles`.
- `/resources/snapshots/{group}/{id}` → `{schema_version, sync_sequence, records[]}`. Record shapes: translation `{id, verse_key, text, foot_notes}`; tafsir `{id, verse_key, text}`; word_translation `{id, word_id, language_id, text, priority}`; word_transliteration `{id, word_id, text}` (resource 60 = 77,431 rows); **mushaf** → `mushaf{name, pages_count, lines_per_page, default_font_name, mapping_mode, qirat}`, `mushaf_page{page_number, first/last_verse_id, first/last_word_id, verses_count, verse_mapping}`, `mushaf_word{word_id, verse_id, text, char_type_name, page_number, line_number, position_in_verse/line/page, css_class, css_style}`; recitation `audio_file{recitation_id, verse_key, url, segments}` / `chapter_audio_file{audio_recitation_id, chapter_id, audio_url}`; article `{article_id, language_id, lang, title, text}`. Fonts/images are **not** in snapshots.
- `Cache-Control: no-store`; store `next_sync_token` per canonical filter, from the final page only.

### A7. Cross-content
- Hadith ↔ ayah: `/hadiths/{key}/references` (ordered {collection, hadith_number, our_hadith_number, arabic_urn, english_urn, surah/ayah range}), `/hadiths/{key}?language=ar|en&limit≤5` (bodies + grades), `/hadiths/count`.
- Quran Reflect: `/quran-reflect/v1/posts/feed` (`filter[references][0][chapterId|from|to]`, `filter[postTypeIds]` 1 reflection / 2 lesson, `tab=qdc` moderated, `filter[verifiedOnly]`), `/posts/{id}`.
- Search API: `mode=quick` (navigation + verses, `indexes=quran,translations`) / `mode=advanced` (`page`, `size`, `translation_ids`, `exact_matches_only`, `highlight`) → `result{navigation[], verses[]}` with `result_type` ∈ surah|juz|hizb|ayah|rub_el_hizb|search_page|page|range|quran_range.

### A8. Fonts & images (from the font tutorial; needed because everything is stored locally)
- Unicode: `https://verses.quran.foundation/fonts/quran/hafs/uthmanic_hafs/UthmanicHafs1Ver18.{woff2,ttf}`; IndoPak Nastaleeq `…/fonts/quran/hafs/nastaleeq/indopak/indopak-nastaleeq-waqf-lazim-v4.2.1.{woff2,woff,ttf}`.
- QCF per-page glyph fonts: `…/fonts/quran/hafs/{v1|v2}/{woff2|woff|ttf}/p{PAGE}.{ext}` (604 pages each). Mushaf ids: QCF V1 = 2, QCF V2 = 1, QCF V4 Tajweed = 19, QPC Hafs = 5, Uthmani = 4, IndoPak = 3/6/7.
- Verse images: `verse.image_url` (`//c22506.r6.cf1.rackcdn.com/{s}_{v}.png`, `image_width`).
- Word audio: `https://verses.quran.foundation/wbw/001_001_001.mp3` (stays remote — audio rule).

---

## Part B — Current state (what exists) and what happens to it

| Area | Today | Decision |
|---|---|---|
| Tables (23 `quran_*` + `reciter_translations` + `ai_*`) | 28 migrations, several no-op/irreversible; two generations of reciter model (recording × riwayah + profiles + aliases + recording sources) | **All `quran_*` and `reciter_translations` dropped and replaced.** `ai_inquiries`, `ai_inquiry_usages` untouched. |
| Domain `app/Domain/Quran` (~220 files: CQRS Application layer 83 files, 40 services, 5 importers, 3 audio providers, LLM/MCP infra) | Mixed quran.com v4, everyayah, mp3quran, Wikidata sources | **Rebuilt.** CQRS layer dropped (only Site controllers used it; every other module is service+repository). LLM/MCP infra (`Infrastructure/Llm`, `Infrastructure/Mcp`, Ask services) kept and re-pointed. |
| Commands (16) | quran.com/everyayah/mp3quran/wikidata | All deleted; replaced by QF importers + sync (see D4). `quran:ai-prune` kept. |
| Admin (12 controllers, ~35 views) | Surah/Verse/Tafseer/Reciter/ReciterProfile/RecordingSource/TafsirResource/TranslationResource/RevelationContext/Playlist/AiInquiry | Rebuilt per CRUD matrix (D5). Playlist + AiInquiry admin kept, adapted. |
| Public pages (`resources/views/public/quran/*`, `components/public/quran/*`, `components/quran/*`, shared `audio-player`) | 4.7k lines Blade + inline Alpine | **Kept**, fed by new services, enhanced (D7). `live.blade.php` moves to Media. `asbab.blade.php` deleted. |
| Site controllers (`QuranController` 1050 lines, `QuranPlaylistController`) | | `QuranController` split into thin controllers (Reader, Navigation, Reciters, Search, Ask). Playlist controller kept. |
| Public API (`Api/QuranController`, 11 routes) | own shapes | Rebuilt, QF-shaped, served from DB (D6). |
| Cross-module | Favorites (`FavorableType::QURAN`→Verse, `RECITER`→ReciterProfile), article embeds (`admin.articles.modules.surahs|verses`), Topics morph map, Locations FK `quran_surahs.revelation_location_id`, dashboard stats provider, export adapters, `config/artisan-commands.php` group | Each re-pointed (D8). |
| Tests (~30 files) | | Old Quran tests deleted with the code; new suite per constitution VI. |

---

## Part C — Target design

### C1. Identity rules
- **Corpus tables use QF ids as primary keys**: chapters 1–114, verses 1–6236, words (QF word id), mushafs, mushaf pages. Corpus is fixed → no admin create/delete, sync is a pure upsert.
- **Catalogue/resource rows use our own PK + `qf_id` (nullable, unique per kind) + `source` enum `qf|manual`** so admins can add manual resources (e.g. a self-hosted reciter) without colliding with QF ids.
- Verse identity in URLs/APIs stays `verse_key` (`"2:255"`), matching CLAUDE.md.
- Editorial edits on synced rows are protected by `is_locked` (row) — sync skips locked rows and reports them (constitution X).

### C2. Database schema (all new migrations, dated `2026_09_18_*`, driver-guarded, reversible)

Corpus
1. `quran_chapters` — `id` (1–114), `revelation_place` (enum cast `RevelationPlace`), `revelation_order`, `bismillah_pre`, `name_simple`, `name_complex`, `name_arabic`, `verses_count`, `first_page`, `last_page`, `revelation_location_id` FK→locations nullOnDelete (carried from 003), `cover_image` null, `is_published`, timestamps. + `quran_chapter_translations` (`chapter_id`, `locale`, `name`; unique) via `HasLocalizedContent`.
2. `quran_sections` — one table for juz/hizb/rubʿ/manzil/rukūʿ: `type` (enum `SectionType`), `number`, `surah_ruku_number` null, `first_verse_id`, `last_verse_id`, `verses_count`, `verse_mapping` json; unique `(type, number)`. (One table instead of five identical ones.)
3. `quran_verses` — `id`, `chapter_id` FK restrict, `verse_number`, `verse_key` unique, `verse_index`, `juz_number`, `hizb_number`, `rub_el_hizb_number`, `ruku_number`, `manzil_number`, `page_number`, `sajdah_type` null, `sajdah_number` null, `image_url` null, `image_path` null (local copy), `image_width` null, 9 script columns (`text_uthmani`, `text_uthmani_simple`, `text_uthmani_tajweed`, `text_imlaei`, `text_imlaei_simple`, `text_indopak`, `text_indopak_nastaleeq`, `text_qpc_hafs`, `text_qpc_nastaleeq`), `code_v1`, `code_v2`, `v1_page`, `v2_page`, `is_locked`, timestamps. Unique `(chapter_id, verse_number)`; indexes on each axis number + `page_number`; MySQL FULLTEXT `(text_uthmani_simple, text_imlaei_simple)`.
4. `quran_words` — `id` (QF), `verse_id` FK cascade, `position`, `location` unique (`"2:255:3"`), `char_type` (enum `CharType`), `text_uthmani`, `text_uthmani_simple`, `text_indopak`, `text_imlaei`, `text_qpc_hafs`, `code_v1`, `code_v2`, `v1_page`, `v2_page`, `page_number`, `line_number`, `audio_url` (remote), timestamps. Index `(verse_id, position)`, `(page_number, line_number, position)`.
5. `quran_word_glosses` — `word_id` FK, `resource_id` FK, `kind` (translation|transliteration), `text`, `priority` null; unique `(word_id, resource_id)`. Populated by sync groups `word_by_word_translations` / `word_by_word_transliterations`.

Mushaf layouts
6. `quran_mushafs` — `id` (QF), `name`, `description`, `pages_count`, `lines_per_page`, `default_font_name`, `mapping_mode`, `qirat_name`, `is_default`, `is_active`, `fonts_downloaded_at` null. Seed list from the tutorial table (1,2,3,5,6,7,19); imported via sync group `mushafs`.
7. `quran_mushaf_pages` — `mushaf_id`, `page_number`, `first_verse_id`, `last_verse_id`, `first_word_id`, `last_word_id`, `verses_count`, `verse_mapping` json; unique `(mushaf_id, page_number)`.
8. `quran_mushaf_words` — `mushaf_id`, `word_id`, `verse_id`, `text`, `char_type`, `page_number`, `line_number`, `position_in_verse`, `position_in_line`, `position_in_page`, `css_class`, `css_style`; unique `(mushaf_id, word_id)`; index `(mushaf_id, page_number, line_number, position_in_line)`.

Catalogues & content
9. `quran_languages` — `id` (QF), `name`, `native_name`, `iso_code` unique, `direction`, `translations_count`, `is_active`.
10. `quran_resources` — single catalogue for every resource kind: `kind` (enum `ResourceKind`: translation | tafsir | chapter_info | verse_media | recitation | chapter_reciter | word_translation | word_transliteration), `qf_id` null, `source` (qf|manual), `slug`, `name`, `author_name` null, `language_iso` null, `style` null (recitation/chapter_reciter), `qirat` null, `info` longtext null (from `/resources/*/{id}/info`), `image` null, `is_active`, `is_default`, `display_order`, `sync_enabled`, `records_count`, `last_synced_at`, `external_source` null (enum `ExternalSource`: qf | mp3quran), `external_ref_id` null (QF recitation/chapter-reciter id, or mp3quran `read_id`), `store_audio_locally` bool default false, `last_fetched_at` null, `last_fetch_report` json null, `metadata` json; unique `(kind, qf_id)`, unique `(kind, slug)`, unique `(kind, external_source, external_ref_id)`. + `quran_resource_translations` (`resource_id`, `locale`, `name`, `description`; unique) via `HasLocalizedContent`.
11. `quran_translations` — `resource_id` FK cascade, `verse_id` FK cascade, `qf_id` null, `text`, `foot_notes` json null, `is_locked`; unique `(resource_id, verse_id)`; FULLTEXT `(text)`.
12. `quran_tafsirs` — same shape as 11 with `text` longtext; FULLTEXT `(text)`.
13. `quran_chapter_infos` — `resource_id`, `chapter_id`, `qf_id` null, `language_iso`, `text` longtext, `short_text`, `source`, `is_locked`; unique `(resource_id, chapter_id)`.

Audio
14. `quran_audio_files` (ayah-by-ayah) — `resource_id` (kind recitation), `verse_id`, `qf_id` null, `url`, `format` null, `duration_ms` null, `segments` json null; unique `(resource_id, verse_id)`.
15. `quran_chapter_audio_files` — `resource_id` (kind chapter_reciter), `chapter_id`, `qf_id` null, `url`, `file_size` null, `format`, `has_timestamps`; unique `(resource_id, chapter_id)`.
16. `quran_chapter_audio_timestamps` — `chapter_audio_file_id` FK cascade, `verse_id`, `timestamp_from`, `timestamp_to`, `duration`, `segments` json null; unique `(chapter_audio_file_id, verse_id)`.

Cross-content
17. `quran_verse_hadith_references` — `verse_id`, `qf_id`, `collection`, `hadith_number`, `our_hadith_number`, `arabic_urn`, `english_urn`, `ayah_start`, `ayah_end`, `position`; unique `(verse_id, qf_id)`. Resolved to our Hadith rows at read time through a new Shared contract `HadithReferenceLookup` (implemented in the Hadith domain; keyed by collection slug + hadith number). No FK across modules.
18. `quran_reflections` — `qf_post_id` unique, `post_type` (reflection|lesson), `author_name`, `author_handle`, `language_iso`, `body` longtext, `likes_count`, `comments_count`, `is_verified`, `published_at`, `is_published` (admin moderation, default false); + `quran_reflection_references` (`reflection_id`, `chapter_id`, `verse_from`, `verse_to`). **Needs `post.read` scope** — phase 7, optional.
19. `quran_articles` — `qf_id` unique, `is_published`, timestamps + `quran_article_translations` (`article_id`, `lang`, `title`, `text`; unique). Sync group `articles`.

Sync bookkeeping
20. `quran_sync_states` — `resources_filter` (canonical string, unique), `sync_token` null, `last_sequence`, `last_run_at`, `status`, `last_error` null.
21. `quran_sync_runs` — `started_at`, `finished_at`, `mode` (bootstrap|incremental|snapshot), `resources_filter`, `mutations_applied`, `snapshots_fetched`, `rows_upserted`, `rows_deleted`, `rows_skipped_locked`, `errors` json, `triggered_by` FK users null.

Kept features (rebuilt FKs)
22. `quran_playlists` (unchanged) / `quran_playlist_items` → `chapter_id` FK→quran_chapters, `resource_id` FK→quran_resources (kind chapter_reciter) nullOnDelete, add timestamps.
23. `ai_inquiries`, `ai_inquiry_usages` untouched (they reference users/faqs; citations are verse keys).

Shared migrations to edit (they touch quran tables): `2025_10_31_075951_add_search_indexes_to_tables` (remove raw quran indexes), `2026_03_10_100003/100007/100008` phase-4 (remove `reciter_translations`), `2026_03_17_000001_add_composite_performance_indexes` (remove `idx_qvt_lang_verse`), `2026_08_17_120000_add_missing_hot_path_indexes` (remove tafsir FULLTEXT), `2026_08_18_000003_add_location_foreign_keys` (target `quran_chapters`). `SharedServiceProvider` location registry entry `quran_surahs.revelation_location_id` → `quran_chapters`.

**Storage estimate (production, 5 locales)**: verses 6,236 rows × 9 scripts; words ~83k; glosses ~77k × (5 translation langs + 1 transliteration) ≈ 460k; mushaf words ~83k × 7 mushafs ≈ 580k; translations ≈ 40 resources × 6,236 ≈ 250k; tafsirs ≈ 25 × 6,236 ≈ 156k (largest text, several GB with Tabari/Ibn Kathir — admin chooses which resources have `sync_enabled`); chapter timestamps ≈ 60 reciters × 6,236 ≈ 375k (+ segments JSON); ayah audio files ≈ 15 × 6,236 ≈ 94k; verse images 6,236 PNG ≈ 200 MB; QCF fonts 604 × 2 versions × woff2 ≈ 120 MB.

### C3. Domain module layout — `app/Domain/Quran/` (rebuilt)
```
Enums/          RevelationPlace (kept), SectionType, CharType, Script, ResourceKind, RecitationStyle,
                SyncMutationType, SyncResourceGroup, ReflectionPostType
Models/         Chapter, ChapterTranslation, Section, Verse, Word, WordGloss, Mushaf, MushafPage, MushafWord,
                Language, Resource, ResourceTranslation, Translation, Tafsir, ChapterInfo, AudioFile,
                ChapterAudioFile, ChapterAudioTimestamp, VerseHadithReference, Reflection, ReflectionReference,
                Article, ArticleTranslation, SyncState, SyncRun, Playlist, PlaylistItem, AiInquiry, AiInquiryUsage
DTOs/           final readonly: ChapterData, VerseEditData, ResourceData, TranslationRowData, TafsirRowData,
                ChapterInfoData, AudioFileData, ChapterAudioFileData, TimestampRowData, HadithReferenceData,
                ReflectionData, PlaylistData (kept), ReaderOptions (script, translations[], tafsirs[], wbw lang,
                recitation, chapter_reciter, mushaf), SearchOptions
Repositories/   interface + Eloquent impl per aggregate (Chapter, Section, Verse, Word, Mushaf, Language,
                Resource, Translation, Tafsir, ChapterInfo, Audio, HadithReference, Reflection, Article, Sync,
                Playlist (kept), AiInquiry (kept)) — every list query has explicit select; caching via QuranCacheKeys
Services/       ChapterService, NavigationService (axes, pages lookup, prev/next), ReaderService (builds the
                page payload the kept Blade views consume), VerseService, ResourceService, TranslationService,
                TafsirService, ChapterInfoService, AudioService (ayah + chapter + timestamps + manual upload),
                MushafService (page → lines → words, font manifest), SearchService (local quick/advanced),
                RandomVerseService, HadithReferenceService, ReflectionService, ArticleService,
                QuranCacheKeys (kept), QuranDashboardStatsProvider (rewritten), export adapters (rewritten),
                Playlist* (kept, adapted), Ask-AI services (kept, re-pointed to new repos)
Import/QuranFoundation/
                TokenProvider (cache + lock, 30 s early refresh), Client (headers, 401 retry-once, 429/5xx
                backoff+jitter, per-second rate limit, JSON error typing), Endpoints (typed methods for every
                A1–A7 endpoint), Mappers (Chapter, Section, Verse, Word, Resource, Language, ChapterInfo,
                AudioFile, ChapterAudio, Timestamp, HadithReference, Reflection, Article),
                Importers/ (ChaptersImporter, SectionsImporter, PagesImporter, VersesImporter [scripts via
                /quran/verses/{script}, metadata + sajdah via by_chapter], WordsImporter [by_chapter?words=true
                &word_fields=all], LanguagesImporter, CatalogueImporter [all resource kinds + info],
                ChapterInfosImporter, ChapterAudioTimestampsImporter, HadithReferencesImporter,
                ReflectionsImporter, AssetDownloader [verse images, fonts]),
                Reciters/ (ReciterFetcher contract + QfReciterFetcher [catalogue row, /chapter_recitations/{id}
                or /quran/recitations/{id}, timestamps+segments] + Mp3QuranReciterFetcher [reciters?language=,
                moshaf server + surah_list → 114 file URLs, ayat_timing/read/{id}/{surah} → timestamps],
                ReciterFetchService [runs a fetcher for one resource, optional local download of files to the
                public disk, writes last_fetch_report]),
                Sync/ (ContentSyncService: bootstrap/incremental paging, MutationApplier, SnapshotImporters per
                group: Translation, Tafsir, WordGloss, Mushaf, Recitation, ChapterRecitation, Article),
                ImportReport (counts reconciled + itemised skips — mirrors the Hadith Sunnah pipeline pattern)
Infrastructure/ Llm/*, Mcp/* (kept as-is)
```
Rules: no imports of other domains (deptrac); Hadith linking through `App\Domain\Shared\Contracts\HadithReferenceLookup`; Locations via existing Shared locations tables; media uploads via Shared `MediaLibraryLookup`/public disk like Seerah.

### C4. Import & sync pipeline (commands)
All commands are idempotent, chunked (500-row upserts in transactions inside repositories), dry-run capable, report reconciled counts, and are registered in `config/artisan-commands.php` under the `quran` group (queued). Scheduled in `routes/console.php`.

| Command | Source | Writes |
|---|---|---|
| `quran:qf:bootstrap {--only=} {--skip=} {--dry-run}` | orchestrator | runs the sequence below, stops on first failed count-parity gate |
| `quran:qf:import-structure` | `/chapters` (×5 languages for translated names), `/juzs`, `/hizbs`, `/rub_el_hizbs`, `/manzils`, `/rukus`, `/pages` (default mushaf), `/resources/languages` | chapters(+translations), sections, languages |
| `quran:qf:import-verses` | `/quran/verses/{script}` for all 11 scripts + `/verses/by_chapter/{n}?fields=…&per_page=50` for axis numbers/sajdah/image | verses (gate: 6,236 rows, per-chapter `verses_count` parity) |
| `quran:qf:import-words {--language=ar,en,fr,tr,ur}` | `/verses/by_chapter/{n}?words=true&word_fields=<all>&language=X` (125 pages × 114… ≈ 700 calls per language) | words (gate: word count vs mushaf snapshot `last_word_id`), per-language glosses fall back to the wbw sync groups when a resource exists |
| `quran:qf:import-catalogue` | `/resources/translations|tafsirs|chapter_infos|verse_media|recitations|chapter_reciters|recitation_styles` (+ `/{id}/info`) | `quran_resources` rows (all inactive/`sync_enabled=false` by default; admin enables) |
| `quran:qf:sync {--bootstrap} {--resources=} {--per-page=100}` | `/resources/sync` + `/resources/snapshots/*` for every resource with `sync_enabled` (filter built canonically from DB) | translations, tafsirs, word glosses, mushafs/pages/words, ayah audio files, chapter audio files, articles; updates `quran_sync_states`, writes `quran_sync_runs` |
| `quran:qf:import-chapter-infos` | `/chapters/{id}/info?resource_id=&include_resources=true` per enabled chapter_info resource | chapter infos |
| `quran:qf:import-timestamps {--reciter=}` | `/chapter_recitations/{r}/{c}?segments=true` (114 calls per chapter reciter) | chapter audio timestamps |
| `quran:reciter:fetch {resource} {--data} {--audio} {--timestamps} {--download}` | the resource's `external_source` + `external_ref_id` (QF or mp3quran `https://www.mp3quran.net/api/v3/reciters`, `…/ayat_timing/read/{id}/{surah}`) | same as the admin manual actions; CLI twin of `FetchReciterFromSourceJob` |
| `quran:qf:import-hadith-references` | `/hadiths/{key}/references` (6,236 calls) | verse hadith references |
| `quran:qf:import-reflections {--verified-only}` | Quran Reflect feed per ayah, `tab=qdc` (needs `post.read`) | reflections (unpublished until moderated) |
| `quran:qf:download-assets {--images} {--fonts=v1,v2,hafs,indopak}` | verse `image_url`, font CDN patterns (A8) | `storage/app/public/quran/verse-images/`, `public/fonts/quran/` + `quran_mushafs.fonts_downloaded_at` |
| `quran:ai-prune` | kept | |

Scheduler: `quran:qf:sync` nightly; `quran:qf:import-catalogue` weekly (surfaces new QF resources in admin as "available to enable").

### C5. Admin panel — CRUD matrix (`/su/portal/v1/admin/quran/...`, routes file `routes/admin/quran.php`, permissions `quran.view|manage|sync`)
All index pages: server-side DataTable, filters, search; all forms: language tabs (5 locales), RTL logical properties, `__()` keys in all five `quran.php` lang files.

| Area | Index | Show | Create | Edit | Delete | Extras |
|---|---|---|---|---|---|---|
| Chapters | ✓ | ✓ (verses, infos, audio coverage) | — (fixed 114) | ✓ names/locale, publish, location, cover | — | reorder not applicable |
| Verses | ✓ filter chapter/juz/page/sajdah | ✓ all scripts, words, translations, tafsirs, audio, hadith refs | — (corpus) | ✓ scripts + lock | — | bulk lock/unlock |
| Words | per verse | — | — | ✓ glosses per resource | — | |
| Sections & Pages | ✓ per type | ✓ | — | — | — | read-only, "re-import" button |
| Mushafs | ✓ | ✓ page viewer (lines→words) | — | ✓ active/default | — | "download fonts", "resync" |
| Languages | ✓ | — | ✓ | ✓ | ✓ | toggle active |
| Resources (one controller, `kind` route default → 8 admin menus: Translations, Tafsirs, Chapter infos, Verse media, Ayah recitations, Chapter reciters, WBW translations, WBW transliterations) | ✓ | ✓ coverage stats | ✓ manual | ✓ names, author, language, style/qirat, info, image, order, default | ✓ (manual or hide QF) | toggle active/default/sync_enabled, reorder, "Sync now", "Import from QF" picker |
| Reciter create/edit (kinds recitation + chapter_reciter) — **source-backed reciter** | | | ✓ form adds: `external_source` select (Manual / Quran Foundation / mp3quran), `external_ref_id` input with "Lookup" (validates the id against the source and previews name, style, qirat, file count), `store_audio_locally` checkbox (default off = stream from source URLs) | ✓ same fields | | Manual actions on show page: **"Fetch data"** (names, style, qirat, info, image when available), **"Fetch audio files"** (114 chapter files or 6,236 ayah files as URLs; downloads to public disk when `store_audio_locally`), **"Fetch timestamps"** (QF segments / mp3quran ayat_timing). Each runs as a queued job (`FetchReciterFromSourceJob`), shows progress + last report (files found / written / skipped / failed), and is idempotent. |
| Translation rows | per resource+chapter | — | ✓ (manual) | ✓ text, footnotes, lock | ✓ | bulk import CSV/JSON |
| Tafsir rows | same | — | ✓ | ✓ | ✓ | |
| Chapter infos | per resource | ✓ | ✓ | ✓ | ✓ | |
| Ayah audio files | per recitation+chapter | — | ✓ URL or upload | ✓ | ✓ | bulk URL template fill |
| Chapter audio files | per reciter | ✓ timestamps | ✓ URL or upload | ✓ | ✓ | timestamps editor + JSON/CSV import, segments |
| Hadith references | per verse | — | ✓ | ✓ | ✓ | resolve status vs our Hadith DB |
| Reflections | ✓ | ✓ | — | ✓ | ✓ | publish toggle, verified filter |
| QF Articles | ✓ | ✓ | — | ✓ translations | ✓ | publish toggle |
| Sync dashboard | states, runs, per-resource freshness | run detail (mutations, skipped locked rows, errors) | | | | buttons: bootstrap, incremental, snapshot one resource, download assets |
| Playlists (kept) | ✓ | | | ✓ | ✓ | toggle public/featured, coverage per chapter |
| AI inquiries (kept) | ✓ | ✓ | | answer edit | ✓ | publish as FAQ |

Reuse: `resources/views/admin/layouts/*`, DataTable pattern, form builder, `language-tabs.js`, media picker; export adapters re-implemented for chapters/verses/translations/tafsirs/reciters (`ExportableModuleInterface`).

### C6. Public API (`/api/v1/quran/…`, Sanctum `api:access`, served from DB, QF-shaped)
`chapters`, `chapters/{id}`, `chapters/{id}/info`; `verses/by_chapter|by_page|by_juz|by_hizb|by_rub_el_hizb|by_manzil|by_ruku/{n}`, `verses/by_range`, `verses/by_key/{key}`, `verses/random` (params: `words`, `translations`, `tafsirs`, `audio`, `fields`, `word_fields`, `language`, `mushaf`, `page`, `per_page≤50`); `juzs`, `hizbs`, `rub_el_hizbs`, `manzils`, `rukus` (+ `/{n}`), `pages`, `pages/{n}`, `pages/lookup`; `quran/verses/{script}`, `quran/translations/{id}`, `quran/tafsirs/{id}`, `quran/recitations/{id}`; `resources/{translations|tafsirs|languages|recitations|chapter_reciters|chapter_infos|verse_media|recitation_styles}` (+ `/{id}/info`); `chapter_recitations/{id}`, `chapter_recitations/{id}/{chapter}?segments=`; `audio/reciters/{id}/timestamp`, `audio/reciters/{id}/lookup`; `hadiths/{key}/references`; `search?mode=quick|advanced`; `foot_notes/{id}` (served from translation `foot_notes` JSON). Resources: `ChapterResource`, `VerseResource` (conditional `words`, `translations`, `tafsirs`, `audio`), `WordResource`, `SectionResource`, `PageResource`, `QuranResourceResource`, `AudioFileResource`, `ChapterAudioFileResource`, `TimestampResource`, `HadithReferenceResource`, `SearchResultResource`. Old 11 routes removed. Each endpoint: tests for 200/401/404/422.

### C6.1. Public API security — API key + secret, rate limiting, CORS (applies to the whole `/api/v1` group)
- **Identity of the calling app**: `api_clients` table (Shared): `name`, `key` (32-char random, unique), `secret_hash` (HMAC-SHA256 of the secret with `APP_KEY`; the plain secret is shown once at creation), `scopes` json (`["quran"]`, `["*"]`, …), `allowed_origins` json, `rate_limit_per_minute`, `is_active`, `expires_at`, `last_used_at`, `created_by`, timestamps.
- **Middleware `api.client`** (`App\Http\Middleware\AuthenticateApiClient`): requires headers `X-Api-Key` + `X-Api-Secret`; looks the client up by key (cached 5 min), checks active/not expired, compares the secret with `hash_equals`, checks the request `Origin` against `allowed_origins` when the header is present, stores the client on the request, touches `last_used_at` at most once a minute. Failures → 401 `{success:false, type:"invalid_api_credentials"}`; origin mismatch → 403 `origin_not_allowed`. Secrets are never logged (header masked in the log context).
- **Middleware `api.scope:{scope}`**: 403 `insufficient_scope` unless the client has the scope or `*`. Quran routes use `api.scope:quran`.
- **Rate limiting**: `RateLimiter::for('api_client')` = `perMinute(client.rate_limit_per_minute)` keyed by client id (default 60, admin-editable per client); unauthenticated attempts are limited by IP (`perMinute(20)`) to stop key brute force; search routes additionally use `throttle:search`; mutation routes `throttle:api_mutations` (30/min). Every response carries `X-RateLimit-Limit/Remaining`; 429 uses the uniform error body with `Retry-After`.
- **CORS** (`config/cors.php`): `paths ['api/*']`, `allowed_origins` from `CORS_ALLOWED_ORIGINS` (csv env) — per-client origins are enforced by the middleware on top; `allowed_methods` GET/POST/PUT/PATCH/DELETE/OPTIONS; `allowed_headers` `Content-Type, Accept, Authorization, X-Api-Key, X-Api-Secret, X-Requested-With`; `exposed_headers` `X-RateLimit-Limit, X-RateLimit-Remaining, Retry-After`; `max_age 86400`; `supports_credentials false` (API-key clients send no cookies).
- **Stacking with user identity**: the existing Sanctum bearer (`auth:sanctum` + `abilities:api:access`) stays for user-bound routes (favourites, profile, register/login) and now runs *after* `api.client`. `GET /health` is the only anonymous route.
- **Management**: admin `Settings → API clients` (index with usage, create showing the secret once, edit scopes/origins/limit, rotate secret, revoke/enable), permission `manage api clients`; command `api:client:create {name} --scopes= --origins= --limit=` prints key + secret once. Tests: 401 missing/wrong key or secret, 403 wrong scope / wrong origin, 429 per-client limit, CORS preflight from an allowed and a disallowed origin.

### C7. Public pages — kept views, new features & changes (user asked for this list explicitly)
Global (all Quran pages)
- Script switcher: Uthmani, Uthmani simple, Imlaei, IndoPak, IndoPak Nastaleeq, QPC Hafs, **Tajweed-coloured** (CSS for `<tajweed class>` rules with a legend), **QCF V1/V2 mushaf fonts** (per-page `@font-face` loaded lazily from local `/fonts/quran/...`). Preference persisted in `localStorage` + `?script=`.
- Amiri/UthmanicHafs for Quran text per constitution; verse-end markers always Unicode font.
- Font-size / line-height controls, night mode already exists → keep.
- Every verse card: copy, share (deep link `#verse-2:255`), bookmark (favorites), play (ayah recitation), open tafsir/translation/word-by-word/hadith/reflections panels.
- Command-bar search (quick mode: chapters, juz, page, verse key, Arabic/translation text) with keyboard nav; full search page (advanced mode, translation filter, exact match, highlight, pagination).

`index.blade.php` (Quran home)
- Chapter grid with `name_arabic`/localized name, revelation place icon, verses count, page span, revelation order toggle (sort by mushaf order / revelation order).
- New navigation tabs: **Juz · Hizb · Rubʿ · Manzil · Rukūʿ · Page** (each lists sections with `verse_mapping` preview).
- "Verse of the day" / random verse block (new `RandomVerseService`, reuses `components/site/daily-verse`).
- Continue reading (last position from `localStorage`).
- Featured reciters + featured playlists rows (existing).

`surah.blade.php` + `_reader` + `_mushaf` + `components/public/quran/*`
- Reader modes: **Translation view** (verse cards) and **Mushaf view** (lines → words from `quran_mushaf_words`, mushaf selector: QCF V2 default, V1, IndoPak 15/16-line, Tajweed V4).
- **Word-by-word mode**: hover/tap word → translation + transliteration (per selected language), per-word audio play; word highlighting during playback from `segments`.
- Multiple simultaneous translations (multi-select from active resources), footnotes rendered as popovers (from `foot_notes` JSON), full-ayah transliteration toggle (resource 57).
- Multiple tafsirs per verse in the side panel (existing `tafseer-panel` generalised to resources); tafsir author info modal (`resources.info`).
- **Chapter info panel** ("عن السورة"): text from `quran_chapter_infos` in current locale with source attribution; resource switcher when >1.
- Audio: reciter picker split into **Ayah reciters** (verse-by-verse, auto-advance) and **Surah reciters** (single mp3 with **ayah highlight + auto-scroll + click-verse-to-seek** using timestamps; word karaoke when segments exist). Continue-from-verse, repeat verse/range, playback speed. Shared `audioPlayer` store extended with `timestamps`/`segments`.
- Sajdah markers, verse images (lightbox), hizb/rubʿ/juz boundary markers inline, page number markers.
- Hadith references panel per verse (resolved to our Hadith pages when present; otherwise collection + number).
- Reflections/lessons panel per verse (published QF Reflect posts) — phase 7.
- Prev/next chapter, jump-to-verse, breadcrumb with juz/page context.

`juz.blade.php`, `hizb.blade.php`, `page.blade.php` → generalised to one `section.blade.php` + `page.blade.php`
- New routes `/quran/rub/{n}`, `/quran/manzil/{n}`, `/quran/ruku/{n}` reuse the section template; page view is mushaf-aware (`?mushaf=`), shows page lines, prev/next page, page → chapters map.

`reciters.blade.php`, `reciter.blade.php`
- Two catalogues (ayah recitations, surah reciters) with style (Murattal/Mujawwad/Muallim) and qirat filters, favourites (`FavorableType::RECITER` now → `Resource`), reciter info blurb, per-chapter list with durations/file sizes and "play all", timestamps-available badge.

`ask.blade.php` (kept) — Ask-AI citations now link to `verse_key` anchors; verse snippets come from local `quran_verses`.
`playlists/*` (kept) — items reference chapters + chapter reciters; wizard reciter picker uses the new catalogue; `?reciter=` hand-off keeps working.
`live.blade.php` — moved to `resources/views/public/media/live.blade.php`, route `public.media.live` (`/media/live`), `/quran/live` 301.
`asbab.blade.php` — removed (no QF source; revelation contexts dropped).
New: `search.blade.php`, `random.blade.php` (redirects to the verse), `translation.blade.php` (a whole translation resource, chapter by chapter), `tafsir.blade.php` (a tafsir resource page).

### C8. Cross-module and cleanup
- `FavoriteServiceProvider`: `QURAN` → `Verse::class` (URL by `chapter_id`, fixing the current `revelation_order` bug); `RECITER` → `Resource::class` filtered to reciter kinds; allowlisted delete of orphaned `favorites` rows in the rollout runbook.
- Article editor module embeds (`ArticleModuleController@surahs|verses`, `ContentReferenceLookup`) → new Chapter/Verse repos; Quill blot unchanged (see memory: quill-module-embeds).
- Topics morph map: `quran_verse`/`quran_surah` aliases → new models.
- `SharedServiceProvider` locations registry → `quran_chapters.revelation_location_id`.
- Media module gains the Live page; `App\Domain\Media` already owns `LiveChannel`.
- Delete `config/quran-audio.php`, `config/quran-editions.php`; new `config/quran.php` (QF env/urls, rate limits, default mushaf, default resources per locale, asset paths, sync schedule).
- `deptrac.yaml`: add Quran layer rules (same as Hadith).
- Docs: update `CLAUDE.md` §6 (no CQRS, new tables, sync), `docs/TRANSLATION_PATTERNS.md` unchanged, new `docs/QURAN-QF-SYNC.md`.

---

## Part D — Delivery phases (each phase ends green: tests, pint, deptrac, `npm run build`)

**Phase 0 — Spec & baseline (1 PR)**: write `specs/006-quran-qf-refactor/*` from this plan; capture `baseline.txt` (route list, table list, test list); confirm QF **production** credentials work (`quran:qf:doctor` prints token OK + `/chapters` count 114, never prints secrets); ask QF for `post.read` scope (phase 7).
**Phase 1 — Foundation + corpus import**: `config/quran.php`, `Import/QuranFoundation/{TokenProvider,Client,Endpoints}` with `Http::fake` tests (401 retry-once, 429 backoff, error typing); new migrations 1–9, 20–21; models/enums; `import-structure`, `import-verses`, `import-words`, `import-catalogue`, `download-assets`; fixtures = trimmed real JSON from prelive/production; count-parity gates.
**Phase 2 — Content sync**: migrations 10–13; `ContentSyncService` + snapshot importers (translations, tafsirs, word glosses, mushafs, articles); `quran:qf:sync` bootstrap/incremental with `sync_token` persistence; locked-row skip reporting; chapter infos importer; scheduler.
**Phase 3 — Audio**: migrations 14–16; recitation/chapter-recitation snapshot importers; timestamps importer; manual upload path (public disk); `AudioService` URL resolution (relative QF → absolute); **source-backed reciter fetch** (`ReciterFetcher` contract, QF + mp3quran fetchers, `FetchReciterFromSourceJob`, `quran:reciter:fetch`, optional local download with size/mime checks); playlists re-homed.
**Phase 4 — Admin**: routes, permissions, controllers, FormRequests (typed DTOs), views per C5, sync dashboard, commands registered in `config/artisan-commands.php`, export adapters, dashboard stats provider; feature tests for every admin action (200/302/403/422).
**Phase 5 — Public read stack + pages + API**: repositories with explicit selects and cache keys; `ReaderService` payload for kept views; site controllers split; enhancements per C7 (script switcher, mushaf view, WBW, karaoke, multi-translation/tafsir, chapter info, search, section pages, reciters); public API per C6 with tests; `NoNPlusOneQueriesTest` coverage for every list route.
**Phase 6 — Cross-module & deletion**: C8 items; delete old domain/commands/admin/tests; Ask-AI re-pointed; Live moved; deptrac; CLAUDE.md/docs; memory note.
**Phase 7 — Cross-content (optional, scope-gated)**: hadith references (+ `HadithReferenceLookup` contract in Shared, implemented in Hadith), Quran Reflect reflections + moderation, QF articles page.
**Phase 8 — Rollout** (`quickstart.md` runbook, never `migrate:fresh`): dump → allowlist-guarded drop of `quran_*`, `reciter_translations`, orphan favorites → `php artisan migrate` → `quran:qf:bootstrap` on dev → verify counts (114 / 6,236 / words / per enabled resource) → prod resync per memory "prod DB resync over migrations" → `quran:qf:download-assets` → cache clear → Arabic RTL browser walkthrough of every public page.

## Verification (end-to-end)
- Importer tests with recorded fixtures: idempotent re-run = zero diff; parity gates fail loudly on missing rows; locked rows skipped and itemised.
- Sync tests: bootstrap paging, `has_more`, token stored only on final page, each mutation type applied, `RESOURCE_DELETE` hides resource, snapshot replace is transactional.
- API: 200/401/404/422 per endpoint; verse payload shape parity with QF examples (compact, with words/translations/tafsirs/audio).
- Admin: CRUD round-trips, "untouched section is not blanked", uploads validated (mime/size), permissions 403.
- Public: every kept page renders in `ar` + `en` with the new payload; N+1 guard; `npm run build`; manual walkthrough in Chrome (RTL, dark mode, mushaf fonts load from local path, karaoke highlight, WBW popover).
- Quality gates: `php artisan test`, `./vendor/bin/pint`, `composer deptrac`, migrations reversible on the test DB.

## Risks & assumptions
- QF production access and rate limits are not documented; the client throttles (configurable req/s) and the bootstrap is resumable per importer. Word import ≈ 700 calls/language; hadith refs 6,236 calls; timestamps 114 calls/reciter.
- Tafsir volume is large; only admin-enabled resources sync. Fonts ≈ 120 MB and verse images ≈ 200 MB on the public disk / CDN later.
- Quran Reflect and hadith endpoints need scopes we may not have yet → phase 7 is optional.
- Dropping Asbab al-Nuzul and reciter-profile merging is per user decision; old public favorites for reciters are removed in rollout.
- mp3quran comes back only as an **on-demand per-reciter fetcher** behind the same `ReciterFetcher` contract as QF (no catalogue import, no provider abstraction in the player). Its id space is kept apart by `external_source`; a reciter present in both sources is two resources unless an admin merges by hand. Local download is opt-in because a full chapter set is ~1 GB per reciter.
- Mushaf V4 tajweed uses `code_v2` with mushaf 19 per the tutorial; verify glyph coverage on import.
- CQRS removal deviates from the CLAUDE.md note "prefer the query stack"; it is recorded in the spec's Complexity Tracking and CLAUDE.md is updated.
