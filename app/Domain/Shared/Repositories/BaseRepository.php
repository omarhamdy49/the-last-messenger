<?php

declare(strict_types=1);

namespace App\Domain\Shared\Repositories;

use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

/**
 * Base Repository Implementation
 * Provides common CRUD operations with caching support
 */
abstract class BaseRepository implements RepositoryInterface
{
    protected Model $model;
    protected int $cacheTTL = 900; // 15 minutes default
    protected string $cachePrefix = '';

    /**
     * BaseRepository constructor
     */
    public function __construct()
    {
        $this->model = $this->getModel();
        $this->cachePrefix = $this->getCachePrefix();
    }

    /**
     * Get model instance
     *
     * @return Model
     */
    abstract protected function getModel(): Model;

    /**
     * Get cache prefix for this repository
     *
     * @return string
     */
    protected function getCachePrefix(): string
    {
        return strtolower(class_basename($this->model));
    }

    /**
     * {@inheritdoc}
     */
    public function all(array $columns = ['*'], array $relations = []): Collection
    {
        $cacheKey = $this->getCacheKey('all', compact('columns', 'relations'));

        return Cache::remember($cacheKey, $this->cacheTTL, function () use ($columns, $relations) {
            $query = $this->model->select($columns);

            if (!empty($relations)) {
                $query->with($relations);
            }

            return $query->get();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function paginate(int $perPage = 15, array $columns = ['*'], array $relations = []): LengthAwarePaginator
    {
        $query = $this->model->select($columns);

        if (!empty($relations)) {
            $query->with($relations);
        }

        return $query->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function find(int $id, array $columns = ['*'], array $relations = []): ?Model
    {
        $cacheKey = $this->getCacheKey('find', compact('id', 'columns', 'relations'));

        return Cache::remember($cacheKey, $this->cacheTTL, function () use ($id, $columns, $relations) {
            $query = $this->model->select($columns);

            if (!empty($relations)) {
                $query->with($relations);
            }

            return $query->find($id);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function findOrFail(int $id, array $columns = ['*'], array $relations = []): Model
    {
        $query = $this->model->select($columns);

        if (!empty($relations)) {
            $query->with($relations);
        }

        return $query->findOrFail($id);
    }

    /**
     * {@inheritdoc}
     */
    public function findBy(string $column, mixed $value, array $columns = ['*'], array $relations = []): ?Model
    {
        $cacheKey = $this->getCacheKey('findBy', compact('column', 'value', 'columns', 'relations'));

        return Cache::remember($cacheKey, $this->cacheTTL, function () use ($column, $value, $columns, $relations) {
            $query = $this->model->select($columns)->where($column, $value);

            if (!empty($relations)) {
                $query->with($relations);
            }

            return $query->first();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function findAllBy(string $column, mixed $value, array $columns = ['*'], array $relations = []): Collection
    {
        $cacheKey = $this->getCacheKey('findAllBy', compact('column', 'value', 'columns', 'relations'));

        return Cache::remember($cacheKey, $this->cacheTTL, function () use ($column, $value, $columns, $relations) {
            $query = $this->model->select($columns)->where($column, $value);

            if (!empty($relations)) {
                $query->with($relations);
            }

            return $query->get();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): Model
    {
        $model = $this->model->create($data);
        $this->clearCache();

        return $model->fresh();
    }

    /**
     * {@inheritdoc}
     */
    public function update(int $id, array $data): bool
    {
        $model = $this->findOrFail($id);
        $updated = $model->update($data);

        if ($updated) {
            $this->clearCache();
        }

        return $updated;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(int $id): bool
    {
        $model = $this->findOrFail($id);
        $deleted = (bool) $model->delete();

        if ($deleted) {
            $this->clearCache();
        }

        return $deleted;
    }

    /**
     * {@inheritdoc}
     */
    public function count(array $conditions = []): int
    {
        $query = $this->model->query();

        foreach ($conditions as $column => $value) {
            $query->where($column, $value);
        }

        return $query->count();
    }

    /**
     * {@inheritdoc}
     */
    public function exists(int $id): bool
    {
        return $this->model->where('id', $id)->exists();
    }

    /**
     * {@inheritdoc}
     */
    public function with(array $relations): Collection
    {
        return $this->model->with($relations)->get();
    }

    /**
     * {@inheritdoc}
     */
    public function clearCache(): void
    {
        Cache::tags([$this->cachePrefix])->flush();
    }

    /**
     * Generate cache key
     *
     * @param string $method
     * @param array<string, mixed> $parameters
     * @return string
     */
    protected function getCacheKey(string $method, array $parameters = []): string
    {
        $key = $this->cachePrefix . ':' . $method;

        if (!empty($parameters)) {
            $key .= ':' . md5(serialize($parameters));
        }

        return $key;
    }

    /**
     * Get query builder with common optimizations
     *
     * @param array<string> $relations
     * @param array<string> $columns
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function queryWithOptimizations(array $relations = [], array $columns = ['*']): \Illuminate\Database\Eloquent\Builder
    {
        $query = $this->model->select($columns);

        if (!empty($relations)) {
            $query->with($relations);
        }

        return $query;
    }
}

