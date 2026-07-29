<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Repositories;

use App\Domain\Hadith\Models\HadithNarrator;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class NarratorRepository extends BaseRepository implements NarratorRepositoryInterface
{
    protected function getModel(): Model
    {
        return new HadithNarrator;
    }

    public function getByName(string $name, string $locale = 'ar'): ?HadithNarrator
    {
        return $this->model
            ->where("name->{$locale}", $name)
            ->orWhereHas('sahabi.translations', fn ($translation) => $translation
                ->where('locale', $locale)
                ->where('name', $name))
            ->first();
    }

    public function getAllWithHadithCount(): Collection
    {
        return $this->model
            ->select(['id', 'sahaba_id', 'name', 'kunya', 'death_year_hijri', 'reliability_grade'])
            ->with('sahabi.translations')
            ->withCount('hadiths')
            ->orderByDesc('hadiths_count')
            ->get();
    }

    public function search(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()->search($query)->paginate($perPage);
    }

    public function paginateWithFilters(
        int $perPage = 15,
        string $search = '',
        string $sortBy = 'id',
        array $filters = []
    ): LengthAwarePaginator {
        $query = $this->model->with('sahabi.translations');

        foreach (['reliability_grade', 'region', 'school'] as $field) {
            if (! empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        if ($search !== '') {
            $query->search($search);
        }

        return $query
            ->orderBy(in_array($sortBy, ['id', 'death_year_hijri', 'reliability_grade', 'region', 'school'], true) ? $sortBy : 'id', $filters['sort_direction'] ?? 'asc')
            ->paginate($perPage);
    }

    public function getTopByHadithCount(int $limit = 10): Collection
    {
        return $this->model
            ->select(['id', 'sahaba_id', 'name', 'kunya', 'death_year_hijri', 'reliability_grade'])
            ->with('sahabi.translations')
            ->withCount('hadiths')
            ->orderByDesc('hadiths_count')
            ->limit($limit)
            ->get();
    }
}
