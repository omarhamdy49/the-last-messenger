<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Category API Resource
 * Transforms Category model to JSON response
 */
class CategoryResource extends JsonResource
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
            'type' => $this->type->value,
            'name' => $this->getNameInLanguage($language),
            'description' => $this->getDescriptionInLanguage($language),
            'icon' => $this->icon,
            'color' => $this->color,
            'display_order' => $this->display_order,

            // Relationships
            'parent' => new CategoryResource($this->whenLoaded('parent')),
            'children' => CategoryResource::collection($this->whenLoaded('children')),

            // Computed
            'has_children' => $this->hasChildren(),
            'is_root' => $this->isRoot(),
        ];
    }
}
