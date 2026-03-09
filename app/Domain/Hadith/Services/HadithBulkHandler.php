<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Services;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Shared\Services\AbstractBulkOperationHandler;

/**
 * Handles all bulk operations for Hadith records.
 * Module key: 'hadith'
 */
class HadithBulkHandler extends AbstractBulkOperationHandler
{
    public function getSupportedModule(): string
    {
        return 'hadith';
    }

    protected function modelClass(): string
    {
        return Hadith::class;
    }
}
