<?php

declare(strict_types=1);

namespace App\Domain\Genealogy\Repositories;

use App\Domain\Genealogy\DTOs\PersonListFilters;
use App\Domain\Genealogy\Enums\PersonType;
use App\Domain\Genealogy\Enums\RelationshipType;
use App\Domain\Genealogy\Models\Person;
use App\Domain\Genealogy\Models\Relationship;
use App\Domain\Shared\Helpers\FullTextSearchHelper;
use App\Domain\Shared\Models\Language;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
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
     * Join the active-locale translation row (falling back to the default
     * language) and order alphabetically by the name that is actually
     * displayed. Mirrors SahabiRepository::applyLocaleNameOrder() — Person
     * and Sahabi share the same `sahaba` + `sahabi_translations` tables.
     */
    private function applyLocaleNameOrder(Builder $query): Builder
    {
        $locale = app()->getLocale();
        $fallback = Language::getDefaultCode();

        return $query
            ->leftJoin('sahabi_translations as name_loc', function ($join) use ($locale) {
                $join->on('name_loc.sahabi_id', '=', 'sahaba.id')->where('name_loc.locale', $locale);
            })
            ->leftJoin('sahabi_translations as name_fb', function ($join) use ($fallback) {
                $join->on('name_fb.sahabi_id', '=', 'sahaba.id')->where('name_fb.locale', $fallback);
            })
            ->orderByRaw('COALESCE(name_loc.name, name_fb.name) ASC')
            ->select('sahaba.*');
    }

    /**
     * Paginate genealogy records for the admin data table: locale-aware name
     * order, plus search (name/kunya/laqab) and gender/type/tribe/verified
     * filters exposed by the "Advanced Filters" panel. Intentionally does
     * NOT scope to is_sahabi — genealogy includes prophets, family members,
     * tribes, and other historical figures the Sahaba module never lists.
     */
    public function getFilteredPaginated(PersonListFilters $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->applyLocaleNameOrder($this->model->newQuery());

        if ($filters->search !== null) {
            $query->whereHas('translations', function ($tq) use ($filters) {
                FullTextSearchHelper::apply($tq, ['name', 'kunya', 'laqab'], $filters->search);
            });
        }

        if ($filters->gender !== null) {
            $query->where('gender', $filters->gender);
        }

        if ($filters->personType !== null) {
            $query->where('person_type', $filters->personType);
        }

        if ($filters->tribe !== null) {
            $query->where('tribe', 'like', "%{$filters->tribe}%");
        }

        if ($filters->isVerified !== null) {
            $query->where('is_verified', $filters->isVerified);
        }

        return $query
            ->with(['father', 'mother', 'translations'])
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
    public function getFamilyTree(int $personId, int $generations = 3, ?int $maxChildrenPerLevel = null): array
    {
        return Cache::remember(
            $this->versionedKey("people:tree:{$personId}:gen:{$generations}:max:".($maxChildrenPerLevel ?? 'all')),
            now()->addHour(),
            function () use ($personId, $generations, $maxChildrenPerLevel) {
                $person = $this->findOrFail($personId, ['*'], ['father', 'mother']);

                return [
                    'person' => $person,
                    'ancestors' => $this->getAncestorsRecursive($person, $generations),
                    'descendants' => $this->getDescendantsRecursive($person, $generations, $maxChildrenPerLevel),
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
     * Get descendants recursively. Queried directly (not via the
     * childrenAsFather/childrenAsMother relations) so hide_from_tree and
     * tree_display_order can be applied at every generation, and so an
     * admin-configured per-level cap can be enforced consistently.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getDescendantsRecursive(Person $person, int $generations, ?int $maxPerLevel = null): array
    {
        if ($generations <= 0) {
            return [];
        }

        $query = $this->model::query()
            ->where(function (Builder $q) use ($person) {
                $q->where('father_id', $person->id)->orWhere('mother_id', $person->id);
            })
            ->where('hide_from_tree', false)
            ->orderByRaw('tree_display_order IS NULL, tree_display_order ASC')
            ->orderBy('id');

        if ($maxPerLevel !== null) {
            $query->limit($maxPerLevel);
        }

        $descendants = [];
        foreach ($query->get() as $child) {
            $descendants[] = [
                'person' => $child,
                'descendants' => $this->getDescendantsRecursive($child, $generations - 1, $maxPerLevel),
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
     * Whether $ancestorCandidateId is already an ancestor of $descendantId —
     * i.e. walking up $descendantId's father_id/mother_id chain reaches
     * $ancestorCandidateId. Used to reject a father_id/mother_id assignment
     * that would close a cycle (assigning P's parent to one of P's own
     * descendants would create parent -> descendant -> ... -> parent).
     * Depth-capped and visited-tracked so it terminates even if the existing
     * data already contains a cycle.
     */
    public function isAncestor(int $ancestorCandidateId, int $descendantId, int $maxDepth = 50): bool
    {
        $visited = [];
        $frontier = [$descendantId];
        $depth = 0;

        while ($frontier !== [] && $depth < $maxDepth) {
            $rows = $this->model::query()
                ->whereIn('id', $frontier)
                ->select(['id', 'father_id', 'mother_id'])
                ->get();

            $frontier = [];
            foreach ($rows as $row) {
                foreach ([$row->father_id, $row->mother_id] as $parentId) {
                    if ($parentId === null || isset($visited[$parentId])) {
                        continue;
                    }

                    if ($parentId === $ancestorCandidateId) {
                        return true;
                    }

                    $visited[$parentId] = true;
                    $frontier[] = $parentId;
                }
            }

            $depth++;
        }

        return false;
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
     * Get grandparents (exactly two hops up via father_id/mother_id).
     *
     * Re-derives what the pre-2026-06-24 `relationships` rows stored
     * explicitly (grandfather/grandmother edges) from the canonical parentage
     * columns instead — those edges are fully recoverable this way, since
     * grandparent status has never depended on anything but father_id/mother_id.
     *
     * @param  array<string>  $relations
     */
    public function getGrandparents(int $personId, array $relations = []): Collection
    {
        $grandparents = new Collection;

        foreach ($this->getAncestors($personId) as $parent) {
            foreach ($this->getAncestors($parent->id, $relations) as $grandparent) {
                $grandparents->push($grandparent);
            }
        }

        return $grandparents->unique('id')->values();
    }

    /**
     * Get grandchildren (exactly two hops down via father_id/mother_id).
     *
     * @param  array<string>  $relations
     */
    public function getGrandchildren(int $personId, array $relations = []): Collection
    {
        $grandchildren = new Collection;

        foreach ($this->getDescendants($personId) as $child) {
            foreach ($this->getDescendants($child->id, $relations) as $grandchild) {
                $grandchildren->push($grandchild);
            }
        }

        return $grandchildren->unique('id')->values();
    }

    /**
     * Get nephews/nieces: children of this person's siblings.
     *
     * @param  array<string>  $relations
     */
    public function getNephewsAndNieces(int $personId, array $relations = []): Collection
    {
        $siblingIds = Relationship::where('relationship_type', RelationshipType::SIBLING)
            ->where(function ($query) use ($personId) {
                $query->where('person_id', $personId)->orWhere('related_person_id', $personId);
            })
            ->get()
            ->map(fn (Relationship $r) => $r->person_id === $personId ? $r->related_person_id : $r->person_id)
            ->unique();

        $nephewsAndNieces = new Collection;
        foreach ($siblingIds as $siblingId) {
            foreach ($this->getDescendants($siblingId, $relations) as $child) {
                $nephewsAndNieces->push($child);
            }
        }

        return $nephewsAndNieces->unique('id')->values();
    }

    /**
     * Get spouses: the other side of every `relationships` row of type
     * spouse involving this person, in either direction (the pivot doesn't
     * privilege a "husband" or "wife" column — see RelationshipType's
     * doc comment on why marriage lives here and not on father_id/mother_id).
     * Excludes rows flagged hide_from_tree and preserves display_order
     * (nulls last) — whereIn() doesn't guarantee row order, so the person
     * list is re-sorted to match the relationship order after fetching.
     *
     * @param  array<string>  $relations
     */
    public function getSpouses(int $personId, array $relations = [], ?int $maxCount = null): Collection
    {
        $spouseIds = Relationship::where('relationship_type', RelationshipType::SPOUSE)
            ->where(function ($query) use ($personId) {
                $query->where('person_id', $personId)->orWhere('related_person_id', $personId);
            })
            ->where('hide_from_tree', false)
            ->orderByRaw('display_order IS NULL, display_order ASC')
            ->orderBy('id')
            ->get()
            ->map(fn (Relationship $r) => $r->person_id === $personId ? $r->related_person_id : $r->person_id)
            ->unique()
            ->values();

        if ($maxCount !== null) {
            $spouseIds = $spouseIds->take($maxCount);
        }

        if ($spouseIds->isEmpty()) {
            return new Collection;
        }

        $people = $this->getBaseQuery()->with($relations)->whereIn('id', $spouseIds)->get()->keyBy('id');

        return new Collection(
            $spouseIds->map(fn (int $id) => $people->get($id))->filter()->values()->all()
        );
    }

    /**
     * All spouse relationship rows involving this person (either direction),
     * unfiltered by hide_from_tree — the admin curation screen must be able
     * to see (and un-hide) relationships that getSpouses() excludes.
     */
    public function getSpouseRelationshipsForAdmin(int $personId): Collection
    {
        return Relationship::where('relationship_type', RelationshipType::SPOUSE)
            ->where(function ($query) use ($personId) {
                $query->where('person_id', $personId)->orWhere('related_person_id', $personId);
            })
            ->with(['person:id,slug', 'person.translations', 'relatedPerson:id,slug', 'relatedPerson.translations'])
            ->orderByRaw('display_order IS NULL, display_order ASC')
            ->orderBy('id')
            ->get();
    }

    /**
     * Whether a spouse relationship already exists between these two
     * people, in either row direction (the pivot has no privileged side).
     */
    public function spouseRelationshipExists(int $personId, int $spouseId): bool
    {
        return Relationship::where('relationship_type', RelationshipType::SPOUSE)
            ->where(function ($query) use ($personId, $spouseId) {
                $query->where(function (Builder $q) use ($personId, $spouseId) {
                    $q->where('person_id', $personId)->where('related_person_id', $spouseId);
                })->orWhere(function (Builder $q) use ($personId, $spouseId) {
                    $q->where('person_id', $spouseId)->where('related_person_id', $personId);
                });
            })
            ->exists();
    }

    /**
     * Create a new spouse relationship row.
     */
    public function addSpouseRelationship(int $personId, int $spouseId): Relationship
    {
        return Relationship::create([
            'person_id' => $personId,
            'related_person_id' => $spouseId,
            'relationship_type' => RelationshipType::SPOUSE,
        ]);
    }

    /**
     * Find a relationship row or fail.
     */
    public function findRelationshipOrFail(int $relationshipId): Relationship
    {
        return Relationship::findOrFail($relationshipId);
    }

    /**
     * Update a relationship's tree display order and visibility.
     */
    public function updateRelationshipDisplay(int $relationshipId, ?int $displayOrder, bool $hideFromTree): Relationship
    {
        $relationship = Relationship::findOrFail($relationshipId);
        $relationship->update([
            'display_order' => $displayOrder,
            'hide_from_tree' => $hideFromTree,
        ]);

        return $relationship;
    }

    /**
     * Delete a relationship row.
     */
    public function deleteRelationship(int $relationshipId): void
    {
        Relationship::findOrFail($relationshipId)->delete();
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
                $q->whereHas('translations', function ($translation) use ($query): void {
                    $translation->where('name', 'like', "%{$query}%")
                        ->orWhere('full_name', 'like', "%{$query}%")
                        ->orWhere('kunya', 'like', "%{$query}%")
                        ->orWhere('laqab', 'like', "%{$query}%");
                })
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
