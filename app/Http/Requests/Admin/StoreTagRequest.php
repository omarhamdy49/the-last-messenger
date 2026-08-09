<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * StoreTagRequest
 *
 * Validates tag creation data. Names live in the `tag_translations`
 * table (one row per locale) — uniqueness is checked there, not on the
 * `tags` table, which only stores the shared slug/type/color.
 */
class StoreTagRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('tags.create');
    }

    /**
     * Get the validation rules that apply to the request
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Multi-language fields (Arabic required, others optional)
            'name.ar' => [
                'required', 'string', 'max:100',
                Rule::unique('tag_translations', 'name')->where('locale', 'ar'),
            ],
            'name.en' => [
                'nullable', 'string', 'max:100',
                Rule::unique('tag_translations', 'name')->where('locale', 'en'),
            ],
            'name.fr' => 'nullable|string|max:100',
            'name.tr' => 'nullable|string|max:100',
            'name.ur' => 'nullable|string|max:100',

            // Multi-language description
            'description' => 'nullable|array',
            'description.ar' => 'nullable|string',
            'description.en' => 'nullable|string',
            'description.fr' => 'nullable|string',
            'description.tr' => 'nullable|string',
            'description.ur' => 'nullable|string',

            // Other fields
            'type' => 'required|in:article,hadith,program,event,general',
            'color' => 'nullable|string|max:7',
            'slug' => 'required|string|max:150|unique:tags,slug',
        ];
    }

    /**
     * Get custom messages for validator errors
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.ar.required' => __('admin.tag_name_ar_required'),
            'name.ar.unique' => __('admin.tag_name_exists'),
            'name.en.unique' => __('admin.tag_name_exists'),
            'slug.required' => __('admin.slug_required'),
            'slug.unique' => __('admin.slug_taken'),
        ];
    }

    /**
     * Prepare the data for validation
     */
    protected function prepareForValidation(): void
    {
        // Auto-generate slug from the English then Arabic name if not provided
        if (empty($this->input('slug'))) {
            $source = $this->input('name.en') ?: $this->input('name.ar');
            $slug = $source ? Str::slug($source) : null;
            $this->merge(['slug' => $slug ?: null]);
        }

        // Blog admin creates article tags unless told otherwise
        if (empty($this->input('type'))) {
            $this->merge(['type' => 'article']);
        }

        // Ensure all language fields are present (set to null if missing)
        $languages = ['ar', 'en', 'fr', 'tr', 'ur'];
        $name = $this->input('name', []);

        foreach ($languages as $lang) {
            if (! isset($name[$lang])) {
                $name[$lang] = null;
            }
        }

        $this->merge(['name' => $name]);
    }

    /**
     * Get custom attributes for validator errors
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name.ar' => __('admin.tag_name').' (AR)',
            'name.en' => __('admin.tag_name').' (EN)',
            'name.fr' => __('admin.tag_name').' (FR)',
            'name.tr' => __('admin.tag_name').' (TR)',
            'name.ur' => __('admin.tag_name').' (UR)',
            'type' => __('admin.type'),
            'slug' => __('admin.slug'),
        ];
    }
}
