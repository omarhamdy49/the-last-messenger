<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Repositories;

use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class BookRepository extends BaseRepository implements BookRepositoryInterface
{
    protected function getModel(): Model
    {
        return new HadithBook;
    }

    public function getByCollection(int $collectionId): Collection
    {
        return $this->model
            ->select(['id', 'collection_id', 'title', 'book_number'])
            ->where('collection_id', $collectionId)
            ->withCount('hadiths')
            ->orderBy('id')
            ->get();
    }

    public function getBySlug(int $collectionId, string $slug, string $locale = 'ar'): ?HadithBook
    {
        return $this->model
            ->where('collection_id', $collectionId)
            ->where('book_number', $slug)
            ->first();
    }

    public function getWithChapters(int $id): ?HadithBook
    {
        return $this->model->with(['chapters' => fn ($chapters) => $chapters->ordered()])->find($id);
    }

    public function getWithHadiths(int $id): ?HadithBook
    {
        return $this->model->with(['hadiths.translations'])->find($id);
    }

    public function getWithFullRelations(int $id): ?HadithBook
    {
        return $this->model->with(['collection', 'chapters', 'hadiths.translations'])->find($id);
    }

    public function paginateWithFilters(
        int $perPage = 15,
        string $search = '',
        string $sortBy = 'id',
        array $filters = []
    ): LengthAwarePaginator {
        $query = $this->model->newQuery();

        if ($search !== '') {
            $query->where(fn ($books) => $books
                ->where('title->ar', 'like', "%{$search}%")
                ->orWhere('title->en', 'like', "%{$search}%"));
        }

        if (! empty($filters['collection_id'])) {
            $query->where('collection_id', $filters['collection_id']);
        }

        return $query
            ->select(['id', 'collection_id', 'title', 'book_number', 'created_at'])
            ->with('collection:id,name,abbreviation')
            ->withCount('hadiths')
            ->orderBy(in_array($sortBy, ['id', 'book_number', 'created_at'], true) ? $sortBy : 'id')
            ->paginate($perPage);
    }

    public function countHadiths(int $bookId): int
    {
        return $this->model->find($bookId)?->hadiths()->count() ?? 0;
    }

    public function getByCollectionAndNumber(int $collectionId, int $bookNumber): ?HadithBook
    {
        return $this->model
            ->where('collection_id', $collectionId)
            ->where('book_number', (string) $bookNumber)
            ->first();
    }

    public function getAllForDropdown(?int $collectionId = null): Collection
    {
        return $this->model
            ->select(['id', 'title', 'collection_id'])
            ->when($collectionId !== null, fn ($books) => $books->where('collection_id', $collectionId))
            ->orderBy('id')
            ->get();
    }

    public function paginateByCollection(
        int $collectionId,
        int $perPage = 15,
        string $sortBy = 'book_number',
        string $sortDirection = 'asc'
    ): LengthAwarePaginator {
        return $this->model
            ->where('collection_id', $collectionId)
            ->withCount(['hadiths', 'chapters'])
            ->orderBy($sortBy, $sortDirection)
            ->paginate($perPage);
    }

    public function getPublicByCollectionAndNumber(int $collectionId, int $bookNumber): ?HadithBook
    {
        return $this->model
            ->select(['id', 'collection_id', 'book_number', 'title', 'description', 'created_at', 'updated_at'])
            ->withCount(['hadiths as hadith_count', 'chapters as chapters_count'])
            ->with('collection:id,name')
            ->where('collection_id', $collectionId)
            ->where('book_number', (string) $bookNumber)
            ->first();
    }

    public function getAdjacentByNumber(int $collectionId, int $bookNumber): array
    {
        $columns = ['id', 'collection_id', 'book_number', 'title'];

        return [
            'previous' => $this->model->select($columns)->where('collection_id', $collectionId)
                ->where('book_number', '<', (string) $bookNumber)->orderByDesc('book_number')->first(),
            'next' => $this->model->select($columns)->where('collection_id', $collectionId)
                ->where('book_number', '>', (string) $bookNumber)->orderBy('book_number')->first(),
        ];
    }
}
