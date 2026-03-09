<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Services;

use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Shared\Services\AbstractBulkOperationHandler;

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
}
