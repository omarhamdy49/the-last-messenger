<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Hadith Theme API Resource
 *
 * Transforms HadithTheme model for API responses.
 *
 * @mixin \App\Domain\Hadith\Models\HadithTheme
 */
class HadithThemeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = $request->header('Accept-Language', 'ar');

        return [
            'id' => $this->id,
            'name' => $locale === 'ar' ? $this->name_ar : $this->name_en,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'slug' => $locale === 'ar' ? $this->slug_ar : $this->slug_en,
            'description' => $locale === 'ar' ? $this->description_ar : $this->description_en,
            'hadiths_count' => $this->whenCounted('hadiths'),
            'hadiths' => HadithResource::collection($this->whenLoaded('hadiths')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
