<?php

declare(strict_types=1);

namespace App\Domain\People\Services;

use App\Domain\People\DTOs\MuslimListFilters;
use App\Domain\People\Models\IslamicRole;
use App\Domain\People\Models\Muslim;
use App\Domain\People\Repositories\MuslimRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

final class MuslimDirectoryService
{
    public function __construct(private readonly MuslimRepositoryInterface $muslims) {}

    public function list(MuslimListFilters $filters): LengthAwarePaginator
    {
        return $this->muslims->paginate($filters);
    }

    public function profile(string $slug, bool $publishedOnly = true): ?Muslim
    {
        return $this->muslims->findBySlug($slug, $publishedOnly);
    }

    public function search(string $term, int $limit = 20): Collection
    {
        return $this->muslims->search($term, $limit);
    }

    /**
     * Picker results for admin forms. Unpublished people are included on
     * purpose — see MuslimRepositoryInterface::pickerSearch().
     */
    public function picker(string $term, ?int $excludeId = null, int $limit = 20): Collection
    {
        return $this->muslims->pickerSearch($term, $excludeId, $limit);
    }

    /** @return Collection<int, IslamicRole> */
    public function roles(): Collection
    {
        return IslamicRole::query()
            ->select(['id', 'slug', 'name_ar', 'name_en'])
            ->orderBy('id')
            ->get();
    }
}
