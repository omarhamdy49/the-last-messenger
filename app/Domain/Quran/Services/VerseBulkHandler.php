<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\Models\Verse;
use App\Domain\Shared\Services\AbstractBulkOperationHandler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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

    /**
     * Verses have no title of any kind — `verse_key` ("2:255") is the identity users
     * recognise.
     */
    protected function previewQuery(array $ids): Builder
    {
        return parent::previewQuery($ids)->addSelect('verse_key');
    }

    protected function previewLabel(Model $item): string
    {
        return (string) $item->verse_key;
    }
}
