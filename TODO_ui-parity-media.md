# Media / Programs UI parity and gap review
**Scope:** `programs-premium.html`, `programs-enhanced.html`, `episode-premium.html`, `episode-enhanced.html`  ·  **Method:** static read of prototypes + Laravel code + migrations

## Executive summary

| Module | Prototype pages | Implemented | Verdict (OK / PARTIAL / MISSING) | Top gap |
|---|---:|---|---|---|
| Programs hub | `programs-premium.html`, `programs-enhanced.html` | Yes — `GET /media` → `Site\MediaController@index` → `public.media.index` | PARTIAL | Canonical category/search/filter/view-all discovery controls are absent. |
| Episode/player | `episode-premium.html`, `episode-enhanced.html` | Yes — `GET /media/{slug}/episodes/{episode}` → `Site\MediaController@episode` → `public.media.episode` | PARTIAL | Watch-progress storage/API exists, but the player neither saves nor resumes position. |

- **The historical “stub/empty API” assessment is stale.** `routes/api.php:80-96` — nine real media endpoints are registered, including authenticated progress and Continue Watching; `app/Http/Controllers/Api/MediaProgramController.php:38-249` — they execute services/queries and return resources rather than hard-coded empty arrays.
- **The public pages are real and substantially exceed the old stub.** `routes/web.php:108-114` — hub, live, program, and episode routes exist; `app/Http/Controllers/Site/MediaController.php:52-201` — each route has a data-backed controller path; `resources/views/public/media/episode.blade.php:40-310` — the episode has a custom YouTube player and real next-episode overlay.
- **P0 stored-XSS path:** `resources/views/public/media/program.blade.php:220-223` — program descriptions render raw; `app/Http/Requests/Admin/MediaProgram/StoreMediaProgramRequest.php:40-45` — descriptions are only checked as strings, and the Media domain grep `rg -n "sanitize|Purifier|strip_tags" app/Domain/Media app/Http/Requests/Admin/MediaProgram` found no write-path sanitizer.
- **P0 unpublished-content disclosure:** `app/Domain/Media/Repositories/MediaEpisodeRepository.php:26-35` — the public paginated episode query omits `published()` even though `routes/api.php:89` exposes it without authentication and `app/Http/Resources/MediaEpisodeResource.php:23-30` returns title, description, video URL, and thumbnail.
- **Player progress is backend-only.** `database/migrations/2026_06_10_200004_create_episode_watch_progress_table.php:11-27` and `routes/api.php:92-96` — schema and endpoints exist; grep `rg -n "media/progress|continue-watching|episode_watch_progress" resources/views/public/media/episode.blade.php` returned no matches.
- **View increments are atomic but trivially inflatable and can display stale cached values.** `app/Domain/Media/Models/MediaEpisode.php:131-134` — `increment()` is atomic; `app/Domain/Media/Services/MediaEpisodeService.php:92-104` — every detail resolution increments; `app/Domain/Media/Repositories/MediaEpisodeRepository.php:127-140` — the pre-increment model is cached for 30 minutes.
- **The default seed path does not install the advertised media catalog.** `database/seeders/MediaProgramSeeder.php:112-1388` — a static 16-program/85-episode catalog exists (14 non-null video URLs); `database/seeders/DatabaseSeeder.php:31-39` — only `MediaCategorySeeder` is called, not `MediaProgramSeeder`.
- **Public artwork bypasses both encryption-at-rest and registered conversions.** `app/Domain/Media/Models/MediaProgram.php:134-157` — public mappings use original poster/banner URLs while a thumbnail conversion exists; `app/Domain/Media/Models/MediaProgram.php:218-245` — those originals are on the `public` disk; `app/Http/Controllers/Admin/Media/MediaProgramController.php:406-412` — encrypted library originals are decrypted and copied into that public Spatie collection.
- **Live is beyond a hook, while chat has no hook.** `resources/views/public/media/live.blade.php:28-54` — `/media/live/{slug}` embeds a real stream; `resources/views/public/media/live.blade.php:91-131` — the sidebar contains related channels, not chat. `../CLAUDE.md:52-57` classifies both live streaming and chat as DEFERRED, so this is not a P0 parity failure.
- **All five public translation key sets are complete.** A recursive key comparison of `resources/lang/{ar,en,fr,tr,ur}/media.php` under `public` returned zero missing keys for EN/FR/TR/UR; `resources/lang/fr/media.php:17-119` is representative. This improves on both premium prototypes, whose inline dictionaries stop at AR/EN (`../the-last-messenger-ui/programs-premium.html:1047-1117`; `../the-last-messenger-ui/episode-premium.html:650-720`).

## Programs listing

### programs-premium.html → `GET /media` → `resources/views/public/media/index.blade.php`
**Status:** PARTIAL

**Page parity:** `routes/web.php:107-114` — `GET /media` maps to `Site\MediaController@index`; `app/Http/Controllers/Site/MediaController.php:52-119` — the method assembles hero, featured, trending, popular, live, category, progress, and favorite data; `resources/views/public/media/index.blade.php:1-10` — the Blade page exists and uses the public layout.

#### Sections & components

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Shared glass header: logo/nav, global search, notification, theme, language/profile, mobile menu | Supplied by `layouts.public`, not reimplemented in this page. Module-specific program search/modal is not present. | PARTIAL | `../the-last-messenger-ui/programs-premium.html:248-346` — prototype header controls; `resources/views/public/media/index.blade.php:1` — shared layout; `rg -n -e "search" -e "notification" resources/views/public/media/index.blade.php` returned no matches. |
| Full-bleed featured hero carousel, badges, meta, description, dots, artwork preview | Five real featured programs, auto-rotation, pause-on-hover, dots, reduced-motion handling, meta and poster preview are implemented. Rating and “98% match” are omitted. | PARTIAL | `../the-last-messenger-ui/programs-premium.html:350-475` — canonical hero; `resources/views/public/media/index.blade.php:17-158` — implemented carousel; `app/Http/Controllers/Site/MediaController.php:55-57` — bounded five-slide query. |
| Hero CTAs: Play Now, My List, More Info | Play Now and My List exist. Play Now opens the program detail rather than its first playable episode; the More Info control is absent. | PARTIAL | `../the-last-messenger-ui/programs-premium.html:425-454` — three canonical actions; `resources/views/public/media/index.blade.php:94-116` — two actions; `app/Http/Controllers/Site/MediaController.php:218` — card URL is the program route. |
| Sticky category pills: All / Seerah / Quran / Hadith / Kids | Explicitly omitted; all configured category rows are stacked. | MISSING | `../the-last-messenger-ui/programs-premium.html:477-501` — canonical pills; `resources/views/public/media/index.blade.php:161-163` — view explicitly says “no in-page filter bar.” |
| Continue Watching rail with thumbnail, progress, duration, completion and overflow controls | Real authenticated progress rail with progress percentage and bounded 10-item query. It lacks prototype View All and per-card overflow menu; clicking does not resume at the saved second. | PARTIAL | `../the-last-messenger-ui/programs-premium.html:506-591` — canonical rail; `resources/views/public/media/index.blade.php:218-276` — implemented rail; `app/Http/Controllers/Site/MediaController.php:389-410` — real progress mapping. |
| Popular Shows rail with large ordinal rank, rating/year, Filter, View All | Top-10 ranked rail is present through `_rank-card`; Filter/View All and rating/year captions are absent. | PARTIAL | `../the-last-messenger-ui/programs-premium.html:602-675` — canonical rail; `resources/views/public/media/index.blade.php:278-306` — rank rail; `resources/views/public/media/_rank-card.blade.php:27-38` — numeral and episode-count caption. |
| Seerah Series rail | Real category row with generic program cards and smart arrows. View All is absent. | PARTIAL | `../the-last-messenger-ui/programs-premium.html:682-738` — canonical rail; `app/Http/Controllers/Site/MediaController.php:84-98` — dynamic category rows; `resources/views/public/media/index.blade.php:315-320` — render path. |
| Quran Recitations rail with reciter attribution | Quran category can render, but the shared card only shows title, episode count and year; no reciter attribution is shown. | PARTIAL | `../the-last-messenger-ui/programs-premium.html:746-800` — reciter cards; `resources/views/public/media/_program-card.blade.php:42-56` — implemented card fields. |
| Hadith Explanations rail | Real category row using the shared program card. View All is absent. | PARTIAL | `../the-last-messenger-ui/programs-premium.html:808-864` — canonical rail; `resources/views/public/media/index.blade.php:315-320` — dynamic implementation. |
| Kids Content rail with age range/family badge | Kids category can render, but age-range metadata/badge is not mapped or displayed. | PARTIAL | `../the-last-messenger-ui/programs-premium.html:872-932` — canonical kids cards; `app/Http/Controllers/Site/MediaController.php:215-231` — mapped card fields contain maturity but no child age range. |
| RTL-aware smart horizontal arrows | Extracted media row partial implements overflow detection, logical positioning, flipped icons, and reduced-motion-safe hover. | IMPLEMENTED | `../the-last-messenger-ui/programs-premium.html:934-945` — canonical arrow behavior; `resources/views/public/media/_row.blade.php:17-34` — implemented controls. |
| Search modal with recent searches | Entire modal is absent. The prototype itself leaves recent-search behavior as a placeholder. | MISSING | `../the-last-messenger-ui/programs-premium.html:949-978` — modal; `../the-last-messenger-ui/programs-premium.html:1202-1204` — prototype TODO; `rg -n -e "searchOpen" -e "searchQuery" -e "recentSearch" resources/views/public/media/index.blade.php` returned no matches. |
| Footer | Supplied by the public layout. | IMPLEMENTED | `../the-last-messenger-ui/programs-premium.html:983-1035` — canonical footer; `resources/views/public/media/index.blade.php:1` — shared layout. |
| Empty/loading/error states | A useful global empty state exists. There is no loading state for server-rendered rows, and controller exceptions collapse into the same empty state rather than a retryable error. | PARTIAL | `resources/views/public/media/index.blade.php:165-176` — empty state; `app/Http/Controllers/Site/MediaController.php:100-108` — all list failures become empty arrays. |
| Extra Featured, Trending and Live rails | Implemented additions. Live cards link to a real `/media/live/{slug}` stream page; this exceeds the deferred product scope. | ENHANCEMENT | `resources/views/public/media/index.blade.php:179-216` and `308-313` — extra rails; `routes/web.php:111` and `resources/views/public/media/live.blade.php:28-54` — real live page/player. |

