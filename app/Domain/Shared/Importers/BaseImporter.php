<?php

declare(strict_types=1);

namespace App\Domain\Shared\Importers;

use App\Domain\Shared\DTOs\ImportError;
use App\Domain\Shared\DTOs\ImportResult;
use App\Domain\Shared\Models\Import;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

/**
 * Base Importer
 *
 * Abstract base class for all importers with common functionality
 */
abstract class BaseImporter
{
    /**
     * The import model instance
     */
    protected Import $import;

    /**
     * Parsed data rows
     *
     * @var array<int, array<string, mixed>>
     */
    protected array $rows = [];

    /**
     * Import errors collection
     *
     * @var array<ImportError>
     */
    protected array $errors = [];

    /**
     * Import options
     *
     * @var array<string, mixed>
     */
    protected array $options = [];

    /**
     * Current row being processed (for error reporting)
     */
    protected int $currentRow = 0;

    /**
     * Success counter
     */
    protected int $successCount = 0;

    /**
     * Skipped counter (duplicates)
     */
    protected int $skippedCount = 0;

    /**
     * Constructor
     */
    public function __construct(Import $import)
    {
        $this->import = $import;
        $this->options = $import->options ?? [];
    }

    /**
     * Get validation rules for the import data
     *
     * @return array<string, mixed>
     */
    abstract protected function getValidationRules(): array;

    /**
     * Get column mapping configuration
     * Maps CSV column names to database field names
     *
     * @return array<string, string>
     */
    abstract protected function getColumnMapping(): array;

    /**
     * Import a single row
     *
     * @param  array<string, mixed>  $row
     * @return mixed The created/updated model
     */
    abstract protected function importRow(array $row): mixed;

    /**
     * Check if row is duplicate
     *
     * @param  array<string, mixed>  $row
     */
    abstract protected function isDuplicate(array $row): bool;

    /**
     * Parse the import file and load data
     *
     * @return array{success: bool, message: string, preview: array}
     */
    public function parseFile(): array
    {
        try {
            $this->import->update(['status' => 'parsing']);

            $data = Excel::toArray(new \stdClass, $this->import->file_path);

            if (empty($data) || empty($data[0])) {
                return [
                    'success' => false,
                    'message' => 'File is empty or could not be parsed',
                    'preview' => [],
                ];
            }

            $sheet = $data[0];
            $hasHeader = $this->options['has_header'] ?? true;

            if ($hasHeader && count($sheet) > 0) {
                $headers = array_shift($sheet);
                $headers = $this->normalizeHeaders($headers);
                $this->rows = $this->mapRowsToHeaders($headers, $sheet);
            } else {
                // No headers, use column mapping directly
                $this->rows = array_map(function ($row) {
                    return $this->mapRowToFields($row);
                }, $sheet);
            }

            // Update import with row count
            $this->import->update([
                'total_rows' => count($this->rows),
                'status' => 'parsed',
            ]);

            return [
                'success' => true,
                'message' => 'File parsed successfully',
                'preview' => array_slice($this->rows, 0, 10),
            ];
        } catch (Throwable $e) {
            Log::error('Import file parsing failed', [
                'import_id' => $this->import->id,
                'error' => $e->getMessage(),
            ]);

            $this->import->markAsFailed('Failed to parse file: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to parse file: '.$e->getMessage(),
                'preview' => [],
            ];
        }
    }

    /**
     * Validate all rows
     *
     * @return array<ImportError>
     */
    public function validate(): array
    {
        $this->errors = [];

        // $rows only lives for the lifetime of the request that parsed the
        // file. Validation arrives in a *later* request against a freshly
        // constructed importer, so without re-parsing (exactly as preview()
        // and import() already do) this loops an empty array and always
        // reports "valid, 0 errors".
        if (empty($this->rows)) {
            $parseResult = $this->parseFile();

            if (! $parseResult['success']) {
                $this->errors[] = new ImportError(
                    rowNumber: 0,
                    fieldName: null,
                    errorMessage: $parseResult['message'],
                    severity: 'error',
                    rowData: null
                );

                return $this->errors;
            }
        }

        $this->import->update(['status' => 'validating']);

        foreach ($this->rows as $index => $row) {
            $rowNumber = $index + 2; // +2 for header row and 1-based indexing
            $this->currentRow = $rowNumber;

            $validator = Validator::make($row, $this->getValidationRules());

            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $field => $messages) {
                    foreach ($messages as $message) {
                        $this->errors[] = new ImportError(
                            rowNumber: $rowNumber,
                            fieldName: $field,
                            errorMessage: $message,
                            severity: 'error',
                            rowData: $row
                        );
                    }
                }
            }

