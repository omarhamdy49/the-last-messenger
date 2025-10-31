<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * SEO Generation Exception
 *
 * Thrown when SEO metadata generation fails.
 *
 * @package App\Exceptions
 */
class SeoGenerationException extends SeoException
{
    /**
     * Create exception for JSON-LD generation failure
     *
     * @param string $reason Failure reason
     * @return static
     */
    public static function jsonLdFailed(string $reason): static
    {
        return new static("Failed to generate JSON-LD structured data: {$reason}");
    }

    /**
     * Create exception for OpenGraph generation failure
     *
     * @param string $reason Failure reason
     * @return static
     */
    public static function openGraphFailed(string $reason): static
    {
        return new static("Failed to generate OpenGraph tags: {$reason}");
    }

    /**
     * Create exception for Twitter Card generation failure
     *
     * @param string $reason Failure reason
     * @return static
     */
    public static function twitterCardFailed(string $reason): static
    {
        return new static("Failed to generate Twitter Card tags: {$reason}");
    }

    /**
     * Create exception for sitemap generation failure
     *
     * @param string $reason Failure reason
     * @return static
     */
    public static function sitemapFailed(string $reason): static
    {
        return new static("Failed to generate sitemap entry: {$reason}");
    }

    /**
     * Create exception for missing article data
     *
     * @param string $field Missing field
     * @return static
     */
    public static function missingArticleData(string $field): static
    {
        return new static("Required article data is missing: {$field}");
    }
}
