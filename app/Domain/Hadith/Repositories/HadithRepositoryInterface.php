<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Repositories;

use App\Domain\Hadith\Models\Hadith;
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
     * @param array<string, mixed> $filters
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
}
