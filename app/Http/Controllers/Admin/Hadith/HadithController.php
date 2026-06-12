<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Hadith;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Repositories\BookRepositoryInterface;
use App\Domain\Hadith\Repositories\CollectionRepositoryInterface;
use App\Domain\Hadith\Repositories\GradingLevelRepositoryInterface;
use App\Domain\Hadith\Repositories\HadithRepositoryInterface;
use App\Domain\Hadith\Repositories\NarratorRepositoryInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HadithController
{
    public function __construct(
        private readonly HadithRepositoryInterface $hadithRepository,
        private readonly CollectionRepositoryInterface $collectionRepository,
        private readonly BookRepositoryInterface $bookRepository,
        private readonly GradingLevelRepositoryInterface $gradingLevelRepository,
        private readonly NarratorRepositoryInterface $narratorRepository,
    ) {}

    /**
     * Display a listing of hadiths.
     */
    public function index(Request $request): View
    {
        $filters = array_filter([
            'collection_id' => $request->input('collection_id'),
            'book_id' => $request->input('book_id'),
            'grading_status' => $request->input('grading_status'),
        ]);

        $hadiths = $this->hadithRepository->listForAdmin(
            perPage: 20,
            search: (string) $request->input('search', ''),
            filters: $filters,
        );

        $collections = $this->collectionRepository->getAllForDropdown();

        $collectionId = $request->filled('collection_id')
            ? (int) $request->input('collection_id')
            : null;

        $books = $this->bookRepository->getAllForDropdown($collectionId);

        $gradingLevels = $this->gradingLevelRepository->getAllByRank();

        return view('admin.hadiths.index-cards', compact('hadiths', 'collections', 'books', 'gradingLevels'));
    }

    /**
     * Show the form for creating a new hadith.
     */
    public function create(): View
    {
        $books = $this->bookRepository->getAllForDropdown();
        $narrators = $this->narratorRepository->all(['id', 'name_ar']);
        $gradingLevels = $this->gradingLevelRepository->getAllByRank();

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

        $this->hadithRepository->create($validated);

        return redirect()->route('admin.hadith.index')
            ->with('success', __('admin.hadith_created_successfully'));
    }

    /**
     * Display the specified hadith.
     */
    public function show(Hadith $hadith): View
    {
        $hadith = $this->hadithRepository->getWithFullRelations($hadith->id);

        return view('admin.hadiths.show', compact('hadith'));
    }

    /**
     * Show the form for editing the specified hadith.
     */
    public function edit(Hadith $hadith): View
    {
        $hadith = $this->hadithRepository->find(
            $hadith->id,
            ['*'],
            ['book'],
        );

        $books = $this->bookRepository->getAllForDropdown();
        $narrators = $this->narratorRepository->all(['id', 'name_ar']);
        $gradingLevels = $this->gradingLevelRepository->getAllByRank();

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

        $this->hadithRepository->update($hadith->id, $validated);

        return redirect()->route('admin.hadith.show', $hadith)
            ->with('success', __('admin.hadith_updated_successfully'));
    }

    /**
     * Remove the specified hadith from storage.
     */
    public function destroy(Hadith $hadith): RedirectResponse
    {
        $this->hadithRepository->delete($hadith->id);

        return redirect()->route('admin.hadith.index')
            ->with('success', __('admin.hadith_deleted_successfully'));
    }

    /**
     * Search hadiths (admin AJAX endpoint).
     */
    public function search(Request $request): \Illuminate\Http\JsonResponse
    {
        $search = (string) $request->input('q', '');
        $perPage = $request->integer('per_page', 15);
        $locale = app()->getLocale();

        $filters = array_filter([
            'collection_id' => $request->input('collection_id'),
        ]);

        $hadiths = $this->hadithRepository->searchForAdmin(
            search: $search,
            filters: $filters,
            perPage: $perPage,
            locale: $locale,
        );

        return response()->json([
            'success' => true,
            'data' => $hadiths->items(),
            'meta' => [
                'current_page' => $hadiths->currentPage(),
                'last_page' => $hadiths->lastPage(),
                'per_page' => $hadiths->perPage(),
                'total' => $hadiths->total(),
            ],
        ]);
    }

    /**
     * Bulk delete hadiths.
     */
    public function bulkDelete(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:hadith_hadiths,id',
        ]);

        $this->hadithRepository->bulkDelete($validated['ids']);

        return redirect()->route('admin.hadith.index')
            ->with('success', __('admin.hadiths_deleted_successfully'));
    }
}
