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
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Authentication API Controller
 * Handles user registration, login, and logout
 */
class AuthController extends Controller
{
    /**
     * A real bcrypt hash of a value nothing can match, used to equalise the work done on a
     * missing-account login. Generated once at class level so the cost matches a live hash.
     */
    private const DUMMY_HASH = '$2y$12$yYJ7r.9DJDi7ubG5LcshcOxtdQl0SogjBkZK7sQZNVzpK0RRjGlLe';

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
            'language' => ['nullable', 'string', Rule::in(enabled_content_locales())],
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

        // Hash::check runs even when the email is unknown. Without the dummy verify below,
        // a miss returned in ~1ms while a hit paid the full bcrypt cost (~100ms at
        // BCRYPT_ROUNDS=12) — a reliable oracle for enumerating which addresses have
        // accounts. Burning the same work on both paths removes the signal.
        if (! $user) {
            Hash::check($request->password, self::DUMMY_HASH);

            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! Hash::check($request->password, $user->password)) {
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
