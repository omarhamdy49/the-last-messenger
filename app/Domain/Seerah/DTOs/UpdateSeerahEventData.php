<?php

declare(strict_types=1);

namespace App\Domain\Seerah\DTOs;

use Illuminate\Http\UploadedFile;

/**
 * Update Seerah Event DTO
 *
 * Carries the validated, type-safe payload needed to update an existing Seerah event.
 * Built from an already-validated attribute array so that neither this DTO nor
 * the service classes touch the HTTP layer; the controller unpacks the request.
 *
 * A null property is ambiguous on its own — it can mean "not submitted" or
 * "submitted empty, clear it". $presentKeys resolves that: it lists the input
 * keys that were actually part of the validated payload, so the service can
 * tell an omitted field from an explicitly emptied one.
 *
 * @param  array<string, string|null>  $title  Locale-keyed titles.
 * @param  array<string, string|null>  $description  Locale-keyed descriptions.
 * @param  array<string, string|null>  $content  Locale-keyed body content.
 * @param  array<string, string|null>  $disputeNote  Locale-keyed dating-dispute notice.
 * @param  array<int, string>  $presentKeys  Input keys present in the validated payload.
 */
final class UpdateSeerahEventData
{
    public function __construct(
        public readonly ?string $slug,
        public readonly array $title,
        public readonly array $description,
        public readonly array $content,
        public readonly array $disputeNote,
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
        public readonly ?int $displayOrder,
        public readonly bool $isFeatured,
        public readonly array $presentKeys,
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
        public readonly array $existingGallery,
    ) {}

    /**
     * Whether the given input key was part of the submitted (validated) payload.
     */
    public function hasInput(string $key): bool
    {
        return in_array($key, $this->presentKeys, true);
    }

    /**
     * Construct an UpdateSeerahEventData instance from validated request data.
     *
     * @param  array<string, mixed>  $validated  Output of UpdateSeerahEventRequest::validated()
     * @param  UploadedFile|null  $image  The uploaded featured image, if any
     */
    public static function fromArray(array $validated, ?UploadedFile $image = null): self
    {
        return new self(
            slug: $validated['slug'] ?? null,
            title: $validated['title'] ?? [],
            description: $validated['description'] ?? [],
            content: $validated['content'] ?? [],
            disputeNote: $validated['dispute_note'] ?? [],
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
            displayOrder: isset($validated['display_order']) ? (int) $validated['display_order'] : null,
            // Unchecked checkboxes are simply absent from the payload.
            isFeatured: filter_var($validated['is_featured'] ?? false, FILTER_VALIDATE_BOOLEAN),
            presentKeys: array_keys($validated),
            era: $validated['era'] ?? null,
            phase: $validated['phase'] ?? null,
            isPivotal: filter_var($validated['is_pivotal'] ?? false, FILTER_VALIDATE_BOOLEAN),
            statistics: CreateSeerahEventData::normaliseStatistics($validated['statistics'] ?? []),
            lessons: CreateSeerahEventData::rejectBlankFlatRows($validated['lessons'] ?? []),
            sources: CreateSeerahEventData::rejectBlankFlatRows($validated['sources'] ?? []),
            timelineItems: CreateSeerahEventData::rejectBlankRows($validated['timeline_items'] ?? [], 'title'),
            participants: CreateSeerahEventData::rejectBlankRows($validated['participants'] ?? [], 'name'),
            existingGallery: array_values(array_filter(
                (array) ($validated['existing_gallery'] ?? []),
                static fn ($path): bool => is_string($path) && trim($path) !== '',
            )),
        );
    }
}
