<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Quran\Models\TafsirResource;
use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Tafseer Repository Interface
 */
interface TafseerRepositoryInterface extends RepositoryInterface
{
    /**
     * Get tafseer with filters and pagination
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateWithFilters(int $perPage, array $filters): LengthAwarePaginator;

    /**
     * Active tafsir editions (resources) ordered for the reader's source selector.
     *
     * @return Collection<int, TafsirResource>
     */
    public function getActiveResources(): Collection;

    /**
     * One verse's tafsir text for a single edition, or null when absent.
     * Loaded per-verse on demand (no whole-surah blob).
     */
    public function getVerseText(int $verseId, int $resourceId): ?string;

    /**
     * Get tafseer by verse reference and source
     */
    public function getByVerseAndSource(string $verseReference, string $source): ?Model;

    /**
     * Get all tafseer for a specific verse
     *
     * @return Collection<int, Model>
     */
    public function getByVerseReference(string $verseReference): Collection;

    /**
     * Get tafseer by surah
     */
    public function getBySurah(int $surahId, int $perPage): LengthAwarePaginator;

    /**
     * Active tafseer for one surah, paginated, carrying only what a listing row
     * shows. Narrower than getBySurah(): the verse is loaded for its key alone,
     * with no surah behind it.
     */
    public function paginateActiveBySurah(int $surahId, int $perPage): LengthAwarePaginator;

    /**
     * Get tafseer by source
     */
    public function getBySource(string $source, int $perPage): LengthAwarePaginator;

    /**
     * Search tafseer by query
     */
    public function search(string $query, int $perPage): LengthAwarePaginator;

    /**
     * Check if duplicate exists (same verse + source)
     */
    public function checkDuplicate(string $verseReference, string $source, ?int $excludeId = null): bool;

    /**
     * Get available sources
     *
     * @return array<string>
     */
    public function getAvailableSources(): array;

    /**
     * Count unique surahs covered
     */
    public function countUniqueSurahs(): int;

    /**
     * Bulk delete by IDs
     *
     * @param  array<int>  $ids
     */
    public function bulkDelete(array $ids): int;

    /**
     * Get tafseer with verse and surah details
     */
    public function getWithDetails(int $id): ?Model;
}
