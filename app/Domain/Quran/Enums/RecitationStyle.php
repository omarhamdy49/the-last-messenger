<?php

declare(strict_types=1);

namespace App\Domain\Quran\Enums;

enum RecitationStyle: string
{
    case MURATTAL = 'murattal';
    case MUJAWWAD = 'mujawwad';
    case MUALLIM = 'muallim';

    /**
     * Normalise QF (`Murattal`), mp3quran moshaf names (`مرتل`, `مجود`, `معلم`) and
     * free text. Returns null rather than guessing.
     */
    public static function fromExternal(?string $value): ?self
    {
        $value = mb_strtolower(trim((string) $value));
        if ($value === '') {
            return null;
        }

        return match (true) {
            str_contains($value, 'mujawwad') || str_contains($value, 'mujawad') || str_contains($value, 'مجود') => self::MUJAWWAD,
            str_contains($value, 'muallim') || str_contains($value, "mu'allim") || str_contains($value, 'معلم') => self::MUALLIM,
            str_contains($value, 'murattal') || str_contains($value, 'murattil') || str_contains($value, 'مرتل') => self::MURATTAL,
            default => null,
        };
    }

    public function label(?string $locale = null): string
    {
        return (string) __('quran.styles.'.$this->value, [], $locale);
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
