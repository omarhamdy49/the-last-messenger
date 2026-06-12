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
     * @param  array<string, mixed>  $filters
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

    /**
     * Get a lightweight list of books for use in form dropdowns.
     *
     * When $collectionId is provided only that collection's books are returned.
     * Returns id, title_ar, title_en, collection_id ordered by title_ar.
     */
    public function getAllForDropdown(?int $collectionId = null): Collection;

    /**
     * Get a book by collection + number with the hadith/chapter counts the
     * public chapter page header needs (aliased so BookDTO reads them without
     * extra count queries).
     */
    public function getPublicByCollectionAndNumber(int $collectionId, int $bookNumber): ?HadithBook;

    /**
     * Get the adjacent books (by book_number) within a collection for
     * previous/next chapter navigation.
     *
     * @return array{previous: ?HadithBook, next: ?HadithBook}
     */
    public function getAdjacentByNumber(int $collectionId, int $bookNumber): array;
}
