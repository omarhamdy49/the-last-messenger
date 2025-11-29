<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Hadith Book API Resource
 *
 * Transforms HadithBook model for API responses.
 *
 * @mixin \App\Domain\Hadith\Models\HadithBook
 */
class HadithBookResource extends JsonResource
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
            'collection_id' => $this->collection_id,
            'title' => $this->getTitleInLocale($locale),
            'title_ar' => $this->title_ar,
            'title_en' => $this->title_en,
            'slug' => $locale === 'ar' ? $this->slug_ar : $this->slug_en,
            'chapter_title' => $this->getChapterTitleInLocale($locale),
            'hadiths_count' => $this->whenCounted('hadiths'),
            'chapters_count' => $this->whenCounted('chapters'),
            'collection' => new HadithCollectionResource($this->whenLoaded('collection')),
            'chapters' => HadithChapterResource::collection($this->whenLoaded('chapters')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
