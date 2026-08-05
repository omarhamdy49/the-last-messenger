<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Hadith;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateHadithCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'updated_at' => ['required', 'string'],
            'is_published' => ['required', 'boolean'],
            'featured' => ['required', 'boolean'],
            'name' => ['required', 'array:ar,en,fr,tr,ur'],
            'name.ar' => ['required', 'string', 'max:500'],
            'name.*' => ['nullable', 'string', 'max:500'],
            'author' => ['required', 'array:ar,en,fr,tr,ur'],
            'author.*' => ['nullable', 'string', 'max:500'],
            'short_intro' => ['required', 'array:ar,en,fr,tr,ur'],
            'short_intro.*' => ['nullable', 'string'],
            'about' => ['required', 'array:ar,en,fr,tr,ur'],
            'about.*' => ['nullable', 'string'],
            'introduction' => ['required', 'array:ar,en,fr,tr,ur'],
            'introduction.*' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_published' => $this->boolean('is_published'),
            'featured' => $this->boolean('featured'),
        ]);
    }
}
