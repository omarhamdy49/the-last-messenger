<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Language Settings Controller
 * Manages system languages and localization settings
 */
class LanguageSettingsController extends Controller
{
    use ApiResponse;

    /**
     * Available languages configuration
     */
    private array $availableLanguages = [
        'ar' => [
            'code' => 'ar',
            'name' => 'Arabic',
            'native_name' => 'العربية',
            'direction' => 'rtl',
            'flag' => '🇸🇦',
        ],
        'en' => [
            'code' => 'en',
            'name' => 'English',
            'native_name' => 'English',
            'direction' => 'ltr',
            'flag' => '🇬🇧',
        ],
        'fr' => [
            'code' => 'fr',
            'name' => 'French',
            'native_name' => 'Français',
            'direction' => 'ltr',
            'flag' => '🇫🇷',
        ],
        'tr' => [
            'code' => 'tr',
            'name' => 'Turkish',
            'native_name' => 'Türkçe',
            'direction' => 'ltr',
            'flag' => '🇹🇷',
        ],
        'ur' => [
            'code' => 'ur',
            'name' => 'Urdu',
            'native_name' => 'اردو',
            'direction' => 'rtl',
            'flag' => '🇵🇰',
        ],
    ];

    /**
     * Display language settings page
     *
     * GET /su/portal/v1/admin/settings/languages
     */
    public function index(): View
    {
        $languages = $this->getLanguagesFromCache();
        $defaultLanguage = config('app.locale', 'ar');

        return view('admin.settings.languages.index', compact('languages', 'defaultLanguage'));
    }

    /**
     * Get all languages (API endpoint)
     *
     * GET /su/portal/v1/admin/settings/languages/list
     */
    public function list(): JsonResponse
    {
        $languages = $this->getLanguagesFromCache();

        return $this->successResponse($languages, 'Languages retrieved successfully');
    }

    /**
     * Add a new language
     *
     * POST /su/portal/v1/admin/settings/languages
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'size:2', Rule::in(array_keys($this->availableLanguages))],
        ]);

        $languages = $this->getLanguagesFromCache();

        // Check if language already exists
        if (isset($languages[$validated['code']])) {
            return $this->errorResponse('اللغة موجودة بالفعل', 422);
        }

        // Add language
        $languages[$validated['code']] = array_merge(
            $this->availableLanguages[$validated['code']],
            [
                'active' => true,
                'order' => count($languages) + 1,
            ]
        );

        $this->saveLanguagesToCache($languages);

        return $this->successResponse(
            $languages[$validated['code']],
            'تمت إضافة اللغة بنجاح'
        );
    }

    /**
     * Update language settings
     *
     * PUT /su/portal/v1/admin/settings/languages/{code}
     */
    public function update(Request $request, string $code): JsonResponse
    {
        $validated = $request->validate([
            'active' => 'sometimes|boolean',
            'order' => 'sometimes|integer|min:1',
        ]);

        $languages = $this->getLanguagesFromCache();

        if (! isset($languages[$code])) {
            return $this->errorResponse('اللغة غير موجودة', 404);
        }

        // Update language settings
        if (isset($validated['active'])) {
            // Prevent deactivating the default language
            if ($code === config('app.locale') && ! $validated['active']) {
                return $this->errorResponse('لا يمكن تعطيل اللغة الافتراضية', 422);
            }
            $languages[$code]['active'] = $validated['active'];
        }

        if (isset($validated['order'])) {
            $languages[$code]['order'] = $validated['order'];
        }

        $this->saveLanguagesToCache($languages);

        return $this->successResponse(
            $languages[$code],
            'تم تحديث إعدادات اللغة بنجاح'
        );
    }

    /**
     * Delete a language
     *
     * DELETE /su/portal/v1/admin/settings/languages/{code}
     */
    public function destroy(string $code): JsonResponse
    {
        // Prevent deleting default language
        if ($code === config('app.locale')) {
            return $this->errorResponse('لا يمكن حذف اللغة الافتراضية', 422);
        }

        $languages = $this->getLanguagesFromCache();

        if (! isset($languages[$code])) {
            return $this->errorResponse('اللغة غير موجودة', 404);
        }

        unset($languages[$code]);
        $this->saveLanguagesToCache($languages);

        return $this->successResponse(null, 'تم حذف اللغة بنجاح');
    }

    /**
     * Set default language
     *
     * POST /su/portal/v1/admin/settings/languages/{code}/set-default
     */
    public function setDefault(string $code): JsonResponse
    {
        $languages = $this->getLanguagesFromCache();

        if (! isset($languages[$code])) {
            return $this->errorResponse('اللغة غير موجودة', 404);
        }

        if (! $languages[$code]['active']) {
            return $this->errorResponse('لا يمكن تعيين لغة غير نشطة كلغة افتراضية', 422);
        }

        // Update config (this is temporary, would need to update .env for persistence)
        Config::set('app.locale', $code);
        Cache::forever('app.locale', $code);

        return $this->successResponse(
            ['default_language' => $code],
            'تم تعيين اللغة الافتراضية بنجاح'
        );
    }

    /**
     * Reorder languages
     *
     * POST /su/portal/v1/admin/settings/languages/reorder
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'languages' => 'required|array',
            'languages.*' => 'required|string|size:2',
        ]);

        $languages = $this->getLanguagesFromCache();
        $newOrder = [];

        foreach ($validated['languages'] as $index => $code) {
            if (isset($languages[$code])) {
                $languages[$code]['order'] = $index + 1;
                $newOrder[$code] = $languages[$code];
            }
        }

        $this->saveLanguagesToCache($newOrder);

        return $this->successResponse(
            $newOrder,
            'تم إعادة ترتيب اللغات بنجاح'
        );
    }

    /**
     * Toggle language active status
     *
     * POST /su/portal/v1/admin/settings/languages/{code}/toggle
     */
    public function toggle(string $code): JsonResponse
    {
        $languages = $this->getLanguagesFromCache();

        if (! isset($languages[$code])) {
            return $this->errorResponse('اللغة غير موجودة', 404);
        }

        // Prevent deactivating the default language
        if ($code === config('app.locale') && $languages[$code]['active']) {
            return $this->errorResponse('لا يمكن تعطيل اللغة الافتراضية', 422);
        }

        $languages[$code]['active'] = ! $languages[$code]['active'];
        $this->saveLanguagesToCache($languages);

        $status = $languages[$code]['active'] ? 'تم تفعيل' : 'تم تعطيل';

        return $this->successResponse(
            $languages[$code],
            "{$status} اللغة بنجاح"
        );
    }

    /**
     * Get languages from cache or initialize default
     */
    private function getLanguagesFromCache(): array
    {
        return Cache::remember('system.languages', now()->addDays(7), function () {
            // Initialize with default languages (all active)
            $languages = [];
            foreach ($this->availableLanguages as $code => $lang) {
                $languages[$code] = array_merge($lang, [
                    'active' => true,
                    'order' => count($languages) + 1,
                ]);
            }

            return $languages;
        });
    }

    /**
     * Save languages to cache
     */
    private function saveLanguagesToCache(array $languages): void
    {
        Cache::put('system.languages', $languages, now()->addDays(7));
    }
}
