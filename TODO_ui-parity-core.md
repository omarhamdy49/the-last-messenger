# CORE & CONTENT public UI parity and gap review
**Scope:** `index-enhanced.html`, `login-enhanced.html`, `register-enhanced.html`, `profile-enhanced.html`, `articles-all-enhanced.html`, `article-single-enhanced.html`, `topic-enhanced.html`, `search-enhanced.html`  ·  **Method:** static read of prototypes + Laravel code + migrations

## Executive summary

| Module | Prototype pages | Implemented | Verdict (OK / PARTIAL / MISSING) | Top gap |
|---|---:|---:|---|---|
| Home | 1 | 1/1 | PARTIAL | Only module cards and quick-search chips are admin-managed; several large sections and controls remain hardcoded/inert. |
| Authentication | 2 | 2/2 | PARTIAL | OAuth is wired, but new social-only accounts do not receive the normal public `user` role and registration does not capture terms consent. |
| Profile | 1 | 1/1 | PARTIAL | Core account/favorites mutations are safe, but reading history/progress, saved searches, preferences, achievements, 2FA, and export are incomplete or absent. |
| Articles | 2 | 2/2 | PARTIAL | Listing parity is high; detail lacks the prototype author/engagement/comments experiences and the public view counter accepts any article ID. |
| Topics | 1 | 1/1 | PARTIAL | Real Quran/Hadith/article aggregation exists, but Seerah/video, admin curation, publication filtering, and pagination do not. |
| Search | 1 | 1/1 | PARTIAL | The public query can expose unpublished content; full-text indexes are not used, popular sort breaks, and pagination/load-more is discarded. |

- **P0 — unpublished-content exposure:** `app/Domain/Shared/Services/SearchService.php:393-414` starts each public search from an unconstrained model query, while `app/Http/Controllers/Site/SearchController.php:83-94` supplies no publication filters.
- **P0 — topic exposure:** `app/Domain/Topics/Repositories/TopicRepository.php:70-88` loads linked articles without status/ready constraints and `app/Domain/Topics/Services/TopicService.php:174-205` maps them directly to public cards.
- All eight prototypes have real public pages; the old “no page” claims are stale. `routes/web.php:24,76-79,116-121,143-210` is the verified route surface.
- Social login is wired end to end through routes, Socialite, config, provider columns, and conditional buttons. `routes/web.php:166-173`, `config/services.php:38-51`, `database/migrations/2026_06_11_240000_add_social_login_columns_to_users_table.php:20-31`, and `resources/views/public/auth/login.blade.php:92-121` are the evidence.
- Home queries are bounded/cached and the card/chip repository uses scoped selects plus eager loading; no home-carousel N+1 was found. `app/Http/Controllers/Site/HomeController.php:38-94` and `app/Domain/Content/Repositories/HomeContentRepository.php:21-49`.
- Topics use a deptrac-safe Bootstrap morph-map composition point rather than direct sibling-domain imports inside `Domain/Topics`. `app/Providers/TopicsServiceProvider.php:32-39` and `deptrac.yaml:173-176`. This is safe, but not yet the extensible Shared contract/registry requested for cross-module discovery.
- Public profile and favorite mutations are acting-user scoped and use FormRequests. `app/Http/Controllers/Site/ProfileController.php:83-198` and `app/Http/Requests/Site/StoreFavoriteRequest.php:18-49`. No profile/favorites IDOR was found.
- Article listing parity is strongest: search, categories, tags, sort, both layouts, active filters, skeleton, empty state, and numbered pagination are implemented. Its 500-row client-side ceiling is the material scalability gap. `app/Repositories/ArticleRepository.php:186-206`.
- Literal translation keys used by the assigned views were present in the five locale trees; the actionable i18n issues found are dynamic/raw values, notably `resources/views/public/home.blade.php:702-706` and `app/Http/Controllers/Site/SearchController.php:334-354`.

## Home / landing

### index-enhanced.html → GET `/` → `resources/views/public/home.blade.php`
**Status:** PARTIAL

Page parity: `routes/web.php:24` binds `GET /` to `Site\HomeController@index`; `app/Http/Controllers/Site/HomeController.php:34-103` returns `public.home`.

#### Sections & components

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Sticky header, mobile navigation, search modal, language and dark-mode controls | Shared header implements all except the prototype notification bell/panel. | PARTIAL | `../the-last-messenger-ui/index-enhanced.html:397-542` — prototype header/search/notifications; `resources/views/components/site/header.blade.php:55-303` — implemented header and search modal, with no notification control. |
| Hero artwork, animated title, search, quick chips | Present; search submits to unified search and chips are DB-backed. | IMPLEMENTED | `../the-last-messenger-ui/index-enhanced.html:544-650`; `resources/views/public/home.blade.php:139-208`. |
| Hero three-card module discovery | Present but duplicated as hardcoded Seerah/Hadith/Media markup rather than managed records. | PARTIAL | `../the-last-messenger-ui/index-enhanced.html:596-650`; `resources/views/public/home.blade.php:211-258`. |
| Featured Sahaba carousel, arrows, skeleton/empty state, “view all” | Real featured companions, explicit limit 8, reusable card, RTL-aware rail controls. | IMPLEMENTED | `../the-last-messenger-ui/index-enhanced.html:652-960`; `app/Http/Controllers/Site/HomeController.php:38-43`; `resources/views/public/home.blade.php:263-384`. |
| Prophet identity card, biography facts, timeline stats, action links | Visually present, but the content is translation-file/static markup rather than an editable content record. | PARTIAL | `../the-last-messenger-ui/index-enhanced.html:962-1145`; `resources/views/public/home.blade.php:386-565`. |
| Daily Quran verse and Hadith | Real random content, cached per day; links work. Empty/error fallback is mislabeled “loading.” | PARTIAL | `../the-last-messenger-ui/index-enhanced.html:1147-1294`; `app/Http/Controllers/Site/HomeController.php:45-80`; `resources/views/public/home.blade.php:567-750`. |
| Main module grid and live statistics | Cards/chips are admin-managed and translated; four statistics are real/constant and cached. | IMPLEMENTED | `../the-last-messenger-ui/index-enhanced.html:1296-1607`; `resources/views/public/home.blade.php:752-838`; `routes/admin/content.php:40-57`. |
| Featured-content trio | Present as three fixed images/translation keys/target modules, not actual featured records. | PARTIAL | `../the-last-messenger-ui/index-enhanced.html:1609-1664`; `resources/views/public/home.blade.php:840-914`. |
| Guest CTA | Present and guest-only. | IMPLEMENTED | `../the-last-messenger-ui/index-enhanced.html:1666-1690`; `resources/views/public/home.blade.php:916-947`. |
| Footer links, social links, newsletter | Menus/social/site text are DB-composed; newsletter is client-only toast with no persistence. | PARTIAL | `../the-last-messenger-ui/index-enhanced.html:1691-1756`; `resources/views/components/site/footer.blade.php:24-89`. |

#### Features & wiring

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Hero/global search | Real `GET /search?q=…`. | IMPLEMENTED | `resources/views/public/home.blade.php:164-185`; `routes/web.php:120-121`. |
| Quick-filter chips | Real links, translated DB rows, admin CRUD. | IMPLEMENTED | `app/Domain/Content/Services/HomeContentService.php:30-70`; `routes/admin/content.php:50-57`. |
| Sahaba carousel interactions | Alpine rail state, arrows, scroll behavior and bounded result set. | IMPLEMENTED | `resources/views/public/home.blade.php:263-384`; `app/Http/Controllers/Site/HomeController.php:38-43`. |
| Bookmark/share buttons on daily content and module cards | Buttons have labels/styles but no click handler, form, URL, or API call. | MISSING | `resources/views/public/home.blade.php:729-743,791-800`. |
| Notification bell/panel | Prototype has a notification drawer; shared public header has no corresponding control or route. | MISSING | `../the-last-messenger-ui/index-enhanced.html:451-507`; `resources/views/components/site/header.blade.php:78-201`. |
| Newsletter subscription | Explicitly faked with `@submit.prevent` and a toast; no endpoint/schema found by `rg -n "newsletter\\|subscribe" routes app database/migrations`. | MISSING | `resources/views/components/site/footer.blade.php:73-81`. |
| Language/dark mode | Real locale POST and persistent theme store; logical spacing is used. | IMPLEMENTED | `resources/views/components/site/header.blade.php:88-123`; `routes/web.php:223-224`. |

#### Schema

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Managed module cards | `home_module_cards` supports key/icon/image/target/accent/featured/active/order and indexed active/order fields. | IMPLEMENTED | `database/migrations/2026_06_10_120002_create_home_module_cards_table.php:17-31`. |
| Card translations | Separate locale/title/description/readiness rows with unique card+locale. | IMPLEMENTED | `database/migrations/2026_06_10_120003_create_home_module_card_translations_table.php:13-25`. |
| Quick chips | Separate managed chip and translation tables exist and repository eager-loads translations. | IMPLEMENTED | `database/migrations/2026_06_10_120004_create_quick_filter_chips_table.php:17-30`; `database/migrations/2026_06_11_120000_create_quick_filter_chip_translations_table.php:25-37`. |
| Other home sections | No `home_sections`/featured-content/daily-selection schema; admin content routes expose only cards and quick filters for home. | MISSING | `routes/admin/content.php:40-58`; `resources/views/public/home.blade.php:840-914`. |
| Newsletter/notifications | No public subscription or notification schema/routes were found. | MISSING | `CLAUDE.md:794-798` — current limitations also state no real-time notifications; `routes/web.php:1-224` — no subscription/notification endpoint. |
| Carousel query support | Featured Sahaba call is explicitly limited and cached; no unbounded carousel query or per-card query in Blade. | IMPLEMENTED | `app/Http/Controllers/Site/HomeController.php:38-43`; `app/Domain/Content/Repositories/HomeContentRepository.php:32-49`. |

