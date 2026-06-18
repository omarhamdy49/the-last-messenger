<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin Authentication Middleware
 *
 * Verifies the request is authenticated on the dedicated `admin` session guard
 * and holds an admin-tier role. Reading from the admin guard (not `web`) means a
 * public visitor's session is never treated as an admin, independent of roles.
 */
class AdminAuthMiddleware
{
    /** The session guard that backs the admin portal. */
    private const GUARD = 'admin';

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user(self::GUARD);

        if (! $user) {
            // Redirect to login for web requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                ], Response::HTTP_UNAUTHORIZED);
            }

            return redirect()->route('login');
        }

        // Super admin users have unrestricted access
        if ($user->hasRole('super_admin')) {
            return $next($request);
        }

        // Admin and editor users need proper authorization
        if (! $user->hasAnyRole(['admin', 'editor'])) {
            // Return JSON for API requests, redirect for web
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden. Admin access required.',
                ], Response::HTTP_FORBIDDEN);
            }

            return redirect()->route('login')->withErrors([
                'email' => __('admin.no_admin_access'),
            ]);
        }

        return $next($request);
    }
}
