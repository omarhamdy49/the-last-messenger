<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Makes the public homepage edge-cacheable for ANONYMOUS visitors so Cloudflare
 * can serve it from the nearest edge instead of round-tripping to the origin
 * (the main lever left on FCP/LCP). Authenticated responses — including the admin
 * portal — are explicitly marked uncacheable, so even a misconfigured edge rule
 * can never share a logged-in page.
 *
 * Must be PREPENDED to the web group: its response logic runs last (outermost),
 * after StartSession has added the session cookie, so the cookie strip sticks.
 */
final class CacheGuestPages
{
    /**
     * Routes safe to cache for guests (no per-user content, no POST/CSRF need).
     * Start narrow — just the homepage.
     */
    private const CACHEABLE_ROUTES = ['public.home'];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $isGuestCacheable = $request->isMethod('GET')
            && auth()->guest()
            && $request->routeIs(self::CACHEABLE_ROUTES)
            && $response->getStatusCode() === 200;

        if ($isGuestCacheable) {
            // 30-min edge TTL (s-maxage) + serve-stale-while-revalidating. The CF
            // cache rule is "respect origin", so this value drives the edge TTL.
            // Strip ALL Set-Cookie so the edge never stores a session/CSRF token
            // tied to one visitor.
            $response->headers->set('Cache-Control', 'public, s-maxage=1800, stale-while-revalidate=600');
            $response->headers->remove('Set-Cookie');
        } elseif (auth()->check()) {
            // Authenticated (incl. admin): never shared-cache. Belt-and-suspenders
            // alongside the Cloudflare bypass rule.
            $response->headers->set('Cache-Control', 'private, no-store');
        }

        return $response;
    }
}
