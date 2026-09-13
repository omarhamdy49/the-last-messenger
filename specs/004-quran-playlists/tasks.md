# Quran Reciter Playlists Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Signed-in visitors build, edit, share and play per-surah reciter playlists; admins moderate them.

**Architecture:** Two new tables in the Quran domain (`quran_playlists`, `quran_playlist_items`), a `PlaylistService::assign()` that maps surahs to recordings server-side, one site controller (gallery / wizard / show / CRUD / plays), one admin controller, and a single Alpine wizard component. Playback reuses the shared `$store.audioPlayer.setPlaylist()`; the reader accepts `?reciter=` for hand-off. Reciter people become a `FavorableType::RECITER` favourite.

**Tech Stack:** PHP 8.5, Laravel 13, MySQL 8, Blade + Alpine.js + Tailwind (site and admin), PHPUnit. No new package.

**Spec:** `specs/004-quran-playlists/spec.md`

## Global Constraints

- PHP `^8.5`, Laravel `^13.0`; no new Composer/npm dependency.
- Translations for reciter names live in `quran_reciter_profile_translations` / `quran_reciter_translations`; never `select('name')` on the base tables.
- Slug: `char(12)` random `[a-z0-9]`, never derived from the name.
- Limits: ≤ 20 playlists per user; name 2–80; description ≤ 300; custom scope ≥ 1 surah; ≥ 1 reciter profile.
- Visibility: `Playlist::scopeVisibleTo()` is the only rule; private playlists → 404 (never 403).
- A recitation counts only if its `quran_recording_sources.is_active = 1` (same rule as `QuranController@reciter`).
- Never `Cache::flush()`; use `QuranCacheKeys` versioned keys.
- Arabic is the default locale; every new string has an `ar` entry; `en` entries added too; `fr/tr/ur` fall back.
- Icons: Lucide (`data-lucide`), no emoji. Touch targets ≥ 44 px. `prefers-reduced-motion` respected.
- Admin routes are named `admin.playlists.*`; site routes `public.quran.playlists.*`.
- Run tests with `php artisan test --filter=<Name>`; style with `./vendor/bin/pint --dirty`.
- Commit after each task; branch `004-quran-playlists`.

---

### Task 1: Schema

**Files:**
- Create: `database/migrations/2026_09_14_000001_create_quran_playlists_table.php`
- Create: `database/migrations/2026_09_14_000002_create_quran_playlist_items_table.php`
- Test: `tests/Feature/Web/QuranPlaylistTest.php` (schema smoke only, extended in later tasks)

**Interfaces:**
- Produces tables exactly as the spec §1.

- [ ] **Step 1: Write the failing schema test**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class QuranPlaylistTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_playlist_tables_exist(): void
    {
        $this->assertTrue(Schema::hasColumns('quran_playlists', ['user_id', 'name', 'slug', 'scope', 'is_public', 'is_featured', 'plays_count', 'items_count']));
        $this->assertTrue(Schema::hasColumns('quran_playlist_items', ['playlist_id', 'surah_id', 'reciter_id']));
    }
}
```

- [ ] **Step 2: Run it** — `php artisan test --filter=test_playlist_tables_exist` → FAIL (table missing).

- [ ] **Step 3: Migrations**

`2026_09_14_000001_create_quran_playlists_table.php`:
```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quran_playlists', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 80);
            $table->char('slug', 12)->unique();
            $table->string('description', 300)->nullable();
            $table->enum('scope', ['full', 'custom'])->default('full');
            $table->boolean('is_public')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('plays_count')->default(0);
            $table->unsignedSmallInteger('items_count')->default(0);
            $table->timestamps();
            $table->index(['is_public', 'is_featured', 'plays_count']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quran_playlists');
    }
};
```

`2026_09_14_000002_create_quran_playlist_items_table.php`:
```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quran_playlist_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('playlist_id')->constrained('quran_playlists')->cascadeOnDelete();
            $table->unsignedBigInteger('surah_id');
            $table->foreign('surah_id')->references('id')->on('quran_surahs')->cascadeOnDelete();
            $table->foreignId('reciter_id')->nullable()->constrained('quran_reciters')->nullOnDelete();
            $table->unique(['playlist_id', 'surah_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quran_playlist_items');
    }
};
```
(Check `quran_surahs.id` type with `SHOW COLUMNS FROM quran_surahs` before choosing `unsignedBigInteger`; match it exactly or the FK fails.)

- [ ] **Step 4: Run** `php artisan migrate && php artisan test --filter=test_playlist_tables_exist` → PASS.
- [ ] **Step 5: Commit** `git add database/migrations tests/Feature/Web/QuranPlaylistTest.php && git commit -m "feat(quran): playlist tables"`

---

### Task 2: Models, DTOs, repository

**Files:**
- Create: `app/Domain/Quran/Models/Playlist.php`, `app/Domain/Quran/Models/PlaylistItem.php`
- Create: `app/Domain/Quran/DTOs/PlaylistData.php`, `app/Domain/Quran/DTOs/AssignmentResult.php`
- Create: `app/Domain/Quran/Repositories/PlaylistRepository.php`
- Modify: `app/Domain/Quran/Services/QuranCacheKeys.php` (add `playlists` family)

**Interfaces (produces):**
- `Playlist` fillable: `user_id,name,description,scope,is_public,is_featured`; relations `owner()`, `items()`; `scopeVisibleTo(?User)`; accessor `reciterProfiles(): Collection<ReciterProfile>`.
- `PlaylistItem` fillable `playlist_id,surah_id,reciter_id`; `surah()`, `reciter()`; `$timestamps = false`.
- `PlaylistData(string $name, ?string $description, string $scope, array $items /* surahId => recordingId|null */, bool $isPublic)`.
- `AssignmentResult(array $items, array $uncovered, array $alternatives)`.
- `PlaylistRepository`: `forUser(User): Collection`, `countForUser(User): int`, `gallery(?string $search, string $sort, int $perPage): LengthAwarePaginator`, `featured(int $limit): Collection`, `findBySlugVisibleTo(string, ?User): ?Playlist`, `findOwnedBySlug(string, User): ?Playlist`, `adminList(array $filters, int $perPage): LengthAwarePaginator`.
- `QuranCacheKeys::flushPlaylists()`, `QuranCacheKeys::playlistScoped(string): string`.

- [ ] **Step 1: Models**

`Playlist.php`:
```php
<?php

declare(strict_types=1);

namespace App\Domain\Quran\Models;

use App\Domain\Quran\Services\QuranCacheKeys;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Playlist extends Model
{
    protected $table = 'quran_playlists';

    protected $fillable = ['user_id', 'name', 'description', 'scope', 'is_public', 'is_featured'];

    protected $casts = ['is_public' => 'boolean', 'is_featured' => 'boolean', 'plays_count' => 'integer', 'items_count' => 'integer'];

    protected static function booted(): void
    {
        static::creating(function (self $playlist): void {
            // ponytail: 36^12 space, collision retry is belt-and-braces.
            do {
                $slug = Str::lower(Str::random(12));
            } while (static::where('slug', $slug)->exists());
            $playlist->slug = $slug;
        });
        static::saved(fn () => QuranCacheKeys::flushPlaylists());
        static::deleted(fn () => QuranCacheKeys::flushPlaylists());
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PlaylistItem::class, 'playlist_id')->orderBy('surah_id');
    }

    /** Public, or owned by the given user. The single visibility rule. */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        return $query->where(function (Builder $q) use ($user): void {
            $q->where('is_public', true);
            if ($user) {
                $q->orWhere('user_id', $user->id);
            }
        });
    }

    /** Distinct people behind the loaded items (requires items.reciter.profile loaded). */
    public function reciterProfiles(): Collection
    {
        return $this->items->map(fn (PlaylistItem $i) => $i->reciter?->profile)->filter()->unique('id')->values();
    }
}
```

`PlaylistItem.php`:
```php
<?php

declare(strict_types=1);

namespace App\Domain\Quran\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlaylistItem extends Model
{
    protected $table = 'quran_playlist_items';

    public $timestamps = false;

    protected $fillable = ['playlist_id', 'surah_id', 'reciter_id'];

    public function playlist(): BelongsTo
    {
        return $this->belongsTo(Playlist::class, 'playlist_id');
    }

    public function surah(): BelongsTo
    {
        return $this->belongsTo(Surah::class, 'surah_id');
    }

    public function reciter(): BelongsTo
    {
        return $this->belongsTo(Reciter::class, 'reciter_id')->without('sources');
    }
}
```

- [ ] **Step 2: DTOs**

```php
// app/Domain/Quran/DTOs/PlaylistData.php
final readonly class PlaylistData
{
    /** @param array<int,int|null> $items surahId => recordingId */
    public function __construct(
        public string $name,
        public ?string $description,
        public string $scope,
        public array $items,
        public bool $isPublic,
    ) {}
}

// app/Domain/Quran/DTOs/AssignmentResult.php
final readonly class AssignmentResult
{
    /**
     * @param array<int,int>            $items        surahId => recordingId
     * @param list<int>                 $uncovered    surah ids nobody covers
     * @param array<int,array<int,list<int>>> $alternatives surahId => profileId => recordingIds
     */
    public function __construct(public array $items, public array $uncovered, public array $alternatives) {}

    public function toArray(): array
    {
        return ['items' => $this->items, 'uncovered' => $this->uncovered, 'alternatives' => $this->alternatives];
    }
}
```

- [ ] **Step 3: Cache keys** — in `QuranCacheKeys` add next to the reciter family:
```php
private const PLAYLIST_VERSION_KEY = 'playlists:version';

public static function flushPlaylists(): void { self::bump(self::PLAYLIST_VERSION_KEY); }

