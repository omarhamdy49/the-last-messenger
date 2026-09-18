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
     *
     * ---------------------------------------------------------------------------
     * A route qualifies only if ALL of these hold. Three of them disqualify pages
     * that otherwise look harmless, so check each before adding one:
     *
     *  1. No per-GUEST variation. Per-USER variation is fine — this middleware only
     *     marks a response cacheable when auth()->guest(), and authenticated
     *     responses get `private, no-store`, so @auth blocks are simply absent from
     *     the shared copy. What breaks caching is variation between guests, which is
     *     why rendersDefaultLocale() exists.
     *  2. No @csrf form in the view. The cacheable branch strips Set-Cookie, so a
     *     first-time visitor never receives a session — any token rendered into the
     *     HTML is a stale one belonging to whoever's render got cached, and the form
     *     fails validation. That rules out contact, auth, profile and quran/ask.
     *  3. No write on the GET path. An edge hit never reaches PHP, so a view counter
     *     on the GET simply stops counting. Media moved its counters to a queued,
     *     deduplicated recorder, but that still runs per request — queueing does not
     *     help if the request never arrives.
     *
     * Deliberately EXCLUDED, with the reason, so nobody has to re-derive it:
     *
     *   public.seerah.event      SeerahEventService:66,84 increments views inline on
     *                            the GET — the last synchronous counter in the app.
     *                            Add this route once that moves to MediaViewRecorder.
     *   public.media.program     Queued view recording, but still per request.
     *   public.media.episode     Also renders watch progress.
     *   public.media.live        Live channel view recording.
     *   public.articles.show     The reader records views via an async POST
     *                            (ArticleController::registerView) specifically so the
     *                            page can be cached — but that POST is CSRF-protected
     *                            and reads its token from the cached HTML's
     *                            <meta name="csrf-token">, so a cookieless visitor
     *                            would 419 and the counter would silently stop. Cache
     *                            this only together with exempting that one route from
     *                            CSRF (it is already session-guarded and throttled
     *                            30/min, so the exemption is cheap — but it is a
     *                            security decision, not a caching one).
     *   public.search            Query-driven; server-side throttled already.
     *   public.quran.ask*        CSRF form + per-user quota.
     *   favorites/profile/auth   Per-user by definition.
     * ---------------------------------------------------------------------------
     */
    private const CACHEABLE_ROUTES = [
        // Home + media hub (pre-existing).
        'public.home',
        'public.media',

        // Module landing pages. `lat_nav` was the slowest group under stress
        // (p95 2.19 s, p99 6.77 s), and it is entirely these.
        'public.articles',
        'public.series',
        'public.seerah',
        'public.seerah.events',
        'public.hadith',
        'public.sahaba',
        'public.topics',
        'public.genealogy',
        'public.genealogy.people',
        'public.media.live.index',

        // Quran reader — second-slowest group (p95 1.69 s) and entirely read-only.
        'public.quran',
        'public.quran.surah',
        'public.quran.surah.short',
        'public.quran.surah.translation',
        'public.quran.juz',
        'public.quran.page',
        'public.quran.hizb',
        'public.quran.rub',
        'public.quran.manzil',
        'public.quran.ruku',
        'public.quran.reciters',
        'public.quran.reciter',
        'public.quran.surahs.json',
        'public.quran.verse.tafsir',

        // Detail pages with no counter and no form.
        'public.series.show',
        'public.topics.show',
        'public.hadith.collection',
        'public.hadith.book',
        'public.hadith.show',
        'public.sahaba.show',
        'public.sahaba.tree',

        // Static content.
        'public.about',
        'public.privacy',
        'public.terms',
        'public.faq',
        'public.pages.show',
    ];

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
            //
            // `max-age=0` is deliberate and applies to the VISITOR's browser only: with
            // no max-age a browser may cache heuristically, so a reader kept seeing the
            // old page after an editor enabled new translations or tafsirs — a reload
            // did not help and the content looked missing. The edge still caches for the
            // full 30 minutes, which is where the load saving actually comes from.
            $response->headers->set('Cache-Control', 'public, max-age=0, s-maxage=1800, stale-while-revalidate=600');
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
