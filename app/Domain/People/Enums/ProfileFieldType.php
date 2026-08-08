<?php

declare(strict_types=1);

namespace App\Domain\People\Enums;

/**
 * How one extended-profile field is stored, validated and rendered.
 *
 * Kept deliberately small. A field type earns its place here only when the
 * admin form needs to render it differently AND the validation rules differ —
 * anything else is a Text field with a different label.
 */
enum ProfileFieldType: string
{
    case Text = 'text';
    case LongText = 'long_text';
    case Number = 'number';

    /**
     * Validation rules for a single value of this type.
     *
     * @return list<string>
     */
    public function rules(?int $max): array
    {
        return match ($this) {
            self::Text => ['nullable', 'string', 'max:'.($max ?? 200)],
            self::LongText => ['nullable', 'string', 'max:'.($max ?? 2000)],
            // Signed on purpose: a negative Gregorian year is BCE, which the
            // ancestry above Adnan legitimately reaches.
            self::Number => ['nullable', 'integer', 'min:-5000', 'max:5000'],
        };
    }

    /** Cast an incoming form value to what should be stored. */
    public function coerce(mixed $value): string|int|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this === self::Number ? (int) $value : trim((string) $value);
    }
}
