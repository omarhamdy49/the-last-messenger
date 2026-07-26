# Quran public UI parity and gap review
**Scope:** `quran-enhanced.html`, `quran-surah-enhanced.html`, `quran-recitors-enhanced.html`, `quran-asbab-nezol-enhanced.html`, `quran-live-enhanced.html`  ·  **Method:** static read of prototypes + Laravel code + migrations

## Executive summary

| Module | Prototype pages | Implemented | Verdict (OK / PARTIAL / MISSING) | Top gap |
|---|---:|---:|---|---|
| Quran index / browse | 1 | 1 | PARTIAL | Favorites and meaningful reading progress are absent; name/meaning data is only Arabic/English |
| Surah reader | 1 | 1 | PARTIAL | Canonical surah numbers are looked up through `revelation_order`, which can combine the wrong header/translation with the requested verses |
| Reciters | 1 | 1 | PARTIAL | Every public “listen” preview receives a null URL, and its popularity/recording metrics are fabricated or zero |
| Asbab al-Nuzul | 1 | 1 | PARTIAL | Timeline rows link through the wrong surah-number field and have no year/event-name schema matching the prototype |
| Quran Live | 1 | 1 UI hook | PARTIAL (DEFERRED) | The real implementation is a localized coming-soon page; streaming, chat, schedule, reactions, and channels remain deliberately deferred |

- **QUR-001 is release-blocking:** `app/Domain/Quran/Repositories/SurahRepository.php:59-68` — `getByNumber()` queries `revelation_order`, while `app/Domain/Quran/Application/DTOs/SurahDTO.php:45-52` and the sync path establish that canonical number is `id`; the reader then loads verses using the route number as `surah_id` at `app/Http/Controllers/Site/QuranController.php:341-350`.
- `storage/app/holy_quran_utf8.json:1` — a read-only static count found 114 source surahs and 6,236 `numberInSurah` entries; `config/islamic-data.php:48-52` declares the same verification floor. This does **not** prove the current DB has those rows (see Open questions).
- `database/seeders/QuranReciterSeeder.php:45-73` — the seeder defines the 11-reciter/per-surah import path; `database/seeders/QuranReciterSeeder.php:110-278` contains 11 folders and 11 surah-audio bases. The reader does not consistently use this data: public reciter preview URLs are forced to null at `app/Http/Controllers/Site/QuranController.php:656-669`.
- `app/Domain/Quran/Services/VerseAudioUrlResolver.php:13-25` — per-verse audio is convention-built from EveryAyah folders, not stored in the DB; `resources/views/public/quran/surah.blade.php:530-541` duplicates that URL convention in JavaScript and has no user-visible missing-file/error state.
- `resources/views/public/quran/surah.blade.php:394-409` and `resources/views/public/quran/surah.blade.php:499-508` — tafsir and translation switching are real server fetches, not client fakes, but failures are converted to empty content.
- `resources/views/public/quran/_mushaf.blade.php:23-38` — page/line/word Mushaf layout is implemented; `database/migrations/2026_06_17_000001_create_quran_words_table.php:21-40` supports layout only, not word meanings, morphology, tajweed, or audio timings.
- `routes/web.php:33-45` — `/quran/ask` is implementation beyond all five prototypes. Its mutations are authenticated/rate-limited, `app/Http/Requests/Site/AskQuranRequest.php:16-45` validates and authorizes input, and `app/Domain/Quran/Repositories/AiInquiryRepository.php:77-100` scopes history to the current user. The gap is a missing approved prototype/design, not missing authorization.
- `routes/web.php:30`, `resources/views/public/quran/live.blade.php:1-31`, and `../CLAUDE.md:52-57` — `/quran/live` is a genuine route and localized no-index coming-soon hook; the prototype’s stream/chat product is deferred and is not classified as P0.
- `TODO_quran-parity.md:25-26` claims the audio resolver is wired and audio is done, but the controller merely injects the resolver at `app/Http/Controllers/Site/QuranController.php:33-44`; the view builds URLs itself and silently fails. The “done” claim is materially overstated.

## Quran index / surah browse

### `quran-enhanced.html` → `GET /quran` → `resources/views/public/quran/index.blade.php`
**Status:** PARTIAL

**Page parity:** `routes/web.php:26-28` — `GET /quran` maps to `Site\QuranController@index`; `app/Http/Controllers/Site/QuranController.php:268-283` returns `public.quran.index`.

#### Sections & components

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Hero, four Quran statistics | Premium hero and four cards exist | Implemented, but values are hardcoded | `../the-last-messenger-ui/quran-enhanced.html:374-410` — prototype hero/stats; `resources/views/public/quran/index.blade.php:203-266` — implemented hero; `app/Http/Controllers/Site/QuranController.php:276-281` — fixed constants |
| Search | Search plus navigational autocomplete | Implemented beyond prototype | `../the-last-messenger-ui/quran-enhanced.html:415-427` — prototype search; `resources/views/public/quran/index.blade.php:277-349` — accessible combobox with loading/error/empty/retry |
| Makki/Madani filter | Segmented filter | Implemented | `../the-last-messenger-ui/quran-enhanced.html:429-452` — three filters; `resources/views/public/quran/index.blade.php:405-448` — implemented filter group |
| Grid/list toggle and sort | Persisted grid/list and number/name/verse sorting | Implemented | `../the-last-messenger-ui/quran-enhanced.html:454-484` — controls; `resources/views/public/quran/index.blade.php:352-399` — current controls |
| Overall progress summary | Prototype progress bar and percentage | Missing | `../the-last-messenger-ui/quran-enhanced.html:487-502` — overall progress; `resources/views/public/quran/index.blade.php:487-496` — only result count is rendered |
| Grid and list surah cards | Both layouts, type/verse/Juz metadata, per-card ring | Partially implemented | `../the-last-messenger-ui/quran-enhanced.html:505-648` — two card forms; `resources/views/public/quran/index.blade.php:501-638` — two current card forms, without favorite controls |
| Empty/reset state | Meaningful no-results state and reset | Implemented | `../the-last-messenger-ui/quran-enhanced.html:650-660` — prototype empty state; `resources/views/public/quran/index.blade.php:643-658` — current reset CTA |
| Juz/Hizb/page jump controls | Not in the prototype | Implemented beyond prototype | `resources/views/public/quran/index.blade.php:451-483` — real links to the three public reader routes |

#### Features & wiring

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Client search by Arabic/English/meaning/number | Searches Arabic, English, number; autocomplete searches server navigation data | PARTIAL — card filter omits transliteration and English meaning | `../the-last-messenger-ui/quran-enhanced.html:250-258` — includes `translation`; `resources/views/public/quran/index.blade.php:134-140` — only three fields |
| Autocomplete / command navigation | Real throttled JSON endpoint | Implemented | `resources/views/public/quran/index.blade.php:161-196` — fetch/keyboard routing; `routes/api.php:142-145` — suggestion endpoint has `throttle:search` |
| Filter, sort, view persistence | Client-side and persisted | Implemented | `resources/views/public/quran/index.blade.php:129-159` — real filtering/sorting/localStorage |
| Favorite surah | Prototype localStorage heart | Missing; generic server favorite supports verses, not a surah UI | `../the-last-messenger-ui/quran-enhanced.html:307-325` — prototype behavior; `resources/views/public/quran/index.blade.php:118-197` — no favorites state/action; `app/Providers/FavoriteServiceProvider.php:38-42` — Quran favorite maps to `Verse` |
| Reading progress | Current page reads `surahProgress`, but no Quran view writes it | PARTIAL / dormant | `resources/views/public/quran/index.blade.php:123-159` — reads and renders a value; repository-wide `rg -n "setItem\\('surahProgress" resources/views app routes` returned no match |

#### Schema

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Canonical surah metadata | `quran_surahs` stores ID, names, revelation metadata, verse count, pages | PARTIAL — no unique constraint for chronology and contradictory number semantics | `database/migrations/2025_11_29_100000_create_quran_surahs_table.php:16-33` — schema and non-unique `revelation_order` index |
| English “meaning” shown on every card and searched | No dedicated meaning/title-translation column | Missing | `../the-last-messenger-ui/quran-enhanced.html:120-233` — each row has `translation`; `database/migrations/2025_11_29_100000_create_quran_surahs_table.php:21-25` — only Arabic/English name and transliteration |
| Five-locale surah labels | Only `name_ar`/`name_en`; `quran_surah_info` translates descriptions, not names | Missing/partial | `database/migrations/2025_11_29_100000_create_quran_surahs_table.php:21-23` — two names; `database/migrations/2025_11_29_100001_create_quran_surah_info_table.php:16-31` — locale description/content only |
| Juz displayed on cards | No surah-level Juz relation/column; controller uses a hardcoded first-Juz map | PARTIAL | `app/Http/Controllers/Site/QuranController.php:303-320` — presentation mapping; `app/Http/Controllers/Site/QuranController.php:820-848` — 114-entry static mapping fallback |

