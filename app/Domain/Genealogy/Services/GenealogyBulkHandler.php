<?php

declare(strict_types=1);

namespace App\Domain\Genealogy\Services;

use App\Domain\Genealogy\Models\Person;
use App\Domain\Shared\Services\AbstractBulkOperationHandler;

/**
 * Handles all bulk operations for Genealogy person records.
 * Module key: 'genealogy'
 */
class GenealogyBulkHandler extends AbstractBulkOperationHandler
{
    public function getSupportedModule(): string
    {
        return 'genealogy';
    }

    protected function modelClass(): string
    {
        return Person::class;
    }
}
