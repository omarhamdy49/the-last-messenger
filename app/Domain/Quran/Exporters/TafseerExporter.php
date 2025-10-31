<?php

declare(strict_types=1);

namespace App\Domain\Quran\Exporters;

use App\Domain\Quran\Repositories\TafseerRepositoryInterface;
use App\Domain\Shared\Exporters\BaseExporter;
use Illuminate\Support\Collection;

/**
 * Quran Tafseer Exporter
 * Exports Quran tafseer (interpretations)
 */
class TafseerExporter extends BaseExporter
{
    /**
     * Constructor
     */
    public function __construct(
        protected TafseerRepositoryInterface $tafseerRepository,
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
            $query = $this->tafseerRepository->getModel()::query()
                ->with(['verse']);

            if (isset($this->filters['surah_id'])) {
                $query->whereHas('verse', function ($q) use ($filters) {
                    $q->where('surah_id', $this->filters['surah_id']);
                });
            }

            if (isset($this->filters['verse_id'])) {
                $query->where('verse_id', $this->filters['verse_id']);
            }

            if (isset($this->filters['source'])) {
                $query->where('source', $this->filters['source']);
            }

            if (isset($this->filters['search'])) {
                $search = $this->filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(tafseer_text, '$.ar')) LIKE ?", ["%{$search}%"])
                        ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(tafseer_text, '$.en')) LIKE ?", ["%{$search}%"]);
                });
            }

            return $query->get();
        }

        return $this->tafseerRepository->all(['*'], ['verse']);
    }

    /**
     * Map each row
     */
    public function map($tafseer): array
    {
        $row = [];

        foreach ($this->selectedColumns as $column) {
            $row[] = match ($column) {
                'id' => $tafseer->id,
                'verse_reference' => ($tafseer->verse?->surah?->surah_number ?? '').' : '.($tafseer->verse?->ayah_number ?? ''),
                'verse_text' => $tafseer->verse?->verse_text ?? '',
                'source' => $tafseer->source ?? '',
                'author' => $this->formatMultiLanguageField($tafseer->author),
                'tafseer_text' => $this->truncate($this->formatMultiLanguageField($tafseer->tafseer_text), 500),
                'language' => $tafseer->language ?? '',
                'status' => ucfirst($tafseer->status ?? 'active'),
                'created_at' => $this->formatDate($tafseer->created_at),
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
            'verse_reference' => ['ar' => 'مرجع الآية', 'en' => 'Verse Reference', 'fr' => 'Référence du verset', 'tr' => 'Ayet Referansı', 'ur' => 'آیت کا حوالہ'],
            'verse_text' => ['ar' => 'نص الآية', 'en' => 'Verse Text', 'fr' => 'Texte du verset', 'tr' => 'Ayet Metni', 'ur' => 'آیت کا متن'],
            'source' => ['ar' => 'المصدر', 'en' => 'Source', 'fr' => 'Source', 'tr' => 'Kaynak', 'ur' => 'ماخذ'],
            'author' => ['ar' => 'المؤلف', 'en' => 'Author', 'fr' => 'Auteur', 'tr' => 'Yazar', 'ur' => 'مصنف'],
            'tafseer_text' => ['ar' => 'التفسير', 'en' => 'Tafseer Text', 'fr' => 'Texte du tafsir', 'tr' => 'Tefsir Metni', 'ur' => 'تفسیر متن'],
            'language' => ['ar' => 'اللغة', 'en' => 'Language', 'fr' => 'Langue', 'tr' => 'Dil', 'ur' => 'زبان'],
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
            'verse_reference',
            'verse_text',
            'source',
            'author',
            'tafseer_text',
            'language',
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
            'verse_reference',
            'source',
            'author',
            'tafseer_text',
            'language',
        ];
    }
}
