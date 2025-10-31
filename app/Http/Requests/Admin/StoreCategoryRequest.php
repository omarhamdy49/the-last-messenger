<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Domain\Blog\Enums\CategoryType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Store Category Request
 * Validation for creating new categories
 */
class StoreCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Multi-language fields (name)
            'name.ar' => ['required', 'string', 'max:255'],
            'name.en' => ['required', 'string', 'max:255'],
            'name.fr' => ['nullable', 'string', 'max:255'],
            'name.tr' => ['nullable', 'string', 'max:255'],
            'name.ur' => ['nullable', 'string', 'max:255'],

            // Multi-language fields (description)
            'description.ar' => ['nullable', 'string', 'max:1000'],
            'description.en' => ['nullable', 'string', 'max:1000'],
            'description.fr' => ['nullable', 'string', 'max:1000'],
            'description.tr' => ['nullable', 'string', 'max:1000'],
            'description.ur' => ['nullable', 'string', 'max:1000'],

            // Type field - must be one of the enum values
            'type' => ['required', Rule::in(CategoryType::values())],

            // Slug - can be manually provided or auto-generated
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', // Only lowercase letters, numbers, and hyphens
                Rule::unique('categories', 'slug'),
            ],

            // Parent category - nullable (for root categories)
            'parent_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
            ],

            // Optional fields
            'icon' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'display_order' => ['nullable', 'integer', 'min:0'],
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
            'name.en.required' => 'English name is required',
            'name.ar.max' => 'Arabic name cannot exceed 255 characters',
            'name.en.max' => 'English name cannot exceed 255 characters',
            'slug.regex' => 'Slug can only contain lowercase letters, numbers, and hyphens',
            'slug.unique' => 'This slug is already in use. Please choose a different one.',
            'type.required' => 'Category type is required',
            'type.in' => 'Invalid category type. Must be blog, media, or timeline',
            'parent_id.exists' => 'Selected parent category does not exist',
            'color.regex' => 'Color must be a valid hex color code (e.g., #0056b3)',
            'display_order.min' => 'Display order must be at least 0',
        ];
    }

    /**
     * Get custom attribute names for validator errors
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
            'description.ar' => 'Arabic description',
            'description.en' => 'English description',
            'description.fr' => 'French description',
            'description.tr' => 'Turkish description',
            'description.ur' => 'Urdu description',
            'parent_id' => 'parent category',
        ];
    }

    /**
     * Prepare the data for validation
     */
    protected function prepareForValidation(): void
    {
        // Convert empty parent_id to null
        if (empty($this->parent_id)) {
            $this->merge(['parent_id' => null]);
        }

        // Set default display_order if not provided
        if (!$this->has('display_order')) {
            $this->merge(['display_order' => 0]);
        }

        // Trim whitespace from all text fields
        $this->merge([
            'name' => array_map('trim', $this->input('name', [])),
            'description' => array_map('trim', $this->input('description', [])),
        ]);
    }
}