#### Enhancement gaps

- `resources/views/public/home.blade.php:211-258,386-565,840-914` — make hero modules, Prophet block, and featured content editorial data rather than static Blade/translation content. This conflicts with the project’s managed-content expectation and repository-only access rule at `CLAUDE.md:425-437`. See CORE-017.
- `resources/views/public/home.blade.php:729-743,791-800` — inert action buttons advertise persistence/sharing that never happens. This violates the task’s frontend functionality and error-feedback rules. See CORE-018.
- `resources/views/public/home.blade.php:623-634,723-727` — caught backend errors and true empty data both render a permanent “loading” message; add distinct empty/error/retry states. Task quality rule: missing loading/empty/error states. See CORE-030.
- `app/Http/Controllers/Site/HomeController.php:82-89` — the four cold-cache stats are separate domain aggregate calls, but one is a constant and all are cached together. No safe cross-domain SQL combination or N+1 was found; retaining module services is preferable to illegal joins under `CLAUDE.md:425-430`.
- `app/Domain/Content/Repositories/HomeContentRepository.php:21-49` — positive finding: list queries use explicit selects and constrained eager loads, satisfying the task performance checks.

## Login

### login-enhanced.html → GET `/login` → `resources/views/public/auth/login.blade.php`
**Status:** PARTIAL

Page parity: `routes/web.php:143-146` maps GET/POST login to `AuthController@showLogin/login`; `app/Http/Controllers/Site/AuthController.php:28-49` returns `public.auth.login` and performs session regeneration.

#### Sections & components

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Two-column marketing/benefit panel | Guest layout has branded background/card, but the prototype’s three-benefit panel is omitted. | PARTIAL | `../the-last-messenger-ui/login-enhanced.html:161-219`; `resources/views/public/auth/login.blade.php:1-28`. |
| Login/register tab switch | Separate routes/pages replace the in-card tabs; register link is present below the form. | PARTIAL | `../the-last-messenger-ui/login-enhanced.html:226-235`; `resources/views/public/auth/login.blade.php:123-131`. |
| Email/password/show-hide/errors | Real form, old input, validation errors, icons, and password reveal. | IMPLEMENTED | `../the-last-messenger-ui/login-enhanced.html:247-286`; `resources/views/public/auth/login.blade.php:29-65`. |
| Remember/forgot | Both are wired. | IMPLEMENTED | `resources/views/public/auth/login.blade.php:67-78`; `routes/web.php:152-163`. |
| Submit spinner/double-submit protection | Real disabled/loading state. | IMPLEMENTED | `resources/views/public/auth/login.blade.php:81-88`. |
| Google/Facebook and continue-as-guest | OAuth buttons are conditional and real; explicit guest CTA is absent (the site itself remains public). | PARTIAL | `../the-last-messenger-ui/login-enhanced.html:309-344`; `resources/views/public/auth/login.blade.php:92-121`. |

#### Features & wiring

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Credential login | `LoginRequest`, `Auth::attempt`, remember flag, session regeneration, safe intended redirect. | IMPLEMENTED | `app/Http/Controllers/Site/AuthController.php:33-63`. |
| Login throttling | POST is throttled 5/minute. | IMPLEMENTED | `routes/web.php:145-146`. |
| Password reset | Full request/reset route flow with throttled POST endpoints. | IMPLEMENTED | `routes/web.php:152-163`. |
| Google/Facebook redirect and callback | Provider whitelist, config check, Socialite callback, link/create flow and avatar import exist. | IMPLEMENTED | `routes/web.php:166-173`; `app/Http/Controllers/Site/SocialAuthController.php:37-90,109-204`. |
| OAuth provider configuration | Standard client/secret/redirect config exists; static review cannot establish deployed environment values. | IMPLEMENTED | `config/services.php:38-51`. |
| Email verification | Notice, signed callback and resend routes work, but browsing/profile is not gated by `verified`. | PARTIAL | `routes/web.php:196-202`; `app/Http/Controllers/Site/AuthController.php:87-89,102-138`. |

#### Schema

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Session auth/reset/verification | Users include password, verification timestamp, remember token; reset tokens and sessions exist. | IMPLEMENTED | `database/migrations/0001_01_01_000000_create_users_table.php:14-45`. |
| Social identities | Nullable provider/provider_id plus composite unique index. | IMPLEMENTED | `database/migrations/2026_06_11_240000_add_social_login_columns_to_users_table.php:20-31`. |
| Avatar | `avatar_path` is used by social and profile controllers; provider fetch uses server-generated storage path. | IMPLEMENTED | `app/Http/Controllers/Site/SocialAuthController.php:180-197`; `app/Http/Controllers/Site/ProfileController.php:121-146`. |

#### Enhancement gaps

- `app/Http/Controllers/Site/SocialAuthController.php:147-173` — social account creation never calls the normal `Role::findOrCreate/assignRole` path at `app/Http/Controllers/Site/AuthController.php:81-85`; normalize account provisioning. Security/authorization consistency rule. See CORE-016.
- `app/Http/Controllers/Site/AuthController.php:87-89` — the controller explicitly says browsing is not verification-gated. Decide and document which authenticated mutations require `verified`; apply middleware where trust is required. Task security rule. See CORE-027.
- `resources/views/public/auth/login.blade.php:1-28,123-131` — marketing panel, tab interaction, and explicit guest path are visual/product parity gaps, not broken authentication. See CORE-032.

## Registration

### register-enhanced.html → GET `/register` → `resources/views/public/auth/register.blade.php`
**Status:** PARTIAL

Page parity: `routes/web.php:148-150` maps GET/POST registration to `AuthController@showRegister/register`; `app/Http/Controllers/Site/AuthController.php:66-97` returns `public.auth.register`.

#### Sections & components

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Name/email/password/confirmation | All four real fields with localized errors. | IMPLEMENTED | `../the-last-messenger-ui/register-enhanced.html:219-314`; `resources/views/public/auth/register.blade.php:29-113`. |
| Password strength meter | Alpine strength state/segments and server strength rules exist. | IMPLEMENTED | `../the-last-messenger-ui/register-enhanced.html:106-129,261-276`; `resources/views/public/auth/register.blade.php:67-101,176-218`. |
| Required terms/privacy consent | Replaced by passive copy; there is no required checkbox or linked consent fields. | MISSING | `../the-last-messenger-ui/register-enhanced.html:315-327`; `resources/views/public/auth/register.blade.php:116`; `app/Http/Requests/Site/RegisterRequest.php:29-37`. |
| Submit loading state | Real disabled/spinner state. | IMPLEMENTED | `resources/views/public/auth/register.blade.php:119-126`. |
| Social registration | Conditional real Google/Facebook links. | IMPLEMENTED | `resources/views/public/auth/register.blade.php:130-159`. |

#### Features & wiring

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Registration validation | Dedicated FormRequest validates unique email and mixed-case/numeric confirmed password. | IMPLEMENTED | `app/Http/Requests/Site/RegisterRequest.php:16-37`. |
| Account creation/default role | Creates the user, assigns non-admin `user`, fires Registered, logs in, regenerates session. | IMPLEMENTED | `app/Http/Controllers/Site/AuthController.php:71-96`. |
| Rate limiting | Registration POST is throttled 5/minute. | IMPLEMENTED | `routes/web.php:148-150`. |
| Consent | Neither browser nor server requires/records terms/privacy acceptance. | MISSING | `resources/views/public/auth/register.blade.php:116`; `app/Http/Requests/Site/RegisterRequest.php:29-37`. |

#### Schema

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| User identity/preferences | Name/email/password/language/preferences/last-login are supported. | IMPLEMENTED | `database/migrations/0001_01_01_000000_create_users_table.php:14-30`. |
| Terms/privacy consent | No `terms_accepted_at`, policy version, or consent audit table/column appears in the user migration path. | MISSING | `database/migrations/0001_01_01_000000_create_users_table.php:14-30`; `database/migrations/2026_06_11_240000_add_social_login_columns_to_users_table.php:20-31`. |

#### Enhancement gaps

- `resources/views/public/auth/register.blade.php:116` — restore an explicit required terms/privacy checkbox with route links and server validation; decide whether timestamp/version must be audited. Task form validation/security rule. See CORE-015.
- `app/Http/Controllers/Site/SocialAuthController.php:147-173` — social registration must share the same role/default provisioning as form registration. See CORE-016.

## User profile / dashboard

### profile-enhanced.html → GET `/profile` → `resources/views/public/profile/index.blade.php`
**Status:** PARTIAL

Page parity: authenticated `routes/web.php:180-209` exposes profile, avatar, password, deletion, verification and favorites; `app/Http/Controllers/Site/ProfileController.php:42-77` returns `public.profile.index`.

