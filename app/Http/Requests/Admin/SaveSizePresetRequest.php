<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Save Size Preset Request
 *
 * Validates size preset creation/update
 */
class SaveSizePresetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasAnyRole(['admin', 'super_admin']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $presetId = $this->route('preset')?->id ?? $this->route('preset');

        return [
            'name' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('media_size_presets', 'name')->ignore($presetId),
            ],
            'width' => 'required|integer|min:1|max:10000',
            'height' => 'required|integer|min:1|max:10000',
            'method' => 'required|in:crop,fit,resize',
            'quality' => 'nullable|integer|min:1|max:100',
            'enabled' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0|max:10000',
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
            'name.required' => __('media.validation.preset_name_required'),
            'name.regex' => __('media.validation.preset_name_format'),
            'width.required' => __('media.validation.preset_width_required'),
            'width.min' => __('media.validation.preset_width_min'),
            'width.max' => __('media.validation.preset_width_max'),
            'height.required' => __('media.validation.preset_height_required'),
            'height.min' => __('media.validation.preset_height_min'),
            'height.max' => __('media.validation.preset_height_max'),
            'method.required' => __('media.validation.preset_method_required'),
            'method.in' => __('media.validation.preset_method_invalid'),
            'quality.min' => __('media.validation.preset_quality_min'),
            'quality.max' => __('media.validation.preset_quality_max'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'enabled' => $this->boolean('enabled', true),
            'quality' => $this->integer('quality', 85),
            'sort_order' => $this->integer('sort_order', 0),
        ]);
    }
}
