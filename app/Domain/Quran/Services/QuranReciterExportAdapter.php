<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\Exporters\ReciterExporter;
use App\Domain\Quran\Repositories\ReciterRepositoryInterface;
use App\Domain\Shared\Contracts\ExportableModuleInterface;
use App\Domain\Shared\Exporters\BaseExporter;

/**
 * QuranReciterExportAdapter
 *
 * Implements ExportableModuleInterface for the quran_reciters module.
 * Wraps ReciterExporter, keeping ExportGateway and the controller
 * free from direct Quran-repository dependencies.
 */
class QuranReciterExportAdapter implements ExportableModuleInterface
{
    public function __construct(
        private readonly ReciterRepositoryInterface $reciterRepository,
    ) {}

    public function getModuleKey(): string
    {
        return 'quran_reciters';
    }

    public function getSupportedFormats(): array
    {
        return ['csv', 'xlsx'];
    }

    public function buildExporter(string $language, array $filters, array $columns): BaseExporter
    {
        return new ReciterExporter(
            reciterRepository: $this->reciterRepository,
            language: $language,
            filters: $filters,
            selectedColumns: $columns,
        );
    }

    public function getAvailableColumns(): array
    {
        return (new ReciterExporter($this->reciterRepository))->getAvailableColumns();
    }

    public function getDefaultColumns(): array
    {
        return (new ReciterExporter($this->reciterRepository))->getDefaultColumns();
    }
}
