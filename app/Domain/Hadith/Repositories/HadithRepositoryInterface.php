<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Repositories;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithIsnad;
use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Hadith Repository Interface
 *
 * Contract for Hadith data access operations.
 */
interface HadithRepositoryInterface extends RepositoryInterface
{
    /**
     * Get hadith by number within a book
     */
    public function getByBookAndNumber(int $bookId, string $hadithNumber): ?Hadith;

    /**
     * Get hadiths by book
     */
    public function getByBook(int $bookId): Collection;

    /**
     * Get hadiths by grading status
     */
    public function getByGrading(string $gradingStatus): Collection;

    /**
     * Get only authentic (sahih) hadiths
     */
    public function getAuthentic(): Collection;

    /**
     * Get hadiths with scholarly agreement
     */
    public function getWithAgreement(): Collection;

    /**
     * Get hadiths by theme
     */
    public function getByTheme(int $themeId): Collection;

    /**
     * Get hadiths by narrator (companion)
     */
    public function getByNarrator(int $companionId): Collection;

    /**
     * Search hadiths across translations
     */
    public function search(string $query, int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Get paginated hadiths with filters
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateWithFilters(
        int $perPage = 15,
        string $search = '',
        string $sortBy = 'hadith_number',
        array $filters = []
    ): LengthAwarePaginator;

    /**
     * Count hadiths by grading
     */
    public function countByGrading(string $gradingStatus): int;

    /**
     * Get random hadith
     */
    public function getRandom(?string $gradingStatus = null): ?Hadith;

    /**
     * Get hadith with full relations
     */
    public function getWithFullRelations(int $id): ?Hadith;

    /**
     * Paginated list for the admin index view.
     *
     * Loads book, book.collection, and the Arabic translation only.
     *
     * @param  array<string, mixed>  $filters  Supported keys: collection_id, book_id, grading_status
     */
    public function listForAdmin(
        int $perPage = 20,
        string $search = '',
        array $filters = []
    ): LengthAwarePaginator;

    /**
     * Locale-aware search used by the admin search endpoint.
     *
     * Returns a paginator whose items are Hadith models ready for JSON
     * serialisation (book, book.collection, and translations in the
     * requested locale plus Arabic).
     *
     * @param  array<string, mixed>  $filters  Supported keys: collection_id
     */
    public function searchForAdmin(
        string $search = '',
        array $filters = [],
        int $perPage = 15,
        string $locale = 'ar'
    ): LengthAwarePaginator;

    /**
     * Delete multiple hadiths by their IDs.
     *
     * @param  array<int>  $ids
     */
    public function bulkDelete(array $ids): int;

    /**
     * Paginate the hadiths of a book (public chapter reader).
     *
     * Search applies a parameterized LIKE on translations.text scoped to the
     * book; gradingStatus is the canonical machine value (sahih/hasan/daif).
     * Items carry book, book.collection and locale+Arabic translations with
     * explicit column lists.
     */
    public function paginatePublicByBook(
        int $bookId,
        int $perPage = 15,
        int $page = 1,
        ?string $gradingStatus = null,
        string $search = '',
        string $locale = 'ar'
    ): LengthAwarePaginator;

    /**
     * Get a hadith with every relation the public detail page renders
     * (translations, isnad+narrators, commentaries+scholars,
     * gradings+scholars+levels, themes), all with explicit column lists.
     */
    public function getWithPublicDetail(int $id): ?Hadith;

    /**
     * Get the parallel narrations of a text hash: hadiths sharing the same
     * normalized Arabic text, excluding the given hadith.
     */
    public function getByTextHash(string $textHash, int $excludeHadithId): Collection;

    /**
     * Count parallel narrations for a text hash, excluding the given hadith.
     */
    public function countByTextHash(string $textHash, int $excludeHadithId): int;

    /**
     * Hadith ids (within the given set) that have a non-empty translation in the
     * given language — lightweight existence check (no text loaded).
     *
     * @param  array<int, int>  $hadithIds
     * @return array<int, int>
     */
    public function hadithIdsWithTranslation(array $hadithIds, string $languageCode): array;

    /**
     * Translation text keyed by hadith id, for the given language.
     *
     * @param  array<int, int>  $hadithIds
     * @return array<int, string>
     */
    public function translationsForHadiths(array $hadithIds, string $languageCode): array;

    /**
     * Append a narrator to a hadith's isnad chain at the next position.
     */
    public function addNarratorToChain(int $hadithId, int $narratorId, ?string $transmissionMethod): HadithIsnad;

    /**
     * Swap an isnad link with its immediate neighbor ('up' or 'down').
     * Returns false if already at that end of the chain.
     */
    public function moveIsnadLink(int $hadithId, int $isnadId, string $direction): bool;

    /**
     * Remove a narrator from a hadith's isnad chain and renumber the
     * remaining links so positions stay contiguous.
     */
    public function removeNarratorFromChain(int $hadithId, int $isnadId): void;
}
