<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\Application\Commands\CreateReciterCommand;
use App\Domain\Quran\Application\Commands\CreateSurahInfoCommand;
use App\Domain\Quran\Application\Commands\CreateTafsirCommand;
use App\Domain\Quran\Application\Commands\CreateVerseCommand;
use App\Domain\Quran\Application\Commands\CreateVerseTranslationCommand;
use App\Domain\Quran\Application\Commands\DeleteReciterCommand;
use App\Domain\Quran\Application\Commands\DeleteTafsirCommand;
use App\Domain\Quran\Application\Commands\ImportQuranFromJsonCommand;
use App\Domain\Quran\Application\Commands\SyncQuranDataCommand;
use App\Domain\Quran\Application\Commands\UpdateReciterCommand;
use App\Domain\Quran\Application\Commands\UpdateSurahCommand;
use App\Domain\Quran\Application\Commands\UpdateSurahInfoCommand;
use App\Domain\Quran\Application\Commands\UpdateTafsirCommand;
use App\Domain\Quran\Application\Commands\UpdateVerseCommand;
use App\Domain\Quran\Application\Commands\UpdateVerseTranslationCommand;
use App\Domain\Quran\Application\DTOs\ReciterDTO;
use App\Domain\Quran\Application\DTOs\SurahDTO;
use App\Domain\Quran\Application\DTOs\SurahInfoDTO;
use App\Domain\Quran\Application\DTOs\TafsirDTO;
use App\Domain\Quran\Application\DTOs\VerseDTO;
use App\Domain\Quran\Application\Handlers\CreateReciterHandler;
use App\Domain\Quran\Application\Handlers\CreateSurahInfoHandler;
use App\Domain\Quran\Application\Handlers\CreateTafsirHandler;
use App\Domain\Quran\Application\Handlers\CreateVerseHandler;
use App\Domain\Quran\Application\Handlers\CreateVerseTranslationHandler;
use App\Domain\Quran\Application\Handlers\DeleteReciterHandler;
use App\Domain\Quran\Application\Handlers\DeleteTafsirHandler;
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
use App\Domain\Quran\Application\Handlers\ImportQuranFromJsonHandler;
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
use App\Domain\Quran\Application\Handlers\SyncQuranDataHandler;
use App\Domain\Quran\Application\Handlers\UpdateReciterHandler;
use App\Domain\Quran\Application\Handlers\UpdateSurahHandler;
use App\Domain\Quran\Application\Handlers\UpdateSurahInfoHandler;
use App\Domain\Quran\Application\Handlers\UpdateTafsirHandler;
use App\Domain\Quran\Application\Handlers\UpdateVerseHandler;
use App\Domain\Quran\Application\Handlers\UpdateVerseTranslationHandler;
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
use App\Domain\Quran\Events\QuranDataImported;
use App\Domain\Quran\Events\QuranDataSynced;
use App\Domain\Quran\Events\ReciterCreated;
use App\Domain\Quran\Events\ReciterDeleted;
use App\Domain\Quran\Events\ReciterUpdated;
use App\Domain\Quran\Events\SurahInfoCreated;
use App\Domain\Quran\Events\SurahInfoUpdated;
use App\Domain\Quran\Events\SurahUpdated;
use App\Domain\Quran\Events\TafsirCreated;
use App\Domain\Quran\Events\TafsirDeleted;
use App\Domain\Quran\Events\TafsirUpdated;
use App\Domain\Quran\Events\VerseCreated;
use App\Domain\Quran\Events\VerseUpdated;
use App\Domain\Quran\Models\VerseTranslation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Quran Application Service Facade
 *
 * Orchestrates all CQRS operations for the Quran domain.
 * This is the main entry point for the Quran module's application layer.
 */
final class QuranApplicationService
{
    public function __construct(
        // Command Handlers
        private readonly CreateReciterHandler $createReciterHandler,
        private readonly UpdateReciterHandler $updateReciterHandler,
        private readonly DeleteReciterHandler $deleteReciterHandler,
        private readonly UpdateSurahHandler $updateSurahHandler,
        private readonly CreateSurahInfoHandler $createSurahInfoHandler,
        private readonly UpdateSurahInfoHandler $updateSurahInfoHandler,
        private readonly CreateVerseHandler $createVerseHandler,
        private readonly UpdateVerseHandler $updateVerseHandler,
        private readonly CreateVerseTranslationHandler $createVerseTranslationHandler,
        private readonly UpdateVerseTranslationHandler $updateVerseTranslationHandler,
        private readonly CreateTafsirHandler $createTafsirHandler,
        private readonly UpdateTafsirHandler $updateTafsirHandler,
        private readonly DeleteTafsirHandler $deleteTafsirHandler,
        private readonly SyncQuranDataHandler $syncQuranDataHandler,
        private readonly ImportQuranFromJsonHandler $importQuranFromJsonHandler,
        // Query Handlers
        private readonly GetSurahByIdHandler $getSurahByIdHandler,
        private readonly GetSurahByNumberHandler $getSurahByNumberHandler,
        private readonly ListSurahsHandler $listSurahsHandler,
        private readonly SearchSurahsHandler $searchSurahsHandler,
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
        private readonly GetSurahInfoHandler $getSurahInfoHandler,
    ) {}

