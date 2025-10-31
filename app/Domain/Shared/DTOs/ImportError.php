<?php

declare(strict_types=1);

namespace App\Domain\Shared\DTOs;

/**
 * Import Error DTO
 *
 * Represents a single import validation or processing error
 */
class ImportError
{
    /**
     * Constructor
     *
     * @param  int  $rowNumber  Row number in the import file
     * @param  string|null  $fieldName  Field name that caused the error
     * @param  string  $errorMessage  Description of the error
     * @param  string  $severity  Severity level: 'error' or 'warning'
     * @param  array<string, mixed>|null  $rowData  Data from the problematic row
     */
    public function __construct(
        public readonly int $rowNumber,
        public readonly ?string $fieldName,
        public readonly string $errorMessage,
        public readonly string $severity = 'error',
        public readonly ?array $rowData = null
    ) {}

    /**
     * Check if this is an error (not a warning)
     */
    public function isError(): bool
    {
        return $this->severity === 'error';
    }

    /**
     * Check if this is a warning
     */
    public function isWarning(): bool
    {
        return $this->severity === 'warning';
    }

    /**
     * Convert to array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'row_number' => $this->rowNumber,
            'field_name' => $this->fieldName,
            'error_message' => $this->errorMessage,
            'severity' => $this->severity,
            'row_data' => $this->rowData,
        ];
    }
}
