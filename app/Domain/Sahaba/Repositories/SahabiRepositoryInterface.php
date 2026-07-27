<?php

declare(strict_types=1);

namespace App\Domain\Sahaba\Repositories;

use App\Domain\Sahaba\DTOs\SahabiListFilters;
use App\Domain\Sahaba\Enums\CompanionStatus;
use App\Domain\Sahaba\Models\Sahabi;
use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface SahabiRepositoryInterface extends RepositoryInterface
{
    /**
     * Get companions by historical-rank category (badr, ansar, muhajir, ...)
     */
    public function getByCategory(CompanionStatus|string $category, int $perPage = 15): LengthAwarePaginator;

    /**
     * Replace this Sahabi's category memberships with the given set of keys.
     *
     * @param  array<int, string>  $categoryKeys
     */
    public function syncCategories(Sahabi $sahabi, array $categoryKeys): void;

    /**
     * Get companions by tribe
     */
    public function getByTribe(string $tribe, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get companions who participated in a battle
     */
    public function getByBattle(string $battle, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get featured companions
     */
    public function getFeatured(int $limit = 10): Collection;

    /**
     * Get early Muslims by conversion order
     */
    public function getEarlyMuslims(int $limit = 10): Collection;

    /**
     * Get top hadith narrators
     */
    public function getTopNarrators(int $limit = 10): Collection;

    /**
     * Search companions
     */
    public function search(string $query, int $perPage = 15): LengthAwarePaginator;

    /**
     * Paginate all companions for the public listing (locale-aware name
     * ordering, lightweight translations).
     */
    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator;

    /**
     * Paginate companions for the admin data table, applying search + filters
     * and the same locale-aware name ordering as {@see getAllPaginated()}.
     */
    public function getFilteredPaginated(SahabiListFilters $filters, int $perPage = 15): LengthAwarePaginator;

    /**
     * Bulk set (or clear) the featured flag for the given companions.
     *
     * @param  array<int, int>  $ids
     */
    public function bulkSetFeatured(array $ids, bool $featured): int;

    /**
     * Bulk publish/unpublish companions — updates `is_ready` across every
     * locale row for each selected companion.
     *
     * @param  array<int, int>  $ids
     */
    public function bulkSetReadyStatus(array $ids, bool $isReady): int;

    /**
     * Get aggregate gender stats for the admin index page (single query).
     *
     * @return array{total: int, male: int, female: int}
     */
    public function getIndexStats(): array;

    /**
     * Get publication-correct aggregate gender stats for the public index.
     *
     * @return array{total: int, male: int, female: int}
     */
    public function getPublicIndexStats(): array;
}
