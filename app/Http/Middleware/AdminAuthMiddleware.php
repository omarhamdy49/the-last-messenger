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

        if (! $request->user()->hasAnyRole(['admin', 'super_admin', 'editor'])) {
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