#### Sections & components

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Hero/avatar/user stats | Real user, avatar upload form, verification banner, engagement/bookmark metrics. | IMPLEMENTED | `../the-last-messenger-ui/profile-enhanced.html:363-465`; `resources/views/public/profile/index.blade.php:48-120`. |
| Dashboard tab | Real summary and six recent bookmarks; less detailed than prototype activity/content cards. | PARTIAL | `../the-last-messenger-ui/profile-enhanced.html:531-618`; `resources/views/public/profile/index.blade.php:140-177`. |
| Bookmarks tab | Preview plus link to real filterable/paginated `/favorites`; no inline grid/list/remove experience in the tab. | PARTIAL | `../the-last-messenger-ui/profile-enhanced.html:619-748`; `resources/views/public/profile/index.blade.php:180-210`; `routes/web.php:204-209`. |
| Progress tab | Only aggregate metrics/empty state, not per-content progress cards/bars. | PARTIAL | `../the-last-messenger-ui/profile-enhanced.html:749-788`; `resources/views/public/profile/index.blade.php:212-237`. |
| Achievements tab | Omitted; no achievement/badge data model found by `rg -n "achievement|badge" app database/migrations`. | MISSING | `../the-last-messenger-ui/profile-enhanced.html:789-853`; `resources/views/public/profile/index.blade.php:20-26`. |
| Activity tab | Real interaction rows and timestamps, but item title/link are reduced to morph class basename. | PARTIAL | `../the-last-messenger-ui/profile-enhanced.html:854-886`; `resources/views/public/profile/index.blade.php:239-274`. |
| Settings/account/language/password/delete | Real acting-user forms with loading protection and a mobile-safe delete modal. | IMPLEMENTED | `../the-last-messenger-ui/profile-enhanced.html:887-1029`; `resources/views/public/profile/index.blade.php:277-437`. |
| Dark/notification preferences, 2FA, data export | Language exists; dark/notification preference, 2FA, and export UI are unavailable. | MISSING | `resources/views/public/profile/index.blade.php:278-370`; `app/Http/Controllers/Site/ProfileController.php:69-77`. |
| Saved searches/search history | Tables/services/API exist, but no profile/public-session UI exposes them. | MISSING | `database/migrations/2025_10_31_075947_create_search_history_table.php:16-34`; `database/migrations/2025_10_31_075950_create_saved_filters_table.php:16-35`; `resources/views/public/profile/index.blade.php:20-26`. |

#### Features & wiring

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Profile/avatar/password/delete mutations | Dedicated FormRequests, acting user only, session cleanup and double-submit controls. | IMPLEMENTED | `app/Http/Controllers/Site/ProfileController.php:83-198`; `resources/views/public/profile/index.blade.php:327-355,389-437`. |
| Favorites object authorization | Type is enum-whitelisted and service calls use `$request->user()`; no user ID is accepted. | IMPLEMENTED | `app/Http/Requests/Site/StoreFavoriteRequest.php:18-49`; `app/Http/Controllers/Site/FavoriteController.php:69-113`. |
| Reading/view history collection | Public web records only authenticated media episode views; assigned article/topic/search pages do not record Shared interactions. | PARTIAL | `app/Http/Controllers/Site/MediaController.php:458-472`; `rg -n "InteractionService|recordView" app/Http/Controllers/Site` — only Media/Profile references. |
| Engagement export | Backend exporter exists, but profile controller deliberately sets `exportAvailable` false. | PARTIAL | `app/Domain/Shared/Services/InteractionService.php:336-364`; `app/Http/Controllers/Site/ProfileController.php:69-77`. |
| Saved-filter ownership | Update/delete/default/apply access is owner/public scoped; no IDOR found. | IMPLEMENTED | `app/Domain/Shared/Services/SavedFilterService.php:84-139,161-188`. |

#### Schema

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Favorites | Polymorphic favorites and user interactions have user/content indexes and cascade deletes. | IMPLEMENTED | `database/migrations/2025_10_27_100004_create_favorites_table.php:16-37`. |
| Detailed interactions/progress | Type enum includes view/like/share/bookmark/comment/download/print/search/progress/complete; user/date and content/type indexes exist. | IMPLEMENTED | `database/migrations/2025_10_31_074635_extend_user_interactions_table_for_engagement_tracking.php:19-66`. |
| Search history/preferences/saved filters | Dedicated tables exist with user/module indexes. | IMPLEMENTED | `database/migrations/2025_10_31_075947_create_search_history_table.php:16-34`; `database/migrations/2025_10_31_075950_create_saved_filters_table.php:16-35`. |
| User preferences | JSON preference storage exists but the profile UI only persists language as a first-class field. | PARTIAL | `database/migrations/0001_01_01_000000_create_users_table.php:20-22`; `resources/views/public/profile/index.blade.php:305-315`. |
| Achievements/2FA | No achievement/badge table and no two-factor secret/recovery columns were found. | MISSING | `rg -n "achievement|two_factor|recovery_codes" app database/migrations` — no matching user feature/schema. |

#### Enhancement gaps

- `app/Http/Controllers/Site/MediaController.php:458-472` — wire Shared interaction recording into article/Hadith/Quran/Seerah readers, and render per-content progress/history; schema already supports it. Task feature parity and repository/service rules. See CORE-012.
- `resources/views/public/profile/index.blade.php:20-26` — surface saved searches/history/preferences using the existing authenticated API/services. See CORE-013.
- `app/Http/Controllers/Site/ProfileController.php:69-77` — finish or explicitly remove advertised export, 2FA, achievements, and notification preference surfaces. See CORE-014.
- `resources/views/public/profile/index.blade.php:251-269` — map eager-loaded `interactable` to localized title and URL rather than displaying a PHP class basename. See CORE-012.
- `app/Http/Controllers/Site/FavoriteController.php:48-53` — the catch block immediately reruns the identical failing call, so the promised graceful degradation is false. Task error-state rule. See CORE-025.

## Articles listing

### articles-all-enhanced.html → GET `/articles` → `resources/views/public/articles/index.blade.php`
**Status:** PARTIAL

Page parity: `routes/web.php:75-79` maps listing/detail/view count; `app/Http/Controllers/Site/ArticleController.php:32-93` returns `public.articles.index`.

#### Sections & components

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Hero/search | Real Alpine client filter over server-provided records. | IMPLEMENTED | `../the-last-messenger-ui/articles-all-enhanced.html:432-458`; `resources/views/public/articles/index.blade.php:40-74`. |
| Category sidebar/counts and tag cloud | Real published-only `withCount` facets and selectable UI. | IMPLEMENTED | `../the-last-messenger-ui/articles-all-enhanced.html:510-589`; `resources/views/public/articles/index.blade.php:80-145`; `app/Repositories/ArticleRepository.php:345-381`. |
| Sort/grid/list/active filters | Latest/oldest/popular/title, persisted layouts, filter chips and reset. | IMPLEMENTED | `../the-last-messenger-ui/articles-all-enhanced.html:591-639`; `resources/views/public/articles/index.blade.php:152-221`. |
| Grid/list cards | Image/category/tags/title/excerpt/date/read time/views/featured are present; prototype author/avatar is omitted. | PARTIAL | `../the-last-messenger-ui/articles-all-enhanced.html:657-824`; `resources/views/public/articles/index.blade.php:250-346`. |
| Loading and empty state | Skeleton/status plus resettable empty state. | IMPLEMENTED | `resources/views/public/articles/index.blade.php:223-242,348-359`. |
| Numbered pagination | Client-side numbered prev/next pagination. | IMPLEMENTED | `../the-last-messenger-ui/articles-all-enhanced.html:826-861`; `resources/views/public/articles/index.blade.php:361-390`. |

#### Features & wiring

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Search/filter/sort | Functional in Alpine against a real payload. | IMPLEMENTED | `resources/views/public/articles/index.blade.php:424-464`. |
| Server scalability | Repository silently caps the catalogue at 500 and sends it all to the DOM/JSON for client processing. | PARTIAL | `app/Repositories/ArticleRepository.php:186-206`; `app/Http/Controllers/Site/ArticleController.php:25-30`. |
| View preference | Grid/list preference is persisted client-side. | IMPLEMENTED | `resources/views/public/articles/index.blade.php:176-196,424-464`. |
| Article navigation | Every grid/list card links to the localized detail page. | IMPLEMENTED | `resources/views/public/articles/index.blade.php:250-300,307-345`. |

#### Schema

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Core article card data | Writer/read time/views/featured/category/Hijri date and useful indexes exist. | IMPLEMENTED | `database/migrations/2025_10_27_120000_create_articles_table.php:14-35`. |
| Localized title/slug/excerpt/content | Locale table has readiness/locale/slug indexes and unique article+locale. | IMPLEMENTED | `database/migrations/2025_10_27_120001_create_article_locale_table.php:15-39`. |
| Categories/tags | Repository has proper constrained relations/facet aggregates; no list N+1 found. | IMPLEMENTED | `app/Repositories/ArticleRepository.php:188-206,345-381`. |
| Author card data | `writer_id` supports a user name/avatar, but no public author bio/follow relationship supports prototype richness. | PARTIAL | `database/migrations/2025_10_27_120000_create_articles_table.php:17-18`; `database/migrations/0001_01_01_000000_create_users_table.php:14-24`. |

#### Enhancement gaps

- `app/Repositories/ArticleRepository.php:186-206` — replace the implicit 500-row ceiling with server-side filter/sort/pagination (or cursor API) and retain explicit selects/eager loads. Task list-query/performance rule. See CORE-022.
- `resources/views/public/articles/index.blade.php:250-346` — add the prototype author identity to both card modes if author attribution remains a product requirement. See CORE-011.
- `resources/views/public/articles/index.blade.php:286-328` — several tertiary dates use `dark:text-gray-500`, below the supplied dark-mode contrast rule. See CORE-031.

