<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Tafseer Request (Admin)
 */
class UpdateTafseerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'super_admin', 'editor']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'verse_reference' => [
                'sometimes',
                'required',
                'string',
                'regex:/^\d{1,3}:\d{1,3}$/',
                'max:10',
            ],
            'source' => [
                'sometimes',
                'required',
                'string',
                'in:ibn_kathir,jalalayn,sadi,qurtubi,tabari,baghawi,muyassar,ashhari,qasimi',
            ],
            'text' => ['sometimes', 'required', 'array'],
            'text.ar' => ['sometimes', 'required', 'string', 'min:10'],
            'text.en' => ['sometimes', 'required', 'string', 'min:10'],
            'text.fr' => ['nullable', 'string'],
            'text.tr' => ['nullable', 'string'],
            'text.ur' => ['nullable', 'string'],
            'author' => ['nullable', 'string', 'max:255'],
            'footnotes' => ['nullable', 'array'],
            'footnotes.*' => ['nullable', 'string'],
            'status' => ['nullable', 'in:draft,published'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'verse_reference.required' => 'Verse reference is required',
            'verse_reference.regex' => 'Verse reference must be in format: surah:ayah (e.g., 2:255)',
            'source.required' => 'Tafseer source is required',
            'source.in' => 'Invalid tafseer source selected',
            'text.required' => 'Tafseer text is required',
            'text.ar.required' => 'Arabic text is required',
            'text.ar.min' => 'Arabic text must be at least 10 characters',
            'text.en.required' => 'English text is required',
            'text.en.min' => 'English text must be at least 10 characters',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'verse_reference' => 'verse reference',
            'text.ar' => 'Arabic text',
            'text.en' => 'English text',
            'text.fr' => 'French text',
            'text.tr' => 'Turkish text',
            'text.ur' => 'Urdu text',
        ];
    }
}
