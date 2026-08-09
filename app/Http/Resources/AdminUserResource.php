<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Admin User Resource
 *
 * The portal's view of a user. Kept separate from {@see UserResource}, which
 * serves the public API and deliberately hides another user's email — the admin
 * edit form needs the full record, including the assigned roles it pre-selects.
 */
class AdminUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'language' => $this->language,
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'is_verified' => $this->email_verified_at !== null,
            'roles' => $this->whenLoaded(
                'roles',
                fn () => $this->roles->map(fn ($role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                ])->values()
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
