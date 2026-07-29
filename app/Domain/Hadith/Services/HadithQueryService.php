<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Services;

use App\Domain\Hadith\Application\DTOs\BookDTO;
use App\Domain\Hadith\Application\DTOs\ChapterContextDTO;
use App\Domain\Hadith\Application\DTOs\CollectionDTO;
use App\Domain\Hadith\Application\DTOs\HadithDetailDTO;
use App\Domain\Hadith\Application\DTOs\HadithDTO;
use App\Domain\Hadith\Application\DTOs\ParallelNarrationsDTO;
use App\Domain\Hadith\Application\Handlers\GetBookByIdHandler;
use App\Domain\Hadith\Application\Handlers\GetChapterContextHandler;
use App\Domain\Hadith\Application\Handlers\GetCollectionByIdHandler;
use App\Domain\Hadith\Application\Handlers\GetCollectionBySlugHandler;
use App\Domain\Hadith\Application\Handlers\GetHadithByIdHandler;
use App\Domain\Hadith\Application\Handlers\GetHadithDetailHandler;
use App\Domain\Hadith\Application\Handlers\GetParallelNarrationsHandler;
use App\Domain\Hadith\Application\Handlers\GetRandomHadithHandler;
use App\Domain\Hadith\Application\Handlers\ListBooksByCollectionHandler;
use App\Domain\Hadith\Application\Handlers\ListChapterHadithsHandler;
use App\Domain\Hadith\Application\Handlers\ListCollectionsHandler;
use App\Domain\Hadith\Application\Handlers\ListHadithsByBookHandler;
use App\Domain\Hadith\Application\Handlers\ListHadithsByThemeHandler;
use App\Domain\Hadith\Application\Handlers\ListThemesHandler;
use App\Domain\Hadith\Application\Handlers\SearchHadithHandler;
use App\Domain\Hadith\Application\Queries\GetBookByIdQuery;
use App\Domain\Hadith\Application\Queries\GetChapterContextQuery;
use App\Domain\Hadith\Application\Queries\GetCollectionByIdQuery;
use App\Domain\Hadith\Application\Queries\GetCollectionBySlugQuery;
use App\Domain\Hadith\Application\Queries\GetHadithByIdQuery;
use App\Domain\Hadith\Application\Queries\GetHadithDetailQuery;
use App\Domain\Hadith\Application\Queries\GetParallelNarrationsQuery;
use App\Domain\Hadith\Application\Queries\GetRandomHadithQuery;
use App\Domain\Hadith\Application\Queries\ListBooksByCollectionQuery;
use App\Domain\Hadith\Application\Queries\ListChapterHadithsQuery;
use App\Domain\Hadith\Application\Queries\ListCollectionsQuery;
use App\Domain\Hadith\Application\Queries\ListHadithsByBookQuery;
use App\Domain\Hadith\Application\Queries\ListHadithsByThemeQuery;
use App\Domain\Hadith\Application\Queries\ListThemesQuery;
use App\Domain\Hadith\Application\Queries\SearchHadithQuery;
use App\Domain\Hadith\Repositories\HadithRepositoryInterface;
use App\Domain\Shared\Application\DTOs\PaginatedResult;

/**
 * Hadith Query Service
 *
 * Handles all read-only (query) operations for the Hadith domain.
 * Part of the CQRS split from HadithApplicationService.
 */
final class HadithQueryService
{
    public function __construct(
        private readonly GetHadithByIdHandler $getHadithByIdHandler,
        private readonly SearchHadithHandler $searchHadithHandler,
        private readonly GetCollectionByIdHandler $getCollectionByIdHandler,
        private readonly GetCollectionBySlugHandler $getCollectionBySlugHandler,
        private readonly ListCollectionsHandler $listCollectionsHandler,
        private readonly GetBookByIdHandler $getBookByIdHandler,
        private readonly ListBooksByCollectionHandler $listBooksByCollectionHandler,
        private readonly ListHadithsByBookHandler $listHadithsByBookHandler,
        private readonly ListHadithsByThemeHandler $listHadithsByThemeHandler,
        private readonly ListThemesHandler $listThemesHandler,
        private readonly GetRandomHadithHandler $getRandomHadithHandler,
        private readonly GetChapterContextHandler $getChapterContextHandler,
        private readonly ListChapterHadithsHandler $listChapterHadithsHandler,
        private readonly GetHadithDetailHandler $getHadithDetailHandler,
        private readonly GetParallelNarrationsHandler $getParallelNarrationsHandler,
        private readonly HadithRepositoryInterface $hadithRepository,
    ) {}

