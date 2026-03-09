<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Services;

use App\Domain\Seerah\Exporters\SeerahEventExporter;
use App\Domain\Seerah\Repositories\SeerahEventRepositoryInterface;
use App\Domain\Shared\Contracts\ExportableModuleInterface;
use App\Domain\Shared\Exporters\BaseExporter;

/**
 * SeerahEventExportAdapter
 *
 * Implements ExportableModuleInterface for the seerah_events module.
 * Wraps SeerahEventExporter, keeping ExportGateway and the controller
 * free from direct Seerah-repository dependencies.
 */
class SeerahEventExportAdapter implements ExportableModuleInterface
{
    public function __construct(
        private readonly SeerahEventRepositoryInterface $seerahEventRepository,
    ) {}

    public function getModuleKey(): string
    {
        return 'seerah_events';
    }

    public function getSupportedFormats(): array
    {
        return ['csv', 'xlsx'];
    }

    public function buildExporter(string $language, array $filters, array $columns): BaseExporter
    {
        return new SeerahEventExporter(
            seerahEventRepository: $this->seerahEventRepository,
            language: $language,
            filters: $filters,
            selectedColumns: $columns,
        );
    }

    public function getAvailableColumns(): array
    {
        return (new SeerahEventExporter($this->seerahEventRepository))->getAvailableColumns();
    }

    public function getDefaultColumns(): array
    {
        return (new SeerahEventExporter($this->seerahEventRepository))->getDefaultColumns();
    }
}
