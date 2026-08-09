<?php

declare(strict_types=1);

namespace App\Domain\People\Services;

use App\Domain\People\Exceptions\GenealogyCycleException;
use App\Domain\People\Models\Muslim;
use App\Domain\People\Models\MuslimRelationship;
use App\Domain\People\Repositories\MuslimRelationshipRepositoryInterface;
use App\Domain\People\Repositories\MuslimRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The family tree, rooted at any person in `muslims`.
 *
 * Two things here are worth understanding before changing anything:
 *
 * 1. CYCLE PREVENTION IS A SERVICE CONCERN. No CHECK constraint can express
 *    "this person is not already my ancestor" — it needs a traversal. So
 *    assignParent() walks ancestors before writing, and the CTE depth cap is
 *    the backstop, not the check. A→B→C→A passes every database constraint.
 *
 * 2. PUBLICATION IS APPLIED AT RENDER, NOT AT SELECTION. An unpublished person
 *    is still a valid ancestor. The admin picker deliberately returns them;
 *    only the rendered tree filters them out.
 */
final class GenealogyTreeService
{
    /**
     * Depth cap for every recursive walk. Comfortably exceeds the deepest real
     * lineage in the corpus (Adam to the Prophet ﷺ is well under this), and
     * bounds a runaway edge that slipped past the cycle guard.
     */
    public const MAX_DEPTH = 60;

    public function __construct(
        private readonly MuslimRepositoryInterface $muslims,
        private readonly MuslimRelationshipRepositoryInterface $relationships,
    ) {}

    /**
     * Assign a father or mother, refusing anything that would create a cycle.
     *
     * @param  'father_id'|'mother_id'  $column
     *
     * @throws GenealogyCycleException
     */
    public function assignParent(Muslim $muslim, string $column, ?int $parentId): Muslim
    {
        if ($parentId === null) {
            return $this->muslims->update($muslim, [$column => null]);
        }

        if ($parentId === $muslim->id) {
            throw GenealogyCycleException::selfParent();
        }

        // The deep case: assigning P as a parent is illegal when the record
        // being edited is already somewhere in P's ancestry, because the edge
        // would close the loop. A→B→C→A is exactly this and no constraint
        // catches it.
        if ($this->muslims->ancestorIds($parentId, self::MAX_DEPTH)->contains($muslim->id)) {
            throw GenealogyCycleException::ancestorCycle($muslim->name());
        }

        return $this->muslims->update($muslim, [$column => $parentId]);
    }

    /**
     * Ancestors of the root, nearest first.
     *
     * @return array{nodes: array<int, array<string, mixed>>, depth_reached: int, depth_capped: bool}
     */
    public function ancestors(Muslim $root, int $maxDepth = self::MAX_DEPTH): array
    {
        $depth = $this->cappedDepth($maxDepth);

        $rows = DB::select(
            <<<'SQL'
            WITH RECURSIVE ancestors (id, slug, name_ar, name_en, is_published, hide_from_tree, relation, depth) AS (
                SELECT m.id, m.slug, m.name_ar, m.name_en, m.is_published, m.hide_from_tree,
                       CAST('root' AS CHAR(10)), 0
                FROM muslims m
                WHERE m.id = ? AND m.deleted_at IS NULL
                UNION ALL
                SELECT p.id, p.slug, p.name_ar, p.name_en, p.is_published, p.hide_from_tree,
                       CAST(IF(p.id = c.father_id, 'father', 'mother') AS CHAR(10)), a.depth + 1
                FROM ancestors a
                JOIN muslims c ON c.id = a.id
                JOIN muslims p ON p.id IN (c.father_id, c.mother_id) AND p.deleted_at IS NULL
                WHERE a.depth < ?
            )
            SELECT * FROM ancestors WHERE depth > 0 ORDER BY depth, id
            SQL,
            [$root->id, $depth]
        );

        return $this->buildResult($rows, $depth);
    }

