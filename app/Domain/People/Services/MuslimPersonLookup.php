<?php

declare(strict_types=1);

namespace App\Domain\People\Services;

use App\Domain\People\Models\Muslim;
use App\Domain\Shared\Contracts\PersonLookup;
use App\Domain\Shared\Contracts\PersonReference;

/**
 * The People module's implementation of {@see PersonLookup}. This is how
 * Hadith (and any other module) reaches a person without importing this one.
 */
final class MuslimPersonLookup implements PersonLookup
{
    /**
     * @param  array<int, int>  $ids
     * @return array<int, PersonReference>
     */
    public function byIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids)));

        if ($ids === []) {
            return [];
        }

        // One query for the whole set — callers resolve a chain of narrators
        // at once, and a per-id lookup would be an N+1 by construction.
        return Muslim::query()
            ->select(['id', 'slug', 'name_ar', 'name_en'])
            ->whereIn('id', $ids)
            ->get()
            ->mapWithKeys(fn (Muslim $m): array => [$m->id => $m->toPersonReference()])
            ->all();
    }

    public function byId(int $id): ?PersonReference
    {
        return $this->byIds([$id])[$id] ?? null;
    }
}