public static function playlistScoped(string $key): string
{
    return 'playlists:v'.((int) Cache::get(self::PLAYLIST_VERSION_KEY, 0)).':'.$key;
}
```

- [ ] **Step 4: Repository**

```php
<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Quran\Models\Playlist;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class PlaylistRepository
{
    private const WITH = [
        'owner:id,name,avatar_path',
        'items.surah:id,name_ar,name_en,transliteration,verses_count',
        'items.reciter:id,profile_id,style,riwayah,slug',
        'items.reciter.profile:id,slug,profile_image,country',
        'items.reciter.profile.translations:id,profile_id,locale,name',
    ];

    private function base(): Builder
    {
        return Playlist::query()->with(self::WITH);
    }

    public function forUser(User $user): Collection
    {
        return $this->base()->where('user_id', $user->id)->latest('id')->get();
    }

    public function countForUser(User $user): int
    {
        return Playlist::where('user_id', $user->id)->count();
    }

    public function gallery(?string $search, string $sort, int $perPage = 24): LengthAwarePaginator
    {
        return $this->base()->where('is_public', true)
            ->when($search, fn ($q) => $q->where('name', 'like', '%'.$search.'%'))
            ->when($sort === 'plays', fn ($q) => $q->orderByDesc('plays_count'), fn ($q) => $q->latest('id'))
            ->paginate($perPage)->withQueryString();
    }

    public function featured(int $limit = 1): Collection
    {
        return $this->base()->where('is_public', true)->where('is_featured', true)->orderByDesc('plays_count')->limit($limit)->get();
    }

    public function latestPublic(int $limit = 3): Collection
    {
        return $this->base()->where('is_public', true)->latest('id')->limit($limit)->get();
    }

    public function findBySlugVisibleTo(string $slug, ?User $user): ?Playlist
    {
        return $this->base()->where('slug', $slug)->visibleTo($user)->first();
    }

    public function findOwnedBySlug(string $slug, User $user): ?Playlist
    {
        return $this->base()->where('slug', $slug)->where('user_id', $user->id)->first();
    }

    /** @param array<string,mixed> $filters keys: search, public ('1'|'0'), featured ('1'|'0') */
    public function adminList(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));

        return Playlist::query()->with('owner:id,name,email')
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')
                ->orWhereHas('owner', fn ($o) => $o->where('email', 'like', '%'.$search.'%')->orWhere('name', 'like', '%'.$search.'%'))))
            ->when(in_array($filters['public'] ?? null, ['1', '0'], true), fn ($q) => $q->where('is_public', $filters['public'] === '1'))
            ->when(in_array($filters['featured'] ?? null, ['1', '0'], true), fn ($q) => $q->where('is_featured', $filters['featured'] === '1'))
            ->latest('id')->paginate($perPage)->withQueryString();
    }
}
```

- [ ] **Step 5: Smoke** — `php artisan tinker --execute="dd(App\Domain\Quran\Models\Playlist::count())"` → `0`.
- [ ] **Step 6: Commit** `feat(quran): playlist models + repository`

---

### Task 3: `PlaylistService::assign()` (TDD)

**Files:**
- Create: `app/Domain/Quran/Services/PlaylistService.php`
- Test: `tests/Unit/Quran/PlaylistAssignmentTest.php` (uses DB — `RefreshDatabase`; it is a unit of behaviour, not of isolation)

**Interfaces (produces):**
```php
final class PlaylistService {
    public function __construct(private PlaylistRepository $repo, private ?Random\Randomizer $rng = null) {}
    /** @param list<int> $profileIds @param list<int> $surahIds @param array<int,int> $keep */
    public function assign(array $profileIds, array $surahIds, array $keep = []): AssignmentResult;
    /** @return array<int,array{surah:int,recording:int}> invalid pairs */
    public function invalidPairs(array $items): array;
    public function create(User $user, PlaylistData $data): Playlist;
    public function update(Playlist $p, PlaylistData $data): Playlist;
    public function delete(Playlist $p): void;
    public function setPublic(Playlist $p, bool $on): void;
    public function setFeatured(Playlist $p, bool $on): void;
    public function recordPlay(Playlist $p): void;
    /** Recording candidates per surah for the admin editor: surahId => list<{id, label}> */
    public function coverage(array $surahIds): array;
}
```

- [ ] **Step 1: Test fixture helpers + failing tests**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Quran;

use App\Domain\Quran\Models\Recitation;
use App\Domain\Quran\Models\Reciter;
use App\Domain\Quran\Models\ReciterProfile;
use App\Domain\Quran\Models\Surah;
use App\Domain\Quran\Services\PlaylistService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Random\Engine\Mt19937;
use Random\Randomizer;
use Tests\TestCase;

final class PlaylistAssignmentTest extends TestCase
{
    use RefreshDatabase;

    /** @return list<int> surah ids */
    private function surahs(int $n): array
    {
        $ids = [];
        for ($i = 1; $i <= $n; $i++) {
            $ids[] = Surah::query()->create(['id' => $i, 'revelation_place' => 'makkah', 'revelation_order' => $i, 'bismillah_pre' => true, 'name_ar' => 'س'.$i, 'name_en' => 'S'.$i, 'transliteration' => 'S'.$i, 'verses_count' => 3])->id;
        }

        return $ids;
    }

    /** @param list<int> $covers surah ids that get audio */
    private function person(string $slug, array $covers, bool $default = true): array
    {
        $profile = ReciterProfile::create(['identity_key' => 'test:'.$slug, 'slug' => $slug, 'is_active' => true]);
        $profile->translations()->create(['locale' => 'ar', 'name' => $slug]);
        $rec = Reciter::create(['slug' => $slug.'-rec', 'profile_id' => $profile->id, 'kind' => 'recitation', 'is_active' => true, 'display_order' => 1]);
        $rec->translations()->create(['locale' => 'ar', 'name' => $slug, 'is_ready' => true]);
        foreach ($covers as $s) {
            Recitation::create(['reciter_id' => $rec->id, 'surah_id' => $s, 'audio_url' => "https://cdn.test/{$slug}/{$s}.mp3", 'format' => 'mp3']);
        }
        if ($default) {
            $profile->update(['default_recording_id' => $rec->id]);
        }

        return [$profile->id, $rec->id];
    }

    private function service(int $seed = 7): PlaylistService
    {
        return new PlaylistService(app(\App\Domain\Quran\Repositories\PlaylistRepository::class), new Randomizer(new Mt19937($seed)));
    }

    public function test_assignment_is_balanced_across_full_coverage(): void
    {
        $surahs = $this->surahs(30);
        [$a] = $this->person('a', $surahs);
        [$b] = $this->person('b', $surahs);
        [$c] = $this->person('c', $surahs);

        $result = $this->service()->assign([$a, $b, $c], $surahs);

        $this->assertCount(30, $result->items);
        $this->assertSame([], $result->uncovered);
        $byRecording = array_count_values($result->items);
        $this->assertLessThanOrEqual(1, max($byRecording) - min($byRecording));
    }

    public function test_only_covering_recordings_are_assigned_and_uncovered_reported(): void
    {
        $surahs = $this->surahs(4);
        [$a, $recA] = $this->person('a', [1, 2]);
        [$b, $recB] = $this->person('b', [2, 3]);

        $result = $this->service()->assign([$a, $b], $surahs);

        $this->assertSame($recA, $result->items[1]);
        $this->assertSame($recB, $result->items[3]);
        $this->assertContains($result->items[2], [$recA, $recB]);
        $this->assertSame([4], $result->uncovered);
        $this->assertSame([$a => [$recA], $b => [$recB]], $result->alternatives[2]);
    }

    public function test_keep_pins_a_recording_when_it_still_covers(): void
    {
        $surahs = $this->surahs(2);
        [$a, $recA] = $this->person('a', $surahs);
        [$b, $recB] = $this->person('b', $surahs);

        $result = $this->service()->assign([$a, $b], $surahs, [1 => $recB, 2 => $recB]);

        $this->assertSame([1 => $recB, 2 => $recB], $result->items);
    }

    public function test_seeded_rng_is_deterministic(): void
    {
        $surahs = $this->surahs(10);
        [$a] = $this->person('a', $surahs);
        [$b] = $this->person('b', $surahs);

        $this->assertSame($this->service(3)->assign([$a, $b], $surahs)->items, $this->service(3)->assign([$a, $b], $surahs)->items);
    }

    public function test_inactive_source_does_not_count_as_coverage(): void
    {
        $surahs = $this->surahs(1);
        [$a, $recA] = $this->person('a', $surahs);
        \DB::table('quran_recording_sources')->where('recording_id', $recA)->update(['is_active' => false]);

        $result = $this->service()->assign([$a], $surahs);

        $this->assertSame([], $result->items);
        $this->assertSame([1], $result->uncovered);
    }
}
```

- [ ] **Step 2: Run** `php artisan test --filter=PlaylistAssignmentTest` → FAIL (class missing).

- [ ] **Step 3: Implement**

