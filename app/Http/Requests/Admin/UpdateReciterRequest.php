<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Reciter Request
 * Validates data for updating existing Quran reciter
 */
class UpdateReciterRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $reciterId = $this->route('reciter'); // Get ID from route

        return [
            'profile_id' => ['nullable', 'integer', 'exists:quran_reciter_profiles,id'],
            'riwayah' => ['nullable', 'string', 'max:100'],
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

            // Slug (auto-generated if not provided, unique except current)
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('quran_reciters', 'slug')->ignore($reciterId),
                Rule::unique('quran_reciter_aliases', 'slug')->where(fn ($q) => $q->where('recording_id', '!=', $reciterId)),
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
                // Must match what imports store and quran.recordings.* translates.
                Rule::in(['murattal', 'mujawwad', 'muallim']),
            ],

            // Profile image
            'profile_image' => [
                'nullable',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:10240', // 10MB
            ],

            'profile_image_id' => ['nullable', 'integer', 'exists:media,id'],

            // External image (fits the varchar(255) column); used when no file/library pick.
            'profile_image_url' => ['nullable', 'string', 'max:255', 'url:http,https'],
            'remove_profile_image' => ['nullable', 'boolean'],

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
            'profile_image.max' => 'حجم الصورة يجب ألا يتجاوز 10 ميجابايت',
            'profile_image_url.url' => 'رابط الصورة يجب أن يكون رابطاً صحيحاً يبدأ بـ ‎http://‎ أو ‎https://‎',
            'profile_image_url.max' => 'رابط الصورة يجب ألا يتجاوز 255 حرفاً',
            'display_order.integer' => 'ترتيب العرض يجب أن يكون رقماً صحيحاً',
            'display_order.min' => 'ترتيب العرض يجب أن يكون صفر أو أكبر',
        ];
    }
}
