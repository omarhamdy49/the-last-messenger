<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Services;

use App\Domain\Hadith\Exporters\HadithExporter;
use App\Domain\Hadith\Repositories\HadithRepositoryInterface;
use App\Domain\Shared\Contracts\ExportableModuleInterface;
use App\Domain\Shared\Exporters\BaseExporter;

/**
 * HadithExportAdapter
 *
 * Implements ExportableModuleInterface for the hadith module.
 * Wraps HadithExporter so ExportGateway can resolve it by module key
 * without importing domain classes into the controller.
 */
class HadithExportAdapter implements ExportableModuleInterface
{
    public function __construct(
        private readonly HadithRepositoryInterface $hadithRepository,
    ) {}

    public function getModuleKey(): string
    {
        return 'hadith';
    }

    public function getSupportedFormats(): array
    {
        return ['csv', 'xlsx'];
    }

    public function buildExporter(string $language, array $filters, array $columns): BaseExporter
    {
        return new HadithExporter(
            hadithRepository: $this->hadithRepository,
            language: $language,
            filters: $filters,
            selectedColumns: $columns,
        );
    }

    public function getAvailableColumns(): array
    {
        return (new HadithExporter($this->hadithRepository))->getAvailableColumns();
    }

    public function getDefaultColumns(): array
    {
        return (new HadithExporter($this->hadithRepository))->getDefaultColumns();
    }
}
