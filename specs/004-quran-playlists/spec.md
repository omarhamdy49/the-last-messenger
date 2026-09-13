# Quran Reciter Playlists — Design

**Branch**: `004-quran-playlists` | **Date**: 2026-09-13 | **Status**: approved

## Summary

A signed-in visitor picks one or more favourite reciters (people), chooses the whole Quran or a
set of surahs, and the system assigns a reciter recording to every surah. The visitor reviews
and adjusts the assignment, names the playlist, saves it, and can play it, edit it, delete it,
and publish it under an unguessable link. Published playlists appear in a public gallery; admins
can feature them on the Quran home page or unpublish, edit, rename and delete them.

No new package. Two tables, one new favourite type, one site controller, one admin controller,
one Alpine wizard. Playback reuses the shared `$store.audioPlayer` (`setPlaylist`) — no player
rewrite.

## Decisions (with the user)

| Question | Decision |
|---|---|
| What is a "reciter" in the picker | The **person** (`ReciterProfile`). The recording (`Reciter`, riwayah) is auto-picked per surah; the preview exposes a recording sub-select only when the person has more than one recording covering that surah. |
| Discovery | Share link (random slug) **and** a public gallery at `/quran/playlists`; admin `is_featured` surfaces playlists on the Quran home. |
| Playback | Dedicated playlist page driving the shared player in `setPlaylist` mode. Each surah row deep-links into the reader with `?reciter=<recording id>` so verse-follow uses the assigned recording. |
| Favourite reciters | A real favourite type (`reciter` → `ReciterProfile`): heart on reciter pages, shown under Profile → Favourites, pre-checked in the wizard. |
| Storage | Two normalised tables (playlist + items). Rejected: JSON items column (no integrity, JSON admin editing) and `users.preferences` (cannot be shared or administered). |
| Out of scope | Custom track order, cover upload, likes/comments, `/api/v1` endpoints. Web session only, same as favourites today. |

## 1. Schema

```
quran_playlists
  id                bigint pk
  user_id           FK users, cascadeOnDelete
  name              varchar(80)
  slug              char(12) unique            -- random [a-z0-9], never derived from name
  description       varchar(300) null
  scope             enum('full','custom')
  is_public         bool default 0
  is_featured       bool default 0             -- admin only
  plays_count       unsigned int default 0
  items_count       unsigned smallint default 0 -- maintained by PlaylistService on write
  created_at / updated_at
  index (user_id)
  index (is_public, is_featured, plays_count)

quran_playlist_items
  id                bigint pk
  playlist_id       FK quran_playlists, cascadeOnDelete
  surah_id          tinyint unsigned FK quran_surahs
  reciter_id        FK quran_reciters, nullOnDelete   -- recording; null = "needs a reciter"
  unique (playlist_id, surah_id)
```

- Play order is surah number. No `position` column.
- The selected *people* are derived from items (`distinct reciter.profile_id`); there is no
  `playlist_reciters` table. Known ceiling: a person selected in step 1 but assigned zero surahs
  (possible only with a small custom scope) is not shown on edit. Accepted.
- Limits, enforced in FormRequests: ≤ 20 playlists per user, name 2–80 chars, description ≤ 300,
  custom scope ≥ 1 surah, ≥ 1 reciter profile, every profile id must be `visible()`.

### Favourites extension

- `App\Domain\Shared\Enums\FavorableType::RECITER = 'reciter'`, icon `mic`, label key
  `profile.type_reciter`.
- `FavoriteServiceProvider` registers `RECITER → App\Domain\Quran\Models\ReciterProfile` with a
  mapper producing `FavoriteItemData` (title = `displayName(locale)`, subtitle = country name,
  image = `profile_image_url`, url = `public.quran.reciter`), and `eagerLoadFor` loads
  `translations`.
- Existing `favorites` table and unique index cover it; no migration.

## 2. Domain layer (`app/Domain/Quran/`)

### Models

- `Models/Playlist` — `owner()` (BelongsTo `App\Models\User`), `items()` (HasMany, ordered by
  `surah_id`), `scopeVisibleTo(?User $user)` = `is_public = 1 OR user_id = $user->id`. This scope
  is the **only** visibility rule; every public read goes through it. `reciterProfiles()` is a
  computed accessor over loaded items (distinct `reciter.profile`), not a relation.
