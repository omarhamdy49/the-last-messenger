<?php

declare(strict_types=1);

namespace App\Domain\Quran\Importers;

use App\Domain\Quran\Enums\RevelationPlace;
use App\Domain\Quran\Models\Surah;
use App\Domain\Quran\Models\SurahInfo;
use App\Domain\Shared\Importers\BaseImporter;

/**
 * Quran Surah Importer
 *
 * Imports Quran surahs (chapters) from CSV/Excel files.
 *
 * Schema mapping (the CSV contract is unchanged; only persistence was rewritten):
 *
 *  - `quran_surahs` has no `number` column — the primary key IS the surah
 *    number — and no JSON `name`; it stores `name_ar` / `name_en` directly.
 *  - Per-language prose goes to `quran_surah_info` (desc/content), one row per
 *    language, written only when the CSV supplies it.
 *  - Columns the schema has no home for (name_fr/tr/ur, meaning_*, words_count,
 *    letters_count, juz, page_number) are accepted by validation for backwards
 *    compatibility but not persisted.
 *
 * The previous version wrote `number`, a JSON `name`, `meaning`, `description`
 * and `theme` — none of which exist on the table — and matched surahs on the
 * absent `number` column, so every surah import fatalled.
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
        return Surah::query()->whereKey((int) $row['number'])->exists();
    }

    /**
     * {@inheritdoc}
     */
    protected function importRow(array $row): Surah
    {
        $number = (int) $row['number'];
        $existing = Surah::find($number);

        if ($existing !== null && ! ($this->options['update_existing'] ?? false)) {
            return $existing;
        }

        // The id is the surah number but is not mass-assignable, so it is set
        // explicitly rather than passed through fill().
        $surah = $existing ?? tap(new Surah, static fn (Surah $new) => $new->id = $number);
        $surah->fill($this->buildSurahData($row))->save();

        $this->syncSurahInfo($surah, $row);

        return $surah;
    }

    /**
     * Attributes that map onto real `quran_surahs` columns.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function buildSurahData(array $row): array
    {
        $data = [
            'name_ar' => (string) ($row['name_ar'] ?? ''),
            'name_en' => (string) ($row['name_en'] ?? ''),
            // ->value: the column is a plain string (no enum cast on Surah, whose
            // scopes compare against the raw value).
            'revelation_place' => RevelationPlace::fromExternal($row['revelation_place'] ?? null)->value,
            'verses_count' => (int) $row['verses_count'],
            'revelation_order' => (int) ($row['revelation_order'] ?? $row['number']),
        ];

        if (! empty($row['name_en'])) {
            $data['transliteration'] = (string) $row['name_en'];
        }

        return $data;
    }

    /**
     * Per-language description/theme rows, written only for supplied languages.
     *
     * @param  array<string, mixed>  $row
     */
    private function syncSurahInfo(Surah $surah, array $row): void
    {
        foreach (['ar', 'en', 'fr', 'tr', 'ur'] as $language) {
            $description = trim((string) ($row["description_{$language}"] ?? ''));
            $theme = trim((string) ($row["theme_{$language}"] ?? ''));

            if ($description === '' && $theme === '') {
                continue;
            }

            SurahInfo::updateOrCreate(
                ['surah_id' => $surah->id, 'lang' => $language],
                ['desc' => $description, 'content' => $theme, 'active' => true],
            );
        }
    }
}
