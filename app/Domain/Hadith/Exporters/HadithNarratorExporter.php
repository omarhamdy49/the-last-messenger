<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Exporters;

use App\Domain\Hadith\Models\HadithNarrator;
use App\Domain\Hadith\Repositories\NarratorRepositoryInterface;
use App\Domain\Shared\Exporters\BaseExporter;
use Illuminate\Support\Collection;

/**
 * Hadith Narrator Exporter
 * Exports narrator biographical information and chain statistics.
 */
class HadithNarratorExporter extends BaseExporter
{
    public function __construct(
        protected NarratorRepositoryInterface $narratorRepository,
        string $language = 'en',
        array $filters = [],
        array $selectedColumns = []
    ) {
        parent::__construct($language, $filters, $selectedColumns);
    }

    public function collection(): Collection
    {
        $query = HadithNarrator::query()
            ->with('sahabi.translations')
            ->withCount('isnads');

        if (isset($this->filters['reliability_grade'])) {
            $query->where('reliability_grade', $this->filters['reliability_grade']);
        }

        if (isset($this->filters['school'])) {
            $query->where('school', $this->filters['school']);
        }

        if (isset($this->filters['region'])) {
            $query->where('region', $this->filters['region']);
        }

        if (isset($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name_ar', 'LIKE', "%{$search}%")
                    ->orWhere('name_en', 'LIKE', "%{$search}%")
                    ->orWhere('full_name_ar', 'LIKE', "%{$search}%")
                    ->orWhereHas('sahabi.translations', function ($translation) use ($search): void {
                        $translation->where('name', 'LIKE', "%{$search}%")
                            ->orWhere('full_name', 'LIKE', "%{$search}%");
                    });
            });
        }

        return $query->orderBy('name_ar')->get();
    }

    public function map($narrator): array
    {
        $name = $this->language === 'ar'
            ? ($narrator->name_ar ?? '')
            : ($narrator->name_en ?? $narrator->name_ar ?? '');

        $fullName = $this->language === 'ar'
            ? ($narrator->full_name_ar ?? '')
            : ($narrator->full_name_en ?? $narrator->full_name_ar ?? '');

        $row = [];

        foreach ($this->selectedColumns as $column) {
            $row[] = match ($column) {
                'id' => $narrator->id,
                'name' => $name,
                'full_name' => $fullName,
                'kunya' => $narrator->kunya ?? '',
                'birth_year_hijri' => $narrator->birth_year_hijri ?? '',
                'death_year_hijri' => $narrator->death_year_hijri ?? '',
                'reliability_grade' => $narrator->reliability_grade ?? '',
                'school' => $narrator->school ?? '',
                'region' => $narrator->region ?? '',
                'hadith_count' => $narrator->isnads_count ?? 0,
                'biography' => $this->truncate($narrator->biography ?? '', 300),
                'created_at' => $this->formatDate($narrator->created_at),
                default => '',
            };
        }

        return $row;
    }

    public function headings(): array
    {
        $translations = [
            'id' => ['ar' => 'المعرف', 'en' => 'ID', 'fr' => 'ID', 'tr' => 'Kimlik', 'ur' => 'شناخت'],
            'name' => ['ar' => 'الاسم', 'en' => 'Name', 'fr' => 'Nom', 'tr' => 'İsim', 'ur' => 'نام'],
            'full_name' => ['ar' => 'الاسم الكامل', 'en' => 'Full Name', 'fr' => 'Nom complet', 'tr' => 'Tam İsim', 'ur' => 'پورا نام'],
            'kunya' => ['ar' => 'الكنية', 'en' => 'Kunya', 'fr' => 'Kunya', 'tr' => 'Künye', 'ur' => 'کنیت'],
            'birth_year_hijri' => ['ar' => 'سنة الولادة هجري', 'en' => 'Birth Year (Hijri)', 'fr' => 'Naissance (Hégirien)', 'tr' => 'Doğum Yılı (Hicri)', 'ur' => 'سال پیدائش (ہجری)'],
            'death_year_hijri' => ['ar' => 'سنة الوفاة هجري', 'en' => 'Death Year (Hijri)', 'fr' => 'Décès (Hégirien)', 'tr' => 'Vefat Yılı (Hicri)', 'ur' => 'سال وفات (ہجری)'],
            'reliability_grade' => ['ar' => 'درجة التوثيق', 'en' => 'Reliability Grade', 'fr' => 'Degré de fiabilité', 'tr' => 'Güvenilirlik Derecesi', 'ur' => 'قابل اعتماد درجہ'],
            'school' => ['ar' => 'المدرسة الفقهية', 'en' => 'School', 'fr' => 'École', 'tr' => 'Mezhep', 'ur' => 'مکتب فکر'],
            'region' => ['ar' => 'المنطقة', 'en' => 'Region', 'fr' => 'Région', 'tr' => 'Bölge', 'ur' => 'علاقہ'],
            'hadith_count' => ['ar' => 'عدد الأحاديث', 'en' => 'Hadith Count', 'fr' => 'Nombre de hadiths', 'tr' => 'Hadis Sayısı', 'ur' => 'احادیث کی تعداد'],
            'biography' => ['ar' => 'السيرة', 'en' => 'Biography', 'fr' => 'Biographie', 'tr' => 'Biyografi', 'ur' => 'سوانح'],
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
            'name',
            'full_name',
            'kunya',
            'birth_year_hijri',
            'death_year_hijri',
            'reliability_grade',
            'school',
            'region',
            'hadith_count',
            'biography',
            'created_at',
        ];
    }

    public function getDefaultColumns(): array
    {
        return [
            'id',
            'name',
            'kunya',
            'reliability_grade',
            'hadith_count',
            'region',
        ];
    }
}
