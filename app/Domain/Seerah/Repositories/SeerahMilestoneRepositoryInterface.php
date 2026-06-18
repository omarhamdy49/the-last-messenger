<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Repositories;

use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface SeerahMilestoneRepositoryInterface extends RepositoryInterface
{
    /**
     * All active milestones, ordered, with translations (cached) — for dropdowns
     * and the public timeline.
     *
     * @return Collection<int, \App\Domain\Seerah\Models\SeerahMilestone>
     */
    public function allActiveOrdered(): Collection;

    /**
     * Paginate milestones for the admin index with optional filters (search, status).
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator;

    /**
     * Bulk delete milestones by id. Returns the number deleted.
     *
     * @param  array<int>  $ids
     */
    public function bulkDelete(array $ids): int;

    /**
     * Toggle the active flag of a milestone. Returns the new value.
     */
    public function toggleActive(int $id): bool;

    /**
     * Count events linked to a milestone.
     */
    public function countEvents(int $id): int;
}
