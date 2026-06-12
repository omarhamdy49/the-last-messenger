<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Domain\Blog\Enums\CategoryType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Category Request
 * Validation for updating existing categories
 */
class UpdateCategoryRequest extends FormRequest
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
        $categoryId = $this->route('id');

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

            // Slug - can be edited manually or auto-generated if empty
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', // Only lowercase letters, numbers, and hyphens
                Rule::unique('categories', 'slug')->ignore($categoryId), // Unique except for this category
            ],

            // Parent category - nullable (for root categories)
            // Cannot set self as parent
            'parent_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
                Rule::notIn([$categoryId]), // Prevent self-reference
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
            'parent_id.not_in' => 'Category cannot be its own parent',
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

        // Trim whitespace from all text fields
        $this->merge([
            'name' => array_map('trim', $this->input('name', [])),
            'description' => array_map('trim', $this->input('description', [])),
        ]);
    }

    /**
     * Validate circular reference in parent hierarchy
     * This is a custom validation that prevents setting a descendant as parent
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $categoryId = $this->route('id');
            $parentId = $this->input('parent_id');

            if ($parentId && $this->wouldCreateCircularReference($categoryId, $parentId)) {
                $validator->errors()->add(
                    'parent_id',
                    'Cannot set a descendant category as parent (circular reference)'
                );
            }
        });
    }

    /**
     * Check if setting the parent would create a circular reference
     *
     * @param  int  $categoryId  Current category ID
     * @param  int  $parentId  Proposed parent ID
     */
    protected function wouldCreateCircularReference(int $categoryId, int $parentId): bool
    {
        // Get all descendants of current category
        $descendants = $this->getAllDescendants($categoryId);

        // Check if proposed parent is in descendants
        return in_array($parentId, $descendants);
    }

    /**
     * Get all descendant IDs of a category (recursive)
     */
    protected function getAllDescendants(int $categoryId): array
    {
        $descendants = [];
        $children = \App\Domain\Blog\Models\Category::where('parent_id', $categoryId)
            ->pluck('id')
            ->toArray();

        foreach ($children as $childId) {
            $descendants[] = $childId;
            $descendants = array_merge($descendants, $this->getAllDescendants($childId));
        }

        return $descendants;
    }
}
