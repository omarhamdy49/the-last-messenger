<?php

declare(strict_types=1);

namespace App\Domain\Sahaba\Exporters;

use App\Domain\Sahaba\Repositories\SahabiRepositoryInterface;
use App\Domain\Shared\Exporters\BaseExporter;
use Illuminate\Support\Collection;

/**
 * Sahabi (Companion) Exporter
 * Exports companion profiles
 */
class SahabiExporter extends BaseExporter
{
    /**
     * Constructor
     */
    public function __construct(
        protected SahabiRepositoryInterface $sahabiRepository,
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
            $query = $this->sahabiRepository->getModel()::query();

            if (isset($this->filters['status'])) {
                $query->where('status', $this->filters['status']);
            }

            if (isset($this->filters['gender'])) {
                $query->where('gender', $this->filters['gender']);
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

        return $this->sahabiRepository->all();
    }

    /**
     * Map each row
     */
    public function map($sahabi): array
    {
        $row = [];

        foreach ($this->selectedColumns as $column) {
            $row[] = match ($column) {
                'id' => $sahabi->id,
                'name' => $this->formatMultiLanguageField($sahabi->name),
                'full_name' => $this->formatMultiLanguageField($sahabi->full_name),
                'kunya' => $sahabi->kunya ?? '',
                'tribe' => $this->formatMultiLanguageField($sahabi->tribe),
                'gender' => ucfirst($sahabi->gender ?? ''),
                'birth_date' => $sahabi->birth_date ?? '',
                'death_date' => $sahabi->death_date ?? '',
                'death_place' => $this->formatMultiLanguageField($sahabi->death_place),
                'status' => ucfirst($sahabi->status ?? ''),
                'hadith_count' => $sahabi->hadith_count ?? 0,
                'known_for' => $this->formatMultiLanguageField($sahabi->known_for),
                'battles_participated' => $this->formatArray(
                    $sahabi->relationLoaded('battles')
                        ? $sahabi->battles->pluck('battle_name')->all()
                        : $sahabi->battles()->pluck('battle_name')->all()
                ),
                'father_name' => $this->formatMultiLanguageField($sahabi->father_name),
                'mother_name' => $this->formatMultiLanguageField($sahabi->mother_name),
                'spouse_names' => $this->formatArray($sahabi->spouse_names),
                'children_names' => $this->formatArray($sahabi->children_names),
                'biography' => $this->truncate($this->formatMultiLanguageField($sahabi->biography), 300),
                'created_at' => $this->formatDate($sahabi->created_at),
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
            'full_name' => ['ar' => 'الاسم الكامل', 'en' => 'Full Name', 'fr' => 'Nom complet', 'tr' => 'Tam İsim', 'ur' => 'پورا نام'],
            'kunya' => ['ar' => 'الكنية', 'en' => 'Kunya', 'fr' => 'Kunya', 'tr' => 'Künye', 'ur' => 'کنیت'],
            'tribe' => ['ar' => 'القبيلة', 'en' => 'Tribe', 'fr' => 'Tribu', 'tr' => 'Kabile', 'ur' => 'قبیلہ'],
            'gender' => ['ar' => 'الجنس', 'en' => 'Gender', 'fr' => 'Sexe', 'tr' => 'Cinsiyet', 'ur' => 'جنس'],
            'birth_date' => ['ar' => 'تاريخ الولادة', 'en' => 'Birth Date', 'fr' => 'Date de naissance', 'tr' => 'Doğum Tarihi', 'ur' => 'تاریخ پیدائش'],
            'death_date' => ['ar' => 'تاريخ الوفاة', 'en' => 'Death Date', 'fr' => 'Date de décès', 'tr' => 'Vefat Tarihi', 'ur' => 'تاریخ وفات'],
            'death_place' => ['ar' => 'مكان الوفاة', 'en' => 'Death Place', 'fr' => 'Lieu de décès', 'tr' => 'Vefat Yeri', 'ur' => 'وفات کی جگہ'],
            'status' => ['ar' => 'الحالة', 'en' => 'Status', 'fr' => 'Statut', 'tr' => 'Durum', 'ur' => 'حالت'],
            'hadith_count' => ['ar' => 'عدد الأحاديث', 'en' => 'Hadith Count', 'fr' => 'Nombre de hadith', 'tr' => 'Hadis Sayısı', 'ur' => 'احادیث کی تعداد'],
            'known_for' => ['ar' => 'مشهور بـ', 'en' => 'Known For', 'fr' => 'Connu pour', 'tr' => 'Bilindiği Şey', 'ur' => 'مشہور'],
            'battles_participated' => ['ar' => 'المعارك', 'en' => 'Battles', 'fr' => 'Batailles', 'tr' => 'Savaşlar', 'ur' => 'جنگیں'],
            'father_name' => ['ar' => 'اسم الأب', 'en' => 'Father Name', 'fr' => 'Nom du père', 'tr' => 'Baba Adı', 'ur' => 'والد کا نام'],
            'mother_name' => ['ar' => 'اسم الأم', 'en' => 'Mother Name', 'fr' => 'Nom de la mère', 'tr' => 'Anne Adı', 'ur' => 'والدہ کا نام'],
            'spouse_names' => ['ar' => 'الزوجات', 'en' => 'Spouses', 'fr' => 'Époux', 'tr' => 'Eşler', 'ur' => 'شریک حیات'],
            'children_names' => ['ar' => 'الأبناء', 'en' => 'Children', 'fr' => 'Enfants', 'tr' => 'Çocuklar', 'ur' => 'اولاد'],
            'biography' => ['ar' => 'السيرة', 'en' => 'Biography', 'fr' => 'Biographie', 'tr' => 'Biyografi', 'ur' => 'سوانح'],
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
            'full_name',
            'kunya',
            'tribe',
            'gender',
            'birth_date',
            'death_date',
            'death_place',
            'status',
            'hadith_count',
            'known_for',
            'battles_participated',
            'father_name',
            'mother_name',
            'spouse_names',
            'children_names',
            'biography',
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
            'tribe',
            'hadith_count',
            'known_for',
            'status',
        ];
    }
}
