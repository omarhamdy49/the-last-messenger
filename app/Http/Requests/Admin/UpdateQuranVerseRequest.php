<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Quran Verse Request (Admin)
 */
class UpdateQuranVerseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'super_admin']);
    }

    public function rules(): array
    {
        return [
            'surah_number' => ['sometimes', 'required', 'integer', 'min:1', 'max:114'],
            'verse_number' => ['sometimes', 'required', 'integer', 'min:1'],
            'text' => ['sometimes', 'required', 'array'],
            'text.ar' => ['required_with:text', 'string'],
            'text.en' => ['nullable', 'string'],
            'text.fr' => ['nullable', 'string'],
            'text.tr' => ['nullable', 'string'],
            'text.ur' => ['nullable', 'string'],
            'tafseer' => ['nullable', 'array'],
            'tafseer.ar' => ['nullable', 'string'],
            'tafseer.en' => ['nullable', 'string'],
            'tafseer.fr' => ['nullable', 'string'],
            'tafseer.tr' => ['nullable', 'string'],
            'tafseer.ur' => ['nullable', 'string'],
            'juz_number' => ['nullable', 'integer', 'min:1', 'max:30'],
            'page_number' => ['nullable', 'integer', 'min:1', 'max:604'],
        ];
    }
}
