<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Services;

use App\Domain\Seerah\Exceptions\SeerahEventTypeInUseException;
use App\Domain\Seerah\Models\SeerahEventType;
use App\Domain\Seerah\Repositories\SeerahEventTypeRepositoryInterface;
use App\Domain\Shared\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class SeerahEventTypeService extends BaseService
{
    public function __construct(
        protected SeerahEventTypeRepositoryInterface $typeRepository,
    ) {
        $this->repository = $typeRepository;
    }

    /**
     * Active types ordered for dropdowns.
     *
     * @return Collection<int, SeerahEventType>
     */
    public function getForDropdown(): Collection
    {
        return $this->typeRepository->allActiveOrdered();
    }

    /**
     * Paginate types for the admin index.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->typeRepository->paginateWithFilters($filters, $perPage);
    }

    /**
     * Toggle the active flag. Returns the new value.
     */
    public function toggleActive(int $id): bool
    {
        return $this->typeRepository->toggleActive($id);
    }

    /**
     * Delete a single type, guarding against deleting one still in use.
     *
     * @throws SeerahEventTypeInUseException
     */
    public function deleteType(int $id): bool
    {
        $count = $this->typeRepository->countEvents($id);

        if ($count > 0) {
            throw SeerahEventTypeInUseException::withCount($count);
        }

        return $this->typeRepository->delete($id);
    }

    /**
     * Bulk delete types, skipping any that are still in use.
     *
     * @param  array<int>  $ids
     * @return array{deleted: int, skipped: int}
     */
    public function bulkDelete(array $ids): array
    {
        $deletable = [];
        $skipped = 0;

        foreach ($ids as $id) {
            if ($this->typeRepository->countEvents((int) $id) > 0) {
                $skipped++;

                continue;
            }
            $deletable[] = (int) $id;
        }

        $deleted = $deletable === [] ? 0 : $this->typeRepository->bulkDelete($deletable);

        return ['deleted' => $deleted, 'skipped' => $skipped];
    }
}
