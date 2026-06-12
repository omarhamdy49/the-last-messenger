<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exceptions;

/**
 * Domain Validation Exception
 *
 * Thrown when domain validation fails.
 * Contains field-level error details.
 */
class ValidationException extends DomainException
{
    /**
     * @var array<string, array<string>>
     */
    protected array $errors = [];

    /**
     * Create with validation errors
     *
     * @param  array<string, array<string>>  $errors
     */
    public static function withErrors(array $errors): static
    {
        $exception = new static(
            message: 'فشل التحقق من صحة البيانات | Domain validation failed',
            context: ['errors' => $errors],
            code: 422
        );

        $exception->errors = $errors;

        return $exception;
    }

    /**
     * Create for single field error
     */
    public static function forField(string $field, string $message): static
    {
        return static::withErrors([$field => [$message]]);
    }

    /**
     * Create for multiple field errors
     *
     * @param  array<string, string>  $fieldMessages
     */
    public static function forFields(array $fieldMessages): static
    {
        $errors = [];
        foreach ($fieldMessages as $field => $message) {
            $errors[$field] = [$message];
        }

        return static::withErrors($errors);
    }

    /**
     * Get all validation errors
     *
     * @return array<string, array<string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get errors for specific field
     *
     * @return array<string>
     */
    public function getFieldErrors(string $field): array
    {
        return $this->errors[$field] ?? [];
    }

    /**
     * Check if field has errors
     */
    public function hasFieldError(string $field): bool
    {
        return isset($this->errors[$field]) && count($this->errors[$field]) > 0;
    }

    /**
     * Get first error message
     */
    public function firstError(): ?string
    {
        foreach ($this->errors as $fieldErrors) {
            if (! empty($fieldErrors)) {
                return $fieldErrors[0];
            }
        }

        return null;
    }
}
