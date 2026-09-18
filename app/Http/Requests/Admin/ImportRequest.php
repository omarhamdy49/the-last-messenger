<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Import Request
 *
 * Validates file upload and import configuration
 */
class ImportRequest extends FormRequest
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
            'module' => [
                'required',
                'string',
                'in:articles,media_programs,seerah_events,sahaba',
            ],
            'file' => [
                'required',
                'file',
                'mimes:csv,xlsx,xls',
                'max:10240', // 10MB max
            ],
            'has_header' => 'boolean',
            'skip_duplicates' => 'boolean',
            'update_existing' => 'boolean',
            'dry_run' => 'boolean',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'module' => 'module name',
            'file' => 'import file',
            'has_header' => 'has header row',
            'skip_duplicates' => 'skip duplicate records',
            'update_existing' => 'update existing records',
            'dry_run' => 'dry run mode',
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
            'module.in' => 'The selected module is not supported for import.',
            'file.mimes' => 'The file must be a CSV or Excel file (csv, xlsx, xls).',
            'file.max' => 'The file size must not exceed 10MB.',
        ];
    }
}
