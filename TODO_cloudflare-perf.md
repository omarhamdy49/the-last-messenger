> **STATUS (applied 2026-06-23 via API):** ✅ Edge-cache rule for the guest
> homepage + ✅ bypass rule for admin/api/auth/logged-in + ✅ cache purged +
> ✅ origin `CacheGuestPages` middleware deployed. Verified: home `/` = cf HIT;
> `/su/portal/v1/admin`, `/login`, `/api/v1/*`, `/seerah`, and home-with-session
> = DYNAMIC (never cached). Result: LCP 2.3s→2.1s, score 97.
> ⏳ Pending (token lacked Zone-Settings:Edit — toggle in dashboard): **Brotli**
> (Speed→Optimization, usually already on), **Early Hints** (Speed→Optimization),
> **Tiered Cache** (Caching→Tiered Cache).
> 🔑 **REVOKE the API token now** (it was shared in chat): dash → My Profile →
> API Tokens → delete the token.

# Cloudflare performance actions (to apply) — FCP is now network-bound

**State:** Code-level perf is done. Mobile PSI **97**, all Core Web Vitals green
(LCP 2.3s, TBT 0ms, CLS ~0.05, SI 2.8s). The one stuck metric is **FCP 1.8s** —
and it's stuck because the request still round-trips from the Cloudflare edge to
the **OVH origin in France**. The server itself renders in ~45ms; the time is
network. Only Cloudflare can remove that leg. Do these in order.

---

## 1. Purge the cache (1 min) — do this first
The re-optimized images (e.g. the seal: 12.7KB→6.8KB) are already on the origin
but Cloudflare is serving the old cached copies (filenames aren't hashed +
`immutable` 30-day cache).

- Dashboard → **Caching → Configuration → Purge Cache → Purge Everything**
  (or purge by URL: `…/images/site/rasool-allah-seal.webp`, `…-hero-section-bg*.webp`).

## 2. Free speed toggles (2 min) — safe, no risk
- **Speed → Optimization → Content Optimization:** turn **Brotli** ON (smaller
  text transfers than gzip).
- **Speed → Optimization:** enable **Early Hints** (sends `103` so the browser
  preloads the hero image/fonts during the origin think-time → helps LCP/FCP).
- **Caching → Tiered Cache:** ON (fewer trips to the France origin).
- **Speed → Optimization → Image Optimization → Polish:** set to **WebP/Lossy**
  *(Pro plan only — auto-optimizes images at the edge; skip if Free plan).*

## 3. Edge-cache the HTML for guests (the actual FCP lever) — needs care
This is what removes the France round-trip from FCP. **Do not do this blind** —
caching a session-aware Laravel page wrong = shared sessions/CSRF (security bug).
The hard rule: **NEVER cache the admin portal, the API, or any auth/account
page.** Safe recipe with three layers of protection:

**3a. Cloudflare — a BYPASS rule FIRST (highest priority).** This guarantees
admin/API/auth are never cached, even if a later rule is broadened.
- **Caching → Cache Rules → Create** (put it ABOVE the cache rule in 3c):
  - When (any of these → bypass):
    ```
    starts_with(http.request.uri.path, "/su/portal/")   (admin portal)
    or starts_with(http.request.uri.path, "/api/")        (API)
    or http.request.uri.path in {"/login" "/register" "/profile" "/favorites"
        "/forgot-password" "/quran/ask"}
    or starts_with(http.request.uri.path, "/reset-password")
    or starts_with(http.request.uri.path, "/quran/ask")
    or any(http.request.headers["cookie"][*] contains "the-last-messenger-session")
    ```
  - Then: **Bypass cache.**

**3b. App side (I implement).** Middleware scoped to the **public web route group
ONLY — never the admin or api groups**. For a **guest GET** to a cacheable public
route (start with just `/`), it sends `Cache-Control: public, s-maxage=300,
stale-while-revalidate=600` and **strips `Set-Cookie`/session** so nothing
per-user is cached. Any authenticated request (admin included) keeps
`Cache-Control: private, no-store` — so even if a CF rule slipped, the origin
itself forbids caching admin/authed responses. Belt and suspenders.

**3c. Cloudflare — the cache rule (lower priority than 3a).**
- **Caching → Cache Rules → Create:**
  - When: `URI Path equals /` **AND** `Cookie` does not contain
    `the-last-messenger-session` (logged-in users never get a cached page).
  - Then: **Eligible for cache**, **Edge TTL: Respect origin**, **Browser TTL:
    Respect origin**.

**Verify (must check ALL):**
```
curl -sI https://rasoolallah.com/                         | grep cf-cache-status   # HIT (guest home)
curl -sI https://rasoolallah.com/su/portal/v1/admin       | grep cf-cache-status   # BYPASS/DYNAMIC — never HIT
curl -sI https://rasoolallah.com/login                    | grep cf-cache-status   # BYPASS/DYNAMIC
curl -sI https://rasoolallah.com/api/v1/hadith            | grep cf-cache-status   # BYPASS/DYNAMIC
# logged in (send the session cookie) → home must be fresh, not a cached guest page
```

Expected: FCP/LCP drop a few hundred ms (TTFB at the PSI location goes from
"France round-trip" to edge), pushing FCP into the green and the score toward 98–99.

---

## Fastest path
Paste a **Cloudflare API token** (scopes: *Zone → Cache Purge: Purge*,
*Zone → Cache Rules: Edit*, *Zone → Zone Settings: Edit*) and I will:
1. purge the cache, 2. enable Brotli/Early-Hints/Tiered-Cache, 3. implement the
guest-cache middleware + create the Cache Rule, and 4. re-run PSI to confirm —
all verified end to end. Revoke the token afterward.
```
