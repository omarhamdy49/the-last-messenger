<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Domain\Media\Models\MediaProgram
 */
class MediaProgramResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->getTranslations('title'),
            'description' => $this->getTranslations('description'),
            'host' => $this->getTranslations('host'),
            'category' => $this->category?->value,
            'category_label' => $this->category?->label(),
            'maturity_rating' => $this->maturity_rating?->value,
            'year' => $this->year,
            'total_seasons' => $this->total_seasons,
            'total_episodes' => $this->total_episodes,
            'rating' => (float) $this->rating,
            'is_featured' => $this->is_featured,
            'is_trending' => $this->is_trending,
            'views_count' => $this->views_count,
            'display_order' => $this->display_order,
            'poster_url' => $this->poster_url,
            'banner_url' => $this->banner_url,
            'episodes_count' => $this->whenLoaded('episodes', fn () => $this->episodes->count()),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
