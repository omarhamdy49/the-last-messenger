<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * User Engagement Metric Resource
 * Transforms UserEngagementMetric model for API responses
 */
class UserEngagementMetricResource extends JsonResource
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
            'engagement_score' => $this->engagement_score,
            'engagement_level' => $this->engagement_level,
            'total_interactions' => $this->total_interactions,
        ];
    }
}