#### Enhancement gaps

- `app/Http/Controllers/Site/QuranController.php:276-281` — stats are not derived from the repository and can disagree with imported data; violates `../CLAUDE.md:158-162` (DB aggregates, shared-filter counts).
- `app/Http/Controllers/Site/QuranController.php:303-320` — presentation caching uses an inline key rather than `QuranCacheKeys`; violates `../CLAUDE.md:193-196`.
- `resources/views/public/quran/index.blade.php:501-638` — grid/list card markup is duplicated instead of extracted as a reusable Quran/site component; conflicts with `../CLAUDE.md:204-207`.
- `resources/views/public/quran/index.blade.php:125-159` — a progress ring backed by a never-written key is a misleading state, and the prototype’s overall progress is absent; violates `../CLAUDE.md:208-209`.

## Surah reader

### `quran-surah-enhanced.html` → `GET /quran/surah/{surahNumber}` (also `GET /quran/{surahNumber}`) → `resources/views/public/quran/surah.blade.php`
**Status:** PARTIAL

**Page parity:** `routes/web.php:46-63` — both numeric routes map to `Site\QuranController@surah`; `app/Http/Controllers/Site/QuranController.php:326-472` returns `public.quran.surah`, which includes `resources/views/public/quran/_mushaf.blade.php`.

#### Sections & components

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Surah header, meta, Bismillah | Header and metadata exist; Bismillah exists in card and Mushaf layouts | Implemented, subject to wrong-surah lookup | `../the-last-messenger-ui/quran-surah-enhanced.html:350-398` — prototype header; `resources/views/public/quran/surah.blade.php:594-631` — current header; `resources/views/public/quran/_mushaf.blade.php:27-29` — Mushaf Bismillah |
| Reading-mode controls | Arabic/translation/both, font size, transliteration | Implemented | `../the-last-messenger-ui/quran-surah-enhanced.html:402-461` — prototype toolbar; `resources/views/public/quran/surah.blade.php:658-691` and `resources/views/public/quran/surah.blade.php:703-729` — rail popovers |
| Mushaf vs verse-list view | Real page/line Mushaf and verse cards | Implemented beyond original prototype | `resources/views/public/quran/surah.blade.php:648-656` — layout toggle; `resources/views/public/quran/surah.blade.php:901-928` — Mushaf/fallback; `resources/views/public/quran/_mushaf.blade.php:31-38` — page/line render |
| Verse cards and actions | Play, copy, tafsir; no bookmark or real share | PARTIAL | `../the-last-messenger-ui/quran-surah-enhanced.html:475-531` — play/copy/bookmark/share/tafsir; `resources/views/public/quran/surah.blade.php:791-828` — only play/copy/tafsir |
| Prototype pagination / per-page / verse grid | Reader deliberately loads the full surah and provides only page dividers/deep links | PARTIAL redesign; no direct verse/Juz/Hizb/page navigator in the reader | `../the-last-messenger-ui/quran-surah-enhanced.html:537-580` and `../the-last-messenger-ui/quran-surah-enhanced.html:657-675` — pagination and quick verse grid; `resources/views/public/quran/surah.blade.php:758-767` — non-interactive page dividers |
| Sticky sidebar info/audio/related links | Replaced by an always-reachable tool rail, info modal, surah switcher, Ask link, shared bottom player | Implemented redesign | `../the-last-messenger-ui/quran-surah-enhanced.html:583-705` — sidebar; `resources/views/public/quran/surah.blade.php:638-752` — rail; `resources/views/public/quran/surah.blade.php:971-1069` — modals |
| Shared audio player | Seek, time, speed, reciter selector, persistence | Implemented for surah-level tracks | `resources/views/components/site/audio-player.blade.php:12-84` — player UI; `resources/views/components/site/audio-player.blade.php:117-186` — real reciter/speed/seek behavior |
| Empty/loading/error | Empty verses and switcher states exist; edition/audio failures do not | PARTIAL | `resources/views/public/quran/surah.blade.php:931-937` — empty verses; `resources/views/public/quran/surah.blade.php:1007-1069` — switcher states; `resources/views/public/quran/surah.blade.php:394-409` — tafsir failure becomes empty |

#### Features & wiring

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Arabic/translation/both and transliteration | Real local display state | Implemented | `resources/views/public/quran/surah.blade.php:272-281` and `resources/views/public/quran/surah.blade.php:436-459` — persisted state |
| Translation resource switching | Real `GET /quran/surah/{n}/translation/{resourceId}` fetch | Implemented, but locked to English and error-silent | `routes/web.php:52-53` — endpoint; `resources/views/public/quran/surah.blade.php:499-508` — actual fetch; `resources/views/public/quran/surah.blade.php:711-719` — only `$defaultTranslationLang` options |
| Tafsir source switching | Real per-verse `GET /quran/verse/{verseId}/tafsir/{resourceId}` | Implemented, not faked | `routes/web.php:48-53` — endpoint; `resources/views/public/quran/surah.blade.php:394-409` and `resources/views/public/quran/surah.blade.php:467-486` — lazy fetch/refetch |
| Per-verse play | Convention-built EveryAyah URL | PARTIAL — page-selected reciter does not follow shared-player reciter; missing-file/network errors are silent | `resources/views/public/quran/surah.blade.php:522-541` — URL and swallowed failure; `resources/views/components/site/audio-player.blade.php:140-157` — reciter changes only store state |
| Continuous play | Full-surah recording can play continuously | PARTIAL — no sequential ayah mode or active-ayah follow | `resources/views/components/site/audio-player.blade.php:133-168` — full-surah track; `resources/views/components/site/audio-player.blade.php:207-212` — time/end only, no verse synchronization |
| Repeat | No repeat verse/range/surah state | Missing | `resources/views/components/site/audio-player.blade.php:89-217` — complete store has speed but no repeat state/control |
| Copy | Clipboard write is real | Implemented, but mislabeled as share | `resources/views/public/quran/surah.blade.php:511-520` — clipboard call; `resources/views/public/quran/surah.blade.php:808-816` — action title uses `site.share` |
| Bookmark/favorite | Generic IDOR-safe verse favorite backend exists, reader does not call it | Missing UI/wiring | `app/Providers/FavoriteServiceProvider.php:38-59` — `Verse` is registered; `routes/web.php:204-209` — authenticated/throttled endpoints; `resources/views/public/quran/surah.blade.php:791-828` — no bookmark action |
| Share | Prototype exposes an unwired share hook; current page has no real Web Share/copy-link action | Missing | `../the-last-messenger-ui/quran-surah-enhanced.html:495-499` — prototype hook lacks handler; `resources/views/public/quran/surah.blade.php:808-816` — copy action is mislabeled “share” |
| Tajweed colouring | No render mode or tajweed data | Missing | `resources/views/public/quran/_mushaf.blade.php:31-36` — all words render the same way; repository-wide `rg -n "tajweed" database/migrations app/Domain/Quran resources/views/public/quran` returned no match |
| Word-by-word meaning | Word spans exist for layout/hover only | PARTIAL layout, missing lexical UI/data | `resources/views/public/quran/_mushaf.blade.php:31-59` — word spans only hover-highlight; `database/migrations/2026_06_17_000001_create_quran_words_table.php:21-40` — no translation/root/lemma/grammar columns |
| Juz/Hizb/page and verse navigation | Separate public routes exist; reader has page labels and a surah switcher only | PARTIAL | `routes/web.php:54-59` — division routes; `resources/views/public/quran/surah.blade.php:734-750` — only surah/info/Ask navigation in rail |
| Audio-follow / scroll sync | Deep-link scroll exists, audio-follow does not | PARTIAL | `resources/views/public/quran/surah.blade.php:375-392` — hash-only scroll; `resources/views/public/quran/surah.blade.php:522-541` — audio events never scroll/highlight |

