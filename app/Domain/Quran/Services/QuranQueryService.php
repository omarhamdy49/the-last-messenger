<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\Application\DTOs\ReciterDTO;
use App\Domain\Quran\Application\DTOs\SurahDTO;
use App\Domain\Quran\Application\DTOs\SurahInfoDTO;
use App\Domain\Quran\Application\DTOs\TafsirDTO;
use App\Domain\Quran\Application\DTOs\VerseDTO;
use App\Domain\Quran\Application\Handlers\GetRandomVerseHandler;
use App\Domain\Quran\Application\Handlers\GetReciterByIdHandler;
use App\Domain\Quran\Application\Handlers\GetReciterBySlugHandler;
use App\Domain\Quran\Application\Handlers\GetSajdaVersesHandler;
use App\Domain\Quran\Application\Handlers\GetSurahByIdHandler;
use App\Domain\Quran\Application\Handlers\GetSurahByNumberHandler;
use App\Domain\Quran\Application\Handlers\GetSurahInfoHandler;
use App\Domain\Quran\Application\Handlers\GetTafsirByVerseHandler;
use App\Domain\Quran\Application\Handlers\GetVerseByIdHandler;
use App\Domain\Quran\Application\Handlers\GetVerseByKeyHandler;
use App\Domain\Quran\Application\Handlers\ListRecitersHandler;
use App\Domain\Quran\Application\Handlers\ListSurahsHandler;
use App\Domain\Quran\Application\Handlers\ListTafsirBySurahHandler;
use App\Domain\Quran\Application\Handlers\ListVersesByJuzHandler;
use App\Domain\Quran\Application\Handlers\ListVersesByPageHandler;
use App\Domain\Quran\Application\Handlers\ListVersesBySurahHandler;
use App\Domain\Quran\Application\Handlers\SearchRecitersHandler;
use App\Domain\Quran\Application\Handlers\SearchSurahsHandler;
use App\Domain\Quran\Application\Handlers\SearchTafsirHandler;
use App\Domain\Quran\Application\Handlers\SearchVersesHandler;
use App\Domain\Quran\Application\Queries\GetRandomVerseQuery;
use App\Domain\Quran\Application\Queries\GetReciterByIdQuery;
use App\Domain\Quran\Application\Queries\GetReciterBySlugQuery;
use App\Domain\Quran\Application\Queries\GetSajdaVersesQuery;
use App\Domain\Quran\Application\Queries\GetSurahByIdQuery;
use App\Domain\Quran\Application\Queries\GetSurahByNumberQuery;
use App\Domain\Quran\Application\Queries\GetSurahInfoQuery;
use App\Domain\Quran\Application\Queries\GetTafsirByVerseQuery;
use App\Domain\Quran\Application\Queries\GetVerseByIdQuery;
use App\Domain\Quran\Application\Queries\GetVerseByKeyQuery;
use App\Domain\Quran\Application\Queries\ListRecitersQuery;
use App\Domain\Quran\Application\Queries\ListSurahsQuery;
use App\Domain\Quran\Application\Queries\ListTafsirBySurahQuery;
use App\Domain\Quran\Application\Queries\ListVersesByJuzQuery;
use App\Domain\Quran\Application\Queries\ListVersesByPageQuery;
use App\Domain\Quran\Application\Queries\ListVersesBySurahQuery;
use App\Domain\Quran\Application\Queries\SearchRecitersQuery;
use App\Domain\Quran\Application\Queries\SearchSurahsQuery;
use App\Domain\Quran\Application\Queries\SearchTafsirQuery;
use App\Domain\Quran\Application\Queries\SearchVersesQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Quran Query Service
 *
 * Handles all read-only (query) operations for the Quran domain.
 * Part of the CQRS split from QuranApplicationService.
 */
final class QuranQueryService
{
    public function __construct(
        private readonly GetSurahByIdHandler $getSurahByIdHandler,
        private readonly GetSurahByNumberHandler $getSurahByNumberHandler,
        private readonly ListSurahsHandler $listSurahsHandler,
        private readonly SearchSurahsHandler $searchSurahsHandler,
        private readonly GetSurahInfoHandler $getSurahInfoHandler,
        private readonly GetVerseByIdHandler $getVerseByIdHandler,
        private readonly GetVerseByKeyHandler $getVerseByKeyHandler,
        private readonly ListVersesBySurahHandler $listVersesBySurahHandler,
        private readonly ListVersesByJuzHandler $listVersesByJuzHandler,
        private readonly ListVersesByPageHandler $listVersesByPageHandler,
        private readonly SearchVersesHandler $searchVersesHandler,
        private readonly GetRandomVerseHandler $getRandomVerseHandler,
        private readonly GetSajdaVersesHandler $getSajdaVersesHandler,
        private readonly GetReciterByIdHandler $getReciterByIdHandler,
        private readonly GetReciterBySlugHandler $getReciterBySlugHandler,
        private readonly ListRecitersHandler $listRecitersHandler,
        private readonly SearchRecitersHandler $searchRecitersHandler,
        private readonly GetTafsirByVerseHandler $getTafsirByVerseHandler,
        private readonly ListTafsirBySurahHandler $listTafsirBySurahHandler,
        private readonly SearchTafsirHandler $searchTafsirHandler,
    ) {}

