<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Series Request
 * Validation for creating a new series
 */
class StoreSeriesRequest extends FormRequest
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
            // Multi-language title fields
            'title.ar' => 'required|string|max:255',
            'title.en' => 'required|string|max:255',
            'title.fr' => 'nullable|string|max:255',
            'title.tr' => 'nullable|string|max:255',
            'title.ur' => 'nullable|string|max:255',

            // Multi-language description fields
            'description.ar' => 'nullable|string|max:1000',
            'description.en' => 'nullable|string|max:1000',
            'description.fr' => 'nullable|string|max:1000',
            'description.tr' => 'nullable|string|max:1000',
            'description.ur' => 'nullable|string|max:1000',

            // Thumbnail upload
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',

            // Slug (auto-generated if not provided)
            'slug' => 'nullable|string|max:255|unique:series,slug',

            // Articles
            'article_ids' => 'nullable|array',
            'article_ids.*' => 'integer|exists:articles,id',
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
            'title.ar.required' => 'العنوان بالعربية مطلوب',
            'title.en.required' => 'English title is required',
            'title.ar.max' => 'العنوان بالعربية يجب أن لا يتجاوز 255 حرف',
            'title.en.max' => 'English title must not exceed 255 characters',
            'description.ar.max' => 'الوصف بالعربية يجب أن لا يتجاوز 1000 حرف',
            'description.en.max' => 'English description must not exceed 1000 characters',
            'thumbnail.image' => 'الصورة المصغرة يجب أن تكون من نوع صورة',
            'thumbnail.mimes' => 'الصورة المصغرة يجب أن تكون من نوع: jpeg, png, jpg, gif, webp',
            'thumbnail.max' => 'حجم الصورة المصغرة يجب أن لا يتجاوز 2 ميجابايت',
            'slug.unique' => 'الرابط المختصر مستخدم بالفعل',
        ];
    }
}
