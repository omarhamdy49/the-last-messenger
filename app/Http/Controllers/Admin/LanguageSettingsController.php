<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Shared\Services\LanguageService;
use App\Http\Controllers\Controller;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LanguageSettingsController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly LanguageService $languages) {}

    public function index(): View
    {
        $languages = $this->languages->settings();
        $defaultLanguage = $this->languages->getDefaultLanguage()?->code ?? 'ar';

        return view('admin.settings.languages.index', compact('languages', 'defaultLanguage'));
    }

    public function list(): JsonResponse
    {
        return $this->successResponse($this->languages->settings(), 'Languages retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(['code' => ['required', 'string', Rule::in(array_keys(config('languages.available')))]]);
        $this->languages->seedDefaultLanguages();
        $this->languages->updateSettings($validated['code'], ['active' => true]);

        return $this->successResponse($this->languages->settings()[$validated['code']], 'تم تفعيل اللغة بنجاح');
    }

    public function update(Request $request, string $code): JsonResponse
    {
        $validated = $request->validate([
            'active' => ['sometimes', 'boolean'],
            'order' => ['sometimes', 'integer', 'min:1'],
        ]);
        $this->languages->updateSettings($code, $validated);

        return $this->successResponse($this->languages->settings()[$code], 'تم تحديث إعدادات اللغة بنجاح');
    }

    /** Disabling preserves translations and allows the language to be restored. */
    public function destroy(string $code): JsonResponse
    {
        $this->languages->updateSettings($code, ['active' => false]);

        return $this->successResponse(null, 'تم تعطيل اللغة دون حذف ترجماتها');
    }

    public function setDefault(string $code): JsonResponse
    {
        $this->languages->setDefaultLanguage($code);

        return $this->successResponse(['default_language' => $code], 'تم تعيين اللغة الافتراضية بنجاح');
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'languages' => ['required', 'array'],
            'languages.*' => ['required', 'string', 'distinct'],
        ]);
        $this->languages->reorder($validated['languages']);

        return $this->successResponse($this->languages->settings(), 'تم إعادة ترتيب اللغات بنجاح');
    }

    public function toggle(string $code): JsonResponse
    {
        $this->languages->toggleLanguageStatus($code);

        return $this->successResponse($this->languages->settings()[$code], 'تم تحديث حالة اللغة بنجاح');
    }
}
