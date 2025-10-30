<?php

declare(strict_types=1);

namespace App\Domain\Genealogy\Repositories;

use App\Domain\Genealogy\Enums\PersonType;
use App\Domain\Genealogy\Models\Person;
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
     * Get people by type
     *
     * @param  array<string>  $relations
     */
    public function getByType(PersonType $type, int $perPage = 15, array $relations = []): LengthAwarePaginator;

    /**
     * Get family tree for a person
     *
     * @return array<string, mixed>
     */
    public function getFamilyTree(int $personId, int $generations = 3): array;

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
