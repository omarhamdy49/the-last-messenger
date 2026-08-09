<?php

declare(strict_types=1);

namespace App\Domain\People\Repositories;

use App\Domain\People\DTOs\MuslimListFilters;
use App\Domain\People\Models\Muslim;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;

interface MuslimRepositoryInterface
{
    public function paginate(MuslimListFilters $filters): LengthAwarePaginator;

    public function findBySlug(string $slug, bool $publishedOnly = true): ?Muslim;

    public function findById(int $id): ?Muslim;

    /** Lowest-id person carrying an islamic role, by role slug. */
    public function findIdByRoleSlug(string $roleSlug): ?int;

    /**
     * The parentless person with the most recorded children through the father
     * link. Only a last-resort tree root — the result depends on import order.
     */
    public function findMostConnectedRootId(): ?int;

    /** Lowest-id person of all, drafts included. */
    public function firstById(): ?Muslim;

    /**
     * Direct children through EITHER parent link, drafts included, ordered by
     * `tree_display_order` with unordered children last.
     *
     * @return Collection<int, Muslim>
     */
    public function childrenOf(int $muslimId): Collection;

    /**
     * Parent links only, keyed by id — enough to nest a descendant list that
     * was already fetched, without re-reading the people themselves.
     *
     * @param  array<int, int>  $ids
     * @return Collection<int, Muslim>
     */
    public function parentLinksFor(array $ids): Collection;

    /**
     * People/linked/roots/hidden in ONE pass over `muslims` with conditional
     * aggregates, rather than four COUNT queries over the same table.
     *
     * @return array{people:int,linked:int,roots:int,hidden:int}
     */
    public function treeCounts(): array;

    /**
     * Bilingual FULLTEXT search over the normalised name columns.
     * Separate MATCH branches per language, merged by score.
     */
    public function search(string $term, int $limit = 20, bool $publishedOnly = true): Collection;

    /**
     * Admin picker source. Deliberately includes unpublished people: an
     * unpublished person is still a valid ancestor, narrator, or compiler.
     *
     * @return Collection<int, Muslim>
     */
    public function pickerSearch(string $term, ?int $excludeId = null, int $limit = 20): Collection;

    /**
     * Ancestor ids, walking father_id/mother_id upward. A plain support
     * collection of ints, NOT an Eloquent collection: Eloquent's contains()
     * treats a bare int as a model key and calls getKey() on it.
     *
     * @return SupportCollection<int, int>
     */
    public function ancestorIds(int $muslimId, int $maxDepth): SupportCollection;

    /** @param array<int, array{role_id: int, is_featured?: bool, sort_order?: int}> $roles */
    public function syncRoles(Muslim $muslim, array $roles): void;

    public function create(array $attributes, array $roles = []): Muslim;

    public function update(Muslim $muslim, array $attributes, ?array $roles = null): Muslim;

    public function delete(Muslim $muslim): void;

    /**
     * @param  array<int, int>  $ids
     */
    public function bulkDelete(array $ids): int;

    /**
     * @param  array<int, int>  $ids
     */
    public function bulkSetPublished(array $ids, bool $published): int;

    /**
     * @param  array<int, int>  $ids
     */
    public function bulkSetFeatured(array $ids, bool $featured): int;
}
