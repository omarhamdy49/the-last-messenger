<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Hadith Chapter Request
 * Validation for updating existing hadith chapters
 */
class UpdateHadithChapterRequest extends FormRequest
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
            // Book ID - required
            'book_id' => [
                'required',
                'integer',
                'exists:hadith_books,id',
            ],

            // Chapter number
            'chapter_number' => ['required', 'integer', 'min:1'],

            // Multi-language fields (name)
            'name.ar' => ['required', 'string', 'max:500'],
            'name.en' => ['required', 'string', 'max:500'],
            'name.fr' => ['nullable', 'string', 'max:500'],
            'name.tr' => ['nullable', 'string', 'max:500'],
            'name.ur' => ['nullable', 'string', 'max:500'],

            // Multi-language fields (description)
            'description.ar' => ['nullable', 'string', 'max:2000'],
            'description.en' => ['nullable', 'string', 'max:2000'],
            'description.fr' => ['nullable', 'string', 'max:2000'],
            'description.tr' => ['nullable', 'string', 'max:2000'],
            'description.ur' => ['nullable', 'string', 'max:2000'],

            // Display order
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
            'book_id.required' => 'الكتاب مطلوب',
            'book_id.exists' => 'الكتاب المختار غير موجود',
            'chapter_number.required' => 'رقم الباب مطلوب',
            'chapter_number.min' => 'رقم الباب يجب أن يكون 1 أو أكثر',
            'name.ar.required' => 'اسم الباب بالعربية مطلوب',
            'name.en.required' => 'اسم الباب بالإنجليزية مطلوب',
            'name.ar.max' => 'اسم الباب بالعربية لا يمكن أن يتجاوز 500 حرف',
            'name.en.max' => 'اسم الباب بالإنجليزية لا يمكن أن يتجاوز 500 حرف',
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
            'book_id' => 'الكتاب',
            'chapter_number' => 'رقم الباب',
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
            'display_order' => 'ترتيب العرض',
        ];
    }

    /**
     * Prepare the data for validation
     */
    protected function prepareForValidation(): void
    {
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
    }
}
