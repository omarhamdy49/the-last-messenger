<?php

declare(strict_types=1);

namespace App\Domain\Quran\Importers;

use App\Domain\Quran\Enums\RevelationPlace;
use App\Domain\Quran\Models\Surah;
use App\Domain\Shared\Importers\BaseImporter;

/**
 * Quran Surah Importer
 *
 * Imports Quran surahs (chapters) from CSV/Excel files
 */
class QuranSurahImporter extends BaseImporter
{
    /**
     * {@inheritdoc}
     */
    protected function getValidationRules(): array
    {
        return [
            'number' => 'required|integer|min:1|max:114',
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'name_fr' => 'nullable|string|max:255',
            'name_tr' => 'nullable|string|max:255',
            'name_ur' => 'nullable|string|max:255',
            'meaning_ar' => 'nullable|string|max:255',
            'meaning_en' => 'nullable|string|max:255',
            'meaning_fr' => 'nullable|string|max:255',
            'meaning_tr' => 'nullable|string|max:255',
            'meaning_ur' => 'nullable|string|max:255',
            'revelation_place' => 'required|in:makkah,madinah',
            'revelation_order' => 'nullable|integer|min:1|max:114',
            'verses_count' => 'required|integer|min:1',
            'words_count' => 'nullable|integer',
            'letters_count' => 'nullable|integer',
            'juz' => 'nullable|string',
            'page_number' => 'nullable|integer',
            'description_ar' => 'nullable|string',
            'description_en' => 'nullable|string',
            'description_fr' => 'nullable|string',
            'description_tr' => 'nullable|string',
            'description_ur' => 'nullable|string',
            'theme_ar' => 'nullable|string',
            'theme_en' => 'nullable|string',
            'theme_fr' => 'nullable|string',
            'theme_tr' => 'nullable|string',
            'theme_ur' => 'nullable|string',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getColumnMapping(): array
    {
        return [
            'number' => 'number',
            'name_ar' => 'name_ar',
            'name_en' => 'name_en',
            'name_fr' => 'name_fr',
            'name_tr' => 'name_tr',
            'name_ur' => 'name_ur',
            'meaning_ar' => 'meaning_ar',
            'meaning_en' => 'meaning_en',
            'meaning_fr' => 'meaning_fr',
            'meaning_tr' => 'meaning_tr',
            'meaning_ur' => 'meaning_ur',
            'revelation_place' => 'revelation_place',
            'revelation_order' => 'revelation_order',
            'verses_count' => 'verses_count',
            'words_count' => 'words_count',
            'letters_count' => 'letters_count',
            'juz' => 'juz',
            'page_number' => 'page_number',
            'description_ar' => 'description_ar',
            'description_en' => 'description_en',
            'description_fr' => 'description_fr',
            'description_tr' => 'description_tr',
            'description_ur' => 'description_ur',
            'theme_ar' => 'theme_ar',
            'theme_en' => 'theme_en',
            'theme_fr' => 'theme_fr',
            'theme_tr' => 'theme_tr',
            'theme_ur' => 'theme_ur',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function isDuplicate(array $row): bool
    {
        return Surah::where('number', $row['number'])->exists();
    }

    /**
     * {@inheritdoc}
     */
    protected function importRow(array $row): Surah
    {
        // Check for update existing option
        if ($this->options['update_existing'] ?? false) {
            $surah = Surah::where('number', $row['number'])->first();
            if ($surah) {
                $surah->update($this->buildSurahData($row));

                return $surah;
            }
        }

        return Surah::create($this->buildSurahData($row));
    }

    /**
     * Build surah data array from row
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function buildSurahData(array $row): array
    {
        $data = [
            'number' => (int) $row['number'],
            'name' => $this->buildMultiLanguageField($row, 'name'),
            'revelation_place' => RevelationPlace::tryFrom(strtolower($row['revelation_place'])) ?? RevelationPlace::MAKKAH,
            'verses_count' => (int) $row['verses_count'],
        ];

        // Optional fields
        if (! empty($row['meaning_ar']) || ! empty($row['meaning_en'])) {
            $data['meaning'] = $this->buildMultiLanguageField($row, 'meaning');
        }

        if (! empty($row['description_ar']) || ! empty($row['description_en'])) {
            $data['description'] = $this->buildMultiLanguageField($row, 'description');
        }

        if (! empty($row['theme_ar']) || ! empty($row['theme_en'])) {
            $data['theme'] = $this->buildMultiLanguageField($row, 'theme');
        }

        if (isset($row['revelation_order']) && ! empty($row['revelation_order'])) {
            $data['revelation_order'] = (int) $row['revelation_order'];
        }

        if (isset($row['words_count']) && ! empty($row['words_count'])) {
            $data['words_count'] = (int) $row['words_count'];
        }

        if (isset($row['letters_count']) && ! empty($row['letters_count'])) {
            $data['letters_count'] = (int) $row['letters_count'];
        }

        if (isset($row['page_number']) && ! empty($row['page_number'])) {
            $data['page_number'] = (int) $row['page_number'];
        }

        // Parse juz field (can be comma-separated for multi-juz surahs)
        if (! empty($row['juz'])) {
            $juzNumbers = array_map('trim', explode(',', $row['juz']));
            $data['juz'] = array_map('intval', $juzNumbers);
        }

        return $data;
    }
}
