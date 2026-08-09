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
    ) {}

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
        );
    }
}
