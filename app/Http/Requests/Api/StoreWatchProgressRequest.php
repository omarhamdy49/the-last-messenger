<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the POST /api/v1/media/progress payload.
 *
 * IDOR note: user_id is never accepted from the request body — it is always
 * derived from auth()->id() in the controller.
 */
class StoreWatchProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route is already guarded by auth:sanctum middleware
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'episode_id' => ['required', 'integer', 'exists:media_episodes,id'],
            'seconds' => ['required', 'integer', 'min:0'],
            'completed' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'episode_id.exists' => 'Episode not found.',
            'seconds.min' => 'Watch position cannot be negative.',
        ];
    }
}
