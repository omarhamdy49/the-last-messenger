<?php

declare(strict_types=1);

namespace App\Domain\Blog\Services;

use App\Domain\Blog\DTOs\CreateCategoryData;
use App\Domain\Blog\DTOs\UpdateCategoryData;
use App\Domain\Blog\Enums\CategoryType;
use App\Domain\Blog\Exceptions\CategoryHasArticlesException;
use App\Domain\Blog\Exceptions\CategoryHasChildrenException;
use App\Domain\Blog\Exceptions\CircularCategoryException;
use App\Domain\Blog\Models\Category;
use App\Domain\Blog\Repositories\CategoryRepositoryInterface;
use App\Domain\Shared\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Category Service
 * Handles business logic for categories
 */
class CategoryService extends BaseService
{
    /**
     * Constructor
     */
    public function __construct(CategoryRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Override create to use createCategory which handles translations.
     * Accepts a raw array for internal callers (e.g. duplicate action) or a
     * CreateCategoryData DTO for controller-driven creates.
     *
     * @param  array<string, mixed>|CreateCategoryData  $data
     */
    public function create(array|CreateCategoryData $data): Model
    {
        if ($data instanceof CreateCategoryData) {
            return $this->createCategory($data);
        }

        // Legacy array path — used by internal callers such as the duplicate action.
        return $this->createCategoryFromArray($data);
    }

    /**
     * Override update to use updateCategory which handles translations.
     * Accepts a raw array for internal callers or an UpdateCategoryData DTO.
     *
     * @param  array<string, mixed>|UpdateCategoryData  $data
     */
    public function update(int $id, array|UpdateCategoryData $data): bool
    {
        if ($data instanceof UpdateCategoryData) {
            $this->updateCategory($id, $data);
        } else {
            $this->updateCategoryFromArray($id, $data);
        }

        return true;
    }

    /**
     * Get paginated categories with optional search and filters
     * Note: This method has different signature than BaseService::paginate()
     * to support additional search and filter parameters
     *
     * @param  array<string, mixed>  $filters  Additional filters (type, parent_id, etc.)
     * @param  array<string>  $with  Relations to eager load
     */
    public function paginateWithFilters(int $perPage = 50, string $search = '', array $filters = [], array $with = []): LengthAwarePaginator
    {
        /** @var CategoryRepositoryInterface */
        $repo = $this->repository;

        return $repo->paginateWithFilters($perPage, $search, $filters, $with);
    }

    /**
     * Create new category with business logic
     */
    public function createCategory(CreateCategoryData $data): Category
    {
        $translatableData = ['name' => $data->name, 'description' => $data->description];
        $nonTranslatableData = [
            'type' => $data->type,
            'slug' => $data->slug,
            'parent_id' => $data->parentId,
            'icon' => $data->icon,
            'color' => $data->color,
            'display_order' => $data->displayOrder,
        ];

        // Generate slug if not provided
        if (empty($nonTranslatableData['slug'])) {
            $titleForSlug = $data->name['en'] ?? $data->name['ar'] ?? 'category';
            $nonTranslatableData['slug'] = $this->generateUniqueSlug($titleForSlug);
        }

        // Set default display order if not provided
        if (! isset($nonTranslatableData['display_order'])) {
            $nonTranslatableData['display_order'] = $this->getNextDisplayOrder($nonTranslatableData['parent_id'] ?? null);
        }

        // Validate parent_id to prevent circular references
        if (! empty($nonTranslatableData['parent_id'])) {
            $this->validateParentId($nonTranslatableData['parent_id']);
        }

        /** @var Category */
        $category = $this->repository->create($nonTranslatableData);

        // Sync translations
        if (! empty($translatableData)) {
            $category->syncTranslations($translatableData);
        }

        return $category;
    }

    /**
     * Create new category from a raw array (legacy/internal path, e.g. duplicate action).
     *
     * @param  array<string, mixed>  $data
     */
    protected function createCategoryFromArray(array $data): Category
    {
        // Separate translatable fields
        $translatableFields = ['name', 'description'];
        $translatableData = [];
        $nonTranslatableData = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $translatableFields)) {
                $translatableData[$key] = $value;
            } else {
                $nonTranslatableData[$key] = $value;
            }
        }

        // Generate slug if not provided
        if (empty($nonTranslatableData['slug'])) {
            $titleForSlug = $translatableData['name']['en'] ?? $translatableData['name']['ar'] ?? 'category';
            $nonTranslatableData['slug'] = $this->generateUniqueSlug($titleForSlug);
        }

        // Set default display order if not provided
        if (! isset($nonTranslatableData['display_order'])) {
            $nonTranslatableData['display_order'] = $this->getNextDisplayOrder($nonTranslatableData['parent_id'] ?? null);
        }

        // Validate parent_id to prevent circular references
        if (! empty($nonTranslatableData['parent_id'])) {
            $this->validateParentId($nonTranslatableData['parent_id']);
        }

        /** @var Category */
        $category = $this->repository->create($nonTranslatableData);

        // Sync translations
        if (! empty($translatableData)) {
            $category->syncTranslations($translatableData);
        }

        return $category;
    }

    /**
     * Update existing category with business logic
     */
    public function updateCategory(int $id, UpdateCategoryData $data): Category
    {
        // Get current category for comparison
        /** @var Category */
        $category = $this->repository->findOrFail($id, with: ['translations']);

        $translatableData = ['name' => $data->name, 'description' => $data->description];
        $nonTranslatableData = [
            'type' => $data->type,
            'slug' => $data->slug,
            'parent_id' => $data->parentId,
            'icon' => $data->icon,
            'color' => $data->color,
            'display_order' => $data->displayOrder,
        ];

        // Validate parent_id to prevent circular references
        if ($data->parentId !== null) {
            $this->validateParentId($data->parentId, $id);
        }

        // Auto-generate slug from English name if slug is empty
        if (empty($nonTranslatableData['slug'])) {
            if (! empty($data->name)) {
                $nonTranslatableData['slug'] = $this->generateUniqueSlug(
                    $data->name['en'] ?? $data->name['ar'],
                    $id
                );
            }
        }

        // Update non-translatable fields
        if (! empty($nonTranslatableData)) {
            $this->repository->update($id, $nonTranslatableData);
        }

        // Sync translations
        if (! empty($translatableData)) {
            $category->syncTranslations($translatableData);
        }

        /** @var Category */
        return $this->repository->findOrFail($id);
    }

    /**
     * Update existing category from a raw array (legacy/internal path).
     *
     * @param  array<string, mixed>  $data
     */
    protected function updateCategoryFromArray(int $id, array $data): Category
    {
        /** @var Category */
        $category = $this->repository->findOrFail($id, with: ['translations']);

        // Separate translatable fields
        $translatableFields = ['name', 'description'];
        $translatableData = [];
        $nonTranslatableData = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $translatableFields)) {
                $translatableData[$key] = $value;
            } else {
                $nonTranslatableData[$key] = $value;
            }
        }

        // Validate parent_id to prevent circular references
        if (isset($nonTranslatableData['parent_id']) && $nonTranslatableData['parent_id'] !== null) {
            $this->validateParentId($nonTranslatableData['parent_id'], $id);
        }

        // Auto-generate slug from English name if slug is empty
        if (empty($nonTranslatableData['slug'])) {
            if (! empty($translatableData['name'])) {
                $nonTranslatableData['slug'] = $this->generateUniqueSlug(
                    $translatableData['name']['en'] ?? $translatableData['name']['ar'],
                    $id
                );
            }
        }

        // Update non-translatable fields
        if (! empty($nonTranslatableData)) {
            $this->repository->update($id, $nonTranslatableData);
        }

        // Sync translations
        if (! empty($translatableData)) {
            $category->syncTranslations($translatableData);
        }

        /** @var Category */
        return $this->repository->findOrFail($id);
    }

    /**
     * Delete category with validation
     *
     * @throws CategoryHasChildrenException
     * @throws CategoryHasArticlesException
     */
    public function deleteCategory(int $id): bool
    {
        /** @var Category */
        $category = $this->repository->findOrFail($id);

        // Check if category has children
        if ($category->hasChildren()) {
            throw CategoryHasChildrenException::forDelete($id);
        }

        // Check if category has articles
        if ($category->articles()->count() > 0) {
            throw CategoryHasArticlesException::forDelete($id);
        }

        return $this->repository->delete($id);
    }

    /**
     * Bulk delete categories with validation
     *
     * @param  array<int>  $ids
     * @return int Number of deleted records
     *
     * @throws CategoryHasChildrenException
     * @throws CategoryHasArticlesException
     */
    public function bulkDelete(array $ids): int
    {
        // Validate all categories before deletion
        foreach ($ids as $id) {
            /** @var Category */
            $category = $this->repository->findOrFail($id);

            if ($category->hasChildren()) {
                throw CategoryHasChildrenException::forDeleteWithName($category->name['en'] ?? '', $id);
            }

            if ($category->articles()->count() > 0) {
                throw CategoryHasArticlesException::forDeleteWithName($category->name['en'] ?? '', $id);
            }
        }

        return $this->repository->bulkDelete($ids);
    }

    /**
     * Find category by ID or fail with default relations
     * Override to add default relations specific to categories
     *
     * @param  array<string>  $relations
     */
    public function findOrFail(int $id, array $relations = ['parent', 'children', 'articles']): Category
    {
        /** @var Category */
        return $this->repository->findOrFail($id, ['*'], $relations);
    }

    /**
     * Get total count of categories
     *
     * @param  array<string, mixed>  $conditions
     */
    public function count(array $conditions = []): int
    {
        return $this->repository->count($conditions);
    }

    /**
     * Count categories by status
     */
    public function countByStatus(string $status): int
    {
        return $this->repository->countByStatus($status);
    }

    /**
     * Count categories by type
     */
    public function countByType(CategoryType $type): int
    {
        return $this->repository->countByType($type);
    }

    /**
     * Get root categories (categories with no parent)
     */
    public function getRootCategories(): Collection
    {
        return $this->repository->getRootCategories();
    }

    /**
     * Get root categories excluding specific category (for parent selector)
     * Used in edit forms to prevent circular references
     */
    public function getRootCategoriesExcept(int $excludeId): Collection
    {
        return $this->repository->getRootCategoriesExcept($excludeId);
    }

    /**
     * Reorder categories by updating display_order
     *
     * @param  array<array{id: int, display_order: int}>  $items
     */
    public function reorder(array $items): void
    {
        $this->repository->updateDisplayOrder($items);
    }

    /**
     * Search categories across all languages
     *
     * @param  string|null  $type  Filter by category type (blog, media, timeline)
     */
    public function search(string $query, int $perPage = 50, ?string $type = null): LengthAwarePaginator
    {
        return $this->repository->search($query, $perPage, $type);
    }

    /**
     * Generate unique slug from text
     */
    protected function generateUniqueSlug(string $text, ?int $excludeId = null): string
    {
        $slug = Str::slug($text);
        $originalSlug = $slug;
        $counter = 1;

        while (true) {
            $query = $this->repository->findBy('slug', $slug);

            // If no conflict or conflict is with the same record being updated
            if ($query === null || ($excludeId !== null && $query->id === $excludeId)) {
                break;
            }

            $slug = $originalSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Get next display order for new category
     */
    protected function getNextDisplayOrder(?int $parentId): int
    {
        $query = $this->repository->findAllBy('parent_id', $parentId);
        $maxOrder = $query->max('display_order');

        return ($maxOrder ?? 0) + 1;
    }

    /**
     * Validate parent_id to prevent circular references
     *
     * @throws CircularCategoryException
     */
    protected function validateParentId(int $parentId, ?int $categoryId = null): void
    {
        // Cannot set parent to self
        if ($categoryId !== null && $parentId === $categoryId) {
            throw CircularCategoryException::selfParent($categoryId);
        }

        // Check if parent exists
        $parent = $this->repository->find($parentId);
        if ($parent === null) {
            throw CircularCategoryException::parentNotFound($parentId);
        }

        // If updating existing category, check for circular reference
        if ($categoryId !== null) {
            $this->checkCircularReference($parentId, $categoryId);
        }
    }

    /**
     * Check for circular reference in parent-child relationship
     *
     * @throws CircularCategoryException
     */
    protected function checkCircularReference(int $parentId, int $categoryId): void
    {
        $currentParentId = $parentId;
        $visited = [];

        while ($currentParentId !== null) {
            // Circular reference detected
            if ($currentParentId === $categoryId) {
                throw CircularCategoryException::descendantLoop($categoryId, $parentId);
            }

            // Prevent infinite loop
            if (in_array($currentParentId, $visited)) {
                break;
            }

            $visited[] = $currentParentId;

            $parent = $this->repository->find($currentParentId);
            $currentParentId = $parent?->parent_id;
        }
    }

    /**
     * Restore a soft-deleted category.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function restoreById(int $id): \App\Domain\Blog\Models\Category
    {
        return $this->repository->restoreById($id);
    }
}
