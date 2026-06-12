<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Blog;

use App\Domain\Blog\DTOs\CreateCategoryData;
use App\Domain\Blog\DTOs\UpdateCategoryData;
use App\Domain\Blog\Enums\CategoryType;
use App\Domain\Blog\Services\CategoryService;
use App\Domain\Shared\Services\AdminLoggerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Support\Traits\ApiResponse;
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
    use ApiResponse;

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
            return $this->successResponse([
                'items' => $categories->items(),
                'stats' => $stats,
                'meta' => [
                    'current_page' => $categories->currentPage(),
                    'per_page' => $categories->perPage(),
                    'total' => $categories->total(),
                    'last_page' => $categories->lastPage(),
                ],
            ], 'Categories retrieved successfully');
        }

        return view('admin.categories.index', compact('categories', 'stats'));
    }

    /**
     * Show create form
     *
     * GET /su/portal/v1/admin/categories/create
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
     */
    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        try {
            $category = $this->categoryService->create(CreateCategoryData::fromRequest($request));

            // Log the creation
            $this->logger->logCreate($category, $request->user());

            return redirect()
                ->route('admin.categories.index')
                ->with('success', __('admin.category_created'));
        } catch (\Throwable $e) {
            \Log::error('admin.category.create_failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', __('admin.category_operation_failed'));
        }
    }

    /**
     * Show edit form
     *
     * GET /su/portal/v1/admin/categories/{id}/edit
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
     */
    public function update(UpdateCategoryRequest $request, int $id): RedirectResponse
    {
        try {
            // Get old values before update
            $oldCategory = $this->categoryService->findOrFail($id);
            $oldValues = $oldCategory->toArray();

            // Perform update using updateCategory (not update) to trigger business logic
            $category = $this->categoryService->updateCategory($id, UpdateCategoryData::fromRequest($request));

            // Log the update with diff
            $this->logger->logUpdate($category, $oldValues, $category->toArray(), $request->user());

            return redirect()
                ->route('admin.categories.index')
                ->with('success', __('admin.category_updated'));
        } catch (\Throwable $e) {
            \Log::error('admin.category.update_failed', [
                'user_id' => auth()->id(),
                'category_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', __('admin.category_operation_failed'));
        }
    }

    /**
     * Show category details
     *
     * GET /su/portal/v1/admin/categories/{id}
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
                    ->with('error', __('admin.category_has_children'));
            }

            $articlesCount = $category->articles()->count();
            if ($articlesCount > 0) {
                return redirect()
                    ->back()
                    ->with('error', __('admin.category_has_articles', ['count' => $articlesCount]));
            }

            // Perform deletion
            $this->categoryService->delete($id);

            // Log the deletion
            $this->logger->logDelete($category, request()->user());

            return redirect()
                ->route('admin.categories.index')
                ->with('success', __('admin.category_deleted'));
        } catch (\Throwable $e) {
            \Log::error('admin.category.delete_failed', [
                'user_id' => auth()->id(),
                'category_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->with('error', __('admin.category_operation_failed'));
        }
    }

    /**
     * Bulk delete multiple categories
     *
     * POST /su/portal/v1/admin/categories/bulk-delete
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
            } catch (\Throwable $e) {
                \Log::warning('admin.category.bulk_delete_item_failed', [
                    'user_id' => auth()->id(),
                    'category_id' => $id,
                    'error' => $e->getMessage(),
                ]);
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

        if ($failed > 0) {
            $message = __('admin.categories_bulk_deleted_partial', ['deleted' => $deleted, 'failed' => $failed]);
        } else {
            $message = __('admin.categories_bulk_deleted', ['deleted' => $deleted]);
        }

        if ($request->wantsJson()) {
            return $this->successResponse(
                ['deleted' => $deleted, 'failed' => $failed],
                $message
            );
        }

        return redirect()
            ->route('admin.categories.index')
            ->with('success', $message);
    }

    /**
     * Reorder categories (for drag-drop functionality)
     *
     * POST /su/portal/v1/admin/categories/reorder
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

            return $this->successResponse(null, __('admin.categories_reordered'));
        } catch (\Throwable $e) {
            \Log::error('admin.category.reorder_failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(__('admin.category_operation_failed'), 422);
        }
    }

    /**
     * Search categories (AJAX endpoint)
     *
     * GET /su/portal/v1/admin/categories/search
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

        return $this->paginatedResponse($categories, 'Categories retrieved successfully');
    }

    /**
     * Get category tree as JSON (for hierarchical display)
     *
     * GET /su/portal/v1/admin/categories/tree
     */
    public function tree(Request $request): JsonResponse
    {
        $language = $request->input('language', 'ar');
        $type = $request->input('type');

        $tree = \App\Domain\Blog\Models\Category::getTree(null, $language);

        if ($type) {
            $tree = array_filter($tree, fn ($cat) => $cat['type'] === $type);
        }

        return $this->successResponse($tree, 'Category tree retrieved successfully');
    }

    /**
     * Restore soft-deleted category
     *
     * POST /su/portal/v1/admin/categories/{id}/restore
     */
    public function restore(Request $request, int $id): RedirectResponse|JsonResponse
    {
        try {
            $category = $this->categoryService->restoreById($id);

            // Log the restoration
            $this->logger->logRestore($category, $request->user());

            if ($request->wantsJson()) {
                return $this->successResponse($category, __('admin.category_restored'));
            }

            return redirect()
                ->route('admin.categories.index')
                ->with('success', __('admin.category_restored'));
        } catch (\Throwable $e) {
            \Log::error('admin.category.restore_failed', [
                'user_id' => auth()->id(),
                'category_id' => $id,
                'error' => $e->getMessage(),
            ]);

            if ($request->wantsJson()) {
                return $this->errorResponse(__('admin.category_operation_failed'), 422);
            }

            return redirect()
                ->back()
                ->with('error', __('admin.category_operation_failed'));
        }
    }

    /**
     * Duplicate/clone an existing category
     *
     * POST /su/portal/v1/admin/categories/{id}/duplicate
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
                    $duplicateData['name'][$lang] = $name.' (Copy)';
                }
            }

            $newCategory = $this->categoryService->create($duplicateData);

            // Log the duplication
            $this->logger->logCreate($newCategory, $request->user());

            if ($request->wantsJson()) {
                return $this->successResponse($newCategory, __('admin.category_duplicated'));
            }

            return redirect()
                ->route('admin.categories.edit', $newCategory->id)
                ->with('success', __('admin.category_duplicated_edit'));
        } catch (\Throwable $e) {
            \Log::error('admin.category.duplicate_failed', [
                'user_id' => auth()->id(),
                'category_id' => $id,
                'error' => $e->getMessage(),
            ]);

            if ($request->wantsJson()) {
                return $this->errorResponse(__('admin.category_operation_failed'), 422);
            }

            return redirect()
                ->back()
                ->with('error', __('admin.category_operation_failed'));
        }
    }
}
