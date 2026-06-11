<?php

declare(strict_types=1);

namespace App\Domain\Media\Services;

use App\Domain\Media\Models\MediaProgram;
use App\Domain\Shared\Services\AbstractBulkOperationHandler;

/**
 * Handles all bulk operations for MediaProgram records.
 * Module key: 'media_programs'
 */
class MediaProgramBulkHandler extends AbstractBulkOperationHandler
{
    public function getSupportedModule(): string
    {
        return 'media_programs';
    }

    protected function modelClass(): string
    {
        return MediaProgram::class;
    }
}
