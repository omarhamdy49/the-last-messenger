<?php

declare(strict_types=1);

namespace App\Http\Requests\Site;

use App\Domain\Hadith\DTOs\SearchCriteria;
use App\Domain\Hadith\Enums\HadithGrade;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shared by the web search page and the public API, so the two surfaces cannot
 * drift apart on what a valid query is.
 */
class HadithSearchRequest extends FormRequest
{
    /** Public read-only search; the throttle is the protection, not authorization. */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            // Two characters is the floor because innodb_ft_min_token_size is 2;
            // a one-character query cannot match anything and would look broken.
            'q' => ['required', 'string', 'min:2', 'max:200'],
            'collection' => ['nullable', 'string', 'max:50', 'exists:hadith_collections,code'],
            'grade' => ['nullable', Rule::in(HadithGrade::values())],
            'locale' => ['nullable', Rule::in(['ar', 'en', 'fr', 'tr', 'ur'])],
            'page' => ['nullable', 'integer', 'min:1'],
            // Not rejected when oversized — clamped in SearchCriteria, per the
            // API contract. Validation only keeps it a positive integer.
            'per_page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'q.required' => __('hadith.search_q_required'),
            'q.min' => __('hadith.search_q_min'),
            'q.max' => __('hadith.search_q_max'),
        ];
    }

    public function criteria(): SearchCriteria
    {
        return SearchCriteria::fromValidated($this->validated());
    }
}
