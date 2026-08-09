<?php

declare(strict_types=1);

namespace App\Domain\Quran\Importers;

use App\Domain\Quran\Models\TafsirResource;
use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Models\VerseTafsir;
use App\Domain\Shared\Importers\BaseImporter;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Tafseer Importer
 *
 * Imports Quran verse interpretations from CSV/Excel files.
 *
 * Schema mapping (the CSV contract in ImportController::getModuleColumns is
 * unchanged; only the persistence side was rewritten):
 *
 *  - `surah_number` + `ayah_number` locate the verse through its canonical
 *    `verse_key` ("surah:ayah"). quran_surahs.id *is* the surah number.
 *  - Tafsir text hangs off a language-specific `TafsirResource` (the edition),
 *    so each populated `tafseer_{lang}` column resolves its own resource from
 *    the row's `source`/`author` and writes one `quran_verse_tafsirs` row.
 *  - Rows are keyed on the `(verse_id, resource_id)` unique index, so a
 *    re-import updates in place rather than duplicating.
 *
 * The previous version targeted a schema that no longer exists: it referenced
 * three classes that were never defined (QuranSurah, QuranVerse, QuranTafseer)
 * and wrote columns (`quran_verse_id`, `source`, `author`, `tafseer`) that are
 * not on the table — every tafseer import fatalled on the first row.
 */
class TafseerImporter extends BaseImporter
{
    /** Languages the CSV may carry, matching getColumnMapping(). */
    private const LANGUAGES = ['ar', 'en', 'fr', 'tr', 'ur'];

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
        $verse = $this->findVerse($row);

        if ($verse === null) {
            return false;
        }

        $resourceIds = TafsirResource::query()
            ->where('source', (string) $row['source'])
            ->pluck('id');

        if ($resourceIds->isEmpty()) {
            return false;
        }

        return VerseTafsir::query()
            ->where('verse_id', $verse->id)
            ->whereIn('resource_id', $resourceIds)
            ->exists();
    }

    /**
     * {@inheritdoc}
     */
    protected function importRow(array $row): Verse
    {
        $verse = $this->findVerse($row);

        if ($verse === null) {
            throw new RuntimeException(
                "Verse {$row['surah_number']}:{$row['ayah_number']} was not found."
            );
        }

        foreach (self::LANGUAGES as $language) {
            $text = trim((string) ($row["tafseer_{$language}"] ?? ''));

            if ($text === '') {
                continue;
            }

            $resource = $this->resolveResource((string) $row['source'], (string) $row['author'], $language);

            VerseTafsir::updateOrCreate(
                ['verse_id' => $verse->id, 'resource_id' => $resource->id],
                [
                    'verse_key' => $verse->verse_key,
                    'lang' => $language,
                    'text' => $text,
                    'active' => true,
                ],
            );
        }

        return $verse;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function findVerse(array $row): ?Verse
    {
        $verseKey = ((int) $row['surah_number']).':'.((int) $row['ayah_number']);

        return Verse::query()->where('verse_key', $verseKey)->first();
    }

    /**
     * Editions are language-specific, so one CSV `source` maps to one resource
     * per language. Matched on (source, language) and created when absent —
     * otherwise an import would need the editions seeded by hand first.
     */
    private function resolveResource(string $source, string $author, string $language): TafsirResource
    {
        return TafsirResource::firstOrCreate(
            ['source' => $source, 'language' => $language],
            [
                'slug' => Str::slug($source.'-'.$language) ?: 'tafsir-'.$language.'-'.Str::random(6),
                'name' => $source,
                'author' => $author,
                'is_active' => true,
            ],
        );
    }
}
