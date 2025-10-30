<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Repositories;

use App\Domain\Seerah\Enums\EventType;
use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface SeerahEventRepositoryInterface extends RepositoryInterface
{
    /**
     * Get events by type
     */
    public function getByType(EventType|string $type, int $perPage = 15): LengthAwarePaginator;

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
}
