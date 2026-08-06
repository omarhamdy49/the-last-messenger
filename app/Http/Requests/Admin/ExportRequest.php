<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Domain\Shared\Enums\ExportFormat;
use App\Domain\Shared\Enums\ExportModule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Export Request
 * Validates export parameters
 */
class ExportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'module' => ['required', 'string', Rule::in(ExportModule::values())],
            'format' => ['required', 'string', Rule::in(ExportFormat::values())],
            'language' => ['nullable', 'string', Rule::in(['ar', 'en', 'fr', 'tr', 'ur'])],
            'selected_ids' => ['nullable', 'array'],
            'selected_ids.*' => ['integer', 'min:1'],
            'filters' => ['nullable', 'array'],
            'columns' => ['nullable', 'array'],
            'columns.*' => ['string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'module.required' => 'يجب تحديد الوحدة المراد تصديرها',
            'module.in' => 'الوحدة المحددة غير صالحة',
            'format.required' => 'يجب تحديد صيغة التصدير',
            'format.in' => 'صيغة التصدير غير صالحة',
            'language.in' => 'اللغة المحددة غير صالحة',
            'selected_ids.array' => 'يجب أن تكون المعرفات المحددة مصفوفة',
            'selected_ids.*.integer' => 'يجب أن تكون المعرفات المحددة أرقام صحيحة',
            'filters.array' => 'يجب أن تكون الفلاتر مصفوفة',
            'columns.array' => 'يجب أن تكون الأعمدة مصفوفة',
        ];
    }

    /**
     * Get validated data with defaults
     *
     * @return array<string, mixed>
     */
    public function validatedWithDefaults(): array
    {
        $validated = $this->validated();

        return [
            'module' => $validated['module'],
            'format' => $validated['format'],
            'language' => $validated['language'] ?? 'en',
            'selected_ids' => $validated['selected_ids'] ?? [],
            'filters' => $validated['filters'] ?? [],
            'columns' => $validated['columns'] ?? [],
        ];
    }
}
