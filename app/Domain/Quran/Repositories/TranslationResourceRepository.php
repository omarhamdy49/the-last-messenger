<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Quran\Models\TranslationResource;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Translation Resource Repository
 * Uncached: edition metadata is read by the admin list only, and an editor must
 * see an edit the moment it is saved. The public reader reads editions through
 * VerseRepository, which does its own caching.
 */
class TranslationResourceRepository implements TranslationResourceRepositoryInterface
{
    public function paginateWithTranslationCounts(int $perPage): LengthAwarePaginator
    {
        return TranslationResource::query()
            ->withCount('translations')
            ->orderBy('language')
            ->orderBy('display_order')
            ->orderBy('id')
            ->paginate($perPage);
    }

    public function findOrFail(int $id): TranslationResource
    {
        return TranslationResource::findOrFail($id);
    }

    public function create(array $attributes): TranslationResource
    {
        return TranslationResource::create($attributes);
    }

    public function update(TranslationResource $resource, array $attributes): TranslationResource
    {
        $resource->update($attributes);

        return $resource;
    }

    public function delete(TranslationResource $resource): void
    {
        $resource->delete();
    }

    public function clearDefaultForLanguage(string $language, int $exceptId): int
    {
        return TranslationResource::query()
            ->where('language', $language)
            ->where('id', '!=', $exceptId)
            ->update(['is_default' => false]);
    }
}
