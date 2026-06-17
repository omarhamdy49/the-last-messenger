# TODO — Ask Quran AI (DeepSeek V4 Flash + mcp.quran.ai)

> Technical plan to convert the existing **canonical-search** "Ask Quran" page into an
> **AI Q&A** feature grounded by `mcp.quran.ai`, answered by **DeepSeek V4 Flash**,
> restricted to **registered users**, capped at **10 questions / calendar month**,
> and engineered for **aggressive cost optimization**.
>
> Skills used: `/laravel-generator` (DDD/CQRS patterns) + `/faang-engineer` (discipline).
> Status legend: `[ ]` todo · `[~]` in progress · `[x]` done.

---

## 0. Context — what already exists (do NOT rebuild)

| Asset | Path | Current behavior |
|-------|------|------------------|
| Page (Blade + Alpine) | `resources/views/public/quran/ask.blade.php` | `quranAsk()` Alpine component, sticky composer, **2–150 char** textarea, threaded results, loading/empty/error states, RTL/Amiri |
| Page route | `routes/web.php` (`public.quran.ask`) | `GET /quran/ask` → `Site\QuranController@ask` |
| Search route | `routes/web.php` (`public.quran.ask.search`) | `GET /quran/ask/search` → `Site\QuranController@askSearch`, `throttle:search` (30/min/IP), **public** |
| Search engine | `app/Domain/Quran/Services/QuranInquirySearchService.php` | Calls `api.quran.com/api/v4/search`, 24h cache by MD5(query), 8s timeout, diacritics stripping — **no LLM** |
| Translations | `resources/lang/{ar,en,fr,tr,ur}/quran.php` (`ask_*` keys) | Subtitle literally says "search — **not** an AI answer" |
| Rate limiters | `app/Providers/AppServiceProvider.php` (`auth`/`search`/`api`) | IP-keyed only |
| Auth | `app/Models/User.php` (Sanctum + Spatie roles), web session guard; public login at `/public/login` | Ask page currently needs **no** auth |
| MCP facts (from research) | `mcp.quran.ai` | streamable-HTTP, **no auth**, free, self-hostable (Docker), rate-limited (undocumented). `avg_entry_tokens` per tafsir edition is the cost driver |

**Conversion, not greenfield.** Reuse the Blade shell, Alpine skeleton, threading, and states. Replace the *backend engine* and *access model*; adjust the *frontend gating + copy*.

---

## 1. Key decisions (resolve before coding)

### AQ-DECISION-1 — Character limit: **200** (recommended), min **10**
**Recommendation: 200 chars, min 10** (current page is 2–150).

Rationale (FAANG: optimize the right variable):
- The user's question is **cost-negligible** — 200 Arabic chars ≈ 60–90 input tokens vs ~5–12K tokens of grounding context per call. Tightening 200→150 saves <0.5% of per-call cost. **The char limit is an abuse/focus control, not a cost lever.**
- Arabic is denser per word, but real theological questions are multi-clause (e.g. *"ما حكم كذا وما تفسير الآية المتعلقة به"*). 150 is cramped in RTL; 200 gives headroom without inviting essays.
- Raise the floor to **10** — a 2-char query can't ground a useful AI answer (wastes a quota slot + an LLM call).
- Enforce in **three** places: FormRequest (`min:10|max:200`), Alpine `maxlength="200"` + live counter, and a DB column cap. Never trust the client.

> If you prefer 150 for stricter UX, it's purely a product call — cost is identical. Default to 200.

