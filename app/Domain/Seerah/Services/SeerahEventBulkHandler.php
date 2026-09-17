<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Services;

use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Shared\Services\AbstractBulkOperationHandler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Handles all bulk operations for Seerah events.
 * Module key: 'seerah_events'
 */
class SeerahEventBulkHandler extends AbstractBulkOperationHandler
{
    public function getSupportedModule(): string
    {
        return 'seerah_events';
    }

    protected function modelClass(): string
    {
        return SeerahEvent::class;
    }

    /**
     * `seerah_events` has no title column — titles live in seerah_event_translations.
     * Eager-load `title` only; the same rows also carry `text description` and
     * `longText content`, which a confirmation dialog has no use for.
     */
    protected function previewQuery(array $ids): Builder
    {
        return parent::previewQuery($ids)
            ->with('translations:id,event_id,locale,title');
    }

    protected function previewLabel(Model $item): string
    {
        return $item->getTranslation('title') ?: '#'.$item->getKey();
    }
}
