<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin Authentication Middleware
 * Verifies user has admin or super_admin role
 */
class AdminAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
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
        if ($request->user()->hasRole('super_admin')) {
            return $next($request);
        }

        // Admin and editor users need proper authorization
        if (! $request->user()->hasAnyRole(['admin', 'editor'])) {
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
