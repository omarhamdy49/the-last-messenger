<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Hadith;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHadithChapterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'book_id' => ['required', 'integer', 'exists:hadith_books,id'],
            'chapter_number' => ['required', 'string', 'max:32'],
            'title_ar' => ['required', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'intro' => ['nullable', 'string'],
            'ending' => ['nullable', 'string'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'is_published' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // sort_order is NOT NULL with no default.
        $this->merge([
            'is_published' => $this->boolean('is_published'),
            'sort_order' => (int) ($this->input('sort_order') ?: 0),
        ]);
    }
}