- `Models/PlaylistItem` — `surah()`, `reciter()` (recording), `reciter.profile`.
- Slug generated in `Playlist::booted()` (`creating`) with a uniqueness retry loop.

### Repository — `Repositories/PlaylistRepository`

```
forUser(User $user): Collection                       // profile tab, wizard "my playlists"
countForUser(User $user): int                         // the 20-playlist cap
gallery(?string $search, string $sort, int $perPage)  // is_public only; sort latest|plays
featured(int $limit): Collection                      // is_public AND is_featured, by plays
findBySlugVisibleTo(string $slug, ?User $user): ?Playlist   // 404 boundary for show/play
findOwnedBySlug(string $slug, User $user): ?Playlist        // edit/update/destroy/visibility
adminList(array $filters, int $perPage)               // public/featured/search by name or owner
```

Reads eager-load `items.surah`, `items.reciter.profile.translations` (constrained columns —
translations live in `quran_reciter_profile_translations`, never `select('name')`).

### Service — `Services/PlaylistService`

```
assign(array $profileIds, array $surahIds, ?array $keep = []): AssignmentResult
create(User $user, PlaylistData $data): Playlist
update(Playlist $playlist, PlaylistData $data): Playlist
delete(Playlist $playlist): void
setPublic(Playlist $playlist, bool $public): void
setFeatured(Playlist $playlist, bool $featured): void     // admin
recordPlay(Playlist $playlist): void                      // increment, no read-modify-write
```

`PlaylistData` (DTO): `name, description, scope, surahIds, items: array<surahId, recordingId>,
isPublic`. Both `preview` and `store/update` receive `items` produced by `assign()`, so what the
user saw is what is stored; `store` re-validates every `(surah, recording)` pair against
`quran_recitations` and rejects the request if any pair no longer has audio.

#### `assign()` — the one non-trivial piece

1. One query: `quran_recitations` joined to `quran_reciters` where `profile_id IN (...)`,
   `kind = 'recitation'`, `is_active = 1`, `surah_id IN (...)` → in-memory map
   `profile → recording → set<surah>`.
2. For each surah in scope, in surah order:
   - `$keep[surah]` (a recording the user pinned in the preview) wins if it still covers the surah.
   - Otherwise candidates = profiles with ≥ 1 covering recording. Pick the candidate with the
     **fewest assignments so far**, ties broken at random. This keeps 3 reciters over 114 surahs
     near 38/38/38 instead of an arbitrary 60/40/14 while still feeling random. `ponytail:`
     balanced-random is O(surahs × profiles); fine for 114 × 20.
   - Recording for that profile = `default_recording_id` if it covers the surah, else the covering
     recording with the lowest `display_order`.
   - No candidate → surah goes to `uncovered[]`. It is **never silently dropped**.
3. Returns `AssignmentResult { items: array<surahId, recordingId>, uncovered: int[], alternatives:
   array<surahId, array<profileId, recordingId[]>> }` — `alternatives` feeds the per-row selects
   in the preview without a second round-trip.

RNG is injectable (seeded in tests) so balance and determinism are unit-testable.

### Cache

Gallery, featured and Quran-home sections use `QuranCacheKeys` versioned keys (new
`playlists` family, 10 min). `Playlist` `saved/deleted` bumps the version. `recordPlay` does not
bump it (plays are eventually consistent in listings). Never `Cache::flush()`.

## 3. Routes and controllers

### Site — `routes/web.php`, Quran block. Static segments before `{slug}`.

| Method & path | Middleware | Name | Controller |
|---|---|---|---|
| GET `/quran/playlists` | — | `public.quran.playlists.index` | `Site\QuranPlaylistController@index` |
| GET `/quran/playlists/new` | `auth` | `…create` | `@create` |
| POST `/quran/playlists/preview` | `auth`, `throttle:30,1` | `…preview` | `@preview` (JSON) |
| POST `/quran/playlists` | `auth` | `…store` | `@store` |
| GET `/quran/playlists/{slug}` | — | `…show` | `@show` |
| GET `/quran/playlists/{slug}/edit` | `auth` | `…edit` | `@edit` |
| PUT `/quran/playlists/{slug}` | `auth` | `…update` | `@update` |
| DELETE `/quran/playlists/{slug}` | `auth` | `…destroy` | `@destroy` |
| PATCH `/quran/playlists/{slug}/visibility` | `auth` | `…visibility` | `@visibility` |
| POST `/quran/playlists/{slug}/plays` | `throttle:10,1` | `…plays` | `@plays` |

