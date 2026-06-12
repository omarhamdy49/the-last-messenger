<?php

declare(strict_types=1);

namespace App\Domain\Sahaba\Repositories;

use App\Domain\Sahaba\Enums\CompanionStatus;
use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface SahabiRepositoryInterface extends RepositoryInterface
{
    /**
     * Get companions by status
     */
    public function getByStatus(CompanionStatus|string $status, int $perPage = 15): LengthAwarePaginator;

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
     * Get aggregate gender stats for the admin index page (single query).
     *
     * @return array{total: int, male: int, female: int}
     */
    public function getIndexStats(): array;
}
