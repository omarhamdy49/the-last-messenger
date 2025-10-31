<?php

declare(strict_types=1);

namespace App\Domain\Shared\Importers;

use App\Domain\Quran\Models\QuranTafseer;
use App\Domain\Quran\Models\QuranSurah;
use App\Domain\Quran\Models\QuranVerse;

/**
 * Tafseer Importer
 *
 * Imports Quran verse interpretations from CSV/Excel files
 */
class TafseerImporter extends BaseImporter
{
    /**
     * {@inheritdoc}
     */
    protected function getValidationRules(): array
    {
        return [
            'surah_number' => 'required|integer|min:1|max:114',
            'ayah_number' => 'required|integer|min:1',
            'source' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'tafseer_ar' => 'required|string',
            'tafseer_en' => 'required|string',
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
            'source' => 'source',
            'author' => 'author',
            'tafseer_ar' => 'tafseer_ar',
            'tafseer_en' => 'tafseer_en',
            'tafseer_fr' => 'tafseer_fr',
            'tafseer_tr' => 'tafseer_tr',
            'tafseer_ur' => 'tafseer_ur',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function isDuplicate(array $row): bool
    {
        $surah = QuranSurah::where('surah_number', $row['surah_number'])->first();

        if (! $surah) {
            return false;
        }

        $verse = QuranVerse::where('quran_surah_id', $surah->id)
            ->where('ayah_number', $row['ayah_number'])
            ->first();

        if (! $verse) {
            return false;
        }

        return QuranTafseer::where('quran_verse_id', $verse->id)
            ->where('source', $row['source'])
            ->exists();
    }

    /**
     * {@inheritdoc}
     */
    protected function importRow(array $row): QuranTafseer
    {
        $surah = QuranSurah::where('surah_number', $row['surah_number'])->firstOrFail();
        $verse = QuranVerse::where('quran_surah_id', $surah->id)
            ->where('ayah_number', $row['ayah_number'])
            ->firstOrFail();

        $tafseer = $this->buildMultiLanguageField($row, 'tafseer');

        return QuranTafseer::create([
            'quran_verse_id' => $verse->id,
            'source' => $row['source'],
            'author' => $row['author'],
            'tafseer' => $tafseer,
        ]);
    }
}