- `{slug}` constrained to `[a-z0-9]{12}`.
- `show` uses `findBySlugVisibleTo` → 404 for private playlists to guests/other users (not 403,
  so existence is not leaked). Owner-only routes use `findOwnedBySlug` → 404 likewise.
- Store/update/destroy/visibility content-negotiate: redirect with flash for form posts, JSON for
  `expectsJson()`. The wizard submits JSON with a real `PUT` (method spoofing does not work from a
  JSON body).
- FormRequests: `Site\StorePlaylistRequest`, `UpdatePlaylistRequest`, `PreviewPlaylistRequest`.

### Reader hand-off

`public.quran.surah` accepts `?reciter=<recording id>`. When present and the id is an active
recitation-kind recording, `QuranController@surah` passes it as the initial `reciterId` to the
player's `configure()` instead of the stored `siteReciterId`. It does **not** write to
`localStorage.siteReciterId` — the user's global preference is untouched.

### Favourite heart

`public.quran.reciter` view gets the same optimistic toggle used by media pages, posting
`{type: 'reciter', id: <profile id>}` to `public.favorites.store` / `destroy`.

### Profile and Quran home

- `public.profile.index` gets a sixth tab `playlists` ("قوائمي"): cards from `forUser` + a
  "new" tile. `ProfileController@show` passes `playlists`.
- `public.quran` (home) gets a "قوائم الاستماع" section: `featured(1)` + latest 3 public + CTA.

### Admin — `routes/admin/quran.php`, controller `Admin\Quran\PlaylistController`

| Route | Name |
|---|---|
| GET `playlists` | `admin.playlists.index` |
| GET `playlists/{playlist}/edit` | `admin.playlists.edit` |
| PUT `playlists/{playlist}` | `admin.playlists.update` |
| DELETE `playlists/{playlist}` | `admin.playlists.destroy` |
| PATCH `playlists/{playlist}/public` | `admin.playlists.toggle-public` |
| PATCH `playlists/{playlist}/featured` | `admin.playlists.toggle-featured` |
| POST `playlists/bulk-unpublish` | `admin.playlists.bulk-unpublish` |

Gated by the existing `role:super_admin|admin|editor,admin` group plus `permission:edit quran,admin`
on mutating routes. Actions are logged through `AdminLoggerService` like the reciter screens.
Sidebar: entry under the Quran submenu; `$moduleMap['playlists'] = 'quran'`.

## 4. UX and screens

Stack: Blade + Alpine + Tailwind (site has no React). Arabic default, full RTL via `dir` on
`<html>` and logical utilities (`ms-/me-`, `text-start`); LTR-locked islands (timers, transport)
use `dir="ltr"`. Dark mode via the existing `class` strategy. Fonts stay Readex Pro / Cairo
(Amiri only for Quranic text). Icons: Lucide only, no emoji. Touch targets ≥ 44 px. Every screen
has skeleton, empty and error states; `prefers-reduced-motion` disables stagger and equalizer.

Design dials: variance 8 (no centered heroes; split / asymmetric layouts that collapse to one
column below `md`), motion 6 (150–300 ms, `transform`/`opacity` only, staggered card entrance
`calc(var(--i) * 40ms)`), density 4. One primary CTA per screen carries the brand gradient
`from-[#0056b3] to-[#218838]`; everything else is zinc neutral with a single emerald accent. No
glows, no gradient text, no pure black.

### 4.1 Gallery — `/quran/playlists`

- Header row: title + one-line subtitle start-aligned; gradient CTA "أنشئ قائمتك" at the end
  (guest → opens the existing login modal).