## Article detail

### article-single-enhanced.html → GET `/articles/{slug}` → `resources/views/public/articles/show.blade.php`
**Status:** PARTIAL

Page parity: `routes/web.php:77-79` maps detail and async view registration; `app/Http/Controllers/Site/ArticleController.php:99-160` returns `public.articles.show`.

#### Sections & components

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Breadcrumb/reading progress/gallery/lightbox | Present with real gallery, autoplay, thumbnails, keyboard arrows and fullscreen view. | IMPLEMENTED | `../the-last-messenger-ui/article-single-enhanced.html:325-500`; `resources/views/public/articles/show.blade.php:168-323`. |
| Hero/meta/title/author card | Dates/read time/views/title exist; author name is only metadata/JSON-LD and no visible author card/bio/follow is rendered. | PARTIAL | `../the-last-messenger-ui/article-single-enhanced.html:501-539`; `app/Http/Controllers/Site/ArticleController.php:227-248`; `resources/views/public/articles/show.blade.php:329-375`. |
| Like/bookmark/share/print/font controls | Share and font size work; like, favorite/bookmark and print buttons are absent despite favorite endpoint/print CSS. | PARTIAL | `../the-last-messenger-ui/article-single-enhanced.html:540-580`; `resources/views/public/articles/show.blade.php:348-363`; `routes/web.php:204-209`. |
| Rich body/TOC/references/tags | Real sanitized body, generated/editor TOC, references and tags. | IMPLEMENTED | `resources/views/public/articles/show.blade.php:377-454`; `app/Http/Controllers/Site/ArticleController.php:222-317`. |
| Previous/next | Real published+ready adjacent queries. | IMPLEMENTED | `../the-last-messenger-ui/article-single-enhanced.html:700-727`; `resources/views/public/articles/show.blade.php:461-489`; `app/Repositories/ArticleRepository.php:307-335`. |
| Related carousel | Six real related records exist, but render as compact sidebar cards rather than the prototype horizontal carousel. | PARTIAL | `../the-last-messenger-ui/article-single-enhanced.html:729-827`; `resources/views/public/articles/show.blade.php:550-576`; `app/Repositories/ArticleRepository.php:235-297`. |
| Comments/replies/load more | Entire user-comments section is absent; grep found only Hadith scholarly commentary and aggregate counters, no article-comment model/migration/route. | MISSING | `../the-last-messenger-ui/article-single-enhanced.html:829-986`; `rg -n "commentable|ArticleComment" routes app database/migrations resources/views/public/articles` — no article comments implementation. |
| Sidebar TOC/stats/tags/share | Present with scroll-spy and real mapped values. | IMPLEMENTED | `../the-last-messenger-ui/article-single-enhanced.html:992-1173`; `resources/views/public/articles/show.blade.php:499-587`. |
| Share modal | Real Twitter/Facebook/WhatsApp/copy actions. | IMPLEMENTED | `../the-last-messenger-ui/article-single-enhanced.html:1175-1227`; `resources/views/public/articles/show.blade.php:591-620`. |

#### Features & wiring

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| View counting | Async, session-debounced and route-throttled, but endpoint accepts draft/archived IDs. | PARTIAL | `app/Http/Controllers/Site/ArticleController.php:163-185`; `app/Repositories/ArticleRepository.php:384-395`; `routes/web.php:77-78`. |
| Sharing/copy | Real platform share URLs and clipboard feedback. | IMPLEMENTED | `resources/views/public/articles/show.blade.php:594-620,708-760`. |
| Bookmark/like/print | No wired control, although favorites POST/DELETE can support article bookmarks. | MISSING | `resources/views/public/articles/show.blade.php:348-363`; `routes/web.php:204-209`. |
| Comments | No endpoint or persistence. | MISSING | `routes/web.php:75-79,204-209`; `database/migrations/2025_10_27_120000_create_articles_table.php:14-35`. |
| Lightbox zoom/download | Prototype controls are absent; current lightbox supports navigate/close only. | PARTIAL | `../the-last-messenger-ui/article-single-enhanced.html:1229-1315`; `resources/views/public/articles/show.blade.php:277-321`. |

#### Schema

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Body/TOC/SEO | Locale JSON content and SEO metadata support the reader. | IMPLEMENTED | `database/migrations/2025_10_27_120001_create_article_locale_table.php:19-25`. |
| Gallery/captions/order | Gallery table exists; caption is five-locale JSON and order is indexed. | IMPLEMENTED | `database/migrations/2025_10_27_120003_create_articles_gallery_table.php:14-25`. |
| Comments/replies/moderation | No article comment table/relationship exists. | MISSING | `rg -n "ArticleComment|commentable" app database/migrations` — no result. |
| Engagement statistics/references | Stored as base-row JSON; names/types are raw strings and not locale-keyed. | PARTIAL | `database/migrations/2025_10_27_120000_create_articles_table.php:23-25`; `app/Http/Controllers/Site/ArticleController.php:276-317`. |
| Translation source | Locale rows coexist with second JSON-translatable title/excerpt/content/SEO fields and duplicate featured/read-time columns on `articles`. | PARTIAL | `database/migrations/2025_10_27_120001_create_article_locale_table.php:15-25`; `database/migrations/2026_06_11_100000_add_seo_fields_to_articles_table.php:18-34`. |

#### Enhancement gaps

- `app/Repositories/ArticleRepository.php:387-395` — require `status=published` plus at least one ready locale before incrementing, and return 404/422 for invalid public IDs. Task security/data-integrity rule. See CORE-019.
- `app/Repositories/ArticleRepository.php:389-394` — invalidate `public_articles_index_*`/detail cache keys when views change or move volatile view counts outside hour-cached payloads. Task cache consistency/performance rule. See CORE-023.
- `resources/views/public/articles/show.blade.php:329-375` — render author identity and wire bookmark/like/print; schema work is needed for bio/follow. See CORE-011.
- `database/migrations/2025_10_27_120000_create_articles_table.php:23-25` — localize reference/stat labels and retire one of the two article translation systems. This conflicts with `CLAUDE.md:449-461,500-511`. See CORE-024.
- `resources/views/public/articles/show.blade.php:594-620` — share modal lacks `role=dialog`, `aria-modal`, focus trapping, focus return, and `max-h/overflow-y-auto`; gallery uses English dynamic aria labels at lines 237 and 255. Task accessibility/mobile-modal rules. See CORE-035.

## Topic hub

### topic-enhanced.html → GET `/topics/{slug}` → `resources/views/public/topics/show.blade.php`
**Status:** PARTIAL

Page parity: `routes/web.php:116-118` maps topic index/show; `app/Http/Controllers/Site/TopicController.php:58-83` returns `public.topics.show`.

#### Sections & components

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Breadcrumb/hero/stats | Real topic translation and real result counts. | IMPLEMENTED | `../the-last-messenger-ui/topic-enhanced.html:271-325`; `resources/views/public/topics/show.blade.php:63-104`. |
| Sticky Quran/Hadith/Media tabs | Present with counts; “Media” currently means articles only. | PARTIAL | `../the-last-messenger-ui/topic-enhanced.html:326-369`; `resources/views/public/topics/show.blade.php:106-135`. |
| Quran cards | Real verse/surah/translation data and empty state; prototype share/tafsir action parity is incomplete. | PARTIAL | `../the-last-messenger-ui/topic-enhanced.html:374-414`; `resources/views/public/topics/show.blade.php:138-176`. |
| Hadith cards | Real text/source/grade/detail link and empty state; bookmark action is absent. | PARTIAL | `../the-last-messenger-ui/topic-enhanced.html:415-455`; `resources/views/public/topics/show.blade.php:178-214`. |
| Media articles/videos toggle | Article cards exist; no sub-toggle, video/episode cards, duration/views/date. | MISSING | `../the-last-messenger-ui/topic-enhanced.html:456-522`; `resources/views/public/topics/show.blade.php:216-252`. |
| Seerah aggregation requested by cluster brief | Neither prototype tab nor implementation has a Seerah group; the cluster requirement explicitly expands the desired hub to Seerah. | MISSING | `app/Domain/Topics/Enums/TopicItemType.php:16-27`; `app/Domain/Topics/Services/TopicService.php:66-98`. |
| Pagination/load state | Per-tab empty states exist; no pagination/load-more and every linked row renders at once. | PARTIAL | `resources/views/public/topics/show.blade.php:138-252`; `app/Domain/Topics/Repositories/TopicRepository.php:70-88`. |

#### Features & wiring

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Cross-module aggregation | Real polymorphic Verse/Hadith/Article links, not keyword fakes. | IMPLEMENTED | `app/Domain/Topics/Services/TopicService.php:58-99`; `database/migrations/2026_06_10_210002_create_topic_items_table.php:11-27`. |
| Module isolation | Topics domain imports no sibling models; provider registers concrete morphs at Bootstrap, allowed by deptrac. | IMPLEMENTED | `app/Providers/TopicsServiceProvider.php:17-39`; `deptrac.yaml:173-176,185-197`. |
| Extensibility registry/contract | Fixed enum/provider/eager-load switch requires edits in three places for every module; no Shared contribution contract exists. | PARTIAL | `app/Domain/Topics/Enums/TopicItemType.php:16-27`; `app/Domain/Topics/Repositories/TopicRepository.php:29-46`; `app/Providers/TopicsServiceProvider.php:35-39`. |
| Bookmark/share/tafsir | Prototype actions are absent from the topic cards. | MISSING | `../the-last-messenger-ui/topic-enhanced.html:385-451`; `resources/views/public/topics/show.blade.php:138-214`. |
| Admin curation | Population exists only through `TopicSeeder`/repository sync; no Admin Topic controller/routes were found. | MISSING | `database/seeders/TopicSeeder.php:117-138`; `rg -n "TopicController|topics" routes/admin.php routes/admin app/Http/Controllers/Admin` — no topic CRUD. |

