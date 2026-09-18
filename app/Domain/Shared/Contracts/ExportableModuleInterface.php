<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

use App\Domain\Shared\Exporters\BaseExporter;

interface ExportableModuleInterface
{
    /**
     * The module key identifying this exporter — must match an ExportModule enum value.
     * Examples: 'hadith', 'quran_chapters', 'seerah_events', 'sahaba'
     */
    public function getModuleKey(): string;

    /**
     * The export formats supported by this module.
     *
     * @return string[]
     */
    public function getSupportedFormats(): array;

    /**
     * Build and return a configured BaseExporter instance for the given parameters.
     * The adapter is responsible for injecting the correct repository into the exporter.
     */
    public function buildExporter(
        string $language,
        array $filters,
        array $columns,
    ): BaseExporter;

    /**
     * All column identifiers this module can export.
     *
     * @return string[]
     */
    public function getAvailableColumns(): array;

    /**
     * Column identifiers exported when the caller does not specify any.
     *
     * @return string[]
     */
    public function getDefaultColumns(): array;
}
