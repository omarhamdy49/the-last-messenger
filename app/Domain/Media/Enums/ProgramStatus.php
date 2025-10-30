<?php

declare(strict_types=1);

namespace App\Domain\Media\Enums;

/**
 * Program Status Enum
 * Represents the current state of a media program
 */
enum ProgramStatus: string
{
    case ONGOING = 'ongoing';
    case COMPLETED = 'completed';
    case PAUSED = 'paused';

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
            self::ONGOING => 'Ongoing',
            self::COMPLETED => 'Completed',
            self::PAUSED => 'Paused',
        };
    }

    /**
     * Get Arabic label
     */
    public function labelArabic(): string
    {
        return match ($this) {
            self::ONGOING => 'جاري',
            self::COMPLETED => 'مكتمل',
            self::PAUSED => 'متوقف',
        };
    }

    /**
     * Get color for UI
     */
    public function color(): string
    {
        return match ($this) {
            self::ONGOING => 'blue',
            self::COMPLETED => 'green',
            self::PAUSED => 'yellow',
        };
    }
}
