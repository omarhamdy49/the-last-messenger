<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Translatable Required Validation Rule
 *
 * Validates that at least the default language (Arabic) is provided
 * in a translatable field array
 */
class TranslatableRequired implements ValidationRule
{
    protected string $defaultLanguage;

    protected string $fieldName;

    /**
     * Constructor
     *
     * @param string $defaultLanguage Default language code (default: 'ar')
     * @param string $fieldName Field name for error messages
     */
    public function __construct(string $defaultLanguage = 'ar', string $fieldName = 'field')
    {
        $this->defaultLanguage = $defaultLanguage;
        $this->fieldName = $fieldName;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Check if value is an array
        if (! is_array($value)) {
            $fail("The {$this->fieldName} must be an array of translations.");
            return;
        }

        // Check if default language exists and is not empty
        if (! isset($value[$this->defaultLanguage]) || empty(trim((string) $value[$this->defaultLanguage]))) {
            $fail("The {$this->fieldName} must include {$this->defaultLanguage} translation.");
            return;
        }
    }

    /**
     * Get the validation error message
     */
    public function message(): string
    {
        return "The {$this->fieldName} must include {$this->defaultLanguage} translation.";
    }
}