#### Schema

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Topic identity/presentation | Slug/icon/color/featured/order and compound featured/order index exist. | IMPLEMENTED | `database/migrations/2026_06_10_210000_create_topics_table.php:11-21`. |
| Topic translations | Locale title/description/readiness with unique topic+locale and full-text index. | IMPLEMENTED | `database/migrations/2026_06_10_210001_create_topic_translations_table.php:12-31`. |
| Polymorphic membership | Unique topic/type/id and hot-path topic/type/order indexes exist. | IMPLEMENTED | `database/migrations/2026_06_10_210002_create_topic_items_table.php:11-27`. |
| Supported content kinds | Schema string is generic, but enum/morph map/service only recognize verse, hadith, article; no Seerah/media support. | PARTIAL | `app/Domain/Topics/Enums/TopicItemType.php:16-27`; `app/Providers/TopicsServiceProvider.php:35-39`. |
| Publication/readiness | Membership has no publication state and query does not constrain linked article `status` or locale `is_ready`. | MISSING | `app/Domain/Topics/Repositories/TopicRepository.php:70-88`; `app/Domain/Topics/Services/TopicService.php:174-205`. |

#### Enhancement gaps

- `app/Domain/Topics/Repositories/TopicRepository.php:70-88` — constrain public itemables through module-provided “public/ready” contracts before mapping. Security rule; current article links can expose drafts. See CORE-002.
- `app/Domain/Topics/Enums/TopicItemType.php:16-27` — add Seerah/media/video contributors and admin attach/detach/reorder CRUD through a Shared contract/registry, keeping Bootstrap as composition root per `CLAUDE.md:425-430`. See CORE-008/CORE-009.
- `app/Domain/Topics/Services/TopicService.php:66-82` — three sequential per-type reads are bounded in number and eager-loaded, so this is not N+1; however, each is unbounded. Paginate each tab or lazy-load it. See CORE-021.
- `app/Domain/Topics/Repositories/TopicRepository.php:49-59` — positive finding: index counts use `withCount` in one topic query rather than collection aggregation/per-row counts.

## Unified search

### search-enhanced.html → GET `/search` → `resources/views/public/search/index.blade.php`
**Status:** PARTIAL

Page parity: `routes/web.php:120-121` maps `GET /search` to `SearchController@index`; `app/Http/Controllers/Site/SearchController.php:36-75` returns `public.search.index`.

#### Sections & components

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Search input/clear/suggestion chips | Real GET form, clear and popular chips; no live suggestion request while typing. | PARTIAL | `../the-last-messenger-ui/search-enhanced.html:210-260`; `resources/views/public/search/index.blade.php:47-114`. |
| Results summary and query | Server total is passed, but displayed Alpine label counts only the first loaded cards. | PARTIAL | `resources/views/public/search/index.blade.php:116-130,345-349`; `app/Http/Controllers/Site/SearchController.php:65-74`. |
| Per-type tabs/filters and counts | Sidebar checkboxes with real facets for Quran/Hadith/article/Seerah/Sahaba; prototype video is absent. | PARTIAL | `../the-last-messenger-ui/search-enhanced.html:315-368`; `resources/views/public/search/index.blade.php:169-220`. |
| Source filters | Prototype Seerah/Bukhari/Muslim controls are absent; the prototype JS itself only filters types, so this also corrects a prototype fake. | MISSING | `../the-last-messenger-ui/search-enhanced.html:370-400,662-670`; `resources/views/public/search/index.blade.php:185-220`. |
| Sort | Relevance/date/popular controls exist; relevance is ID order and popular is incompatible with several schemas. | PARTIAL | `resources/views/public/search/index.blade.php:209-218`; `app/Domain/Shared/Search/SearchQueryBuilder.php:91-107`. |
| List/grid cards and highlighting | Both real layouts; highlighting escapes text before adding `<mark>`. | IMPLEMENTED | `../the-last-messenger-ui/search-enhanced.html:419-522`; `resources/views/public/search/index.blade.php:226-304,384-397`. |
| Bookmark/share | Share uses Web Share/clipboard; bookmark only emits a success toast and stores nothing. | PARTIAL | `resources/views/public/search/index.blade.php:252-269,399-407`; `routes/web.php:204-209`. |
| Empty/load-more/pagination | Empty state exists; no load-more or pagination controls/metadata. | PARTIAL | `../the-last-messenger-ui/search-enhanced.html:524-551`; `resources/views/public/search/index.blade.php:306-313`. |

#### Features & wiring

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Unified search | Real sequential module queries for five modules. | IMPLEMENTED | `app/Http/Controllers/Site/SearchController.php:21-23,61-94`; `app/Domain/Shared/Services/SearchService.php:52-75`. |
| Publication filtering | No public filter is supplied and no global scopes constrain Article/Seerah; draft records can match. | MISSING | `app/Http/Controllers/Site/SearchController.php:83-94`; `app/Domain/Shared/Services/SearchService.php:393-414`; `app/Models/Article.php:52-67,130-136`. |
| Full-text | Builder uses full-text only when the parent model defines `getFullTextColumns`; no searched model does, so every module takes relation/OR-LIKE fallback. | PARTIAL | `app/Domain/Shared/Search/SearchQueryBuilder.php:27-50,173-195`; `rg -n "getFullTextColumns" app` — only this builder appears. |
| Filter/sort whitelist | Public controller safely allowlists sort and type values. Saved-filter sort accepts arbitrary strings and reaches `orderBy`. | PARTIAL | `app/Http/Controllers/Site/SearchController.php:42-46,302-305`; `app/Http/Requests/SaveFilterRequest.php:36-45`; `app/Domain/Shared/Builders/FilterBuilder.php:253-266`. |
| Rate limiting | API search group is `throttle:search`; public GET `/search` has no throttle. | MISSING | `routes/api.php:155-169`; `routes/web.php:120-121`. |
| Pagination | Each module internally paginates 12 and counts, but controller flattens only `items()` and discards next/last-page metadata. | MISSING | `app/Domain/Shared/Services/SearchService.php:63-75,115-151`; `app/Http/Controllers/Site/SearchController.php:101-130`. |
| Error state | Any module exception is caught around the entire search and returned as zero results, indistinguishable from a valid empty query. | PARTIAL | `app/Http/Controllers/Site/SearchController.php:83-99`; `resources/views/public/search/index.blade.php:306-313`. |
| Shared architecture | `Shared\Enums\SearchModule` returns concrete sibling-domain model classes; deptrac violation is suppressed in baseline. | PARTIAL | `app/Domain/Shared/Enums/SearchModule.php:112-126`; `deptrac.baseline.yaml:51-58`; `deptrac.yaml:76-85,126-133`. |

#### Schema

| Prototype element | Implementation | Status | Evidence |
|---|---|---|---|
| Search history | Query/module/filters/count/meta/IP/UA/time and useful indexes exist. | IMPLEMENTED | `database/migrations/2025_10_31_075947_create_search_history_table.php:16-34`; `app/Domain/Shared/Services/SearchService.php:423-441`. |
| Saved filters/preferences | Tables exist and ownership is service-enforced. | IMPLEMENTED | `database/migrations/2025_10_31_075950_create_saved_filters_table.php:16-35`; `app/Domain/Shared/Services/SavedFilterService.php:84-188`. |
| Full-text indexes | Article/Quran/Hadith/Seerah/Sahaba translation tables have full-text indexes, but unified search does not use them. | PARTIAL | `database/migrations/2025_10_27_120001_create_article_locale_table.php:36-39`; `database/migrations/2025_11_29_100003_create_quran_verse_translations_table.php:30-33`; `database/migrations/2025_11_21_160005_create_hadith_translations_table.php:35-38`; `database/migrations/2026_03_09_200000_create_seerah_event_translations_table.php:29-33`; `database/migrations/2026_03_09_200001_create_sahabi_translations_table.php:33-37`. |
| Popular-sort columns | Generic `views_count` does not exist on Quran verses; Articles use `views`, so cross-module popular sort is not schema-compatible. | MISSING | `app/Domain/Shared/Search/SearchQueryBuilder.php:93-100`; `database/migrations/2025_11_29_100002_create_quran_verses_table.php:16-39`; `database/migrations/2025_10_27_120000_create_articles_table.php:18-20`. |
| Search source facet | Result card has a display source, but no normalized source-facet contract/index spans module source fields. | MISSING | `app/Http/Controllers/Site/SearchController.php:153-235`; `resources/views/public/search/index.blade.php:185-220`. |

#### Enhancement gaps

