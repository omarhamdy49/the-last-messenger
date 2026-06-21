<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

/**
 * SetLocale Middleware
 * Automatically sets the application locale based on multiple sources
 */
class SetLocale
{
    /**
     * Supported languages
     */
    private array $supportedLanguages = ['ar', 'en', 'fr', 'tr', 'ur'];

    /**
     * Handle an incoming request.
     *
     * Priority order:
     * 1. Force Arabic for admin panel
     * 2. User preference (if authenticated)
     * 3. Session locale
     * 4. Request header (Accept-Language)
     * 5. Default locale (ar)
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Force Arabic for admin panel routes (bypass validation)
        $isAdminRoute = $request->is('su/portal/v1/admin/*') || $request->is('su/portal/v1/admin') ||
                        $request->is('admin/*') || $request->is('admin');

        if ($isAdminRoute) {
            $locale = 'ar';
            // Force set locale for admin without validation
            App::setLocale($locale);
            Carbon::setLocale($locale);
        } else {
            $locale = $this->determineLocale($request);

            // Set application locale (with validation for non-admin routes)
            if ($this->isValidLocale($locale)) {
                App::setLocale($locale);
                Carbon::setLocale($locale);
            }
        }

        return $next($request);
    }

    /**
     * Determine the locale based on priority order
     */
    private function determineLocale(Request $request): string
    {
        // 0. Explicit ?lang override — powers the per-language hreflang/canonical
        //    URLs (so a crawler hitting ?lang=en actually gets English) and
        //    shareable language links. Persists to the session for the visit.
        $queryLocale = $request->query('lang');
        if (is_string($queryLocale) && $this->isValidLocale($queryLocale)) {
            Session::put('locale', $queryLocale);

            return $queryLocale;
        }

        // 1. Check authenticated user preference
        if (Auth::check() && Auth::user()->language) {
            $userLocale = Auth::user()->language;
            if ($this->isValidLocale($userLocale)) {
                // Store in session for consistency
                Session::put('locale', $userLocale);

                return $userLocale;
            }
        }

        // 2. Check session
        if (Session::has('locale')) {
            $sessionLocale = Session::get('locale');
            if ($this->isValidLocale($sessionLocale)) {
                return $sessionLocale;
            }
        }

        // 3. Accept-Language header — ONLY for API clients doing content
        //    negotiation. The public site is Arabic-first: a browser sending
        //    "en" must never override the Arabic default. Visitors change
        //    language explicitly (stored in the session, handled above).
        if ($request->is('api/*')) {
            $headerLocale = $this->getLocaleFromHeader($request);
            if ($this->isValidLocale($headerLocale)) {
                return $headerLocale;
            }
        }

        // 4. Use default locale (check cache first for admin-configured default)
        return Cache::get('app.locale', config('app.locale', 'ar'));
    }

    /**
     * Extract locale from Accept-Language header
     */
    private function getLocaleFromHeader(Request $request): ?string
    {
        $acceptLanguage = $request->header('Accept-Language');

        if (! $acceptLanguage) {
            return null;
        }

        // Parse Accept-Language header (e.g., "ar,en;q=0.9,fr;q=0.8")
        $languages = explode(',', $acceptLanguage);

        foreach ($languages as $language) {
            // Extract language code (ignore quality value)
            $code = strtok(trim($language), ';');

            // Handle language codes like "en-US" -> "en"
            $code = substr($code, 0, 2);

            if ($this->isValidLocale($code)) {
                return $code;
            }
        }

        return null;
    }

    /**
     * Check if locale is valid and active
     */
    private function isValidLocale(?string $locale): bool
    {
        if (! $locale || ! in_array($locale, $this->supportedLanguages)) {
            return false;
        }

        // Check if language is active in system settings
        $languages = Cache::get('system.languages', []);

        if (empty($languages)) {
            // If no cache, allow all supported languages
            return true;
        }

        return isset($languages[$locale]) && ($languages[$locale]['active'] ?? false);
    }
}
