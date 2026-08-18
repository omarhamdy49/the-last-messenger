<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ProtectsPrivilegedUsers;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Update User Request (Admin)
 */
class UpdateUserRequest extends FormRequest
{
    use ProtectsPrivilegedUsers;

    public function authorize(): bool
    {
        return $this->authorizeUserManagement();
    }

    public function rules(): array
    {
        $userId = $this->targetUserId();

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            // Same policy as creation — see StoreUserRequest.
            'password' => ['nullable', 'string', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
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