- `app/Domain/Shared/Services/SearchService.php:393-414` — introduce module search contributors that always apply locale readiness/publication policy. This is a P0 data-exposure issue. See CORE-001.
- `app/Domain/Shared/Search/SearchQueryBuilder.php:91-107` — replace generic column aliases with per-module sort mappings/relevance expressions; popular currently fails and relevance is merely newest ID. See CORE-003.
- `app/Domain/Shared/Services/SearchService.php:63-75` — design a real merged pagination/cursor strategy and preserve module totals/pages. See CORE-004.
- `app/Http/Controllers/Site/SearchController.php:21-23` — register Media/video and real source filters/suggestions; wire bookmark to favorites. See CORE-005.
- `routes/web.php:120-121` — apply `throttle:search`, especially because every request writes search history at `app/Domain/Shared/Services/SearchService.php:423-441`. See CORE-006.
- `app/Domain/Shared/Enums/SearchModule.php:112-126` — replace baselined concrete model dependencies with Shared contracts plus Bootstrap registry. This is required by `CLAUDE.md:425-430` and `deptrac.yaml:126-133`. See CORE-008.

## Findings checklist

- [ ] **CORE-001** [P0] Prevent public unified search from returning unpublished or unready content
      - **Module:** Search  ·  **Type:** SECURITY
      - **Evidence:** `app/Domain/Shared/Services/SearchService.php:393-414` — starts from `$modelClass::query()` with only term/filter clauses; `app/Http/Controllers/Site/SearchController.php:83-94` passes no publication filters.
      - **What to do:** Add per-module search contributor contracts with a `publicQuery(locale)`/scope; require Article `status=published` + ready locale, Seerah published + ready translation, and equivalent visibility rules before search/count; add regression tests for drafts.
      - **Why it matters / trade-offs:** Draft editorial content can be disclosed by guessing keywords. Per-module policies add adapters but avoid unsafe generic assumptions.

- [ ] **CORE-002** [P0] Filter topic itemables through public/readiness policies
      - **Module:** Topics  ·  **Type:** SECURITY
      - **Evidence:** `app/Domain/Topics/Repositories/TopicRepository.php:70-88` — loads linked itemables without visibility constraints; `app/Domain/Topics/Services/TopicService.php:174-205` maps any linked Article locale.
      - **What to do:** Move item fetching behind contributor contracts that apply published/ready scopes; reject or omit non-public linked records and test draft/unready articles on a public topic.
      - **Why it matters / trade-offs:** A stale/manual topic link can disclose draft content. Filtering may make curated counts differ, so compute counts from the same public scopes.

- [ ] **CORE-003** [P1] Implement schema-aware relevance/date/popular search sorting
      - **Module:** Search  ·  **Type:** PARTIAL
      - **Evidence:** `app/Domain/Shared/Search/SearchQueryBuilder.php:91-107` — relevance maps to `id`, popular maps every module to `views_count`; `database/migrations/2025_10_27_120000_create_articles_table.php:18-20` uses `views`.
      - **What to do:** Put allowed sort expressions on module contributors; use MATCH score (or deterministic fallback) for relevance, module-specific published dates, and each module’s real view/popularity metric; reject unsupported sorts.
      - **Why it matters / trade-offs:** `sort=popular` can collapse the whole page to zero through an SQL exception and “relevance” is semantically false.

- [ ] **CORE-004** [P1] Preserve unified-search pagination and implement load more
      - **Module:** Search  ·  **Type:** MISSING
      - **Evidence:** `app/Domain/Shared/Services/SearchService.php:63-75` — records paginator metadata internally; `app/Http/Controllers/Site/SearchController.php:101-130` flattens only first-page items.
      - **What to do:** Choose grouped per-type paging or a merged cursor, return next/last-page metadata, add query-preserving pagination/load-more controls, and display server total rather than `visibleCount`.
      - **Why it matters / trade-offs:** Most matching results are unreachable and the visible count contradicts the reported total.

- [ ] **CORE-005** [P1] Complete public search filters, Media/video results, suggestions, and real bookmarking
      - **Module:** Search  ·  **Type:** MISSING
      - **Evidence:** `app/Http/Controllers/Site/SearchController.php:21-23` — public modules exclude Media; `resources/views/public/search/index.blade.php:185-220,252-256` — only type/sort filters and a toast-only bookmark.
      - **What to do:** Register Media program/episode contributors; add source facets (collection/book/category/module), live debounced suggestions, video cards, and optimistic favorite POST/DELETE with guest login handling.
      - **Why it matters / trade-offs:** These are prominent prototype controls; source facets require normalized contributor output rather than one universal DB column.

- [ ] **CORE-006** [P1] Apply the named search throttle to the public web route
      - **Module:** Search  ·  **Type:** SECURITY
      - **Evidence:** `routes/web.php:120-121` — no middleware; `routes/api.php:155-169` — API equivalent uses `throttle:search`.
      - **What to do:** Add `->middleware('throttle:search')` to `public.search`, configure a user/IP-aware limiter, and test 429 behavior.
      - **Why it matters / trade-offs:** One GET triggers several count/select queries and a history insert, making it an easy abuse/DB-growth path.

- [ ] **CORE-007** [P1] Whitelist saved-filter sort fields before they reach `orderBy`
      - **Module:** Search  ·  **Type:** SECURITY
      - **Evidence:** `app/Http/Requests/SaveFilterRequest.php:42-44` — accepts arbitrary sort strings; `app/Domain/Shared/Builders/FilterBuilder.php:263-266` — passes the field to `orderBy`.
      - **What to do:** Validate sort against module contributor aliases with `Rule::in`, normalize legacy rows on read, and never accept raw column names in `SearchQueryBuilder::applySorting`.
      - **Why it matters / trade-offs:** Laravel quotes identifiers, limiting injection, but users can still control query structure enough to cause SQL errors/DoS.

- [ ] **CORE-008** [P1] Replace fixed/baselined cross-module model maps with Shared contributor registries
      - **Module:** Search + Topics  ·  **Type:** ENHANCEMENT
      - **Evidence:** `app/Domain/Shared/Enums/SearchModule.php:112-126` imports concrete models and `deptrac.baseline.yaml:51-58` suppresses it; Topics requires enum/repository/provider edits at `app/Domain/Topics/Enums/TopicItemType.php:16-27`, `app/Domain/Topics/Repositories/TopicRepository.php:29-46`, and `app/Providers/TopicsServiceProvider.php:35-39`.
      - **What to do:** Define Shared search/topic contributor contracts and registries; let each module/provider register model, public scope, mappings, relations, filters and sorts at Bootstrap.
      - **Why it matters / trade-offs:** This removes a known architecture violation and makes Seerah/Media additions open for extension without sibling imports.

- [ ] **CORE-009** [P1] Add Seerah/video topic groups and admin topic curation
      - **Module:** Topics  ·  **Type:** MISSING
      - **Evidence:** `app/Domain/Topics/Enums/TopicItemType.php:16-27` supports only verse/hadith/article; `database/seeders/TopicSeeder.php:117-138` is the only writer found.
      - **What to do:** Add contributor aliases/morphs/mappers for Seerah and Media episodes/programs; implement admin Topic CRUD plus translated fields and attach/detach/reorder selectors using FormRequests/permissions; add article/video sub-tabs.
      - **Why it matters / trade-offs:** The hub cannot satisfy the intended cross-module scope or be maintained editorially after seeding.

- [ ] **CORE-010** [P1] Implement the article comments/replies/moderation feature or remove it from parity scope
      - **Module:** Articles  ·  **Type:** SCHEMA
      - **Evidence:** `../the-last-messenger-ui/article-single-enhanced.html:829-986` — full comments UI; `rg -n "ArticleComment|commentable" app database/migrations routes` — no implementation.
      - **What to do:** Add comments table (article/user/parent/body/status, soft delete, indexes), policies, FormRequests, throttled endpoints, moderation, paginated replies and localized UI; otherwise record the product deferral explicitly.
      - **Why it matters / trade-offs:** It is a large visible prototype section; moderation/spam/privacy cost makes explicit deferral preferable to a fake UI.

- [ ] **CORE-011** [P1] Add article author identity and wire detail/list engagement actions
      - **Module:** Articles  ·  **Type:** MISSING
      - **Evidence:** `resources/views/public/articles/show.blade.php:329-375` has no visible author block; `resources/views/public/articles/index.blade.php:250-346` omits author; `routes/web.php:204-209` already exposes favorites.
      - **What to do:** Render author/avatar in cards and detail; add optional localized bio/profile schema if required; wire article favorite, like policy/endpoint, and `window.print()` with optimistic/error states.
      - **Why it matters / trade-offs:** Users lose attribution and prominent actions; follow/like requires a product/data decision beyond bookmarks.

- [ ] **CORE-012** [P1] Make profile reading history and per-content progress real across web readers
      - **Module:** Profile  ·  **Type:** PARTIAL
      - **Evidence:** `app/Http/Controllers/Site/MediaController.php:458-472` is the only public content controller that records a Shared view; `resources/views/public/profile/index.blade.php:212-274` shows aggregates/class names rather than content progress.
      - **What to do:** Record view/progress/complete through `InteractionService` in article/Hadith/Quran/Seerah/Media flows, debounce writes, map localized title/URL, and build paginated history/progress cards.
      - **Why it matters / trade-offs:** Current metrics can remain empty/incomplete despite normal browsing; progress tracking increases write volume, so batch/debounce carefully.

- [ ] **CORE-013** [P1] Surface saved searches, history, and search preferences in the public profile
      - **Module:** Profile + Search  ·  **Type:** MISSING
      - **Evidence:** `database/migrations/2025_10_31_075947_create_search_history_table.php:16-34` and `2025_10_31_075950_create_saved_filters_table.php:16-35` exist; `resources/views/public/profile/index.blade.php:20-26` has no search tab.
      - **What to do:** Add authenticated web endpoints or a session-auth adapter to existing services, profile panels for save/apply/delete/clear, pagination, and ownership tests.
      - **Why it matters / trade-offs:** Built backend capabilities are invisible to website users and the requested saved-search experience is absent.

