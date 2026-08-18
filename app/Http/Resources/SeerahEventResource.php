<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Seerah\Models\SeerahEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SeerahEvent
 */
class SeerahEventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->getTranslations('title'),
            'description' => $this->getTranslations('description'),
            'content' => $this->when(
                $request->route('slug'),
                fn (): array => $this->getTranslations('content')
            ),
            'hijri_date' => $this->hijri_date,
            'hijri_year' => $this->hijri_year,
            'hijri_month' => $this->hijri_month,
            'hijri_day' => $this->hijri_day,
            'gregorian_date' => $this->gregorian_date?->toDateString(),
            'gregorian_year' => $this->gregorian_year,
            'formatted_hijri_date' => $this->getFormattedHijriDate(),
            'formatted_gregorian_date' => $this->getFormattedGregorianDate(),
            'event_type' => $this->type?->slug,
            'event_type_label' => $this->type?->getTranslation('name'),
            'event_type_icon' => $this->type?->icon,
            'event_type_color' => $this->type?->color,
            'event_type_gradient' => $this->type?->gradient,
            'importance' => $this->importance?->value,
            'importance_label' => $this->importance?->label(),
            'importance_weight' => $this->importance?->weight(),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'location' => $this->location,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'has_location' => $this->hasLocation(),
            'featured_image' => $this->getFirstMediaUrl('featured_image') ?: $this->featured_image,
            'gallery' => $this->gallery,
            'views_count' => $this->views_count,
            'display_order' => $this->display_order,
            'is_featured' => $this->is_featured,
            'article' => new ArticleResource($this->whenLoaded('article')),
            'participants' => SeerahParticipantResource::collection($this->whenLoaded('participants')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
