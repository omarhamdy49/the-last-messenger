<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Repositories;

use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface SeerahEventRepositoryInterface extends RepositoryInterface
{
    /**
     * @return array{event: SeerahEvent, prev: ?SeerahEvent, next: ?SeerahEvent, related: Collection<int, SeerahEvent>}|null
     */
    public function getPublicPage(string $slug, string $locale, int $relatedLimit = 3): ?array;

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
     * Publicly visible event counts grouped by type slug, for the public filter
     * pills. Unlike getIndexStats() this respects status + translation readiness.
     *
     * @return array<string, int>
     */
    public function countPublicByCategory(string $locale): array;

    /**
     * Public events directory with every filter composed into one query.
     *
     * @param  array{search?: string, type?: string, milestone?: string, importance?: string, pivotal?: bool, disputed?: bool, sort?: string}  $filters
     */
    public function paginatePublicFiltered(array $filters, string $locale, int $perPage = 12): LengthAwarePaginator;

    /**
     * Counts behind the public filter controls.
     *
     * @return array{pivotal: int, disputed: int, importance: array<string, int>}
     */
    public function publicFilterFacets(string $locale): array;

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

    /**
     * Fetch events for export with the relations an export row needs
     * (translations, type, participants) and optional whitelisted filters
     * (event_type, start_date, end_date, search).
     *
     * Admin path — drafts are included so an export mirrors the admin index.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, SeerahEvent>
     */
    public function getForExport(array $filters = []): Collection;
}
