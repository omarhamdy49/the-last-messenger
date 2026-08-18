<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ProtectsPrivilegedUsers;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Store User Request (Admin)
 */
class StoreUserRequest extends FormRequest
{
    use ProtectsPrivilegedUsers;

    public function authorize(): bool
    {
        return $this->authorizeUserManagement();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            // These accounts reach /su/portal/v1/admin. They previously had the WEAKEST policy in
            // the application — a bare min:8, no complexity — while public registration required
            // mixed case + numbers and the API required 12 chars with symbols. `password` was a
            // valid admin password.
            'password' => ['required', 'string', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
            'language' => ['nullable', 'string', 'in:ar,en,fr,tr,ur'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'This email is already taken',
            'password.confirmed' => 'Password confirmation does not match',
        ];
    }
}
