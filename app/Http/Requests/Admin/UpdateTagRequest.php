<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * UpdateTagRequest
 *
 * Validates tag update data
 */
class UpdateTagRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('tags.update');
    }

    /**
     * Get the validation rules that apply to the request
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tagId = $this->route('tag');

        return [
            // Multi-language fields (Arabic and English required, others optional)
            'name.ar' => [
                'required',
                'string',
                'max:100',
                Rule::unique('tags', 'name->ar')->ignore($tagId),
            ],
            'name.en' => [
                'required',
                'string',
                'max:100',
                Rule::unique('tags', 'name->en')->ignore($tagId),
            ],
            'name.fr' => 'nullable|string|max:100',
            'name.tr' => 'nullable|string|max:100',
            'name.ur' => 'nullable|string|max:100',

            // Other fields
            'type' => 'required|in:article,hadith,program,event',
            'slug' => [
                'nullable',
                'string',
                'max:150',
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
            'name.ar.required' => 'Arabic name is required',
            'name.ar.unique' => 'A tag with this Arabic name already exists',
            'name.en.required' => 'English name is required',
            'name.en.unique' => 'A tag with this English name already exists',
            'type.required' => 'Tag type is required',
            'type.in' => 'Invalid tag type. Must be: article, hadith, program, or event',
            'slug.unique' => 'This slug is already in use',
        ];
    }

    /**
     * Prepare the data for validation
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Auto-generate slug from English name if not provided
        if (empty($this->slug) && !empty($this->input('name.en'))) {
            $this->merge([
                'slug' => Str::slug($this->input('name.en')),
            ]);
        }

        // Ensure all language fields are present (set to null if missing)
        $languages = ['ar', 'en', 'fr', 'tr', 'ur'];
        $name = $this->input('name', []);

        foreach ($languages as $lang) {
            if (!isset($name[$lang])) {
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
            'name.ar' => 'Arabic name',
            'name.en' => 'English name',
            'name.fr' => 'French name',
            'name.tr' => 'Turkish name',
            'name.ur' => 'Urdu name',
            'type' => 'tag type',
            'slug' => 'URL slug',
        ];
    }
}
