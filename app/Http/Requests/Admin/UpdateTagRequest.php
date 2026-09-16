<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * UpdateTagRequest
 *
 * Validates tag update data. Names live in the `tag_translations`
 * table (one row per locale) — uniqueness is checked there, excluding
 * the tag being updated.
 */
class UpdateTagRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('tags.update');
    }

    /**
     * Get the validation rules that apply to the request
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tag = $this->route('tag');
        $tagId = is_object($tag) ? $tag->id : (int) $tag;

        return [
            // Multi-language fields (Arabic required, others optional)
            'name.ar' => [
                'required', 'string', 'max:100',
                Rule::unique('tag_translations', 'name')
                    ->where('locale', 'ar')
                    ->where(fn ($q) => $q->where('tag_id', '!=', $tagId)),
            ],
            'name.en' => [
                'nullable', 'string', 'max:100',
                Rule::unique('tag_translations', 'name')
                    ->where('locale', 'en')
                    ->where(fn ($q) => $q->where('tag_id', '!=', $tagId)),
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
            'slug' => [
                'required', 'string', 'max:150',
                Rule::unique('tags', 'slug')->ignore($tagId),
            ],
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

        if (empty($this->input('type'))) {
            $this->merge(['type' => 'article']);
        }

        // Ensure all language fields are present (set to null if missing)
        $languages = enabled_content_locales();
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
