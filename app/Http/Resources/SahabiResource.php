<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Sahaba\Enums\CompanionStatus;
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
            'name' => $this->getTranslations('name'),
            'full_name' => $this->getTranslations('full_name'),
            'kunya' => $this->getTranslations('kunya'),
            'laqab' => $this->getTranslations('laqab'),
            'display_name' => $this->when(
                $request->has('lang'),
                fn () => $this->getDisplayName($request->input('lang', 'ar'))
            ),
            'gender' => $this->gender,
            'categories' => $this->when(
                $this->relationLoaded('categories'),
                fn () => $this->categories
                    ->map(fn ($category) => CompanionStatus::tryFrom($category->key))
                    ->filter()
                    ->map(fn (CompanionStatus $category) => [
                        'key' => $category->value,
                        'label' => $category->label(),
                        'label_ar' => $category->labelArabic(),
                        'rank' => $category->rank(),
                    ])
                    ->values()
            ),
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
            'reported_hadith_count' => $this->reported_hadith_count,
            'battles' => $this->when(
                $this->relationLoaded('battles'),
                fn () => $this->battles->pluck('battle_name')->values()
            ),
            'profile_image' => $this->profile_image_url,
            'avatar' => $this->avatar_url,
            'gallery' => $this->when($request->route('slug'), $this->gallery_images),
            'views_count' => $this->views_count,
            'is_featured' => $this->is_featured,
            'importance_rank' => $this->importance_rank,
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