#### Features & wiring

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Hero auto-rotation/dots/pause | Real Alpine component; seven-second rotation, hover pause, wraparound, reduced-motion guard. | IMPLEMENTED | `resources/views/public/media/index.blade.php:390-429` — `mediaHeroCarousel()`. |
| Mouse parallax on hero artwork | Not implemented. | MISSING | `../the-last-messenger-ui/programs-premium.html:1240-1293` — prototype parallax state/handler; `rg -n -e "parallax" -e "mousemove" resources/views/public/media/index.blade.php` returned no matches. |
| My List add/remove | Authenticated users call real polymorphic favorite endpoints with CSRF, pending disable and rollback; guests use localStorage. | IMPLEMENTED | `resources/views/public/media/index.blade.php:329-388` — real/guest branches; `app/Http/Controllers/Site/MediaController.php:423-455` — server favorite state. |
| Category filtering | No client or server filter is wired on `/media`, although the API accepts a category. | MISSING | `resources/views/public/media/index.blade.php:161-163` — no filter bar; `app/Http/Controllers/Api/MediaProgramController.php:38-50` — API category parameter exists. |
| Search within programs | Rate-limited real API search exists; `/media` exposes no input/modal or request wiring. | PARTIAL | `routes/api.php:86` — `throttle:search`; `app/Http/Controllers/Api/MediaProgramController.php:88-110` — search endpoint; no-match grep in the Sections table. |
| Popular Filter and View All | Visual controls/routes are absent; the public hub always uses one bounded top-10 query. | MISSING | `../the-last-messenger-ui/programs-premium.html:612-620` — controls; `app/Http/Controllers/Site/MediaController.php:67-70` — fixed query; `rg -n -e "view_all" -e "filter" resources/views/public/media/index.blade.php` returned no matches. |
| Continue Watching calculation | Real, authenticated, owner-scoped progress is loaded and converted to percentage. | IMPLEMENTED | `app/Domain/Media/Repositories/MediaEpisodeRepository.php:293-310` — owner/completion filters and limit; `app/Http/Controllers/Site/MediaController.php:389-410` — percentage mapping. |
| Resume playback | Saved seconds are not passed to the episode page/player and the player never calls the progress endpoint. | MISSING | `app/Http/Controllers/Site/MediaController.php:171-180` — episode view payload has no progress; `rg -n -e "media/progress" -e "episode_watch_progress" resources/views/public/media/episode.blade.php` returned no matches. |
| Pagination/load-more/sort | Not part of the canonical rail design and absent from the hub. The JSON list API does paginate and whitelist sort columns. | IMPLEMENTED (API ONLY) | `app/Http/Controllers/Api/MediaProgramController.php:38-50` — paginated API; `app/Domain/Media/Repositories/MediaProgramRepository.php:23-44` — sort whitelist. |
| Theme/language/RTL | Shared public layout supplies them; all public media keys exist in AR/EN/FR/TR/UR, and media views use logical properties/flipped icons. | IMPLEMENTED | `resources/views/public/media/_row.blade.php:25-33` — logical/flipped arrows; recursive `public` key comparison across `resources/lang/{ar,en,fr,tr,ur}/media.php` returned zero missing keys. |
| Live stream/chat | `_live-card` is a real link to an iframe stream page; no chat UI or endpoint exists. Both are DEFERRED, not P0. | PARTIAL / DEFERRED | `resources/views/public/media/_live-card.blade.php:6-40` — card hook; `resources/views/public/media/live.blade.php:31-37` — real stream; inspection of `rg -n -e "chat" -e "message" routes app/Http/Controllers/Site/MediaController.php resources/views/public/media` found generic message variables and the controller’s “no chat” comment, but no implementation; `../CLAUDE.md:56-57`. |

#### Schema

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Program identity, category, maturity, year, rating, flags, views and order | Backed by `media_programs`; totals/publishing/playlist ID are added by later migrations. | IMPLEMENTED | `database/migrations/2026_06_10_200000_create_media_programs_table.php:11-27`; `database/migrations/2026_06_11_100000_add_totals_to_media_programs_table.php:11-18`; `database/migrations/2026_06_11_110000_add_publishing_to_media_programs_table.php:11-18`. |
| Translatable title/description/host and SEO | Translation table supports arbitrary five-character locales, readiness and per-locale SEO. | IMPLEMENTED | `database/migrations/2026_06_10_200001_create_media_program_translations_table.php:12-30`; `database/migrations/2026_06_11_130000_add_seo_fields_to_media_program_translations_table.php:11-17`. |
| Episode counts | List rails use `withCount('episodes')` on hero/featured/trending/popular/category paths. | IMPLEMENTED | `app/Domain/Media/Repositories/MediaProgramRepository.php:102-194` — explicit selects, eager loads, counts and limits. |
| Continue Watching | Dedicated unique `(user_id, episode_id)` table with seconds/completed and owner/status/update index. | IMPLEMENTED | `database/migrations/2026_06_10_200004_create_episode_watch_progress_table.php:11-27`. |
| My List | Shared polymorphic favorites table has a unique user/content/type key. | IMPLEMENTED | `database/migrations/2025_10_27_100004_create_favorites_table.php:16-24`; `app/Domain/Shared/Services/FavoriteService.php:101-146`. |
| Hero “98% match” | No recommendation/match-score column or relation exists in the checked program, episode, progress, interaction, and favorite migrations. | MISSING | `database/migrations/2026_06_10_200000_create_media_programs_table.php:11-27` — complete base program fields; `rg -n -e "match_score" -e "recommendation_score" -e "affinity" app/Domain/Media database/migrations` returned no matches. |
| Quran reciter, kids age range, subscriber count | No explicit reciter relation, child min/max age, or program subscriber schema exists. `host` can approximate presenter/reciter but is not rendered on cards. | PARTIAL / SCHEMA GAP | `database/migrations/2026_06_10_200001_create_media_program_translations_table.php:18-21` — generic host only; `rg -n -e "reciter_id" -e "age_min" -e "age_max" -e "subscriber" app/Domain/Media database/migrations` returned Quran-only reciter matches and no Media-domain backing. |
| Rail query bounds | Hero/featured/trending/popular/live each have a bound, but grouped categories fetch every matching published program and apply `take(12)` in PHP. | PARTIAL | `app/Http/Controllers/Site/MediaController.php:55-82` — requested bounds; `app/Domain/Media/Repositories/MediaProgramRepository.php:217-226` — unbounded SQL `get()` then collection `take()`. |
| Rail/search indexes | Separate and partial composites exist, but public rails begin with `is_published` and order/filter on views/flags/category without matching leading composites. A title/description FULLTEXT index exists but search uses leading-wildcard `LIKE`. | PARTIAL | `database/migrations/2026_06_10_200000_create_media_programs_table.php:14-26`; `database/migrations/2026_06_10_200001_create_media_program_translations_table.php:27-31`; `app/Domain/Media/Repositories/MediaProgramRepository.php:107-113`, `230-241`. |
| Artwork and variants | Spatie table/collections/conversions exist, but public card mappings resolve original public URLs rather than `thumbnail`/`medium` conversions. | PARTIAL | `database/migrations/2026_06_11_180000_create_spatie_media_table.php:18-37`; `app/Domain/Media/Models/MediaProgram.php:134-157` and `218-245`; `app/Http/Controllers/Site/MediaController.php:228-229`. |
| Live channel hook | Channel slug/stream URL/status/publication/order/views and localized title/description are fully backed; chat has no backing schema. | IMPLEMENTED STREAM / DEFERRED CHAT | `database/migrations/2026_06_14_100000_create_live_channels_table.php:11-22`; `database/migrations/2026_06_14_100001_create_live_channel_translations_table.php:11-24`; `resources/views/public/media/live.blade.php:28-54`. |
| Seeded content | Static seeder defines 16 program blocks and 85 episode blocks, but default seeding omits it; runtime row counts cannot be inferred statically. | PARTIAL | `database/seeders/MediaProgramSeeder.php:112-1388`; `database/seeders/DatabaseSeeder.php:31-39`. |