#### Schema

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Canonical surah number | Canonical number is `quran_surahs.id`; true chronology is `revelation_order` | BROKEN read mapping | `app/Domain/Quran/Application/DTOs/SurahDTO.php:45-52` — number is ID; `app/Domain/Quran/Repositories/SurahRepository.php:59-68` — lookup uses chronology |
| 6,236 verses and navigation fields | Verse table supports surah/verse key/Juz/Hizb/page | Implemented, with index gaps | `database/migrations/2025_11_29_100002_create_quran_verses_table.php:16-40` — fields/indexes; `storage/app/holy_quran_utf8.json:1` — static source count 6,236 |
| Multiple translations | Resource and per-verse tables support multiple editions | Implemented | `database/migrations/2026_06_15_130001_create_quran_translation_resources_table.php:21-37` and `database/migrations/2026_06_15_130002_add_resource_id_to_quran_verse_translations_table.php:20-68` |
| Translation footnotes | No footnote/endnote table, column, relationship, or render path | Missing | `database/migrations/2025_11_29_100003_create_quran_verse_translations_table.php:16-33` and `database/migrations/2026_06_15_130002_add_resource_id_to_quran_verse_translations_table.php:20-68` — translation rows contain text/resource metadata only; repository-wide `rg -n "footnote|endnote" app/Domain/Quran database/migrations resources/views/public/quran` returned no match |
| Multiple tafsirs | Resource and per-verse tables support multiple works | Implemented | `database/migrations/2026_06_15_120001_create_quran_tafsir_resources_table.php:18-31` and `database/migrations/2026_06_15_120002_add_resource_id_to_quran_verse_tafsirs_table.php:18-60` |
| Per-surah audio | Stored by reciter/surah/format | Implemented | `database/migrations/2026_03_09_000001_create_quran_recitations_table.php:16-43` — URL and unique key |
| Per-verse audio | Deliberately derived from `everyayah_folder`; no availability row | Implemented convention, no failure metadata | `database/migrations/2026_06_10_000001_add_audio_metadata_to_quran_reciters_table.php:14-28`; `app/Domain/Quran/Services/VerseAudioUrlResolver.php:21-29` |
| Word layout | `quran_words` supports Unicode page/line placement | Implemented | `database/migrations/2026_06_17_000001_create_quran_words_table.php:14-40` |
| Word meanings, tajweed, audio segments | No backing columns/table | Missing | `database/migrations/2026_06_17_000001_create_quran_words_table.php:21-40` — layout fields only; `rg -n "quran_audio_segments|tajweed|text_indopak|lemma|grammar" database/migrations app/Domain/Quran` returned no relevant Quran schema |
| Resource uniqueness | `resource_id` remains nullable while old unique constraints are dropped | PARTIAL / data-integrity risk | `database/migrations/2026_06_15_120002_add_resource_id_to_quran_verse_tafsirs_table.php:20-23,52-59`; `database/migrations/2026_06_15_130002_add_resource_id_to_quran_verse_translations_table.php:22-24,60-67` |

#### Enhancement gaps

- `app/Http/Controllers/Site/QuranController.php:341-350` — header and verses use two incompatible number paths; violates correctness and `../CLAUDE.md:126-137`.
- `app/Http/Controllers/Site/QuranController.php:448-467` — the inlined/default translation and selector language are hardcoded to English even in AR/FR/TR/UR; violates `../CLAUDE.md:211-215`.
- `resources/views/public/quran/surah.blade.php:403-408` and `resources/views/public/quran/surah.blade.php:503-508` — failed edition requests become indistinguishable from “no content”; violates `../CLAUDE.md:221-228`.
- `resources/views/components/site/audio-player.blade.php:154-172` and `resources/views/components/site/audio-player.blade.php:202-212` — failed `play()` calls are swallowed and no `error` listener offers retry/fallback; violates `../CLAUDE.md:221-228`.
- `resources/views/public/quran/surah.blade.php:791-828` — icon-only action buttons have titles but no `aria-label`; violates the prototype’s action discoverability and baseline accessibility.
- `resources/lang/fr/quran.php:1`, `resources/lang/tr/quran.php:1`, and `resources/lang/ur/quran.php:1` — full static key lookup found no `public.previous_surah`, `public.next_surah`, `public.playback_speed`, or `public.surah_tools`, although they are called at `resources/views/public/quran/surah.blade.php:645,946-957` and `resources/views/components/site/audio-player.blade.php:49-51`; violates `../CLAUDE.md:211-214`.
- `resources/views/public/quran/_mushaf.blade.php:41-104` — Mushaf words respond only to hover; tap-to-select and the planned play/copy/tafsir/bookmark popover are absent, and the resize/hover behavior has no reduced-motion guard; violates `../CLAUDE.md:221-244`.

## Reciters listing

### `quran-recitors-enhanced.html` → `GET /quran/reciters` → `resources/views/public/quran/reciters.blade.php`
**Status:** PARTIAL

**Page parity:** `routes/web.php:28-30` — `GET /quran/reciters` maps to `Site\QuranController@reciters`; `app/Http/Controllers/Site/QuranController.php:632-693` returns `public.quran.reciters`.

#### Sections & components

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Breadcrumb and hero/stat badges | Present and localized | Implemented | `../the-last-messenger-ui/quran-recitors-enhanced.html:533-590` — prototype; `resources/views/public/quran/reciters.blade.php:213-264` — current |
| Featured reciters | Three-card section with image fallback, badge, bio, listen action | Implemented visually | `../the-last-messenger-ui/quran-recitors-enhanced.html:593-660` — prototype; `resources/views/public/quran/reciters.blade.php:266-347` — current |
| Search, Hafs filter, sort, per-page, view toggle | Client-side controls | Implemented | `../the-last-messenger-ui/quran-recitors-enhanced.html:667-750` — prototype; `resources/views/public/quran/reciters.blade.php:349-425` — current |
| Grid and list cards | Both layouts with localized names and image fallback | Implemented visually | `../the-last-messenger-ui/quran-recitors-enhanced.html:764-891`; `resources/views/public/quran/reciters.blade.php:446-569` |
| Pagination | Client-side five-page window | Implemented | `../the-last-messenger-ui/quran-recitors-enhanced.html:893-936`; `resources/views/public/quran/reciters.blade.php:582-626` |
| Empty state | No-results reset | Implemented | `resources/views/public/quran/reciters.blade.php:571-579` — reset button |
| Error/loading state | Controller errors become an empty data set; no retry/error distinction | Missing | `app/Http/Controllers/Site/QuranController.php:684-685` — catches to empty; `resources/views/public/quran/reciters.blade.php:571-579` — only no-results state |

#### Features & wiring

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Search/filter/sort/pagination | Real against server-provided records, client-side | Implemented | `resources/views/public/quran/reciters.blade.php:130-183` |
| “Listen now” preview | `Audio` code exists but every mapped URL is null | BROKEN | `resources/views/public/quran/reciters.blade.php:186-198` — only plays a non-null URL; `app/Http/Controllers/Site/QuranController.php:656-669` — `firstSurahAudioUrl => null` |
| Prototype preview behavior | Prototype itself only simulates a brief playing indicator | Prototype fake; app still fails to deliver real audio | `../the-last-messenger-ui/quran-recitors-enhanced.html:473-480` — timeout simulation |
| Popularity sort | Sorts `followersCount`, but controller maps that from always-zero recitation count | BROKEN data | `resources/views/public/quran/reciters.blade.php:143-147`; `app/Http/Controllers/Site/QuranController.php:663-668`; `app/Domain/Quran/Application/DTOs/ReciterDTO.php:41-58` — count hardcoded zero |
| Recording count | Zero is replaced with 114 in all card layouts | Misleading/fabricated | `resources/views/public/quran/reciters.blade.php:326`, `resources/views/public/quran/reciters.blade.php:487`, and `resources/views/public/quran/reciters.blade.php:556` |

#### Schema

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Localized name/description | `reciter_translations` with unique reciter/locale | Implemented | `database/migrations/2026_03_10_100003_create_phase_4_translation_tables.php:48-63` |
| Style, country, feature status, image | Core reciter columns | Implemented | `database/migrations/2025_11_29_100005_create_quran_reciters_table.php:16-36` |
| Riwayah, EveryAyah folder, followers | Added to reciters | Implemented but not correctly exposed by DTO/controller | `database/migrations/2026_06_10_000001_add_audio_metadata_to_quran_reciters_table.php:14-28`; `app/Domain/Quran/Application/DTOs/ReciterDTO.php:21-36` — no follower property |
| Per-surah recordings | URL, duration, size, unique reciter/surah/format | Implemented | `database/migrations/2026_03_09_000001_create_quran_recitations_table.php:16-43` |

