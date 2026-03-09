<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\Exporters\VerseExporter;
use App\Domain\Quran\Repositories\VerseRepositoryInterface;
use App\Domain\Shared\Contracts\ExportableModuleInterface;
use App\Domain\Shared\Exporters\BaseExporter;

/**
 * QuranVerseExportAdapter
 *
 * Implements ExportableModuleInterface for the quran_verses module.
 * Wraps VerseExporter, keeping ExportGateway and the controller
 * free from direct Quran-repository dependencies.
 */
class QuranVerseExportAdapter implements ExportableModuleInterface
{
    public function __construct(
        private readonly VerseRepositoryInterface $verseRepository,
    ) {}

    public function getModuleKey(): string
    {
        return 'quran_verses';
    }

    public function getSupportedFormats(): array
    {
        return ['csv', 'xlsx'];
    }

    public function buildExporter(string $language, array $filters, array $columns): BaseExporter
    {
        return new VerseExporter(
            verseRepository: $this->verseRepository,
            language: $language,
            filters: $filters,
            selectedColumns: $columns,
        );
    }

    public function getAvailableColumns(): array
    {
        return (new VerseExporter($this->verseRepository))->getAvailableColumns();
    }

    public function getDefaultColumns(): array
    {
        return (new VerseExporter($this->verseRepository))->getDefaultColumns();
    }
}