    // ==================== RECITER COMMANDS ====================

    /**
     * Create a new reciter
     *
     * @param array<string, string> $name
     * @param array<string, string>|null $description
     */
    public function createReciter(
        array $name,
        string $slug,
        ?array $description = null,
        ?string $style = null,
        ?string $country = null,
        ?string $profileImage = null,
        bool $isFeatured = false,
        bool $isActive = true,
        int $displayOrder = 0,
    ): ReciterDTO {
        $command = new CreateReciterCommand(
            name: $name,
            slug: $slug,
            description: $description,
            style: $style,
            country: $country,
            profileImage: $profileImage,
            isFeatured: $isFeatured,
            isActive: $isActive,
            displayOrder: $displayOrder,
        );

        $reciter = $this->createReciterHandler->handle($command);

        event(new ReciterCreated($reciter));

        return $reciter;
    }

    /**
     * Update a reciter
     *
     * @param array<string, string>|null $name
     * @param array<string, string>|null $description
     */
    public function updateReciter(
        int $id,
        ?array $name = null,
        ?string $slug = null,
        ?array $description = null,
        ?string $style = null,
        ?string $country = null,
        ?string $profileImage = null,
        ?bool $isFeatured = null,
        ?bool $isActive = null,
        ?int $displayOrder = null,
    ): ReciterDTO {
        $command = new UpdateReciterCommand(
            id: $id,
            name: $name,
            slug: $slug,
            description: $description,
            style: $style,
            country: $country,
            profileImage: $profileImage,
            isFeatured: $isFeatured,
            isActive: $isActive,
            displayOrder: $displayOrder,
        );

        $reciter = $this->updateReciterHandler->handle($command);

        event(new ReciterUpdated($reciter));

        return $reciter;
    }

    /**
     * Delete a reciter
     */
    public function deleteReciter(int $id): bool
    {
        $command = new DeleteReciterCommand($id);

        $result = $this->deleteReciterHandler->handle($command);

        if ($result) {
            event(new ReciterDeleted($id));
        }

        return $result;
    }

    // ==================== SURAH COMMANDS ====================

    /**
     * Update a Surah
     *
     * @param array<int, int>|null $pages
     */
    public function updateSurah(
        int $id,
        ?string $nameAr = null,
        ?string $nameEn = null,
        ?string $transliteration = null,
        ?string $revelationPlace = null,
        ?int $revelationOrder = null,
        ?int $versesCount = null,
        ?bool $bismillahPre = null,
        ?array $pages = null,
    ): SurahDTO {
        $command = new UpdateSurahCommand(
            id: $id,
            nameAr: $nameAr,
            nameEn: $nameEn,
            transliteration: $transliteration,
            revelationPlace: $revelationPlace,
            revelationOrder: $revelationOrder,
            versesCount: $versesCount,
            bismillahPre: $bismillahPre,
            pages: $pages,
        );

        $surah = $this->updateSurahHandler->handle($command);

        event(new SurahUpdated($surah));

        return $surah;
    }

    /**
     * Create Surah info
     */
    public function createSurahInfo(
        int $surahId,
        string $lang,
        ?string $description = null,
        ?string $content = null,
        ?string $source = null,
        bool $isActive = true,
    ): SurahInfoDTO {
        $command = new CreateSurahInfoCommand(
            surahId: $surahId,
            lang: $lang,
            description: $description,
            content: $content,
            source: $source,
            isActive: $isActive,
        );

        $surahInfo = $this->createSurahInfoHandler->handle($command);

        event(new SurahInfoCreated($surahInfo));

        return $surahInfo;
    }

    /**
     * Update Surah info
     */
    public function updateSurahInfo(
        int $id,
        ?string $lang = null,
        ?string $description = null,
        ?string $content = null,
        ?string $source = null,
        ?bool $isActive = null,
    ): SurahInfoDTO {
        $command = new UpdateSurahInfoCommand(
            id: $id,
            lang: $lang,
            description: $description,
            content: $content,
            source: $source,
            isActive: $isActive,
        );

        $surahInfo = $this->updateSurahInfoHandler->handle($command);

        event(new SurahInfoUpdated($surahInfo));

        return $surahInfo;
    }

    // ==================== VERSE COMMANDS ====================

