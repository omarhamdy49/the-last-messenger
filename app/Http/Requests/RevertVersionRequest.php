<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Revert Version Request
 *
 * Validates version revert data
 */
class RevertVersionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware/policy
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'version_id' => ['required', 'integer', 'exists:content_versions,id'],
            'reason' => ['nullable', 'string', 'max:500'],
            'notify_users' => ['nullable', 'boolean'],
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
            'version_id.required' => 'Version ID is required',
            'version_id.exists' => 'Version not found',
            'reason.max' => 'Reason cannot exceed 500 characters',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'version_id' => 'version',
            'notify_users' => 'notification setting',
        ];
    }
}
