<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Shared\Services\AdminLoggerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignRolesRequest;
use App\Http\Requests\Admin\BulkDeleteUsersRequest;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Resources\AdminUserResource;
use App\Models\User;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

/**
 * Admin User Controller
 * Handles user management for admin portal
 */
class UserController extends Controller
{
    use ApiResponse;

    /**
     * Constructor
     */
    public function __construct(
        protected AdminLoggerService $logger
    ) {}

    /**
     * Display a listing of all users
     *
     * GET /su/portal/v1/admin/users
     */
    public function index(Request $request): \Illuminate\View\View|JsonResponse
    {
        $perPage = $request->integer('per_page', 15);
        $verified = $request->input('verified');
        $search = $request->input('search');

        $users = User::with('roles:id,name')
            ->select(['id', 'name', 'email', 'language', 'email_verified_at', 'created_at'])
            // Grouped: without the closure the verified filter below would AND
            // onto the email branch only, silently widening the result set.
            ->when($search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($verified !== null, function ($query) use ($verified) {
                return $verified === 'true'
                    ? $query->whereNotNull('email_verified_at')
                    : $query->whereNull('email_verified_at');
            })
            ->latest()
            ->paginate($perPage);

        $counts = User::selectRaw('COUNT(*) AS total, SUM(email_verified_at IS NOT NULL) AS verified')
            ->first();

        $stats = [
            'total' => (int) $counts->total,
            'verified' => (int) $counts->verified,
            'unverified' => (int) $counts->total - (int) $counts->verified,
        ];

        // Return JSON for AJAX requests
        if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true,
                'data' => $users->items(),
                'stats' => $stats,
                'meta' => [
                    'current_page' => $users->currentPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                    'last_page' => $users->lastPage(),
                ],
            ]);
        }

        return view('admin.users.index', compact('users', 'stats'));
    }

    /**
     * Show the form for creating a new user
     *
     * GET /su/portal/v1/admin/users/create
     */
    public function create(): \Illuminate\View\View
    {
        return view('admin.users.create');
    }

    /**
     * Show the form for editing a user
     *
     * GET /su/portal/v1/admin/users/{id}/edit
     */
    public function edit(Request $request, int $id): \Illuminate\View\View
    {
        $user = User::with('roles:id,name')
            ->select(['id', 'name', 'email', 'language', 'email_verified_at', 'created_at'])
            ->findOrFail($id);

        // Refuse here rather than rendering a form whose save would 403: only a
        // super_admin may edit an account that holds super_admin.
        abort_if(
            $user->hasRole('super_admin') && ! $request->user()?->hasRole('super_admin'),
            403
        );

        // Rendered server-side rather than fetched: the form previously loaded
        // itself from two XHRs, so a failure in either left the fields blank.
        return view('admin.users.edit', [
            'id' => $id,
            'user' => (new AdminUserResource($user))->toArray($request),
            'allRoles' => Role::orderBy('name')->pluck('name')->all(),
            'canGrantSuperAdmin' => (bool) $request->user()?->hasRole('super_admin'),
        ]);
    }

    /**
     * Store a newly created user
     *
     * POST /su/portal/v1/admin/users
     */
    public function store(StoreUserRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        // Note: password hashing is handled by the User model's 'hashed' cast

        $user = User::create($data);

        if ($request->has('roles')) {
            $user->syncRoles($request->input('roles'));
        }

        // Log the creation
        $this->logger->logCreate($user, $request->user());

        // The create page posts a plain HTML form; without this it would render
        // the JSON body in the browser instead of returning to the list.
        if (! $request->expectsJson()) {
            return redirect()->route('admin.users.index')
                ->with('success', __('admin.user_created'));
        }

        return $this->createdResponse(
            new AdminUserResource($user->load('roles')),
            'User created successfully'
        );
    }

    /**
     * Display the specified user
     *
     * GET /su/portal/v1/admin/users/{id}
     */
    public function show(int $id): JsonResponse
    {
        $user = User::with('roles', 'permissions')->find($id);

        if (! $user) {
            return $this->notFoundResponse('User not found');
        }

        return $this->successResponse(new AdminUserResource($user));
    }

    /**
     * Update the specified user
     *
     * PUT/PATCH /su/portal/v1/admin/users/{id}
     */
    public function update(UpdateUserRequest $request, int $id): JsonResponse|RedirectResponse
    {
        $user = User::find($id);

        if (! $user) {
            return $request->expectsJson()
                ? $this->notFoundResponse('User not found')
                : redirect()->route('admin.users.index')
                    ->with('error', __('admin.user_not_found'));
        }

        // Get old values before update
        $oldValues = $user->toArray();

        $data = $request->validated();
        // Note: password hashing is handled by the User model's 'hashed' cast

        // Remove password from data if not provided (to avoid setting null)
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        if ($request->has('roles')) {
            $user->syncRoles($request->input('roles'));
        }

        // Log the update with diff
        $this->logger->logUpdate($user, $oldValues, $user->fresh()->toArray(), $request->user());

        if (! $request->expectsJson()) {
            return redirect()->route('admin.users.index')
                ->with('success', __('admin.user_updated'));
        }

        return $this->successResponse(
            new AdminUserResource($user->load('roles')),
            'User updated successfully'
        );
    }

    /**
     * Remove the specified user
     *
     * DELETE /su/portal/v1/admin/users/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $user = User::find($id);

        if (! $user) {
            return $request->expectsJson()
                ? $this->notFoundResponse('User not found')
                : redirect()->route('admin.users.index')
                    ->with('error', __('admin.user_not_found'));
        }

        // Prevent self-deletion
        if ($user->id === auth()->id()) {
            return $request->expectsJson()
                ? $this->errorResponse('You cannot delete your own account', 400)
                : redirect()->route('admin.users.index')
                    ->with('error', __('admin.cannot_delete_own_account'));
        }

        // Perform deletion
        $user->delete();

        // Log the deletion
        $this->logger->logDelete($user, $request->user());

        // The index posts a plain HTML form; without this the browser would land
        // on /users/{id} showing the raw JSON body.
        if (! $request->expectsJson()) {
            return redirect()->route('admin.users.index')
                ->with('success', __('admin.user_deleted'));
        }

        return $this->successResponse(null, 'User deleted successfully');
    }

    /**
     * Bulk delete users
     *
     * POST /su/portal/v1/admin/users/bulk-delete
     */
    public function bulkDelete(BulkDeleteUsersRequest $request): JsonResponse
    {
        $currentUserId = auth()->id();
        $ids = array_filter($request->input('ids'), fn ($id) => $id !== $currentUserId);

        $deleted = User::whereIn('id', $ids)->delete();

        // Log bulk action
        $this->logger->logBulkAction(
            'bulk_delete',
            User::class,
            $ids,
            $request->user()
        );

        return $this->successResponse(
            ['deleted' => $deleted],
            "{$deleted} users deleted successfully"
        );
    }

    /**
     * Search users
     *
     * GET /su/portal/v1/admin/users/search
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = $request->input('q');
        $perPage = $request->integer('per_page', 15);

        $users = User::with('roles:id,name')
            ->select(['id', 'name', 'email', 'language', 'email_verified_at', 'created_at'])
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            })
            ->latest()
            ->paginate($perPage);

        return $this->paginatedResponse($users->setCollection(
            $users->getCollection()->map(fn ($u) => new AdminUserResource($u))
        ));
    }

    /**
     * Assign roles to user
     *
     * POST /su/portal/v1/admin/users/{id}/roles
     */
    public function assignRoles(AssignRolesRequest $request, int $id): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return $this->notFoundResponse('User not found');
        }

        // Captured before the sync: syncRoles() refreshes the relation, so
        // reading role names afterwards would log the new roles as the old ones.
        $oldRoles = $user->getRoleNames()->toArray();

        $user->syncRoles($request->input('roles'));

        // Log custom action
        $this->logger->logCustomAction(
            'assign_roles',
            $user,
            $request->user(),
            ['old_roles' => $oldRoles],
            ['new_roles' => $request->input('roles')]
        );

        return $this->successResponse(
            new AdminUserResource($user->load('roles')),
            'Roles assigned successfully'
        );
    }
}
