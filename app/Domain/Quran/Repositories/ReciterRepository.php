<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Quran\Models\Recitation;
use App\Domain\Quran\Models\Reciter;
use App\Domain\Quran\Services\QuranCacheKeys;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Reciter Repository
 * Handles data access for Quran reciters with caching
 */
class ReciterRepository implements ReciterRepositoryInterface
{
    /**
     * Cache TTL in minutes
     */
    private const CACHE_TTL = 30;

    /**
     * Get paginated reciters
     */
    public function paginate(int $perPage = 15, bool $activeOnly = true, ?string $kind = null): LengthAwarePaginator
    {
        // Eager-load translations: DTO name/description maps come from
        // reciter_translations and would otherwise lazy-load per row.
        $query = Reciter::query()
            ->with(['translations:id,reciter_id,locale,name,description'])
            ->ordered();

        if ($activeOnly) {
            $query->active();
        }

        if ($kind !== null) {
            $query->where('kind', $kind);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get reciter by ID
     */
    public function getById(int $id): ?Reciter
    {
        return Cache::remember(
            $this->versionedKey("reciter:$id"),
            now()->addMinutes(self::CACHE_TTL),
            fn () => Reciter::find($id)
        );
    }

    /**
     * Get reciter by slug
     */
    public function getBySlug(string $slug): ?Reciter
    {
        return Cache::remember(
            $this->versionedKey("reciter:slug:$slug"),
            now()->addMinutes(self::CACHE_TTL),
            fn () => Reciter::where('slug', $slug)->first()
        );
    }

    /**
     * Get reciters by country
     */
    public function getByCountry(string $country, bool $activeOnly = true): Collection
    {
        $cacheKey = $this->versionedKey("reciters:country:$country:".($activeOnly ? 'active' : 'all'));

        return Cache::remember(
            $cacheKey,
            now()->addMinutes(self::CACHE_TTL),
            function () use ($country, $activeOnly) {
                $query = Reciter::where('country', $country)->ordered();

                if ($activeOnly) {
                    $query->active();
                }

                return $query->get();
            }
        );
    }

    /**
     * Get reciters by recitation style
     */
    public function getByStyle(string $style, bool $activeOnly = true): Collection
    {
        $cacheKey = $this->versionedKey("reciters:style:$style:".($activeOnly ? 'active' : 'all'));

        return Cache::remember(
            $cacheKey,
            now()->addMinutes(self::CACHE_TTL),
            function () use ($style, $activeOnly) {
                $query = Reciter::where('style', $style)->ordered();

                if ($activeOnly) {
                    $query->active();
                }

                return $query->get();
            }
        );
    }

    /**
     * Get featured reciters
     */
    public function getFeatured(bool $activeOnly = true): Collection
    {
        $cacheKey = $this->versionedKey('reciters:featured:'.($activeOnly ? 'active' : 'all'));

        return Cache::remember(
            $cacheKey,
            now()->addMinutes(self::CACHE_TTL),
            function () use ($activeOnly) {
                $query = Reciter::featured()->ordered();

                if ($activeOnly) {
                    $query->active();
                }

                return $query->get();
            }
        );
    }

    /**
     * Get active reciters
     */
    public function getActive(): Collection
    {
        return Cache::remember(
            $this->versionedKey('reciters:active'),
            now()->addMinutes(self::CACHE_TTL),
            fn () => Reciter::active()->ordered()->get()
        );
    }

    /**
     * Search reciters by name (multi-language)
     *
     * Reciter names live in the reciter_translations table (one row per locale),
     * so the search runs against translations rather than a JSON column.
     */
    public function search(string $query, bool $activeOnly = true): Collection
    {
        $searchQuery = Reciter::query()
            ->whereHas('translations', function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%");
            })
            ->ordered();

        if ($activeOnly) {
            $searchQuery->active();
        }

        return $searchQuery->get();
    }

    /**
     * Create new reciter
     */
    public function create(array $data): Reciter
    {
        $reciter = Reciter::create($data);
        $this->clearCache();

        return $reciter;
    }

    /**
     * Update reciter
     */
    public function update(Reciter $reciter, array $data): bool
    {
        $updated = $reciter->update($data);
        $this->clearCache();

        return $updated;
    }

    /**
     * Delete reciter
     */
    public function delete(Reciter $reciter): bool
    {
        $deleted = $reciter->delete();
        $this->clearCache();

        return $deleted;
    }

    /**
     * Bulk delete reciters
     *
     * @return int Number of deleted reciters
     */
    public function bulkDelete(array $ids): int
    {
        $deleted = Reciter::whereIn('id', $ids)->delete();
        $this->clearCache();

        return $deleted;
    }

    /**
     * Reorder reciters
     *
     * @param  array  $ids  Ordered array of IDs
     */
    public function reorder(array $ids): bool
    {
        foreach ($ids as $index => $id) {
            Reciter::where('id', $id)->update(['display_order' => $index]);
        }

        $this->clearCache();

        return true;
    }

    /**
     * Update featured status
     */
    public function updateFeatured(Reciter $reciter, bool $featured): bool
    {
        $updated = $reciter->update(['is_featured' => $featured]);
        $this->clearCache();

        return $updated;
    }

    /**
     * Update active status
     */
    public function updateActive(Reciter $reciter, bool $active): bool
    {
        $updated = $reciter->update(['is_active' => $active]);
        $this->clearCache();

        return $updated;
    }

    /**
     * Count recitations for reciter
     */
    public function countRecitations(Reciter $reciter): int
    {
        return Recitation::where('reciter_id', $reciter->id)->count();
    }

    public function getProfileRecordings(int $profileId): EloquentCollection
    {
        return Reciter::query()
            ->without('sources')
            ->select(['id', 'profile_id', 'style', 'riwayah', 'is_active', 'display_order'])
            ->where('profile_id', $profileId)
            ->withCount(['recitations as surahs_with_audio' => fn ($q) => $q->select(DB::raw('count(distinct surah_id)'))])
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * Get paginated reciters with search/filter support (admin list).
     *
     * Searches against the reciter_translations table so the query is
     * database-agnostic and works whether or not a JSON name column exists.
     *
     * @param  array{search?: string, country?: string, style?: string}  $filters
     */
    public function paginateWithFilters(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = Reciter::query()
            ->select([
                'id',
                'slug',
                'country',
                'style',
                'profile_image',
                'profile_id',
                'is_featured',
                'is_active',
                'display_order',
                'created_at',
                'updated_at',
            ])
            // The admin list falls back to the person's portrait when a recording has none.
            ->with(['translations:reciter_id,locale,name,description,is_ready', 'profile:id,profile_image'])
            ->orderBy('display_order')
            ->orderByDesc('created_at');

        $search = $filters['search'] ?? null;
        if ($search) {
            $query->whereHas('translations', function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%");
            });
        }

        if (! empty($filters['country'])) {
            $query->where('country', $filters['country']);
        }

        if (! empty($filters['style'])) {
            $query->where('style', $filters['style']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get aggregate stats for the admin dashboard card.
     *
     * Uses a single query with conditional aggregates to avoid N round-trips.
     *
     * @return array{total: int, featured: int, countries: int}
     */
    public function getStats(): array
    {
        $row = DB::table('quran_reciters')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN is_featured = 1 THEN 1 ELSE 0 END) as featured')
            ->selectRaw('COUNT(DISTINCT country) as countries')
            ->whereNotNull('country')
            ->first();

        // A separate total count is needed because the WHERE above excludes null-country rows.
        $total = DB::table('quran_reciters')->count();

        return [
            'total' => $total,
            'featured' => (int) ($row->featured ?? 0),
            'countries' => (int) ($row->countries ?? 0),
        ];
    }

    /**
     * Get a sorted, distinct list of non-null country codes.
     *
     * @return Collection<int, string>
     */
    public function getDistinctCountries(): Collection
    {
        return Reciter::query()
            ->select('country')
            ->distinct()
            ->whereNotNull('country')
            ->orderBy('country')
            ->pluck('country');
    }

    /**
     * Fetch a set of reciters by their primary keys.
     *
     * Used by the service before bulk-deleting to collect image paths for cleanup.
     *
     * @param  array<int>  $ids
     * @return EloquentCollection<int, Reciter>
     */
    public function getByIds(array $ids): EloquentCollection
    {
        return Reciter::query()
            ->select(['id', 'profile_image'])
            ->whereIn('id', $ids)
            ->get();
    }

    /**
     * Clear all reciter caches.
     *
     * Cache::tags() requires Redis/Memcached and is not available with the
     * file or database drivers. We use a version counter instead: bumping
     * the version orphans every previously-cached key without needing tag
     * support, and works with any cache driver. The counter lives in
     * QuranCacheKeys so the reciter import commands can bump the same one.
     */
    private function clearCache(): void
    {
        QuranCacheKeys::flushReciters();
    }

    /**
     * Prefix a cache key with the current version so that clearCache()
     * effectively invalidates all keys written by this repository.
     */
    private function versionedKey(string $key): string
    {
        return QuranCacheKeys::reciterScoped($key);
    }
}
