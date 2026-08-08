<?php

declare(strict_types=1);

namespace App\Domain\People\Services;

use App\Domain\People\Enums\RelationshipType;
use App\Domain\People\Models\MuslimRelationship;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Writes non-descent relationships.
 *
 * The one thing this class exists for: `uq_muslim_relationships` treats
 * (A, B, sibling) and (B, A, sibling) as DISTINCT rows, so the unique index
 * alone cannot stop the same relationship being entered twice from opposite
 * ends. Normalising pair order on write is what makes the index effective.
 * Constraint plus service — neither is sufficient alone.
 */
final class MuslimRelationshipService
{
    public function create(
        int $muslimId,
        int $relatedMuslimId,
        RelationshipType $type,
        ?string $notes = null,
        int $sortOrder = 0,
        bool $hideFromTree = false,
    ): MuslimRelationship {
        if ($muslimId === $relatedMuslimId) {
            throw new ModelNotFoundException(__('sahaba.errors.self_relationship'));
        }

        [$near, $far] = $this->orderPair($muslimId, $relatedMuslimId, $type);

        return MuslimRelationship::create([
            'muslim_id' => $near,
            'related_muslim_id' => $far,
            'relationship_type' => $type,
            'notes' => $notes,
            'sort_order' => $sortOrder,
            'hide_from_tree' => $hideFromTree,
        ]);
    }

    /**
     * Edit an existing edge.
     *
     * The pair order is re-normalised because the type may have changed: a row
     * stored as (B, A, uncle) is legal, but the same pair as `sibling` must be
     * (A, B) or the unique index stops catching a duplicate entered from the
     * other end. Direction is preserved for the directional types.
     */
    public function update(
        MuslimRelationship $relationship,
        RelationshipType $type,
        ?string $notes = null,
        int $sortOrder = 0,
        bool $hideFromTree = false,
    ): MuslimRelationship {
        [$near, $far] = $this->orderPair(
            $relationship->muslim_id,
            $relationship->related_muslim_id,
            $type,
        );

        $relationship->update([
            'muslim_id' => $near,
            'related_muslim_id' => $far,
            'relationship_type' => $type,
            'notes' => $notes,
            'sort_order' => $sortOrder,
            'hide_from_tree' => $hideFromTree,
        ]);

        return $relationship;
    }

    public function delete(MuslimRelationship $relationship): void
    {
        $relationship->delete();
    }

    /**
     * Whether this pair already carries this type, in EITHER stored direction.
     *
     * The unique index cannot answer this on its own — (A,B,sibling) and
     * (B,A,sibling) are distinct rows to MySQL — so validation has to ask in
     * both directions to turn a would-be 500 into a field error.
     */
    public function existsBetween(
        int $muslimId,
        int $relatedMuslimId,
        RelationshipType $type,
        ?int $ignoreId = null,
    ): bool {
        return MuslimRelationship::query()
            ->where('relationship_type', $type->value)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where(function ($query) use ($muslimId, $relatedMuslimId): void {
                $query
                    ->where(fn ($q) => $q
                        ->where('muslim_id', $muslimId)
                        ->where('related_muslim_id', $relatedMuslimId))
                    ->orWhere(fn ($q) => $q
                        ->where('muslim_id', $relatedMuslimId)
                        ->where('related_muslim_id', $muslimId));
            })
            ->exists();
    }

    /**
     * Lower id first for the fully symmetric types, so the pair has exactly one
     * storable form and the unique index can catch a duplicate entered from
     * either side.
     *
     * `uncle` and `aunt` are directional in the row even though the edge is
     * symmetric — "A is B's uncle" is not "B is A's uncle" — so their stored
     * order is meaningful and must be preserved as given.
     *
     * @return array{int, int}
     */
    private function orderPair(int $muslimId, int $relatedMuslimId, RelationshipType $type): array
    {
        if (in_array($type, [RelationshipType::Uncle, RelationshipType::Aunt], true)) {
            return [$muslimId, $relatedMuslimId];
        }

        return $muslimId <= $relatedMuslimId
            ? [$muslimId, $relatedMuslimId]
            : [$relatedMuslimId, $muslimId];
    }
}