    /**
     * Descendants of the root, nearest first, siblings ordered by
     * `tree_display_order`.
     *
     * @return array{nodes: array<int, array<string, mixed>>, depth_reached: int, depth_capped: bool}
     */
    public function descendants(Muslim $root, int $maxDepth = self::MAX_DEPTH): array
    {
        $depth = $this->cappedDepth($maxDepth);

        $rows = DB::select(
            <<<'SQL'
            WITH RECURSIVE descendants (id, slug, name_ar, name_en, is_published, hide_from_tree, tree_display_order, depth) AS (
                SELECT m.id, m.slug, m.name_ar, m.name_en, m.is_published, m.hide_from_tree,
                       m.tree_display_order, 0
                FROM muslims m
                WHERE m.id = ? AND m.deleted_at IS NULL
                UNION ALL
                SELECT c.id, c.slug, c.name_ar, c.name_en, c.is_published, c.hide_from_tree,
                       c.tree_display_order, d.depth + 1
                FROM descendants d
                JOIN muslims c ON (c.father_id = d.id OR c.mother_id = d.id) AND c.deleted_at IS NULL
                WHERE d.depth < ?
            )
            SELECT * FROM descendants WHERE depth > 0
            ORDER BY depth, COALESCE(tree_display_order, 4294967295), id
            SQL,
            [$root->id, $depth]
        );

        return $this->buildResult($rows, $depth);
    }

    /**
     * Non-descent relations, read from BOTH sides because each relationship is
     * stored once.
     *
     * @return array<int, array<string, mixed>>
     */
    public function relations(Muslim $root, string $locale = 'ar', bool $publishedOnly = true): array
    {
        $relations = [];

        foreach ($this->relationships->forMuslim($root->id) as $row) {
            if ($publishedOnly && $row->hide_from_tree) {
                continue;
            }

            $counterpart = $this->counterpartOf($row, $root->id);

            if ($counterpart === null || ($publishedOnly && ! $counterpart->is_published)) {
                continue;
            }

            $relations[] = [
                'slug' => $counterpart->slug,
                'name' => $counterpart->name($locale),
                'type' => $row->relationship_type->value,
                'label' => $row->labelFor($root->id, $locale),
                'notes' => $row->notes,
            ];
        }

        return $relations;
    }

    /**
     * The same edges as relations(), but shaped for the admin table: the row
     * model itself (the edit and delete forms post against it), the counterpart
     * model, and how the row reads from THIS person's side.
     *
     * Drafts and tree-hidden edges are always included — an editor who cannot
     * see them cannot tell an incomplete tree from a finished one.
     *
     * @return array<int, array<string, mixed>>
     */
    public function relationshipRows(Muslim $muslim, string $locale = 'ar'): array
    {
        $rows = [];

        foreach ($this->relationships->forMuslim($muslim->id) as $row) {
            $counterpart = $this->counterpartOf($row, $muslim->id);

            $rows[] = [
                'model' => $row,
                'counterpart' => $counterpart,
                'counterpart_name' => $counterpart?->name($locale) ?? '—',
                // The stored type reads differently from each side, so the
                // label shown must be the one for the person being edited.
                'label' => $row->labelFor($muslim->id, $locale),
                'is_reversed' => $row->muslim_id !== $muslim->id,
            ];
        }

        return $rows;
    }

    /**
     * Direct children of one person through either parent link, drafts included.
     *
     * @return Collection<int, Muslim>
     */
    public function children(Muslim $muslim): Collection
    {
        return $this->muslims->childrenOf($muslim->id);
    }

