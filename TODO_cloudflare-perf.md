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
Safe recipe:

**App side (I implement):**
- Add a middleware that, for **guest GET** requests to cacheable routes
  (start with `/`), sends `Cache-Control: public, s-maxage=300,
  stale-while-revalidate=600` and **strips the `Set-Cookie`/session** so no
  per-user data is ever cached. Authenticated requests (cookie present) bypass.
- Confirm the homepage needs no per-session CSRF for guests (search is GET; login
  is a separate, uncached route) — it doesn't today.

**Cloudflare side (Cache Rule):**
- **Caching → Cache Rules → Create:**
  - When: `URI Path equals /` **AND** `Cookie` does not contain
    `the-last-messenger-session` (so logged-in users are never served a cached page).
  - Then: **Eligible for cache**, **Edge TTL: Respect origin** (uses the
    `s-maxage` above), **Browser TTL: Respect origin**.
- Verify: `curl -sI https://rasoolallah.com/ | grep cf-cache-status` → `HIT` for
  anonymous, and confirm a logged-in session still gets fresh, per-user HTML.

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
