<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Store Verse Request
 * Validation for creating new Quran verses
 */
class StoreVerseRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Surah relationship - Required
            'surah_id' => [
                'required',
                'integer',
                'exists:quran_surahs,id',
            ],

            // Verse number - Required and unique per surah
            'verse_number' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('quran_verses', 'verse_number')
                    ->where('surah_id', $this->input('surah_id')),
            ],

            // Quran organization metadata. Every rule below maps to a real column on
            // `quran_verses`; `required` mirrors the schema's NOT NULL constraints.
            //
            // Note there are deliberately NO text/translation rules here. Verse text is
            // not a column on this table — the Uthmani text lives per-word in
            // `quran_words.text_uthmani` and translations live in
            // `quran_verse_translations` (a HasMany needing a resource_id). The previous
            // rules required text_uthmani/text_simple/translations.*, which `Verse::create()`
            // silently discarded via $fillable and which the create form never submitted,
            // so every submission failed validation even once the fatal was removed.
            'juz_number' => ['required', 'integer', 'min:1', 'max:30'],
            'hizb_number' => ['required', 'integer', 'min:1', 'max:60'],
            'rub_el_hizb_number' => ['required', 'integer', 'min:1', 'max:240'],
            'ruku_number' => ['required', 'integer', 'min:1', 'max:558'],
            'manzil_number' => ['nullable', 'integer', 'min:1', 'max:7'],
            'page_number' => ['required', 'integer', 'min:1', 'max:604'],

            // Sajda (prostration). The column is `sajdah_number` — 1..15, or NULL for
            // the vast majority of verses. `Verse::hasSajdah()` derives the boolean from
            // it, so there is no separate is_sajda/sajda_type to validate.
            'sajdah_number' => ['nullable', 'integer', 'min:1', 'max:15'],
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
            'surah_id.required' => 'Surah is required',
            'surah_id.exists' => 'Selected surah does not exist',
            'verse_number.required' => 'Verse number is required',
            'verse_number.unique' => 'A verse with this number already exists in the selected surah',
            'verse_number.min' => 'Verse number must be at least 1',
            'juz_number.required' => 'Juz number is required',
            'juz_number.min' => 'Juz number must be at least 1',
            'juz_number.max' => 'Juz number cannot exceed 30',
            'page_number.required' => 'Page number is required',
            'page_number.min' => 'Page number must be at least 1',
            'page_number.max' => 'Page number cannot exceed 604',
            'hizb_number.required' => 'Hizb number is required',
            'hizb_number.min' => 'Hizb number must be at least 1',
            'hizb_number.max' => 'Hizb number cannot exceed 60',
            'rub_el_hizb_number.required' => 'Rub el Hizb number is required',
            'rub_el_hizb_number.min' => 'Rub el Hizb number must be at least 1',
            'rub_el_hizb_number.max' => 'Rub el Hizb number cannot exceed 240',
            'ruku_number.required' => 'Ruku number is required',
            'sajdah_number.min' => 'Sajdah number must be at least 1',
            'sajdah_number.max' => 'Sajdah number cannot exceed 15',
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
            'surah_id' => 'surah',
            'verse_number' => 'verse number',
            'juz_number' => 'Juz number',
            'hizb_number' => 'Hizb number',
            'rub_el_hizb_number' => 'Rub el Hizb number',
            'ruku_number' => 'Ruku number',
            'manzil_number' => 'Manzil number',
            'page_number' => 'page number',
            'sajdah_number' => 'Sajdah number',
        ];
    }

    // No prepareForValidation(): the text/translation trimming that used to live here
    // operated on fields this request no longer validates and that are not columns on
    // `quran_verses`, so validated() dropped them regardless. verse_key is likewise not
    // validated — VerseService::create() derives it from surah_id + verse_number.
}
