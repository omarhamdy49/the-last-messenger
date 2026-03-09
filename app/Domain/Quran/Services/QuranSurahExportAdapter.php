<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\Exporters\SurahExporter;
use App\Domain\Quran\Repositories\SurahRepositoryInterface;
use App\Domain\Shared\Contracts\ExportableModuleInterface;
use App\Domain\Shared\Exporters\BaseExporter;

/**
 * QuranSurahExportAdapter
 *
 * Implements ExportableModuleInterface for the quran_surahs module.
 * Wraps SurahExporter, keeping ExportGateway and the controller
 * free from direct Quran-repository dependencies.
 */
class QuranSurahExportAdapter implements ExportableModuleInterface
{
    public function __construct(
        private readonly SurahRepositoryInterface $surahRepository,
    ) {}

    public function getModuleKey(): string
    {
        return 'quran_surahs';
    }

    public function getSupportedFormats(): array
    {
        return ['csv', 'xlsx'];
    }

    public function buildExporter(string $language, array $filters, array $columns): BaseExporter
    {
        return new SurahExporter(
            surahRepository: $this->surahRepository,
            language: $language,
            filters: $filters,
            selectedColumns: $columns,
        );
    }

    public function getAvailableColumns(): array
    {
        return (new SurahExporter($this->surahRepository))->getAvailableColumns();
    }

    public function getDefaultColumns(): array
    {
        return (new SurahExporter($this->surahRepository))->getDefaultColumns();
    }
}
