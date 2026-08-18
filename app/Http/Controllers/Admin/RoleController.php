<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Shared\Services\AdminLoggerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignPermissionsRequest;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Services\RoleService;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

/**
 * Admin Role Controller
 * Handles role and permission management for admin portal.
 * All business logic lives in RoleService.
 */
class RoleController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AdminLoggerService $logger,
        protected RoleService $roleService,
    ) {}

    /**
     * Display a listing of all roles.
     *
     * GET /su/portal/v1/admin/roles
     */
    public function index(Request $request): View|JsonResponse
    {
        $roles = $this->roleService->list([
            'search' => $request->input('search'),
            'per_page' => $request->integer('per_page', 15),
        ]);

        $stats = $this->roleService->stats();

        if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true,
                'data' => $roles->items(),
                'stats' => $stats,
                'meta' => [
                    'current_page' => $roles->currentPage(),
                    'per_page' => $roles->perPage(),
                    'total' => $roles->total(),
                    'last_page' => $roles->lastPage(),
                ],
            ]);
        }

        return view('admin.roles.index', compact('roles', 'stats'));
    }

    /**
     * Show the form for creating a new role.
     *
     * GET /su/portal/v1/admin/roles/create
     */
    public function create(): View
    {
        return view('admin.roles.create');
    }

    /**
     * Store a newly created role.
     *
     * POST /su/portal/v1/admin/roles
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = $this->roleService->create(
            name: $request->input('name'),
            permissionNames: (array) $request->input('permissions', []),
        );

        $this->logger->logCreate($role, $request->user());

        return $this->createdResponse($role, 'Role created successfully');
    }

    /**
     * Display the specified role.
     *
     * GET /su/portal/v1/admin/roles/{id}
     */
    public function show(int $id): JsonResponse
    {
        $role = $this->roleService->getRoleWithPermissions($id);

        if (! $role) {
            return $this->notFoundResponse('Role not found');
        }

        return $this->successResponse($role);
    }

    /**
     * Show the form for editing a role.
     *
     * GET /su/portal/v1/admin/roles/{id}/edit
     */
    public function edit(int $id): View
    {
        return view('admin.roles.edit', ['id' => $id]);
    }

    /**
     * Update the specified role.
     *
     * PUT/PATCH /su/portal/v1/admin/roles/{id}
     */
    public function update(UpdateRoleRequest $request, int $id): JsonResponse
    {
        $role = $this->roleService->getRoleWithPermissions($id);

        if (! $role) {
            return $this->notFoundResponse('Role not found');
        }

        $result = $this->roleService->update(
            id: $id,
            name: $request->input('name'),
            permissionNames: (array) $request->input('permissions', []),
        );

        $newValues = $result['role']->toArray();
        $newValues['permissions'] = $result['role']->permissions->pluck('name')->toArray();

        $this->logger->logUpdate($result['role'], $result['old_values'], $newValues, $request->user());

        return $this->successResponse($result['role'], 'Role updated successfully');
    }

    /**
     * Remove the specified role.
     *
     * DELETE /su/portal/v1/admin/roles/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $role = $this->roleService->getRoleWithPermissions($id);

        if (! $role) {
            return $this->notFoundResponse('Role not found');
        }

        $check = $this->roleService->canDelete($id);

        if (! $check['allowed']) {
            return $this->errorResponse($check['reason'], 403);
        }

        $deletedRole = $this->roleService->delete($id);

        $this->logger->logDelete($deletedRole, request()->user());

        return $this->successResponse(null, 'Role deleted successfully');
    }

    /**
     * Get all permissions.
     *
     * GET /su/portal/v1/admin/permissions
     */
    public function permissions(): JsonResponse
    {
        return $this->successResponse($this->roleService->getPermissions());
    }

    /**
     * Assign permissions to role.
     *
     * POST /su/portal/v1/admin/roles/{id}/permissions
     */
    public function assignPermissions(AssignPermissionsRequest $request, int $id): JsonResponse
    {
        $role = $this->roleService->getRoleWithPermissions($id);

        if (! $role) {
            return $this->notFoundResponse('Role not found');
        }

        $oldPermissions = $role->permissions->pluck('name')->toArray();

        $updatedRole = $this->roleService->syncPermissions($id, $request->input('permissions'));

        $this->logger->logCustomAction(
            'assign_permissions',
            $updatedRole,
            $request->user(),
            ['old_permissions' => $oldPermissions],
            ['new_permissions' => $request->input('permissions')],
        );

        return $this->successResponse($updatedRole, 'Permissions assigned successfully');
    }

    /**
     * Bulk delete roles (with system role protection).
     *
     * POST /su/portal/v1/admin/roles/bulk-delete
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:roles,id'],
        ]);

        $ids = $request->input('ids');
        $result = $this->roleService->bulkDelete($ids);

        $this->logger->logBulkAction('bulk_delete', Role::class, $ids, $request->user());

        $message = "تم حذف {$result['deleted']} دور بنجاح";
        if (count($result['protected']) > 0) {
            $message .= '. الأدوار المحمية: '.implode(', ', $result['protected']);
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'deleted' => $result['deleted'],
            'protected' => $result['protected'],
        ]);
    }

    /**
     * Clone role with all permissions.
     *
     * POST /su/portal/v1/admin/roles/{id}/clone
     */
    public function cloneRole(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
        ]);

        $originalRole = $this->roleService->getRoleWithPermissions($id);

        if (! $originalRole) {
            return $this->notFoundResponse('Role not found');
        }

        $result = $this->roleService->cloneRole($id, $request->input('name'));

        $this->logger->logCustomAction(
            'clone_role',
            $result['role'],
            $request->user(),
            ['original_role_id' => $result['original_role']->id],
            ['permissions_count' => $result['permissions_count']],
        );

        return $this->createdResponse(
            $result['role'],
            "تم نسخ الدور بنجاح: {$result['original_role']->name} -> {$result['role']->name}",
        );
    }

    /**
     * Get permission matrix for all roles.
     *
     * GET /su/portal/v1/admin/roles/permission-matrix
     */
    public function getPermissionMatrix(): JsonResponse
    {
        $data = $this->roleService->getPermissionMatrix();

        return response()->json([
            'success' => true,
            'data' => [
                'matrix' => $data['matrix'],
                'all_permissions' => $data['all_permissions'],
            ],
        ]);
    }

    /**
     * Get user count per role.
     *
     * GET /su/portal/v1/admin/roles/{id}/users-count
     */
    public function getUserCount(int $id): JsonResponse
    {
        $role = $this->roleService->getRoleWithPermissions($id);

        if (! $role) {
            return $this->notFoundResponse('Role not found');
        }

        $userCount = $this->roleService->getUserCount($id);

        return response()->json([
            'success' => true,
            'data' => [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'user_count' => $userCount,
            ],
        ]);
    }

    /**
     * Search roles (AJAX endpoint).
     *
     * GET /su/portal/v1/admin/roles/search
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:1'],
        ]);

        $roles = $this->roleService->search($request->input('q'));

        return response()->json([
            'success' => true,
            'data' => $roles,
        ]);
    }

    /**
     * Check if role is a system role (protected from deletion).
     *
     * GET /su/portal/v1/admin/roles/{id}/is-system
     */
    public function isSystemRole(int $id): JsonResponse
    {
        $role = $this->roleService->getRoleWithPermissions($id);

        if (! $role) {
            return $this->notFoundResponse('Role not found');
        }

        $data = $this->roleService->isSystemRole($id);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