#### Enhancement gaps

- `resources/views/public/media/index.blade.php:161-163` — restore the canonical discovery controls (MED-004/005/006). Violates `../CLAUDE.md:208-209` (prototypes are the functional spec).
- `app/Domain/Media/Repositories/MediaProgramRepository.php:217-226` — enforce per-category bounds in SQL instead of loading all programs (MED-016). Violates `../CLAUDE.md:164-169`.
- `app/Domain/Media/Repositories/MediaProgramRepository.php:118-126` and `230-241` — API recent/search omit `media`, while the resource reads poster/banner accessors; the episode API list has the same mismatch (MED-018). Violates `../CLAUDE.md:139-168`.
- `app/Http/Controllers/Site/MediaController.php:29-46` — the public controller injects four services and performs cross-workflow composition (MED-020). Violates `../CLAUDE.md:128-136` (one service/handler per controller).
- `app/Http/Controllers/Site/MediaController.php:100-108` — distinguish empty catalog from load failure and offer retry (MED-023). Violates `../CLAUDE.md:221-232`.
- `resources/views/public/media/_row.blade.php:1-35` — the reused row remains a media include rather than a component under `components/site` (MED-024). Violates `../CLAUDE.md:202-207`.
- No RTL defect was found in the reviewed media views: `resources/views/public/media/_row.blade.php:25-33` and `resources/views/public/media/index.blade.php:47-49` use logical positions or explicit direction switching as required by `../CLAUDE.md:211-218`.

## Earlier programs variant

### programs-enhanced.html → `GET /media` → `resources/views/public/media/index.blade.php`
**Status:** PARTIAL

**Page parity:** There is deliberately no separate route/view for the earlier variant. `routes/web.php:108` and `app/Http/Controllers/Site/MediaController.php:52-119` map both design comparisons to the canonical `/media` implementation, consistent with `../CLAUDE.md:54-57`.

#### Sections & components

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Shared header with search/theme/language/profile | Shared layout supplies global chrome; media search modal is absent. | PARTIAL | `../the-last-messenger-ui/programs-enhanced.html:38-105`; `resources/views/public/media/index.blade.php:1`; no-match search grep documented above. |
| Single featured hero | Replaced by the canonical multi-slide hero, which is the correct chosen design. | IMPLEMENTED / RESTRUCTURED | `../the-last-messenger-ui/programs-enhanced.html:112-190`; `resources/views/public/media/index.blade.php:17-158`. |
| Continue Watching with remaining-time label | Real progress rail exists, but it shows percent complete rather than calculated time remaining. | PARTIAL | `../the-last-messenger-ui/programs-enhanced.html:197-237`; `resources/views/public/media/index.blade.php:251-259`. |
| Popular Shows | Replaced with canonical ranked Top-10 cards. | IMPLEMENTED / RESTRUCTURED | `../the-last-messenger-ui/programs-enhanced.html:243-287`; `resources/views/public/media/index.blade.php:278-306`. |
| Seerah Series | Dynamic category row exists. | IMPLEMENTED | `../the-last-messenger-ui/programs-enhanced.html:293-330`; `resources/views/public/media/index.blade.php:315-320`. |
| Quran Recitations with reciter | Row exists but reciter attribution remains absent. | PARTIAL | `../the-last-messenger-ui/programs-enhanced.html:336-371`; `resources/views/public/media/_program-card.blade.php:42-56`. |
| Hadith Explanations | Dynamic category row exists. | IMPLEMENTED | `../the-last-messenger-ui/programs-enhanced.html:377-415`; `resources/views/public/media/index.blade.php:315-320`. |
| Kids Content | Dynamic category row exists, but age metadata remains absent. | PARTIAL | `../the-last-messenger-ui/programs-enhanced.html:421-459`; `app/Http/Controllers/Site/MediaController.php:215-231`. |
| Search modal/recent-search placeholder | Missing, as in the canonical comparison. | MISSING | `../the-last-messenger-ui/programs-enhanced.html:466-490`; no-match search grep documented above. |
| Footer | Shared layout supplies it. | IMPLEMENTED | `../the-last-messenger-ui/programs-enhanced.html:497-526`; `resources/views/public/media/index.blade.php:1`. |
| Five-locale inline dictionary | Laravel implementation has complete public media keys in all five locales, improving on canonical premium’s AR/EN-only inline dictionary. | IMPLEMENTED | `../the-last-messenger-ui/programs-enhanced.html:537-676`; recursive public-key comparison returned zero omissions; `resources/lang/fr/media.php:17-119` is representative. |

#### Features & wiring

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Search open/close/query state | No media-specific search UI; real API exists. | PARTIAL | `../the-last-messenger-ui/programs-enhanced.html:466-490`, `685-686`; `routes/api.php:86`. |
| Rail scroll interactions | Current canonical implementation adds smart, RTL-aware arrow controls. | IMPLEMENTED / ENHANCED | `resources/views/public/media/_row.blade.php:17-34`. |
| My List | Current implementation is real for authenticated users, unlike the variant’s local-only state. | IMPLEMENTED / ENHANCED | `resources/views/public/media/index.blade.php:329-388`. |
| Remaining time | Backend has seconds and duration, so it is calculable; current UI chooses percentage. | PARTIAL | `app/Http/Controllers/Site/MediaController.php:403-406`; `resources/views/public/media/index.blade.php:257-259`. |
| Category/filter/rank/view-all differences | Earlier variant lacks canonical category pills, rank numerals, popular Filter and View All; current rank follows canonical, while the canonical controls remain missing. | DIVERGENCE RECORDED | `../the-last-messenger-ui/programs-enhanced.html:243-287`; `../the-last-messenger-ui/programs-premium.html:477-500`, `602-675`; `resources/views/public/media/index.blade.php:278-306`. |

#### Schema

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Program/translation/episode/progress/favorite data | Same real schema described for canonical listing. | IMPLEMENTED | `database/migrations/2026_06_10_200000_create_media_programs_table.php:11-27`; `database/migrations/2026_06_10_200001_create_media_program_translations_table.php:12-25`; `database/migrations/2026_06_10_200004_create_episode_watch_progress_table.php:11-27`. |
| Remaining-time calculation | `seconds` and `duration_seconds` exist; no schema change is needed. | IMPLEMENTED | `database/migrations/2026_06_10_200004_create_episode_watch_progress_table.php:19-20`; `database/migrations/2026_06_10_200002_create_media_episodes_table.php:19-21`. |
| Reciter and child age | Same partial/no explicit schema as canonical. | PARTIAL | `database/migrations/2026_06_10_200001_create_media_program_translations_table.php:18-21`; no-match schema grep documented above. |
| Five-language content | Translation tables support five locales, but `MediaProgramSeeder` writes only AR/EN. | PARTIAL | `database/seeders/MediaProgramSeeder.php:54-100`; `database/migrations/2026_06_10_200001_create_media_program_translations_table.php:17-24`. |

#### Enhancement gaps

- `resources/views/public/media/index.blade.php:257-259` — consider restoring the earlier variant’s remaining-time text alongside percentage when duration is known; this is P3-level polish, not a canonical requirement. `../CLAUDE.md:208-209` makes premium canonical.
- `database/seeders/MediaProgramSeeder.php:54-100` — seed FR/TR/UR or explicitly document Arabic fallback (MED-021). Violates `../CLAUDE.md:47-50` and `211-214`.
- All canonical hub findings MED-004 through MED-006 and MED-014 through MED-024 also apply because this variant maps to the same implementation; evidence is in the canonical section above.

## Episode player

### episode-premium.html → `GET /media/{slug}/episodes/{episode}` → `resources/views/public/media/episode.blade.php`
**Status:** PARTIAL

**Page parity:** `routes/web.php:112-114` — numeric episode route exists; `app/Http/Controllers/Site/MediaController.php:150-180` — controller resolves program, season, episode, next episode, and related episode list; `resources/views/public/media/episode.blade.php:1-10` — dedicated Blade view exists.