```php
<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\DTOs\AssignmentResult;
use App\Domain\Quran\DTOs\PlaylistData;
use App\Domain\Quran\Models\Playlist;
use App\Domain\Quran\Repositories\PlaylistRepository;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Random\Randomizer;

final class PlaylistService
{
    private readonly Randomizer $rng;

    public function __construct(private readonly PlaylistRepository $repo, ?Randomizer $rng = null)
    {
        $this->rng = $rng ?? new Randomizer;
    }

    /**
     * profileId => recordingId => list<surahId>, restricted to active recitation
     * recordings whose audio source is active (same rule as the reciter page).
     *
     * @return array<int, array<int, list<int>>>
     */
    private function coverageMap(array $profileIds, array $surahIds): array
    {
        $rows = DB::table('quran_recitations as rc')
            ->join('quran_reciters as r', 'r.id', '=', 'rc.reciter_id')
            ->join('quran_recording_sources as s', 's.id', '=', 'rc.source_id')
            ->whereIn('r.profile_id', $profileIds)->whereIn('rc.surah_id', $surahIds)
            ->where('r.kind', 'recitation')->where('r.is_active', true)->where('s.is_active', true)
            ->orderBy('r.display_order')->orderBy('r.id')
            ->get(['r.profile_id', 'rc.reciter_id', 'rc.surah_id']);

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->profile_id][(int) $row->reciter_id][] = (int) $row->surah_id;
        }

        return $map;
    }

    public function assign(array $profileIds, array $surahIds, array $keep = []): AssignmentResult
    {
        $profileIds = array_values(array_unique(array_map('intval', $profileIds)));
        $surahIds = array_values(array_unique(array_map('intval', $surahIds)));
        sort($surahIds);

        $map = $this->coverageMap($profileIds, $surahIds);
        $defaults = DB::table('quran_reciter_profiles')->whereIn('id', $profileIds)->pluck('default_recording_id', 'id');

        // surahId => profileId => list<recordingId> (recordings already in display order)
        $alternatives = [];
        foreach ($map as $profileId => $recordings) {
            foreach ($recordings as $recordingId => $surahs) {
                foreach ($surahs as $s) {
                    $alternatives[$s][$profileId][] = $recordingId;
                }
            }
        }

        $items = [];
        $uncovered = [];
        $load = array_fill_keys($profileIds, 0);

        foreach ($surahIds as $s) {
            $options = $alternatives[$s] ?? [];
            if ($options === []) {
                $uncovered[] = $s;
                continue;
            }
            // A pinned recording wins if it still covers this surah.
            $pinned = isset($keep[$s]) ? (int) $keep[$s] : null;
            $pinnedProfile = null;
            foreach ($options as $profileId => $recordings) {
                if ($pinned !== null && in_array($pinned, $recordings, true)) {
                    $pinnedProfile = $profileId;
                }
            }
            if ($pinnedProfile !== null) {
                $items[$s] = $pinned;
                $load[$pinnedProfile]++;
                continue;
            }
            // Balanced random: fewest assignments so far, ties broken randomly.
            $candidates = array_keys($options);
            $min = min(array_map(fn ($p) => $load[$p], $candidates));
            $tied = array_values(array_filter($candidates, fn ($p) => $load[$p] === $min));
            $profileId = $tied[$this->rng->getInt(0, count($tied) - 1)];
            $recordings = $options[$profileId];
            $default = (int) ($defaults[$profileId] ?? 0);
            $items[$s] = in_array($default, $recordings, true) ? $default : $recordings[0];
            $load[$profileId]++;
        }

        return new AssignmentResult($items, $uncovered, $alternatives);
    }

    /**
     * (surah, recording) pairs that have no active audio. Empty means the payload is safe to store.
     *
     * @param array<int,int|null> $items
     * @return list<array{surah:int,recording:int}>
     */
    public function invalidPairs(array $items): array
    {
        $pairs = array_filter($items, fn ($r) => $r !== null);
        if ($pairs === []) {
            return [];
        }
        $valid = DB::table('quran_recitations as rc')
            ->join('quran_reciters as r', 'r.id', '=', 'rc.reciter_id')
            ->join('quran_recording_sources as s', 's.id', '=', 'rc.source_id')
            ->whereIn('rc.reciter_id', array_values($pairs))->whereIn('rc.surah_id', array_keys($pairs))
            ->where('r.kind', 'recitation')->where('r.is_active', true)->where('s.is_active', true)
            ->get(['rc.surah_id', 'rc.reciter_id'])
            ->map(fn ($r) => $r->surah_id.':'.$r->reciter_id)->flip();

        $bad = [];
        foreach ($pairs as $surah => $recording) {
            if (! isset($valid[$surah.':'.$recording])) {
                $bad[] = ['surah' => (int) $surah, 'recording' => (int) $recording];
            }
        }

        return $bad;
    }

    public function create(User $user, PlaylistData $data): Playlist
    {
        return DB::transaction(function () use ($user, $data): Playlist {
            $playlist = Playlist::create([
                'user_id' => $user->id, 'name' => $data->name, 'description' => $data->description,
                'scope' => $data->scope, 'is_public' => $data->isPublic,
            ]);
            $this->replaceItems($playlist, $data->items);

            return $playlist->refresh();
        });
    }

    public function update(Playlist $playlist, PlaylistData $data): Playlist
    {
        return DB::transaction(function () use ($playlist, $data): Playlist {
            $playlist->fill(['name' => $data->name, 'description' => $data->description, 'scope' => $data->scope, 'is_public' => $data->isPublic])->save();
            $this->replaceItems($playlist, $data->items);

            return $playlist->refresh();
        });
    }

    /** @param array<int,int|null> $items */
    private function replaceItems(Playlist $playlist, array $items): void
    {
        $playlist->items()->delete();
        ksort($items);
        $rows = [];
        foreach ($items as $surah => $recording) {
            $rows[] = ['playlist_id' => $playlist->id, 'surah_id' => (int) $surah, 'reciter_id' => $recording !== null ? (int) $recording : null];
        }
        if ($rows !== []) {
            DB::table('quran_playlist_items')->insert($rows);
        }
        $playlist->forceFill(['items_count' => count($rows)])->save();
    }

    public function delete(Playlist $playlist): void
    {
        $playlist->delete();
    }

    public function setPublic(Playlist $playlist, bool $on): void
    {
        $playlist->forceFill(['is_public' => $on])->save();
    }

    public function setFeatured(Playlist $playlist, bool $on): void
    {
        $playlist->forceFill(['is_featured' => $on])->save();
    }

    public function recordPlay(Playlist $playlist): void
    {
        Playlist::whereKey($playlist->id)->increment('plays_count'); // no model event → no cache bump
    }

    /**
     * Every active recording that covers each surah (admin editor). surahId => list<{id,label}>.
     *
     * @param list<int> $surahIds
     * @return array<int, list<array{id:int,label:string}>>
     */
    public function coverage(array $surahIds): array
    {
        $locale = app()->getLocale();
        $rows = DB::table('quran_recitations as rc')
            ->join('quran_reciters as r', 'r.id', '=', 'rc.reciter_id')
            ->join('quran_recording_sources as s', 's.id', '=', 'rc.source_id')
            ->leftJoin('quran_reciter_profile_translations as t', fn ($j) => $j->on('t.profile_id', '=', 'r.profile_id')->where('t.locale', $locale))
            ->leftJoin('quran_reciter_profile_translations as ta', fn ($j) => $j->on('ta.profile_id', '=', 'r.profile_id')->where('ta.locale', 'ar'))
            ->whereIn('rc.surah_id', $surahIds)->where('r.kind', 'recitation')->where('r.is_active', true)->where('s.is_active', true)
            ->orderBy('r.display_order')
            ->get(['rc.surah_id', 'r.id', 'r.style', 'r.riwayah', DB::raw('COALESCE(t.name, ta.name, r.slug) as name')]);

        $out = [];
        $seen = [];
        foreach ($rows as $row) {
            $key = $row->surah_id.':'.$row->id;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $label = $row->name.($row->riwayah ? ' · '.$row->riwayah : ($row->style ? ' · '.__('quran.recordings.'.$row->style) : ''));
            $out[(int) $row->surah_id][] = ['id' => (int) $row->id, 'label' => $label];
        }

        return $out;
    }
}
```

- [ ] **Step 4: Run** → all 5 PASS. `./vendor/bin/pint --dirty`.
- [ ] **Step 5: Commit** `feat(quran): playlist assignment service`

---

### Task 4: Reciter favourites (`FavorableType::RECITER`)

**Files:**
- Modify: `app/Domain/Shared/Enums/FavorableType.php`
- Modify: `app/Providers/FavoriteServiceProvider.php`
- Modify: `resources/lang/ar/profile.php`, `resources/lang/en/profile.php` (`type_reciter`)
- Test: `tests/Feature/Web/ProfileFavoritesTest.php`

**Interfaces (produces):** `FavorableType::RECITER` ('reciter', icon `mic`) → `ReciterProfile`.

- [ ] **Step 1: Failing test** (append to `ProfileFavoritesTest`):

```php
public function test_user_can_favorite_a_reciter_profile(): void
{
    $profile = \App\Domain\Quran\Models\ReciterProfile::create(['identity_key' => 't:husary', 'slug' => 'husary', 'is_active' => true]);
    $profile->translations()->create(['locale' => 'ar', 'name' => 'الحصري']);
    $user = User::factory()->create();

    $this->actingAs($user)->postJson(route('public.favorites.store'), ['type' => 'reciter', 'id' => $profile->id])->assertOk();

    $this->assertDatabaseHas('favorites', ['user_id' => $user->id, 'favorable_id' => $profile->id, 'favorable_type' => \App\Domain\Quran\Models\ReciterProfile::class]);
    $this->actingAs($user)->get(route('public.favorites'))->assertOk()->assertSee('الحصري');
}
```
(Check `favorable_type` value with `DB::table('favorites')->first()` if the app uses a morph map alias; assert on whatever it actually stores.)

- [ ] **Step 2: Run** → FAIL (422, type not allowed).
- [ ] **Step 3: Implement** — enum: `case RECITER = 'reciter';` + `self::RECITER => 'mic'` in `icon()`. Provider `register()`: `$registry->register(FavorableType::RECITER, ReciterProfile::class, $this->reciterMapper());`; `boot()`: `$repository->eagerLoadFor(ReciterProfile::class, ['translations']);`; mapper:

```php
private function reciterMapper(): callable
{
    return static function (Model $model, string $locale): array {
        /** @var ReciterProfile $model */
        return [
            'title' => $model->displayName($locale),
            'excerpt' => $model->country ? country_name($model->country, $locale) : null,
            'url' => Route::has('public.quran.reciter') ? route('public.quran.reciter', $model->slug) : null,
        ];
    };
}
```
Lang: `'type_reciter' => 'قارئ'` (ar), `'Reciter'` (en). If the favourites view switches on `type` for a filter pill list, add the case there too (grep `type_media` in `resources/views/public/favorites`).

- [ ] **Step 4: Run** `php artisan test --filter=ProfileFavoritesTest` → PASS.
- [ ] **Step 5: Commit** `feat(favorites): reciter profiles are bookmarkable`

---

### Task 5: Site routes, FormRequests, `QuranPlaylistController` (JSON/HTTP layer, TDD)

**Files:**
- Create: `app/Http/Controllers/Site/QuranPlaylistController.php`
- Create: `app/Http/Requests/Site/PreviewPlaylistRequest.php`, `StorePlaylistRequest.php` (used for update too)
- Modify: `routes/web.php` (Quran block, before `/quran/{surahNumber}`; auth routes inside the existing `auth` group or inline `->middleware('auth')`)
- Test: `tests/Feature/Web/QuranPlaylistTest.php`

