<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Hadith;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNarratorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'super_admin']);
    }

    public function rules(): array
    {
        return [
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'full_name_ar' => ['nullable', 'string', 'max:500'],
            'full_name_en' => ['nullable', 'string', 'max:500'],
            'kunya' => ['nullable', 'string', 'max:255'],
            'birth_year_hijri' => ['nullable', 'integer'],
            'death_year_hijri' => ['nullable', 'integer'],
            'reliability_grade' => ['nullable', 'string', 'max:100'],
            'biography' => ['nullable', 'string'],
            'school' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
        ];
    }
}
