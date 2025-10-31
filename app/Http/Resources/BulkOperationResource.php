<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Bulk Operation Resource
 * Transforms bulk operation data for API responses
 */
class BulkOperationResource extends JsonResource
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
            'operation_type' => $this->operation_type,
            'operation_type_label' => $this->getOperationTypeLabel(),
            'module' => $this->module,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'progress' => [
                'total_items' => $this->total_items,
                'processed_count' => $this->processed_count,
                'success_count' => $this->success_count,
                'failed_count' => $this->failed_count,
                'percentage' => $this->getProgressPercentage(),
                'success_rate' => $this->getSuccessRate(),
            ],
            'creator' => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
                'email' => $this->creator->email,
            ],
            'parameters' => $this->parameters,
            'result_data' => $this->when($this->isComplete(), $this->result_data),
            'error_log' => $this->when($this->failed_count > 0, $this->error_log),
            'dry_run' => $this->dry_run,
            'notify_on_completion' => $this->notify_on_completion,
            'duration' => $this->getDuration(),
            'timestamps' => [
                'created_at' => $this->created_at?->toIso8601String(),
                'started_at' => $this->started_at?->toIso8601String(),
                'completed_at' => $this->completed_at?->toIso8601String(),
                'updated_at' => $this->updated_at?->toIso8601String(),
            ],
            'can_cancel' => $this->canBeCancelled(),
            'is_complete' => $this->isComplete(),
        ];
    }
}
