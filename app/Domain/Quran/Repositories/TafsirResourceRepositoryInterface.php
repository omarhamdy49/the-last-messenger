<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Quran\Models\TafsirResource;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Tafsir Resource Repository Interface
 * Data access for tafsir sources (the works, not the verse text).
 */
interface TafsirResourceRepositoryInterface
{
    /**
     * Sources for the admin list, each carrying how many verse tafsirs it
     * holds — the one number that tells an editor whether a source has
     * actually been imported.
     */
    public function paginateWithTafsirCounts(int $perPage): LengthAwarePaginator;

    public function findOrFail(int $id): TafsirResource;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): TafsirResource;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(TafsirResource $resource, array $attributes): TafsirResource;

    public function delete(TafsirResource $resource): void;
}
