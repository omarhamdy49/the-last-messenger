<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Quran\Enums\RevelationPlace;
use App\Domain\Quran\Models\Surah;
use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface SurahRepositoryInterface extends RepositoryInterface
{
    /**
     * Get surah by number (1-114)
     */
    public function getByNumber(int $number): ?Surah;

    /**
     * Get surahs by revelation place
     */
    public function getByRevelationPlace(RevelationPlace|string $place): Collection;

    /**
     * Get surahs by Juz
     */
    public function getByJuz(int $juz): Collection;

    /**
     * Get paginated surahs with filters
     */
    public function paginateWithFilters(
        int $perPage = 15,
        string $search = '',
        string $sortBy = 'number',
        array $filters = []
    ): LengthAwarePaginator;

    /**
     * Count surahs by revelation place
     */
    public function countByRevelation(string $place): int;

    /**
     * Get total verses count across all surahs
     */
    public function getTotalVersesCount(): int;

    /**
     * Search surahs across all languages
     */
    public function search(string $query, int $perPage = 15, ?string $revelationPlace = null): LengthAwarePaginator;

    /**
     * Get average verses per surah
     */
    public function getAverageVersesPerSurah(): float;
}
