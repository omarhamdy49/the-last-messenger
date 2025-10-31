<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Base SEO Exception
 *
 * Parent exception for all SEO-related errors in The Last Messenger platform.
 *
 * @package App\Exceptions
 */
class SeoException extends Exception
{
    /**
     * Create a new SEO exception instance
     *
     * @param string $message Exception message
     * @param int $code Exception code (default: 0)
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message = 'An SEO error occurred',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create exception for missing content
     *
     * @param string $field Field name
     * @return static
     */
    public static function missingContent(string $field): static
    {
        return new static("Required SEO content is missing: {$field}");
    }

    /**
     * Create exception for invalid locale
     *
     * @param string $locale Invalid locale
     * @param array<string> $supported Supported locales
     * @return static
     */
    public static function invalidLocale(string $locale, array $supported): static
    {
        $supportedList = implode(', ', $supported);

        return new static("Invalid locale '{$locale}'. Supported locales: {$supportedList}");
    }

    /**
     * Create exception for generation failure
     *
     * @param string $type Type of SEO data (e.g., 'JSON-LD', 'OpenGraph')
     * @param string $reason Failure reason
     * @return static
     */
    public static function generationFailed(string $type, string $reason): static
    {
        return new static("Failed to generate {$type}: {$reason}");
    }
}
