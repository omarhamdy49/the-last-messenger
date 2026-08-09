<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Exporters;

use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Seerah\Repositories\SeerahEventRepositoryInterface;
use App\Domain\Shared\Exporters\BaseExporter;
use Illuminate\Support\Collection;

/**
 * Seerah Event Exporter
 * Exports timeline events
 */
class SeerahEventExporter extends BaseExporter
{
    /**
     * Constructor
     */
    public function __construct(
        protected SeerahEventRepositoryInterface $seerahEventRepository,
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
        return $this->seerahEventRepository->getForExport($this->filters);
    }

    /**
     * Map each row
     *
     * @param  SeerahEvent  $event
     */
    public function map($event): array
    {
        $row = [];

        foreach ($this->selectedColumns as $column) {
            $row[] = match ($column) {
                'id' => $event->id,
                'title' => $event->getTranslation('title', $this->language) ?? '',
                'slug' => $event->slug ?? '',
                'event_date_gregorian' => $event->gregorian_date?->toDateString() ?? '',
                'event_date_hijri' => $event->hijri_date ?? '',
                'event_type' => $event->type?->getTranslation('name', $this->language) ?? '',
                'location' => $event->location ?? '',
                'description' => $this->truncate($event->getTranslation('description', $this->language), 300),
                'participants' => $event->participants
                    ->map(fn ($participant) => $participant->getTranslation('name', $this->language))
                    ->filter()
                    ->implode(', '),
                'importance' => $event->importance?->label() ?? '',
                'latitude' => $event->latitude ?? '',
                'longitude' => $event->longitude ?? '',
                'status' => $event->status?->label() ?? '',
                'created_at' => $this->formatDate($event->created_at),
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
            'event_date_gregorian' => ['ar' => 'التاريخ الميلادي', 'en' => 'Date (Gregorian)', 'fr' => 'Date grégorienne', 'tr' => 'Miladi Tarih', 'ur' => 'عیسوی تاریخ'],
            'event_date_hijri' => ['ar' => 'التاريخ الهجري', 'en' => 'Date (Hijri)', 'fr' => 'Date hégirien', 'tr' => 'Hicri Tarih', 'ur' => 'ہجری تاریخ'],
            'event_type' => ['ar' => 'نوع الحدث', 'en' => 'Event Type', 'fr' => 'Type d\'événement', 'tr' => 'Olay Türü', 'ur' => 'واقعہ کی قسم'],
            'location' => ['ar' => 'المكان', 'en' => 'Location', 'fr' => 'Lieu', 'tr' => 'Konum', 'ur' => 'مقام'],
            'description' => ['ar' => 'الوصف', 'en' => 'Description', 'fr' => 'Description', 'tr' => 'Açıklama', 'ur' => 'تفصیل'],
            'participants' => ['ar' => 'المشاركون', 'en' => 'Participants', 'fr' => 'Participants', 'tr' => 'Katılımcılar', 'ur' => 'شرکاء'],
            'importance' => ['ar' => 'الأهمية', 'en' => 'Importance', 'fr' => 'Importance', 'tr' => 'Önem', 'ur' => 'اہمیت'],
            'latitude' => ['ar' => 'خط العرض', 'en' => 'Latitude', 'fr' => 'Latitude', 'tr' => 'Enlem', 'ur' => 'عرض البلد'],
            'longitude' => ['ar' => 'خط الطول', 'en' => 'Longitude', 'fr' => 'Longitude', 'tr' => 'Boylam', 'ur' => 'طول البلد'],
            'status' => ['ar' => 'الحالة', 'en' => 'Status', 'fr' => 'Statut', 'tr' => 'Durum', 'ur' => 'حالت'],
            'created_at' => ['ar' => 'تاريخ الإنشاء', 'en' => 'Created At', 'fr' => 'Créé le', 'tr' => 'Oluşturulma', 'ur' => 'تخلیق کردہ'],
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
            'event_date_gregorian',
            'event_date_hijri',
            'event_type',
            'location',
            'description',
            'participants',
            'importance',
            'latitude',
            'longitude',
            'status',
            'created_at',
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
            'event_date_gregorian',
            'event_date_hijri',
            'event_type',
            'location',
            'status',
        ];
    }
}
