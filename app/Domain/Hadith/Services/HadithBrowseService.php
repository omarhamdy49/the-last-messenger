<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Services;

use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithChapter;
use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Hadith\Repositories\CollectionRepositoryInterface;
use App\Domain\Hadith\Repositories\HadithRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

/**
 * The read side of the hub → collection → book → chapter journey.
 *
 * Kept apart from HadithQueryService, which answers "one hadith by identity".
 * Browsing asks a different question — "what is in here, in what order" — and
 * mixing the two produced a service that eager-loaded detail relations for list
 * pages that never render them.
 */
final class HadithBrowseService
{
    /** Matches the reading experience of the source's own book pages. */
    private const HADITHS_PER_PAGE = 50;

    public function __construct(
        private readonly CollectionRepositoryInterface $collections,
        private readonly HadithRepositoryInterface $hadiths,
    ) {}

    /**
     * @return Collection<int, HadithCollection>
     */
    public function hub(): Collection
    {
        return $this->collections->listPublishedCollections();
    }

    /**
     * What the published library holds, independent of what the hub renders.
     *
     * @return array{collections: int, hadiths: int, books: int}
     */
    public function libraryTotals(): array
    {
        return $this->collections->publishedLibraryTotals();
    }

    public function findCollection(string $code): ?HadithCollection
    {
        return $this->collections->findPublishedByCode($code);
    }

    /**
     * @return Collection<int, HadithBook>
     */
    public function booksFor(HadithCollection $collection): Collection
    {
        return $this->collections->booksFor($collection->id);
    }

    /**
     * Published hadiths stored under a bookless collection's hidden placeholder.
     * The placeholder remains absent from book listings and URLs; it is only the
     * database anchor required by hadith_hadiths.book_id.
     *
     * @return array{
     *     paginator: LengthAwarePaginator,
     *     groups: SupportCollection<int, array{chapter: mixed, hadiths: SupportCollection}>
     * }|null
     */
    public function booklessContents(HadithCollection $collection): ?array
    {
        $syntheticBook = $this->collections->syntheticBookFor($collection->id);

        return $syntheticBook === null ? null : $this->bookContents($syntheticBook);
    }

    public function findBook(HadithCollection $collection, string $bookNumber): ?HadithBook
    {
        return $this->collections->findBook($collection->id, $bookNumber);
    }

    /**
     * The previous/next book, for the reader's book-to-book navigation.
     *
     * @return array{previous: ?HadithBook, next: ?HadithBook}
     */
    public function adjacentBooks(HadithBook $book): array
    {
        return $this->collections->adjacentBooks($book);
    }

    /**
     * A book's hadiths in reading order, grouped under their chapter.
     *
     * Chapters are fetched once and joined in memory rather than eager-loaded
     * per hadith, so the query count is the same for 20 hadiths as for 100.
     *
     * @return array{
     *     paginator: LengthAwarePaginator,
     *     groups: SupportCollection<int, array{chapter: mixed, hadiths: SupportCollection}>
     * }
     */
    public function bookContents(HadithBook $book, ?int $perPage = null): array
    {
        $paginator = $this->hadiths->paginateForBook($book->id, $perPage ?? self::HADITHS_PER_PAGE);
        $chapters = $this->collections->chaptersFor($book->id)->keyBy('id');

        $groups = collect($paginator->items())
            // Chapterless collections put everything in one unlabelled group
            // rather than rendering a heading with nothing to name it.
            ->groupBy(fn ($hadith): string => (string) ($hadith->chapter_id ?? 'none'))
            ->map(fn ($hadiths, $key): array => [
                'chapter' => $key === 'none' ? null : $chapters->get((int) $key),
                'hadiths' => $hadiths,
            ])
            ->values();

        return ['paginator' => $paginator, 'groups' => $groups];
    }

    /**
     * A book's chapters in sort order. Empty for a collection with
     * has_chapters = false, which is a 200 with no rows rather than a 404.
     *
     * @return Collection<int, HadithChapter>
     */
    public function chaptersFor(HadithBook $book): Collection
    {
        return $this->collections->chaptersFor($book->id);
    }

    /**
     * A book's hadiths as a plain paginator, for the API, which renders each
     * record itself rather than grouping them under chapter headings.
     */
    public function paginateBookHadiths(HadithBook $book, int $perPage): LengthAwarePaginator
    {
        return $this->hadiths->paginateForBook($book->id, $perPage);
    }

    public function perPage(): int
    {
        return self::HADITHS_PER_PAGE;
    }
}
