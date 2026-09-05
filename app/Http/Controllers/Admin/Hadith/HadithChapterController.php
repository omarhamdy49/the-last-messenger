<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Hadith;

use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithChapter;
use App\Domain\Hadith\Models\HadithCollection;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Hadith\StoreHadithChapterRequest;
use App\Http\Requests\Admin\Hadith\UpdateHadithChapterRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class HadithChapterController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $bookId = $request->integer('book') ?: null;
        $collectionId = $request->integer('collection') ?: null;
        $status = $request->query('status');

        $chapters = HadithChapter::query()
            ->with('book:id,collection_id,book_number,title_ar,title_en', 'book.collection:id,title_ar,title_en')
            ->withCount('hadiths')
            ->when($bookId, fn ($q) => $q->where('book_id', $bookId))
            ->when($collectionId, fn ($q) => $q->whereHas('book', fn ($b) => $b->where('collection_id', $collectionId)))
            ->when($search !== '', fn ($q) => $q->where(
                fn ($w) => $w->where('title_ar', 'LIKE', "%{$search}%")
                    ->orWhere('title_en', 'LIKE', "%{$search}%")
                    ->orWhere('chapter_number', 'LIKE', "%{$search}%")
            ))
            ->when($status === 'published', fn ($q) => $q->where('is_published', true))
            ->when($status === 'draft', fn ($q) => $q->where('is_published', false))
            ->orderBy('book_id')->orderBy('sort_order')->orderBy('chapter_number')
            ->paginate(25)
            ->withQueryString();

        return view('admin.hadith.chapters.index', [
            'chapters' => $chapters,
            'books' => $this->bookOptions($collectionId),
            'collections' => HadithCollection::orderBy('sort_order')->get(['id', 'title_ar', 'title_en']),
            'filters' => ['q' => $search, 'book' => $bookId, 'collection' => $collectionId, 'status' => $status],
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.hadith.chapters.create', [
            'books' => $this->bookOptions(),
            'presetBook' => $request->integer('book') ?: null,
        ]);
    }

    public function store(StoreHadithChapterRequest $request): RedirectResponse
    {
        $chapter = HadithChapter::create($request->validated());

        return redirect()
            ->route('admin.hadith-chapters.index', ['book' => $chapter->book_id])
            ->with('success', __('hadith_admin.chapter_created'));
    }

    public function edit(HadithChapter $chapter): View
    {
        return view('admin.hadith.chapters.edit', [
            'chapter' => $chapter,
            'books' => $this->bookOptions(),
        ]);
    }

    public function update(UpdateHadithChapterRequest $request, HadithChapter $chapter): RedirectResponse
    {
        $chapter->update($request->validated());

        return redirect()
            ->route('admin.hadith-chapters.index', ['book' => $chapter->book_id])
            ->with('success', __('hadith_admin.chapter_updated'));
    }

    /** Refused while hadiths still point at it (ON DELETE RESTRICT). */
    public function destroy(HadithChapter $chapter): RedirectResponse
    {
        $hadiths = $chapter->hadiths()->count();

        if ($hadiths > 0) {
            return back()->with('error', __('hadith_admin.chapter_not_empty', ['hadiths' => number_format($hadiths)]));
        }

        $chapter->delete();

        return redirect()
            ->route('admin.hadith-chapters.index')
            ->with('success', __('hadith_admin.chapter_deleted'));
    }

    public function toggleStatus(HadithChapter $chapter): RedirectResponse
    {
        $chapter->update(['is_published' => ! $chapter->is_published]);

        return back()->with('success', __('hadith_admin.status_updated'));
    }

    /** @return Collection<int, HadithBook> */
    private function bookOptions(?int $collectionId = null)
    {
        return HadithBook::query()
            ->with('collection:id,title_ar,title_en')
            ->when($collectionId, fn ($q) => $q->where('collection_id', $collectionId))
            ->orderBy('collection_id')->orderBy('sort_order')->orderBy('book_number')
            ->get(['id', 'collection_id', 'book_number', 'title_ar', 'title_en']);
    }
}
