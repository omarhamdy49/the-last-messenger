<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Shared\Contracts\MediaReference;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin SeerahEvent
 */
class SeerahEventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * The detail-only keys (content, dispute note, details, sources, gallery,
     * media) appear on the single-event endpoint; list endpoints never load them.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isDetail = $request->routeIs('v1.seerah.show');
        $place = $this->relationLoaded('place') ? $this->place : null;

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->getEnabledTranslations('title'),
            'description' => $this->getEnabledTranslations('description'),
            'content' => $this->when($isDetail, fn (): array => $this->getEnabledTranslations('content')),
            'dispute_note' => $this->when($isDetail, fn (): array => $this->getEnabledTranslations('dispute_note')),
            'metadata' => $this->when($isDetail, fn (): array => $this->metadataFor()),
            'sources' => $this->when($isDetail, fn (): array => $this->sourcesFor()),
            'hijri_year' => $this->hijri_year,
            'hijri_month' => $this->hijri_month,
            'hijri_day' => $this->hijri_day,
            'formatted_hijri_date' => $this->formattedHijriDate(),
            'gregorian_date' => $this->gregorian_date?->toDateString(),
            'formatted_gregorian_date' => $this->getFormattedGregorianDate(),
            'era' => $this->era?->value,
            'era_label' => $this->era?->label(),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'order' => $this->order,
            'event_type' => $this->whenLoaded('type', fn (): ?array => $this->type ? [
                'id' => $this->type->id,
                'slug' => $this->type->slug,
                'name' => $this->type->name(),
                'icon' => $this->type->icon,
                'color' => $this->type->color,
                'gradient' => $this->type->gradient,
            ] : null),
            'milestone' => $this->whenLoaded('milestone', fn (): ?array => $this->milestone ? [
                'id' => $this->milestone->id,
                'slug' => $this->milestone->slug,
                'title' => $this->milestone->getTranslation('title'),
                'order' => $this->milestone->order,
            ] : null),
            'location' => $place ? [
                'id' => $place->id,
                'slug' => $place->slug,
                'name' => $place->name(),
                'latitude' => $place->latitude !== null ? (float) $place->latitude : null,
                'longitude' => $place->longitude !== null ? (float) $place->longitude : null,
            ] : null,
            'has_location' => $this->hasResolvableCoordinates(),
            'thumb_url' => $this->thumb_url ? Storage::disk('public')->url($this->thumb_url) : null,
            'cover_url' => $this->cover_url ? Storage::disk('public')->url($this->cover_url) : null,
            'gallery' => $this->when($isDetail && $this->relationLoaded('gallery'), fn (): array => $this->gallery
                ->map(fn ($item): array => [
                    'image' => Storage::disk('public')->url($item->image),
                    'caption' => $item->captionFor(),
                    'order' => $item->order,
                ])
                ->values()
                ->all()),
            // Resolved once by the service (MediaLibraryLookup) — never per row here.
            'media' => $this->when($isDetail && $this->relationLoaded('resolvedMedia'), fn (): array => $this->resolvedMedia
                ->filter(fn (MediaReference $media): bool => $media->url !== null)
                ->map(fn (MediaReference $media): array => [
                    'id' => $media->id,
                    'type' => $media->type,
                    'title' => $media->title,
                    'url' => $media->url,
                ])
                ->values()
                ->all()),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
