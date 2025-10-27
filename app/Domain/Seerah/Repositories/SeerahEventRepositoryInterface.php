<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Repositories;

use App\Domain\Shared\Contracts\RepositoryInterface;
use App\Domain\Seerah\Enums\EventImportance;
use App\Domain\Seerah\Enums\EventType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface SeerahEventRepositoryInterface extends RepositoryInterface
{
    /**
     * Get events by type
     *
     * @param EventType|string $type
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByType(EventType|string $type, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get events by Hijri year
     *
     * @param int $year
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByHijriYear(int $year, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get events by Gregorian year
     *
     * @param int $year
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByGregorianYear(int $year, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get featured events
     *
     * @param int $limit
     * @return Collection
     */
    public function getFeatured(int $limit = 10): Collection;

    /**
     * Get events with location
     *
     * @return Collection
     */
    public function getEventsWithLocation(): Collection;

    /**
     * Search events
     *
     * @param string $query
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function search(string $query, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get events in chronological order
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getChronological(int $perPage = 15): LengthAwarePaginator;
}

