<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

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
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Reciter::query()
            ->ordered()
            ->paginate($perPage);
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
     * Get reciters by country
     *
     * @param string $country
     * @return Collection
     */
    public function getByCountry(string $country): Collection
    {
        return Cache::tags(['reciters'])->remember(
            "reciters:country:$country",
            now()->addMinutes(self::CACHE_TTL),
            fn () => Reciter::where('country', $country)
                ->ordered()
                ->get()
        );
    }

    /**
     * Get reciters by recitation style
     *
     * @param string $style
     * @return Collection
     */
    public function getByStyle(string $style): Collection
    {
        return Cache::tags(['reciters'])->remember(
            "reciters:style:$style",
            now()->addMinutes(self::CACHE_TTL),
            fn () => Reciter::where('style', $style)
                ->ordered()
                ->get()
        );
    }

    /**
     * Get featured reciters
     *
     * @return Collection
     */
    public function getFeatured(): Collection
    {
        return Cache::tags(['reciters'])->remember(
            'reciters:featured',
            now()->addMinutes(self::CACHE_TTL),
            fn () => Reciter::featured()
                ->ordered()
                ->get()
        );
    }

    /**
     * Search reciters by name (multi-language)
     *
     * @param string $query
     * @return Collection
     */
    public function search(string $query): Collection
    {
        return Reciter::where(function ($q) use ($query) {
            $q->whereJsonContains('name->ar', $query)
                ->orWhereJsonContains('name->en', $query)
                ->orWhereJsonContains('name->fr', $query)
                ->orWhereJsonContains('name->tr', $query)
                ->orWhereJsonContains('name->ur', $query);
        })
            ->ordered()
            ->get();
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
     * Count recitations for reciter
     * (This will be implemented when recitations table exists)
     *
     * @param Reciter $reciter
     * @return int
     */
    public function countRecitations(Reciter $reciter): int
    {
        // TODO: Implement when quran_recitations table is created
        // return $reciter->recitations()->count();
        return 0;
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
