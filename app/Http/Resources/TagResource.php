<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Tag API Resource
 * Transforms Tag model to JSON response
 */
class TagResource extends JsonResource
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

            // Multi-language fields
            'name' => $this->getNameInLanguage($language),
            'description' => $this->getDescriptionInLanguage($language),
            'translations' => [
                'name' => is_array($this->name) ? $this->name : json_decode($this->name, true),
                'description' => is_array($this->description) ? $this->description : json_decode($this->description ?? '{}', true),
            ],

            // Other fields
            'type' => $this->type,
            'color' => $this->color,
            'usage_count' => $this->usage_count,

            // Computed
            'articles_count' => $this->when(isset($this->articles_count), $this->articles_count),

            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
