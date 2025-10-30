<?php

declare(strict_types=1);

namespace App\Domain\Quran\Enums;

enum SajdaType: string
{
    case RECOMMENDED = 'recommended';
    case OBLIGATORY = 'obligatory';

    /**
     * Get label in English
     */
    public function label(): string
    {
        return match ($this) {
            self::RECOMMENDED => 'Recommended',
            self::OBLIGATORY => 'Obligatory',
        };
    }

    /**
     * Get label in Arabic
     */
    public function labelArabic(): string
    {
        return match ($this) {
            self::RECOMMENDED => 'مستحبة',
            self::OBLIGATORY => 'واجبة',
        };
    }
}
