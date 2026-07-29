<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Repositories;

use App\Domain\Hadith\Models\HadithScholar;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class ScholarRepository extends BaseRepository implements ScholarRepositoryInterface
{
    protected function getModel(): Model
    {
        return new HadithScholar;
    }

    public function getByName(string $name, string $locale = 'ar'): ?HadithScholar
    {
        return $this->model->where("name->{$locale}", $name)->first();
    }

    public function getAllByEra(): Collection
    {
        return $this->model
            ->select(['id', 'name', 'kunya', 'school', 'death_year_hijri', 'region'])
            ->orderBy('death_year_hijri')
            ->get();
    }

    public function search(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('name->ar', 'like', "%{$query}%")
            ->orWhere('name->en', 'like', "%{$query}%")
            ->orWhere('full_name->ar', 'like', "%{$query}%")
            ->orWhere('full_name->en', 'like', "%{$query}%")
            ->paginate($perPage);
    }

    public function getWithGradingCount(): Collection
    {
        return $this->model
            ->select(['id', 'name', 'kunya', 'school', 'death_year_hijri'])
            ->withCount('gradings')
            ->orderByDesc('gradings_count')
            ->get();
    }
}
