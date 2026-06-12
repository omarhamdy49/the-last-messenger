<?php

declare(strict_types=1);

namespace App\Domain\Sahaba\Importers;

use App\Domain\Sahaba\Enums\SahabiStatus;
use App\Domain\Sahaba\Models\Sahabi;
use App\Domain\Sahaba\Models\SahabiBattle;
use App\Domain\Shared\Importers\BaseImporter;
use Illuminate\Support\Str;

/**
 * Sahabi (Companion) Importer
 *
 * Imports companion profiles from CSV/Excel files
 */
class SahabiImporter extends BaseImporter
{
    /**
     * {@inheritdoc}
     */
    protected function getValidationRules(): array
    {
        return [
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'tribe' => 'nullable|string|max:255',
            'birth_date' => 'nullable|string|max:50',
            'death_date' => 'nullable|string|max:50',
            'battles' => 'nullable|string',
            'status' => 'nullable|in:verified,pending',
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
            'tribe' => 'tribe',
            'birth_date' => 'birth_date',
            'death_date' => 'death_date',
            'battles' => 'battles',
            'status' => 'status',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function isDuplicate(array $row): bool
    {
        $slug = Str::slug($row['name_en'] ?? $row['name_ar']);

        return Sahabi::where('slug', $slug)->exists();
    }

    /**
     * {@inheritdoc}
     */
    protected function importRow(array $row): Sahabi
    {
        $name = $this->buildMultiLanguageField($row, 'name');

        $sahabi = Sahabi::create([
            'name' => $name,
            'slug' => Str::slug($name['en'] ?: $name['ar']),
            'tribe' => $row['tribe'] ?? null,
            'birth_date' => $row['birth_date'] ?? null,
            'death_date' => $row['death_date'] ?? null,
            'status' => SahabiStatus::tryFrom($row['status'] ?? 'pending') ?? SahabiStatus::PENDING,
        ]);

        // Parse battles (comma-separated) and insert into the pivot table.
        if (! empty($row['battles'])) {
            $battleNames = array_filter(array_map('trim', explode(',', $row['battles'])));

            foreach ($battleNames as $battleName) {
                SahabiBattle::firstOrCreate([
                    'sahabi_id' => $sahabi->id,
                    'battle_name' => $battleName,
                ]);
            }
        }

        return $sahabi;
    }
}
