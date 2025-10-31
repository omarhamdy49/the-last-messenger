<?php

declare(strict_types=1);

namespace App\Domain\Quran\Exporters;

use App\Domain\Quran\Repositories\VerseRepositoryInterface;
use App\Domain\Shared\Exporters\BaseExporter;
use Illuminate\Support\Collection;

/**
 * Quran Verse Exporter
 * Exports Quran verses
 */
class VerseExporter extends BaseExporter
{
    /**
     * Constructor
     */
    public function __construct(
        protected VerseRepositoryInterface $verseRepository,
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
            $query = $this->verseRepository->getModel()::query()
                ->with(['surah']);

            if (isset($this->filters['surah_id'])) {
                $query->where('surah_id', $this->filters['surah_id']);
            }

            if (isset($this->filters['juz_number'])) {
                $query->where('juz_number', $this->filters['juz_number']);
            }

            if (isset($this->filters['search'])) {
                $search = $this->filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('verse_text', 'LIKE', "%{$search}%")
                        ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(translation, '$.en')) LIKE ?", ["%{$search}%"]);
                });
            }

            return $query->orderBy('surah_id')->orderBy('ayah_number')->get();
        }

        return $this->verseRepository->all(['*'], ['surah']);
    }

    /**
     * Map each row
     */
    public function map($verse): array
    {
        $row = [];

        foreach ($this->selectedColumns as $column) {
            $row[] = match ($column) {
                'id' => $verse->id,
                'surah' => $verse->surah?->name[$this->language] ?? $verse->surah?->name['ar'] ?? '',
                'surah_number' => $verse->surah?->surah_number ?? '',
                'ayah_number' => $verse->ayah_number,
                'verse_text' => $verse->verse_text ?? '',
                'transliteration' => $verse->transliteration ?? '',
                'translation' => $this->formatMultiLanguageField($verse->translation),
                'juz_number' => $verse->juz_number ?? '',
                'page_number' => $verse->page_number ?? '',
                'hizb_quarter' => $verse->hizb_quarter ?? '',
                'sajda' => $this->formatBoolean($verse->sajda ?? false),
                'revelation_order' => $verse->revelation_order ?? '',
                'created_at' => $this->formatDate($verse->created_at),
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
            'surah' => ['ar' => 'السورة', 'en' => 'Surah', 'fr' => 'Sourate', 'tr' => 'Sure', 'ur' => 'سورہ'],
            'surah_number' => ['ar' => 'رقم السورة', 'en' => 'Surah Number', 'fr' => 'Numéro de sourate', 'tr' => 'Sure No', 'ur' => 'سورہ نمبر'],
            'ayah_number' => ['ar' => 'رقم الآية', 'en' => 'Ayah Number', 'fr' => 'Numéro de verset', 'tr' => 'Ayet No', 'ur' => 'آیت نمبر'],
            'verse_text' => ['ar' => 'نص الآية', 'en' => 'Verse Text (Arabic)', 'fr' => 'Texte du verset', 'tr' => 'Ayet Metni', 'ur' => 'آیت کا متن'],
            'transliteration' => ['ar' => 'النسخ الصوتي', 'en' => 'Transliteration', 'fr' => 'Translittération', 'tr' => 'Transkripsiyon', 'ur' => 'نقل حرفی'],
            'translation' => ['ar' => 'الترجمة', 'en' => 'Translation', 'fr' => 'Traduction', 'tr' => 'Çeviri', 'ur' => 'ترجمہ'],
            'juz_number' => ['ar' => 'رقم الجزء', 'en' => 'Juz', 'fr' => 'Juz', 'tr' => 'Cüz', 'ur' => 'پارہ'],
            'page_number' => ['ar' => 'رقم الصفحة', 'en' => 'Page', 'fr' => 'Page', 'tr' => 'Sayfa', 'ur' => 'صفحہ'],
            'hizb_quarter' => ['ar' => 'ربع الحزب', 'en' => 'Hizb Quarter', 'fr' => 'Quart de Hizb', 'tr' => 'Hizb Çeyreği', 'ur' => 'حزب چوتھائی'],
            'sajda' => ['ar' => 'سجدة', 'en' => 'Sajda', 'fr' => 'Sajda', 'tr' => 'Secde', 'ur' => 'سجدہ'],
            'revelation_order' => ['ar' => 'ترتيب النزول', 'en' => 'Revelation Order', 'fr' => 'Ordre de révélation', 'tr' => 'İniş Sırası', 'ur' => 'نزول کی ترتیب'],
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
            'surah',
            'surah_number',
            'ayah_number',
            'verse_text',
            'transliteration',
            'translation',
            'juz_number',
            'page_number',
            'hizb_quarter',
            'sajda',
            'revelation_order',
            'created_at',
        ];
    }

    /**
     * Get default columns if none selected
     */
    public function getDefaultColumns(): array
    {
        return [
            'surah_number',
            'ayah_number',
            'verse_text',
            'translation',
            'juz_number',
        ];
    }
}
