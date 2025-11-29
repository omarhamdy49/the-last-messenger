<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObjects;

use InvalidArgumentException;
use Stringable;

/**
 * Base Value Object for Entity Identifiers
 *
 * Provides type-safe identifier handling with validation.
 * Extend this class for domain-specific IDs.
 */
abstract readonly class EntityId implements Stringable
{
    /**
     * @param int|string $value The identifier value
     * @throws InvalidArgumentException If value is invalid
     */
    public function __construct(
        protected int|string $value
    ) {
        $this->validate($value);
    }

    /**
     * Create from integer ID
     */
    public static function fromInt(int $id): static
    {
        return new static($id);
    }

    /**
     * Create from string ID
     */
    public static function fromString(string $id): static
    {
        if (is_numeric($id)) {
            return new static((int) $id);
        }

        return new static($id);
    }

    /**
     * Get the raw value
     */
    public function value(): int|string
    {
        return $this->value;
    }

    /**
     * Get value as integer (throws if string-based)
     *
     * @throws InvalidArgumentException If value is not numeric
     */
    public function toInt(): int
    {
        if (is_string($this->value) && ! is_numeric($this->value)) {
            throw new InvalidArgumentException('Cannot convert non-numeric ID to integer');
        }

        return (int) $this->value;
    }

    /**
     * Check equality with another ID
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value && static::class === $other::class;
    }

    /**
     * String representation
     */
    public function __toString(): string
    {
        return (string) $this->value;
    }

    /**
     * Validate the ID value
     *
     * @throws InvalidArgumentException If validation fails
     */
    protected function validate(int|string $value): void
    {
        if (is_int($value) && $value <= 0) {
            throw new InvalidArgumentException('Entity ID must be a positive integer');
        }

        if (is_string($value) && trim($value) === '') {
            throw new InvalidArgumentException('Entity ID cannot be empty');
        }
    }
}