    /**
     * Create a verse
     */
    public function createVerse(
        int $surahId,
        int $verseNumber,
        string $verseKey,
        int $juzNumber,
        int $hizbNumber,
        int $rubElHizbNumber,
        int $rukuNumber,
        ?int $manzilNumber = null,
        ?int $sajdahNumber = null,
        int $pageNumber = 1,
    ): VerseDTO {
        $command = new CreateVerseCommand(
            surahId: $surahId,
            verseNumber: $verseNumber,
            verseKey: $verseKey,
            juzNumber: $juzNumber,
            hizbNumber: $hizbNumber,
            rubElHizbNumber: $rubElHizbNumber,
            rukuNumber: $rukuNumber,
            manzilNumber: $manzilNumber,
            sajdahNumber: $sajdahNumber,
            pageNumber: $pageNumber,
        );

        $verse = $this->createVerseHandler->handle($command);

        event(new VerseCreated($verse));

        return $verse;
    }

    /**
     * Update a verse
     */
    public function updateVerse(
        int $id,
        ?int $juzNumber = null,
        ?int $hizbNumber = null,
        ?int $rubElHizbNumber = null,
        ?int $rukuNumber = null,
        ?int $manzilNumber = null,
        ?int $sajdahNumber = null,
        ?int $pageNumber = null,
    ): VerseDTO {
        $command = new UpdateVerseCommand(
            id: $id,
            juzNumber: $juzNumber,
            hizbNumber: $hizbNumber,
            rubElHizbNumber: $rubElHizbNumber,
            rukuNumber: $rukuNumber,
            manzilNumber: $manzilNumber,
            sajdahNumber: $sajdahNumber,
            pageNumber: $pageNumber,
        );

        $verse = $this->updateVerseHandler->handle($command);

        event(new VerseUpdated($verse));

        return $verse;
    }

    /**
     * Create a verse translation
     */
    public function createVerseTranslation(
        int $verseId,
        string $lang,
        string $text,
        ?string $translatorName = null,
        bool $isActive = true,
    ): VerseTranslation {
        $command = new CreateVerseTranslationCommand(
            verseId: $verseId,
            lang: $lang,
            text: $text,
            translatorName: $translatorName,
            isActive: $isActive,
        );

        return $this->createVerseTranslationHandler->handle($command);
    }

    /**
     * Update a verse translation
     */
    public function updateVerseTranslation(
        int $id,
        ?string $lang = null,
        ?string $text = null,
        ?string $translatorName = null,
        ?bool $isActive = null,
    ): VerseTranslation {
        $command = new UpdateVerseTranslationCommand(
            id: $id,
            lang: $lang,
            text: $text,
            translatorName: $translatorName,
            isActive: $isActive,
        );

        return $this->updateVerseTranslationHandler->handle($command);
    }

    // ==================== TAFSIR COMMANDS ====================

    /**
     * Create a tafsir
     */
    public function createTafsir(
        int $verseId,
        string $verseKey,
        string $lang,
        string $text,
        bool $isActive = true,
    ): TafsirDTO {
        $command = new CreateTafsirCommand(
            verseId: $verseId,
            verseKey: $verseKey,
            lang: $lang,
            text: $text,
            isActive: $isActive,
        );

        $tafsir = $this->createTafsirHandler->handle($command);

        event(new TafsirCreated($tafsir));

        return $tafsir;
    }

    /**
     * Update a tafsir
     */
    public function updateTafsir(
        int $id,
        ?string $lang = null,
        ?string $text = null,
        ?bool $isActive = null,
    ): TafsirDTO {
        $command = new UpdateTafsirCommand(
            id: $id,
            lang: $lang,
            text: $text,
            isActive: $isActive,
        );

        $tafsir = $this->updateTafsirHandler->handle($command);

        event(new TafsirUpdated($tafsir));

        return $tafsir;
    }

    /**
     * Delete a tafsir
     */
    public function deleteTafsir(int $id): bool
    {
        $command = new DeleteTafsirCommand($id);

        $result = $this->deleteTafsirHandler->handle($command);

        if ($result) {
            event(new TafsirDeleted($id));
        }

        return $result;
    }

    // ==================== SYNC & IMPORT COMMANDS ====================

    /**
     * Sync Quran data from external API
     *
     * @return array<string, mixed>
     */
    public function syncQuranData(
        bool $syncSurahs = true,
        bool $syncVerses = true,
        bool $syncTranslations = true,
        bool $syncReciters = true,
        ?string $language = null,
        ?int $surahId = null,
    ): array {
        $command = new SyncQuranDataCommand(
            syncSurahs: $syncSurahs,
            syncVerses: $syncVerses,
            syncTranslations: $syncTranslations,
            syncReciters: $syncReciters,
            language: $language,
            surahId: $surahId,
        );

        $results = $this->syncQuranDataHandler->handle($command);

        event(new QuranDataSynced($results));

        return $results;
    }

    /**
     * Import Quran data from JSON file
     *
     * @return array<string, mixed>
     */
    public function importFromJson(
        string $filePath,
        string $type,
        bool $truncateFirst = false,
    ): array {
        $command = new ImportQuranFromJsonCommand(
            filePath: $filePath,
            type: $type,
            truncateFirst: $truncateFirst,
        );

        $results = $this->importQuranFromJsonHandler->handle($command);

        event(new QuranDataImported($type, $filePath, $results));

        return $results;
    }

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
