<?php

declare(strict_types=1);

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Validates public visitor registration (session/web auth).
 *
 * Password requires min 8 chars, mixed case and a digit; `password_confirmation`
 * must match. Email is unique against the shared `users` table.
 */
final class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Guest-only route guards access; nothing user-owned to authorize here.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)->mixedCase()->numbers(),
            ],
        ];
    }
}
