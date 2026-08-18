<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Translatable Array Validation Rule
 *
 * Validates that a translatable field contains valid language codes
 * and optionally ensures all required languages are present
 */
class TranslatableArray implements ValidationRule
{
    protected array $allowedLanguages;

    protected bool $requireAllLanguages;

    protected string $fieldName;

    /**
     * Constructor
     *
     * @param  array  $allowedLanguages  Allowed language codes
     * @param  bool  $requireAllLanguages  Whether all languages are required
     * @param  string  $fieldName  Field name for error messages
     */
    public function __construct(
        array $allowedLanguages = ['ar', 'en', 'fr', 'tr', 'ur'],
        bool $requireAllLanguages = false,
        string $fieldName = 'field'
    ) {
        $this->allowedLanguages = $allowedLanguages;
        $this->requireAllLanguages = $requireAllLanguages;
        $this->fieldName = $fieldName;
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Check if value is an array
        if (! is_array($value)) {
            $fail("The {$this->fieldName} must be an array.");

            return;
        }

        // Check for invalid language codes
        $invalidCodes = array_diff(array_keys($value), $this->allowedLanguages);
        if (! empty($invalidCodes)) {
            $fail("The {$this->fieldName} contains invalid language codes: ".implode(', ', $invalidCodes));

            return;
        }

        // Check if all languages are required and present
        if ($this->requireAllLanguages) {
            $missingLanguages = array_diff($this->allowedLanguages, array_keys($value));
            if (! empty($missingLanguages)) {
                $fail("The {$this->fieldName} must include all languages: ".implode(', ', $missingLanguages));

                return;
            }
        }
    }

    /**
     * Get the validation error message
     */
    public function message(): string
    {
        return "The {$this->fieldName} must be a valid translation array.";
    }
}
