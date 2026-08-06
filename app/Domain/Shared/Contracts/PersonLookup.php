<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

/**
 * Resolves person ids to {@see PersonReference} values.
 *
 * `hadith_collections.compiler_id` and `hadith_narrators.narrator_id` are
 * foreign keys into `muslims`, which is legitimate — database keys may cross
 * module boundaries. What may NOT cross is the PHP import: constitution IV
 * forbids `App\Domain\Hadith` from importing `App\Domain\People`, and deptrac
 * enforces it.
 *
 * Hadith therefore never holds an Eloquent relation to a person. It asks this
 * contract instead, and the People module supplies the implementation.
 */
interface PersonLookup
{
    /**
     * @param  array<int, int>  $ids
     * @return array<int, PersonReference> keyed by person id
     */
    public function byIds(array $ids): array;

    public function byId(int $id): ?PersonReference;
}
