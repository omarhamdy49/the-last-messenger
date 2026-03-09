<?php

declare(strict_types=1);

namespace App\Domain\Blog\Services;

use App\Domain\Shared\Services\AbstractBulkOperationHandler;
use App\Models\Article;

/**
 * Handles all bulk operations for Blog articles.
 * Module key: 'articles'
 */
class ArticleBulkHandler extends AbstractBulkOperationHandler
{
    public function getSupportedModule(): string
    {
        return 'articles';
    }

    protected function modelClass(): string
    {
        return Article::class;
    }
}
