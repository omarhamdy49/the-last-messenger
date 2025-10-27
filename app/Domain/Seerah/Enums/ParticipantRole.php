<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Enums;

/**
 * Participant Role Enum
 * Represents roles of participants in timeline events
 */
enum ParticipantRole: string
{
    case PROPHET = 'prophet';
    case COMPANION = 'companion';
    case ENEMY = 'enemy';
    case ALLY = 'ally';
    case WITNESS = 'witness';
    case OTHER = 'other';

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
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::PROPHET => 'Prophet',
            self::COMPANION => 'Companion',
            self::ENEMY => 'Enemy',
            self::ALLY => 'Ally',
            self::WITNESS => 'Witness',
            self::OTHER => 'Other',
        };
    }

    /**
     * Get Arabic label
     *
     * @return string
     */
    public function labelArabic(): string
    {
        return match ($this) {
            self::PROPHET => 'النبي ﷺ',
            self::COMPANION => 'صحابي',
            self::ENEMY => 'عدو',
            self::ALLY => 'حليف',
            self::WITNESS => 'شاهد',
            self::OTHER => 'آخر',
        };
    }
}