#### Enhancement gaps

- `app/Domain/Quran/Repositories/ReciterRepository.php:29-41` — public pagination uses `select *` and does not `withCount('recitations')`; violates `../CLAUDE.md:139-169`.
- `app/Http/Controllers/Site/QuranController.php:641-648` — calls `ReciterService::getFeatured()` and stores `$featured`, then never uses it and performs another list query; violates `../CLAUDE.md:158-166`.
- `app/Domain/Quran/Application/DTOs/ReciterDTO.php:41-58` — `recitationsCount` is always zero and `followers_count` is not transported despite the backing column; this is the direct source of wrong sorting/stats.
- `resources/views/public/quran/reciters.blade.php:276-343`, `resources/views/public/quran/reciters.blade.php:446-501`, and `resources/views/public/quran/reciters.blade.php:504-569` — three large variants duplicate the same reciter display/action behavior rather than sharing a component; conflicts with `../CLAUDE.md:204-207`.

## Asbab al-Nuzul

### `quran-asbab-nezol-enhanced.html` → `GET /quran/asbab-al-nuzul` → `resources/views/public/quran/asbab.blade.php`
**Status:** PARTIAL

**Page parity:** `routes/web.php:30-32` — route maps to `Site\QuranController@asbab`; `app/Http/Controllers/Site/QuranController.php:704-758` validates query filters and returns `public.quran.asbab`.

#### Sections & components

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Hero and context/reference badges | Present | Implemented | `../the-last-messenger-ui/quran-asbab-nezol-enhanced.html:423-450`; `resources/views/public/quran/asbab.blade.php:40-90` |
| Search, period, surah, sort, per-page | Server-side GET form | Implemented | `../the-last-messenger-ui/quran-asbab-nezol-enhanced.html:458-518`; `resources/views/public/quran/asbab.blade.php:96-165` |
| Results and loading skeleton | Count plus navigation-triggered skeleton | Implemented | `resources/views/public/quran/asbab.blade.php:168-203` |
| Alternating timeline cards | Connector, nodes, period/surah/range/title, excerpt, narrative, source | Implemented | `../the-last-messenger-ui/quran-asbab-nezol-enhanced.html:540-628`; `resources/views/public/quran/asbab.blade.php:203-295` |
| Hijri year and event label | Prototype has separate year/event; current card has neither | Missing | `../the-last-messenger-ui/quran-asbab-nezol-enhanced.html:567-575`; `resources/views/public/quran/asbab.blade.php:220-244` |
| Read/share/bookmark actions | Only Read Surah remains | PARTIAL | `../the-last-messenger-ui/quran-asbab-nezol-enhanced.html:607-621`; `resources/views/public/quran/asbab.blade.php:285-291` |
| Empty/reset and pagination | Present, server-backed | Implemented | `resources/views/public/quran/asbab.blade.php:296-310` and `resources/views/public/quran/asbab.blade.php:313-365` |

#### Features & wiring

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Search/filter/sort/per-page | Whitelisted/capped query values passed to a CQRS handler | Implemented | `app/Http/Controllers/Site/QuranController.php:711-734`; `app/Domain/Quran/Repositories/RevelationContextRepository.php:49-79` |
| Chronological sort | Sorts curated `display_order`, not an explicit historical year | PARTIAL | `app/Domain/Quran/Repositories/RevelationContextRepository.php:71-77`; `../the-last-messenger-ui/quran-asbab-nezol-enhanced.html:283-287` — prototype sorts year |
| Search fields | Searches translated title/narrative and surah name; prototype also searches event | PARTIAL | `app/Domain/Quran/Repositories/RevelationContextRepository.php:179-193`; `../the-last-messenger-ui/quran-asbab-nezol-enhanced.html:272-280` |
| Read Surah | Real route, but receives chronology instead of canonical ID | BROKEN for most surahs | `resources/views/public/quran/asbab.blade.php:234-238,285-290`; `app/Domain/Quran/Application/DTOs/RevelationContextDTO.php:44-59` — `surahNumber` is `revelation_order` |
| Share/bookmark | Prototype presents unwired UI hooks; implementation drops them | Missing UI/design wiring | `../the-last-messenger-ui/quran-asbab-nezol-enhanced.html:607-621`; `resources/views/public/quran/asbab.blade.php:285-291` |

#### Schema

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Context, verse range, period, event key, ordering, active | Core context table | PARTIAL — `event_key` is not a localized event name and no Hijri year exists | `database/migrations/2026_06_11_220000_create_quran_revelation_contexts_table.php:35-51` |
| Localized title, narrative, source | Translation table, unique per locale, fulltext | Implemented | `database/migrations/2026_06_11_220000_create_quran_revelation_contexts_table.php:54-78` |
| Localized event label | Model translates only title/narrative/source | Missing | `app/Domain/Quran/Models/RevelationContext.php:75-83`; `app/Domain/Quran/Models/RevelationContextTranslation.php:37-44` |
| Filter/sort indexes | FK/surah, period, active+display exist | PARTIAL — no composites matching active+mushaf order or active+chronological order | `database/migrations/2026_06_11_220000_create_quran_revelation_contexts_table.php:38-51`; `app/Domain/Quran/Repositories/RevelationContextRepository.php:55-77` |

#### Enhancement gaps

- `app/Domain/Quran/Application/DTOs/RevelationContextDTO.php:46-59` — links expose `revelation_order` as the route number; this is the same correctness defect as QUR-001.
- `app/Domain/Quran/Repositories/RevelationContextRepository.php:182-193` — leading-wildcard `LIKE` ignores the available FULLTEXT index and searches translations from all locales; violates `../CLAUDE.md:187-195`.
- `app/Domain/Quran/Repositories/RevelationContextRepository.php:137-156` — excerpts are correctly bulk-fetched, so the per-row accessor warning at `app/Domain/Quran/Models/RevelationContext.php:105-120` is not an N+1 on this page; no finding is raised for the closed N+1 concern.
- `app/Http/Controllers/Site/QuranController.php:737-746` — surah options use an inline cache key and silently become empty on failure; violates `../CLAUDE.md:193-196,221-228`.

## Quran Live

### `quran-live-enhanced.html` → `GET /quran/live` → `resources/views/public/quran/live.blade.php`
**Status:** PARTIAL (DEFERRED)

**Page parity:** `routes/web.php:28-31` — `GET /quran/live` maps to `Site\QuranController@live`; `app/Http/Controllers/Site/QuranController.php:696-702` returns `public.quran.live`.

#### Sections & components

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Live video/iframe and live badge | Not implemented; coming-soon empty state instead | DEFERRED, not P0 | `../the-last-messenger-ui/quran-live-enhanced.html:244-260`; `resources/views/public/quran/live.blade.php:17-29` |
| Viewer count, like/share/save, channel/subscription | Not implemented | DEFERRED | `../the-last-messenger-ui/quran-live-enhanced.html:263-317`; `../CLAUDE.md:52-57` |
| Daily schedule/reminders | Not implemented | DEFERRED | `../the-last-messenger-ui/quran-live-enhanced.html:320-364`; `../CLAUDE.md:52-57` |
| Live chat | Not implemented | DEFERRED | `../the-last-messenger-ui/quran-live-enhanced.html:367-426`; `../CLAUDE.md:52-57` |
| Related channels | Not implemented | DEFERRED | `../the-last-messenger-ui/quran-live-enhanced.html:428-459`; `../CLAUDE.md:52-57` |
| Breadcrumb, honest state, return CTA | Localized, no-index coming-soon hook | Implemented | `resources/views/public/quran/live.blade.php:1-31` |

#### Features & wiring

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Prototype video | Prototype embeds a static YouTube demo ID, not a product stream | Prototype fake | `../the-last-messenger-ui/quran-live-enhanced.html:246-252` — static embed URL |
| Viewer updates | Prototype mutates a local number | Prototype fake / deferred | `../the-last-messenger-ui/quran-live-enhanced.html:102` and `../the-last-messenger-ui/quran-live-enhanced.html:516` |
| Chat submit | Prototype appends to an in-memory array only | Prototype fake / deferred | `../the-last-messenger-ui/quran-live-enhanced.html:136-151` and `../the-last-messenger-ui/quran-live-enhanced.html:408-424` |
| Real live backend/endpoints | None found in the `/quran/*` route block | DEFERRED by decision | `routes/web.php:26-63` — only the page hook exists; `../PROJECT_PROGRESS.md:67-72` — live streaming/chat explicitly deferred |

