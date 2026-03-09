<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Quran\Models\Surah;
use App\Domain\Quran\Models\Tafseer;
use App\Domain\Quran\Services\QuranCacheKeys;
use App\Domain\Quran\Services\TafseerService;
use App\Domain\Shared\Services\AdminLoggerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTafseerRequest;
use App\Http\Requests\Admin\UpdateTafseerRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Admin Tafseer Controller
 * Handles Quran Tafseer (interpretation) management for admin portal
 */
class TafseerController extends Controller
{
    /**
     * Constructor
     */
    public function __construct(
        protected TafseerService $tafseerService,
        protected AdminLoggerService $logger
    ) {}

    /**
     * Display a listing of all tafseer entries
     *
     * GET /su/portal/v1/admin/tafseer
     */
    public function index(Request $request): View|JsonResponse
    {
        $perPage = $request->integer('per_page', 15);
        $surahId = $request->integer('surah_id');
        $source = $request->input('source');
        $search = $request->input('search');
        $sortBy = $request->input('sort_by', 'verse_reference');

        $tafseer = $this->tafseerService->paginateWithFilters(
            perPage: $perPage,
            surahId: $surahId ?: null,
            source: $source,
            search: $search,
            sortBy: $sortBy
        );

        $stats = [
            'total' => $this->tafseerService->count(),
            'sources' => count($this->tafseerService->getAvailableSources()),
            'surahs_covered' => $this->tafseerService->countUniqueSurahs(),
        ];

        // Return JSON for AJAX requests
        if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true,
                'data' => $tafseer->items(),
                'stats' => $stats,
                'meta' => [
                    'current_page' => $tafseer->currentPage(),
                    'per_page' => $tafseer->perPage(),
                    'total' => $tafseer->total(),
                    'last_page' => $tafseer->lastPage(),
                ],
            ]);
        }

        // Get surahs for filter dropdown
        $surahs = $this->getSurahsForSelector();
        $sources = $this->tafseerService->getSourceNames();

        return view('admin.tafseer.index', compact('tafseer', 'stats', 'surahs', 'sources'));
    }

    /**
     * Show the form for creating a new tafseer
     *
     * GET /su/portal/v1/admin/tafseer/create
     */
    public function create(): View
    {
        $surahs = $this->getSurahsForSelector();
        $sources = $this->tafseerService->getSourceNames();

        return view('admin.tafseer.create', compact('surahs', 'sources'));
    }

    /**
     * Store a newly created tafseer
     *
     * POST /su/portal/v1/admin/tafseer
     */
    public function store(StoreTafseerRequest $request): RedirectResponse|JsonResponse
    {
        try {
            $tafseer = $this->tafseerService->create($request->validated());

            // Invalidate tafseer caches affected by the new entry
            Cache::forget(QuranCacheKeys::tafseerByVerseReference($tafseer->verse_reference));
            Cache::forget(QuranCacheKeys::tafseerSources());
            Cache::forget(QuranCacheKeys::tafseerUniqueSurahs());

            // Log the creation
            $this->logger->logCreate($tafseer, $request->user());

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tafseer created successfully',
                    'data' => $tafseer,
                ], 201);
            }

            return redirect()->route('admin.tafseer.show', $tafseer)
                ->with('success', 'تم إنشاء التفسير بنجاح');
        } catch (\App\Exceptions\Domain\DuplicateResourceException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()
                ->withInput()
                ->withErrors(['duplicate' => $e->getMessage()]);
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create tafseer: '.$e->getMessage(),
                ], 400);
            }

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create tafseer']);
        }
    }

    /**
     * Display the specified tafseer
     *
     * GET /su/portal/v1/admin/tafseer/{id}
     */
    public function show(int $id): View
    {
        $tafseer = $this->tafseerService->getWithDetails($id);

        if (! $tafseer) {
            abort(404, 'Tafseer not found');
        }

        return view('admin.tafseer.show', compact('tafseer'));
    }

    /**
     * Show the form for editing the specified tafseer
     *
     * GET /su/portal/v1/admin/tafseer/{id}/edit
     */
    public function edit(int $id): View
    {
        $tafseer = $this->tafseerService->find($id);

        if (! $tafseer) {
            abort(404, 'Tafseer not found');
        }

        $surahs = $this->getSurahsForSelector();
        $sources = $this->tafseerService->getSourceNames();

        return view('admin.tafseer.edit', compact('tafseer', 'surahs', 'sources'));
    }

    /**
     * Update the specified tafseer
     *
     * PUT/PATCH /su/portal/v1/admin/tafseer/{id}
     */
    public function update(UpdateTafseerRequest $request, int $id): RedirectResponse|JsonResponse
    {
        try {
            // Get old values before update
            $oldTafseer = $this->tafseerService->find($id);

            if (! $oldTafseer) {
                abort(404, 'Tafseer not found');
            }

            $oldValues = $oldTafseer->toArray();

            // Perform update
            $tafseer = $this->tafseerService->updateTafseer($oldTafseer, $request->validated());

            // Invalidate tafseer caches
            Cache::forget(QuranCacheKeys::tafseerByVerseReference($tafseer->verse_reference));
            if (($oldValues['verse_reference'] ?? null) !== $tafseer->verse_reference) {
                Cache::forget(QuranCacheKeys::tafseerByVerseReference($oldValues['verse_reference'] ?? ''));
            }
            Cache::forget(QuranCacheKeys::tafseerSources());
            Cache::forget(QuranCacheKeys::tafseerUniqueSurahs());

            // Log the update with diff
            $this->logger->logUpdate($tafseer, $oldValues, $tafseer->toArray(), $request->user());

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tafseer updated successfully',
                    'data' => $tafseer,
                ]);
            }

            return redirect()->route('admin.tafseer.show', $tafseer)
                ->with('success', 'تم تحديث التفسير بنجاح');
        } catch (\App\Exceptions\Domain\DuplicateResourceException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()
                ->withInput()
                ->withErrors(['duplicate' => $e->getMessage()]);
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update tafseer: '.$e->getMessage(),
                ], 400);
            }

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update tafseer']);
        }
    }

    /**
     * Remove the specified tafseer
     *
     * DELETE /su/portal/v1/admin/tafseer/{id}
     */
    public function destroy(int $id): RedirectResponse|JsonResponse
    {
        try {
            // Get model before deletion
            $tafseer = $this->tafseerService->find($id);

            if (! $tafseer) {
                abort(404, 'Tafseer not found');
            }

            // Perform deletion
            $this->tafseerService->deleteTafseer($tafseer);

            // Invalidate tafseer caches
            Cache::forget(QuranCacheKeys::tafseerByVerseReference($tafseer->verse_reference));
            Cache::forget(QuranCacheKeys::tafseerSources());
            Cache::forget(QuranCacheKeys::tafseerUniqueSurahs());

            // Log the deletion
            $this->logger->logDelete($tafseer, request()->user());

            if (request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tafseer deleted successfully',
                ]);
            }

            return redirect()->route('admin.tafseer.index')
                ->with('success', 'تم حذف التفسير بنجاح');
        } catch (\Exception $e) {
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete tafseer: '.$e->getMessage(),
                ], 400);
            }

            return redirect()->route('admin.tafseer.index')
                ->withErrors(['error' => 'Failed to delete tafseer']);
        }
    }

    /**
     * Bulk delete tafseer
     *
     * POST /su/portal/v1/admin/tafseer/bulk-delete
     */
    public function bulkDelete(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:quran_tafseer,id'],
        ]);

        try {
            $ids = $request->input('ids');
            $deleted = $this->tafseerService->bulkDelete($ids);

            // Log bulk action
            $this->logger->logBulkAction(
                'bulk_delete',
                Tafseer::class,
                $ids,
                $request->user()
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Deleted {$deleted} tafseer entries",
                    'deleted_count' => $deleted,
                ]);
            }

            return redirect()->route('admin.tafseer.index')
                ->with('success', "تم حذف {$deleted} تفسير بنجاح");
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete tafseer: '.$e->getMessage(),
                ], 400);
            }

            return redirect()->route('admin.tafseer.index')
                ->withErrors(['error' => 'Failed to delete tafseer']);
        }
    }

    /**
     * Search tafseer
     *
     * GET /su/portal/v1/admin/tafseer/search
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = $request->input('q');
        $perPage = $request->integer('per_page', 15);

        $tafseer = $this->tafseerService->search($query, $perPage);

        return response()->json([
            'success' => true,
            'data' => $tafseer->items(),
            'meta' => [
                'current_page' => $tafseer->currentPage(),
                'last_page' => $tafseer->lastPage(),
                'per_page' => $tafseer->perPage(),
                'total' => $tafseer->total(),
            ],
        ]);
    }

    /**
     * Helper: Get surahs for selector dropdown
     *
     * @return \Illuminate\Support\Collection
     */
    private function getSurahsForSelector()
    {
        return cache()->remember(QuranCacheKeys::surahSelector(), now()->addMinutes(30), function () {
            return Surah::select(['id', 'revelation_order', 'name_ar'])
                ->orderBy('revelation_order')
                ->get();
        });
    }
}
