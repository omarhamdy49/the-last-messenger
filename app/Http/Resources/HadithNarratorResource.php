<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Hadith Narrator API Resource
 * Transforms HadithNarrator model to JSON response
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
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name_arabic' => $this->name_arabic,
            'name_english' => $this->name_english,
            'biography' => $this->biography,
            'reliability_grade' => [
                'value' => $this->reliability_grade->value,
                'label' => $this->reliability_grade->label(),
                'label_arabic' => $this->reliability_grade->labelArabic(),
            ],
            'is_trustworthy' => $this->isTrustworthy(),
            'position' => $this->whenPivotLoaded('hadith_narrator_chain', function () {
                return $this->pivot->position;
            }),
        ];
    }
}
