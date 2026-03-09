<?php

declare(strict_types=1);

namespace App\Domain\Sahaba\Services;

use App\Domain\Sahaba\Exporters\SahabiExporter;
use App\Domain\Sahaba\Repositories\SahabiRepositoryInterface;
use App\Domain\Shared\Contracts\ExportableModuleInterface;
use App\Domain\Shared\Exporters\BaseExporter;

/**
 * SahabiExportAdapter
 *
 * Implements ExportableModuleInterface for the sahaba module.
 * Wraps SahabiExporter, keeping ExportGateway and the controller
 * free from direct Sahaba-repository dependencies.
 */
class SahabiExportAdapter implements ExportableModuleInterface
{
    public function __construct(
        private readonly SahabiRepositoryInterface $sahabiRepository,
    ) {}

    public function getModuleKey(): string
    {
        return 'sahaba';
    }

    public function getSupportedFormats(): array
    {
        return ['csv', 'xlsx'];
    }

    public function buildExporter(string $language, array $filters, array $columns): BaseExporter
    {
        return new SahabiExporter(
            sahabiRepository: $this->sahabiRepository,
            language: $language,
            filters: $filters,
            selectedColumns: $columns,
        );
    }

    public function getAvailableColumns(): array
    {
        return (new SahabiExporter($this->sahabiRepository))->getAvailableColumns();
    }

    public function getDefaultColumns(): array
    {
        return (new SahabiExporter($this->sahabiRepository))->getDefaultColumns();
    }
}
