<?php

declare(strict_types=1);

namespace App\Domain\Blog\Services;

use App\Domain\Shared\Services\AbstractBulkOperationHandler;
use App\Models\Article;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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

    /**
     * `articles` is the one table here that still has a real `title` column — Spatie
     * HasTranslations over a JSON column, since Blog is mid-migration to article_locale.
     * Read it through getTranslation() rather than `$item->title`, which would hand back
     * the raw JSON if the locale is missing.
     */
    protected function previewQuery(array $ids): Builder
    {
        return parent::previewQuery($ids)->addSelect('title');
    }

    protected function previewLabel(Model $item): string
    {
        return $item->getTranslation('title', app()->getLocale()) ?: '#'.$item->getKey();
    }
}
