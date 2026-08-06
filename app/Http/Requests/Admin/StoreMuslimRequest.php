<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Domain\People\Enums\Gender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Rejects unexpected fields by only ever passing `validated()` downstream
 * (constitution VII).
 */
class StoreMuslimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $id = $this->route('muslim')?->id;

        return [
            'slug' => ['required', 'string', 'max:200', 'regex:/^[a-z0-9-]+$/',
                Rule::unique('muslims', 'slug')->ignore($id)],
            'thumb' => ['nullable', 'string', 'max:255'],

            // Both nullable individually; withValidator() enforces "at least
            // one", mirroring chk_muslims_has_name so the user gets a 422
            // instead of a database error.
            'name_ar' => ['nullable', 'string', 'max:200'],
            'name_en' => ['nullable', 'string', 'max:200'],

            'kunya' => ['nullable', 'array'],
            'kunya.ar' => ['nullable', 'string', 'max:200'],
            'kunya.en' => ['nullable', 'string', 'max:200'],
            'bio' => ['nullable', 'array'],
            'bio.ar' => ['nullable', 'string'],
            'bio.en' => ['nullable', 'string'],
            'content' => ['nullable', 'array'],
            'content.ar' => ['nullable', 'string'],
            'content.en' => ['nullable', 'string'],
            'birth_place' => ['nullable', 'array'],
            'birth_place.ar' => ['nullable', 'string', 'max:200'],
            'birth_place.en' => ['nullable', 'string', 'max:200'],

            'gender' => ['nullable', Rule::enum(Gender::class)],

            // Signed range: pre-Hijra years are negative.
            'birth_year_hijri' => ['nullable', 'integer', 'min:-200', 'max:1500'],
            'death_year_hijri' => ['nullable', 'integer', 'min:-200', 'max:1500'],
            'generation' => ['nullable', 'string', 'max:50'],

            'father_id' => ['nullable', 'integer', 'exists:muslims,id'],
            'mother_id' => ['nullable', 'integer', 'exists:muslims,id'],
            'tree_display_order' => ['nullable', 'integer', 'min:0'],
            'hide_from_tree' => ['boolean'],
            'is_published' => ['boolean'],

            'roles' => ['array'],
            'roles.*.role_id' => ['required', 'integer', 'exists:islamic_roles,id'],
            'roles.*.is_featured' => ['boolean'],
            'roles.*.sort_order' => ['integer', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            if (blank($this->input('name_ar')) && blank($this->input('name_en'))) {
                $v->errors()->add('name_ar', __('people.errors.name_required'));
            }

            $id = $this->route('muslim')?->id;
            if ($id !== null) {
                foreach (['father_id', 'mother_id'] as $column) {
                    if ((int) $this->input($column) === (int) $id) {
                        $v->errors()->add($column, __('people.errors.self_parent'));
                    }
                }
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'hide_from_tree' => $this->boolean('hide_from_tree'),
            'is_published' => $this->boolean('is_published'),
        ]);
    }
}
