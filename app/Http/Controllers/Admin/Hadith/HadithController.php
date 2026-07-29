<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Hadith;

use App\Domain\Hadith\Enums\HadithGrade;
use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithChapter;
use App\Domain\Hadith\Models\HadithGrading;
use App\Domain\Hadith\Models\HadithIsnad;
use App\Domain\Hadith\Models\HadithTranslation;
use App\Domain\Hadith\Repositories\BookRepositoryInterface;
use App\Domain\Hadith\Repositories\CollectionRepositoryInterface;
use App\Domain\Hadith\Repositories\HadithRepositoryInterface;
use App\Domain\Hadith\Repositories\NarratorRepositoryInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HadithController
{
    public function __construct(
        private readonly HadithRepositoryInterface $hadithRepository,
        private readonly CollectionRepositoryInterface $collectionRepository,
        private readonly BookRepositoryInterface $bookRepository,
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

        $gradingLevels = $this->gradingLevels();

        return view('admin.hadiths.index-cards', compact('hadiths', 'collections', 'books', 'gradingLevels'));
    }

    /**
     * Show the form for creating a new hadith.
     */
    public function create(): View
    {
        $books = $this->bookRepository->getAllForDropdown();
        $chapters = HadithChapter::query()->with('book')->ordered()->get();
        $gradingLevels = $this->gradingLevels();

        return view('admin.hadiths.create', compact('books', 'chapters', 'gradingLevels'));
    }

    /**
     * Store a newly created hadith in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'chapter_id' => 'required|exists:hadith_chapters,id',
            'hadith_number' => 'required|string|max:50',
            'grading_status' => 'nullable|string|in:sahih,hasan,daif,mawdu,unknown',
            'primary_narrator_id' => 'nullable|exists:hadith_narrators,id',
            'text_ar' => 'required|string',
        ]);

        $grading = $validated['grading_status'] ?? null;
        $text = $validated['text_ar'];
        unset($validated['grading_status'], $validated['text_ar']);
        $validated['text_hash'] = hash('sha256', trim($text));
        $hadith = $this->hadithRepository->create($validated);
        HadithTranslation::query()->create([
            'hadith_id' => $hadith->id,
            'locale' => 'ar',
            'text' => $text,
        ]);

        if ($grading !== null) {
            HadithGrading::query()->create([
                'hadith_id' => $hadith->id,
                'grade_key' => $grading,
                'raw_grade' => $grading,
            ]);
        }

        return redirect()->route('admin.hadith.index')
            ->with('success', __('admin.hadith_created_successfully'));
    }

    /**
     * Display the specified hadith.
     */
    public function show(Hadith $hadith): View
    {
        $hadith = $this->hadithRepository->getWithFullRelations($hadith->id);
        $hadith->setRelation('isnads', $hadith->isnads->sortBy('position_in_chain')->values());

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
            ['chapter.book', 'translations', 'isnads', 'isnads.narrator'],
        );
        // find() caches by serializing $relations, so the ordering constraint
        // can't be a closure — sort the already-loaded chain here instead.
        $hadith->setRelation('isnads', $hadith->isnads->sortBy('position_in_chain')->values());

        $books = $this->bookRepository->getAllForDropdown();
        $chapters = HadithChapter::query()->with('book')->ordered()->get();
        $narrators = $this->narratorRepository->all(['id', 'name', 'kunya']);
        $gradingLevels = $this->gradingLevels();

        return view('admin.hadiths.edit', compact('hadith', 'books', 'chapters', 'narrators', 'gradingLevels'));
    }

    /**
     * Update the specified hadith in storage.
     */
    public function update(Request $request, Hadith $hadith): RedirectResponse
    {
        $validated = $request->validate([
            'chapter_id' => 'required|exists:hadith_chapters,id',
            'hadith_number' => 'required|string|max:50',
            'grading_status' => 'nullable|string|in:sahih,hasan,daif,mawdu,unknown',
            'primary_narrator_id' => 'nullable|exists:hadith_narrators,id',
            'text_ar' => 'required|string',
        ]);

        $grading = $validated['grading_status'] ?? null;
        $text = $validated['text_ar'];
        unset($validated['grading_status'], $validated['text_ar']);
        $validated['text_hash'] = hash('sha256', trim($text));
        $this->hadithRepository->update($hadith->id, $validated);
        HadithTranslation::query()->updateOrCreate(
            ['hadith_id' => $hadith->id, 'locale' => 'ar'],
            ['text' => $text],
        );

        if ($grading !== null) {
            HadithGrading::query()->updateOrCreate(
                ['hadith_id' => $hadith->id, 'scholar_id' => null],
                ['grade_key' => $grading, 'raw_grade' => $grading],
            );
        }

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

    /**
     * Append a narrator to a hadith's isnad chain.
     */
    public function storeIsnad(Request $request, Hadith $hadith): RedirectResponse
    {
        $validated = $request->validate([
            'narrator_id' => ['required', 'integer', 'exists:hadith_narrators,id'],
            'transmission_method' => ['nullable', 'string', 'max:100'],
        ]);

        $this->hadithRepository->addNarratorToChain(
            $hadith->id,
            (int) $validated['narrator_id'],
            $validated['transmission_method'] ?? null,
        );

        $this->forgetPublicShowCache($hadith->id);

        return redirect()->route('admin.hadith.edit', $hadith)
            ->with('success', __('admin.narrator_added_to_chain'));
    }

    /**
     * Swap an isnad link with its neighbor to change the chain order.
     */
    public function moveIsnad(Request $request, Hadith $hadith, HadithIsnad $isnad): RedirectResponse
    {
        $validated = $request->validate([
            'direction' => ['required', 'string', 'in:up,down'],
        ]);

        $this->hadithRepository->moveIsnadLink($hadith->id, $isnad->id, $validated['direction']);
        $this->forgetPublicShowCache($hadith->id);

        return redirect()->route('admin.hadith.edit', $hadith);
    }

    /**
     * Remove a narrator from a hadith's isnad chain.
     */
    public function destroyIsnad(Hadith $hadith, HadithIsnad $isnad): RedirectResponse
    {
        $this->hadithRepository->removeNarratorFromChain($hadith->id, $isnad->id);
        $this->forgetPublicShowCache($hadith->id);

        return redirect()->route('admin.hadith.edit', $hadith)
            ->with('success', __('admin.narrator_removed_from_chain'));
    }

    /**
     * The public single-hadith page caches its full payload per locale for
     * an hour (see Site\HadithController::show()) under a key independent
     * of the repository's own version-based cache — bust it for every
     * supported locale so isnad edits show up immediately instead of after
     * the TTL expires. Uses the static locale list (not
     * Language::getActiveCodes(), which reads the `languages` table) so
     * cache invalidation never silently no-ops if that table is empty,
     * unseeded, or its own 5-minute cache is stale.
     */
    private function forgetPublicShowCache(int $hadithId): void
    {
        foreach (config('translatable.locales') as $locale) {
            Cache::forget("public_hadith_show_{$hadithId}_{$locale}");
        }
    }

    private function gradingLevels(): array
    {
        return array_map(
            static fn (HadithGrade $grade): object => (object) [
                'id' => $grade->value,
                'grade_ar' => $grade->labelArabic(),
                'grade_en' => $grade->label(),
            ],
            HadithGrade::cases(),
        );
    }
}