**Interfaces (produces):** routes from spec §3; `preview` JSON `{items:{surah:recording}, uncovered:[…], alternatives:{surah:{profile:[rec…]}}}`; store/update JSON `{success:true, redirect:url}`; visibility JSON `{success:true, is_public:bool}`; plays JSON `{success:true}`.

- [ ] **Step 1: Failing tests** (add to `QuranPlaylistTest`; reuse the fixture helpers from Task 3 by copying `surahs()` and `person()` into this class as private methods):

```php
private function payload(array $profiles, array $surahs, array $over = []): array
{
    $preview = app(\App\Domain\Quran\Services\PlaylistService::class)->assign($profiles, $surahs);

    return $over + ['name' => 'ختمتي', 'description' => null, 'scope' => 'custom', 'surah_ids' => $surahs, 'items' => $preview->items, 'is_public' => false];
}

public function test_guest_cannot_open_wizard(): void
{
    $this->get(route('public.quran.playlists.create'))->assertRedirect();
}

public function test_preview_assigns_only_covering_recordings(): void
{
    $surahs = $this->surahs(3);
    [$a, $recA] = $this->person('a', [1, 2]);
    $user = User::factory()->create();

    $res = $this->actingAs($user)->postJson(route('public.quran.playlists.preview'), ['profile_ids' => [$a], 'scope' => 'full'])->assertOk();

    $this->assertSame([1 => $recA, 2 => $recA], array_map('intval', $res->json('items')));
    $this->assertSame([3], $res->json('uncovered'));
}

public function test_store_persists_previewed_items(): void
{
    $surahs = $this->surahs(2);
    [$a, $recA] = $this->person('a', $surahs);
    $user = User::factory()->create();

    $res = $this->actingAs($user)->postJson(route('public.quran.playlists.store'), $this->payload([$a], $surahs))->assertCreated();

    $playlist = \App\Domain\Quran\Models\Playlist::first();
    $this->assertSame($user->id, $playlist->user_id);
    $this->assertSame(2, $playlist->items_count);
    $this->assertMatchesRegularExpression('/^[a-z0-9]{12}$/', $playlist->slug);
    $this->assertSame(route('public.quran.playlists.show', $playlist->slug), $res->json('redirect'));
}

public function test_store_rejects_pair_without_audio(): void
{
    $surahs = $this->surahs(2);
    [$a, $recA] = $this->person('a', [1]);
    $user = User::factory()->create();

    $this->actingAs($user)->postJson(route('public.quran.playlists.store'), $this->payload([$a], $surahs, ['items' => [1 => $recA, 2 => $recA]]))
        ->assertUnprocessable()->assertJsonValidationErrorFor('items.2');
}

public function test_store_enforces_playlist_cap(): void
{
    $surahs = $this->surahs(1);
    [$a] = $this->person('a', $surahs);
    $user = User::factory()->create();
    for ($i = 0; $i < 20; $i++) {
        \App\Domain\Quran\Models\Playlist::create(['user_id' => $user->id, 'name' => 'p'.$i, 'scope' => 'full']);
    }

    $this->actingAs($user)->postJson(route('public.quran.playlists.store'), $this->payload([$a], $surahs))->assertUnprocessable();
}

public function test_private_playlist_is_404_for_guest_and_others_but_visible_to_owner(): void
{
    $surahs = $this->surahs(1);
    [$a] = $this->person('a', $surahs);
    $owner = User::factory()->create();
    $playlist = app(\App\Domain\Quran\Services\PlaylistService::class)->create($owner, new \App\Domain\Quran\DTOs\PlaylistData('x', null, 'full', [1 => null], false));

    $this->get(route('public.quran.playlists.show', $playlist->slug))->assertNotFound();
    $this->actingAs(User::factory()->create())->get(route('public.quran.playlists.show', $playlist->slug))->assertNotFound();
    $this->actingAs($owner)->get(route('public.quran.playlists.show', $playlist->slug))->assertOk();
}

public function test_public_playlist_is_visible_to_guest_and_plays_increment(): void
{
    $surahs = $this->surahs(1);
    [$a, $recA] = $this->person('a', $surahs);
    $owner = User::factory()->create();
    $playlist = app(\App\Domain\Quran\Services\PlaylistService::class)->create($owner, new \App\Domain\Quran\DTOs\PlaylistData('x', null, 'full', [1 => $recA], true));

    $this->get(route('public.quran.playlists.show', $playlist->slug))->assertOk()->assertSee('x');
    $this->post(route('public.quran.playlists.plays', $playlist->slug))->assertOk();
    $this->assertSame(1, $playlist->fresh()->plays_count);
}

public function test_owner_can_update_toggle_and_delete_and_others_cannot(): void
{
    $surahs = $this->surahs(1);
    [$a, $recA] = $this->person('a', $surahs);
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $playlist = app(\App\Domain\Quran\Services\PlaylistService::class)->create($owner, new \App\Domain\Quran\DTOs\PlaylistData('x', null, 'full', [1 => $recA], false));

    $this->actingAs($other)->putJson(route('public.quran.playlists.update', $playlist->slug), $this->payload([$a], $surahs))->assertNotFound();
    $this->actingAs($owner)->putJson(route('public.quran.playlists.update', $playlist->slug), $this->payload([$a], $surahs, ['name' => 'جديد']))->assertOk();
    $this->assertSame('جديد', $playlist->fresh()->name);

    $this->actingAs($owner)->patchJson(route('public.quran.playlists.visibility', $playlist->slug), ['is_public' => true])->assertOk();
    $this->assertTrue($playlist->fresh()->is_public);

    $this->actingAs($other)->deleteJson(route('public.quran.playlists.destroy', $playlist->slug))->assertNotFound();
    $this->actingAs($owner)->deleteJson(route('public.quran.playlists.destroy', $playlist->slug))->assertOk();
    $this->assertDatabaseMissing('quran_playlists', ['id' => $playlist->id]);
}
```

- [ ] **Step 2: Run** → FAIL (routes missing).

- [ ] **Step 3: Routes** — in `routes/web.php` immediately after the `public.quran.reciter` line:

```php
// Reciter playlists. Static segments first so `new`/`preview` never read as a slug.
Route::get('/quran/playlists', [QuranPlaylistController::class, 'index'])->name('public.quran.playlists.index');
Route::get('/quran/playlists/new', [QuranPlaylistController::class, 'create'])->middleware('auth')->name('public.quran.playlists.create');
Route::post('/quran/playlists/preview', [QuranPlaylistController::class, 'preview'])->middleware(['auth', 'throttle:30,1'])->name('public.quran.playlists.preview');
Route::post('/quran/playlists', [QuranPlaylistController::class, 'store'])->middleware('auth')->name('public.quran.playlists.store');
Route::get('/quran/playlists/{slug}', [QuranPlaylistController::class, 'show'])->where('slug', '[a-z0-9]{12}')->name('public.quran.playlists.show');
Route::get('/quran/playlists/{slug}/edit', [QuranPlaylistController::class, 'edit'])->where('slug', '[a-z0-9]{12}')->middleware('auth')->name('public.quran.playlists.edit');
Route::put('/quran/playlists/{slug}', [QuranPlaylistController::class, 'update'])->where('slug', '[a-z0-9]{12}')->middleware('auth')->name('public.quran.playlists.update');
Route::delete('/quran/playlists/{slug}', [QuranPlaylistController::class, 'destroy'])->where('slug', '[a-z0-9]{12}')->middleware('auth')->name('public.quran.playlists.destroy');
Route::patch('/quran/playlists/{slug}/visibility', [QuranPlaylistController::class, 'visibility'])->where('slug', '[a-z0-9]{12}')->middleware('auth')->name('public.quran.playlists.visibility');
Route::post('/quran/playlists/{slug}/plays', [QuranPlaylistController::class, 'plays'])->where('slug', '[a-z0-9]{12}')->middleware('throttle:10,1')->name('public.quran.playlists.plays');
```

- [ ] **Step 4: FormRequests**

`PreviewPlaylistRequest`:
```php
public function authorize(): bool { return $this->user() !== null; }
public function rules(): array
{
    return [
        'profile_ids' => ['required', 'array', 'min:1', 'max:30'],
        'profile_ids.*' => ['integer', Rule::exists('quran_reciter_profiles', 'id')->where('is_active', true)],
        'scope' => ['required', Rule::in(['full', 'custom'])],
        'surah_ids' => ['required_if:scope,custom', 'array', 'max:114'],
        'surah_ids.*' => ['integer', 'between:1,114'],
        'keep' => ['sometimes', 'array'],
        'keep.*' => ['integer'],
    ];
}
/** @return list<int> */
public function surahIds(): array
{
    return $this->validated('scope') === 'full' ? range(1, 114) : array_values(array_unique(array_map('intval', $this->validated('surah_ids', []))));
}
```

`StorePlaylistRequest` (also used by `update`):
```php
public function authorize(): bool { return $this->user() !== null; }
public function rules(): array
{
    return [
        'name' => ['required', 'string', 'min:2', 'max:80'],
        'description' => ['nullable', 'string', 'max:300'],
        'scope' => ['required', Rule::in(['full', 'custom'])],
        'items' => ['required', 'array', 'min:1', 'max:114'],
        'items.*' => ['nullable', 'integer'],
        'is_public' => ['sometimes', 'boolean'],
    ];
}
public function withValidator($validator): void
{
    $validator->after(function ($v): void {
        $items = $this->input('items', []);
        foreach (array_keys($items) as $surah) {
            if (! is_numeric($surah) || (int) $surah < 1 || (int) $surah > 114) { $v->errors()->add('items', __('quran.playlists.invalid_surah')); return; }
        }
        foreach (app(PlaylistService::class)->invalidPairs($items) as $bad) {
            $v->errors()->add('items.'.$bad['surah'], __('quran.playlists.no_audio_for_pair'));
        }
    });
}
public function data(): PlaylistData
{
    $items = [];
    foreach ($this->validated('items') as $surah => $rec) { $items[(int) $surah] = $rec !== null ? (int) $rec : null; }
    return new PlaylistData(trim($this->validated('name')), $this->validated('description') ?: null, $this->validated('scope'), $items, (bool) $this->validated('is_public', false));
}
```

