<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Shared\Services\AdminLoggerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Admin Role Controller
 * Handles role and permission management for admin portal
 */
class RoleController extends Controller
{
    use ApiResponse;

    /**
     * Constructor
     */
    public function __construct(
        protected AdminLoggerService $logger
    ) {}

    /**
     * Display a listing of all roles
     *
     * GET /su/portal/v1/admin/roles
     */
    public function index(Request $request): \Illuminate\View\View|JsonResponse
    {
        $perPage = $request->integer('per_page', 15);
        $search = $request->input('search');

        $roles = Role::with('permissions')
            ->when($search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%");
            })
            ->paginate($perPage);

        $stats = [
            'total' => Role::count(),
            'with_permissions' => Role::has('permissions')->count(),
        ];

        // Return JSON for AJAX requests
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
     * Show the form for creating a new role
     *
     * GET /su/portal/v1/admin/roles/create
     */
    public function create(): \Illuminate\View\View
    {
        return view('admin.roles.create');
    }

    /**
     * Store a newly created role
     *
     * POST /su/portal/v1/admin/roles
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = Role::create(['name' => $request->input('name')]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->input('permissions'));
        }

        // Log the creation
        $this->logger->logCreate($role, $request->user());

        return $this->createdResponse(
            $role->load('permissions'),
            'Role created successfully'
        );
    }

    /**
     * Display the specified role
     *
     * GET /su/portal/v1/admin/roles/{id}
     */
    public function show(int $id): JsonResponse
    {
        $role = Role::with('permissions')->find($id);

        if (! $role) {
            return $this->notFoundResponse('Role not found');
        }

        return $this->successResponse($role);
    }

    /**
     * Show the form for editing a role
     *
     * GET /su/portal/v1/admin/roles/{id}/edit
     */
    public function edit(int $id): \Illuminate\View\View
    {
        return view('admin.roles.edit', ['id' => $id]);
    }

    /**
     * Update the specified role
     *
     * PUT/PATCH /su/portal/v1/admin/roles/{id}
     */
    public function update(UpdateRoleRequest $request, int $id): JsonResponse
    {
        $role = Role::find($id);

        if (! $role) {
            return $this->notFoundResponse('Role not found');
        }

        // Get old values before update
        $oldValues = $role->toArray();
        $oldValues['permissions'] = $role->permissions->pluck('name')->toArray();

        $role->update(['name' => $request->input('name')]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->input('permissions'));
        }

        // Get new values
        $newValues = $role->fresh()->toArray();
        $newValues['permissions'] = $role->fresh()->permissions->pluck('name')->toArray();

        // Log the update with diff
        $this->logger->logUpdate($role, $oldValues, $newValues, $request->user());

