<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Quran\Models\Reciter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Reciter Repository Interface
 * Defines contract for Quran reciter data access
 */
interface ReciterRepositoryInterface
{
    /**
     * Get paginated reciters
     *
     * @param int $perPage
     * @param bool $activeOnly
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, bool $activeOnly = true): LengthAwarePaginator;

    /**
     * Get reciter by ID
     *
     * @param int $id
     * @return Reciter|null
     */
    public function getById(int $id): ?Reciter;

    /**
     * Get reciter by slug
     *
     * @param string $slug
     * @return Reciter|null
     */
    public function getBySlug(string $slug): ?Reciter;

    /**
     * Get reciters by country
     *
     * @param string $country
     * @param bool $activeOnly
     * @return Collection
     */
    public function getByCountry(string $country, bool $activeOnly = true): Collection;

    /**
     * Get reciters by recitation style
     *
     * @param string $style
     * @param bool $activeOnly
     * @return Collection
     */
    public function getByStyle(string $style, bool $activeOnly = true): Collection;

    /**
     * Get featured reciters
     *
     * @param bool $activeOnly
     * @return Collection
     */
    public function getFeatured(bool $activeOnly = true): Collection;

    /**
     * Get active reciters
     *
     * @return Collection
     */
    public function getActive(): Collection;

    /**
     * Search reciters by name (multi-language)
     *
     * @param string $query
     * @param bool $activeOnly
     * @return Collection
     */
    public function search(string $query, bool $activeOnly = true): Collection;

    /**
     * Create new reciter
     *
     * @param array $data
     * @return Reciter
     */
    public function create(array $data): Reciter;

    /**
     * Update reciter
     *
     * @param Reciter $reciter
     * @param array $data
     * @return bool
     */
    public function update(Reciter $reciter, array $data): bool;

    /**
     * Delete reciter
     *
     * @param Reciter $reciter
     * @return bool
     */
    public function delete(Reciter $reciter): bool;

    /**
     * Bulk delete reciters
     *
     * @param array $ids
     * @return int Number of deleted reciters
     */
    public function bulkDelete(array $ids): int;

    /**
     * Reorder reciters
     *
     * @param array $ids Ordered array of IDs
     * @return bool
     */
    public function reorder(array $ids): bool;

    /**
     * Update featured status
     *
     * @param Reciter $reciter
     * @param bool $featured
     * @return bool
     */
    public function updateFeatured(Reciter $reciter, bool $featured): bool;

    /**
     * Update active status
     *
     * @param Reciter $reciter
     * @param bool $active
     * @return bool
     */
    public function updateActive(Reciter $reciter, bool $active): bool;

    /**
     * Count recitations for reciter
     *
     * @param Reciter $reciter
     * @return int
     */
    public function countRecitations(Reciter $reciter): int;
}