#### Sections & components

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Minimal episode header/back/language/profile | Shared full public header replaces the minimal prototype header; a localized back-to-program breadcrumb remains. | IMPLEMENTED / RESTRUCTURED | `../the-last-messenger-ui/episode-premium.html:192-235`; `resources/views/public/media/episode.blade.php:1`, `323-329`. |
| 16:9 YouTube video player/poster | Custom YouTube IFrame API player with poster, unavailable state, and generic iframe fallback exists. | IMPLEMENTED | `../the-last-messenger-ui/episode-premium.html:242-255`; `resources/views/public/media/episode.blade.php:40-225`. |
| Custom controls: play/pause, seek ±10, timeline, volume, speed, fullscreen | All implemented for recognized YouTube embed IDs. | IMPLEMENTED | `resources/views/public/media/episode.blade.php:105-208`. |
| Next-episode overlay with SVG ring, thumbnail, Play/Cancel | Real next episode is resolved in the service and rendered as a full-player five-second countdown near media end. | IMPLEMENTED / ENHANCED | `../the-last-messenger-ui/episode-premium.html:256-352`; `resources/views/public/media/episode.blade.php:230-310`; `app/Domain/Media/Repositories/MediaEpisodeRepository.php:143-159`. |
| Episode title/series/meta: duration, rating, views, year, maturity, match | Title, series, duration, rating, views, year and maturity exist; 98% match is omitted. | PARTIAL | `../the-last-messenger-ui/episode-premium.html:358-383`; `resources/views/public/media/episode.blade.php:331-397`. |
| Like/share/save/download actions | Share is real Web Share/clipboard; save is real program favorite; like is localStorage-only; download is absent. | PARTIAL | `../the-last-messenger-ui/episode-premium.html:388-414`; `resources/views/public/media/episode.blade.php:359-381`, `729-783`. |
| Program/channel info and subscriber count | Program poster/title, episode count and host are shown; subscriber count has no implementation. | PARTIAL | `../the-last-messenger-ui/episode-premium.html:418-426`; `resources/views/public/media/episode.blade.php:340-357`. |
| Collapsible description | Implemented with escaped episode/program text, meta and show-more/less. | IMPLEMENTED | `../the-last-messenger-ui/episode-premium.html:428-442`; `resources/views/public/media/episode.blade.php:384-406`. |
| Comments thread/composer | No UI hook, endpoint, comment-body table, moderation or thread rendering. DEFERRED by product decision. | MISSING / DEFERRED | `../the-last-messenger-ui/episode-premium.html:444-622`; `rg -n -e "comment" -e "reply" resources/views/public/media/episode.blade.php routes/api.php app/Domain/Media` returned no media comments; `../CLAUDE.md:56-57`. |
| More Episodes sticky sidebar | Real same-program episode links, thumbnail/duration, overflow scroll and empty state are implemented. | IMPLEMENTED | `../the-last-messenger-ui/episode-premium.html:624-643`; `resources/views/public/media/episode.blade.php:441-485`. |
| Transcript | Added beyond canonical; per-locale transcript is parsed safely into optional timestamp rows. | ENHANCEMENT | `resources/views/public/media/episode.blade.php:408-438`; `app/Http/Controllers/Site/MediaController.php:344-381`. |
| Footer | Shared layout supplies it. | IMPLEMENTED | `../the-last-messenger-ui/episode-premium.html:646-648`; `resources/views/public/media/episode.blade.php:1`. |

#### Features & wiring

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Video vs audio | Episode schema and page are video-URL-only. The shared audio player is Quran-specific and is not mounted here. | PARTIAL / VIDEO ONLY | `database/migrations/2026_06_10_200002_create_media_episodes_table.php:19-20`; `resources/views/components/site/audio-player.blade.php:34` — Quran translation keys; `rg -n -e "audio_url" -e "media_type" app/Domain/Media database/migrations/2026_06_10_200002_create_media_episodes_table.php` returned no matches. |
| Poster/thumbnail handling | Spatie thumbnail first; YouTube `hqdefault` fallback; gradient/unavailable fallback. Public URL is the original attachment, not the `thumb` conversion. | PARTIAL | `app/Http/Controllers/Site/MediaController.php:255-265`, `486-499`; `app/Domain/Media/Models/MediaEpisode.php:98-115`. |
| Initial play | The first poster click initializes the player but `onReady` explicitly does not call `playVideo`, so a second interaction can be required. | PARTIAL | `resources/views/public/media/episode.blade.php:545-583`. |
| YouTube/direct video compatibility | Only `youtube.com/embed/{11-char-id}` gets custom controls/timing. Watch/short URLs or direct MP4 URLs accepted by admin validation fall to an iframe without custom timing/next automation. | PARTIAL | `app/Http/Controllers/Site/MediaController.php:486-499`; `app/Http/Requests/Admin/MediaProgram/StoreMediaEpisodeRequest.php:24`; `resources/views/public/media/episode.blade.php:209-215`. |
| Playback position/save/resume | Schema, authenticated FormRequest and API exist, but player JS never reads or writes progress. | MISSING | `app/Http/Requests/Api/StoreWatchProgressRequest.php:15-29`; `app/Http/Controllers/Api/MediaProgramController.php:185-210`; no-match player grep documented above. |
| Playlist/next episode | Real next episode is selected in season/episode order; countdown appears in the final five seconds and links to it. | IMPLEMENTED | `app/Domain/Media/Repositories/MediaEpisodeRepository.php:143-159`; `resources/views/public/media/episode.blade.php:636-659`. |
| Speed control | Real 0.5×–2× menu wired to YouTube `setPlaybackRate`. | IMPLEMENTED | `resources/views/public/media/episode.blade.php:174-196`, `668-673`. |
| Download | No control, downloadable file column/collection, signed route, or API exists. Generic interaction enum support alone does not provide a download. | MISSING | `resources/views/public/media/episode.blade.php:359-381` — complete action row; `app/Domain/Media/Models/MediaEpisode.php:169-184` — thumbnail-only collection; grep `rg -n "download" routes app/Http/Controllers app/Domain/Media resources/views/public/media` found no episode-download path. |
| Share | Web Share with clipboard fallback is wired; it does not persist a share metric. | IMPLEMENTED / PARTIAL METRIC | `resources/views/public/media/episode.blade.php:773-783`; `app/Domain/Shared/Enums/InteractionType.php:11-21` — share type exists but the episode page does not call it. |
| Like/count | Like state is only per-browser localStorage; no server request or count is displayed. Generic interactions can store LIKE but are not connected. | PARTIAL | `resources/views/public/media/episode.blade.php:729-732`; `app/Domain/Shared/Services/InteractionService.php:53-90`; `rg -n -e "InteractionType::LIKE" -e "recordInteraction" app/Http/Controllers/Site/MediaController.php` returned no matches. |
| Save/bookmark | Saves the parent program through authenticated favorites, with pending disable/rollback; it is not episode-specific. | IMPLEMENTED / DIVERGENCE | `resources/views/public/media/episode.blade.php:734-767`; `app/Http/Controllers/Site/MediaController.php:442-455`. |
| Transcript/description | Both are real and escaped on the episode page; HTML transcript input is stripped during parsing. | IMPLEMENTED | `resources/views/public/media/episode.blade.php:399-438`; `app/Http/Controllers/Site/MediaController.php:359-378`. |
| Related episodes | Implemented as all other published episodes in the same program, not recommendation-based related content. | IMPLEMENTED / BASIC | `app/Http/Controllers/Site/MediaController.php:168-179`; `resources/views/public/media/episode.blade.php:441-485`. |
| View count safety | Atomic DB increment is used, but every page/API detail request increments before playback and has no session/IP/dedupe guard; cached models can render a stale count. | PARTIAL / INFLATABLE | `app/Domain/Media/Models/MediaEpisode.php:131-134`; `app/Domain/Media/Services/MediaEpisodeService.php:92-104`; `app/Domain/Media/Repositories/MediaEpisodeRepository.php:127-140`. |
| Error/loading/empty | Unavailable video and empty related-episode states exist; YouTube errors show text, but the player has no retry action or loading label/skeleton. | PARTIAL | `resources/views/public/media/episode.blade.php:96-101`, `219-225`, `449-453`; `../CLAUDE.md:221-232`. |

