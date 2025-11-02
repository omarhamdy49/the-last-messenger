<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

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
        return [
            'name' => 'required|string|max:50|regex:/^[a-z0-9_]+$/',
            'width' => 'required|integer|min:1|max:10000',
            'height' => 'required|integer|min:1|max:10000',
            'method' => 'required|in:crop,fit,resize',
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
            'name.required' => 'Preset name is required',
            'name.regex' => 'Preset name can only contain lowercase letters, numbers, and underscores',
            'width.required' => 'Width is required',
            'width.min' => 'Width must be at least 1 pixel',
            'width.max' => 'Width cannot exceed 10000 pixels',
            'height.required' => 'Height is required',
            'height.min' => 'Height must be at least 1 pixel',
            'height.max' => 'Height cannot exceed 10000 pixels',
            'method.required' => 'Resize method is required',
            'method.in' => 'Method must be one of: crop, fit, or resize',
        ];
    }
}
