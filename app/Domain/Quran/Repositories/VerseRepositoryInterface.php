<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface VerseRepositoryInterface extends RepositoryInterface
{
    /**
     * Get verses by surah
     */
    public function getBySurah(int $surahId): Collection;

    /**
     * Get verse by surah number and verse number
     */
    public function getBySurahAndVerse(int $surahNumber, int $verseNumber): ?\App\Domain\Quran\Models\Verse;

    /**
     * Get verses by Juz
     */
    public function getByJuz(int $juz, int $perPage = 50): LengthAwarePaginator;

    /**
     * Get verses by Hizb (1-60), paginated and grouped by surah order.
     */
    public function getByHizb(int $hizb, int $perPage = 50): LengthAwarePaginator;

    /**
     * Get verses by page
     */
    public function getByPage(int $page): Collection;

    /**
     * Get all Sajda verses
     */
    public function getSajdaVerses(): Collection;

    /**
     * Search in Arabic text
     */
    public function searchArabic(string $query, int $perPage = 20): LengthAwarePaginator;

    /**
     * Search in translations
     */
    public function searchTranslation(string $query, string $language, int $perPage = 20): LengthAwarePaginator;

    /**
     * Get random verse
     */
    public function getRandomVerse(): ?\App\Domain\Quran\Models\Verse;

    /**
     * Get paginated verses with filters
     */
    public function paginateWithFilters(
        int $perPage = 15,
        string $search = '',
        string $sortBy = 'verse_key',
        array $filters = []
    ): LengthAwarePaginator;

    /**
     * Bulk delete verses
     */
    public function bulkDelete(array $ids): int;

    /**
     * Search verses with filters
     */
    public function search(
        string $query,
        int $perPage = 15,
        ?int $surahId = null,
        string $language = 'en'
    ): LengthAwarePaginator;

    /**
     * Count Sajda verses
     */
    public function countSajdaVerses(): int;
}
