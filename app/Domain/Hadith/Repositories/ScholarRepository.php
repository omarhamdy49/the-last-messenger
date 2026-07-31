<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Repositories;

use App\Domain\Hadith\Models\HadithScholar;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ScholarRepository extends BaseRepository implements ScholarRepositoryInterface
{
    protected function getModel(): Model
    {
        return new HadithScholar;
    }

    public function findBySlug(string $slug): ?HadithScholar
    {
        return HadithScholar::query()
            ->select(['id', 'slug', 'name', 'full_name', 'kunya', 'biography',
                'birth_year_hijri', 'death_year_hijri', 'generation', 'reliability',
                'is_companion', 'sahaba_id'])
            ->where('slug', $slug)
            ->first();
    }

    public function findPublicNarratorBySlug(string $slug): ?HadithScholar
    {
        return HadithScholar::query()
            ->select(['id', 'slug', 'name', 'full_name', 'kunya', 'biography',
                'birth_year_hijri', 'death_year_hijri', 'generation', 'reliability',
                'is_companion', 'sahaba_id'])
            ->where('slug', $slug)
            ->whereHas('primaryHadiths', fn (Builder $query) => $query
                ->where('is_published', true)
                ->whereHas('collection', fn (Builder $collection) => $collection->where('is_published', true)))
            ->first();
    }

    public function resolveByName(string $name): HadithScholar
    {
        $slug = Str::slug($name);

        // Slug, not name: the same scholar arrives as "Al-Albani", "al-Albani"
        // and "Al Albani" across records, and three rows for one man would make
        // every per-scholar count wrong.
        return HadithScholar::query()->firstOrCreate(
            ['slug' => $slug],
            ['name' => ['en' => $name]],
        );
    }

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $search = $filters['search'] ?? null;

        return HadithScholar::query()
            ->select(['id', 'slug', 'name', 'death_year_hijri', 'generation', 'reliability', 'is_companion'])
            ->withCount('gradings')
            ->when($search !== null, fn (Builder $query) => $query
                ->where('slug', 'like', Str::slug((string) $search).'%'))
            ->when(isset($filters['is_companion']), fn (Builder $query) => $query
                ->where('is_companion', (bool) $filters['is_companion']))
            ->orderBy('slug')
            ->paginate($perPage);
    }
}