            // Check for duplicates if option enabled
            if ($this->options['skip_duplicates'] ?? false) {
                if ($this->isDuplicate($row)) {
                    $this->errors[] = new ImportError(
                        rowNumber: $rowNumber,
                        fieldName: null,
                        errorMessage: 'Duplicate record detected',
                        severity: 'warning',
                        rowData: $row
                    );
                }
            }
        }

        return $this->errors;
    }

    /**
     * Preview import data (first N rows)
     */
    public function preview(int $limit = 10): array
    {
        if (empty($this->rows)) {
            $this->parseFile();
        }

        return array_slice($this->rows, 0, $limit);
    }

    /**
     * Execute the import
     */
    public function import(): ImportResult
    {
        // Parse if not already done
        if (empty($this->rows)) {
            $parseResult = $this->parseFile();
            if (! $parseResult['success']) {
                throw new \Exception($parseResult['message']);
            }
        }

        // Validate if not in dry-run mode
        if (! ($this->options['dry_run'] ?? false)) {
            $validationErrors = $this->validate();

            // Stop if there are critical errors
            $criticalErrors = array_filter($validationErrors, fn ($e) => $e->isError());
            if (count($criticalErrors) > 0) {
                $this->saveErrors($validationErrors);
                $this->import->markAsFailed('Validation failed with '.count($criticalErrors).' errors');

                return new ImportResult(
                    totalRows: count($this->rows),
                    successCount: 0,
                    errorCount: count($criticalErrors),
                    skippedCount: 0,
                    errors: $validationErrors
                );
            }
        }

        // Start import
        $this->import->markAsStarted();
        $this->successCount = 0;
        $this->skippedCount = 0;

        DB::beginTransaction();

        try {
            foreach ($this->rows as $index => $row) {
                $rowNumber = $index + 2;
                $this->currentRow = $rowNumber;

                try {
                    // Check if duplicate and skip if needed
                    if (($this->options['skip_duplicates'] ?? false) && $this->isDuplicate($row)) {
                        $this->skippedCount++;

                        continue;
                    }

                    // Import the row
                    if (! ($this->options['dry_run'] ?? false)) {
                        $this->importRow($row);
                    }

                    $this->successCount++;
                } catch (Throwable $e) {
                    $this->errors[] = new ImportError(
                        rowNumber: $rowNumber,
                        fieldName: null,
                        errorMessage: 'Import failed: '.$e->getMessage(),
                        severity: 'error',
                        rowData: $row
                    );
                }
            }

            // Commit or rollback based on options
            if ($this->options['dry_run'] ?? false) {
                DB::rollBack();
            } else {
                DB::commit();
            }

            // Save errors to database
            if (! empty($this->errors)) {
                $this->saveErrors($this->errors);
            }

            // Mark as completed
            $this->import->update([
                'success_count' => $this->successCount,
                'error_count' => count($this->errors),
                'skipped_count' => $this->skippedCount,
            ]);

            $this->import->markAsCompleted([
                'success_rate' => $this->import->getSuccessRate(),
                'dry_run' => $this->options['dry_run'] ?? false,
            ]);

            return new ImportResult(
                totalRows: count($this->rows),
                successCount: $this->successCount,
                errorCount: count($this->errors),
                skippedCount: $this->skippedCount,
                errors: $this->errors
            );
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('Import execution failed', [
                'import_id' => $this->import->id,
                'error' => $e->getMessage(),
            ]);

            $this->import->markAsFailed('Import execution failed: '.$e->getMessage());

            throw $e;
        }
    }

    /**
     * Save errors to database
     *
     * @param  array<ImportError>  $errors
     */
    protected function saveErrors(array $errors): void
    {
        $errorData = array_map(function (ImportError $error) {
            return [
                'import_id' => $this->import->id,
                'row_number' => $error->rowNumber,
                'field_name' => $error->fieldName,
                'error_message' => $error->errorMessage,
                'severity' => $error->severity,
                'row_data' => json_encode($error->rowData),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $errors);

        // Insert in chunks to avoid memory issues
        $chunks = array_chunk($errorData, 100);
        foreach ($chunks as $chunk) {
            \App\Domain\Shared\Models\ImportError::insert($chunk);
        }
    }

    /**
     * Normalize header names
     *
     * @param  array<string>  $headers
     * @return array<string>
     */
    protected function normalizeHeaders(array $headers): array
    {
        return array_map(function ($header) {
            // Convert to lowercase, trim, replace spaces with underscores
            return strtolower(trim(str_replace(' ', '_', $header)));
        }, $headers);
    }

    /**
     * Map rows to headers
     *
     * @param  array<string>  $headers
     * @param  array<array>  $rows
     * @return array<array<string, mixed>>
     */
    protected function mapRowsToHeaders(array $headers, array $rows): array
    {
        return array_map(function ($row) use ($headers) {
            return array_combine($headers, array_pad($row, count($headers), null));
        }, $rows);
    }

    /**
     * Map a single row to database fields using column mapping
     *
     * @param  array<mixed>  $row
     * @return array<string, mixed>
     */
    protected function mapRowToFields(array $row): array
    {
        $mapped = [];
        $columnMapping = $this->getColumnMapping();

        foreach ($columnMapping as $csvColumn => $dbField) {
            $columnIndex = array_search($csvColumn, array_keys($columnMapping));
            $mapped[$dbField] = $row[$columnIndex] ?? null;
        }

        return $mapped;
    }

    /**
     * Transform date string to proper format
     */
    protected function transformDate(?string $date, string $format = 'Y-m-d'): ?string
    {
        if (empty($date)) {
            return null;
        }

        try {
            return Carbon::parse($date)->format($format);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Transform boolean value
     */
    protected function transformBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $value = strtolower((string) $value);

        return in_array($value, ['yes', 'true', '1', 'y', 't', 'on']);
    }

    /**
     * Build multi-language array from row
     *
     * @param  array<string, mixed>  $row
     * @return array<string, string>
     */
    protected function buildMultiLanguageField(array $row, string $fieldPrefix): array
    {
        return [
            'ar' => $row[$fieldPrefix.'_ar'] ?? '',
            'en' => $row[$fieldPrefix.'_en'] ?? '',
            'fr' => $row[$fieldPrefix.'_fr'] ?? '',
            'tr' => $row[$fieldPrefix.'_tr'] ?? '',
            'ur' => $row[$fieldPrefix.'_ur'] ?? '',
        ];
    }

    /**
     * Add error to collection
     */
    protected function addError(string $field, string $message, string $severity = 'error'): void
    {
        $this->errors[] = new ImportError(
            rowNumber: $this->currentRow,
            fieldName: $field,
            errorMessage: $message,
            severity: $severity,
            rowData: $this->rows[$this->currentRow - 2] ?? []
        );
    }
}
