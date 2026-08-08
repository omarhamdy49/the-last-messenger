<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * The descent side of a person's genealogy: parents and tree placement.
 *
 * Only these four fields — the rest of the profile is the Islamic Figures form.
 * The cycle check is NOT here: it needs an ancestor walk, so it lives in
 * GenealogyTreeService::assignParent and surfaces as a field error.
 */
class UpdateGenealogyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'father_id' => ['nullable', 'integer', 'exists:muslims,id'],
            'mother_id' => ['nullable', 'integer', 'exists:muslims,id'],
            'tree_display_order' => ['nullable', 'integer', 'min:0'],
            'hide_from_tree' => ['boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $id = $this->route('muslim')?->id;

            if ($id === null) {
                return;
            }

            // The shallow case, caught here so the user gets a 422 rather than
            // relying on the service's exception path.
            foreach (['father_id', 'mother_id'] as $column) {
                if ($this->filled($column) && (int) $this->input($column) === (int) $id) {
                    $v->errors()->add($column, __('sahaba.errors.self_parent'));
                }
            }
        });
    }

    protected function prepareForValidation(): void
    {
        // An empty picker means "no parent", not "leave unchanged" — the
        // controller has to receive an explicit null to clear the column.
        $this->merge([
            'father_id' => $this->input('father_id') === '' ? null : $this->input('father_id'),
            'mother_id' => $this->input('mother_id') === '' ? null : $this->input('mother_id'),
            'tree_display_order' => $this->input('tree_display_order') === '' ? null : $this->input('tree_display_order'),
            'hide_from_tree' => $this->boolean('hide_from_tree'),
        ]);
    }
}