- [ ] **Step 5: Controller**

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Domain\Quran\Models\Playlist;
use App\Domain\Quran\Models\ReciterProfile;
use App\Domain\Quran\Models\Surah;
use App\Domain\Quran\Repositories\PlaylistRepository;
use App\Domain\Quran\Services\PlaylistService;
use App\Domain\Shared\Enums\FavorableType;
use App\Domain\Shared\Services\FavoriteService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\PreviewPlaylistRequest;
use App\Http\Requests\Site\StorePlaylistRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use App\Domain\Quran\Services\QuranCacheKeys;

final class QuranPlaylistController extends Controller
{
    private const PER_USER_CAP = 20;

    public function __construct(private readonly PlaylistRepository $repo, private readonly PlaylistService $service, private readonly FavoriteService $favorites) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $sort = $request->query('sort') === 'plays' ? 'plays' : 'latest';
        $featured = Cache::remember(QuranCacheKeys::playlistScoped('featured:'.app()->getLocale()), 600, fn () => $this->repo->featured(1)->map(fn ($p) => $this->card($p))->all());

        return view('public.quran.playlists.index', [
            'playlists' => $this->repo->gallery($search ?: null, $sort),
            'featured' => $featured[0] ?? null,
            'search' => $search,
            'sort' => $sort,
            'totals' => Cache::remember(QuranCacheKeys::playlistScoped('totals'), 600, fn () => [
                'public' => Playlist::where('is_public', true)->count(),
                'plays' => (int) Playlist::where('is_public', true)->sum('plays_count'),
            ]),
        ]);
    }

    public function create(Request $request): View
    {
        return view('public.quran.playlists.wizard', $this->wizardProps($request, null));
    }

    public function edit(Request $request, string $slug): View
    {
        $playlist = $this->repo->findOwnedBySlug($slug, $request->user()) ?? abort(404);

        return view('public.quran.playlists.wizard', $this->wizardProps($request, $playlist));
    }

    public function preview(PreviewPlaylistRequest $request): JsonResponse
    {
        $result = $this->service->assign($request->validated('profile_ids'), $request->surahIds(), $request->validated('keep', []));

        return response()->json($result->toArray());
    }

    public function store(StorePlaylistRequest $request): JsonResponse|RedirectResponse
    {
        if ($this->repo->countForUser($request->user()) >= self::PER_USER_CAP) {
            return $this->fail($request, __('quran.playlists.cap_reached', ['max' => self::PER_USER_CAP]), 'name');
        }
        $playlist = $this->service->create($request->user(), $request->data());

        return $this->done($request, $playlist, __('quran.playlists.created'), 201);
    }

    public function update(StorePlaylistRequest $request, string $slug): JsonResponse|RedirectResponse
    {
        $playlist = $this->repo->findOwnedBySlug($slug, $request->user()) ?? abort(404);
        $this->service->update($playlist, $request->data());

        return $this->done($request, $playlist, __('quran.playlists.updated'));
    }

    public function show(Request $request, string $slug): View
    {
        $playlist = $this->repo->findBySlugVisibleTo($slug, $request->user()) ?? abort(404);
        $locale = app()->getLocale();
        $tracks = $playlist->items->map(fn ($i) => [
            'surah' => $i->surah_id,
            'surahName' => $i->surah?->getDisplayName($locale) ?? '',
            'verses' => $i->surah?->verses_count,
            'reciterName' => $i->reciter?->profile?->displayName($locale),
            'reciterImage' => $i->reciter?->profile?->profile_image_url,
            'recordingLabel' => $i->reciter?->recordingLabel($locale),
            'url' => $i->reciter_id ? $this->audioUrl($i->reciter_id, $i->surah_id) : null,
            'duration' => $i->reciter_id ? $this->duration($i->reciter_id, $i->surah_id) : null,
            'readerUrl' => route('public.quran.surah', $i->surah_id).($i->reciter_id ? '?reciter='.$i->reciter_id : ''),
        ])->values()->all();

        return view('public.quran.playlists.show', [
            'playlist' => $playlist,
            'card' => $this->card($playlist),
            'tracks' => $tracks,
            'isOwner' => $request->user()?->id === $playlist->user_id,
        ]);
    }

    public function visibility(Request $request, string $slug): JsonResponse|RedirectResponse
    {
        $playlist = $this->repo->findOwnedBySlug($slug, $request->user()) ?? abort(404);
        $on = $request->boolean('is_public');
        $this->service->setPublic($playlist, $on);

        return $request->expectsJson()
            ? response()->json(['success' => true, 'is_public' => $on])
            : back()->with('success', __($on ? 'quran.playlists.now_public' : 'quran.playlists.now_private'));
    }

    public function destroy(Request $request, string $slug): JsonResponse|RedirectResponse
    {
        $playlist = $this->repo->findOwnedBySlug($slug, $request->user()) ?? abort(404);
        $this->service->delete($playlist);

        return $request->expectsJson()
            ? response()->json(['success' => true, 'redirect' => route('public.profile').'#playlists'])
            : redirect()->route('public.profile')->with('success', __('quran.playlists.deleted'));
    }

    public function plays(Request $request, string $slug): JsonResponse
    {
        $playlist = $this->repo->findBySlugVisibleTo($slug, $request->user()) ?? abort(404);
        $this->service->recordPlay($playlist);

        return response()->json(['success' => true]);
    }

    // ---------------------------------------------------------------- helpers

    private function wizardProps(Request $request, ?Playlist $playlist): array
    {
        $locale = app()->getLocale();
        $profiles = ReciterProfile::visible()->with('translations:id,profile_id,locale,name')
            ->with(['recordings' => fn ($q) => $q->without('sources')->active()->recitationsOnly()->with('translations:id,reciter_id,locale,name')->orderBy('display_order')])
            ->orderBy('display_order')->orderBy('id')->get()
            ->map(fn (ReciterProfile $p) => [
                'id' => $p->id, 'name' => $p->displayName($locale), 'image' => $p->profile_image_url,
                'country' => $p->country, 'flag' => country_flag($p->country),
                'styles' => $p->recordings->pluck('style')->filter()->unique()->map(fn ($s) => __('quran.recordings.'.$s))->values()->all(),
                'recordings' => $p->recordings->map(fn ($r) => ['id' => $r->id, 'label' => $r->recordingLabel($locale)])->values()->all(),
            ])->values()->all();
        $favoriteIds = $this->favorites->favoritedContentIds($request->user(), FavorableType::RECITER);
        $surahs = Surah::query()->orderBy('id')->get(['id', 'name_ar', 'name_en', 'transliteration', 'verses_count'])
            ->map(fn (Surah $s) => ['id' => $s->id, 'name' => $s->getDisplayName($locale), 'verses' => $s->verses_count])->all();

        $initial = null;
        if ($playlist) {
            $initial = [
                'slug' => $playlist->slug, 'name' => $playlist->name, 'description' => $playlist->description,
                'scope' => $playlist->scope, 'isPublic' => $playlist->is_public,
                'profileIds' => $playlist->reciterProfiles()->pluck('id')->all(),
                'surahIds' => $playlist->items->pluck('surah_id')->all(),
                'items' => $playlist->items->pluck('reciter_id', 'surah_id')->all(),
            ];
        }

        return compact('profiles', 'favoriteIds', 'surahs', 'initial') + ['count' => $this->repo->countForUser($request->user()), 'cap' => self::PER_USER_CAP];
    }

    /** Presentation shape shared by gallery / profile / home cards. */
    private function card(Playlist $p): array
    {
        $locale = app()->getLocale();

        return [
            'slug' => $p->slug, 'name' => $p->name, 'description' => $p->description,
            'url' => route('public.quran.playlists.show', $p->slug),
            'owner' => ['name' => $p->owner?->name ?? '', 'avatar' => $p->owner?->avatarUrl()],
            'items' => $p->items_count, 'plays' => $p->plays_count, 'isPublic' => $p->is_public, 'isFeatured' => $p->is_featured,
            'reciters' => $p->reciterProfiles()->map(fn ($r) => ['name' => $r->displayName($locale), 'image' => $r->profile_image_url])->all(),
        ];
    }

    private function audioUrl(int $recordingId, int $surah): ?string
    {
        return app(\App\Domain\Quran\Repositories\RecitationRepository::class)->getSurahAudioSources($surah)[$recordingId]['url'] ?? null;
    }

    private function duration(int $recordingId, int $surah): ?int
    {
        return \App\Domain\Quran\Models\Recitation::where('reciter_id', $recordingId)->where('surah_id', $surah)->value('duration_seconds');
    }

    private function done(Request $request, Playlist $playlist, string $message, int $status = 200): JsonResponse|RedirectResponse
    {
        $url = route('public.quran.playlists.show', $playlist->slug);

        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => $message, 'redirect' => $url], $status)
            : redirect($url)->with('success', $message);
    }

    private function fail(Request $request, string $message, string $field): JsonResponse|RedirectResponse
    {
        return $request->expectsJson()
            ? response()->json(['success' => false, 'message' => $message, 'errors' => [$field => [$message]]], 422)
            : back()->withErrors([$field => $message])->withInput();
    }
}
```
`show()` computes `duration` per row with one query each — 114 small PK-index lookups worst case; replace with one `whereIn` grouped query if it shows in the debugbar (`ponytail:` comment it).

Also expose the `card()` shape for the profile tab: make it a public static on a tiny presenter or keep it private and have `ProfileController` call the repository + a shared `PlaylistCard::from($p)` helper — pick the helper (`app/Domain/Quran/Services/PlaylistCard.php`, one static `from(Playlist, string $locale): array`) and use it from both controllers.

- [ ] **Step 6: Run** `php artisan test --filter=QuranPlaylistTest` → view-related tests fail on missing view. Create stub views `resources/views/public/quran/playlists/{index,show,wizard}.blade.php` that `@extends('layouts.public')` and print `{{ $playlist->name ?? '' }}` so the HTTP tests pass; real views come in Task 6. Run again → PASS. `pint --dirty`.
- [ ] **Step 7: Commit** `feat(quran): playlist site routes + controller`

---

### Task 6: Site views — gallery, wizard, playlist page

**Files:**
- Replace stubs: `resources/views/public/quran/playlists/index.blade.php`, `wizard.blade.php`, `show.blade.php`
- Create partial: `resources/views/components/site/playlist-card.blade.php`
- Modify: `resources/lang/ar/quran.php`, `resources/lang/en/quran.php` — new `'playlists' => [...]` block
- Modify: `resources/views/components/audio-player.blade.php` — `configure()` gains `force` (Task 7 uses it; add here so the file is touched once)

**Interfaces (consumes):** controller props from Task 5. Player: `$store.audioPlayer.setPlaylist(tracks, i)`, `play({url,title,subtitle})`, `track`, `playing`.

- [ ] **Step 1: Lang keys** (Arabic complete; English mirror):

```php
'playlists' => [
    'title' => 'قوائم الاستماع', 'subtitle' => 'قوائم قرآنية من اختيار المستمعين، بقرّاء متعددين لكل سورة',
    'create' => 'أنشئ قائمتك', 'new' => 'قائمة جديدة', 'my' => 'قوائمي', 'edit' => 'تعديل', 'delete' => 'حذف',
    'delete_confirm' => 'سيتم حذف القائمة نهائيًا. متابعة؟', 'play_all' => 'تشغيل الكل', 'share' => 'مشاركة',
    'public' => 'عامة', 'private' => 'خاصة', 'make_public' => 'نشر للجميع', 'make_private' => 'إخفاء',
    'featured' => 'مميّزة', 'plays' => 'استماع', 'surahs_count' => ':count سورة', 'reciters_count' => ':count قرّاء',
    'by' => 'بواسطة', 'search' => 'ابحث باسم القائمة', 'sort_latest' => 'الأحدث', 'sort_plays' => 'الأكثر استماعًا',
    'empty' => 'لا توجد قوائم عامة بعد', 'empty_search' => 'لا نتائج لهذا البحث', 'empty_mine' => 'لم تنشئ أي قائمة بعد',
    'totals_public' => 'قائمة عامة', 'totals_plays' => 'مرة استماع',
    'step_reciters' => 'القرّاء', 'step_scope' => 'النطاق', 'step_preview' => 'المعاينة',
    'name' => 'اسم القائمة', 'name_help' => 'من حرفين إلى ٨٠ حرفًا', 'name_default' => 'قائمتي', 'description' => 'وصف (اختياري)',
    'favorites_section' => 'المفضّلون', 'all_reciters' => 'كل القرّاء', 'search_reciters' => 'ابحث عن قارئ',
    'selected' => 'مختار', 'next' => 'التالي', 'back' => 'رجوع', 'save' => 'حفظ', 'saving' => 'جارٍ الحفظ…',
    'scope_full' => 'المصحف كاملًا', 'scope_full_help' => '١١٤ سورة', 'scope_custom' => 'سور محددة', 'scope_custom_help' => 'اختر السور التي تريدها',
    'search_surahs' => 'ابحث عن سورة', 'quick_juz_amma' => 'جزء عمّ', 'quick_mufassal' => 'المفصّل', 'quick_all' => 'تحديد الكل', 'quick_clear' => 'مسح',
    'reshuffle' => 'إعادة التوزيع', 'coverage' => ':covered من :total سورة مغطاة', 'uncovered' => 'لا توجد تلاوة لهذه السورة عند القرّاء المختارين — اختر قارئًا آخر أو أزلها',
    'remove_surah' => 'إزالة السورة', 'recording' => 'التسجيل', 'preview_play' => 'استماع', 'needs_reciter' => 'بحاجة إلى قارئ',
    'public_toggle_help' => 'يمكن لأي شخص يملك الرابط الاستماع، وتظهر القائمة في صفحة القوائم',
    'created' => 'تم إنشاء القائمة', 'updated' => 'تم حفظ التغييرات', 'deleted' => 'تم حذف القائمة',
    'now_public' => 'أصبحت القائمة عامة', 'now_private' => 'أصبحت القائمة خاصة',
    'cap_reached' => 'وصلت إلى الحد الأقصى (:max قائمة)', 'no_audio_for_pair' => 'لا توجد تلاوة لهذه السورة بهذا التسجيل', 'invalid_surah' => 'رقم سورة غير صالح',
    'unsaved' => 'لديك تغييرات غير محفوظة', 'error' => 'حدث خطأ، حاول مجددًا', 'link_copied' => 'تم نسخ الرابط',
    'home_title' => 'قوائم الاستماع', 'home_cta' => 'استكشف القوائم', 'view_all' => 'عرض الكل',
],
```

- [ ] **Step 2: `x-site.playlist-card`** — props `card` (array from `PlaylistCard::from`), optional `index` for stagger. Markup: `<a href="{{ $card['url'] }}" class="group block rounded-2xl border border-zinc-200/70 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-5 transition-transform duration-200 hover:-translate-y-0.5 active:scale-[0.98] motion-safe:animate-[fadeUp_.35s_ease-out_both]" style="--i: {{ $index }}; animation-delay: calc(var(--i) * 40ms)">` containing: reciter portrait stack (`flex -space-x-2 rtl:space-x-reverse`, ≤4 `<img class="w-9 h-9 rounded-full ring-2 ring-white object-cover">`, `+n` chip), name (`font-bold text-lg truncate`), meta line (`__('quran.playlists.surahs_count')` · `reciters_count`), footer row: owner avatar+name at start, plays with `data-lucide="headphones"` at end, and a 44 px round play button (`data-lucide="play"`, `-scale-x-100` when RTL). Define `@keyframes fadeUp {from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}` once in the gallery `@push('head')`; `@media (prefers-reduced-motion: reduce){*{animation:none!important}}` scoped to `.pl-grid`.

- [ ] **Step 3: Gallery `index.blade.php`** — sections in order: (1) header `grid md:grid-cols-[1fr_auto] items-end gap-6` — h1 `text-3xl sm:text-4xl font-black tracking-tight text-start`, subtitle `max-w-[60ch] text-zinc-600`, CTA `bg-gradient-to-r from-[#0056b3] to-[#218838] text-white rounded-xl px-6 h-12` (guest: `@click.prevent="$dispatch('open-login')"` if the login modal listens to that event — check `components/site/login-modal.blade.php`; otherwise `href="{{ route('public.login') }}"`). (2) featured band `grid md:grid-cols-[2fr_1fr] gap-6` when `$featured`: start = large card variant (portrait collage 2×2, name `text-2xl`, play-all link), end = two bare figures `text-4xl font-black tabular-nums` + labels, separated by `md:border-s md:ps-6`. (3) form `GET` with `q` input (label `sr-only`, visible placeholder is not the label — add `<label class="text-sm font-semibold">`), sort pills as `<a>` links with `aria-current`. (4) grid `pl-grid grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5` of `<x-site.playlist-card>`; `{{ $playlists->links() }}` (existing `x-site.pagination` if that is how other pages paginate — grep `->links(` in `resources/views/public/quran`). (5) `<x-site.empty-state>` for none / no search hits.

- [ ] **Step 4: Wizard `wizard.blade.php`** — Alpine component inline in `@push('scripts')`:

```js
function playlistWizard(props) {
  return {
    step: 1, profiles: props.profiles, surahs: props.surahs, favoriteIds: props.favoriteIds,
    editing: !!props.initial, slug: props.initial?.slug ?? null,
    name: props.initial?.name ?? props.defaultName, description: props.initial?.description ?? '',
    isPublic: props.initial?.isPublic ?? false,
    selected: new Set(props.initial?.profileIds ?? props.favoriteIds),
    scope: props.initial?.scope ?? 'full',
    surahIds: new Set(props.initial?.surahIds ?? []),
    items: props.initial?.items ?? {},      // surah -> recording|null
    pinned: {},                              // surah -> recording (user overrides survive reshuffle)
    alternatives: {}, uncovered: [], loading: false, saving: false, errors: {}, dirty: false,
    reciterSearch: '', surahSearch: '',
    profileById(id) { return this.profiles.find(p => p.id === id); },
    get favorites() { return this.profiles.filter(p => this.favoriteIds.includes(p.id) && this.matches(p)); },
    get others()    { return this.profiles.filter(p => !this.favoriteIds.includes(p.id) && this.matches(p)); },
    matches(p) { const q = this.reciterSearch.trim(); return !q || p.name.includes(q); },
    toggleProfile(id) { this.selected.has(id) ? this.selected.delete(id) : this.selected.add(id); this.selected = new Set(this.selected); this.dirty = true; },
    toggleSurah(id) { this.surahIds.has(id) ? this.surahIds.delete(id) : this.surahIds.add(id); this.surahIds = new Set(this.surahIds); this.dirty = true; },
    quick(kind) { const ids = kind === 'amma' ? range(78, 114) : kind === 'mufassal' ? range(50, 114) : kind === 'all' ? range(1, 114) : []; this.surahIds = new Set(ids); this.dirty = true; },
    get scopeIds() { return this.scope === 'full' ? range(1, 114) : [...this.surahIds].sort((a, b) => a - b); },
    get canNext() { return this.step === 1 ? this.selected.size > 0 && this.name.trim().length >= 2 : this.step === 2 ? this.scopeIds.length > 0 : true; },
    async next() { if (this.step < 3) { this.step++; if (this.step === 3) await this.preview(false); } },
    back() { if (this.step > 1) this.step--; },
    async preview(reshuffle) {
      this.loading = true; this.errors = {};
      const keep = reshuffle ? this.pinned : { ...this.items, ...this.pinned };
      try {
        const res = await fetch(props.previewUrl, { method: 'POST', headers: hdr(), body: JSON.stringify({ profile_ids: [...this.selected], scope: this.scope, surah_ids: this.scopeIds, keep }) });
        if (!res.ok) throw new Error();
        const data = await res.json();
        this.items = {}; this.scopeIds.forEach(s => { this.items[s] = data.items[s] ?? null; });
        this.alternatives = data.alternatives; this.uncovered = data.uncovered;
      } catch { this.$store.toast?.push(props.t.error, 'error'); } finally { this.loading = false; }
    },
    optionsFor(surah) { const alt = this.alternatives[surah] ?? {}; return Object.entries(alt).map(([pid, recs]) => ({ profile: this.profileById(+pid), recordings: recs })); },
    recordingsFor(surah, recording) { const alt = this.alternatives[surah] ?? {}; return Object.values(alt).find(recs => recs.includes(recording)) ?? []; },
    setRecording(surah, recording) { this.items[surah] = recording; this.pinned[surah] = recording; this.dirty = true; },
    removeSurah(surah) { delete this.items[surah]; this.surahIds.delete(surah); this.surahIds = new Set(this.surahIds); if (this.scope === 'full') { this.scope = 'custom'; this.surahIds = new Set(Object.keys(this.items).map(Number)); } },
    get covered() { return Object.values(this.items).filter(Boolean).length; },
    previewPlay(surah) { const rec = this.items[surah]; if (!rec) return; this.$store.audioPlayer.play({ url: props.audioUrlTemplate.replace('__S__', surah).replace('__R__', rec), title: this.surahs.find(s => s.id === surah)?.name ?? '', subtitle: this.reciterName(rec) }); },
    reciterName(rec) { const p = this.profiles.find(p => p.recordings.some(r => r.id === rec)); return p?.name ?? ''; },
    async save() {
      if (this.saving) return; this.saving = true; this.errors = {};
      const body = { name: this.name.trim(), description: this.description || null, scope: this.scope, items: this.items, is_public: this.isPublic };
      try {
        const res = await fetch(this.editing ? props.updateUrl : props.storeUrl, { method: this.editing ? 'PUT' : 'POST', headers: hdr(), body: JSON.stringify(body) });
        const data = await res.json().catch(() => ({}));
        if (res.status === 422) { this.errors = data.errors ?? {}; this.$store.toast?.push(Object.values(this.errors)[0]?.[0] ?? props.t.error, 'error'); return; }
        if (!res.ok) throw new Error();
        this.dirty = false; this.$store.toast?.push(data.message, 'success'); window.location.href = data.redirect;
      } catch { this.$store.toast?.push(props.t.error, 'error'); } finally { this.saving = false; }
    },
    init() { window.addEventListener('beforeunload', e => { if (this.dirty && !this.saving) { e.preventDefault(); e.returnValue = ''; } }); if (this.editing) { this.step = 3; this.preview(false); } },
  };
  function range(a, b) { return Array.from({ length: b - a + 1 }, (_, i) => a + i); }
  function hdr() { return { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '' }; }
}
```
`audioUrlTemplate`: the preview play needs a URL per `(surah, recording)`. Add a tiny JSON route `GET /quran/playlists/audio/{surah}/{recording}` → 302 to the recitation `audio_url` (through `RecitationRepository::getSurahAudioSources`), named `public.quran.playlists.audio`, registered with the static routes in Task 5; template = `route(..., ['surah' => '__S__', 'recording' => '__R__'])`. (Preview play then just sets `url` to that route; the player follows the redirect.)

Blade structure: `@extends('layouts.public')`, wrapper `x-data="playlistWizard(@js([...props...]))"`, progress rail (`<ol class="grid grid-cols-3 gap-2">` with `aria-current="step"`), three `<section x-show="step === n" x-cloak>` blocks per spec §4.2, sticky bottom bar `sticky bottom-0 inset-x-0 border-t bg-white/90 dark:bg-zinc-900/90 backdrop-blur` with counter + back/next/save (`:disabled`, spinner via `x-show="saving"`). Reciter card: `<button type="button" role="checkbox" :aria-checked="selected.has(p.id)" class="min-h-[44px] …">`. Surah chips: `<button type="button" :aria-pressed>`. Preview rows: `<ul class="divide-y">`, each row `grid grid-cols-[auto_1fr_auto] sm:grid-cols-[auto_1fr_minmax(200px,auto)_auto] gap-3 items-center py-3`, reciter `<select :value="items[s.id]" @change="setRecording(s.id, +$event.target.value)">` grouped by person (`<optgroup :label="opt.profile.name">` with one `<option>` per recording — this yields the person select and recording sub-select in one native control, which is both the 44 px native path and RTL-safe). Uncovered rows: `bg-amber-50 dark:bg-amber-950/30` + message + remove button. Errors: `x-text="errors['items.'+s.id]?.[0]"` under the row; `errors.name` under the name input.

- [ ] **Step 5: Playlist page `show.blade.php`** — per spec §4.3. Alpine: `x-data="{ tracks: @js($tracks), counted: false, async playAll(i = 0) { const list = this.tracks.filter(t => t.url).map(t => ({ url: t.url, title: t.surahName, subtitle: t.reciterName })); const idx = Math.max(0, list.findIndex(t => t.url === this.tracks[i]?.url)); this.$store.audioPlayer.setPlaylist(list, idx); if (!this.counted) { this.counted = true; fetch(@js(route('public.quran.playlists.plays', $playlist->slug)), { method: 'POST', headers: { 'X-CSRF-TOKEN': …, Accept: 'application/json' } }).catch(() => {}); } }, isActive(t) { return t.url && this.$store.audioPlayer.track?.url === t.url; } }"`. Owner controls: visibility switch → `fetch(PATCH visibility)`; delete → `<form method="POST" @submit="if(!confirm(...)) $event.preventDefault()">` with `@method('DELETE')` (plain form → redirect branch). Share: `<x-site.share-button :url="…" :title="$playlist->name" />` only when `is_public`. Reuse the `.eq-bars` CSS from `reciter.blade.php` for the active row.

- [ ] **Step 6: Player `configure()` change** (`audio-player.blade.php` ~line 304): signature `configure({ reciters, reciterId, surah, urlBase, force = false })`; replace the reciterId line with:
```js
this.reciterId = (force && valid(reciterId)) ? reciterId
    : (valid(stored) ? stored : (valid(reciterId) ? reciterId : (this.reciters[0]?.id ?? null)));
```
Update the comment above it: "`force` (playlist hand-off via ?reciter=) wins over the stored choice without overwriting it".

- [ ] **Step 7: Verify** — `php artisan test --filter=QuranPlaylistTest` still PASS; `npm run build`; open `/quran/playlists`, `/quran/playlists/new` logged in, walk the 3 steps, save, open the page, play all. Fix what breaks.
- [ ] **Step 8: Commit** `feat(quran): playlist gallery, wizard and page`

---

### Task 7: Reader hand-off, reciter heart, profile tab, Quran home section

**Files:**
- Modify: `app/Http/Controllers/Site/QuranController.php` (`surah()` ~line 537 props; `index()` ~line 293; `reciter()` ~line 731)
- Modify: `resources/views/public/quran/surah.blade.php` (~line 388 & 465), `reciter.blade.php` (hero), `index.blade.php` (new section)
- Modify: `app/Http/Controllers/Site/ProfileController.php@show`, `resources/views/public/profile/index.blade.php`
- Test: `tests/Feature/Web/QuranPlaylistTest.php` (+2), `ProfileFavoritesTest.php` (+1)

- [ ] **Step 1: Failing tests**

```php
public function test_reader_accepts_reciter_query_and_ignores_invalid(): void
{
    $surahs = $this->surahs(1);
    [$a, $recA] = $this->person('a', $surahs);

    $this->get(route('public.quran.surah', 1).'?reciter='.$recA)->assertOk()->assertSee('forcedReciterId: '.$recA, false);
    $this->get(route('public.quran.surah', 1).'?reciter=999999')->assertOk()->assertSee('forcedReciterId: null', false);
}

public function test_profile_lists_own_playlists(): void
{
    $surahs = $this->surahs(1);
    [$a, $recA] = $this->person('a', $surahs);
    $owner = User::factory()->create();
    app(\App\Domain\Quran\Services\PlaylistService::class)->create($owner, new \App\Domain\Quran\DTOs\PlaylistData('قائمة الفجر', null, 'full', [1 => $recA], false));

    $this->actingAs($owner)->get(route('public.profile'))->assertOk()->assertSee('قائمة الفجر');
}
```
(`surah()` may be cached per surah — the forced id is computed per request, outside the cached payload, so this is safe.)

- [ ] **Step 2: Implement**
  - `QuranController@surah`: `$forced = request()->integer('reciter') ?: null; $forcedReciterId = $forced && in_array($forced, array_column($reciters, 'id'), true) ? $forced : null;` → add `'forcedReciterId' => $forcedReciterId` to the view array. In `surah.blade.php` line ~415: `forcedReciterId: {{ $forcedReciterId ?? 'null' }},` and in the `configure({...})` call: `reciterId: this.forcedReciterId ?? this.selectedReciterId, force: this.forcedReciterId !== null,`.
  - `QuranController@reciter`: add `'id' => $profile->id` and `'isSaved' => auth()->check() && app(FavoriteService::class)->isFavorited(auth()->user(), FavorableType::RECITER, $profile->id)` to `$reciter`. In `reciter.blade.php` hero, next to the name: a 44 px heart button using the media-page pattern (`toggleSave()` with `type: 'reciter'`), `aria-pressed`, filled heart when saved; guests go to login.
  - `QuranController@index`: `$playlists = Cache::remember(QuranCacheKeys::playlistScoped('home:'.$locale), 600, fn () => ['featured' => $repo->featured(1)->map(PlaylistCard::from…)->first(), 'latest' => $repo->latestPublic(3)->map(…)->all()]);` pass to view; in `index.blade.php` add the section per spec §4.5 (hidden when both empty and guest).
  - `ProfileController@show`: `'playlists' => $this->safe(fn () => app(PlaylistRepository::class)->forUser($user)->map(fn ($p) => PlaylistCard::from($p, $locale))->all(), [])`. Profile view: add `['id' => 'playlists', 'icon' => 'list-music', 'label' => __('quran.playlists.my')]` to `$tabs`, a `<section x-show="activeTab === 'playlists'">` with a grid of `<x-site.playlist-card>` + dashed "new" tile (`border-2 border-dashed`, link to create) + empty state; `init()` already reads `#fragment` so `#playlists` deep-links.

- [ ] **Step 3: Run** both test classes → PASS. Build assets. Manual check of the three pages.
- [ ] **Step 4: Commit** `feat(quran): playlist entry points (reader hand-off, reciter heart, profile tab, home)`

---

### Task 8: Admin CRUD

**Files:**
- Create: `app/Http/Controllers/Admin/Quran/PlaylistController.php`
- Create: `app/Http/Requests/Admin/Quran/UpdatePlaylistRequest.php`
- Create: `resources/views/admin/playlists/index.blade.php`, `edit.blade.php`
- Modify: `routes/admin/quran.php`, `resources/views/components/admin/sidebar.blade.php` (submenu + `$moduleMap`), `resources/lang/ar/quran.php` + `en` (`playlists_admin` block), `resources/lang/ar/admin.php` (`'playlists' => 'قوائم الاستماع'`)
- Test: `tests/Feature/Admin/PlaylistAdminTest.php`

- [ ] **Step 1: Failing tests**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Quran\DTOs\PlaylistData;
use App\Domain\Quran\Models\Playlist;
use App\Domain\Quran\Services\PlaylistService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PlaylistAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void { parent::setUp(); $this->withoutVite(); }

    private function playlist(bool $public = true): Playlist
    {
        return app(PlaylistService::class)->create(User::factory()->create(), new PlaylistData('قائمة', null, 'full', [], $public));
    }

    public function test_guest_is_redirected(): void
    {
        $this->get(route('admin.playlists.index'))->assertRedirect();
    }

    public function test_admin_sees_index(): void
    {
        $p = $this->playlist();
        $this->actingAs($this->createAdminUser(), 'admin')->get(route('admin.playlists.index'))->assertOk()->assertSee('قائمة');
    }

    public function test_admin_can_toggle_public_and_featured(): void
    {
        $p = $this->playlist();
        $admin = $this->createAdminUser();
        $this->actingAs($admin, 'admin')->post(route('admin.playlists.toggle', [$p->id, 'public']))->assertRedirect();
        $this->assertFalse($p->fresh()->is_public);
        $this->actingAs($admin, 'admin')->post(route('admin.playlists.toggle', [$p->id, 'featured']))->assertRedirect();
        $this->assertTrue($p->fresh()->is_featured);
    }

    public function test_admin_can_rename_and_delete(): void
    {
        $p = $this->playlist();
        $admin = $this->createAdminUser();
        $this->actingAs($admin, 'admin')->put(route('admin.playlists.update', $p->id), ['name' => 'جديد', 'description' => '', 'is_public' => 1, 'is_featured' => 0, 'items' => []])->assertRedirect();
        $this->assertSame('جديد', $p->fresh()->name);
        $this->actingAs($admin, 'admin')->delete(route('admin.playlists.destroy', $p->id))->assertRedirect(route('admin.playlists.index'));
        $this->assertDatabaseMissing('quran_playlists', ['id' => $p->id]);
    }

    public function test_bulk_unpublish(): void
    {
        $a = $this->playlist(); $b = $this->playlist();
        $this->actingAs($this->createAdminUser(), 'admin')->post(route('admin.playlists.bulk-unpublish'), ['ids' => [$a->id, $b->id]])->assertRedirect();
        $this->assertSame(0, Playlist::where('is_public', true)->count());
    }
}
```
(Check how other admin tests authenticate — `RecitationAdminTest` uses `actingAs($this->createAdminUser())` without a guard; mirror exactly what it does.)

- [ ] **Step 2: Routes** (`routes/admin/quran.php`, after reciter-profiles):
```php
Route::prefix('playlists')->name('admin.playlists.')->controller(PlaylistController::class)->group(function (): void {
    Route::get('/', 'index')->name('index');
    Route::post('/bulk-unpublish', 'bulkUnpublish')->name('bulk-unpublish');
    Route::get('/{playlist}/edit', 'edit')->whereNumber('playlist')->name('edit');
    Route::put('/{playlist}', 'update')->whereNumber('playlist')->name('update');
    Route::delete('/{playlist}', 'destroy')->whereNumber('playlist')->name('destroy');
    Route::post('/{playlist}/toggle/{field}', 'toggle')->whereNumber('playlist')->whereIn('field', ['public', 'featured'])->name('toggle');
});
```
Note `{playlist}` is the numeric id here (admin), so resolve with `Playlist::findOrFail($id)` — do not rely on route-model binding since the model's route key is `slug`.

- [ ] **Step 3: Controller + request**

```php
final class PlaylistController extends Controller
{
    public function __construct(private readonly PlaylistRepository $repo, private readonly PlaylistService $service) {}

