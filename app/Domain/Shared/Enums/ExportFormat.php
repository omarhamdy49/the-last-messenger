<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

/**
 * Export Format Enum
 * Defines supported export formats
 */
enum ExportFormat: string
{
    case CSV = 'csv';
    case EXCEL = 'xlsx';

    /**
     * Get all values as array
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get MIME type for format
     */
    public function getMimeType(): string
    {
        return match ($this) {
            self::CSV => 'text/csv',
            self::EXCEL => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        };
    }

    /**
     * Get file extension
     */
    public function getExtension(): string
    {
        return $this->value;
    }
}
