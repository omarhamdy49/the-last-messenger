<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Hadith\Enums\HadithGrade;
use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Services\HadithService;
use App\Exceptions\Domain\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Resources\HadithResource;
use App\Http\Resources\HadithBookResource;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Hadith API Controller
 * Handles HTTP requests for hadith
 */
class HadithController extends Controller
{
    use ApiResponse;

    /**
     * Constructor
     *
     * @param HadithService $hadithService
     */
    public function __construct(
        protected HadithService $hadithService
    ) {
        $this->middleware('auth:sanctum')->except([
            'index',
            'show',
            'books',
            'bookShow',
            'chapterShow',
            'search',
            'sahih',
            'reliable',
        ]);
    }

    /**
     * Display a listing of hadith
     *
     * GET /api/hadith
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'book_id' => 'nullable|integer|exists:hadith_books,id',
            'chapter_id' => 'nullable|integer|exists:hadith_chapters,id',
            'grade' => 'nullable|string|in:sahih,hasan,daif,mawdu,unknown',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $perPage = $request->input('per_page', 15);

        // Check if filters are provided
        if ($request->hasAny(['book_id', 'chapter_id', 'grade'])) {
            $filters = $request->only(['book_id', 'chapter_id', 'grade']);
            $hadiths = $this->hadithService->filterHadith($filters, (int) $perPage);
        } else {
            $hadiths = $this->hadithService->paginate((int) $perPage, ['*'], ['book', 'chapter']);
        }

        return $this->paginatedResponse($hadiths->setCollection(
            $hadiths->getCollection()->map(fn($hadith) => new HadithResource($hadith))
        ));
    }

    /**
     * Display the specified hadith
     *
     * GET /api/hadith/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $hadith = $this->hadithService->getHadithWithNarrators($id);
            return $this->successResponse(new HadithResource($hadith));
        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        }
    }

    /**
     * Get all hadith books
     *
     * GET /api/hadith/books
     *
     * @return JsonResponse
     */
    public function books(): JsonResponse
    {
        $books = HadithBook::with('chapters')->ordered()->get();
        return $this->successResponse(HadithBookResource::collection($books));
    }

    /**
     * Get a specific hadith book with hadiths
     *
     * GET /api/hadith/books/{slug}
     *
     * @param Request $request
     * @param string $slug
     * @return JsonResponse
     */
    public function bookShow(Request $request, string $slug): JsonResponse
    {
        $book = HadithBook::where('slug', $slug)->with('chapters')->first();

        if (!$book) {
            return $this->notFoundResponse('Hadith book not found');
        }

        $perPage = $request->input('per_page', 15);
        $hadiths = $this->hadithService->getHadithByBook($book->id, (int) $perPage);

        return $this->successResponse([
            'book' => new HadithBookResource($book),
            'hadiths' => [
                'data' => HadithResource::collection($hadiths->items()),
                'meta' => [
                    'current_page' => $hadiths->currentPage(),
                    'last_page' => $hadiths->lastPage(),
                    'per_page' => $hadiths->perPage(),
                    'total' => $hadiths->total(),
                ],
            ],
        ]);
    }

    /**
     * Get hadiths by chapter
     *
     * GET /api/hadith/chapters/{id}
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function chapterShow(Request $request, int $id): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $hadiths = $this->hadithService->getHadithByChapter($id, (int) $perPage);

        return $this->paginatedResponse($hadiths->setCollection(
            $hadiths->getCollection()->map(fn($hadith) => new HadithResource($hadith))
        ));
    }

    /**
     * Search hadith
     *
     * GET /api/hadith/search
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = $request->input('q');
        $perPage = $request->input('per_page', 15);

        $hadiths = $this->hadithService->searchHadith($query, (int) $perPage);

        return $this->paginatedResponse($hadiths->setCollection(
            $hadiths->getCollection()->map(fn($hadith) => new HadithResource($hadith))
        ));
    }

    /**
     * Get authentic hadith (sahih)
     *
     * GET /api/hadith/sahih
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sahih(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $hadiths = $this->hadithService->getSahihHadith((int) $perPage);

        return $this->paginatedResponse($hadiths->setCollection(
            $hadiths->getCollection()->map(fn($hadith) => new HadithResource($hadith))
        ));
    }

    /**
     * Get reliable hadith (sahih or hasan)
     *
     * GET /api/hadith/reliable
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function reliable(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $hadiths = $this->hadithService->getReliableHadith((int) $perPage);

        return $this->paginatedResponse($hadiths->setCollection(
            $hadiths->getCollection()->map(fn($hadith) => new HadithResource($hadith))
        ));
    }

    /**
     * Get hadith by book and number
     *
     * GET /api/hadith/books/{bookId}/hadith/{number}
     *
     * @param int $bookId
     * @param int $number
     * @return JsonResponse
     */
    public function byBookAndNumber(int $bookId, int $number): JsonResponse
    {
        try {
            $hadith = $this->hadithService->findByBookAndNumber($bookId, $number);
            return $this->successResponse(new HadithResource($hadith));
        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        }
    }
}

