<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\Models\Verse;
use App\Domain\Shared\Services\AbstractBulkOperationHandler;

/**
 * Handles all bulk operations for Quran verses.
 * Module key: 'quran_verses'
 */
class VerseBulkHandler extends AbstractBulkOperationHandler
{
    public function getSupportedModule(): string
    {
        return 'quran_verses';
    }

    protected function modelClass(): string
    {
        return Verse::class;
    }
}