#### Schema

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Streams/channels/schedule/viewers | No Quran live schema in the matching migrations | DEFERRED, not a schema defect for current scope | The complete Quran migration list from `rg --files database/migrations \| rg -i '(quran|surah|verse|tafsir|recit|audio|asbab)'` contains no live-stream/chat migration; `../CLAUDE.md:52-57` limits current work to UI hooks |
| Chat messages/supporters/reactions | No backing table | DEFERRED | `../the-last-messenger-ui/quran-live-enhanced.html:99-161` — prototype-only arrays; `../CLAUDE.md:52-57` |

#### Enhancement gaps

- No actionable live-stream/chat gap is opened: `../TASK_BOARD.md:16` and `resources/views/public/quran/live.blade.php:1-31` match the explicit “localized coming-soon UI hook” decision.
- `app/Http/Controllers/Site/QuranController.php:696-702` has a stale docblock (“YouTube embed with static chat notice”) that does not describe the actual coming-soon view; this is documentation polish only.

## Implementation beyond prototypes: Ask the Quran

There is no assigned/static Quran Ask prototype. `routes/web.php:32-45` and `resources/views/public/quran/ask.blade.php:1-467` implement a substantial conversational search/AI page, so the absence of a design source is a design gap, not a code-page gap.

| Check | Implementation | Status | Evidence |
|---|---|---|---|
| Public canonical search | `GET /quran/ask/search`, throttled and validated | OK | `routes/web.php:32-35`; `app/Http/Controllers/Site/QuranController.php:123-148` |
| AI mutation | Auth + burst throttle + monthly quota | OK | `routes/web.php:36-42`; `app/Providers/AppServiceProvider.php:117-121`; `app/Http/Controllers/Site/QuranController.php:150-188` |
| SSE | Auth + same throttle/quota path, session lock released | OK | `app/Http/Controllers/Site/QuranController.php:191-239` |
| Input validation | FormRequest authorizes signed-in user, sanitizes, length/type-validates | OK | `app/Http/Requests/Site/AskQuranRequest.php:16-45` |
| Inquiry history / IDOR | Both deep-link check and history fetch scope by `id` + `user_id` | OK | `app/Http/Controllers/Site/QuranController.php:77-85,99-120`; `app/Domain/Quran/Repositories/AiInquiryRepository.php:77-100` |
| Verse-link HTML / XSS | Escapes source before emitting integer-built links | OK | `app/Domain/Quran/Services/AnswerHtmlRenderer.php:8-18,28-36,202-212` |
| Schema/indexes | User FK and history/cache indexes exist | OK | `database/migrations/2026_06_17_100001_create_ai_inquiries_table.php:19-65` |
| Prototype/design | No `quran-ask*.html` in assigned prototype directory/list | DESIGN GAP | `routes/web.php:32-45` — page/endpoints exist without a corresponding prototype in this review scope |

## Findings checklist

- [ ] **QUR-001** [P0] Correct canonical-surah lookups before shipping any Quran reader route
      - **Module:** Quran reader / Asbab / API  ·  **Type:** SCHEMA
      - **Evidence:** `app/Domain/Quran/Repositories/SurahRepository.php:59-68` — `getByNumber()` queries `revelation_order`; `app/Domain/Quran/Application/DTOs/SurahDTO.php:45-52` says number is `id`; `app/Http/Controllers/Site/QuranController.php:341-350` loads header through the former and verses through `surah_id = route number`.
      - **What to do:** Make canonical-number reads use `quran_surahs.id` (`find`/`whereKey`) in `SurahRepository::getByNumber`, `VerseRepository::getBySurahAndVerse`, and `VerseRepository::getSurahTranslationByResource`; keep `revelation_order` only for chronology. Change `RevelationContextDTO::surahNumber` to `$surah->id`, `Surah::getNumberAttribute()` to `id`, audit navigation/API calls, rename/cache-bust affected keys, and add feature tests for surahs whose canonical and revelation orders differ (2, 68, 96).
      - **Why it matters / trade-offs:** The current reader can show one surah’s title/meta with another surah’s verses, load another surah’s lazy translation, validate verse suggestions against the wrong verse count, and misroute Asbab links. This is correctness, not cosmetic parity.

- [ ] **QUR-002** [P1] Supply a real public preview URL for every playable reciter card
      - **Module:** Reciters  ·  **Type:** PARTIAL
      - **Evidence:** `app/Http/Controllers/Site/QuranController.php:656-669` — maps every `firstSurahAudioUrl` to null; `resources/views/public/quran/reciters.blade.php:186-198` — the listen action only constructs `Audio` when that value is non-null.
      - **What to do:** Add a repository bulk query selecting one deterministic recitation per listed reciter (for example surah 1, preferred format), return it in `ReciterDTO`, and map it into the page. Disable/label the button when absent; add `loading`, `error`, `ended`, and retry states.
      - **Why it matters / trade-offs:** “Listen now” is the page’s primary action and currently only changes its visual playing state. One bulk query avoids an N+1.

- [ ] **QUR-003** [P1] Centralize per-verse audio resolution and expose missing-file failures
      - **Module:** Surah reader  ·  **Type:** PARTIAL
      - **Evidence:** `app/Http/Controllers/Site/QuranController.php:33-44` — injects `VerseAudioUrlResolver` but never uses it; `resources/views/public/quran/surah.blade.php:530-541` — reimplements the EveryAyah URL and silently clears state on failure; `resources/views/components/site/audio-player.blade.php:140-157` — changing reciter does not update the page’s `selectedReciterId`.
      - **What to do:** Expose resolved per-verse URL templates/data through `QuranReaderService` or one configuration value, remove the duplicated CDN literal from Blade, bind per-verse play to `$store.audioPlayer.reciterId`, add audio `error` handling/toast/retry and an unavailable state, and test a 404 MP3 plus reciter switching.
      - **Why it matters / trade-offs:** A missing remote file currently looks like a broken control, and users may hear a different reciter than the one selected in the shared player.

- [ ] **QUR-004** [P1] Wire verse bookmarks and genuine share actions; decide whether surah favorites remain in scope
      - **Module:** Quran index / reader  ·  **Type:** MISSING
      - **Evidence:** `../the-last-messenger-ui/quran-enhanced.html:307-325` and `../the-last-messenger-ui/quran-surah-enhanced.html:480-499` — favorite/bookmark/share controls; `resources/views/public/quran/surah.blade.php:791-828` — only play/copy/tafsir; `app/Providers/FavoriteServiceProvider.php:38-59` — secure generic favorites already register Quran verses.
      - **What to do:** Fetch the signed-in user’s favorited verse IDs in bulk, POST/DELETE `{type:quran,id:verseId}` through existing routes with pending/error/auth states, and add Web Share API plus copy-link fallback. For index-level surah hearts, either add a distinct whitelisted Surah favorable type or explicitly remove that prototype requirement and document the decision.
      - **Why it matters / trade-offs:** The backend capability exists for verses, so this is a high-visibility missing interaction rather than a new subsystem. Do not overload a verse ID to represent a surah.

- [ ] **QUR-005** [P1] Add repeat and audio-follow behavior to the reader
      - **Module:** Surah reader  ·  **Type:** MISSING
      - **Evidence:** `resources/views/components/site/audio-player.blade.php:89-217` — full player store has seek/speed but no repeat/range state; `resources/views/public/quran/surah.blade.php:522-541` — per-verse audio only handles pause/play/end and never advances or scrolls.
      - **What to do:** Add repeat-off/verse-N/range/surah controls, sequential ayah playback using resolved URLs, active verse state, `scrollIntoView` with reduced-motion behavior, and an option to disable follow scrolling. Persist only user preferences, not transient active state.
      - **Why it matters / trade-offs:** Continuous study and memorization are core reader workflows. Sequential per-ayah playback costs more HTTP requests than the existing full-surah track, so retain both modes.

