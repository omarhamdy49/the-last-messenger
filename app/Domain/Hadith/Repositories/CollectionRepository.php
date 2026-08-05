<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Repositories;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithChapter;
use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class CollectionRepository extends BaseRepository implements CollectionRepositoryInterface
{
    protected function getModel(): Model
    {
        return new HadithCollection;
    }

    public function listPublishedCollections(): Collection
    {
        return HadithCollection::query()
            ->select([
                'id', 'code', 'name', 'author', 'short_intro',
                'has_books', 'has_chapters', 'has_volumes', 'featured', 'display_order',
            ])
            ->where('is_published', true)
            // withCount, not a stored counter: 17 rows and the counter would
            // drift the moment an editor publishes or unpublishes a hadith.
            // Both counts in one query — the hub card shows them side by side.
            // Synthetic books are excluded to match booksFor().
            ->withCount([
                'hadiths' => fn ($query) => $query->where('is_published', true),
                'books' => fn ($query) => $query->where('is_published', true)->where('is_synthetic', false),
            ])
            ->orderByDesc('featured')
            ->orderBy('display_order')
            ->get();
    }

    public function publishedLibraryTotals(): array
    {
        // A factory, not a shared builder: each total needs its own instance,
        // and handing the same builder to two parent queries invites the kind
        // of binding reuse that only shows up under a specific call order.
        $publishedCollectionIds = fn () => HadithCollection::query()
            ->select('id')
            ->where('is_published', true);

        return [
            'collections' => HadithCollection::query()
                ->where('is_published', true)
                ->count(),

            // Scoped through the collection, not just `is_published` on the row:
            // a published hadith under an unpublished collection is unreachable
            // to readers and must not be advertised as part of the library.
            'hadiths' => Hadith::query()
                ->where('is_published', true)
                ->whereIn('collection_id', $publishedCollectionIds())
                ->count(),

            // Synthetic books are hierarchy plumbing for bookless collections;
            // excluded here exactly as booksFor() and the per-row count exclude
            // them, so the total can never exceed the sum of the parts.
            'books' => HadithBook::query()
                ->where('is_published', true)
                ->where('is_synthetic', false)
                ->whereIn('collection_id', $publishedCollectionIds())
                ->count(),
        ];
    }

    public function findPublishedByCode(string $code): ?HadithCollection
    {
        return HadithCollection::query()
            ->select([
                'id', 'code', 'name', 'author', 'short_intro', 'about',
                'has_books', 'has_chapters', 'has_volumes', 'reference_template',
                'featured', 'display_order',
            ])
            ->where('code', $code)
            ->where('is_published', true)
            ->withCount(['hadiths' => fn ($query) => $query->where('is_published', true)])
            ->first();
    }

    public function booksFor(int $collectionId, bool $publishedOnly = true): Collection
    {
        return HadithBook::query()
            ->select([
                'id', 'collection_id', 'book_number', 'sort_order', 'title',
                'first_hadith_number', 'last_hadith_number', 'is_published',
            ])
            ->where('collection_id', $collectionId)
            // Synthetic books are the hierarchy's plumbing, never content.
            ->where('is_synthetic', false)
            ->when($publishedOnly, fn ($query) => $query->where('is_published', true))
            ->withCount(['hadiths' => fn ($query) => $query->where('is_published', true)])
            ->orderBy('sort_order')
            ->get();
    }

    public function syntheticBookFor(int $collectionId): ?HadithBook
    {
        return HadithBook::query()
            ->select([
                'id', 'collection_id', 'book_number', 'sort_order', 'title', 'intro',
                'first_hadith_number', 'last_hadith_number', 'is_published',
            ])
            ->where('collection_id', $collectionId)
            ->where('is_synthetic', true)
            ->orderBy('sort_order')
            ->first();
    }

    public function findBook(int $collectionId, string $bookNumber, bool $publishedOnly = true): ?HadithBook
    {
        return HadithBook::query()
            ->select([
                'id', 'collection_id', 'book_number', 'sort_order', 'title', 'intro',
                'first_hadith_number', 'last_hadith_number', 'is_published',
            ])
            ->where('collection_id', $collectionId)
            ->where('book_number', $bookNumber)
            ->where('is_synthetic', false)
            ->when($publishedOnly, fn ($query) => $query->where('is_published', true))
            ->first();
    }

    /** @return array{previous: ?HadithBook, next: ?HadithBook} */
    public function adjacentBooks(HadithBook $book, bool $publishedOnly = true): array
    {
        $neighbour = fn (string $operator, string $direction): ?HadithBook => HadithBook::query()
            ->select(['id', 'collection_id', 'book_number', 'sort_order', 'title'])
            ->where('collection_id', $book->collection_id)
            ->where('is_synthetic', false)
            ->when($publishedOnly, fn ($query) => $query->where('is_published', true))
            ->where('sort_order', $operator, $book->sort_order)
            ->orderBy('sort_order', $direction)
            ->first();

        return [
            'previous' => $neighbour('<', 'desc'),
            'next' => $neighbour('>', 'asc'),
        ];
    }

    public function chaptersFor(int $bookId, bool $publishedOnly = true): Collection
    {
        return HadithChapter::query()
            ->select(['id', 'book_id', 'chapter_number', 'sort_order', 'title', 'intro', 'ending', 'is_published'])
            ->where('book_id', $bookId)
            ->when($publishedOnly, fn ($query) => $query->where('is_published', true))
            ->orderBy('sort_order')
            ->get();
    }
}
