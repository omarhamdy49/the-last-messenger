<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Shared\Models\Language;
use App\Domain\Shared\Services\LanguageService;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

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
    public function __construct(private readonly LanguageService $languages) {}

    /**
     * Switch application language
     *
     * POST /language/switch
     */
    public function switch(Request $request): RedirectResponse|JsonResponse
    {
        // Support both 'locale' and 'language' parameters for backward compatibility
        $language = $request->input('language') ?? $request->input('locale');

        // Validate language
        if (! is_string($language) || ! in_array($language, array_keys(config('languages.available')), true)) {
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
     */
    public function all(): JsonResponse
    {
        $languages = $this->languages->settings();

        return $this->successResponse(
            array_values($languages),
            'All languages retrieved successfully'
        );
    }

    /**
     * Get current locale
     *
     * GET /api/languages/current
     */
    public function current(): JsonResponse
    {
        $currentLocale = app()->getLocale();
        $languages = $this->languages->settings();
        $currentLanguage = $languages[$currentLocale] ?? null;

        return $this->successResponse([
            'locale' => $currentLocale,
            'language' => $currentLanguage,
        ], 'Current language retrieved successfully');
    }

    /**
     * Get active languages from cache
     */
    private function getActiveLanguages(): array
    {
        $languages = $this->languages->settings();

        return array_values(array_filter($languages, function ($lang) {
            return $lang['active'] ?? false;
        }));
    }

    private function isLanguageActive(string $code): bool
    {
        return Language::isValidCode($code);
    }
}