- [ ] **QUR-006** [P1] Add the missing tajweed, word-meaning, and timing data contracts
      - **Module:** Surah reader  ·  **Type:** SCHEMA
      - **Evidence:** `database/migrations/2026_06_17_000001_create_quran_words_table.php:21-40` — words contain only layout/text/type/location; `resources/views/public/quran/_mushaf.blade.php:31-59` — words only render and hover-highlight; no `quran_audio_segments`/tajweed Quran migration was found.
      - **What to do:** Extend/import word translations (at least all rendered locales or a documented fallback), root/lemma/grammar if word study is in scope, and a tajweed token/rule representation; create `quran_audio_segments(recitation_id, verse_id, word_position, start_ms, end_ms)` with unique/indexed lookup keys. Add word popover and tajweed legend/toggle.
      - **Why it matters / trade-offs:** The current “word” table is a Mushaf layout table, not word-by-word Quran functionality. Timing imports are large and reciter-specific; phase them behind one verified reciter.

- [ ] **QUR-007** [P1] Make the default reader translation follow the active locale
      - **Module:** Surah reader  ·  **Type:** I18N
      - **Evidence:** `app/Http/Controllers/Site/QuranController.php:448-467` — selects only the default English resource and passes `defaultTranslationLang => 'en'`; `resources/views/public/quran/surah.blade.php:711-719` — selector renders only that language.
      - **What to do:** Resolve the rendered locale’s active default resource (with an explicit fallback policy), inline that locale’s text in `versesData`, expose editions for the locale, set direction from the selected resource, and add AR/FR/TR/UR feature tests.
      - **Why it matters / trade-offs:** The DB supports the five app languages, but four locales still receive an English-only study experience.

- [ ] **QUR-008** [P1] Add direct verse, Juz, Hizb, and page navigation inside the surah reader
      - **Module:** Surah reader  ·  **Type:** MISSING
      - **Evidence:** `../the-last-messenger-ui/quran-surah-enhanced.html:537-580,657-675` — pagination and quick verse grid; `routes/web.php:54-59` — Juz/Hizb/page routes exist; `resources/views/public/quran/surah.blade.php:758-767` — page numbers are labels only.
      - **What to do:** Keep the full-surah redesign, but add an accessible jump control for verse and current page plus links/selectors for Juz/Hizb/page routes; update the URL hash and highlight target. Avoid reintroducing client pagination solely for parity.
      - **Why it matters / trade-offs:** The full-surah design is stronger for reading, but long chapters need a precise navigation affordance.

- [ ] **QUR-009** [P1] Store/display actual Asbab chronology and localized event names
      - **Module:** Asbab al-Nuzul  ·  **Type:** SCHEMA
      - **Evidence:** `../the-last-messenger-ui/quran-asbab-nezol-enhanced.html:567-575` — year and event are distinct; `database/migrations/2026_06_11_220000_create_quran_revelation_contexts_table.php:35-68` — has only `event_key`/`display_order`; `app/Domain/Quran/Models/RevelationContext.php:75-83` — event is not translatable.
      - **What to do:** Add nullable `hijri_year` (and a before/after-Hijra representation or signed documented convention), add localized `event_name` to the translation table/model/FormRequests/import, render both fields, search event names, and sort chronology by explicit chronology columns with `display_order` as tie-breaker.
      - **Why it matters / trade-offs:** “Chronological” currently means curator order and cannot explain the timeline date users see in the prototype.

- [ ] **QUR-010** [P1] Rate-limit the full Quran search API
      - **Module:** Quran API  ·  **Type:** SECURITY
      - **Evidence:** `routes/api.php:141-145` — `/quran/search` has no route throttle while only suggestions do; `bootstrap/app.php:29-37` — API middleware adds CORS/locale/logging but no default throttle; `app/Http/Controllers/Api/QuranController.php:182-214` — endpoint can run fulltext pagination up to 100 rows.
      - **What to do:** Apply `throttle:search` to `/api/v1/quran/search` (or a documented equivalent keyed by user/IP), preserve 429 headers, and add a rate-limit feature test.
      - **Why it matters / trade-offs:** This is a public, potentially expensive search surface; `../CLAUDE.md:250-255` requires search throttling.

- [ ] **QUR-011** [P1] Close nullable-resource and legacy tafsir-import integrity holes
      - **Module:** Tafsir/translation data  ·  **Type:** SCHEMA
      - **Evidence:** `database/migrations/2026_06_15_120002_add_resource_id_to_quran_verse_tafsirs_table.php:20-23,52-59` — nullable resource and dropped old unique; `app/Domain/Quran/Services/QuranTextDatasetImportService.php:60-88` — legacy tafsir import still upserts `(verse_id,lang)` without `resource_id`.
      - **What to do:** Make import paths resolve/create an explicit resource and upsert `(verse_id,resource_id)`; verify no null/duplicate rows; then make `resource_id` non-null (or document and uniquely constrain a real legacy sentinel). Mirror the invariant for translations and make SQLite/MySQL tests exercise multiple same-language resources.
      - **Why it matters / trade-offs:** MySQL unique indexes permit multiple null resource IDs, and the legacy import no longer targets a database unique key. That can create invisible duplicate/resource-less tafsir rows.

- [ ] **QUR-012** [P2] Complete index meaning search and reading-progress behavior
      - **Module:** Quran index  ·  **Type:** PARTIAL
      - **Evidence:** `../the-last-messenger-ui/quran-enhanced.html:250-258,487-502` — meaning search and overall progress; `resources/views/public/quran/index.blade.php:134-140` — omits meaning/transliteration; `resources/views/public/quran/index.blade.php:125-159` — reads an unwritten progress key.
      - **What to do:** Include transliteration and a real localized meaning field in filter data; define when/how reader progress is written (last verse/page viewed), render/reset overall progress, or remove dormant rings until the feature is real.
      - **Why it matters / trade-offs:** Misleading zero progress is worse than an honest absence; client-only progress is acceptable for guests if its lifecycle is explicit.

- [ ] **QUR-013** [P2] Add localized surah names/meanings without overloading English fields
      - **Module:** Quran index / reader  ·  **Type:** SCHEMA
      - **Evidence:** `database/migrations/2025_11_29_100000_create_quran_surahs_table.php:21-25` — only `name_ar`, `name_en`, transliteration; `database/migrations/2025_11_29_100001_create_quran_surah_info_table.php:18-22` — translates description/content, not names/meaning.
      - **What to do:** Add a `quran_surah_translations` table keyed by `(surah_id,locale)` with display name and meaning (or extend a clearly named existing locale table), backfill all five locales, eager-load explicit columns, and update DTO/index/switcher/search fallbacks.
      - **Why it matters / trade-offs:** Current FR/TR/UR pages still present English chapter naming and cannot match the prototype’s “meaning” field.

- [ ] **QUR-014** [P2] Use real followers and recording aggregates on reciter cards
      - **Module:** Reciters  ·  **Type:** PARTIAL
      - **Evidence:** `database/migrations/2026_06_10_000001_add_audio_metadata_to_quran_reciters_table.php:19-28` — `followers_count` exists; `app/Domain/Quran/Application/DTOs/ReciterDTO.php:41-58` — does not expose it and hardcodes recitation count zero; `resources/views/public/quran/reciters.blade.php:326,487,556` — replaces zero with 114.
      - **What to do:** Select/DTO-map `followers_count`, add `withCount('recitations')`, remove `|| 114`, use real values for popularity and recording badges, and render zero honestly.
      - **Why it matters / trade-offs:** The current UI sorts every row equally and reports recordings that may not exist.

- [ ] **QUR-015** [P2] Distinguish edition absence from fetch failure and reject inactive resources
      - **Module:** Surah reader  ·  **Type:** ENHANCEMENT
      - **Evidence:** `resources/views/public/quran/surah.blade.php:394-409,499-508` — network/HTTP failure is cached as empty; `app/Domain/Quran/Repositories/TafseerRepository.php:57-63` — checks tafsir row active but not resource active.
      - **What to do:** Track loading/error/loaded separately per verse/resource, show retry, return 404 for missing/inactive resources, validate resource language/scope in `QuranReaderService`, and avoid caching an error as a successful empty response.
      - **Why it matters / trade-offs:** Users currently cannot tell “this edition has no text” from “the server/CDN failed.”

- [ ] **QUR-016** [P2] Add composite indexes matching public division and Asbab order paths
      - **Module:** Quran reader / Asbab  ·  **Type:** PERF
      - **Evidence:** `app/Domain/Quran/Repositories/VerseRepository.php:171-210` — filters Juz/Hizb/page then orders by surah and verse; `database/migrations/2025_11_29_100002_create_quran_verses_table.php:31-39` — only single-column division indexes; `app/Domain/Quran/Repositories/RevelationContextRepository.php:55-77` — active filters and two order paths.
      - **What to do:** Validate with MySQL 8 `EXPLAIN`, then add only proven composites such as `(juz_number,surah_id,verse_number)`, `(hizb_number,surah_id,verse_number)`, `(page_number,surah_id,verse_number)`, `(is_active,surah_id,verse_from)`, and `(is_active,display_order,surah_id,verse_from)`; remove redundant indexes only if the plans prove them redundant.
      - **Why it matters / trade-offs:** This fulfills `../CLAUDE.md:183-195`; 6,236 verses are modest, so plan evidence should precede DDL.

