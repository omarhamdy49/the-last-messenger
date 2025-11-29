<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Services;

use App\Domain\Hadith\Application\Commands\CreateBookCommand;
use App\Domain\Hadith\Application\Commands\CreateChapterCommand;
use App\Domain\Hadith\Application\Commands\CreateCollectionCommand;
use App\Domain\Hadith\Application\Commands\CreateHadithCommand;
use App\Domain\Hadith\Application\Commands\CreateNarratorCommand;
use App\Domain\Hadith\Application\Commands\CreateThemeCommand;
use App\Domain\Hadith\Application\Commands\DeleteHadithCommand;
use App\Domain\Hadith\Application\Commands\ImportHadithsCommand;
use App\Domain\Hadith\Application\Commands\SyncHadithsCommand;
use App\Domain\Hadith\Application\Commands\UpdateCollectionCommand;
use App\Domain\Hadith\Application\Commands\UpdateGradingCommand;
use App\Domain\Hadith\Application\Commands\UpdateHadithCommand;
use App\Domain\Hadith\Application\DTOs\BookDTO;
use App\Domain\Hadith\Application\DTOs\ChapterDTO;
use App\Domain\Hadith\Application\DTOs\CollectionDTO;
use App\Domain\Hadith\Application\DTOs\HadithDTO;
use App\Domain\Hadith\Application\DTOs\NarratorDTO;
use App\Domain\Hadith\Application\DTOs\ThemeDTO;
use App\Domain\Hadith\Application\Handlers\CreateBookHandler;
use App\Domain\Hadith\Application\Handlers\CreateChapterHandler;
use App\Domain\Hadith\Application\Handlers\CreateCollectionHandler;
use App\Domain\Hadith\Application\Handlers\CreateHadithHandler;
use App\Domain\Hadith\Application\Handlers\CreateNarratorHandler;
use App\Domain\Hadith\Application\Handlers\CreateThemeHandler;
use App\Domain\Hadith\Application\Handlers\DeleteHadithHandler;
use App\Domain\Hadith\Application\Handlers\GetBookByIdHandler;
use App\Domain\Hadith\Application\Handlers\GetCollectionByIdHandler;
use App\Domain\Hadith\Application\Handlers\GetCollectionBySlugHandler;
use App\Domain\Hadith\Application\Handlers\GetHadithByIdHandler;
use App\Domain\Hadith\Application\Handlers\GetRandomHadithHandler;
use App\Domain\Hadith\Application\Handlers\ImportHadithsHandler;
use App\Domain\Hadith\Application\Handlers\ListBooksByCollectionHandler;
use App\Domain\Hadith\Application\Handlers\ListCollectionsHandler;
use App\Domain\Hadith\Application\Handlers\ListHadithsByBookHandler;
use App\Domain\Hadith\Application\Handlers\ListHadithsByThemeHandler;
use App\Domain\Hadith\Application\Handlers\ListThemesHandler;
use App\Domain\Hadith\Application\Handlers\SearchHadithHandler;
use App\Domain\Hadith\Application\Handlers\SyncHadithsHandler;
use App\Domain\Hadith\Application\Handlers\UpdateCollectionHandler;
use App\Domain\Hadith\Application\Handlers\UpdateGradingHandler;
use App\Domain\Hadith\Application\Handlers\UpdateHadithHandler;
use App\Domain\Hadith\Application\Queries\GetBookByIdQuery;
use App\Domain\Hadith\Application\Queries\GetCollectionByIdQuery;
use App\Domain\Hadith\Application\Queries\GetCollectionBySlugQuery;
use App\Domain\Hadith\Application\Queries\GetHadithByIdQuery;
use App\Domain\Hadith\Application\Queries\GetRandomHadithQuery;
use App\Domain\Hadith\Application\Queries\ListBooksByCollectionQuery;
use App\Domain\Hadith\Application\Queries\ListCollectionsQuery;
use App\Domain\Hadith\Application\Queries\ListHadithsByBookQuery;
use App\Domain\Hadith\Application\Queries\ListHadithsByThemeQuery;
use App\Domain\Hadith\Application\Queries\ListThemesQuery;
use App\Domain\Hadith\Application\Queries\SearchHadithQuery;
use App\Domain\Hadith\Events\HadithCreated;
use App\Domain\Hadith\Events\HadithDeleted;
use App\Domain\Hadith\Events\HadithGradingUpdated;
use App\Domain\Hadith\Events\HadithUpdated;
use App\Domain\Shared\Application\DTOs\PaginatedResult;
use App\Domain\Shared\Application\DTOs\Result;
use App\Domain\Shared\Events\EventDispatcherInterface;

/**
 * Hadith Application Service (Facade)
 *
 * Orchestrates all Hadith domain operations using CQRS pattern.
 * This service acts as the main entry point for all Hadith-related operations,
 * coordinating commands, queries, and domain events.
 */
