<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Repositories\VerseRepositoryInterface;
use App\Domain\Shared\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Verse Service
 * Handles business logic for Quran verses (ayahs)
 */
class VerseService extends BaseService
{
    /**
     * Constructor
     */
    public function __construct(VerseRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get paginated verses with optional search and filters
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateWithFilters(
        int $perPage = 15,
        string $search = '',
        string $sortBy = 'verse_key',
        array $filters = []
    ): LengthAwarePaginator {
        /** @var VerseRepositoryInterface */
        $repo = $this->repository;

        return $repo->paginateWithFilters($perPage, $search, $sortBy, $filters);
    }

    /**
     * Create new verse with business logic
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Verse
    {
        // Calculate verse_key if not provided
        if (! isset($data['verse_key']) || $data['verse_key'] === 0) {
            $data['verse_key'] = $this->calculateVerseKey($data['surah_id'], $data['verse_number']);
        }

        /** @var Verse */
        return $this->repository->create($data);
    }

    /**
     * Update existing verse (BaseService signature compatibility)
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): bool
    {
        // Recalculate verse_key if surah or verse number changed
        if (isset($data['surah_id']) || isset($data['verse_number'])) {
            $verse = $this->repository->findOrFail($id);
            $surahId = $data['surah_id'] ?? $verse->surah_id;
            $verseNumber = $data['verse_number'] ?? $verse->verse_number;

            $data['verse_key'] = $this->calculateVerseKey($surahId, $verseNumber);
        }

        return parent::update($id, $data);
    }

    /**
     * Update existing verse and return updated model
     *
     * @param  array<string, mixed>  $data
     */
    public function updateVerse(int $id, array $data): Verse
    {
        // Recalculate verse_key if surah or verse number changed
        if (isset($data['surah_id']) || isset($data['verse_number'])) {
            $verse = $this->repository->findOrFail($id);
            $surahId = $data['surah_id'] ?? $verse->surah_id;
            $verseNumber = $data['verse_number'] ?? $verse->verse_number;

            $data['verse_key'] = $this->calculateVerseKey($surahId, $verseNumber);
        }

        $this->repository->update($id, $data);

        /** @var Verse */
        return $this->repository->findOrFail($id);
    }

    /**
     * Get verse with all relations
     */
    public function getWithRelations(int $id): Verse
    {
        /** @var Verse */
        return $this->repository->findOrFail($id, ['*'], ['surah', 'tafsirs']);
    }

    /**
     * Bulk delete verses
     *
     * @param  array<int>  $ids
     * @return int Number of deleted records
     */
    public function bulkDelete(array $ids): int
    {
        return $this->repository->bulkDelete($ids);
    }

    /**
     * Search verses across text and translations
     */
    public function search(
        string $query,
        int $perPage = 15,
        ?int $surahId = null,
        string $language = 'en'
    ): LengthAwarePaginator {
        /** @var VerseRepositoryInterface */
        $repo = $this->repository;

        return $repo->search($query, $perPage, $surahId, $language);
    }

    /**
     * Count verses
     *
     * @param  array<string, mixed>  $conditions
     */
    public function count(array $conditions = []): int
    {
        return $this->repository->count($conditions);
    }

    /**
     * Count Sajda verses
     */
    public function countSajdaVerses(): int
    {
        /** @var VerseRepositoryInterface */
        $repo = $this->repository;

        return $repo->countSajdaVerses();
    }

    /**
     * Get verses by surah
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getBySurah(int $surahId)
    {
        return $this->repository->getBySurah($surahId);
    }

    /**
     * Get verse by surah and verse number
     */
    public function getBySurahAndVerse(int $surahNumber, int $verseNumber): ?Verse
    {
        return $this->repository->getBySurahAndVerse($surahNumber, $verseNumber);
    }

    /**
     * Get verses by Juz
     */
    public function getByJuz(int $juz, int $perPage = 50): LengthAwarePaginator
    {
        return $this->repository->getByJuz($juz, $perPage);
    }

    /**
     * Get verses by page
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByPage(int $page)
    {
        return $this->repository->getByPage($page);
    }

    /**
     * Calculate verse_key (cumulative verse number across entire Quran)
     * This is a simplified calculation - actual implementation may need adjustment
     */
    protected function calculateVerseKey(int $surahId, int $verseNumber): int
    {
        // Get all verses count before this surah
        $versesBeforeSurah = \App\Domain\Quran\Models\Surah::where('id', '<', $surahId)
            ->sum('verses_count');

        return (int) $versesBeforeSurah + $verseNumber;
    }
}