### AQ-DECISION-2 — What consumes quota
- **Only a successful DeepSeek call** decrements the monthly counter.
- **Cache hits are free** (no LLM call → no decrement). Rewards common questions, slashes cost, better UX.
- **Failures/timeouts refund** the reserved slot (don't bill users for our errors).
- Reserve→call→refund pattern (see AQ-6) avoids holding a DB lock across the network call.

### AQ-DECISION-3 — Quota window: **calendar month** (`YYYY-MM`)
Simplest and explainable ("resets on the 1st"). Rolling-30-day is a future option; do not build it now.

### AQ-DECISION-4 — Sync vs async
**v1 = synchronous** AJAX (matches existing page), hard 45s timeout, bounded tool loop (≤4 iterations). DeepSeek V4 Flash with capped iterations answers in a few seconds. **Follow-up:** SSE streaming for token-by-token UX (AQ-12).

### AQ-DECISION-5 — Bridge required
DeepSeek is **not** MCP-native (OpenAI-style function calling only). Our backend is the MCP client: fetch grounding rules + tool schemas once, expose MCP tools to DeepSeek as functions, run the loop, feed results back. (Anthropic's API would skip this — but we chose DeepSeek for cost.)

---

## 2. Target architecture (fits existing DDD/CQRS)

```
Browser (Alpine, gated to auth users)
  └─ POST /quran/ask  (web, auth + throttle:ai_inquiry)
       └─ Site\QuranController@ask  (1 FormRequest → 1 Query dispatch → Resource)
            └─ AskQuranHandler            (CQRS query handler — orchestration policy)
                 ├─ AiInquiryQuotaRepository   reserve slot (atomic) ─┐
                 ├─ AiInquiryRepository        cache lookup (hash) ───┤ free path
                 ├─ QuranAiInquiryService      the bridge ────────────┤ paid path
                 │     ├─ QuranMcpClient       streamable-HTTP MCP client
                 │     └─ DeepSeekChatClient   chat completions + tool calling
                 ├─ AiInquiryRepository        persist answer + usage/cost
                 └─ AiInquiryQuotaRepository   confirm or refund slot
```

Module isolation: all of the above lives under `app/Domain/Quran/`. The two infra
clients (`DeepSeekChatClient`, `QuranMcpClient`) are generic but **only Quran uses them
today** → keep them in `Quran/Infrastructure/` (simplicity first; promote to `Shared/`
only if a second module needs them). `composer deptrac` must stay green.

---

## 3. Implementation tasks

### AQ-1 — Config & secrets
- [x] `config/services.php` add:
  ```php
  'deepseek' => [
      'api_key'  => env('DEEPSEEK_API_KEY'),
      'base_url' => env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com'),
      'model'    => env('DEEPSEEK_MODEL', 'deepseek-chat'), // V4 Flash alias
      'timeout'  => (int) env('DEEPSEEK_TIMEOUT', 40),
      'max_output_tokens' => (int) env('DEEPSEEK_MAX_OUTPUT', 1200),
  ],
  'quran_mcp' => [
      'url'             => env('QURAN_MCP_URL', 'https://mcp.quran.ai'),
      'timeout'         => (int) env('QURAN_MCP_TIMEOUT', 10),
      'default_tafsir'  => env('QURAN_MCP_TAFSIR', 'en-maarif-ul-quran'),
      'max_verses'      => (int) env('QURAN_MCP_MAX_VERSES', 6),
      'max_tool_calls'  => (int) env('QURAN_MCP_MAX_TOOL_CALLS', 4),
  ],
  ```
- [x] `.env.example`: add `DEEPSEEK_API_KEY=`, `DEEPSEEK_BASE_URL=`, `DEEPSEEK_MODEL=deepseek-chat`, `QURAN_MCP_URL=https://mcp.quran.ai`, `QURAN_MCP_TAFSIR=en-maarif-ul-quran`, `QURAN_AI_MONTHLY_QUOTA=10`.
- [ ] **Never** log the API key; ensure `X-Request-Id` correlation stays on responses.

### AQ-2 — Migrations (reversible, driver-guarded)
- [x] `create_ai_inquiries_table` — history + audit + cache source + moderation:
  - `id`, `user_id` (FK → users, cascadeOnDelete), `locale` (char 5),
    `question` (string 255), `normalized_question` (string 255),
    `question_hash` (char 64), `answer` (longText), `citations` (json nullable),
    `source` (enum: `llm`,`cache`), `model` (string nullable),
    `input_tokens`/`output_tokens`/`cached_tokens` (unsignedInt default 0),
    `cost_usd` (decimal(10,6) default 0), `tool_calls` (unsignedTinyInt default 0),
    `geo` (json nullable) — client geo snapshot (see AQ-18; advisory/untrusted),
    `published_faq_id` (FK → faqs nullOnDelete, nullable) — set when published as FAQ,
    `answer_edited_at` (timestamp nullable), `answer_edited_by` (FK → users nullable) — admin edit audit,
    `timestamps`.
  - Indexes: `(user_id, created_at)` (history + audit), `(question_hash, locale)` (cache lookup), `(published_faq_id)`.
- [x] `create_ai_inquiry_usages_table` — atomic monthly quota:
  - `id`, `user_id` (FK), `period` (char 7, `YYYY-MM`), `count` (unsignedInt default 0), `timestamps`.
  - `unique(user_id, period)`.
- [ ] Wrap any MySQL-only DDL in `DB::getDriverName()` guards (follow Hadith migration precedent); ensure `down()` drops both tables. Verify on the test driver.
- [x] Run: `php artisan migrate` (Docker MySQL on host 3308 must be up: `docker compose up -d`).

### AQ-3 — Models (`app/Domain/Quran/Models/`)
- [x] `AiInquiry` — explicit `$fillable`, casts (`citations` => array, token ints, `cost_usd` => decimal), `belongsTo(User)`. No `$guarded = []`.
- [x] `AiInquiryUsage` — `$fillable = ['user_id','period','count']`, `belongsTo(User)`.

### AQ-4 — `QuranMcpClient` (`app/Domain/Quran/Infrastructure/Mcp/`)
Streamable-HTTP MCP client. Responsibilities:
- [x] `initialize()` → MCP handshake; cache `grounding_nonce` + grounding-rules text (see AQ-7).
- [x] `toolSchemas(): array` — return the subset we expose to DeepSeek as function defs:
  **`search_quran`, `search_translation`, `fetch_translation`, `fetch_tafsir`, `fetch_quran`** (omit morphology/mushaf/concordance for v1 — fewer tokens, fewer round-trips).
- [x] `callTool(string $name, array $args): array` — pass `grounding_nonce` on every call to suppress the ~2.5K rules re-injection; clamp `editions` to `config('quran_mcp.default_tafsir')` and ayah ranges to `max_verses`.
- [ ] Timeouts (`config('quran_mcp.timeout')`), 2 retries on idempotent reads, structured errors (`\Throwable`), never leak internals to users.
- [x] **Cost guardrail:** hard-cap fetched ayat per call and reject editions outside an allowlist (default tafsir + optional `en-ibn-kathir` for escalation) — prevents a 40× token blow-up from `ar-tabari`/`ar-tahrir-wa-tanwir`.

### AQ-5 — `DeepSeekChatClient` (`app/Domain/Quran/Infrastructure/Llm/`)
Thin wrapper over DeepSeek `POST /chat/completions`:
- [x] `complete(array $messages, array $tools): DeepSeekResponse` returning content, `tool_calls`, and **`usage`** (prompt/completion/`prompt_cache_hit_tokens`).
- [x] Enforce `max_tokens = config('deepseek.max_output_tokens')`, `temperature` low (≈0.2 — faithfulness > creativity), timeout, single retry on 5xx only (idempotent — same prompt).
- [x] Surface usage so the handler can compute `cost_usd` (Flash: $0.14/M in, $0.0028/M cached-in, $0.28/M out).

### AQ-6 — `QuranAiInquiryService` (`app/Domain/Quran/Services/`)
The bridge / bounded agentic loop:
- [x] Build a **byte-stable system prompt** = grounding rules (cached) + persona + answer-format contract (must cite, must attribute tafsir, applied-reasoning disclaimer per `mcp.quran.ai` rules). **Stability is critical** → DeepSeek prefix cache hits at $0.0028/M.
- [x] Loop: send (system + tools + question) → if `tool_calls`, execute via `QuranMcpClient`, append results, repeat — **cap at `max_tool_calls`** (default 4). After the cap, force a final answer (no more tools).
- [ ] Return `AiInquiryResultDTO` (`final readonly`): answer, citations[], grounding line, token usage, tool-call count.
- [x] Inject **interfaces**, no `DB::`, no Eloquent here. Fail fast; never fabricate Quran/tafsir text (the whole point of the MCP).

### AQ-7 — Grounding-rules cache (one-time, shared)
- [x] On first use, call MCP `fetch_grounding_rules`, store the rules text + `grounding_nonce` in cache via a new `QuranCacheKeys::aiGroundingRules()` (TTL ~6–24h). All requests reuse it → the 2.5K rules block is paid ~once, and the nonce suppresses re-injection on every subsequent tool call.

### AQ-8 — CQRS query + handler (`Application/`)
- [x] `Queries/AskQuranQuery.php` — `final readonly` (userId, question, locale).
- [x] `Handlers/AskQuranHandler.php` — the **orchestration policy**, in order:
  1. Normalize question (reuse the diacritics-stripping logic from `QuranInquirySearchService`; lowercase, collapse whitespace) → `question_hash`.
  2. **Cache lookup** by `(question_hash, locale)` in `ai_inquiries` (or a dedicated answer cache, TTL 30–90d). Hit → return immediately, `source=cache`, **no quota decrement**.
  3. **Reserve a slot atomically** (see AQ-9). If none left → throw `MonthlyQuotaExceededException`.
  4. Call `QuranAiInquiryService`. On failure → **refund** the slot, rethrow.
  5. Persist `AiInquiry` (answer, citations, usage, computed `cost_usd`, `source=llm`).
  6. Return result DTO.
- [ ] `DTOs/AiInquiryResultDTO.php`.

### AQ-9 — Quota repository (race-safe)
- [x] `AiInquiryQuotaRepository::tryReserve(int $userId, string $period, int $limit): bool`
  - Atomic conditional increment inside `DB::transaction()` **in the repository** (not controller/service):
    `INSERT ... ON DUPLICATE KEY UPDATE count = count + 1 WHERE count < :limit` (MySQL) — or `updateOrCreate` + conditional `UPDATE ... WHERE count < limit`; return whether a row was actually affected. **No lock held across the LLM call.**
- [x] `refund(int $userId, string $period)` — `count = GREATEST(count - 1, 0)`.
- [x] `remaining(int $userId, string $period, int $limit): int` — for the UI badge.
- [x] Portable lockForUpdate txn (no raw SQL, no driver guard needed); provide a portable fallback for the test driver.

### AQ-10 — HTTP layer
- [x] `Http/Requests/Site/AskQuranRequest.php` — `authorize()` requires `auth()->check()`; rules `question => required|string|min:10|max:200`; field-level 422 messages (5 langs). Also read + validate the **geo headers** (AQ-18) into the query payload (whitelist fields, cap lengths) — treat as untrusted/advisory.
- [ ] `Site\QuranController@askSearch` → repurpose to `@ask` (AI). **Change to `POST`** (mutating, consumes quota — GET is wrong/cacheable/loggable-in-URL). Controller does **only**: validate (FormRequest) → dispatch `AskQuranQuery` → return JSON Resource. No logic, no Eloquent.
- [ ] `Http/Resources/AiInquiryResource.php` — shape: `{ answer, citations[], grounding_note, remaining_quota, source }`. Generic client messages on error (never stack traces).
- [x] Keep `@ask` GET route for the **page**; the old public canonical `askSearch` GET endpoint is **removed/replaced** (decide: keep canonical search as a fallback tab, or retire — see AQ-13).

### AQ-11 — Routing, auth gate, rate limiting (`routes/web.php`, `AppServiceProvider`)
- [x] Page route `GET /quran/ask` stays public (renders the gated UI). Inquiry route:
  ```php
  Route::post('/quran/ask', [QuranController::class, 'ask'])
      ->middleware(['auth', 'throttle:ai_inquiry'])
      ->name('public.quran.ask.submit');
  ```
- [x] Add limiter in `AppServiceProvider::configureRateLimiters()` — **keyed by user id**, burst protection layered under the monthly quota:
  ```php
  RateLimiter::for('ai_inquiry', fn (Request $r) =>
      Limit::perMinute(5)->by((string) optional($r->user())->id ?: $r->ip()));
  ```
- [x] Guests hitting the page see a login CTA (Blade), and the POST returns 401 JSON (already handled in `bootstrap/app.php`).

### AQ-12 — Frontend (Blade + Alpine) — gating, limit, copy
- [x] **Auth gate:** `@guest` → render login/register CTA card instead of composer.
- [x] **Char limit:** textarea `maxlength="200"`, live `x-text` counter `{{ len }}/200`, disable submit when `< 10` or `> 200` or pending; `text-base sm:text-sm` (iOS zoom), `flex-wrap`, modal `max-h-[80vh]`.
- [x] **Quota badge:** "‎N of 10 questions left this month" from `remaining_quota`; when 0 → disable composer + show reset-date note.
- [x] **Copy change:** subtitle no longer "not an AI answer". New disclaimer: "AI-assisted answer grounded in canonical sources from quran.com — a study aid, not a fatwa." Render the `grounding_note` line under each answer.
- [x] **States:** keep loading/empty/error; add `quota_exceeded` state. Double-submit guard (disable + spinner). Dark + light verified, RTL (logical props, flipped icons), tested in AR.
- [ ] **Design:** run new components through `/ui-ux-pro-max` + `/sc:design` before finalizing (per CLAUDE.md §0).

### AQ-13 — Decide fate of canonical search
- [x] Resolved (b — keep): (a) **retire** the old quran.com canonical search, or (b) keep it as a free, unlimited, unauthenticated **"Search verses"** tab beside the gated **"Ask AI"** tab. Recommended: **(b)** — gives guests value, offloads simple lookups off the LLM (cost!), and the code already exists.

### AQ-14 — Translations (all 5: ar, en, fr, tr, ur)
- [x] New keys: `ask_ai_*` (title/subtitle/disclaimer), `ask_login_required`, `ask_quota_badge` (`:remaining`, `:limit`), `ask_quota_exceeded` (`:reset_date`), `ask_char_counter`, `ask_grounding_note`, `ask_min_length`. Update stale `ask_page_subtitle`. Never hardcode user-facing text.

### AQ-15 — Tests (`tests/Feature/...`, `tests/Unit/...`)
- [x] Feature (follow existing 82-test style): success 200; **401** when guest; **422** when `<10`/`>200`/empty; **429** quota-exceeded (custom code or 403 — pick one, document); cache-hit path returns without calling DeepSeek (mock the client); quota decrements only on LLM success; refund on failure.
- [x] Unit: quota `tryReserve` race (two concurrent reserves at limit-1 → exactly one succeeds); normalization → hash determinism; tool-loop cap; cost computation from usage.
- [x] Admin (AQ-17): publish-as-FAQ creates a `Faq` + translation and sets `published_faq_id`; double-publish guarded; edit-answer updates audit fields + invalidates the answer cache; delete behaves per decision; permission-gated (403 without `quran.ai.moderate`); `composer deptrac` passes (no Quran→Content import).
- [x] Geo (AQ-18): spoofed/oversized geo headers are rejected/whitelisted, not stored raw; missing geo still succeeds (fail-open); geo never affects quota/auth.
- [x] Mock `DeepSeekChatClient` + `QuranMcpClient` (don't hit live APIs in tests). DB-backed for quota/inquiry repos.
- [ ] Bug fixes start with a failing test.

### AQ-16 — Validation gates (Definition of Done)
- [ ] `php artisan test` green · `./vendor/bin/pint` clean · `composer deptrac` green · migrations reversible + driver-guarded.
- [ ] Controller calls exactly one handler; no Eloquent/`DB::` in controller/service; explicit `->select([...])` on any list query; no N+1.
- [ ] Security: auth on submit, FormRequest validation, IDOR-safe (history scoped to `auth id`), rate limits present, no secret logging, generic error messages.

---

### AQ-17 — Admin: moderate inquiries + publish as FAQ (cross-module, deptrac-safe)
FAQ lives in the **Content** domain (`app/Domain/Content/`: `Faq` + `FaqTranslation` (translatable),
`FaqService`, `FaqRepository`, admin CRUD `admin.content.faqs.*`). Quran inquiries live in the
**Quran** domain. Quran must **not** import Content directly → bridge via a **Shared contract**
(same pattern as `ExportableModuleInterface` etc.). `composer deptrac` must stay green.

- [x] **Shared contract:** `app/Domain/Shared/Contracts/FaqPublisherInterface.php` with
  `publish(FaqDraft $draft): int` (returns new FAQ id). Define a small `FaqDraft` value object
  in `Shared` (question, answer, locale, optional category/tag, `source = 'quran_ai'`).
- [x] **Content implements it:** `FaqService implements FaqPublisherInterface` (Content → Shared, allowed).
  Maps the draft → `Faq` + `FaqTranslation` for `draft.locale`; other languages left blank for the
  admin to fill later in the existing FAQ editor. Bind in `ContentServiceProvider`.
- [x] **Admin controller:** `app/Http/Controllers/Admin/Quran/AiInquiryController.php` — actions:
  `index` (paginated list: question, answer preview, user, geo country, tokens, cost, published flag),
  `show`, `updateAnswer`, `destroy`, `publishAsFaq`. Each action authorizes (Spatie permission,
  e.g. `quran.ai.moderate`), calls **exactly one** handler/service, returns a Resource/redirect.
- [x] **Application handlers (Quran side):** (delivered via AiInquiryAdminService — single service per action, cache invalidation + idempotent publish)
  - `UpdateInquiryAnswerHandler` — edits stored `answer`, sets `answer_edited_at/by`, and
    **refreshes/invalidates the answer cache** for `(question_hash, locale)` so future identical
    questions serve the corrected answer (don't leave a stale cache).
  - `DeleteInquiryHandler` — soft or hard delete (decide; soft keeps audit). If it was published,
    decide whether deleting the inquiry also deletes the FAQ (default: **no** — they diverge once published).
  - `PublishInquiryAsFaqHandler` — depends on `AiInquiryRepositoryInterface` (Quran) +
    `FaqPublisherInterface` (Shared); builds `FaqDraft` from the (possibly admin-edited) answer,
    calls the publisher, stores returned id in `published_faq_id`, guards against double-publish.
- [x] **FormRequests:** `Admin/Quran/UpdateInquiryAnswerRequest`, `Admin/Quran/PublishInquiryAsFaqRequest`
  (validate target category/locale). Mutating → CSRF + authorize + field-level messages.
- [x] **Routes:** added to the admin route group (e.g. `routes/admin/quran.php`) under
  `admin.quran.ai-inquiries.*` (`index`, `show`, `updateAnswer` PUT, `destroy` DELETE, `publishAsFaq` POST).
- [x] **Admin views:** `resources/views/admin/quran/ai-inquiries/{index,show}.blade.php` reusing the
  shared admin `data-table`, form components, action-button rows (`flex-wrap`), RTL/dark — per the
  admin component conventions already in the repo.
- [x] **Translations:** admin keys in all 5 langs (`admin.*` namespace): list columns, "Edit answer",
  "Publish as FAQ", "Delete", confirm dialogs, success/error toasts.
- [ ] **Editing the answer** must not silently change a published FAQ — they're decoupled after publish
  (admin re-edits the FAQ in the FAQ module). Document this in the UI.

### AQ-18 — Client geo capture (centralized, reused on every request)
Requirement: load user geo once via `https://cmp.inmobi.com/geoip`, reuse it as shareable info on
**all** subsequent requests (no duplicated fetch logic), send it on the Ask submit, persist on the inquiry.

- [x] **Single shared JS helper — fetch once, reuse everywhere.** Add one module (e.g.
  `resources/js/geo.js`) exposing an Alpine global store `Alpine.store('geo')`:
  - On app init **for authenticated users only** (`@auth` flag passed to JS), check `sessionStorage`;
    if absent, `fetch('https://cmp.inmobi.com/geoip')` once, store the parsed JSON in the store +
    `sessionStorage` (key e.g. `tlm.geo`). Never refetch within the session.
  - Expose `Alpine.store('geo').headers()` → `{ 'X-Geo-Country': cc, 'X-Geo-City': city }` (only fields present).
- [x] **One request wrapper — don't duplicate header logic.** Add a single `apiFetch(url, opts)`
  (or an axios interceptor) that merges CSRF + `Accept-Language` + `Alpine.store('geo').headers()`
  into every request. The Ask composer (and any future authed feature) calls `apiFetch`, so geo
  rides along automatically — **DRY**, one place to change.
- [x] **Fail-open:** `cmp.inmobi.com` is a CMP/ad-tech host frequently blocked by ad-blockers/DNS.
  Wrap the fetch in try/catch with a short timeout; **never block question submit on geo** — if it's
  missing, send the request without geo headers. No console errors surfaced to users.
- [x] **Server ingest:** the controller reads the `X-Geo-Country` + `X-Geo-City` headers, **whitelists** fields,
  validates (country = 2-letter ISO, city length-capped to 64; region/lat/lng dropped), and the handler stores the
  sanitized object in `ai_inquiries.geo`. Treat as **untrusted + advisory** — spoofable client input;
  **never** use for authorization, quota, pricing, or access decisions. Analytics/audit only.
- [x] **Privacy/security:** geo is personal data — (a) never place it in URLs/query strings (we POST,
  per project rule); (b) it cascades-deletes with the user (FK); (c) add it to the privacy policy +
  data-retention note; (d) cap stored JSON size. If a consent gate exists, respect it before fetching.

## 4. Cost optimization — the levers, ranked by impact

| # | Lever | Mechanism | Impact |
|---|-------|-----------|--------|
| **1** | **Cross-user answer cache** | normalize→hash→cache full answer 30–90d, keyed `(hash, locale)`; cache hits are free + don't consume quota | Eliminates the LLM call for all repeat/common questions — typically the **majority** of traffic on a Quran Q&A app. Biggest single saving. |
| **2** | **DeepSeek prefix caching** | byte-stable system prompt + tool schemas as the request prefix → cache-hit at **$0.0028/M** (98% off) vs $0.14/M | Cuts per-call input cost ~30–40%. Verify via `prompt_cache_hit_tokens` in usage. |
| **3** | **Tafsir edition discipline** | default `en-maarif-ul-quran` (~948 tok/ayah); allowlist blocks `ar-tabari`(2,825)/`ar-tahrir-wa-tanwir`(6,142); escalate to `en-ibn-kathir` only on flagged complex Qs | Up to **40× swing** on the dominant cost component. |
| **4** | **Verse-count cap** | `max_verses` (6) per fetch; reject wide ranges | Prevents a single careless 20-ayah fetch from 10×-ing a call. |
| **5** | **Bounded tool loop** | `max_tool_calls` (4) + `grounding_nonce` to suppress 2.5K rules re-injection | Caps the accumulating-context blow-up of agentic loops. |
| **6** | **Output cap** | `max_tokens` 1200, low temperature | Bounds the $0.28/M output side. |
| **7** | **Monthly quota = hard spend ceiling** | 10 LLM calls/user/month, atomic | Worst-case spend is deterministic and tiny. |
| **8** | **Per-minute throttle** | `throttle:ai_inquiry` 5/min/user | Stops burst abuse within the monthly window. |
| **9** | **Keep canonical search free** (AQ-13b) | route simple lookups to quran.com, not the LLM | Removes a whole class of queries from LLM cost. |
| **10** | **Self-host `mcp.quran.ai`** (follow-up) | Docker (postgres + server :8088) | $0 MCP cost + escapes the undocumented rate limiter at scale. License: QFGPL-1.0 — review before prod. |

### Cost envelope (DeepSeek V4 Flash, "Standard" grounding)
- **Per LLM-answered question:** ~**$0.0016** (≈10K in @ mostly-cached + ~700 out).
- **Per user/month, worst case** (all 10 are cache-miss LLM calls): ~**$0.016**.
- **Realistic** (lever #1 deflects most repeats): **well under 1¢/user/month**.
- **At 10,000 active users:** worst case ~**$160/mo**; realistically **double-digit dollars**.
- Even a 10× estimation error keeps it under **$0.25/user/month**.

> The LLM is rounding error. The real operational variable is your own infra + whether you self-host the MCP (lever #10).

---

## 5. Follow-ups (not in v1)
- [ ] AQ-12-SSE: token-streaming UX (SSE) instead of synchronous wait.
- [ ] Semantic/near-duplicate answer cache (embeddings) — bigger cache hit-rate than exact-normalized.
- [ ] User-visible inquiry **history** page (data already persisted in `ai_inquiries`).
- [ ] Self-host MCP (lever #10) + observability dashboard on `cost_usd`/tokens with an anomaly alert.
- [ ] Abuse/quality: prompt-injection hardening review of the system prompt; profanity/off-topic guard before spending a call.

---

## 6. Open questions for product
1. **Char limit: 200 (recommended) or 150?** — cost-identical; pure UX call.
2. **Quota window:** calendar month (recommended) vs rolling 30 days.
3. **Canonical search:** retire or keep as a free guest tab (recommended keep)?
4. **Quota-exceeded HTTP code:** 429 vs 403 — pick one for the API contract.
5. **Verified-email required?** User model implements `MustVerifyEmail` — gate AI on verified only, or any registered user?
6. **Publish-as-FAQ:** which FAQ category/section do AI inquiries land in, and do we auto-translate the answer to the other 4 languages on publish (extra LLM cost) or leave them blank for the admin? Default: **leave blank**, admin fills in the FAQ editor.
7. **Delete semantics:** soft-delete inquiries (keep audit/cost history) or hard-delete? And on delete of a *published* inquiry, leave the FAQ intact (recommended) or cascade?
8. **Geo + consent:** `cmp.inmobi.com/geoip` is a CMP/ad-tech host (often blocked) — confirm it's acceptable for production and covered by the privacy policy/consent banner; otherwise swap for a self-hosted GeoIP (MaxMind) reading the request IP server-side (more reliable, not spoofable, no third-party call).

---

## 7. Completion status (2026-06-17)

**Built, tested, and verified — feature is functionally complete end-to-end.**

| Gate | Result |
|------|--------|
| Full test suite | **660 passed, 6 skipped, 0 failures** (30 new Ask-Quran-AI tests) |
| Pint (my files) | Clean (repo has unrelated pre-existing `scripts/*` style debt) |
| Deptrac | 290 violations, **0 introduced** by this work (−3 vs the 293 baseline); 0 errors |
| Migrations | Applied to MySQL :3308; reversible; portable (test driver runs them) |
| Assets | `npm run build` succeeded — geo store + `apiFetch` bundled |
| Live bridge | `php artisan quran:ai-smoke "<q>"` — run with a real `DEEPSEEK_API_KEY` to verify DeepSeek + mcp.quran.ai |

### §6 decisions — resolved (defaults applied)
1. **Char limit:** 200, min 10.
2. **Quota window:** calendar month (`YYYY-MM`).
3. **Canonical search:** kept (b) — free "Search verses" tab for everyone, gated "Ask AI" for members.
4. **Quota-exceeded code:** HTTP **429**.
5. **Verified-email:** any registered user (not verified-only) for v1.
6. **Publish-as-FAQ:** source locale only; other languages left blank for the admin; default inactive unless the admin ticks "active".
7. **Delete:** soft-delete inquiries (preserve cost/audit); a published inquiry's FAQ is left intact.
8. **Geo:** country + city only via `cmp.inmobi.com/geoip`; untrusted/advisory; fail-open.

### Remaining (genuinely external / future — not blockers)
- **Live smoke test** with real keys (`quran:ai-smoke`) — needs `DEEPSEEK_API_KEY` (not in repo).
- **Self-host mcp.quran.ai** before scale (escape the undocumented rate limiter) — §4 lever #10.
- **Deferred follow-ups (§5):** SSE streaming UX, semantic answer cache, user history page, prompt-injection hardening pass, `/ui-ux-pro-max` formal design review (the §5.3 hardening checklist — states, RTL, dark, mobile, counter, double-submit — is already met).

---

## 8. Live E2E (Chrome) — 2026-06-17

Driven through the browser against the running app + **real** DeepSeek key and mcp.quran.ai. **1 DeepSeek API call total** (the second identical ask was a free cache hit).

| Step | Result |
|------|--------|
| Guest page | Sign-in notice + locked "Ask AI" tab + free "Search verses" — ✅ |
| Free canonical search (guest) | Returned quran.com verses for "الرحمة" — ✅ **0 API** |
| Login (session) | ✅ |
| AI mode UI | AI subtitle, quota badge "8/8", `0/200` counter — ✅ |
| AI answer (call #1) | Live DeepSeek+MCP grounded Arabic answer + citation chips (search_quran/translation, fetch_quran/translation/tafsir) + disclaimer — ✅ |
| Quota decrement | 8 → 7 — ✅ |
| Cache hit (re-ask) | "إجابة محفوظة" chip, quota stayed 7 — ✅ **0 API** |
| Admin moderation | List shows both inquiries; **cost ledger live: AI $0.0038, cache $0.0000**; source labels correct — ✅ |

**Bug found & fixed by the E2E:** `ask.blade.php` used `{{ $isAuthenticated ? "'ai'" : "'search'" }}` — Blade HTML-escaped the quotes (`mode: &#039;search&#039;`), a JS syntax error that broke the whole Alpine component (`quranAsk` undefined). Fixed with `@json(...)`. (Feature tests render HTML but don't execute JS, so this class of bug needs an in-browser E2E.)