    /**
     * The person a tree should root at.
     *
     * A slug wins when it resolves. Otherwise the root is whoever carries the
     * `prophet` role — the role rather than a hardcoded slug, so it stays
     * correct if the record is renamed or re-slugged. Only if nobody carries
     * that role does it fall back to the parentless person with the most
     * recorded children, which makes the landing tree depend on import order
     * and is therefore a last resort, not the intent.
     */
    public function resolveRoot(?string $slug): ?Muslim
    {
        if (is_string($slug) && $slug !== '') {
            $root = $this->muslims->findBySlug($slug, publishedOnly: false);

            if ($root !== null) {
                return $root;
            }
        }

        $prophetId = $this->muslims->findIdByRoleSlug('prophet');

        if ($prophetId !== null) {
            return $this->muslims->findById($prophetId);
        }

        $id = $this->muslims->findMostConnectedRootId();

        return $id === null
            ? $this->muslims->firstById()
            : $this->muslims->findById($id);
    }

    /**
     * Turn the flat depth-ordered list from descendants() into a nested
     * structure a tree view can indent. One extra query for the whole subtree,
     * not one per node, and the depth-ordered sort is preserved because rows
     * are appended in the order they arrive.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    public function nestDescendants(Muslim $root, array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $ids = array_map(static fn (array $row): int => (int) $row['id'], $rows);
        $inSubtree = array_fill_keys([...$ids, $root->id], true);

        $links = $this->muslims->parentLinksFor($ids);

        $childrenByParent = [];

        foreach ($rows as $row) {
            $link = $links->get((int) $row['id']);

            // A node can hang off either parent. Attach it to whichever one is
            // inside this subtree — otherwise a person whose father is outside
            // the rendered depth would be dropped instead of shown.
            $parentId = match (true) {
                isset($inSubtree[(int) $link?->father_id]) => (int) $link->father_id,
                isset($inSubtree[(int) $link?->mother_id]) => (int) $link->mother_id,
                default => $root->id,
            };

            $childrenByParent[$parentId][] = $row;
        }

        return $this->buildBranch($root->id, $childrenByParent);
    }

    /**
     * Corpus-wide tree health, for the explorer's header.
     *
     * @return array{people:int,linked:int,roots:int,edges:int,hidden:int}
     */
    public function stats(): array
    {
        $counts = $this->muslims->treeCounts();

        // `edges` lives in another table, so it stays its own query.
        return [
            'people' => $counts['people'],
            'linked' => $counts['linked'],
            'roots' => $counts['roots'],
            'edges' => $this->relationships->count(),
            'hidden' => $counts['hidden'],
        ];
    }

    /**
     * @param  array<int, array<int, array<string, mixed>>>  $childrenByParent
     * @return array<int, array<string, mixed>>
     */
    private function buildBranch(int $parentId, array $childrenByParent): array
    {
        $branch = [];

        foreach ($childrenByParent[$parentId] ?? [] as $row) {
            $row['children'] = $this->buildBranch((int) $row['id'], $childrenByParent);
            $branch[] = $row;
        }

        return $branch;
    }

    /**
     * The counterpart MODEL, from the id the row itself resolves. Rows are read
     * with `muslim_id = ? OR related_muslim_id = ?`, so either column may hold
     * the person asked about.
     */
    private function counterpartOf(MuslimRelationship $row, int $muslimId): ?Muslim
    {
        return $row->counterpartIdFor($muslimId) === $row->related_muslim_id
            ? $row->relatedMuslim
            : $row->muslim;
    }

    /**
     * @param  array<int, object>  $rows
     * @return array{nodes: array<int, array<string, mixed>>, depth_reached: int, depth_capped: bool}
     */
    private function buildResult(array $rows, int $requestedDepth): array
    {
        $reached = 0;
        foreach ($rows as $row) {
            $reached = max($reached, (int) $row->depth);
        }

        return [
            'nodes' => array_map(static fn (object $row): array => (array) $row, $rows),
            'depth_reached' => $reached,
            // Reported rather than silently truncated (constitution X): a
            // capped tree that looked complete is indistinguishable from a
            // real one, which is exactly the lie to avoid.
            'depth_capped' => $reached >= $requestedDepth,
        ];
    }

    private function cappedDepth(int $requested): int
    {
        return min(max($requested, 1), self::MAX_DEPTH);
    }
}
