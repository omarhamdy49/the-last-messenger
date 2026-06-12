<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Importers;

use App\Domain\Seerah\Enums\EventStatus;
use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Shared\Importers\BaseImporter;
use Illuminate\Support\Str;

/**
 * Seerah Event Importer
 *
 * Imports timeline events from CSV/Excel files
 */
class SeerahEventImporter extends BaseImporter
{
    /**
     * {@inheritdoc}
     */
    protected function getValidationRules(): array
    {
        return [
            'title_ar' => 'required|string|max:255',
            'title_en' => 'required|string|max:255',
            'description_ar' => 'required|string',
            'description_en' => 'required|string',
            'event_date' => 'required|date',
            'hijri_date' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:255',
            'status' => 'nullable|in:published,draft',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getColumnMapping(): array
    {
        return [
            'title_ar' => 'title_ar',
            'title_en' => 'title_en',
            'title_fr' => 'title_fr',
            'title_tr' => 'title_tr',
            'title_ur' => 'title_ur',
            'description_ar' => 'description_ar',
            'description_en' => 'description_en',
            'description_fr' => 'description_fr',
            'description_tr' => 'description_tr',
            'description_ur' => 'description_ur',
            'event_date' => 'event_date',
            'hijri_date' => 'hijri_date',
            'location' => 'location',
            'status' => 'status',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function isDuplicate(array $row): bool
    {
        $slug = Str::slug($row['title_en'] ?? $row['title_ar']);

        return SeerahEvent::where('slug', $slug)->exists();
    }

    /**
     * {@inheritdoc}
     */
    protected function importRow(array $row): SeerahEvent
    {
        $title = $this->buildMultiLanguageField($row, 'title');
        $description = $this->buildMultiLanguageField($row, 'description');

        return SeerahEvent::create([
            'title' => $title,
            'description' => $description,
            'slug' => Str::slug($title['en'] ?: $title['ar']),
            'event_date' => $this->transformDate($row['event_date']),
            'hijri_date' => $row['hijri_date'] ?? null,
            'location' => $row['location'] ?? null,
            'status' => EventStatus::tryFrom($row['status'] ?? 'draft') ?? EventStatus::DRAFT,
        ]);
    }
}
