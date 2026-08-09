# API security architecture and inventory

Last reviewed: 2026-08-09

## Security boundary

The versioned API is private by default. Exactly three routes are deliberately anonymous:

| Method | Route | Why it is public | Controls |
|---|---|---|---|
| `POST` | `/api/v1/auth/register` | Creates a public user account and first token | 10/min/IP and 5/min/email+IP, strong password, validated fields |
| `POST` | `/api/v1/auth/login` | Exchanges credentials for a token | Same credential/IP throttles, generic invalid-credential response, old tokens revoked |
| `GET` | `/api/v1/health` | External liveness check | 60/min/IP, no dependency/build/host/exception details |

All other `/api/v1` routes require `auth:sanctum` and the `api:access` token ability. Every route has a named request budget; search endpoints have an additional search budget. A regression test fails if a future route falls outside either invariant.

## Client usage and compatibility

The public website does not depend on bearer-token API calls. Its browser-side requests use the separate named `web` routes with session authentication and CSRF protection, so protecting `/api/v1` does not interrupt public page navigation, favorites, media pages, Quran navigation, or admin workflows. Repository-wide searches for literal API paths and `v1.*` route generation must remain part of an API-boundary change review.

External API consumers must bootstrap a token through `POST /api/v1/auth/login` or `POST /api/v1/auth/register`, then send it on every non-bootstrap request:

```http
Authorization: Bearer <token>
Accept: application/json
```

Login and registration tokens include the required `api:access` ability. Existing clients may continue using the same endpoint paths, parameters, and success envelopes; the compatibility change is the required bearer credential on routes that were previously readable anonymously. Clients should treat `401` as a missing/expired token, `403` as a missing ability or object-authorization denial, `422` as request validation failure, and `429` as a signal to respect `Retry-After` and back off. Tokens must not be placed in URLs, logs, browser local storage, or source control.

Example:

```bash
curl -sS -X POST https://example.test/api/v1/auth/login \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  --data '{"email":"reader@example.test","password":"replace-with-secret"}'

curl -sS https://example.test/api/v1/articles \
  -H 'Accept: application/json' \
  -H 'Authorization: Bearer replace-with-login-token'
```

```mermaid
flowchart LR
    C["API client"] --> T["Request throttle"]
    T --> P{"Public allowlist?"}
    P -->|"register / login / health"| V["Validation or minimal health response"]
    P -->|"everything else"| S["Sanctum token authentication"]
    S --> A["Object ownership and publication gates"]
    A --> R["Resource field allowlist"]
    V --> H["No-store and defensive headers"]
    R --> H
```

## Complete `/api/v1` inventory and usage

Authentication:

- `POST /auth/register`, `POST /auth/login`: token bootstrap; anonymous and tightly throttled.
- `POST /auth/logout`, `POST /auth/logout-all`, `GET /auth/user`: token/session management; authenticated and user-scoped.

Articles:

- `GET /articles`, `/articles/featured`, `/articles/popular`, `/articles/recent`, `/articles/{slug}`: authenticated content reading. Only published parents and ready translations are returned. Writer email, admin URLs, preview links, and version history are not part of API responses.
- `GET /articles/search`: same publication policy plus the search limiter and bounded query/pagination validation.

Categories:

- `GET /categories`, `/categories/{slug}`: authenticated taxonomy lookup. Roots, requested categories, and returned children must be active; inactive records are indistinguishable from missing records.

Media:

- `GET /media/programs`, `/popular`, `/recent`, `/search`, `/category/{categoryId}`, `/{slug}`, `/{slug}/episodes`, `/{slug}/episodes/{episodeNumber}`: authenticated program and episode reading. Repositories apply published/ready visibility. Category, sorting, direction, season, limits, and pagination are allowlisted and bounded.
- `POST /media/progress`, `GET /media/continue-watching`: authenticated and strictly scoped to the token owner. A request body cannot select another `user_id`; draft episodes are treated as missing.

Seerah:

- `GET /seerah`, `/featured`, `/search`, `/map`, `/type/{type}`, `/hijri/{year}`, `/gregorian/{year}`, `/{slug}`: authenticated published-event access. Slugs/types/years are constrained and list/search sizes are bounded.

People and roles:

- `GET /sahaba`, `/sahaba/search`, `/sahaba/{slug}`, `/sahaba/{slug}/tree`, `/roles`: authenticated directory access. Only published profiles are rendered; hidden or unpublished genealogy nodes are removed. Search length, page size, and tree depth are bounded.

Quran:

- `GET /quran/surahs`, `/surahs/{number}`, `/surahs/{surahNumber}/verses`, `/surahs/{surahNumber}/verses/{verseNumber}`, `/revelation/{place}`, `/juz/{juz}`, `/page/{page}`, `/sajda`, `/random`, `/search`, `/search/suggest`: authenticated canonical Quran access. Numeric/range/enum/query validation applies, and search has the stricter budget.
- The public Quran web page no longer calls the protected suggestion endpoint anonymously. It builds navigation suggestions locally from the 114-surah payload already rendered into the page.

