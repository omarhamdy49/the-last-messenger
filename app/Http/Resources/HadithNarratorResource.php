<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Hadith Narrator API Resource
 *
 * Transforms HadithNarrator model for API responses.
 *
 * @mixin \App\Domain\Hadith\Models\HadithNarrator
 */
class HadithNarratorResource extends JsonResource
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
            'biography' => $this->biography[$locale] ?? $this->biography['ar'] ?? null,
            'birth_year' => $this->birth_year_hijri,
            'death_year' => $this->death_year_hijri,
            'hadiths_count' => $this->whenCounted('isnads'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
