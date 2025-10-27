<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Hadith Book API Resource
 * Transforms HadithBook model to JSON response
 */
class HadithBookResource extends JsonResource
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
            'slug' => $this->slug,
            'name' => $this->getNameInLanguage($language),
            'description' => $this->getDescriptionInLanguage($language),
            'author_name' => $this->author_name,
            'total_hadith_count' => $this->total_hadith_count,
            'display_order' => $this->display_order,
            
            // Relationships
            'chapters' => HadithChapterResource::collection($this->whenLoaded('chapters')),
        ];
    }
}

