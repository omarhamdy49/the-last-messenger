<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Media\Services\MediaCategoryService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMediaCategoryRequest;
use App\Http\Requests\Admin\UpdateMediaCategoryRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * MediaCategoryController
 *
 * Handles CRUD operations for media categories.
 * Categories are used to organize video programs.
 */
class MediaCategoryController extends Controller
{
    /**
     * Constructor with dependency injection
     *
     * @param MediaCategoryService $service
     */
    public function __construct(
        protected MediaCategoryService $service
    ) {
        // Protect routes - only authenticated admins
        $this->middleware('auth');
    }

    /**
     * Display paginated list of media categories
     *
     * GET /admin/media-categories
     *
     * @param Request $request
     * @return View|JsonResponse
     */
    public function index(Request $request): View|JsonResponse
    {
        // Get pagination and search parameters
        $perPage = $request->integer('per_page', 50);
        $search = $request->string('search')->trim()->toString();

        // Get filters from request (no hierarchical filters needed for flat categories)
        $filters = [];

        // Fetch paginated data with filters
        $categories = $this->service->paginateWithFilters(
            perPage: $perPage,
            search: $search,
            filters: $filters,
            with: []
        );

        // Get statistics for stats cards
        $stats = [
            'total' => $this->service->count(),
            'totalPrograms' => $this->service->countTotalPrograms(),
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

        return view('admin.media-categories.index', compact('categories', 'stats'));
    }

    /**
     * Show create form
     *
     * GET /admin/media-categories/create
     *
     * @return View
     */
    public function create(): View
    {
        return view('admin.media-categories.create');
    }

    /**
     * Store new media category
     *
     * POST /admin/media-categories
     *
     * @param StoreMediaCategoryRequest $request
     * @return JsonResponse
     */
    public function store(StoreMediaCategoryRequest $request): JsonResponse
    {
        try {
            $category = $this->service->create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Media category created successfully',
                'data' => $category,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create media category',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Show edit form
     *
     * GET /admin/media-categories/{id}/edit
     *
     * @param int $id
     * @return View
     */
    public function edit(int $id): View
    {
        $mediaCategory = $this->service->findOrFail($id);
        return view('admin.media-categories.edit', compact('mediaCategory'));
    }

    /**
     * Update media category
     *
     * PUT /admin/media-categories/{id}
     *
     * @param UpdateMediaCategoryRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateMediaCategoryRequest $request, int $id): JsonResponse
    {
        try {
            $category = $this->service->update($id, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Media category updated successfully',
                'data' => $category,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update media category',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Show category details
     *
     * GET /admin/media-categories/{id}
     *
     * @param int $id
     * @return View
     */
    public function show(int $id): View
    {
        $mediaCategory = $this->service->findOrFail($id);
        $programs = $this->service->getProgramsByCategory($id, perPage: 15);

        return view('admin.media-categories.show', compact('mediaCategory', 'programs'));
    }

    /**
     * Delete media category
     *
     * DELETE /admin/media-categories/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->service->delete($id);

            return response()->json([
                'success' => true,
                'message' => 'Media category deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete media category',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Reorder categories (for drag-drop functionality)
     *
     * POST /admin/media-categories/reorder
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function reorder(Request $request): JsonResponse
    {
        $items = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|integer|exists:media_categories,id',
            'items.*.order' => 'required|integer|min:0',
        ])['items'];

        try {
            $this->service->reorder($items);

            return response()->json([
                'success' => true,
                'message' => 'Media categories reordered successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder media categories',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
