<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Hadith Request (Admin)
 */
class StoreHadithRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'super_admin']);
    }

    public function rules(): array
    {
        return [
            'book' => ['required', 'string', 'max:255'],
            'chapter' => ['nullable', 'string', 'max:255'],
            'hadith_number' => ['nullable', 'integer'],
            'text' => ['required', 'array'],
            'text.ar' => ['required', 'string'],
            'text.en' => ['nullable', 'string'],
            'text.fr' => ['nullable', 'string'],
            'text.tr' => ['nullable', 'string'],
            'text.ur' => ['nullable', 'string'],
            'grade' => ['nullable', 'string', 'max:100'],
            'narrator' => ['nullable', 'string', 'max:500'],
            'reference' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'text.ar.required' => 'Arabic text is required',
            'book.required' => 'Book name is required',
        ];
    }
}
