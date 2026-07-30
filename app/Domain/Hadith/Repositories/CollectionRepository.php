<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Repositories;

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
            ->withCount(['hadiths' => fn ($query) => $query->where('is_published', true)])
            ->orderByDesc('featured')
            ->orderBy('display_order')
            ->get();
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
