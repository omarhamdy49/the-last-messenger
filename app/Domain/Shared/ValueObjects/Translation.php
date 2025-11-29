<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObjects;

use ArrayAccess;
use InvalidArgumentException;
use JsonSerializable;

/**
 * Value Object for Multi-Language Text
 *
 * Immutable container for translated content.
 * Supports Arabic (ar), English (en), Turkish (tr), French (fr), Urdu (ur).
 *
 * @implements ArrayAccess<string, string|null>
 */
final readonly class Translation implements ArrayAccess, JsonSerializable
{
    public const SUPPORTED_LOCALES = ['ar', 'en', 'tr', 'fr', 'ur'];

    public const DEFAULT_LOCALE = 'ar';

    /**
     * @param array<string, string|null> $translations
     */
    public function __construct(
        private array $translations
    ) {
        $this->validate($translations);
    }

    /**
     * Create from array of translations
     *
     * @param array<string, string|null> $data
     */
    public static function fromArray(array $data): self
    {
        $translations = [];

        foreach (self::SUPPORTED_LOCALES as $locale) {
            $translations[$locale] = $data[$locale] ?? null;
        }

        return new self($translations);
    }

    /**
     * Create with single translation
     */
    public static function forLocale(string $locale, string $text): self
    {
        return new self([$locale => $text]);
    }

    /**
     * Create empty translation
     */
    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * Get translation for locale
     */
    public function get(string $locale, ?string $fallback = null): ?string
    {
        // Return direct match
        if (isset($this->translations[$locale]) && $this->translations[$locale] !== null) {
            return $this->translations[$locale];
        }

        // Try fallback
        if ($fallback !== null) {
            return $fallback;
        }

        // Try default locale
        if ($locale !== self::DEFAULT_LOCALE && isset($this->translations[self::DEFAULT_LOCALE])) {
            return $this->translations[self::DEFAULT_LOCALE];
        }

        // Return first available translation
        foreach ($this->translations as $translation) {
            if ($translation !== null) {
                return $translation;
            }
        }

        return null;
    }

    /**
     * Get Arabic translation
     */
    public function arabic(): ?string
    {
        return $this->get('ar');
    }

    /**
     * Get English translation
     */
    public function english(): ?string
    {
        return $this->get('en');
    }

    /**
     * Set/update a translation (returns new instance)
     */
    public function with(string $locale, string $text): self
    {
        $translations = $this->translations;
        $translations[$locale] = $text;

        return new self($translations);
    }

    /**
     * Check if translation exists for locale
     */
    public function has(string $locale): bool
    {
        return isset($this->translations[$locale]) && $this->translations[$locale] !== null;
    }

    /**
     * Check if any translation exists
     */
    public function hasAny(): bool
    {
        foreach ($this->translations as $translation) {
            if ($translation !== null && trim($translation) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all translations
     *
     * @return array<string, string|null>
     */
    public function all(): array
    {
        return $this->translations;
    }

    /**
     * Get only non-null translations
     *
     * @return array<string, string>
     */
    public function filled(): array
    {
        return array_filter($this->translations, fn ($v) => $v !== null);
    }

    /**
     * Get available locales
     *
     * @return array<string>
     */
    public function availableLocales(): array
    {
        return array_keys($this->filled());
    }

    /**
     * Check equality
     */
    public function equals(self $other): bool
    {
        return $this->translations === $other->translations;
    }

    /**
     * Validate translations
     *
     * @param array<string, string|null> $translations
     * @throws InvalidArgumentException
     */
    private function validate(array $translations): void
    {
        foreach (array_keys($translations) as $locale) {
            if (! in_array($locale, self::SUPPORTED_LOCALES, true)) {
                throw new InvalidArgumentException("Unsupported locale: {$locale}");
            }
        }
    }

    // ArrayAccess implementation

    public function offsetExists(mixed $offset): bool
    {
        return $this->has((string) $offset);
    }

    public function offsetGet(mixed $offset): ?string
    {
        return $this->get((string) $offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \BadMethodCallException('Translation is immutable');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \BadMethodCallException('Translation is immutable');
    }

    /**
     * @return array<string, string|null>
     */
    public function jsonSerialize(): array
    {
        return $this->translations;
    }
}
