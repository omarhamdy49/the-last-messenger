<?php

declare(strict_types=1);

namespace App\Domain\Blog\Services;

use App\Domain\Blog\Exporters\ArticleExporter;
use App\Domain\Shared\Contracts\ExportableModuleInterface;
use App\Domain\Shared\Exporters\BaseExporter;

/**
 * ArticleExportAdapter
 *
 * Implements ExportableModuleInterface for the articles module.
 * Wraps ArticleExporter, keeping ExportGateway and the controller
 * free from direct Blog-domain and App\Models imports.
 */
class ArticleExportAdapter implements ExportableModuleInterface
{
    public function getModuleKey(): string
    {
        return 'articles';
    }

    public function getSupportedFormats(): array
    {
        return ['csv', 'xlsx'];
    }

    public function buildExporter(string $language, array $filters, array $columns): BaseExporter
    {
        return new ArticleExporter(
            language: $language,
            filters: $filters,
            selectedColumns: $columns,
        );
    }

    public function getAvailableColumns(): array
    {
        return (new ArticleExporter)->getAvailableColumns();
    }

    public function getDefaultColumns(): array
    {
        return (new ArticleExporter)->getDefaultColumns();
    }
}
