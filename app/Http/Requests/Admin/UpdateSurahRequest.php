<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Domain\Quran\Enums\RevelationPlace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Surah Request
 * Validation for updating surah metadata (not content)
 */
class UpdateSurahRequest extends FormRequest
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
            // Multi-language fields (name) - Required
            'name.ar' => ['required', 'string', 'max:255'],
            'name.en' => ['required', 'string', 'max:255'],
            'name.fr' => ['nullable', 'string', 'max:255'],
            'name.tr' => ['nullable', 'string', 'max:255'],
            'name.ur' => ['nullable', 'string', 'max:255'],

            // Multi-language fields (meaning)
            'meaning.ar' => ['nullable', 'string', 'max:255'],
            'meaning.en' => ['nullable', 'string', 'max:255'],
            'meaning.fr' => ['nullable', 'string', 'max:255'],
            'meaning.tr' => ['nullable', 'string', 'max:255'],
            'meaning.ur' => ['nullable', 'string', 'max:255'],

            // Multi-language fields (description)
            'description.ar' => ['nullable', 'string', 'max:2000'],
            'description.en' => ['nullable', 'string', 'max:2000'],
            'description.fr' => ['nullable', 'string', 'max:2000'],
            'description.tr' => ['nullable', 'string', 'max:2000'],
            'description.ur' => ['nullable', 'string', 'max:2000'],

            // Multi-language fields (theme)
            'theme.ar' => ['nullable', 'string', 'max:2000'],
            'theme.en' => ['nullable', 'string', 'max:2000'],
            'theme.fr' => ['nullable', 'string', 'max:2000'],
            'theme.tr' => ['nullable', 'string', 'max:2000'],
            'theme.ur' => ['nullable', 'string', 'max:2000'],

            // Revelation metadata
            'revelation_place' => ['required', Rule::in(RevelationPlace::values())],
            'revelation_order' => ['nullable', 'integer', 'min:1', 'max:114'],

            // Statistics (read-only in most cases)
            'verses_count' => ['nullable', 'integer', 'min:0'],
            'words_count' => ['nullable', 'integer', 'min:0'],
            'letters_count' => ['nullable', 'integer', 'min:0'],

            // Juz information (array)
            'juz' => ['nullable', 'array'],
            'juz.*' => ['integer', 'min:1', 'max:30'],

            // Audio URLs (array)
            'audio_urls' => ['nullable', 'array'],
            'audio_urls.*' => ['url', 'max:500'],

            // Page number
            'page_number' => ['nullable', 'integer', 'min:1', 'max:604'],
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
            'name.ar.max' => 'Arabic name cannot exceed 255 characters',
            'name.en.max' => 'English name cannot exceed 255 characters',
            'revelation_place.required' => 'Revelation place is required',
            'revelation_place.in' => 'Revelation place must be either Makkah or Madinah',
            'revelation_order.min' => 'Revelation order must be at least 1',
            'revelation_order.max' => 'Revelation order cannot exceed 114',
            'verses_count.min' => 'Verses count cannot be negative',
            'juz.*.min' => 'Juz number must be at least 1',
            'juz.*.max' => 'Juz number cannot exceed 30',
            'audio_urls.*.url' => 'Audio URL must be a valid URL',
            'page_number.min' => 'Page number must be at least 1',
            'page_number.max' => 'Page number cannot exceed 604',
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
            'name.ar' => 'Arabic name',
            'name.en' => 'English name',
            'name.fr' => 'French name',
            'name.tr' => 'Turkish name',
            'name.ur' => 'Urdu name',
            'meaning.ar' => 'Arabic meaning',
            'meaning.en' => 'English meaning',
            'meaning.fr' => 'French meaning',
            'meaning.tr' => 'Turkish meaning',
            'meaning.ur' => 'Urdu meaning',
            'description.ar' => 'Arabic description',
            'description.en' => 'English description',
            'description.fr' => 'French description',
            'description.tr' => 'Turkish description',
            'description.ur' => 'Urdu description',
            'theme.ar' => 'Arabic theme',
            'theme.en' => 'English theme',
            'theme.fr' => 'French theme',
            'theme.tr' => 'Turkish theme',
            'theme.ur' => 'Urdu theme',
            'revelation_place' => 'revelation place',
            'revelation_order' => 'revelation order',
            'verses_count' => 'verses count',
            'words_count' => 'words count',
            'letters_count' => 'letters count',
            'page_number' => 'page number',
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
                'name' => array_map(fn ($val) => is_string($val) ? trim($val) : $val, $this->input('name', [])),
            ]);
        }

        if ($this->has('meaning')) {
            $this->merge([
                'meaning' => array_map(fn ($val) => is_string($val) ? trim($val) : $val, $this->input('meaning', [])),
            ]);
        }

        if ($this->has('description')) {
            $this->merge([
                'description' => array_map(fn ($val) => is_string($val) ? trim($val) : $val, $this->input('description', [])),
            ]);
        }

        if ($this->has('theme')) {
            $this->merge([
                'theme' => array_map(fn ($val) => is_string($val) ? trim($val) : $val, $this->input('theme', [])),
            ]);
        }

        // Convert empty revelation_order to null
        if (empty($this->revelation_order)) {
            $this->merge(['revelation_order' => null]);
        }
    }
}