- Featured band: `md:grid-cols-[2fr_1fr]`. Start cell = featured playlist (name, owner, reciter
  portrait stack, play). End cell = totals (public playlists, total plays) as bare figures separated by
  `border-s`, not boxed cards.
- Controls: search input + sort pills (الأحدث / الأكثر استماعًا); plain GET reloads, state in
  the URL (no client fetch, so no skeleton here).
- Grid `grid-cols-1 sm:grid-cols-2 xl:grid-cols-4`. Card: name, owner avatar + name, stacked
  reciter portraits (≤ 4, `+n`), meta "١١٤ سورة · ٣ قرّاء", plays, play button. `:active
  scale-[0.98]`. Whole card links to the playlist page.
- Empty state with the CTA (different copy when a search matched nothing).

### 4.2 Wizard — `/quran/playlists/new` and `/{slug}/edit`

One Alpine component (`playlistWizard(initial)`) rendered from one Blade view; edit passes the
saved state. Progress rail at the top (3 steps, current highlighted, completed steps clickable);
sticky bottom action bar; `beforeunload` guard while dirty; back always available.

1. **القرّاء** — name input (label above, helper text, error below; default "قائمتي"), then the
   reciter grid. Favourited profiles pinned first under a small "المفضّلون" heading and
   pre-checked. Card = portrait, name, country flag, style chips, check overlay; keyboard
   togglable (`role="checkbox"`, `aria-checked`). Search filters client-side (list is ≤ ~100
   visible profiles, passed as JSON). Bottom bar: "٣ قرّاء" counter + التالي (disabled until ≥ 1).
2. **النطاق** — two large radio tiles: "المصحف كاملًا · ١١٤ سورة" / "سور محددة". Custom reveals a
   114-chip picker (surah number + name, `aria-pressed`), search, quick picks (جزء عمّ، المفصّل،
   تحديد الكل، مسح). Counter + التالي (disabled until ≥ 1 in custom).
3. **المعاينة** — on entry POST `/preview` → skeleton rows → list. Row: surah number badge, name,
   verses count, reciter select (portrait + name; options limited to chosen people covering that
   surah, from `alternatives`), recording sub-select only when > 1 recording, 30-second preview
   play (`$store.audioPlayer.play`). Header actions: "إعادة التوزيع" (re-POSTs preview, keeps
   rows the user pinned), coverage summary "١١٢ من ١١٤ سورة مغطاة". Uncovered rows in amber with
   the inline fix "لا توجد تلاوة لهذه السورة عند القرّاء المختارين — اختر قارئًا آخر أو أزلها".
   Bottom bar: public switch (with the explanation that a link will be shareable) + حفظ. Submit:
   button spinner → success toast → redirect to the playlist page. Server validation errors map
   back onto the field or row.

### 4.3 Playlist page — `/quran/playlists/{slug}`

- Split hero `md:grid-cols-[3fr_2fr]`: start column = name, owner, reciter portrait stack with
  names, "١١٤ سورة · ٣ قرّاء · ١٬٢٤٠ استماع", "تشغيل الكل" gradient CTA, `x-site.share-button`
  (only when public), and — for the owner — edit / public switch / delete (confirm dialog).
  End column = portrait collage of the reciters (2×2 masonry-ish, overlapped by `-mt`).
- Private playlists show a "خاصة" badge and no share button; a public one shows "عامة".
- Track list (`divide-y`, no cards): number, surah name (link to reader with `?reciter=`),
  reciter portrait + name, duration (`duration_seconds`, tabular numerals), row play. Active row
  highlighted with a 3-bar equalizer glyph (CSS keyframes, off under reduced motion). Items with
  `reciter_id = null` render "بحاجة إلى قارئ" and are skipped in playback.
- "تشغيل الكل" builds `[{url, title: surah name, subtitle: reciter name}]` and calls
  `setPlaylist(tracks, 0)`; posts `/plays` once per page load on first play. Row play calls
  `setPlaylist(tracks, i)`.

### 4.4 Profile → قوائمي

Compact card list (name, meta, public badge, play, edit) + dashed "قائمة جديدة" tile; empty
state points to the wizard.

### 4.5 Quran home section

"قوائم الاستماع": featured playlist (if any) as a wide row, then up to 3 latest public as cards,
"عرض الكل" link, and the CTA. Section hidden entirely when there are no public playlists and
the visitor is a guest.

