<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Hadith Chapter API Resource
 * Transforms HadithChapter model to JSON response
 */
class HadithChapterResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $language = $request->header('Accept-Language', 'ar');

        return [
            'id' => $this->id,
            'chapter_number' => $this->chapter_number,
            'name' => $this->getNameInLanguage($language),
            'description' => $this->getDescriptionInLanguage($language),
            'display_order' => $this->display_order,

            // Relationships
            'book' => new HadithBookResource($this->whenLoaded('book')),
        ];
    }
}
