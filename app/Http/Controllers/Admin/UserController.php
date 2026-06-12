<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Shared\Services\AdminLoggerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        $users = User::with('roles')
            ->when($search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->when($verified !== null, function ($query) use ($verified) {
                return $verified === 'true'
                    ? $query->whereNotNull('email_verified_at')
                    : $query->whereNull('email_verified_at');
            })
            ->latest()
            ->paginate($perPage);

        $stats = [
            'total' => User::count(),
            'verified' => User::whereNotNull('email_verified_at')->count(),
            'unverified' => User::whereNull('email_verified_at')->count(),
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
    public function edit(int $id): \Illuminate\View\View
    {
        return view('admin.users.edit', ['id' => $id]);
    }

    /**
     * Store a newly created user
     *
     * POST /su/portal/v1/admin/users
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();
        // Note: password hashing is handled by the User model's 'hashed' cast

        $user = User::create($data);

        if ($request->has('roles')) {
            $user->syncRoles($request->input('roles'));
        }

        // Log the creation
        $this->logger->logCreate($user, $request->user());

        return $this->createdResponse(
            new UserResource($user->load('roles')),
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

        return $this->successResponse(new UserResource($user));
    }

    /**
     * Update the specified user
     *
     * PUT/PATCH /su/portal/v1/admin/users/{id}
     */
    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return $this->notFoundResponse('User not found');
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

        return $this->successResponse(
            new UserResource($user->load('roles')),
            'User updated successfully'
        );
    }

    /**
     * Remove the specified user
     *
     * DELETE /su/portal/v1/admin/users/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return $this->notFoundResponse('User not found');
        }

        // Prevent self-deletion
        if ($user->id === auth()->id()) {
            return $this->errorResponse('You cannot delete your own account', 400);
        }

        // Perform deletion
        $user->delete();

        // Log the deletion
        $this->logger->logDelete($user, request()->user());

        return $this->successResponse(null, 'User deleted successfully');
    }

    /**
     * Bulk delete users
     *
     * POST /su/portal/v1/admin/users/bulk-delete
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:users,id'],
        ]);

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

        $users = User::with('roles')
            ->where('name', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->latest()
            ->paginate($perPage);

        return $this->paginatedResponse($users->setCollection(
            $users->getCollection()->map(fn ($u) => new UserResource($u))
        ));
    }

    /**
     * Assign roles to user
     *
     * POST /su/portal/v1/admin/users/{id}/roles
     */
    public function assignRoles(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

        $user = User::find($id);

        if (! $user) {
            return $this->notFoundResponse('User not found');
        }

        $user->syncRoles($request->input('roles'));

        // Log custom action
        $this->logger->logCustomAction(
            'assign_roles',
            $user,
            $request->user(),
            ['old_roles' => $user->getRoleNames()->toArray()],
            ['new_roles' => $request->input('roles')]
        );

        return $this->successResponse(
            new UserResource($user->load('roles')),
            'Roles assigned successfully'
        );
    }
}
