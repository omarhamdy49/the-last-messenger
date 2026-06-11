<?php

declare(strict_types=1);

namespace App\Domain\Media\Exporters;

use App\Domain\Media\Repositories\MediaProgramRepositoryInterface;
use App\Domain\Shared\Exporters\BaseExporter;
use Illuminate\Support\Collection;

/**
 * Exports MediaProgram records to CSV / XLSX.
 */
class MediaProgramExporter extends BaseExporter
{
    public function __construct(
        protected MediaProgramRepositoryInterface $programRepository,
        string $language = 'en',
        array $filters = [],
        array $selectedColumns = []
    ) {
        parent::__construct($language, $filters, $selectedColumns);
    }

    public function collection(): Collection
    {
        return $this->programRepository->all();
    }

    public function map($program): array
    {
        $row = [];

        foreach ($this->selectedColumns as $column) {
            $row[] = match ($column) {
                'id' => $program->id,
                'slug' => $program->slug,
                'title' => $program->getTranslation('title', $this->language) ?? '',
                'description' => $this->truncate($program->getTranslation('description', $this->language) ?? '', 300),
                'category' => $program->category?->value ?? '',
                'year' => $program->year ?? '',
                'rating' => $program->rating,
                'is_featured' => $program->is_featured ? 'Yes' : 'No',
                'is_trending' => $program->is_trending ? 'Yes' : 'No',
                'views_count' => $program->views_count,
                'created_at' => $this->formatDate($program->created_at),
                default => '',
            };
        }

        return $row;
    }

    public function headings(): array
    {
        $map = [
            'id' => ['ar' => 'المعرف',     'en' => 'ID'],
            'slug' => ['ar' => 'الرابط',      'en' => 'Slug'],
            'title' => ['ar' => 'العنوان',     'en' => 'Title'],
            'description' => ['ar' => 'الوصف',       'en' => 'Description'],
            'category' => ['ar' => 'التصنيف',     'en' => 'Category'],
            'year' => ['ar' => 'السنة',        'en' => 'Year'],
            'rating' => ['ar' => 'التقييم',     'en' => 'Rating'],
            'is_featured' => ['ar' => 'مميز',        'en' => 'Featured'],
            'is_trending' => ['ar' => 'رائج',        'en' => 'Trending'],
            'views_count' => ['ar' => 'المشاهدات',   'en' => 'Views'],
            'created_at' => ['ar' => 'تاريخ الإنشاء', 'en' => 'Created At'],
        ];

        return array_map(
            fn ($col) => $this->getTranslation($map[$col] ?? ['en' => $col]),
            $this->selectedColumns
        );
    }

    public function getAvailableColumns(): array
    {
        return ['id', 'slug', 'title', 'description', 'category', 'year', 'rating', 'is_featured', 'is_trending', 'views_count', 'created_at'];
    }

    public function getDefaultColumns(): array
    {
        return ['id', 'title', 'category', 'year', 'rating', 'views_count'];
    }
}
