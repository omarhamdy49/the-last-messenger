<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\Enums\RevelationPlace;
use App\Domain\Quran\Models\Surah;
use App\Domain\Quran\Repositories\SurahRepositoryInterface;
use App\Domain\Shared\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Surah Service
 * Handles business logic for Quran surahs (chapters)
 */
class SurahService extends BaseService
{
    /**
     * Constructor
     */
    public function __construct(SurahRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get paginated surahs (BaseService signature compatibility)
     *
     * @param  array<string>  $columns
     * @param  array<string>  $relations
     */
    public function paginate(int $perPage = 15, array $columns = ['*'], array $relations = []): LengthAwarePaginator
    {
        return parent::paginate($perPage, $columns, $relations);
    }

    /**
     * Get paginated surahs with optional search and filters
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateWithFilters(
        int $perPage = 15,
        string $search = '',
        string $sortBy = 'number',
        array $filters = []
    ): LengthAwarePaginator {
        /** @var SurahRepositoryInterface */
        $repo = $this->repository;

        return $repo->paginateWithFilters($perPage, $search, $sortBy, $filters);
    }

    /**
     * Get surah with detailed relations (verses)
     */
    public function getWithDetails(int $id): Surah
    {
        /** @var Surah */
        return $this->repository->findOrFail($id, ['*'], ['verses']);
    }

    /**
     * Count surahs by revelation place
     *
     * @param  string  $place  'makkah' or 'madinah'
     */
    public function countByRevelation(string $place): int
    {
        /** @var SurahRepositoryInterface */
        $repo = $this->repository;

        return $repo->countByRevelation($place);
    }

    /**
     * Get total verses count across all surahs
     */
    public function getTotalVersesCount(): int
    {
        /** @var SurahRepositoryInterface */
        $repo = $this->repository;

        return $repo->getTotalVersesCount();
    }

    /**
     * Search surahs across all languages
     */
    public function search(string $query, int $perPage = 15, ?string $revelationPlace = null): LengthAwarePaginator
    {
        /** @var SurahRepositoryInterface */
        $repo = $this->repository;

        return $repo->search($query, $perPage, $revelationPlace);
    }

    /**
     * Get surahs by revelation place
     */
    public function getByRevelationPlace(RevelationPlace|string $place): Collection
    {
        return $this->repository->getByRevelationPlace($place);
    }

    /**
     * Get surahs by Juz
     */
    public function getByJuz(int $juz): Collection
    {
        return $this->repository->getByJuz($juz);
    }

    /**
     * Get all surahs (cached)
     */
    public function getAll(): Collection
    {
        return $this->repository->all();
    }

    /**
     * Update surah metadata (BaseService signature compatibility)
     * Note: Surahs cannot be created or deleted (fixed 114 surahs)
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): bool
    {
        return parent::update($id, $data);
    }

    /**
     * Update surah metadata and return updated model
     *
     * @param  array<string, mixed>  $data
     */
    public function updateSurah(int $id, array $data): Surah
    {
        $this->repository->update($id, $data);

        /** @var Surah */
        return $this->repository->findOrFail($id);
    }

    /**
     * Get average verses per surah
     */
    public function getAverageVersesPerSurah(): float
    {
        /** @var SurahRepositoryInterface */
        $repo = $this->repository;

        return $repo->getAverageVersesPerSurah();
    }

    /**
     * Find surah by number (1-114)
     */
    public function findByNumber(int $number): ?Surah
    {
        return $this->repository->getByNumber($number);
    }
}
