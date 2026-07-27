<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update a relationship's tree display order and visibility (Admin).
 */
class UpdateGenealogyRelationshipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'super_admin']);
    }

    public function rules(): array
    {
        return [
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'hide_from_tree' => ['nullable', 'boolean'],
        ];
    }
}
