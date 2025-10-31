<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Blog\Services\TagService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTagRequest;
use App\Http\Requests\Admin\UpdateTagRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * TagController
 *
 * Manages tags for articles, hadith, programs, and events
 */
class TagController extends Controller
{
    /**
     * Constructor with dependency injection
     *
     * @param TagService $service
     */
    public function __construct(
        protected TagService $service
    ) {
        // Protect routes - only authenticated admins
        $this->middleware('auth');
    }

    /**
     * Display paginated list of tags
     *
     * GET /su/portal/v1/admin/tags
     *
     * @param Request $request
     * @return View|JsonResponse
     */
    public function index(Request $request): View|JsonResponse
    {
        // Get pagination and search parameters
        $perPage = $request->integer('per_page', 50);
        $search = $request->string('search')->toString();

        // Get filters from request
        $filters = $request->only(['type', 'date_from', 'date_to']);

        // Fetch paginated data with filters applied
        $tags = $this->service->paginate(
            perPage: $perPage,
            search: $search,
            filters: $filters
        );

        // Get statistics for stats cards
        $stats = [
            'total' => $this->service->count(),
            'total_uses' => $this->service->getTotalUsageCount(),
            'most_used' => $this->service->getMostUsedTag(),
            'recent' => $this->service->getRecentlyAddedCount(7),
        ];

        // Return JSON for AJAX requests, HTML for page loads
        if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true,
                'data' => $tags->items(),
                'stats' => $stats,
                'meta' => [
                    'current_page' => $tags->currentPage(),
                    'per_page' => $tags->perPage(),
                    'total' => $tags->total(),
                    'last_page' => $tags->lastPage(),
                ],
            ]);
        }

        return view('admin.tags.index', compact('tags', 'stats'));
    }

    /**
     * Show create form
     *
     * GET /su/portal/v1/admin/tags/create
     *
     * @return View
     */
    public function create(): View
    {
        return view('admin.tags.create');
    }

    /**
     * Store new tag
     *
     * POST /su/portal/v1/admin/tags
     *
     * @param StoreTagRequest $request
     * @return JsonResponse
     */
    public function store(StoreTagRequest $request): JsonResponse
    {
        try {
            // Check for duplicate tags
            $duplicate = $this->service->findDuplicate($request->validated()['name']);
            if ($duplicate) {
                return response()->json([
                    'success' => false,
                    'message' => 'A similar tag already exists',
                    'duplicate' => $duplicate,
                ], 422);
            }

            $tag = $this->service->create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Tag created successfully',
                'data' => $tag,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create tag',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Show edit form
     *
     * GET /su/portal/v1/admin/tags/{id}/edit
     *
     * @param int $id
     * @return View
     */
    public function edit(int $id): View
    {
        $tag = $this->service->findOrFail($id);
        $usageCount = $this->service->getUsageCount($id);

        return view('admin.tags.edit', compact('tag', 'usageCount'));
    }

    /**
     * Update tag
     *
     * PUT /su/portal/v1/admin/tags/{id}
     *
     * @param UpdateTagRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateTagRequest $request, int $id): JsonResponse
    {
        try {
            $tag = $this->service->update($id, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Tag updated successfully',
                'data' => $tag,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update tag',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Show tag details
     *
     * GET /su/portal/v1/admin/tags/{id}
     *
     * @param int $id
     * @return View
     */
    public function show(int $id): View
    {
        $tag = $this->service->findOrFail($id);
        $articles = $this->service->getArticlesForTag($id, perPage: 20);
        $usageStats = [
            'total_articles' => $this->service->getUsageCount($id),
            'published_articles' => $this->service->getPublishedArticlesCount($id),
        ];

        return view('admin.tags.show', compact('tag', 'articles', 'usageStats'));
    }

    /**
     * Delete tag
     *
     * DELETE /su/portal/v1/admin/tags/{id}
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
                'message' => 'Tag deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete tag',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Merge two tags
     *
     * Move all articles from source tag to destination tag, then delete source tag
     *
     * POST /su/portal/v1/admin/tags/merge
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function merge(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'source_tag_id' => 'required|integer|exists:tags,id',
            'destination_tag_id' => 'required|integer|exists:tags,id|different:source_tag_id',
        ]);

        try {
            $mergedCount = $this->service->mergeTags(
                sourceId: $validated['source_tag_id'],
                destinationId: $validated['destination_tag_id']
            );

            return response()->json([
                'success' => true,
                'message' => "Successfully merged tags. {$mergedCount} articles moved.",
                'count' => $mergedCount,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to merge tags',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Bulk delete multiple tags
     *
     * POST /su/portal/v1/admin/tags/bulk-delete
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $ids = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:tags,id',
        ])['ids'];

        try {
            $count = $this->service->bulkDelete($ids);

            return response()->json([
                'success' => true,
                'message' => "$count tags deleted successfully",
                'count' => $count,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete tags',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
