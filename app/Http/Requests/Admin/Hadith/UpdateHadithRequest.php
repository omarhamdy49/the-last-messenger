<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Hadith;

use App\Domain\Hadith\Enums\GradeScope;
use App\Domain\Hadith\Enums\HadithGrade;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateHadithRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'updated_at' => ['required', 'string'],
            'is_published' => ['required', 'boolean'],
            'texts' => ['required', 'array:ar,en,fr,tr,ur'],
            'texts.ar.body' => ['required', 'string'],
            'texts.*.body' => ['nullable', 'string'],
            'texts.*.narrated_by' => ['nullable', 'string'],
            'texts.*.sanad' => ['nullable', 'string'],
            'texts.*.matn' => ['nullable', 'string'],
            'texts.*.translator' => ['nullable', 'string', 'max:255'],
            'texts.*.source_url' => ['nullable', 'url', 'max:500'],
            'gradings' => ['sometimes', 'array'],
            'gradings.*.id' => ['nullable', 'integer', 'exists:hadith_gradings,id'],
            'gradings.*.scholar_id' => ['nullable', 'integer', 'exists:hadith_scholars,id'],
            'gradings.*.grade' => ['required', Rule::in(HadithGrade::values())],
            'gradings.*.scope' => ['required', Rule::in(GradeScope::values())],
            'gradings.*.part_label' => ['nullable', 'string', 'max:100'],
            'gradings.*.authority_rank' => ['required', 'integer', 'between:0,255'],
            'gradings.*.raw_grade' => ['required', 'string'],
            'gradings.*.locale' => ['nullable', Rule::in(['ar', 'en', 'fr', 'tr', 'ur'])],
            'gradings.*.notes' => ['nullable', 'string'],
            'gradings.*.source_reference' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_published' => $this->boolean('is_published')]);
    }
}
