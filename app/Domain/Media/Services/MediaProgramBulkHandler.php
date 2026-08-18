<?php

declare(strict_types=1);

namespace App\Domain\Media\Services;

use App\Domain\Media\Models\MediaProgram;
use App\Domain\Shared\Services\AbstractBulkOperationHandler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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

    /**
     * `media_programs` has no title column — titles live in media_program_translations.
     * The FK stays in the column list; dropping it would return an empty relation and
     * every label would silently fall back to the id.
     */
    protected function previewQuery(array $ids): Builder
    {
        return parent::previewQuery($ids)
            ->with('translations:id,media_program_id,locale,title');
    }

    protected function previewLabel(Model $item): string
    {
        return $item->getTranslation('title') ?: '#'.$item->getKey();
    }
}
