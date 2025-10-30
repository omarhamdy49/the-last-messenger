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

            // Create series
            $series = Series::create($data);

            // Log the creation
            $this->logger->logCreate($series, $request->user());

            // Return JSON for AJAX requests
            if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => true,
                    'message' => 'Series created successfully',
                    'data' => $series,
                ], 201);
            }

            return redirect()
                ->route('admin.series.index')
                ->with('success', 'تم إنشاء السلسلة بنجاح');
        } catch (\Exception $e) {
            if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create series',
                    'error' => $e->getMessage(),
                ], 422);
            }

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'فشل إنشاء السلسلة: ' . $e->getMessage()]);
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
        $series = Series::with('articles')->findOrFail($id);

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
        $series = Series::with('articles')->findOrFail($id);

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

            // Perform update
            $series->update($data);

            // Log the update with diff
            $this->logger->logUpdate($series, $oldValues, $series->fresh()->toArray(), $request->user());

            // Return JSON for AJAX requests
            if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => true,
                    'message' => 'Series updated successfully',
                    'data' => $series->fresh(),
                ]);
            }

            return redirect()
                ->route('admin.series.index')
                ->with('success', 'تم تحديث السلسلة بنجاح');
        } catch (\Exception $e) {
            if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update series',
                    'error' => $e->getMessage(),
                ], 422);
            }

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'فشل تحديث السلسلة: ' . $e->getMessage()]);
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

            // Remove series_id from all articles in this series
            $series->articles()->update(['series_id' => null]);

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
                    'message' => 'Series deleted successfully',
                ]);
            }

            return redirect()
                ->route('admin.series.index')
                ->with('success', 'تم حذف السلسلة بنجاح');
        } catch (\Exception $e) {
            if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete series',
                    'error' => $e->getMessage(),
                ], 422);
            }

            return redirect()
                ->back()
                ->withErrors(['error' => 'فشل حذف السلسلة: ' . $e->getMessage()]);
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
                'articles.*.published_at' => 'required|date',
            ]);

            $series = Series::findOrFail($id);
            $articles = $request->input('articles');

            // Update published_at for ordering (since position_in_series doesn't exist)
            foreach ($articles as $articleData) {
                $series->articles()
                    ->where('id', $articleData['id'])
                    ->update(['published_at' => $articleData['published_at']]);
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
                'message' => 'Articles reordered successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder articles',
                'error' => $e->getMessage(),
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

                    // Remove series_id from articles
                    $series->articles()->update(['series_id' => null]);

                    // Delete thumbnail
                    if ($series->thumbnail && Storage::disk('public')->exists($series->thumbnail)) {
                        Storage::disk('public')->delete($series->thumbnail);
                    }

                    // Delete series
                    $series->delete();
                    $deleted++;
                } catch (\Exception $e) {
                    // Continue deleting other series
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
                    'message' => "$deleted series deleted successfully",
                    'count' => $deleted,
                ]);
            }

            return redirect()
                ->route('admin.series.index')
                ->with('success', "تم حذف {$deleted} سلسلة بنجاح");
        } catch (\Exception $e) {
            if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete series',
                    'error' => $e->getMessage(),
                ], 422);
            }

            return redirect()
                ->back()
                ->withErrors(['error' => 'فشل حذف السلاسل: ' . $e->getMessage()]);
        }
    }
}
