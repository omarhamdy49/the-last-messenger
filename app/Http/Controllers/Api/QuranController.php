<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Quran\Services\QuranService;
use App\Http\Resources\SurahResource;
use App\Http\Resources\VerseResource;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class QuranController extends Controller
{
    use ApiResponse;

    /**
     * QuranController constructor
     *
     * @param QuranService $quranService
     */
    public function __construct(
        private readonly QuranService $quranService
    ) {
    }

    // ===================== SURAH ENDPOINTS =====================

    /**
     * Get all surahs
     *
     * @return JsonResponse
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
     *
     * @param int $number
     * @return JsonResponse
     */
    public function surah(int $number): JsonResponse
    {
        try {
            $surah = $this->quranService->getSurahByNumber($number);

            return $this->success(
                new SurahResource($surah)
            );
        } catch (\Exception $e) {
            return $this->notFound($e->getMessage());
        }
    }

    /**
     * Get surahs by revelation place (Makkan/Madinan)
     *
     * @param string $place
     * @return JsonResponse
     */
    public function surahsByRevelation(string $place): JsonResponse
    {
        try {
            $surahs = $this->quranService->getSurahsByRevelationPlace($place);

            return $this->success(
                SurahResource::collection($surahs)
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ===================== VERSE ENDPOINTS =====================

    /**
     * Get all verses in a surah
     *
     * @param int $surahNumber
     * @return JsonResponse
     */
    public function verses(int $surahNumber): JsonResponse
    {
        try {
            $verses = $this->quranService->getVersesBySurahNumber($surahNumber);

            return $this->success(
                VerseResource::collection($verses)
            );
        } catch (\Exception $e) {
            return $this->notFound($e->getMessage());
        }
    }

    /**
     * Get single verse
     *
     * @param int $surahNumber
     * @param int $verseNumber
     * @return JsonResponse
     */
    public function verse(int $surahNumber, int $verseNumber): JsonResponse
    {
        try {
            $verse = $this->quranService->getVerse($surahNumber, $verseNumber);

            return $this->success(
                new VerseResource($verse)
            );
        } catch (\Exception $e) {
            return $this->notFound($e->getMessage());
        }
    }

    /**
     * Get verses by Juz number
     *
     * @param Request $request
     * @param int $juz
     * @return JsonResponse
     */
    public function versesByJuz(Request $request, int $juz): JsonResponse
    {
        try {
            $perPage = $request->integer('per_page', 50);
            $verses = $this->quranService->getVersesByJuz($juz, $perPage);

            return $this->success(
                VerseResource::collection($verses)->response()->getData(true)
            );
        } catch (\Exception $e) {
            return $this->notFound($e->getMessage());
        }
    }

    /**
     * Get verses by page number
     *
     * @param int $page
     * @return JsonResponse
     */
    public function versesByPage(int $page): JsonResponse
    {
        try {
            $verses = $this->quranService->getVersesByPage($page);

            return $this->success(
                VerseResource::collection($verses)
            );
        } catch (\Exception $e) {
            return $this->notFound($e->getMessage());
        }
    }

    /**
     * Get all Sajda verses
     *
     * @return JsonResponse
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
     *
     * @return JsonResponse
     */
    public function randomVerse(): JsonResponse
    {
        $verse = $this->quranService->getRandomVerse();

        return $this->success(
            new VerseResource($verse)
        );
    }

    // ===================== SEARCH ENDPOINTS =====================

    /**
     * Search in Quran (Arabic or Translation)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        $language = $request->input('lang', 'en');
        $searchType = $request->input('type', 'translation'); // 'arabic' or 'translation'
        $perPage = $request->integer('per_page', 20);

        if (empty($query)) {
            return $this->error('Search query is required', 400);
        }

        try {
            if ($searchType === 'arabic') {
                $verses = $this->quranService->searchArabic($query, $perPage);
            } else {
                $verses = $this->quranService->searchTranslation($query, $language, $perPage);
            }

            return $this->success(
                VerseResource::collection($verses)->response()->getData(true)
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}

