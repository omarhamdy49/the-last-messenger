<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Content Version API Resource
 *
 * Transforms ContentVersion model to JSON response
 */
class ContentVersionResource extends JsonResource
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
            'versionable_type' => class_basename($this->versionable_type),
            'versionable_id' => $this->versionable_id,
            'version_number' => $this->version_number,
            'change_type' => $this->change_type,
            'change_type_label' => $this->getChangeTypeLabel(),
            'change_summary' => $this->change_summary,
            'diff' => $this->when($request->input('include_diff', false), $this->diff),
            'snapshot' => $this->when($request->input('include_snapshot', false), $this->snapshot),
            'metadata' => $this->metadata,
            'ip_address' => $this->when($request->user()?->can('view-audit-details'), $this->ip_address),
            'user_agent' => $this->when($request->user()?->can('view-audit-details'), $this->user_agent),
            'created_at' => $this->created_at->toIso8601String(),
            'created_at_human' => $this->created_at->diffForHumans(),

            // Relationships
            'changed_by' => $this->when(
                $this->relationLoaded('changedBy'),
                fn () => [
                    'id' => $this->changedBy?->id,
                    'name' => $this->changedBy?->name,
                    'email' => $this->changedBy?->email,
                ]
            ),

            // Computed
            'is_latest' => $this->when(
                method_exists($this->resource, 'isLatest'),
                fn () => $this->isLatest()
            ),
            'has_diff' => ! empty($this->diff),
            'changes_count' => is_array($this->diff) ? count($this->diff) : 0,
        ];
    }
}
