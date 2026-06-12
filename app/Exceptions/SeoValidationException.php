<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * SEO Validation Exception
 *
 * Thrown when SEO content validation fails.
 */
class SeoValidationException extends SeoException
{
    /**
     * Create exception for validation failure
     *
     * @param  array<string>  $errors  Validation errors
     */
    public static function validationFailed(array $errors): static
    {
        $message = 'SEO validation failed: '.implode(', ', $errors);

        return new static($message);
    }

    /**
     * Create exception for title length validation
     *
     * @param  int  $length  Current length
     * @param  int  $min  Minimum length
     * @param  int  $max  Maximum length
     */
    public static function invalidTitleLength(int $length, int $min, int $max): static
    {
        return new static("SEO title length ({$length}) must be between {$min} and {$max} characters");
    }

    /**
     * Create exception for description length validation
     *
     * @param  int  $length  Current length
     * @param  int  $min  Minimum length
     * @param  int  $max  Maximum length
     */
    public static function invalidDescriptionLength(int $length, int $min, int $max): static
    {
        return new static("SEO description length ({$length}) must be between {$min} and {$max} characters");
    }

    /**
     * Create exception for keyword validation
     *
     * @param  int  $count  Current count
     * @param  int  $min  Minimum count
     * @param  int  $max  Maximum count
     */
    public static function invalidKeywordCount(int $count, int $min, int $max): static
    {
        return new static("Keyword count ({$count}) must be between {$min} and {$max}");
    }
}
