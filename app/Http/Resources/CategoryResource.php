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
            'status' => $this->status,

            // Multi-language fields
            'name' => $this->getNameInLanguage($language),
            'description' => $this->getDescriptionInLanguage($language),
            'translations' => [
                'name' => $this->getTranslations('name'),
                'description' => $this->getTranslations('description'),
            ],

            // Hierarchy fields
            'parent_id' => $this->parent_id,
            'depth' => $this->depth,

            // Visual fields
            'icon' => $this->icon,
            'color' => $this->color,
            'display_order' => $this->display_order,

            // Relationships
            'parent' => new CategoryResource($this->whenLoaded('parent')),
            'children' => CategoryResource::collection($this->whenLoaded('children')),

            // Computed properties
            'has_children' => $this->hasChildren(),
            'is_root' => $this->isRoot(),
            'is_leaf' => $this->isLeaf(),
            'is_active' => $this->isActive(),
            'articles_count' => $this->when(isset($this->articles_count), $this->articles_count),

            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
