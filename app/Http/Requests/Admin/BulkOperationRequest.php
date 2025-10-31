<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Domain\Shared\Enums\BulkOperationTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Bulk Operation Request
 * Validates bulk operation creation requests
 */
class BulkOperationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('manage_content');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'operation_type' => [
                'required',
                'string',
                Rule::in(BulkOperationTypeEnum::values()),
            ],
            'module' => [
                'required',
                'string',
                Rule::in(['articles', 'hadith', 'media_programs', 'seerah_events', 'sahaba', 'quran_verses']),
            ],
            'item_ids' => [
                'required',
                'array',
                'min:1',
            ],
            'item_ids.*' => [
                'required',
                'integer',
                'min:1',
            ],
            'parameters' => [
                'sometimes',
                'array',
            ],
            'dry_run' => [
                'sometimes',
                'boolean',
            ],
            'notify_on_completion' => [
                'sometimes',
                'boolean',
            ],

            // Conditional validation based on operation type
            'parameters.fields' => [
                'required_if:operation_type,update',
                'array',
            ],
            'parameters.category_id' => [
                'required_if:operation_type,change_category',
                'integer',
                'exists:categories,id',
            ],
            'parameters.tag_ids' => [
                Rule::requiredIf(function () {
                    return in_array($this->operation_type, ['add_tags', 'remove_tags'], true);
                }),
                'array',
            ],
            'parameters.tag_ids.*' => [
                'integer',
                'exists:tags,id',
            ],
            'parameters.status' => [
                'required_if:operation_type,change_status',
                'string',
                Rule::in(['draft', 'published', 'archived']),
            ],
            'parameters.author_id' => [
                'required_if:operation_type,change_author',
                'integer',
                'exists:users,id',
            ],
            'parameters.user_id' => [
                'required_if:operation_type,assign_user',
                'integer',
                'exists:users,id',
            ],
            'parameters.format' => [
                'required_if:operation_type,export',
                'string',
                Rule::in(['json', 'csv']),
            ],
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
            'operation_type.required' => 'Operation type is required',
            'operation_type.in' => 'Invalid operation type',
            'module.required' => 'Module is required',
            'module.in' => 'Invalid module',
            'item_ids.required' => 'At least one item must be selected',
            'item_ids.array' => 'Item IDs must be an array',
            'item_ids.min' => 'At least one item must be selected',
            'parameters.fields.required_if' => 'Fields are required for update operation',
            'parameters.category_id.required_if' => 'Category ID is required for change category operation',
            'parameters.tag_ids.required_if' => 'Tag IDs are required for tag operations',
            'parameters.status.required_if' => 'Status is required for change status operation',
            'parameters.author_id.required_if' => 'Author ID is required for change author operation',
            'parameters.user_id.required_if' => 'User ID is required for assign user operation',
            'parameters.format.required_if' => 'Format is required for export operation',
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
            'operation_type' => 'operation type',
            'module' => 'module',
            'item_ids' => 'selected items',
            'parameters.fields' => 'fields',
            'parameters.category_id' => 'category',
            'parameters.tag_ids' => 'tags',
            'parameters.status' => 'status',
            'parameters.author_id' => 'author',
            'parameters.user_id' => 'user',
            'parameters.format' => 'export format',
        ];
    }
}
