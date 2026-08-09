<?php

declare(strict_types=1);

namespace App\Domain\Quran\Enums;

/**
 * Where a surah was revealed.
 *
 * The single source of truth for this vocabulary. The stored values are the
 * lowercase `makkah` / `madinah` written by the migration and relied on by
 * Surah::scopeMakki/scopeMadani and the admin statistics — import paths that
 * previously wrote `Mecca` / `Medina` must normalise through here.
 */
enum RevelationPlace: string
{
    case MAKKAH = 'makkah';
    case MADINAH = 'madinah';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Resolve the many spellings that arrive from external datasets
     * (meccan/makki/mecca/…) to a case, falling back to Makkah.
     */
    public static function fromExternal(?string $value): self
    {
        return match (strtolower(trim((string) $value))) {
            'madinah', 'medinah', 'medina', 'madina', 'medinan', 'madani', 'madaniyyah' => self::MADINAH,
            default => self::MAKKAH,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::MAKKAH => 'مكية',
            self::MADINAH => 'مدنية',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::MAKKAH => 'Meccan',
            self::MADINAH => 'Medinan',
        };
    }
}
