<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Services;

use App\Domain\Hadith\Enums\HadithGrade;
use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Repositories\HadithRepositoryInterface;
use App\Domain\Shared\Services\BaseService;
use App\Exceptions\Domain\ResourceNotFoundException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Hadith Service
 * Handles business logic for hadith
 */
class HadithService extends BaseService
{
    /**
     * Constructor
     *
     * @param HadithRepositoryInterface $repository
     */
    public function __construct(
        protected HadithRepositoryInterface $repository
    ) {
    }

    /**
     * Get hadith by book with pagination
     *
     * @param int $bookId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getHadithByBook(int $bookId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByBook($bookId, $perPage, ['book', 'chapter']);
    }

    /**
     * Get hadith by chapter
     *
     * @param int $chapterId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getHadithByChapter(int $chapterId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByChapter($chapterId, $perPage, ['book', 'chapter']);
    }

    /**
     * Get hadith by authenticity grade
     *
     * @param HadithGrade $grade
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getHadithByGrade(HadithGrade $grade, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByGrade($grade, $perPage, ['book', 'chapter']);
    }

    /**
     * Get authentic hadith (sahih)
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getSahihHadith(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getSahih($perPage, ['book', 'chapter']);
    }

    /**
     * Get reliable hadith (sahih or hasan)
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getReliableHadith(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getReliable($perPage, ['book', 'chapter']);
    }

    /**
     * Search hadith
     *
     * @param string $query
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function searchHadith(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->search($query, $perPage, ['book', 'chapter']);
    }

    /**
     * Find hadith by book and hadith number
     *
     * @param int $bookId
     * @param int $hadithNumber
     * @return Hadith
     * @throws ResourceNotFoundException
     */
    public function findByBookAndNumber(int $bookId, int $hadithNumber): Hadith
    {
        $hadith = $this->repository->findByBookAndNumber($bookId, $hadithNumber, ['book', 'chapter', 'narrators']);

        if (!$hadith) {
            throw new ResourceNotFoundException('Hadith', "{$bookId}:{$hadithNumber}");
        }

        return $hadith;
    }

    /**
     * Get hadith with narrator chain
     *
     * @param int $id
     * @return Hadith
     * @throws ResourceNotFoundException
     */
    public function getHadithWithNarrators(int $id): Hadith
    {
        $hadith = $this->repository->getWithNarrators($id);

        if (!$hadith) {
            throw new ResourceNotFoundException('Hadith', $id);
        }

        // Increment views
        $this->repository->incrementViews($id);

        return $hadith;
    }

    /**
     * Filter hadith with multiple criteria
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function filterHadith(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->filterHadith($filters, $perPage, ['book', 'chapter']);
    }

    /**
     * Create new hadith
     *
     * @param array<string, mixed> $data
     * @return Hadith
     */
    public function createHadith(array $data): Hadith
    {
        return $this->repository->create($data);
    }

    /**
     * Update hadith
     *
     * @param int $id
     * @param array<string, mixed> $data
     * @return bool
     */
    public function updateHadith(int $id, array $data): bool
    {
        return $this->repository->update($id, $data);
    }

    /**
     * Delete hadith
     *
     * @param int $id
     * @return bool
     */
    public function deleteHadith(int $id): bool
    {
        return $this->repository->delete($id);
    }

    /**
     * Attach narrators to hadith
     *
     * @param int $hadithId
     * @param array<int, int> $narratorPositions [narrator_id => position]
     * @return void
     */
    public function attachNarrators(int $hadithId, array $narratorPositions): void
    {
        $hadith = $this->repository->findOrFail($hadithId);
        
        $syncData = [];
        foreach ($narratorPositions as $narratorId => $position) {
            $syncData[$narratorId] = ['position' => $position];
        }

        $hadith->narrators()->sync($syncData);
        $this->repository->clearCache();
    }
}