    // ==================== SURAH QUERIES ====================

    /**
     * Get Surah by ID
     */
    public function getSurahById(
        int $id,
        string $locale = 'ar',
        bool $withInfo = false,
        bool $withVerses = false,
    ): SurahDTO {
        $query = new GetSurahByIdQuery(
            id: $id,
            locale: $locale,
            withInfo: $withInfo,
            withVerses: $withVerses,
        );

        return $this->getSurahByIdHandler->handle($query);
    }

    /**
     * Get Surah by number (1-114)
     */
    public function getSurahByNumber(
        int $number,
        string $locale = 'ar',
        bool $withInfo = false,
        bool $withVerses = false,
    ): SurahDTO {
        $query = new GetSurahByNumberQuery(
            number: $number,
            locale: $locale,
            withInfo: $withInfo,
            withVerses: $withVerses,
        );

        return $this->getSurahByNumberHandler->handle($query);
    }

    /**
     * List all Surahs
     *
     * @return Collection<int, SurahDTO>
     */
    public function listSurahs(
        string $locale = 'ar',
        ?string $revelationPlace = null,
        ?int $juz = null,
        string $sortBy = 'id',
        string $sortDirection = 'asc',
    ): Collection {
        $query = new ListSurahsQuery(
            locale: $locale,
            revelationPlace: $revelationPlace,
            juz: $juz,
            sortBy: $sortBy,
            sortDirection: $sortDirection,
        );

        return $this->listSurahsHandler->handle($query);
    }

    /**
     * Search Surahs
     */
    public function searchSurahs(
        string $searchQuery,
        int $perPage = 15,
        ?string $revelationPlace = null,
        string $locale = 'ar',
    ): LengthAwarePaginator {
        $query = new SearchSurahsQuery(
            query: $searchQuery,
            perPage: $perPage,
            revelationPlace: $revelationPlace,
            locale: $locale,
        );

        return $this->searchSurahsHandler->handle($query);
    }

    /**
     * Get Surah info
     */
    public function getSurahInfo(int $surahId, string $lang = 'ar'): SurahInfoDTO
    {
        $query = new GetSurahInfoQuery(
            surahId: $surahId,
            lang: $lang,
        );

        return $this->getSurahInfoHandler->handle($query);
    }

    // ==================== VERSE QUERIES ====================

    /**
     * Get verse by ID
     */
    public function getVerseById(
        int $id,
        string $locale = 'ar',
        bool $withTranslations = false,
        bool $withTafsir = false,
    ): VerseDTO {
        $query = new GetVerseByIdQuery(
            id: $id,
            locale: $locale,
            withTranslations: $withTranslations,
            withTafsir: $withTafsir,
        );

        return $this->getVerseByIdHandler->handle($query);
    }

    /**
     * Get verse by key (surah:verse format)
     */
    public function getVerseByKey(
        int $surahNumber,
        int $verseNumber,
        string $locale = 'ar',
        bool $withTranslations = false,
        bool $withTafsir = false,
    ): VerseDTO {
        $query = new GetVerseByKeyQuery(
            surahNumber: $surahNumber,
            verseNumber: $verseNumber,
            locale: $locale,
            withTranslations: $withTranslations,
            withTafsir: $withTafsir,
        );

        return $this->getVerseByKeyHandler->handle($query);
    }

    /**
     * List verses by Surah
     *
     * @return Collection<int, VerseDTO>
     */
    public function listVersesBySurah(
        int $surahId,
        string $locale = 'ar',
        bool $withTranslations = true,
    ): Collection {
        $query = new ListVersesBySurahQuery(
            surahId: $surahId,
            locale: $locale,
            withTranslations: $withTranslations,
        );

        return $this->listVersesBySurahHandler->handle($query);
    }

    /**
     * List verses by Juz
     */
    public function listVersesByJuz(
        int $juz,
        int $perPage = 50,
        string $locale = 'ar',
        bool $withTranslations = true,
    ): LengthAwarePaginator {
        $query = new ListVersesByJuzQuery(
            juz: $juz,
            perPage: $perPage,
            locale: $locale,
            withTranslations: $withTranslations,
        );

        return $this->listVersesByJuzHandler->handle($query);
    }