#### Schema

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Episode identity/order/video/duration/views/feature | Backed by `media_episodes`, unique per program/season/number. | IMPLEMENTED | `database/migrations/2026_06_10_200002_create_media_episodes_table.php:11-29`. |
| Publishing | `is_published` and `publish_at` with publisher index exist. | IMPLEMENTED | `database/migrations/2026_06_12_100000_add_publishing_to_media_episodes_table.php:11-18`. |
| Translatable title/description/transcript/SEO | Fully supported by translation migrations. | IMPLEMENTED | `database/migrations/2026_06_10_200003_create_media_episode_translations_table.php:12-29`; `database/migrations/2026_06_11_200000_add_transcript_to_media_episode_translations.php:14-18`; `database/migrations/2026_06_12_110000_add_seo_fields_to_media_episode_translations_table.php:11-17`. |
| Seasons | No season entity/table; numeric `season_number` is sufficient for current filter/order but cannot store season title/artwork/description/release metadata. The checked `series` table belongs to articles, not Media. | PARTIAL | `database/migrations/2026_06_10_200002_create_media_episodes_table.php:13-18`; `database/migrations/2025_10_27_100002_create_series_table.php:16-23`; `database/migrations/2026_03_09_162423_create_article_series_table.php:16-24`; `rg -n -e "create_seasons" -e "class MediaSeason" -e "season_title" app/Domain/Media database/migrations` returned no matches. |
| Watch progress | Dedicated, owner-unique schema and authenticated API exist. | IMPLEMENTED (UNUSED BY PLAYER) | `database/migrations/2026_06_10_200004_create_episode_watch_progress_table.php:11-27`; `routes/api.php:92-96`. |
| Thumbnail | Spatie attachment and `thumb` conversion exist, but original is used publicly. | PARTIAL | `app/Domain/Media/Models/MediaEpisode.php:98-115`, `169-184`. |
| Audio/download | No audio source, media-kind discriminator, downloadable asset, or episode file collection. | MISSING | `database/migrations/2026_06_10_200002_create_media_episodes_table.php:16-23`; `app/Domain/Media/Models/MediaEpisode.php:169-184`. |
| Like/share/download counts | Generic polymorphic interactions can represent these events, but no episode aggregation columns/relationship/API are wired to this page. | PARTIAL | `app/Domain/Shared/Enums/InteractionType.php:11-22`; `app/Domain/Shared/Models/UserInteraction.php:42-53`; no-match Site controller grep documented above. |
| Subscriber count | No subscription/follow relation for a media program. | MISSING | `rg -n -e "subscriber" -e "subscription" -e "follow" app/Domain/Media database/migrations` returned no Media-domain matches. |
| Comments | Generic COMMENT interaction is only an event type; no comment text/reply/moderation schema exists in Media. DEFERRED. | MISSING / DEFERRED | `app/Domain/Shared/Enums/InteractionType.php:11-22`; `rg -n -e "create_comments" -e "comment_body" -e "parent_comment" app/Domain/Media database/migrations` returned no media matches; `../CLAUDE.md:56-57`. |
| Public episode list privacy | Publishing schema exists, but the public API repository does not apply it. | BROKEN | `database/migrations/2026_06_12_100000_add_publishing_to_media_episodes_table.php:11-17`; `app/Domain/Media/Repositories/MediaEpisodeRepository.php:26-35`. |
| API episode-list resource loading | Public episode pagination omits `media` although the Resource resolves `thumbnail_url`; API Continue Watching also omits episode/program media and selects only a subset of fields consumed by nested resources. | PARTIAL / N+1 | `app/Domain/Media/Repositories/MediaEpisodeRepository.php:26-35`; `app/Http/Controllers/Api/MediaProgramController.php:224-243`; `app/Http/Resources/MediaEpisodeResource.php:18-32`. |
| Episode list bounds | Public program and episode pages call `getByProgram()` which retrieves every published episode; the sidebar renders all except current. | PARTIAL | `app/Domain/Media/Repositories/MediaEpisodeRepository.php:111-124`; `app/Http/Controllers/Site/MediaController.php:168-179`. |

#### Enhancement gaps

- `app/Domain/Media/Repositories/MediaEpisodeRepository.php:26-35` — enforce published visibility in the public API (MED-002). Violates `../CLAUDE.md:248-258`.
- `resources/views/public/media/episode.blade.php:492-795` — integrate authenticated progress/resume and debounce writes (MED-003). Violates prototype functionality and leaves existing schema unused.
- `app/Domain/Media/Services/MediaEpisodeService.php:92-104` — move counting to an actual play event and dedupe/rate-limit it (MED-013). Violates `../CLAUDE.md:248-258`.
- `app/Domain/Media/Repositories/MediaEpisodeRepository.php:111-124` — paginate/bound the episode grid/sidebar (MED-017). Violates `../CLAUDE.md:164-169`.
- `resources/views/public/media/episode.blade.php:174-196` — add `aria-expanded`, menu roles and keyboard focus/escape semantics to the speed popup; localize the breadcrumb landmark (MED-026). Violates `../CLAUDE.md:221-232` accessibility hardening expectations.
- `resources/views/public/media/episode.blade.php:40-310` — extract a reusable site video-player/next-episode component rather than retaining a 300-line page-local player (MED-024). Violates `../CLAUDE.md:202-207`.

## Earlier episode variant

### episode-enhanced.html → `GET /media/{slug}/episodes/{episode}` → `resources/views/public/media/episode.blade.php`
**Status:** PARTIAL

**Page parity:** The earlier design intentionally maps to the canonical episode route/view. `../CLAUDE.md:54-57` chooses premium; `routes/web.php:112-114` and `app/Http/Controllers/Site/MediaController.php:150-180` provide the sole current player path.

#### Sections & components

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Always-dark minimal header | Replaced with canonical shared, theme-aware public layout. | IMPLEMENTED / RESTRUCTURED | `../the-last-messenger-ui/episode-enhanced.html:58-99`; `resources/views/public/media/episode.blade.php:1-10`. |
| YouTube iframe | Replaced with the stronger custom IFrame API player and fallbacks. | IMPLEMENTED / ENHANCED | `../the-last-messenger-ui/episode-enhanced.html:102-114`; `resources/views/public/media/episode.blade.php:40-225`. |
| Next episode panel below player, including next description | Canonical overlay is implemented on top of the player; it has title/program/duration but drops the earlier next-description field. | IMPLEMENTED / RESTRUCTURED | `../the-last-messenger-ui/episode-enhanced.html:115-160`; `resources/views/public/media/episode.blade.php:230-310`. |
| Episode metadata/actions | Same canonical result: share/save real, like local-only, download absent. | PARTIAL | `../the-last-messenger-ui/episode-enhanced.html:164-209`; `resources/views/public/media/episode.blade.php:331-406`. |
| Description/show more | Implemented. | IMPLEMENTED | `../the-last-messenger-ui/episode-enhanced.html:212-230`; `resources/views/public/media/episode.blade.php:384-406`. |
| Comments | No hook; DEFERRED. | MISSING / DEFERRED | `../the-last-messenger-ui/episode-enhanced.html:232-294`; no-match grep and `../CLAUDE.md:56-57` documented above. |
| More Episodes sidebar | Implemented as the canonical sticky sidebar. | IMPLEMENTED | `../the-last-messenger-ui/episode-enhanced.html:296-341`; `resources/views/public/media/episode.blade.php:441-485`. |
| Five-locale inline translations | Laravel public keys cover all five locales, improving on canonical premium’s AR/EN-only inline dictionary. | IMPLEMENTED | `../the-last-messenger-ui/episode-enhanced.html:361-474`; recursive public-key comparison returned zero missing keys. |

#### Features & wiring

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Like/save toggles | Earlier prototype toggles both locally; current save is server-backed program favorite while like stays local. | PARTIAL / ENHANCED SAVE | `../the-last-messenger-ui/episode-enhanced.html:481-521`; `resources/views/public/media/episode.blade.php:729-767`. |
| YouTube autoplay/countdown | Earlier prototype uses a fixed dummy ID and timer; current next episode is real and timing is based on player duration, but initial play can require two clicks. | PARTIAL / ENHANCED DATA | `../the-last-messenger-ui/episode-enhanced.html:529-574`; `resources/views/public/media/episode.blade.php:545-583`, `636-659`. |
| Share/download | Share is now functional; download remains absent. | PARTIAL | `resources/views/public/media/episode.blade.php:773-783`; no-match download grep documented above. |
| Speed/transcript | Added beyond the earlier variant. | ENHANCEMENT | `resources/views/public/media/episode.blade.php:174-196`, `408-438`. |
| Progress/resume | Still absent despite backend support. | MISSING | no-match player grep; `routes/api.php:92-96`. |

#### Schema

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Program/episode/translation/player data | Real schema as detailed for canonical episode. | IMPLEMENTED | `database/migrations/2026_06_10_200000_create_media_programs_table.php:11-27`; `database/migrations/2026_06_10_200002_create_media_episodes_table.php:11-29`; `database/migrations/2026_06_10_200003_create_media_episode_translations_table.php:12-29`. |
| Next episode | Ordered program/season/episode data supports deterministic next resolution. | IMPLEMENTED | `database/migrations/2026_06_10_200002_create_media_episodes_table.php:13-28`; `app/Domain/Media/Repositories/MediaEpisodeRepository.php:143-159`. |
| Likes/download/comments | Same partial/missing/deferred backend identified for canonical episode. | PARTIAL / MISSING | `app/Domain/Shared/Enums/InteractionType.php:11-22`; `app/Domain/Media/Models/MediaEpisode.php:169-184`; `../CLAUDE.md:56-57`. |
| Five-language seeded content | Tables support it; demo content is AR/EN only. | PARTIAL | `database/seeders/MediaProgramSeeder.php:54-100`; translation migration locale columns cited above. |

