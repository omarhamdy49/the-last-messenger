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
     */
    public function __construct(
        HadithRepositoryInterface $repository
    ) {
        $this->repository = $repository;
    }

    /**
     * Get hadith by book with pagination
     */
    public function getHadithByBook(int $bookId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByBook($bookId, $perPage, ['book', 'chapter']);
    }

    /**`
     * Get hadith by chapter
     */
    public function getHadithByChapter(int $chapterId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByChapter($chapterId, $perPage, ['book', 'chapter']);
    }

    /**
     * Get hadith by authenticity grade
     */
    public function getHadithByGrade(HadithGrade $grade, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByGrade($grade, $perPage, ['book', 'chapter']);
    }

    /**
     * Get authentic hadith (sahih)
     */
    public function getSahihHadith(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getSahih($perPage, ['book', 'chapter']);
    }

    /**
     * Get reliable hadith (sahih or hasan)
     */
    public function getReliableHadith(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getReliable($perPage, ['book', 'chapter']);
    }

    /**
     * Search hadith
     */
    public function searchHadith(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->search($query, $perPage, ['book', 'chapter']);
    }

    /**
     * Find hadith by book and hadith number
     *
     * @throws ResourceNotFoundException
     */
    public function findByBookAndNumber(int $bookId, int $hadithNumber): Hadith
    {
        $hadith = $this->repository->findByBookAndNumber($bookId, $hadithNumber, ['book', 'chapter', 'narrators']);

        if (! $hadith) {
            throw new ResourceNotFoundException('Hadith', "{$bookId}:{$hadithNumber}");
        }

        return $hadith;
    }

    /**
     * Get hadith with narrator chain
     *
     * @throws ResourceNotFoundException
     */
    public function getHadithWithNarrators(int $id): Hadith
    {
        $hadith = $this->repository->getWithNarrators($id);

        if (! $hadith) {
            throw new ResourceNotFoundException('Hadith', $id);
        }

        // Increment views
        $this->repository->incrementViews($id);

        return $hadith;
    }

    /**
     * Filter hadith with multiple criteria
     *
     * @param  array<string, mixed>  $filters
     */
    public function filterHadith(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->filterHadith($filters, $perPage, ['book', 'chapter']);
    }

    /**
     * Create new hadith
     *
     * @param  array<string, mixed>  $data
     */
    public function createHadith(array $data): Hadith
    {
        return $this->repository->create($data);
    }

    /**
     * Update hadith
     *
     * @param  array<string, mixed>  $data
     */
    public function updateHadith(int $id, array $data): bool
    {
        return $this->repository->update($id, $data);
    }

    /**
     * Delete hadith
     */
    public function deleteHadith(int $id): bool
    {
        return $this->repository->delete($id);
    }

    /**
     * Attach narrators to hadith
     *
     * @param  array<int, int>  $narratorPositions  [narrator_id => position]
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

    /**
     * Filter hadith by book
     */
    public function filterByBook(int $bookId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByBook($bookId, $perPage, ['book', 'chapter', 'narrators']);
    }

    /**
     * Filter hadith by chapter
     */
    public function filterByChapter(int $chapterId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByChapter($chapterId, $perPage, ['book', 'chapter', 'narrators']);
    }

    /**
     * Filter hadith by grade
     */
    public function filterByGrade(string $grade, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByGrade(HadithGrade::from($grade), $perPage, ['book', 'chapter']);
    }

    /**
     * Filter hadith by narrator
     */
    public function filterByNarrator(int $narratorId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByNarrator($narratorId, $perPage, ['book', 'chapter', 'narrators']);
    }

    /**
     * Filter hadith by date range
     */
    public function filterByDateRange(string $startDate, string $endDate, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByDateRange($startDate, $endDate, $perPage, ['book', 'chapter']);
    }

    /**
     * Get chapters for specific book
     */
    public function getChaptersForBook(int $bookId): \Illuminate\Database\Eloquent\Collection
    {
        return \App\Domain\Hadith\Models\HadithChapter::where('book_id', $bookId)
            ->orderBy('chapter_number')
            ->get(['id', 'book_id', 'chapter_number', 'title']);
    }

    /**
     * Filter hadith with multiple criteria
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->filterHadith($filters, $perPage, ['book', 'chapter', 'narrators']);
    }
}
