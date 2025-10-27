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
     *
     * @param CompanionStatus|string $status
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByStatus(CompanionStatus|string $status, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get companions by tribe
     *
     * @param string $tribe
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByTribe(string $tribe, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get companions who participated in a battle
     *
     * @param string $battle
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByBattle(string $battle, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get featured companions
     *
     * @param int $limit
     * @return Collection
     */
    public function getFeatured(int $limit = 10): Collection;

    /**
     * Get early Muslims by conversion order
     *
     * @param int $limit
     * @return Collection
     */
    public function getEarlyMuslims(int $limit = 10): Collection;

    /**
     * Get top hadith narrators
     *
     * @param int $limit
     * @return Collection
     */
    public function getTopNarrators(int $limit = 10): Collection;

    /**
     * Search companions
     *
     * @param string $query
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function search(string $query, int $perPage = 15): LengthAwarePaginator;
}