#### Enhancement gaps

- All canonical player findings MED-002, MED-003, MED-007 through MED-013, MED-017, MED-019, MED-020, MED-023, MED-024, MED-026 and MED-028 apply to this shared route/view; their evidence is in the canonical episode section.
- `../the-last-messenger-ui/episode-enhanced.html:115-160` — the earlier next-description content was intentionally dropped by canonical premium; do not restore it unless product requests it. `../CLAUDE.md:54-57` resolves this divergence.

## Findings checklist

- [ ] **MED-001** [P0] Sanitize program rich text before rendering it raw
      - **Module:** Media program detail  ·  **Type:** SECURITY
      - **Evidence:** `resources/views/public/media/program.blade.php:220-223` — raw `{!! $program['description'] !!}`; `app/Http/Requests/Admin/MediaProgram/StoreMediaProgramRequest.php:40-45` — arbitrary strings are accepted with no sanitizer in the checked Media write path.
      - **What to do:** Sanitize every locale in `StoreMediaProgramRequest::prepareForValidation()` and `UpdateMediaProgramRequest::prepareForValidation()` with the project HTMLPurifier allowlist; persist only sanitized HTML; add feature tests for `<script>`, `onerror`, `javascript:` and allowed formatting. Keep raw Blade only after that invariant is enforced.
      - **Why it matters / trade-offs:** A compromised or malicious admin payload becomes stored JavaScript for every public visitor. Sanitization may remove unsupported editor markup, so align the allowlist with the editor.

- [ ] **MED-002** [P0] Exclude draft and scheduled episodes from the public episodes API
      - **Module:** Media API / episode  ·  **Type:** SECURITY
      - **Evidence:** `app/Domain/Media/Repositories/MediaEpisodeRepository.php:26-35` — `paginateByProgram()` has no `published()` predicate; `routes/api.php:89` — endpoint is public.
      - **What to do:** Add `->published()` to the public repository path (or a dedicated `paginatePublishedByProgram()`), ensure scheduled rows remain hidden until the publisher flips `is_published`, and add API tests asserting drafts/future schedules and their `video_url` never appear.
      - **Why it matters / trade-offs:** Unreleased titles, descriptions and video URLs are currently enumerable without authentication.

- [ ] **MED-003** [P1] Wire player playback-position save and resume
      - **Module:** Episode player  ·  **Type:** MISSING
      - **Evidence:** `routes/api.php:92-96` — progress endpoints exist; grep `rg -n "media/progress|continue-watching|episode_watch_progress" resources/views/public/media/episode.blade.php` returned no matches.
      - **What to do:** Pass the authenticated user’s progress into `MediaController@episode`; seek in `onReady`; debounce POSTs to `/api/v1/media/progress` during playback/pause/unload; mark completed near the end; include CSRF/Sanctum handling and offline/error fallback.
      - **Why it matters / trade-offs:** Continue Watching advertises resume but currently opens at zero. Writes must be throttled/debounced to avoid excessive DB traffic.

- [ ] **MED-004** [P1] Restore canonical category-filter pills on the programs hub
      - **Module:** Programs hub  ·  **Type:** MISSING
      - **Evidence:** `../the-last-messenger-ui/programs-premium.html:477-501` — canonical pills; `resources/views/public/media/index.blade.php:161-163` — implementation explicitly omits the filter bar.
      - **What to do:** Add All/Seerah/Quran/Hadith/Kids pills, backed by validated category values; either filter already-loaded rows accessibly in Alpine or use a query-string/server route that preserves shareable URLs, focus state and an empty/reset state.
      - **Why it matters / trade-offs:** Users cannot narrow the dense rail stack. Client filtering is faster; server filtering scales better and is linkable.

- [ ] **MED-005** [P1] Add a real program-search modal to `/media`
      - **Module:** Programs hub  ·  **Type:** MISSING
      - **Evidence:** `../the-last-messenger-ui/programs-premium.html:949-978` — canonical modal; `routes/api.php:86` — rate-limited search exists; grep `rg -n "searchOpen|searchQuery|recentSearch" resources/views/public/media/index.blade.php` returned no matches.
      - **What to do:** Build an accessible dialog under `components/site`, use `text-base sm:text-sm`, debounce the existing API, display loading/error/empty states, escape results, close on Escape/backdrop, trap/restore focus, and either implement owner-scoped recent searches or omit that label honestly.
      - **Why it matters / trade-offs:** A working backend discovery path is invisible on the page; recent-search persistence introduces privacy/ownership requirements.

- [ ] **MED-006** [P1] Implement the canonical Popular Filter/View All and rail View All paths
      - **Module:** Programs hub  ·  **Type:** MISSING
      - **Evidence:** `../the-last-messenger-ui/programs-premium.html:612-620` and `682-932` — Filter/View All controls; `app/Http/Controllers/Site/MediaController.php:67-70` — one fixed top-10 result; grep `rg -n "view_all|filter" resources/views/public/media/index.blade.php` returned no matches.
      - **What to do:** Define whitelisted filter semantics (category/year/rating/order), add a paginated programs result route or query-string mode, and point every View All control at it with preserved category/sort state.
      - **Why it matters / trade-offs:** Rails are capped by design, so users need a complete browse path. This adds a result-page state not presently represented in the canonical static HTML.

- [ ] **MED-007** [P1] Provide an authorized episode download path or remove the promise from product scope
      - **Module:** Episode player  ·  **Type:** MISSING
      - **Evidence:** `../the-last-messenger-ui/episode-premium.html:406-413` — Download action; `app/Domain/Media/Models/MediaEpisode.php:169-184` — only thumbnail media exists; grep `rg -n "download" routes app/Domain/Media resources/views/public/media` found no episode-download endpoint.
      - **What to do:** Add an explicit downloadable media attachment/source, MIME/extension/size validation, entitlement/publication checks, signed/streamed response with safe server filename, optional download interaction recording, and the localized action/loading/error UI—or formally drop the prototype action.
      - **Why it matters / trade-offs:** A visible canonical capability has no backend at all; downloadable copies add storage, copyright and bandwidth obligations.

- [ ] **MED-008** [P1] Replace local-only episode likes with server-backed engagement
      - **Module:** Episode player  ·  **Type:** PARTIAL
      - **Evidence:** `resources/views/public/media/episode.blade.php:729-732` — localStorage toggle; `app/Domain/Shared/Services/InteractionService.php:53-90` — generic interaction persistence exists but is unused here.
      - **What to do:** Add authenticated, owner-derived like/unlike endpoints through one service/repository, uniqueness semantics, rate limits, aggregate count query, optimistic rollback UI and guest-login behavior; do not trust a client-supplied user ID.
      - **Why it matters / trade-offs:** Likes do not follow users across devices and cannot support the prototype count. Generic append-only interactions need deduplication semantics before use as toggles.

- [ ] **MED-009** [P1] Normalize supported episode video sources and player capability
      - **Module:** Episode player  ·  **Type:** PARTIAL
      - **Evidence:** `app/Http/Controllers/Site/MediaController.php:486-499` — only YouTube embed URLs are recognized; `app/Http/Requests/Admin/MediaProgram/StoreMediaEpisodeRequest.php:24` — any URL is accepted.
      - **What to do:** Introduce a provider/type enum plus normalized provider ID/source URL; validate YouTube watch/short/embed forms or direct video formats; render an HTML5 `<video>` path for allowed files; expose consistent duration/error/next-event adapters and reject unsupported embeds.
      - **Why it matters / trade-offs:** Accepted URLs can silently lose custom controls, progress and next-episode behavior. Supporting direct media increases browser-format/CORS work.

- [ ] **MED-010** [P1] Make the first player interaction actually start playback
      - **Module:** Episode player  ·  **Type:** PARTIAL
      - **Evidence:** `resources/views/public/media/episode.blade.php:545-583` — `startPlayback()` initializes the API, while `onReady` explicitly avoids `playVideo()`.
      - **What to do:** Track a user-gesture play request and call `player.playVideo()` from `onReady` for that request; keep autoplay-policy handling and show a retry/play state when blocked; add a browser test for one-click start.
      - **Why it matters / trade-offs:** The poster CTA can require a confusing second click. Browser autoplay rules require tying play to the original gesture.

- [ ] **MED-011** [P1] Serve derived artwork instead of plaintext public originals
      - **Module:** Media assets  ·  **Type:** SECURITY
      - **Evidence:** `app/Domain/Media/Models/MediaProgram.php:134-157` and `218-245` — original URL accessors plus registered conversions on public disk; `app/Http/Controllers/Admin/Media/MediaProgramController.php:406-412` — encrypted library original is decrypted and copied to Spatie; `config/filesystems.php:41-47` — public disk maps to `/storage`.
      - **What to do:** Keep originals private/encrypted, generate page-sized derivatives, expose only controlled conversion/variant URLs, map cards to poster `thumbnail`, banner `medium`, episode/live `thumb`, and reconcile Spatie conversions with `media_size_presets` or document one authoritative pipeline.
      - **Why it matters / trade-offs:** Current pages disclose full originals and lose at-rest protection after attachment while also paying larger transfer costs. Private delivery may require signed URLs/CDN changes.

