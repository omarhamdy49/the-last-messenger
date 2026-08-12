<?php

declare(strict_types=1);

namespace App\Domain\Seerah\DTOs;

use Illuminate\Http\UploadedFile;

/**
 * Create Seerah Event DTO
 *
 * Carries the validated, type-safe payload needed to create a new Seerah event.
 * Built from an already-validated attribute array so that neither this DTO nor
 * the service classes touch the HTTP layer; the controller unpacks the request.
 *
 * @param  array<string, string|null>  $title  Locale-keyed titles.
 * @param  array<string, string|null>  $description  Locale-keyed descriptions.
 * @param  array<string, string|null>  $content  Locale-keyed body content.
 */
final class CreateSeerahEventData
{
    public function __construct(
        public readonly string $slug,
        public readonly array $title,
        public readonly array $description,
        public readonly array $content,
        public readonly ?string $hijriDate,
        public readonly ?int $hijriYear,
        public readonly ?int $hijriMonth,
        public readonly ?int $hijriDay,
        public readonly ?string $gregorianDate,
        public readonly ?int $gregorianYear,
        public readonly ?int $seerahEventTypeId,
        public readonly ?int $seerahMilestoneId,
        public readonly ?string $importance,
        public readonly ?string $status,
        public readonly ?string $location,
        public readonly ?float $latitude,
        public readonly ?float $longitude,
        public readonly ?int $relatedArticleId,
        public readonly ?UploadedFile $featuredImage,
        public readonly int $displayOrder,
        public readonly bool $isFeatured,
        public readonly ?string $era,
        public readonly ?string $phase,
        public readonly bool $isPivotal,
        public readonly array $statistics,
        /** @var array<int, array{title?: string, description?: string}> */
        public readonly array $lessons,
        /** @var array<int, array{title?: string, author?: string, reference?: string}> */
        public readonly array $sources,
        public readonly array $timelineItems,
        public readonly array $participants,
    ) {}

    /**
     * Collapse the repeater rows into the label => value map the public event
     * page renders, dropping rows where either side was left blank.
     *
     * @param  mixed  $rows
     * @return array<string, string>
     */
    public static function normaliseStatistics($rows): array
    {
        $stats = [];

        foreach (is_array($rows) ? $rows : [] as $row) {
            $label = trim((string) ($row['label'] ?? ''));
            $value = trim((string) ($row['value'] ?? ''));

            if ($label !== '' && $value !== '') {
                $stats[$label] = $value;
            }
        }

        return $stats;
    }

    /**
     * Drop repeater rows the editor left entirely blank — the browser submits an
     * empty template row whenever one is added and then not filled in.
     *
     * @param  mixed  $rows
     * @return array<int, array<string, mixed>>
     */
    public static function rejectBlankRows($rows, string $requiredField = 'name'): array
    {
        return array_values(array_filter(
            is_array($rows) ? $rows : [],
            static fn ($row): bool => is_array($row)
                && trim((string) ($row[$requiredField]['ar'] ?? '')) !== '',
        ));
    }

    /**
     * Same as rejectBlankRows() but for repeaters whose fields are plain strings
     * rather than locale maps (lessons / sources), where the required value lives
     * at $row['title'], not $row['title']['ar'].
     *
     * @param  mixed  $rows
     * @return array<int, array<string, string>>
     */
    public static function rejectBlankFlatRows($rows, string $requiredField = 'title'): array
    {
        return array_values(array_filter(
            is_array($rows) ? $rows : [],
            static fn ($row): bool => is_array($row)
                && trim((string) ($row[$requiredField] ?? '')) !== '',
        ));
    }

    /**
     * Construct a CreateSeerahEventData instance from validated request data.
     *
     * @param  array<string, mixed>  $validated  Output of StoreSeerahEventRequest::validated()
     * @param  UploadedFile|null  $image  The uploaded featured image, if any
     */
    public static function fromArray(array $validated, ?UploadedFile $image = null): self
    {
        return new self(
            slug: $validated['slug'],
            title: $validated['title'] ?? [],
            description: $validated['description'] ?? [],
            content: $validated['content'] ?? [],
            hijriDate: $validated['hijri_date'] ?? null,
            hijriYear: isset($validated['hijri_year']) ? (int) $validated['hijri_year'] : null,
            hijriMonth: isset($validated['hijri_month']) ? (int) $validated['hijri_month'] : null,
            hijriDay: isset($validated['hijri_day']) ? (int) $validated['hijri_day'] : null,
            gregorianDate: $validated['gregorian_date'] ?? null,
            gregorianYear: isset($validated['gregorian_year']) ? (int) $validated['gregorian_year'] : null,
            seerahEventTypeId: isset($validated['seerah_event_type_id']) ? (int) $validated['seerah_event_type_id'] : null,
            seerahMilestoneId: isset($validated['seerah_milestone_id']) ? (int) $validated['seerah_milestone_id'] : null,
            importance: $validated['importance'] ?? null,
            status: $validated['status'] ?? null,
            location: $validated['location'] ?? null,
            latitude: isset($validated['latitude']) ? (float) $validated['latitude'] : null,
            longitude: isset($validated['longitude']) ? (float) $validated['longitude'] : null,
            relatedArticleId: isset($validated['related_article_id']) ? (int) $validated['related_article_id'] : null,
            featuredImage: $image,
            displayOrder: (int) ($validated['display_order'] ?? 0),
            // Unchecked checkboxes are simply absent from the payload.
            isFeatured: filter_var($validated['is_featured'] ?? false, FILTER_VALIDATE_BOOLEAN),
            era: $validated['era'] ?? null,
            phase: $validated['phase'] ?? null,
            isPivotal: filter_var($validated['is_pivotal'] ?? false, FILTER_VALIDATE_BOOLEAN),
            statistics: self::normaliseStatistics($validated['statistics'] ?? []),
            lessons: self::rejectBlankFlatRows($validated['lessons'] ?? []),
            sources: self::rejectBlankFlatRows($validated['sources'] ?? []),
            timelineItems: self::rejectBlankRows($validated['timeline_items'] ?? [], 'title'),
            participants: self::rejectBlankRows($validated['participants'] ?? [], 'name'),
        );
    }
}
