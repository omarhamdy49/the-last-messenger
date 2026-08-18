<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Base Service Class
 * Provides common service layer operations
 *
 * Subclasses each hold a narrower repository than RepositoryInterface, but PHP property
 * types are **invariant** — redeclaring `protected CategoryRepositoryInterface $repository`
 * in a child is a fatal error ("Type of X::$repository must be RepositoryInterface"). So
 * the narrowing is expressed for static analysis instead: this class is generic over its
 * repository, and every subclass declares `@extends BaseService<ItsRepositoryInterface>`.
 * That makes the ~98 `$this->repository->…` calls across the subclasses type-checkable
 * without changing a line of runtime behaviour.
 *
 * @template TRepository of RepositoryInterface
 */
abstract class BaseService
{
    /** @var TRepository */
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
     * @throws ModelNotFoundException
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
