<?php

declare(strict_types=1);

namespace App\Domain\Quran\Exporters;

use App\Domain\Quran\Repositories\ReciterRepositoryInterface;
use App\Domain\Shared\Exporters\BaseExporter;
use Illuminate\Support\Collection;

/**
 * Quran Reciter Exporter
 * Exports Quran reciters
 */
class ReciterExporter extends BaseExporter
{
    /**
     * Constructor
     */
    public function __construct(
        protected ReciterRepositoryInterface $reciterRepository,
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
            $query = $this->reciterRepository->getModel()::query();

            if (isset($this->filters['country'])) {
                $query->where('country', $this->filters['country']);
            }

            if (isset($this->filters['style'])) {
                $query->where('style', $this->filters['style']);
            }

            if (isset($this->filters['is_featured'])) {
                $query->where('is_featured', (bool) $this->filters['is_featured']);
            }

            if (isset($this->filters['search'])) {
                $search = $this->filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.ar')) LIKE ?", ["%{$search}%"])
                        ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.en')) LIKE ?", ["%{$search}%"]);
                });
            }

            return $query->get();
        }

        return $this->reciterRepository->all();
    }

    /**
     * Map each row
     */
    public function map($reciter): array
    {
        $row = [];

        foreach ($this->selectedColumns as $column) {
            $row[] = match ($column) {
                'id' => $reciter->id,
                'name' => $this->formatMultiLanguageField($reciter->name),
                'country' => $reciter->country ?? '',
                'style' => ucfirst($reciter->style ?? ''),
                'biography' => $this->truncate($this->formatMultiLanguageField($reciter->biography), 300),
                'audio_quality' => $reciter->audio_quality ?? '',
                'is_featured' => $this->formatBoolean($reciter->is_featured ?? false),
                'birth_year' => $reciter->birth_year ?? '',
                'death_year' => $reciter->death_year ?? '',
                'complete_quran_available' => $this->formatBoolean($reciter->complete_quran_available ?? false),
                'status' => ucfirst($reciter->status ?? 'active'),
                'created_at' => $this->formatDate($reciter->created_at),
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
            'name' => ['ar' => 'الاسم', 'en' => 'Name', 'fr' => 'Nom', 'tr' => 'İsim', 'ur' => 'نام'],
            'country' => ['ar' => 'البلد', 'en' => 'Country', 'fr' => 'Pays', 'tr' => 'Ülke', 'ur' => 'ملک'],
            'style' => ['ar' => 'الأسلوب', 'en' => 'Style', 'fr' => 'Style', 'tr' => 'Stil', 'ur' => 'انداز'],
            'biography' => ['ar' => 'السيرة', 'en' => 'Biography', 'fr' => 'Biographie', 'tr' => 'Biyografi', 'ur' => 'سوانح'],
            'audio_quality' => ['ar' => 'جودة الصوت', 'en' => 'Audio Quality', 'fr' => 'Qualité audio', 'tr' => 'Ses Kalitesi', 'ur' => 'آواز کا معیار'],
            'is_featured' => ['ar' => 'مميز', 'en' => 'Featured', 'fr' => 'En vedette', 'tr' => 'Öne Çıkan', 'ur' => 'نمایاں'],
            'birth_year' => ['ar' => 'سنة الولادة', 'en' => 'Birth Year', 'fr' => 'Année de naissance', 'tr' => 'Doğum Yılı', 'ur' => 'سال پیدائش'],
            'death_year' => ['ar' => 'سنة الوفاة', 'en' => 'Death Year', 'fr' => 'Année de décès', 'tr' => 'Vefat Yılı', 'ur' => 'سال وفات'],
            'complete_quran_available' => ['ar' => 'القرآن كامل متاح', 'en' => 'Complete Quran', 'fr' => 'Coran complet', 'tr' => 'Tam Kuran', 'ur' => 'مکمل قرآن'],
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
            'name',
            'country',
            'style',
            'biography',
            'audio_quality',
            'is_featured',
            'birth_year',
            'death_year',
            'complete_quran_available',
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
            'name',
            'country',
            'style',
            'is_featured',
            'status',
        ];
    }
}
