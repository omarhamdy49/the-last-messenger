<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Domain\Hadith\Services\HadithBrowseService;
use App\Domain\Hadith\Services\HadithQueryService;
use App\Domain\Hadith\Services\HadithSearchService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\HadithSearchRequest;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class HadithController extends Controller
{
    public function __construct(
        private readonly HadithQueryService $hadithQueryService,
        private readonly HadithBrowseService $browseService,
        private readonly HadithSearchService $searchService,
    ) {}

    /**
     * Full-text search. Rate limited on the route, and the page size is clamped
     * inside SearchCriteria rather than validated, so an oversized value narrows
     * instead of erroring.
     */
    public function search(HadithSearchRequest $request): View
    {
        $criteria = $request->criteria();

        return view('public.hadith.search', [
            'criteria' => $criteria,
            'results' => $this->searchService->search($criteria),
            'collections' => $this->browseService->hub(),
        ]);
    }

    /** The hub: every published collection. */
    public function hub(): View
    {
        return view('public.hadith.index', [
            'collections' => $this->browseService->hub(),
        ]);
    }

    /** One collection and its books, in source order. */
    public function collection(string $collection): View
    {
        $model = $this->browseService->findCollection($collection);

        if ($model === null) {
            throw new NotFoundHttpException;
        }

        return view('public.hadith.collection', [
            'collection' => $model,
            'books' => $this->browseService->booksFor($model),
        ]);
    }

    /** One book: its hadiths in reading order, under their chapter headings. */
    public function book(string $collection, string $bookNumber): View
    {
        $model = $this->browseService->findCollection($collection);

        if ($model === null) {
            throw new NotFoundHttpException;
        }

        $book = $this->browseService->findBook($model, $bookNumber);

        if ($book === null) {
            throw new NotFoundHttpException;
        }

        $contents = $this->browseService->bookContents($book);

        return view('public.hadith.book', [
            'collection' => $model,
            'book' => $book,
            'paginator' => $contents['paginator'],
            'groups' => $contents['groups'],
            'adjacent' => $this->browseService->adjacentBooks($book),
        ]);
    }

    /**
     * The canonical page: /hadith/{collection}:{number}
     *
     * `$number` is a string because the source uses forms like `1a`.
     */
    public function show(string $collection, string $number): View
    {
        $hadith = $this->hadithQueryService->findByCollectionAndNumber($collection, $number);

        // Unpublished and unknown are the same answer on purpose: an empty 200
        // would tell a reader the hadith exists but is being withheld.
        if ($hadith === null) {
            throw new NotFoundHttpException;
        }

        return view('public.hadith.show', ['hadith' => $hadith->toArray()]);
    }

    /**
     * Permalink lookup: /hadith/urn/{urn}. Roughly 35% of records carry no urn,
     * so a miss here is an ordinary 404, not an error condition.
     */
    public function urn(int $urn): View
    {
        $hadith = $this->hadithQueryService->findByUrn($urn);

        if ($hadith === null) {
            throw new NotFoundHttpException;
        }

        return view('public.hadith.show', ['hadith' => $hadith->toArray()]);
    }
}
