<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exporters;

use App\Domain\Shared\Enums\ExportFormat;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Base Exporter Class
 * Abstract class that all exporters must extend
 */
abstract class BaseExporter implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    use Exportable;

    /**
     * Selected language for headers
     */
    protected string $language = 'en';

    /**
     * Filters to apply
     *
     * @var array<string, mixed>
     */
    protected array $filters = [];

    /**
     * Selected columns to export
     *
     * @var array<string>
     */
    protected array $selectedColumns = [];

    /**
     * Constructor
     *
     * @param  array<string, mixed>  $filters
     * @param  array<string>  $selectedColumns
     */
    public function __construct(string $language = 'en', array $filters = [], array $selectedColumns = [])
    {
        $this->language = $language;
        $this->filters = $filters;
        $this->selectedColumns = empty($selectedColumns) ? $this->getDefaultColumns() : $selectedColumns;
    }

    /**
     * Get the collection to export
     * Must be implemented by child classes
     */
    abstract public function collection(): Collection;

    /**
     * Get column headers
     * Must be implemented by child classes
     *
     * @return array<string>
     */
    abstract public function headings(): array;

    /**
     * Map each row
     * Must be implemented by child classes
     *
     * @param  mixed  $row
     * @return array<mixed>
     */
    abstract public function map($row): array;

    /**
     * Get available columns for export
     * Must be implemented by child classes
     *
     * @return array<string>
     */
    abstract public function getAvailableColumns(): array;

    /**
     * Get default columns if none selected
     * Must be implemented by child classes
     *
     * @return array<string>
     */
    abstract public function getDefaultColumns(): array;

    /**
     * Apply styles to worksheet
     *
     * @param  Worksheet  $sheet
     * @return array<int|string, mixed>
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0056b3'],
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true],
            ],
        ];
    }

    /**
     * Get translation helper
     *
     * @param  array<string, string>  $translations
     */
    protected function getTranslation(array $translations): string
    {
        return $translations[$this->language] ?? $translations['en'] ?? '';
    }

    /**
     * Format multi-language field
     *
     * @param  array<string, string>|string|null  $field
     */
    protected function formatMultiLanguageField(mixed $field): string
    {
        if (is_null($field)) {
            return '';
        }

        if (is_string($field)) {
            return $field;
        }

        if (is_array($field)) {
            return $field[$this->language] ?? $field['en'] ?? $field['ar'] ?? '';
        }

        return '';
    }

    /**
     * Format date field
     */
    protected function formatDate(mixed $date): string
    {
        if (is_null($date)) {
            return '';
        }

        if ($date instanceof \Carbon\Carbon) {
            return $date->format('Y-m-d H:i:s');
        }

        return (string) $date;
    }

    /**
     * Format boolean field
     */
    protected function formatBoolean(?bool $value): string
    {
        if (is_null($value)) {
            return '';
        }

        return $value ? 'Yes' : 'No';
    }

    /**
     * Format array field (comma-separated)
     *
     * @param  array<mixed>|null  $array
     */
    protected function formatArray(?array $array): string
    {
        if (is_null($array) || empty($array)) {
            return '';
        }

        return implode(', ', $array);
    }

    /**
     * Truncate text
     */
    protected function truncate(?string $text, int $length = 100): string
    {
        if (is_null($text)) {
            return '';
        }

        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return mb_substr($text, 0, $length).'...';
    }

    /**
     * Export to file
     */
    public function exportToFile(ExportFormat $format, string $filename): string
    {
        $extension = $format->getExtension();
        $fullFilename = $filename.'.'.$extension;
        $path = storage_path('app/exports/'.$fullFilename);

        // Ensure exports directory exists
        if (! file_exists(storage_path('app/exports'))) {
            mkdir(storage_path('app/exports'), 0755, true);
        }

        // Export based on format
        if ($format === ExportFormat::CSV) {
            $this->store($fullFilename, 'exports', \Maatwebsite\Excel\Excel::CSV);
        } else {
            $this->store($fullFilename, 'exports', \Maatwebsite\Excel\Excel::XLSX);
        }

        return $path;
    }
}
