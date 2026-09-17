<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Importers;

use App\Domain\Seerah\Enums\SeerahEventStatus;
use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Seerah\Models\SeerahEventType;
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
 * `event_type` is a type slug; an unknown slug fails the row rather than being
 * silently re-typed.
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
            'event_type' => 'required|string|exists:seerah_event_types,slug',
            'hijri_year' => 'nullable|integer|between:-100,100',
            'hijri_month' => 'nullable|integer|between:1,12',
            'hijri_day' => 'nullable|integer|between:1,30',
            'order' => 'nullable|integer|min:0',
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
            'event_type' => 'event_type',
            'hijri_year' => 'hijri_year',
            'hijri_month' => 'hijri_month',
            'hijri_day' => 'hijri_day',
            'order' => 'order',
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
            'event_type' => SeerahEventType::query()->where('slug', trim((string) $row['event_type']))->value('id'),
            'order' => $this->nullableInt($row['order'] ?? null) ?? 0,
            'gregorian_date' => $eventDate,
            'hijri_year' => $this->nullableInt($row['hijri_year'] ?? null),
            'hijri_month' => $this->nullableInt($row['hijri_month'] ?? null),
            'hijri_day' => $this->nullableInt($row['hijri_day'] ?? null),
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
    private function nullableInt(mixed $value): ?int
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : (int) $value;
    }
}
