<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Quran\Services\QuranNavigationService;
use App\Domain\Quran\Services\QuranService;
use App\Exceptions\Domain\ResourceNotFoundException;
use App\Http\Resources\SurahResource;
use App\Http\Resources\VerseResource;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class QuranController extends Controller
{
    use ApiResponse;

    /**
     * QuranController constructor
     */
    public function __construct(
        private readonly QuranService $quranService,
        private readonly QuranNavigationService $navigationService,
    ) {}

    // ===================== SURAH ENDPOINTS =====================

    /**
     * Get all surahs
     */
    public function surahs(): JsonResponse
    {
        $surahs = $this->quranService->getAllSurahs();

        return $this->success(
            SurahResource::collection($surahs)
        );
    }

    /**
     * Get single surah by number
     */
    public function surah(int $number): JsonResponse
    {
        try {
            $surah = $this->quranService->getSurahByNumber($number);

            return $this->success(
                new SurahResource($surah)
            );
        } catch (ResourceNotFoundException) {
            return $this->notFound('Surah not found');
        } catch (\Throwable $e) {
            // Anything that is not a domain 404 is an internal fault: log it and
            // return a generic 500 rather than echoing the exception text.
            report($e);

            return $this->serverErrorResponse();
        }
    }

    /**
     * Get surahs by revelation place (Makkan/Madinan)
     */
    public function surahsByRevelation(string $place): JsonResponse
    {
        try {
            $surahs = $this->quranService->getSurahsByRevelationPlace($place);

            return $this->success(
                SurahResource::collection($surahs)
            );
        } catch (ResourceNotFoundException) {
            return $this->notFound('Surahs not found');
        } catch (\Throwable $e) {
            report($e);

            return $this->serverErrorResponse();
        }
    }

    // ===================== VERSE ENDPOINTS =====================

    /**
     * Get all verses in a surah
     */
    public function verses(int $surahNumber): JsonResponse
    {
        try {
            $verses = $this->quranService->getVersesBySurahNumber($surahNumber);

            return $this->success(
                VerseResource::collection($verses)
            );
        } catch (ResourceNotFoundException) {
            return $this->notFound('Surah not found');
        } catch (\Throwable $e) {
            report($e);

            return $this->serverErrorResponse();
        }
    }

    /**
     * Get single verse
     */
    public function verse(int $surahNumber, int $verseNumber): JsonResponse
    {
        try {
            $verse = $this->quranService->getVerse($surahNumber, $verseNumber);

            return $this->success(
                new VerseResource($verse)
            );
        } catch (ResourceNotFoundException) {
            return $this->notFound('Verse not found');
        } catch (\Throwable $e) {
            report($e);

            return $this->serverErrorResponse();
        }
    }

    /**
     * Get verses by Juz number
     */
    public function versesByJuz(Request $request, int $juz): JsonResponse
    {
        try {
            $perPage = max(1, min(100, $request->integer('per_page', 50)));
            $paginator = $this->quranService->getVersesByJuz($juz, $perPage);

            /** @var LengthAwarePaginator $paginator */
            $paginator->through(fn ($verse) => new VerseResource($verse));

            return $this->paginatedResponse($paginator);
        } catch (ResourceNotFoundException) {
            return $this->notFound('Juz not found');
        } catch (\Throwable $e) {
            report($e);

            return $this->serverErrorResponse();
        }
    }

    /**
     * Get verses by page number
     */
    public function versesByPage(int $page): JsonResponse
    {
        try {
            $verses = $this->quranService->getVersesByPage($page);

            return $this->success(
                VerseResource::collection($verses)
            );
        } catch (ResourceNotFoundException) {
            return $this->notFound('Page not found');
        } catch (\Throwable $e) {
            report($e);

            return $this->serverErrorResponse();
        }
    }

    /**
     * Get all Sajda verses
     */
    public function sajdaVerses(): JsonResponse
    {
        $verses = $this->quranService->getSajdaVerses();

        return $this->success(
            VerseResource::collection($verses)
        );
    }

    /**
     * Get random verse (Verse of the Day)
     */
    public function randomVerse(): JsonResponse
    {
        $verse = $this->quranService->getRandomVerse();

        if ($verse === null) {
            return $this->notFound(__('quran.no_verses_available'));
        }

        return $this->success(
            new VerseResource($verse)
        );
    }

    // ===================== SEARCH ENDPOINTS =====================

    /**
     * Search in Quran (Arabic or Translation)
     *
     * Query params:
     *   q         string  required  min:2, max:200
     *   lang      string  optional  default 'ar'   used when type=translation
     *   type      string  optional  'arabic' | 'translation'  default 'translation'
     *   per_page  int     optional  1–100  default 20
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'q' => 'required|string|min:2|max:200',
                'lang' => ['sometimes', 'string', Rule::in(enabled_content_locales())],
                'type' => 'sometimes|string|in:arabic,translation',
                'per_page' => 'sometimes|integer|min:1|max:100',
            ]);
        } catch (ValidationException $e) {
            report($e);

            return $this->validationErrorResponse($e->errors());
        }

        $query = $validated['q'];
        $language = $validated['lang'] ?? 'ar';
        $searchType = $validated['type'] ?? 'translation';
        $perPage = isset($validated['per_page']) ? (int) $validated['per_page'] : 20;

        try {
            if ($searchType === 'arabic') {
                $verses = $this->quranService->searchArabic($query, $perPage);
            } else {
                $verses = $this->quranService->searchTranslation($query, $language, $perPage);
            }

            /** @var LengthAwarePaginator $verses */
            $verses->through(fn ($verse) => new VerseResource($verse));

            return $this->paginatedResponse($verses);
        } catch (\Throwable $e) {
            report($e);

            return $this->serverErrorResponse('Search failed. Please try again.');
        }
    }

    /**
     * Navigational search suggestions (autocomplete).
     *
     * Resolves direct jump targets the way quran.com's search box does:
     * "2:255" → verse, "juz 5"/"page 50"/"hizb 12" → division, a bare number
     * 1–114 or a surah name → surah. Returns a small, ordered list.
     *
     * Query params:
     *   q      string  required  min:1, max:100
     *   limit  int     optional  1–20  default 8
     */
    public function suggest(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'q' => 'required|string|min:1|max:100',
                'limit' => 'sometimes|integer|min:1|max:20',
                'locale' => ['sometimes', 'string', Rule::in(enabled_content_locales())],
            ]);
        } catch (ValidationException $e) {
            report($e);

            return $this->validationErrorResponse($e->errors());
        }

        // Labels are localized via __(); honor an explicit locale so the
        // autocomplete matches the page even when the browser's Accept-Language
        // (which fetch() cannot override) differs.
        if (isset($validated['locale'])) {
            app()->setLocale($validated['locale']);
        }

        $limit = isset($validated['limit']) ? (int) $validated['limit'] : 8;

        return $this->success(
            $this->navigationService->suggest($validated['q'], $limit)
        );
    }
}
