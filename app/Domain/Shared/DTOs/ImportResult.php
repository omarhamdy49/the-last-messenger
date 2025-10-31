<?php

declare(strict_types=1);

namespace App\Domain\Shared\DTOs;

/**
 * Import Result DTO
 *
 * Encapsulates the result of an import operation
 */
class ImportResult
{
    /**
     * Constructor
     *
     * @param  int  $totalRows  Total number of rows processed
     * @param  int  $successCount  Number of successfully imported rows
     * @param  int  $errorCount  Number of rows with errors
     * @param  int  $skippedCount  Number of skipped rows (duplicates)
     * @param  array<ImportError>  $errors  List of import errors
     * @param  array<string, mixed>  $metadata  Additional metadata
     */
    public function __construct(
        public readonly int $totalRows,
        public readonly int $successCount,
        public readonly int $errorCount,
        public readonly int $skippedCount,
        public readonly array $errors,
        public readonly array $metadata = []
    ) {}

    /**
     * Check if import was successful
     */
    public function isSuccessful(): bool
    {
        return $this->errorCount === 0;
    }

    /**
     * Check if import was partially successful
     */
    public function isPartiallySuccessful(): bool
    {
        return $this->successCount > 0 && $this->errorCount > 0;
    }

    /**
     * Get success rate percentage
     */
    public function getSuccessRate(): float
    {
        if ($this->totalRows === 0) {
            return 0.0;
        }

        return round(($this->successCount / $this->totalRows) * 100, 2);
    }

    /**
     * Convert to array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'total_rows' => $this->totalRows,
            'success_count' => $this->successCount,
            'error_count' => $this->errorCount,
            'skipped_count' => $this->skippedCount,
            'success_rate' => $this->getSuccessRate(),
            'is_successful' => $this->isSuccessful(),
            'is_partially_successful' => $this->isPartiallySuccessful(),
            'errors' => array_map(fn (ImportError $error) => $error->toArray(), $this->errors),
            'metadata' => $this->metadata,
        ];
    }
}
