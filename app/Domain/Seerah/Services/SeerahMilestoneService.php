<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Services;

use App\Domain\Seerah\Models\SeerahMilestone;
use App\Domain\Seerah\Repositories\SeerahMilestoneRepositoryInterface;
use App\Domain\Shared\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class SeerahMilestoneService extends BaseService
{
    public function __construct(
        protected SeerahMilestoneRepositoryInterface $milestoneRepository,
    ) {
        $this->repository = $milestoneRepository;
    }

    /**
     * Active milestones ordered for dropdowns / public timeline.
     *
     * @return Collection<int, SeerahMilestone>
     */
    public function getForDropdown(): Collection
    {
        return $this->milestoneRepository->allActiveOrdered();
    }

    /**
     * Active milestones with their published events (chronological) for the public
     * hero-journey timeline.
     *
     * @return Collection<int, SeerahMilestone>
     */
    public function getJourney(): Collection
    {
        return $this->milestoneRepository->getActiveWithPublishedEvents();
    }

    /**
     * Paginate milestones for the admin index.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->milestoneRepository->paginateWithFilters($filters, $perPage);
    }

    /**
     * Toggle the active flag. Returns the new value.
     */
    public function toggleActive(int $id): bool
    {
        return $this->milestoneRepository->toggleActive($id);
    }

    /**
     * Count events linked to a milestone (for the show page / warnings).
     */
    public function countEvents(int $id): int
    {
        return $this->milestoneRepository->countEvents($id);
    }

    /**
     * Bulk delete milestones. Linked events are unlinked (FK null-on-delete).
     *
     * @param  array<int>  $ids
     */
    public function bulkDelete(array $ids): int
    {
        return $this->milestoneRepository->bulkDelete($ids);
    }
}
