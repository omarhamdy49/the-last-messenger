<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Domain\Hadith\Enums\NarratorGrade;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Hadith Narrator Request
 * Validates data for updating an existing hadith narrator
 */
class UpdateHadithNarratorRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Multi-language name (required)
            'name' => ['required', 'array'],
            'name.ar' => ['required', 'string', 'max:255'],
            'name.en' => ['required', 'string', 'max:255'],
            'name.fr' => ['nullable', 'string', 'max:255'],
            'name.tr' => ['nullable', 'string', 'max:255'],
            'name.ur' => ['nullable', 'string', 'max:255'],

            // Multi-language biography (optional)
            'biography_json' => ['nullable', 'array'],
            'biography_json.ar' => ['nullable', 'string', 'max:5000'],
            'biography_json.en' => ['nullable', 'string', 'max:5000'],
            'biography_json.fr' => ['nullable', 'string', 'max:5000'],
            'biography_json.tr' => ['nullable', 'string', 'max:5000'],
            'biography_json.ur' => ['nullable', 'string', 'max:5000'],

            // Required fields
            'reliability_grade' => ['required', Rule::in(NarratorGrade::values())],
            'era' => [
                'required',
                Rule::in([
                    'Sahabah',
                    'Tabiun',
                    'Taba_al_Tabiun',
                    'Taba_Taba_al_Tabiun',
                    'Later_Scholars',
                ]),
            ],

            // Optional fields
            'country' => ['nullable', 'string', 'max:100'],
            'birth_hijri' => ['nullable', 'string', 'max:20'],
            'death_hijri' => ['nullable', 'string', 'max:20'],
            'teachers' => ['nullable', 'array'],
            'teachers.*' => ['integer', 'exists:hadith_narrators,id'],
            'students' => ['nullable', 'array'],
            'students.*' => ['integer', 'exists:hadith_narrators,id'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'الاسم مطلوب',
            'name.ar.required' => 'الاسم بالعربية مطلوب',
            'name.en.required' => 'الاسم بالإنجليزية مطلوب',
            'reliability_grade.required' => 'درجة الموثوقية مطلوبة',
            'reliability_grade.in' => 'درجة الموثوقية غير صالحة',
            'era.required' => 'العصر مطلوب',
            'era.in' => 'العصر غير صالح',
            'teachers.*.exists' => 'أحد المعلمين المحددين غير موجود',
            'students.*.exists' => 'أحد الطلاب المحددين غير موجود',
        ];
    }

    /**
     * Get custom attribute names for validation errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name.ar' => 'الاسم بالعربية',
            'name.en' => 'الاسم بالإنجليزية',
            'name.fr' => 'الاسم بالفرنسية',
            'name.tr' => 'الاسم بالتركية',
            'name.ur' => 'الاسم بالأردية',
            'biography_json.ar' => 'السيرة بالعربية',
            'biography_json.en' => 'السيرة بالإنجليزية',
            'reliability_grade' => 'درجة الموثوقية',
            'era' => 'العصر',
            'country' => 'البلد',
            'birth_hijri' => 'تاريخ الميلاد الهجري',
            'death_hijri' => 'تاريخ الوفاة الهجري',
            'teachers' => 'المعلمين',
            'students' => 'الطلاب',
            'status' => 'الحالة',
        ];
    }
}
