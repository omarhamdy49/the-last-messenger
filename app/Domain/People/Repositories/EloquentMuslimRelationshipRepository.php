<?php

declare(strict_types=1);

namespace App\Domain\People\Repositories;

use App\Domain\People\Models\MuslimRelationship;
use Illuminate\Database\Eloquent\Collection;

final class EloquentMuslimRelationshipRepository implements MuslimRelationshipRepositoryInterface
{
    /** Counterpart columns — enough to render a link, never `SELECT *`. */
    private const COUNTERPART_COLUMNS = 'id,slug,name_ar,name_en,is_published';

    public function forMuslim(int $muslimId): Collection
    {
        return MuslimRelationship::query()
            ->where('muslim_id', $muslimId)
            ->orWhere('related_muslim_id', $muslimId)
            ->with([
                'muslim:'.self::COUNTERPART_COLUMNS,
                'relatedMuslim:'.self::COUNTERPART_COLUMNS,
            ])
            // `id` is the tiebreaker: `sort_order` alone leaves rows sharing a
            // value in whatever order the engine returns them, so the same
            // person's relationship list could reorder between requests.
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function count(): int
    {
        return MuslimRelationship::query()->count();
    }
}
