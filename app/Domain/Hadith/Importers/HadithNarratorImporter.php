<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Importers;

use App\Domain\Hadith\Models\HadithNarrator;
use App\Domain\Shared\Importers\BaseImporter;

/**
 * Hadith Narrator Importer
 *
 * Imports HadithNarrator records from CSV/Excel files.
 *
 * Expected CSV columns (required): name_ar, name_en
 * Optional columns: biography_ar, biography_en, grade, era, country, birth_hijri, death_hijri
 *
 * Duplicate detection is based on a case-insensitive match on name_ar.
 */
class HadithNarratorImporter extends BaseImporter
{
    /**
     * {@inheritdoc}
     */
    protected function getValidationRules(): array
    {
        return [
            'name_ar'      => 'required|string|max:255',
            'name_en'      => 'required|string|max:255',
            'biography_ar' => 'nullable|string',
            'biography_en' => 'nullable|string',
            'grade'        => 'nullable|string|max:100',
            'era'          => 'nullable|string|max:100',
            'country'      => 'nullable|string|max:100',
            'birth_hijri'  => 'nullable|integer|min:1',
            'death_hijri'  => 'nullable|integer|min:1',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getColumnMapping(): array
    {
        return [
            'name_ar'      => 'name_ar',
            'name_en'      => 'name_en',
            'biography_ar' => 'biography_ar',
            'biography_en' => 'biography_en',
            'grade'        => 'grade',
            'era'          => 'era',
            'country'      => 'country',
            'birth_hijri'  => 'birth_hijri',
            'death_hijri'  => 'death_hijri',
        ];
    }

    /**
     * {@inheritdoc}
     *
     * A narrator is a duplicate when name_ar already exists (case-insensitive).
     */
    protected function isDuplicate(array $row): bool
    {
        return HadithNarrator::whereRaw('LOWER(name_ar) = ?', [strtolower($row['name_ar'] ?? '')])
            ->exists();
    }

    /**
     * {@inheritdoc}
     */
    protected function importRow(array $row): HadithNarrator
    {
        if ($this->options['update_existing'] ?? false) {
            $existing = HadithNarrator::whereRaw('LOWER(name_ar) = ?', [strtolower($row['name_ar'])])
                ->first();

            if ($existing) {
                $existing->update($this->buildNarratorData($row));

                return $existing;
            }
        }

        return HadithNarrator::create($this->buildNarratorData($row));
    }

    /**
     * Build the HadithNarrator attribute array from a CSV row.
     *
     * The CSV column 'biography_ar' / 'biography_en' is merged into the single
     * 'biography' JSON column that HadithNarrator stores, and 'grade' maps to
     * 'reliability_grade', 'era' maps to 'school', 'country' maps to 'region',
     * 'birth_hijri' to 'birth_year_hijri', 'death_hijri' to 'death_year_hijri'.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function buildNarratorData(array $row): array
    {
        $biographyAr = trim((string) ($row['biography_ar'] ?? ''));
        $biographyEn = trim((string) ($row['biography_en'] ?? ''));

        $biography = null;
        if ($biographyAr !== '' || $biographyEn !== '') {
            $biography = array_filter([
                'ar' => $biographyAr ?: null,
                'en' => $biographyEn ?: null,
            ]);
        }

        $data = [
            'name_ar'          => trim((string) $row['name_ar']),
            'name_en'          => trim((string) $row['name_en']),
            'reliability_grade' => $row['grade'] ?? null,
            'school'           => $row['era'] ?? null,
            'region'           => $row['country'] ?? null,
        ];

        if ($biography !== null) {
            $data['biography'] = $biography;
        }

        $birthHijri = $row['birth_hijri'] ?? null;
        if ($birthHijri !== null && $birthHijri !== '') {
            $data['birth_year_hijri'] = (int) $birthHijri;
        }

        $deathHijri = $row['death_hijri'] ?? null;
        if ($deathHijri !== null && $deathHijri !== '') {
            $data['death_year_hijri'] = (int) $deathHijri;
        }

        return $data;
    }
}
