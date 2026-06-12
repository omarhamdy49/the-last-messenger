<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Enums;

/**
 * Event Era Enum
 * Represents the broad era of the Prophet's ﷺ life a timeline event belongs to.
 */
enum EventEra: string
{
    case PRE_BIRTH = 'pre_birth';
    case CHILDHOOD = 'childhood';
    case PRE_REVELATION = 'pre_revelation';
    case MECCA = 'mecca';
    case MEDINA = 'medina';

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
            self::PRE_BIRTH => 'Pre-birth',
            self::CHILDHOOD => 'Childhood',
            self::PRE_REVELATION => 'Pre-revelation',
            self::MECCA => 'Meccan Era',
            self::MEDINA => 'Medinan Era',
        };
    }

    /**
     * Get Arabic label
     */
    public function labelArabic(): string
    {
        return match ($this) {
            self::PRE_BIRTH => 'ما قبل المولد',
            self::CHILDHOOD => 'الطفولة والنشأة',
            self::PRE_REVELATION => 'ما قبل البعثة',
            self::MECCA => 'العهد المكي',
            self::MEDINA => 'العهد المدني',
        };
    }
}
