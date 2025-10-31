<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Exporters;

use App\Domain\Hadith\Repositories\HadithNarratorRepositoryInterface;
use App\Domain\Shared\Exporters\BaseExporter;
use Illuminate\Support\Collection;

/**
 * Hadith Narrator Exporter
 * Exports hadith narrators
 */
class HadithNarratorExporter extends BaseExporter
{
    /**
     * Constructor
     */
    public function __construct(
        protected HadithNarratorRepositoryInterface $narratorRepository,
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
            $query = $this->narratorRepository->getModel()::query();

            if (isset($this->filters['grade'])) {
                $query->where('grade', $this->filters['grade']);
            }

            if (isset($this->filters['era'])) {
                $query->where('era', $this->filters['era']);
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

        return $this->narratorRepository->all();
    }

    /**
     * Map each row
     */
    public function map($narrator): array
    {
        $row = [];

        foreach ($this->selectedColumns as $column) {
            $row[] = match ($column) {
                'id' => $narrator->id,
                'name' => $this->formatMultiLanguageField($narrator->name),
                'grade' => ucfirst($narrator->grade ?? ''),
                'era' => ucfirst($narrator->era ?? ''),
                'country' => $narrator->country ?? '',
                'birth_hijri' => $narrator->birth_hijri ?? '',
                'death_hijri' => $narrator->death_hijri ?? '',
                'biography' => $this->truncate($this->formatMultiLanguageField($narrator->biography), 300),
                'teachers' => $narrator->teachers ?? '',
                'students' => $narrator->students ?? '',
                'status' => ucfirst($narrator->status ?? 'active'),
                'hadith_count' => $narrator->hadith_count ?? 0,
                'created_at' => $this->formatDate($narrator->created_at),
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
            'grade' => ['ar' => 'الدرجة', 'en' => 'Grade', 'fr' => 'Grade', 'tr' => 'Derece', 'ur' => 'درجہ'],
            'era' => ['ar' => 'العصر', 'en' => 'Era', 'fr' => 'Ère', 'tr' => 'Dönem', 'ur' => 'دور'],
            'country' => ['ar' => 'البلد', 'en' => 'Country', 'fr' => 'Pays', 'tr' => 'Ülke', 'ur' => 'ملک'],
            'birth_hijri' => ['ar' => 'تاريخ الولادة', 'en' => 'Birth (Hijri)', 'fr' => 'Naissance', 'tr' => 'Doğum', 'ur' => 'ولادت'],
            'death_hijri' => ['ar' => 'تاريخ الوفاة', 'en' => 'Death (Hijri)', 'fr' => 'Décès', 'tr' => 'Vefat', 'ur' => 'وفات'],
            'biography' => ['ar' => 'السيرة', 'en' => 'Biography', 'fr' => 'Biographie', 'tr' => 'Biyografi', 'ur' => 'سوانح'],
            'teachers' => ['ar' => 'الشيوخ', 'en' => 'Teachers', 'fr' => 'Professeurs', 'tr' => 'Hocalar', 'ur' => 'اساتذہ'],
            'students' => ['ar' => 'التلاميذ', 'en' => 'Students', 'fr' => 'Étudiants', 'tr' => 'Öğrenciler', 'ur' => 'شاگرد'],
            'status' => ['ar' => 'الحالة', 'en' => 'Status', 'fr' => 'Statut', 'tr' => 'Durum', 'ur' => 'حالت'],
            'hadith_count' => ['ar' => 'عدد الأحاديث', 'en' => 'Hadith Count', 'fr' => 'Nombre de hadith', 'tr' => 'Hadis Sayısı', 'ur' => 'احادیث کی تعداد'],
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
            'grade',
            'era',
            'country',
            'birth_hijri',
            'death_hijri',
            'biography',
            'teachers',
            'students',
            'status',
            'hadith_count',
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
            'grade',
            'era',
            'country',
            'hadith_count',
            'status',
        ];
    }
}
