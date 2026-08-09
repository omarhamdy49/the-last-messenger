<?php

declare(strict_types=1);

namespace App\Domain\Quran\Importers;

use App\Domain\Quran\Models\Surah;
use App\Domain\Quran\Models\TranslationResource;
use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Models\VerseTranslation;
use App\Domain\Shared\Importers\BaseImporter;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Quran Verse Importer
 *
 * Imports Quran verses from CSV/Excel files.
 *
 * Schema mapping (the CSV contract is unchanged; only persistence was rewritten):
 *
 *  - `quran_verses` holds structure only — surah_id, verse_number, verse_key and
 *    the juz/hizb/page positions. It has no text columns.
 *  - Arabic source text is a `quran_verse_translations` row with
 *    `resource_id = NULL` and `lang = 'ar'`, matching QuranService's convention.
 *  - Each non-Arabic translation belongs to a `TranslationResource` (the
 *    edition), resolved per language from the CSV.
 *  - quran_surahs has no `number` column: the primary key *is* the surah number.
 *
 * The previous version wrote `text_uthmani`, `text_simple`, `translations` and
 * `transliteration` — none of which exist on the table or in Verse::$fillable —
 * and looked surahs up by a `number` column that does not exist, so every verse
 * import fatalled on the first row.
 */
class QuranVerseImporter extends BaseImporter
{
    /** Arabic source rows carry no edition, matching QuranService. */
    private const SOURCE_TEXT_LANG = 'ar';

    /** CSV column suffix => language code for the translation editions. */
    private const TRANSLATION_COLUMNS = [
        'english_translation' => 'en',
        'french_translation' => 'fr',
        'turkish_translation' => 'tr',
        'urdu_translation' => 'ur',
    ];

    /**
     * {@inheritdoc}
     */
    protected function getValidationRules(): array
    {
        return [
            'surah_number' => 'required|integer|min:1|max:114',
            'ayah_number' => 'required|integer|min:1',
            'arabic_text' => 'required|string',
            'english_translation' => 'required|string',
            'transliteration' => 'nullable|string',
            'revelation_order' => 'nullable|integer',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getColumnMapping(): array
    {
        return [
            'surah_number' => 'surah_number',
            'ayah_number' => 'ayah_number',
            'arabic_text' => 'arabic_text',
            'english_translation' => 'english_translation',
            'transliteration' => 'transliteration',
            'revelation_order' => 'revelation_order',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function isDuplicate(array $row): bool
    {
        return Verse::query()
            ->where('surah_id', (int) $row['surah_number'])
            ->where('verse_number', (int) $row['ayah_number'])
            ->exists();
    }

    /**
     * {@inheritdoc}
     */
    protected function importRow(array $row): Verse
    {
        $surahId = (int) $row['surah_number'];
        $verseNumber = (int) $row['ayah_number'];

        if (! Surah::query()->whereKey($surahId)->exists()) {
            throw new RuntimeException("Surah {$surahId} was not found.");
        }

        $verse = Verse::updateOrCreate(
            ['surah_id' => $surahId, 'verse_number' => $verseNumber],
            [
                'verse_key' => $surahId.':'.$verseNumber,
                'juz_number' => (int) ($row['juz_number'] ?? 1),
                'hizb_number' => (int) ($row['hizb_number'] ?? 1),
                'rub_el_hizb_number' => (int) ($row['rub_el_hizb_number'] ?? 1),
                'ruku_number' => (int) ($row['ruku_number'] ?? 1),
                'page_number' => (int) ($row['page_number'] ?? 1),
            ],
        );

        $this->storeSourceText($verse, (string) $row['arabic_text']);
        $this->storeTranslations($verse, $row);

        return $verse;
    }

    /**
     * The Arabic source row: no edition, so it is matched on the nullable
     * resource_id explicitly rather than through a unique-index upsert (MySQL
     * unique indexes do not constrain NULLs, so an upsert would duplicate it).
     */
    private function storeSourceText(Verse $verse, string $text): void
    {
        $existingId = VerseTranslation::query()
            ->where('verse_id', $verse->id)
            ->where('lang', self::SOURCE_TEXT_LANG)
            ->whereNull('resource_id')
            ->value('id');

        $attributes = [
            'verse_id' => $verse->id,
            'resource_id' => null,
            'lang' => self::SOURCE_TEXT_LANG,
            'text' => $text,
        ];

        if ($existingId !== null) {
            VerseTranslation::query()->whereKey($existingId)->update($attributes);

            return;
        }

        VerseTranslation::create($attributes);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function storeTranslations(Verse $verse, array $row): void
    {
        foreach (self::TRANSLATION_COLUMNS as $column => $language) {
            $text = trim((string) ($row[$column] ?? ''));

            if ($text === '') {
                continue;
            }

            $resource = $this->resolveResource($language);

            VerseTranslation::updateOrCreate(
                ['verse_id' => $verse->id, 'resource_id' => $resource->id],
                ['lang' => $language, 'text' => $text],
            );
        }
    }

    /**
     * A CSV carries no edition metadata, so each language gets one implicit
     * "imported" edition rather than silently mixing into an existing one.
     */
    private function resolveResource(string $language): TranslationResource
    {
        return TranslationResource::firstOrCreate(
            ['slug' => 'csv-import-'.$language],
            [
                'name' => 'CSV Import ('.Str::upper($language).')',
                'language' => $language,
                'is_active' => true,
            ],
        );
    }
}
