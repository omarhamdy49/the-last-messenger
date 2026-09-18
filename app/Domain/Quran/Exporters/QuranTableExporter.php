<?php

declare(strict_types=1);

namespace App\Domain\Quran\Exporters;

use App\Domain\Quran\Repositories\QuranExportRepositoryInterface;
use App\Domain\Shared\Exporters\BaseExporter;
use Illuminate\Support\Collection;

/**
 * One exporter for every Quran module: the module's columns are the row fields,
 * headings come from `quran.export.*` in the export language.
 */
final class QuranTableExporter extends BaseExporter
{
    /** Module => exportable columns (first entries are the defaults). */
    public const COLUMNS = [
        'quran_chapters' => ['id', 'name_arabic', 'name_simple', 'name_localized', 'revelation_place', 'revelation_order', 'verses_count', 'first_page', 'last_page', 'is_published'],
        'quran_verses' => ['id', 'verse_key', 'chapter_id', 'verse_number', 'juz_number', 'hizb_number', 'page_number', 'text_uthmani', 'text_imlaei_simple', 'sajdah_type', 'is_locked'],
        'quran_translations' => ['verse_key', 'resource', 'text', 'is_locked'],
        'quran_tafsirs' => ['verse_key', 'resource', 'text', 'is_locked'],
        'quran_reciters' => ['id', 'kind', 'name', 'style', 'qirat', 'language_iso', 'records_count', 'is_active', 'external_source', 'external_ref_id'],
    ];

    /**
     * @param  array<string, mixed>  $filters
     * @param  list<string>  $selectedColumns
     */
    public function __construct(
        private readonly QuranExportRepositoryInterface $repository,
        private readonly string $module,
        string $language = 'ar',
        array $filters = [],
        array $selectedColumns = [],
    ) {
        parent::__construct($language, $filters + ['locale' => $language], array_values(array_intersect($selectedColumns, self::COLUMNS[$module])));
    }

    public function collection(): Collection
    {
        return $this->repository->rows($this->module, $this->filters);
    }

    /**
     * @param  object  $row
     * @return list<string|int|null>
     */
    public function map($row): array
    {
        return array_map(function (string $column) use ($row): string|int|null {
            $value = $row->{$column} ?? null;

            return match (true) {
                str_starts_with($column, 'is_') => $this->formatBoolean($value === null ? null : (bool) $value),
                $column === 'text' => strip_tags((string) $value),
                default => $value,
            };
        }, $this->selectedColumns);
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return array_map(fn (string $column): string => (string) __('quran.export.'.$column, [], $this->language), $this->selectedColumns);
    }

    public function getAvailableColumns(): array
    {
        return self::COLUMNS[$this->module];
    }

    public function getDefaultColumns(): array
    {
        return self::COLUMNS[$this->module];
    }
}
