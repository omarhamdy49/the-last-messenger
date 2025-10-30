<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Domain\Sahaba\Models\Sahabi
 */
class SahabiResource extends JsonResource
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
            'name' => $this->name,
            'full_name' => $this->full_name,
            'kunya' => $this->kunya,
            'laqab' => $this->laqab,
            'display_name' => $this->when(
                $request->has('lang'),
                fn () => $this->getDisplayName($request->input('lang', 'ar'))
            ),
            'gender' => $this->gender,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_label_ar' => $this->status->labelArabic(),
            'status_rank' => $this->status->rank(),
            'biography' => $this->when($request->route('slug'), $this->biography),
            'virtues' => $this->when($request->route('slug'), $this->virtues),
            'famous_narrations' => $this->when($request->route('slug'), $this->famous_narrations),
            'birth_date_hijri' => $this->birth_date_hijri,
            'death_date_hijri' => $this->death_date_hijri,
            'birth_date_gregorian' => $this->birth_date_gregorian?->toDateString(),
            'death_date_gregorian' => $this->death_date_gregorian?->toDateString(),
            'age_at_death' => $this->age_at_death,
            'birth_place' => $this->birth_place,
            'death_place' => $this->death_place,
            'burial_place' => $this->burial_place,
            'tribe' => $this->tribe,
            'clan' => $this->clan,
            'conversion_date_hijri' => $this->conversion_date_hijri,
            'conversion_order' => $this->conversion_order,
            'roles' => $this->roles,
            'hadith_count' => $this->hadith_count,
            'battles' => $this->battles,
            'profile_image' => $this->profile_image,
            'gallery' => $this->when($request->route('slug'), $this->gallery),
            'views_count' => $this->views_count,
            'is_featured' => $this->is_featured,
            'importance_rank' => $this->importance_rank,
            'article' => new ArticleResource($this->whenLoaded('article')),
            'father' => $this->when(
                $this->relationLoaded('father') && $this->father,
                fn () => [
                    'id' => $this->father->id,
                    'name' => $this->father->name,
                    'slug' => $this->father->slug,
                ]
            ),
            'mother' => $this->when(
                $this->relationLoaded('mother') && $this->mother,
                fn () => [
                    'id' => $this->mother->id,
                    'name' => $this->mother->name,
                    'slug' => $this->mother->slug,
                ]
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