### 4.6 Admin

- Index: `<x-admin.stats-card>` row (إجمالي / عامة / مميّزة / استماعات) + `<x-admin.data-table>`
  (name, owner, surahs, reciters, public switch, featured switch, plays, created, actions).
  Filters: public, featured, search (name or owner email). Bulk: unpublish.
- Edit: name, description, public, featured, and the same preview-style row list with a reciter
  select per surah (all active recitation recordings covering that surah). Save via
  `AdminLoggerService`-logged update.
- Delete with the standard confirm modal.

## 5. Error handling

- All writes in `DB::transaction`. Items are replaced atomically on update (delete + insert
  inside the transaction; `items_count` recomputed).
- Ownership is enforced in the repository lookups (`findOwnedBySlug`), not in controllers.
- `preview`/`store` reject profiles that are not `visible()` and recordings that are not active
  recitation-kind — server-side, regardless of what the client sent.
- `recordPlay` uses `increment()`; throttled per IP.
- No `Throwable` swallowed into empty fallbacks; failures `report()`.

## 6. Testing

Feature (`tests/Feature/Web/QuranPlaylistTest.php`):
- preview assigns only recordings with a `quran_recitations` row for the surah and lists
  `uncovered`;
- store persists exactly the previewed items, sets `items_count`, respects the 20-playlist cap;
- store rejects a `(surah, recording)` pair without audio;
- private playlist → 404 for guest and another user; public → 200 for guest;
- owner can update, toggle visibility, delete; non-owner → 404;
- `/plays` increments once and is throttled;
- reader `?reciter=` selects that recording and ignores an invalid id.

Feature (`tests/Feature/Admin/PlaylistAdminTest.php`): index lists, toggle public/featured,
bulk unpublish, destroy, permission gate.

Feature (`tests/Feature/Web/ProfileFavoritesTest.php` extension): add/remove `reciter` favourite,
appears in the favourites tab and pre-checks the wizard.

Unit (`tests/Unit/Quran/PlaylistAssignmentTest.php`): balance (max − min ≤ 1 over 114 surahs with
3 full-coverage profiles), determinism under a seeded RNG, `keep` honoured, default recording
preferred, uncovered reported.

Final manual pass in Chrome: create → preview (reshuffle, change one row) → save → share link as
guest → row deep-link into the reader with the assigned reciter → profile tab → admin unpublish →
link now 404 for guest.

## 7. Files touched (expected)

```
database/migrations/2026_09_13_000001_create_quran_playlists_table.php
database/migrations/2026_09_13_000002_create_quran_playlist_items_table.php
app/Domain/Shared/Enums/FavorableType.php                      (+RECITER)
app/Providers/FavoriteServiceProvider.php                      (+reciter registration)
app/Domain/Quran/Models/{Playlist,PlaylistItem}.php
app/Domain/Quran/DTOs/{PlaylistData,AssignmentResult}.php
app/Domain/Quran/Repositories/PlaylistRepository.php
app/Domain/Quran/Services/PlaylistService.php
app/Domain/Quran/Services/QuranCacheKeys.php                   (+playlists family)
app/Http/Controllers/Site/QuranPlaylistController.php
app/Http/Controllers/Site/QuranController.php                  (?reciter= hand-off, home section)
app/Http/Controllers/Site/ProfileController.php                (+playlists)
app/Http/Requests/Site/{Store,Update,Preview}PlaylistRequest.php
app/Http/Controllers/Admin/Quran/PlaylistController.php
app/Http/Requests/Admin/UpdatePlaylistRequest.php
routes/web.php · routes/admin/quran.php
resources/views/public/quran/playlists/{index,show,wizard}.blade.php
resources/views/public/quran/{index,reciter}.blade.php         (home section, heart)
resources/views/public/profile/index.blade.php                 (+tab)
resources/views/admin/playlists/{index,edit}.blade.php
resources/views/components/admin/sidebar.blade.php
resources/lang/{ar,en,fr,tr,ur}/quran.php · profile.php        (playlists.* keys; Arabic complete, others fall back)
tests/… as in §6
```
