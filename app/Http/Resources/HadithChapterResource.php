<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Hadith Chapter API Resource
 *
 * Transforms HadithChapter model for API responses.
 *
 * @mixin \App\Domain\Hadith\Models\HadithChapter
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
        $locale = $request->header('Accept-Language', 'ar');

        return [
            'id' => $this->id,
            'book_id' => $this->book_id,
            'title' => $locale === 'ar' ? $this->title_ar : $this->title_en,
            'title_ar' => $this->title_ar,
            'title_en' => $this->title_en,
            'chapter_number' => $this->chapter_number,
            'hadiths_count' => $this->whenCounted('hadiths'),
            'book' => new HadithBookResource($this->whenLoaded('book')),
            'hadiths' => HadithResource::collection($this->whenLoaded('hadiths')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
