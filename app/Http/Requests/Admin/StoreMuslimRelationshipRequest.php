<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Domain\People\Enums\RelationshipType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreMuslimRelationshipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'muslim_id' => ['required', 'integer', 'exists:muslims,id'],
            'related_muslim_id' => ['required', 'integer', 'exists:muslims,id'],
            'relationship_type' => ['required', Rule::enum(RelationshipType::class)],
            'notes' => ['nullable', 'string'],
            'sort_order' => ['integer', 'min:0'],
            'hide_from_tree' => ['boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            // Mirrors chk_muslim_relationships_not_self so the editor sees a
            // 422 rather than a database error.
            if ((int) $this->input('muslim_id') === (int) $this->input('related_muslim_id')) {
                $v->errors()->add('related_muslim_id', __('sahaba.errors.self_relationship'));
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['hide_from_tree' => $this->boolean('hide_from_tree')]);
    }
}
