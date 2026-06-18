<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates an admin portal login attempt.
 *
 * Guest-only route; nothing user-owned to authorize at this stage. The role
 * gate (admin/super_admin/editor) is enforced in AdminAuthController after the
 * password is verified, so that a non-admin never establishes a session.
 */
final class AdminLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }
}
