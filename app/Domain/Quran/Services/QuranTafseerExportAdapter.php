<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\Exporters\TafseerExporter;
use App\Domain\Quran\Repositories\TafseerRepositoryInterface;
use App\Domain\Shared\Contracts\ExportableModuleInterface;
use App\Domain\Shared\Exporters\BaseExporter;

/**
 * QuranTafseerExportAdapter
 *
 * Implements ExportableModuleInterface for the quran_tafseer module.
 * Wraps TafseerExporter, keeping ExportGateway and the controller
 * free from direct Quran-repository dependencies.
 */
class QuranTafseerExportAdapter implements ExportableModuleInterface
{
    public function __construct(
        private readonly TafseerRepositoryInterface $tafseerRepository,
    ) {}

    public function getModuleKey(): string
    {
        return 'quran_tafseer';
    }

    public function getSupportedFormats(): array
    {
        return ['csv', 'xlsx'];
    }

    public function buildExporter(string $language, array $filters, array $columns): BaseExporter
    {
        return new TafseerExporter(
            tafseerRepository: $this->tafseerRepository,
            language: $language,
            filters: $filters,
            selectedColumns: $columns,
        );
    }

    public function getAvailableColumns(): array
    {
        return (new TafseerExporter($this->tafseerRepository))->getAvailableColumns();
    }

    public function getDefaultColumns(): array
    {
        return (new TafseerExporter($this->tafseerRepository))->getDefaultColumns();
    }
}
