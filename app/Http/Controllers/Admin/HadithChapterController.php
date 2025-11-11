<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithChapter;
use App\Domain\Shared\Services\AdminLoggerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreHadithChapterRequest;
use App\Http\Requests\Admin\UpdateHadithChapterRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin Hadith Chapter Controller
 * Handles hadith chapter management for admin portal
 */
class HadithChapterController extends Controller
{
    /**
     * Constructor
     */
    public function __construct(
        protected AdminLoggerService $logger
    ) {}

    /**
     * Display a listing of hadith chapters
     *
     * GET /su/portal/v1/admin/hadith-chapters
     */
    public function index(Request $request): View|JsonResponse
    {
        $perPage = $request->integer('per_page', 50);
        $search = $request->string('search')->trim()->toString();

        // Build query
        $query = HadithChapter::with(['book', 'hadiths']);

        // Apply search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('chapter_number', 'like', "%{$search}%")
                  ->orWhereRaw("JSON_EXTRACT(name, '$.ar') LIKE ?", ["%{$search}%"])
                  ->orWhereRaw("JSON_EXTRACT(name, '$.en') LIKE ?", ["%{$search}%"]);
            });
        }

        // Apply filters
        if ($request->has('book_id')) {
            $query->where('book_id', $request->input('book_id'));
        }

        // Order by book and display order
        $query->orderBy('book_id')->ordered();

        $chapters = $query->paginate($perPage);

        // Get statistics
        $stats = [
            'total' => HadithChapter::count(),
            'by_book' => HadithChapter::selectRaw('book_id, COUNT(*) as count')
                ->groupBy('book_id')
                ->get()
                ->pluck('count', 'book_id')
                ->toArray(),
        ];

        // Return JSON for AJAX requests
        if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true,
                'data' => $chapters->items(),
                'stats' => $stats,
                'meta' => [
                    'current_page' => $chapters->currentPage(),
                    'per_page' => $chapters->perPage(),
                    'total' => $chapters->total(),
                    'last_page' => $chapters->lastPage(),
                ],
            ]);
        }

        // Get books for filter dropdown
        $books = HadithBook::select('id', 'name', 'slug')->ordered()->get();

        return view('admin.hadith-chapters.index', compact('chapters', 'stats', 'books'));
    }

    /**
     * Show the form for creating a new hadith chapter
     *
     * GET /su/portal/v1/admin/hadith-chapters/create
     */
    public function create(Request $request): View
    {
        $books = HadithBook::select('id', 'name', 'slug')->ordered()->get();

        // Pre-select book if provided
        $selectedBookId = $request->input('book_id');

        return view('admin.hadith-chapters.create', compact('books', 'selectedBookId'));
    }

    /**
     * Store a newly created hadith chapter
     *
     * POST /su/portal/v1/admin/hadith-chapters
     */
    public function store(StoreHadithChapterRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Set display order if not provided
        if (!isset($data['display_order'])) {
            $lastChapter = HadithChapter::where('book_id', $data['book_id'])
                ->orderBy('display_order', 'desc')
                ->first();

            $data['display_order'] = $lastChapter ? $lastChapter->display_order + 1 : 1;
        }

        // Create chapter
        $chapter = HadithChapter::create($data);

        // Log the creation
        $this->logger->logCreate($chapter, $request->user());

        return redirect()
            ->route('admin.hadith-chapters.index', ['book_id' => $chapter->book_id])
            ->with('success', 'تم إنشاء الباب بنجاح');
    }

    /**
     * Display the specified hadith chapter
     *
     * GET /su/portal/v1/admin/hadith-chapters/{id}
     */
    public function show(int $id): View
    {
        $chapter = HadithChapter::with(['book', 'hadiths'])
            ->findOrFail($id);

        return view('admin.hadith-chapters.show', compact('chapter'));
    }

    /**
     * Show the form for editing the specified hadith chapter
     *
     * GET /su/portal/v1/admin/hadith-chapters/{id}/edit
     */
    public function edit(int $id): View
    {
        $chapter = HadithChapter::with(['book'])->findOrFail($id);
        $books = HadithBook::select('id', 'name', 'slug')->ordered()->get();

        return view('admin.hadith-chapters.edit', compact('chapter', 'books'));
    }

    /**
     * Update the specified hadith chapter
     *
     * PUT/PATCH /su/portal/v1/admin/hadith-chapters/{id}
     */
    public function update(UpdateHadithChapterRequest $request, int $id): RedirectResponse
    {
        $chapter = HadithChapter::findOrFail($id);

        // Get old values before update
        $oldValues = $chapter->toArray();

        // Update chapter
        $chapter->update($request->validated());

        // Log the update with diff
        $this->logger->logUpdate($chapter, $oldValues, $chapter->fresh()->toArray(), $request->user());

        return redirect()
            ->route('admin.hadith-chapters.index', ['book_id' => $chapter->book_id])
            ->with('success', 'تم تحديث الباب بنجاح');
    }

    /**
     * Remove the specified hadith chapter
     *
     * DELETE /su/portal/v1/admin/hadith-chapters/{id}
     */
    public function destroy(int $id): RedirectResponse
    {
        $chapter = HadithChapter::findOrFail($id);

        // Check if chapter has hadith
        if ($chapter->hadiths()->count() > 0) {
            return redirect()
                ->route('admin.hadith-chapters.index')
                ->with('error', 'لا يمكن حذف الباب لأنه يحتوي على أحاديث');
        }

        $bookId = $chapter->book_id;

        // Log the deletion
        $this->logger->logDelete($chapter, request()->user());

        // Delete chapter
        $chapter->delete();

        return redirect()
            ->route('admin.hadith-chapters.index', ['book_id' => $bookId])
            ->with('success', 'تم حذف الباب بنجاح');
    }

    /**
     * Bulk delete hadith chapters
     *
     * POST /su/portal/v1/admin/hadith-chapters/bulk-delete
     */
    public function bulkDelete(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:hadith_chapters,id'],
        ]);

        $ids = $request->input('ids');
        $deleted = 0;

        foreach ($ids as $id) {
            $chapter = HadithChapter::find($id);

            if ($chapter && $chapter->hadiths()->count() === 0) {
                $chapter->delete();
                $deleted++;
            }
        }

        // Log bulk action
        $this->logger->logBulkAction(
            'bulk_delete',
            HadithChapter::class,
            $ids,
            $request->user()
        );

        return redirect()
            ->route('admin.hadith-chapters.index')
            ->with('success', "تم حذف {$deleted} باب بنجاح");
    }

    /**
     * Search hadith chapters (AJAX endpoint)
     *
     * GET /su/portal/v1/admin/hadith-chapters/search
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2'],
            'book_id' => ['nullable', 'integer', 'exists:hadith_books,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = $request->input('q');
        $bookId = $request->input('book_id');
        $perPage = $request->integer('per_page', 15);

        $chapters = HadithChapter::with('book')
            ->where(function ($q) use ($query) {
                $q->where('chapter_number', 'like', "%{$query}%")
                  ->orWhereRaw("JSON_EXTRACT(name, '$.ar') LIKE ?", ["%{$query}%"])
                  ->orWhereRaw("JSON_EXTRACT(name, '$.en') LIKE ?", ["%{$query}%"]);
            })
            ->when($bookId, fn ($q) => $q->where('book_id', $bookId))
            ->ordered()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $chapters->items(),
            'meta' => [
                'current_page' => $chapters->currentPage(),
                'per_page' => $chapters->perPage(),
                'total' => $chapters->total(),
                'last_page' => $chapters->lastPage(),
            ],
        ]);
    }

    /**
     * Get chapters for a specific book (AJAX endpoint)
     *
     * GET /su/portal/v1/admin/hadith-chapters/by-book/{bookId}
     */
    public function byBook(int $bookId): JsonResponse
    {
        $chapters = HadithChapter::where('book_id', $bookId)
            ->ordered()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $chapters,
        ]);
    }

    /**
     * Reorder chapters
     *
     * POST /su/portal/v1/admin/hadith-chapters/reorder
     */
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'orders' => ['required', 'array'],
            'orders.*.id' => ['required', 'integer', 'exists:hadith_chapters,id'],
            'orders.*.display_order' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($request->input('orders') as $order) {
            HadithChapter::where('id', $order['id'])
                ->update(['display_order' => $order['display_order']]);
        }

        // Log bulk action
        $this->logger->logBulkAction(
            'reorder',
            HadithChapter::class,
            collect($request->input('orders'))->pluck('id')->toArray(),
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'تم إعادة ترتيب الأبواب بنجاح',
        ]);
    }
}
