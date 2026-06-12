<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Content;

use App\Http\Requests\Admin\Content\Concerns\SanitizesRichText;
use Illuminate\Validation\Rule;

/**
 * Validates static page creation. The rich `body` values are sanitized
 * (allowlist purifier) before validation, so persisted HTML is safe.
 */
class StoreStaticPageRequest extends ContentFormRequest
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
            'slug' => [
                'required',
                'string',
                'max:120',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('static_pages', 'slug')->ignore($this->route('static_page')),
            ],
            'is_active' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
            ...$this->localizedRules('title', true, ['string', 'max:255']),
            ...$this->localizedRules('body', false, ['string', 'max:100000']),
            ...$this->localizedRules('seo_title', false, ['string', 'max:255']),
            ...$this->localizedRules('seo_description', false, ['string', 'max:500']),
        ];
    }

    /**
     * Sanitize rich HTML before validation so validated() is already clean.
     */
    protected function prepareForValidation(): void
    {
        $this->sanitizeRichTextField('body');
    }
}
