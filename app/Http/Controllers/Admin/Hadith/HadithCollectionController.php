<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Hadith;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithCollection;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Hadith\StoreHadithCollectionRequest;
use App\Http\Requests\Admin\Hadith\UpdateHadithCollectionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HadithCollectionController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = $request->query('status');

        $collections = HadithCollection::query()
            ->withCount(['books', 'hadiths'])
            ->when($search !== '', fn ($query) => $query->where(
                fn ($q) => $q->where('title_ar', 'LIKE', "%{$search}%")
                    ->orWhere('title_en', 'LIKE', "%{$search}%")
                    ->orWhere('slug', 'LIKE', "%{$search}%")
            ))
            ->when($status === 'published', fn ($query) => $query->where('is_published', true))
            ->when($status === 'draft', fn ($query) => $query->where('is_published', false))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.hadith.collections.index', [
            'collections' => $collections,
            'filters' => ['q' => $search, 'status' => $status],
            'stats' => [
                'total' => HadithCollection::count(),
                'published' => HadithCollection::where('is_published', true)->count(),
                'hadiths' => Hadith::count(),
            ],
        ]);
    }

    public function show(HadithCollection $collection): View
    {
        $collection->loadCount(['books', 'hadiths']);

        return view('admin.hadith.collections.show', [
            'collection' => $collection,
            'hadiths' => $collection->has_books ? null : $collection->hadiths()
                ->orderBy('sort_order')->orderBy('id')->paginate(25),
            'books' => $collection->books()
                ->withCount(['chapters', 'hadiths'])
                ->orderBy('sort_order')->orderBy('book_number')
                ->paginate(25),
        ]);
    }

    public function create(): View
    {
        return view('admin.hadith.collections.create');
    }

    public function store(StoreHadithCollectionRequest $request): RedirectResponse
    {
        $collection = HadithCollection::create($request->validated());

        return redirect()
            ->route('admin.hadith-collections.show', $collection)
            ->with('success', __('hadith_admin.collection_created'));
    }

    public function edit(HadithCollection $collection): View
    {
        return view('admin.hadith.collections.edit', ['collection' => $collection]);
    }

    public function update(UpdateHadithCollectionRequest $request, HadithCollection $collection): RedirectResponse
    {
        $collection->update($request->validated());

        return redirect()
            ->route('admin.hadith-collections.show', $collection)
            ->with('success', __('hadith_admin.collection_updated'));
    }

    /**
     * Deleting a collection is refused while anything still points at it.
     *
     * Every hadith FK is ON DELETE RESTRICT, so without this check the database
     * throws and the editor gets a 500 instead of being told why — and the
     * largest collection carries over 7,000 rows, which is not something to
     * cascade away behind a single button.
     */
    public function destroy(HadithCollection $collection): RedirectResponse
    {
        $books = $collection->books()->count();
        $hadiths = $collection->hadiths()->count();

        if ($books > 0 || $hadiths > 0) {
            return back()->with('error', __('hadith_admin.collection_not_empty', [
                'books' => number_format($books),
                'hadiths' => number_format($hadiths),
            ]));
        }

        $collection->delete();

        return redirect()
            ->route('admin.hadith-collections.index')
            ->with('success', __('hadith_admin.collection_deleted'));
    }

    public function toggleStatus(HadithCollection $collection): RedirectResponse
    {
        $collection->update(['is_published' => ! $collection->is_published]);

        // Unpublishing hides every hadith beneath it — scopePubliclyVisible()
        // walks up to the collection — so say so rather than let it surprise.
        return back()->with('success', $collection->is_published
            ? __('hadith_admin.collection_published')
            : __('hadith_admin.collection_unpublished', ['count' => number_format($collection->hadiths()->count())]));
    }
}