    /**
     * Hadith ids (within the given set) that have a translation in the given
     * language — used to decide whether to surface the lazy translation toggle.
     *
     * @param  array<int, int>  $hadithIds
     * @return array<int, int>
     */
    public function hadithIdsWithTranslation(array $hadithIds, string $languageCode): array
    {
        return $this->hadithRepository->hadithIdsWithTranslation($hadithIds, $languageCode);
    }

    /**
     * Lazy translation payload: translation text keyed by hadith id.
     *
     * @param  array<int, int>  $hadithIds
     * @return array<int, string>
     */
    public function hadithTranslations(array $hadithIds, string $languageCode): array
    {
        return $this->hadithRepository->translationsForHadiths($hadithIds, $languageCode);
    }

    // ========================================
    // Hadith Queries
    // ========================================

    /**
     * Get a hadith by ID
     */
    public function getHadithById(int $hadithId, bool $withRelations = true, ?string $locale = 'ar'): HadithDTO
    {
        $query = new GetHadithByIdQuery(
            hadithId: $hadithId,
            withRelations: $withRelations,
            locale: $locale,
        );

        return $this->getHadithByIdHandler->handle($query);
    }

    /**
     * Search hadiths
     */
    public function searchHadiths(
        string $searchQuery,
        int $perPage = 15,
        array $filters = [],
        ?string $locale = 'ar',
        int $page = 1,
        string $sortBy = 'hadith_number',
        string $sortDirection = 'asc',
    ): PaginatedResult {
        // The handler resolves the DTO locale from filters['locale'].
        $filters['locale'] = $locale ?? 'ar';

        $query = new SearchHadithQuery(
            searchTerm: $searchQuery,
            page: $page,
            perPage: $perPage,
            filters: $filters,
            sortBy: $sortBy,
            sortDirection: $sortDirection,
        );

        return $this->searchHadithHandler->handle($query);
    }

    /**
     * Get a random hadith
     */
    public function getRandomHadith(
        ?string $gradingStatus = 'sahih',
        ?int $collectionId = null,
        bool $withTranslations = true,
        ?string $locale = 'ar',
    ): HadithDTO {
        $query = new GetRandomHadithQuery(
            gradingStatus: $gradingStatus,
            collectionId: $collectionId,
            withTranslations: $withTranslations,
            locale: $locale,
        );

        return $this->getRandomHadithHandler->handle($query);
    }

    /**
     * Get the full public detail of a hadith (translations, isnad,
     * commentaries, scholar gradings, parallels count).
     */
    public function getHadithDetail(int $hadithId, ?string $locale = 'ar'): HadithDetailDTO
    {
        $query = new GetHadithDetailQuery(
            hadithId: $hadithId,
            locale: $locale,
        );

        return $this->getHadithDetailHandler->handle($query);
    }

    /**
     * Get a hadith with its parallel narrations (same text hash).
     */
    public function getParallelNarrations(int $hadithId, ?string $locale = 'ar'): ParallelNarrationsDTO
    {
        $query = new GetParallelNarrationsQuery(
            hadithId: $hadithId,
            locale: $locale,
        );

        return $this->getParallelNarrationsHandler->handle($query);
    }

    /**
     * Resolve the public chapter (kitab) page context by collection slug +
     * book number, including previous/next chapter navigation.
     */
    public function getChapterContext(string $collectionSlug, int $bookNumber, ?string $locale = 'ar'): ChapterContextDTO
    {
        $query = new GetChapterContextQuery(
            collectionSlug: $collectionSlug,
            bookNumber: $bookNumber,
            locale: $locale,
        );

        return $this->getChapterContextHandler->handle($query);
    }

    /**
     * List the hadiths of a public chapter with search + grading filter.
     */
    public function listChapterHadiths(
        int $bookId,
        int $perPage = 15,
        int $page = 1,
        ?string $gradingStatus = null,
        string $search = '',
        ?string $locale = 'ar',
    ): PaginatedResult {
        $query = new ListChapterHadithsQuery(
            bookId: $bookId,
            perPage: $perPage,
            page: $page,
            gradingStatus: $gradingStatus,
            search: $search,
            locale: $locale,
        );

        return $this->listChapterHadithsHandler->handle($query);
    }

