<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Enums;

/**
 * Event Importance Enum
 * Represents the significance level of timeline events
 */
enum EventImportance: string
{
    case HIGH = 'high';
    case MEDIUM = 'medium';
    case LOW = 'low';

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
            self::HIGH => 'High Importance',
            self::MEDIUM => 'Medium Importance',
            self::LOW => 'Low Importance',
        };
    }

    /**
     * Get Arabic label
     */
    public function labelArabic(): string
    {
        return match ($this) {
            self::HIGH => 'أهمية عالية',
            self::MEDIUM => 'أهمية متوسطة',
            self::LOW => 'أهمية منخفضة',
        };
    }

    /**
     * Get numeric weight for sorting
     */
    public function weight(): int
    {
        return match ($this) {
            self::HIGH => 3,
            self::MEDIUM => 2,
            self::LOW => 1,
        };
    }
}
