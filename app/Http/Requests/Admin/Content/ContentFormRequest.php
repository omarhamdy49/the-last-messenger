<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Content;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Base request for the admin Content module.
 *
 * Centralizes authorization (content management permission) and the
 * locale set used by every translatable field group.
 */
abstract class ContentFormRequest extends FormRequest
{
    /**
     * Locales every translatable field accepts (Arabic is mandatory
     * for primary fields — it is the site fallback language).
     *
     * @var array<int, string>
     */
    protected const LOCALES = ['ar', 'en', 'fr', 'tr', 'ur'];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('manage_content') ?? false;
    }

    /**
     * Build per-locale rules for one translatable field.
     *
     * @param  array<int, string>  $valueRules  Rules applied to each locale value
     * @return array<string, array<int, string>>
     */
    protected function localizedRules(string $field, bool $requireArabic, array $valueRules): array
    {
        $rules = [
            $field => $requireArabic ? ['required', 'array'] : ['nullable', 'array'],
        ];

        foreach (self::LOCALES as $locale) {
            $required = $requireArabic && $locale === 'ar' ? 'required' : 'nullable';
            $rules["{$field}.{$locale}"] = [$required, ...$valueRules];
        }

        return $rules;
    }
}
