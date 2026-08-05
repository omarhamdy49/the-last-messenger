<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Repositories;

use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithChapter;
use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Owns collection, book and chapter reads. One repository rather than three
 * because the three are never read independently — every book read is scoped to
 * a collection and every chapter read to a book.
 */
interface CollectionRepositoryInterface extends RepositoryInterface
{
    /**
     * Published collections for the hub, ordered by `featured` then
     * `display_order`, with a hadith count. Synthetic books are excluded from
     * the count.
     *
     * @return Collection<int, HadithCollection>
     */
    public function listPublishedCollections(): Collection;

    /**
     * Library-wide published totals for the hub header.
     *
     * A dedicated aggregate rather than a sum over listPublishedCollections():
     * these figures describe the whole library, so deriving them from whatever
     * that listing happens to return would start silently under-reporting the
     * day the listing is paginated, filtered or capped. The scope definitions
     * here are the same ones listPublishedCollections() counts per row.
     *
     * @return array{collections: int, hadiths: int, books: int}
     */
    public function publishedLibraryTotals(): array;

    /**
     * A single published collection by `code`, or null. Never returns an
     * unpublished row: unpublished must 404, not render empty.
     */
    public function findPublishedByCode(string $code): ?HadithCollection;

    /**
     * Books of a collection in `sort_order`, with hadith counts. Synthetic books
     * are excluded — they exist only to satisfy the hierarchy.
     *
     * @return Collection<int, HadithBook>
     */
    public function booksFor(int $collectionId, bool $publishedOnly = true): Collection;

    /**
     * The hidden hierarchy placeholder for a bookless collection, if present.
     * It is never subject to public book visibility because callers use it only
     * as a storage anchor for the collection's published hadiths.
     */
    public function syntheticBookFor(int $collectionId): ?HadithBook;

    /**
     * One book of a collection by its string number (`2`, `35b`, `introduction`).
     */
    public function findBook(int $collectionId, string $bookNumber, bool $publishedOnly = true): ?HadithBook;

    /**
     * The books immediately before and after `$book` in `sort_order`, for the
     * reader's prev/next links. Two one-row reads rather than loading the whole
     * book list — Musnad Ahmad has 904 of them.
     *
     * @return array{previous: ?HadithBook, next: ?HadithBook}
     */
    public function adjacentBooks(HadithBook $book, bool $publishedOnly = true): array;

    /**
     * Chapters of a book in `sort_order`. Empty for collections with
     * `has_chapters = false` — that is a 200 with no rows, not a 404.
     *
     * @return Collection<int, HadithChapter>
     */
    public function chaptersFor(int $bookId, bool $publishedOnly = true): Collection;
}
