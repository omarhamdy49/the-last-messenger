<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Blog\Enums\CategoryType;
use App\Domain\Blog\Services\CategoryService;
use App\Domain\Shared\Services\AdminLoggerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin Category Controller
 * Handles category management for blog, media, and timeline content
 */
class CategoryController extends Controller
{
    /**
     * Constructor with dependency injection
     */
    public function __construct(
        protected CategoryService $categoryService,
        protected AdminLoggerService $logger
    ) {}

    /**
     * Display paginated list of categories
     *
     * GET /su/portal/v1/admin/categories
     *
     * @param Request $request
     * @return View|JsonResponse
     */
    public function index(Request $request): View|JsonResponse
    {
        // Get pagination and search parameters
        $perPage = $request->integer('per_page', 50);
        $search = $request->string('search')->trim()->toString();

        // Get filters from request
        $filters = $request->only(['type', 'parent_id']);

        // Fetch paginated data with filters applied
        $categories = $this->categoryService->paginateWithFilters(
            perPage: $perPage,
            search: $search,
            filters: $filters,
            with: ['parent', 'children']
        );

        // Get statistics for stats cards
        $stats = [
            'total' => $this->categoryService->count(),
            'blog' => $this->categoryService->countByType(CategoryType::BLOG),
            'media' => $this->categoryService->countByType(CategoryType::MEDIA),
            'timeline' => $this->categoryService->countByType(CategoryType::TIMELINE),
        ];

        // Return JSON for AJAX requests, HTML for page loads
        if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true,
                'data' => $categories->items(),
                'stats' => $stats,
                'meta' => [
                    'current_page' => $categories->currentPage(),
                    'per_page' => $categories->perPage(),
                    'total' => $categories->total(),
                    'last_page' => $categories->lastPage(),
                ]
            ]);
        }

        return view('admin.categories.index', compact('categories', 'stats'));
    }

    /**
     * Show create form
     *
     * GET /su/portal/v1/admin/categories/create
     *
     * @return View
     */
    public function create(): View
    {
        // Get all root categories for parent selection
        $rootCategories = $this->categoryService->getRootCategories();

        return view('admin.categories.create', compact('rootCategories'));
    }

    /**
     * Store new category
     *
     * POST /su/portal/v1/admin/categories
     *
     * @param StoreCategoryRequest $request
     * @return RedirectResponse
     */
    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        try {
            $category = $this->categoryService->create($request->validated());

            // Log the creation
            $this->logger->logCreate($category, $request->user());

            return redirect()
                ->route('admin.categories.index')
                ->with('success', 'Category created successfully');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to create category: ' . $e->getMessage());
        }
    }

    /**
     * Show edit form
     *
     * GET /su/portal/v1/admin/categories/{id}/edit
     *
     * @param int $id
     * @return View
     */
    public function edit(int $id): View
    {
        $category = $this->categoryService->findOrFail($id, ['parent', 'children', 'articles']);

        // Get all root categories for parent selection (excluding self and descendants)
        $rootCategories = $this->categoryService->getRootCategoriesExcept($id);

        // Get article count for warning
        $articlesCount = $category->articles()->count();

        return view('admin.categories.edit', compact('category', 'rootCategories', 'articlesCount'));
    }

    /**
     * Update category
     *
     * PUT/PATCH /su/portal/v1/admin/categories/{id}
     *
     * @param UpdateCategoryRequest $request
     * @param int $id
     * @return RedirectResponse
     */
    public function update(UpdateCategoryRequest $request, int $id): RedirectResponse
    {
        try {
            // Get old values before update
            $oldCategory = $this->categoryService->findOrFail($id);
            $oldValues = $oldCategory->toArray();

            // Perform update using updateCategory (not update) to trigger business logic
            $category = $this->categoryService->updateCategory($id, $request->validated());

            // Log the update with diff
            $this->logger->logUpdate($category, $oldValues, $category->toArray(), $request->user());

            return redirect()
                ->route('admin.categories.index')
                ->with('success', 'Category updated successfully');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to update category: ' . $e->getMessage());
        }
    }

    /**
     * Show category details
     *
     * GET /su/portal/v1/admin/categories/{id}
     *
     * @param int $id
     * @return View
     */
    public function show(int $id): View
    {
        $category = $this->categoryService->findOrFail($id, ['parent', 'children', 'articles']);

        // Get statistics
        $stats = [
            'children_count' => $category->children()->count(),
            'articles_count' => $category->articles()->count(),
        ];

        return view('admin.categories.show', compact('category', 'stats'));
    }

    /**
     * Delete category
     *
     * DELETE /su/portal/v1/admin/categories/{id}
     *
     * @param int $id
     * @return RedirectResponse
     */
    public function destroy(int $id): RedirectResponse
    {
        try {
            // Get model before deletion
            $category = $this->categoryService->findOrFail($id);

            // Check if category has children or articles
            if ($category->hasChildren()) {
                return redirect()
                    ->back()
                    ->with('error', 'Cannot delete category with child categories. Please delete children first.');
            }

            $articlesCount = $category->articles()->count();
            if ($articlesCount > 0) {
                return redirect()
                    ->back()
                    ->with('error', "Cannot delete category with {$articlesCount} articles. Please reassign articles first.");
            }

            // Perform deletion
            $this->categoryService->delete($id);

            // Log the deletion
            $this->logger->logDelete($category, request()->user());

            return redirect()
                ->route('admin.categories.index')
                ->with('success', 'Category deleted successfully');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to delete category: ' . $e->getMessage());
        }
    }

    /**
     * Bulk delete multiple categories
     *
     * POST /su/portal/v1/admin/categories/bulk-delete
     *
     * @param Request $request
     * @return RedirectResponse|JsonResponse
     */
    public function bulkDelete(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:categories,id'],
        ]);

        $ids = $request->input('ids');
        $deleted = 0;
        $failed = 0;

        foreach ($ids as $id) {
            try {
                $category = $this->categoryService->findOrFail($id);

                // Skip if has children or articles
                if ($category->hasChildren() || $category->articles()->count() > 0) {
                    $failed++;
                    continue;
                }

                $this->categoryService->delete($id);
                $deleted++;
            } catch (\Exception $e) {
                $failed++;
            }
        }

        // Log bulk action
        $this->logger->logBulkAction(
            'bulk_delete',
            \App\Domain\Blog\Models\Category::class,
            $ids,
            $request->user()
        );

        $message = "Deleted {$deleted} categories successfully";
        if ($failed > 0) {
            $message .= " ({$failed} failed)";
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'deleted' => $deleted,
                'failed' => $failed,
            ]);
        }

        return redirect()
            ->route('admin.categories.index')
            ->with('success', $message);
    }

    /**
     * Reorder categories (for drag-drop functionality)
     *
     * POST /su/portal/v1/admin/categories/reorder
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer', 'exists:categories,id'],
            'items.*.order' => ['required', 'integer', 'min:0'],
        ]);

        try {
            $items = $request->input('items');
            $this->categoryService->reorder($items);

            return response()->json([
                'success' => true,
                'message' => 'Categories reordered successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder categories',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Search categories (AJAX endpoint)
     *
     * GET /su/portal/v1/admin/categories/search
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'type' => ['nullable', 'string', 'in:blog,media,timeline'],
        ]);

        $query = $request->input('q');
        $perPage = $request->integer('per_page', 15);
        $type = $request->input('type');

        $categories = $this->categoryService->search($query, $perPage, $type);

        return response()->json([
            'success' => true,
            'data' => $categories->items(),
            'meta' => [
                'current_page' => $categories->currentPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
                'last_page' => $categories->lastPage(),
            ],
        ]);
    }

    /**
     * Get category tree as JSON (for hierarchical display)
     *
     * GET /su/portal/v1/admin/categories/tree
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function tree(Request $request): JsonResponse
    {
        $language = $request->input('language', 'ar');
        $type = $request->input('type');

        $tree = \App\Domain\Blog\Models\Category::getTree(null, $language);

        if ($type) {
            $tree = array_filter($tree, fn($cat) => $cat['type'] === $type);
        }

        return response()->json([
            'success' => true,
            'data' => $tree,
        ]);
    }

    /**
     * Restore soft-deleted category
     *
     * POST /su/portal/v1/admin/categories/{id}/restore
     *
     * @param int $id
     * @return RedirectResponse|JsonResponse
     */
    public function restore(Request $request, int $id): RedirectResponse|JsonResponse
    {
        try {
            $category = \App\Domain\Blog\Models\Category::withTrashed()->findOrFail($id);
            $category->restore();

            // Log the restoration
            $this->logger->logRestore($category, $request->user());

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Category restored successfully',
                    'data' => $category,
                ]);
            }

            return redirect()
                ->route('admin.categories.index')
                ->with('success', 'Category restored successfully');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to restore category',
                    'error' => $e->getMessage(),
                ], 422);
            }

            return redirect()
                ->back()
                ->with('error', 'Failed to restore category: ' . $e->getMessage());
        }
    }

    /**
     * Duplicate/clone an existing category
     *
     * POST /su/portal/v1/admin/categories/{id}/duplicate
     *
     * @param int $id
     * @return RedirectResponse|JsonResponse
     */
    public function duplicate(Request $request, int $id): RedirectResponse|JsonResponse
    {
        try {
            $originalCategory = $this->categoryService->findOrFail($id);

            // Create duplicate data
            $duplicateData = [
                'name' => $originalCategory->getNameTranslations(),
                'description' => $originalCategory->getDescriptionTranslations(),
                'type' => $originalCategory->type->value,
                'status' => 'inactive', // Set duplicates as inactive by default
                'parent_id' => $originalCategory->parent_id,
                'icon' => $originalCategory->icon,
                'color' => $originalCategory->color,
                'display_order' => $originalCategory->display_order + 1,
            ];

            // Add suffix to names to indicate it's a copy
            foreach ($duplicateData['name'] as $lang => $name) {
                if ($name) {
                    $duplicateData['name'][$lang] = $name . ' (Copy)';
                }
            }

            $newCategory = $this->categoryService->create($duplicateData);

            // Log the duplication
            $this->logger->logCreate($newCategory, $request->user());

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Category duplicated successfully',
                    'data' => $newCategory,
                ]);
            }

            return redirect()
                ->route('admin.categories.edit', $newCategory->id)
                ->with('success', 'Category duplicated successfully. You can now edit the copy.');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to duplicate category',
                    'error' => $e->getMessage(),
                ], 422);
            }

            return redirect()
                ->back()
                ->with('error', 'Failed to duplicate category: ' . $e->getMessage());
        }
    }
}
