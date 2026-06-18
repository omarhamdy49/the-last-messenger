<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Seerah Event Type Request (Admin)
 */
class UpdateSeerahEventTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'super_admin']);
    }

    public function rules(): array
    {
        $routeParameter = $this->route('seerah_event_type') ?? $this->route('id');
        $typeId = is_object($routeParameter) ? $routeParameter->id : $routeParameter;

        return [
            'slug' => ['sometimes', 'required', 'string', 'max:255', 'regex:/^[a-z0-9\-]+$/', Rule::unique('seerah_event_types', 'slug')->ignore($typeId)],
            'name' => ['sometimes', 'required', 'array'],
            'name.ar' => ['required_with:name', 'string', 'max:255'],
            'name.en' => ['nullable', 'string', 'max:255'],
            'name.fr' => ['nullable', 'string', 'max:255'],
            'name.tr' => ['nullable', 'string', 'max:255'],
            'name.ur' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:30'],
            'gradient' => ['nullable', 'string', 'max:60'],
            'is_active' => ['nullable', 'boolean'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.ar.required_with' => __('admin.field_required'),
            'slug.unique' => __('admin.slug_taken'),
            'slug.regex' => __('admin.slug_format'),
        ];
    }
}
