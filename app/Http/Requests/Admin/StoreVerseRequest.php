<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Domain\Quran\Enums\SajdaType;
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

            // Arabic text - Required
            'text_uthmani' => ['required', 'string'],
            'text_simple' => ['required', 'string'],
            'text_imlaei' => ['nullable', 'string'],

            // Multi-language translations (JSON) - Required for ar and en
            'translations.ar' => ['required', 'string'],
            'translations.en' => ['required', 'string'],
            'translations.fr' => ['nullable', 'string'],
            'translations.tr' => ['nullable', 'string'],
            'translations.ur' => ['nullable', 'string'],

            // Transliteration
            'transliteration' => ['nullable', 'string', 'max:1000'],

            // Quran organization metadata
            'juz_number' => ['required', 'integer', 'min:1', 'max:30'],
            'hizb_number' => ['nullable', 'integer', 'min:1', 'max:60'],
            'page_number' => ['required', 'integer', 'min:1', 'max:604'],
            'rub_number' => ['nullable', 'integer', 'min:1', 'max:240'],

            // Sajda (prostration) information
            'is_sajda' => ['nullable', 'boolean'],
            'sajda_type' => ['nullable', Rule::in(SajdaType::values())],

            // Audio URLs (array)
            'audio_urls' => ['nullable', 'array'],
            'audio_urls.*' => ['url', 'max:500'],

            // Statistics
            'words_count' => ['nullable', 'integer', 'min:0'],
            'letters_count' => ['nullable', 'integer', 'min:0'],
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
            'text_uthmani.required' => 'Uthmani text (Arabic) is required',
            'text_simple.required' => 'Simple text (Arabic) is required',
            'translations.ar.required' => 'Arabic translation is required',
            'translations.en.required' => 'English translation is required',
            'juz_number.required' => 'Juz number is required',
            'juz_number.min' => 'Juz number must be at least 1',
            'juz_number.max' => 'Juz number cannot exceed 30',
            'page_number.required' => 'Page number is required',
            'page_number.min' => 'Page number must be at least 1',
            'page_number.max' => 'Page number cannot exceed 604',
            'hizb_number.min' => 'Hizb number must be at least 1',
            'hizb_number.max' => 'Hizb number cannot exceed 60',
            'rub_number.min' => 'Rub number must be at least 1',
            'rub_number.max' => 'Rub number cannot exceed 240',
            'sajda_type.in' => 'Invalid Sajda type',
            'audio_urls.*.url' => 'Audio URL must be a valid URL',
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
            'text_uthmani' => 'Uthmani text',
            'text_simple' => 'simple text',
            'text_imlaei' => 'Imlaei text',
            'translations.ar' => 'Arabic translation',
            'translations.en' => 'English translation',
            'translations.fr' => 'French translation',
            'translations.tr' => 'Turkish translation',
            'translations.ur' => 'Urdu translation',
            'juz_number' => 'Juz number',
            'hizb_number' => 'Hizb number',
            'page_number' => 'page number',
            'rub_number' => 'Rub number',
            'is_sajda' => 'Sajda verse',
            'sajda_type' => 'Sajda type',
            'words_count' => 'words count',
            'letters_count' => 'letters count',
        ];
    }

    /**
     * Prepare the data for validation
     */
    protected function prepareForValidation(): void
    {
        // Convert is_sajda to boolean
        if ($this->has('is_sajda')) {
            $this->merge([
                'is_sajda' => filter_var($this->is_sajda, FILTER_VALIDATE_BOOLEAN),
            ]);
        } else {
            $this->merge(['is_sajda' => false]);
        }

        // If not sajda, clear sajda_type
        if (! $this->is_sajda) {
            $this->merge(['sajda_type' => null]);
        }

        // Trim whitespace from text fields
        if ($this->has('text_uthmani')) {
            $this->merge(['text_uthmani' => trim($this->text_uthmani)]);
        }
        if ($this->has('text_simple')) {
            $this->merge(['text_simple' => trim($this->text_simple)]);
        }
        if ($this->has('text_imlaei')) {
            $this->merge(['text_imlaei' => trim($this->text_imlaei)]);
        }

        // Trim translations
        if ($this->has('translations')) {
            $this->merge([
                'translations' => array_map(
                    fn ($val) => is_string($val) ? trim($val) : $val,
                    $this->input('translations', [])
                ),
            ]);
        }

        // Auto-generate verse_key if not provided
        if (! $this->has('verse_key') && $this->has('surah_id') && $this->has('verse_number')) {
            // Simple calculation: verse_key is cumulative across all surahs
            // This would need proper implementation based on actual data
            $this->merge(['verse_key' => 0]); // Will be calculated in service
        }
    }
}
