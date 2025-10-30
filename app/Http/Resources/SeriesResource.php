<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Series API Resource
 * Transforms Series model to JSON response
 */
class SeriesResource extends JsonResource
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
            'slug' => $this->slug,
            'title' => $this->getTitleInLanguage($language),
            'description' => $this->getDescriptionInLanguage($language),
            'thumbnail' => $this->thumbnail,
            'total_articles' => $this->total_articles,

            // Relationships
            'articles' => ArticleResource::collection($this->whenLoaded('articles')),
        ];
    }
}
