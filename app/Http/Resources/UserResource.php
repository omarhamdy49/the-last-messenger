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
            || $request->routeIs('auth.login')
            || $request->routeIs('auth.register')
            || $request->routeIs('auth.user');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->when($shouldExposeEmail, $this->email),
            'language' => $this->language,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
