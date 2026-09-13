<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Quran\Models\Reciter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * Reciter Repository Interface
 * Defines contract for Quran reciter data access
 */
interface ReciterRepositoryInterface
{
    /**
     * Get paginated reciters
     */
    public function paginate(int $perPage = 15, bool $activeOnly = true, ?string $kind = null): LengthAwarePaginator;

    /**
     * Get reciter by ID
     */
    public function getById(int $id): ?Reciter;

    /**
     * Get reciter by slug
     */
    public function getBySlug(string $slug): ?Reciter;

    /**
     * Get reciters by country
     */
    public function getByCountry(string $country, bool $activeOnly = true): Collection;

    /**
     * Get reciters by recitation style
     */
    public function getByStyle(string $style, bool $activeOnly = true): Collection;

    /**
     * Get featured reciters
     */
    public function getFeatured(bool $activeOnly = true): Collection;

    /**
     * Get active reciters
     */
    public function getActive(): Collection;

    /**
     * Search reciters by name (multi-language)
     */
    public function search(string $query, bool $activeOnly = true): Collection;

    /**
     * Create new reciter
     */
    public function create(array $data): Reciter;

    /**
     * Update reciter
     */
    public function update(Reciter $reciter, array $data): bool;

    /**
     * Delete reciter
     */
    public function delete(Reciter $reciter): bool;

    /**
     * Bulk delete reciters
     *
     * @return int Number of deleted reciters
     */
    public function bulkDelete(array $ids): int;

    /**
     * Reorder reciters
     *
     * @param  array  $ids  Ordered array of IDs
     */
    public function reorder(array $ids): bool;

    /**
     * Update featured status
     */
    public function updateFeatured(Reciter $reciter, bool $featured): bool;

    /**
     * Update active status
     */
    public function updateActive(Reciter $reciter, bool $active): bool;

    /**
     * Count recitations for reciter
     */
    public function countRecitations(Reciter $reciter): int;

    /**
     * All recordings of a profile with `surahs_with_audio` (distinct surahs).
     */
    public function getProfileRecordings(int $profileId): EloquentCollection;

    /**
     * Get paginated reciters with search/filter support (admin list)
     *
     * @param  array{search?: string, country?: string, style?: string}  $filters
     */
    public function paginateWithFilters(array $filters, int $perPage): LengthAwarePaginator;

    /**
     * Get aggregate stats for the admin dashboard card
     *
     * @return array{total: int, featured: int, countries: int}
     */
    public function getStats(): array;

    /**
     * Get a sorted, distinct list of non-null country codes
     *
     * @return Collection<int, string>
     */
    public function getDistinctCountries(): Collection;

    /**
     * Get reciters whose image paths need to be cleaned up for a set of IDs
     *
     * @param  array<int>  $ids
     * @return EloquentCollection<int, Reciter>
     */
    public function getByIds(array $ids): EloquentCollection;
}
