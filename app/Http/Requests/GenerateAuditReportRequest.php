<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Generate Audit Report Request
 *
 * Validates audit report generation parameters
 */
class GenerateAuditReportRequest extends FormRequest
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
            'start_date' => ['required', 'date', 'before_or_equal:end_date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'format' => ['nullable', 'string', 'in:array,json,csv,pdf'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'action' => ['nullable', 'string', 'max:100'],
            'subject_type' => ['nullable', 'string', 'max:255'],
            'ip_address' => ['nullable', 'ip'],
            'include_details' => ['nullable', 'boolean'],
            'group_by' => ['nullable', 'string', 'in:user,action,date,subject_type'],
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
            'start_date.required' => 'Start date is required',
            'end_date.required' => 'End date is required',
            'start_date.before_or_equal' => 'Start date must be before or equal to end date',
            'end_date.after_or_equal' => 'End date must be after or equal to start date',
            'format.in' => 'Format must be one of: array, json, csv, pdf',
            'user_id.exists' => 'User not found',
            'ip_address.ip' => 'Invalid IP address format',
            'group_by.in' => 'Group by must be one of: user, action, date, subject_type',
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
            'start_date' => 'start date',
            'end_date' => 'end date',
            'user_id' => 'user',
            'ip_address' => 'IP address',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default format if not provided
        if (! $this->has('format')) {
            $this->merge(['format' => 'array']);
        }

        // Set default date range if not provided (last 30 days)
        if (! $this->has('start_date')) {
            $this->merge(['start_date' => now()->subDays(30)->toDateString()]);
        }

        if (! $this->has('end_date')) {
            $this->merge(['end_date' => now()->toDateString()]);
        }
    }
}
