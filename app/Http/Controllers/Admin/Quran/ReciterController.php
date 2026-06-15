<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Quran;

use App\Domain\Quran\DTOs\CreateReciterData;
use App\Domain\Quran\DTOs\UpdateReciterData;
use App\Domain\Quran\Models\Reciter;
use App\Domain\Quran\Services\RecitationService;
use App\Domain\Quran\Services\ReciterService;
use App\Domain\Shared\Services\AdminLoggerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreReciterRequest;
use App\Http\Requests\Admin\UpdateReciterRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Admin Reciter Controller
 * Manages Quran reciters (Qaris) in admin portal
 */
class ReciterController extends Controller
{
    /**
     * Available recitation styles (shared across actions)
     */
    private const STYLES = [
        'Tajweed',
        'Qaloon',
        'Warsh',
        'Hafs',
        'Kalon',
        'Duri',
        'Kasai',
    ];

    /**
     * Constructor
     */
    public function __construct(
        protected ReciterService $reciterService,
        protected RecitationService $recitationService,
        protected AdminLoggerService $logger
    ) {}

    /**
     * Display a listing of all reciters
     *
     * GET /su/portal/v1/admin/reciters
     */
    public function index(Request $request): View|JsonResponse
    {
        $perPage = $request->integer('per_page', 50);
        $filters = [
            'search' => $request->input('search'),
            'country' => $request->input('country'),
            'style' => $request->input('style'),
        ];

        $reciters = $this->reciterService->list($filters, $perPage);
        $stats = $this->reciterService->getStats();
        $countries = $this->reciterService->getDistinctCountries();
        $styles = self::STYLES;

        // Return JSON for AJAX requests
        if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true,
                'data' => $reciters->items(),
                'stats' => $stats,
                'filters' => [
                    'countries' => $countries,
                    'styles' => $styles,
                ],
                'meta' => [
                    'current_page' => $reciters->currentPage(),
                    'per_page' => $reciters->perPage(),
                    'total' => $reciters->total(),
                    'last_page' => $reciters->lastPage(),
                ],
            ]);
        }

        return view('admin.reciters.index', compact('reciters', 'stats', 'countries', 'styles'));
    }

    /**
     * Show the form for creating a new reciter
     *
     * GET /su/portal/v1/admin/reciters/create
     */
    public function create(): View
    {
        $styles = self::STYLES;

        return view('admin.reciters.create', compact('styles'));
    }

    /**
     * Store a newly created reciter
     *
     * POST /su/portal/v1/admin/reciters
     */
    public function store(StoreReciterRequest $request): RedirectResponse|JsonResponse
    {
        try {
            $data = CreateReciterData::fromRequest($request);
            $reciter = DB::transaction(function () use ($data, $request) {
                $reciter = $this->reciterService->create($data);
                $this->logger->logCreate($reciter, $request->user());

                return $reciter;
            });

            // Return JSON for AJAX requests
            if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => true,
                    'message' => __('admin.reciter_created'),
                    'data' => $reciter,
                ], 201);
            }

            return redirect()
                ->route('admin.reciters.index')
                ->with('success', __('admin.reciter_created'));
        } catch (\Throwable $e) {
            if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => false,
                    'message' => __('admin.reciter_create_failed'),
                ], 422);
            }

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => __('admin.reciter_create_failed')]);
        }
    }

    /**
     * Display the specified reciter
     *
     * GET /su/portal/v1/admin/reciters/{id}
     */
    public function show(int $id): View
    {
        $reciter = $this->reciterService->findOrFail($id);
        $recitationsCount = $this->reciterService->countRecitations($reciter);

        // All 114 surahs merged with this reciter's recitation (when present),
        // built in two queries (surahs + recitations) and merged in PHP.
        $surahs = $this->recitationService->getReciterSurahs($reciter->id);

        return view('admin.reciters.show', compact('reciter', 'recitationsCount', 'surahs'));
    }

    /**
     * Show the form for editing the specified reciter
     *
     * GET /su/portal/v1/admin/reciters/{id}/edit
     */
    public function edit(int $id): View
    {
        $reciter = $this->reciterService->findOrFail($id);
        $styles = self::STYLES;

        return view('admin.reciters.edit', compact('reciter', 'styles'));
    }

    /**
     * Update the specified reciter
     *
     * PUT/PATCH /su/portal/v1/admin/reciters/{id}
     */
    public function update(UpdateReciterRequest $request, int $id): RedirectResponse|JsonResponse
    {
        try {
            $data = UpdateReciterData::fromRequest($request);
            $reciter = DB::transaction(function () use ($id, $data, $request) {
                $reciter = $this->reciterService->findOrFail($id);
                $oldValues = $reciter->toArray();
                $reciter = $this->reciterService->update($reciter, $data);
                $this->logger->logUpdate($reciter, $oldValues, $reciter->toArray(), $request->user());

                return $reciter;
            });

            // Return JSON for AJAX requests
            if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => true,
                    'message' => __('admin.reciter_updated'),
                    'data' => $reciter,
                ]);
            }

            return redirect()
                ->route('admin.reciters.index')
                ->with('success', __('admin.reciter_updated'));
        } catch (\Throwable $e) {
            if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => false,
                    'message' => __('admin.reciter_update_failed'),
                ], 422);
            }

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => __('admin.reciter_update_failed')]);
        }
    }

    /**
     * Remove the specified reciter
     *
     * DELETE /su/portal/v1/admin/reciters/{id}
     */
    public function destroy(Request $request, int $id): RedirectResponse|JsonResponse
    {
        try {
            DB::transaction(function () use ($id, $request) {
                $reciter = $this->reciterService->findOrFail($id);
                $this->reciterService->delete($reciter);
                $this->logger->logDelete($reciter, $request->user());
            });

            // Return JSON for AJAX requests
            if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => true,
                    'message' => __('admin.reciter_deleted'),
                ]);
            }

            return redirect()
                ->route('admin.reciters.index')
                ->with('success', __('admin.reciter_deleted'));
        } catch (\Throwable $e) {
            if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => false,
                    'message' => __('admin.reciter_delete_failed'),
                ], 422);
            }

            return redirect()
                ->back()
                ->withErrors(['error' => __('admin.reciter_delete_failed')]);
        }
    }

    /**
     * Bulk delete multiple reciters
     *
     * POST /su/portal/v1/admin/reciters/bulk-delete
     */
    public function bulkDelete(Request $request): RedirectResponse|JsonResponse
    {
        try {
            $request->validate([
                'ids' => ['required', 'array', 'min:1'],
                'ids.*' => ['integer', 'exists:quran_reciters,id'],
            ]);

            $ids = $request->input('ids');
            $deleted = DB::transaction(function () use ($ids, $request) {
                $count = $this->reciterService->bulkDelete($ids);
                $this->logger->logBulkAction(
                    'bulk_delete',
                    Reciter::class,
                    $ids,
                    $request->user()
                );

                return $count;
            });

            // Return JSON for AJAX requests
            if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => true,
                    'message' => __('admin.reciters_bulk_deleted', ['count' => $deleted]),
                    'count' => $deleted,
                ]);
            }

            return redirect()
                ->route('admin.reciters.index')
                ->with('success', __('admin.reciters_bulk_deleted', ['count' => $deleted]));
        } catch (\Throwable $e) {
            if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => false,
                    'message' => __('admin.reciters_bulk_delete_failed'),
                ], 422);
            }

            return redirect()
                ->back()
                ->withErrors(['error' => __('admin.reciters_bulk_delete_failed')]);
        }
    }

    /**
     * Reorder reciters
     *
     * POST /su/portal/v1/admin/reciters/reorder
     */
    public function reorder(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'ids' => 'required|array|min:1',
                'ids.*' => 'required|integer|exists:quran_reciters,id',
            ]);

            $ids = $request->input('ids');

            DB::transaction(function () use ($ids, $request) {
                $this->reciterService->reorder($ids);
                $this->logger->logCustomAction(
                    'reorder_reciters',
                    Reciter::class,
                    $request->user(),
                    null,
                    ['ids' => $ids]
                );
            });

            return response()->json([
                'success' => true,
                'message' => __('admin.reciters_reordered'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => __('admin.reciters_reorder_failed'),
            ], 422);
        }
    }

    /**
     * Toggle featured status
     *
     * POST /su/portal/v1/admin/reciters/{id}/toggle-featured
     */
    public function toggleFeatured(Request $request, int $id): JsonResponse
    {
        try {
            $reciter = DB::transaction(function () use ($id, $request) {
                $reciter = $this->reciterService->findOrFail($id);
                $oldFeatured = $reciter->is_featured;
                $reciter = $this->reciterService->toggleFeatured($reciter);
                $this->logger->logUpdate(
                    $reciter,
                    ['is_featured' => $oldFeatured],
                    ['is_featured' => $reciter->is_featured],
                    $request->user()
                );

                return $reciter;
            });

            return response()->json([
                'success' => true,
                'message' => __('admin.reciter_featured_toggled'),
                'data' => [
                    'is_featured' => $reciter->is_featured,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => __('admin.reciter_featured_toggle_failed'),
            ], 422);
        }
    }

    /**
     * Search reciters
     *
     * GET /su/portal/v1/admin/reciters/search
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'query' => 'required|string|min:2',
            ]);

            $query = $request->input('query');
            $reciters = $this->reciterService->search($query);

            return response()->json([
                'success' => true,
                'data' => $reciters,
                'count' => $reciters->count(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => __('admin.reciter_search_failed'),
            ], 422);
        }
    }
}
