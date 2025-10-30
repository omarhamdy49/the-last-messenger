<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Repositories;

use App\Domain\Hadith\Enums\NarratorGrade;
use App\Domain\Hadith\Models\HadithNarrator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Hadith Narrator Repository Interface
 * Defines contract for hadith narrator data access
 */
interface HadithNarratorRepositoryInterface
{
    /**
     * Get paginated narrators with filters
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Find narrator by ID
     */
    public function getById(int $id): ?HadithNarrator;

    /**
     * Search narrators by name or country
     */
    public function search(string $query): Collection;

    /**
     * Get narrators by reliability grade
     */
    public function getByGrade(NarratorGrade $grade): Collection;

    /**
     * Get narrators by era/period
     */
    public function getByEra(string $era): Collection;

    /**
     * Get narrators by country
     */
    public function getByCountry(string $country): Collection;

    /**
     * Get teachers of a narrator
     */
    public function getTeachers(int $narratorId): Collection;

    /**
     * Get students of a narrator
     */
    public function getStudents(int $narratorId): Collection;

    /**
     * Get hadith count for a narrator
     */
    public function getHadithCount(int $narratorId): int;

    /**
     * Count narrators by grade
     *
     * @return array<string, int>
     */
    public function countByGrade(): array;

    /**
     * Count narrators by era
     *
     * @return array<string, int>
     */
    public function countByEra(): array;

    /**
     * Create new narrator
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): HadithNarrator;

    /**
     * Update narrator
     *
     * @param  array<string, mixed>  $data
     */
    public function update(HadithNarrator $narrator, array $data): HadithNarrator;

    /**
     * Delete narrator
     */
    public function delete(HadithNarrator $narrator): bool;

    /**
     * Bulk delete narrators
     *
     * @param  array<int>  $ids
     */
    public function bulkDelete(array $ids): int;

    /**
     * Get active narrators
     */
    public function getActive(): Collection;

    /**
     * Get trustworthy narrators
     */
    public function getTrustworthy(): Collection;

    /**
     * Clear cache
     */
    public function clearCache(): void;
}
