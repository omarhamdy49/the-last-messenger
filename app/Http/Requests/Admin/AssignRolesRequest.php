<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ProtectsPrivilegedUsers;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Assign Roles Request (Admin)
 *
 * Roles are grantable privileges: without a gate this endpoint lets any
 * admin-tier user (including editors) grant themselves super_admin.
 */
class AssignRolesRequest extends FormRequest
{
    use ProtectsPrivilegedUsers;

    public function authorize(): bool
    {
        return $this->authorizeUserManagement();
    }

    public function rules(): array
    {
        return [
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', 'exists:roles,name'],
        ];
    }
}
