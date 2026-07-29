<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithChapter;
use App\Domain\Hadith\Repositories\BookRepository;
use App\Domain\Hadith\Repositories\CollectionRepository;
use App\Domain\Hadith\Repositories\HadithRepository;
use App\Domain\Hadith\Repositories\NarratorRepository;
use App\Domain\Hadith\Repositories\ThemeRepositoryInterface;
use App\Domain\Shared\Services\PublicVisibilityRegistry;
use App\Http\Controllers\Controller;
use App\Http\Resources\HadithBookResource;
use App\Http\Resources\HadithCollectionResource;
use App\Http\Resources\HadithNarratorResource;
use App\Http\Resources\HadithResource;
use App\Support\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HadithController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly HadithRepository $hadithRepository,
        private readonly CollectionRepository $collectionRepository,
        private readonly BookRepository $bookRepository,
        private readonly NarratorRepository $narratorRepository,
        private readonly ThemeRepositoryInterface $themeRepository,
        private readonly PublicVisibilityRegistry $publicVisibility,
    ) {}

    /**
     * Paginated list of hadiths with optional filters.
     * Filters: collection_id, book_id, grade, narrator_id.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'collection_id' => ['sometimes', 'integer', 'min:1'],
            'book_id' => ['sometimes', 'integer', 'min:1'],
            'grade' => ['sometimes', 'string', 'in:sahih,hasan,daif,mawdu,unknown'],
            'narrator_id' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = $validated['per_page'] ?? 20;

        $filters = [];

        if (isset($validated['collection_id'])) {
            $filters['collection_id'] = (int) $validated['collection_id'];
        }

        if (isset($validated['book_id'])) {
            $filters['book_id'] = (int) $validated['book_id'];
        }

        if (isset($validated['grade'])) {
            $filters['grading_status'] = $validated['grade'];
        }

        if (isset($validated['narrator_id'])) {
            $filters['narrator_id'] = (int) $validated['narrator_id'];
        }

        $paginator = $this->hadithRepository->paginateWithFilters(
            perPage: $perPage,
            filters: $filters
        );

        return $this->paginatedResponse($paginator, 'Hadiths retrieved successfully');
    }

    /**
     * Single hadith by ID with translations and narrator chain.
     */
    public function show(int $id): JsonResponse
    {
        $hadith = $this->hadithRepository->getWithPublicDetail($id);

        if ($hadith === null) {
            return $this->notFoundResponse('Hadith not found');
        }

        return $this->successResponse(
            new HadithResource($hadith),
            'Hadith retrieved successfully'
        );
    }

    /**
     * Search hadiths by keyword (min 3 chars).
     * Optional filters: book_id, collection_id, grade.
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:3', 'max:500'],
            'collection_id' => ['sometimes', 'integer', 'min:1'],
            'book_id' => ['sometimes', 'integer', 'min:1'],
            'grade' => ['sometimes', 'string', 'in:sahih,hasan,daif,mawdu,unknown'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = $validated['per_page'] ?? 20;

        $filters = [];

        if (isset($validated['book_id'])) {
            $filters['book_id'] = (int) $validated['book_id'];
        }

        if (isset($validated['collection_id'])) {
            $filters['collection_id'] = (int) $validated['collection_id'];
        }

        if (isset($validated['grade'])) {
            $filters['grading_status'] = $validated['grade'];
        }

        $paginator = $this->hadithRepository->search(
            query: $validated['q'],
            perPage: $perPage,
            filters: $filters
        );

        return $this->paginatedResponse($paginator, 'Search results retrieved successfully');
    }

    /**
     * Sahih hadiths only (grading_status = 'sahih').
     */
    public function sahih(Request $request): JsonResponse
    {
        $perPage = $request->integer('per_page', 20);
        $perPage = max(1, min(100, $perPage));

        $paginator = $this->hadithRepository->paginateWithFilters(
            perPage: $perPage,
            filters: ['grading_status' => Hadith::GRADING_SAHIH]
        );

        return $this->paginatedResponse($paginator, 'Sahih hadiths retrieved successfully');
    }

    /**
     * Reliable hadiths: grading_status in ['sahih', 'hasan'].
     */
    public function reliable(Request $request): JsonResponse
    {
        $perPage = $request->integer('per_page', 20);
        $perPage = max(1, min(100, $perPage));

        $query = Hadith::query();
        $this->publicVisibility->apply($query, app()->getLocale());

        $paginator = $query
            ->whereHas('gradings', fn (Builder $grading) => $grading
                ->whereIn('grade_key', [Hadith::GRADING_SAHIH, Hadith::GRADING_HASAN]))
            ->with(['translations', 'gradings', 'chapter.book.collection'])
            ->orderBy('hadith_number')
            ->paginate($perPage);

        return $this->paginatedResponse($paginator, 'Reliable hadiths retrieved successfully');
    }

    /**
     * All hadith collections with book count.
     */
    public function collections(): JsonResponse
    {
        $collections = $this->collectionRepository->getAllWithBooksCount();

        return $this->successResponse(
            HadithCollectionResource::collection($collections),
            'Collections retrieved successfully'
        );
    }

    /**
     * Single collection by ID.
     */
    public function collectionShow(int $id): JsonResponse
    {
        $collection = $this->collectionRepository->getWithBooks($id);

        if ($collection === null) {
            return $this->notFoundResponse('Collection not found');
        }

        return $this->successResponse(
            new HadithCollectionResource($collection),
            'Collection retrieved successfully'
        );
    }

    /**
     * Books in a collection.
     */
    public function collectionBooks(int $id): JsonResponse
    {
        $books = $this->bookRepository->getByCollection($id);

        return $this->successResponse(
            HadithBookResource::collection($books),
            'Books retrieved successfully'
        );
    }

    /**
     * All books (paginated, with collection).
     */
    public function books(Request $request): JsonResponse
    {
        $perPage = $request->integer('per_page', 20);
        $perPage = max(1, min(100, $perPage));

        $paginator = $this->bookRepository->paginateWithFilters(perPage: $perPage);

        return $this->paginatedResponse($paginator, 'Books retrieved successfully');
    }

    /**
     * Single book by its collection-scoped book number.
     */
    public function bookShow(string $slug): JsonResponse
    {
        $allowedLocales = ['ar', 'en', 'fr', 'tr', 'ur'];
        $requestedLocale = request()->header('Accept-Language', 'ar');
        $locale = in_array($requestedLocale, $allowedLocales, true) ? $requestedLocale : 'ar';
        $book = \App\Domain\Hadith\Models\HadithBook::query()
            ->where('book_number', $slug)
            ->with('collection')
            ->withCount(['hadiths', 'chapters'])
            ->first();

        if ($book === null) {
            return $this->notFoundResponse('Book not found');
        }

        return $this->successResponse(
            new HadithBookResource($book),
            'Book retrieved successfully'
        );
    }

    /**
     * Single hadith by book ID and hadith number.
     */
    public function byBookAndNumber(int $bookId, int $number): JsonResponse
    {
        $hadith = $this->hadithRepository->getByBookAndNumber($bookId, (string) $number);

        if ($hadith === null) {
            return $this->notFoundResponse('Hadith not found');
        }

        return $this->successResponse(
            new HadithResource($hadith),
            'Hadith retrieved successfully'
        );
    }

    /**
     * Single chapter with its hadiths.
     */
    public function chapterShow(int $id): JsonResponse
    {
        $chapter = HadithChapter::query()
            ->select(['id', 'book_id', 'chapter_number', 'name', 'description', 'display_order'])
            ->with([
                // with() hands the closure the Relation, not a Builder — the visibility
                // gate needs the underlying query to constrain the eager load.
                'hadiths' => fn (Relation $relation) => $this->publicVisibility
                    ->apply($relation->getQuery(), app()->getLocale())
                    ->with(['translations', 'gradings'])
                    ->orderBy('hadith_number'),
            ])
            ->find($id);

        if ($chapter === null) {
            return $this->notFoundResponse('Chapter not found');
        }

        return $this->successResponse($chapter, 'Chapter retrieved successfully');
    }

    /**
     * Paginated narrators list.
     */
    public function narrators(Request $request): JsonResponse
    {
        $perPage = $request->integer('per_page', 20);
        $perPage = max(1, min(100, $perPage));

        $paginator = \App\Domain\Hadith\Models\HadithNarrator::query()
            ->select(['id', 'sahaba_id', 'name', 'kunya', 'death_year_hijri', 'reliability_grade'])
            ->with('sahabi.translations')
            ->withCount('isnads')
            ->orderBy('id')
            ->paginate($perPage);

        return $this->paginatedResponse($paginator, 'Narrators retrieved successfully');
    }

    /**
     * Single narrator by ID.
     */
    public function narratorShow(int $id): JsonResponse
    {
        $narrator = \App\Domain\Hadith\Models\HadithNarrator::query()
            ->select(['id', 'sahaba_id', 'name', 'full_name', 'kunya', 'biography', 'birth_year_hijri', 'death_year_hijri', 'reliability_grade', 'school', 'region'])
            ->with('sahabi.translations')
            ->withCount('isnads')
            ->find($id);

        if ($narrator === null) {
            return $this->notFoundResponse('Narrator not found');
        }

        return $this->successResponse(
            new HadithNarratorResource($narrator),
            'Narrator retrieved successfully'
        );
    }

    /**
     * Hadiths narrated by a specific narrator (via isnad chain).
     */
    public function narratorHadiths(int $id, Request $request): JsonResponse
    {
        $perPage = $request->integer('per_page', 20);
        $perPage = max(1, min(100, $perPage));

        $narrator = \App\Domain\Hadith\Models\HadithNarrator::query()
            ->select(['id', 'name'])
            ->find($id);

        if ($narrator === null) {
            return $this->notFoundResponse('Narrator not found');
        }

        $query = Hadith::query();
        $this->publicVisibility->apply($query, app()->getLocale());

        $paginator = $query
            ->whereHas('isnads', fn ($q) => $q->where('narrator_id', $id))
            ->with(['translations', 'gradings', 'chapter.book.collection'])
            ->orderBy('hadith_number')
            ->paginate($perPage);

        return $this->paginatedResponse($paginator, 'Narrator hadiths retrieved successfully');
    }

    /**
     * Hadiths filtered by theme ID or human-readable name.
     */
    public function byTheme(string $theme, Request $request): JsonResponse
    {
        $perPage = $request->integer('per_page', 20);
        $perPage = max(1, min(100, $perPage));

        $paginator = $this->themeRepository->paginateHadithsByIdentifier($theme, $perPage);

        if ($paginator === null) {
            return $this->notFoundResponse('Theme not found');
        }

        return $this->paginatedResponse($paginator, 'Hadiths by theme retrieved successfully');
    }
}
