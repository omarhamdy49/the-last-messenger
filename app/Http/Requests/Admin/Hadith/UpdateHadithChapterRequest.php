<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Hadith;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateHadithChapterRequest extends FormRequest
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
            'title' => ['required', 'array:ar,en,fr,tr,ur'],
            'title.ar' => ['required', 'string', 'max:500'],
            'title.*' => ['nullable', 'string', 'max:500'],
            'intro' => ['required', 'array:ar,en,fr,tr,ur'],
            'intro.*' => ['nullable', 'string'],
            'ending' => ['required', 'array:ar,en,fr,tr,ur'],
            'ending.*' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_published' => $this->boolean('is_published')]);
    }
}
