<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Domain\Shared\Enums\LocationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Store Location Request (Admin)
 */
class StoreLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'super_admin']) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'slug' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9\-]+$/', 'unique:locations,slug'],
            'type' => ['required', Rule::enum(LocationType::class)],
            'parent_id' => ['nullable', 'integer', 'exists:locations,id'],

            // Arabic is the default AND fallback locale, so a place without it
            // would render blank in every language.
            'name' => ['required', 'array'],
            'name.ar' => ['required', 'string', 'max:255'],
            'name.en' => ['nullable', 'string', 'max:255'],
            'name.fr' => ['nullable', 'string', 'max:255'],
            'name.tr' => ['nullable', 'string', 'max:255'],
            'name.ur' => ['nullable', 'string', 'max:255'],

            'description' => ['nullable', 'array'],
            'description.*' => ['nullable', 'string', 'max:5000'],

            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],

            'aliases' => ['nullable', 'array', 'max:50'],
            'aliases.*' => ['nullable', 'string', 'max:255'],

            'is_active' => ['nullable', 'boolean'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.ar.required' => __('locations.errors.name_required_ar'),
            'slug.unique' => __('admin.slug_taken'),
            'slug.regex' => __('admin.slug_format'),
        ];
    }

    /**
     * Coordinates arrive as empty strings from an untouched form field.
     * Normalise them to null so "no coordinate" and "coordinate 0" stay distinct.
     */
    protected function prepareForValidation(): void
    {
        foreach (['latitude', 'longitude'] as $field) {
            if ($this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }
    }
}
