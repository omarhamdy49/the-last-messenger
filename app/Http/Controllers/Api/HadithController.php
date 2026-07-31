<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Services\HadithBrowseService;
use App\Domain\Hadith\Services\HadithQueryService;
use App\Domain\Hadith\Services\HadithSearchService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\HadithSearchRequest;
use App\Http\Resources\Hadith\HadithBookResource;
use App\Http\Resources\Hadith\HadithChapterResource;
use App\Http\Resources\Hadith\HadithCollectionResource;
use App\Http\Resources\Hadith\HadithResource;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The ten read-only endpoints of contracts/hadith-api.md.
 *
 * Shaped after sunnah.com's layout so its concepts map cleanly, but the
 * envelope, field names and pagination are ours — drop-in client compatibility
 * is explicitly not claimed.
 *
 * Every action calls exactly one service method. Unpublished records return 404
 * rather than an empty 200: an empty success would tell a caller the resource
 * exists but is being withheld.
 */
final class HadithController extends Controller
{
    use ApiResponse;

    /** Anything outside this list falls back to Arabic rather than being honoured. */
    private const SUPPORTED_LOCALES = ['ar', 'en', 'fr', 'tr', 'ur'];

    private const MAX_PER_PAGE = 100;

    private const DEFAULT_PER_PAGE = 20;

    public function __construct(
        private readonly HadithBrowseService $browse,
        private readonly HadithQueryService $query,
        private readonly HadithSearchService $search,
    ) {}

    /** §1 — published collections, featured first. */
    public function collections(Request $request): JsonResponse
    {
        $locale = $this->localeFor($request);

        return $this->successResponse(
            HadithCollectionResource::collection($this->browse->hub())
                ->each(fn (HadithCollectionResource $r) => $r->forLocale($locale)),
            'Collections retrieved successfully',
        );
    }

    /** §2 — one collection, with `about`. */
    public function collection(Request $request, string $code): JsonResponse
    {
        $locale = $this->localeFor($request);
        $collection = $this->browse->findCollection($code);

        if ($collection === null) {
            return $this->notFoundResponse('Collection not found');
        }

        return $this->successResponse(
            HadithCollectionResource::detail($collection)->forLocale($locale),
            'Collection retrieved successfully',
        );
    }

    /** §3 — books in sort order. Synthetic books are excluded by the service. */
    public function books(Request $request, string $code): JsonResponse
    {
        $locale = $this->localeFor($request);
        $collection = $this->browse->findCollection($code);

        if ($collection === null) {
            return $this->notFoundResponse('Collection not found');
        }

        return $this->successResponse(
            HadithBookResource::collection($this->browse->booksFor($collection))
                ->each(fn (HadithBookResource $r) => $r->forLocale($locale)),
            'Books retrieved successfully',
        );
    }

    /** §4 — one book, with `intro`. */
    public function book(Request $request, string $code, string $bookNumber): JsonResponse
    {
        $locale = $this->localeFor($request);
        $book = $this->resolveBook($code, $bookNumber);

        if ($book === null) {
            return $this->notFoundResponse('Book not found');
        }

        return $this->successResponse(
            HadithBookResource::detail($book)->forLocale($locale),
            'Book retrieved successfully',
        );
    }

    /**
     * §5 — chapters in sort order.
     *
     * A collection with `has_chapters = false` yields 200 and an empty list, not
     * a 404: the book exists, it simply has no chapters.
     */
    public function chapters(Request $request, string $code, string $bookNumber): JsonResponse
    {
        $locale = $this->localeFor($request);
        $book = $this->resolveBook($code, $bookNumber);

        if ($book === null) {
            return $this->notFoundResponse('Book not found');
        }

        return $this->successResponse(
            HadithChapterResource::collection($this->browse->chaptersFor($book))
                ->each(fn (HadithChapterResource $r) => $r->forLocale($locale)),
            'Chapters retrieved successfully',
        );
    }

    /** §6 — a book's hadiths, paginated, in reading order. */
    public function bookHadiths(Request $request, string $code, string $bookNumber): JsonResponse
    {
        $locale = $this->localeFor($request);
        $book = $this->resolveBook($code, $bookNumber);

        if ($book === null) {
            return $this->notFoundResponse('Book not found');
        }

        return $this->paginatedResponse(
            $this->browse->paginateBookHadiths($book, $this->perPage($request))
                ->through(fn ($hadith) => (new HadithResource($hadith))->forLocale($locale)),
            'Hadiths retrieved successfully',
        );
    }

    /** §7 — the canonical lookup. `$hadithNumber` may be non-numeric (`1a`). */
    public function hadith(Request $request, string $code, string $hadithNumber): JsonResponse
    {
        $locale = $this->localeFor($request);
        $hadith = $this->query->modelByCollectionAndNumber($code, $hadithNumber);

        if ($hadith === null) {
            return $this->notFoundResponse('Hadith not found');
        }

        return $this->successResponse((new HadithResource($hadith))->forLocale($locale), 'Hadith retrieved successfully');
    }

    /**
     * §8 — permalink lookup.
     *
     * Around 35% of records carry no urn, so a miss here is an ordinary outcome
     * rather than an error condition.
     */
    public function urn(Request $request, int $urn): JsonResponse
    {
        $locale = $this->localeFor($request);
        $hadith = $this->query->modelByUrn($urn);

        if ($hadith === null) {
            return $this->notFoundResponse('No hadith carries that urn');
        }

        return $this->successResponse((new HadithResource($hadith))->forLocale($locale), 'Hadith retrieved successfully');
    }

    /** §9 — one random published hadith, off the (is_published, id) index. */
    public function random(Request $request): JsonResponse
    {
        $locale = $this->localeFor($request);
        $hadith = $this->query->randomModel();

        if ($hadith === null) {
            return $this->notFoundResponse('No published hadith is available');
        }

        return $this->successResponse((new HadithResource($hadith))->forLocale($locale), 'Hadith retrieved successfully');
    }

    /**
     * §10 — search. Shares HadithSearchRequest with the web surface so the two
     * cannot drift on what a valid query is, and the same `throttle:search`
     * limiter applies.
     */
    public function search(HadithSearchRequest $request): JsonResponse
    {
        $locale = $this->localeFor($request);

        return $this->paginatedResponse(
            $this->search->search($request->criteria())
                ->through(fn ($hadith) => (new HadithResource($hadith))->forLocale($locale)),
            'Search completed successfully',
        );
    }

    private function resolveBook(string $code, string $bookNumber): ?HadithBook
    {
        $collection = $this->browse->findCollection($code);

        return $collection === null ? null : $this->browse->findBook($collection, $bookNumber);
    }

    /**
     * Resolves the response locale from Accept-Language against an allowlist.
     *
     * Returned and passed explicitly to each resource rather than set globally:
     * a JsonResource is serialized outside this frame, and both a request
     * attribute and app()->setLocale() were gone by then, so every response came
     * back in English regardless of the header.
     *
     * The header is never trusted verbatim — it is caller-controlled and would
     * otherwise reach getTranslation() unchecked.
     */
    private function localeFor(Request $request): string
    {
        $requested = substr((string) $request->header('Accept-Language', 'ar'), 0, 2);

        return in_array($requested, self::SUPPORTED_LOCALES, true) ? $requested : 'ar';
    }

    private function perPage(Request $request): int
    {
        // Clamped rather than rejected, per the contract's conventions.
        return max(1, min((int) $request->query('per_page', (string) self::DEFAULT_PER_PAGE), self::MAX_PER_PAGE));
    }
}
