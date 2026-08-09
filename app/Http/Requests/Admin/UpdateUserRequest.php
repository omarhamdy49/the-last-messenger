<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ProtectsPrivilegedUsers;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
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
