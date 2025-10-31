<?php

declare(strict_types=1);

namespace App\Domain\Quran\Exporters;

use App\Domain\Quran\Repositories\SurahRepositoryInterface;
use App\Domain\Shared\Exporters\BaseExporter;
use Illuminate\Support\Collection;

/**
 * Quran Surah Exporter
 * Exports Quran surahs
 */
class SurahExporter extends BaseExporter
{
    /**
     * Constructor
     */
    public function __construct(
        protected SurahRepositoryInterface $surahRepository,
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
            $query = $this->surahRepository->getModel()::query()
                ->with(['verses']);

            if (isset($this->filters['revelation_place'])) {
                $query->where('revelation_place', $this->filters['revelation_place']);
            }

            if (isset($this->filters['revelation_type'])) {
                $query->where('revelation_type', $this->filters['revelation_type']);
            }

            return $query->orderBy('surah_number')->get();
        }

        return $this->surahRepository->all(['*'], ['verses']);
    }

    /**
     * Map each row
     */
    public function map($surah): array
    {
        $row = [];

        foreach ($this->selectedColumns as $column) {
            $row[] = match ($column) {
                'id' => $surah->id,
                'surah_number' => $surah->surah_number,
                'name' => $this->formatMultiLanguageField($surah->name),
                'transliteration' => $surah->transliteration ?? '',
                'translation' => $this->formatMultiLanguageField($surah->translation),
                'verse_count' => $surah->verses?->count() ?? $surah->verse_count ?? 0,
                'revelation_place' => ucfirst($surah->revelation_place ?? ''),
                'revelation_order' => $surah->revelation_order ?? '',
                'revelation_type' => ucfirst($surah->revelation_type ?? ''),
                'page_number' => $surah->page_number ?? '',
                'juz_number' => $surah->juz_number ?? '',
                'rukus' => $surah->rukus ?? '',
                'created_at' => $this->formatDate($surah->created_at),
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
            'surah_number' => ['ar' => 'رقم السورة', 'en' => 'Surah Number', 'fr' => 'Numéro de sourate', 'tr' => 'Sure Numarası', 'ur' => 'سورہ نمبر'],
            'name' => ['ar' => 'الاسم', 'en' => 'Name', 'fr' => 'Nom', 'tr' => 'İsim', 'ur' => 'نام'],
            'transliteration' => ['ar' => 'النسخ الصوتي', 'en' => 'Transliteration', 'fr' => 'Translittération', 'tr' => 'Transkripsiyon', 'ur' => 'نقل حرفی'],
            'translation' => ['ar' => 'الترجمة', 'en' => 'Translation', 'fr' => 'Traduction', 'tr' => 'Çeviri', 'ur' => 'ترجمہ'],
            'verse_count' => ['ar' => 'عدد الآيات', 'en' => 'Verses', 'fr' => 'Versets', 'tr' => 'Ayet Sayısı', 'ur' => 'آیات کی تعداد'],
            'revelation_place' => ['ar' => 'مكان النزول', 'en' => 'Revelation Place', 'fr' => 'Lieu de révélation', 'tr' => 'İniş Yeri', 'ur' => 'نزول کی جگہ'],
            'revelation_order' => ['ar' => 'ترتيب النزول', 'en' => 'Revelation Order', 'fr' => 'Ordre de révélation', 'tr' => 'İniş Sırası', 'ur' => 'نزول کی ترتیب'],
            'revelation_type' => ['ar' => 'نوع النزول', 'en' => 'Revelation Type', 'fr' => 'Type de révélation', 'tr' => 'İniş Türü', 'ur' => 'نزول کی قسم'],
            'page_number' => ['ar' => 'رقم الصفحة', 'en' => 'Page', 'fr' => 'Page', 'tr' => 'Sayfa', 'ur' => 'صفحہ نمبر'],
            'juz_number' => ['ar' => 'رقم الجزء', 'en' => 'Juz', 'fr' => 'Juz', 'tr' => 'Cüz', 'ur' => 'پارہ نمبر'],
            'rukus' => ['ar' => 'الركوعات', 'en' => 'Rukus', 'fr' => 'Rukus', 'tr' => 'Rükûlar', 'ur' => 'رکوع'],
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
            'surah_number',
            'name',
            'transliteration',
            'translation',
            'verse_count',
            'revelation_place',
            'revelation_order',
            'revelation_type',
            'page_number',
            'juz_number',
            'rukus',
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
            'name',
            'verse_count',
            'revelation_place',
            'revelation_order',
        ];
    }
}
