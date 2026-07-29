<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Repositories;

use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Hadith Collection Repository Interface
 *
 * Contract for Hadith Collection data access operations.
 */
interface CollectionRepositoryInterface extends RepositoryInterface
{
    /**
     * Get collection by slug
     */
    public function getBySlug(string $slug, string $locale = 'ar'): ?HadithCollection;

    /**
     * Get collection by abbreviation
     */
    public function getByAbbreviation(string $abbreviation): ?HadithCollection;

    /**
     * Get all collections with books count
     */
    public function getAllWithBooksCount(): Collection;

    /**
     * Get collection with all its books
     */
    public function getWithBooks(int $id): ?HadithCollection;

    /**
     * Search collections by name
     */
    public function search(string $query, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get collections with statistics
     *
     * @return Collection<int, array{collection: HadithCollection, books_count: int, hadiths_count: int}>
     */
    public function getWithStatistics(): Collection;

    /**
     * Get paginated collections with filters
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateWithFilters(
        int $perPage = 15,
        string $search = '',
        string $sortBy = 'id',
        array $filters = []
    ): LengthAwarePaginator;

    /**
     * Get a lightweight list of all collections for use in form dropdowns.
     *
     * Returns id and localized name JSON ordered by id.
     */
    public function getAllForDropdown(): Collection;

    /**
     * Bulk-update display_order for a set of collections.
     *
     * @param  array<array{id: int, order: int}>  $items
     */
    public function reorder(array $items): void;

    /**
     * Toggle a boolean field on a single collection and return the updated record.
     *
     * @param  int  $id  Collection primary key
     * @param  string  $field  Column name ('active' or 'featured')
     */
    public function toggleField(int $id, string $field): HadithCollection;
}
