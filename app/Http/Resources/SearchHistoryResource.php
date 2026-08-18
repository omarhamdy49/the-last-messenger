<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Shared\Models\SearchHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Search History Resource
 *
 * @mixin SearchHistory
 */
class SearchHistoryResource extends JsonResource
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
            'query' => $this->query,
            'module' => $this->module,
            'filters' => $this->filters,
            'results_count' => $this->results_count,
            'results_meta' => $this->results_meta,
            'searched_at' => $this->searched_at?->toISOString(),
            'user' => [
                'id' => $this->user_id,
                'name' => $this->whenLoaded('user', fn () => $this->user->name),
            ],
        ];
    }
}
