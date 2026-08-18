<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Shared\Enums\ScheduleAction;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Schedule Content Request
 * Validates content scheduling data
 */
class ScheduleContentRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Content identification
            'schedulable_type' => ['required', 'string', Rule::in([
                'App\\Domain\\Blog\\Models\\Article',
                'App\\Domain\\Hadith\\Models\\Hadith',
                'App\\Domain\\Media\\Models\\MediaProgram',
                'App\\Domain\\Media\\Models\\MediaEpisode',
                'App\\Domain\\Seerah\\Models\\SeerahEvent',
                'App\\Domain\\Sahaba\\Models\\Sahabi',
                'App\\Domain\\Quran\\Models\\QuranVerse',
                'App\\Domain\\Quran\\Models\\QuranSurah',
            ])],
            'schedulable_id' => ['required', 'integer', 'min:1'],

            // Schedule details
            'scheduled_at' => ['required', 'date', 'after:now'],
            'action' => ['required', 'string', Rule::enum(ScheduleAction::class)],
            'timezone' => ['nullable', 'string', 'timezone'],
            'notes' => ['nullable', 'string', 'max:1000'],

            // Recurrence settings (optional)
            'recurrence.type' => ['nullable', 'string', Rule::in(['daily', 'weekly', 'monthly', 'yearly'])],
            'recurrence.interval' => ['nullable', 'integer', 'min:1', 'max:365'],
            'recurrence.ends_at' => ['nullable', 'date', 'after:scheduled_at'],
            'recurrence.days' => ['nullable', 'array'],
            'recurrence.days.*' => ['integer', 'min:0', 'max:6'],

            // Update data (required only for 'update' action)
            'scheduled_data' => ['required_if:action,update', 'nullable', 'array'],
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
            'schedulable_type.required' => 'نوع المحتوى مطلوب',
            'schedulable_type.in' => 'نوع المحتوى غير صالح',
            'schedulable_id.required' => 'معرف المحتوى مطلوب',
            'schedulable_id.integer' => 'معرف المحتوى يجب أن يكون رقماً',
            'scheduled_at.required' => 'تاريخ الجدولة مطلوب',
            'scheduled_at.date' => 'تاريخ الجدولة غير صالح',
            'scheduled_at.after' => 'تاريخ الجدولة يجب أن يكون في المستقبل',
            'action.required' => 'الإجراء مطلوب',
            'action.enum' => 'الإجراء غير صالح',
            'timezone.timezone' => 'المنطقة الزمنية غير صالحة',
            'notes.max' => 'الملاحظات يجب ألا تتجاوز 1000 حرف',
            'recurrence.type.in' => 'نوع التكرار غير صالح',
            'recurrence.interval.min' => 'فترة التكرار يجب أن تكون على الأقل 1',
            'recurrence.interval.max' => 'فترة التكرار يجب ألا تتجاوز 365',
            'recurrence.ends_at.after' => 'تاريخ انتهاء التكرار يجب أن يكون بعد تاريخ الجدولة',
            'scheduled_data.required_if' => 'بيانات التحديث مطلوبة عند اختيار إجراء التحديث',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Allow short class names and convert to full class name
        if ($this->has('schedulable_type')) {
            $shortToFull = [
                'Article' => 'App\\Domain\\Blog\\Models\\Article',
                'Hadith' => 'App\\Domain\\Hadith\\Models\\Hadith',
                'MediaProgram' => 'App\\Domain\\Media\\Models\\MediaProgram',
                'MediaEpisode' => 'App\\Domain\\Media\\Models\\MediaEpisode',
                'SeerahEvent' => 'App\\Domain\\Seerah\\Models\\SeerahEvent',
                'Sahabi' => 'App\\Domain\\Sahaba\\Models\\Sahabi',
                'QuranVerse' => 'App\\Domain\\Quran\\Models\\QuranVerse',
                'QuranSurah' => 'App\\Domain\\Quran\\Models\\QuranSurah',
            ];

            $type = $this->input('schedulable_type');
            if (isset($shortToFull[$type])) {
                $this->merge(['schedulable_type' => $shortToFull[$type]]);
            }
        }

        // Set default timezone if not provided
        if (! $this->has('timezone')) {
            $this->merge(['timezone' => 'UTC']);
        }
    }

    /**
     * Get custom validation attribute names.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'schedulable_type' => 'نوع المحتوى',
            'schedulable_id' => 'معرف المحتوى',
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
