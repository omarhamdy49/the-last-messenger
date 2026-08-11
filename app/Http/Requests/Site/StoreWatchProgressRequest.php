<?php

declare(strict_types=1);

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the POST /media/progress payload (web session guard).
 *
 * IDOR note: user_id is never accepted from the request body — it is always
 * derived from the authenticated user in the controller.
 */
class StoreWatchProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route is already guarded by the auth middleware.
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
}
