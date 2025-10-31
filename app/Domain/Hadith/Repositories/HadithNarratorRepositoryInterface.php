<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Repositories;

use App\Domain\Hadith\Enums\NarratorGrade;
use App\Domain\Hadith\Models\HadithNarrator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Hadith Narrator Repository Interface
 * Defines contract for hadith narrator data access
 */
interface HadithNarratorRepositoryInterface
{
    /**
     * Get all narrators
     *
     * @param  array<string>  $columns
     * @param  array<string>  $relations
     * @return Collection<int, Model>
     */
    public function all(array $columns = ['*'], array $relations = []): Collection;

    /**
     * Find narrator by ID
     *
     * @param  array<string>  $columns
     * @param  array<string>  $relations
     */
    public function find(int $id, array $columns = ['*'], array $relations = []): ?Model;

    /**
     * Find narrator by ID or fail
     *
     * @param  array<string>  $columns
     * @param  array<string>  $relations
     */
    public function findOrFail(int $id, array $columns = ['*'], array $relations = []): Model;

    /**
     * Find narrator by column value
     *
     * @param  array<string>  $columns
     * @param  array<string>  $relations
     */
    public function findBy(string $column, mixed $value, array $columns = ['*'], array $relations = []): ?Model;

    /**
     * Find narrators by column value
     *
     * @param  array<string>  $columns
     * @param  array<string>  $relations
     * @return Collection<int, Model>
     */
    public function findAllBy(string $column, mixed $value, array $columns = ['*'], array $relations = []): Collection;

    /**
     * Count narrators with optional conditions
     *
     * @param  array<string, mixed>  $conditions
     */
    public function count(array $conditions = []): int;

    /**
     * Check if narrator exists
     */
    public function exists(int $id): bool;

    /**
     * Get narrators with eager loading
     *
     * @param  array<string>  $relations
     * @return Collection<int, Model>
     */
    public function with(array $relations): Collection;

    /**
     * Get paginated narrators (standard signature for compatibility)
     *
     * @param  array<string>  $columns
     * @param  array<string>  $relations
     */
    public function paginate(int $perPage = 15, array $columns = ['*'], array $relations = []): LengthAwarePaginator;

    /**
     * Get paginated narrators with filters
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateWithFilters(int $perPage = 15, array $filters = []): LengthAwarePaginator;

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
     * Update narrator by ID (standard signature for compatibility)
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): bool;

    /**
     * Update narrator by model instance
     *
     * @param  array<string, mixed>  $data
     */
    public function updateNarrator(HadithNarrator $narrator, array $data): HadithNarrator;

    /**
     * Delete narrator by ID (standard signature for compatibility)
     */
    public function delete(int $id): bool;

    /**
     * Delete narrator by model instance
     */
    public function deleteNarrator(HadithNarrator $narrator): bool;

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
