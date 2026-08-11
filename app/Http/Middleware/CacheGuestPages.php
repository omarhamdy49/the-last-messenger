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
     *
     * public.media qualifies since Continue Watching moved to a lazy-loaded
     * per-user JSON endpoint: the hub HTML is identical for every guest
     * (guest My-List lives in localStorage; view counting happens on the
     * detail pages, which stay uncached).
     */
    private const CACHEABLE_ROUTES = ['public.home', 'public.media'];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $isGuestCacheable = $request->isMethod('GET')
            && auth()->guest()
            && $request->routeIs(self::CACHEABLE_ROUTES)
            && $response->getStatusCode() === 200
            && $this->rendersDefaultLocale($request);

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

    /**
     * The rendered HTML varies by locale, but for a guest the locale comes from
     * ?lang= and from the SESSION that SetLocale persists it into — neither of
     * which is part of the edge cache key, and neither of which Vary can express
     * (we strip the very cookie that carries it). Sharing an English render under
     * the plain "/" key would serve English to every subsequent visitor.
     *
     * So only the render a cookie-less first-time visitor would get is marked
     * shareable. For a guest on a non-API route SetLocale's priority list is
     * exactly ?lang= -> session -> default, so the absence of both signals means
     * the response is in the default locale. A visitor who switched language
     * still gets their language, just from the origin rather than the edge.
     *
     * Comparing app()->getLocale() against config('app.locale') would NOT work:
     * App::setLocale() overwrites that config key with the request's locale, so
     * the two are always equal by the time this response middleware runs.
     */
    private function rendersDefaultLocale(Request $request): bool
    {
        if ($request->has('lang')) {
            return false;
        }

        return ! $request->hasSession() || ! $request->session()->has('locale');
    }
}
