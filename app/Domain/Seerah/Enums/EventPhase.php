<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Enums;

/**
 * Event Phase Enum
 * Represents the timeline navigation phase used by the public Seerah timeline UI.
 */
enum EventPhase: string
{
    case EARLY_LIFE = 'early_life';
    case MECCA = 'mecca';
    case HIJRA = 'hijra';
    case FINAL_YEARS = 'final_years';

    /**
     * Get all values
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get label for display
     */
    public function label(): string
    {
        return match ($this) {
            self::EARLY_LIFE => 'Early Life',
            self::MECCA => 'Mecca',
            self::HIJRA => 'Hijra & Madinah',
            self::FINAL_YEARS => 'Final Years',
        };
    }

    /**
     * Get Arabic label
     */
    public function labelArabic(): string
    {
        return match ($this) {
            self::EARLY_LIFE => 'النشأة الأولى',
            self::MECCA => 'العهد المكي',
            self::HIJRA => 'الهجرة وبناء الدولة',
            self::FINAL_YEARS => 'السنوات الأخيرة',
        };
    }
}
