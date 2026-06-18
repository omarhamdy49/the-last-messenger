<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Repositories;

use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface SeerahEventRepositoryInterface extends RepositoryInterface
{
    /**
     * Get events by type (type id or slug)
     */
    public function getByType(int|string $type, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get events by Hijri year
     */
    public function getByHijriYear(int $year, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get events by Gregorian year
     */
    public function getByGregorianYear(int $year, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get featured events
     */
    public function getFeatured(int $limit = 10): Collection;

    /**
     * Get events with location
     */
    public function getEventsWithLocation(): Collection;

    /**
     * Search events
     */
    public function search(string $query, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get events in chronological order
     */
    public function getChronological(int $perPage = 15): LengthAwarePaginator;

    /**
     * Get event counts grouped by type slug (single aggregate query).
     * Returns ['total' => int, 'categories' => array<string, int>].
     *
     * @return array{total: int, categories: array<string, int>}
     */
    public function getIndexStats(): array;

    /**
     * Paginate events for the admin index with optional whitelisted filters
     * (type, importance, status, search).
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator;

    /**
     * Bulk-update the publication status of the given events in one query.
     *
     * @param  array<int>  $ids
     */
    public function bulkUpdateStatus(array $ids, string $status): int;
}