    public function index(Request $request): View
    {
        $filters = array_filter($request->only(['search', 'public', 'featured']), 'filled');
        $stats = DB::table('quran_playlists')->selectRaw('COUNT(*) total, COALESCE(SUM(is_public),0) public_count, COALESCE(SUM(is_featured),0) featured, COALESCE(SUM(plays_count),0) plays')->first();

        return view('admin.playlists.index', ['playlists' => $this->repo->adminList($filters), 'filters' => $filters, 'stats' => $stats]);
    }

    public function edit(int $playlist): View
    {
        $model = Playlist::with(['owner:id,name,email', 'items.surah:id,name_ar,name_en,transliteration', 'items.reciter:id,profile_id,style,riwayah', 'items.reciter.profile:id', 'items.reciter.profile.translations:id,profile_id,locale,name'])->findOrFail($playlist);
        $coverage = $this->service->coverage($model->items->pluck('surah_id')->all());

        return view('admin.playlists.edit', ['playlist' => $model, 'coverage' => $coverage]);
    }

    public function update(UpdatePlaylistRequest $request, int $playlist): RedirectResponse
    {
        $model = Playlist::findOrFail($playlist);
        $items = $model->items->pluck('reciter_id', 'surah_id')->all();
        foreach ($request->validated('items', []) as $surah => $rec) { if (array_key_exists((int) $surah, $items)) { $items[(int) $surah] = $rec !== null && $rec !== '' ? (int) $rec : null; } }
        $this->service->update($model, new PlaylistData($request->validated('name'), $request->validated('description') ?: null, $model->scope, $items, $request->boolean('is_public')));
        $this->service->setFeatured($model, $request->boolean('is_featured'));
        app(AdminLoggerService::class)->log('playlist.updated', $model);  // match the signature used in ReciterController

        return redirect()->route('admin.playlists.edit', $model->id)->with('success', __('quran.playlists_admin.updated'));
    }

