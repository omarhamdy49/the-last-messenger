<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Repositories;

use App\Domain\Hadith\Models\HadithScholar;
use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ScholarRepositoryInterface extends RepositoryInterface
{
    public function findBySlug(string $slug): ?HadithScholar;

    /**
     * Resolve a scholar by the name embedded in a grade string — "(Al-Albani)",
     * "(Darussalam)" — creating one if absent, so the import never drops an
     * attribution. Matching is on the slugified name because the raw form varies
     * in casing and diacritics across records.
     */
    public function resolveByName(string $name): HadithScholar;

    /**
     * Admin index, with the number of gradings each scholar is cited in.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator;
}
