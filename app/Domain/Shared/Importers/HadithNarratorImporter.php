<?php

declare(strict_types=1);

namespace App\Domain\Shared\Importers;

use App\Domain\Hadith\Models\HadithNarrator;
use App\Domain\Hadith\Enums\NarratorGrade;
use Illuminate\Support\Str;

/**
 * Hadith Narrator Importer
 *
 * Imports hadith narrators from CSV/Excel files
 */
class HadithNarratorImporter extends BaseImporter
{
    /**
     * {@inheritdoc}
     */
    protected function getValidationRules(): array
    {
        return [
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'biography_ar' => 'nullable|string',
            'biography_en' => 'nullable|string',
            'grade' => 'nullable|in:thiqah,saduq,daif,matruk',
            'era' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'birth_hijri' => 'nullable|integer',
            'death_hijri' => 'nullable|integer',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getColumnMapping(): array
    {
        return [
            'name_ar' => 'name_ar',
            'name_en' => 'name_en',
            'biography_ar' => 'biography_ar',
            'biography_en' => 'biography_en',
            'grade' => 'grade',
            'era' => 'era',
            'country' => 'country',
            'birth_hijri' => 'birth_hijri',
            'death_hijri' => 'death_hijri',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function isDuplicate(array $row): bool
    {
        $slug = Str::slug($row['name_en'] ?? $row['name_ar']);

        return HadithNarrator::where('slug', $slug)->exists();
    }

    /**
     * {@inheritdoc}
     */
    protected function importRow(array $row): HadithNarrator
    {
        $name = [
            'ar' => $row['name_ar'],
            'en' => $row['name_en'],
            'fr' => $row['name_en'],
            'tr' => $row['name_en'],
            'ur' => $row['name_ar'],
        ];

        $biography = [
            'ar' => $row['biography_ar'] ?? '',
            'en' => $row['biography_en'] ?? '',
            'fr' => $row['biography_en'] ?? '',
            'tr' => $row['biography_en'] ?? '',
            'ur' => $row['biography_ar'] ?? '',
        ];

        return HadithNarrator::create([
            'name' => $name,
            'slug' => Str::slug($name['en'] ?: $name['ar']),
            'biography' => $biography,
            'grade' => $row['grade'] ? NarratorGrade::tryFrom($row['grade']) : null,
            'era' => $row['era'] ?? null,
            'country' => $row['country'] ?? null,
            'birth_hijri' => isset($row['birth_hijri']) ? (int) $row['birth_hijri'] : null,
            'death_hijri' => isset($row['death_hijri']) ? (int) $row['death_hijri'] : null,
        ]);
    }
}
