<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Quran\Models\TranslationResource;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Translation Resource Repository Interface
 * Data access for translation editions (the works, not the verse text).
 */
interface TranslationResourceRepositoryInterface
{
    /**
     * Editions for the admin list, each carrying how many verse translations
     * it holds — the one number that tells an editor whether an edition has
     * actually been imported.
     */
    public function paginateWithTranslationCounts(int $perPage): LengthAwarePaginator;

    public function findOrFail(int $id): TranslationResource;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): TranslationResource;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(TranslationResource $resource, array $attributes): TranslationResource;

    public function delete(TranslationResource $resource): void;

    /**
     * Clear the default flag on every other edition of this language.
     *
     * @return int Rows affected
     */
    public function clearDefaultForLanguage(string $language, int $exceptId): int;
}
