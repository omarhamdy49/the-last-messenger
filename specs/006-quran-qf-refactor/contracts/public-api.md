# Contract: Public API `v1.quran.*` (breaking change, same version)

Auth (all `/api/v1` routes except `GET /health`): headers `X-Api-Key` + `X-Api-Secret`
validated by the `api.client` middleware, then `api.scope:quran`; user-bound routes add
Sanctum bearer + `api:access` after it. Rate limits: `throttle:api_client` (per-client
`rate_limit_per_minute`, default 60), search routes `throttle:search`, mutation routes
`throttle:api_mutations`. Responses carry `X-RateLimit-Limit` / `X-RateLimit-Remaining`.
CORS: `config/cors.php` (`CORS_ALLOWED_ORIGINS`) plus the client's `allowed_origins`;
`supports_credentials = false`.
Envelope: `{success, data, message, meta}`; `data` carries the QF-shaped payload so a
client written against QF docs maps 1:1. All data is served from our database.
The old routes `quran.surahs`, `quran.surah`, `quran.by-revelation`, `quran.verses`,
`quran.verse`, `quran.juz`, `quran.page`, `quran.sajda`, `quran.random`, `quran.search`,
`quran.search.suggest` are **removed** (404).

## Routes (prefix `/api/v1/quran`)

| Route name | Method + path | Query | `data` |
|---|---|---|---|
| `v1.quran.chapters.index` | GET `/chapters` | `language` | `{chapters: [Chapter]}` |
| `v1.quran.chapters.show` | GET `/chapters/{id}` | `language` | `{chapter: Chapter}` |
| `v1.quran.chapters.info` | GET `/chapters/{id}/info` | `language`, `resource_id`, `include_resources` | `{chapter_info: ChapterInfo\|null, resources?: [ResourceRef]}` |
| `v1.quran.verses.by-chapter` | GET `/verses/by_chapter/{n}` | verse params | `{verses: [Verse], pagination}` |
| `v1.quran.verses.by-page` | GET `/verses/by_page/{n}` | verse params + `mushaf` | same |
| `v1.quran.verses.by-juz` … `by-ruku` | GET `/verses/by_{juz\|hizb\|rub_el_hizb\|manzil\|ruku}/{n}` | verse params | same |
| `v1.quran.verses.by-range` | GET `/verses/by_range` | `from`, `to` (verse key or id) + verse params | same |
| `v1.quran.verses.by-key` | GET `/verses/by_key/{key}` | verse params | `{verse: Verse}` |
| `v1.quran.verses.random` | GET `/verses/random` | any single axis filter + verse params | `{verse: Verse}` |
| `v1.quran.sections.index` | GET `/{juzs\|hizbs\|rub_el_hizbs\|manzils\|rukus}` | | `{juzs: [Section]}` (key named per axis) |
| `v1.quran.sections.show` | GET `/{axis}/{n}` | | `{juz: Section}` |
| `v1.quran.pages.index` | GET `/pages` | `mushaf` | `{pages: [Page]}` |
| `v1.quran.pages.show` | GET `/pages/{n}` | `mushaf` | `{page: Page}` |
| `v1.quran.pages.lookup` | GET `/pages/lookup` | `mushaf`, one axis or `from`/`to` | `{total_page, lookup_range, pages: {n: {first_verse_key,last_verse_key,from,to}}}` |
| `v1.quran.scripts` | GET `/quran/verses/{script}` | axis filter or `verse_key` | `{verses: [{id, verse_key, <script column>}], meta: {filters}}` |
| `v1.quran.translations.full` | GET `/quran/translations/{resource}` | axis filter, `foot_notes`, `fields` | `{translations: [TranslationRow], meta: {translation_name, author_name, filters}}` |
| `v1.quran.tafsirs.full` | GET `/quran/tafsirs/{resource}` | axis filter, `page`, `per_page` | `{tafsirs: [TafsirRow], meta, pagination}` |
| `v1.quran.recitations.files` | GET `/quran/recitations/{resource}` | axis filter, `fields` | `{audio_files: [{verse_key,url,…}], meta: {reciter_name, recitation_style}}` |
| `v1.quran.resources.index` | GET `/resources/{translations\|tafsirs\|languages\|recitations\|chapter_reciters\|chapter_infos\|verse_media\|recitation_styles}` | `language` | `{<kind>: [ResourceRef]}` (`recitation_styles` → `{mujawwad, murattal, muallim}`) |
| `v1.quran.resources.info` | GET `/resources/{kind}/{id}/info` | | `{info: {id, info}}` |
| `v1.quran.chapter-recitations.index` | GET `/chapter_recitations/{resource}` | | `{audio_files: [ChapterAudioFile]}` |
| `v1.quran.chapter-recitations.show` | GET `/chapter_recitations/{resource}/{chapter}` | `segments` | `{audio_file: ChapterAudioFile + timestamps[]}` |
| `v1.quran.audio.timestamp` | GET `/audio/reciters/{resource}/timestamp` | `chapter_number` or `verse_key`, `word`, `word_from`, `word_to` | `{result: {timestamp_from, timestamp_to}}` |
| `v1.quran.audio.lookup` | GET `/audio/reciters/{resource}/lookup` | `chapter_number`/`verse_key`, `timestamp`, `segments` | `{result: {verse_key, timestamp_from, timestamp_to, duration, segments?}}` |
| `v1.quran.hadiths.references` | GET `/hadiths/{key}/references` | `language` | `{verse_key, verse_number, chapter_number, hadith_references: [HadithRef + local: {hadith_id, url}\|null]}` |
| `v1.quran.foot-notes.show` | GET `/foot_notes/{id}` | | `{foot_note: {id, text, language_name}}` |
| `v1.quran.search` | GET `/search` | `mode` (quick\|advanced), `query`, `page`, `size`, `translation_ids`, `exact_matches_only`, `highlight`, `navigationalResultsNumber`, `versesResultsNumber` | `{result: {navigation: [SearchHit], verses: [SearchHit]}, pagination}` |

