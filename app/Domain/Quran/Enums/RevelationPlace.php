<?php

declare(strict_types=1);

namespace App\Domain\Quran\Enums;

enum RevelationPlace: string
{
    case MAKKAH = 'makkah';
    case MADINAH = 'madinah';

    /**
     * Get label in English
     */
    public function label(): string
    {
        return match ($this) {
            self::MAKKAH => 'Makkan',
            self::MADINAH => 'Madinan',
        };
    }

    /**
     * Get label in Arabic
     */
    public function labelArabic(): string
    {
        return match ($this) {
            self::MAKKAH => 'مكية',
            self::MADINAH => 'مدنية',
        };
    }
}
