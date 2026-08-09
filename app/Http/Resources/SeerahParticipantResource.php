<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Domain\Seerah\Models\SeerahParticipant
 */
class SeerahParticipantResource extends JsonResource
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
            'participant_type' => $this->participant_type,
            'participant_id' => $this->participant_id,
            // Locale-keyed maps, matching how SeerahEventResource exposes
            // translatable fields. The repository eager-loads
            // participants.translations (filtered to the requested locale on
            // the public path), so this does not trigger extra queries.
            'name' => $this->getTranslations('name'),
            'role' => $this->role->value,
            'role_label' => $this->role->label(),
            'description' => $this->getTranslations('description'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
