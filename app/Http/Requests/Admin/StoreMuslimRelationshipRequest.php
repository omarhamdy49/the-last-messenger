<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Domain\People\Enums\RelationshipType;
use App\Domain\People\Models\MuslimRelationship;
use App\Domain\People\Services\MuslimRelationshipService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * A non-descent edge: spouse, sibling, uncle, aunt, cousin.
 *
 * Parent and child are deliberately absent — descent lives on
 * `muslims.father_id` / `mother_id` and is edited through UpdateGenealogyRequest.
 *
 * Both guards here exist to turn database-level failures into field errors:
 * `chk_muslim_relationships_not_self` and `uq_muslim_relationships` would
 * otherwise surface as a 500 on ordinary editor mistakes.
 */
class StoreMuslimRelationshipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'related_muslim_id' => ['required', 'integer', 'exists:muslims,id'],
            'relationship_type' => ['required', Rule::enum(RelationshipType::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'hide_from_tree' => ['boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            if ($v->errors()->isNotEmpty()) {
                return;
            }

            $muslimId = (int) $this->route('muslim')->id;
            $relatedId = (int) $this->input('related_muslim_id');

            if ($muslimId === $relatedId) {
                $v->errors()->add('related_muslim_id', __('sahaba.errors.self_relationship'));

                return;
            }

            $type = RelationshipType::from((string) $this->input('relationship_type'));

            // On an edit, the row being edited must not count as its own clash.
            $existing = $this->route('relationship');
            $ignoreId = $existing instanceof MuslimRelationship ? $existing->id : null;

            if (app(MuslimRelationshipService::class)->existsBetween($muslimId, $relatedId, $type, $ignoreId)) {
                $v->errors()->add('relationship_type', __('sahaba.errors.relationship_exists'));
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'sort_order' => $this->input('sort_order') === '' ? 0 : $this->input('sort_order'),
            'hide_from_tree' => $this->boolean('hide_from_tree'),
        ]);
    }
}
