<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Domain\Seerah\Models\SeerahEvent
 */
class SeerahEventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'description' => $this->description,
            'content' => $this->when($request->route('slug'), $this->content),
            'hijri_date' => $this->hijri_date,
            'hijri_year' => $this->hijri_year,
            'hijri_month' => $this->hijri_month,
            'hijri_day' => $this->hijri_day,
            'gregorian_date' => $this->gregorian_date?->toDateString(),
            'gregorian_year' => $this->gregorian_year,
            'formatted_hijri_date' => $this->getFormattedHijriDate(),
            'formatted_gregorian_date' => $this->getFormattedGregorianDate(),
            'event_type' => $this->event_type->value,
            'event_type_label' => $this->event_type->label(),
            'event_type_icon' => $this->event_type->icon(),
            'event_type_color' => $this->event_type->color(),
            'importance' => $this->importance->value,
            'importance_label' => $this->importance->label(),
            'importance_weight' => $this->importance->weight(),
            'location' => $this->location,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'has_location' => $this->hasLocation(),
            'featured_image' => $this->featured_image,
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

