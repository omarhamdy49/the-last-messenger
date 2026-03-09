<?php

declare(strict_types=1);

namespace App\Domain\Blog\Services;

use App\Domain\Blog\Enums\CategoryType;
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
     * Override create to use createCategory which handles translations
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model
    {
        return $this->createCategory($data);
    }

    /**
     * Override update to use updateCategory which handles translations
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): bool
    {
        $this->updateCategory($id, $data);
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
     *
     * @param  array<string, mixed>  $data
     */
    public function createCategory(array $data): Category
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
        if (!empty($translatableData)) {
            $category->syncTranslations($translatableData);
        }

        return $category;
    }

    /**
     * Update existing category with business logic
     *
     * @param  array<string, mixed>  $data
     */
    public function updateCategory(int $id, array $data): Category
    {
        // Get current category for comparison
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
            if (!empty($translatableData['name'])) {
                $nonTranslatableData['slug'] = $this->generateUniqueSlug(
                    $translatableData['name']['en'] ?? $translatableData['name']['ar'],
                    $id
                );
            }
        }

        // Update non-translatable fields
        if (!empty($nonTranslatableData)) {
            $this->repository->update($id, $nonTranslatableData);
        }

        // Sync translations
        if (!empty($translatableData)) {
            $category->syncTranslations($translatableData);
        }

        /** @var Category */
        return $this->repository->findOrFail($id);
    }

    /**
     * Delete category with validation
     *
     * @throws \Exception If category has children or articles
     */
    public function deleteCategory(int $id): bool
    {
        /** @var Category */
        $category = $this->repository->findOrFail($id);

        // Check if category has children
        if ($category->hasChildren()) {
            throw new \Exception('Cannot delete category with child categories. Please delete or move child categories first.');
        }

        // Check if category has articles
        if ($category->articles()->count() > 0) {
            throw new \Exception('Cannot delete category with articles. Please reassign or delete articles first.');
        }

        return $this->repository->delete($id);
    }

    /**
     * Bulk delete categories with validation
     *
     * @param  array<int>  $ids
     * @return int Number of deleted records
     *
     * @throws \Exception If any category has children or articles
     */
    public function bulkDelete(array $ids): int
    {
        // Validate all categories before deletion
        foreach ($ids as $id) {
            /** @var Category */
            $category = $this->repository->findOrFail($id);

            if ($category->hasChildren()) {
                throw new \Exception("Cannot delete category '{$category->name['en']}' (ID: {$id}) because it has child categories.");
            }

            if ($category->articles()->count() > 0) {
                throw new \Exception("Cannot delete category '{$category->name['en']}' (ID: {$id}) because it has articles.");
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
     * @throws \Exception If circular reference detected
     */
    protected function validateParentId(int $parentId, ?int $categoryId = null): void
    {
        // Cannot set parent to self
        if ($categoryId !== null && $parentId === $categoryId) {
            throw new \Exception('Category cannot be its own parent.');
        }

        // Check if parent exists
        $parent = $this->repository->find($parentId);
        if ($parent === null) {
            throw new \Exception("Parent category with ID {$parentId} does not exist.");
        }

        // If updating existing category, check for circular reference
        if ($categoryId !== null) {
            $this->checkCircularReference($parentId, $categoryId);
        }
    }

    /**
     * Check for circular reference in parent-child relationship
     *
     * @throws \Exception If circular reference detected
     */
    protected function checkCircularReference(int $parentId, int $categoryId): void
    {
        $currentParentId = $parentId;
        $visited = [];

        while ($currentParentId !== null) {
            // Circular reference detected
            if ($currentParentId === $categoryId) {
                throw new \Exception('Circular reference detected. A category cannot be a child of its own descendant.');
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
}
