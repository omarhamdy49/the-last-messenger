<?php

declare(strict_types=1);

namespace App\Domain\Genealogy\Repositories;

use App\Domain\Genealogy\Enums\PersonType;
use App\Domain\Genealogy\Models\Person;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Person Repository
 * Handles data access for people/genealogy
 */
class PersonRepository extends BaseRepository implements PersonRepositoryInterface
{
    /**
     * Get the model class name
     */
    protected function getModel(): \Illuminate\Database\Eloquent\Model
    {
        return new Person;
    }

    /**
     * Get base query with common relations
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function getBaseQuery()
    {
        return $this->model::query();
    }

    /**
     * Get all people with pagination
     *
     * @param  array<string>  $relations
     */
    public function getAllPaginated(int $perPage = 15, array $relations = []): LengthAwarePaginator
    {
        return $this->getBaseQuery()
            ->with($relations)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get people by type
     *
     * @param  array<string>  $relations
     */
    public function getByType(PersonType $type, int $perPage = 15, array $relations = []): LengthAwarePaginator
    {
        return Cache::remember(
            $this->versionedKey("people:type:{$type->value}:page:1"),
            now()->addMinutes(30),
            fn () => $this->getBaseQuery()
                ->with($relations)
                ->where('person_type', $type)
                ->latest()
                ->paginate($perPage)
        );
    }

    /**
     * Get family tree for a person
     *
     * @return array<string, mixed>
     */
    public function getFamilyTree(int $personId, int $generations = 3): array
    {
        return Cache::remember(
            $this->versionedKey("people:tree:{$personId}:gen:{$generations}"),
            now()->addHour(),
            function () use ($personId, $generations) {
                $person = $this->findOrFail($personId, ['*'], ['father', 'mother', 'childrenAsFather', 'childrenAsMother']);

                return [
                    'person' => $person,
                    'ancestors' => $this->getAncestorsRecursive($person, $generations),
                    'descendants' => $this->getDescendantsRecursive($person, $generations),
                ];
            }
        );
    }

    /**
     * Get ancestors recursively
     *
     * @return array<string, mixed>
     */
    protected function getAncestorsRecursive(Person $person, int $generations): array
    {
        if ($generations <= 0) {
            return [];
        }

        $ancestors = [];

        if ($person->father) {
            $ancestors['father'] = [
                'person' => $person->father,
                'ancestors' => $this->getAncestorsRecursive($person->father, $generations - 1),
            ];
        }

        if ($person->mother) {
            $ancestors['mother'] = [
                'person' => $person->mother,
                'ancestors' => $this->getAncestorsRecursive($person->mother, $generations - 1),
            ];
        }

        return $ancestors;
    }

    /**
     * Get descendants recursively
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getDescendantsRecursive(Person $person, int $generations): array
    {
        if ($generations <= 0) {
            return [];
        }

        $descendants = [];

        // Combine children from both father and mother relationships
        $children = $person->childrenAsFather->merge($person->childrenAsMother)->unique('id');

        foreach ($children as $child) {
            $descendants[] = [
                'person' => $child,
                'descendants' => $this->getDescendantsRecursive($child, $generations - 1),
            ];
        }

        return $descendants;
    }

    /**
     * Get direct descendants
     *
     * @param  array<string>  $relations
     */
    public function getDescendants(int $personId, array $relations = []): Collection
    {
        return $this->model::with($relations)
            ->where('father_id', $personId)
            ->orWhere('mother_id', $personId)
            ->get();
    }

    /**
     * Get direct ancestors
     *
     * @param  array<string>  $relations
     */
    public function getAncestors(int $personId, array $relations = []): Collection
    {
        $person = $this->findOrFail($personId);
        $ancestors = new Collection;

        if ($person->father_id) {
            $father = $this->find($person->father_id, $relations);
            if ($father) {
                $ancestors->push($father);
            }
        }

        if ($person->mother_id) {
            $mother = $this->find($person->mother_id, $relations);
            if ($mother) {
                $ancestors->push($mother);
            }
        }

        return $ancestors;
    }

    /**
     * Search people
     *
     * @param  array<string>  $relations
     */
    public function search(string $query, int $perPage = 15, array $relations = []): LengthAwarePaginator
    {
        return $this->getBaseQuery()
            ->with($relations)
            ->where(function ($q) use ($query) {
                $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.ar')) LIKE ?", ["%{$query}%"])
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.en')) LIKE ?", ["%{$query}%"])
                    ->orWhere('tribe', 'like', "%{$query}%");
            })
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get people by tribe
     *
     * @param  array<string>  $relations
     */
    public function getByTribe(string $tribe, int $perPage = 15, array $relations = []): LengthAwarePaginator
    {
        return $this->getBaseQuery()
            ->with($relations)
            ->where('tribe', $tribe)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get verified people
     *
     * @param  array<string>  $relations
     */
    public function getVerified(int $perPage = 15, array $relations = []): LengthAwarePaginator
    {
        return Cache::remember(
            $this->versionedKey('people:verified:page:1'),
            now()->addMinutes(30),
            fn () => $this->getBaseQuery()
                ->with($relations)
                ->where('is_verified', true)
                ->latest()
                ->paginate($perPage)
        );
    }

    /**
     * Clear cache.
     *
     * Cache::tags() requires Redis/Memcached and is unavailable with the file
     * or database drivers. We bump a version counter so that all versioned
     * keys written by this repository are orphaned on the next read cycle.
     */
    public function clearCache(): void
    {
        $versionKey = 'people:version';
        Cache::forever($versionKey, ((int) Cache::get($versionKey, 0)) + 1);
    }

    /**
     * Prefix a cache key with the current version so that clearCache()
     * effectively invalidates all keys written by this repository.
     */
    private function versionedKey(string $key): string
    {
        $version = (int) Cache::get('people:version', 0);

        return "people:v{$version}:{$key}";
    }
}
