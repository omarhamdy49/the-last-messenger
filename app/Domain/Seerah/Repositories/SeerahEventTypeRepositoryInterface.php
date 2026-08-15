<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Repositories;

use App\Domain\Seerah\Models\SeerahEventType;
use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface SeerahEventTypeRepositoryInterface extends RepositoryInterface
{
    /**
     * All active types, ordered for dropdowns (cached).
     *
     * @return Collection<int, SeerahEventType>
     */
    public function allActiveOrdered(): Collection;

    /**
     * Paginate types for the admin index with optional filters (search, status).
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator;

    /**
     * Bulk delete types by id. Returns the number deleted.
     *
     * @param  array<int>  $ids
     */
    public function bulkDelete(array $ids): int;

    /**
     * Toggle the active flag of a type. Returns the new value.
     */
    public function toggleActive(int $id): bool;

    /**
     * Count events linked to a type (used to guard deletion).
     */
    public function countEvents(int $id): int;
}
