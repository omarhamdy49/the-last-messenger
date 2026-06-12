<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Content;

use App\Http\Requests\Admin\Content\Concerns\SanitizesRichText;

/**
 * Validates FAQ creation. The rich `answer` values are sanitized
 * (allowlist purifier) before validation, so persisted HTML is safe.
 */
class StoreFaqRequest extends ContentFormRequest
{
    use SanitizesRichText;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:10000'],
            ...$this->localizedRules('question', true, ['string', 'max:500']),
            ...$this->localizedRules('answer', true, ['string', 'max:20000']),
        ];
    }

    /**
     * Sanitize rich HTML before validation so validated() is already clean.
     */
    protected function prepareForValidation(): void
    {
        $this->sanitizeRichTextField('answer');
    }
}
