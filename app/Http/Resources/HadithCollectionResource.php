<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Hadith Collection API Resource
 *
 * Transforms HadithCollection model for API responses.
 *
 * @mixin \App\Domain\Hadith\Models\HadithCollection
 */
class HadithCollectionResource extends JsonResource
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
            'name' => $this->getNameInLocale($locale),
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'slug' => $locale === 'ar' ? $this->slug_ar : $this->slug_en,
            'abbreviation' => $this->abbreviation,
            'description' => $this->getDescriptionInLocale($locale),
            'total_hadiths' => $this->total_hadiths,
            'books_count' => $this->whenCounted('books'),
            'books' => HadithBookResource::collection($this->whenLoaded('books')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
