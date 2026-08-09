<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Assign Permissions Request (Admin)
 *
 * Permissions attach to roles the caller may already hold, so this endpoint can
 * be used to inflate one's own role. Restricted to super_admin.
 */
class AssignPermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('super_admin');
    }

    public function rules(): array
    {
        return [
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ];
    }
}
