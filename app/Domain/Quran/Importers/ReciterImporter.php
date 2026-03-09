<?php

declare(strict_types=1);

namespace App\Domain\Quran\Importers;

use App\Domain\Quran\Models\QuranReciter;
use App\Domain\Shared\Importers\BaseImporter;
use Illuminate\Support\Str;

/**
 * Reciter Importer
 *
 * Imports Quran reciters from CSV/Excel files
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
        $slug = Str::slug($row['name_en'] ?? $row['name_ar']);

        return QuranReciter::where('slug', $slug)->exists();
    }

    /**
     * {@inheritdoc}
     */
    protected function importRow(array $row): QuranReciter
    {
        $name = $this->buildMultiLanguageField($row, 'name');
        $biography = $this->buildMultiLanguageField($row, 'biography');

        return QuranReciter::create([
            'name' => $name,
            'slug' => Str::slug($name['en'] ?: $name['ar']),
            'country' => $row['country'] ?? null,
            'style' => $row['style'] ?? null,
            'biography' => $biography,
            'is_featured' => $this->transformBoolean($row['featured'] ?? false),
        ]);
    }
}
