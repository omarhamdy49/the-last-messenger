<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ProtectsPrivilegedUsers;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Bulk Delete Users Request (Admin)
 *
 * Admins may bulk-delete users, but only a super_admin may remove an account
 * that holds super_admin.
 */
class BulkDeleteUsersRequest extends FormRequest
{
    use ProtectsPrivilegedUsers;

    public function authorize(): bool
    {
        if (! $this->callerManagesUsers()) {
            return false;
        }

        return $this->callerIsSuperAdmin() || ! $this->selectionIncludesSuperAdmin();
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:users,id'],
        ];
    }

    private function selectionIncludesSuperAdmin(): bool
    {
        $ids = array_filter((array) $this->input('ids', []), 'is_numeric');

        if ($ids === []) {
            return false;
        }

        return User::whereIn('id', $ids)
            ->whereHas('roles', fn ($q) => $q->where('name', self::SUPER_ADMIN))
            ->exists();
    }
}
