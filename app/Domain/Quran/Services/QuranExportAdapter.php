<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\Exporters\QuranTableExporter;
use App\Domain\Quran\Repositories\QuranExportRepositoryInterface;
use App\Domain\Shared\Contracts\ExportableModuleInterface;
use App\Domain\Shared\Exporters\BaseExporter;

/**
 * Export adapter for one Quran module key (see QuranTableExporter::COLUMNS).
 */
final class QuranExportAdapter implements ExportableModuleInterface
{
    public function __construct(
        private readonly QuranExportRepositoryInterface $repository,
        private readonly string $module,
    ) {}

    /**
     * @return list<self>
     */
    public static function all(QuranExportRepositoryInterface $repository): array
    {
        return array_map(static fn (string $module): self => new self($repository, $module), array_keys(QuranTableExporter::COLUMNS));
    }

    public function getModuleKey(): string
    {
        return $this->module;
    }

    public function getSupportedFormats(): array
    {
        return ['csv', 'xlsx'];
    }

    public function buildExporter(string $language, array $filters, array $columns): BaseExporter
    {
        return new QuranTableExporter($this->repository, $this->module, $language, $filters, $columns);
    }

    public function getAvailableColumns(): array
    {
        return QuranTableExporter::COLUMNS[$this->module];
    }

    public function getDefaultColumns(): array
    {
        return QuranTableExporter::COLUMNS[$this->module];
    }
}
