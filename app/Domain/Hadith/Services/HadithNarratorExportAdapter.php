<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Services;

use App\Domain\Hadith\Exporters\HadithNarratorExporter;
use App\Domain\Hadith\Repositories\NarratorRepositoryInterface;
use App\Domain\Shared\Contracts\ExportableModuleInterface;
use App\Domain\Shared\Exporters\BaseExporter;

/**
 * HadithNarratorExportAdapter
 *
 * Implements ExportableModuleInterface for the hadith_narrators module.
 * Wraps HadithNarratorExporter, keeping ExportGateway and the controller
 * free from direct narrator-repository dependencies.
 */
class HadithNarratorExportAdapter implements ExportableModuleInterface
{
    public function __construct(
        private readonly NarratorRepositoryInterface $narratorRepository,
    ) {}

    public function getModuleKey(): string
    {
        return 'hadith_narrators';
    }

    public function getSupportedFormats(): array
    {
        return ['csv', 'xlsx'];
    }

    public function buildExporter(string $language, array $filters, array $columns): BaseExporter
    {
        return new HadithNarratorExporter(
            narratorRepository: $this->narratorRepository,
            language: $language,
            filters: $filters,
            selectedColumns: $columns,
        );
    }

    public function getAvailableColumns(): array
    {
        return (new HadithNarratorExporter($this->narratorRepository))->getAvailableColumns();
    }

    public function getDefaultColumns(): array
    {
        return (new HadithNarratorExporter($this->narratorRepository))->getDefaultColumns();
    }
}
