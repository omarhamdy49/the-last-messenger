<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Hadith;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateThemeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'super_admin']);
    }

    public function rules(): array
    {
        return [
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('tags', 'slug')->ignore($this->route('theme'))],
            'name' => ['required', 'array'],
            'name.ar' => ['required', 'string', 'max:255'],
            'name.en' => ['nullable', 'string', 'max:255'],
            'name.fr' => ['nullable', 'string', 'max:255'],
            'name.tr' => ['nullable', 'string', 'max:255'],
            'name.ur' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'array'],
            'description.ar' => ['nullable', 'string'],
            'description.en' => ['nullable', 'string'],
            'description.fr' => ['nullable', 'string'],
            'description.tr' => ['nullable', 'string'],
            'description.ur' => ['nullable', 'string'],
            'color' => ['nullable', 'string', 'max:7'],
        ];
    }
}
