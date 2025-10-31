<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Shared\Enums\ScheduleAction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Schedule Request
 * Validates schedule update data
 */
class UpdateScheduleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Schedule details (all optional for update)
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'action' => ['nullable', 'string', Rule::enum(ScheduleAction::class)],
            'timezone' => ['nullable', 'string', 'timezone'],
            'notes' => ['nullable', 'string', 'max:1000'],

            // Recurrence settings (optional)
            'recurrence.type' => ['nullable', 'string', Rule::in(['daily', 'weekly', 'monthly', 'yearly'])],
            'recurrence.interval' => ['nullable', 'integer', 'min:1', 'max:365'],
            'recurrence.ends_at' => ['nullable', 'date', 'after:scheduled_at'],
            'recurrence.days' => ['nullable', 'array'],
            'recurrence.days.*' => ['integer', 'min:0', 'max:6'],

            // Update data (required only if action is 'update')
            'scheduled_data' => ['nullable', 'array'],
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
            'scheduled_at.date' => 'تاريخ الجدولة غير صالح',
            'scheduled_at.after' => 'تاريخ الجدولة يجب أن يكون في المستقبل',
            'action.enum' => 'الإجراء غير صالح',
            'timezone.timezone' => 'المنطقة الزمنية غير صالحة',
            'notes.max' => 'الملاحظات يجب ألا تتجاوز 1000 حرف',
            'recurrence.type.in' => 'نوع التكرار غير صالح',
            'recurrence.interval.min' => 'فترة التكرار يجب أن تكون على الأقل 1',
            'recurrence.interval.max' => 'فترة التكرار يجب ألا تتجاوز 365',
            'recurrence.ends_at.after' => 'تاريخ انتهاء التكرار يجب أن يكون بعد تاريخ الجدولة',
        ];
    }

    /**
     * Get custom validation attribute names.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'scheduled_at' => 'تاريخ الجدولة',
            'action' => 'الإجراء',
            'timezone' => 'المنطقة الزمنية',
            'notes' => 'الملاحظات',
            'recurrence.type' => 'نوع التكرار',
            'recurrence.interval' => 'فترة التكرار',
            'recurrence.ends_at' => 'تاريخ انتهاء التكرار',
            'scheduled_data' => 'بيانات التحديث',
        ];
    }
}
