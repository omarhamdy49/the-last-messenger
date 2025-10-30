<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Base Service Class
 * Provides common service layer operations
 */
abstract class BaseService
{
    protected RepositoryInterface $repository;

    /**
     * Get all records
     *
     * @param  array<string>  $columns
     * @param  array<string>  $relations
     * @return Collection<int, Model>
     */
    public function all(array $columns = ['*'], array $relations = []): Collection
    {
        return $this->repository->all($columns, $relations);
    }

    /**
     * Get paginated records
     *
     * @param  array<string>  $columns
     * @param  array<string>  $relations
     */
    public function paginate(int $perPage = 15, array $columns = ['*'], array $relations = []): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage, $columns, $relations);
    }

    /**
     * Find record by ID
     *
     * @param  array<string>  $relations
     */
    public function find(int $id, array $relations = []): ?Model
    {
        return $this->repository->find($id, ['*'], $relations);
    }

    /**
     * Find record by ID or fail
     *
     * @param  array<string>  $relations
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id, array $relations = []): Model
    {
        return $this->repository->findOrFail($id, ['*'], $relations);
    }

    /**
     * Create new record
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model
    {
        return $this->repository->create($data);
    }

    /**
     * Update record
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): bool
    {
        return $this->repository->update($id, $data);
    }

    /**
     * Delete record
     */
    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }

    /**
     * Count records
     *
     * @param  array<string, mixed>  $conditions
     */
    public function count(array $conditions = []): int
    {
        return $this->repository->count($conditions);
    }

    /**
     * Check if record exists
     */
    public function exists(int $id): bool
    {
        return $this->repository->exists($id);
    }
}
