<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Seerah Event Type Request (Admin)
 *
 * Names are posted per locale as `locale[ar]`, `locale[en]`, … and stored on the
 * type itself.
 */
class StoreSeerahEventTypeRequest extends FormRequest
{
    private const REMOVED_FIELDS = ['name', 'icon', 'color', 'gradient', 'display_order'];

    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'super_admin']);
    }

    public function rules(): array
    {
        return [
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9\-]+$/', 'unique:seerah_event_types,slug'],
            'locale' => ['required', 'array'],
            'locale.ar' => ['required', 'string', 'max:255'],
            'locale.en' => ['nullable', 'string', 'max:255'],
            'locale.fr' => ['nullable', 'string', 'max:255'],
            'locale.tr' => ['nullable', 'string', 'max:255'],
            'locale.ur' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
            'metadata.icon' => ['nullable', 'string', 'max:50'],
            'metadata.color' => ['nullable', 'string', 'max:30'],
            'metadata.gradient' => ['nullable', 'string', 'max:60'],
            'is_active' => ['nullable', 'boolean'],
            ...array_fill_keys(self::REMOVED_FIELDS, ['prohibited']),
        ];
    }

    public function messages(): array
    {
        return [
            'locale.ar.required' => __('admin.field_required'),
            'slug.unique' => __('admin.slug_taken'),
            'slug.regex' => __('admin.slug_format'),
        ];
    }
}