    public function toggle(int $playlist, string $field): RedirectResponse
    {
        $model = Playlist::findOrFail($playlist);
        $field === 'public' ? $this->service->setPublic($model, ! $model->is_public) : $this->service->setFeatured($model, ! $model->is_featured);

        return back()->with('success', __('quran.playlists_admin.toggled'));
    }

    public function bulkUnpublish(Request $request): RedirectResponse
    {
        $ids = array_map('intval', (array) $request->input('ids', []));
        Playlist::whereIn('id', $ids)->update(['is_public' => false, 'is_featured' => false]);
        QuranCacheKeys::flushPlaylists();

        return back()->with('success', __('quran.playlists_admin.unpublished', ['count' => count($ids)]));
    }

    public function destroy(int $playlist): RedirectResponse
    {
        $this->service->delete(Playlist::findOrFail($playlist));

        return redirect()->route('admin.playlists.index')->with('success', __('quran.playlists_admin.deleted'));
    }
}
```
`UpdatePlaylistRequest` rules: `name` required 2–80, `description` nullable ≤ 300, `is_public`/`is_featured` boolean, `items` array, `items.*` nullable integer exists `quran_reciters,id`. `authorize()` → `$this->user('admin')?->can('edit quran') ?? false` (verify how the reciter requests authorize and mirror it). Wire `AdminLoggerService` exactly as `ReciterController` does — read it first; if it's not used there, drop the call.

- [ ] **Step 4: Views** — `index.blade.php`: `@extends('layouts.admin')`, page header + 4 `<x-admin.stats-card>`, filter form (search / public select / featured select), a table (hand-rolled like `admin/reciters/index.blade.php`) with a bulk checkbox column, columns per spec §4.6, inline toggle forms (`POST toggle/{field}`, button styled as a switch with `aria-pressed`), edit link, delete form with confirm, pagination. `edit.blade.php`: form `PUT`, fields name / description / public / featured, then `<table>` of items: surah, current reciter, `<select name="items[{{ surah }}]">` with `<option value="">—</option>` + coverage options (selected = current). Save button; back link; owner info block. Sidebar: add `<a href="{{ route('admin.playlists.index') }}">` in the Quran submenu with `data-lucide="list-music"`, add `'playlists'` to the `includes(activeMenu)` array and `$moduleMap`.

- [ ] **Step 5: Run** `php artisan test --filter=PlaylistAdminTest` → PASS. Open `/su/portal/v1/admin/playlists` and check the table, toggles, edit.
- [ ] **Step 6: Commit** `feat(admin): quran playlists moderation`

---

### Task 9: Full test run, style, docs, Chrome walkthrough

- [ ] `./vendor/bin/pint --dirty` then `php artisan test` (whole suite; note the per-worktree test DB memory — don't run two suites concurrently).
- [ ] `npm run build`; `php artisan optimize:clear`.
- [ ] Update `CLAUDE.md` Quran module bullet: "Reciter playlists: `quran_playlists` / `quran_playlist_items`, `PlaylistService::assign()`, routes `public.quran.playlists.*`, admin `admin.playlists.*`; `FavorableType::RECITER`."
- [ ] Chrome walkthrough (record a GIF `quran_playlists_walkthrough.gif`): login → `/quran/playlists/new` → pick 2–3 reciters (verify favourites pinned) → custom scope with جزء عمّ → preview → reshuffle → change one row → save → playlist page → play all (player shows track, next advances) → copy share link → open in a fresh incognito tab as guest (200 when public; toggle private → 404) → click a surah row → reader loads with `?reciter=` and the assigned reciter selected → profile `#playlists` tab → admin `/su/portal/v1/admin/playlists` → unpublish → guest link 404.
- [ ] Commit `chore(quran): playlists docs + polish`, then report.
