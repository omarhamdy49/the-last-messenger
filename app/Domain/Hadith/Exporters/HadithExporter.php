<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Exporters;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Shared\Exporters\BaseExporter;
use Illuminate\Support\Collection;

/**
 * Exports hadiths flat: one row per hadith, with the requested locale's body and
 * the gradings collapsed into one cell.
 *
 * Bodies live in `hadith_texts`, so the locale is resolved per row with an Arabic
 * fallback — an English-only export would silently drop the ~7% of records that
 * have no English text.
 */
class HadithExporter extends BaseExporter
{
    public function collection(): Collection
    {
        $query = Hadith::query()
            ->select(['id', 'collection_id', 'book_id', 'chapter_id', 'hadith_number',
                'in_book_number', 'display_grade', 'is_published'])
            ->with([
                'collection:id,code,name',
                'book:id,collection_id,book_number,title',
                'texts:id,hadith_id,locale,body,narrated_by',
                'gradings:id,hadith_id,grade,scope,raw_grade',
            ]);

        if (isset($this->filters['collection'])) {
            $query->whereHas('collection', fn ($q) => $q->where('code', $this->filters['collection']));
        }

        if (isset($this->filters['grade'])) {
            $query->where('display_grade', $this->filters['grade']);
        }

        if (isset($this->filters['is_published'])) {
            $query->where('is_published', (bool) $this->filters['is_published']);
        }

        // Bounded on purpose: an unbounded export of ~51,400 rows with three
        // eager loads is an out-of-memory waiting to happen.
        return $query->orderBy('collection_id')->orderBy('in_book_number')
            ->limit((int) ($this->filters['limit'] ?? 5000))
            ->get();
    }

    /** @return array<string> */
    public function headings(): array
    {
        return $this->selectedColumns;
    }

    /**
     * @return array<int, mixed>
     */
    public function map($row): array
    {
        /** @var Hadith $row */
        $text = $row->texts->firstWhere('locale', $this->language)
            ?? $row->texts->firstWhere('locale', 'ar');

        $values = [
            'collection' => $row->collection?->code,
            'book_number' => $row->book?->book_number,
            'hadith_number' => $row->hadith_number,
            'in_book_number' => $row->in_book_number,
            'locale' => $text?->locale,
            'narrated_by' => $text?->narrated_by,
            'body' => $text?->body,
            'display_grade' => $row->display_grade->value,
            'raw_grades' => $row->gradings->pluck('raw_grade')->filter()->implode(' | '),
            'is_published' => $row->is_published ? 1 : 0,
        ];

        return array_map(
            static fn (string $column): mixed => $values[$column] ?? null,
            $this->selectedColumns,
        );
    }

    /** @return array<string> */
    public function getAvailableColumns(): array
    {
        return ['collection', 'book_number', 'hadith_number', 'in_book_number',
            'locale', 'narrated_by', 'body', 'display_grade', 'raw_grades', 'is_published'];
    }

    /** @return array<string> */
    public function getDefaultColumns(): array
    {
        return ['collection', 'book_number', 'hadith_number', 'body', 'display_grade', 'raw_grades'];
    }
}
