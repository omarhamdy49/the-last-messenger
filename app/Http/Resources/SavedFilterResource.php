<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Saved Filter Resource
 *
 * @mixin \App\Domain\Shared\Models\SavedFilter
 */
class SavedFilterResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'module' => $this->module,
            'filters' => $this->filters,
            'sort_options' => $this->sort_options,
            'is_public' => $this->is_public,
            'is_default' => $this->is_default,
            'usage_count' => $this->usage_count,
            'last_used_at' => $this->last_used_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'user' => [
                'id' => $this->user_id,
                'name' => $this->whenLoaded('user', fn () => $this->user->name),
            ],
        ];
    }
}
