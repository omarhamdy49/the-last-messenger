<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Hadith\Models\HadithNarrator;
use App\Domain\Hadith\Models\HadithGradingLevel;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HadithController
{
    /**
     * Display a listing of hadiths.
     */
    public function index(Request $request): View
    {
        $query = Hadith::with([
            'book:id,title_ar,title_en,collection_id',
            'book.collection:id,name_ar,name_en',
            'translations' => fn ($q) => $q->where('language_code', 'ar')->select('id', 'hadith_id', 'language_code', 'text')
        ]);

        // Collection filter
        if ($request->filled('collection_id')) {
            $query->whereHas('book', fn ($q) => $q->where('collection_id', $request->input('collection_id')));
        }

        // Book filter
        if ($request->filled('book_id')) {
            $query->where('book_id', $request->input('book_id'));
        }

        // Grading filter
        if ($request->filled('grading_status')) {
            $query->where('grading_status', $request->input('grading_status'));
        }

        // Full-text search (hadith number, hadith text, or book title)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('hadith_number', 'like', "%{$search}%")
                  ->orWhereHas('book', fn ($q) => $q->where('title_ar', 'like', "%{$search}%"))
                  ->orWhereHas('translations', fn ($q) => $q->where('language_code', 'ar')->where('text', 'like', "%{$search}%"));
            });
        }

        $hadiths = $query->orderBy('hadith_number', 'asc')->paginate(20);

        // Get distinct collections
        $collections = HadithCollection::select('id', 'name_ar', 'name_en')
            ->orderBy('name_ar')
            ->get();

        // Get books (filtered by collection if selected)
        $bookQuery = HadithBook::select('id', 'title_ar', 'title_en', 'collection_id');
        if ($request->filled('collection_id')) {
            $bookQuery->where('collection_id', $request->input('collection_id'));
        }
        $books = $bookQuery->orderBy('title_ar')->get();

        // Get all books for dropdown if no collection selected
        if (!$request->filled('collection_id')) {
            $books = HadithBook::select('id', 'title_ar', 'title_en', 'collection_id')->orderBy('title_ar')->get();
        }

        $gradingLevels = HadithGradingLevel::select('id', 'grade_ar', 'grade_en')->orderBy('grade_ar')->get();

        return view('admin.hadiths.index-cards', compact('hadiths', 'collections', 'books', 'gradingLevels'));
    }

    /**
     * Show the form for creating a new hadith.
     */
    public function create(): View
    {
        $books = HadithBook::select('id', 'title_ar', 'title_en')->orderBy('title_ar')->get();
        $narrators = HadithNarrator::select('id', 'name_ar')->orderBy('name_ar')->get();
        $gradingLevels = HadithGradingLevel::select('id', 'grade_ar', 'grade_en')->get();

        return view('admin.hadiths.create', compact('books', 'narrators', 'gradingLevels'));
    }

    /**
     * Store a newly created hadith in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'book_id' => 'required|exists:hadith_books,id',
            'hadith_number' => 'required|string|max:50',
            'grading_status' => 'nullable|string|max:50',
            'companion_id' => 'nullable|integer',
        ]);

        Hadith::create($validated);

        return redirect()->route('admin.hadith.index')
            ->with('success', __('admin.hadith_created_successfully'));
    }

    /**
     * Display the specified hadith.
     */
    public function show(Hadith $hadith): View
    {
        $hadith->load([
            'book:id,title_ar,title_en,collection_id',
            'book.collection:id,name_ar,name_en',
            'translations'
        ]);

        return view('admin.hadiths.show', compact('hadith'));
    }

    /**
     * Show the form for editing the specified hadith.
     */
    public function edit(Hadith $hadith): View
    {
        $hadith->load(['book']);
        $books = HadithBook::select('id', 'title_ar', 'title_en')->orderBy('title_ar')->get();
        $narrators = HadithNarrator::select('id', 'name_ar')->orderBy('name_ar')->get();
        $gradingLevels = HadithGradingLevel::select('id', 'grade_ar', 'grade_en')->get();

        return view('admin.hadiths.edit', compact('hadith', 'books', 'narrators', 'gradingLevels'));
    }

    /**
     * Update the specified hadith in storage.
     */
    public function update(Request $request, Hadith $hadith): RedirectResponse
    {
        $validated = $request->validate([
            'book_id' => 'required|exists:hadith_books,id',
            'hadith_number' => 'required|string|max:50',
            'grading_status' => 'nullable|string|max:50',
            'companion_id' => 'nullable|integer',
        ]);

        $hadith->update($validated);

        return redirect()->route('admin.hadith.show', $hadith)
            ->with('success', __('admin.hadith_updated_successfully'));
    }

    /**
     * Remove the specified hadith from storage.
     */
    public function destroy(Hadith $hadith): RedirectResponse
    {
        $hadith->delete();

        return redirect()->route('admin.hadith.index')
            ->with('success', __('admin.hadith_deleted_successfully'));
    }

    /**
     * Search hadiths.
     */
    public function search(Request $request)
    {
        $search = $request->input('q');

        $hadiths = Hadith::with(['book:id,title_ar'])
            ->where('hadith_number', 'like', "%{$search}%")
            ->orWhereHas('book', fn ($q) => $q->where('title_ar', 'like', "%{$search}%"))
            ->limit(10)
            ->get();

        return response()->json($hadiths);
    }

    /**
     * Bulk delete hadiths.
     */
    public function bulkDelete(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:hadiths,id',
        ]);

        Hadith::whereIn('id', $validated['ids'])->delete();

        return redirect()->route('admin.hadith.index')
            ->with('success', __('admin.hadiths_deleted_successfully'));
    }
}
