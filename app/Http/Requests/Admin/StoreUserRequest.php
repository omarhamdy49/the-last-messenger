<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ProtectsPrivilegedUsers;
use Illuminate\Foundation\Http\FormRequest;

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
            'password' => ['required', 'string', 'min:8', 'confirmed'],
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