- [ ] **CORE-014** [P1] Complete profile preferences, export, achievements, and 2FA product decisions
      - **Module:** Profile  ·  **Type:** MISSING
      - **Evidence:** `app/Http/Controllers/Site/ProfileController.php:69-77` disables export; `resources/views/public/profile/index.blade.php:278-370` only offers account/language/password; no achievement/2FA schema was found.
      - **What to do:** Wire export with throttling/audit, persist dark/notification preferences, add Fortify-compatible 2FA columns/actions, and either implement achievement models or explicitly keep the tab out as deferred.
      - **Why it matters / trade-offs:** Several prototype settings are absent; 2FA and exports carry security/privacy operational costs and need explicit scope.

- [ ] **CORE-015** [P1] Require and audit registration terms/privacy consent
      - **Module:** Authentication  ·  **Type:** SCHEMA
      - **Evidence:** `resources/views/public/auth/register.blade.php:116` is passive text; `app/Http/Requests/Site/RegisterRequest.php:29-37` has no consent rule; `database/migrations/0001_01_01_000000_create_users_table.php:14-30` has no consent fields.
      - **What to do:** Add required checkbox and terms/privacy links, `accepted` validation, and—if legal audit is required—`terms_accepted_at` plus policy-version columns/event record.
      - **Why it matters / trade-offs:** The prototype requires affirmative consent; audit storage should be chosen with legal/privacy input.

- [ ] **CORE-016** [P1] Give new social accounts the normal non-admin public role
      - **Module:** Authentication  ·  **Type:** SECURITY
      - **Evidence:** `app/Http/Controllers/Site/AuthController.php:81-85` assigns `user`; `app/Http/Controllers/Site/SocialAuthController.php:147-173` creates and returns without role assignment.
      - **What to do:** Extract shared account provisioning in a service/transaction and invoke it from both registration paths; backfill roleless social accounts; add role assertions to OAuth tests.
      - **Why it matters / trade-offs:** Role-gated public features may deny social users or behave inconsistently.

- [ ] **CORE-017** [P1] Make all editorial home sections admin-manageable
      - **Module:** Home  ·  **Type:** ENHANCEMENT
      - **Evidence:** `routes/admin/content.php:40-58` manages only home cards/chips; `resources/views/public/home.blade.php:211-258,386-565,840-914` hardcodes major sections.
      - **What to do:** Add ordered `home_sections`/section-item schema and admin CRUD, or targeted settings for hero modules, Prophet block, daily selection policy and featured content; consume via Content service/repository with cache invalidation.
      - **Why it matters / trade-offs:** Editorial changes currently require deploys and contradict project claims that home sections are DB-driven.

- [ ] **CORE-018** [P1] Wire home bookmark/share actions and notification center
      - **Module:** Home  ·  **Type:** PARTIAL
      - **Evidence:** `resources/views/public/home.blade.php:729-743,791-800` has inert buttons; `../the-last-messenger-ui/index-enhanced.html:451-507` has a notification panel absent from `resources/views/components/site/header.blade.php:78-201`.
      - **What to do:** Reuse a favorite/share Alpine component with real IDs/types and error/login states; implement a notification data source/read state or remove the bell requirement explicitly.
      - **Why it matters / trade-offs:** Controls promise actions they do not perform; notifications add delivery/read-state scope beyond a placeholder panel.

- [ ] **CORE-019** [P1] Restrict article view increments to publicly readable articles
      - **Module:** Articles  ·  **Type:** SECURITY
      - **Evidence:** `app/Repositories/ArticleRepository.php:387-395` increments solely by ID; `routes/web.php:77-78` exposes it publicly.
      - **What to do:** Use a published+ready existence/update query, return an appropriate not-found response, retain session/throttle safeguards, and test draft/archived/missing IDs.
      - **Why it matters / trade-offs:** Attackers can corrupt editorial analytics for non-public records.

- [ ] **CORE-020** [P2] Make unified search use the existing full-text indexes
      - **Module:** Search  ·  **Type:** PERF
      - **Evidence:** `app/Domain/Shared/Search/SearchQueryBuilder.php:47-50,173-195` falls back to relation `LIKE`; `rg -n "getFullTextColumns" app` finds no model implementation.
      - **What to do:** Implement contributor queries that join/whereHas locale translation tables and use their exact indexed MATCH columns, with locale/readiness constraints and LIKE fallback for SQLite/tests.
      - **Why it matters / trade-offs:** Leading-wildcard relation scans ignore expensive indexes and will degrade sharply with content growth.

- [ ] **CORE-021** [P2] Paginate or lazy-load every topic content tab
      - **Module:** Topics  ·  **Type:** PERF
      - **Evidence:** `app/Domain/Topics/Repositories/TopicRepository.php:70-88` ends in unbounded `get()` for each of three types.
      - **What to do:** Accept per-tab cursor/page and limit, return counts separately, lazy-load inactive tabs, and preserve sort order.
      - **Why it matters / trade-offs:** Query count is constant (not N+1), but row count/DOM/payload is unbounded.

- [ ] **CORE-022** [P2] Replace the articles catalogue’s silent 500-row cap with server pagination
      - **Module:** Articles  ·  **Type:** PERF
      - **Evidence:** `app/Repositories/ArticleRepository.php:186-206` defaults to `limit(500)` and `app/Http/Controllers/Site/ArticleController.php:25-30` sends all rows for client pagination.
      - **What to do:** Move search/category/tag/sort/page into a FormRequest and repository paginator with whitelisted sort aliases; return/render one page with query-preserving links.
      - **Why it matters / trade-offs:** Records after 500 disappear silently; current client filtering is fast at today’s small dataset but does not scale.

- [ ] **CORE-023** [P2] Keep article view counts and popularity caches coherent
      - **Module:** Articles  ·  **Type:** PERF
      - **Evidence:** `app/Repositories/ArticleRepository.php:389-394` clears only `article_statistics`; `app/Http/Controllers/Site/ArticleController.php:36-38,103-105` caches index/detail payloads for one hour.
      - **What to do:** Evict tagged article index/detail caches on increments or fetch volatile counts separately; ensure popular order does not use hour-stale embedded counts.
      - **Why it matters / trade-offs:** Users can receive a fresh async count on detail while cards/sorts stay stale; frequent cache eviction may reduce hit rate.

- [ ] **CORE-024** [P2] Consolidate article translation fields and localize references/stat labels
      - **Module:** Articles  ·  **Type:** SCHEMA
      - **Evidence:** `database/migrations/2025_10_27_120001_create_article_locale_table.php:15-25` and `database/migrations/2026_06_11_100000_add_seo_fields_to_articles_table.php:18-34` define competing translated fields; base JSON labels are read raw at `app/Http/Controllers/Site/ArticleController.php:276-317`.
      - **What to do:** Declare one canonical translation store, migrate/drop duplicate legacy columns (`featured`, read-time and localized JSON duplicates), and store reference/stat labels per locale or as translation keys.
      - **Why it matters / trade-offs:** Dual sources drift and raw labels cannot reliably render all five locales; migration needs careful backward compatibility.

- [ ] **CORE-025** [P2] Fix favorites listing failure handling
      - **Module:** Profile/Favorites  ·  **Type:** ENHANCEMENT
      - **Evidence:** `app/Http/Controllers/Site/FavoriteController.php:48-53` catches and immediately repeats the identical `paginateForUser` call.
      - **What to do:** Return an empty `LengthAwarePaginator` plus localized error banner/retry, or let the exception handler render a real error; add failure-path test.
      - **Why it matters / trade-offs:** A transient/backend error still produces a 500 and no usable error state.

- [ ] **CORE-026** [P2] Use FormRequests and module/sort allowlists for all saved-search mutations
      - **Module:** Search/Profile  ·  **Type:** SECURITY
      - **Evidence:** `app/Http/Controllers/Api/FilterController.php:115-172,215-259` uses bare Request/inline validation for destroy/apply/default/preferences and does not validate route module; `SaveFilterRequest.php:42-44` accepts any sort.
      - **What to do:** Add dedicated authorize/rules requests for each mutation, validate module with `SearchModule`, validate sort/filter aliases per contributor, and keep existing service ownership checks.
      - **Why it matters / trade-offs:** Object authorization is currently sound, but malformed modules/sorts can create invalid persistent state and SQL failures.

- [ ] **CORE-027** [P2] Define and enforce the email-verification trust boundary
      - **Module:** Authentication/Profile  ·  **Type:** SECURITY
      - **Evidence:** `app/Http/Controllers/Site/AuthController.php:87-89` explicitly states browsing is not gated; `routes/web.php:180-209` uses `auth` only for all profile/favorite mutations.
      - **What to do:** Document which actions require verified email and add `verified` middleware to them (at minimum sensitive/community features if policy requires); retain profile access for verification/account recovery.
      - **Why it matters / trade-offs:** Verification exists but currently changes no authorization; gating everything could lock users out of account maintenance.

