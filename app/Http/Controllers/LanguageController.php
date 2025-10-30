<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

/**
 * Language Controller
 * Handles public language switching for unauthenticated and authenticated users
 */
class LanguageController extends Controller
{
    use ApiResponse;

    /**
     * Supported languages
     */
    private array $supportedLanguages = ['ar', 'en', 'fr', 'tr', 'ur'];

    /**
     * Switch application language
     *
     * POST /language/switch
     *
     * @param  Request  $request
     * @return RedirectResponse|JsonResponse
     */
    public function switch(Request $request): RedirectResponse|JsonResponse
    {
        // Support both 'locale' and 'language' parameters for backward compatibility
        $language = $request->input('language') ?? $request->input('locale');

        // Validate language
        if (! in_array($language, $this->supportedLanguages)) {
            if ($request->expectsJson()) {
                return $this->errorResponse('اللغة المختارة غير صحيحة', 422);
            }

            return back()->with('error', 'اللغة المختارة غير صحيحة');
        }

        // Validate language is active
        if (! $this->isLanguageActive($language)) {
            if ($request->expectsJson()) {
                return $this->errorResponse('اللغة المختارة غير متاحة حالياً', 422);
            }

            return back()->with('error', 'اللغة المختارة غير متاحة حالياً');
        }

        // Store in session
        Session::put('locale', $language);

        // If user is authenticated, update their preference
        if (auth()->check()) {
            auth()->user()->update(['language' => $language]);
        }

        // Return appropriate response
        if ($request->expectsJson()) {
            return $this->successResponse(
                ['language' => $language],
                'تم تغيير اللغة بنجاح'
            );
        }

        return redirect()->back()->with('success', 'تم تغيير اللغة بنجاح');
    }

    /**
     * Get list of active languages
     *
     * GET /api/languages/active
     *
     * @return JsonResponse
     */
    public function active(): JsonResponse
    {
        $languages = $this->getActiveLanguages();

        return $this->successResponse($languages, 'Active languages retrieved successfully');
    }

    /**
     * Get all available languages (active and inactive)
     *
     * GET /api/languages/all
     *
     * @return JsonResponse
     */
    public function all(): JsonResponse
    {
        $languages = Cache::get('system.languages', $this->getDefaultLanguages());

        return $this->successResponse(
            array_values($languages),
            'All languages retrieved successfully'
        );
    }

    /**
     * Get current locale
     *
     * GET /api/languages/current
     *
     * @return JsonResponse
     */
    public function current(): JsonResponse
    {
        $currentLocale = app()->getLocale();
        $languages = Cache::get('system.languages', $this->getDefaultLanguages());
        $currentLanguage = $languages[$currentLocale] ?? null;

        return $this->successResponse([
            'locale' => $currentLocale,
            'language' => $currentLanguage,
        ], 'Current language retrieved successfully');
    }

    /**
     * Get active languages from cache
     *
     * @return array
     */
    private function getActiveLanguages(): array
    {
        $languages = Cache::get('system.languages', $this->getDefaultLanguages());

        return array_values(array_filter($languages, function ($lang) {
            return $lang['active'] ?? false;
        }));
    }

    /**
     * Check if language is active
     *
     * @param  string  $code
     * @return bool
     */
    private function isLanguageActive(string $code): bool
    {
        $languages = Cache::get('system.languages', []);

        if (empty($languages)) {
            // If no cache, allow all supported languages
            return in_array($code, $this->supportedLanguages);
        }

        return isset($languages[$code]) && ($languages[$code]['active'] ?? false);
    }

    /**
     * Get default languages configuration
     *
     * @return array
     */
    private function getDefaultLanguages(): array
    {
        return [
            'ar' => [
                'code' => 'ar',
                'name' => 'Arabic',
                'native_name' => 'العربية',
                'direction' => 'rtl',
                'flag' => '🇸🇦',
                'active' => true,
                'order' => 1,
            ],
            'en' => [
                'code' => 'en',
                'name' => 'English',
                'native_name' => 'English',
                'direction' => 'ltr',
                'flag' => '🇬🇧',
                'active' => true,
                'order' => 2,
            ],
            'fr' => [
                'code' => 'fr',
                'name' => 'French',
                'native_name' => 'Français',
                'direction' => 'ltr',
                'flag' => '🇫🇷',
                'active' => true,
                'order' => 3,
            ],
            'tr' => [
                'code' => 'tr',
                'name' => 'Turkish',
                'native_name' => 'Türkçe',
                'direction' => 'ltr',
                'flag' => '🇹🇷',
                'active' => true,
                'order' => 4,
            ],
            'ur' => [
                'code' => 'ur',
                'name' => 'Urdu',
                'native_name' => 'اردو',
                'direction' => 'rtl',
                'flag' => '🇵🇰',
                'active' => true,
                'order' => 5,
            ],
        ];
    }
}