- [ ] **QUR-017** [P2] Route every Quran cache key through `QuranCacheKeys`
      - **Module:** Quran domain/public pages  ·  **Type:** PERF
      - **Evidence:** `app/Http/Controllers/Site/QuranController.php:303-306,337-339,409-410,639-640,736-738`, `app/Domain/Quran/Repositories/VerseRepository.php:92-114`, `app/Domain/Quran/Repositories/TafseerRepository.php:40-46`, and `app/Domain/Quran/Repositories/RecitationRepository.php:72-79` — inline Quran cache strings; `app/Domain/Quran/Services/QuranCacheKeys.php:7-15` declares centralization mandatory.
      - **What to do:** Add typed key methods for public index/surah/reciters/asbab, resource lists/text, translations, and audio URLs; replace inline strings and centralize invalidation/version policy.
      - **Why it matters / trade-offs:** Inline keys make write invalidation drift and directly violate `../CLAUDE.md:193-196`.

- [ ] **QUR-018** [P2] Replace non-SARGable Quran search predicates on indexed/hot fields
      - **Module:** Quran domain / Asbab  ·  **Type:** PERF
      - **Evidence:** `app/Domain/Quran/Repositories/SurahRepository.php:121-134,173-186` — leading-wildcard `LIKE` on integer `revelation_order` causes implicit conversion; `app/Domain/Quran/Repositories/RevelationContextRepository.php:182-193` — leading-wildcard text search despite a FULLTEXT migration.
      - **What to do:** Parse exact numeric surah queries before text search, use equality on canonical `id`, use the existing fulltext helper/index for Asbab translations with locale constraints, and inspect/remove the stale dropped-JSON search scope at `app/Domain/Quran/Models/Reciter.php:191-202`.
      - **Why it matters / trade-offs:** These violate `../CLAUDE.md:193-195`; fulltext token behavior differs from substring matching, so document minimum term/fallback behavior.

- [ ] **QUR-019** [P2] Add the four missing reader/player keys to FR, TR, and UR
      - **Module:** Surah reader  ·  **Type:** I18N
      - **Evidence:** `resources/views/public/quran/surah.blade.php:645,946-957` and `resources/views/components/site/audio-player.blade.php:49-51` call `surah_tools`, previous/next surah, and playback speed; `resources/lang/en/quran.php:170-175` defines them, while a full static lookup of `resources/lang/fr|tr|ur/quran.php` found all four absent.
      - **What to do:** Add accurate `public.playback_speed`, `public.surah_tools`, `public.next_surah`, and `public.previous_surah` to FR/TR/UR and add an automated five-locale key-parity test for public Quran views.
      - **Why it matters / trade-offs:** Laravel will fall back to Arabic, producing mixed-language controls and accessible names.

- [ ] **QUR-020** [P2] Stop presenting repository failures as empty content or 404s
      - **Module:** Quran public pages  ·  **Type:** ENHANCEMENT
      - **Evidence:** `app/Http/Controllers/Site/QuranController.php:398-407` — any reader exception becomes 404; `app/Http/Controllers/Site/QuranController.php:684-685` — any reciter error becomes an empty list; `app/Domain/Quran/Services/QuranReaderService.php:34-40,49-61,70-84,91-111,125-131` — all data errors degrade to empty.
      - **What to do:** Preserve true not-found separately, log/correlate infrastructure errors, return a 5xx or render a localized error/retry state, and only use empty arrays for verified absence.
      - **Why it matters / trade-offs:** Operational failures currently look like missing Quran data, preventing diagnosis and misleading users.

- [ ] **QUR-021** [P2] Restore Asbab share/bookmark hooks or document their removal
      - **Module:** Asbab al-Nuzul  ·  **Type:** MISSING
      - **Evidence:** `../the-last-messenger-ui/quran-asbab-nezol-enhanced.html:607-621` — Read/share/bookmark action row; `resources/views/public/quran/asbab.blade.php:285-291` — only Read remains.
      - **What to do:** Add share-link/Web Share behavior; decide whether bookmarking targets a revelation context, its first verse, or a new favorable type, then implement the selected semantics with existing auth/IDOR patterns. If these are intentionally non-functional prototype decorations, record that product decision.
      - **Why it matters / trade-offs:** The target of “bookmark context” is ambiguous; resolve that before extending the polymorphic registry.

- [ ] **QUR-022** [P2] Give reader icon actions accessible names and make Mushaf interactions keyboard/touch reachable
      - **Module:** Surah reader  ·  **Type:** A11Y
      - **Evidence:** `resources/views/public/quran/surah.blade.php:791-828` — icon buttons use `title` but no `aria-label`; `resources/views/public/quran/_mushaf.blade.php:46-59` — interaction is mouseover-only on non-focusable spans.
      - **What to do:** Add localized `aria-label`, `type=button`, visible focus rings, and pressed/expanded state as applicable; make verse/word groups focusable buttons or supply an adjacent accessible action menu supporting click, touch, Enter, Space, and Escape.
      - **Why it matters / trade-offs:** Title tooltips do not label controls reliably for touch or assistive technology.

- [ ] **QUR-023** [P2] Create an approved prototype/design contract for `/quran/ask`
      - **Module:** Ask the Quran  ·  **Type:** ENHANCEMENT
      - **Evidence:** `routes/web.php:32-45` and `resources/views/public/quran/ask.blade.php:1-467` implement page/search/AI/SSE/history, but no assigned `quran-ask*.html` exists.
      - **What to do:** Produce a five-locale, RTL/dark/mobile prototype covering guest/search/AI modes, auth gate, quota, SSE progress/error/retry, citations, verse links, and history empty/loading/error states; then audit the existing Blade against it.
      - **Why it matters / trade-offs:** Security is statically sound, but there is no design source of truth for a complex, high-cost AI interaction.

- [ ] **QUR-024** [P2] Bring the public Quran controller/read paths back to the one-orchestrator and explicit-select rules
      - **Module:** Quran architecture  ·  **Type:** PERF
      - **Evidence:** `app/Http/Controllers/Site/QuranController.php:33-45` — eleven injected services/handlers/repositories; `app/Http/Controllers/Site/QuranController.php:326-472` — `surah()` orchestrates several collaborators; `app/Domain/Quran/Repositories/ReciterRepository.php:29-41` — public list has no explicit select.
      - **What to do:** Introduce page-query handlers/read-model services per public page so each controller action calls one orchestrator; move presentation aggregation/hardcoded lookups out of the controller; add explicit columns and constrained eager loads to reciter queries.
      - **Why it matters / trade-offs:** `../CLAUDE.md:126-169` requires controller → one service/handler and explicit selection. Avoid a single new “god service”; page-specific queries are the smaller seam.

- [ ] **QUR-025** [P2] Replace hardcoded Quran statistics and first-Juz mapping with repository data
      - **Module:** Quran index / reader  ·  **Type:** PERF
      - **Evidence:** `app/Http/Controllers/Site/QuranController.php:276-281` — hardcoded totals; `app/Http/Controllers/Site/QuranController.php:820-848` — hardcoded first-Juz map despite indexed verse data.
      - **What to do:** Add one cached aggregate query for totals/type counts and one bulk `MIN(juz_number)` grouped by `surah_id` for all 114 rows; merge through the page query handler and cache via `QuranCacheKeys`.
      - **Why it matters / trade-offs:** The present constants conceal import drift. The bulk aggregate is cheap at 6,236 rows and removes a 114-entry maintenance table.

- [ ] **QUR-026** [P2] Add first-class translation footnotes to the reader data model
      - **Module:** Surah reader  ·  **Type:** SCHEMA
      - **Evidence:** `database/migrations/2025_11_29_100003_create_quran_verse_translations_table.php:16-33` and `database/migrations/2026_06_15_130002_add_resource_id_to_quran_verse_translations_table.php:20-68` contain no footnote linkage; repository-wide `rg -n "footnote|endnote" app/Domain/Quran database/migrations resources/views/public/quran` returned no match.
      - **What to do:** Add a resource-scoped translation-footnotes table (resource ID plus stable external footnote key, localized/rich text, unique composite key and lookup index), import footnote references separately from verse text, expose them through the translation DTO/endpoint, and render accessible expandable references in both list and Mushaf modes.
      - **Why it matters / trade-offs:** Embedding note markup directly in verse text makes source switching, sanitization, and citation accessibility fragile; a normalized table costs an extra constrained eager load.

