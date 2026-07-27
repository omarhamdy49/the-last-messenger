<?php

declare(strict_types=1);

namespace App\Domain\Genealogy\Repositories;

use App\Domain\Genealogy\DTOs\PersonListFilters;
use App\Domain\Genealogy\Enums\PersonType;
use App\Domain\Genealogy\Models\Person;
use App\Domain\Genealogy\Models\Relationship;
use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Person Repository Interface
 */
interface PersonRepositoryInterface extends RepositoryInterface
{
    /**
     * Get all people with pagination
     *
     * @param  array<string>  $relations
     */
    public function getAllPaginated(int $perPage = 15, array $relations = []): LengthAwarePaginator;

    /**
     * Paginate genealogy records for the admin data table with search +
     * advanced filters, locale-aware name ordering.
     */
    public function getFilteredPaginated(PersonListFilters $filters, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get people by type
     *
     * @param  array<string>  $relations
     */
    public function getByType(PersonType $type, int $perPage = 15, array $relations = []): LengthAwarePaginator;

    /**
     * Get family tree for a person. Descendants exclude anyone flagged
     * hide_from_tree and are ordered by tree_display_order (nulls last),
     * optionally capped at $maxChildrenPerLevel per generation.
     *
     * @return array<string, mixed>
     */
    public function getFamilyTree(int $personId, int $generations = 3, ?int $maxChildrenPerLevel = null): array;

    /**
     * Get direct descendants
     *
     * @param  array<string>  $relations
     */
    public function getDescendants(int $personId, array $relations = []): Collection;

    /**
     * Get direct ancestors
     *
     * @param  array<string>  $relations
     */
    public function getAncestors(int $personId, array $relations = []): Collection;

    /**
     * Whether $ancestorCandidateId is already an ancestor of $descendantId —
     * used to reject father_id/mother_id assignments that would close a
     * cycle in the family tree.
     */
    public function isAncestor(int $ancestorCandidateId, int $descendantId, int $maxDepth = 50): bool;

    /**
     * Get grandparents (exactly two hops up via father_id/mother_id).
     *
     * @param  array<string>  $relations
     */
    public function getGrandparents(int $personId, array $relations = []): Collection;

    /**
     * Get grandchildren (exactly two hops down via father_id/mother_id).
     *
     * @param  array<string>  $relations
     */
    public function getGrandchildren(int $personId, array $relations = []): Collection;

    /**
     * Get nephews/nieces: children of this person's siblings.
     * Siblings come from the `relationships` table (type=sibling); their
     * children are derived from father_id/mother_id, same as any other
     * parent/child lookup.
     *
     * @param  array<string>  $relations
     */
    public function getNephewsAndNieces(int $personId, array $relations = []): Collection;

    /**
     * Get spouses: the other side of every `relationships` row of type
     * spouse involving this person, in either direction. Excludes rows
     * flagged hide_from_tree and orders by display_order (nulls last),
     * optionally capped at $maxCount.
     *
     * @param  array<string>  $relations
     */
    public function getSpouses(int $personId, array $relations = [], ?int $maxCount = null): Collection;

    /**
     * All spouse relationship rows involving this person (either direction),
     * unfiltered — for the admin curation screen, which must be able to see
     * and un-hide relationships that getSpouses() would otherwise exclude.
     *
     * @return Collection<int, Relationship>
     */
    public function getSpouseRelationshipsForAdmin(int $personId): Collection;

    /**
     * Whether a spouse relationship already exists between these two
     * people, in either row direction.
     */
    public function spouseRelationshipExists(int $personId, int $spouseId): bool;

    /**
     * Create a new spouse relationship row.
     */
    public function addSpouseRelationship(int $personId, int $spouseId): Relationship;

    /**
     * Find a relationship row or fail.
     */
    public function findRelationshipOrFail(int $relationshipId): Relationship;

    /**
     * Update a relationship's tree display order and visibility.
     */
    public function updateRelationshipDisplay(int $relationshipId, ?int $displayOrder, bool $hideFromTree): Relationship;

    /**
     * Delete a relationship row.
     */
    public function deleteRelationship(int $relationshipId): void;

    /**
     * Search people
     *
     * @param  array<string>  $relations
     */
    public function search(string $query, int $perPage = 15, array $relations = []): LengthAwarePaginator;

    /**
     * Get people by tribe
     *
     * @param  array<string>  $relations
     */
    public function getByTribe(string $tribe, int $perPage = 15, array $relations = []): LengthAwarePaginator;

    /**
     * Get verified people
     *
     * @param  array<string>  $relations
     */
    public function getVerified(int $perPage = 15, array $relations = []): LengthAwarePaginator;
}
