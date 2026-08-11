<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Blog;

use App\Domain\Blog\DTOs\CreateSeriesData;
use App\Domain\Blog\DTOs\UpdateSeriesData;
use App\Domain\Blog\Models\Series;
use App\Domain\Blog\Services\SeriesService;
use App\Domain\Shared\Services\AdminLoggerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSeriesRequest;
use App\Http\Requests\Admin\UpdateSeriesRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        protected SeriesService $seriesService,
        protected AdminLoggerService $logger
    ) {}

    /**
     * Display a listing of all series
     *
     * GET /su/portal/v1/admin/series
     */
    public function index(Request $request): View|JsonResponse
    {
        $perPage = $request->integer('per_page', 50);
        $search = (string) $request->input('search', '');

        $series = $this->seriesService->paginateAdmin($perPage, $search);
        $stats = $this->seriesService->getStats();

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
                ],
            ]);
        }

        return view('admin.series.index', compact('series', 'stats'));
    }

    /**
     * Show the form for creating a new series
     *
     * GET /su/portal/v1/admin/series/create
     */
    public function create(): View
    {
        return view('admin.series.create');
    }

    /**
     * Store a newly created series
     *
     * POST /su/portal/v1/admin/series
     */
    public function store(StoreSeriesRequest $request): RedirectResponse|JsonResponse
    {
        try {
            $data = CreateSeriesData::fromRequest($request);
            $series = $this->seriesService->storeSeries($data);

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
                ->route('admin.series.show', $series->id)
                ->with('success', __('admin.series_created'));
        } catch (\Throwable $e) {
            \Log::error('admin.series.create_failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
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
     */
    public function show(int $id): View
    {
        $series = $this->seriesService->findForAdmin($id);

        return view('admin.series.show', compact('series'));
    }

    /**
     * Show the form for editing the specified series
     *
     * GET /su/portal/v1/admin/series/{id}/edit
     */
    public function edit(int $id): View
    {
        $series = $this->seriesService->findForAdmin($id);

        return view('admin.series.edit', compact('series'));
    }

    /**
     * Update the specified series
     *
     * PUT/PATCH /su/portal/v1/admin/series/{id}
     */
    public function update(UpdateSeriesRequest $request, int $id): RedirectResponse|JsonResponse
    {
        try {
            // Capture old values for the audit log before mutation
            $oldValues = $this->seriesService->findOrFail($id, ['translations'])->toArray();

            $data = UpdateSeriesData::fromRequest($request);
            $series = $this->seriesService->updateSeries($id, $data);

            // Log the update with diff
            $this->logger->logUpdate($series, $oldValues, $series->toArray(), $request->user());

            // Return JSON for AJAX requests
            if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => true,
                    'message' => __('admin.series_updated'),
                    'data' => $series,
                ]);
            }

            return redirect()
                ->route('admin.series.show', $series->id)
                ->with('success', __('admin.series_updated'));
        } catch (\Throwable $e) {
            \Log::error('admin.series.update_failed', [
                'user_id' => auth()->id(),
                'series_id' => $id,
                'error' => $e->getMessage(),
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
     */
    public function destroy(Request $request, int $id): RedirectResponse|JsonResponse
    {
        try {
            // Load for the audit log before deletion
            $series = $this->seriesService->findOrFail($id);

            $this->seriesService->destroySeries($id);

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
                'user_id' => auth()->id(),
                'series_id' => $id,
                'error' => $e->getMessage(),
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
     */
    public function reorderArticles(Request $request, int $id): JsonResponse
    {
        try {
            // An empty list is valid: it unassigns every article from the series.
            $request->validate([
                'articles' => 'present|array',
                'articles.*.id' => 'required|integer|exists:articles,id',
                'articles.*.position' => 'required|integer|min:0',
            ]);

            $articles = $request->input('articles');

            $this->seriesService->reorderArticles($id, $articles);

            // Load for the audit log
            $series = $this->seriesService->findOrFail($id);
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
                'user_id' => auth()->id(),
                'series_id' => $id,
                'error' => $e->getMessage(),
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
     */
    public function bulkDelete(Request $request): RedirectResponse|JsonResponse
    {
        try {
            $request->validate([
                'ids' => ['required', 'array', 'min:1'],
                'ids.*' => ['integer', 'exists:series,id'],
            ]);

            $ids = $request->input('ids');
            $deleted = $this->seriesService->bulkDestroy($ids);

            if ($deleted < count($ids)) {
                \Log::warning('admin.series.bulk_delete_partial', [
                    'user_id' => auth()->id(),
                    'requested_ids' => $ids,
                    'deleted_count' => $deleted,
                ]);
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
                'error' => $e->getMessage(),
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
