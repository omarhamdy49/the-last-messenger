<?php

declare(strict_types=1);

namespace App\Domain\Shared\Repositories;

use App\Domain\Shared\Contracts\RepositoryInterface;
use App\Domain\Shared\DTOs\LocationData;
use App\Domain\Shared\Models\Location;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Location Repository Interface
 */
interface LocationRepositoryInterface extends RepositoryInterface
{
    /**
     * Paginated admin list. Filters are allowlisted by the caller.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters, int $perPage = 25): LengthAwarePaginator;

    /**
     * The whole active vocabulary with translations and aliases, cached.
     *
     * Bounded at ~55 rows, so loading all of it is cheaper than any per-row
     * lookup. This is what LocationResolver matches against.
     *
     * @return Collection<int, Location>
     */
    public function vocabulary(): Collection;

    /**
     * Active places whose translated name or alias matches the term.
     *
     * @return Collection<int, Location>
     */
    public function search(string $term, int $limit = 20): Collection;

    /**
     * Every location as a flat, ordered tree for the admin index and selects.
     *
     * @return Collection<int, Location>
     */
    public function tree(): Collection;

    public function findBySlug(string $slug): ?Location;

    /**
     * The location currently owning this alias, if any.
     */
    public function findByAlias(string $alias, ?int $exceptId = null): ?Location;

    public function createWithTranslations(LocationData $data): Location;

    public function updateWithTranslations(int $id, LocationData $data): Location;
}