Languages:

- `GET /languages/active`, `/languages/all`, `/languages/current`: authenticated locale metadata. These remain inside the private boundary to keep the allowlist mechanically small.

Unified search:

- `POST /search`, `POST /search/{module}`, `GET /search/suggestions`, `/trending`, `/modules`, `/history`, and `DELETE /search/history`: authenticated, search-throttled, module-allowlisted, and pagination/query bounded.
- Every module query passes through the public-visibility registry and a response resource allowlist. Suggestions and trends suppress cohorts below three distinct users so one person's repeated uncommon search is not exposed to other users. History read/delete is token-owner scoped.

Saved filters:

- `GET/POST /filters`, `PUT/DELETE /filters/{id}`, `POST /filters/{id}/apply`, `/set-default`, `GET /filters/public`, and `GET/POST /filters/preferences/{module}`: authenticated. Private records are owner-scoped; route modules and numeric identifiers are constrained. “Public” describes sharing among authenticated API clients, not anonymous internet access.

Interactions and engagement:

- `POST /interactions`: authenticated. Clients send a stable content wire name, never a PHP class. The server resolves through a closed model allowlist and refuses unpublished content. Metadata, duration, and IDs are bounded.
- `GET /interactions/me`, `/engagement/me`, `/trends`, `/export`: token-owner data only.
- `GET /engagement/users/{id}`: retained for compatibility but returns `403` unless `{id}` is the token owner's ID.
- `GET /engagement/content/{type}/{id}`, `/content/top`, `/leaderboard`, `/stats`: authenticated aggregates. Draft IDs and raw PHP model names are removed; leaderboard output is minimized and does not expose email, activity timestamps, detailed behavior, or time-spent data.

## Cross-cutting controls

| Risk | Control |
|---|---|
| Accidental anonymous route | Secure-by-default route group plus exact public-allowlist test |
| Brute force / scraping / expensive queries | Auth, API, and search limiters keyed by user or IP |
| Non-expiring bearer secrets | Sanctum expiration defaults to 43,200 minutes (30 days) and token prefix `tlm_` |
| Token accumulation | Login revokes older tokens; logout-all is available |
| BOLA / IDOR | Acting-user ownership for filters, history, progress, engagement, and exports |
| Draft/unready content disclosure | Publication scopes, ready-translation filtering, and indistinguishable 404s |
| Mass assignment / arbitrary classes | FormRequests, enum/allowlist validation, stable content type map |
| Resource exhaustion | Maximum page sizes, limits, query lengths, days, seasons, and interaction durations |
| Sensitive response caching | `Cache-Control: no-store, private, max-age=0` and `Pragma: no-cache` |
| MIME/frame/referrer attacks | `nosniff`, `DENY`, and `no-referrer` headers on API responses |
| Header/log injection | Client request IDs are accepted only through a strict character/length pattern |
| Search-history privacy | Small-cohort suppression; personal history endpoints remain owner-scoped |

## Other HTTP surfaces

- Admin portal: 380 non-bootstrap routes are protected by `auth:admin`, the admin gate, an allowed admin-tier role, CSRF/session middleware, and a 60/min budget. Only the throttled login page and login submission are anonymous. Logout now requires the admin guard.
- Public website: public GET pages and read-only JSON helpers are intentionally anonymous because they are the website's delivery surface. They use published-content repositories, constrained identifiers, and search throttles where queries fan out. Account, favorites, profile, AI inquiry, and inquiry-history operations use the web session guard and owner scoping. State-changing web requests receive Laravel CSRF protection.
- Private local-file delivery: Laravel's `/storage/{path}` private-disk routes require a valid relative signed URL; uploads additionally require the signed `upload=1` form. Path traversal is rejected by the framework.
- Laravel `/up`: an intentionally public liveness endpoint. It contains no application data.

## Operational requirements and residual decisions

Authentication does not by itself make open-registration data commercially private: anyone permitted to register can obtain a token. If production access must be invitation-only, disable public registration or add an approval/email-verification policy before launch. This is a product access decision, not a missing route guard.

Production must use HTTPS, keep `APP_DEBUG=false`, protect `APP_KEY` and database credentials, set correct trusted proxies/hosts and CORS origins, run dependency/secret scanning, rotate compromised tokens, and monitor repeated `401`, `403`, `422`, and `429` responses. Do not set `SANCTUM_TOKEN_EXPIRATION` to zero; keep it finite.

## Verification

Run:

```bash
DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test tests/Feature/Api
php artisan route:list --path=api/v1
```

`ApiSecurityBoundaryTest` proves the exact anonymous allowlist, full throttling coverage, defensive headers, cross-user engagement denial, PHP-class rejection, and draft-content denial. `ApiRouteSmokeTest` exercises every registered `/api/v1` route both anonymously where required and authenticated, failing on skipped routes, unexpected authentication behavior, exceptions, or server errors.
