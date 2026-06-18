<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Seerah Milestone Request (Admin)
 */
class UpdateSeerahMilestoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'super_admin']);
    }

    public function rules(): array
    {
        $routeParameter = $this->route('seerah_milestone') ?? $this->route('id');
        $milestoneId = is_object($routeParameter) ? $routeParameter->id : $routeParameter;

        return [
            'slug' => ['sometimes', 'required', 'string', 'max:255', 'regex:/^[a-z0-9\-]+$/', Rule::unique('seerah_milestones', 'slug')->ignore($milestoneId)],
            'order_index' => ['nullable', 'integer', 'min:0'],
            'title' => ['sometimes', 'required', 'array'],
            'title.ar' => ['required_with:title', 'string', 'max:500'],
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
            'title.ar.required_with' => __('admin.field_required'),
            'slug.unique' => __('admin.slug_taken'),
            'slug.regex' => __('admin.slug_format'),
        ];
    }
}
