<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Base Repository Interface
 * Defines standard CRUD operations for all repositories
 */
interface RepositoryInterface
{
    /**
     * Get all records
     *
     * @param  array<string>  $columns
     * @param  array<string>  $relations
     * @return Collection<int, Model>
     */
    public function all(array $columns = ['*'], array $relations = []): Collection;

    /**
     * Get paginated records
     *
     * @param  array<string>  $columns
     * @param  array<string>  $relations
     */
    public function paginate(int $perPage = 15, array $columns = ['*'], array $relations = []): LengthAwarePaginator;

    /**
     * Find record by ID
     *
     * @param  array<string>  $columns
     * @param  array<string>  $relations
     */
    public function find(int $id, array $columns = ['*'], array $relations = []): ?Model;

    /**
     * Find record by ID or fail
     *
     * @param  array<string>  $columns
     * @param  array<string>  $relations
     *
     * @throws ModelNotFoundException
     */
    public function findOrFail(int $id, array $columns = ['*'], array $relations = []): Model;

    /**
     * Find record by column value
     *
     * @param  array<string>  $columns
     * @param  array<string>  $relations
     */
    public function findBy(string $column, mixed $value, array $columns = ['*'], array $relations = []): ?Model;

    /**
     * Find records by column value
     *
     * @param  array<string>  $columns
     * @param  array<string>  $relations
     * @return Collection<int, Model>
     */
    public function findAllBy(string $column, mixed $value, array $columns = ['*'], array $relations = []): Collection;

    /**
     * Create new record
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model;

    /**
     * Update record by ID
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete record by ID
     */
    public function delete(int $id): bool;

    /**
     * Count records with optional conditions
     *
     * @param  array<string, mixed>  $conditions
     */
    public function count(array $conditions = []): int;

    /**
     * Check if record exists
     */
    public function exists(int $id): bool;

    /**
     * Get records with eager loading
     *
     * @param  array<string>  $relations
     * @return Collection<int, Model>
     */
    public function with(array $relations): Collection;

    /**
     * Clear repository cache
     */
    public function clearCache(): void;
}
