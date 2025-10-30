<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Shared\Models\Language;
use App\Rules\TranslatableArray;
use App\Rules\TranslatableRequired;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Base Translatable Request
 *
 * Provides common validation rules for translatable fields
 */
abstract class BaseTranslatableRequest extends FormRequest
{
    /**
     * Get translatable field names
     *
     * @return array
     */
    abstract protected function translatableFields(): array;

    /**
     * Get required translatable field names
     *
     * @return array
     */
    protected function requiredTranslatableFields(): array
    {
        return [];
    }

    /**
     * Generate validation rules for translatable fields
     *
     * @param string $field Field name
     * @param bool $required Whether field is required
     * @param array $extraRules Extra validation rules for each language
     * @return array
     */
    protected function translatableRules(string $field, bool $required = true, array $extraRules = []): array
    {
        $activeCodes = Language::getActiveCodes();
        $defaultLang = Language::getDefaultCode();

        $rules = [];

        // Main array validation
        if ($required) {
            $rules[$field] = ['required', 'array', new TranslatableRequired($defaultLang, $field)];
        } else {
            $rules[$field] = ['nullable', 'array', new TranslatableArray($activeCodes, false, $field)];
        }

        // Individual language validation
        foreach ($activeCodes as $code) {
            $languageRules = $code === $defaultLang && $required ? ['required'] : ['nullable'];

            if (! empty($extraRules)) {
                $languageRules = array_merge($languageRules, $extraRules);
            } else {
                $languageRules[] = 'string';
            }

            $rules["{$field}.{$code}"] = $languageRules;
        }

        return $rules;
    }

    /**
     * Generate validation messages for translatable fields
     *
     * @param string $field Field name
     * @param string $displayName Display name for error messages
     * @return array
     */
    protected function translatableMessages(string $field, string $displayName): array
    {
        $defaultLang = Language::getDefaultCode();

        return [
            "{$field}.required" => "The {$displayName} field is required.",
            "{$field}.array" => "The {$displayName} must be an array of translations.",
            "{$field}.{$defaultLang}.required" => "The {$displayName} in {$defaultLang} is required.",
        ];
    }

    /**
     * Merge translatable rules with custom rules
     *
     * @param array $customRules
     * @return array
     */
    protected function mergeTranslatableRules(array $customRules = []): array
    {
        $rules = [];

        // Add translatable field rules
        $translatableFields = $this->translatableFields();
        $requiredFields = $this->requiredTranslatableFields();

        foreach ($translatableFields as $field => $extraRules) {
            if (is_numeric($field)) {
                $field = $extraRules;
                $extraRules = [];
            }

            $isRequired = in_array($field, $requiredFields);
            $rules = array_merge($rules, $this->translatableRules($field, $isRequired, $extraRules));
        }

        // Merge with custom rules
        return array_merge($rules, $customRules);
    }

    /**
     * Prepare data for validation
     */
    protected function prepareForValidation(): void
    {
        // Sanitize translatable fields
        $translatableFields = $this->translatableFields();

        foreach ($translatableFields as $field => $extraRules) {
            if (is_numeric($field)) {
                $field = $extraRules;
            }

            if ($this->has($field) && is_array($this->input($field))) {
                $sanitized = [];

                foreach ($this->input($field) as $code => $value) {
                    if (is_string($value)) {
                        $sanitized[$code] = trim($value);
                    } else {
                        $sanitized[$code] = $value;
                    }
                }

                $this->merge([
                    $field => $sanitized,
                ]);
            }
        }
    }
}
