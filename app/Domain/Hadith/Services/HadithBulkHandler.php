<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Services;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Shared\Services\AbstractBulkOperationHandler;

/**
 * Bulk operations for hadiths. Module key: 'hadith'.
 *
 * Constructed with `new` by SharedServiceProvider, so it must stay no-arg.
 */
class HadithBulkHandler extends AbstractBulkOperationHandler
{
    private const PREVIEW_LIMIT = 10;

    public function getSupportedModule(): string
    {
        return 'hadith';
    }

    protected function modelClass(): string
    {
        return Hadith::class;
    }

    /**
     * The base implementation selects `title` and `name`; `hadith_hadiths` has
     * neither, so it would fail on an unknown column. A hadith's human label is
     * its canonical reference, which needs the collection code.
     *
     * @param  int[]  $ids
     * @return array<int, array{id: int, title: string}>
     */
    public function preview(array $ids): array
    {
        return Hadith::query()
            ->select(['id', 'collection_id', 'hadith_number'])
            ->with('collection:id,code')
            ->whereIn('id', array_slice($ids, 0, self::PREVIEW_LIMIT))
            ->get()
            ->map(static fn (Hadith $hadith): array => [
                'id' => $hadith->id,
                'title' => ($hadith->collection?->code ?? '?').':'.$hadith->hadith_number,
            ])
            ->all();
    }
}