- [ ] **CORE-028** [P2] Remove dynamic/raw locale leaks on Home, Search, and Profile
      - **Module:** Home/Search/Profile  ·  **Type:** I18N
      - **Evidence:** `resources/views/public/home.blade.php:702-706` constructs missing `site.grading_*` keys; `app/Http/Controllers/Site/SearchController.php:352-354` hardcodes Arabic fallback terms for every locale; `resources/views/public/profile/index.blade.php:81-89` formats raw engagement level.
      - **What to do:** Map grading and engagement enums to existing locale keys; move popular fallback terms to each locale file or DB; add five-locale tests for dynamic values.
      - **Why it matters / trade-offs:** Non-Arabic users can see untranslated Arabic, English enums, or literal translation-key strings.

- [ ] **CORE-029** [P2] Implement or remove the fake newsletter subscription
      - **Module:** Home/Shared footer  ·  **Type:** ENHANCEMENT
      - **Evidence:** `resources/views/components/site/footer.blade.php:73-81` prevents submit and always shows a success toast; no subscription route is present in `routes/web.php:1-224`.
      - **What to do:** Add consent-aware subscriber schema, double-opt-in/throttled FormRequest endpoint and unsubscribe flow, or remove the form until delivery exists.
      - **Why it matters / trade-offs:** The UI claims success while discarding the address; real email collection introduces privacy/compliance obligations.

- [ ] **CORE-030** [P2] Distinguish backend errors from valid empty/loading states
      - **Module:** Home/Search  ·  **Type:** ENHANCEMENT
      - **Evidence:** `resources/views/public/home.blade.php:623-634,723-727` labels null data “loading”; `app/Http/Controllers/Site/SearchController.php:95-99` converts all failures to empty results.
      - **What to do:** Return explicit error flags, render localized retry/error components, reserve skeleton/loading for active requests, and log per-module search failures without discarding successful groups.
      - **Why it matters / trade-offs:** Users receive false “no results” or infinite “loading,” hiding outages and broken sorts.

- [ ] **CORE-031** [P2] Raise assigned-page dark-mode secondary text contrast
      - **Module:** Core public views  ·  **Type:** ENHANCEMENT
      - **Evidence:** `resources/views/public/articles/index.blade.php:286-328` and `resources/views/public/profile/index.blade.php:81,266` use `dark:text-gray-500` for dates/metadata.
      - **What to do:** Audit assigned views against the supplied rule (secondary dark `text-gray-300`, light `text-gray-600`), reserve dimmer colors only where contrast testing passes, and add visual/a11y regression checks.
      - **Why it matters / trade-offs:** Low-contrast metadata is hard to read; uniformly promoting tertiary text can reduce hierarchy, so validate contrast and semantics.

- [ ] **CORE-032** [P3] Restore or intentionally retire login/register presentation-only prototype elements
      - **Module:** Authentication  ·  **Type:** PARTIAL
      - **Evidence:** `../the-last-messenger-ui/login-enhanced.html:161-235,336-344` has marketing benefits, tabs and guest CTA; `resources/views/public/auth/login.blade.php:1-28,123-131` uses a simpler card and separate register link.
      - **What to do:** Either port the benefit panel/tab/guest CTA responsively or document the simplified auth-shell decision.
      - **Why it matters / trade-offs:** Functionality is sound; this is conversion/visual parity and adds maintenance/copy surface.

- [ ] **CORE-033** [P3] Match article related-carousel and lightbox utility polish
      - **Module:** Articles  ·  **Type:** PARTIAL
      - **Evidence:** `../the-last-messenger-ui/article-single-enhanced.html:729-827,1229-1315` has horizontal related rail and zoom/download; `resources/views/public/articles/show.blade.php:277-321,550-576` has navigate/close and sidebar cards only.
      - **What to do:** Add an RTL-aware related rail and optional zoom/download controls with content-disposition/CORS-safe behavior.
      - **Why it matters / trade-offs:** Visual parity improves discovery; downloads may conflict with image rights and should be product-controlled.

- [ ] **CORE-034** [P3] Extract repeated public card/action primitives
      - **Module:** Core public views  ·  **Type:** ENHANCEMENT
      - **Evidence:** `resources/views/components/site/` contains shared shell/empty/filter/bookmark/Sahaba primitives, while article cards are duplicated at `resources/views/public/articles/index.blade.php:250-346` and search/topic action/card markup remains inline at `resources/views/public/search/index.blade.php:226-304` and `resources/views/public/topics/show.blade.php:138-252`.
      - **What to do:** Extract article/result card metadata, favorite/share action, modal shell, and content-type badge components with slots/typed props; do not over-generalize module-specific body layouts.
      - **Why it matters / trade-offs:** Shared wiring prevents another round of toast-only/inert actions; excessive generic components can obscure module semantics.

- [ ] **CORE-035** [P3] Complete modal/lightbox accessibility and mobile containment
      - **Module:** Articles  ·  **Type:** A11Y
      - **Evidence:** `resources/views/public/articles/show.blade.php:594-620` share modal lacks dialog semantics/focus management/max-height; gallery aria labels are raw English at `resources/views/public/articles/show.blade.php:237,255`.
      - **What to do:** Add `role=dialog`, `aria-modal`, labelled title, focus trap/initial focus/focus return, `max-h-[85vh] overflow-y-auto`, localized play/pause/image labels, and reduced-motion-safe transitions.
      - **Why it matters / trade-offs:** Keyboard/screen-reader users can lose context or tab behind the overlay; changes are localized and low risk.

## Doc corrections

- `../WEB_APP_MODULES_ANALYSIS.md:49-59,100` — stale: Home, Auth/Profile, Topics and their routes/pages now exist. Verified replacements are `routes/web.php:24,116-121,143-210`.
- `../WEB_APP_MODULES_ANALYSIS.md:145-151` — stale: public session auth, social login, password reset, email verification, avatar/profile/account deletion and favorites routes/controllers now exist. The remaining gaps are consent, role parity, verification policy and deeper profile features.
- `../WEB_APP_MODULES_ANALYSIS.md:215-217` — partially stale: Topic entity/tables/index/show and real three-module aggregation exist. Admin curation and video/Seerah aggregation remain missing.
- `../WEB_APP_MODULES_ANALYSIS.md:492-496` — overstated: the API search group is throttled, but public web search is not; full-text indexes exist, but `SearchQueryBuilder` takes LIKE fallback because no model implements `getFullTextColumns`.
- `../PROJECT_PROGRESS.md:19,32` — “Home DB-driven” and “no mocked data remains/all chrome DB-driven” are too broad. Cards/chips/stats are real, but `resources/views/public/home.blade.php:211-258,386-565,840-914` is static editorial content and footer newsletter is fake at `resources/views/components/site/footer.blade.php:73-81`.
- `../PROJECT_PROGRESS.md:26` — “Articles … author” is backend-payload-only; no visible author card appears in listing/detail (`resources/views/public/articles/index.blade.php:250-346`, `show.blade.php:329-375`).
- `../PROJECT_PROGRESS.md:27` — “Topics Admin ✅” is wrong: no Admin Topic controller/routes were found; `database/seeders/TopicSeeder.php:117-138` is the only writer found.
- `../PROJECT_PROGRESS.md:28` — “Search … incl. media / MySQL full-text” is wrong for the public site: `SearchController::PUBLIC_MODULES` excludes media at `app/Http/Controllers/Site/SearchController.php:21-23`, and full-text is not reached.
- `../DESIGN_REVIEW.md:74-77` — “skeletons/empty states” and dark contrast are not universally clean: Home null content is called loading (`resources/views/public/home.blade.php:623-634,723-727`) and representative assigned-page metadata uses `dark:text-gray-500`.
- `CLAUDE.md:413-416,794-797` — internally stale/ambiguous: it calls Search/notifications missing in one place and says Search uses LIKE later. Search exists; LIKE is currently accurate for unified search despite full-text indexes.
- `CLAUDE.md:813,840` — stale: public article API/routes and API rate limiting exist (`routes/api.php:155-169` is one example), although the public web search throttle gap remains.
- `TODO_articles-defects.md:95-98` — D-18 is stale: `app/Http/Controllers/Site/ArticleController.php:374-376` prepends `images/` to a fallback already containing `site/`, resolving to existing `public/images/site/article-{1,2,3}.webp`.
- `../KNOWN_ISSUES.md:15-25` — KI-3/KI-5/KI-9/KI-10/KI-12 remain listed under Open although later tracker rows say those features were fixed; the file needs reconciliation with its Fixed section/current code.

## Open questions

- OAuth cannot be proven operational without deployed Google/Facebook credentials, callback allowlists and a live provider round trip; static wiring is complete (`config/services.php:38-51`).
- Mail delivery, queue configuration, signed verification links and reset email delivery require a running environment; static routes/controllers/schema are present.
- The current database’s actual topic membership/publication mix and whether any draft article is already linked were not queried by design; the code path permits exposure regardless.
- Query counts and execution plans were not measured against a live DB. Static inspection found bounded eager loading on Home/Articles, three unbounded topic type reads, and sequential search paginator queries; validate with Telescope/EXPLAIN and production-scale fixtures.
- The brief requests Seerah under Topics although `topic-enhanced.html` itself shows Quran/Hadith/Media (articles/videos), not a Seerah tab. Product should decide whether Seerah is a fourth tab or part of Media.
- Comments, achievements/gamification and 2FA are explicitly described as deferred/optional in `../WEB_APP_MODULES_ANALYSIS.md:17-20,130-135,572-579`; this report records prototype parity gaps but does not override that product decision.
- No assigned page received a thinner pass; all eight prototypes were read in full and traced through route, controller, Blade/components, service/repository/model and relevant migrations.
