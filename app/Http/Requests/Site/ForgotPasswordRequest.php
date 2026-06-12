<?php

declare(strict_types=1);

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a public visitor's request for a password-reset link (session/web auth).
 *
 * Email must exist in the shared `users` table; the broker then issues a token and
 * sends the default ResetPassword notification (log mailer in this environment).
 */
final class ForgotPasswordRequest extends FormRequest
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
            'email' => ['required', 'string', 'email:rfc', 'max:255', 'exists:users,email'],
        ];
    }
}
