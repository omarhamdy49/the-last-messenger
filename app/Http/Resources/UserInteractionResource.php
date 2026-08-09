<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * User Interaction Resource
 * Transforms UserInteraction model for API responses
 */
class UserInteractionResource extends JsonResource
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
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                ];
            }),
            'interactable_type' => class_basename($this->interactable_type),
            'interactable_id' => $this->interactable_id,
            'interaction_type' => $this->interaction_type->value,
            'interaction_label' => $this->interaction_type->label(),
            'points' => $this->interaction_type->getPoints(),
            'metadata' => $this->metadata,
            'duration_seconds' => $this->duration_seconds,
            'duration_formatted' => $this->duration_seconds ? $this->formatDuration($this->duration_seconds) : null,
            'device_type' => $this->device_type,
            'platform' => $this->platform,
            'created_at' => $this->created_at->toDateTimeString(),
            'created_at_human' => $this->created_at->diffForHumans(),
        ];
    }

    /**
     * Format duration in seconds to human-readable format
     */
    protected function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds}s";
        }

        $minutes = floor($seconds / 60);
        if ($minutes < 60) {
            $remainingSeconds = $seconds % 60;

            return "{$minutes}m {$remainingSeconds}s";
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        return "{$hours}h {$remainingMinutes}m";
    }
}
