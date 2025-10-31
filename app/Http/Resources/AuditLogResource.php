<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Audit Log API Resource
 *
 * Transforms AuditLog model to JSON response
 */
class AuditLogResource extends JsonResource
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
            'action' => $this->action,
            'action_label' => $this->getActionLabel(),
            'subject_type' => class_basename($this->subject_type),
            'subject_id' => $this->subject_id,
            'description' => $this->description,
            'old_values' => $this->when($request->input('include_values', false), $this->old_values),
            'new_values' => $this->when($request->input('include_values', false), $this->new_values),
            'changes_summary' => $this->when($request->input('include_changes', false), $this->getChangesSummary()),
            'metadata' => $this->metadata,
            'ip_address' => $this->when($request->user()?->can('view-audit-details'), $this->ip_address),
            'user_agent' => $this->when($request->user()?->can('view-audit-details'), $this->user_agent),
            'url' => $this->when($request->user()?->can('view-audit-details'), $this->url),
            'method' => $this->when($request->user()?->can('view-audit-details'), $this->method),
            'created_at' => $this->created_at->toIso8601String(),
            'created_at_human' => $this->created_at->diffForHumans(),

            // Relationships
            'user' => $this->when(
                $this->relationLoaded('user'),
                fn () => [
                    'id' => $this->user?->id,
                    'name' => $this->user?->name,
                    'email' => $this->user?->email,
                ]
            ),

            // Computed
            'is_create' => $this->isCreate(),
            'is_update' => $this->isUpdate(),
            'is_delete' => $this->isDelete(),
            'has_changes' => ! empty($this->old_values) || ! empty($this->new_values),
        ];
    }
}
