<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Quran\Models\Reciter;
use App\Domain\Quran\Services\ReciterService;
use App\Domain\Shared\Services\AdminLoggerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreReciterRequest;
use App\Http\Requests\Admin\UpdateReciterRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Admin Reciter Controller
 * Manages Quran reciters (Qaris) in admin portal
 */
class ReciterController extends Controller
{
    /**
     * Constructor
     *
     * @param ReciterService $reciterService
     * @param AdminLoggerService $logger
     */
    public function __construct(
        protected ReciterService $reciterService,
        protected AdminLoggerService $logger
    ) {}

    /**
     * Display a listing of all reciters
     *
     * GET /su/portal/v1/admin/reciters
     *
     * @param Request $request
     * @return View|JsonResponse
     */
    public function index(Request $request): View|JsonResponse
    {
        $perPage = $request->integer('per_page', 50);
        $search = $request->input('search');
        $country = $request->input('country');
        $style = $request->input('style');

        // Build query
        $query = Reciter::query()->orderBy('display_order')->orderByDesc('created_at');

        // Apply filters
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereJsonContains('name->ar', $search)
                    ->orWhereJsonContains('name->en', $search)
                    ->orWhereJsonContains('name->fr', $search)
                    ->orWhereJsonContains('name->tr', $search)
                    ->orWhereJsonContains('name->ur', $search);
            });
        }

        if ($country) {
            $query->where('country', $country);
        }

        if ($style) {
            $query->where('style', $style);
        }

        // Paginate
        $reciters = $query->paginate($perPage);

        // Calculate stats
        $stats = [
            'total' => Reciter::count(),
            'featured' => Reciter::where('is_featured', true)->count(),
            'countries' => Reciter::distinct('country')->whereNotNull('country')->count('country'),
            // Recitations count will be 0 until recitations table is implemented
            'total_recitations' => 0,
        ];

        // Get unique countries for filter
        $countries = Reciter::distinct('country')
            ->whereNotNull('country')
            ->pluck('country')
            ->sort()
            ->values();

        // Available recitation styles
        $styles = [
            'Tajweed',
            'Qaloon',
            'Warsh',
            'Hafs',
            'Kalon',
            'Duri',
            'Kasai',
        ];

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
     *
     * @return View
     */
    public function create(): View
    {
        // Available recitation styles
        $styles = [
            'Tajweed',
            'Qaloon',
            'Warsh',
            'Hafs',
            'Kalon',
            'Duri',
            'Kasai',
        ];

        return view('admin.reciters.create', compact('styles'));
    }

    /**
     * Store a newly created reciter
     *
     * POST /su/portal/v1/admin/reciters
     *
     * @param StoreReciterRequest $request
     * @return RedirectResponse|JsonResponse
     */
    public function store(StoreReciterRequest $request): RedirectResponse|JsonResponse
    {
        try {
            $data = $request->validated();

            // Handle profile image upload
            if ($request->hasFile('profile_image')) {
                $data['profile_image'] = $request->file('profile_image');
            }

            // Create reciter
            $reciter = $this->reciterService->create($data);

            // Log the creation
            $this->logger->logCreate($reciter, $request->user());

            // Return JSON for AJAX requests
            if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => true,
                    'message' => 'Reciter created successfully',
                    'data' => $reciter,
                ], 201);
            }

            return redirect()
                ->route('admin.reciters.index')
                ->with('success', 'تم إنشاء القارئ بنجاح');
        } catch (\Exception $e) {
            if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create reciter',
                    'error' => $e->getMessage(),
                ], 422);
            }

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'فشل إنشاء القارئ: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified reciter
     *
     * GET /su/portal/v1/admin/reciters/{id}
     *
     * @param int $id
     * @return View
     */
    public function show(int $id): View
    {
        $reciter = Reciter::findOrFail($id);

        // Count recitations (will be 0 until recitations table is implemented)
        $recitationsCount = $this->reciterService->countRecitations($reciter);

        return view('admin.reciters.show', compact('reciter', 'recitationsCount'));
    }

    /**
     * Show the form for editing the specified reciter
     *
     * GET /su/portal/v1/admin/reciters/{id}/edit
     *
     * @param int $id
     * @return View
     */
    public function edit(int $id): View
    {
        $reciter = Reciter::findOrFail($id);

        // Available recitation styles
        $styles = [
            'Tajweed',
            'Qaloon',
            'Warsh',
            'Hafs',
            'Kalon',
            'Duri',
            'Kasai',
        ];

        return view('admin.reciters.edit', compact('reciter', 'styles'));
    }

    /**
     * Update the specified reciter
     *
     * PUT/PATCH /su/portal/v1/admin/reciters/{id}
     *
     * @param UpdateReciterRequest $request
     * @param int $id
     * @return RedirectResponse|JsonResponse
     */
    public function update(UpdateReciterRequest $request, int $id): RedirectResponse|JsonResponse
    {
        try {
            // Get old values before update
            $reciter = Reciter::findOrFail($id);
            $oldValues = $reciter->toArray();

            $data = $request->validated();

            // Handle profile image upload
            if ($request->hasFile('profile_image')) {
                $data['profile_image'] = $request->file('profile_image');
            }

            // Update reciter
            $reciter = $this->reciterService->update($reciter, $data);

            // Log the update with diff
            $this->logger->logUpdate($reciter, $oldValues, $reciter->toArray(), $request->user());

            // Return JSON for AJAX requests
            if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => true,
                    'message' => 'Reciter updated successfully',
                    'data' => $reciter,
                ]);
            }

            return redirect()
                ->route('admin.reciters.index')
                ->with('success', 'تم تحديث القارئ بنجاح');
        } catch (\Exception $e) {
            if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update reciter',
                    'error' => $e->getMessage(),
                ], 422);
            }

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'فشل تحديث القارئ: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified reciter
     *
     * DELETE /su/portal/v1/admin/reciters/{id}
     *
     * @param Request $request
     * @param int $id
     * @return RedirectResponse|JsonResponse
     */
    public function destroy(Request $request, int $id): RedirectResponse|JsonResponse
    {
        try {
            // Get model before deletion
            $reciter = Reciter::findOrFail($id);

            // Delete reciter and cleanup image
            $this->reciterService->delete($reciter);

            // Log the deletion
            $this->logger->logDelete($reciter, $request->user());

            // Return JSON for AJAX requests
            if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => true,
                    'message' => 'Reciter deleted successfully',
                ]);
            }

            return redirect()
                ->route('admin.reciters.index')
                ->with('success', 'تم حذف القارئ بنجاح');
        } catch (\Exception $e) {
            if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete reciter',
                    'error' => $e->getMessage(),
                ], 422);
            }

            return redirect()
                ->back()
                ->withErrors(['error' => 'فشل حذف القارئ: ' . $e->getMessage()]);
        }
    }

    /**
     * Bulk delete multiple reciters
     *
     * POST /su/portal/v1/admin/reciters/bulk-delete
     *
     * @param Request $request
     * @return RedirectResponse|JsonResponse
     */
    public function bulkDelete(Request $request): RedirectResponse|JsonResponse
    {
        try {
            $request->validate([
                'ids' => ['required', 'array', 'min:1'],
                'ids.*' => ['integer', 'exists:quran_reciters,id'],
            ]);

            $ids = $request->input('ids');

            // Bulk delete reciters and cleanup images
            $deleted = $this->reciterService->bulkDelete($ids);

            // Log bulk action
            $this->logger->logBulkAction(
                'bulk_delete',
                Reciter::class,
                $ids,
                $request->user()
            );

            // Return JSON for AJAX requests
            if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => true,
                    'message' => "$deleted reciters deleted successfully",
                    'count' => $deleted,
                ]);
            }

            return redirect()
                ->route('admin.reciters.index')
                ->with('success', "تم حذف {$deleted} قارئ بنجاح");
        } catch (\Exception $e) {
            if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete reciters',
                    'error' => $e->getMessage(),
                ], 422);
            }

            return redirect()
                ->back()
                ->withErrors(['error' => 'فشل حذف القراء: ' . $e->getMessage()]);
        }
    }

    /**
     * Reorder reciters
     *
     * POST /su/portal/v1/admin/reciters/reorder
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function reorder(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'ids' => 'required|array|min:1',
                'ids.*' => 'required|integer|exists:quran_reciters,id',
            ]);

            $ids = $request->input('ids');

            // Reorder reciters
            $this->reciterService->reorder($ids);

            // Log the action
            $this->logger->logCustomAction(
                'reorder_reciters',
                Reciter::class,
                $request->user(),
                null,
                ['ids' => $ids]
            );

            return response()->json([
                'success' => true,
                'message' => 'Reciters reordered successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder reciters',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Toggle featured status
     *
     * POST /su/portal/v1/admin/reciters/{id}/toggle-featured
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function toggleFeatured(Request $request, int $id): JsonResponse
    {
        try {
            $reciter = Reciter::findOrFail($id);
            $oldFeatured = $reciter->is_featured;

            // Toggle featured status
            $reciter = $this->reciterService->toggleFeatured($reciter);

            // Log the action
            $this->logger->logUpdate(
                $reciter,
                ['is_featured' => $oldFeatured],
                ['is_featured' => $reciter->is_featured],
                $request->user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Featured status updated successfully',
                'data' => [
                    'is_featured' => $reciter->is_featured,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle featured status',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Search reciters
     *
     * GET /su/portal/v1/admin/reciters/search
     *
     * @param Request $request
     * @return JsonResponse
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
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search reciters',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
