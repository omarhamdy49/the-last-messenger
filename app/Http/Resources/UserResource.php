<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * User API Resource
 * Transforms User model to JSON response
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $shouldExposeEmail = $request->user()?->id === $this->id
            || $request->routeIs('v1.auth.login')
            || $request->routeIs('v1.auth.register')
            || $request->routeIs('v1.auth.user');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->when($shouldExposeEmail, $this->email),
            'language' => $this->language,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
