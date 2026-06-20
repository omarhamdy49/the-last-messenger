<?php

declare(strict_types=1);

namespace App\Domain\Sahaba\Services;

use App\Domain\Sahaba\Enums\CompanionStatus;
use App\Domain\Sahaba\Models\Sahabi;
use App\Domain\Sahaba\Repositories\SahabiRepositoryInterface;
use App\Domain\Shared\Services\BaseService;
use App\Exceptions\Domain\ResourceNotFoundException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class SahabiService extends BaseService
{
    /**
     * SahabiService constructor
     */
    public function __construct(
        SahabiRepositoryInterface $repository
    ) {
        $this->repository = $repository;
    }

    /**
     * Get all companions with pagination
     */
    public function getAllCompanions(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getAllPaginated($perPage);
    }

    /**
     * Get companion by slug
     *
     * @throws ResourceNotFoundException
     */
    public function getCompanionBySlug(string $slug): Sahabi
    {
        $sahabi = $this->repository->findBySlug($slug);

        if (! $sahabi) {
            throw new ResourceNotFoundException("Companion not found: {$slug}");
        }

        // Increment views
        $sahabi->incrementViews();

        return $sahabi;
    }

    /**
     * Get companions by status
     */
    public function getCompanionsByStatus(CompanionStatus|string $status, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByStatus($status, $perPage);
    }

    /**
     * Get companions by tribe
     */
    public function getCompanionsByTribe(string $tribe, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByTribe($tribe, $perPage);
    }

    /**
     * Get companions who participated in a battle
     */
    public function getCompanionsByBattle(string $battle, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByBattle($battle, $perPage);
    }

    /**
     * Get featured companions
     */
    public function getFeaturedCompanions(int $limit = 10): Collection
    {
        return $this->repository->getFeatured($limit);
    }

    /**
     * Get early Muslims
     */
    public function getEarlyMuslims(int $limit = 10): Collection
    {
        return $this->repository->getEarlyMuslims($limit);
    }

    /**
     * Get top hadith narrators
     */
    public function getTopNarrators(int $limit = 10): Collection
    {
        return $this->repository->getTopNarrators($limit);
    }

    /**
     * Search companions
     */
    public function searchCompanions(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->search($query, $perPage);
    }

    /**
     * Count companions by gender
     */
    public function countByGender(string $gender): int
    {
        return $this->repository->count(['gender' => $gender]);
    }

    /**
     * Get aggregate gender stats for the admin index page (single query).
     *
     * @return array{total: int, male: int, female: int}
     */
    public function getIndexStats(): array
    {
        return $this->repository->getIndexStats();
    }
}
