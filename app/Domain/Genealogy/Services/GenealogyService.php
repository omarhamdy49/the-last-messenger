<?php

declare(strict_types=1);

namespace App\Domain\Genealogy\Services;

use App\Domain\Genealogy\Enums\PersonType;
use App\Domain\Genealogy\Models\Person;
use App\Domain\Genealogy\Repositories\PersonRepositoryInterface;
use App\Domain\Shared\Services\BaseService;
use App\Exceptions\Domain\ResourceNotFoundException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Genealogy Service
 * Handles business logic for genealogy/family tree
 */
class GenealogyService extends BaseService
{
    /**
     * Constructor
     */
    public function __construct(
        PersonRepositoryInterface $repository
    ) {
        $this->repository = $repository;
    }

    /**
     * Get all people with pagination
     */
    public function getAllPeople(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getAllPaginated($perPage, ['father', 'mother', 'sahabi']);
    }

    /**
     * Get people by type
     */
    public function getPeopleByType(PersonType $type, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByType($type, $perPage, ['father', 'mother', 'sahabi']);
    }

    /**
     * Get family tree for a person
     *
     * @return array<string, mixed>
     *
     * @throws ResourceNotFoundException
     */
    public function getFamilyTree(int $personId, int $generations = 3): array
    {
        $person = $this->repository->find($personId);

        if (! $person) {
            throw new ResourceNotFoundException('Person', $personId);
        }

        return $this->repository->getFamilyTree($personId, $generations);
    }

    /**
     * Get descendants of a person
     *
     * @throws ResourceNotFoundException
     */
    public function getDescendants(int $personId): Collection
    {
        $person = $this->repository->find($personId);

        if (! $person) {
            throw new ResourceNotFoundException('Person', $personId);
        }

        return $this->repository->getDescendants($personId, ['father', 'mother']);
    }

    /**
     * Get ancestors of a person
     *
     * @throws ResourceNotFoundException
     */
    public function getAncestors(int $personId): Collection
    {
        $person = $this->repository->find($personId);

        if (! $person) {
            throw new ResourceNotFoundException('Person', $personId);
        }

        return $this->repository->getAncestors($personId, ['father', 'mother']);
    }

    /**
     * Search people
     */
    public function searchPeople(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->search($query, $perPage, ['father', 'mother', 'sahabi']);
    }

    /**
     * Get people by tribe
     */
    public function getPeopleByTribe(string $tribe, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByTribe($tribe, $perPage, ['father', 'mother', 'sahabi']);
    }

    /**
     * Get verified people
     */
    public function getVerifiedPeople(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getVerified($perPage, ['father', 'mother', 'sahabi']);
    }

    /**
     * Create new person
     *
     * @param  array<string, mixed>  $data
     */
    public function createPerson(array $data): Person
    {
        return $this->repository->create($data);
    }

    /**
     * Update person
     *
     * @param  array<string, mixed>  $data
     */
    public function updatePerson(int $id, array $data): bool
    {
        return $this->repository->update($id, $data);
    }

    /**
     * Delete person
     */
    public function deletePerson(int $id): bool
    {
        return $this->repository->delete($id);
    }

    /**
     * Get person by ID
     *
     * @throws ResourceNotFoundException
     */
    public function getPerson(int $id): Person
    {
        $person = $this->repository->find($id, ['*'], ['father', 'mother', 'sahabi', 'childrenAsFather', 'childrenAsMother']);

        if (! $person) {
            throw new ResourceNotFoundException('Person', $id);
        }

        return $person;
    }

    /**
     * Count total relationships in the genealogy tree
     */
    public function countRelationships(): int
    {
        // Count relationships by querying the Relationship model or similar
        // For now, return count of all parent-child relationships
        return $this->repository->count();
    }
}
