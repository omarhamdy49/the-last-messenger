<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Blog;

use App\Domain\Blog\DTOs\CreateTagData;
use App\Domain\Blog\DTOs\UpdateTagData;
use App\Domain\Blog\Models\Tag;
use App\Domain\Blog\Services\TagService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTagRequest;
use App\Http\Requests\Admin\UpdateTagRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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
     */
    public function __construct(
        protected TagService $service
    ) {
        // Protect routes - only authenticated admins (admin session guard)
        $this->middleware('auth:admin');
    }

    /**
     * Display paginated list of tags
     *
     * GET /su/portal/v1/admin/tags
     */
    public function index(Request $request): View|JsonResponse
    {
        // Get pagination and search parameters
        $perPage = $request->integer('per_page', 50);
        $search = $request->string('search')->toString();

        // Get filters from request
        $filters = $request->only(['type', 'date_from', 'date_to']);

        // Fetch paginated data with filters applied. Translations are eager
        // loaded because every row below resolves its name from them.
        $tags = $this->service->paginate(
            perPage: $perPage,
            search: $search,
            filters: $filters,
            with: ['translations:id,tag_id,locale,name']
        );

        // Get statistics for stats cards
        $mostUsedTag = $this->service->getMostUsedTag();
        $stats = [
            'count' => $this->service->count(),
            'total_usage' => $this->service->getTotalUsageCount(),
            'most_used' => $mostUsedTag ? $mostUsedTag->getTranslation('name', app()->getLocale()) : '—',
            'unused_count' => Tag::where('usage_count', 0)->count(),
            'recent' => $this->service->getRecentlyAddedCount(7),
        ];

        // Return JSON for AJAX requests, HTML for page loads
        if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true,
                'data' => $tags->items(),
                'tags' => $tags->getCollection()->map(fn ($tag) => [
                    'id' => $tag->id,
                    'name' => $tag->getTranslation('name') ?? $tag->slug,
                    'articles_count' => (int) ($tag->articles_count ?? $tag->usage_count ?? 0),
                ])->values(),
                'stats' => $stats,
                'meta' => [
                    'current_page' => $tags->currentPage(),
                    'per_page' => $tags->perPage(),
                    'total' => $tags->total(),
                    'last_page' => $tags->lastPage(),
                ],
            ]);
        }

        // Plain array for the Alpine-rendered table (names live on translations)
        $tagsForJs = $tags->getCollection()->map(fn ($tag) => [
            'id' => $tag->id,
            'name' => $tag->getTranslation('name') ?? $tag->slug,
            'translations' => $tag->translations->pluck('name', 'locale')->filter(),
            'color' => $tag->color,
            'articles_count' => (int) ($tag->articles_count ?? $tag->usage_count ?? 0),
            'created_at' => optional($tag->created_at)->toIso8601String(),
        ])->values();

        return view('admin.tags.index', compact('tags', 'tagsForJs', 'stats'));
    }

    /**
     * Show create form
     *
     * GET /su/portal/v1/admin/tags/create
     */
    public function create(): View
    {
        return view('admin.tags.create');
    }

    /**
     * Store new tag
     *
     * POST /su/portal/v1/admin/tags
     */
    public function store(StoreTagRequest $request): JsonResponse|RedirectResponse
    {
        try {
            // Check for duplicate tags
            $duplicate = $this->service->findDuplicate($request->validated()['name']);
            if ($duplicate) {
                if (! $request->expectsJson()) {
                    return back()->withInput()
                        ->withErrors(['name.ar' => __('admin.tag_duplicate_exists')]);
                }

                return response()->json([
                    'success' => false,
                    'message' => __('admin.tag_duplicate_exists'),
                    'duplicate' => $duplicate,
                ], 422);
            }

            $tag = $this->service->create(CreateTagData::fromRequest($request));

            if (! $request->expectsJson()) {
                return redirect()->route('admin.tags.index')
                    ->with('success', __('admin.tag_created'));
            }

            return response()->json([
                'success' => true,
                'message' => __('admin.tag_created'),
                'data' => $tag,
            ], 201);
        } catch (\Throwable $e) {
            \Log::error('admin.tag.create_failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            if (! $request->expectsJson()) {
                return back()->withInput()
                    ->withErrors(['name.ar' => __('admin.operation_failed')]);
            }

            return response()->json([
                'success' => false,
                'message' => __('admin.operation_failed'),
            ], 422);
        }
    }

    /**
     * Show edit form
     *
     * GET /su/portal/v1/admin/tags/{id}/edit
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
     */
    public function update(UpdateTagRequest $request, int $id): JsonResponse|RedirectResponse
    {
        try {
            $tag = $this->service->update($id, UpdateTagData::fromRequest($request));

            if (! $request->expectsJson()) {
                return redirect()->route('admin.tags.index')
                    ->with('success', __('admin.tag_updated'));
            }

            return response()->json([
                'success' => true,
                'message' => __('admin.tag_updated'),
                'data' => $tag,
            ]);
        } catch (\Throwable $e) {
            \Log::error('admin.tag.update_failed', [
                'user_id' => auth()->id(),
                'tag_id' => $id,
                'error' => $e->getMessage(),
            ]);

            if (! $request->expectsJson()) {
                return back()->withInput()
                    ->withErrors(['name.ar' => __('admin.operation_failed')]);
            }

            return response()->json([
                'success' => false,
                'message' => __('admin.operation_failed'),
            ], 422);
        }
    }

    /**
     * Show tag details
     *
     * GET /su/portal/v1/admin/tags/{id}
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
     */
    public function destroy(Request $request, int $id): JsonResponse|RedirectResponse
    {
        try {
            $this->service->delete($id);

            if (! $request->expectsJson()) {
                return redirect()->route('admin.tags.index')->with('success', __('admin.tag_deleted'));
            }

            return response()->json([
                'success' => true,
                'message' => __('admin.tag_deleted'),
            ]);
        } catch (\Throwable $e) {
            \Log::error('admin.tag.delete_failed', [
                'user_id' => auth()->id(),
                'tag_id' => $id,
                'error' => $e->getMessage(),
            ]);

            if (! $request->expectsJson()) {
                return back()->with('error', __('admin.operation_failed'));
            }

            return response()->json([
                'success' => false,
                'message' => __('admin.operation_failed'),
            ], 422);
        }
    }

    /**
     * Merge two tags
     *
     * Move all articles from source tag to destination tag, then delete source tag
     *
     * POST /su/portal/v1/admin/tags/merge
     */
    public function merge(Request $request): JsonResponse
    {
        if ($request->has('source_tag_ids')) {
            return $this->mergeSelected($request);
        }

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
                'message' => __('admin.tags_merged', ['count' => $mergedCount]),
                'count' => $mergedCount,
            ]);
        } catch (\Throwable $e) {
            \Log::error('admin.tag.merge_failed', [
                'user_id' => auth()->id(),
                'source_tag_id' => $validated['source_tag_id'],
                'destination_tag_id' => $validated['destination_tag_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('admin.operation_failed'),
            ], 422);
        }
    }

    private function mergeSelected(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'source_tag_ids' => ['required', 'array', 'min:1'],
            'source_tag_ids.*' => ['required', 'integer', 'distinct', 'exists:tags,id'],
            'merge_mode' => ['required', Rule::in(['existing', 'new'])],
            'target_tag_id' => ['required_if:merge_mode,existing', 'nullable', 'integer', 'exists:tags,id', Rule::notIn($request->input('source_tag_ids', []))],
            'new_tag_name' => ['required_if:merge_mode,new', 'nullable', 'string', 'max:255'],
        ]);

        try {
            $count = DB::transaction(function () use ($validated): int {
                $targetId = $validated['target_tag_id'] ?? null;
                if ($validated['merge_mode'] === 'new') {
                    $names = [app()->getLocale() => $validated['new_tag_name']];
                    if ($this->service->findDuplicate($names)) {
                        throw new \RuntimeException(__('admin.tag_duplicate_exists'));
                    }
                    $target = $this->service->create(new CreateTagData(
                        name: $names, description: [], type: 'article', color: null, slug: null,
                    ));
                    $targetId = $target->id;
                }

                $count = 0;
                foreach ($validated['source_tag_ids'] as $sourceId) {
                    $count += $this->service->mergeTags((int) $sourceId, (int) $targetId);
                }

                return $count;
            });

            return response()->json(['success' => true, 'message' => __('admin.tags_merged', ['count' => $count]), 'count' => $count]);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json(['success' => false, 'message' => __('admin.operation_failed')], 422);
        }
    }

    /**
     * Bulk delete multiple tags
     *
     * POST /su/portal/v1/admin/tags/bulk-delete
     */
    public function bulkDelete(Request $request): JsonResponse|RedirectResponse
    {
        $ids = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:tags,id',
        ])['ids'];

        try {
            $count = $this->service->bulkDelete($ids);

            if (! $request->expectsJson()) {
                return back()->with('success', __('admin.tags_bulk_deleted', ['count' => $count]));
            }

            return response()->json([
                'success' => true,
                'message' => __('admin.tags_bulk_deleted', ['count' => $count]),
                'count' => $count,
            ]);
        } catch (\Throwable $e) {
            \Log::error('admin.tag.bulk_delete_failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            if (! $request->expectsJson()) {
                return back()->with('error', __('admin.operation_failed'));
            }

            return response()->json([
                'success' => false,
                'message' => __('admin.operation_failed'),
            ], 422);
        }
    }

    /**
     * Search tags for autocomplete (AJAX endpoint)
     *
     * GET /su/portal/v1/admin/tags/search
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'required|string|min:1|max:100',
            'type' => 'nullable|in:article,hadith,program,event',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $query = $validated['q'];
        $type = $validated['type'] ?? null;
        $limit = $validated['limit'] ?? 10;

        $tags = $this->service->searchForAutocomplete($query, $type, $limit);

        return response()->json([
            'success' => true,
            'data' => $tags,
        ]);
    }

    /**
     * Get unused tags (tags with zero usage_count)
     *
     * GET /su/portal/v1/admin/tags/unused
     */
    public function unused(Request $request): View|JsonResponse
    {
        $perPage = $request->integer('per_page', 50);
        $unusedTags = $this->service->getUnusedTags($perPage);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $unusedTags->items(),
                'meta' => [
                    'current_page' => $unusedTags->currentPage(),
                    'per_page' => $unusedTags->perPage(),
                    'total' => $unusedTags->total(),
                    'last_page' => $unusedTags->lastPage(),
                ],
            ]);
        }

        return view('admin.tags.unused', compact('unusedTags'));
    }
}
