<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Role Service
 *
 * Encapsulates all business logic for role and permission management,
 * keeping the admin controller thin and free of direct Eloquent calls.
 */
class RoleService
{
    /**
     * System roles that are protected from deletion.
     */
    private const PROTECTED_ROLES = ['super_admin', 'admin'];

    /**
     * Return a paginated list of roles with column-specific eager load.
     *
     * @param  array<string, mixed>  $filters  Supported keys: search (string), per_page (int)
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 15);
        $search = $filters['search'] ?? null;

        return Role::select(['id', 'name', 'guard_name', 'created_at', 'updated_at'])
            ->with('permissions:id,name')
            ->when($search, static function ($query, $search): void {
                $query->where('name', 'like', "%{$search}%");
            })
            ->paginate($perPage);
    }

    /**
     * Return aggregate stats about roles.
     *
     * @return array{total: int, with_permissions: int}
     */
    public function stats(): array
    {
        return [
            'total' => Role::count(),
            'with_permissions' => Role::has('permissions')->count(),
        ];
    }

    /**
     * Create a new role and optionally sync permissions by name.
     *
     * @param  array<string>  $permissionNames  Permission names (not IDs)
     */
    public function create(string $name, array $permissionNames = []): Role
    {
        $role = Role::create(['name' => $name]);

        if ($permissionNames !== []) {
            $role->syncPermissions($permissionNames);
        }

        return $role->load('permissions:id,name');
    }

    /**
     * Update a role's name and permission set.
     *
     * Returns the updated Role with its old attribute snapshot so the caller
     * can pass both to AdminLoggerService::logUpdate() without doing a second
     * Eloquent fetch.
     *
     * @param  array<string>  $permissionNames
     * @return array{role: Role, old_values: array<string, mixed>}
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function update(int $id, string $name, array $permissionNames = []): array
    {
        $role = Role::with('permissions:id,name')->findOrFail($id);

        $oldValues = $role->toArray();
        $oldValues['permissions'] = $role->permissions->pluck('name')->toArray();

        $role->update(['name' => $name]);

        if ($permissionNames !== []) {
            $role->syncPermissions($permissionNames);
        }

        $role->load('permissions:id,name');

        return ['role' => $role, 'old_values' => $oldValues];
    }

    /**
     * Delete a single role after business-rule validation.
     *
     * Throws \RuntimeException for protected roles.
     * Returns the deleted Role model so the caller can pass it to AdminLoggerService.
     *
     * @throws \RuntimeException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function delete(int $id): Role
    {
        $role = Role::findOrFail($id);

        if (in_array($role->name, self::PROTECTED_ROLES, true)) {
            throw new \RuntimeException("Cannot delete protected role: {$role->name}");
        }

        $role->delete();

        return $role;
    }

    /**
     * Bulk-delete roles inside a DB transaction, silently skipping protected roles.
     *
     * Returns a summary so the controller can build its response message.
     *
     * @param  array<int>  $ids
     * @return array{deleted: int, protected: array<string>}
     */
    public function bulkDelete(array $ids): array
    {
        $deleted = 0;
        $protected = [];

        DB::transaction(function () use ($ids, &$deleted, &$protected): void {
            foreach ($ids as $id) {
                $role = Role::select(['id', 'name'])->find($id);

                if (! $role) {
                    continue;
                }

                if (in_array($role->name, self::PROTECTED_ROLES, true)) {
                    $protected[] = $role->name;

                    continue;
                }

                $role->delete();
                $deleted++;
            }
        });

        return ['deleted' => $deleted, 'protected' => $protected];
    }

    /**
     * Sync permissions on an existing role by name.
     *
     * @param  array<string>  $permissionNames
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function syncPermissions(int $id, array $permissionNames): Role
    {
        $role = Role::findOrFail($id);
        $role->syncPermissions($permissionNames);

        return $role->load('permissions:id,name');
    }

    /**
     * Evaluate whether a role may be deleted and why not if not.
     *
     * @return array{allowed: bool, reason: string}
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function canDelete(int $id): array
    {
        $role = Role::select(['id', 'name'])->findOrFail($id);

        if (in_array($role->name, self::PROTECTED_ROLES, true)) {
            return ['allowed' => false, 'reason' => "Role '{$role->name}' is a protected system role."];
        }

        $userCount = User::role($role->name)->count();

        if ($userCount > 0) {
            return ['allowed' => false, 'reason' => "Role has {$userCount} assigned user(s). Reassign them first."];
        }

        return ['allowed' => true, 'reason' => ''];
    }

    /**
     * Return all permissions with only the columns needed by the UI.
     */
    public function getPermissions(): Collection
    {
        return Permission::select(['id', 'name'])->get();
    }

    /**
     * Find a single role with its full permission data.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getRoleWithPermissions(int $id): Role
    {
        return Role::select(['id', 'name', 'guard_name', 'created_at', 'updated_at'])
            ->with('permissions:id,name')
            ->findOrFail($id);
    }

    /**
     * Return the number of users currently assigned to a role.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getUserCount(int $id): int
    {
        $role = Role::select(['id', 'name'])->findOrFail($id);

        return User::role($role->name)->count();
    }

    /**
     * Search roles by name fragment.
     */
    public function search(string $query): Collection
    {
        return Role::select(['id', 'name', 'guard_name', 'created_at', 'updated_at'])
            ->with('permissions:id,name')
            ->where('name', 'like', "%{$query}%")
            ->get();
    }

    /**
     * Clone a role with all of its permissions under a new name.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function cloneRole(int $originalId, string $newName): array
    {
        $originalRole = Role::select(['id', 'name'])
            ->with('permissions:id,name')
            ->findOrFail($originalId);

        $newRole = Role::create(['name' => $newName]);

        $permissionNames = $originalRole->permissions->pluck('name')->toArray();
        $newRole->syncPermissions($permissionNames);

        return [
            'role' => $newRole->load('permissions:id,name'),
            'original_role' => $originalRole,
            'permissions_count' => count($permissionNames),
        ];
    }

    /**
     * Build the permission matrix (all roles x all permissions).
     *
     * @return array{matrix: array<string, array{id: int, permissions: array<string, true>}>, all_permissions: Collection}
     */
    public function getPermissionMatrix(): array
    {
        $roles = Role::select(['id', 'name'])->with('permissions:id,name')->get();
        $allPermissions = Permission::select(['id', 'name'])->get();

        $matrix = [];
        foreach ($roles as $role) {
            $rolePermissions = $role->permissions->pluck('name')->toArray();
            $matrix[$role->name] = [
                'id' => $role->id,
                'permissions' => array_fill_keys($rolePermissions, true),
            ];
        }

        return ['matrix' => $matrix, 'all_permissions' => $allPermissions];
    }

    /**
     * Evaluate whether a role name belongs to the protected set.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function isSystemRole(int $id): array
    {
        $role = Role::select(['id', 'name'])->findOrFail($id);
        $isSystem = in_array($role->name, self::PROTECTED_ROLES, true);

        return [
            'role_id' => $role->id,
            'role_name' => $role->name,
            'is_system' => $isSystem,
        ];
    }
}
