<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Article API Resource
 * Transforms Article model to JSON response
 */
class ArticleResource extends JsonResource
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
            'title' => $this->getTitleInLanguage($language),
            'excerpt' => $this->getExcerptInLanguage($language),
            'content' => $this->when($request->routeIs('articles.show'), $this->getContentInLanguage($language)),
            'featured_image' => $this->featured_image,
            'reading_time_minutes' => $this->reading_time_minutes,
            'views_count' => $this->views_count,
            'favorites_count' => $this->favorites_count,
            'status' => $this->status->value,
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            
            // Relationships
            'author' => new UserResource($this->whenLoaded('author')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'series' => new SeriesResource($this->whenLoaded('series')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            
            // Computed
            'is_published' => $this->isPublished(),
            'url' => route('articles.show', $this->slug),
        ];
    }
}

