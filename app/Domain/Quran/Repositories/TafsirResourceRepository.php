<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Quran\Models\TafsirResource;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Tafsir Resource Repository
 * Uncached: source metadata is read by the admin list only, and an editor must
 * see an edit the moment it is saved. The public reader reads sources through
 * TafseerRepository::getActiveResources(), which does its own caching.
 */
class TafsirResourceRepository implements TafsirResourceRepositoryInterface
{
    public function paginateWithTafsirCounts(int $perPage): LengthAwarePaginator
    {
        return TafsirResource::query()
            ->withCount('tafsirs')
            ->orderBy('display_order')
            ->orderBy('id')
            ->paginate($perPage);
    }

    public function findOrFail(int $id): TafsirResource
    {
        return TafsirResource::findOrFail($id);
    }

    public function create(array $attributes): TafsirResource
    {
        return TafsirResource::create($attributes);
    }

    public function update(TafsirResource $resource, array $attributes): TafsirResource
    {
        $resource->update($attributes);

        return $resource;
    }

    public function delete(TafsirResource $resource): void
    {
        $resource->delete();
    }
}