Verse params: `language`, `words` (bool), `translations` (csv ids), `tafsirs` (csv ids),
`audio` (recitation id), `fields`, `word_fields`, `translation_fields`,
`tafsir_fields`, `page`, `per_page` (int 1–50, else 422).

## Shapes

`Chapter` `{id, revelation_place, revelation_order, bismillah_pre, name_simple, name_complex, name_arabic, verses_count, pages: [first,last], translated_name: {language_name, name}}`

`Verse` (compact by default) `{id, chapter_id, verse_number, verse_key, verse_index, juz_number, hizb_number, rub_el_hizb_number, ruku_number, manzil_number, page_number, sajdah_type, sajdah_number}` + requested `fields` (any script/glyph/image column) + `words?: [Word]` + `translations?: [{resource_id, resource_name, id, text, foot_notes?}]` + `tafsirs?: [{id, resource_id, name, language_name, text}]` + `audio?: {verse_key, url}`.

`Word` `{id, position, char_type_name, line_number, page_number, audio_url, location, <word_fields>, translation: {text, language_name}, transliteration: {text, language_name}}`

`Section` `{id, <axis>_number, surah_ruku_number?, verse_mapping, first_verse_id, last_verse_id, verses_count}` · `Page` `{id, page_number, verse_mapping, first_verse_id, last_verse_id, verses_count}`

`ResourceRef` `{id, name, author_name, slug, language_name, translated_name: {name, language_name}, style?, qirat?}` · `ChapterAudioFile` `{id, chapter_id, file_size, format, audio_url, timestamps?: [{verse_key, timestamp_from, timestamp_to, duration, segments?}]}`

`SearchHit` `{result_type, key, name, arabic?, isArabic, isTransliteration, highlighted?}`

## Status codes
200 · 401 (missing/invalid `X-Api-Key`/`X-Api-Secret`, `type: invalid_api_credentials`; or invalid bearer on user-bound routes) · 403 (`insufficient_scope`, `origin_not_allowed`, expired/revoked client) · 404 (unknown chapter/verse/resource/section, inactive resource) · 422 (bad `per_page`, malformed `verse_key`, unknown script, invalid csv ids, missing required search params) · 429 on search throttle.
