<?php

declare(strict_types=1);

namespace App\Domain\People\Services;

use App\Domain\People\Exceptions\GenealogyCycleException;
use App\Domain\People\Models\Muslim;
use App\Domain\People\Models\MuslimRelationship;
use App\Domain\People\Repositories\MuslimRepositoryInterface;
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

    public function __construct(private readonly MuslimRepositoryInterface $muslims) {}

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
        $rows = MuslimRelationship::query()
            ->where('muslim_id', $root->id)
            ->orWhere('related_muslim_id', $root->id)
            ->with([
                'muslim:id,slug,name_ar,name_en,is_published',
                'relatedMuslim:id,slug,name_ar,name_en,is_published',
            ])
            ->orderBy('sort_order')
            ->get();

        $relations = [];

        foreach ($rows as $row) {
            if ($publishedOnly && $row->hide_from_tree) {
                continue;
            }

            $counterpart = $row->muslim_id === $root->id ? $row->relatedMuslim : $row->muslim;

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
