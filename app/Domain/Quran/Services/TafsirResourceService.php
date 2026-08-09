<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\Models\TafsirResource;
use App\Domain\Quran\Repositories\TafsirResourceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Tafsir sources (resources).
 *
 * Unlike translation editions there is no "default source" to keep unique, so a
 * write is a single statement and needs no transaction. What every write does
 * need is a content-cache flush: the reader's source selector is served from
 * cache and would otherwise keep offering a renamed or deactivated source.
 */
class TafsirResourceService
{
    public function __construct(
        private readonly TafsirResourceRepositoryInterface $repository
    ) {}

    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        return $this->repository->paginateWithTafsirCounts($perPage);
    }

    public function findOrFail(int $id): TafsirResource
    {
        return $this->repository->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): TafsirResource
    {
        $resource = $this->repository->create($attributes);

        QuranCacheKeys::flushContent();

        return $resource;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(TafsirResource $resource, array $attributes): TafsirResource
    {
        $resource = $this->repository->update($resource, $attributes);

        QuranCacheKeys::flushContent();

        return $resource;
    }

    /**
     * Tafsir rows keep their text but lose the resource link (nullOnDelete).
     */
    public function delete(TafsirResource $resource): void
    {
        $this->repository->delete($resource);

        QuranCacheKeys::flushContent();
    }
}