- [ ] **QUR-027** [P3] Extract repeated Quran public cards/controls into components
      - **Module:** Quran index / reciters  ·  **Type:** ENHANCEMENT
      - **Evidence:** `resources/views/public/quran/index.blade.php:501-638` duplicates grid/list surah presentation; `resources/views/public/quran/reciters.blade.php:276-343,446-569` repeats reciter metadata/actions three times; `resources/views/components/quran/` contains no matching public card component.
      - **What to do:** Extract small presentation components/partials for surah metadata/progress and reciter identity/stats/listen action while leaving page-level Alpine collections at the page boundary.
      - **Why it matters / trade-offs:** Extraction reduces parity drift, but do not force visually different card shells into a prop-heavy universal component.

- [ ] **QUR-028** [P3] Complete reduced-motion and secondary-text contrast hardening
      - **Module:** Quran public UI  ·  **Type:** A11Y
      - **Evidence:** `resources/views/public/quran/_mushaf.blade.php:89-103` — resize/observer effects have no reduced-motion branch; `resources/views/public/quran/reciters.blade.php:105-112` — featured animation is unconditional; `resources/views/public/quran/reciters.blade.php:311-332` — dark secondary text uses gray-400 rather than the mandated gray-300.
      - **What to do:** Add `prefers-reduced-motion` CSS/JS guards for decorative animation/smooth scrolling, and normalize secondary text to the project’s light/dark contrast tokens.
      - **Why it matters / trade-offs:** This closes `../CLAUDE.md:230-244` polish requirements without changing information architecture.

## Doc corrections

- `TODO_quran-parity.md:25-26,95-102` — **stale/overstated:** the injected `VerseAudioUrlResolver` is not used by the controller/view; per-verse URLs are rebuilt in Blade (`resources/views/public/quran/surah.blade.php:530-541`), failures are silent, player reciter changes do not update per-verse selection, and the shared player does not auto-advance to the next surah (`resources/views/components/site/audio-player.blade.php:207-212`). Speed/seek/surah-level reciter switching are real.
- `TODO_quran-parity.md:56-57,72` — **partly stale:** the layout subset of `quran_words` and a Mushaf page mode now exist (`database/migrations/2026_06_17_000001_create_quran_words_table.php:21-40`, `resources/views/public/quran/_mushaf.blade.php:23-38`). Word meanings/morphology/glyph variants remain open, so the broad “word-by-word corpus” item should be split rather than marked wholly open or done.
- `TODO_quran-parity.md:35-36` — **stale in part:** `TODO_quran-data.md:22-24,54-55` claims multiple English tafsirs are imported, so “only Arabic” is no longer consistent with the newer document. Static code confirms multi-language-capable resource schema (`database/migrations/2026_06_15_120001_create_quran_tafsir_resources_table.php:18-31`); FR/TR/UR data completeness still needs a DB check.
- `TODO_quran-parity.md:62-63` — **partly closed:** playback speed exists at `resources/views/components/site/audio-player.blade.php:181-185`; repeat/loop remains open.
- `TODO_quran-parity.md:47-48` — **still open and correctly claimed:** no translation-footnote schema or reader path exists (`database/migrations/2025_11_29_100003_create_quran_verse_translations_table.php:16-33`; repository-wide `rg -n "footnote|endnote" app/Domain/Quran database/migrations resources/views/public/quran` returned no match).
- `TODO_quran-parity.md:82` — **stale in part:** server-side Quran verse favorites are already registered and IDOR-safe (`app/Providers/FavoriteServiceProvider.php:38-59`, `app/Http/Requests/Site/StoreFavoriteRequest.php:18-49`); only the Quran page UI/wiring is open.
- `TODO_quran-data.md:30-31,50,60` — **wrong locale implication:** the reader does expose real lazy resources, but the current default and selector are explicitly English (`app/Http/Controllers/Site/QuranController.php:448-467`, `resources/views/public/quran/surah.blade.php:711-719`), not the default resource “per language.”
- `TODO_quran-data.md:63` — **overstated architecture claim:** the controller avoids direct Eloquent, but `QuranController` has eleven collaborators and actions call several, contrary to the “ONE service/handler” standard at `../CLAUDE.md:126-137`.
- `TODO_quran-data.md:85` — **stale wording:** per-ayah audio is not deferred; it is convention-built and exposed now (`resources/views/public/quran/surah.blade.php:522-541`). The missing portions are centralized resolver use, availability/error handling, and synchronized/continuous playback.
- `QURAN_MODULE_IMPROVEMENT_PLAN.md:10-24,241-258` — **historical:** its 0-verse/29-surah critical status and “unknown” reciter/tafsir state conflict with the static import source/seed paths and newer verified-data documents. Retain only genuinely unverified admin/testing tasks or archive this plan.
- `QURAN_READER_REDESIGN.md:148-171` — **mostly implemented:** schema, importer/read path, page/line render, rosettes, and page numbers exist. `QURAN_READER_REDESIGN.md:166` remains open: tap/action popover is not in `_mushaf.blade.php`.
- `QURAN_READER_REDESIGN.md:304-316` — **not all success criteria are met:** four FR/TR/UR keys are missing, reduced-motion is incomplete, and audio errors have no retry despite the design’s explicit hardening rules at `QURAN_READER_REDESIGN.md:280-287`.
- `../WEB_APP_MODULES_ANALYSIS.md:3-12` correctly warns that the body is historical, but `../WEB_APP_MODULES_ANALYSIS.md:319-325` still contains stale missing-Asbab/audio/transliteration claims. The current gaps are the broken reciter preview, reader failure handling, and UI wiring—not absent base tables.
- `../PROJECT_PROGRESS.md:20` and `../PROJECT_PROGRESS.md:88` — **overstated:** pages exist, but “working per-verse audio” and “UI matches” do not survive the static parity check; QUR-001 through QUR-028 remain.
- `../KNOWN_ISSUES.md:16` — **stale:** Asbab model/table/page now exist (`routes/web.php:31`, `database/migrations/2026_06_11_220000_create_quran_revelation_contexts_table.php:35-78`). Move KI-4 to Fixed.
- `../KNOWN_ISSUES.md:19` and `../TASK_BOARD.md:16` are consistent with code: the `/quran/live` route is intentional and its coming-soon UI hook is complete while the product remains deferred.

## Open questions

- The task prohibited DB access/migrations. `storage/app/holy_quran_utf8.json:1` statically contains 114 surahs/6,236 verse entries, and `database/seeders/QuranReciterSeeder.php:110-278` statically defines 11 reciters, but a human/read-only production query must confirm current counts: 114 surahs, 6,236 verses, 11 active reciters, 1,254 complete recitations, resource coverage, and zero null/duplicate resource IDs.
- Remote audio availability cannot be proven statically or with network access disabled. Test representative first/middle/last surahs and ayahs for all 11 EveryAyah folders and all stored surah-audio bases, including CORS/range-request behavior.
- The exact live DB values of `quran_surahs.revelation_order` need confirmation. The sync contract at `app/Domain/Quran/Services/QuranSyncService.php:208-229` stores upstream chronology while ID stores canonical number, so QUR-001 is code-certain if the normal sync/import was used.
- Product must decide whether index favorites target whole surahs, whether Asbab bookmarks target contexts or verses, and whether guest-only localStorage favorites should coexist with authenticated server favorites.
- Product must decide whether “continuous play” means the existing full-surah recording, sequential ayah files with highlight/follow, or both.
- Product must decide the word-by-word scope: layout-only (already implemented), localized meanings, morphology/grammar, tajweed, and/or reciter-specific timing.
- Translation/tafsir row totals in `TODO_quran-data.md` are live-DB claims and were not treated as evidence here. Re-run the project’s read-only verifier/audit in the intended environment before marking coverage complete.
- Browser/runtime checks remain needed for Alpine initialization, clipboard permission failures, audio errors, mobile bottom-rail/player stacking, dark contrast, keyboard focus, and RTL behavior. All five prototype pages received a full static pass; none was intentionally thinned.
