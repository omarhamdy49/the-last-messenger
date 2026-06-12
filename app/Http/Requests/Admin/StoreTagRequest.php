<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * StoreTagRequest
 *
 * Validates tag creation data
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Multi-language fields (Arabic and English required, others optional)
            'name.ar' => 'required|string|max:100|unique:tags,name->ar',
            'name.en' => 'required|string|max:100|unique:tags,name->en',
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
            'type' => 'required|in:article,hadith,program,event',
            'color' => 'nullable|string|max:7',
            'slug' => 'nullable|string|max:150|unique:tags,slug',
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
     */
    protected function prepareForValidation(): void
    {
        // Auto-generate slug from English name if not provided
        if (empty($this->slug) && ! empty($this->input('name.en'))) {
            $this->merge([
                'slug' => Str::slug($this->input('name.en')),
            ]);
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
