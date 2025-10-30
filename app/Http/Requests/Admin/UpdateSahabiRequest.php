<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Sahabi Request (Admin)
 */
class UpdateSahabiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'super_admin']);
    }

    public function rules(): array
    {
        $sahabiId = $this->route('id');

        return [
            'slug' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('sahaba', 'slug')->ignore($sahabiId)],
            'name' => ['sometimes', 'required', 'array'],
            'name.ar' => ['required_with:name', 'string', 'max:255'],
            'name.en' => ['nullable', 'string', 'max:255'],
            'name.fr' => ['nullable', 'string', 'max:255'],
            'name.tr' => ['nullable', 'string', 'max:255'],
            'name.ur' => ['nullable', 'string', 'max:255'],
            'biography' => ['nullable', 'array'],
            'biography.ar' => ['nullable', 'string'],
            'biography.en' => ['nullable', 'string'],
            'biography.fr' => ['nullable', 'string'],
            'biography.tr' => ['nullable', 'string'],
            'biography.ur' => ['nullable', 'string'],
            'gender' => ['sometimes', 'required', 'string', 'in:male,female'],
            'birth_date' => ['nullable', 'string', 'max:100'],
            'death_date' => ['nullable', 'string', 'max:100'],
            'relationship_to_prophet' => ['nullable', 'string', 'max:500'],
            'notable_events' => ['nullable', 'array'],
        ];
    }
}
