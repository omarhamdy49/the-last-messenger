<?php

declare(strict_types=1);

namespace App\Domain\Media\Repositories;

use App\Domain\Media\Models\MediaCategory;
use App\Domain\Media\Models\MediaProgram;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * MediaCategory Repository Implementation
 * Handles all database operations for media categories with optimization and caching
 */
class MediaCategoryRepository extends BaseRepository implements MediaCategoryRepositoryInterface
{
    protected int $cacheTTL = 900; // 15 minutes

    /**
     * {@inheritdoc}
     */
    protected function getModel(): Model
    {
        return new MediaCategory;
    }

    /**
     * {@inheritdoc}
     */
    protected function getCachePrefix(): string
    {
        return 'media_categories';
    }

    /**
     * {@inheritdoc}
     */
    public function paginateWithFilters(int $perPage = 50, string $search = '', array $filters = [], array $with = []): LengthAwarePaginator
    {
        $query = $this->model->withCount('programs')
            ->ordered();

        // Apply search across all languages
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                // Search in name JSON field for all languages
                $q->where('name->ar', 'like', "%{$search}%")
                    ->orWhere('name->en', 'like', "%{$search}%")
                    ->orWhere('name->fr', 'like', "%{$search}%")
                    ->orWhere('name->tr', 'like', "%{$search}%")
                    ->orWhere('name->ur', 'like', "%{$search}%")
                    // Search in description JSON field
                    ->orWhere('description->ar', 'like', "%{$search}%")
                    ->orWhere('description->en', 'like', "%{$search}%")
                    ->orWhere('description->fr', 'like', "%{$search}%")
                    ->orWhere('description->tr', 'like', "%{$search}%")
                    ->orWhere('description->ur', 'like', "%{$search}%");
            });
        }

        // Apply additional filters
        foreach ($filters as $key => $value) {
            if ($value !== null && $value !== '') {
                $query->where($key, $value);
            }
        }

        // Eager load relationships
        if (! empty($with)) {
            $query->with($with);
        }

        return $query->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function countTotalPrograms(): int
    {
        $cacheKey = $this->getCacheKey('countTotalPrograms');

        return Cache::remember($cacheKey, $this->cacheTTL, function () {
            return MediaProgram::count();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getProgramsByCategory(int $categoryId, int $perPage = 50): LengthAwarePaginator
    {
        return MediaProgram::where('category_id', $categoryId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function updateDisplayOrder(array $items): void
    {
        DB::transaction(function () use ($items) {
            foreach ($items as $id => $order) {
                $this->model->where('id', $id)->update(['display_order' => $order]);
            }
        });

        $this->clearCache();
    }

    /**
     * {@inheritdoc}
     */
    public function search(string $query, int $perPage = 50): LengthAwarePaginator
    {
        return $this->paginateWithFilters($perPage, $query);
    }

    /**
     * {@inheritdoc}
     */
    public function getAllOrdered(array $with = []): Collection
    {
        $cacheKey = $this->getCacheKey('allOrdered', compact('with'));

        return Cache::remember($cacheKey, $this->cacheTTL, function () use ($with) {
            $query = $this->model->withCount('programs')
                ->ordered();

            if (! empty($with)) {
                $query->with($with);
            }

            return $query->get();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function findOrFail(int $id, array $columns = ['*'], array $relations = []): Model
    {
        $query = $this->model->withCount('programs');

        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query->findOrFail($id);
    }

    /**
     * {@inheritdoc}
     */
    public function clearCache(): void
    {
        Cache::forget('media_categories:all');
        Cache::forget('media_categories:featured');
    }
}
