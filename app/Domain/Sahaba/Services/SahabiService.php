<?php

declare(strict_types=1);

namespace App\Domain\Sahaba\Services;

use App\Domain\Sahaba\DTOs\SahabiListFilters;
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
     * Get companions for the admin data table with search + advanced filters.
     */
    public function getAdminCompanionList(SahabiListFilters $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getFilteredPaginated($filters, $perPage);
    }

    /**
     * @param  array<int, int>  $ids
     */
    public function bulkSetFeatured(array $ids, bool $featured): int
    {
        return $this->repository->bulkSetFeatured($ids, $featured);
    }

    /**
     * @param  array<int, int>  $ids
     */
    public function bulkSetReadyStatus(array $ids, bool $isReady): int
    {
        return $this->repository->bulkSetReadyStatus($ids, $isReady);
    }

    /**
     * Get companion by slug
     *
     * @throws ResourceNotFoundException
     */
    public function getCompanionBySlug(string $slug): Sahabi
    {
        /** @var Sahabi|null $sahabi */
        $sahabi = ctype_digit($slug)
            ? $this->repository->find((int) $slug)
            : $this->repository->findBySlug($slug);

        if (! $sahabi) {
            throw new ResourceNotFoundException("Companion not found: {$slug}");
        }

        // Increment views
        $sahabi->incrementViews();

        return $sahabi;
    }

    /**
     * Get companions by historical-rank category (badr, ansar, muhajir, ...)
     */
    public function getCompanionsByCategory(CompanionStatus|string $category, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByCategory($category, $perPage);
    }

    /**
     * Replace a Sahabi's category memberships (badr, ansar, muhajir, ...).
     *
     * @param  array<int, string>  $categoryKeys
     */
    public function syncCategories(Sahabi $sahabi, array $categoryKeys): void
    {
        $this->repository->syncCategories($sahabi, $categoryKeys);
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
        return $this->repository->count(['gender' => $gender, 'is_sahabi' => true]);
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

    /**
     * @return array{total: int, male: int, female: int}
     */
    public function getPublicIndexStats(): array
    {
        return $this->repository->getPublicIndexStats();
    }
}
