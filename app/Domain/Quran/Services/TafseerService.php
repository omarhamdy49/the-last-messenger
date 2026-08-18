<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\Models\VerseTafsir;
use App\Domain\Quran\Repositories\TafseerRepositoryInterface;
use App\Domain\Shared\Exceptions\BusinessRuleViolationException;
use App\Domain\Shared\Services\BaseService;
use App\Exceptions\Domain\ResourceNotFoundException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Tafseer Service
 * Business logic for Tafseer management
 *
 * @extends BaseService<TafseerRepositoryInterface>
 */
class TafseerService extends BaseService
{
    /**
     * TafseerService constructor
     */
    public function __construct(
        TafseerRepositoryInterface $repository
    ) {
        $this->repository = $repository;
    }

    /**
     * Get tafseer with filters and pagination
     */
    public function paginateWithFilters(
        int $perPage = 15,
        ?int $surahId = null,
        ?string $source = null,
        ?string $search = null,
        string $sortBy = 'verse_reference'
    ): LengthAwarePaginator {
        $filters = [
            'surah_id' => $surahId,
            'source' => $source,
            'search' => $search,
            'sort_by' => $sortBy,
        ];

        return $this->repository->paginateWithFilters($perPage, $filters);
    }

    /**
     * Get tafseer by verse reference
     *
     * @return Collection<int, Model>
     */
    public function getByVerseReference(string $verseReference): Collection
    {
        return $this->repository->getByVerseReference($verseReference);
    }

    /**
     * Get tafseer by surah
     */
    public function getBySurah(int $surahId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getBySurah($surahId, $perPage);
    }

    /**
     * Get active tafseer for one surah, paginated for a listing
     */
    public function paginateActiveBySurah(int $surahId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->repository->paginateActiveBySurah($surahId, $perPage);
    }

    /**
     * Get tafseer by source
     */
    public function getBySource(string $source, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getBySource($source, $perPage);
    }

    /**
     * Search tafseer
     */
    public function search(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->search($query, $perPage);
    }

    /**
     * Create new tafseer with duplicate check
     *
     * @param  array<string, mixed>  $data
     *
     * @throws BusinessRuleViolationException
     */
    public function create(array $data): Model
    {
        // Check for duplicates - combination of verse_key and language must be unique
        if ($this->repository->checkDuplicate($data['verse_key'], $data['lang'])) {
            throw new BusinessRuleViolationException(
                "Tafseer already exists for verse {$data['verse_key']} in language {$data['lang']}"
            );
        }

        return $this->repository->create($data);
    }

    /**
     * Update tafseer with duplicate check
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ResourceNotFoundException
     * @throws BusinessRuleViolationException
     */
    public function updateTafseer(VerseTafsir $tafseer, array $data): Model
    {
        // Check for duplicates (excluding current record)
        if (isset($data['verse_key']) && isset($data['lang'])) {
            if ($this->repository->checkDuplicate($data['verse_key'], $data['lang'], $tafseer->id)) {
                throw new BusinessRuleViolationException(
                    "Tafseer already exists for verse {$data['verse_key']} in language {$data['lang']}"
                );
            }
        }

        $this->repository->update($tafseer->id, $data);

        return $this->repository->find($tafseer->id);
    }

    /**
     * Delete tafseer by model
     *
     * @throws ResourceNotFoundException
     */
    public function deleteTafseer(VerseTafsir $tafseer): bool
    {
        return $this->repository->delete($tafseer->id);
    }

    /**
     * Bulk delete tafseer
     *
     * @param  array<int>  $ids
     */
    public function bulkDelete(array $ids): int
    {
        return $this->repository->bulkDelete($ids);
    }

    /**
     * Get tafseer with details
     *
     * @throws ResourceNotFoundException
     */
    public function getWithDetails(int $id): Model
    {
        $tafseer = $this->repository->getWithDetails($id);

        if (! $tafseer) {
            throw new ResourceNotFoundException("Tafseer not found: {$id}");
        }

        return $tafseer;
    }

    /**
     * Get available sources
     *
     * @return array<string>
     */
    public function getAvailableSources(): array
    {
        return $this->repository->getAvailableSources();
    }

    /**
     * Count unique surahs covered
     */
    public function countUniqueSurahs(): int
    {
        return $this->repository->countUniqueSurahs();
    }

    /**
     * Get source display names
     *
     * @return array<string, string>
     */
    public function getSourceNames(): array
    {
        return [
            'ibn_kathir' => 'Ibn Kathir',
            'jalalayn' => 'Jalalayn',
            'sadi' => "Sa'di",
            'qurtubi' => 'Qurtubi',
            'tabari' => 'Tabari',
            'baghawi' => 'Baghawi',
            'muyassar' => 'Muyassar',
            'ashhari' => 'Ashhari',
            'qasimi' => 'Qasimi',
        ];
    }
}
