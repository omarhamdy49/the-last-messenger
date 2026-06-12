<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Quran;

use App\Domain\Quran\Services\QuranService;
use App\Domain\Shared\Services\AdminLoggerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreQuranVerseRequest;
use App\Http\Requests\Admin\UpdateQuranVerseRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin Quran Controller
 * Handles Quran verse management for admin portal
 */
class QuranController extends Controller
{
    /**
     * Constructor
     */
    public function __construct(
        protected QuranService $quranService,
        protected AdminLoggerService $logger
    ) {}

    /**
     * Display a listing of all Quran verses
     *
     * GET /su/portal/v1/admin/quran
     */
    public function index(Request $request): View|JsonResponse
    {
        $perPage = $request->integer('per_page', 15);
        $verses = $this->quranService->paginate($perPage, ['*'], []);

        $stats = [
            'total' => $this->quranService->count(),
            'surahs' => 114, // Total surahs in Quran
        ];

        // Return JSON for AJAX requests
        if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true,
                'data' => $verses->items(),
                'stats' => $stats,
                'meta' => [
                    'current_page' => $verses->currentPage(),
                    'per_page' => $verses->perPage(),
                    'total' => $verses->total(),
                    'last_page' => $verses->lastPage(),
                ],
            ]);
        }

        return view('admin.quran.index', compact('verses', 'stats'));
    }

    /**
     * Show the form for creating a new Quran verse
     *
     * GET /su/portal/v1/admin/quran/create
     */
    public function create(): View
    {
        return view('admin.quran.create');
    }

    /**
     * Store a newly created Quran verse
     *
     * POST /su/portal/v1/admin/quran
     */
    public function store(StoreQuranVerseRequest $request): RedirectResponse
    {
        $verse = $this->quranService->create($request->validated());

        // Log the creation
        $this->logger->logCreate($verse, $request->user());

        return redirect()->route('admin.quran.index')
            ->with('success', 'تم إنشاء الآية بنجاح');
    }

    /**
     * Display the specified Quran verse
     *
     * GET /su/portal/v1/admin/quran/{id}
     */
    public function show(int $id): View
    {
        $verse = $this->quranService->find($id);

        if (! $verse) {
            abort(404, 'Quran verse not found');
        }

        return view('admin.quran.show', compact('verse'));
    }

    /**
     * Show the form for editing the specified Quran verse
     *
     * GET /su/portal/v1/admin/quran/{id}/edit
     */
    public function edit(int $id): View
    {
        $verse = $this->quranService->find($id);

        if (! $verse) {
            abort(404, 'Quran verse not found');
        }

        return view('admin.quran.edit', compact('verse'));
    }

    /**
     * Update the specified Quran verse
     *
     * PUT/PATCH /su/portal/v1/admin/quran/{id}
     */
    public function update(UpdateQuranVerseRequest $request, int $id): RedirectResponse
    {
        // Get old values before update
        $oldVerse = $this->quranService->find($id);

        if (! $oldVerse) {
            abort(404, 'Quran verse not found');
        }

        $oldValues = $oldVerse->toArray();

        // Perform update
        $this->quranService->update($id, $request->validated());

        $verse = $this->quranService->find($id);

        // Log the update with diff
        $this->logger->logUpdate($verse, $oldValues, $verse->toArray(), $request->user());

        return redirect()->route('admin.quran.index')
            ->with('success', 'تم تحديث الآية بنجاح');
    }

    /**
     * Remove the specified Quran verse
     *
     * DELETE /su/portal/v1/admin/quran/{id}
     */
    public function destroy(int $id): RedirectResponse
    {
        // Get model before deletion
        $verse = $this->quranService->find($id);

        if (! $verse) {
            abort(404, 'Quran verse not found');
        }

        // Perform deletion
        $this->quranService->delete($id);

        // Log the deletion
        $this->logger->logDelete($verse, request()->user());

        return redirect()->route('admin.quran.index')
            ->with('success', 'تم حذف الآية بنجاح');
    }

    /**
     * Bulk delete Quran verses
     *
     * POST /su/portal/v1/admin/quran/bulk-delete
     */
    public function bulkDelete(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:quran_verses,id'],
        ]);

        $ids = $request->input('ids');
        $deleted = 0;
        foreach ($ids as $id) {
            try {
                $this->quranService->delete($id);
                $deleted++;
            } catch (\Throwable $e) {
                // Continue deleting
            }
        }

        // Log bulk action
        $this->logger->logBulkAction(
            'bulk_delete',
            \App\Domain\Quran\Models\QuranVerse::class,
            $ids,
            $request->user()
        );

        return redirect()->route('admin.quran.index')
            ->with('success', "تم حذف {$deleted} آية بنجاح");
    }

    /**
     * Search Quran verses
     *
     * GET /su/portal/v1/admin/quran/search
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = $request->input('q');
        $perPage = $request->integer('per_page', 15);

        $verses = $this->quranService->search($query, $perPage);

        return response()->json([
            'success' => true,
            'data' => $verses->items(),
            'meta' => [
                'current_page' => $verses->currentPage(),
                'last_page' => $verses->lastPage(),
                'per_page' => $verses->perPage(),
                'total' => $verses->total(),
            ],
        ]);
    }
}
