<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Hadith;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHadithCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'slug' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9-]+$/', Rule::unique('hadith_collections', 'slug')->ignore($this->route('collection'))],
            'title_ar' => ['required', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'intro_ar' => ['nullable', 'string'],
            'intro_en' => ['nullable', 'string'],
            // The importer builds public references from this; a wrong template
            // produces dead /hadith/{collection}:{ref} links.
            'reference_template' => ['nullable', 'string', 'max:255'],
            'compiler_id' => ['nullable', 'integer', 'exists:muslims,id'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'has_books' => ['nullable', 'boolean'],
            'has_chapters' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            // title_en and sort_order are NOT NULL with no default. Left optional in
            // the form — an Arabic-only collection is legitimate — so they are
            // coerced here rather than forced on the editor.
            'title_en' => $this->input('title_en') ?? '',
            'sort_order' => (int) ($this->input('sort_order') ?: 0),
            'has_books' => $this->boolean('has_books'),
            'has_chapters' => $this->boolean('has_chapters'),
            'is_featured' => $this->boolean('is_featured'),
            'is_published' => $this->boolean('is_published'),
        ]);
    }
}
