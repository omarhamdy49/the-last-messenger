<?php

declare(strict_types=1);

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Validates a public visitor's password reset (session/web auth).
 *
 * Mirrors RegisterRequest's password policy (min 8, mixed case, a digit,
 * confirmed). The `token` and `email` identify the broker record.
 */
final class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Guest-only route guards access.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)->mixedCase()->numbers(),
            ],
        ];
    }
}
