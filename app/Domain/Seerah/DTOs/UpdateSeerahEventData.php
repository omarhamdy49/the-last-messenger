<?php

declare(strict_types=1);

namespace App\Domain\Seerah\DTOs;

use App\Http\Requests\Admin\UpdateSeerahEventRequest;
use Illuminate\Http\UploadedFile;

/**
 * Update Seerah Event DTO
 *
 * Carries the validated, type-safe payload needed to update an existing Seerah event.
 * Built from the incoming UpdateSeerahEventRequest so that service classes never
 * touch the HTTP layer directly.
 *
 * @param  array<string, string|null>  $title  Locale-keyed titles.
 * @param  array<string, string|null>  $description  Locale-keyed descriptions.
 * @param  array<string, string|null>  $content  Locale-keyed body content.
 */
final class UpdateSeerahEventData
{
    public function __construct(
        public readonly ?string $slug,
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
        public readonly ?int $displayOrder,
        public readonly bool $isFeatured,
    ) {}

    /**
     * Construct an UpdateSeerahEventData instance from the validated update request.
     */
    public static function fromRequest(UpdateSeerahEventRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            slug: $validated['slug'] ?? null,
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
            featuredImage: $request->hasFile('featured_image') ? $request->file('featured_image') : null,
            displayOrder: isset($validated['display_order']) ? (int) $validated['display_order'] : null,
            isFeatured: $request->boolean('is_featured'),
        );
    }
}
