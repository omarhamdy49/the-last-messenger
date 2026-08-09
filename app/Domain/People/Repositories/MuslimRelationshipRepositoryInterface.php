<?php

declare(strict_types=1);

namespace App\Domain\People\Repositories;

use App\Domain\People\Models\MuslimRelationship;
use Illuminate\Database\Eloquent\Collection;

interface MuslimRelationshipRepositoryInterface
{
    /**
     * Every non-descent edge touching this person, from BOTH sides — a
     * relationship is stored once, so the known person may sit in either
     * column. Counterparts on both sides are eager-loaded because the caller
     * cannot know which side it will need until it reads the row.
     *
     * @return Collection<int, MuslimRelationship>
     */
    public function forMuslim(int $muslimId): Collection;

    /** Total number of stored edges. */
    public function count(): int;
}
