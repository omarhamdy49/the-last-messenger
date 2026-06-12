<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Store Reciter Request
 * Validates data for creating new Quran reciter
 */
class StoreReciterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Multi-language name (required for ar and en)
            'name.ar' => [
                'required',
                'string',
                'max:255',
            ],
            'name.en' => [
                'required',
                'string',
                'max:255',
            ],
            'name.fr' => [
                'nullable',
                'string',
                'max:255',
            ],
            'name.tr' => [
                'nullable',
                'string',
                'max:255',
            ],
            'name.ur' => [
                'nullable',
                'string',
                'max:255',
            ],

            // Multi-language description (optional)
            'description.ar' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'description.en' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'description.fr' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'description.tr' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'description.ur' => [
                'nullable',
                'string',
                'max:2000',
            ],

            // Slug (auto-generated if not provided)
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                'unique:quran_reciters,slug',
            ],

            // Country
            'country' => [
                'nullable',
                'string',
                'max:100',
            ],

            // Recitation style
            'style' => [
                'nullable',
                Rule::in([
                    'Tajweed',
                    'Qaloon',
                    'Warsh',
                    'Hafs',
                    'Kalon',
                    'Duri',
                    'Kasai',
                ]),
            ],

            // Profile image
            'profile_image' => [
                'nullable',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:2048', // 2MB
            ],

            // Featured status
            'is_featured' => [
                'nullable',
                'boolean',
            ],

            // Display order
            'display_order' => [
                'nullable',
                'integer',
                'min:0',
            ],
        ];
    }

    /**
     * Get custom validation messages
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.ar.required' => 'الاسم بالعربية مطلوب',
            'name.en.required' => 'الاسم بالإنجليزية مطلوب',
            'name.ar.max' => 'الاسم بالعربية يجب ألا يتجاوز 255 حرف',
            'name.en.max' => 'الاسم بالإنجليزية يجب ألا يتجاوز 255 حرف',
            'slug.unique' => 'هذا الرابط المختصر مستخدم مسبقاً',
            'slug.regex' => 'الرابط المختصر يجب أن يحتوي على أحرف صغيرة وأرقام وشرطات فقط',
            'style.in' => 'نوع القراءة غير صالح',
            'profile_image.image' => 'الملف يجب أن يكون صورة',
            'profile_image.mimes' => 'الصورة يجب أن تكون بصيغة jpeg أو png أو webp',
            'profile_image.max' => 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت',
            'display_order.integer' => 'ترتيب العرض يجب أن يكون رقماً صحيحاً',
            'display_order.min' => 'ترتيب العرض يجب أن يكون صفر أو أكبر',
        ];
    }

    /**
     * Prepare the data for validation
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        if (! $this->has('is_featured')) {
            $this->merge(['is_featured' => false]);
        }

        if (! $this->has('display_order')) {
            $this->merge(['display_order' => 0]);
        }
    }
}
