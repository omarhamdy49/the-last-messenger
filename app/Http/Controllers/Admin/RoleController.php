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
}
