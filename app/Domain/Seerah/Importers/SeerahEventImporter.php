<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Importers;

use App\Domain\Seerah\Enums\SeerahEventStatus;
use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Shared\Importers\BaseImporter;
use Illuminate\Support\Str;

/**
 * Seerah Event Importer
 *
 * Imports timeline events from CSV/Excel files.
 *
 * Titles and descriptions live in `seerah_event_translations` (one row per
 * locale), so the event row is created first and the locale columns are pushed
 * through syncTranslations(), mirroring the admin controller flow.
 *
 * The `event_date` CSV column maps to the `gregorian_date` DB column; the
 * template column name is kept so existing import files keep working.
 */
class SeerahEventImporter extends BaseImporter
{
    /**
     * {@inheritdoc}
     */
    protected function getValidationRules(): array
    {
        return [
            'title_ar' => 'required|string|max:500',
            'title_en' => 'required|string|max:500',
            'description_ar' => 'required|string',
            'description_en' => 'required|string',
            'event_date' => 'required|date',
            'hijri_date' => 'nullable|string|max:100',
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
        return SeerahEvent::where('slug', $this->buildSlug($row))->exists();
    }

    /**
     * {@inheritdoc}
     */
    protected function importRow(array $row): SeerahEvent
    {
        $eventDate = $this->transformDate($row['event_date'] ?? null);

        $event = SeerahEvent::create([
            'slug' => $this->buildSlug($row),
            'gregorian_date' => $eventDate,
            'gregorian_year' => $eventDate !== null ? (int) substr($eventDate, 0, 4) : null,
            'hijri_date' => $this->nullableString($row['hijri_date'] ?? null),
            'location' => $this->nullableString($row['location'] ?? null),
            'status' => SeerahEventStatus::tryFrom(trim((string) ($row['status'] ?? ''))) ?? SeerahEventStatus::DRAFT,
        ]);

        $event->syncTranslations([
            'title' => $this->buildMultiLanguageField($row, 'title'),
            'description' => $this->buildMultiLanguageField($row, 'description'),
        ]);

        return $event;
    }

    /**
     * Build the event slug from the English title, falling back to the Arabic
     * one and finally to a random token so the NOT NULL/UNIQUE column is always
     * satisfiable (Str::slug() strips non-transliterable scripts to '').
     *
     * @param  array<string, mixed>  $row
     */
    private function buildSlug(array $row): string
    {
        foreach (['title_en', 'title_ar'] as $field) {
            $slug = Str::slug((string) ($row[$field] ?? ''));

            if ($slug !== '') {
                return $slug;
            }
        }

        return 'seerah-event-'.Str::lower(Str::random(10));
    }

    /**
     * Normalise a blank spreadsheet cell to NULL.
     */
    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }
}
