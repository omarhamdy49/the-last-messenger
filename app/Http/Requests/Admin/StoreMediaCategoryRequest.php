<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * StoreMediaCategoryRequest
 *
 * Validation for creating new media categories
 */
class StoreMediaCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermissionTo('media-category.create');
    }

    /**
     * Get the validation rules that apply to the request
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Multi-language fields (name is required for ar and en)
            'name.ar' => 'required|string|max:255',
            'name.en' => 'required|string|max:255',
            'name.fr' => 'nullable|string|max:255',
            'name.tr' => 'nullable|string|max:255',
            'name.ur' => 'nullable|string|max:255',

            // Description is optional
            'description.ar' => 'nullable|string|max:1000',
            'description.en' => 'nullable|string|max:1000',
            'description.fr' => 'nullable|string|max:1000',
            'description.tr' => 'nullable|string|max:1000',
            'description.ur' => 'nullable|string|max:1000',

            // Other fields
            'icon' => 'nullable|string|max:50',
            'display_order' => 'nullable|integer|min:0',
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
            'name.ar.max' => 'Arabic name must not exceed 255 characters',
            'name.en.max' => 'English name must not exceed 255 characters',
            'icon.max' => 'Icon must not exceed 50 characters',
            'display_order.integer' => 'Display order must be an integer',
            'display_order.min' => 'Display order must be at least 0',
        ];
    }

    /**
     * Prepare the data for validation
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Generate slug from English name if not provided
        if (!$this->has('slug') && $this->has('name.en')) {
            $this->merge([
                'slug' => Str::slug($this->input('name.en'))
            ]);
        }

        // Set default display_order if not provided
        if (!$this->has('display_order')) {
            $this->merge(['display_order' => 0]);
        }
    }
}
