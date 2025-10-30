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
            'book' => ['sometimes', 'required', 'string', 'max:255'],
            'chapter' => ['nullable', 'string', 'max:255'],
            'hadith_number' => ['nullable', 'integer'],
            'text' => ['sometimes', 'required', 'array'],
            'text.ar' => ['required_with:text', 'string'],
            'text.en' => ['nullable', 'string'],
            'text.fr' => ['nullable', 'string'],
            'text.tr' => ['nullable', 'string'],
            'text.ur' => ['nullable', 'string'],
            'grade' => ['nullable', 'string', 'max:100'],
            'narrator' => ['nullable', 'string', 'max:500'],
            'reference' => ['nullable', 'string', 'max:500'],
        ];
    }
}
