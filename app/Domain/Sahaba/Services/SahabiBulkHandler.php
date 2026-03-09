<?php

declare(strict_types=1);

namespace App\Domain\Sahaba\Services;

use App\Domain\Sahaba\Models\Sahabi;
use App\Domain\Shared\Services\AbstractBulkOperationHandler;

/**
 * Handles all bulk operations for Sahaba (Companions) records.
 * Module key: 'sahaba'
 */
class SahabiBulkHandler extends AbstractBulkOperationHandler
{
    public function getSupportedModule(): string
    {
        return 'sahaba';
    }

    protected function modelClass(): string
    {
        return Sahabi::class;
    }
}
