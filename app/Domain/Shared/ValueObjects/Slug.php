<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObjects;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Stringable;

/**
 * Value Object for URL-friendly slugs
 *
 * Immutable slug with automatic generation from text.
 * Supports Arabic/Unicode text conversion.
 */
final readonly class Slug implements Stringable
{
    private string $value;

    /**
     * @param string $value The slug value
     * @throws InvalidArgumentException If slug is invalid
     */
    public function __construct(string $value)
    {
        $normalized = $this->normalize($value);
        $this->validate($normalized);
        $this->value = $normalized;
    }

    /**
     * Create slug from any text (auto-slugify)
     */
    public static function fromText(string $text): self
    {
        $slug = Str::slug($text, '-', null);

        // Handle Arabic/Unicode text
        if (empty($slug)) {
            $slug = self::slugifyUnicode($text);
        }

        return new self($slug);
    }

    /**
     * Create slug from existing slug value
     */
    public static function fromSlug(string $slug): self
    {
        return new self($slug);
    }

    /**
     * Generate unique slug with suffix
     */
    public function withSuffix(string|int $suffix): self
    {
        return new self($this->value . '-' . $suffix);
    }

    /**
     * Get the slug value
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * Check equality
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * String representation
     */
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Normalize slug value
     */
    private function normalize(string $value): string
    {
        // Convert to lowercase and trim
        $value = mb_strtolower(trim($value));

        // Replace multiple dashes with single dash
        $value = preg_replace('/-+/', '-', $value);

        // Remove leading/trailing dashes
        return trim($value, '-');
    }

    /**
     * Validate slug format
     *
     * @throws InvalidArgumentException
     */
    private function validate(string $value): void
    {
        if (empty($value)) {
            throw new InvalidArgumentException('Slug cannot be empty');
        }

        if (mb_strlen($value) > 255) {
            throw new InvalidArgumentException('Slug cannot exceed 255 characters');
        }

        // Allow alphanumeric, dashes, and Unicode letters (for Arabic)
        if (! preg_match('/^[\p{L}\p{N}\-]+$/u', $value)) {
            throw new InvalidArgumentException('Slug contains invalid characters');
        }
    }

    /**
     * Slugify Unicode text (Arabic, etc.)
     */
    private static function slugifyUnicode(string $text): string
    {
        // Remove special characters but keep Unicode letters
        $slug = preg_replace('/[^\p{L}\p{N}\s\-]/u', '', $text);

        // Replace spaces with dashes
        $slug = preg_replace('/[\s_]+/', '-', $slug);

        // Remove multiple dashes
        $slug = preg_replace('/-+/', '-', $slug);

        return trim($slug, '-');
    }
}
