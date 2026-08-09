<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\Models\TranslationResource;
use App\Domain\Quran\Repositories\TranslationResourceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Translation editions (resources).
 *
 * The rule this class exists for: AT MOST ONE DEFAULT EDITION PER LANGUAGE.
 * No unique index can express it — "one row per language where is_default = 1"
 * is a partial unique constraint, which MySQL does not have — so the write path
 * has to demote the other editions itself, in the same transaction, or a reader
 * asking for "the default English translation" gets whichever row it happens to
 * see first.
 *
 * Every write flushes the Quran content cache AFTER the transaction commits.
 * Flushing inside would open a window where a reader repopulates the cache from
 * a snapshot that is about to be rolled back.
 */
class TranslationResourceService
{
    public function __construct(
        private readonly TranslationResourceRepositoryInterface $repository
    ) {}

    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        return $this->repository->paginateWithTranslationCounts($perPage);
    }

    public function findOrFail(int $id): TranslationResource
    {
        return $this->repository->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): TranslationResource
    {
        $resource = DB::transaction(function () use ($attributes): TranslationResource {
            $resource = $this->repository->create($attributes);
            $this->syncDefault($resource);

            return $resource;
        });

        QuranCacheKeys::flushContent();

        return $resource;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(TranslationResource $resource, array $attributes): TranslationResource
    {
        $resource = DB::transaction(function () use ($resource, $attributes): TranslationResource {
            $resource = $this->repository->update($resource, $attributes);
            $this->syncDefault($resource);

            return $resource;
        });

        QuranCacheKeys::flushContent();

        return $resource;
    }

    /**
     * Translation rows keep their text but lose the resource link (nullOnDelete).
     */
    public function delete(TranslationResource $resource): void
    {
        $this->repository->delete($resource);

        QuranCacheKeys::flushContent();
    }

    /**
     * Enforce a single default edition per language.
     */
    private function syncDefault(TranslationResource $resource): void
    {
        if (! $resource->is_default) {
            return;
        }

        $this->repository->clearDefaultForLanguage($resource->language, $resource->id);
    }
}
