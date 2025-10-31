<?php

declare(strict_types=1);

namespace App\Domain\Media\Exporters;

use App\Domain\Media\Repositories\MediaProgramRepositoryInterface;
use App\Domain\Shared\Exporters\BaseExporter;
use Illuminate\Support\Collection;

/**
 * Media Program Exporter
 * Exports media programs with episode count
 */
class MediaProgramExporter extends BaseExporter
{
    /**
     * Constructor
     */
    public function __construct(
        protected MediaProgramRepositoryInterface $mediaProgramRepository,
        string $language = 'en',
        array $filters = [],
        array $selectedColumns = []
    ) {
        parent::__construct($language, $filters, $selectedColumns);
    }

    /**
     * Get the collection to export
     */
    public function collection(): Collection
    {
        if (! empty($this->filters)) {
            $query = $this->mediaProgramRepository->getModel()::query()
                ->with(['category', 'episodes']);

            if (isset($this->filters['category_id'])) {
                $query->where('category_id', $this->filters['category_id']);
            }

            if (isset($this->filters['status'])) {
                $query->where('status', $this->filters['status']);
            }

            if (isset($this->filters['search'])) {
                $search = $this->filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(title, '$.ar')) LIKE ?", ["%{$search}%"])
                        ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(title, '$.en')) LIKE ?", ["%{$search}%"]);
                });
            }

            return $query->get();
        }

        return $this->mediaProgramRepository->all(['*'], ['category', 'episodes']);
    }

    /**
     * Map each row
     */
    public function map($program): array
    {
        $row = [];

        foreach ($this->selectedColumns as $column) {
            $row[] = match ($column) {
                'id' => $program->id,
                'title' => $this->formatMultiLanguageField($program->title),
                'slug' => $program->slug ?? '',
                'description' => $this->truncate($this->formatMultiLanguageField($program->description), 300),
                'category' => $program->category?->name[$this->language] ?? $program->category?->name['ar'] ?? '',
                'host' => $this->formatMultiLanguageField($program->host),
                'episode_count' => $program->episodes?->count() ?? $program->episode_count ?? 0,
                'total_duration' => $program->total_duration ?? 0,
                'status' => ucfirst($program->status ?? ''),
                'view_count' => $program->view_count ?? 0,
                'is_featured' => $this->formatBoolean($program->is_featured ?? false),
                'release_year' => $program->release_year ?? '',
                'created_at' => $this->formatDate($program->created_at),
                'updated_at' => $this->formatDate($program->updated_at),
                default => '',
            };
        }

        return $row;
    }

    /**
     * Get column headers
     */
    public function headings(): array
    {
        $translations = [
            'id' => ['ar' => 'المعرف', 'en' => 'ID', 'fr' => 'ID', 'tr' => 'Kimlik', 'ur' => 'شناخت'],
            'title' => ['ar' => 'العنوان', 'en' => 'Title', 'fr' => 'Titre', 'tr' => 'Başlık', 'ur' => 'عنوان'],
            'slug' => ['ar' => 'الرابط', 'en' => 'Slug', 'fr' => 'Slug', 'tr' => 'Slug', 'ur' => 'سلگ'],
            'description' => ['ar' => 'الوصف', 'en' => 'Description', 'fr' => 'Description', 'tr' => 'Açıklama', 'ur' => 'تفصیل'],
            'category' => ['ar' => 'التصنيف', 'en' => 'Category', 'fr' => 'Catégorie', 'tr' => 'Kategori', 'ur' => 'قسم'],
            'host' => ['ar' => 'المقدم', 'en' => 'Host', 'fr' => 'Animateur', 'tr' => 'Sunucu', 'ur' => 'میزبان'],
            'episode_count' => ['ar' => 'عدد الحلقات', 'en' => 'Episodes', 'fr' => 'Épisodes', 'tr' => 'Bölüm Sayısı', 'ur' => 'اقساط'],
            'total_duration' => ['ar' => 'المدة الإجمالية', 'en' => 'Total Duration (min)', 'fr' => 'Durée totale', 'tr' => 'Toplam Süre', 'ur' => 'کل دورانیہ'],
            'status' => ['ar' => 'الحالة', 'en' => 'Status', 'fr' => 'Statut', 'tr' => 'Durum', 'ur' => 'حالت'],
            'view_count' => ['ar' => 'المشاهدات', 'en' => 'Views', 'fr' => 'Vues', 'tr' => 'Görüntüleme', 'ur' => 'مناظر'],
            'is_featured' => ['ar' => 'مميز', 'en' => 'Featured', 'fr' => 'En vedette', 'tr' => 'Öne Çıkan', 'ur' => 'نمایاں'],
            'release_year' => ['ar' => 'سنة الإصدار', 'en' => 'Release Year', 'fr' => 'Année de sortie', 'tr' => 'Çıkış Yılı', 'ur' => 'ریلیز سال'],
            'created_at' => ['ar' => 'تاريخ الإنشاء', 'en' => 'Created At', 'fr' => 'Créé le', 'tr' => 'Oluşturulma', 'ur' => 'تخلیق کردہ'],
            'updated_at' => ['ar' => 'تاريخ التحديث', 'en' => 'Updated At', 'fr' => 'Mis à jour le', 'tr' => 'Güncellenme', 'ur' => 'تازہ کاری'],
        ];

        $headers = [];
        foreach ($this->selectedColumns as $column) {
            $headers[] = $this->getTranslation($translations[$column] ?? ['en' => $column]);
        }

        return $headers;
    }

    /**
     * Get available columns for export
     */
    public function getAvailableColumns(): array
    {
        return [
            'id',
            'title',
            'slug',
            'description',
            'category',
            'host',
            'episode_count',
            'total_duration',
            'status',
            'view_count',
            'is_featured',
            'release_year',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * Get default columns if none selected
     */
    public function getDefaultColumns(): array
    {
        return [
            'id',
            'title',
            'category',
            'episode_count',
            'status',
            'view_count',
            'created_at',
        ];
    }
}
