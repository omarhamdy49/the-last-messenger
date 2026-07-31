<?php

declare(strict_types=1);

namespace App\Domain\Genealogy\Services;

use App\Domain\Genealogy\DTOs\CreatePersonData;
use App\Domain\Genealogy\DTOs\PersonListFilters;
use App\Domain\Genealogy\Enums\PersonType;
use App\Domain\Genealogy\Models\Person;
use App\Domain\Genealogy\Models\Relationship;
use App\Domain\Genealogy\Repositories\PersonRepositoryInterface;
use App\Domain\Shared\Services\BaseService;
use App\Exceptions\Domain\ResourceNotFoundException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

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
        return $this->repository->getAllPaginated($perPage, ['father', 'mother', 'translations']);
    }

    /**
     * Get genealogy records for the admin data table with search + filters.
     */
    public function getAdminPersonList(PersonListFilters $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getFilteredPaginated($filters, $perPage);
    }

    /**
     * Get people by type
     */
    public function getPeopleByType(PersonType $type, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByType($type, $perPage, ['father', 'mother', 'translations']);
    }

    /**
     * Get family tree for a person
     *
     * @return array<string, mixed>
     *
     * @throws ResourceNotFoundException
     */
    public function getFamilyTree(int $personId, int $generations = 3, ?int $maxChildrenPerLevel = null): array
    {
        $person = $this->repository->find($personId);

        if (! $person) {
            throw new ResourceNotFoundException('Person', $personId);
        }

        return $this->repository->getFamilyTree($personId, $generations, $maxChildrenPerLevel);
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
     * Get grandparents of a person
     *
     * @throws ResourceNotFoundException
     */
    public function getGrandparents(int $personId): Collection
    {
        if (! $this->repository->find($personId)) {
            throw new ResourceNotFoundException('Person', $personId);
        }

        return $this->repository->getGrandparents($personId, ['father', 'mother']);
    }

    /**
     * Get grandchildren of a person
     *
     * @throws ResourceNotFoundException
     */
    public function getGrandchildren(int $personId): Collection
    {
        if (! $this->repository->find($personId)) {
            throw new ResourceNotFoundException('Person', $personId);
        }

        return $this->repository->getGrandchildren($personId, ['father', 'mother']);
    }

    /**
     * Get nephews and nieces of a person
     *
     * @throws ResourceNotFoundException
     */
    public function getNephewsAndNieces(int $personId): Collection
    {
        if (! $this->repository->find($personId)) {
            throw new ResourceNotFoundException('Person', $personId);
        }

        return $this->repository->getNephewsAndNieces($personId, ['father', 'mother']);
    }

    /**
     * Get spouses of a person
     *
     * @throws ResourceNotFoundException
     */
    public function getSpouses(int $personId, ?int $maxCount = null): Collection
    {
        if (! $this->repository->find($personId)) {
            throw new ResourceNotFoundException('Person', $personId);
        }

        return $this->repository->getSpouses($personId, ['translations'], $maxCount);
    }

    /**
     * All spouse relationships involving this person, for the admin
     * curation screen (add/remove/reorder/hide).
     *
     * @throws ResourceNotFoundException
     */
    public function getSpouseRelationships(int $personId): Collection
    {
        if (! $this->repository->find($personId)) {
            throw new ResourceNotFoundException('Person', $personId);
        }

        return $this->repository->getSpouseRelationshipsForAdmin($personId);
    }

    /**
     * Add a spouse relationship between two people.
     *
     * @throws ResourceNotFoundException
     * @throws InvalidArgumentException
     */
    public function addSpouse(int $personId, int $spouseId): Relationship
    {
        if ($personId === $spouseId) {
            throw new InvalidArgumentException('A person cannot be their own spouse.');
        }

        if (! $this->repository->find($personId) || ! $this->repository->find($spouseId)) {
            throw new ResourceNotFoundException('Person', $spouseId);
        }

        if ($this->repository->spouseRelationshipExists($personId, $spouseId)) {
            throw new InvalidArgumentException('This spouse relationship already exists.');
        }

        return $this->repository->addSpouseRelationship($personId, $spouseId);
    }

    /**
     * Get a relationship or fail.
     */
    public function getRelationship(int $relationshipId): Relationship
    {
        return $this->repository->findRelationshipOrFail($relationshipId);
    }

    /**
     * Update a relationship's tree display order and visibility.
     */
    public function updateRelationshipDisplay(int $relationshipId, ?int $displayOrder, bool $hideFromTree): Relationship
    {
        return $this->repository->updateRelationshipDisplay($relationshipId, $displayOrder, $hideFromTree);
    }

    /**
     * Remove a relationship.
     */
    public function removeRelationship(int $relationshipId): void
    {
        $this->repository->deleteRelationship($relationshipId);
    }

    /**
     * Search people
     */
    public function searchPeople(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->search($query, $perPage, ['father', 'mother', 'translations']);
    }

    /**
     * Get people by tribe
     */
    public function getPeopleByTribe(string $tribe, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByTribe($tribe, $perPage, ['father', 'mother', 'translations']);
    }

    /**
     * Get verified people
     */
    public function getVerifiedPeople(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getVerified($perPage, ['father', 'mother', 'translations']);
    }

    /**
     * Create new person
     */
    public function createPerson(CreatePersonData $data): Person
    {
        return DB::transaction(function () use ($data): Person {
            $baseSlug = Str::slug($data->name['en'] ?? $data->name['ar'] ?? '') ?: 'genealogy-person';
            $slug = $baseSlug;
            $suffix = 2;
            while (Person::where('slug', $slug)->exists()) {
                $slug = $baseSlug.'-'.$suffix++;
            }

            /** @var Person $person */
            $person = $this->repository->create([
                'slug' => $slug,
                'person_type' => $data->personType,
                'is_sahabi' => $data->personType === 'sahabi',
                'gender' => $data->gender,
                'father_id' => $data->fatherId,
                'mother_id' => $data->motherId,
                'tree_display_order' => $data->treeDisplayOrder,
                'hide_from_tree' => $data->hideFromTree,
                'birth_date_hijri' => $data->birthDateHijri,
                'birth_date_gregorian' => $data->birthDateGregorian,
                'death_date_hijri' => $data->deathDateHijri,
                'death_date_gregorian' => $data->deathDateGregorian,
                'tribe' => $data->tribe,
                'is_verified' => $data->isVerified,
            ]);

            $person->syncTranslations([
                'name' => $data->name,
                'title' => $data->title,
                'birth_place' => $data->birthPlace,
                'death_place' => $data->deathPlace,
                'burial_place' => $data->burialPlace,
                'occupation' => $data->occupation,
                'biography' => $data->biography,
                'notes' => $data->notes,
            ]);

            return $person->fresh(['translations', 'father', 'mother']);
        });
    }

    /**
     * Update person
     *
     * @param  array<string, mixed>  $data
     */
    public function updatePerson(int $id, array $data): bool
    {
        return DB::transaction(function () use ($id, $data): bool {
            $person = $this->getPerson($id);
            $translationData = array_intersect_key($data, array_flip($person->getTranslatableFields()));
            $coreData = array_diff_key($data, $translationData, ['sahaba_id' => true]);

            if (isset($coreData['person_type'])) {
                $coreData['is_sahabi'] = $coreData['person_type'] === 'sahabi';
            }

            $updated = empty($coreData) || $this->repository->update($id, $coreData);
            if ($translationData !== []) {
                $person->syncTranslations($translationData);
            }

            return $updated;
        });
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
        $person = $this->repository->find($id, ['*'], ['father', 'mother', 'translations', 'childrenAsFather', 'childrenAsMother']);

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
        return Relationship::query()->count();
    }
}
