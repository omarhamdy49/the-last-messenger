<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Services;

use App\Domain\Hadith\Exporters\HadithExporter;
use App\Domain\Shared\Contracts\ExportableModuleInterface;
use App\Domain\Shared\Exporters\BaseExporter;

/**
 * Implements ExportableModuleInterface for the 'hadith' module, keeping
 * ExportGateway and the export controller free of Hadith internals.
 *
 * There is deliberately no scholars exporter: ExportModule no longer carries a
 * hadith_narrators case, and adding one without a verified column set would ship
 * an export of empty biography fields.
 */
class HadithExportAdapter implements ExportableModuleInterface
{
    public function getModuleKey(): string
    {
        return 'hadith';
    }

    /** @return array<string> */
    public function getSupportedFormats(): array
    {
        return ['csv', 'xlsx'];
    }

    public function buildExporter(string $language, array $filters, array $columns): BaseExporter
    {
        return new HadithExporter($language, $filters, $columns);
    }

    /** @return array<string> */
    public function getAvailableColumns(): array
    {
        return (new HadithExporter)->getAvailableColumns();
    }

    /** @return array<string> */
    public function getDefaultColumns(): array
    {
        return (new HadithExporter)->getDefaultColumns();
    }
}
