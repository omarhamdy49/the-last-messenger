<?php

declare(strict_types=1);

namespace App\Domain\Quran\Importers;

use App\Domain\Quran\Models\Surah;
use App\Domain\Quran\Models\Verse;
use App\Domain\Shared\Importers\BaseImporter;

/**
 * Quran Verse Importer
 *
 * Imports Quran verses from CSV/Excel files
 */
class QuranVerseImporter extends BaseImporter
{
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
        $surah = Surah::where('number', $row['surah_number'])->first();

        if (! $surah) {
            return false;
        }

        return Verse::where('surah_id', $surah->id)
            ->where('verse_number', $row['ayah_number'])
            ->exists();
    }

    /**
     * {@inheritdoc}
     */
    protected function importRow(array $row): Verse
    {
        $surah = Surah::where('number', $row['surah_number'])->firstOrFail();

        // Build verse key (surah:verse format, e.g., 1:1)
        $verseKey = $row['surah_number'].':'.$row['ayah_number'];

        return Verse::create([
            'surah_id' => $surah->id,
            'verse_number' => (int) $row['ayah_number'],
            'verse_key' => $verseKey,
            'text_uthmani' => $row['arabic_text'],
            'text_simple' => $row['arabic_text'],
            'translations' => [
                'ar' => $row['arabic_text'],
                'en' => $row['english_translation'],
                'fr' => $row['french_translation'] ?? '',
                'tr' => $row['turkish_translation'] ?? '',
                'ur' => $row['urdu_translation'] ?? '',
            ],
            'transliteration' => $row['transliteration'] ?? null,
            'juz_number' => isset($row['juz_number']) ? (int) $row['juz_number'] : 1,
            'page_number' => isset($row['page_number']) ? (int) $row['page_number'] : 1,
        ]);
    }
}
