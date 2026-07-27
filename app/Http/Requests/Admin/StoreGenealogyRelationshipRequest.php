<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Domain\Genealogy\Repositories\PersonRepositoryInterface;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Add a spouse relationship to a person (Admin).
 */
class StoreGenealogyRelationshipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'super_admin']);
    }

    public function rules(): array
    {
        return [
            'spouse_id' => ['required', 'integer', 'exists:sahaba,id'],
        ];
    }

    /**
     * A person can't be their own spouse, and the pair can't already have a
     * spouse relationship row in either direction — both require a DB
     * lookup, so can't be expressed as static rules.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $personId = (int) $this->route('person');
            $spouseId = (int) $this->input('spouse_id');

            if ($spouseId === $personId) {
                $validator->errors()->add('spouse_id', __('admin.genealogy_self_spouse_error'));

                return;
            }

            $repository = app(PersonRepositoryInterface::class);

            if ($repository->spouseRelationshipExists($personId, $spouseId)) {
                $validator->errors()->add('spouse_id', __('admin.genealogy_duplicate_spouse_error'));
            }
        });
    }
}
