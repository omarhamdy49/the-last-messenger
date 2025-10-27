<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Hadith API Resource
 * Transforms Hadith model to JSON response
 */
class HadithResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $language = $request->header('Accept-Language', 'ar');

        return [
            'id' => $this->id,
            'hadith_number' => $this->hadith_number,
            'arabic_text' => $this->arabic_text,
            'translation' => $this->getTranslation($language),
            'narrator_chain' => $this->narrator_chain,
            'grade' => [
                'value' => $this->grade->value,
                'label' => $this->grade->label(),
                'label_arabic' => $this->grade->labelArabic(),
            ],
            'reference_url' => $this->reference_url,
            'views_count' => $this->views_count,
            'is_authentic' => $this->isAuthentic(),
            'is_reliable' => $this->isReliable(),
            
            // Relationships
            'book' => new HadithBookResource($this->whenLoaded('book')),
            'chapter' => new HadithChapterResource($this->whenLoaded('chapter')),
            'narrators' => HadithNarratorResource::collection($this->whenLoaded('narrators')),
            
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}

