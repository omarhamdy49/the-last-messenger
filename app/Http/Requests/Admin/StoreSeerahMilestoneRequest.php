<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Content\Concerns\SanitizesRichText;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Seerah Milestone Request (Admin)
 */
class StoreSeerahMilestoneRequest extends FormRequest
{
    use SanitizesRichText;

    private const REMOVED_FIELDS = [
        'order_index', 'icon', 'color', 'gradient', 'hijri_label_from', 'hijri_label_to',
        'hijri_year_from', 'hijri_year_to', 'gregorian_year_from', 'gregorian_year_to',
    ];

    protected function prepareForValidation(): void
    {
        $this->sanitizeRichTextField('description');
    }

    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'super_admin']);
    }

    public function rules(): array
    {
        return [
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9\-]+$/', 'unique:seerah_milestones,slug'],
            'order' => ['required', 'integer', 'min:0'],
            'title' => ['required', 'array'],
            'title.ar' => ['required', 'string', 'max:500'],
            'title.*' => ['nullable', 'string', 'max:500'],
            'subtitle' => ['nullable', 'array'],
            'subtitle.*' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'array'],
            'description.*' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
            'metadata.icon' => ['nullable', 'string', 'max:50'],
            'metadata.color' => ['nullable', 'string', 'max:30'],
            'metadata.gradient' => ['nullable', 'string', 'max:60'],
            'hijri_from' => ['nullable', 'integer', 'between:-100,100'],
            'hijri_to' => ['nullable', 'integer', 'between:-100,100', 'gte:hijri_from'],
            'gregorian_from' => ['nullable', 'integer', 'between:400,800'],
            'gregorian_to' => ['nullable', 'integer', 'between:400,800', 'gte:gregorian_from'],
            'is_active' => ['nullable', 'boolean'],
            ...array_fill_keys(self::REMOVED_FIELDS, ['prohibited']),
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
