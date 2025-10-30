<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Hadith Request (Admin)
 */
class UpdateHadithRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'super_admin']);
    }

    public function rules(): array
    {
        return [
            'book_id' => ['sometimes', 'required', 'integer', 'exists:hadith_books,id'],
            'chapter_id' => ['sometimes', 'required', 'integer', 'exists:hadith_chapters,id'],
            'hadith_number' => ['sometimes', 'required', 'integer', 'min:1'],
            'arabic_text' => ['sometimes', 'required', 'string'],
            'narrator_chain' => ['nullable', 'string'],
            'translations' => ['nullable', 'array'],
            'translations.en' => ['nullable', 'string'],
            'translations.fr' => ['nullable', 'string'],
            'translations.tr' => ['nullable', 'string'],
            'translations.ur' => ['nullable', 'string'],
            'grade' => ['sometimes', 'required', 'in:sahih,hasan,daif,mawdu,unknown'],
            'reference_url' => ['nullable', 'url', 'max:500'],
            'narrators' => ['sometimes', 'required', 'array', 'min:1'],
            'narrators.*' => ['integer', 'exists:hadith_narrators,id'],
            'variant_hadith_ids' => ['nullable', 'array'],
            'variant_hadith_ids.*' => ['integer', 'exists:hadith,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'arabic_text.required' => 'النص العربي مطلوب',
            'book_id.required' => 'الكتاب مطلوب',
            'chapter_id.required' => 'الباب مطلوب',
            'hadith_number.required' => 'رقم الحديث مطلوب',
            'grade.required' => 'درجة الحديث مطلوبة',
            'narrators.required' => 'يجب اختيار راوٍ واحد على الأقل',
        ];
    }
}
