<?php

declare(strict_types=1);

namespace App\Domain\Media\Services;

use App\Domain\Media\Exporters\MediaProgramExporter;
use App\Domain\Media\Repositories\MediaProgramRepositoryInterface;
use App\Domain\Shared\Contracts\ExportableModuleInterface;
use App\Domain\Shared\Exporters\BaseExporter;

class MediaProgramExportAdapter implements ExportableModuleInterface
{
    public function __construct(
        private readonly MediaProgramRepositoryInterface $programRepository,
    ) {}

    public function getModuleKey(): string
    {
        return 'media_programs';
    }

    public function getSupportedFormats(): array
    {
        return ['csv', 'xlsx'];
    }

    public function buildExporter(string $language, array $filters, array $columns): BaseExporter
    {
        return new MediaProgramExporter(
            programRepository: $this->programRepository,
            language: $language,
            filters: $filters,
            selectedColumns: $columns,
        );
    }

    public function getAvailableColumns(): array
    {
        return (new MediaProgramExporter($this->programRepository))->getAvailableColumns();
    }

    public function getDefaultColumns(): array
    {
        return (new MediaProgramExporter($this->programRepository))->getDefaultColumns();
    }
}
