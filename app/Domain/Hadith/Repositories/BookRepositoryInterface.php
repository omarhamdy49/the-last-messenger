<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Repositories;

use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Hadith Book Repository Interface
 *
 * Contract for Hadith Book data access operations.
 */
interface BookRepositoryInterface extends RepositoryInterface
{
    /**
     * Get books by collection
     */
    public function getByCollection(int $collectionId): Collection;

    /**
     * Get book by slug within collection
     */
    public function getBySlug(int $collectionId, string $slug, string $locale = 'ar'): ?HadithBook;

    /**
     * Get book with chapters
     */
    public function getWithChapters(int $id): ?HadithBook;

    /**
     * Get book with hadiths
     */
    public function getWithHadiths(int $id): ?HadithBook;

    /**
     * Get book with all relations
     */
    public function getWithFullRelations(int $id): ?HadithBook;

    /**
     * Get paginated books with filters
     *
     * @param array<string, mixed> $filters
     */
    public function paginateWithFilters(
        int $perPage = 15,
        string $search = '',
        string $sortBy = 'id',
        array $filters = []
    ): LengthAwarePaginator;

    /**
     * Count hadiths in book
     */
    public function countHadiths(int $bookId): int;

    /**
     * Get book by collection and book number
     */
    public function getByCollectionAndNumber(int $collectionId, int $bookNumber): ?HadithBook;

    /**
     * Paginate books by collection
     */
    public function paginateByCollection(
        int $collectionId,
        int $perPage = 15,
        string $sortBy = 'book_number',
        string $sortDirection = 'asc'
    ): LengthAwarePaginator;
}
