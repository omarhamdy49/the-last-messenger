<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Exporters;

use App\Domain\Hadith\Repositories\HadithRepositoryInterface;
use App\Domain\Shared\Exporters\BaseExporter;
use Illuminate\Support\Collection;

/**
 * Hadith Exporter
 * Exports hadith with narrators
 */
class HadithExporter extends BaseExporter
{
    /**
     * Constructor
     */
    public function __construct(
        protected HadithRepositoryInterface $hadithRepository,
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
        // Get all hadith or filtered hadith
        if (! empty($this->filters)) {
            $query = $this->hadithRepository->getModel()::query()
                ->with(['book', 'chapter', 'narrators']);

            // Apply filters
            if (isset($this->filters['book_id'])) {
                $query->where('book_id', $this->filters['book_id']);
            }

            if (isset($this->filters['chapter_id'])) {
                $query->where('chapter_id', $this->filters['chapter_id']);
            }

            if (isset($this->filters['grade'])) {
                $query->where('grade', $this->filters['grade']);
            }

            if (isset($this->filters['search'])) {
                $search = $this->filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('arabic_text', 'LIKE', "%{$search}%")
                        ->orWhere('english_text', 'LIKE', "%{$search}%");
                });
            }

            return $query->get();
        }

        return $this->hadithRepository->all(['*'], ['book', 'chapter', 'narrators']);
    }

    /**
     * Map each row
     */
    public function map($hadith): array
    {
        $row = [];

        foreach ($this->selectedColumns as $column) {
            $row[] = match ($column) {
                'id' => $hadith->id,
                'hadith_number' => $hadith->hadith_number ?? '',
                'book' => $hadith->book?->name[$this->language] ?? $hadith->book?->name['ar'] ?? '',
                'chapter' => $hadith->chapter?->name[$this->language] ?? $hadith->chapter?->name['ar'] ?? '',
                'arabic_text' => $hadith->arabic_text ?? '',
                'english_text' => $hadith->english_text ?? '',
                'narrators' => $hadith->narrators?->pluck('name.'.$this->language)->filter()->implode(' → ') ?? '',
                'grade' => ucfirst($hadith->grade ?? ''),
                'reference' => $hadith->reference ?? '',
                'notes' => $this->truncate($hadith->notes, 200),
                'status' => ucfirst($hadith->status ?? 'active'),
                'created_at' => $this->formatDate($hadith->created_at),
                'updated_at' => $this->formatDate($hadith->updated_at),
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
            'hadith_number' => ['ar' => 'رقم الحديث', 'en' => 'Hadith Number', 'fr' => 'Numéro', 'tr' => 'Hadis Numarası', 'ur' => 'حدیث نمبر'],
            'book' => ['ar' => 'الكتاب', 'en' => 'Book', 'fr' => 'Livre', 'tr' => 'Kitap', 'ur' => 'کتاب'],
            'chapter' => ['ar' => 'الباب', 'en' => 'Chapter', 'fr' => 'Chapitre', 'tr' => 'Bölüm', 'ur' => 'باب'],
            'arabic_text' => ['ar' => 'النص العربي', 'en' => 'Arabic Text', 'fr' => 'Texte arabe', 'tr' => 'Arapça Metin', 'ur' => 'عربی متن'],
            'english_text' => ['ar' => 'النص الإنجليزي', 'en' => 'English Text', 'fr' => 'Texte anglais', 'tr' => 'İngilizce Metin', 'ur' => 'انگریزی متن'],
            'narrators' => ['ar' => 'الرواة', 'en' => 'Narrators', 'fr' => 'Narrateurs', 'tr' => 'Raviler', 'ur' => 'راوی'],
            'grade' => ['ar' => 'الدرجة', 'en' => 'Grade', 'fr' => 'Grade', 'tr' => 'Derece', 'ur' => 'درجہ'],
            'reference' => ['ar' => 'المرجع', 'en' => 'Reference', 'fr' => 'Référence', 'tr' => 'Referans', 'ur' => 'حوالہ'],
            'notes' => ['ar' => 'ملاحظات', 'en' => 'Notes', 'fr' => 'Notes', 'tr' => 'Notlar', 'ur' => 'نوٹ'],
            'status' => ['ar' => 'الحالة', 'en' => 'Status', 'fr' => 'Statut', 'tr' => 'Durum', 'ur' => 'حالت'],
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
            'hadith_number',
            'book',
            'chapter',
            'arabic_text',
            'english_text',
            'narrators',
            'grade',
            'reference',
            'notes',
            'status',
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
            'hadith_number',
            'book',
            'chapter',
            'arabic_text',
            'english_text',
            'grade',
            'created_at',
        ];
    }
}