- [ ] **MED-012** [P1] Validate upload extensions and replace client filenames
      - **Module:** Media assets  ·  **Type:** SECURITY
      - **Evidence:** `app/Http/Requests/Admin/MediaProgram/StoreMediaProgramRequest.php:58-59`, `app/Http/Requests/Admin/MediaProgram/StoreMediaEpisodeRequest.php:27`, and `app/Http/Requests/Admin/LiveChannel/StoreLiveChannelRequest.php:42` — MIME/mimes rules have no `extensions`; `vendor/spatie/laravel-medialibrary/src/MediaCollections/FileAdder.php:110-113` — client original name is used.
      - **What to do:** Add matching `extensions:jpg,jpeg,png,webp` to every store/update/inline image request; call `usingFileName()` with a server UUID/ULID plus validated extension for direct and library-copy attachments; retain original name only as non-executable metadata; add spoofed-extension tests.
      - **Why it matters / trade-offs:** This violates the explicit upload boundary and exposes client-chosen names in public paths. Random names reduce human readability, which metadata can preserve safely.

- [ ] **MED-013** [P1] Count genuine plays without refresh inflation or stale cache display
      - **Module:** Media programs/episodes  ·  **Type:** SECURITY
      - **Evidence:** `app/Domain/Media/Services/MediaEpisodeService.php:92-104` and `app/Domain/Media/Services/MediaProgramService.php:87-97` — each detail resolution increments; `app/Domain/Media/Repositories/MediaEpisodeRepository.php:127-140` — pre-increment detail is cached.
      - **What to do:** Record a play after a minimum playback threshold, dedupe by authenticated user or privacy-safe session/IP window, rate-limit anonymous events, increment atomically in the repository, and invalidate/update cached counters or keep volatile counts outside cached detail objects.
      - **Why it matters / trade-offs:** Refresh/API loops manipulate ranking and cached objects can show old counts. Stronger dedupe improves integrity but changes the meaning of historical counters.

- [ ] **MED-014** [P1] Include the media catalog in an intentional default seed path
      - **Module:** Media content  ·  **Type:** MISSING
      - **Evidence:** `database/seeders/MediaProgramSeeder.php:112-1388` — 16 program and 85 episode definitions; `database/seeders/DatabaseSeeder.php:31-39` — seeder is omitted.
      - **What to do:** Either add `MediaProgramSeeder::class` to `DatabaseSeeder` after categories, or create/document a production/demo content seeder profile and make deployment invoke it; add a fresh-seed assertion for expected program/episode/video counts.
      - **Why it matters / trade-offs:** A fresh default install can still show an empty hub despite the catalog claim. Production may intentionally avoid demo content, so make the profile explicit.

- [ ] **MED-015** [P1] Model and render canonical rail-specific metadata
      - **Module:** Programs hub / episode  ·  **Type:** SCHEMA
      - **Evidence:** `../the-last-messenger-ui/programs-premium.html:746-932` — reciter/age metadata; `../the-last-messenger-ui/episode-premium.html:418-426` — subscribers; `database/migrations/2026_06_10_200000_create_media_programs_table.php:11-27` — no match/age/subscriber fields.
      - **What to do:** Decide which prototype labels remain requirements; render `host` as reciter if semantically valid or add a reciter/content-credit relation; add nullable `age_min`/`age_max` (with validation/index only if filtered); add real follow/subscriber relation if needed; compute recommendation match rather than store fake percentages.
      - **Why it matters / trade-offs:** Generic cards lose meaning for Quran/kids and the episode channel row cannot show the canonical audience signal. New schema is unnecessary if product drops those labels.

- [ ] **MED-016** [P2] Bound category rails in SQL
      - **Module:** Programs hub  ·  **Type:** PERF
      - **Evidence:** `app/Domain/Media/Repositories/MediaProgramRepository.php:217-226` — all matching programs are loaded before per-group `take($perCategory)`.
      - **What to do:** Use a MySQL 8 `ROW_NUMBER() OVER (PARTITION BY category ORDER BY display_order,id)` subquery capped at 12, or issue a small fixed set of individually limited queries; retain explicit selects/eager loads/counts and measure with EXPLAIN.
      - **Why it matters / trade-offs:** Memory/query transfer grows with the complete catalog despite a 12-card UI. Window SQL is efficient but database-specific.

- [ ] **MED-017** [P2] Paginate or cap public program episode collections
      - **Module:** Program detail / episode sidebar  ·  **Type:** PERF
      - **Evidence:** `app/Domain/Media/Repositories/MediaEpisodeRepository.php:111-124` — `getByProgram()` has no limit; `app/Http/Controllers/Site/MediaController.php:168-179` — all episodes are passed to the player.
      - **What to do:** Add a bounded initial episode page and paginated/load-more endpoint; query next episode separately (already available); cap sidebar recommendations independently; preserve season filters with server pagination.
      - **Why it matters / trade-offs:** Long-running series make every program/player response scale linearly. Pagination complicates client-side season switching.

- [ ] **MED-018** [P2] Align media API list queries with their Resource contracts
      - **Module:** Media API  ·  **Type:** PERF
      - **Evidence:** `app/Domain/Media/Repositories/MediaProgramRepository.php:118-126` and `230-241` omit `media`; `app/Domain/Media/Repositories/MediaEpisodeRepository.php:26-35` does likewise; `app/Http/Controllers/Api/MediaProgramController.php:224-243` under-selects nested Continue Watching resources; `app/Http/Resources/MediaEpisodeResource.php:18-32` reads those fields/media.
      - **What to do:** Align every select/eager load with its Resource, including episode/program media in list and Continue Watching paths, or create explicit compact list/progress resources; constrain media columns and add constant-query-count plus response-field tests for 1 vs 20 rows.
      - **Why it matters / trade-offs:** Artwork access can lazy-load per row and omitted attributes silently serialize null/default values. Smaller resources reduce payload but change API shape.

- [ ] **MED-019** [P2] Validate media API query parameters and rate-limit progress writes
      - **Module:** Media API  ·  **Type:** SECURITY
      - **Evidence:** `app/Http/Controllers/Api/MediaProgramController.php:38-46`, `60-64`, `76-80`, `149-154`, `218-235` — raw Request values are only upper-capped; `routes/api.php:93-95` — progress mutation has auth but no throttle.
      - **What to do:** Add FormRequests with integer `min:1|max:*`, enum category, whitelisted sort/direction and sane season limits; add a progress-specific throttle compatible with debounced playback; return localized/generic 422 errors rather than caught 500s.
      - **Why it matters / trade-offs:** Zero/negative limits can reach paginator/SQL and progress can be write-spammed. A throttle must allow normal periodic checkpoints.

- [ ] **MED-020** [P2] Restore the mandated controller-service-repository boundary
      - **Module:** Media web/API  ·  **Type:** ENHANCEMENT
      - **Evidence:** `app/Http/Controllers/Site/MediaController.php:29-46` — four services injected; `app/Http/Controllers/Api/MediaProgramController.php:196-205` and `224-236` — direct Eloquent writes/queries.
      - **What to do:** Introduce one public Media facade/application service per controller; move progress upsert and Continue Watching query behind `MediaEpisodeService` and its repository interface; keep controllers to validation/authorization/response mapping.
      - **Why it matters / trade-offs:** Current code violates `../CLAUDE.md:128-136`, spreads business/query logic into HTTP layers and makes reuse/testing harder. A facade adds one orchestration abstraction.

- [ ] **MED-021** [P2] Seed media content in all five supported locales
      - **Module:** Media content  ·  **Type:** I18N
      - **Evidence:** `database/seeders/MediaProgramSeeder.php:54-100` — program and episode loops are hard-coded to `['ar','en']`; `../CLAUDE.md:47-50` requires AR/EN/FR/TR/UR.
      - **What to do:** Add editorially reviewed FR/TR/UR translations with `is_ready`, or document/finalize the Arabic fallback policy and expose missing-translation status to admins; add coverage checks by locale.
      - **Why it matters / trade-offs:** Chrome translations are complete but seeded titles/descriptions fall back to Arabic in three locales. Machine translation without review would lower content quality.

