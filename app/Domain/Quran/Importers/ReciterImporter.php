<?php

declare(strict_types=1);

namespace App\Domain\Quran\Importers;

use App\Domain\Quran\Models\Reciter;
use App\Domain\Shared\Importers\BaseImporter;
use Illuminate\Support\Str;

/**
 * Reciter Importer
 *
 * Imports Quran reciters from CSV/Excel files.
 *
 * Schema mapping (the CSV contract is unchanged; only persistence was rewritten):
 *
 *  - `quran_reciters` holds the attributes only. Names and biographies live in
 *    `reciter_translations` (name, description) and are written with
 *    syncTranslations(), so the CSV's biography_* columns map to `description`.
 *  - Matching is on `slug`, derived from the English name (Arabic as fallback).
 *
 * The previous version referenced `QuranReciter`, a class that does not exist,
 * and wrote `name`/`biography` columns that are not on the table — so every
 * reciter import fatalled before inserting a row.
 */
class ReciterImporter extends BaseImporter
{
    /**
     * {@inheritdoc}
     */
    protected function getValidationRules(): array
    {
        return [
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'country' => 'nullable|string|max:100',
            'style' => 'nullable|string|max:100',
            'featured' => 'nullable|string',
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
            'name_fr' => 'name_fr',
            'name_tr' => 'name_tr',
            'name_ur' => 'name_ur',
            'country' => 'country',
            'style' => 'style',
            'biography_ar' => 'biography_ar',
            'biography_en' => 'biography_en',
            'biography_fr' => 'biography_fr',
            'biography_tr' => 'biography_tr',
            'biography_ur' => 'biography_ur',
            'featured' => 'featured',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function isDuplicate(array $row): bool
    {
        return Reciter::query()->where('slug', $this->slugFor($row))->exists();
    }

    /**
     * {@inheritdoc}
     */
    protected function importRow(array $row): Reciter
    {
        $names = $this->buildMultiLanguageField($row, 'name');
        $biographies = $this->buildMultiLanguageField($row, 'biography');

        $reciter = Reciter::updateOrCreate(
            ['slug' => $this->slugFor($row)],
            [
                'country' => $row['country'] ?? null,
                'style' => $row['style'] ?? null,
                'is_featured' => $this->transformBoolean($row['featured'] ?? false),
                'is_active' => true,
            ],
        );

        // name/description live in reciter_translations, not on the reciter row.
        $reciter->syncTranslations([
            'name' => array_filter($names, static fn ($value): bool => (string) $value !== ''),
            'description' => array_filter($biographies, static fn ($value): bool => (string) $value !== ''),
        ]);

        return $reciter;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function slugFor(array $row): string
    {
        $source = (string) ($row['name_en'] ?? '') !== ''
            ? (string) $row['name_en']
            : (string) ($row['name_ar'] ?? '');

        // Str::slug() returns '' for non-transliterable scripts, so an
        // Arabic-only row would otherwise collide on an empty slug.
        return Str::slug($source) ?: 'reciter-'.Str::lower(Str::random(8));
    }
}
