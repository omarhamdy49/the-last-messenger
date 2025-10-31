<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Import Execute Request
 *
 * Validates import execution parameters
 */
class ImportExecuteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['super_admin', 'admin', 'editor']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'import_id' => 'required|integer|exists:imports,id',
            'confirm' => 'required|boolean|accepted',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'import_id.exists' => 'The specified import record does not exist.',
            'confirm.accepted' => 'You must confirm the import execution.',
        ];
    }
}
