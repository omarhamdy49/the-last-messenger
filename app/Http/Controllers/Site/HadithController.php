<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Domain\Hadith\Services\HadithBrowseService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Public Hadith surface: library → collection → book → hadith, plus search.
 *
 * Reads go through HadithBrowseService so `publiclyVisible()` is applied in one
 * place; see the note on that scope for why a bare is_published check leaks.
 */
final class HadithController extends Controller
{
    public function __construct(private readonly HadithBrowseService $hadith) {}

    /** The library index: every published collection. */
    public function index(): View
    {
        return view('public.hadith.index', [
            'collections' => $this->hadith->collections(),
            'total' => $this->hadith->totalVisible(),
        ]);
    }

    /** Search across the corpus. Renders the empty state when q is absent. */
    public function search(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'min:2', 'max:120'],
            'collection' => ['nullable', 'string', 'max:64'],
            'grade' => ['nullable', 'string', 'in:sahih,hasan,daif,mawdu'],
        ]);

        $term = trim((string) ($validated['q'] ?? ''));

        return view('public.hadith.search', [
            'term' => $term,
            'collections' => $this->hadith->collections(),
            'gradeCounts' => $this->hadith->gradeCounts(),
            'filters' => [
                'collection' => $validated['collection'] ?? null,
                'grade' => $validated['grade'] ?? null,
            ],
            'results' => $term === '' ? null : $this->hadith->search($term, [
                'collection' => $validated['collection'] ?? null,
                'grade' => $validated['grade'] ?? null,
            ]),
        ]);
    }

    /** One collection: its books, or its direct narrations when bookless. */
    public function collection(string $slug): View
    {
        $collection = $this->hadith->findCollection($slug)
            ?? throw new NotFoundHttpException;

        return view('public.hadith.collection', [
            'collection' => $collection,
            'books' => $this->hadith->books($collection),
            'hadiths' => $collection->has_books ? null : $this->hadith->collectionHadiths($collection),
        ]);
    }

    /** One book: its chapters plus its hadiths, paginated. */
    public function book(string $slug, string $bookNumber): View
    {
        $collection = $this->hadith->findCollection($slug)
            ?? throw new NotFoundHttpException;
        $book = $this->hadith->findBook($collection, $bookNumber)
            ?? throw new NotFoundHttpException;

        return view('public.hadith.book', [
            'collection' => $collection,
            'book' => $book,
            'chapters' => $this->hadith->chapters($book),
            'hadiths' => $this->hadith->bookHadiths($book),
        ]);
    }

    /**
     * A single hadith, addressed as `{collection}:{referenceKey}`.
     *
     * A colon rather than a slash because `/hadith/{slug}/{bookNumber}` already
     * occupies the slash form — `/hadith/bukhari/12` would be ambiguous between
     * book 12 and reference key 12. See Hadith::publicPath().
     */
    public function show(string $reference): View
    {
        [$slug, $referenceKey] = array_pad(explode(':', $reference, 2), 2, null);

        if ($slug === null || $referenceKey === null || $referenceKey === '') {
            throw new NotFoundHttpException;
        }

        $collection = $this->hadith->findCollection($slug)
            ?? throw new NotFoundHttpException;
        $hadith = $this->hadith->findHadith($collection, $referenceKey)
            ?? throw new NotFoundHttpException;

        return view('public.hadith.show', [
            'collection' => $collection,
            'hadith' => $hadith,
            'neighbours' => $this->hadith->neighbours($hadith),
        ]);
    }
}
