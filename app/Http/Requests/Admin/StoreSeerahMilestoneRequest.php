<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Seerah Milestone Request (Admin)
 */
class StoreSeerahMilestoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'super_admin']);
    }

    public function rules(): array
    {
        return [
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9\-]+$/', 'unique:seerah_milestones,slug'],
            'order_index' => ['nullable', 'integer', 'min:0'],
            'title' => ['required', 'array'],
            'title.ar' => ['required', 'string', 'max:500'],
            'title.en' => ['nullable', 'string', 'max:500'],
            'title.fr' => ['nullable', 'string', 'max:500'],
            'title.tr' => ['nullable', 'string', 'max:500'],
            'title.ur' => ['nullable', 'string', 'max:500'],
            'subtitle' => ['nullable', 'array'],
            'subtitle.*' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'array'],
            'description.*' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:30'],
            'gradient' => ['nullable', 'string', 'max:60'],
            'hijri_label_from' => ['nullable', 'string', 'max:100'],
            'hijri_label_to' => ['nullable', 'string', 'max:100'],
            'hijri_year_from' => ['nullable', 'integer', 'between:-100,100'],
            'hijri_year_to' => ['nullable', 'integer', 'between:-100,100'],
            'gregorian_year_from' => ['nullable', 'integer', 'between:400,800'],
            'gregorian_year_to' => ['nullable', 'integer', 'between:400,800'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.ar.required' => __('admin.field_required'),
            'slug.unique' => __('admin.slug_taken'),
            'slug.regex' => __('admin.slug_format'),
        ];
    }
}
