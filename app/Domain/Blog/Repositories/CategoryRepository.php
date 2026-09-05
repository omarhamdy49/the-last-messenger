<?php

declare(strict_types=1);

namespace App\Domain\Blog\Repositories;

use App\Domain\Blog\Enums\CategoryType;
use App\Domain\Blog\Models\Category;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Category Repository Implementation
 * Handles all database operations for categories with optimization and caching
 */
class CategoryRepository extends BaseRepository implements CategoryRepositoryInterface
{
    protected int $cacheTTL = 900; // 15 minutes

    /**
     * {@inheritdoc}
     */
    protected function getModel(): Model
    {
        return new Category;
    }

    /**
     * {@inheritdoc}
     */
    public function paginateWithFilters(int $perPage = 50, string $search = '', array $filters = [], array $with = []): LengthAwarePaginator
    {
        $query = $this->model->query();

        // Eager load relationships
        $defaultRelations = ['parent', 'children'];
        $relations = ! empty($with) ? $with : $defaultRelations;
        $query->with($relations)->withCount('articles');

        // Apply search across all languages in name and description (via translation table)
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                // Search in translation table
                $q->whereHas('translations', function ($sub) use ($search) {
                    $sub->where(function ($inner) use ($search) {
                        $inner->where('name', 'LIKE', "%{$search}%")
                            ->orWhere('description', 'LIKE', "%{$search}%");
                    });
                })
                // Also search slug directly on categories table
                    ->orWhere('slug', 'LIKE', "%{$search}%");
            });
        }

        // Apply filters
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['parent_id'])) {
            if ($filters['parent_id'] === 'null' || $filters['parent_id'] === null) {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $filters['parent_id']);
            }
        }

        // Order by display order, then by name
        $query->orderBy('display_order')->orderBy('id');

        return $query->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function countByStatus(string $status): int
    {
        $cacheKey = $this->getCacheKey('countByStatus', compact('status'));

        return (int) Cache::remember($cacheKey, $this->cacheTTL, function () use ($status) {
            return $this->model->where('status', $status)->count();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function countByType(CategoryType $type): int
    {
        $cacheKey = $this->getCacheKey('countByType', ['type' => $type->value]);

        return (int) Cache::remember($cacheKey, $this->cacheTTL, function () use ($type) {
            return $this->model->where('type', $type)->count();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getRootCategories(): Collection
    {
        $cacheKey = $this->getCacheKey('rootCategories');

        return Cache::remember($cacheKey, $this->cacheTTL, function () {
            return $this->model->root()
                ->with(['children'])
                ->withCount('articles')
                ->orderBy('display_order')
                ->get();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getRootCategoriesExcept(int $excludeId): Collection
    {
        $cacheKey = $this->getCacheKey('rootCategoriesExcept', compact('excludeId'));

        return Cache::remember($cacheKey, $this->cacheTTL, function () use ($excludeId) {
            return $this->model->root()
                ->where('id', '!=', $excludeId)
                ->orderBy('display_order')
                ->get();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function search(string $query, int $perPage = 50, ?string $type = null): LengthAwarePaginator
    {
        $searchQuery = $this->model->query();

        // Search the translations table: categories.name/description were
        // dropped in favour of category_translations, so the old JSON_EXTRACT
        // raw query threw on every admin category search.
        $searchQuery->where(function ($q) use ($query) {
            $q->whereHas('translations', function ($t) use ($query) {
                $t->where('name', 'LIKE', "%{$query}%")
                    ->orWhere('description', 'LIKE', "%{$query}%");
            })->orWhere('slug', 'LIKE', "%{$query}%");
        });

        // Filter by type if provided
        if ($type !== null) {
            $searchQuery->where('type', $type);
        }

        // Eager load relationships
        $searchQuery->with(['parent', 'children'])
            ->withCount('articles')
            ->orderBy('display_order');

        return $searchQuery->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function bulkDelete(array $ids): int
    {
        $count = $this->model->whereIn('id', $ids)->delete();
        $this->clearCache();

        return $count;
    }

    /**
     * {@inheritdoc}
     */
    public function updateDisplayOrder(array $items): void
    {
        DB::transaction(function () use ($items) {
            foreach ($items as $item) {
                $this->model->where('id', $item['id'])
                    ->update(['display_order' => $item['display_order']]);
            }
        });

        $this->clearCache();
    }

    /**
     * {@inheritdoc}
     */
    public function listRoots(): Collection
    {
        $columns = [
            'id', 'slug', 'type', 'status', 'parent_id', 'depth',
            'icon', 'color', 'display_order', 'created_at', 'updated_at',
        ];
        $relationColumns = implode(',', $columns);

        return $this->model->query()
            ->select($columns)
            ->with([
                'translations:id,category_id,locale,name,description',
                'children' => fn ($query) => $query
                    ->select($columns)
                    ->where('status', 'active')
                    ->orderBy('display_order'),
                'children.translations:id,category_id,locale,name,description',
            ])
            ->where('status', 'active')
            ->whereNull('parent_id')
            ->orderBy('display_order')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function findBySlug(string $slug): ?Category
    {
        $columns = [
            'id', 'slug', 'type', 'status', 'parent_id', 'depth',
            'icon', 'color', 'display_order', 'created_at', 'updated_at',
        ];
        $relationColumns = implode(',', $columns);

        return $this->model->query()
            ->select($columns)
            ->with([
                'translations:id,category_id,locale,name,description',
                'children' => fn ($query) => $query
                    ->select($columns)
                    ->where('status', 'active')
                    ->orderBy('display_order'),
                'children.translations:id,category_id,locale,name,description',
                "parent:{$relationColumns}",
                'parent.translations:id,category_id,locale,name,description',
            ])
            ->where('status', 'active')
            ->where('slug', $slug)
            ->first();
    }

    /**
     * Clear cache for all categories
     */
    public function clearCache(): void
    {
        // Bump the version counter first: the real cache keys are versioned
        // hashes from getCacheKey(), so forgetting these five literals alone
        // left cached find/findBy/countByType/getRootCategories results stale
        // for up to 15 minutes after a write.
        parent::clearCache();

        Cache::forget('categories:all');
        Cache::forget('categories:published');
        Cache::forget('categories:by_type');
        Cache::forget('categories:tree');
        Cache::forget('categories:siblings');
    }

    /**
     * Override base create to clear cache
     *
     * {@inheritdoc}
     */
    public function create(array $data): Model
    {
        $model = parent::create($data);
        $this->clearCache();

        return $model;
    }

    /**
     * Override base update to clear cache
     *
     * {@inheritdoc}
     */
    public function update(int $id, array $data): bool
    {
        $result = parent::update($id, $data);

        if ($result) {
            $this->clearCache();
        }

        return $result;
    }

    /**
     * Override base delete to clear cache
     *
     * {@inheritdoc}
     */
    public function delete(int $id): bool
    {
        $result = parent::delete($id);

        if ($result) {
            $this->clearCache();
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function restoreById(int $id): Category
    {
        /** @var Category $category */
        $category = Category::withTrashed()->findOrFail($id);
        $category->restore();
        $this->clearCache();

        return $category;
    }
}
