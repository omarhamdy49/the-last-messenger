<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Genealogy Request (Admin)
 */
class UpdateGenealogyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'super_admin']);
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'array'],
            'name.ar' => ['required_with:name', 'string', 'max:255'],
            'name.en' => ['nullable', 'string', 'max:255'],
            'name.fr' => ['nullable', 'string', 'max:255'],
            'name.tr' => ['nullable', 'string', 'max:255'],
            'name.ur' => ['nullable', 'string', 'max:255'],

            'title' => ['nullable', 'array'],
            'title.ar' => ['nullable', 'string', 'max:255'],
            'title.en' => ['nullable', 'string', 'max:255'],
            'title.fr' => ['nullable', 'string', 'max:255'],
            'title.tr' => ['nullable', 'string', 'max:255'],
            'title.ur' => ['nullable', 'string', 'max:255'],

            'person_type' => ['sometimes', 'required', 'string', 'in:prophet,companion_male,companion_female,family,tribe_leader,historical_figure'],
            'gender' => ['sometimes', 'required', 'string', 'in:male,female'],

            'father_id' => ['nullable', 'integer', 'exists:people,id'],
            'mother_id' => ['nullable', 'integer', 'exists:people,id'],
            'sahaba_id' => ['nullable', 'integer', 'exists:sahabis,id'],

            'birth_date_hijri' => ['nullable', 'date'],
            'birth_date_gregorian' => ['nullable', 'date'],
            'birth_place' => ['nullable', 'array'],
            'birth_place.ar' => ['nullable', 'string'],
            'birth_place.en' => ['nullable', 'string'],
            'birth_place.fr' => ['nullable', 'string'],
            'birth_place.tr' => ['nullable', 'string'],
            'birth_place.ur' => ['nullable', 'string'],

            'death_date_hijri' => ['nullable', 'date'],
            'death_date_gregorian' => ['nullable', 'date'],
            'death_place' => ['nullable', 'array'],
            'death_place.ar' => ['nullable', 'string'],
            'death_place.en' => ['nullable', 'string'],
            'death_place.fr' => ['nullable', 'string'],
            'death_place.tr' => ['nullable', 'string'],
            'death_place.ur' => ['nullable', 'string'],

            'burial_place' => ['nullable', 'array'],
            'burial_place.ar' => ['nullable', 'string'],
            'burial_place.en' => ['nullable', 'string'],
            'burial_place.fr' => ['nullable', 'string'],
            'burial_place.tr' => ['nullable', 'string'],
            'burial_place.ur' => ['nullable', 'string'],

            'tribe' => ['nullable', 'string', 'max:255'],

            'occupation' => ['nullable', 'array'],
            'occupation.ar' => ['nullable', 'string'],
            'occupation.en' => ['nullable', 'string'],
            'occupation.fr' => ['nullable', 'string'],
            'occupation.tr' => ['nullable', 'string'],
            'occupation.ur' => ['nullable', 'string'],

            'biography' => ['nullable', 'array'],
            'biography.ar' => ['nullable', 'string'],
            'biography.en' => ['nullable', 'string'],
            'biography.fr' => ['nullable', 'string'],
            'biography.tr' => ['nullable', 'string'],
            'biography.ur' => ['nullable', 'string'],

            'notes' => ['nullable', 'array'],
            'notes.ar' => ['nullable', 'string'],
            'notes.en' => ['nullable', 'string'],
            'notes.fr' => ['nullable', 'string'],
            'notes.tr' => ['nullable', 'string'],
            'notes.ur' => ['nullable', 'string'],

            'is_verified' => ['nullable', 'boolean'],
        ];
    }
}
