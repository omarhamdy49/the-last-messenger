<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Hadith;

use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithCollection;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Hadith\StoreHadithBookRequest;
use App\Http\Requests\Admin\Hadith\UpdateHadithBookRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class HadithBookController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $collectionId = $request->integer('collection') ?: null;
        $status = $request->query('status');

        $books = HadithBook::query()
            ->with('collection:id,title_ar,title_en,slug')
            ->withCount(['chapters', 'hadiths'])
            ->when($collectionId, fn ($q) => $q->where('collection_id', $collectionId))
            ->when($search !== '', fn ($q) => $q->where(
                fn ($w) => $w->where('title_ar', 'LIKE', "%{$search}%")
                    ->orWhere('title_en', 'LIKE', "%{$search}%")
                    ->orWhere('book_number', 'LIKE', "%{$search}%")
            ))
            ->when($status === 'published', fn ($q) => $q->where('is_published', true))
            ->when($status === 'draft', fn ($q) => $q->where('is_published', false))
            ->orderBy('collection_id')->orderBy('sort_order')->orderBy('book_number')
            ->paginate(25)
            ->withQueryString();

        return view('admin.hadith.books.index', [
            'books' => $books,
            'collections' => $this->collectionOptions(),
            'filters' => ['q' => $search, 'collection' => $collectionId, 'status' => $status],
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.hadith.books.create', [
            'collections' => $this->collectionOptions(),
            'presetCollection' => $request->integer('collection') ?: null,
        ]);
    }

    public function store(StoreHadithBookRequest $request): RedirectResponse
    {
        $book = HadithBook::create($request->validated());

        return redirect()
            ->route('admin.hadith-books.index', ['collection' => $book->collection_id])
            ->with('success', __('hadith_admin.book_created'));
    }

    public function edit(HadithBook $book): View
    {
        return view('admin.hadith.books.edit', [
            'book' => $book,
            'collections' => $this->collectionOptions(),
        ]);
    }

    public function update(UpdateHadithBookRequest $request, HadithBook $book): RedirectResponse
    {
        $book->update($request->validated());

        return redirect()
            ->route('admin.hadith-books.index', ['collection' => $book->collection_id])
            ->with('success', __('hadith_admin.book_updated'));
    }

    /** Refused while chapters or hadiths still point at it (ON DELETE RESTRICT). */
    public function destroy(HadithBook $book): RedirectResponse
    {
        $chapters = $book->chapters()->count();
        $hadiths = $book->hadiths()->count();

        if ($chapters > 0 || $hadiths > 0) {
            return back()->with('error', __('hadith_admin.book_not_empty', [
                'chapters' => number_format($chapters),
                'hadiths' => number_format($hadiths),
            ]));
        }

        $book->delete();

        return redirect()
            ->route('admin.hadith-books.index')
            ->with('success', __('hadith_admin.book_deleted'));
    }

    public function toggleStatus(HadithBook $book): RedirectResponse
    {
        $book->update(['is_published' => ! $book->is_published]);

        return back()->with('success', __('hadith_admin.status_updated'));
    }

    /** @return Collection<int, HadithCollection> */
    private function collectionOptions()
    {
        return HadithCollection::query()
            ->orderBy('sort_order')->orderBy('id')
            ->get(['id', 'title_ar', 'title_en', 'slug', 'has_books', 'has_chapters']);
    }
}
