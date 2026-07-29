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
use App\Domain\Hadith\Application\Handlers\GetHadithByIdHandler;
use App\Domain\Hadith\Application\Handlers\UpdateCollectionHandler;
use App\Domain\Hadith\Application\Handlers\UpdateGradingHandler;
use App\Domain\Hadith\Application\Handlers\UpdateHadithHandler;
use App\Domain\Hadith\Application\Queries\GetHadithByIdQuery;
use App\Domain\Hadith\Events\HadithCreated;
use App\Domain\Hadith\Events\HadithDeleted;
use App\Domain\Hadith\Events\HadithGradingUpdated;
use App\Domain\Hadith\Events\HadithUpdated;
use App\Domain\Shared\Application\DTOs\Result;
use App\Domain\Shared\Events\EventDispatcherInterface;

/**
 * Hadith Command Service
 *
 * Handles all write (command) operations for the Hadith domain.
 * Part of the CQRS split from HadithApplicationService.
 */
final class HadithCommandService
{
    public function __construct(
        private readonly CreateHadithHandler $createHadithHandler,
        private readonly UpdateHadithHandler $updateHadithHandler,
        private readonly DeleteHadithHandler $deleteHadithHandler,
        private readonly UpdateGradingHandler $updateGradingHandler,
        private readonly CreateCollectionHandler $createCollectionHandler,
        private readonly UpdateCollectionHandler $updateCollectionHandler,
        private readonly CreateBookHandler $createBookHandler,
        private readonly CreateChapterHandler $createChapterHandler,
        private readonly CreateNarratorHandler $createNarratorHandler,
        private readonly CreateThemeHandler $createThemeHandler,
        // Needed for event data in delete/update grading
        private readonly GetHadithByIdHandler $getHadithByIdHandler,
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
        $hadith = $this->getHadithByIdHandler->handle(
            new GetHadithByIdQuery(hadithId: $hadithId, withRelations: false, locale: 'ar')
        );

        $command = new DeleteHadithCommand(
            hadithId: $hadithId,
            forceDelete: $forceDelete,
        );

        $result = $this->deleteHadithHandler->handle($command);

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
        ?string $notes = null,
    ): HadithDTO {
        $currentHadith = $this->getHadithByIdHandler->handle(
            new GetHadithByIdQuery(hadithId: $hadithId, withRelations: false, locale: 'ar')
        );

        $command = new UpdateGradingCommand(
            hadithId: $hadithId,
            gradingStatus: $gradingStatus,
            scholarId: $scholarId,
            notes: $notes,
        );

        $result = $this->updateGradingHandler->handle($command);

        $this->eventDispatcher->dispatch(new HadithGradingUpdated(
            hadithId: $hadithId,
            previousGrading: $currentHadith->gradingStatus,
            newGrading: $gradingStatus,
            scholarId: $scholarId,
        ));

        return $result;
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
}
