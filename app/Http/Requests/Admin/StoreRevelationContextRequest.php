<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Store Revelation Context Request
 * Validates data for creating an Asbab al-Nuzul entry
 */
class StoreRevelationContextRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by admin middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'surah_id' => ['required', 'integer', 'exists:quran_surahs,id'],
            'verse_from' => ['required', 'integer', 'min:1'],
            'verse_to' => ['nullable', 'integer', 'gte:verse_from'],
            'period' => ['required', Rule::in(['makki', 'madani'])],
            'event_key' => ['nullable', 'string', 'max:255'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],

            // Per-locale translatable content (Arabic is the source of truth)
            'title' => ['required', 'array'],
            'title.ar' => ['required', 'string', 'max:500'],
            'title.en' => ['nullable', 'string', 'max:500'],
            'title.fr' => ['nullable', 'string', 'max:500'],
            'title.tr' => ['nullable', 'string', 'max:500'],
            'title.ur' => ['nullable', 'string', 'max:500'],

            'narrative' => ['required', 'array'],
            'narrative.ar' => ['required', 'string'],
            'narrative.en' => ['nullable', 'string'],
            'narrative.fr' => ['nullable', 'string'],
            'narrative.tr' => ['nullable', 'string'],
            'narrative.ur' => ['nullable', 'string'],

            'source_reference' => ['nullable', 'array'],
            'source_reference.ar' => ['nullable', 'string', 'max:500'],
            'source_reference.en' => ['nullable', 'string', 'max:500'],
            'source_reference.fr' => ['nullable', 'string', 'max:500'],
            'source_reference.tr' => ['nullable', 'string', 'max:500'],
            'source_reference.ur' => ['nullable', 'string', 'max:500'],
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
            'surah_id.required' => 'السورة مطلوبة',
            'surah_id.exists' => 'السورة المحددة غير موجودة',
            'verse_from.required' => 'رقم الآية الأولى مطلوب',
            'verse_from.min' => 'رقم الآية يجب أن يكون 1 أو أكثر',
            'verse_to.gte' => 'رقم الآية الأخيرة يجب أن يكون أكبر من أو يساوي الآية الأولى',
            'period.required' => 'فترة النزول مطلوبة',
            'period.in' => 'فترة النزول يجب أن تكون مكية أو مدنية',
            'title.ar.required' => 'العنوان بالعربية مطلوب',
            'narrative.ar.required' => 'السياق التاريخي بالعربية مطلوب',
        ];
    }

    /**
     * Prepare the data for validation
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('is_active')) {
            $this->merge(['is_active' => false]);
        }

        if (! $this->has('display_order') || $this->input('display_order') === null) {
            $this->merge(['display_order' => 0]);
        }
    }
}