    /**
     * List verses by Quran page
     *
     * @return Collection<int, VerseDTO>
     */
    public function listVersesByPage(
        int $page,
        string $locale = 'ar',
        bool $withTranslations = true,
    ): Collection {
        $query = new ListVersesByPageQuery(
            page: $page,
            locale: $locale,
            withTranslations: $withTranslations,
        );

        return $this->listVersesByPageHandler->handle($query);
    }

    /**
     * Search verses
     */
    public function searchVerses(
        string $searchQuery,
        int $perPage = 20,
        ?int $surahId = null,
        string $language = 'ar',
        bool $searchArabic = true,
        bool $searchTranslation = true,
    ): LengthAwarePaginator {
        $query = new SearchVersesQuery(
            query: $searchQuery,
            perPage: $perPage,
            surahId: $surahId,
            language: $language,
            searchArabic: $searchArabic,
            searchTranslation: $searchTranslation,
        );

        return $this->searchVersesHandler->handle($query);
    }

    /**
     * Get random verse
     */
    public function getRandomVerse(
        string $locale = 'ar',
        bool $withTranslations = true,
    ): VerseDTO {
        $query = new GetRandomVerseQuery(
            locale: $locale,
            withTranslations: $withTranslations,
        );

        return $this->getRandomVerseHandler->handle($query);
    }

    /**
     * Get Sajda verses
     *
     * @return Collection<int, VerseDTO>
     */
    public function getSajdaVerses(
        string $locale = 'ar',
        bool $withTranslations = true,
    ): Collection {
        $query = new GetSajdaVersesQuery(
            locale: $locale,
            withTranslations: $withTranslations,
        );

        return $this->getSajdaVersesHandler->handle($query);
    }

    // ==================== RECITER QUERIES ====================

    /**
     * Get reciter by ID
     */
    public function getReciterById(int $id, string $locale = 'ar'): ReciterDTO
    {
        $query = new GetReciterByIdQuery(
            id: $id,
            locale: $locale,
        );

        return $this->getReciterByIdHandler->handle($query);
    }

    /**
     * Get reciter by slug
     */
    public function getReciterBySlug(string $slug, string $locale = 'ar'): ReciterDTO
    {
        $query = new GetReciterBySlugQuery(
            slug: $slug,
            locale: $locale,
        );

        return $this->getReciterBySlugHandler->handle($query);
    }

    /**
     * List reciters
     */
    public function listReciters(
        int $perPage = 15,
        bool $activeOnly = true,
        ?string $country = null,
        ?string $style = null,
        bool $featuredOnly = false,
        string $locale = 'ar',
    ): LengthAwarePaginator|Collection {
        $query = new ListRecitersQuery(
            perPage: $perPage,
            activeOnly: $activeOnly,
            country: $country,
            style: $style,
            featuredOnly: $featuredOnly,
            locale: $locale,
        );

        return $this->listRecitersHandler->handle($query);
    }

    /**
     * Search reciters
     *
     * @return Collection<int, ReciterDTO>
     */
    public function searchReciters(
        string $searchQuery,
        bool $activeOnly = true,
        string $locale = 'ar',
    ): Collection {
        $query = new SearchRecitersQuery(
            query: $searchQuery,
            activeOnly: $activeOnly,
            locale: $locale,
        );

        return $this->searchRecitersHandler->handle($query);
    }

    // ==================== TAFSIR QUERIES ====================

    /**
     * Get tafsir for a verse
     *
     * @return Collection<int, TafsirDTO>
     */
    public function getTafsirByVerse(int $verseId, string $locale = 'ar'): Collection
    {
        $query = new GetTafsirByVerseQuery(
            verseId: $verseId,
            locale: $locale,
        );

        return $this->getTafsirByVerseHandler->handle($query);
    }

    /**
     * List tafsir by Surah
     */
    public function listTafsirBySurah(
        int $surahId,
        int $perPage = 20,
        string $locale = 'ar',
    ): LengthAwarePaginator {
        $query = new ListTafsirBySurahQuery(
            surahId: $surahId,
            perPage: $perPage,
            locale: $locale,
        );

        return $this->listTafsirBySurahHandler->handle($query);
    }

    /**
     * Search tafsir
     */
    public function searchTafsir(
        string $searchQuery,
        int $perPage = 20,
        string $locale = 'ar',
    ): LengthAwarePaginator {
        $query = new SearchTafsirQuery(
            query: $searchQuery,
            perPage: $perPage,
            locale: $locale,
        );

        return $this->searchTafsirHandler->handle($query);
    }
}
