<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Hadith\Services\HadithService;
use App\Domain\Shared\Services\AdminLoggerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreHadithRequest;
use App\Http\Requests\Admin\UpdateHadithRequest;
use App\Http\Resources\HadithResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin Hadith Controller
 * Handles hadith management for admin portal
 */
class HadithController extends Controller
{
    /**
     * Constructor
     */
    public function __construct(
        protected HadithService $hadithService,
        protected AdminLoggerService $logger
    ) {}

    /**
     * Display a listing of all hadith
     *
     * GET /su/portal/v1/admin/hadith
     */
    public function index(Request $request): View|JsonResponse
    {
        $perPage = $request->integer('per_page', 50);

        // Build filters array
        $filters = [
            'book_id' => $request->input('book_id'),
            'chapter_id' => $request->input('chapter_id'),
            'grade' => $request->input('grade'),
            'narrator_id' => $request->input('narrator_id'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'search' => $request->input('search'),
        ];

        // Remove empty filters
        $filters = array_filter($filters, fn ($value) => $value !== null && $value !== '');

        // Get hadith with filters
        if (! empty($filters)) {
            $hadith = $this->hadithService->paginateWithFilters($filters, $perPage);
        } else {
            $hadith = $this->hadithService->paginate(
                $perPage,
                ['*'],
                ['narrators', 'book', 'chapter']
            );
        }

        $stats = [
            'total' => $this->hadithService->count(),
        ];

        // Return JSON for AJAX requests
        if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true,
                'data' => HadithResource::collection($hadith->items()),
                'stats' => $stats,
                'meta' => [
                    'current_page' => $hadith->currentPage(),
                    'per_page' => $hadith->perPage(),
                    'total' => $hadith->total(),
                    'last_page' => $hadith->lastPage(),
                ],
            ]);
        }

        return view('admin.hadith.index', compact('hadith', 'stats'));
    }

    /**
     * Show the form for creating a new hadith
     *
     * GET /su/portal/v1/admin/hadith/create
     */
    public function create(): View
    {
        return view('admin.hadith.create');
    }

    /**
     * Store a newly created hadith
     *
     * POST /su/portal/v1/admin/hadith
     */
    public function store(StoreHadithRequest $request): RedirectResponse
    {
        $hadith = $this->hadithService->create($request->validated());

        // Log the creation
        $this->logger->logCreate($hadith, $request->user());

        return redirect()
            ->route('admin.hadith.index')
            ->with('success', 'تم إنشاء الحديث بنجاح');
    }

    /**
     * Display the specified hadith
     *
     * GET /su/portal/v1/admin/hadith/{id}
     */
    public function show(int $id): View
    {
        $hadith = $this->hadithService->find($id, ['narrators', 'book', 'chapter']);

        if (! $hadith) {
            abort(404, 'Hadith not found');
        }

        return view('admin.hadith.show', compact('hadith'));
    }

    /**
     * Show the form for editing the specified hadith
     *
     * GET /su/portal/v1/admin/hadith/{id}/edit
     */
    public function edit(int $id): View
    {
        $hadith = $this->hadithService->find($id, ['narrators', 'book', 'chapter']);

        if (! $hadith) {
            abort(404, 'Hadith not found');
        }

        return view('admin.hadith.edit', compact('hadith'));
    }

    /**
     * Update the specified hadith
     *
     * PUT/PATCH /su/portal/v1/admin/hadith/{id}
     */
    public function update(UpdateHadithRequest $request, int $id): RedirectResponse
    {
        // Get old values before update
        $oldHadith = $this->hadithService->find($id);

        if (! $oldHadith) {
            abort(404, 'Hadith not found');
        }

        $oldValues = $oldHadith->toArray();

        // Perform update
        $this->hadithService->update($id, $request->validated());

        $hadith = $this->hadithService->find($id, ['narrators']);

        // Log the update with diff
        $this->logger->logUpdate($hadith, $oldValues, $hadith->toArray(), $request->user());

        return redirect()
            ->route('admin.hadith.index')
            ->with('success', 'تم تحديث الحديث بنجاح');
    }

    /**
     * Remove the specified hadith
     *
     * DELETE /su/portal/v1/admin/hadith/{id}
     */
    public function destroy(int $id): RedirectResponse
    {
        // Get model before deletion
        $hadith = $this->hadithService->find($id);

        if (! $hadith) {
            abort(404, 'Hadith not found');
        }

        // Perform deletion
        $this->hadithService->delete($id);

        // Log the deletion
        $this->logger->logDelete($hadith, request()->user());

        return redirect()
            ->route('admin.hadith.index')
            ->with('success', 'تم حذف الحديث بنجاح');
    }

    /**
     * Bulk delete hadith
     *
     * POST /su/portal/v1/admin/hadith/bulk-delete
     */
    public function bulkDelete(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:hadith,id'],
        ]);

        $ids = $request->input('ids');
        $deleted = 0;
        foreach ($ids as $id) {
            try {
                $this->hadithService->delete($id);
                $deleted++;
            } catch (\Exception $e) {
                // Continue deleting
            }
        }

        // Log bulk action
        $this->logger->logBulkAction(
            'bulk_delete',
            \App\Domain\Hadith\Models\Hadith::class,
            $ids,
            $request->user()
        );

        return redirect()
            ->route('admin.hadith.index')
            ->with('success', "تم حذف {$deleted} حديث بنجاح");
    }

    /**
     * Search hadith (AJAX endpoint)
     *
     * GET /su/portal/v1/admin/hadith/search
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = $request->input('q');
        $perPage = $request->integer('per_page', 15);

        $hadith = $this->hadithService->search($query, $perPage);

        return response()->json([
            'success' => true,
            'data' => $hadith->items(),
            'meta' => [
                'current_page' => $hadith->currentPage(),
                'per_page' => $hadith->perPage(),
                'total' => $hadith->total(),
                'last_page' => $hadith->lastPage(),
            ],
        ]);
    }

    /**
     * Get all hadith books for selector (AJAX endpoint)
     *
     * GET /su/portal/v1/admin/hadith/books
     */
    public function getBooks(): JsonResponse
    {
        $books = \App\Domain\Hadith\Models\HadithBook::select('id', 'name', 'slug')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $books,
        ]);
    }

    /**
     * Get chapters for specific book (AJAX endpoint)
     *
     * GET /su/portal/v1/admin/hadith/chapters?book_id={id}
     */
    public function getChapters(Request $request): JsonResponse
    {
        $request->validate([
            'book_id' => ['required', 'integer', 'exists:hadith_books,id'],
        ]);

        $bookId = $request->integer('book_id');
        $chapters = $this->hadithService->getChaptersForBook($bookId);

        return response()->json([
            'success' => true,
            'data' => $chapters,
        ]);
    }

    /**
     * Get all narrators for selector (AJAX endpoint)
     *
     * GET /su/portal/v1/admin/hadith/narrators
     */
    public function getNarrators(Request $request): JsonResponse
    {
        $search = $request->input('search');

        $query = \App\Domain\Hadith\Models\HadithNarrator::select('id', 'name', 'full_name');

        if ($search) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('full_name', 'like', "%{$search}%");
        }

        $narrators = $query->orderBy('name')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $narrators,
        ]);
    }
}