- [ ] **MED-022** [P2] Align indexes and search operators with public media queries
      - **Module:** Media schema  ·  **Type:** SCHEMA
      - **Evidence:** `database/migrations/2026_06_10_200000_create_media_programs_table.php:14-26` omits leading `is_published`; `database/migrations/2026_06_10_200001_create_media_program_translations_table.php:27-31` adds FULLTEXT; `app/Domain/Media/Repositories/MediaProgramRepository.php:230-241` nevertheless searches with `LIKE "%term%"`.
      - **What to do:** Use EXPLAIN at realistic volume, then add only demonstrated composites such as `(is_published, views_count)`, `(is_published, is_featured, display_order)`, `(is_published, is_trending, display_order)`, and `(is_published, category, display_order)`; use parameterized `MATCH(title,description) AGAINST` for eligible MySQL search terms with a deliberate fallback; remove superseded indexes where safe.
      - **Why it matters / trade-offs:** Existing rail indexes need extra filtering/filesort, while leading-wildcard LIKE cannot use the existing FULLTEXT index. Composites cost writes/storage and FULLTEXT tokenization changes substring behavior, hence measurement/fallbacks.

- [ ] **MED-023** [P2] Separate media loading, empty and error states
      - **Module:** Programs hub / episode  ·  **Type:** ENHANCEMENT
      - **Evidence:** `app/Http/Controllers/Site/MediaController.php:100-108` — exceptions become empty arrays; `resources/views/public/media/index.blade.php:165-176` — the catalog-empty UI is then shown.
      - **What to do:** Pass a recoverable error state/request ID, render a localized retry action distinct from editorial emptiness, and add contextual player loading/error retry states; retain existing empty states.
      - **Why it matters / trade-offs:** Outages are presented as “no programs,” hiding operational failures and giving users no recovery action.

- [ ] **MED-024** [P2] Extract reusable site rail and video-player components
      - **Module:** Media frontend  ·  **Type:** ENHANCEMENT
      - **Evidence:** `resources/views/public/media/_row.blade.php:1-35` — reusable include outside `components/site`; `resources/views/public/media/episode.blade.php:40-310` — large page-local player/overlay.
      - **What to do:** Move the rail, program/rank/live cards and player/next overlay into typed Blade components under `resources/views/components/site/media/`; keep Alpine factories in a shared asset/module and give each component explicit props/states.
      - **Why it matters / trade-offs:** The project rule at `../CLAUDE.md:202-207` requires components for reuse; extraction reduces drift but should preserve current tested markup/behavior.

- [ ] **MED-025** [P3] Restore canonical hero motion details deliberately
      - **Module:** Programs hub  ·  **Type:** ENHANCEMENT
      - **Evidence:** `../the-last-messenger-ui/programs-premium.html:1240-1293` — parallax behavior; `resources/views/public/media/index.blade.php:390-429` — carousel has no parallax and uses a seven-second interval.
      - **What to do:** If product still wants it, add pointer-only subtle parallax with transform bounds and `prefers-reduced-motion` disable; reconcile the canonical five-second timing with the current seven seconds.
      - **Why it matters / trade-offs:** This is polish, not missing content. Motion adds GPU work and can harm comfort without reduced-motion handling.

- [ ] **MED-026** [P3] Complete player popup and landmark accessibility semantics
      - **Module:** Episode player  ·  **Type:** A11Y
      - **Evidence:** `resources/views/public/media/episode.blade.php:174-196` — speed popup lacks `aria-expanded`, menu roles/focus handling; `resources/views/public/media/episode.blade.php:323` — breadcrumb landmark label is hard-coded English.
      - **What to do:** Add button/menu ARIA linkage, keyboard arrow/Escape/focus return behavior, selected-state announcement, and a translated breadcrumb key in all five locale files; verify with keyboard and a screen reader.
      - **Why it matters / trade-offs:** Mouse/touch users can operate the menu, but assistive-technology and keyboard context is incomplete.

- [ ] **MED-027** [P3] Remove the duplicate episode ordering index after plan verification
      - **Module:** Media schema  ·  **Type:** SCHEMA
      - **Evidence:** `database/migrations/2026_06_10_200002_create_media_episodes_table.php:25-28` — a unique and a normal index cover the same `(program_id, season_number, episode_number)` columns.
      - **What to do:** Confirm with `SHOW INDEX`/EXPLAIN that the unique index serves lookup/order paths, then drop the redundant normal index in a reversible migration with its actual generated name.
      - **Why it matters / trade-offs:** Duplicate indexes cost storage/write work; retain it only if a measured optimizer difference justifies it.

- [ ] **MED-028** [P3] Preserve explicit deferred hooks for comments and live chat
      - **Module:** Episode/live  ·  **Type:** MISSING
      - **Evidence:** `../CLAUDE.md:56-57` — comments/live chat are deferred but hooks should remain; grep `rg -n "comment|reply|chat|message" resources/views/public/media routes/api.php app/Domain/Media` found no episode-comment or live-chat hook.
      - **What to do:** Add non-interactive, localized extension slots/feature-flagged placeholders and document expected component/event contracts; do not build persistence, moderation or realtime transport until the deferred decision changes.
      - **Why it matters / trade-offs:** This keeps later integration seams visible without pretending deferred functionality works or elevating it to P0.

## Doc corrections

- `../WEB_APP_MODULES_ANALYSIS.md:58` and `433-472` — **stale:** Media is not a stub and the listed domain/API/progress/next/search gaps are mostly implemented. Current evidence: `routes/api.php:80-96`, `app/Domain/Media/Models/MediaProgram.php:40-245`, `app/Domain/Media/Models/MediaEpisode.php:31-184`, and `resources/views/public/media/episode.blade.php:40-485`.
- `MODULES-AND-PAGES-INVENTORY.md:254-282` — **stale/wrong route inventory:** it says stub, `/media/programs`, `/media/episode/{id}`, and empty `/api/v1/media`. Actual public routes are `routes/web.php:108-114`, and actual API routes are `routes/api.php:80-96`.
- `../KNOWN_ISSUES.md:15` — **partly stale:** episode page/player and static content seeder now exist. The “0 rows” runtime claim cannot be verified statically, and MED-014 shows the default seeder still omits the catalog.
- `../KNOWN_ISSUES.md:13` and `../PROJECT_PROGRESS.md:72` — **stale:** the former Spatie/custom-table collision was addressed by `database/migrations/2026_06_11_180000_create_spatie_media_table.php:7-37` plus `config/media-library.php:34-38`. The current asset defect is different: plaintext public originals and bypassed conversions (MED-011).
- `../PROJECT_PROGRESS.md:25`, `61`, and `../TASK_BOARD.md:27` — **overstated:** the hub/player/API are real and the seeder statically contains 16/85/14, but “seeded” is not guaranteed by the default seed path (`database/seeders/DatabaseSeeder.php:31-39`), and hub “parity” remains partial due to MED-004/005/006.
- `../TASK_BOARD.md:47-50` — **internally stale:** C-1 through C-4 remain marked Todo below the completed combined C-1..4 row at line 27. Routes/controller/views/search integration prove the lower rows obsolete.
- Repo-local `CLAUDE.md:413-415` and `822-823` — **stale:** it calls Media Programs partial/video-incomplete and Search absent. The mandatory workspace `../CLAUDE.md:52-57` plus current code should govern this review.
- `../WEB_APP_MODULES_ANALYSIS.md:448` — **prototype characterization is inaccurate:** it credits the premium hub with all five inline language dictionaries, but `../the-last-messenger-ui/programs-premium.html:1047-1117` contains only AR/EN; the earlier `../the-last-messenger-ui/programs-enhanced.html:537-676` contains AR/EN/TR/FR/UR. Laravel currently improves on premium with complete public keys in five files.

## Open questions

- Runtime database row counts and whether `MediaProgramSeeder` was manually run in a deployed environment cannot be determined without querying the DB; no migration/seeder was executed for this static review. Evidence for the static/default distinction is `database/seeders/MediaProgramSeeder.php:112-1388` versus `database/seeders/DatabaseSeeder.php:31-39`.
- The validity, ownership/licensing, region availability and embeddability of the 14 non-null seeded YouTube URLs require network/runtime verification; this review only verified their static presence in `database/seeders/MediaProgramSeeder.php`.
- Whether copying decrypted originals into public Spatie collections is an intentional exception to encrypted-at-rest policy needs a security/product decision. The behavior is explicit at `app/Http/Controllers/Admin/Media/MediaProgramController.php:406-412`, while the public disk is explicit at `app/Domain/Media/Models/MediaProgram.php:218-230`.
- Exact query plans/index value require MySQL 8 `EXPLAIN` against realistic media volume; MED-022 is based on static predicate/index comparison, not a runtime optimizer plan.
- YouTube autoplay behavior varies by browser/privacy policy. Static code proves `onReady` does not request playback (`resources/views/public/media/episode.blade.php:545-583`); a browser test is needed to quantify the one-click failure rate.
- Live streaming is implemented despite being deferred (`resources/views/public/media/live.blade.php:28-54`; `../CLAUDE.md:56-57`). A product owner should decide whether to feature-flag the real stream, retain it, or treat only chat as still deferred.
- No assigned page received a thinner pass: all four prototypes were read in full and each has all four required parity subsections above.
