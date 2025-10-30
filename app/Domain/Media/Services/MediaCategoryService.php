<?php

declare(strict_types=1);

namespace App\Domain\Media\Services;

use App\Domain\Media\Models\MediaCategory;
use App\Domain\Media\Repositories\MediaCategoryRepositoryInterface;
use App\Domain\Shared\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * MediaCategory Service
 * Handles business logic for media categories
 */
class MediaCategoryService extends BaseService
{
    /**
     * Constructor
     */
    public function __construct(
        MediaCategoryRepositoryInterface $repository
    ) {
        $this->repository = $repository;
    }

    /**
     * Get paginated categories with filters
     *
     * @param  array<string, mixed>  $filters
     * @param  array<string>  $with
     */
    public function paginateWithFilters(int $perPage = 50, string $search = '', array $filters = [], array $with = []): LengthAwarePaginator
    {
        return $this->repository->paginateWithFilters($perPage, $search, $filters, $with);
    }

    /**
     * Create new category
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): MediaCategory
    {
        // If display_order not provided, set to next available position
        if (! isset($data['display_order'])) {
            $data['display_order'] = $this->getNextDisplayOrder();
        }

        return $this->repository->create($data);
    }

    /**
     * Update category and return updated model
     *
     * @param  array<string, mixed>  $data
     */
    public function updateAndReturn(int $id, array $data): MediaCategory
    {
        $this->repository->update($id, $data);

        return $this->repository->findOrFail($id);
    }

    /**
     * Delete category
     */
    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }

    /**
     * Bulk delete categories
     *
     * @param  array<int>  $ids
     */
    public function bulkDelete(array $ids): int
    {
        $count = 0;

        foreach ($ids as $id) {
            if ($this->repository->delete($id)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Find category by ID or fail
     *
     * @param  array<string>  $relations
     */
    public function findOrFail(int $id, array $relations = []): MediaCategory
    {
        return $this->repository->findOrFail($id, ['*'], $relations);
    }

    /**
     * Count total programs across all categories
     */
    public function countTotalPrograms(): int
    {
        return $this->repository->countTotalPrograms();
    }

    /**
     * Get programs by category with pagination
     */
    public function getProgramsByCategory(int $categoryId, int $perPage = 50): LengthAwarePaginator
    {
        return $this->repository->getProgramsByCategory($categoryId, $perPage);
    }

    /**
     * Reorder categories
     *
     * @param  array<array{id: int, display_order: int}>  $items
     */
    public function reorder(array $items): void
    {
        $reorderData = [];

        foreach ($items as $item) {
            $reorderData[$item['id']] = $item['display_order'];
        }

        $this->repository->updateDisplayOrder($reorderData);
    }

    /**
     * Search categories
     */
    public function search(string $query, int $perPage = 50): LengthAwarePaginator
    {
        return $this->repository->search($query, $perPage);
    }

    /**
     * Get all categories ordered by display order
     *
     * @param  array<string>  $with
     */
    public function getAllOrdered(array $with = []): Collection
    {
        return $this->repository->getAllOrdered($with);
    }

    /**
     * Get next available display order
     */
    protected function getNextDisplayOrder(): int
    {
        $maxOrder = MediaCategory::max('display_order');

        return $maxOrder ? $maxOrder + 1 : 1;
    }
}
