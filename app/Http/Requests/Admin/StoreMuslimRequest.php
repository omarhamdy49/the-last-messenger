<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Domain\People\Enums\Gender;
use App\Domain\People\Enums\Generation;
use App\Domain\People\Models\IslamicRole;
use App\Domain\People\Models\Muslim;
use App\Domain\People\ValueObjects\MuslimProfile;
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

        // Profile rules are generated from the field registry and scoped to the
        // roles being saved, so a new field needs no change here and a
        // role-restricted field is not an accepted key for the wrong person.
        return MuslimProfile::rulesFor($this->submittedRoleSlugs()) + [
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
            // A fixed set now, not free text: the طبقة is a closed taxonomy, and
            // free text produced values nothing could group or filter on.
            'generation' => ['nullable', Rule::enum(Generation::class)],

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

    /**
     * The role slugs this save will result in.
     *
     * Read from the submitted `roles[]` rather than the persisted record: if an
     * editor ticks "نبي" and fills the mission fields in the same submit, those
     * fields have to be accepted. Falls back to the stored roles when the form
     * did not post any (a partial update).
     *
     * @return list<string>
     */
    public function submittedRoleSlugs(): array
    {
        $roleIds = collect($this->input('roles', []))
            ->filter(fn ($row): bool => is_array($row) && isset($row['role_id']))
            ->pluck('role_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        if ($roleIds === []) {
            $muslim = $this->route('muslim');

            return $muslim instanceof Muslim
                ? $muslim->roles()->pluck('islamic_roles.slug')->all()
                : [];
        }

        return IslamicRole::query()->whereKey($roleIds)->pluck('slug')->all();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            if (blank($this->input('name_ar')) && blank($this->input('name_en'))) {
                $v->errors()->add('name_ar', __('sahaba.errors.name_required'));
            }

            $id = $this->route('muslim')?->id;
            if ($id !== null) {
                foreach (['father_id', 'mother_id'] as $column) {
                    if ((int) $this->input($column) === (int) $id) {
                        $v->errors()->add($column, __('sahaba.errors.self_parent'));
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
