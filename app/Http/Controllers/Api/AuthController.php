<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Authentication API Controller
 * Handles user registration, login, and logout
 */
class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly UserRepository $users) {}

    /**
     * Register a new user
     *
     * POST /api/register
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => [
                'required',
                'confirmed',
                Password::min(12)->mixedCase()->letters()->numbers()->symbols(),
            ],
            'language' => ['nullable', 'string', 'in:ar,en,fr,tr,ur'],
        ]);

        $user = $this->users->createPublicAccount([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'language' => $request->language ?? 'ar',
        ]);

        // Create API token
        $token = $user->createToken('api-token', ['api:access'])->plainTextToken;

        return $this->createdResponse([
            'user' => new UserResource($user),
            'token' => $token,
        ], 'User registered successfully');
    }

    /**
     * Login user
     *
     * POST /api/login
     *
     * @throws ValidationException
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Update last login
        $user->updateLastLogin();

        // Revoke all existing tokens before issuing a fresh one
        // to prevent token accumulation across sessions.
        $user->tokens()->delete();

        // Create API token
        $token = $user->createToken('api-token', ['api:access'])->plainTextToken;

        return $this->successResponse([
            'user' => new UserResource($user),
            'token' => $token,
        ], 'Logged in successfully');
    }

    /**
     * Logout user (revoke token)
     *
     * POST /api/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'Logged out successfully');
    }

    /**
     * Logout user from all devices (revoke all tokens)
     *
     * POST /api/v1/auth/logout-all
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return $this->successResponse(null, __('auth.logged_out_all_devices'));
    }

    /**
     * Get authenticated user
     *
     * GET /api/user
     */
    public function user(Request $request): JsonResponse
    {
        return $this->successResponse(new UserResource($request->user()));
    }
}
