<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Blog\Models\Series;
use App\Domain\Shared\Services\AdminLoggerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSeriesRequest;
use App\Http\Requests\Admin\UpdateSeriesRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Admin Series Controller
 * Handles series management for admin portal
 */
class SeriesController extends Controller
{
    /**
     * Constructor
     */
    public function __construct(
        protected AdminLoggerService $logger
    ) {}

    /**
     * Display a listing of all series
     *
     * GET /su/portal/v1/admin/series
     *
     * @param Request $request
     * @return View|JsonResponse
     */
    public function index(Request $request): View|JsonResponse
    {
        $perPage = $request->integer('per_page', 50);
        $search = $request->input('search');

        // Build query
        $query = Series::query()->with('articles');

        // Apply search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereJsonContains('title->ar', $search)
                    ->orWhereJsonContains('title->en', $search)
                    ->orWhereJsonContains('title->fr', $search)
                    ->orWhereJsonContains('title->tr', $search)
                    ->orWhereJsonContains('title->ur', $search)
                    ->orWhereJsonContains('description->ar', $search)
                    ->orWhereJsonContains('description->en', $search);
            });
        }

        // Order by created_at desc
        $query->orderByDesc('created_at');

        // Paginate
        $series = $query->paginate($perPage);

        // Calculate stats
        $stats = [
            'total' => Series::count(),
            'total_articles_in_series' => Series::sum('total_articles'),
            'max_articles' => Series::max('total_articles') ?? 0,
        ];

        // Return JSON for AJAX requests
        if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true,
                'data' => $series->items(),
                'stats' => $stats,
                'meta' => [
                    'current_page' => $series->currentPage(),
                    'per_page' => $series->perPage(),
                    'total' => $series->total(),
                    'last_page' => $series->lastPage(),
                ]
            ]);
        }

        return view('admin.series.index', compact('series', 'stats'));
    }

    /**
     * Show the form for creating a new series
     *
     * GET /su/portal/v1/admin/series/create
     *
     * @return View
     */
    public function create(): View
    {
        return view('admin.series.create');
    }

    /**
     * Store a newly created series
     *
     * POST /su/portal/v1/admin/series
     *
     * @param StoreSeriesRequest $request
     * @return RedirectResponse|JsonResponse
     */
    public function store(StoreSeriesRequest $request): RedirectResponse|JsonResponse
    {
        try {
            $data = $request->validated();

            // Handle thumbnail upload
            if ($request->hasFile('thumbnail')) {
                $thumbnailPath = $request->file('thumbnail')->store('series/thumbnails', 'public');
                $data['thumbnail'] = $thumbnailPath;
            }

            // Set default values
            $data['total_articles'] = 0;
            if (empty($data['slug'])) {
                $data['slug'] = \Illuminate\Support\Str::slug($data['title']['en'] ?? $data['title']['ar']);
            }

            // Remove articles from data before creating
            $articleIds = $request->input('article_ids', []);
            unset($data['article_ids']);

            // Create series
            $series = Series::create($data);

            // Sync articles with positions
            if (!empty($articleIds)) {
                $syncData = [];
                foreach ($articleIds as $position => $articleId) {
                    $syncData[$articleId] = ['position' => $position];
                }
                $series->articles()->sync($syncData);
                $series->updateArticlesCount();
            }

            // Log the creation
            $this->logger->logCreate($series, $request->user());

            // Return JSON for AJAX requests
            if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => true,
                    'message' => __('admin.series_created'),
                    'data' => $series,
                ], 201);
            }

            return redirect()
                ->route('admin.series.index')
                ->with('success', __('admin.series_created'));
        } catch (\Throwable $e) {
            \Log::error('admin.series.create_failed', [
                'user_id' => auth()->id(),
                'error'   => $e->getMessage(),
            ]);

            if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => false,
                    'message' => __('admin.operation_failed'),
                ], 422);
            }

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => __('admin.operation_failed')]);
        }
    }

    /**
     * Display the specified series
     *
     * GET /su/portal/v1/admin/series/{id}
     *
     * @param int $id
     * @return View
     */
    public function show(int $id): View
    {
        $series = Series::with(['articles.locales:id,article_id,locale,title'])->findOrFail($id);

        return view('admin.series.show', compact('series'));
    }

    /**
     * Show the form for editing the specified series
     *
     * GET /su/portal/v1/admin/series/{id}/edit
     *
     * @param int $id
     * @return View
     */
    public function edit(int $id): View
    {
        $series = Series::with(['articles.locales:id,article_id,locale,title'])->findOrFail($id);

        return view('admin.series.edit', compact('series'));
    }

    /**
     * Update the specified series
     *
     * PUT/PATCH /su/portal/v1/admin/series/{id}
     *
     * @param UpdateSeriesRequest $request
     * @param int $id
     * @return RedirectResponse|JsonResponse
     */
    public function update(UpdateSeriesRequest $request, int $id): RedirectResponse|JsonResponse
    {
        try {
            // Get old values before update
            $series = Series::findOrFail($id);
            $oldValues = $series->toArray();
            $oldThumbnail = $series->thumbnail;

            $data = $request->validated();

            // Handle thumbnail upload
            if ($request->hasFile('thumbnail')) {
                // Delete old thumbnail if exists
                if ($oldThumbnail && Storage::disk('public')->exists($oldThumbnail)) {
                    Storage::disk('public')->delete($oldThumbnail);
                }

                // Store new thumbnail
                $thumbnailPath = $request->file('thumbnail')->store('series/thumbnails', 'public');
                $data['thumbnail'] = $thumbnailPath;
            }

            // Update slug if title changed
            if (isset($data['title']) && $series->title !== $data['title']) {
                $data['slug'] = \Illuminate\Support\Str::slug($data['title']['en'] ?? $data['title']['ar']);
            }

            // Handle articles sync
            $articleIds = $request->input('article_ids', []);
            unset($data['article_ids']);

            // Perform update
            $series->update($data);

            // Sync articles with positions
            $syncData = [];
            foreach ($articleIds as $position => $articleId) {
                $syncData[$articleId] = ['position' => $position];
            }
            $series->articles()->sync($syncData);
            $series->updateArticlesCount();

            // Log the update with diff
            $this->logger->logUpdate($series, $oldValues, $series->fresh()->toArray(), $request->user());

            // Return JSON for AJAX requests
            if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => true,
                    'message' => __('admin.series_updated'),
                    'data' => $series->fresh(),
                ]);
            }

            return redirect()
                ->route('admin.series.edit', $id)
                ->with('success', __('admin.series_updated'));
        } catch (\Throwable $e) {
            \Log::error('admin.series.update_failed', [
                'user_id'   => auth()->id(),
                'series_id' => $id,
                'error'     => $e->getMessage(),
            ]);

            if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => false,
                    'message' => __('admin.operation_failed'),
                ], 422);
            }

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => __('admin.operation_failed')]);
        }
    }

    /**
     * Remove the specified series
     *
     * DELETE /su/portal/v1/admin/series/{id}
     *
     * @param int $id
     * @return RedirectResponse|JsonResponse
     */
    public function destroy(Request $request, int $id): RedirectResponse|JsonResponse
    {
        try {
            // Get model before deletion
            $series = Series::findOrFail($id);
            $thumbnail = $series->thumbnail;

            // Detach all articles from this series
            $series->articles()->detach();

            // Delete thumbnail file if exists
            if ($thumbnail && Storage::disk('public')->exists($thumbnail)) {
                Storage::disk('public')->delete($thumbnail);
            }

            // Perform deletion
            $series->delete();

            // Log the deletion
            $this->logger->logDelete($series, $request->user());

            // Return JSON for AJAX requests
            if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => true,
                    'message' => __('admin.series_deleted'),
                ]);
            }

            return redirect()
                ->route('admin.series.index')
                ->with('success', __('admin.series_deleted'));
        } catch (\Throwable $e) {
            \Log::error('admin.series.delete_failed', [
                'user_id'   => auth()->id(),
                'series_id' => $id,
                'error'     => $e->getMessage(),
            ]);

            if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => false,
                    'message' => __('admin.operation_failed'),
                ], 422);
            }

            return redirect()
                ->back()
                ->withErrors(['error' => __('admin.operation_failed')]);
        }
    }

    /**
     * Reorder articles within a series
     *
     * POST /su/portal/v1/admin/series/{id}/reorder-articles
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function reorderArticles(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'articles' => 'required|array|min:1',
                'articles.*.id' => 'required|integer|exists:articles,id',
                'articles.*.position' => 'required|integer|min:0',
            ]);

            $series = Series::findOrFail($id);
            $articles = $request->input('articles');

            // Update position in pivot table for ordering
            foreach ($articles as $articleData) {
                $series->articles()->updateExistingPivot(
                    $articleData['id'],
                    ['position' => $articleData['position']]
                );
            }

            // Log the action
            $this->logger->logCustomAction(
                'reorder_articles',
                $series,
                $request->user(),
                null,
                ['articles' => $articles]
            );

            return response()->json([
                'success' => true,
                'message' => __('admin.series_articles_reordered'),
            ]);
        } catch (\Throwable $e) {
            \Log::error('admin.series.reorder_articles_failed', [
                'user_id'   => auth()->id(),
                'series_id' => $id,
                'error'     => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('admin.operation_failed'),
            ], 422);
        }
    }

    /**
     * Bulk delete multiple series
     *
     * POST /su/portal/v1/admin/series/bulk-delete
     *
     * @param Request $request
     * @return RedirectResponse|JsonResponse
     */
    public function bulkDelete(Request $request): RedirectResponse|JsonResponse
    {
        try {
            $request->validate([
                'ids' => ['required', 'array', 'min:1'],
                'ids.*' => ['integer', 'exists:series,id'],
            ]);

            $ids = $request->input('ids');
            $deleted = 0;

            foreach ($ids as $id) {
                try {
                    $series = Series::find($id);
                    if (!$series) {
                        continue;
                    }

                    // Detach all articles from this series
                    $series->articles()->detach();

                    // Delete thumbnail
                    if ($series->thumbnail && Storage::disk('public')->exists($series->thumbnail)) {
                        Storage::disk('public')->delete($series->thumbnail);
                    }

                    // Delete series
                    $series->delete();
                    $deleted++;
                } catch (\Throwable $e) {
                    \Log::warning('admin.series.bulk_delete_item_failed', [
                        'user_id'   => auth()->id(),
                        'series_id' => $id,
                        'error'     => $e->getMessage(),
                    ]);
                    continue;
                }
            }

            // Log bulk action
            $this->logger->logBulkAction(
                'bulk_delete',
                Series::class,
                $ids,
                $request->user()
            );

            // Return JSON for AJAX requests
            if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => true,
                    'message' => __('admin.series_bulk_deleted', ['count' => $deleted]),
                    'count' => $deleted,
                ]);
            }

            return redirect()
                ->route('admin.series.index')
                ->with('success', __('admin.series_bulk_deleted', ['count' => $deleted]));
        } catch (\Throwable $e) {
            \Log::error('admin.series.bulk_delete_failed', [
                'user_id' => auth()->id(),
                'error'   => $e->getMessage(),
            ]);

            if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => false,
                    'message' => __('admin.operation_failed'),
                ], 422);
            }

            return redirect()
                ->back()
                ->withErrors(['error' => __('admin.operation_failed')]);
        }
    }
}
