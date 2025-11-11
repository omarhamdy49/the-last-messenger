<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Hadith Book Request
 * Validation for updating existing hadith books
 */
class UpdateHadithBookRequest extends FormRequest
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
        $bookId = $this->route('hadith_book') ?? $this->route('id');

        return [
            // Multi-language fields (name)
            'name.ar' => ['required', 'string', 'max:255'],
            'name.en' => ['required', 'string', 'max:255'],
            'name.fr' => ['nullable', 'string', 'max:255'],
            'name.tr' => ['nullable', 'string', 'max:255'],
            'name.ur' => ['nullable', 'string', 'max:255'],

            // Multi-language fields (description)
            'description.ar' => ['nullable', 'string', 'max:2000'],
            'description.en' => ['nullable', 'string', 'max:2000'],
            'description.fr' => ['nullable', 'string', 'max:2000'],
            'description.tr' => ['nullable', 'string', 'max:2000'],
            'description.ur' => ['nullable', 'string', 'max:2000'],

            // Slug - unique identifier for the book (ignore current book)
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('hadith_books', 'slug')->ignore($bookId),
            ],

            // Collection - optional grouping
            'collection_id' => [
                'nullable',
                'integer',
                'exists:hadith_collections,id',
            ],

            // Author information
            'author_name' => ['nullable', 'string', 'max:255'],

            // Hadith count
            'total_hadith_count' => ['nullable', 'integer', 'min:0'],

            // Display order
            'display_order' => ['nullable', 'integer', 'min:0'],

            // Active status
            'active' => ['nullable', 'boolean'],
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
            'name.ar.required' => 'اسم الكتاب بالعربية مطلوب',
            'name.en.required' => 'اسم الكتاب بالإنجليزية مطلوب',
            'name.ar.max' => 'اسم الكتاب بالعربية لا يمكن أن يتجاوز 255 حرفاً',
            'name.en.max' => 'اسم الكتاب بالإنجليزية لا يمكن أن يتجاوز 255 حرفاً',
            'slug.required' => 'معرف الكتاب (slug) مطلوب',
            'slug.regex' => 'معرف الكتاب يجب أن يحتوي على أحرف إنجليزية صغيرة وأرقام وشرطات فقط',
            'slug.unique' => 'هذا المعرف مستخدم مسبقاً. الرجاء اختيار معرف آخر',
            'collection_id.exists' => 'المجموعة المختارة غير موجودة',
            'total_hadith_count.min' => 'عدد الأحاديث يجب أن يكون صفراً أو أكثر',
            'display_order.min' => 'ترتيب العرض يجب أن يكون صفراً أو أكثر',
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
            'name.ar' => 'الاسم بالعربية',
            'name.en' => 'الاسم بالإنجليزية',
            'name.fr' => 'الاسم بالفرنسية',
            'name.tr' => 'الاسم بالتركية',
            'name.ur' => 'الاسم بالأردية',
            'description.ar' => 'الوصف بالعربية',
            'description.en' => 'الوصف بالإنجليزية',
            'description.fr' => 'الوصف بالفرنسية',
            'description.tr' => 'الوصف بالتركية',
            'description.ur' => 'الوصف بالأردية',
            'slug' => 'المعرف',
            'collection_id' => 'المجموعة',
            'author_name' => 'اسم المؤلف',
            'total_hadith_count' => 'عدد الأحاديث',
            'display_order' => 'ترتيب العرض',
            'active' => 'الحالة',
        ];
    }

    /**
     * Prepare the data for validation
     */
    protected function prepareForValidation(): void
    {
        // Convert empty collection_id to null
        if (empty($this->collection_id)) {
            $this->merge(['collection_id' => null]);
        }

        // Trim whitespace from all text fields
        if ($this->has('name')) {
            $this->merge([
                'name' => array_map('trim', $this->input('name', [])),
            ]);
        }

        if ($this->has('description')) {
            $this->merge([
                'description' => array_map('trim', $this->input('description', [])),
            ]);
        }

        // Trim author name
        if ($this->has('author_name')) {
            $this->merge([
                'author_name' => trim($this->input('author_name')),
            ]);
        }

        // Trim and lowercase slug
        if ($this->has('slug')) {
            $this->merge([
                'slug' => strtolower(trim($this->input('slug'))),
            ]);
        }
    }
}
