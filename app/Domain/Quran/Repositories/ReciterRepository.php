<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Quran\Models\Recitation;
use App\Domain\Quran\Models\Reciter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

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
     *
     * @param int $perPage
     * @param bool $activeOnly
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, bool $activeOnly = true): LengthAwarePaginator
    {
        $query = Reciter::query()->ordered();

        if ($activeOnly) {
            $query->active();
        }

        return $query->paginate($perPage);
    }

    /**
     * Get reciter by ID
     *
     * @param int $id
     * @return Reciter|null
     */
    public function getById(int $id): ?Reciter
    {
        return Cache::tags(['reciters'])->remember(
            "reciter:$id",
            now()->addMinutes(self::CACHE_TTL),
            fn () => Reciter::find($id)
        );
    }

    /**
     * Get reciter by slug
     *
     * @param string $slug
     * @return Reciter|null
     */
    public function getBySlug(string $slug): ?Reciter
    {
        return Cache::tags(['reciters'])->remember(
            "reciter:slug:$slug",
            now()->addMinutes(self::CACHE_TTL),
            fn () => Reciter::where('slug', $slug)->first()
        );
    }

    /**
     * Get reciters by country
     *
     * @param string $country
     * @param bool $activeOnly
     * @return Collection
     */
    public function getByCountry(string $country, bool $activeOnly = true): Collection
    {
        $cacheKey = "reciters:country:$country:" . ($activeOnly ? 'active' : 'all');

        return Cache::tags(['reciters'])->remember(
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
     *
     * @param string $style
     * @param bool $activeOnly
     * @return Collection
     */
    public function getByStyle(string $style, bool $activeOnly = true): Collection
    {
        $cacheKey = "reciters:style:$style:" . ($activeOnly ? 'active' : 'all');

        return Cache::tags(['reciters'])->remember(
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
     *
     * @param bool $activeOnly
     * @return Collection
     */
    public function getFeatured(bool $activeOnly = true): Collection
    {
        $cacheKey = 'reciters:featured:' . ($activeOnly ? 'active' : 'all');

        return Cache::tags(['reciters'])->remember(
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
     *
     * @return Collection
     */
    public function getActive(): Collection
    {
        return Cache::tags(['reciters'])->remember(
            'reciters:active',
            now()->addMinutes(self::CACHE_TTL),
            fn () => Reciter::active()->ordered()->get()
        );
    }

    /**
     * Search reciters by name (multi-language)
     *
     * @param string $query
     * @param bool $activeOnly
     * @return Collection
     */
    public function search(string $query, bool $activeOnly = true): Collection
    {
        $searchQuery = Reciter::where(function ($q) use ($query) {
            $q->whereRaw("JSON_EXTRACT(name, '$.ar') LIKE ?", ["%{$query}%"])
                ->orWhereRaw("JSON_EXTRACT(name, '$.en') LIKE ?", ["%{$query}%"])
                ->orWhereRaw("JSON_EXTRACT(name, '$.fr') LIKE ?", ["%{$query}%"])
                ->orWhereRaw("JSON_EXTRACT(name, '$.tr') LIKE ?", ["%{$query}%"])
                ->orWhereRaw("JSON_EXTRACT(name, '$.ur') LIKE ?", ["%{$query}%"]);
        })->ordered();

        if ($activeOnly) {
            $searchQuery->active();
        }

        return $searchQuery->get();
    }

    /**
     * Create new reciter
     *
     * @param array $data
     * @return Reciter
     */
    public function create(array $data): Reciter
    {
        $reciter = Reciter::create($data);
        $this->clearCache();

        return $reciter;
    }

    /**
     * Update reciter
     *
     * @param Reciter $reciter
     * @param array $data
     * @return bool
     */
    public function update(Reciter $reciter, array $data): bool
    {
        $updated = $reciter->update($data);
        $this->clearCache();

        return $updated;
    }

    /**
     * Delete reciter
     *
     * @param Reciter $reciter
     * @return bool
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
     * @param array $ids
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
     * @param array $ids Ordered array of IDs
     * @return bool
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
     *
     * @param Reciter $reciter
     * @param bool $featured
     * @return bool
     */
    public function updateFeatured(Reciter $reciter, bool $featured): bool
    {
        $updated = $reciter->update(['is_featured' => $featured]);
        $this->clearCache();

        return $updated;
    }

    /**
     * Update active status
     *
     * @param Reciter $reciter
     * @param bool $active
     * @return bool
     */
    public function updateActive(Reciter $reciter, bool $active): bool
    {
        $updated = $reciter->update(['is_active' => $active]);
        $this->clearCache();

        return $updated;
    }

    /**
     * Count recitations for reciter
     *
     * @param Reciter $reciter
     * @return int
     */
    public function countRecitations(Reciter $reciter): int
    {
        return Recitation::where('reciter_id', $reciter->id)->count();
    }

    /**
     * Clear all reciter caches
     *
     * @return void
     */
    private function clearCache(): void
    {
        Cache::tags(['reciters'])->flush();
    }
}
