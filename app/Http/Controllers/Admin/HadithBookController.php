<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Shared\Services\AdminLoggerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreHadithBookRequest;
use App\Http\Requests\Admin\UpdateHadithBookRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin Hadith Book Controller
 * Handles hadith book management for admin portal
 */
class HadithBookController extends Controller
{
    /**
     * Constructor
     */
    public function __construct(
        protected AdminLoggerService $logger
    ) {}

    /**
     * Display a listing of hadith books
     *
     * GET /su/portal/v1/admin/hadith-books
     */
    public function index(Request $request): View|JsonResponse
    {
        $perPage = $request->integer('per_page', 50);
        $search = $request->string('search')->trim()->toString();

        // Build query
        $query = HadithBook::with(['collection', 'chapters']);

        // Apply search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('slug', 'like', "%{$search}%")
                  ->orWhere('author_name', 'like', "%{$search}%")
                  ->orWhereRaw("JSON_EXTRACT(name, '$.ar') LIKE ?", ["%{$search}%"])
                  ->orWhereRaw("JSON_EXTRACT(name, '$.en') LIKE ?", ["%{$search}%"]);
            });
        }

        // Apply filters
        if ($request->has('collection_id')) {
            $query->where('collection_id', $request->input('collection_id'));
        }

        if ($request->has('active')) {
            $query->where('active', $request->boolean('active'));
        }

        // Order by display order
        $query->ordered();

        $books = $query->paginate($perPage);

        // Get statistics
        $stats = [
            'total' => HadithBook::count(),
            'active' => HadithBook::where('active', true)->count(),
            'inactive' => HadithBook::where('active', false)->count(),
            'total_hadith' => HadithBook::sum('total_hadith_count'),
        ];

        // Return JSON for AJAX requests
        if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true,
                'data' => $books->items(),
                'stats' => $stats,
                'meta' => [
                    'current_page' => $books->currentPage(),
                    'per_page' => $books->perPage(),
                    'total' => $books->total(),
                    'last_page' => $books->lastPage(),
                ],
            ]);
        }

        // Get collections for filter dropdown
        $collections = HadithCollection::orderBy('name->ar')->get();

        return view('admin.hadith-books.index', compact('books', 'stats', 'collections'));
    }

    /**
     * Show the form for creating a new hadith book
     *
     * GET /su/portal/v1/admin/hadith-books/create
     */
    public function create(): View
    {
        $collections = HadithCollection::orderBy('name->ar')->get();

        return view('admin.hadith-books.create', compact('collections'));
    }

    /**
     * Store a newly created hadith book
     *
     * POST /su/portal/v1/admin/hadith-books
     */
    public function store(StoreHadithBookRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Create book
        $book = HadithBook::create($data);

        // Log the creation
        $this->logger->logCreate($book, $request->user());

        return redirect()
            ->route('admin.hadith-books.index')
            ->with('success', 'تم إنشاء الكتاب بنجاح');
    }

    /**
     * Display the specified hadith book
     *
     * GET /su/portal/v1/admin/hadith-books/{id}
     */
    public function show(int $id): View
    {
        $book = HadithBook::with(['collection', 'chapters', 'hadiths'])
            ->findOrFail($id);

        return view('admin.hadith-books.show', compact('book'));
    }

    /**
     * Show the form for editing the specified hadith book
     *
     * GET /su/portal/v1/admin/hadith-books/{id}/edit
     */
    public function edit(int $id): View
    {
        $book = HadithBook::with(['collection'])->findOrFail($id);
        $collections = HadithCollection::orderBy('name->ar')->get();

        return view('admin.hadith-books.edit', compact('book', 'collections'));
    }

    /**
     * Update the specified hadith book
     *
     * PUT/PATCH /su/portal/v1/admin/hadith-books/{id}
     */
    public function update(UpdateHadithBookRequest $request, int $id): RedirectResponse
    {
        $book = HadithBook::findOrFail($id);

        // Get old values before update
        $oldValues = $book->toArray();

        // Update book
        $book->update($request->validated());

        // Log the update with diff
        $this->logger->logUpdate($book, $oldValues, $book->fresh()->toArray(), $request->user());

        return redirect()
            ->route('admin.hadith-books.index')
            ->with('success', 'تم تحديث الكتاب بنجاح');
    }

    /**
     * Remove the specified hadith book
     *
     * DELETE /su/portal/v1/admin/hadith-books/{id}
     */
    public function destroy(int $id): RedirectResponse
    {
        $book = HadithBook::findOrFail($id);

        // Check if book has hadith
        if ($book->hadiths()->count() > 0) {
            return redirect()
                ->route('admin.hadith-books.index')
                ->with('error', 'لا يمكن حذف الكتاب لأنه يحتوي على أحاديث');
        }

        // Log the deletion
        $this->logger->logDelete($book, request()->user());

        // Delete book
        $book->delete();

        return redirect()
            ->route('admin.hadith-books.index')
            ->with('success', 'تم حذف الكتاب بنجاح');
    }

    /**
     * Bulk delete hadith books
     *
     * POST /su/portal/v1/admin/hadith-books/bulk-delete
     */
    public function bulkDelete(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:hadith_books,id'],
        ]);

        $ids = $request->input('ids');
        $deleted = 0;

        foreach ($ids as $id) {
            $book = HadithBook::find($id);

            if ($book && $book->hadiths()->count() === 0) {
                $book->delete();
                $deleted++;
            }
        }

        // Log bulk action
        $this->logger->logBulkAction(
            'bulk_delete',
            HadithBook::class,
            $ids,
            $request->user()
        );

        return redirect()
            ->route('admin.hadith-books.index')
            ->with('success', "تم حذف {$deleted} كتاب بنجاح");
    }

    /**
     * Search hadith books (AJAX endpoint)
     *
     * GET /su/portal/v1/admin/hadith-books/search
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = $request->input('q');
        $perPage = $request->integer('per_page', 15);

        $books = HadithBook::where(function ($q) use ($query) {
            $q->where('slug', 'like', "%{$query}%")
              ->orWhere('author_name', 'like', "%{$query}%")
              ->orWhereRaw("JSON_EXTRACT(name, '$.ar') LIKE ?", ["%{$query}%"])
              ->orWhereRaw("JSON_EXTRACT(name, '$.en') LIKE ?", ["%{$query}%"]);
        })
        ->ordered()
        ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $books->items(),
            'meta' => [
                'current_page' => $books->currentPage(),
                'per_page' => $books->perPage(),
                'total' => $books->total(),
                'last_page' => $books->lastPage(),
            ],
        ]);
    }

    /**
     * Toggle active status
     *
     * POST /su/portal/v1/admin/hadith-books/{id}/toggle-active
     */
    public function toggleActive(int $id): JsonResponse
    {
        $book = HadithBook::findOrFail($id);
        $book->active = !$book->active;
        $book->save();

        // Log the update
        $this->logger->logUpdate(
            $book,
            ['active' => !$book->active],
            ['active' => $book->active],
            request()->user()
        );

        return response()->json([
            'success' => true,
            'active' => $book->active,
            'message' => $book->active ? 'تم تفعيل الكتاب' : 'تم إلغاء تفعيل الكتاب',
        ]);
    }

    /**
     * Update hadith count for a book
     *
     * POST /su/portal/v1/admin/hadith-books/{id}/update-count
     */
    public function updateCount(int $id): JsonResponse
    {
        $book = HadithBook::findOrFail($id);
        $book->updateHadithCount();

        return response()->json([
            'success' => true,
            'count' => $book->total_hadith_count,
            'message' => 'تم تحديث عدد الأحاديث',
        ]);
    }
}