    // ========================================
    // Collection Queries
    // ========================================

    /**
     * Get a collection by ID
     */
    public function getCollectionById(int $collectionId, bool $withBooks = false, ?string $locale = 'ar'): CollectionDTO
    {
        $query = new GetCollectionByIdQuery(
            collectionId: $collectionId,
            withBooks: $withBooks,
            locale: $locale,
        );

        return $this->getCollectionByIdHandler->handle($query);
    }

    /**
     * Get a collection by slug
     */
    public function getCollectionBySlug(string $slug, bool $withBooks = false, ?string $locale = 'ar'): CollectionDTO
    {
        $query = new GetCollectionBySlugQuery(
            slug: $slug,
            withBooks: $withBooks,
            locale: $locale,
        );

        return $this->getCollectionBySlugHandler->handle($query);
    }

    /**
     * List all collections
     */
    public function listCollections(
        int $perPage = 15,
        int $page = 1,
        ?string $search = null,
        bool $onlyActive = true,
        string $sortBy = 'id',
        string $sortDirection = 'asc',
        ?string $locale = 'ar',
    ): PaginatedResult {
        $query = new ListCollectionsQuery(
            perPage: $perPage,
            page: $page,
            search: $search,
            onlyActive: $onlyActive,
            sortBy: $sortBy,
            sortDirection: $sortDirection,
            locale: $locale,
        );

        return $this->listCollectionsHandler->handle($query);
    }

    // ========================================
    // Book Queries
    // ========================================

    /**
     * Get a book by ID
     */
    public function getBookById(
        int $bookId,
        bool $withChapters = false,
        bool $withCollection = true,
        ?string $locale = 'ar',
    ): BookDTO {
        $query = new GetBookByIdQuery(
            bookId: $bookId,
            withChapters: $withChapters,
            withCollection: $withCollection,
            locale: $locale,
        );

        return $this->getBookByIdHandler->handle($query);
    }

    /**
     * List books by collection
     */
    public function listBooksByCollection(
        int $collectionId,
        int $perPage = 15,
        int $page = 1,
        string $sortBy = 'book_number',
        string $sortDirection = 'asc',
        ?string $locale = 'ar',
    ): PaginatedResult {
        $query = new ListBooksByCollectionQuery(
            collectionId: $collectionId,
            perPage: $perPage,
            page: $page,
            sortBy: $sortBy,
            sortDirection: $sortDirection,
            locale: $locale,
        );

        return $this->listBooksByCollectionHandler->handle($query);
    }

    /**
     * List hadiths by book
     */
    public function listHadithsByBook(
        int $bookId,
        int $perPage = 20,
        int $page = 1,
        ?string $gradingStatus = null,
        string $sortBy = 'hadith_number',
        string $sortDirection = 'asc',
        ?string $locale = 'ar',
    ): PaginatedResult {
        $query = new ListHadithsByBookQuery(
            bookId: $bookId,
            perPage: $perPage,
            page: $page,
            gradingStatus: $gradingStatus,
            sortBy: $sortBy,
            sortDirection: $sortDirection,
            locale: $locale,
        );

        return $this->listHadithsByBookHandler->handle($query);
    }

    // ========================================
    // Theme Queries
    // ========================================

    /**
     * List hadiths by theme
     */
    public function listHadithsByTheme(
        int $themeId,
        int $perPage = 20,
        int $page = 1,
        ?string $gradingStatus = null,
        ?string $locale = 'ar',
    ): PaginatedResult {
        $query = new ListHadithsByThemeQuery(
            themeId: $themeId,
            perPage: $perPage,
            page: $page,
            gradingStatus: $gradingStatus,
            locale: $locale,
        );

        return $this->listHadithsByThemeHandler->handle($query);
    }

    /**
     * List themes
     */
    public function listThemes(
        int $perPage = 50,
        int $page = 1,
        ?string $search = null,
        string $sortBy = 'id',
        string $sortDirection = 'asc',
        ?string $locale = 'ar',
    ): PaginatedResult {
        $query = new ListThemesQuery(
            perPage: $perPage,
            page: $page,
            search: $search,
            sortBy: $sortBy,
            sortDirection: $sortDirection,
            locale: $locale,
        );

        return $this->listThemesHandler->handle($query);
    }
}
