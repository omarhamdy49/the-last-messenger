<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Exporters;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Repositories\HadithRepositoryInterface;
use App\Domain\Shared\Exporters\BaseExporter;
use Illuminate\Support\Collection;

/**
 * Hadith Exporter
 * Exports hadiths with their translations and grading information.
 */
class HadithExporter extends BaseExporter
{
    public function __construct(
        protected HadithRepositoryInterface $hadithRepository,
        string $language = 'en',
        array $filters = [],
        array $selectedColumns = []
    ) {
        parent::__construct($language, $filters, $selectedColumns);
    }

    public function collection(): Collection
    {
        $query = Hadith::query()
            ->with([
                'book:id,title',
                'translations',
                'companion:id,name_ar,name_en',
            ]);

        if (isset($this->filters['book_id'])) {
            $query->where('book_id', $this->filters['book_id']);
        }

        if (isset($this->filters['grading_status'])) {
            $query->where('grading_status', $this->filters['grading_status']);
        }

        if (isset($this->filters['grading_agreement'])) {
            $query->where('grading_agreement', (bool) $this->filters['grading_agreement']);
        }

        if (isset($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->whereHas('translations', function ($q) use ($search) {
                $q->where('text', 'LIKE', "%{$search}%");
            });
        }

        return $query->orderBy('book_id')->orderBy('hadith_number')->get();
    }

    public function map($hadith): array
    {
        $translation = $hadith->translations
            ->firstWhere('language_code', $this->language)
            ?? $hadith->translations->firstWhere('language_code', 'ar');

        $row = [];

        foreach ($this->selectedColumns as $column) {
            $row[] = match ($column) {
                'id' => $hadith->id,
                'hadith_number' => $hadith->hadith_number,
                'book' => $hadith->book?->title[$this->language] ?? $hadith->book?->title['ar'] ?? '',
                'grading_status' => $hadith->grading_status ?? '',
                'grading_agreement' => $this->formatBoolean($hadith->grading_agreement),
                'narrator' => $hadith->companion
                    ? ($this->language === 'ar'
                        ? ($hadith->companion->name_ar ?? '')
                        : ($hadith->companion->name_en ?? $hadith->companion->name_ar ?? ''))
                    : '',
                'text' => $this->truncate($translation?->text ?? '', 500),
                'created_at' => $this->formatDate($hadith->created_at),
                default => '',
            };
        }

        return $row;
    }

    public function headings(): array
    {
        $translations = [
            'id' => ['ar' => 'المعرف', 'en' => 'ID', 'fr' => 'ID', 'tr' => 'Kimlik', 'ur' => 'شناخت'],
            'hadith_number' => ['ar' => 'رقم الحديث', 'en' => 'Hadith Number', 'fr' => 'Numéro de hadith', 'tr' => 'Hadis No', 'ur' => 'حدیث نمبر'],
            'book' => ['ar' => 'الكتاب', 'en' => 'Book', 'fr' => 'Livre', 'tr' => 'Kitap', 'ur' => 'کتاب'],
            'grading_status' => ['ar' => 'درجة الحديث', 'en' => 'Grading', 'fr' => 'Degré', 'tr' => 'Derece', 'ur' => 'درجہ'],
            'grading_agreement' => ['ar' => 'إجماع العلماء', 'en' => 'Scholar Agreement', 'fr' => 'Accord des savants', 'tr' => 'Alim İttifakı', 'ur' => 'علما کا اتفاق'],
            'narrator' => ['ar' => 'الراوي', 'en' => 'Narrator', 'fr' => 'Narrateur', 'tr' => 'Ravî', 'ur' => 'راوی'],
            'text' => ['ar' => 'متن الحديث', 'en' => 'Hadith Text', 'fr' => 'Texte du hadith', 'tr' => 'Hadis Metni', 'ur' => 'حدیث متن'],
            'created_at' => ['ar' => 'تاريخ الإنشاء', 'en' => 'Created At', 'fr' => 'Créé le', 'tr' => 'Oluşturulma', 'ur' => 'تخلیق کردہ'],
        ];

        $headers = [];
        foreach ($this->selectedColumns as $column) {
            $headers[] = $this->getTranslation($translations[$column] ?? ['en' => $column]);
        }

        return $headers;
    }

    public function getAvailableColumns(): array
    {
        return [
            'id',
            'hadith_number',
            'book',
            'grading_status',
            'grading_agreement',
            'narrator',
            'text',
            'created_at',
        ];
    }

    public function getDefaultColumns(): array
    {
        return [
            'hadith_number',
            'book',
            'grading_status',
            'narrator',
            'text',
        ];
    }
}