        return $this->successResponse(
            $role->load('permissions'),
            'Role updated successfully'
        );
    }

    /**
     * Remove the specified role
     *
     * DELETE /su/portal/v1/admin/roles/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $role = Role::find($id);

        if (! $role) {
            return $this->notFoundResponse('Role not found');
        }

        // Prevent deletion of super_admin role
        if ($role->name === 'super_admin') {
            return $this->errorResponse('Cannot delete super_admin role', 403);
        }

        // Perform deletion
        $role->delete();

        // Log the deletion
        $this->logger->logDelete($role, request()->user());

        return $this->successResponse(null, 'Role deleted successfully');
    }

    /**
     * Get all permissions
     *
     * GET /su/portal/v1/admin/permissions
     */
    public function permissions(): JsonResponse
    {
        $permissions = Permission::all(['id', 'name']);

        return $this->successResponse($permissions);
    }

    /**
     * Assign permissions to role
     *
     * POST /su/portal/v1/admin/roles/{id}/permissions
     */
    public function assignPermissions(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role = Role::find($id);

        if (! $role) {
            return $this->notFoundResponse('Role not found');
        }

        $oldPermissions = $role->permissions->pluck('name')->toArray();

        $role->syncPermissions($request->input('permissions'));

        // Log custom action
        $this->logger->logCustomAction(
            'assign_permissions',
            $role,
            $request->user(),
            ['old_permissions' => $oldPermissions],
            ['new_permissions' => $request->input('permissions')]
        );

        return $this->successResponse(
            $role->load('permissions'),
            'Permissions assigned successfully'
        );
    }

    /**
     * Bulk delete roles (with system role protection)
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
        $systemRoles = ['super_admin', 'admin'];
        $deleted = 0;
        $protected = [];

        foreach ($ids as $id) {
            $role = Role::find($id);

            if ($role && in_array($role->name, $systemRoles)) {
                $protected[] = $role->name;

                continue;
            }

            if ($role) {
                $role->delete();
                $deleted++;
            }
        }

        // Log bulk action
        $this->logger->logBulkAction(
            'bulk_delete',
            Role::class,
            $ids,
            $request->user()
        );

        $message = "تم حذف {$deleted} دور بنجاح";
        if (count($protected) > 0) {
            $message .= '. الأدوار المحمية: '.implode(', ', $protected);
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'deleted' => $deleted,
            'protected' => $protected,
        ]);
    }

    /**
     * Clone role with all permissions
     *
     * POST /su/portal/v1/admin/roles/{id}/clone
     */
    public function cloneRole(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
        ]);

        $originalRole = Role::with('permissions')->find($id);

        if (! $originalRole) {
            return $this->notFoundResponse('Role not found');
        }

        // Create new role
        $newRole = Role::create(['name' => $request->input('name')]);

        // Copy all permissions
        $permissions = $originalRole->permissions->pluck('name')->toArray();
        $newRole->syncPermissions($permissions);

        // Log custom action
        $this->logger->logCustomAction(
            'clone_role',
            $newRole,
            $request->user(),
            ['original_role_id' => $originalRole->id],
            ['permissions_count' => count($permissions)]
        );

        return $this->createdResponse(
            $newRole->load('permissions'),
            "تم نسخ الدور بنجاح: {$originalRole->name} -> {$newRole->name}"
        );
    }

    /**
     * Get permission matrix for all roles
     *
     * GET /su/portal/v1/admin/roles/permission-matrix
     */
    public function getPermissionMatrix(): JsonResponse
    {
        $roles = Role::with('permissions')->get();
        $allPermissions = Permission::all(['id', 'name']);

        $matrix = [];
        foreach ($roles as $role) {
            $rolePermissions = $role->permissions->pluck('name')->toArray();
            $matrix[$role->name] = [
                'id' => $role->id,
                'permissions' => array_fill_keys($rolePermissions, true),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'matrix' => $matrix,
                'all_permissions' => $allPermissions,
            ],
        ]);
    }

    /**
     * Get user count per role
     *
     * GET /su/portal/v1/admin/roles/{id}/users-count
     */
    public function getUserCount(int $id): JsonResponse
    {
        $role = Role::find($id);

        if (! $role) {
            return $this->notFoundResponse('Role not found');
        }

        $userCount = \App\Models\User::role($role->name)->count();

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
     * Search roles (AJAX endpoint)
     *
     * GET /su/portal/v1/admin/roles/search
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:1'],
        ]);

        $query = $request->input('q');

        $roles = Role::with('permissions')
            ->where('name', 'like', "%{$query}%")
            ->get();

        return response()->json([
            'success' => true,
            'data' => $roles,
        ]);
    }

    /**
     * Check if role is a system role (protected from deletion)
     *
     * GET /su/portal/v1/admin/roles/{id}/is-system
     */
    public function isSystemRole(int $id): JsonResponse
    {
        $role = Role::find($id);

        if (! $role) {
            return $this->notFoundResponse('Role not found');
        }

        $systemRoles = ['super_admin', 'admin'];
        $isSystem = in_array($role->name, $systemRoles);

        return response()->json([
            'success' => true,
            'data' => [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'is_system' => $isSystem,
            ],
        ]);
    }
}
