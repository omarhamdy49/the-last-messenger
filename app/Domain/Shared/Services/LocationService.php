<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Shared\DTOs\LocationData;
use App\Domain\Shared\Exceptions\BusinessRuleViolationException;
use App\Domain\Shared\Models\Location;
use App\Domain\Shared\Repositories\LocationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Location CRUD orchestration.
 *
 * Constitution I: injects the interface, never the concrete repository, and
 * owns no database access of its own — the transaction lives in the repository.
 */
class LocationService
{
    public function __construct(
        private readonly LocationRepositoryInterface $locations,
        private readonly LocationUsageRegistry $usage,
    ) {}

    /**
     * @param  array<string, mixed>  $filters  Already allowlisted by the controller
     */
    public function paginate(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        return $this->locations->paginateFiltered($filters, $perPage);
    }

    /** @return Collection<int, Location> */
    public function tree(): Collection
    {
        return $this->locations->tree();
    }

    /** @return Collection<int, Location> */
    public function search(string $term, int $limit = 20): Collection
    {
        return $this->locations->search($term, $limit);
    }

    public function create(LocationData $data): Location
    {
        $this->guardAliasesAreFree($data, null);

        return $this->locations->createWithTranslations($data);
    }

    public function update(int $id, LocationData $data): Location
    {
        $this->guardAliasesAreFree($data, $id);

        return $this->locations->updateWithTranslations($id, $data);
    }

    /**
     * Refuse to delete a location that content still points at, and say how much
     * content that is — an editor needs an actionable number, not a bare refusal.
     *
     * @throws BusinessRuleViolationException
     */
    public function delete(int $id): bool
    {
        $counts = $this->usage->countReferences($id);

        if ($counts !== []) {
            throw BusinessRuleViolationException::forRule(
                'location.in_use',
                __('locations.errors.in_use'),
                ['location_id' => $id, 'references' => $counts]
            );
        }

        $deleted = $this->locations->delete($id);
        $this->locations->clearCache();

        return $deleted;
    }

    /**
     * @return array<string, int> table => count, empty when unreferenced
     */
    public function referenceCounts(int $id): array
    {
        return $this->usage->countReferences($id);
    }

    public function toggleActive(int $id): Location
    {
        /** @var Location $location */
        $location = $this->locations->findOrFail($id);

        $location->is_active = ! $location->is_active;
        $location->save();

        $this->locations->clearCache();

        return $location;
    }

    /**
     * An alias must identify exactly one place. Two places claiming the same
     * spelling would make resolution non-deterministic, which is the failure the
     * whole alias mechanism exists to prevent.
     *
     * @throws BusinessRuleViolationException
     */
    private function guardAliasesAreFree(LocationData $data, ?int $exceptId): void
    {
        foreach ($data->aliases as $alias) {
            $owner = $this->locations->findByAlias($alias, $exceptId);

            if ($owner !== null) {
                throw BusinessRuleViolationException::forRule(
                    'location.alias_taken',
                    __('locations.errors.alias_taken', [
                        'alias' => $alias,
                        'location' => $owner->name() ?? $owner->slug,
                    ]),
                    ['alias' => $alias, 'owner_id' => $owner->id]
                );
            }
        }
    }
}
