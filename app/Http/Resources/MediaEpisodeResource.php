<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Media\Models\MediaEpisode;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MediaEpisode
 */
class MediaEpisodeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'program_id' => $this->program_id,
            'season_number' => $this->season_number,
            'episode_number' => $this->episode_number,
            'slug' => $this->slug,
            'title' => $this->getTranslations('title'),
            'description' => $this->getTranslations('description'),
            'video_url' => $this->video_url,
            'duration_seconds' => $this->duration_seconds,
            'duration_formatted' => $this->duration_formatted,
            'is_featured' => $this->is_featured,
            'views_count' => $this->views_count,
            'thumbnail_url' => $this->getThumbnailUrl(),
            'program' => new MediaProgramResource($this->whenLoaded('program')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
