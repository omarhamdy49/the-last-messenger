<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Repositories;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class CollectionRepository extends BaseRepository implements CollectionRepositoryInterface
{
    private const CACHE_TTL = 3600;

    protected function getModel(): Model
    {
        return new HadithCollection;
    }

    public function all(array $columns = ['*'], array $relations = []): Collection
    {
        return Cache::remember('hadith_collections_all', self::CACHE_TTL, fn () => $this->model
            ->with($relations)
            ->orderBy('id')
            ->get($columns));
    }

    public function getBySlug(string $slug, string $locale = 'ar'): ?HadithCollection
    {
        return Cache::remember("hadith_collection_slug_{$slug}", self::CACHE_TTL, fn () => $this->model
            ->where('slug', $slug)
            ->first());
    }

    public function getByAbbreviation(string $abbreviation): ?HadithCollection
    {
        return $this->model->where('abbreviation', $abbreviation)->first();
    }

    public function getAllWithBooksCount(): Collection
    {
        return $this->model
            ->select(['id', 'code', 'slug', 'name', 'description', 'abbreviation'])
            ->withCount('books')
            ->orderBy('id')
            ->get();
    }

    public function getWithBooks(int $id): ?HadithCollection
    {
        return $this->model
            ->with(['books' => fn ($books) => $books
                ->select(['id', 'collection_id', 'title', 'book_number'])
                ->orderBy('id')])
            ->find($id);
    }

    public function search(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where(fn ($builder) => $builder
                ->where('name->ar', 'like', "%{$query}%")
                ->orWhere('name->en', 'like', "%{$query}%")
                ->orWhere('abbreviation', 'like', "%{$query}%"))
            ->paginate($perPage);
    }

    public function getWithStatistics(): Collection
    {
        return $this->model->withCount('books')
            ->addSelect([
                'hadiths_count' => Hadith::query()
                    ->selectRaw('COUNT(*)')
                    ->join('hadith_chapters', 'hadith_chapters.id', '=', 'hadith_hadiths.chapter_id')
                    ->join('hadith_books', 'hadith_books.id', '=', 'hadith_chapters.book_id')
                    ->whereColumn('hadith_books.collection_id', 'hadith_collections.id'),
            ])
            ->get();
    }

    public function getAllForDropdown(): Collection
    {
        return $this->model->select(['id', 'name'])->orderBy('id')->get();
    }

    public function reorder(array $items): void
    {
        foreach ($items as $item) {
            $this->model->whereKey($item['id'])->update(['display_order' => $item['order']]);
        }

        $this->clearCache();
    }

    public function toggleField(int $id, string $field): HadithCollection
    {
        /** @var HadithCollection $collection */
        $collection = $this->findOrFail($id);
        $collection->update([$field => ! $collection->{$field}]);
        $this->clearCache();

        return $collection->fresh();
    }

    public function paginateWithFilters(
        int $perPage = 15,
        string $search = '',
        string $sortBy = 'name',
        array $filters = []
    ): LengthAwarePaginator {
        $query = $this->model->newQuery();

        if ($search !== '') {
            $query->where(fn ($builder) => $builder
                ->where('name->ar', 'like', "%{$search}%")
                ->orWhere('name->en', 'like', "%{$search}%")
                ->orWhere('abbreviation', 'like', "%{$search}%"));
        }

        foreach (['active', 'featured'] as $field) {
            if (array_key_exists($field, $filters) && $filters[$field] !== '' && $filters[$field] !== null) {
                $query->where($field, (bool) $filters[$field]);
            }
        }

        return $query->withCount('books')
            ->orderByDesc('featured')
            ->orderBy('display_order')
            ->orderBy(in_array($sortBy, ['id', 'code', 'slug', 'created_at'], true) ? $sortBy : 'id', $filters['sort_direction'] ?? 'asc')
            ->paginate($perPage);
    }
}