final class HadithApplicationService
{
    public function __construct(
        // Command Handlers
        private readonly CreateHadithHandler $createHadithHandler,
        private readonly UpdateHadithHandler $updateHadithHandler,
        private readonly DeleteHadithHandler $deleteHadithHandler,
        private readonly SyncHadithsHandler $syncHadithsHandler,
        private readonly ImportHadithsHandler $importHadithsHandler,
        private readonly UpdateGradingHandler $updateGradingHandler,
        private readonly CreateCollectionHandler $createCollectionHandler,
        private readonly UpdateCollectionHandler $updateCollectionHandler,
        private readonly CreateBookHandler $createBookHandler,
        private readonly CreateChapterHandler $createChapterHandler,
        private readonly CreateNarratorHandler $createNarratorHandler,
        private readonly CreateThemeHandler $createThemeHandler,
        // Query Handlers
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
        // Event Dispatcher
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    // ========================================
    // Hadith Commands
    // ========================================

    /**
     * Create a new hadith
     */
    public function createHadith(
        int $bookId,
        string $hadithNumber,
        array $translations = [],
        ?string $gradingStatus = null,
        ?int $chapterId = null,
        array $narratorIds = [],
        array $themeIds = [],
    ): HadithDTO {
        $command = new CreateHadithCommand(
            bookId: $bookId,
            hadithNumber: $hadithNumber,
            translations: $translations,
            gradingStatus: $gradingStatus,
            chapterId: $chapterId,
            narratorIds: $narratorIds,
            themeIds: $themeIds,
        );

        $result = $this->createHadithHandler->handle($command);

        // Dispatch domain event
        $this->eventDispatcher->dispatch(new HadithCreated(
            hadithId: $result->id,
            bookId: $bookId,
            hadithNumber: $hadithNumber,
            gradingStatus: $gradingStatus,
        ));

        return $result;
    }

    /**
     * Update an existing hadith
     */
    public function updateHadith(
        int $hadithId,
        ?array $translations = null,
        ?string $gradingStatus = null,
        ?int $chapterId = null,
        ?array $narratorIds = null,
        ?array $themeIds = null,
    ): HadithDTO {
        $command = new UpdateHadithCommand(
            hadithId: $hadithId,
            translations: $translations,
            gradingStatus: $gradingStatus,
            chapterId: $chapterId,
            narratorIds: $narratorIds,
            themeIds: $themeIds,
        );

        $result = $this->updateHadithHandler->handle($command);

        // Dispatch domain event
        $this->eventDispatcher->dispatch(new HadithUpdated(
            hadithId: $hadithId,
            changedFields: array_filter([
                'translations' => $translations !== null,
                'grading_status' => $gradingStatus !== null,
                'chapter_id' => $chapterId !== null,
                'narrator_ids' => $narratorIds !== null,
                'theme_ids' => $themeIds !== null,
            ]),
        ));

        return $result;
    }

    /**
     * Delete a hadith
     */
    public function deleteHadith(int $hadithId, bool $forceDelete = false): Result
    {
        // Get hadith first for event data
        $hadith = $this->getHadithById($hadithId);

        $command = new DeleteHadithCommand(
            hadithId: $hadithId,
            forceDelete: $forceDelete,
        );

        $result = $this->deleteHadithHandler->handle($command);

        // Dispatch domain event
        $this->eventDispatcher->dispatch(new HadithDeleted(
            hadithId: $hadithId,
            bookId: $hadith->bookId,
            hadithNumber: $hadith->hadithNumber,
            forceDeleted: $forceDelete,
        ));

        return $result;
    }

    /**
     * Update hadith grading
     */
    public function updateGrading(
        int $hadithId,
        string $gradingStatus,
        ?int $scholarId = null,
        ?int $gradingLevelId = null,
        ?string $notes = null,
    ): HadithDTO {
        // Get current hadith for event data
        $currentHadith = $this->getHadithById($hadithId);

        $command = new UpdateGradingCommand(
            hadithId: $hadithId,
            gradingStatus: $gradingStatus,
            scholarId: $scholarId,
            gradingLevelId: $gradingLevelId,
            notes: $notes,
        );

        $result = $this->updateGradingHandler->handle($command);

        // Dispatch domain event
        $this->eventDispatcher->dispatch(new HadithGradingUpdated(
            hadithId: $hadithId,
            previousGrading: $currentHadith->gradingStatus,
            newGrading: $gradingStatus,
            scholarId: $scholarId,
            gradingLevelId: $gradingLevelId,
        ));

        return $result;
    }

    /**
     * Sync hadiths from external API
     */
    public function syncHadiths(
        int $bookId,
        string $collectionName,
        string $bookNumber,
        bool $dryRun = false,
        ?int $syncLogId = null,
    ): Result {
        $command = new SyncHadithsCommand(
            bookId: $bookId,
            collectionName: $collectionName,
            bookNumber: $bookNumber,
            dryRun: $dryRun,
            syncLogId: $syncLogId,
        );

        return $this->syncHadithsHandler->handle($command);
    }

    /**
     * Import hadiths from JSON file
     */
    public function importHadiths(
        string $filePath,
        int $collectionId,
        bool $validateOnly = false,
        bool $skipExisting = true,
    ): Result {
        $command = new ImportHadithsCommand(
            filePath: $filePath,
            collectionId: $collectionId,
            validateOnly: $validateOnly,
            skipExisting: $skipExisting,
        );

        return $this->importHadithsHandler->handle($command);
    }

    // ========================================
    // Collection Commands
    // ========================================

    /**
     * Create a new collection
     */
    public function createCollection(
        array $name,
        string $slug,
        array $description = [],
        ?int $scholarId = null,
        int $hadithCount = 0,
        bool $isActive = true,
    ): CollectionDTO {
        $command = new CreateCollectionCommand(
            name: $name,
            slug: $slug,
            description: $description,
            scholarId: $scholarId,
            hadithCount: $hadithCount,
            isActive: $isActive,
        );

        return $this->createCollectionHandler->handle($command);
    }

    /**
     * Update an existing collection
     */
    public function updateCollection(
        int $collectionId,
        ?array $name = null,
        ?string $slug = null,
        ?array $description = null,
        ?int $scholarId = null,
        ?int $hadithCount = null,
        ?bool $isActive = null,
    ): CollectionDTO {
        $command = new UpdateCollectionCommand(
            collectionId: $collectionId,
            name: $name,
            slug: $slug,
            description: $description,
            scholarId: $scholarId,
            hadithCount: $hadithCount,
            isActive: $isActive,
        );

        return $this->updateCollectionHandler->handle($command);
    }

    // ========================================
    // Book Commands
    // ========================================

    /**
     * Create a new book
     */
    public function createBook(
        int $collectionId,
        array $title,
        string $slug,
        int $bookNumber,
        array $chapterTitle = [],
        int $hadithCount = 0,
    ): BookDTO {
        $command = new CreateBookCommand(
            collectionId: $collectionId,
            title: $title,
            slug: $slug,
            bookNumber: $bookNumber,
            chapterTitle: $chapterTitle,
            hadithCount: $hadithCount,
        );

        return $this->createBookHandler->handle($command);
    }

    // ========================================
    // Chapter Commands
    // ========================================

    /**
     * Create a new chapter
     */
    public function createChapter(
        int $bookId,
        array $title,
        int $chapterNumber,
        array $intro = [],
    ): ChapterDTO {
        $command = new CreateChapterCommand(
            bookId: $bookId,
            title: $title,
            chapterNumber: $chapterNumber,
            intro: $intro,
        );

        return $this->createChapterHandler->handle($command);
    }

    // ========================================
    // Narrator Commands
    // ========================================

    /**
     * Create a new narrator
     */
    public function createNarrator(
        string $nameAr,
        ?string $nameEn = null,
        ?string $kuniya = null,
        ?string $nasab = null,
        ?string $laqab = null,
        ?int $birthYear = null,
        ?int $deathYear = null,
        ?string $biographyAr = null,
        ?string $biographyEn = null,
        ?string $reliability = null,
    ): NarratorDTO {
        $command = new CreateNarratorCommand(
            nameAr: $nameAr,
            nameEn: $nameEn,
            kuniya: $kuniya,
            nasab: $nasab,
            laqab: $laqab,
            birthYear: $birthYear,
            deathYear: $deathYear,
            biographyAr: $biographyAr,
            biographyEn: $biographyEn,
            reliability: $reliability,
        );

        return $this->createNarratorHandler->handle($command);
    }

    // ========================================
    // Theme Commands
    // ========================================

    /**
     * Create a new theme
     */
    public function createTheme(
        array $name,
        array $slug,
        array $description = [],
        ?int $parentId = null,
        int $sortOrder = 0,
    ): ThemeDTO {
        $command = new CreateThemeCommand(
            name: $name,
            slug: $slug,
            description: $description,
            parentId: $parentId,
            sortOrder: $sortOrder,
        );

        return $this->createThemeHandler->handle($command);
    }

    // ========================================
    // Queries
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
    ): PaginatedResult {
        $query = new SearchHadithQuery(
            query: $searchQuery,
            perPage: $perPage,
            filters: $filters,
            locale: $locale,
        );

        return $this->searchHadithHandler->handle($query);
    }

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
        string $sortBy = 'name_ar',
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

    /**
     * List hadiths by theme
     */
    public function listHadithsByTheme(
        int $themeId,
        int $perPage = 20,
        int $page = 1,
        ?string $gradingStatus = null,
        bool $includeSubThemes = false,
        ?string $locale = 'ar',
    ): PaginatedResult {
        $query = new ListHadithsByThemeQuery(
            themeId: $themeId,
            perPage: $perPage,
            page: $page,
            gradingStatus: $gradingStatus,
            includeSubThemes: $includeSubThemes,
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
        bool $onlyRoot = false,
        bool $withChildren = false,
        string $sortBy = 'sort_order',
        string $sortDirection = 'asc',
        ?string $locale = 'ar',
    ): PaginatedResult {
        $query = new ListThemesQuery(
            perPage: $perPage,
            page: $page,
            search: $search,
            onlyRoot: $onlyRoot,
            withChildren: $withChildren,
            sortBy: $sortBy,
            sortDirection: $sortDirection,
            locale: $locale,
        );

        return $this->listThemesHandler->handle($query);
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
}
